<?php
declare(strict_types=1);
$pageTitle = __('nav.stories');
include __DIR__ . '/layout/header.php';

use NyitottMult\Database;

$stories = Database::query("SELECT * FROM stories WHERE is_published = 1 ORDER BY created_at DESC");
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        📖 <?= __('nav.stories') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Levéltári forrásokra és térképi helyszínekre épülő interaktív helytörténeti történetek.
    </p>
</div>

<div class="grid grid-cols-2">
    <?php foreach ($stories as $story): ?>
        <?php
        $steps = Database::query(
            "SELECT ss.*, d.archive_code, c.quote 
             FROM story_steps ss
             LEFT JOIN source_documents d ON ss.document_id = d.id
             LEFT JOIN claims c ON ss.claim_id = c.id
             WHERE ss.story_id = :sid ORDER BY ss.step_number ASC",
            ['sid' => $story['id']]
        );
        ?>
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <span class="badge" style="background: #e0f2fe; color: #0369a1; margin-bottom: 0.5rem;">
                    <?= count($steps) ?> fejezet
                </span>
                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">
                    <?= htmlspecialchars($story['title']) ?>
                </h2>
                <p style="font-size: 0.9rem; color: #334155; line-height: 1.6; margin-bottom: 1rem;">
                    <?= htmlspecialchars($story['summary']) ?>
                </p>

                <!-- Lépések listája -->
                <div style="border-top: 1px solid var(--border); padding-top: 0.75rem; margin-bottom: 1rem;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">Fejezetek:</div>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php foreach ($steps as $st): ?>
                            <li style="font-size: 0.85rem; color: #475569; display: flex; gap: 0.5rem; align-items: flex-start;">
                                <span style="font-weight: 700; color: var(--primary);"><?= $st['step_number'] ?>.</span>
                                <div>
                                    <strong><?= htmlspecialchars($st['title']) ?></strong>
                                    <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($st['content']) ?></div>
                                    <?php if (!empty($st['archive_code'])): ?>
                                        <div style="font-size: 0.75rem; color: var(--secondary); font-family: monospace; margin-top: 0.2rem;">
                                            Forrás: <?= htmlspecialchars($st['archive_code']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                <span style="font-size: 0.8rem; color: var(--text-muted);">Szerző: <?= htmlspecialchars($story['author_name']) ?></span>
                <a href="/terkep" class="btn btn-primary" style="font-size: 0.8rem;">
                    🗺️ Megtekintés térképen &rarr;
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
