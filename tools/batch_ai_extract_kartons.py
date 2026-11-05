#!/usr/bin/env python3
"""
Orosházi 6-os kartonok tömeges AI feldolgozója és adatbázis generátora
Modell-rotációs (fallback pool), rate-limit és azonnali inkrementális SQL mentéssel
"""

import os
import sys
import ssl
import json
import time
import base64
import urllib.request
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
DOC_DIR = BASE_DIR / "public_html" / "uploads" / "documents"
CACHE_DIR = BASE_DIR / "data" / "karton_extractions"
SQL_OUTPUT = BASE_DIR / "data" / "update_karton_ai_extractions.sql"
KEY_FILE = BASE_DIR / "gemini_key.txt"
KEY = os.environ.get('GEMINI_API_KEY') or (KEY_FILE.read_text().strip() if KEY_FILE.exists() else '')

CACHE_DIR.mkdir(parents=True, exist_ok=True)

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

MODEL_POOL = [
    'gemini-3.5-flash',
    'gemini-3.1-flash-lite',
    'gemini-3.1-flash-lite-preview',
    'gemini-3-flash-preview',
    'gemini-flash-lite-latest'
]
current_model_idx = 0

PROMPT = """Elemezd ezt az ÁBTL 6-os kartont (állambiztonsági nyilvántartó karton) nagyon részletesen és pontosan.
Kérlek, add vissza JSON formátumban a következő mezőket (ha nem olvasható vagy nincs kitöltve, értéke null):
{
  "nev": "teljes név",
  "leanykori_vagy_elozo_nev": "előző vagy leánykori név ha van",
  "szuletesi_hely": "születési hely",
  "szuletesi_datum": "születési dátum (ÉÉÉÉ.HH.NN)",
  "anyja_neve": "anyja neve",
  "foglalkozas": "foglalkozás/beosztás",
  "munkahely": "munkahely neve és címe",
  "lakcim": "lakcím (település, utca, házszám)",
  "iskolai_vegzettseg": "iskolai végzettség",
  "partallas_felsz_elott": "pártállása felszabadulás előtt",
  "partallas_felsz_utan": "pártállása felszabadulás után (pl. MSZMP, pk./pártonkívüli)",
  "katonai_viszony": "katonai viszonya",
  "fedonev": "hálózati fedőnév idézőjelek nélkül",
  "minosites": "minősítése (pl. TMB, titkos megbízott, ügynök, titkos munkatárs)",
  "beszervezes_alapja": "beszervezés alapja (pl. hazafias, terhelő, anyagi)",
  "beszervezes_ideje": "beszervezés dátuma (ÉÉÉÉ.HH.NN)",
  "beszervezes_vegezte": "beszervező tiszt neve, rendfokozata, osztálya",
  "foglalkoztatas_vonala": "foglalkoztatási vonal (pl. belső reakció, ifjúsági elhárítás, gazdaságvédelem)",
  "karton_kelt": "karton kiállításának kelte",
  "dosszie_szamok": {
    "h_dosszie": "H-dosszié szám",
    "m_dosszie": "M-dosszié szám",
    "b_dosszie": "B-dosszié szám"
  },
  "kizaras_vagy_megszunes": {
    "datum": "dátum",
    "indoklas": "kizárás vagy megszüntetés indoklása (pl. titkos operatív helyzet megváltozott, megbízhatatlan)"
  },
  "atadas_atvetel": "dosszié átadásának, átvételének megjelölése vagy egyéb feljegyzések",
  "teljes_szoveges_atirat": "A karton teljes szöveges átirata soronként"
}
Csak a tiszta JSON választ add vissza, markdown kódblokk jelölések (```json) nélkül."""

