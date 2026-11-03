<?php
declare(strict_types=1);
$siteConfig = \NyitottMult\Config::getSiteConfig();
?>
    </div> <!-- .container -->
</main> <!-- .site-main -->

<footer class="site-footer">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <div>
                <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 0.75rem; font-weight: 700;">
                    <?= htmlspecialchars($siteConfig['site']['name'] ?? 'Orosháza titkos múltja') ?>
                </h3>
                <p style="font-size: 0.85rem; line-height: 1.5; color: #94a3b8;">
                    Településfüggetlen, forrásalapú helytörténeti kutatóplatform.
                    Minden állítás mögött pontos levéltári forrás, oldal és idézet áll.
                </p>
                <p style="font-size: 0.8rem; margin-top: 0.5rem; color: #cbd5e1;">
                    <strong>Kezdeményező:</strong> <?= htmlspecialchars($siteConfig['site']['initiator']['name'] ?? 'Kátai Attila') ?> (<?= htmlspecialchars($siteConfig['site']['initiator']['title'] ?? 'AI specialista') ?>)
                </p>
            </div>

            <div>
                <h4 style="color: #fff; font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 600;">Gyorshivatkozások</h4>
                <ul style="list-style: none; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.4rem;">
                    <li><a href="/">Kezdőlap & Módszertan</a></li>
                    <li><a href="/terkep">Időutazó Térkép</a></li>
                    <li><a href="/kereses">Összetett Kereső</a></li>
                    <li><a href="/bizonyitekterkep">Bizonyítéktérkép</a></li>
                    <li><a href="/atlathatosag">Átláthatóság és Változásnapló</a></li>
                </ul>
            </div>

            <div>
                <h4 style="color: #fff; font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 600;">Jogi & Etikai Keret</h4>
                <ul style="list-style: none; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.4rem;">
                    <li><span style="color: #cbd5e1;">2026. évi LI. törvény 14. §</span></li>
                    <li><span style="color: #cbd5e1;">Halál éve + 5 év elévülési szabály</span></li>
                    <li><span style="color: #cbd5e1;">Kétlépcsős lektori ellenőrzés</span></li>
                    <li><a href="/helyesbites" style="color: #fde68a;">Helyesbítés & Kiegészítő nyilatkozat</a></li>
                    <li><a href="/api-docs">Nyílt REST API & Export</a></li>
                </ul>
            </div>
        </div>

        <div style="border-top: 1px solid #1e293b; padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; font-size: 0.8rem;">
            <div>
                &copy; 2026 <strong>Nyitott Múlt</strong>. Licenc: <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener">AGPL-3.0</a> (Kód) és <a href="https://creativecommons.org/publicdomain/zero/1.0/" target="_blank" rel="noopener">CC0-1.0</a> (Mintaadatok és Névtár).
            </div>
            <div>
                Első telepítés: <em>Orosháza titkos múltja</em>. Kezdeményezte: <strong>Kátai Attila</strong>.
            </div>
        </div>
    </div>
</footer>

</body>
</html>
