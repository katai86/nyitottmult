<?php
declare(strict_types=1);

use NyitottMult\Database;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $claims = Database::query(
        "SELECT c.id, c.statement, c.quote, c.status, c.confidence_score, 
                d.archive_code, d.title as doc_title, p.page_number,
                e.canonical_name as subject_name,
                (SELECT COUNT(*) FROM claim_verifications cv WHERE cv.claim_id = c.id AND cv.decision = 'approved') as verifier_count
         FROM claims c
         JOIN source_documents d ON c.document_id = d.id
         JOIN pages p ON c.page_id = p.id
         LEFT JOIN entities e ON c.subject_entity_id = e.id
         WHERE c.status = 'ellenorzott'
         ORDER BY c.created_at DESC LIMIT 100"
    );

    echo json_encode(['data' => $claims, 'count' => count($claims)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
