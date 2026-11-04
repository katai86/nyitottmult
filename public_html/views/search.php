<?php
declare(strict_types=1);
$pageTitle = __('nav.search');
include __DIR__ . '/layout/header.php';

use NyitottMult\Database;
use NyitottMult\PrivacyEngine;

$q = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$results = [];

$sql = "SELECT e.*, pr.status as privacy_status, pr.death_year, pr.is_publicly_visible,
               r.role_type, d.title as doc_title, d.archive_code
        FROM entities e
        LEFT JOIN privacy_records pr ON e.id = pr.entity_id
        LEFT JOIN roles r ON e.id = r.entity_id
        LEFT JOIN source_documents d ON r.document_id = d.id
        WHERE 1=1";

$params = [];

if ($q !== '') {
    $sql .= " AND (e.canonical_name LIKE :q OR e.aliases LIKE :q2 OR d.title LIKE :q3 OR d.archive_code LIKE :q4)";
    $params['q'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
    $params['q3'] = '%' . $q . '%';
    $params['q4'] = '%' . $q . '%';
}

if ($typeFilter !== '') {
    $sql .= " AND e.type = :type";
    $params['type'] = $typeFilter;
}

if ($roleFilter !== '') {
    $sql .= " AND r.role_type = :role";
    $params['role'] = $roleFilter;
}

$sql .= " GROUP BY e.id ORDER BY e.canonical_name ASC LIMIT 50";
$rawResults = Database::query($sql, $params);

foreach ($rawResults as $row) {
    $results[] = PrivacyEngine::sanitizeEntityForPublic($row, [
        'status' => $row['privacy_status'],
        'death_year' => $row['death_year'],
        'is_publicly_visible' => $row['is_publicly_visible']
    ]);
}
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        <?= __('nav.search') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Keresés nevek, fedőnevek, orosházi intézmények, iratjelzetek és szerepkörök között.
    </p>
</div>

<!-- Kereső űrlap és Fazetták -->
<form method="GET" action="/kereses" class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" 
               placeholder="<?= __('search.search_placeholder') ?>" 
               class="form-input" style="flex: 1; min-width: 280px; font-size: 1rem;">
        <button type="submit" class="btn btn-primary" style="padding-left: 1.5rem; padding-right: 1.5rem;">
            🔍 Keresés
        </button>
    </div>

    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 180px;">
            <label class="form-label">Entitás típusa</label>
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value=""><?= __('search.all_types') ?></option>
                <option value="person" <?= $typeFilter === 'person' ? 'selected' : '' ?>><?= __('search.person') ?></option>
                <option value="organization" <?= $typeFilter === 'organization' ? 'selected' : '' ?>><?= __('search.organization') ?></option>
                <option value="codename" <?= $typeFilter === 'codename' ? 'selected' : '' ?>><?= __('search.codename') ?></option>
                <option value="place" <?= $typeFilter === 'place' ? 'selected' : '' ?>><?= __('search.place') ?></option>
            </select>
        </div>

        <div style="flex: 1; min-width: 220px;">
            <label class="form-label">Szerepkör az iratban</label>
            <select name="role" class="form-select" onchange="this.form.submit()">
                <option value=""><?= __('search.all_roles') ?></option>
                <option value="beszervezett_halozati_szemely" <?= $roleFilter === 'beszervezett_halozati_szemely' ? 'selected' : '' ?>><?= __('roles.beszervezett_halozati_szemely') ?></option>
                <option value="megfigyelt" <?= $roleFilter === 'megfigyelt' ? 'selected' : '' ?>><?= __('roles.megfigyelt') ?></option>
                <option value="tiszt" <?= $roleFilter === 'tiszt' ? 'selected' : '' ?>><?= __('roles.tiszt') ?></option>
                <option value="egyeb_emlitett" <?= $roleFilter === 'egyeb_emlitett' ? 'selected' : '' ?>><?= __('roles.egyeb_emlitett') ?></option>
            </select>
        </div>
    </div>
</form>

<!-- Találati lista -->
<?php if ($q !== '' || $typeFilter !== '' || $roleFilter !== ''): ?>
    <div style="margin-bottom: 1rem; font-weight: 600; color: var(--text-muted);">
        <?= __('search.results_count', ['count' => count($results)]) ?>
    </div>

    <?php if (empty($results)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔍</div>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                <?= __('search.no_results') ?>
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Próbáljon kevesebb feltételt megadni vagy használjon névváltozatot.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1">
            <?php foreach ($results as $item): ?>
                <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.35rem;">
                            <span class="badge" style="background: #e2e8f0; color: #1e293b;">
                                <?= htmlspecialchars($item['type']) ?>
                            </span>

                            <?php if (!empty($item['role_type'])): ?>
                                <?php if ($item['role_type'] === 'beszervezett_halozati_szemely'): ?>
                                    <span class="badge badge-agent"><?= __('roles.beszervezett_halozati_szemely') ?></span>
                                <?php elseif ($item['role_type'] === 'megfigyelt'): ?>
                                    <span class="badge badge-observed"><?= __('roles.megfigyelt') ?></span>
                                <?php elseif ($item['role_type'] === 'tiszt'): ?>
                                    <span class="badge badge-officer"><?= __('roles.tiszt') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-protected"><?= htmlspecialchars($item['role_type']) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($item['is_protected'])): ?>
                                <span class="badge" style="background: #fee2e2; color: #991b1b;">🛡️ Védett személy</span>
                            <?php endif; ?>
                        </div>

                        <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--primary);">
                            <?= htmlspecialchars($item['canonical_name']) ?>
                        </h3>

                        <?php if (!empty($item['archive_code'])): ?>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                                Forrásirat: <span style="font-family: monospace;"><?= htmlspecialchars($item['archive_code']) ?></span>
                                (<?= htmlspecialchars($item['doc_title']) ?>)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php if (empty($item['is_protected'])): ?>
                            <a href="/entitasok/<?= urlencode($item['id']) ?>" class="btn btn-secondary" style="font-size: 0.85rem;">
                                Entitás adatlap &rarr;
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: #b91c1c; font-style: italic;">
                                2026. évi LI. tv. alapján védett
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/layout/footer.php'; ?>
