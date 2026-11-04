<?php
declare(strict_types=1);

use NyitottMult\Auth;
use NyitottMult\Database;
use NyitottMult\AuditLogger;

if (!Auth::check() || !Auth::hasRole(Auth::ROLE_REVIEWER)) {
    header('Location: /kutato/login');
    exit;
}

$currentUserId = Auth::id();
$message = null;
$messageType = 'success';

// Műveletek feldolgozása: Jóváhagyás, Vitatás, Elvetés/Elutasítás, Végleges Törlés
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $claimId = trim($_POST['claim_id']);
    $action = $_POST['action'] ?? ($_POST['decision'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($action === 'delete') {
        // Végleges törlés az adatbázisból (kaszkádolva törli a lektori bejegyzéseket és szerepköröket is)
        $deleted = Database::execute("DELETE FROM claims WHERE id = :cid", ['cid' => $claimId]);
        if ($deleted) {
            AuditLogger::log('CLAIM_DELETE', 'claims', $claimId, ['deleted_by' => $currentUserId]);
            $message = "Állítás sikeresen és véglegesen törölve az adatbázisból.";
            $messageType = 'success';
        } else {
            $message = "Hiba történt az állítás törlésekor.";
            $messageType = 'error';
        }
    } elseif ($action === 'rejected' || $action === 'elvetes') {
        // Állítás elvetése / elutasítása
        Database::execute("UPDATE claims SET status = 'elutasitva' WHERE id = :cid", ['cid' => $claimId]);
        
        $verifId = Database::generateUuid();
        Database::execute(
            "INSERT INTO claim_verifications (id, claim_id, verifier_id, decision, notes, created_at)
             VALUES (:id, :cid, :uid, 'rejected', :notes, NOW())
             ON DUPLICATE KEY UPDATE decision = 'rejected', notes = :notes2",
            ['id' => $verifId, 'cid' => $claimId, 'uid' => $currentUserId, 'notes' => $notes, 'notes2' => $notes]
        );
        AuditLogger::log('CLAIM_REJECT', 'claims', $claimId, ['status' => 'elutasitva', 'verifier' => $currentUserId]);
        $message = "Az állítás elutasítva és elvetve. Nem jelenik meg a nyilvános publikációkban.";
        $messageType = 'success';
    } elseif ($action === 'disputed' || $actionvitata = 'vitatas') {
        // Vitatottá tétel
        Database::execute("UPDATE claims SET status = 'vitatott' WHERE id = :cid", ['cid' => $claimId]);
        
        $verifId = Database::generateUuid();
        Database::execute(
            "INSERT INTO claim_verifications (id, claim_id, verifier_id, decision, 'disputed', notes, created_at)
             VALUES (:id, :cid, :uid, 'disputed', :notes, NOW())
             ON DUPLICATE KEY UPDATE decision = 'disputed', notes = :notes2",
            ['id' => $verifId, 'cid' => $claimId, 'uid' => $currentUserId, 'notes' => $notes, 'notes2' => $notes]
        );
        AuditLogger::log('CLAIM_DISPUTED', 'claims', $claimId, ['status' => 'vitatott', 'verifier' => $currentUserId]);
        $message = "Az állítás vitatott státuszba került további forráskutatásig.";
        $messageType = 'success';
    } elseif ($action === 'approved') {
        // Lektori jóváhagyás
        $verifId = Database::generateUuid();
        Database::execute(
            "INSERT INTO claim_verifications (id, claim_id, verifier_id, decision, notes, created_at)
             VALUES (:id, :cid, :uid, 'approved', :notes, NOW())
             ON DUPLICATE KEY UPDATE decision = 'approved', notes = :notes2",
            ['id' => $verifId, 'cid' => $claimId, 'uid' => $currentUserId, 'notes' => $notes, 'notes2' => $notes]
        );

        // Ellenőrizzük a jóváhagyások számát
        $count = Database::queryOne(
            "SELECT COUNT(DISTINCT verifier_id) as c FROM claim_verifications WHERE claim_id = :cid AND decision = 'approved'",
            ['cid' => $claimId]
        )['c'] ?? 0;

        if ((int)$count >= 2) {
            Database::execute("UPDATE claims SET status = 'ellenorzott' WHERE id = :cid", ['cid' => $claimId]);
            AuditLogger::log('VERIFY_COMPLETE', 'claims', $claimId, ['status' => 'ellenorzott', 'verifiers' => $count]);
            $message = "Jóváhagyás rögzítve! Elérte a 2 lektori jóváhagyást, publikussá vált.";
        } else {
            $message = "Első lektori jóváhagyás rögzítve (1/2). Még egy jóváhagyás szükséges a publikáláshoz.";
        }
        $messageType = 'success';
    } elseif ($action === 'reopen') {
        // Újranyitás ellenőrzésre
        Database::execute("UPDATE claims SET status = 'javasolt' WHERE id = :cid", ['cid' => $claimId]);
        $message = "Állítás újranyitva ellenőrzésre.";
        $messageType = 'success';
    }
}

// Fülek és szűrés
$tab = $_GET['tab'] ?? 'pending';
$whereSql = "WHERE 1=1";
$params = [];

if ($tab === 'pending') {
    $whereSql .= " AND c.status = 'javasolt'";
} elseif ($tab === 'approved') {
    $whereSql .= " AND c.status = 'ellenorzott'";
} elseif ($tab === 'disputed') {
    $whereSql .= " AND c.status = 'vitatott'";
} elseif ($tab === 'rejected') {
    $whereSql .= " AND c.status = 'elutasitva'";
}

// Számlálók a fülekhez
$tabCounts = [
    'pending' => (int)(Database::queryOne("SELECT COUNT(*) as c FROM claims WHERE status = 'javasolt'")['c'] ?? 0),
    'approved' => (int)(Database::queryOne("SELECT COUNT(*) as c FROM claims WHERE status = 'ellenorzott'")['c'] ?? 0),
    'disputed' => (int)(Database::queryOne("SELECT COUNT(*) as c FROM claims WHERE status = 'vitatott'")['c'] ?? 0),
    'rejected' => (int)(Database::queryOne("SELECT COUNT(*) as c FROM claims WHERE status = 'elutasitva'")['c'] ?? 0),
    'all' => (int)(Database::queryOne("SELECT COUNT(*) as c FROM claims")['c'] ?? 0),
];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$totalInTab = $tab === 'all' ? $tabCounts['all'] : ($tabCounts[$tab] ?? 0);
$totalPages = max(1, (int)ceil($totalInTab / $perPage));

$claims = Database::query(
    "SELECT c.*, d.archive_code, d.title as doc_title, p.page_number, e.canonical_name as subject_name,
            (SELECT COUNT(*) FROM claim_verifications cv WHERE cv.claim_id = c.id AND cv.decision = 'approved') as approved_count,
            (SELECT COUNT(*) FROM claim_verifications cv WHERE cv.claim_id = c.id AND cv.decision = 'rejected') as rejected_count
     FROM claims c
     JOIN source_documents d ON c.document_id = d.id
     JOIN pages p ON c.page_id = p.id
     LEFT JOIN entities e ON c.subject_entity_id = e.id
     {$whereSql}
     ORDER BY c.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$pageTitle = "Lektori Ellenőrzés & Állításkezelés";
include dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
        <a href="/kutato" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">&larr; Vissza a vezérlőpultra</a>
        <span class="badge" style="background: #dcfce7; color: #166534;">Lektori Minőségbiztosítás</span>
    </div>
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        ⚖️ Lektori Ellenőrzés & Állítások Kezelése
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        A levéltári állítások kétlépcsős lektorálása, vitatása, elvetése és karbantartása. 
        Kizárólag legalább két jóváhagyással rendelkező állítás jelenik meg a nyilvános felületen.
    </p>
</div>

<!-- Értesítő sáv -->
<?php if ($message): ?>
    <div class="card" style="background: <?= $messageType === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?> margin-bottom: 1.5rem; font-weight: 600;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Fülválasztó sáv -->
<div class="card" style="margin-bottom: 2rem; padding: 0.75rem 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
    <span style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-right: 0.5rem;">Státusz:</span>
    <a href="/kutato/ellenorzes?tab=pending" class="btn btn-<?= $tab === 'pending' ? 'primary' : 'secondary' ?>" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
        ⏳ Ellenőrzésre váró (<?= $tabCounts['pending'] ?>)
    </a>
    <a href="/kutato/ellenorzes?tab=approved" class="btn btn-<?= $tab === 'approved' ? 'primary' : 'secondary' ?>" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
        ✓ Jóváhagyott (<?= $tabCounts['approved'] ?>)
    </a>
    <a href="/kutato/ellenorzes?tab=disputed" class="btn btn-<?= $tab === 'disputed' ? 'primary' : 'secondary' ?>" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
        ⚠️ Vitatott (<?= $tabCounts['disputed'] ?>)
    </a>
    <a href="/kutato/ellenorzes?tab=rejected" class="btn btn-<?= $tab === 'rejected' ? 'primary' : 'secondary' ?>" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
        ✕ Elvetett / Elutasított (<?= $tabCounts['rejected'] ?>)
    </a>
    <a href="/kutato/ellenorzes?tab=all" class="btn btn-<?= $tab === 'all' ? 'primary' : 'secondary' ?>" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
        Összes (<?= $tabCounts['all'] ?>)
    </a>
</div>

<!-- Állítások listája -->
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <?php if (empty($claims)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📂</div>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #0f172a;">Ebben a nézetben nincs állítás</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Válasszon másik fület a szűréshez.</p>
        </div>
    <?php else: ?>
        <?php foreach ($claims as $c): ?>
            <?php
            $borderColor = 'var(--warning)';
            if ($c['status'] === 'ellenorzott') $borderColor = 'var(--success)';
            elseif ($c['status'] === 'elutasitva') $borderColor = 'var(--danger)';
            elseif ($c['status'] === 'vitatott') $borderColor = '#d97706';
            ?>
            <div class="card" style="border-left: 5px solid <?= $borderColor ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <span class="badge" style="background: <?= $borderColor ?>; color: #fff; font-weight: 700;">
                            <?= htmlspecialchars(strtoupper($c['status'])) ?>
                        </span>
                        <span class="badge" style="background: #e0f2fe; color: #0369a1;">
                            ✓ Lektori szavazat: <?= (int)$c['approved_count'] ?> / 2
                        </span>
                        <span style="font-family: monospace; font-size: 0.85rem; color: #64748b;">
                            <?= htmlspecialchars($c['archive_code']) ?>, <?= (int)$c['page_number'] ?>. o.
                        </span>
                    </div>

                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <?php if (!empty($c['subject_name'])): ?>
                            <span style="font-weight: 700; color: var(--primary); font-size: 0.9rem;">
                                Érintett: <?= htmlspecialchars($c['subject_name']) ?>
                            </span>
                        <?php endif; ?>
                        <a href="/dokumentumok/<?= urlencode($c['document_id']) ?>?page=<?= (int)$c['page_number'] ?>" 
                           target="_blank" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            📄 Megnyitás aktában &rarr;
                        </a>
                    </div>
                </div>

                <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    <?= htmlspecialchars($c['statement']) ?>
                </h3>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; padding: 0.85rem; margin-bottom: 1rem; font-family: monospace; font-size: 0.85rem; color: #334155;">
                    "<?= htmlspecialchars($c['quote']) ?>"
                </div>

                <!-- Lektori és kezelő gombsor -->
                <form method="POST" action="/kutato/ellenorzes?tab=<?= urlencode($tab) ?>&page=<?= (int)$page ?>" 
                      style="background: #ffffff; border-top: 1px solid var(--border); padding-top: 1rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="claim_id" value="<?= htmlspecialchars($c['id']) ?>">
                    
                    <input type="text" name="notes" placeholder="Lektori jegyzet / indoklás..." 
                           class="form-input" style="flex: 1; min-width: 220px; font-size: 0.85rem;">
                    
                    <?php if ($c['status'] !== 'ellenorzott'): ?>
                        <button type="submit" name="action" value="approved" class="btn btn-primary" 
                                style="font-size: 0.85rem; background: var(--success); border-color: var(--success);">
                            ✓ Jóváhagyás
                        </button>
                    <?php endif; ?>

                    <?php if ($c['status'] !== 'vitatott'): ?>
                        <button type="submit" name="action" value="disputed" class="btn btn-warning" 
                                style="font-size: 0.85rem; background: #f59e0b; border-color: #d97706; color: #fff;">
                            ⚠️ Vitatás
                        </button>
                    <?php endif; ?>

                    <?php if ($c['status'] !== 'elutasitva'): ?>
                        <button type="submit" name="action" value="rejected" class="btn btn-secondary" 
                                style="font-size: 0.85rem; color: #b91c1c; border-color: #fca5a5;">
                            ✕ Elvetés
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="reopen" class="btn btn-secondary" style="font-size: 0.85rem;">
                            ↺ Újranyitás
                        </button>
                    <?php endif; ?>

                    <!-- Végleges Törlés gomb -->
                    <button type="submit" name="action" value="delete" class="btn btn-secondary" 
                            style="font-size: 0.85rem; background: #fee2e2; color: #991b1b; border-color: #fca5a5;" 
                            onclick="return confirm('BIZTOSAN TÖRÖLNI KÍVÁNJA? Ez a tétel véglegesen megsemmisül az adatbázisból.');">
                        🗑️ Törlés
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Lapozó -->
<?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: center; align-items: center; gap: 0.35rem; margin-top: 2.5rem; flex-wrap: wrap;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="/kutato/ellenorzes?tab=<?= urlencode($tab) ?>&page=<?= $p ?>" 
               class="btn btn-<?= $p === $page ? 'primary' : 'secondary' ?>" 
               style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
