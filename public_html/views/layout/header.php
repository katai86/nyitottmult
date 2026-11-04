<?php
declare(strict_types=1);
use NyitottMult\I18n;
use NyitottMult\Auth;

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentLang = I18n::getLocale();
$siteConfig = \NyitottMult\Config::getSiteConfig();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? __('site_title')) ?> – <?= htmlspecialchars($siteConfig['settlement']['name'] ?? 'Orosháza') ?></title>
    <meta name="description" content="<?= htmlspecialchars(__('tagline')) ?>">

    <!-- Beépített modern stíluslap -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Kliensoldali térkép és iratnéző könyvtárak (CDN) -->
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" />
    <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/openseadragon/4.1.0/openseadragon.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.28.1/cytoscape.min.js"></script>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22 width=%2232%22 height=%2232%22><text y=%2224%22 font-size=%2224%22>📜</text></svg>">
</head>
<body>

<header class="site-header">
    <div class="container nav-container">
        <a href="/" class="brand-link">
            <div class="brand-logo">NM</div>
            <div class="brand-text">
                <h1><?= htmlspecialchars($siteConfig['site']['name'] ?? 'Orosháza titkos múltja') ?></h1>
                <p><?= htmlspecialchars($siteConfig['site']['subtitle'] ?? 'Az ügynökakták nyomában') ?></p>
            </div>
        </a>

        <nav class="main-nav">
            <a href="/" class="<?= $currentUri === '/' ? 'active' : '' ?>"><?= __('nav.home') ?></a>
            <a href="/terkep" class="<?= str_starts_with($currentUri, '/terkep') ? 'active' : '' ?>">🗺️ <?= __('nav.map') ?></a>
            <a href="/dokumentumok" class="<?= (str_starts_with($currentUri, '/dokumentumok') || str_starts_with($currentUri, '/kereses') || str_starts_with($currentUri, '/entitasok')) ? 'active' : '' ?>">📄 Iratok & Kereső</a>
            <a href="/bizonyitekterkep" class="<?= str_starts_with($currentUri, '/bizonyitekterkep') ? 'active' : '' ?>">⚖️ <?= __('nav.evidence_map') ?></a>
            <a href="/kapcsolati-halo" class="<?= str_starts_with($currentUri, '/kapcsolati-halo') ? 'active' : '' ?>">🕸️ <?= __('nav.network') ?></a>
            <a href="/ai-kutato" class="<?= (str_starts_with($currentUri, '/ai-kutato') || str_starts_with($currentUri, '/ai-tortenesz')) ? 'active' : '' ?>">🤖 <?= __('nav.ai_historian') ?></a>
            
            <!-- További hasznos oldalak menü -->
            <div class="nav-dropdown" style="position: relative; display: inline-block;">
                <button type="button" class="nav-dropbtn" style="background: none; border: none; font-size: 0.9rem; font-weight: 500; color: #334155; cursor: pointer; padding: 0.5rem 0.4rem; display: flex; align-items: center; gap: 0.25rem;">
                    Továbbiak ▾
                </button>
                <div class="dropdown-content" style="display: none; position: absolute; right: 0; background-color: #ffffff; min-width: 210px; box-shadow: 0px 10px 25px rgba(0,0,0,0.15); border-radius: var(--radius); z-index: 1000; border: 1px solid var(--border); padding: 0.4rem 0;">
                    <a href="/idovonal" style="display: block; padding: 0.5rem 1rem; color: #334155; text-decoration: none; font-size: 0.85rem;">⏳ Idővonal (1945–1990)</a>
                    <a href="/tortenetek" style="display: block; padding: 0.5rem 1rem; color: #334155; text-decoration: none; font-size: 0.85rem;">📖 Helytörténeti Sztorik</a>
                    <a href="/kozosseg" style="display: block; padding: 0.5rem 1rem; color: #334155; text-decoration: none; font-size: 0.85rem;">🤝 Közösségi Beküldés</a>
                    <a href="/helyesbites" style="display: block; padding: 0.5rem 1rem; color: #334155; text-decoration: none; font-size: 0.85rem;">📝 Helyesbítés & Kérelem</a>
                    <a href="/atlathatosag" style="display: block; padding: 0.5rem 1rem; color: #334155; text-decoration: none; font-size: 0.85rem;">📊 Átláthatóság & Statisztika</a>
                    <a href="/api-docs" style="display: block; padding: 0.5rem 1rem; color: #334155; text-decoration: none; font-size: 0.85rem;">⚡ Nyílt REST API</a>
                </div>
            </div>

            <?php if (Auth::check()): ?>
                <a href="/kutato" class="btn btn-warning" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; margin-left: 0.5rem;">🔒 <?= __('nav.researcher') ?></a>
            <?php else: ?>
                <a href="/kutato/login" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; margin-left: 0.5rem;">Kutatói Belépés</a>
            <?php endif; ?>

            <!-- Nyelvválasztó -->
            <div style="margin-left: 0.5rem; display: flex; gap: 0.25rem;">
                <a href="?lang=hu" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; <?= $currentLang === 'hu' ? 'font-weight: bold; background: #e2e8f0; border-radius: 4px;' : '' ?>">HU</a>
                <a href="?lang=en" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; <?= $currentLang === 'en' ? 'font-weight: bold; background: #e2e8f0; border-radius: 4px;' : '' ?>">EN</a>
            </div>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
