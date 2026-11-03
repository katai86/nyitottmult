<?php
/**
 * Nyitott Múlt – Rendszer Konfiguráció
 */

declare(strict_types=1);

// Adatbázis Beállítások (MySQL 8 / MariaDB)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'oroshaza_mult');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Alkalmazás Beállítások
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_NAME', 'Orosháza titkos múltja');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_SECRET', getenv('APP_SECRET') ?: 'c7f938c35bb5493b8e4e6d2a71d8e1f0');
define('DEFAULT_LOCALE', 'hu');

// AI Szolgáltató Beállítások
define('AI_PROVIDER', getenv('AI_PROVIDER') ?: 'gemini');
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

// Adatvédelmi motor
define('PRIVACY_DEATH_PLUS_YEARS', 5);

// Elérési utak
define('SITE_CONFIG_PATH', dirname(__DIR__) . '/config/site.yaml');
define('GAZETTEER_DIR', dirname(__DIR__) . '/config/gazetteer');
define('UPLOADS_DIR', __DIR__ . '/uploads');
