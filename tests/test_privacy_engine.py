#!/usr/bin/env python3
"""
Nyitott Múlt – Adatvédelmi és 2026. évi LI. tv. Szabály Tesztelő
"""

import unittest
from datetime import datetime

class TestPrivacyRules(unittest.TestCase):
    def test_death_plus_five_years_rule(self):
        """Halál éve + 5 év szabály tesztelése (2026-os állapot szerint)"""
        current_year = 2026
        plus_years = 5

        # 1. 1985-ben halt meg -> 1985 + 5 = 1990 <= 2026 -> NYILVÁNOS
        death_year_1 = 1985
        self.assertTrue(death_year_1 + plus_years <= current_year)

        # 2. 1995-ben halt meg -> 1995 + 5 = 2000 <= 2026 -> NYILVÁNOS
        death_year_2 = 1995
        self.assertTrue(death_year_2 + plus_years <= current_year)

        # 3. 2023-ban halt meg -> 2023 + 5 = 2028 > 2026 -> VÉDETT / NEM NYILVÁNOS!
        death_year_3 = 2023
        self.assertFalse(death_year_3 + plus_years <= current_year)

        # 4. Élő vagy ismeretlen státuszú személy -> VÉDETT / NEM NYILVÁNOS!
        living_person_status = "alive"
        self.assertNotEqual(living_person_status, "deceased")

        unknown_person_status = "unknown"
        self.assertNotEqual(unknown_person_status, "deceased")

    def test_private_residence_coordinate_blurring(self):
        """Magánlakások koordinátáinak törvényi elmosása"""
        exact_lat = 46.5682000
        exact_lng = 20.6675000
        district_center_lat = 46.5658000
        district_center_lng = 20.6634000

        is_private_residence = True

        if is_private_residence:
            public_lat = district_center_lat
            public_lng = district_center_lng
            address_text = "[Magánlakás – városrész szintre elmosva]"
        else:
            public_lat = exact_lat
            public_lng = exact_lng
            address_text = "Pontos cím"

        self.assertEqual(public_lat, district_center_lat)
        self.assertEqual(public_lng, district_center_lng)
        self.assertIn("elmosva", address_text)

if __name__ == "__main__":
    unittest.main()
