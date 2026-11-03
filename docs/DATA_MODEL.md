# Nyitott Múlt – Adatmodell Dokumentáció

## 1. Ontológia és Alapelvek

A rendszer adatmodellje a történettudományi forráskritika elveit képezi le adatbázis-struktúrára:
- **Tények helyett állítások:** A levéltári iratokban szereplő mondatok nem feltétlenül objektív igazságok, hanem a korabeli állambiztonsági tisztek vagy informátorok *állításai*.
- **Claim-alapú ontológia:** A rendszerben entitások közötti kapcsolat (`relation`) kizárólag igazolt állítás (`claim`) alapján jöhet létre.
- **Szerepkörök szigorú elválasztása:** Egy személy aktában való megemlítése nem jelent ügynöki minősítést.

---

## 2. Főbb Entitások és Táblák

### `source_documents` (Levéltári forrásirat)
- `id` (UUID): Egyedi azonosító
- `archive_code`: Hivatalos levéltári jelzet (pl. `ÁBTL - 3.1.2. - M-12345/1`)
- `document_type`: `6_os_karton`, `b_naplo`, `h_adattar`, `b_dosszie`, `m_dosszie`, `o_dosszie`, `vizsgalati_dosszie`, `egyeb`
- `sha256_hash`: Fájl integritási ellenőrzőösszeg

### `pages` (Iratoldal)
- `document_id`: Hivatkozás az iratra
- `page_number`: Oldalszám
- `image_path`: Oldalkép relatív elérési útja (WebP/PNG)
- `ocr_text_raw`: Eredeti OCR szöveg
- `ocr_text_corrected`: Lektorált javított szöveg
- `ocr_boxes_json`: Bounding boxok a képen `[{"x":..., "y":..., "w":..., "h":..., "text":...}]`

### `entities` (Entitás)
- `type`: `person` (személy), `organization` (szervezet), `place` (helyszín), `event` (esemény), `codename` (fedőnév)
- `canonical_name`: Hivatalos név
- `aliases`: Névváltozatok és elírások JSON tömbje
- `is_sample`: `1` ha MINTA adat

### `roles` (Szerepkör)
- `role_type`:
  - `beszervezett_halozati_szemely`: Hálózati személy (ügynök, informátor, tmb, tmt)
  - `megfigyelt`: Áldozat, operatív megfigyelés célszemélye
  - `tiszt`: Operatív tiszt, tartótiszt
  - `beszervezesi_kiserlet_alanya`: Sikertelen beszervezés alanya
  - `egyeb_emlitett`: Tanú, szomszéd, családtag
  - `ismeretlen`: Tisztázatlan szerep
- `justification_claim_id`: A minősítést alátámasztó állítás

### `claims` (Forrásállítás)
- `document_id`, `page_id`: Forrás helye
- `quote`: Szó szerinti idézet az iratból
- `statement`: Állítás összefoglalása
- `status`: `javasolt`, `ellenorzott`, `vitatott`

### `claim_verifications` (Lektori ellenőrzés)
- `claim_id`: Ellenőrzött állítás
- `verifier_id`: Lektor azonosítója
- `decision`: `approved`, `rejected`, `disputed`
- *Szabály: Csak 2 független `approved` döntés után válik a claim `ellenorzott`-té!*

### `place_geometries` (Térbeli helyszínek)
- `latitude`, `longitude`: Valós koordináta
- `is_private_residence`: `1` ha magánlakás
- `public_latitude`, `public_longitude`: Publikus koordináta (magánlakás esetén elmosva a városrész centroidjára)
