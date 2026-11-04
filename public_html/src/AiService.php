<?php
/**
 * Nyitott Múlt – AI Történész és Szemantikus Levéltári Kereső Szolgáltatás
 * 
 * Szigorúan forrásalapú RAG (Retrieval-Augmented Generation):
 * - Kizárólag az orosházi levéltári forrásokból és adatbázisból válaszol
 * - Minden állításnál kötelező a pontos levéltári forrásjelzet és oldalszám
 * - Ha nincs forrás: "Erre a kérdésre a rendelkezésre álló orosházi levéltári dokumentumokban nincs forrás."
 */

declare(strict_types=1);

namespace NyitottMult;

class AiService
{
    /**
     * Kérdés megválaszolása a levéltári források alapján
     */
    public static function askHistorian(string $question): array
    {
        $question = trim($question);
        if (empty($question)) {
            return [
                'answer' => __('ai.no_source_answer'),
                'sources' => [],
                'status' => 'no_query'
            ];
        }

        // 1. Keresőszavak tisztítása és előkészítése (írásjelek eltávolítása, stop szavak kiszűrése)
        $cleanQuestion = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $question);
        $rawWords = array_filter(explode(' ', mb_strtolower($cleanQuestion)), fn($w) => mb_strlen($w) >= 3);

        $stopwords = ['ki', 'volt', 'voltak', 'milyen', 'hol', 'mikor', 'hogy', 'miert', 'miért', 'tudsz', 'valamit', 'mondani', 'mesélj', 'szerint', 'lehet', 'orosháza', 'orosházi', 'akták', 'akta', 'irat', 'iratok', 'ember', 'emberek', 'személy', 'személyek', 'valaki'];
        $keywords = array_values(array_filter($rawWords, fn($w) => !in_array($w, $stopwords, true)));

        if (empty($keywords)) {
            $keywords = array_values($rawWords);
        }

        if (empty($keywords)) {
            return [
                'answer' => __('ai.no_source_answer'),
                'sources' => [],
                'status' => 'no_keywords'
            ];
        }

        // 2. Keresőszavak tágítása: magyar ragozás levágása (stemming) és ékezet-normalizálás
        $searchTerms = [];
        foreach ($keywords as $kw) {
            $searchTerms[] = $kw;
            $stem = self::stemHungarian($kw);
            $searchTerms[] = $stem;
            $searchTerms[] = self::stripAccents($kw);
            $searchTerms[] = self::stripAccents($stem);
        }
        $searchTerms = array_values(array_unique(array_filter($searchTerms, fn($t) => mb_strlen($t) >= 3)));

        // 3. Kontextus és releváns adatok kinyerése az adatbázisból
        $context = self::retrieveArchivalContext($searchTerms, $question);

        if (empty($context['claims']) && empty($context['entities'])) {
            return [
                'answer' => __('ai.no_source_answer'),
                'sources' => [],
                'status' => 'no_matching_documents'
            ];
        }

        // 4. Külső Gemini API hívása, ha van érvényes API kulcs
        $geminiKey = defined('GEMINI_API_KEY') ? trim(GEMINI_API_KEY) : '';
        if (!empty($geminiKey)) {
            $systemPrompt = "Te a 'Nyitott Múlt - Orosháza titkos múltja' elnevezésű helytörténeti platform szigorúan forrásalapú levéltári AI kutatóasszisztense vagy.\n"
                . "SZABÁLYOK:\n"
                . "1. Kizárólag a lent megadott HITELESÍTETT LEVÉLTÁRI FORRÁSOK alapján válaszolhatsz.\n"
                . "2. Minden egyes kijelentésed végére KÖTELEZŐ kitenni a pontos forráshivatkozást, pl. [ÁBTL-3.1.2-M-123, 14. o.]!\n"
                . "3. Ha a források nem tartalmaznak választ a feltett kérdésre, KIZÁRÓLAG ezt a választ adhatod szó szerint: '" . __('ai.no_source_answer') . "'\n"
                . "4. Találgatni, kikövetkeztetni nem létező adatot SZIGORÚAN TILOS!\n"
                . "5. Az aktában való puszta említés önmagában NEM jelent ügynöki múltat; mindig pontosan nevezd meg a szerepkört (megfigyelt, hivatásos tiszt, beszervezett informátor, egyéb említett).";

            $userPrompt = "KÉRDÉS: " . $question . "\n\n"
                . "RENDELKEZÉSRE ÁLLÓ FORRÁSOK:\n"
                . $context['formatted_text'] . "\n\n"
                . "VÁLASZ:";

            $apiAnswer = self::callGeminiApi($systemPrompt, $userPrompt, $geminiKey);
            if (!empty($apiAnswer)) {
                return [
                    'answer' => $apiAnswer,
                    'sources' => $context['sources'],
                    'status' => 'gemini_success'
                ];
            }
        }

