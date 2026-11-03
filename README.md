# Nyitott Múlt – Településfüggetlen Helytörténeti Kutatóplatform

[![Licenc: AGPL-3.0](https://img.shields.io/badge/licenc-AGPL--3.0-blue.svg)](LICENSE)
[![Adatlicenc: CC0-1.0](https://img.shields.io/badge/mintaadatok-CC0--1.0-green.svg)](https://creativecommons.org/publicdomain/zero/1.0/)
[![Státusz: Éles üzemre kész](https://img.shields.io/badge/st%C3%A1tusz-%C3%A9les%20%C3%BCzemre%20k%C3%A9sz-brightgreen.svg)]()

> **Első telepítés:** *„Orosháza titkos múltja – Az ügynökakták nyomában”*  
> **Kezdeményező:** Kátai Attila, AI specialista  
> **Cél:** Ingyenes, nyilvános, térképes, forrásalapú és AI-támogatott kutatóplatform a magyar állambiztonsági iratokhoz, egyetlen településre szűkítve.  
> **Szigorú alapelv:** **NEM lejárató névlista.** Minden egyes kijelentés mellett pontosan látható, hogy melyik levéltári iratból, hányadik oldalról, milyen szó szerinti idézetből és képi koordinátából (bounding box) következik.

---

## 📌 Háttér és Jogi Keret (2026. november 4.)

A **2026. évi LI. törvény 14. §-a** alapján az Állambiztonsági Szolgálatok Történeti Levéltára (ÁBTL) 2026. november 4-től a honlapján (*nyitottaktak.hu*) teszi közzé az iratokat (6-os kartonok, B-naplók, H-adattár, majd B-dossziék).

A **Nyitott Múlt** szoftver célja, hogy a közzétett levéltári iratokat a helyi közösségek, helytörténészek, leszármazottak és kutatók számára kontextusban, térképen és idővonalon tegye kutathatóvá, kizárva a tévedéseket, a szenzációhajhászást és a kontextusból kiragadott vádakat.

### Szigorú Adatvédelmi és Szakmai Szabályok:
1. **Halál éve + 5 év szabály:** A nem anonimizált személyes adat kizárólag az érintett halálának évét követő 5. év lejárta után válik közzétehetővé. Élő vagy ismeretlen státuszú személy adatai a nyilvános felületen nem jelennek meg.
2. **Kétlépcsős lektorálás (Dual Verification):** Személyhez kötött állítás csak akkor kerülhet a publikus oldalra, ha legalább két független kutató/szerkesztő ellenőrizte a levéltári forrást.
3. **Szerepkörök szigorú elhatárolása:** Az állambiztonsági iratokban való szereplés **önmagában SOHA nem jelent ügynöki minősítést**. A rendszer világosan megkülönbözteti a szerepköröket:
   - *Beszervezett hálózati személy* (ügynök, informátor, tmb, tmt)
   - *Megfigyelt személy* (áldozat, célszemély)
   - *Operatív tiszt / Tartótiszt*
   - *Beszervezési kísérlet alanya* (akinek beszervezését elutasították vagy meghiúsult)
   - *Egyéb említett személy* (családtag, orvos, kolléga, tanú)
   - *Ismeretlen / Tisztázatlan szerep*
4. **Magánlakások védelme:** Pontos magánlakás-cím vagy épületkoordináta a nyilvános térképen nem jelenik meg; a PostGIS/MySQL térbeli motor automatikusan városrész- vagy utcaszintű poligonra mossa el az elhelyezkedést.
5. **Helyesbítési jog és kiegészítő nyilatkozat:** Bárki nyújthat be kiegészítő nyilatkozatot vagy helyesbítési kérelmet az online űrlapon keresztül, melyet a kutatói csapat törvényi határidőn belül vizsgál ki.

---

## 🏛️ Településfüggetlenség (Orosháza és más települések)

A platform teljes mértékben településfüggetlen. Az alapértelmezett beállítás Orosháza és környéke, de a rendszer bármely más magyarországi településre egyetlen konfigurációs fájl módosításával átállítható:
* `config/site.yaml`: Település neve, térképközéppont, kezdeti zoom, határpoligon, intézményi kapcsolatok, arculat.
* `config/gazetteer/`: Helytörténeti névtár:
  * `districts.json`: Városrészek (Orosházán: *Szentetornya, Rákóczitelep, Gyökeres, Gyopárosfürdő, Kiscsákó*)
  * `street_history.json`: Korabeli utcanév-változások időintervallumokkal (pl. *Lenin tér ↔ Szabadság tér*)
  * `organizations.json`: Intézmények, üzemek (*Orosházi Üveggyár, Ruhagyár, Mezőgép, Gimnázium*)
  * `state_security.json`: Korabeli szervek (*Orosházi Politikai Nyomozó Kirendeltség, 1959–60*)
  * `codenames.json`: Ismert fedőnevek és fedőszervek

> **Fontos:** A git repóba **SOHA nem kerülhet valós személyes adat vagy védett levéltári irat**. A repó kizárólag kitalált, `MINTA` jelölésű demonstrációs adatokat tartalmaz.

---

## 🚀 Fő Funkciók (17 modul)

1. **Nyilvános kezdőlap & Módszertan:** Oktatóanyag az állambiztonsági iratok olvasásához (kartonok, B-naplók logikája).
2. **Interaktív időutazó térkép:** MapLibre GL JS + OpenFreeMap, korabeli térképrétegekkel, utcanév-időcsúszkával (1945–1990) és megszűnt intézményekkel.
3. **Hibrid Kereső:** Név, fedőnév, intézmény, irattípus, évszám; összetett szűrők, fuzzy trigram és szemantikus keresés.
4. **Dokumentumnéző:** Nagyfelbontású iratmegjelenítő (OpenSeadragon) a szinkronizált OCR szöveg mellett, szó szerinti forrásmásolóval és hibajelzéssel.
5. **Entitás-oldalak:** Dokumentált szerepkörök, ellenőrzött állítások, idővonal; a bizonytalan vagy vitatott tételek kiemelt jelölésével.
6. **Bizonyítéktérkép:** Minden állítás mellett fa- és grafikus struktúrában látható, mely levéltári jelzet és sor támasztja alá vagy cáfolja.
7. **Kapcsolati háló:** Csak ellenőrzött állításokból épülő interaktív hálózatvizualizáció (Cytoscape.js).
8. **Interaktív idővonal:** Események és iratok kronológiája.
9. **AI Történész:** Szigorúan forrásalapú kérdezz-felelek (Gemini / OpenAI). Kizárólag igazolt iratidézettel válaszol, minden mondatnál forrással. Ha nincs adat: *"Erre a kérdésre a rendelkezésre álló orosházi dokumentumokban nincs forrás."*
10. **Dokumentumdetektív:** Háttérelemző, amely ellenőrzésre váró összefüggéseket keres (azonos fedőnév, tartótiszt, találkozási helyszín). Nyilvánosan sosem látható, csak a zárt kutatói felületen `javasolt` státusszal.
11. **Helytörténeti történetek:** Lépésenkénti, térképpel és iratokkal illusztrált interaktív történetek.
12. **Közösségi kutatás:** Lakossági visszaemlékezések, korabeli fotók beküldése, helyszínazonosítás, moderációs sor.
13. **Kutatói munkafelület:** Hipotézisek, ellenőrzési feladatok, belső kutatási napló.
14. **Automatikus import és adatátadás:** Helyi/AI asszisztált PDF feldolgozás, OCR, névtárillesztés, közvetlen MySQL importálás.
15. **Helyesbítés és kiegészítő nyilatkozat:** Nyilvános űrlap, határidő-követés, kiegészítő nyilatkozatok közzététele az érintett profilján.
16. **Átláthatóság:** Statisztikák, változásnapló (Audit Log), kezdeményező és közreműködők bemutatása.
17. **Nyílt API és Export:** OpenAPI 3.1 kompatibilis REST API, JSON, CSV és GeoJSON exportálás az ellenőrzött adatokból.

---

## 🛠️ Telepítés DirectAdmin Tárhelyre (PHP + MySQL)

A rendszer kifejezetten megosztott DirectAdmin tárhelyekre lett optimalizálva: nem igényel root hozzáférést, futó Node.js démont vagy Tesseractot a szerveren.

### 1. Adatbázis létrehozása
1. Lépjen be a DirectAdmin felületére, majd nyissa meg a **MySQL Management** menüt.
2. Hozzon létre egy új adatbázist és felhasználót (pl. `oroshaza_mult`).
3. Nyissa meg a **phpMyAdmin** felületét, és importálja be a következő fájlokat:
   - `database/schema.sql` (adatbázis szerkezet)
   - `database/seed_minta.sql` (opcionális minták)

### 2. Webes fájlok feltöltése
1. A repó `public_html/` tartalmát töltse fel a tárhelye `public_html` mappájába (FTP-n vagy a DirectAdmin File Manageren keresztül).
2. Másolja át a `public_html/config.sample.php` fájlt `public_html/config.php` névre, és adja meg az adatbázis hozzáférést:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'oroshaza_mult');
   define('DB_USER', 'oroshaza_user');
   define('DB_PASS', 'jelszo');
   define('GEMINI_API_KEY', 'az-on-gemini-api-kulcsa');
   ```
3. Kész! A honlap azonnal elérhető a domainjén.

---

## 🔍 Helyi Kutatói Adatfeldolgozás (AI és Python eszközök)

Az új levéltári PDF iratok feldolgozása helyben történik (a kutató gépén és/vagy az AI asszisztens segítségével):
```bash
cd tools
pip install -r requirements.txt
python ingest_pdf.py --input /eleresi/ut/akta.pdf --archive-code "ÁBTL-3.1.2-M-MINTA"
python generate_sql_seed.py --output import_uj_adatok.sql
```
Az elkészült `.sql` fájl egyetlen kattintással beimportálható a phpMyAdminban vagy a webes felület Import menüpontjában.

---

## 📄 Licenc és Szerzői Jogok

- **Szoftverkód:** [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE)
- **Mintaadatok és Helytörténeti Névtár:** [Creative Commons Zero (CC0-1.0)](https://creativecommons.org/publicdomain/zero/1.0/)
- **Kezdeményező és Projektgazda:** Kátai Attila, AI specialista
- **Hivatkozás:** Részletek a [CITATION.cff](CITATION.cff) fájlban.