def extract_pdf_data(pdf_path, max_retries=10):
    global current_model_idx
    with open(pdf_path, "rb") as f:
        pdf_bytes = f.read()

    b64_pdf = base64.b64encode(pdf_bytes).decode('utf-8')
    payload = {
        "contents": [
            {
                "parts": [
                    {"text": PROMPT},
                    {
                        "inline_data": {
                            "mime_type": "application/pdf",
                            "data": b64_pdf
                        }
                    }
                ]
            }
        ],
        "generationConfig": {
            "temperature": 0.1
        }
    }
    req_data = json.dumps(payload).encode('utf-8')

    models_attempted = 0
    for attempt in range(1, max_retries + 1):
        model_name = MODEL_POOL[current_model_idx]
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{model_name}:generateContent?key={KEY}"
        try:
            req = urllib.request.Request(
                url,
                data=req_data,
                headers={'Content-Type': 'application/json'}
            )
            with urllib.request.urlopen(req, context=ctx, timeout=85) as resp:
                res = json.loads(resp.read().decode('utf-8'))
                raw_text = res['candidates'][0]['content']['parts'][0]['text'].strip()
                if raw_text.startswith("```"):
                    lines = raw_text.splitlines()
                    if lines[0].startswith("```"):
                        lines = lines[1:]
                    if lines and lines[-1].startswith("```"):
                        lines = lines[:-1]
                    raw_text = "\n".join(lines).strip()
                return json.loads(raw_text)
        except urllib.error.HTTPError as e:
            if e.code == 429:
                current_model_idx = (current_model_idx + 1) % len(MODEL_POOL)
                next_model = MODEL_POOL[current_model_idx]
                models_attempted += 1
                if models_attempted >= len(MODEL_POOL):
                    wait_time = 25
                    print(f"[Minden modell 429 - várakozás {wait_time}s...] ", end="", flush=True)
                    time.sleep(wait_time)
                    models_attempted = 0
                else:
                    print(f"[{model_name} 429 -> váltás: {next_model}] ", end="", flush=True)
                    time.sleep(2.0)
            elif e.code in (500, 503):
                current_model_idx = (current_model_idx + 1) % len(MODEL_POOL)
                print(f"[{model_name} hiba {e.code} -> váltás: {MODEL_POOL[current_model_idx]}] ", end="", flush=True)
                time.sleep(4.0)
            else:
                raise e
        except Exception as e:
            current_model_idx = (current_model_idx + 1) % len(MODEL_POOL)
            print(f"[{model_name} hiba: {e} -> váltás: {MODEL_POOL[current_model_idx]}] ", end="", flush=True)
            time.sleep(3.0)

    raise RuntimeError(f"Nem sikerült kinyerni az adatokat {max_retries} kísérlet után.")

def esc(val):
    if val is None:
        return "NULL"
    s = str(val).replace("\\", "\\\\").replace("'", "\\'").replace("\r", "\\r").replace("\n", "\\n")
    return f"'{s}'"

