<?php
declare(strict_types=1);

use NyitottMult\Auth;
use NyitottMult\Database;
use NyitottMult\AuditLogger;

if (!Auth::check() || !Auth::hasRole(Auth::ROLE_RESEARCHER)) {
    header('Location: /kutato/login');
    exit;
}

$currentUser = Auth::user();
$userId = Auth::id();
$successMsg = null;
$errorMsg = null;

// POST műveletek kezelése (Elfogadás, Elvetés/Elutasítás, Törlés)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = trim($_POST['id'] ?? '');

    if (empty($id)) {
        $errorMsg = 'Hiányzó rekordazonosító!';
    } else {
        try {
            if ($action === 'accept_submission') {
                $notes = trim($_POST['moderator_notes'] ?? '');
                Database::execute(
                    "UPDATE submissions SET moderation_status = 'accepted', moderator_notes = :notes, moderated_by = :uid WHERE id = :id",
                    ['notes' => $notes, 'uid' => $userId, 'id' => $id]
                );
                AuditLogger::log('MODERATE_SUBMISSION_ACCEPT', 'submissions', $id, ['status' => 'accepted', 'notes' => $notes], $userId);
                $successMsg = 'A közösségi beküldést sikeresen elfogadta!';
            } elseif ($action === 'reject_submission') {
                $notes = trim($_POST['moderator_notes'] ?? '');
                Database::execute(
                    "UPDATE submissions SET moderation_status = 'rejected', moderator_notes = :notes, moderated_by = :uid WHERE id = :id",
                    ['notes' => $notes, 'uid' => $userId, 'id' => $id]
                );
                AuditLogger::log('MODERATE_SUBMISSION_REJECT', 'submissions', $id, ['status' => 'rejected', 'notes' => $notes], $userId);
                $successMsg = 'A közösségi beküldést elutasította (elvetette).';
            } elseif ($action === 'delete_submission') {
                Database::execute("DELETE FROM submissions WHERE id = :id", ['id' => $id]);
                AuditLogger::log('DELETE_SUBMISSION', 'submissions', $id, null, $userId);
                $successMsg = 'A beküldést véglegesen törölte az adatbázisból.';
            } elseif ($action === 'accept_correction') {
                $resolution = trim($_POST['resolution_text'] ?? 'A kérelem / nyilatkozat elfogadva és nyilvántartásba véve.');
                Database::execute(
                    "UPDATE correction_requests SET status = 'elfogadva_kozzetett', resolution_text = :res, resolved_by = :uid, resolved_at = NOW() WHERE id = :id",
                    ['res' => $resolution, 'uid' => $userId, 'id' => $id]
                );
                AuditLogger::log('MODERATE_CORRECTION_ACCEPT', 'correction_requests', $id, ['status' => 'elfogadva_kozzetett', 'res' => $resolution], $userId);
                $successMsg = 'A helyesbítési kérelmet elfogadta és közzétételre jelölte!';
            } elseif ($action === 'investigate_correction') {
                $resolution = trim($_POST['resolution_text'] ?? 'A kérelem forráskritikai vizsgálat alatt áll.');
                Database::execute(
                    "UPDATE correction_requests SET status = 'vizsgalat_alatt', resolution_text = :res, resolved_by = :uid, resolved_at = NOW() WHERE id = :id",
                    ['res' => $resolution, 'uid' => $userId, 'id' => $id]
                );
                AuditLogger::log('MODERATE_CORRECTION_INVESTIGATE', 'correction_requests', $id, ['status' => 'vizsgalat_alatt'], $userId);
                $successMsg = 'A kérelem státusza vizsgálat alattira módosítva.';
            } elseif ($action === 'reject_correction') {
                $resolution = trim($_POST['resolution_text'] ?? 'A rendelkezésre álló levéltári források alapján a kérelem elutasítva.');
                Database::execute(
                    "UPDATE correction_requests SET status = 'elutasitva', resolution_text = :res, resolved_by = :uid, resolved_at = NOW() WHERE id = :id",
                    ['res' => $resolution, 'uid' => $userId, 'id' => $id]
                );
                AuditLogger::log('MODERATE_CORRECTION_REJECT', 'correction_requests', $id, ['status' => 'elutasitva', 'res' => $resolution], $userId);
                $successMsg = 'A helyesbítési kérelmet elutasította (elvetette).';
            } elseif ($action === 'delete_correction') {
                Database::execute("DELETE FROM correction_requests WHERE id = :id", ['id' => $id]);
                AuditLogger::log('DELETE_CORRECTION', 'correction_requests', $id, null, $userId);
                $successMsg = 'A helyesbítési kérelmet véglegesen törölte.';
            }
        } catch (\Throwable $e) {
            $errorMsg = 'Hiba a művelet végrehajtásakor: ' . $e->getMessage();
        }
    }
}

