#!/usr/bin/env python3
"""
Orosházi 6-os kartonok tömeges PDF letöltője
Használat:
  python tools/batch_download_kartons.py --pass-token "<TOKEN>"
"""

import os
import sys
import csv
import ssl
import json
import time
import argparse
import urllib.request
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
CSV_PATH = Path("C:/Users/Kátai Attila/.gemini/antigravity/brain/518befa3-3ed7-4757-a3ff-c71ae0aa3629/.user_uploaded/media_1793793396021_a11cf9c6.csv")
DOWNLOAD_DIR = BASE_DIR / "public_html" / "uploads" / "documents"

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def safe_filename(name):
    keep = (" ", ".", "_", "-")
    return "".join(c for c in name if c.isalnum() or c in keep).rstrip().replace(" ", "_")

def get_pdf_download_url(guid, pdf_pass):
    url = f"https://nyitottaktak.hu/api/v1/pdf-link/{guid}"
    req = urllib.request.Request(url, headers={
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Referer": "https://nyitottaktak.hu/",
        "Accept": "application/json, text/plain, */*",
        "X-PDF-Pass": pdf_pass
    })
    with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
        return json.loads(resp.read().decode('utf-8'))

def download_file(url, target_path):
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, context=ctx, timeout=30) as resp:
        content = resp.read()
        target_path.parent.mkdir(parents=True, exist_ok=True)
        with open(target_path, "wb") as f:
            f.write(content)
        return len(content)

def main():
    parser = argparse.ArgumentParser(description="Orosházi 6-os kartonok tömeges letöltése")
    parser.add_argument("--pass-token", required=True, help="abtl_pdf_pass token értéke")
    args = parser.parse_args()

    token = args.pass_token.strip()
    if token.startswith("{"):
        try:
            token = json.loads(token).get("pass", token)
        except:
            pass

    print(f"[*] Betöltés: {CSV_PATH}...")
    with open(CSV_PATH, "r", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        kartons = [r for r in reader if r.get('Irattípus') == '6-os karton']

    print(f"[*] Összesen {len(kartons)} db 6-os karton található Orosházához.")
    DOWNLOAD_DIR.mkdir(parents=True, exist_ok=True)

    success_count = 0
    fail_count = 0
    manifest = []

    for idx, k in enumerate(kartons, 1):
        name = k['Név']
        link = k['Link']
        guid = link.split('/')[-1] if '/' in link else link
        fname = f"{safe_filename(name)}_{guid[:8]}.pdf"
        target_path = DOWNLOAD_DIR / fname

        print(f"[{idx}/{len(kartons)}] {safe_filename(name)} ({guid[:8]})...", end=" ", flush=True)

        if target_path.exists() and target_path.stat().st_size > 1000:
            print(f"[MÁR LETÖLTVE: {round(target_path.stat().st_size/1024, 1)} KB]")
            success_count += 1
            manifest.append({"guid": guid, "name": name, "file": fname, "size": target_path.stat().st_size})
            continue

        try:
            link_info = get_pdf_download_url(guid, token)
            dl_url = link_info.get("url")
            if not dl_url:
                print(f"[HIBA: nincs url]")
                fail_count += 1
                continue

            size = download_file(dl_url, target_path)
            print(f"[OK: {round(size/1024, 1)} KB]")
            success_count += 1
            manifest.append({"guid": guid, "name": name, "file": fname, "size": size})
            time.sleep(0.4) # Kíméletes lekérés
        except Exception as e:
            print(f"[HIBA: {e}]")
            fail_count += 1
            if "pass_invalid" in str(e) or "pass_exhausted" in str(e):
                print("\n[!] A megadott token lejárt vagy érvénytelen. Kérjük frissítse a tokent a böngészőből.")
                break

    # Mentés manifestbe
    manifest_path = BASE_DIR / "tools" / "downloaded_kartons.json"
    with open(manifest_path, "w", encoding="utf-8") as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)

    print(f"\n==========================================")
    print(f"KÉSZ! Sikeres: {success_count}/{len(kartons)}, Sikertelen: {fail_count}")
    print(f"Fájlok helye: {DOWNLOAD_DIR}")
    print(f"Jegyzék: {manifest_path}")

if __name__ == "__main__":
    main()
