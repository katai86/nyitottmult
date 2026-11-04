<?php
declare(strict_types=1);
$pageTitle = __('nav.documents');
include __DIR__ . '/layout/header.php';

use NyitottMult\Database;

$type = $_GET['type'] ?? '';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$whereSql = "WHERE 1=1";
$params = [];

if ($type !== '') {
    $whereSql .= " AND d.document_type = :type";
    $params['type'] = $type;
}

if ($search !== '') {
    $whereSql .= " AND (d.title LIKE :search OR d.archive_code LIKE :search2)";
    $params['search'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
}

$countSql = "SELECT COUNT(*) as total FROM source_documents d {$whereSql}";
$totalDocs = (int)(Database::queryOne($countSql, $params)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalDocs / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "SELECT d.*, COUNT(p.id) as page_count 
        FROM source_documents d 
        LEFT JOIN pages p ON d.id = p.document_id 
        {$whereSql}
        GROUP BY d.id 
        ORDER BY d.archive_code ASC 
        LIMIT {$perPage} OFFSET {$offset}";

$docs = Database::query($sql, $params);
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        <?= __('nav.documents') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Az Állambiztonsági Szolgálatok Történeti Levéltárából (ÁBTL) származó orosházi forrásiratok gyűjteménye.
        Összesen: <strong><?= $totalDocs ?></strong> nyilvántartott levéltári irat.
    </p>
</div>

<!-- Szűrők és Gyorskereső -->
<div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <form method="GET" action="/dokumentumok" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem;">
        <?php if ($type !== ''): ?>
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
               placeholder="Keresés iratcímben, jelzetben (pl. Mágnesszalag, 6-os karton, B-90224)..." 
               class="form-input" style="flex: 1; min-width: 260px;">
        <button type="submit" class="btn btn-primary">
            🔍 Szűrés
        </button>
        <?php if ($search !== '' || $type !== ''): ?>
            <a href="/dokumentumok" class="btn btn-secondary">Szűrők törlése</a>
        <?php endif; ?>
    </form>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        <span style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-right: 0.5rem;">Irattípus:</span>
        <a href="/dokumentumok<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>" class="btn btn-<?= $type === '' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            Összes irat (<?= $totalDocs ?>)
        </a>
        <a href="/dokumentumok?type=magnesszalag<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-<?= $type === 'magnesszalag' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            Mágnesszalag adattár
        </a>
        <a href="/dokumentumok?type=6_os_karton<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-<?= $type === '6_os_karton' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            6-os kartonok
        </a>
        <a href="/dokumentumok?type=b_naplo<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-<?= $type === 'b_naplo' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            B-naplók
        </a>
        <a href="/dokumentumok?type=h_adattar<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-<?= $type === 'h_adattar' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            H-adattár
        </a>
        <a href="/dokumentumok?type=m_dosszie<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="btn btn-<?= $type === 'm_dosszie' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            Munka-dossziék (M)
        </a>
    </div>
</div>

<!-- Iratok rácsa -->
<?php if (empty($docs)): ?>
    <div class="card" style="text-align: center; padding: 3rem;">
        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📂</div>
        <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
            Nem található irat a megadott szűrési feltételekkel.
        </h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Próbáljon kevesebb feltételt megadni vagy törölje a szűrőket.
        </p>
    </div>
<?php else: ?>
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

                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($doc['title']) ?>
                    </h3>

                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Keltezés: <strong><?= htmlspecialchars($doc['date_created'] ?? 'N/A') ?></strong> | 
                        Oldalszám: <strong><?= (int)$doc['page_count'] ?></strong>
                    </p>

                    <?php if (!empty($doc['license_note'])): ?>
                        <p style="font-size: 0.75rem; color: #64748b; background: #f8fafc; padding: 0.4rem; border-radius: 4px; margin-bottom: 1rem;">
                            <?= htmlspecialchars($doc['license_note']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                    <a href="/dokumentumok/<?= urlencode($doc['id']) ?>" class="btn btn-primary" style="flex: 1; font-size: 0.85rem; text-align: center;">
                        <?= __('common.open_viewer') ?> &rarr;
                    </a>
                    <?php if (!empty($doc['original_url'])): ?>
                        <a href="<?= htmlspecialchars($doc['original_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" title="Hivatalos ÁBTL Nyitott Akták oldal" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                            🏛️
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Lapozó (Pagination) -->
    <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; align-items: center; gap: 0.35rem; margin-top: 2.5rem; flex-wrap: wrap;">
            <?php
            $baseParams = [];
            if ($type !== '') $baseParams['type'] = $type;
            if ($search !== '') $baseParams['q'] = $search;
            
            $buildPageUrl = function($p) use ($baseParams) {
                return '/dokumentumok?' . http_build_query(array_merge($baseParams, ['page' => $p]));
            };
            ?>

            <?php if ($page > 1): ?>
                <a href="<?= $buildPageUrl(1) ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">&laquo; Első</a>
                <a href="<?= $buildPageUrl($page - 1) ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">&larr; Előző</a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($p = $startPage; $p <= $endPage; $p++):
            ?>
                <a href="<?= $buildPageUrl($p) ?>" class="btn btn-<?= $p === $page ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $buildPageUrl($page + 1) ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">Következő &rarr;</a>
                <a href="<?= $buildPageUrl($totalPages) ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">Utolsó &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/layout/footer.php'; ?>
