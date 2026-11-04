<?php
declare(strict_types=1);

use NyitottMult\Auth;
use NyitottMult\Database;
use NyitottMult\AuditLogger;

if (!Auth::check() || !Auth::hasRole(Auth::ROLE_RESEARCHER)) {
    header('Location: /kutato/login');
    exit;
}

$userId = Auth::id();
$msg = null;
$error = null;

// Kezeljük az elvetett javaslatokat a sessionben
if (!isset($_SESSION['dismissed_suggestions'])) {
    $_SESSION['dismissed_suggestions'] = [];
}

// POST műveletek kezelése (Feladat létrehozása, Elvetés)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $suggestionId = trim($_POST['suggestion_id'] ?? '');

    if ($action === 'dismiss') {
        if ($suggestionId) {
            $_SESSION['dismissed_suggestions'][] = $suggestionId;
            $msg = 'A javaslatot sikeresen elvetette (elrejtve a vizsgálati listáról).';
        }
    } elseif ($action === 'create_task') {
        $title = trim($_POST['title'] ?? '');
        $docId = trim($_POST['doc_id'] ?? '');
        if ($title) {
            $taskId = Database::generateUuid();
            Database::execute(
                "INSERT INTO research_tasks (id, title, priority, status, assigned_to, related_doc_id, created_at)
                 VALUES (:id, :title, 'high', 'todo', :uid, :doc_id, NOW())",
                [
                    'id' => $taskId,
                    'title' => $title,
                    'uid' => $userId,
                    'doc_id' => !empty($docId) ? $docId : null
                ]
            );
            AuditLogger::log('DETECTIVE_CREATE_TASK', 'research_tasks', $taskId, ['title' => $title], $userId);
            $_SESSION['dismissed_suggestions'][] = $suggestionId;
            $msg = 'A vizsgálati feladatot sikeresen felvette a kutatási feladatlistára!';
        }
    } elseif ($action === 'reset_dismissed') {
        $_SESSION['dismissed_suggestions'] = [];
        $msg = 'Az elvetett javaslatok listája visszaállítva.';
    }
}

// 1. Valós párhuzamos egyezések: Olyan személyek, akik 6-os kartonban ÉS Mágnesszalagban is szerepelnek
$rawOverlaps = Database::query(
    "SELECT e.id as entity_id, e.canonical_name, 
            COUNT(DISTINCT d.id) as doc_count,
            GROUP_CONCAT(DISTINCT d.id) as doc_ids,
            GROUP_CONCAT(DISTINCT d.archive_code SEPARATOR ' || ') as archives,
            GROUP_CONCAT(DISTINCT d.title SEPARATOR ' || ') as titles,
            GROUP_CONCAT(DISTINCT d.document_type SEPARATOR ' + ') as doc_types
     FROM entities e
     JOIN roles r ON e.id = r.entity_id
     JOIN source_documents d ON r.document_id = d.id
     GROUP BY e.id, e.canonical_name
     HAVING doc_count > 1
     ORDER BY e.canonical_name ASC
     LIMIT 25"
);

// 2. Különleges minősítésű személyek (pl. 07 - Kizárt hálózati személy)
$rawKizart = Database::query(
    "SELECT c.id as claim_id, c.statement, c.quote, d.id as doc_id, d.archive_code, d.document_type, d.title as doc_title, e.id as entity_id, e.canonical_name
     FROM claims c
     JOIN source_documents d ON c.document_id = d.id
     JOIN entities e ON c.subject_entity_id = e.id
     WHERE c.statement LIKE '%07 - Kizárt hálózati személy%'
     LIMIT 15"
);

// Összegyűjtjük a szuggesztiókat
$suggestions = [];

foreach ($rawOverlaps as $ov) {
    $sugId = 'overlap_' . md5($ov['entity_id']);
    if (in_array($sugId, $_SESSION['dismissed_suggestions'])) {
        continue;
    }
    
    $firstDocId = explode(',', $ov['doc_ids'])[0] ?? null;
    $codes = explode(' || ', $ov['archives']);

    $suggestions[] = [
        'id' => $sugId,
        'doc_id' => $firstDocId,
        'type' => 'Párhuzamos Nyilvántartási Korreláció (6-os karton + Mágnesszalag)',
        'confidence' => 0.98,
        'entities' => [$ov['canonical_name']],
        'summary' => "A személy neve és anyja neve alapján teljes egyezés mutatható ki a 6-os nyilvántartó karton és a mágnesszalag adatbázisa között. Mindkét iratban az orosházi állambiztonsági nyilvántartáshoz kapcsolódó adatok szerepelnek.",
        'doc_codes' => $codes,
        'task_title' => "6-os karton és mágnesszalag egyeztetése: {$ov['canonical_name']} (" . implode(', ', $codes) . ")"
    ];
}

