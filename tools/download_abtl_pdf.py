#!/usr/bin/env python3
"""
Nyitott Akták (ÁBTL) PDF letöltő segédeszköz
Használat:
  python tools/download_abtl_pdf.py --guid <DOKUMENTUM_GUID> --pass <ABTL_PDF_PASS>
"""

import sys
import ssl
import json
import argparse
import urllib.request
from pathlib import Path

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def get_record_metadata(guid):
    url = f"https://nyitottaktak.hu/api/record/{guid}"
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
        return json.loads(resp.read().decode('utf-8'))

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
    parser = argparse.ArgumentParser(description="ÁBTL PDF Letöltő")
    parser.add_argument("--guid", required=True, help="Nyitott Akták dokumentum GUID")
    parser.add_argument("--pass-token", dest="pdf_pass", required=False, help="abtl_pdf_pass token a böngésző localStorage-ból")
    parser.add_argument("--out", default=None, help="Célfájl elérési útja")
    args = parser.parse_args()

    print(f"[*] Dokumentum metaadatok lekérése: {args.guid}...")
    try:
        meta = get_record_metadata(args.guid)
        print(f"    Név: {meta.get('nev')} | Irattípus: {meta.get('irattipus')} | Fedőnév: {meta.get('fedonev')}")
        print(f"    Eredeti PDF azonosító: {meta.get('pdf')}")
    except Exception as e:
        print(f"[!] Hiba a metaadatok lekérésekor: {e}")
        return

    if not args.pdf_pass:
        print("\n[!] Nincs megadva --pass-token.")
        print("    A nyitottaktak.hu Cloudflare Turnstile robotvédelmet használ a PDF fájlok közvetlen eléréséhez.")
        print("    A böngészőjében a megnyitott oldalon a feloldókulcs megtekinthető a konzolban:")
        print("    F12 -> Console -> írja be: localStorage.getItem('abtl_pdf_pass')")
        print("    Ezután futtassa újra: python tools/download_abtl_pdf.py --guid <guid> --pass-token <token_értéke>")
        return

    print("[*] PDF letöltési hivatkozás kérése az ÁBTL API-tól...")
    try:
        link_data = get_pdf_download_url(args.guid, args.pdf_pass)
        direct_url = link_data.get("url")
        if not direct_url:
            print(f"[!] Nem érkezett érvényes letöltési URL: {link_data}")
            return
        print(f"    Közvetlen URL megszerezve (érvényes: {link_data.get('expires_in')} másodpercig)")
    except Exception as e:
        print(f"[!] Hiba a letöltési URL kérésekor (érvénytelen vagy lejárt pass token?): {e}")
        return

    out_name = args.out or f"uploads/documents/{meta.get('nev', 'abtl')}_{meta.get('pdf', 'karton.pdf')}".replace(" ", "_")
    target = Path(out_name)
    print(f"[*] PDF letöltése a(z) {target} helyre...")
    try:
        size = download_file(direct_url, target)
        print(f"[OK] SIKER! A PDF fajl letoltve ({round(size / 1024, 1)} KB): {target}")
    except Exception as e:
        print(f"[!] Hiba a letöltés során: {e}")

if __name__ == "__main__":
    main()
