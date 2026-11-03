#!/usr/bin/env python3
"""
Nyitott Múlt – Adatbázis Séma és MINTA Seed Ellenőrző Tesztek
"""

import unittest
import re
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
DB_DIR = BASE_DIR / "database"

class TestDatabaseFiles(unittest.TestCase):
    def test_schema_sql_tables(self):
        schema_path = DB_DIR / "schema.sql"
        self.assertTrue(schema_path.exists(), "schema.sql hiányzik")

        with open(schema_path, "r", encoding="utf-8") as f:
            content = f.read()

        required_tables = [
            "users",
            "source_documents",
            "pages",
            "entities",
            "privacy_records",
            "claims",
            "claim_verifications",
            "roles",
            "relations",
            "place_geometries",
            "submissions",
            "correction_requests",
            "stories",
            "story_steps",
            "hypotheses",
            "research_tasks",
            "audit_logs"
        ]

        for tbl in required_tables:
            pattern = rf"CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`{tbl}`"
            self.assertRegex(content, pattern, f"A(z) {tbl} tábla nem található a schema.sql-ben")

    def test_seed_minta_only_policy(self):
        """Szigorú teszt: a seed adat kizárólag 'MINTA' jelölésű kitalált személyeket tartalmazhat!"""
        seed_path = DB_DIR / "seed_minta.sql"
        self.assertTrue(seed_path.exists(), "seed_minta.sql hiányzik")

        with open(seed_path, "r", encoding="utf-8") as f:
            content = f.read()

        # Keressük az entities táblába történő beszúrásokat
        # Minden beszúrt személynévnek MINTA előtaggal kell rendelkeznie
        person_inserts = re.findall(r"'person',\s*'([^']+)'", content)
        self.assertGreater(len(person_inserts), 0)

        for person in person_inserts:
            self.assertTrue(
                person.startswith("MINTA "),
                f"HIBA: A(z) '{person}' személynév nem MINTA-ként van jelölve a repóban!"
            )

    def test_dual_verification_in_seed(self):
        """Teszteli, hogy a seedben a közzétett állítások 2 független ellenőrzővel rendelkeznek-e"""
        seed_path = DB_DIR / "seed_minta.sql"
        with open(seed_path, "r", encoding="utf-8") as f:
            content = f.read()

        # Számoljuk meg a claim_verifications rekordokat
        verifications = re.findall(r"\('v0000001-[^)]+\)", content)
        self.assertGreaterEqual(len(verifications), 2, "A seedben nincs meg a legalább 2 lektori jóváhagyás")

if __name__ == "__main__":
    unittest.main()
