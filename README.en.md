# Open Past (Nyitott Múlt) – Settlement-Agnostic Local History Research Platform

[![License: AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-blue.svg)](LICENSE)
[![Data License: CC0-1.0](https://img.shields.io/badge/sample__data-CC0--1.0-green.svg)](https://creativecommons.org/publicdomain/zero/1.0/)

> **Initial Deployment:** *"Secret Past of Orosháza – In the Footsteps of State Security Files"*  
> **Initiator & Architect:** Attila Kátai, AI Specialist  
> **Mission:** A free, public, map-based, source-driven, AI-supported research platform for Hungarian state security records, scoped to a single locality.  
> **Core Principle:** **NEVER a smear list.** Every claim is strictly and visibly linked to its archival document, page number, verbatim quote, and image bounding box.

---

## 📌 Context and Legal Framework

Under **Section 14 of Hungarian Act LI of 2026**, the Historical Archives of the Hungarian State Security (ÁBTL) publishes declassified files online starting November 4, 2026 (*nyitottaktak.hu*).

**Nyitott Múlt** provides local communities, descendants, and researchers with context, geographical correlation, and chronological timelines, systematically preventing misinterpretation, defamatory name lists, and sensationalist claims.

### Strict Privacy and Research Ethics:
1. **Death Year + 5 Years Rule:** Non-anonymized personal data is made public strictly after the 5th calendar year following the person's death. Living or unknown status individuals are obscured by default.
2. **Dual Verification:** Claims involving individuals require independent verification by two researchers before publication.
3. **Role Segregation:** Being mentioned in an operative file **NEVER equates to being an agent**. The system strictly differentiates:
   - *Recruited Network Person* (agent, informer, secret operative)
   - *Target / Observed Person* (victim, person under surveillance)
   - *State Security Officer* (case officer, operative officer)
   - *Target of Failed Recruitment Attempt*
   - *Other Mentioned Person* (family member, coworker, witness)
   - *Unknown / Unclarified Role*
4. **Private Residence Protection:** Exact residential addresses are never displayed; coordinates are blurred to district or neighborhood polygons using spatial algorithms.
5. **Right to Rectification:** Anyone may submit a correction request or complementary statement, which is legally bound to research review deadlines.

---

## 🏛️ Settlement Agnostic Architecture

The platform can be adapted to any municipality via simple configuration:
* `config/site.yaml`: Town name, map coordinates, boundary polygon, branding, contact info.
* `config/gazetteer/`: Historical districts, past street names with valid time intervals, historical institutions, churches, factories, and operative units.
* **Sample Data Policy:** No real archival records or personal data are committed to the repository; only fictitious `MINTA` (sample) records.

---

## 🛠️ Deployment on DirectAdmin Shared Hosting (PHP + MySQL)

Optimized for lightweight, maintenance-free hosting on standard DirectAdmin / cPanel servers (PHP 8.1/8.2+ and MySQL 8.0 / MariaDB 10.6+):
1. Import `database/schema.sql` and `database/seed_minta.sql` into MySQL.
2. Upload the contents of `public_html/` to your server's `public_html` directory.
3. Configure database credentials and optional Gemini API key in `public_html/config.php`.

---

## 📄 License & Attribution

- **Source Code:** [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE)
- **Sample Data and Gazetteer:** [Creative Commons Zero (CC0-1.0)](https://creativecommons.org/publicdomain/zero/1.0/)
- **Initiator & Project Lead:** Attila Kátai
- **Citation:** See [CITATION.cff](CITATION.cff).
