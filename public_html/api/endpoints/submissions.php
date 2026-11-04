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
    // Értesítő e-mail az adminisztrátornak az új lakossági beküldésről
    $adminMail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'hello@kataiattila.hu';
    $subjectAdmin = "[Nyitott Múlt] Új közösségi kutatási anyag érkezett";
    $bodyAdmin = "<h2>Új kutatási anyag beküldve moderációra</h2>"
        . "<p><strong>Beküldő:</strong> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")</p>"
        . "<p><strong>Típus:</strong> " . htmlspecialchars($type) . "</p>"
        . "<p><strong>Cím:</strong> " . htmlspecialchars($title) . "</p>"
        . "<hr><p><strong>Leírás / Visszaemlékezés:</strong><br>" . nl2br(htmlspecialchars($desc)) . "</p>";
    \NyitottMult\MailService::send($adminMail, $subjectAdmin, $bodyAdmin);

    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Submission received into moderation queue'], JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database save failed']);
}