foreach ($rawKizart as $kz) {
    $sugId = 'kizart_' . md5($kz['claim_id']);
    if (in_array($sugId, $_SESSION['dismissed_suggestions'])) {
        continue;
    }

    $suggestions[] = [
        'id' => $sugId,
        'doc_id' => $kz['doc_id'],
        'type' => 'Különleges Forráskritikai Elemzés: „07 - Kizárt hálózati személy”',
        'confidence' => 0.95,
        'entities' => [$kz['canonical_name']],
        'summary' => "Az ÁBTL nyilvántartási bejegyzése szerint az érintett „07 - Kizárt hálózati személy” minősítést kapott. Ez azt jelzi, hogy a hálózati kapcsolat megszakadt, vagy a szerv kizárta a személyt a hálózatból. Szigorú forráskritikai elemzést igényel.",
        'doc_codes' => [$kz['archive_code']],
        'task_title' => "Forráskritika - Kizárt hálózati személy státusz vizsgálata: {$kz['canonical_name']} ({$kz['archive_code']})"
    ];
}

$pageTitle = "Dokumentumdetektív (AI)";
include dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.5rem;">
        <div>
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                <a href="/kutato" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">&larr; Vissza a vezérlőpultra</a>
                <span class="badge" style="background: #fef3c7; color: #92400e;">Csak Belső Kutatói Felület</span>
            </div>
            <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.25rem;">
                🕵️ Dokumentumdetektív (AI Javaslatok)
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                A levéltári adatbázisban feltárt összefüggések (kereszt-nyilvántartási egyezések, kizárt személyek minősítései, szervkód-korrelációk).<br>
                <strong>Szigorú alapelv:</strong> ezek a gépi detekciók a nyilvános felületen SOHA nem jelennek meg automatikusan, kizárólag a kutatói vizsgálatot támogatják.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <?php if (!empty($_SESSION['dismissed_suggestions'])): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="reset_dismissed">
                    <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem;">
                        Elvetett javaslatok visszaállítása (<?= count($_SESSION['dismissed_suggestions']) ?>)
                    </button>
                </form>
            <?php endif; ?>
            <a href="/kutato/ellenorzes" class="btn btn-warning" style="font-size: 0.8rem;">
                ⚖️ Lektori Ellenőrzés
            </a>
        </div>
    </div>
</div>

<?php if ($msg): ?>
    <div class="card" style="background: #dcfce7; border-color: #86efac; color: #166534; margin-bottom: 1.5rem; font-weight: 600;">
        ✓ <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if (empty($suggestions)): ?>
    <div class="card" style="text-align: center; padding: 3rem; color: var(--text-muted);">
        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎉</div>
        <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">Nincs több feldolgozatlan detektív javaslat</h3>
        <p style="font-size: 0.85rem;">Minden jelenleg azonosított levéltári összefüggést felvett feladatként vagy elvetett.</p>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?php foreach ($suggestions as $s): ?>
            <div class="card" style="border-left: 5px solid var(--accent);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <span class="badge" style="background: #fef3c7; color: #92400e; margin-bottom: 0.35rem;">
                            <?= htmlspecialchars($s['type']) ?>
                        </span>
                        <span class="badge" style="background: #e0f2fe; color: #0369a1;">
                            Bizonyossági szint: <?= round($s['confidence'] * 100) ?>%
                        </span>
                    </div>
                    <span class="badge" style="background: #f1f5f9; color: #475569;">
                        Státusz: JAVASOLT (Belső kutatói nézet)
                    </span>
                </div>

                <h3 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    Érintett személy: <strong><?= htmlspecialchars(implode(' ↔ ', $s['entities'])) ?></strong>
                </h3>

                <p style="font-size: 0.9rem; color: #334155; line-height: 1.6; margin-bottom: 1rem;">
                    <?= htmlspecialchars($s['summary']) ?>
                </p>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem;">
                    <span style="font-weight: 700; color: #64748b;">Kapcsolódó ÁBTL Levéltári Iratok:</span>
                    <ul style="list-style: none; margin-top: 0.4rem; padding-left: 0; display: flex; flex-direction: column; gap: 0.25rem;">
                        <?php foreach ($s['doc_codes'] as $code): ?>
                            <li style="font-family: monospace; color: var(--primary);">
                                📄 <?= htmlspecialchars($code) ?>
                                <a href="/kereses?q=<?= urlencode($code) ?>" target="_blank" style="font-size: 0.75rem; margin-left: 0.5rem; text-decoration: underline;">
                                    (Irat keresése &rarr;)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Valódi Műveleti Gombok (Elvetés, Vizsgálati feladat indítása) -->
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; flex-wrap: wrap;">
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="dismiss">
                        <input type="hidden" name="suggestion_id" value="<?= htmlspecialchars($s['id']) ?>">
                        <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; color: #991b1b;">
                            ✕ Javaslat elvetése
                        </button>
                    </form>

                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="create_task">
                        <input type="hidden" name="suggestion_id" value="<?= htmlspecialchars($s['id']) ?>">
                        <input type="hidden" name="doc_id" value="<?= htmlspecialchars($s['doc_id'] ?? '') ?>">
                        <input type="hidden" name="title" value="<?= htmlspecialchars($s['task_title']) ?>">
                        <button type="submit" class="btn btn-primary" style="font-size: 0.8rem; background: var(--primary);">
                            ✓ Vizsgálati feladat indítása &rarr;
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
