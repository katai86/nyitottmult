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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'], $_POST['decision'])) {
    $claimId = $_POST['claim_id'];
    $decision = $_POST['decision'];
    $notes = trim($_POST['notes'] ?? '');

    $verifId = Database::generateUuid();
    $saved = Database::execute(
        "INSERT INTO claim_verifications (id, claim_id, verifier_id, decision, notes, created_at)
         VALUES (:id, :cid, :uid, :dec, :notes, NOW())
         ON DUPLICATE KEY UPDATE decision = :dec2, notes = :notes2",
        [
            'id' => $verifId,
            'cid' => $claimId,
            'uid' => $currentUserId,
            'dec' => $decision,
            'notes' => $notes,
            'dec2' => $decision,
            'notes2' => $notes
        ]
    );

    if ($saved) {
        // Ellenőrizzük, megvan-e a 2 jóváhagyás
        $count = Database::queryOne(
            "SELECT COUNT(DISTINCT verifier_id) as c FROM claim_verifications WHERE claim_id = :cid AND decision = 'approved'",
            ['cid' => $claimId]
        )['c'] ?? 0;

        if ((int)$count >= 2) {
            Database::execute("UPDATE claims SET status = 'ellenorzott' WHERE id = :cid", ['cid' => $claimId]);
            AuditLogger::log('VERIFY_COMPLETE', 'claims', $claimId, ['status' => 'ellenorzott', 'verifiers' => $count]);
            $message = "Jóváhagyás rögzítve! Az állítás elérte a 2 független ellenőrzést, státusza 'ELLENŐRZÖTT' lett és publikálható.";
        } else {
            $message = "Jóváhagyás rögzítve (1/2). Még egy független lektor ellenőrzése szükséges a közzétételhez.";
        }
    }
}

$pendingClaims = Database::query(
    "SELECT c.*, d.archive_code, d.title as doc_title, p.page_number, e.canonical_name as subject_name,
            (SELECT COUNT(*) FROM claim_verifications cv WHERE cv.claim_id = c.id AND cv.decision = 'approved') as approved_count
     FROM claims c
     JOIN source_documents d ON c.document_id = d.id
     JOIN pages p ON c.page_id = p.id
     LEFT JOIN entities e ON c.subject_entity_id = e.id
     WHERE c.status != 'ellenorzott'
     ORDER BY c.created_at DESC"
);

$pageTitle = "Kétlépcsős Lektori Ellenőrzés";
include dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
        <a href="/kutato" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">&larr; Vissza a vezérlőpultra</a>
        <span class="badge" style="background: #dcfce7; color: #166534;">Lektori Minőségbiztosítás</span>
    </div>
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        ⚖️ Kétlépcsős Lektori Ellenőrzés (Dual Verification)
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        A 2026. évi LI. törvény és a szakmai hitelesség garanciája: egyetlen személyhez köthető állítás sem publikálható legalább két független lektor írásos jóváhagyása nélkül.
    </p>
</div>

<?php if ($message): ?>
    <div class="card" style="background: #dcfce7; color: #166534; margin-bottom: 2rem; font-weight: 600;">
        ✓ <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <?php if (empty($pendingClaims)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">✓</div>
            <h3 style="font-size: 1.25rem; font-weight: 700;">Nincs ellenőrzésre váró állítás</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Minden rögzített tétel lektorálva van.</p>
        </div>
    <?php else: ?>
        <?php foreach ($pendingClaims as $c): ?>
            <div class="card" style="border-left: 5px solid var(--warning);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <div>
                        <span class="badge badge-officer">⏳ Jóváhagyások: <?= (int)$c['approved_count'] ?> / 2</span>
                        <span style="font-family: monospace; font-size: 0.85rem; color: #64748b; margin-left: 0.5rem;">
                            <?= htmlspecialchars($c['archive_code']) ?>, <?= (int)$c['page_number'] ?>. o.
                        </span>
                    </div>
                    <?php if (!empty($c['subject_name'])): ?>
                        <span style="font-weight: 700; color: var(--primary);">
                            Érintett: <?= htmlspecialchars($c['subject_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    <?= htmlspecialchars($c['statement']) ?>
                </h3>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; padding: 0.85rem; margin-bottom: 1rem; font-family: monospace; font-size: 0.85rem;">
                    "<?= htmlspecialchars($c['quote']) ?>"
                </div>

                <form method="POST" action="/kutato/ellenorzes" style="background: #ffffff; border-top: 1px solid var(--border); padding-top: 1rem; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="claim_id" value="<?= htmlspecialchars($c['id']) ?>">
                    <input type="text" name="notes" placeholder="Lektori indoklás / jegyzet..." class="form-input" style="flex: 1; min-width: 250px; font-size: 0.85rem;">
                    
                    <button type="submit" name="decision" value="approved" class="btn btn-primary" style="font-size: 0.85rem; background: var(--success);">
                        ✓ Jóváhagyás
                    </button>
                    <button type="submit" name="decision" value="disputed" class="btn btn-warning" style="font-size: 0.85rem;">
                        ⚠️ Vitatás
                    </button>
                    <button type="submit" name="decision" value="rejected" class="btn btn-secondary" style="font-size: 0.85rem; color: var(--danger);">
                        ✕ Elutasítás
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
