<?php
declare(strict_types=1);

use NyitottMult\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name = trim($input['applicant_name'] ?? '');
$email = trim($input['applicant_email'] ?? '');
$rel = trim($input['applicant_relationship'] ?? '');
$type = $input['request_type'] ?? 'kiegeszito_nyilatkozat';
$ground = trim($input['legal_ground'] ?? '');
$statement = trim($input['statement_text'] ?? '');

if (empty($name) || empty($email) || empty($ground) || empty($statement)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required legal fields']);
    exit;
}

$id = Database::generateUuid();
$deadline = date('Y-m-d', strtotime('+30 days'));

$ok = Database::execute(
    "INSERT INTO correction_requests (id, applicant_name, applicant_email, applicant_relationship, request_type, legal_ground, statement_text, status, deadline, created_at)
     VALUES (:id, :name, :email, :rel, :type, :ground, :stmt, 'beerkett', :deadline, NOW())",
    [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'rel' => $rel,
        'type' => $type,
        'ground' => $ground,
        'stmt' => $statement,
        'deadline' => $deadline
    ]
);

if ($ok) {
    echo json_encode([
        'success' => true,
        'case_id' => $id,
        'deadline' => $deadline,
        'message' => 'Correction request registered under Act LI of 2026'
    ], JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database save failed']);
}
