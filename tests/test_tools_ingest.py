#!/usr/bin/env python3
"""
Nyitott Múlt – Ingestion és SQL Generáló Eszközök Tesztje
"""

import unittest
import json
import tempfile
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
TOOLS_DIR = BASE_DIR / "tools"

import sys
sys.path.insert(0, str(TOOLS_DIR))
import generate_sql_seed

class TestIngestionTools(unittest.TestCase):
    def test_sql_generation_from_json(self):
        # Minta JSON adat
        sample_json_data = {
            "document": {
                "id": "test-doc-001",
                "archive_code": "ÁBTL - 3.1.2. - MINTA-TESZT-01",
                "document_type": "6_os_karton",
                "title": "Teszt 6-os karton irat",
                "sha256_hash": "abcdef1234567890"
            },
            "pages": [
                {
                    "page_number": 1,
                    "image_path": "uploads/docs/test-doc-001/page_1.png",
                    "ocr_text": "Kovács János karton teszt szöveg",
                    "confidence": 0.98,
                    "boxes": [{"x": 10, "y": 20, "w": 30, "h": 40, "text": "Kovács"}]
                }
            ]
        }

        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as f_json:
            json.dump(sample_json_data, f_json)
            json_file = f_json.name

        with tempfile.NamedTemporaryFile("w", suffix=".sql", delete=False, encoding="utf-8") as f_sql:
            sql_file = f_sql.name

        try:
            generate_sql_seed.generate_sql(json_file, sql_file)
            with open(sql_file, "r", encoding="utf-8") as f:
                sql_content = f.read()

            self.assertIn("ÁBTL - 3.1.2. - MINTA-TESZT-01", sql_content)
            self.assertIn("test-doc-001", sql_content)
            self.assertIn("Kovács János karton teszt szöveg", sql_content)
        finally:
            Path(json_file).unlink(missing_ok=True)
            Path(sql_file).unlink(missing_ok=True)

if __name__ == "__main__":
    unittest.main()
