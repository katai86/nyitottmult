<?php
declare(strict_types=1);

use NyitottMult\Database;
use NyitottMult\PrivacyEngine;

$entityId = $id ?? ($_GET['id'] ?? '');
if (empty($entityId)) {
    header('Location: /kereses');
    exit;
}

$rawEntity = Database::queryOne("SELECT e.*, pr.status as privacy_status, pr.death_year, pr.is_publicly_visible 
                                 FROM entities e 
                                 LEFT JOIN privacy_records pr ON e.id = pr.entity_id 
                                 WHERE e.id = :id", ['id' => $entityId]);

if (!$rawEntity) {
    http_response_code(404);
    echo "Az entitás nem található.";
    exit;
}

// Adatvédelmi szűrés
$entity = PrivacyEngine::sanitizeEntityForPublic($rawEntity, [
    'status' => $rawEntity['privacy_status'],
    'death_year' => $rawEntity['death_year'],
    'is_publicly_visible' => $rawEntity['is_publicly_visible']
]);

if (!empty($entity['is_protected'])) {
    http_response_code(403);
    $pageTitle = "Védett személy";
    include __DIR__ . '/layout/header.php';
    echo '<div class="card" style="text-align: center; padding: 3rem; max-width: 600px; margin: 3rem auto;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🛡️</div>
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #b91c1c; margin-bottom: 1rem;">2026. évi LI. törvény alapján védett személy</h2>
            <p style="color: var(--text-muted); line-height: 1.6;">A hatályos jogszabályok értelmében a nem anonimizált személyes adatok kizárólag a halálozás évét követő 5 év elteltével válnak nyilvánossá. Élő vagy tisztázatlan státuszú személyek adatlapja nyilvánosan nem tekinthető meg.</p>
            <a href="/" class="btn btn-primary" style="margin-top: 1.5rem;">Vissza a kezdőlapra</a>
          </div>';
    include __DIR__ . '/layout/footer.php';
    exit;
}

// Szerepek lekérése
$roles = Database::query(
    "SELECT r.*, d.title as doc_title, d.archive_code, c.quote as claim_quote, c.page_id
     FROM roles r
     JOIN source_documents d ON r.document_id = d.id
     LEFT JOIN claims c ON r.justification_claim_id = c.id
     WHERE r.entity_id = :id",
    ['id' => $entityId]
);

// Állítások lekérése
$claims = Database::query(
    "SELECT c.*, d.archive_code, d.title as doc_title, p.page_number
     FROM claims c
     JOIN source_documents d ON c.document_id = d.id
     JOIN pages p ON c.page_id = p.id
     WHERE c.subject_entity_id = :id AND c.status = 'ellenorzott'",
    ['id' => $entityId]
);

// Kapcsolatok lekérése
$relations = Database::query(
    "SELECT rel.*, e.canonical_name as target_name, e.type as target_type, c.quote as justification
     FROM relations rel
     JOIN entities e ON rel.target_entity_id = e.id
     LEFT JOIN claims c ON rel.claim_id = c.id
     WHERE rel.source_entity_id = :id",
    ['id' => $entityId]
);

$pageTitle = $entity['canonical_name'];
include __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
        <span class="badge" style="background: #e2e8f0; color: #1e293b;"><?= htmlspecialchars($entity['type']) ?></span>
        <?php if (!empty($rawEntity['is_sample'])): ?>
            <span class="badge" style="background: #fef3c7; color: #92400e;">MINTA Demonstrációs Adat</span>
        <?php endif; ?>
    </div>
    <h1 style="font-size: 2.25rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        <?= htmlspecialchars($entity['canonical_name']) ?>
    </h1>
    <?php 
    $aliases = json_decode($entity['aliases'] ?? '[]', true) ?: [];
    if (!empty($aliases)): ?>
        <p style="font-size: 0.9rem; color: var(--text-muted);">
            Névváltozatok és fedőnevek a jelentésekben: <strong><?= htmlspecialchars(implode(', ', $aliases)) ?></strong>
        </p>
    <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    <!-- Bal oszlop: Szerepkörök és Ellenőrzött Állítások -->
    <div>
        <!-- Dokumentált Szerepkörök -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">
                Dokumentált Szerepkörök az Iratokban
            </h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($roles as $r): ?>
                    <div style="border-left: 4px solid var(--primary); padding-left: 1rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                            <span class="badge badge-<?= $r['role_type'] === 'beszervezett_halozati_szemely' ? 'agent' : ($r['role_type'] === 'megfigyelt' ? 'observed' : 'officer') ?>">
                                <?= __("roles.{$r['role_type']}") ?>
                            </span>
                            <span style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">
                                <?= htmlspecialchars($r['archive_code']) ?>
                            </span>
                        </div>
                        <p style="font-size: 0.85rem; color: #334155; margin-bottom: 0.25rem;">
                            Forrásirat: <strong><?= htmlspecialchars($r['doc_title']) ?></strong>
                        </p>
                        <?php if (!empty($r['claim_quote'])): ?>
                            <div style="background: #f8fafc; padding: 0.5rem; border-radius: 4px; font-size: 0.8rem; color: #475569; font-style: italic;">
                                "<?= htmlspecialchars($r['claim_quote']) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ellenőrzött Állítások (Claims) -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">
                Forrásalapú Állítások (<?= count($claims) ?>)
            </h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($claims as $c): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span class="badge badge-verified">✓ Ellenőrzött állítás</span>
                            <a href="/dokumentumok/<?= urlencode($c['document_id']) ?>?page=<?= (int)$c['page_number'] ?>" 
                               class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                📄 <?= htmlspecialchars($c['archive_code']) ?>, <?= (int)$c['page_number'] ?>. o.
                            </a>
                        </div>
                        <p style="font-size: 0.95rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">
                            <?= htmlspecialchars($c['statement']) ?>
                        </p>
                        <blockquote style="border-left: 3px solid #cbd5e1; padding-left: 0.75rem; font-size: 0.85rem; color: #475569; font-style: italic;">
                            "<?= htmlspecialchars($c['quote']) ?>"
                        </blockquote>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Jobb oszlop: Metaadatok és Kapcsolatok -->
    <div>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
                Adatlap és Történeti Adatok
            </h3>
            <?php 
            $meta = json_decode($entity['metadata'] ?? '[]', true) ?: [];
            ?>
            <ul style="list-style: none; font-size: 0.85rem; line-height: 1.8; color: #334155;">
                <?php foreach ($meta as $k => $v): ?>
                    <?php if (is_string($v)): ?>
                        <li><strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $k))) ?>:</strong> <?= htmlspecialchars($v) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li><strong>Közzétehetőség:</strong> 2026. évi LI. tv. alapján ellenőrizve</li>
            </ul>
        </div>

        <!-- Hálózati Kapcsolatok -->
        <div class="card">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
                Kapcsolati Háló
            </h3>
            <?php if (empty($relations)): ?>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Nincs rögzített kapcsolat.</p>
            <?php else: ?>
                <ul style="list-style: none; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($relations as $rel): ?>
                        <li style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                            <span class="badge" style="background: #e2e8f0; font-size: 0.7rem;">
                                <?= htmlspecialchars($rel['relation_type']) ?>
                            </span>
                            <div style="font-weight: 600; margin-top: 0.2rem;">
                                <a href="/entitasok/<?= urlencode($rel['target_entity_id']) ?>" style="color: var(--primary); text-decoration: none;">
                                    <?= htmlspecialchars($rel['target_name']) ?>
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a href="/kapcsolati-halo" class="btn btn-secondary" style="width: 100%; margin-top: 1rem; font-size: 0.8rem;">
                Kapcsolati gráf megnyitása &rarr;
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