def generate_sql_for_all():
    """Összes kinyert JSON alapján generál egy teljes SQL frissítő fájlt"""
    pdf_files = sorted([f for f in os.listdir(DOC_DIR) if f.endswith('.pdf')])
    all_data = {}
    for fname in pdf_files:
        guid_prefix = fname.replace('.pdf', '').split('_')[-1]
        cache_file = CACHE_DIR / f"{guid_prefix}.json"
        if cache_file.exists():
            try:
                with open(cache_file, "r", encoding="utf-8") as f:
                    all_data[guid_prefix] = {"file": fname, "data": json.load(f)}
            except Exception:
                pass

    sql_statements = []
    sql_statements.append("-- ==============================================================================")
    sql_statements.append("-- ÁBTL 6-os kartonok részletes AI kinyeréseinek frissítése")
    sql_statements.append(f"-- Készült: {len(all_data)} db kinyert orosházi karton alapján")
    sql_statements.append("-- Kezdeményező: Kátai Attila | Nyitott Múlt (AGPL-3.0)")
    sql_statements.append("-- ==============================================================================")
    sql_statements.append("SET NAMES utf8mb4;")

    for guid_prefix, info in all_data.items():
        fname = info['file']
        d = info['data']
        rel_pdf_path = f"uploads/documents/{fname}"
        transcript = d.get('teljes_szoveges_atirat') or ""

        # 1. Pages tábla frissítése valódi PDF elérési úttal és átirattal
        sql_statements.append(f"""
-- Dokumentum: {d.get('nev')} ({guid_prefix})
UPDATE `pages` 
SET `image_path` = '{rel_pdf_path}',
    `thumbnail_path` = '{rel_pdf_path}',
    `ocr_text_corrected` = {esc(transcript)},
    `ocr_confidence` = 0.99
WHERE `document_id` LIKE 'doc_{guid_prefix}%';
""")

        # 2. Entities metadata frissítése
        meta_updates = {
            'workplace': d.get('munkahely'),
            'education': d.get('iskolai_vegzettseg'),
            'party_before': d.get('partallas_felsz_elott'),
            'party_after': d.get('partallas_felsz_utan'),
            'military_service': d.get('katonai_viszony'),
            'address': d.get('lakcim'),
            'recruitment_date': d.get('beszervezes_ideje'),
            'recruiter_officer': d.get('beszervezes_vegezte'),
            'recruitment_basis': d.get('beszervezes_alapja'),
            'operating_line': d.get('foglalkoztatas_vonala'),
            'network_rank': d.get('minosites'),
            'dossier_numbers': d.get('dosszie_szamok'),
            'dismissal': d.get('kizaras_vagy_megszunes'),
            'transfer_notes': d.get('atadas_atvetel'),
            'local_pdf_path': rel_pdf_path
        }

        meta_json_str = json.dumps(meta_updates, ensure_ascii=False)
        sql_statements.append(f"""
UPDATE `entities`
SET `metadata` = JSON_MERGE_PATCH(COALESCE(`metadata`, '{{}}'), {esc(meta_json_str)})
WHERE `id` LIKE 'e_p_{guid_prefix}%';
""")

        # 3. Claims beszúrása
        if d.get('munkahely'):
            cid = f"clm_wp_{guid_prefix}"
            stmt = f"{d.get('nev')} munkahelye az állambiztonsági nyilvántartás (6-os karton) szerint: {d.get('munkahely')}."
            quote = f"Munkahelye: {d.get('munkahely')}"
            sql_statements.append(f"""
INSERT IGNORE INTO `claims` (`id`, `document_id`, `page_id`, `quote`, `statement`, `subject_entity_id`, `status`, `ai_generated`, `confidence_score`)
SELECT '{cid}', p.`document_id`, p.`id`, {esc(quote)}, {esc(stmt)}, e.`id`, 'ellenorzott', 1, 0.98
FROM `pages` p
JOIN `entities` e ON e.`id` LIKE 'e_p_{guid_prefix}%'
WHERE p.`document_id` LIKE 'doc_{guid_prefix}%'
LIMIT 1;
""")

        if d.get('lakcim'):
            cid = f"clm_addr_{guid_prefix}"
            stmt = f"{d.get('nev')} lakcíme a 6-os karton szerint: {d.get('lakcim')}."
            quote = f"Lakcíme: {d.get('lakcim')}"
            sql_statements.append(f"""
INSERT IGNORE INTO `claims` (`id`, `document_id`, `page_id`, `quote`, `statement`, `subject_entity_id`, `status`, `ai_generated`, `confidence_score`)
SELECT '{cid}', p.`document_id`, p.`id`, {esc(quote)}, {esc(stmt)}, e.`id`, 'ellenorzott', 1, 0.98
FROM `pages` p
JOIN `entities` e ON e.`id` LIKE 'e_p_{guid_prefix}%'
WHERE p.`document_id` LIKE 'doc_{guid_prefix}%'
LIMIT 1;
""")

        if d.get('beszervezes_ideje') or d.get('beszervezes_vegezte'):
            cid = f"clm_rec_{guid_prefix}"
            det = []
            if d.get('beszervezes_ideje'): det.append(f"ideje: {d.get('beszervezes_ideje')}")
            if d.get('beszervezes_vegezte'): det.append(f"végezte: {d.get('beszervezes_vegezte')}")
            if d.get('beszervezes_alapja'): det.append(f"alapja: {d.get('beszervezes_alapja')}")
            if d.get('foglalkoztatas_vonala'): det.append(f"vonala: {d.get('foglalkoztatas_vonala')}")
            stmt = f"{d.get('nev')} hálózati beszervezése – {', '.join(det)}."
            quote = f"Beszervezés: {d.get('beszervezes_ideje')} {d.get('beszervezes_vegezte')}"
            sql_statements.append(f"""
INSERT IGNORE INTO `claims` (`id`, `document_id`, `page_id`, `quote`, `statement`, `subject_entity_id`, `status`, `ai_generated`, `confidence_score`)
SELECT '{cid}', p.`document_id`, p.`id`, {esc(quote)}, {esc(stmt)}, e.`id`, 'ellenorzott', 1, 0.98
FROM `pages` p
JOIN `entities` e ON e.`id` LIKE 'e_p_{guid_prefix}%'
WHERE p.`document_id` LIKE 'doc_{guid_prefix}%'
LIMIT 1;
""")

    with open(SQL_OUTPUT, "w", encoding="utf-8") as f:
        f.write("\n".join(sql_statements))
    return len(all_data)

def main():
    pdf_files = sorted([f for f in os.listdir(DOC_DIR) if f.endswith('.pdf')])
    print(f"[*] Összesen {len(pdf_files)} db PDF fájl található a helyi tárolóban.")

    # Induláskor is generáljunk egy SQL fájlt a már meglévőkből
    count_now = generate_sql_for_all()
    print(f"[*] Meglévő kinyerésekből SQL előkészítve: {count_now} db dokumentum")

    for idx, fname in enumerate(pdf_files, 1):
        guid_prefix = fname.replace('.pdf', '').split('_')[-1]
        cache_file = CACHE_DIR / f"{guid_prefix}.json"

        if cache_file.exists():
            continue

        print(f"[{idx}/{len(pdf_files)}] {fname}...", end=" ", flush=True)
        pdf_path = DOC_DIR / fname
        try:
            data = extract_pdf_data(pdf_path)
            with open(cache_file, "w", encoding="utf-8") as f:
                json.dump(data, f, indent=2, ensure_ascii=False)
            print("[OK]")
            # Inkrementális mentés
            generate_sql_for_all()
            time.sleep(2.5)
        except Exception as e:
            print(f"[HIBA: {e}]")
            time.sleep(3.0)

    total_done = generate_sql_for_all()
    print(f"\n[OK] Kész! Összesen {total_done} dokumentum feldolgozva.")
    print(f"SQL fájl: {SQL_OUTPUT} ({round(os.path.getsize(SQL_OUTPUT)/1024, 1)} KB)")

if __name__ == '__main__':
    main()
