<?php
declare(strict_types=1);
$pageTitle = __('nav.network');
include __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        🕸️ <?= __('nav.network') ?> – Orosháza
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        A személyek és szervezetek közötti kapcsolatok hálója. <strong>Szigorú alapelv:</strong> a hálózatban kizárólag olyan kapcsolatok jelennek meg élként, amelyeket legalább egy ellenőrzött levéltári állítás (Claim) bizonyít.
    </p>
</div>

<!-- Kapcsolati Háló és Részletek -->
<div style="display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: start;">
    <div class="card" style="padding: 0.5rem;">
        <div id="network-graph-container"></div>
        <div style="font-size: 0.75rem; color: var(--text-muted); padding: 0.5rem; display: flex; justify-content: space-between;">
            <span>Görgő: Zoom | Húzás: Mozgatás | Kattintás: Forrás részletek</span>
            <span><span style="color: #b91c1c;">●</span> Ügynök | <span style="color: #0284c7;">●</span> Megfigyelt | <span style="color: #b45309;">●</span> Tiszt</span>
        </div>
    </div>

    <!-- Oldalsó info doboz -->
    <div class="card" id="network-info-panel">
        <h3 id="net-title" style="font-size: 1.15rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">
            Válasszon egy kapcsolatot vagy személyt
        </h3>
        <div id="net-content" style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
            Kattintson bármelyik csomópontra vagy összekötő élre a hálózatban a bizonyító levéltári forrás megtekintéséhez.
        </div>
        <div id="net-action" style="margin-top: 1rem;"></div>
    </div>
</div>

<script src="/assets/js/network.js"></script>

<?php include __DIR__ . '/layout/footer.php'; ?>
