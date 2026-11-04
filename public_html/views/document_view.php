<?php
declare(strict_types=1);

use NyitottMult\Database;

$docId = $id ?? ($_GET['id'] ?? '');
if (empty($docId)) {
    header('Location: /dokumentumok');
    exit;
}

$doc = Database::queryOne("SELECT * FROM source_documents WHERE id = :id", ['id' => $docId]);
if (!$doc) {
    http_response_code(404);
    echo "A keresett irat nem található.";
    exit;
}

$allPages = Database::query("SELECT id, page_number, thumbnail_path FROM pages WHERE document_id = :id ORDER BY page_number ASC", ['id' => $docId]);
$currentPageNum = isset($_GET['page']) ? (int)$_GET['page'] : ($allPages[0]['page_number'] ?? 1);

$currentPage = Database::queryOne(
    "SELECT * FROM pages WHERE document_id = :doc_id AND page_number = :p",
    ['doc_id' => $docId, 'p' => $currentPageNum]
);

$claims = [];
if ($currentPage) {
    $claims = Database::query(
        "SELECT c.*, u.full_name as creator_name, e.canonical_name as subject_name
         FROM claims c
         LEFT JOIN users u ON c.created_by = u.id
         LEFT JOIN entities e ON c.subject_entity_id = e.id
         WHERE c.page_id = :page_id",
        ['page_id' => $currentPage['id']]
    );
}

$pageTitle = $doc['title'] . " – " . $currentPageNum . ". oldal";
include __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap;">
        <a href="/dokumentumok" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">&larr; Vissza az iratokhoz</a>
        <span class="badge badge-verified"><?= htmlspecialchars($doc['document_type']) ?></span>
        <span style="font-family: monospace; font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($doc['archive_code']) ?></span>
        <?php if (!empty($doc['original_url'])): ?>
            <a href="<?= htmlspecialchars($doc['original_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.6rem; background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">
                🏛️ Hivatalos ÁBTL Nyitott Akták &rarr;
            </a>
        <?php endif; ?>
    </div>
    <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 0.25rem;">
        <?= htmlspecialchars($doc['title']) ?>
    </h1>
    <p style="font-size: 0.85rem; color: var(--text-muted);">
        Keltezés: <?= htmlspecialchars($doc['date_created'] ?? 'N/A') ?> | Megtekintett oldal: <strong><?= $currentPageNum ?>. oldal</strong>
    </p>
</div>

<!-- Lapozó sáv -->
<div class="card" style="margin-bottom: 1rem; padding: 0.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
        <span style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-right: 0.5rem;">Oldalak:</span>
        <?php foreach ($allPages as $p): ?>
            <a href="/dokumentumok/<?= urlencode($docId) ?>?page=<?= $p['page_number'] ?>" 
               class="btn btn-<?= $p['page_number'] === $currentPageNum ? 'primary' : 'secondary' ?>"
               style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                <?= $p['page_number'] ?>. o.
            </a>
        <?php endforeach; ?>
    </div>

    <div style="display: flex; gap: 0.5rem;">
        <button id="copyCitationBtn" class="btn btn-secondary" style="font-size: 0.8rem;">
            📋 Hivatkozás másolása
        </button>
        <button id="reportOcrBtn" class="btn btn-secondary" style="font-size: 0.8rem; color: var(--accent);">
            ✏️ OCR javítás jelzése
        </button>
    </div>
</div>

<!-- Kétoszlopos néző: Balra OpenSeadragon kép + Bounding Boxok, Jobbra OCR szöveg + Állítások -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
    <!-- Bal oszlop: OpenSeadragon képmegjelenítő -->
    <div>
        <div class="card" style="padding: 0.5rem;">
            <div id="openseadragon-viewer" style="height: 680px; background: #0f172a;"></div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; display: flex; justify-content: space-between;">
                <span>Egér görgő: Zoom | Húzás: Mozgatás</span>
                <span id="zoomDisplay">Zoom: 100%</span>
            </div>
        </div>
    </div>

    <!-- Jobb oszlop: OCR Szövegréteg és Forrásállítások -->
    <div>
        <!-- OCR Szövegréteg -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--primary);">
                    Szövegréteg (OCR)
                </h3>
                <span class="badge" style="background: #e0f2fe; color: #0369a1;">
                    Megbízhatóság: <?= round(($currentPage['ocr_confidence'] ?? 1.0) * 100) ?>%
                </span>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; padding: 1rem; font-family: monospace; font-size: 0.85rem; line-height: 1.7; max-height: 280px; overflow-y: auto; white-space: pre-wrap;" id="ocrTextContainer"><?= htmlspecialchars($currentPage['ocr_text_corrected'] ?: ($currentPage['ocr_text_raw'] ?? 'Nincs kinyert szöveg ezen az oldalon.')) ?></div>
        </div>

        <!-- Kapcsolódó Állítások (Claims) ezen az oldalon -->
        <div class="card">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
                Kapcsolódó Forrásállítások (<?= count($claims) ?>)
            </h3>

            <?php if (empty($claims)): ?>
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Ezen az oldalon még nem rögzítettek kutatói állítást.
                </p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($claims as $c): ?>
                        <div style="background: #ffffff; border: 1px solid var(--border); border-left: 4px solid var(--success); border-radius: 4px; padding: 0.85rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                                <span class="badge badge-verified">
                                    ✓ <?= htmlspecialchars($c['status']) ?>
                                </span>
                                <?php if (!empty($c['subject_name'])): ?>
                                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary);">
                                        Érintett: <?= htmlspecialchars($c['subject_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p style="font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($c['statement']) ?>
                            </p>

                            <div style="background: #f1f5f9; padding: 0.5rem; border-radius: 4px; font-size: 0.8rem; color: #334155; font-style: italic; margin-bottom: 0.5rem;">
                                "<?= htmlspecialchars($c['quote']) ?>"
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                                <span>Rögzítette: <?= htmlspecialchars($c['creator_name'] ?? 'Rendszer') ?></span>
                                <a href="/bizonyitekterkep" style="color: var(--secondary); font-weight: 600;">
                                    Bizonyítéktérkép &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Néző Inicializáló Szkript -->
<script>
    window.viewerConfig = {
        imagePath: '/<?= htmlspecialchars(ltrim($currentPage['image_path'] ?? 'assets/sample_docs/karton_sample_p1.webp', '/')) ?>',
        boxes: <?= $currentPage['ocr_boxes_json'] ?: '[]' ?>,
        citationText: '[<?= addslashes($doc['archive_code']) ?>, <?= (int)$currentPageNum ?>. o.]'
    };
</script>
<script src="/assets/js/viewer.js"></script>

<?php include __DIR__ . '/layout/footer.php'; ?>
