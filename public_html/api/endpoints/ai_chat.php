<?php
declare(strict_types=1);

use NyitottMult\AiService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$question = trim($input['question'] ?? ($input['q'] ?? ''));

if (empty($question)) {
    http_response_code(400);
    echo json_encode(['error' => 'Question is required']);
    exit;
}

$result = AiService::askHistorian($question);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
