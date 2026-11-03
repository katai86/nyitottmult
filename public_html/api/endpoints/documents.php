<?php
declare(strict_types=1);

use NyitottMult\Database;

$method = $_SERVER['REQUEST_METHOD'];
$parts = explode('/', trim($uri, '/'));
$docId = $parts[2] ?? null;

if ($method === 'GET') {
    if ($docId) {
        $doc = Database::queryOne("SELECT * FROM source_documents WHERE id = :id", ['id' => $docId]);
        if (!$doc) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            exit;
        }

        $pages = Database::query("SELECT id, page_number, thumbnail_path, ocr_confidence FROM pages WHERE document_id = :id ORDER BY page_number ASC", ['id' => $docId]);
        $doc['pages'] = $pages;

        echo json_encode(['data' => $doc], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $type = $_GET['type'] ?? null;
        $sql = "SELECT d.*, COUNT(p.id) as page_count FROM source_documents d LEFT JOIN pages p ON d.id = p.document_id WHERE 1=1";
        $params = [];
        if ($type) {
            $sql .= " AND d.document_type = :type";
            $params['type'] = $type;
        }
        $sql .= " GROUP BY d.id ORDER BY d.archive_code ASC";
        $docs = Database::query($sql, $params);

        echo json_encode(['data' => $docs, 'count' => count($docs)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
