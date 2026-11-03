<?php
declare(strict_types=1);
$pageTitle = __('nav.home');
include __DIR__ . '/layout/header.php';

// Statisztikák lekérése
$stats = [
    'docs' => \NyitottMult\Database::queryOne("SELECT COUNT(*) as c FROM source_documents")['c'] ?? 0,
    'pages' => \NyitottMult\Database::queryOne("SELECT COUNT(*) as c FROM pages")['c'] ?? 0,
    'claims' => \NyitottMult\Database::queryOne("SELECT COUNT(*) as c FROM claims WHERE status = 'ellenorzott'")['c'] ?? 0,
    'entities' => \NyitottMult\Database::queryOne("SELECT COUNT(*) as c FROM entities")['c'] ?? 0,
];
?>

<!-- Hero szakasz -->
<section style="background: linear-gradient(135deg, #1e3a8a, #0f172a); color: #fff; padding: 3.5rem 2rem; border-radius: var(--radius); margin-bottom: 2.5rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);">
    <div style="max-width: 850px;">
        <span class="badge" style="background: rgba(255,255,255,0.2); color: #fff; margin-bottom: 1rem;">
            2026. évi LI. törvény 14. § szerinti közzététel
        </span>
        <h1 style="font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 1rem;">
            <?= htmlspecialchars($siteConfig['site']['name'] ?? 'Orosháza titkos múltja') ?>
        </h1>
        <p style="font-size: 1.15rem; color: #e2e8f0; margin-bottom: 1.75rem; line-height: 1.6;">
            <?= htmlspecialchars(__('tagline')) ?>
        </p>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/terkep" class="btn" style="background: #38bdf8; color: #0f172a; font-weight: 700;">
                🗺️ Indulás az Időutazó Térképen
            </a>
            <a href="/bizonyitekterkep" class="btn" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                ⚖️ Bizonyítéktérkép Megtekintése
            </a>
            <a href="/ai-kutato" class="btn" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                🤖 AI Történész
            </a>
        </div>
    </div>
</section>

<!-- Módszertani Figyelmeztetés és Alapelv -->
<div class="methodology-box">
    <div style="display: flex; gap: 1rem; align-items: flex-start;">
        <div style="font-size: 2rem; line-height: 1;">⚠️</div>
        <div>
            <h3><?= __('methodology.warning_title') ?></h3>
            <p style="color: #334155; font-size: 0.95rem; margin-bottom: 0.5rem;">
                <?= __('methodology.warning_text') ?>
            </p>
            <p style="font-size: 0.85rem; color: #64748b;">
                A platformon szereplő minden egyes állítás mellett látható, hogy melyik levéltári iratból, hányadik oldalról, milyen szó szerinti idézetből következik. Személyhez kötött nyilvános adat csak akkor jelenik meg, ha legalább <strong>két független kutató jóváhagyta</strong>, és az érintett halála óta eltelt <strong>legalább 5 év</strong>.
            </p>
        </div>
    </div>
</div>

<!-- Gyors Statisztikák -->
<div class="grid grid-cols-4" style="margin-bottom: 2.5rem;">
    <div class="card" style="text-align: center;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?= $stats['docs'] ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Levéltári Irat</div>
    </div>
    <div class="card" style="text-align: center;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--secondary);"><?= $stats['pages'] ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Feldolgozott Oldal</div>
    </div>
    <div class="card" style="text-align: center;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--success);"><?= $stats['claims'] ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Ellenőrzött Állítás</div>
    </div>
    <div class="card" style="text-align: center;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--accent);"><?= $stats['entities'] ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Feltárt Entitás</div>
    </div>
</div>

<!-- Hogyan olvassunk aktát? (Módszertani útmutató kártyák) -->
<section style="margin-bottom: 3rem;">
    <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 1.5rem; color: #0f172a;">
        <?= __('methodology.title') ?>
    </h2>
    <div class="grid grid-cols-3">
        <div class="card">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📇</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary);">
                <?= __('methodology.card_6_title') ?>
            </h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
                <?= __('methodology.card_6_desc') ?>
            </p>
        </div>

        <div class="card">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📖</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary);">
                <?= __('methodology.b_naplo_title') ?>
            </h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
                <?= __('methodology.b_naplo_desc') ?>
            </p>
        </div>

        <div class="card">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🛡️</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary);">
                <?= __('methodology.dual_verif_title') ?>
            </h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
                <?= __('methodology.dual_verif_desc') ?>
            </p>
        </div>
    </div>
</section>

<!-- Szerepkörök magyarázata -->
<section class="card" style="margin-bottom: 3rem;">
    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: #0f172a;">
        A szerepkörök megkülönböztetése a forrásokban
    </h3>
    <div class="grid grid-cols-2">
        <div style="border-left: 3px solid #b91c1c; padding-left: 1rem;">
            <span class="badge badge-agent" style="margin-bottom: 0.4rem;"><?= __('roles.beszervezett_halozati_szemely') ?></span>
            <p style="font-size: 0.85rem; color: #475569;"><?= __('roles_desc.beszervezett_halozati_szemely') ?></p>
        </div>
        <div style="border-left: 3px solid #0284c7; padding-left: 1rem;">
            <span class="badge badge-observed" style="margin-bottom: 0.4rem;"><?= __('roles.megfigyelt') ?></span>
            <p style="font-size: 0.85rem; color: #475569;"><?= __('roles_desc.megfigyelt') ?></p>
        </div>
        <div style="border-left: 3px solid #b45309; padding-left: 1rem;">
            <span class="badge badge-officer" style="margin-bottom: 0.4rem;"><?= __('roles.tiszt') ?></span>
            <p style="font-size: 0.85rem; color: #475569;"><?= __('roles_desc.tiszt') ?></p>
        </div>
        <div style="border-left: 3px solid #64748b; padding-left: 1rem;">
            <span class="badge badge-protected" style="margin-bottom: 0.4rem;"><?= __('roles.egyeb_emlitett') ?></span>
            <p style="font-size: 0.85rem; color: #475569;"><?= __('roles_desc.egyeb_emlitett') ?></p>
        </div>
    </div>
</section>

<!-- Kiemelt Minta Levéltári Iratok -->
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #0f172a;">Legfrissebb Ellenőrzött Forrásiratok</h2>
        <a href="/dokumentumok" class="btn btn-secondary">Összes irat böngészése &rarr;</a>
    </div>

    <?php
    $docs = \NyitottMult\Database::query(
        "SELECT d.*, COUNT(p.id) as page_count 
         FROM source_documents d 
         LEFT JOIN pages p ON d.id = p.document_id 
         GROUP BY d.id 
         ORDER BY d.created_at DESC LIMIT 3"
    );
    ?>

    <div class="grid grid-cols-3">
        <?php foreach ($docs as $doc): ?>
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <span class="badge badge-verified"><?= htmlspecialchars($doc['document_type']) ?></span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                            <?= htmlspecialchars($doc['archive_code']) ?>
                        </span>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($doc['title']) ?>
                    </h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Keltezés: <?= htmlspecialchars($doc['date_created'] ?? 'Ismeretlen') ?> | <?= $doc['page_count'] ?> oldal
                    </p>
                </div>
                <a href="/dokumentumok/<?= urlencode($doc['id']) ?>" class="btn btn-primary" style="width: 100%; font-size: 0.85rem;">
                    <?= __('common.open_viewer') ?> &rarr;
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
