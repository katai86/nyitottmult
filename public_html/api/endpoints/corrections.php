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
    // Értesítő e-mail küldése az adminisztrátornak
    $adminMail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'hello@kataiattila.hu';
    $subjectAdmin = "[Nyitott Múlt] Új helyesbítési kérelem érkezett (ID: " . substr($id, 0, 8) . ")";
    $bodyAdmin = "<h2>Új törvényes helyesbítési kérelem érkezett</h2>"
        . "<p><strong>Kérelmező:</strong> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")</p>"
        . "<p><strong>Érintettség / Jogcím:</strong> " . htmlspecialchars($rel) . "</p>"
        . "<p><strong>Kérelem típusa:</strong> " . htmlspecialchars($type) . "</p>"
        . "<p><strong>Törvényes jogalap (2026. évi LI. tv.):</strong> " . htmlspecialchars($ground) . "</p>"
        . "<p><strong>Ügyintézési határidő (30 nap):</strong> " . htmlspecialchars($deadline) . "</p>"
        . "<hr><p><strong>Nyilatkozat tartalma:</strong><br>" . nl2br(htmlspecialchars($statement)) . "</p>";
    \NyitottMult\MailService::send($adminMail, $subjectAdmin, $bodyAdmin);

    // Visszaigazolás a beadványozónak
    $subjectUser = "[Nyitott Múlt] Helyesbítési kérelem iktatva (Ügyszám: " . substr($id, 0, 8) . ")";
    $bodyUser = "<h2>Tisztelt " . htmlspecialchars($name) . "!</h2>"
        . "<p>A 2026. évi LI. törvény alapján benyújtott helyesbítési kérelmét rendszerünk sikeresen iktatta.</p>"
        . "<p><strong>Iktatási azonosító:</strong> <code>{$id}</code></p>"
        . "<p><strong>Törvényes határidő:</strong> {$deadline}</p>"
        . "<p>A kutatóbizottság a törvényben meghatározott határidőn belül felülvizsgálja kérelmét, és ezen az e-mail címen tájékoztatja az eredményről.</p>"
        . "<hr><p style='color: #64748b; font-size: 12px;'>Nyitott Múlt – Orosháza titkos múltja kutatóplatform</p>";
    \NyitottMult\MailService::send($email, $subjectUser, $bodyUser, $name);

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
