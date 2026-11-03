<?php
declare(strict_types=1);
$pageTitle = __('nav.transparency');
include __DIR__ . '/layout/header.php';

use NyitottMult\Database;

$auditLogs = Database::query(
    "SELECT a.*, u.full_name as user_name 
     FROM audit_logs a 
     LEFT JOIN users u ON a.user_id = u.id 
     ORDER BY a.created_at DESC LIMIT 25"
);
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        🔍 <?= __('nav.transparency') ?> és Változásnapló
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        A Nyitott Múlt kutatóplatform működésének szakmai, etikai és technikai átláthatósága.
    </p>
</div>

<!-- Kezdeményező és Szakmai Vezetés -->
<div class="card" style="margin-bottom: 2.5rem; border-left: 5px solid var(--primary);">
    <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
        A Kezdeményezés Háttere és Szakmai Vezetője
    </h2>
    <p style="font-size: 0.95rem; color: #334155; line-height: 1.6; margin-bottom: 1rem;">
        A projektet <strong>Kátai Attila</strong> AI specialista és szoftverarchitekt kezdeményezte a 2026. évi LI. törvény által megnyitott levéltári iratok tudományos, forrásalapú és etikus feldolgozására.
    </p>
    <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius); font-size: 0.85rem; color: #475569; line-height: 1.6;">
        <strong>Szakmai hitvallás:</strong> <em>"A múlt megismerése nem a megbélyegzés, hanem a megértés eszköze. A helyi közösségeknek joguk van tudni, mi történt a városukban, de kizárólag a legszigorúbb forráskritika, kettős lektori ellenőrzés és a törvényes személyiségi jogok maradéktalan tiszteletben tartása mellett."</em>
    </div>
</div>

<!-- Teljes Rendszer Változásnapló (Audit Log) -->
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.35rem; font-weight: 700; color: #0f172a;">
            Rendszer Változásnapló (Audit Log)
        </h2>
        <span class="badge" style="background: #e0f2fe; color: #0369a1;">
            Valós idejű integritás-naplózás
        </span>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 1px solid var(--border); color: #475569;">
                    <th style="padding: 0.75rem 1rem;">Időpont</th>
                    <th style="padding: 0.75rem 1rem;">Művelet</th>
                    <th style="padding: 0.75rem 1rem;">Tábla</th>
                    <th style="padding: 0.75rem 1rem;">Felhasználó / Lektor</th>
                    <th style="padding: 0.75rem 1rem;">IP (Maszkolt)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($auditLogs)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                            Még nincsenek rögzített audit események.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 0.75rem 1rem; color: #64748b; font-family: monospace;">
                                <?= htmlspecialchars((string)$log['created_at']) ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <span class="badge" style="background: #f1f5f9; color: #0f172a;">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-family: monospace; color: var(--primary);">
                                <?= htmlspecialchars($log['table_name']) ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <?= htmlspecialchars($log['user_name'] ?? 'Rendszerfolyamat') ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #94a3b8; font-family: monospace;">
                                <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.xxx') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
