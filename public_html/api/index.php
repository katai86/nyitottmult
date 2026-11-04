<?php
/**
 * Nyitott Múlt – REST API Központi Belépési Pont és Router
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/i18n.php';
require_once dirname(__DIR__) . '/src/PrivacyEngine.php';
require_once dirname(__DIR__) . '/src/AiService.php';
require_once dirname(__DIR__) . '/src/Auth.php';

use NyitottMult\I18n;

I18n::init();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// Prefix eltávolítása: pl. /api/v1/... -> /v1/...
$prefix = '/api';
if (str_starts_with($uri, $prefix)) {
    $uri = substr($uri, strlen($prefix));
}
$uri = '/' . ltrim($uri, '/');

// OpenAPI specifikáció kiszolgálása
if ($uri === '/v1/openapi.json' || $uri === '/openapi.json') {
    $openapiFile = __DIR__ . '/openapi.json';
    if (file_exists($openapiFile)) {
        echo file_get_contents($openapiFile);
    } else {
        echo json_encode(['error' => 'OpenAPI spec not found']);
    }
    exit;
}

// Útvonalak lekezelése
if (str_starts_with($uri, '/v1/documents')) {
    require __DIR__ . '/endpoints/documents.php';
} elseif (str_starts_with($uri, '/v1/entities')) {
    require __DIR__ . '/endpoints/entities.php';
} elseif (str_starts_with($uri, '/v1/claims')) {
    require __DIR__ . '/endpoints/claims.php';
} elseif (str_starts_with($uri, '/v1/map')) {
    require __DIR__ . '/endpoints/map.php';
} elseif (str_starts_with($uri, '/v1/network/graph')) {
    require __DIR__ . '/endpoints/network.php';
} elseif (str_starts_with($uri, '/v1/search')) {
    require __DIR__ . '/endpoints/search.php';
} elseif (str_starts_with($uri, '/v1/ai/ask')) {
    require __DIR__ . '/endpoints/ai_chat.php';
} elseif (str_starts_with($uri, '/v1/export')) {
    require __DIR__ . '/endpoints/export.php';
} elseif (str_starts_with($uri, '/v1/submissions')) {
    require __DIR__ . '/endpoints/submissions.php';
} elseif (str_starts_with($uri, '/v1/corrections')) {
    require __DIR__ . '/endpoints/corrections.php';
} else {
    http_response_code(404);
    echo json_encode([
        'error' => 'API endpoint not found',
        'requested_uri' => $uri
    ]);
}
