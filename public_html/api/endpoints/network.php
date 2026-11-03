<?php
declare(strict_types=1);

use NyitottMult\Database;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Csak olyan relációk és entitások jelennek meg, amelyeket igazolt Claim támaszt alá
    $relations = Database::query(
        "SELECT rel.*, 
                s.canonical_name as source_name, s.type as source_type,
                t.canonical_name as target_name, t.type as target_type,
                c.quote, c.statement
         FROM relations rel
         JOIN entities s ON rel.source_entity_id = s.id
         JOIN entities t ON rel.target_entity_id = t.id
         JOIN claims c ON rel.claim_id = c.id
         WHERE c.status = 'ellenorzott'"
    );

    $nodes = [];
    $edges = [];

    foreach ($relations as $r) {
        if (!isset($nodes[$r['source_entity_id']])) {
            $nodes[$r['source_entity_id']] = [
                'data' => [
                    'id' => $r['source_entity_id'],
                    'label' => $r['source_name'],
                    'type' => $r['source_type']
                ]
            ];
        }

        if (!isset($nodes[$r['target_entity_id']])) {
            $nodes[$r['target_entity_id']] = [
                'data' => [
                    'id' => $r['target_entity_id'],
                    'label' => $r['target_name'],
                    'type' => $r['target_type']
                ]
            ];
        }

        $edges[] = [
            'data' => [
                'id' => $r['id'],
                'source' => $r['source_entity_id'],
                'target' => $r['target_entity_id'],
                'label' => $r['relation_type'],
                'quote' => $r['quote'],
                'statement' => $r['statement']
            ]
        ];
    }

    $elements = array_merge(array_values($nodes), $edges);

    echo json_encode(['elements' => $elements], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
