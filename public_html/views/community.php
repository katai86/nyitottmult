<?php
declare(strict_types=1);
$pageTitle = __('nav.community');

use NyitottMult\Database;

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['submitter_name'] ?? '');
    $email = trim($_POST['submitter_email'] ?? '');
    $type = $_POST['content_type'] ?? 'reminiscence';
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $gdpr = !empty($_POST['gdpr_consent']);

    if (empty($name) || empty($email) || empty($title) || empty($desc) || !$gdpr) {
        $error = 'Kérjük, töltsön ki minden kötelező mezőt és fogadja el az adatkezelési tájékoztatót!';
    } else {
        $id = Database::generateUuid();
        $ok = Database::execute(
            "INSERT INTO submissions (id, submitter_name, submitter_email, gdpr_consent, content_type, title, description, moderation_status, created_at)
             VALUES (:id, :name, :email, 1, :type, :title, :desc, 'pending', NOW())",
            [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'type' => $type,
                'title' => $title,
                'desc' => $desc
            ]
        );

        if ($ok) {
            $message = 'Köszönjük beküldését! Kutatói csapatunk a moderációs sorban megvizsgálja az anyagot a közzététel előtt.';
        } else {
            $error = 'Hiba történt a beküldés mentésekor. Kérjük próbálja újra később.';
        }
    }
}

include __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        👥 <?= __('nav.community') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Helytörténeti közösségi kutatás: családi visszaemlékezések, korabeli fotók, dokumentumok vagy helyszín-pontosítások megosztása.
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
    <!-- Beküldő űrlap -->
    <form method="POST" action="/kozosseg" class="card">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 1.25rem;">
            Adatbeküldési Űrlap
        </h2>

        <div class="form-group">
            <label class="form-label">Beküldő Neve *</label>
            <input type="text" name="submitter_name" required class="form-input" placeholder="Pl. Orosházi Helytörténész">
        </div>

        <div class="form-group">
            <label class="form-label">E-mail cím *</label>
            <input type="email" name="submitter_email" required class="form-input" placeholder="nev@example.hu">
            <small style="color: var(--text-muted); font-size: 0.75rem;">Csak a kapcsolatfelvételhez szükséges, nyilvánosan sosem jelenik meg.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Beküldés típusa</label>
            <select name="content_type" class="form-select">
                <option value="reminiscence">Családi vagy személyes visszaemlékezés</option>
                <option value="photo">Korabeli fénykép (épület, üzem, személy)</option>
                <option value="document">Családi levéltári irat vagy másolat</option>
                <option value="location_fix">Helyszín vagy korabeli utcanév pontosítás</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Téma / Cím *</label>
            <input type="text" name="title" required class="form-input" placeholder="Pl. Az üveggyári kolónia élete az 1960-as években">
        </div>

        <div class="form-group">
            <label class="form-label">Részletes leírás, történet vagy észrevétel *</label>
            <textarea name="description" rows="6" required class="form-textarea" placeholder="Írja le a részleteket, neveket, évszámokat és helyszíneket..."></textarea>
        </div>

        <div class="form-group" style="display: flex; gap: 0.5rem; align-items: flex-start;">
            <input type="checkbox" name="gdpr_consent" id="gdpr" required style="margin-top: 0.25rem;">
            <label for="gdpr" style="font-size: 0.8rem; color: #475569; line-height: 1.5;">
                Hozzájárulok a megadott adatok helytörténeti kutatási célú feldolgozásához a GDPR és a 2026. évi LI. törvény adatvédelmi rendelkezései szerint. Tudomásul veszem, hogy a beküldött tartalom lektori moderáció után kerülhet publikálásra.
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding-top: 0.75rem; padding-bottom: 0.75rem; font-size: 1rem;">
            📤 Beküldés a Kutatói Csoportnak
        </button>
    </form>

    <!-- Tájékoztató panel -->
    <div>
        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
                Hogyan dolgozzuk fel a beküldéseket?
            </h3>
            <ol style="font-size: 0.85rem; color: #475569; line-height: 1.7; padding-left: 1.25rem;">
                <li><strong>Beérkezés:</strong> A beküldés bekerül a zárt kutatói moderációs sorba.</li>
                <li><strong>Forrásellenőrzés:</strong> Szakembereink összevetik a meglévő állambiztonsági és helytörténeti forrásokkal.</li>
                <li><strong>Adatvédelem:</strong> Ellenőrizzük, hogy élő vagy védett személyek jogai ne sérüljenek.</li>
                <li><strong>Közzététel:</strong> A jóváhagyott információk forrásként bekerülnek a térképre és az idővonalra.</li>
            </ol>
        </div>

        <div class="card" style="background: #f8fafc;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                Nyitott feladatok a közösségnek
            </h4>
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
                Keresünk korabeli fotókat az 1950-es és 60-as évek orosházi üzemeiről (Ruhagyár, Mezőgép), valamint az egykori Beloiannisz utcai épületekről.
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
