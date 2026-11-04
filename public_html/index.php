<?php
/**
 * Nyitott Múlt – Központi Belépési Pont
 *
 * Licenc: AGPL-3.0
 * Kezdeményező: Kátai Attila
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/i18n.php';
require_once __DIR__ . '/src/PrivacyEngine.php';
require_once __DIR__ . '/src/AuditLogger.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/AiService.php';
require_once __DIR__ . '/src/Router.php';

use NyitottMult\Router;
use NyitottMult\I18n;

I18n::init();

// Nyilvános útvonalak regisztrálása
Router::get('/', 'home');
Router::get('/terkep', 'map');
Router::get('/kereses', 'search');
Router::get('/dokumentumok', 'documents');
Router::get('/dokumentumok/{id}', 'document_view');
Router::get('/entitasok', 'search');
Router::get('/entitasok/{id}', 'entity_view');
Router::get('/bizonyitekterkep', 'evidence_map');
Router::get('/kapcsolati-halo', 'network');
Router::get('/idovonal', 'timeline');
Router::get('/ai-kutato', 'ai_historian');
Router::get('/ai-tortenesz', 'ai_historian');
Router::get('/tortenetek', 'stories');
Router::get('/kozosseg', 'community');
Router::post('/kozosseg', 'community');
Router::get('/helyesbites', 'correction');
Router::post('/helyesbites', 'correction');
Router::get('/atlathatosag', 'transparency');
Router::get('/api-docs', 'api_docs');
Router::get('/api-dokumentacio', 'api_docs');

// Zárt kutatói útvonalak
Router::get('/kutato', 'researcher/dashboard');
Router::post('/kutato', 'researcher/dashboard');
Router::get('/kutato/login', 'researcher/login');
Router::post('/kutato/login', 'researcher/login');
Router::get('/kutato/belepes', 'researcher/login');
Router::post('/kutato/belepes', 'researcher/login');
Router::get('/kutato/detektiv', 'researcher/detective');
Router::post('/kutato/detektiv', 'researcher/detective');
Router::get('/kutato/ellenorzes', 'researcher/verification');
Router::post('/kutato/ellenorzes', 'researcher/verification');
Router::get('/kutato/moderacio', 'researcher/moderation');
Router::post('/kutato/moderacio', 'researcher/moderation');
Router::get('/kutato/beadvanyok', 'researcher/moderation');
Router::post('/kutato/beadvanyok', 'researcher/moderation');

// Kérés kiszolgálása
Router::dispatch();