// Aktív fül és szűrő meghatározása
$activeTab = $_GET['tab'] ?? 'submissions';
if (!in_array($activeTab, ['submissions', 'corrections'])) {
    $activeTab = 'submissions';
}

// Számlálók lekérdezése
$subCounts = [
    'pending' => Database::queryOne("SELECT COUNT(*) as c FROM submissions WHERE moderation_status = 'pending'")['c'] ?? 0,
    'accepted' => Database::queryOne("SELECT COUNT(*) as c FROM submissions WHERE moderation_status = 'accepted'")['c'] ?? 0,
    'rejected' => Database::queryOne("SELECT COUNT(*) as c FROM submissions WHERE moderation_status = 'rejected'")['c'] ?? 0,
    'all' => Database::queryOne("SELECT COUNT(*) as c FROM submissions")['c'] ?? 0,
];

$corrCounts = [
    'beerkett' => Database::queryOne("SELECT COUNT(*) as c FROM correction_requests WHERE status = 'beerkett'")['c'] ?? 0,
    'vizsgalat_alatt' => Database::queryOne("SELECT COUNT(*) as c FROM correction_requests WHERE status = 'vizsgalat_alatt'")['c'] ?? 0,
    'elfogadva_kozzetett' => Database::queryOne("SELECT COUNT(*) as c FROM correction_requests WHERE status = 'elfogadva_kozzetett'")['c'] ?? 0,
    'elutasitva' => Database::queryOne("SELECT COUNT(*) as c FROM correction_requests WHERE status = 'elutasitva'")['c'] ?? 0,
    'all' => Database::queryOne("SELECT COUNT(*) as c FROM correction_requests")['c'] ?? 0,
];

// Elemek lekérdezése
if ($activeTab === 'submissions') {
    $subFilter = $_GET['status'] ?? 'pending';
    if (!in_array($subFilter, ['pending', 'accepted', 'rejected', 'all'])) {
        $subFilter = 'pending';
    }
    
    $whereClause = $subFilter === 'all' ? '1=1' : 's.moderation_status = :status';
    $params = $subFilter === 'all' ? [] : ['status' => $subFilter];
    
    $submissions = Database::query(
        "SELECT s.*, u.full_name as moderator_name 
         FROM submissions s 
         LEFT JOIN users u ON s.moderated_by = u.id 
         WHERE {$whereClause} 
         ORDER BY s.created_at DESC",
        $params
    );
} else {
    $corrFilter = $_GET['status'] ?? 'beerkett';
    if (!in_array($corrFilter, ['beerkett', 'vizsgalat_alatt', 'elfogadva_kozzetett', 'elutasitva', 'all'])) {
        $corrFilter = 'beerkett';
    }
    
    $whereClause = $corrFilter === 'all' ? '1=1' : 'cr.status = :status';
    $params = $corrFilter === 'all' ? [] : ['status' => $corrFilter];
    
    $corrections = Database::query(
        "SELECT cr.*, u.full_name as resolver_name, e.canonical_name as entity_name, c.statement as claim_text
         FROM correction_requests cr 
         LEFT JOIN users u ON cr.resolved_by = u.id 
         LEFT JOIN entities e ON cr.entity_id = e.id
         LEFT JOIN claims c ON cr.claim_id = c.id
         WHERE {$whereClause} 
         ORDER BY cr.created_at DESC",
        $params
    );
}

$pageTitle = "Beadványok & Moderáció";
include dirname(__DIR__) . '/layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
            <a href="/kutato" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">&larr; Vissza a vezérlőpultra</a>
            <span class="badge" style="background: #fef3c7; color: #92400e;">Kutatói Moderációs Felület</span>
        </div>
        <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.25rem;">
            📬 Beadványok és Moderáció
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Lakossági beküldések és hivatalos helyesbítési / kiegészítő nyilatkozati kérelmek vizsgálata, elfogadása, elutasítása vagy törlése.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="/kutato/ellenorzes" class="btn btn-warning" style="font-size: 0.85rem;">
            ⚖️ Lektori Ellenőrzés
        </a>
        <a href="/kutato/detektiv" class="btn btn-primary" style="font-size: 0.85rem;">
            🕵️ Dokumentumdetektív
        </a>
    </div>
