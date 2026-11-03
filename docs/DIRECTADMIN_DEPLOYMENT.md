# Nyitott Múlt – DirectAdmin Telepítési Útmutató

Ez a kézikönyv lépésről lépésre bemutatja, hogyan telepíthető a platform egy hagyományos, megosztott **DirectAdmin** (vagy cPanel) tárhelyre.

---

## 📋 Követelmények a Tárhelyen

- **Webkiszolgáló:** Apache vagy LiteSpeed `mod_rewrite` támogatással
- **PHP verzió:** PHP 8.1 vagy 8.2+ (PDO, curl, mbstring, json kiterjesztésekkel – ezek minden DirectAdmin tárhelyen alapértelmezetten aktívak)
- **Adatbázis:** MySQL 8.0+ vagy MariaDB 10.6+
- **Futó háttérfolyamatok:** NEM szükséges Node.js, sem Python a szerveren!

---

## 🚀 Telepítési Lépések

### 1. Lépés: Adatbázis létrehozása a DirectAdminban
1. Jelentkezzen be a DirectAdmin kezelőfelületére.
2. Kattintson az **Extra Features** &rarr; **MySQL Management** menüpontra.
3. Kattintson a **Create New Database** gombra:
   - Adatbázis név: pl. `oroshaza_mult`
   - Felhasználónév: pl. `oroshaza_user`
   - Jelszó: generáljon egy biztonságos jelszót, és jegyezze fel!
4. Kattintson a **Create Database** gombra.

### 2. Lépés: Adatbázisséma importálása a phpMyAdminban
1. A DirectAdmin felületén nyissa meg a **phpMyAdmin** alkalmazást.
2. Bal oldalon válassza ki a frissen létrehozott adatbázist.
3. Kattintson a felső menüsorban az **Importálás** (Import) fülre:
   - Válassza ki a repóból a `database/schema.sql` fájlt, majd kattintson a **Végrehajtás** (Go) gombra.
   - (Opcionális tesztadatokhoz) Ismételje meg az importálást a `database/seed_minta.sql` fájllal.

### 3. Lépés: A Webes Fájlok Feltöltése
1. A repóban található **`public_html`** mappa tartalmát kell feltölteni a DirectAdmin tárhelyének `public_html` mappájába.
2. Feltöltési módok:
   - **FTP klienssel** (pl. FileZilla vagy WinSCP), vagy
   - A DirectAdmin beépített **File Manager** felületén (a fájlokat előtte egy ZIP-be tömörítheti, feltöltheti, majd a tárhelyen kicsomagolhatja).

### 4. Lépés: Konfiguráció beállítása
1. A tárhely `public_html` mappájában másolja át (vagy szerkessze) a `config.php` fájlt.
2. Adja meg az 1. lépésben létrehozott adatbázis hozzáféréseket:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', 3306);
   define('DB_NAME', 'az_on_felhasznaloneve_oroshaza_mult');
   define('DB_USER', 'az_on_felhasznaloneve_oroshaza_user');
   define('DB_PASS', 'az_on_adatbazis_jelszava');
   ```
3. Ha szeretné használni a felhős AI Történész modult, másolja be ingyenes Gemini API kulcsát:
   ```php
   define('GEMINI_API_KEY', 'AIzaSy...');
   ```
   *(Ha nincs kulcs megadva, a rendszer a beépített hitelesített levéltári keresővel válaszol.)*

### 5. Lépés: Ellenőrzés és Belépés
1. Nyissa meg böngészőjében a domain nevét (pl. `https://azondomainje.hu`).
2. A kezdőlap, a térkép és a kereső azonnal működik!
3. Kutatói felület elérése:
   - URL: `https://azondomainje.hu/kutato/login`
   - Felhasználónév: `admin`
   - Jelszó: `MintaJelszo2026!` *(Kérjük, az első belépés után módosítsa a jelszót!)*
