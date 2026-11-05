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

require_once __DIR__ . '/i18n.php';

class AiService
{
    /**
     * Nyelvi lefordító biztonsági wrapper
     */
    private static function t(string $key, array $params = []): string
    {
        if (function_exists('__')) {
            $val = \__($key, $params);
            if (!empty($val) && $val !== $key) {
                return $val;
            }
        }
        if ($key === 'ai.no_source_answer') {
            return 'Erre a kérdésre a rendelkezésre álló orosházi levéltári dokumentumokban nincs közvetlen forrás.';
        }
        return $key;
    }

    /**
     * Kérdés megválaszolása a levéltári források alapján
     */
    public static function askHistorian(string $question): array
    {
        $question = trim($question);
        if (empty($question)) {
            return [
                'answer' => self::t('ai.no_source_answer'),
                'sources' => [],
                'status' => 'no_query'
            ];
        }

        // 1. Keresőszavak tisztítása és előkészítése (írásjelek eltávolítása, stop szavak kiszűrése)
        $cleanQuestion = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $question);
        $rawWords = array_filter(explode(' ', mb_strtolower($cleanQuestion)), fn($w) => mb_strlen($w) >= 3);

        $stopwords = ['ki', 'volt', 'voltak', 'milyen', 'hol', 'mikor', 'hogy', 'miert', 'miért', 'tudsz', 'valamit', 'mondani', 'mesélj', 'szerint', 'lehet', 'akták', 'akta', 'irat', 'iratok', 'ember', 'emberek', 'személy', 'személyek', 'valaki'];
        $keywords = array_values(array_filter($rawWords, fn($w) => !in_array($w, $stopwords, true)));

        if (empty($keywords)) {
            $keywords = array_values($rawWords);
        }

        if (empty($keywords)) {
            return [
                'answer' => self::t('ai.no_source_answer'),
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
                'answer' => self::t('ai.no_source_answer'),
                'sources' => [],
                'status' => 'no_matching_documents'
            ];
        }

