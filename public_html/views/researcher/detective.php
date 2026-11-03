<?php
declare(strict_types=1);

use NyitottMult\Auth;
use NyitottMult\Database;

if (!Auth::check() || !Auth::hasRole(Auth::ROLE_RESEARCHER)) {
    header('Location: /kutato/login');
    exit;
}

// AI által észlelt összefüggések (Dokumentumdetektív)
$suggestions = [
    [
        'id' => 'det-001',
        'type' => 'Azonos fedőnév és tartótiszt korreláció',
        'confidence' => 0.94,
        'entities' => ['MINTA Kovács János ("Homok")', 'MINTA Tóth Béla r. százados'],
        'summary' => 'A 6-os karton (1959) és az M-dosszié (1960. szept.) alapján Tóth Béla r. százados vezette a "Homok" fedőnevű informátort, akivel mindkét iratban az orosházi tanyavilág és mezőgazdasági üzemek megfigyelésére koncentráltak.',
        'doc_codes' => ['ÁBTL - 3.1.2. - MINTA-6-OS-KARTON-1959', 'ÁBTL - 3.1.2. - MINTA-M-9999-DOSSZIE'],
        'status' => 'javasolt'
    ],
    [
        'id' => 'det-002',
        'type' => 'Közös megfigyelési helyszín egyezés',
        'confidence' => 0.88,
        'entities' => ['MINTA Dr. Szabó István', 'Szabadság téri presszó'],
        'summary' => '1960 szeptemberében Dr. Szabó István gimnáziumi tanár a Szabadság téri presszóban folytatott beszélgetést, amely közvetlen egyezést mutat az Orosházi Politikai Nyomozó Kirendeltség egyidejű egyházügyi jelentésével.',
        'doc_codes' => ['ÁBTL - 3.1.2. - MINTA-M-9999-DOSSZIE'],
        'status' => 'javasolt'
    ]
];

$pageTitle = "Dokumentumdetektív (AI)";
include dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
        <a href="/kutato" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">&larr; Vissza a vezérlőpultra</a>
        <span class="badge" style="background: #fef3c7; color: #92400e;">Csak Belső Kutatói Felület</span>
    </div>
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        🕵️ Dokumentumdetektív (AI Javaslatok)
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        A gépi intelligencia által feltárt, ellenőrzésre váró összefüggések (azonos fedőnevek, tartótisztek, dátumok, helyszínek). <strong>Szigorú szabály:</strong> ezek a javaslatok a nyilvános oldalon SOHA nem jelennek meg, kizárólag a kutatói vizsgálatot segítik.
    </p>
</div>

<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <?php foreach ($suggestions as $s): ?>
        <div class="card" style="border-left: 5px solid var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span class="badge" style="background: #fef3c7; color: #92400e; margin-bottom: 0.35rem;">
                        <?= htmlspecialchars($s['type']) ?>
                    </span>
                    <span class="badge" style="background: #e0f2fe; color: #0369a1;">
                        AI Bizonyossági szint: <?= round($s['confidence'] * 100) ?>%
                    </span>
                </div>
                <span class="badge" style="background: #f1f5f9; color: #475569;">
                    Státusz: JAVASOLT (Nem nyilvános)
                </span>
            </div>

            <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                Érintett entitások: <?= htmlspecialchars(implode(' ↔ ', $s['entities'])) ?>
            </h3>

            <p style="font-size: 0.9rem; color: #334155; line-height: 1.6; margin-bottom: 1rem;">
                <?= htmlspecialchars($s['summary']) ?>
            </p>

            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.8rem;">
                <span style="font-weight: 700; color: #64748b;">Kapcsolódó Levéltári Iratok:</span>
                <ul style="list-style: none; margin-top: 0.25rem;">
                    <?php foreach ($s['doc_codes'] as $code): ?>
                        <li style="font-family: monospace; color: var(--primary);">📄 <?= htmlspecialchars($code) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button class="btn btn-secondary" style="font-size: 0.8rem; color: #991b1b;" onclick="alert('Javaslat elvetve a vizsgálatból.')">
                    ✕ Elvetés
                </button>
                <button class="btn btn-primary" style="font-size: 0.8rem;" onclick="alert('Átmozgatva a lektori jóváhagyási sorba.')">
                    ✓ Vizsgálat megindítása &rarr;
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
