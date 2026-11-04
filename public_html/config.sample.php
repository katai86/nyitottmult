<?php
/**
 * Nyitott Múlt – Rendszer Konfiguráció
 *
 * Másolja át ezt a fájlt config.php névre és adja meg a tárhelye adatait!
 */

declare(strict_types=1);

// Adatbázis Beállítások (MySQL 8 / MariaDB)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'oroshaza_mult');
define('DB_USER', getenv('DB_USER') ?: 'oroshaza_user');
define('DB_PASS', getenv('DB_PASS') ?: 'TitkosAdatbazisJelszo123!');
define('DB_CHARSET', 'utf8mb4');

// Alkalmazás Beállítások
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'production' vagy 'development'
define('APP_NAME', 'Orosháza titkos múltja');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_SECRET', getenv('APP_SECRET') ?: 'c7f938c35bb5493b8e4e6d2a71d8e1f0');
define('DEFAULT_LOCALE', 'hu');

// AI Szolgáltató Beállítások (Gemini vagy OpenAI)
define('AI_PROVIDER', getenv('AI_PROVIDER') ?: 'gemini');
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

// Adatvédelmi motor (2026. évi LI. tv. – halál éve + 5 év)
define('PRIVACY_DEATH_PLUS_YEARS', 5);

// Település konfiguráció fájl útvonala (rugalmas felismerés külső vagy belső config mappa esetén)
$detectedConfigDir = file_exists(dirname(__DIR__) . '/config/site.yaml')
    ? dirname(__DIR__) . '/config'
    : (file_exists(__DIR__ . '/config/site.yaml') ? __DIR__ . '/config' : dirname(__DIR__) . '/config');

define('SITE_CONFIG_PATH', $detectedConfigDir . '/site.yaml');
define('GAZETTEER_DIR', $detectedConfigDir . '/gazetteer');
