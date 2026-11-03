<?php
declare(strict_types=1);

use NyitottMult\Database;
use NyitottMult\PrivacyEngine;

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$q = trim($input['query'] ?? ($input['q'] ?? ''));
$type = $input['type'] ?? '';
$role = $input['role'] ?? '';

$sql = "SELECT e.*, pr.status as privacy_status, pr.death_year, pr.is_publicly_visible,
               r.role_type, d.title as doc_title, d.archive_code
        FROM entities e
        LEFT JOIN privacy_records pr ON e.id = pr.entity_id
        LEFT JOIN roles r ON e.id = r.entity_id
        LEFT JOIN source_documents d ON r.document_id = d.id
        WHERE 1=1";

$params = [];

if ($q !== '') {
    $sql .= " AND (e.canonical_name LIKE :q OR e.aliases LIKE :q2 OR d.title LIKE :q3 OR d.archive_code LIKE :q4)";
    $params['q'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
    $params['q3'] = '%' . $q . '%';
    $params['q4'] = '%' . $q . '%';
}

if ($type !== '') {
    $sql .= " AND e.type = :type";
    $params['type'] = $type;
}

if ($role !== '') {
    $sql .= " AND r.role_type = :role";
    $params['role'] = $role;
}

$sql .= " ORDER BY e.canonical_name ASC LIMIT 50";
$rawResults = Database::query($sql, $params);

$results = [];
foreach ($rawResults as $row) {
    $results[] = PrivacyEngine::sanitizeEntityForPublic($row, [
        'status' => $row['privacy_status'],
        'death_year' => $row['death_year'],
        'is_publicly_visible' => $row['is_publicly_visible']
    ]);
}

echo json_encode(['results' => $results, 'count' => count($results)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
