<?php
declare(strict_types=1);
$pageTitle = __('ai.title');
include __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
        🤖 <?= __('ai.title') ?>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        <?= __('ai.disclaimer') ?>
    </p>
</div>

<!-- Szigorú Forráshivatkozási Figyelmeztetés -->
<div class="card" style="background: #f8fafc; border-left: 4px solid var(--primary); margin-bottom: 2rem; padding: 1rem;">
    <div style="font-size: 0.85rem; color: #334155; line-height: 1.6;">
        🛡️ <strong>Biztonsági Garancia:</strong> Ez az asszisztens <em>nem spekulál és nem generál feltételezéseket</em>. Kizárólag az Orosházához kapcsolódó, lektorált levéltári iratokból vett szó szerinti idézetekkel támasztja alá válaszait. Ha egy tényről nincs levéltári irat a rendszerben, azt válaszolja: <em>"<?= __('ai.no_source_answer') ?>"</em>
    </div>
</div>

<!-- Chat Doboz -->
<div class="card" style="display: flex; flex-direction: column; height: 600px; padding: 0; overflow: hidden; margin-bottom: 2rem;">
    <!-- Üzenetlista konténer -->
    <div id="chatMessages" style="flex: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1.25rem; background: #fafafa;">
        <!-- Üdvözlő üzenet -->
        <div style="display: flex; gap: 0.75rem; max-width: 80%;">
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                🤖
            </div>
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 0.9rem; line-height: 1.6;">
                Üdvözlöm! Az orosházi állambiztonsági iratok forrásalapú AI kutatóasszisztense vagyok. Tegyen fel egy kérdést a feldolgozott iratokkal, személyekkel vagy intézményekkel kapcsolatban!
            </div>
        </div>
    </div>

    <!-- Javasolt kutatási kérdések gombjai -->
    <div style="padding: 0.5rem 1rem; background: #f1f5f9; border-top: 1px solid var(--border); display: flex; gap: 0.5rem; overflow-x: auto; font-size: 0.8rem;">
        <span style="color: #64748b; font-weight: 600; align-self: center; white-space: nowrap;">Javasolt kérdések:</span>
        <button class="sample-q-btn btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; white-space: nowrap;">
            Ki volt Adász Zoltán és mi volt a fedőneve?
        </button>
        <button class="sample-q-btn btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; white-space: nowrap;">
            Mit tudunk Bánki Horváth Ferencről a levéltári iratok alapján?
        </button>
        <button class="sample-q-btn btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; white-space: nowrap;">
            Kik szerepelnek az orosházi állambiztonsági nyilvántartásokban?
        </button>
    </div>

    <!-- Kérdező beviteli sáv -->
    <form id="chatForm" style="padding: 1rem; background: #ffffff; border-top: 1px solid var(--border); display: flex; gap: 0.75rem;">
        <input type="text" id="chatInput" placeholder="<?= __('ai.ask_placeholder') ?>" 
               class="form-input" style="flex: 1; font-size: 0.95rem;" required autocomplete="off">
        <button type="submit" id="chatSubmitBtn" class="btn btn-primary" style="padding-left: 1.5rem; padding-right: 1.5rem;">
            <?= __('ai.send') ?> &rarr;
        </button>
    </form>
</div>

<script src="/assets/js/ai_chat.js"></script>

<?php include __DIR__ . '/layout/footer.php'; ?>