        // 5. Intelligens helyi levéltári válaszképző (Rule-based Archival Engine)
        $localAnswer = self::synthesizeLocalAnswer($context, $question);

        return [
            'answer' => $localAnswer,
            'sources' => $context['sources'],
            'status' => 'local_rag_success'
        ];
    }

    /**
     * Hitelesített adatok és kontextus keresése (kifejezések, ragozatlan szótövek és ékezetmentes egyezés)
     */
    private static function retrieveArchivalContext(array $searchTerms, string $origQuery): array
    {
        $claims = [];
        $entities = [];
        $sources = [];
        $formattedText = "";

        // 1. Állítások keresése (Claims)
        $allClaims = Database::query(
            "SELECT c.*, d.archive_code, d.title as doc_title, p.page_number
             FROM claims c
             JOIN source_documents d ON c.document_id = d.id
             JOIN pages p ON c.page_id = p.id
             WHERE c.status = 'ellenorzott'
             ORDER BY c.created_at ASC"
        );

        foreach ($allClaims as $c) {
            $haystack = ' ' . mb_strtolower($c['quote'] . ' ' . $c['statement'] . ' ' . $c['archive_code'] . ' ' . $c['doc_title']) . ' ';
            $haystackNorm = self::stripAccents($haystack);

            $match = false;
            foreach ($searchTerms as $term) {
                if (preg_match('/(?:\b|[^\p{L}\p{N}])' . preg_quote($term, '/') . '(?:\b|[^\p{L}\p{N}])/ui', $haystack)
                    || preg_match('/(?:\b|[^\p{L}\p{N}])' . preg_quote($term, '/') . '(?:\b|[^\p{L}\p{N}])/ui', $haystackNorm)) {
                    $match = true;
                    break;
                }
            }

            if ($match) {
                $claims[] = $c;
                $ref = "[{$c['archive_code']}, {$c['page_number']}. o.]";
                $sources[] = [
                    'archive_code' => $c['archive_code'],
                    'page_number' => $c['page_number'],
                    'title' => $c['doc_title'],
                    'quote' => $c['quote']
                ];
                $formattedText .= "Forrás {$ref}:\n"
                    . "Dokumentum: {$c['doc_title']}\n"
                    . "Állítás: {$c['statement']}\n"
                    . "Idézet: \"{$c['quote']}\"\n\n";
            }
        }

        // 2. Entitások és szerepek keresése (Entities & Roles)
        $allEntities = Database::query(
            "SELECT e.*, r.role_type, d.archive_code, d.title as doc_title
             FROM entities e
             LEFT JOIN roles r ON e.id = r.entity_id
             LEFT JOIN source_documents d ON r.document_id = d.id"
        );

        foreach ($allEntities as $ent) {
            $haystack = ' ' . mb_strtolower($ent['canonical_name'] . ' ' . $ent['aliases']) . ' ';
            $haystackNorm = self::stripAccents($haystack);

            $match = false;
            foreach ($searchTerms as $term) {
                if (preg_match('/(?:\b|[^\p{L}\p{N}])' . preg_quote($term, '/') . '(?:\b|[^\p{L}\p{N}])/ui', $haystack)
                    || preg_match('/(?:\b|[^\p{L}\p{N}])' . preg_quote($term, '/') . '(?:\b|[^\p{L}\p{N}])/ui', $haystackNorm)) {
                    $match = true;
                    break;
                }
            }
            if ($match) {
                $entities[] = $ent;
            }
        }

        return [
            'claims' => $claims,
            'entities' => $entities,
            'sources' => array_values(array_unique($sources, SORT_REGULAR)),
            'formatted_text' => $formattedText
        ];
    }

    /**
     * Hiteles levéltári válasz szintetizálása külső API nélkül
     */
    private static function synthesizeLocalAnswer(array $context, string $question): string
    {
        $claims = $context['claims'];
        $entities = $context['entities'];

        if (empty($claims) && empty($entities)) {
            return __('ai.no_source_answer');
        }

        $lines = [];
        $lines[] = "A rendelkezésre álló orosházi levéltári iratok és ellenőrzött állítások alapján:";
        $lines[] = "";

        if (!empty($claims)) {
            foreach ($claims as $i => $c) {
                $ref = "[{$c['archive_code']}, {$c['page_number']}. o.]";
                $num = ($i + 1) . ".";
                $lines[] = "{$num} **Állítás:** " . $c['statement'] . " " . $ref;
                $lines[] = "   *Szó szerinti idézet az aktából:* »" . $c['quote'] . "« " . $ref;
                $lines[] = "";
            }
        } elseif (!empty($entities)) {
            $ent = $entities[0];
            $meta = json_decode($ent['metadata'] ?? '{}', true) ?: [];
            $roleLabel = !empty($ent['role_type']) ? __("roles.{$ent['role_type']}") : "Ismert orosházi személy/intézmény";

            $lines[] = "**Név/Megnevezés:** " . $ent['canonical_name'];
            $lines[] = "**Szerepkör a forrásokban:** " . $roleLabel;
            if (!empty($meta['occupation'])) {
                $lines[] = "**Foglalkozás:** " . $meta['occupation'];
            }
            if (!empty($ent['archive_code'])) {
                $lines[] = "**Kapcsolódó irat:** " . $ent['archive_code'] . " – " . ($ent['doc_title'] ?? '');
            }
        }

        $lines[] = "> ℹ️ *Módszertani alapelv:* Az iratokban való szereplés önmagában soha nem jelent ügynöki minősítést. A megfigyelt személyek az állambiztonsági megfigyelés áldozatai.";

        return implode("\n", $lines);
    }

    /**
     * Magyar ragozási végződések eltávolítása egyszerű szótő képzéshez
     */
    private static function stemHungarian(string $word): string
    {
        $suffixes = [
            'ról', 'ről', 'rol', 'rel',
            'val', 'vel',
            'ban', 'ben',
            'nak', 'nek',
            'hoz', 'hez', 'höz',
            'nál', 'nél', 'nal', 'nel',
            'tól', 'től', 'tol', 'tel',
            'ért', 'ert',
            'ból', 'ből', 'bol', 'bel',
            'on', 'en', 'ön',
            'ot', 'et', 'öt', 'at',
            'ra', 're'
        ];
        foreach ($suffixes as $s) {
            if (mb_strlen($word) > mb_strlen($s) + 2 && str_ends_with($word, $s)) {
                return mb_substr($word, 0, mb_strlen($word) - mb_strlen($s));
            }
        }
        return $word;
    }

    /**
     * Ékezetek eltávolítása normalizált összehasonlításhoz
     */
    private static function stripAccents(string $str): string
    {
        $unaccent = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ö' => 'o', 'Ő' => 'o',
            'Ú' => 'u', 'Ü' => 'u', 'Ű' => 'u'
        ];
        return strtr($str, $unaccent);
    }

    /**
     * Gemini API hívás HTTP cURL alapon
     */
    private static function callGeminiApi(string $systemPrompt, string $userPrompt, string $apiKey): ?string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\n" . $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 800
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $json = json_decode($response, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
}
