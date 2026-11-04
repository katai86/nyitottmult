<?php
declare(strict_types=1);

use NyitottMult\Auth;
use NyitottMult\Database;
use NyitottMult\AuditLogger;

if (!Auth::check() || !Auth::hasRole(Auth::ROLE_RESEARCHER)) {
    header('Location: /kutato/login');
    exit;
}

$user = Auth::user();
$userId = Auth::id();
$msg = null;
$error = null;

// POST feladatkezelés (Feladat elvégzése, törlése, új létrehozása)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_task_done') {
        $taskId = trim($_POST['task_id'] ?? '');
        if ($taskId) {
            Database::execute("UPDATE research_tasks SET status = 'done' WHERE id = :id", ['id' => $taskId]);
            AuditLogger::log('TASK_MARK_DONE', 'research_tasks', $taskId, ['status' => 'done'], $userId);
            $msg = 'A feladat sikeresen lezárva (kész)!';
        }
    } elseif ($action === 'delete_task') {
        $taskId = trim($_POST['task_id'] ?? '');
        if ($taskId) {
            Database::execute("DELETE FROM research_tasks WHERE id = :id", ['id' => $taskId]);
            AuditLogger::log('TASK_DELETE', 'research_tasks', $taskId, null, $userId);
            $msg = 'A kutatási feladatot törölte.';
        }
    } elseif ($action === 'create_task') {
        $title = trim($_POST['title'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

        if (empty($title)) {
            $error = 'A feladat címe kötelező!';
        } else {
            $taskId = Database::generateUuid();
            Database::execute(
                "INSERT INTO research_tasks (id, title, priority, status, assigned_to, deadline, created_at)
                 VALUES (:id, :title, :prio, 'todo', :uid, :deadline, NOW())",
                [
                    'id' => $taskId,
                    'title' => $title,
                    'prio' => in_array($priority, ['low', 'medium', 'high', 'urgent']) ? $priority : 'medium',
                    'uid' => $userId,
                    'deadline' => $deadline
                ]
            );
            AuditLogger::log('TASK_CREATE', 'research_tasks', $taskId, ['title' => $title, 'priority' => $priority], $userId);
            $msg = 'Új kutatási feladat sikeresen rögzítve!';
        }
    }
}

$pendingClaims = Database::queryOne("SELECT COUNT(*) as c FROM claims WHERE status = 'javasolt'")['c'] ?? 0;
$pendingSubmissions = Database::queryOne("SELECT COUNT(*) as c FROM submissions WHERE moderation_status = 'pending'")['c'] ?? 0;
$pendingCorrections = Database::queryOne("SELECT COUNT(*) as c FROM correction_requests WHERE status = 'beerkett'")['c'] ?? 0;
$openHypotheses = Database::queryOne("SELECT COUNT(*) as c FROM hypotheses WHERE status = 'nyitott'")['c'] ?? 0;

$tasks = Database::query(
    "SELECT t.*, u.full_name as assignee_name 
     FROM research_tasks t 
     LEFT JOIN users u ON t.assigned_to = u.id 
     ORDER BY CASE WHEN t.status = 'done' THEN 2 ELSE 1 END, 
              CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END, 
              t.created_at DESC 
     LIMIT 10"
);

$pageTitle = "Kutatói Vezérlőpult";
include dirname(__DIR__) . '/layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.25rem;">
            🔒 Kutatói Vezérlőpult
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Bejelentkezve: <strong><?= htmlspecialchars($user['full_name']) ?></strong> (Szerepkör: <span class="badge" style="background: #e2e8f0;"><?= htmlspecialchars($user['role']) ?></span>)
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="/kutato/ellenorzes" class="btn btn-warning" style="font-size: 0.85rem;">
            ⚖️ Lektori Ellenőrzés (<?= $pendingClaims ?>)
        </a>
        <a href="/kutato/moderacio" class="btn btn-secondary" style="font-size: 0.85rem;">
            📬 Moderáció (<?= $pendingSubmissions + $pendingCorrections ?>)
        </a>
        <a href="/kutato/detektiv" class="btn btn-primary" style="font-size: 0.85rem;">
            🕵️ Dokumentumdetektív (AI)
        </a>
        <a href="/kutato/login?action=logout" class="btn btn-secondary" style="font-size: 0.85rem; color: #991b1b;">
            Kijelentkezés
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="card" style="background: #dcfce7; border-color: #86efac; color: #166534; margin-bottom: 1.5rem; font-weight: 600;">
        ✓ <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="card" style="background: #fee2e2; border-color: #fca5a5; color: #991b1b; margin-bottom: 1.5rem; font-weight: 600;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Kattintható Gyorsstatisztikák -->
<div class="grid grid-cols-4" style="margin-bottom: 2rem;">
    <a href="/kutato/ellenorzes?status=pending" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-left: 4px solid var(--warning); height: 100%; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--warning);"><?= $pendingClaims ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Jóváhagyásra váró állítás</div>
            <div style="font-size: 0.75rem; color: var(--primary); margin-top: 0.25rem;">Megnyitás &rarr;</div>
        </div>
    </a>
    
    <a href="/kutato/moderacio?tab=submissions&status=pending" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-left: 4px solid var(--secondary); height: 100%; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--secondary);"><?= $pendingSubmissions ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Lakossági beküldés</div>
            <div style="font-size: 0.75rem; color: var(--primary); margin-top: 0.25rem;">Moderáció &rarr;</div>
        </div>
    </a>
    
    <a href="/kutato/moderacio?tab=corrections&status=beerkett" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-left: 4px solid var(--danger); height: 100%; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--danger);"><?= $pendingCorrections ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Helyesbítési kérelem</div>
            <div style="font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem;">Kivizsgálás &rarr;</div>
        </div>
    </a>
    
    <a href="/kutato/detektiv" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-left: 4px solid var(--primary); height: 100%; transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--primary);">88+</div>
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Detektív egyezés (AI)</div>
            <div style="font-size: 0.75rem; color: var(--primary); margin-top: 0.25rem;">Vizsgálat &rarr;</div>
        </div>
    </a>