</div>

<?php if ($successMsg): ?>
    <div class="card" style="background: #dcfce7; border-color: #86efac; color: #166534; margin-bottom: 1.5rem; font-weight: 600;">
        ✓ <?= htmlspecialchars($successMsg) ?>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="card" style="background: #fee2e2; border-color: #fca5a5; color: #991b1b; margin-bottom: 1.5rem; font-weight: 600;">
        ⚠️ <?= htmlspecialchars($errorMsg) ?>
    </div>
<?php endif; ?>

<!-- Fő navigációs fülek -->
<div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border); margin-bottom: 1.5rem;">
    <a href="/kutato/moderacio?tab=submissions" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid <?= $activeTab === 'submissions' ? 'var(--primary)' : 'transparent' ?>; color: <?= $activeTab === 'submissions' ? 'var(--primary)' : 'var(--text-muted)' ?>; display: flex; align-items: center; gap: 0.5rem;">
        👥 Lakossági Beküldések
        <span class="badge" style="background: <?= $subCounts['pending'] > 0 ? '#ef4444' : '#e2e8f0' ?>; color: <?= $subCounts['pending'] > 0 ? '#fff' : '#475569' ?>; font-size: 0.75rem;">
            <?= $subCounts['pending'] ?>
        </span>
    </a>
    <a href="/kutato/moderacio?tab=corrections" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid <?= $activeTab === 'corrections' ? 'var(--primary)' : 'transparent' ?>; color: <?= $activeTab === 'corrections' ? 'var(--primary)' : 'var(--text-muted)' ?>; display: flex; align-items: center; gap: 0.5rem;">
        📝 Helyesbítési Kérelmek (2026. évi LI. tv.)
        <span class="badge" style="background: <?= $corrCounts['beerkett'] > 0 ? '#f59e0b' : '#e2e8f0' ?>; color: <?= $corrCounts['beerkett'] > 0 ? '#fff' : '#475569' ?>; font-size: 0.75rem;">
            <?= $corrCounts['beerkett'] ?>
        </span>
    </a>
</div>

