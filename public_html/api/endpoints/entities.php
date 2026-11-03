<?php
declare(strict_types=1);

use NyitottMult\Database;
use NyitottMult\PrivacyEngine;

$method = $_SERVER['REQUEST_METHOD'];
$parts = explode('/', trim($uri, '/'));
$entityId = $parts[2] ?? null;

if ($method === 'GET') {
    if ($entityId) {
        $raw = Database::queryOne(
            "SELECT e.*, pr.status as privacy_status, pr.death_year, pr.is_publicly_visible 
             FROM entities e 
             LEFT JOIN privacy_records pr ON e.id = pr.entity_id 
             WHERE e.id = :id",
            ['id' => $entityId]
        );

        if (!$raw) {
            http_response_code(404);
            echo json_encode(['error' => 'Entity not found']);
            exit;
        }

        $entity = PrivacyEngine::sanitizeEntityForPublic($raw, [
            'status' => $raw['privacy_status'],
            'death_year' => $raw['death_year'],
            'is_publicly_visible' => $raw['is_publicly_visible']
        ]);

        if (!empty($entity['is_protected'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Protected person under Hungarian Act LI of 2026']);
            exit;
        }

        $roles = Database::query("SELECT r.*, d.archive_code, d.title as doc_title FROM roles r JOIN source_documents d ON r.document_id = d.id WHERE r.entity_id = :id", ['id' => $entityId]);
        $claims = Database::query("SELECT c.*, d.archive_code FROM claims c JOIN source_documents d ON c.document_id = d.id WHERE c.subject_entity_id = :id AND c.status = 'ellenorzott'", ['id' => $entityId]);

        $entity['roles'] = $roles;
        $entity['claims'] = $claims;

        echo json_encode(['data' => $entity], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $raw = Database::query(
            "SELECT e.*, pr.status as privacy_status, pr.death_year, pr.is_publicly_visible 
             FROM entities e 
             LEFT JOIN privacy_records pr ON e.id = pr.entity_id 
             ORDER BY e.canonical_name ASC LIMIT 100"
        );

        $list = [];
        foreach ($raw as $item) {
            $sanitized = PrivacyEngine::sanitizeEntityForPublic($item, [
                'status' => $item['privacy_status'],
                'death_year' => $item['death_year'],
                'is_publicly_visible' => $item['is_publicly_visible']
            ]);
            $list[] = $sanitized;
        }

        echo json_encode(['data' => $list, 'count' => count($list)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
