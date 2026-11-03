#!/usr/bin/env python3
"""
Nyitott Múlt – Közvetlen Szinkronizáló a DirectAdmin Tárhelyhez

Használat:
    python sync_to_directadmin.py --input ingested_xxxx.json --url https://oroshaza.nyitottaktak.hu --api-key TITKOS_KULCS
"""

import sys
import json
import argparse
import urllib.request
import urllib.error

def sync_data(json_path, server_url, api_key):
    with open(json_path, "r", encoding="utf-8") as f:
        payload = json.load(f)

    target_endpoint = server_url.rstrip("/") + "/api/v1/researcher/import"
    data = json.dumps(payload, ensure_ascii=False).encode("utf-8")

    req = urllib.request.Request(
        target_endpoint,
        data=data,
        headers={
            "Content-Type": "application/json; charset=utf-8",
            "Authorization": f"Bearer {api_key}"
        },
        method="POST"
    )

    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            result = json.loads(resp.read().decode("utf-8"))
            print(f"[SIKER] A szerver fogadta az adatokat: {result}")
    except urllib.error.HTTPError as e:
        print(f"[HIBA] Szerver hiba: HTTP {e.code} - {e.read().decode('utf-8')}")
    except Exception as e:
        print(f"[HIBA] Hálózati hiba a szinkronizálás során: {e}")

def main():
    parser = argparse.ArgumentParser(description="Nyitott Múlt DirectAdmin Szinkronizáló")
    parser.add_argument("--input", required=True, help="Az ingested JSON fájl elérési útja")
    parser.add_argument("--url", default="http://localhost", help="A DirectAdminos honlap URL-je")
    parser.add_argument("--api-key", default="", help="Adminisztrátori API kulcs")

    args = parser.parse_args()
    sync_data(args.input, args.url, args.api_key)

if __name__ == "__main__":
    main()