        // 4. Külső Gemini API hívása, ha van érvényes API kulcs
        $geminiKey = defined('GEMINI_API_KEY') ? trim(GEMINI_API_KEY) : '';
        if (!empty($geminiKey)) {
            $noSourceText = self::t('ai.no_source_answer');
            $systemPrompt = "Te a 'Nyitott Múlt - Orosháza titkos múltja' elnevezésű helytörténeti kutatóplatform szigorúan forrásalapú levéltári AI asszisztense vagy.\n"
                . "SZABÁLYOK:\n"
                . "1. Kizárólag a lent megadott HITELESÍTETT LEVÉLTÁRI FORRÁSOK alapján válaszolhatsz.\n"
                . "2. Minden egyes kijelentésed végére KÖTELEZŐ kitenni a pontos forráshivatkozást, pl. [ÁBTL - H-61836, 1. o.]!\n"
                . "3. Ha a megadott források nem tartalmaznak választ a feltett kérdésre, KIZÁRÓLAG ezt a választ adhatod szó szerint: '{$noSourceText}'\n"
                . "4. Találgatni, kikövetkeztetni nem létező adatot SZIGORÚAN TILOS!\n"
                . "5. Az aktában való puszta említés önmagában NEM jelent ügynöki múltat; mindig pontosan nevezd meg a szerepkört és hivatkozz a forrásra.";

            $userPrompt = "KÉRDÉS: " . $question . "\n\n"
                . "RENDELKEZÉSRE ÁLLÓ HITELES FORRÁSOK ÉS ADATBÁZIS BEJEGYZÉSEK:\n"
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
        $entities = [];
        $sources = [];
        $formattedText = "";

        // 1. Állítások keresése és pontozása (Claims Scoring)
        $allClaims = Database::query(
            "SELECT c.*, d.archive_code, d.title as doc_title, p.page_number
             FROM claims c
             JOIN source_documents d ON c.document_id = d.id
             JOIN pages p ON c.page_id = p.id
             WHERE c.status = 'ellenorzott'
             ORDER BY c.created_at ASC"
        );

        $origCleanNorm = self::stripAccents(mb_strtolower(preg_replace('/[?.,!;:\'\"()\[\]{}]/u', ' ', $origQuery)));
        $origCleanWords = array_values(array_filter(explode(' ', $origCleanNorm), fn($w) => mb_strlen($w) >= 3));

        $scoredClaims = [];
        foreach ($allClaims as $c) {
            $haystack = ' ' . mb_strtolower($c['quote'] . ' ' . $c['statement'] . ' ' . $c['archive_code'] . ' ' . $c['doc_title']) . ' ';
            $haystackNorm = self::stripAccents($haystack);

            $score = 0;
            // Teljes keresőkifejezés pontos egyezése (pl. "adász zoltán")
            if (!empty($origCleanNorm) && mb_strlen($origCleanNorm) >= 4 && str_contains($haystackNorm, $origCleanNorm)) {
                $score += 30;
            }

            // Kérdés szavainak egyezése
            $matchedWords = 0;
            foreach ($origCleanWords as $w) {
                if (preg_match('/(?:\b|[^\p{L}\p{N}])' . preg_quote($w, '/') . '(?:\b|[^\p{L}\p{N}])/ui', $haystackNorm)) {
                    $score += 8;
                    $matchedWords++;
                }
            }

            // Ha több kulcsszó volt, és mindegyik szerepel ebben az állításban: kiemelt bónusz
            if (count($origCleanWords) > 1 && $matchedWords === count($origCleanWords)) {
                $score += 25;
            }

            // Szótövek és szinonimák egyezése
            foreach ($searchTerms as $term) {
                if (preg_match('/(?:\b|[^\p{L}\p{N}])' . preg_quote($term, '/') . '(?:\b|[^\p{L}\p{N}])/ui', $haystack)
                    || preg_match('/(?:\b|[^\p{L}\p{N}])' . preg_quote($term, '/') . '(?:\b|[^\p{L}\p{N}])/ui', $haystackNorm)) {
                    $score += 2;
                }
            }

            if ($score > 0) {
                $c['_score'] = $score;
                $scoredClaims[] = $c;
            }
        }

        // Relevancia szerinti csökkenő rendezés
        usort($scoredClaims, fn($a, $b) => $b['_score'] <=> $a['_score']);

        // Legrelevánsabb állítások (max. 8) kiválasztása
        $topClaims = array_slice($scoredClaims, 0, 8);

        foreach ($topClaims as $c) {
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
                $meta = json_decode($ent['metadata'] ?? '{}', true) ?: [];
                $formattedText .= "Entitás adatlap:\n"
                    . "Név: {$ent['canonical_name']}\n"
                    . "Típus: {$ent['type']}\n";
                if (!empty($meta['workplace'])) $formattedText .= "Munkahely: {$meta['workplace']}\n";
                if (!empty($meta['address'])) $formattedText .= "Lakcím: {$meta['address']}\n";
                if (!empty($meta['education'])) $formattedText .= "Iskolai végzettség: {$meta['education']}\n";
                if (!empty($meta['recruiter_officer'])) $formattedText .= "Beszervező tiszt: {$meta['recruiter_officer']}\n";
                if (!empty($meta['recruitment_date'])) $formattedText .= "Beszervezés ideje: {$meta['recruitment_date']}\n";
                if (!empty($meta['operating_line'])) $formattedText .= "Foglalkoztatási vonal: {$meta['operating_line']}\n";
                if (!empty($ent['archive_code'])) $formattedText .= "Iratjelzet: {$ent['archive_code']}\n";
                $formattedText .= "\n";
            }
        }

        return [
            'claims' => $topClaims,
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
            return self::t('ai.no_source_answer');
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
            $roleLabel = !empty($ent['role_type']) ? self::t("roles.{$ent['role_type']}") : "Ismert orosházi személy/intézmény";

            $lines[] = "**Név:** " . $ent['canonical_name'];
            $lines[] = "**Szerepkör:** " . $roleLabel;
            if (!empty($meta['workplace'])) {
                $lines[] = "**Munkahely:** " . $meta['workplace'];
            }
            if (!empty($meta['address'])) {
                $lines[] = "**Lakcím:** " . $meta['address'];
            }
            if (!empty($meta['education'])) {
                $lines[] = "**Iskolai végzettség:** " . $meta['education'];
            }
            if (!empty($meta['recruiter_officer'])) {
                $lines[] = "**Beszervező tiszt:** " . $meta['recruiter_officer'];
            }
            if (!empty($meta['recruitment_date'])) {
                $lines[] = "**Beszervezés ideje:** " . $meta['recruitment_date'];
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
            'ra', 're',
            'név', 'neve', 'nevét', 'nevei',
            'ban', 'ben', 'ba', 'be'
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
     * Gemini API hívás HTTP cURL alapon a működő modellpoollal
     */
    private static function callGeminiApi(string $systemPrompt, string $userPrompt, string $apiKey): ?string
    {
        $models = [
            'gemini-3.1-flash-lite',
            'gemini-3.1-flash-lite-preview',
            'gemini-3.5-flash',
            'gemini-flash-lite-latest'
        ];

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
                'temperature' => 0.2,
                'maxOutputTokens' => 2048
            ]
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        foreach ($models as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($apiKey);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $json = json_decode($response, true);
                $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!empty($text)) {
                    return trim($text);
                }
            }
        }

        return null;
    }
}
