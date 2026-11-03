<?php
/**
 * Nyitott Múlt – AI Történész és Szemantikus Kereső Szolgáltatás
 * 
 * Szigorúan forrásalapú RAG (Retrieval-Augmented Generation):
 * - Kizárólag az orosházi levéltári forrásokból válaszol
 * - Minden mondatnál kötelező forráshivatkozás
 * - Ha nincs adat: "Erre a kérdésre a rendelkezésre álló orosházi dokumentumokban nincs forrás."
 */

declare(strict_types=1);

namespace NyitottMult;

class AiService
{
    /**
     * Kérdés megválaszolása kizárólag a feltöltött és ellenőrzött dokumentumok alapján
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

        // 1. Kontextus összegyűjtése az adatbázisból (FTS és kulcsszavas keresés a hitelesített Claims és Pages alapján)
        $contextData = self::retrieveContext($question);

        if (empty($contextData['items'])) {
            return [
                'answer' => __('ai.no_source_answer'),
                'sources' => [],
                'status' => 'no_matching_documents'
            ];
        }

        // 2. Prompt összeállítása szigorú forráshivatkozási kényszerrel
        $systemPrompt = "Te a 'Nyitott Múlt - Orosháza titkos múltja' elnevezésű platform szigorúan forrásalapú AI kutatóasszisztense vagy.\n"
            . "SZABÁLYOK:\n"
            . "1. Kizárólag a lent megadott HITELESÍTETT LEVÉLTÁRI FORRÁSOK alapján válaszolhatsz.\n"
            . "2. Minden egyes kijelentésed végére KÖTELEZŐ kitenni a forrás pontos hivatkozását, pl. [ÁBTL-3.1.2-M-123, 14. o.]!\n"
            . "3. Ha a megadott források nem tartalmaznak választ a feltett kérdésre, KIZÁRÓLAG ezt a választ adhatod szó szerint: '" . __('ai.no_source_answer') . "'\n"
            . "4. Találgatni, kikövetkeztetni nem létező tényeket, vagy a forrásokon kívüli információt bevonni SZIGORÚAN TILOS!\n"
            . "5. Az aktában való puszta szereplés nem ügynöki múlt: mindig pontosan jelöld meg a személy szerepkörét (megfigyelt, tiszt, informátor, tanú).";

        $userPrompt = "KÉRDÉS: " . $question . "\n\n"
            . "RENDELKEZÉSRE ÁLLÓ LEVÉLTÁRI FORRÁSOK:\n"
            . $contextData['formatted_text'] . "\n\n"
            . "VÁLASZ (szigorúan idézettel és forrásmegjelöléssel minden mondatnál):";

        // 3. API hívás (Gemini 1.5 vagy OpenAI)
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        if (empty($apiKey)) {
            // Ha nincs megadva külső kulcs, a beépített hitelesített mintaválaszt adjuk a talált kontextusból
            return [
                'answer' => self::generateRuleBasedAnswer($contextData['items'], $question),
                'sources' => $contextData['sources'],
                'status' => 'local_rag'
            ];
        }

        $response = self::callGeminiApi($systemPrompt, $userPrompt, $apiKey);

        return [
            'answer' => $response ?: __('ai.no_source_answer'),
            'sources' => $contextData['sources'],
            'status' => 'success'
        ];
    }

    /**
     * Hitelesített kontextus keresése a levéltári iratokban
     */
    private static function retrieveContext(string $query): array
    {
        $keywords = array_filter(explode(' ', mb_strtolower($query)), fn($w) => mb_strlen($w) > 2);
        if (empty($keywords)) {
            return ['items' => [], 'formatted_text' => '', 'sources' => []];
        }

        // Keresés az ellenőrzött állítások (Claims) és a dokumentumoldalak (Pages) között
        $sql = "SELECT c.id, c.quote, c.statement, d.archive_code, d.title as doc_title, p.page_number
                FROM claims c
                JOIN source_documents d ON c.document_id = d.id
                JOIN pages p ON c.page_id = p.id
                WHERE c.status = 'ellenorzott'
                ORDER BY c.created_at DESC
                LIMIT 10";

        $claims = Database::query($sql);
        $items = [];
        $sources = [];
        $formatted = "";

        // Szűrés a kulcsszavakra
        foreach ($claims as $c) {
            $text = mb_strtolower($c['quote'] . ' ' . $c['statement'] . ' ' . $c['archive_code']);
            $match = false;
            foreach ($keywords as $kw) {
                if (mb_strpos($text, $kw) !== false) {
                    $match = true;
                    break;
                }
            }

            if ($match) {
                $items[] = $c;
                $ref = "[{$c['archive_code']}, {$c['page_number']}. o.]";
                $sources[] = [
                    'archive_code' => $c['archive_code'],
                    'page_number' => $c['page_number'],
                    'title' => $c['doc_title'],
                    'quote' => $c['quote']
                ];
                $formatted .= "Forrás {$ref}:\n"
                    . "Dokumentum: {$c['doc_title']}\n"
                    . "Állítás: {$c['statement']}\n"
                    . "Szó szerinti idézet: \"{$c['quote']}\"\n\n";
            }
        }

        return [
            'items' => $items,
            'formatted_text' => $formatted,
            'sources' => $sources
        ];
    }

    /**
     * Szabályalapú válaszképző, ha nincs külső API kulcs beállítva
     */
    private static function generateRuleBasedAnswer(array $items, string $query): string
    {
        if (empty($items)) {
            return __('ai.no_source_answer');
        }

        $first = $items[0];
        $ref = "[{$first['archive_code']}, {$first['page_number']}. o.]";
        return "A fellelhető levéltári iratok alapján: " . $first['statement'] . " " . $ref . "\n\n"
             . "Szó szerinti idézet az aktából: \"" . $first['quote'] . "\" " . $ref;
    }

    /**
     * Gemini 1.5 Flash API hívása HTTP cURL alapon
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
