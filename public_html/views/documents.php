<?php
declare(strict_types=1);
$pageTitle = __('nav.documents');
include __DIR__ . '/layout/header.php';

use NyitottMult\Database;

$type = $_GET['type'] ?? '';
$sql = "SELECT d.*, COUNT(p.id) as page_count 
        FROM source_documents d 
        LEFT JOIN pages p ON d.id = p.document_id 
        WHERE 1=1";
$params = [];

if ($type !== '') {
    $sql .= " AND d.document_type = :type";
    $params['type'] = $type;
}

$sql .= " GROUP BY d.id ORDER BY d.archive_code ASC";
$docs = Database::query($sql, $params);
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        <?= __('nav.documents') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Az Állambiztonsági Szolgálatok Történeti Levéltárából (ÁBTL) származó orosházi forrásiratok gyűjteménye.
    </p>
</div>

<!-- Szűrők -->
<div class="card" style="margin-bottom: 2rem; padding: 1rem;">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        <span style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-right: 0.5rem;">Irattípus szűrő:</span>
        <a href="/dokumentumok" class="btn btn-<?= $type === '' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            Összes irat
        </a>
        <a href="/dokumentumok?type=6_os_karton" class="btn btn-<?= $type === '6_os_karton' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            6-os kartonok
        </a>
        <a href="/dokumentumok?type=b_naplo" class="btn btn-<?= $type === 'b_naplo' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            B-naplók
        </a>
        <a href="/dokumentumok?type=h_adattar" class="btn btn-<?= $type === 'h_adattar' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            H-adattár
        </a>
        <a href="/dokumentumok?type=m_dosszie" class="btn btn-<?= $type === 'm_dosszie' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
            Munka-dossziék (M)
        </a>
    </div>
</div>

<!-- Iratok rácsa -->
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

            <a href="/dokumentumok/<?= urlencode($doc['id']) ?>" class="btn btn-primary" style="width: 100%; font-size: 0.875rem;">
                <?= __('common.open_viewer') ?> &rarr;
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
