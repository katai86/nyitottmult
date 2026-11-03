# Nyitott Múlt – Rendszerarchitektúra Dokumentáció

## 1. Áttekintés

A **Nyitott Múlt** egy nyílt forráskódú, településfüggetlen helytörténeti kutatóplatform a magyar állambiztonsági iratok feldolgozására.
A rendszer első telepítése: *„Orosháza titkos múltja – Az ügynökakták nyomában”*, kezdeményezője: **Kátai Attila**, AI specialista.

A platform architektúrája két fő komponensre tagolódik:
1. **Helyi és AI asszisztált adat-előkészítés (Local & AI Ingest Pipeline):**
   - A kutató számítógépén és az AI segítségével futó feldolgozás.
   - PDF iratok darabolása, képi konverzió (WebP/PNG), OCR szövegréteg kinyerése és tisztítása.
   - Névtár-illesztés (Gazetteer matching): orosházi utcák, üzemek, állambiztonsági szervek azonosítása.
   - Strukturált MySQL adatbázis-mentés (`.sql` dump) előállítása.
2. **Könnyűsúlyú, éles webes kiszolgálás (DirectAdmin / PHP 8.2+ & MySQL):**
   - Megosztott webtárhelyeken (DirectAdmin, cPanel) futó alkalmazás.
   - Nem igényel futó Node.js szervert vagy háttér-Python démont a tárhelyen.
   - Reszponzív, forrásalapú felületet biztosít: térkép (MapLibre), iratnéző (OpenSeadragon), kapcsolati háló (Cytoscape), AI Történész (Gemini API HTTP proxy), közösségi modulok.

---

## 2. Rendszerdiagram

```text
[ Letöltött ÁBTL PDF iratok ]
              │
              ▼
[ Helyi Feldolgozó / AI (tools/ingest_pdf.py) ]
  ├── Tesseract OCR / Szövegkinyerés
  └── Gazetteer illesztés (Orosháza)
              │
              ▼
[ Strukturált SQL / JSON Import ]
              │
              ▼ (phpMyAdmin vagy Import API)
[ DirectAdmin Tárhely: MySQL Adatbázis ]
              ▲
              │ PDO Prepared Statements
[ DirectAdmin PHP 8.2+ Alkalmazás (public_html) ]
  ├── PrivacyEngine (Halál éve + 5 év, lakáscím elmosás)
  ├── Dual Verification (2 lektori jóváhagyás ellenőrzése)
  ├── REST API & OpenAPI 3.1 (/api/v1/...)
  └── AiService (Proxy a Google Gemini API-hoz)
              │
              ▼
[ Felhasználó Böngészője ]
  ├── MapLibre GL JS (Időutazó térkép, OpenFreeMap)
  ├── OpenSeadragon (Mély zoom, Bounding Box kiemelés)
  └── Cytoscape.js (Kapcsolati háló)
```

---

## 3. Kulcsfontosságú Szabályok és Minőségbiztosítás

1. **Forrásalapúság (Evidence-first):** Minden kijelentés (`claim`) irathoz, oldalhoz és szöveghez kötött.
2. **Kétlépcsős lektorálás:** Nyilvános személyes állítás csak 2 független ellenőrzés után publikus.
3. **Szerepkörök szétválasztása:** Az aktában szereplés nem ügynöki múlt.
4. **Adatvédelem:** 2026. évi LI. tv., halál éve + 5 év szabály érvényesítése.
