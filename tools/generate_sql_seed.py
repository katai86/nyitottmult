#!/usr/bin/env python3
"""
Nyitott Múlt – SQL Import Generáló Eszköz

Használat:
    python generate_sql_seed.py --input ingested_xxxx.json --output import_uj_adatok.sql
"""

import sys
import json
import uuid
import argparse
from pathlib import Path

def escape_sql(val):
    if val is None:
        return "NULL"
    if isinstance(val, (int, float)):
        return str(val)
    if isinstance(val, (dict, list)):
        val = json.dumps(val, ensure_ascii=False)
    # MySQL string escape
    escaped = str(val).replace("\\", "\\\\").replace("'", "\\'")
    return f"'{escaped}'"

def generate_sql(json_path, output_sql_path):
    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)

    doc = data["document"]
    pages = data.get("pages", [])

    lines = [
        "-- ==============================================================================",
        f"-- Nyitott Múlt Automatikus Import: {doc['archive_code']}",
        "-- ==============================================================================",
        "SET NAMES utf8mb4;",
        "SET FOREIGN_KEY_CHECKS = 0;\n"
    ]

    # 1. source_documents beszúrása
    lines.append(
        f"INSERT INTO `source_documents` (`id`, `archive_code`, `document_type`, `title`, `sha256_hash`, `license_note`, `created_at`) "
        f"VALUES ({escape_sql(doc['id'])}, {escape_sql(doc['archive_code'])}, {escape_sql(doc['document_type'])}, {escape_sql(doc['title'])}, {escape_sql(doc.get('sha256_hash'))}, 'ÁBTL kutatói másolat 2026. évi LI. tv.', NOW()) "
        f"ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);\n"
    )

    # 2. pages beszúrása
    for p in pages:
        page_id = str(uuid.uuid4())
        boxes_json = p.get("boxes", [])
        lines.append(
            f"INSERT INTO `pages` (`id`, `document_id`, `page_number`, `image_path`, `ocr_text_raw`, `ocr_text_corrected`, `ocr_confidence`, `ocr_boxes_json`, `created_at`) "
            f"VALUES ({escape_sql(page_id)}, {escape_sql(doc['id'])}, {int(p['page_number'])}, {escape_sql(p['image_path'])}, {escape_sql(p['ocr_text'])}, {escape_sql(p['ocr_text'])}, {float(p.get('confidence', 1.0))}, {escape_sql(boxes_json)}, NOW()) "
            f"ON DUPLICATE KEY UPDATE `ocr_text_raw` = VALUES(`ocr_text_raw`);"
        )

    lines.append("\nSET FOREIGN_KEY_CHECKS = 1;")

    output_path = Path(output_sql_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with open(output_path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines) + "\n")

    print(f"[SIKER] SQL import fájl létrehozva: {output_path}")
    print("[INFO] Ezt a fájlt közvetlenül importálhatja a DirectAdmin phpMyAdmin felületén!")

def main():
    parser = argparse.ArgumentParser(description="Nyitott Múlt SQL Generáló")
    parser.add_argument("--input", required=True, help="Az ingest_pdf.py által generált JSON fájl")
    parser.add_argument("--output", default="import_uj_adatok.sql", help="Kimeneti SQL fájl neve")

    args = parser.parse_args()
    generate_sql(args.input, args.output)

if __name__ == "__main__":
    main()
