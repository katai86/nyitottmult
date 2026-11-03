<?php
declare(strict_types=1);
$pageTitle = __('nav.correction');

use NyitottMult\Database;

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['applicant_name'] ?? '');
    $email = trim($_POST['applicant_email'] ?? '');
    $rel = trim($_POST['applicant_relationship'] ?? '');
    $type = $_POST['request_type'] ?? 'kiegeszito_nyilatkozat';
    $ground = trim($_POST['legal_ground'] ?? '');
    $statement = trim($_POST['statement_text'] ?? '');

    if (empty($name) || empty($email) || empty($ground) || empty($statement)) {
        $error = 'Kérjük, töltse ki az összes kötelező mezőt a jogi vizsgálat megindításához!';
    } else {
        $id = Database::generateUuid();
        $deadline = date('Y-m-d', strtotime('+30 days')); // 30 napos törvényi vizsgálati határidő

        $ok = Database::execute(
            "INSERT INTO correction_requests (id, applicant_name, applicant_email, applicant_relationship, request_type, legal_ground, statement_text, status, deadline, created_at)
             VALUES (:id, :name, :email, :rel, :type, :ground, :stmt, 'beerkett', :deadline, NOW())",
            [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'rel' => $rel,
                'type' => $type,
                'ground' => $ground,
                'stmt' => $statement,
                'deadline' => $deadline
            ]
        );

        if ($ok) {
            $message = "Helyesbítési / kiegészítő nyilatkozati kérelmét sikeresen rögzítettük (Ügyiratszám: " . substr($id, 0, 8) . "). Törvényes kivizsgálási határidő: {$deadline}.";
        } else {
            $error = 'Hiba történt a kérelem mentésekor.';
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        📝 <?= __('nav.correction') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Kiegészítő nyilatkozat közzététele vagy téves adat helyesbítése a <strong>2026. évi LI. törvény</strong> és az információs önrendelkezési jog alapján.
    </p>
</div>

<!-- Jogi tájékoztató doboz -->
<div class="card" style="background: #f8fafc; border-left: 4px solid var(--accent); margin-bottom: 2rem; padding: 1.25rem;">
    <h3 style="font-size: 1rem; font-weight: 700; color: var(--accent); margin-bottom: 0.5rem;">
        A helyesbítés és kiegészítő nyilatkozat joga
    </h3>
    <p style="font-size: 0.85rem; color: #334155; line-height: 1.6;">
        A törvényi előírások alapján az érintett vagy közvetlen hozzátartozója jogosult kezdeményezni téves levéltári tények felülvizsgálatát, vagy <strong>kiegészítő nyilatkozatot tenni</strong>, amely az adott személy adatlapján a forrásiratok mellett kötelezően, elválaszthatatlanul megjelenítésre kerül.
    </p>
</div>

<?php if ($message): ?>
    <div class="card" style="background: #dcfce7; border-color: #86efac; color: #166534; margin-bottom: 2rem; font-weight: 600;">
        ✓ <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="card" style="background: #fee2e2; border-color: #fca5a5; color: #991b1b; margin-bottom: 2rem; font-weight: 600;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    <form method="POST" action="/helyesbites" class="card">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1.25rem;">
            Kérelem Benyújtása
        </h2>

        <div class="form-group">
            <label class="form-label">Kérelmező Teljes Neve *</label>
            <input type="text" name="applicant_name" required class="form-input" placeholder="Az Ön neve">
        </div>

        <div class="form-group">
            <label class="form-label">Kapcsolattartási E-mail cím *</label>
            <input type="email" name="applicant_email" required class="form-input" placeholder="email@example.hu">
        </div>

        <div class="form-group">
            <label class="form-label">Érintettség / Kapcsolat az érintett személlyel</label>
            <input type="text" name="applicant_relationship" class="form-input" placeholder="Pl. Érintett saját maga, közvetlen leszármazott (gyermek, unoka)">
        </div>

        <div class="form-group">
            <label class="form-label">Kérelem Típusa</label>
            <select name="request_type" class="form-select">
                <option value="kiegeszito_nyilatkozat">Kiegészítő nyilatkozat közzététele az adatlapon</option>
                <option value="adatjavitas">Ténybeli vagy névelírási hiba helyesbítése</option>
                <option value="torles_vagy_korlatozas">Törlési vagy korlátozási kérelem (élő személy / határidő előtt)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Jogi Indoklás / Érintett Levéltári Forrás *</label>
            <textarea name="legal_ground" rows="3" required class="form-textarea" placeholder="Jelölje meg az érintett iratjelzetet, oldalszámot vagy személyt, valamint a kérelem indokát..."></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">A közzétenni vagy helyesbíteni kért nyilatkozat szövege *</label>
            <textarea name="statement_text" rows="5" required class="form-textarea" placeholder="Írja le a megjeleníteni kívánt kiegészítő nyilatkozat pontos szövegét..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
            ⚖️ Helyesbítési Kérelem Hivatalos Benyújtása
        </button>
    </form>

    <div>
        <div class="card">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
                Hivatalos Eljárási Rend
            </h3>
            <ul style="font-size: 0.85rem; color: #475569; line-height: 1.7; padding-left: 1rem;">
                <li><strong>Nyilvántartásba vétel:</strong> A rendszer egyedi ügyszámot generál.</li>
                <li><strong>Határidő:</strong> Legkésőbb 30 napon belül érdemi vizsgálatot végzünk.</li>
                <li><strong>Kiegészítő nyilatkozat:</strong> A jóváhagyott kiegészítő nyilatkozat közvetlenül az érintett személy profilján jelenik meg nyilvánosan.</li>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