<?php if ($activeTab === 'submissions'): ?>
    <!-- Alszűrők beküldésekhez -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="/kutato/moderacio?tab=submissions&status=pending" class="btn <?= $subFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            ⏳ Függőben (<?= $subCounts['pending'] ?>)
        </a>
        <a href="/kutato/moderacio?tab=submissions&status=accepted" class="btn <?= $subFilter === 'accepted' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            ✓ Elfogadva (<?= $subCounts['accepted'] ?>)
        </a>
        <a href="/kutato/moderacio?tab=submissions&status=rejected" class="btn <?= $subFilter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            ✕ Elutasítva / Elvetve (<?= $subCounts['rejected'] ?>)
        </a>
        <a href="/kutato/moderacio?tab=submissions&status=all" class="btn <?= $subFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            Összes (<?= $subCounts['all'] ?>)
        </a>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="card" style="text-align: center; padding: 3rem; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📭</div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">Nincs megjeleníthető lakossági beküldés</h3>
            <p style="font-size: 0.85rem;">Ebben a kategóriában jelenleg nincsenek moderálásra váró bejegyzések.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($submissions as $sub): ?>
                <div class="card" style="border-left: 5px solid <?= $sub['moderation_status'] === 'accepted' ? '#10b981' : ($sub['moderation_status'] === 'rejected' ? '#ef4444' : '#f59e0b') ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; text-transform: uppercase;">
                                Típus: <?= htmlspecialchars($sub['content_type']) ?>
                            </span>
                            <span class="badge" style="background: <?= $sub['moderation_status'] === 'accepted' ? '#dcfce7; color: #166534;' : ($sub['moderation_status'] === 'rejected' ? '#fee2e2; color: #991b1b;' : '#fef3c7; color: #92400e;') ?>">
                                Státusz: <?= htmlspecialchars($sub['moderation_status']) ?>
                            </span>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            Beküldve: <?= htmlspecialchars($sub['created_at']) ?> | ID: <span style="font-family: monospace;"><?= substr($sub['id'], 0, 8) ?></span>
                        </div>
                    </div>

                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($sub['title']) ?>
                    </h2>

                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                        Beküldő: <strong><?= htmlspecialchars($sub['submitter_name']) ?></strong> (<a href="mailto:<?= htmlspecialchars($sub['submitter_email']) ?>"><?= htmlspecialchars($sub['submitter_email']) ?></a>)
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; margin-bottom: 1rem; font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">
<?= htmlspecialchars($sub['description']) ?>
                    </div>

                    <?php if (!empty($sub['moderator_notes'])): ?>
                        <div style="background: #fffbeb; border-left: 3px solid #f59e0b; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem;">
                            <strong>Moderátori megjegyzés:</strong> <?= htmlspecialchars($sub['moderator_notes']) ?>
                            <?php if ($sub['moderator_name']): ?>
                                <span style="color: var(--text-muted);"> (Moderátor: <?= htmlspecialchars($sub['moderator_name']) ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Műveleti gombok -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                        <!-- Végleges törlés -->
                        <form method="POST" onsubmit="return confirm('BIZTOSAN TÖRÖLNI KÍVÁNJA ezt a beküldést? A művelet nem visszavonható!');">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($sub['id']) ?>">
                            <input type="hidden" name="action" value="delete_submission">
                            <button type="submit" class="btn btn-secondary" style="color: #991b1b; font-size: 0.8rem;">
                                🗑️ Végleges Törlés
                            </button>
                        </form>

                        <!-- Elfogadás / Elutasítás űrlap -->
                        <form method="POST" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($sub['id']) ?>">
                            <input type="text" name="moderator_notes" placeholder="Megjegyzés a döntéshez (opcionális)..." style="font-size: 0.8rem; padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: 4px; width: 260px;">
                            
                            <button type="submit" name="action" value="reject_submission" class="btn btn-secondary" style="font-size: 0.8rem; color: #b91c1c; border-color: #fca5a5;">
                                ✕ Elutasítás / Elvetés
                            </button>
                            
                            <button type="submit" name="action" value="accept_submission" class="btn btn-primary" style="font-size: 0.8rem; background: #16a34a; border-color: #16a34a;">
                                ✓ Elfogadás és Közzététel
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Alszűrők helyesbítési kérelmekhez -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="/kutato/moderacio?tab=corrections&status=beerkett" class="btn <?= $corrFilter === 'beerkett' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            ⏳ Beérkezett (<?= $corrCounts['beerkett'] ?>)
        </a>
        <a href="/kutato/moderacio?tab=corrections&status=vizsgalat_alatt" class="btn <?= $corrFilter === 'vizsgalat_alatt' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            🔍 Vizsgálat alatt (<?= $corrCounts['vizsgalat_alatt'] ?>)
        </a>
        <a href="/kutato/moderacio?tab=corrections&status=elfogadva_kozzetett" class="btn <?= $corrFilter === 'elfogadva_kozzetett' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            ✓ Elfogadva / Közzétéve (<?= $corrCounts['elfogadva_kozzetett'] ?>)
        </a>
        <a href="/kutato/moderacio?tab=corrections&status=elutasitva" class="btn <?= $corrFilter === 'elutasitva' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            ✕ Elutasítva / Elvetve (<?= $corrCounts['elutasitva'] ?>)
        </a>
        <a href="/kutato/moderacio?tab=corrections&status=all" class="btn <?= $corrFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size: 0.8rem;">
            Összes (<?= $corrCounts['all'] ?>)
        </a>
    </div>

    <?php if (empty($corrections)): ?>
        <div class="card" style="text-align: center; padding: 3rem; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚖️</div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">Nincs megjeleníthető helyesbítési kérelem</h3>
            <p style="font-size: 0.85rem;">Ebben a kategóriában jelenleg nincsenek elbírálásra váró kérelmek.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($corrections as $cr): ?>
                <div class="card" style="border-left: 5px solid <?= $cr['status'] === 'elfogadva_kozzetett' ? '#10b981' : ($cr['status'] === 'elutasitva' ? '#ef4444' : ($cr['status'] === 'vizsgalat_alatt' ? '#0284c7' : '#f59e0b')) ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; text-transform: uppercase;">
                                Típus: <?= htmlspecialchars(str_replace('_', ' ', $cr['request_type'])) ?>
                            </span>
                            <span class="badge" style="background: <?= $cr['status'] === 'elfogadva_kozzetett' ? '#dcfce7; color: #166534;' : ($cr['status'] === 'elutasitva' ? '#fee2e2; color: #991b1b;' : '#fef3c7; color: #92400e;') ?>">
                                Státusz: <?= htmlspecialchars($cr['status']) ?>
                            </span>
                            <span class="badge" style="background: #fee2e2; color: #991b1b;">
                                Határidő: <?= htmlspecialchars($cr['deadline']) ?>
                            </span>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            Ügyszám: <span style="font-family: monospace; font-weight: 700;"><?= substr($cr['id'], 0, 8) ?></span> | Beérkezett: <?= htmlspecialchars($cr['created_at']) ?>
                        </div>
                    </div>

                    <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                        Kérelmező: <?= htmlspecialchars($cr['applicant_name']) ?> (<a href="mailto:<?= htmlspecialchars($cr['applicant_email']) ?>"><?= htmlspecialchars($cr['applicant_email']) ?></a>)
                        <?php if (!empty($cr['applicant_relationship'])): ?>
                            <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-muted);">&ndash; Érintettség: <?= htmlspecialchars($cr['applicant_relationship']) ?></span>
                        <?php endif; ?>
                    </h3>

                    <?php if ($cr['entity_name'] || $cr['claim_text']): ?>
                        <div style="background: #f1f5f9; border-radius: 4px; padding: 0.6rem 0.85rem; margin-bottom: 0.75rem; font-size: 0.85rem;">
                            <?php if ($cr['entity_name']): ?>
                                <div><strong>Érintett személy:</strong> <?= htmlspecialchars($cr['entity_name']) ?></div>
                            <?php endif; ?>
                            <?php if ($cr['claim_text']): ?>
                                <div><strong>Kifogásolt állítás:</strong> <em>"<?= htmlspecialchars($cr['claim_text']) ?>"</em></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-bottom: 0.75rem; font-size: 0.85rem;">
                        <strong>Törvényes jogalap (LI. tv.):</strong>
                        <div style="color: #334155; margin-top: 0.25rem; font-style: italic;">
                            <?= htmlspecialchars($cr['legal_ground']) ?>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; margin-bottom: 1rem; font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">
