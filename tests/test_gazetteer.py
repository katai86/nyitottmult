#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Nyitott Múlt – Orosháza Helytörténeti Névtár Integritás Tesztek
"""

import json
import unittest
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
GAZETTEER_DIR = BASE_DIR / "config" / "gazetteer"

class TestGazetteerIntegrity(unittest.TestCase):
    def test_districts_file(self):
        fpath = GAZETTEER_DIR / "districts.json"
        self.assertTrue(fpath.exists(), "districts.json hiányzik")
        with open(fpath, "r", encoding="utf-8") as f:
            data = json.load(f)
        self.assertIsInstance(data, list)
        self.assertGreater(len(data), 0)

        names = [d["name"] for d in data]
        self.assertIn("Szentetornya", names)
        self.assertIn("Rákóczitelep", names)
        self.assertIn("Gyökeres", names)
        self.assertIn("Gyopárosfürdő", names)
        self.assertIn("Kiscsákó", names)

    def test_street_history_file(self):
        fpath = GAZETTEER_DIR / "street_history.json"
        self.assertTrue(fpath.exists(), "street_history.json hiányzik")
        with open(fpath, "r", encoding="utf-8") as f:
            data = json.load(f)
        self.assertIsInstance(data, list)

        # Ellenőrizzük, hogy szerepel-e a Szabadság tér korábbi Lenin tér neve
        szabadsag_ter = next((s for s in data if s["id"] == "szabadsag_ter"), None)
        self.assertIsNotNone(szabadsag_ter)
        history_names = [h["name"] for h in szabadsag_ter["history"]]
        self.assertIn("Lenin tér", history_names)
        self.assertIn("Szabadság tér", history_names)

    def test_organizations_file(self):
        fpath = GAZETTEER_DIR / "organizations.json"
        self.assertTrue(fpath.exists(), "organizations.json hiányzik")
        with open(fpath, "r", encoding="utf-8") as f:
            data = json.load(f)
        self.assertIsInstance(data, list)

        names = [o["name"].lower() for o in data]
        self.assertTrue(any("üveggyár" in n or "sikuveggyar" in n or "üveg" in n for n in names))
        self.assertTrue(any("gimnázium" in n or "gimnazium" in n for n in names))

    def test_state_security_file(self):
        fpath = GAZETTEER_DIR / "state_security.json"
        self.assertTrue(fpath.exists(), "state_security.json hiányzik")
        with open(fpath, "r", encoding="utf-8") as f:
            data = json.load(f)
        self.assertIsInstance(data, list)

        units = [u["id"] for u in data]
        self.assertIn("oroshazi_pnk_1959_60", units)

    def test_codenames_sample_flag(self):
        fpath = GAZETTEER_DIR / "codenames.json"
        self.assertTrue(fpath.exists(), "codenames.json hiányzik")
        with open(fpath, "r", encoding="utf-8") as f:
            data = json.load(f)
        # Szigorú követelmény: minden fedőnévnek MINTA jelöléssel kell rendelkeznie!
        for c in data:
            self.assertTrue(c.get("is_sample"), f"A(z) {c['codename']} fedőnév nincs MINTA-ként jelölve!")

if __name__ == "__main__":
    unittest.main()
