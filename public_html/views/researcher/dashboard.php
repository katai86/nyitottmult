<?php
declare(strict_types=1);

use NyitottMult\Auth;
use NyitottMult\Database;

if (!Auth::check() || !Auth::hasRole(Auth::ROLE_RESEARCHER)) {
    header('Location: /kutato/login');
    exit;
}

$user = Auth::user();

$pendingClaims = Database::queryOne("SELECT COUNT(*) as c FROM claims WHERE status = 'javasolt'")['c'] ?? 0;
$pendingSubmissions = Database::queryOne("SELECT COUNT(*) as c FROM submissions WHERE moderation_status = 'pending'")['c'] ?? 0;
$pendingCorrections = Database::queryOne("SELECT COUNT(*) as c FROM correction_requests WHERE status = 'beerkett'")['c'] ?? 0;
$openHypotheses = Database::queryOne("SELECT COUNT(*) as c FROM hypotheses WHERE status = 'nyitott'")['c'] ?? 0;

$tasks = Database::query(
    "SELECT t.*, u.full_name as assignee_name 
     FROM research_tasks t 
     LEFT JOIN users u ON t.assigned_to = u.id 
     ORDER BY t.priority DESC LIMIT 5"
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
    <div style="display: flex; gap: 0.5rem;">
        <a href="/kutato/detektiv" class="btn btn-primary" style="font-size: 0.85rem;">
            🕵️ Dokumentumdetektív (AI)
        </a>
        <a href="/kutato/ellenorzes" class="btn btn-warning" style="font-size: 0.85rem;">
            ⚖️ Kétlépcsős Ellenőrzés (<?= $pendingClaims ?>)
        </a>
        <a href="/kutato/login?action=logout" class="btn btn-secondary" style="font-size: 0.85rem;">
            Kijelentkezés
        </a>
    </div>
</div>

<!-- Gyorsstatisztikák -->
<div class="grid grid-cols-4" style="margin-bottom: 2rem;">
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--warning);"><?= $pendingClaims ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted);">Jóváhagyásra váró állítás</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--secondary);">
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--secondary);"><?= $pendingSubmissions ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted);">Lakossági beküldés</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--danger);"><?= $pendingCorrections ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted);">Helyesbítési kérelem</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--primary);"><?= $openHypotheses ?></div>
        <div style="font-size: 0.85rem; color: var(--text-muted);">Nyitott hipotézis</div>
    </div>
</div>

<!-- Aktuális Kutatási Feladatok -->
<div class="card" style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">
        Kutatási Feladatok (Tasks)
    </h2>
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <?php foreach ($tasks as $t): ?>
            <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span class="badge" style="background: <?= $t['priority'] === 'high' ? '#fee2e2; color: #991b1b;' : '#f1f5f9; color: #475569;' ?> margin-bottom: 0.25rem;">
                        <?= htmlspecialchars($t['priority']) ?>
                    </span>
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a;">
                        <?= htmlspecialchars($t['title']) ?>
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        Felelős: <?= htmlspecialchars($t['assignee_name'] ?? 'Nincs hozzárendelve') ?> | Határidő: <?= htmlspecialchars($t['deadline'] ?? 'Rugalmas') ?>
                    </div>
                </div>
                <span class="badge" style="background: #e0f2fe; color: #0369a1;">
                    <?= htmlspecialchars($t['status']) ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
