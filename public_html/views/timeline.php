<?php
declare(strict_types=1);
$pageTitle = __('nav.timeline');
include __DIR__ . '/layout/header.php';

use NyitottMult\Database;

// Idővonal események és iratok lekérése
$events = Database::query(
    "SELECT d.date_created as event_date, d.title, d.archive_code, d.id as doc_id, 'irat' as item_type,
            'Levéltári irat keletkezése' as category
     FROM source_documents d
     WHERE d.date_created IS NOT NULL
     UNION ALL
     SELECT c.created_at as event_date, c.statement as title, d.archive_code, c.document_id as doc_id, 'claim' as item_type,
            'Ellenőrzött forrásállítás' as category
     FROM claims c
     JOIN source_documents d ON c.document_id = d.id
     WHERE c.status = 'ellenorzott'
     ORDER BY event_date ASC LIMIT 50"
);
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        ⏳ <?= __('nav.timeline') ?> (1945–1990)
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Az orosházi állambiztonsági működés, beszervezések és megfigyelési jelentések időrendi áttekintése.
    </p>
</div>

<!-- Idővonal sáv -->
<div style="position: relative; max-width: 800px; margin: 0 auto; padding-left: 2rem; border-left: 3px solid var(--primary);">
    <?php if (empty($events)): ?>
        <p style="color: var(--text-muted);">Nincs megjeleníthető idővonal-adat.</p>
    <?php else: ?>
        <?php foreach ($events as $idx => $ev): ?>
            <div style="margin-bottom: 2rem; position: relative;">
                <!-- Idővonal pötty -->
                <div style="position: absolute; left: -2.6rem; top: 0.2rem; width: 1.1rem; height: 1.1rem; border-radius: 50%; background: #ffffff; border: 3px solid var(--primary);"></div>

                <div class="card" style="padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <span style="font-size: 0.85rem; font-weight: 700; color: var(--accent);">
                            📅 <?= htmlspecialchars(substr((string)$ev['event_date'], 0, 10)) ?>
                        </span>
                        <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.75rem;">
                            <?= htmlspecialchars($ev['category']) ?>
                        </span>
                    </div>

                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($ev['title']) ?>
                    </h3>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
                        <span style="font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                            <?= htmlspecialchars($ev['archive_code']) ?>
                        </span>
                        <a href="/dokumentumok/<?= urlencode($ev['doc_id']) ?>" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                            Irat megtekintése &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
