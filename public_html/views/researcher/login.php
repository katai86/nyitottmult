<?php
declare(strict_types=1);

use NyitottMult\Auth;

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if (Auth::login($u, $p)) {
        header('Location: /kutato');
        exit;
    } else {
        $error = 'Hibás felhasználónév vagy jelszó.';
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
    header('Location: /');
    exit;
}

$pageTitle = "Kutatói Belépés";
include dirname(__DIR__) . '/layout/header.php';
?>

<div style="max-width: 440px; margin: 3rem auto;">
    <div class="card">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔒</div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">Kutatói Belépés</h1>
            <p style="font-size: 0.85rem; color: var(--text-muted);">Zárt lektori és kutatói munkafelület</p>
        </div>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius); margin-bottom: 1rem; font-size: 0.85rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/kutato/login">
            <div class="form-group">
                <label class="form-label">Felhasználónév vagy E-mail</label>
                <input type="text" name="username" required class="form-input" placeholder="pl. hello@kataiattila.hu vagy kataiattila" autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label">Jelszó</label>
                <input type="password" name="password" required class="form-input" placeholder="Jelszó" autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 0.95rem;">
                Belépés a Kutatói Felületre &rarr;
            </button>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/layout/footer.php'; ?>