</div>

<!-- Kutatási Feladatok -->
<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin: 0;">
            📋 Kutatási Feladatok (Research Tasks)
        </h2>
        <button class="btn btn-secondary" style="font-size: 0.8rem;" onclick="document.getElementById('newTaskForm').style.display = document.getElementById('newTaskForm').style.display === 'none' ? 'block' : 'none';">
            ➕ Új feladat hozzáadása
        </button>
    </div>

    <!-- Rejtett új feladat űrlap -->
    <div id="newTaskForm" style="display: none; background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.75rem;">Új kutatási feladat rögzítése</h3>
        <form method="POST" action="/kutato" style="display: flex; flex-direction: column; gap: 0.75rem;">
            <input type="hidden" name="action" value="create_task">
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Feladat leírása / címe *</label>
                <input type="text" name="title" required placeholder="pl. Orosházi M-dossziék lehallgatási jegyzőkönyveinek egybevetése" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; font-size: 0.85rem;">
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Prioritás</label>
                    <select name="priority" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; font-size: 0.85rem;">
                        <option value="medium">Közepes (Normál)</option>
                        <option value="high">Magas</option>
                        <option value="urgent">Sürgős (Törvényi határidős)</option>
                        <option value="low">Alacsony</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Határidő (opcionális)</label>
                    <input type="date" name="deadline" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; font-size: 0.85rem;">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" style="font-size: 0.8rem;" onclick="document.getElementById('newTaskForm').style.display='none';">Mégse</button>
                <button type="submit" class="btn btn-primary" style="font-size: 0.8rem;">Feladat mentése</button>
            </div>
        </form>
    </div>

    <?php if (empty($tasks)): ?>
        <div style="text-align: center; padding: 2.5rem; color: var(--text-muted); background: #f8fafc; border-radius: var(--radius); border: 1px dashed var(--border);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📁</div>
            <h4 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">Jelenleg nincsenek aktív kutatási feladatok</h4>
            <p style="font-size: 0.85rem; max-width: 500px; margin: 0 auto 1rem auto;">
                A kutatói munkafolyamat során itt követheti nyomon a felülvizsgálatokat, vagy a Dokumentumdetektív AI korrelációiból egy kattintással indíthat vizsgálatot.
            </p>
            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                <button class="btn btn-secondary" style="font-size: 0.8rem;" onclick="document.getElementById('newTaskForm').style.display='block';">
                    ➕ Új feladat
                </button>
                <a href="/kutato/detektiv" class="btn btn-primary" style="font-size: 0.8rem;">
                    🕵️ Dokumentumdetektív megnyitása &rarr;
                </a>
            </div>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <?php foreach ($tasks as $t): ?>
                <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 0.85rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; background: <?= $t['status'] === 'done' ? '#f8fafc; opacity: 0.75;' : '#ffffff;' ?>">
                    <div style="flex: 1; min-width: 250px;">
                        <div style="display: flex; gap: 0.4rem; align-items: center; margin-bottom: 0.25rem;">
                            <span class="badge" style="background: <?= $t['priority'] === 'urgent' || $t['priority'] === 'high' ? '#fee2e2; color: #991b1b;' : '#f1f5f9; color: #475569;' ?>; font-size: 0.7rem;">
                                <?= htmlspecialchars($t['priority']) ?>
                            </span>
                            <span class="badge" style="background: <?= $t['status'] === 'done' ? '#dcfce7; color: #166534;' : '#e0f2fe; color: #0369a1;' ?>; font-size: 0.7rem;">
                                <?= htmlspecialchars($t['status'] === 'done' ? '✓ Kész' : 'Folyamatban') ?>
                            </span>
                        </div>
                        <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.2rem; <?= $t['status'] === 'done' ? 'text-decoration: line-through;' : '' ?>">
                            <?= htmlspecialchars($t['title']) ?>
                        </h3>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            Felelős: <?= htmlspecialchars($t['assignee_name'] ?? 'Nincs hozzárendelve') ?> 
                            <?php if ($t['deadline']): ?>
                                | Határidő: <?= htmlspecialchars($t['deadline']) ?>
                            <?php endif; ?>
                            | Létrehozva: <?= htmlspecialchars(substr($t['created_at'], 0, 10)) ?>
                        </div>
                    </div>
                    
                    <!-- Műveleti gombok (Lezárás, Törlés) -->
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <?php if ($t['status'] !== 'done'): ?>
                            <form method="POST" action="/kutato" style="margin: 0;">
                                <input type="hidden" name="action" value="mark_task_done">
                                <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['id']) ?>">
                                <button type="submit" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.35rem 0.6rem; background: #16a34a; border-color: #16a34a;">
                                    ✓ Kész
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" action="/kutato" style="margin: 0;" onsubmit="return confirm('Biztosan törölni kívánja ezt a kutatási feladatot?');">
                            <input type="hidden" name="action" value="delete_task">
                            <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['id']) ?>">
                            <button type="submit" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.35rem 0.6rem; color: #991b1b;">
                                🗑️ Törlés
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