<strong>Kérelem / Kiegészítő Nyilatkozat Szövege:</strong>
<?= htmlspecialchars($cr['statement_text']) ?>
                    </div>

                    <?php if (!empty($cr['resolution_text'])): ?>
                        <div style="background: #f0fdf4; border-left: 3px solid #10b981; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem;">
                            <strong>Határozat / Lektori Indoklás:</strong> <?= htmlspecialchars($cr['resolution_text']) ?>
                            <?php if ($cr['resolver_name']): ?>
                                <span style="color: var(--text-muted);"> (Elbírálta: <?= htmlspecialchars($cr['resolver_name']) ?> &ndash; <?= htmlspecialchars($cr['resolved_at'] ?? '') ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Műveleti gombok -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                        <!-- Végleges törlés -->
                        <form method="POST" onsubmit="return confirm('BIZTOSAN TÖRÖLNI KÍVÁNJA ezt a kérelmet? A törlés végleges és visszafordíthatatlan!');">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($cr['id']) ?>">
                            <input type="hidden" name="action" value="delete_correction">
                            <button type="submit" class="btn btn-secondary" style="color: #991b1b; font-size: 0.8rem;">
                                🗑️ Végleges Törlés
                            </button>
                        </form>

                        <!-- Elbíráló műveletek -->
                        <form method="POST" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($cr['id']) ?>">
                            <input type="text" name="resolution_text" placeholder="Lektori határozat indoklása..." style="font-size: 0.8rem; padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: 4px; width: 260px;">
                            
                            <button type="submit" name="action" value="investigate_correction" class="btn btn-secondary" style="font-size: 0.8rem; color: #0369a1; border-color: #bae6fd;">
                                🔍 Vizsgálat alá vonás
                            </button>

                            <button type="submit" name="action" value="reject_correction" class="btn btn-secondary" style="font-size: 0.8rem; color: #b91c1c; border-color: #fca5a5;">
                                ✕ Elutasítás / Elvetés
                            </button>
                            
                            <button type="submit" name="action" value="accept_correction" class="btn btn-primary" style="font-size: 0.8rem; background: #16a34a; border-color: #16a34a;">
                                ✓ Elfogadás és Közzététel
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
