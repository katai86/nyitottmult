<?php
declare(strict_types=1);
$pageTitle = __('evidence.title');
include __DIR__ . '/layout/header.php';

use NyitottMult\Database;

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT c.*, d.archive_code, d.title as doc_title, p.page_number, p.thumbnail_path,
               e.canonical_name as subject_name,
               (SELECT COUNT(*) FROM claim_verifications cv WHERE cv.claim_id = c.id AND cv.decision = 'approved') as verifier_count
        FROM claims c
        JOIN source_documents d ON c.document_id = d.id
        JOIN pages p ON c.page_id = p.id
        LEFT JOIN entities e ON c.subject_entity_id = e.id
        WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND c.status = :status";
    $params['status'] = $statusFilter;
}

$sql .= " ORDER BY c.created_at DESC";
$claims = Database::query($sql, $params);
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        ⚖️ <?= __('evidence.title') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        <?= __('evidence.subtitle') ?>. A kutatóplatformon egyetlen megállapítás sem szerepelhet önmagában: minden tétel mellett látható a forrásdokumentum, az oldalszám, a képi koordináták és a lektori jóváhagyások.
    </p>
</div>

<!-- Szűrő sáv -->
<div class="card" style="margin-bottom: 2rem; padding: 1rem; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
    <span style="font-size: 0.85rem; font-weight: 600; color: #475569;">Státusz szűrő:</span>
    <a href="/bizonyitekterkep" class="btn btn-<?= $statusFilter === '' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
        Összes állítás (<?= count($claims) ?>)
    </a>
    <a href="/bizonyitekterkep?status=ellenorzott" class="btn btn-<?= $statusFilter === 'ellenorzott' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
        ✓ Ellenőrzött (Dual Verified)
    </a>
    <a href="/bizonyitekterkep?status=javasolt" class="btn btn-<?= $statusFilter === 'javasolt' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
        ⏳ Javasolt / Ellenőrzésre vár
    </a>
    <a href="/bizonyitekterkep?status=vitatott" class="btn btn-<?= $statusFilter === 'vitatott' ? 'primary' : 'secondary' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
        ⚠️ Vitatott
    </a>
</div>

<!-- Bizonyítéktérkép Folyama -->
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <?php foreach ($claims as $c): ?>
        <div class="card" style="border-left: 5px solid <?= $c['status'] === 'ellenorzott' ? 'var(--success)' : ($c['status'] === 'vitatott' ? 'var(--danger)' : 'var(--warning)') ?>;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span class="badge badge-<?= $c['status'] === 'ellenorzott' ? 'verified' : ($c['status'] === 'vitatott' ? 'agent' : 'officer') ?>">
                        <?= htmlspecialchars(strtoupper($c['status'])) ?>
                    </span>
                    <span class="badge" style="background: #e0f2fe; color: #0369a1;">
                        <?= (int)$c['verifier_count'] ?> / 2 független jóváhagyás
                    </span>
                    <?php if (!empty($c['subject_name'])): ?>
                        <span style="font-size: 0.9rem; font-weight: 700; color: var(--primary);">
                            Érintett: <?= htmlspecialchars($c['subject_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <a href="/dokumentumok/<?= urlencode($c['document_id']) ?>?page=<?= (int)$c['page_number'] ?>" 
                   class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">
                    📄 Megnyitás az aktában &rarr;
                </a>
            </div>

            <div style="margin-bottom: 0.75rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.35rem;">
                    <?= htmlspecialchars($c['statement']) ?>
                </h3>
            </div>

            <!-- Idézet doboz az aktából -->
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; padding: 0.85rem; margin-bottom: 0.75rem;">
                <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.25rem; text-transform: uppercase;">
                    <?= __('evidence.quote') ?> (<?= htmlspecialchars($c['archive_code']) ?>, <?= (int)$c['page_number'] ?>. oldal):
                </div>
                <blockquote style="font-size: 0.9rem; font-family: monospace; color: #1e293b; line-height: 1.6;">
                    "<?= htmlspecialchars($c['quote']) ?>"
                </blockquote>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                <span>Forrásirat: <strong><?= htmlspecialchars($c['doc_title']) ?></strong></span>
                <span>Integritás ID: <span style="font-family: monospace;"><?= substr($c['id'], 0, 8) ?>...</span></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
