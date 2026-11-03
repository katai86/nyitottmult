<?php
declare(strict_types=1);
$pageTitle = __('nav.map');
include __DIR__ . '/layout/header.php';
$siteConfig = \NyitottMult\Config::getSiteConfig();
$districts = \NyitottMult\Config::getDistricts();
$streets = \NyitottMult\Config::getStreetHistory();
$orgs = \NyitottMult\Config::getOrganizations();
?>

<div style="margin-bottom: 1.5rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        <?= __('nav.map') ?> – <?= htmlspecialchars($siteConfig['settlement']['name']) ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Fedezze fel Orosháza korabeli helyszíneit, megszűnt gyárait, az állambiztonsági kirendeltséget és a konspirált találkozási pontokat.
    </p>
</div>

<!-- Térkép vezérlősáv és Időcsúszka -->
<div class="card" style="margin-bottom: 1rem; padding: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <!-- Időcsúszka (1945 - 1990) -->
        <div style="flex: 1; min-width: 280px; display: flex; align-items: center; gap: 1rem;">
            <label for="yearSlider" style="font-weight: 700; color: var(--primary); font-size: 0.95rem; white-space: nowrap;">
                ⏳ Vizsgált év: <span id="currentYearDisplay" style="font-size: 1.25rem; color: var(--accent);">1960</span>
            </label>
            <input type="range" id="yearSlider" min="1945" max="1990" value="1960" step="1" style="flex: 1; cursor: pointer;">
        </div>

        <!-- Rétegkapcsolók -->
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; font-size: 0.85rem;">
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                <input type="checkbox" id="toggleOrgs" checked> 🏭 Üzemek & Intézmények
            </label>
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                <input type="checkbox" id="toggleSecurity" checked> 🛡️ Állambiztonsági Pontok
            </label>
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                <input type="checkbox" id="toggleDistricts" checked> 🏘️ Városrészek
            </label>
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                <input type="checkbox" id="toggleStreets" checked> 🛣️ Korabeli Utcanevek
            </label>
        </div>
    </div>
</div>

<!-- Térkép és Oldalsó Info Panel -->
<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">
    <div id="map-container">
        <!-- Térkép konténer a MapLibre számára -->
    </div>

    <!-- Oldalsó Információs Panel -->
    <div class="card" id="map-info-panel" style="max-height: 650px; overflow-y: auto;">
        <h3 id="info-title" style="font-size: 1.15rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">
            Válasszon egy helyszínt a térképen
        </h3>
        <div id="info-content" style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
            Kattintson bármelyik orosházi ikonra vagy városrészre a kapcsolódó levéltári adatok, megfigyelési jelentések és forrásidézetek megtekintéséhez.
        </div>
        <div id="info-claims" style="margin-top: 1rem;"></div>
    </div>
</div>

<!-- MapLibre és Térképkezelő szkript -->
<script>
    window.siteMapConfig = {
        center: [<?= (float)$siteConfig['settlement']['center']['lng'] ?>, <?= (float)$siteConfig['settlement']['center']['lat'] ?>],
        zoom: <?= (int)$siteConfig['settlement']['initial_zoom'] ?>,
        tileUrl: '<?= htmlspecialchars($siteConfig['map']['tile_url'] ?? "https://tiles.openfreemap.org/styles/bright/{z}/{x}/{y}.png") ?>',
        districts: <?= json_encode($districts, JSON_UNESCAPED_UNICODE) ?>,
        streetHistory: <?= json_encode($streets, JSON_UNESCAPED_UNICODE) ?>,
        organizations: <?= json_encode($orgs, JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="/assets/js/map.js"></script>

<?php include __DIR__ . '/layout/footer.php'; ?>
