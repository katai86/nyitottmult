<?php
declare(strict_types=1);

use NyitottMult\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name = trim($input['submitter_name'] ?? '');
$email = trim($input['submitter_email'] ?? '');
$type = $input['content_type'] ?? 'reminiscence';
$title = trim($input['title'] ?? '');
$desc = trim($input['description'] ?? '');
$gdpr = !empty($input['gdpr_consent']);

if (empty($name) || empty($email) || empty($title) || empty($desc) || !$gdpr) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields or GDPR consent']);
    exit;
}

$id = Database::generateUuid();
$ok = Database::execute(
    "INSERT INTO submissions (id, submitter_name, submitter_email, gdpr_consent, content_type, title, description, moderation_status, created_at)
     VALUES (:id, :name, :email, 1, :type, :title, :desc, 'pending', NOW())",
    [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'type' => $type,
        'title' => $title,
        'desc' => $desc
    ]
);

if ($ok) {
    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Submission received into moderation queue'], JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database save failed']);
}
