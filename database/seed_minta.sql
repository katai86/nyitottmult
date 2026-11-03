-- ==============================================================================
-- Nyitott Múlt – Demonstrációs Mintaadatok (Kizárólag fiktív "MINTA" adatok!)
-- Licenc: CC0-1.0 Universal
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Teszt Felhasználók (jelszó: 'MintaJelszo2026!' Argon2/Bcrypt hash: $2y$10$abcdefg...)
-- sha256 mock hash admin felülethez
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `full_name`, `created_at`) VALUES
('u0000001-0000-0000-0000-000000000001', 'admin', 'admin@oroshaza-minta.hu', '$2y$10$wE8s4bYp9YwZ.GfQn3Z3g.v9w9v9w9v9w9v9w9v9w9v9w9v9w9v9w', 'admin', 'Kátai Attila (Rendszergazda)', NOW()),
('u0000001-0000-0000-0000-000000000002', 'lektor_maria', 'maria.lektor@oroshaza-minta.hu', '$2y$10$wE8s4bYp9YwZ.GfQn3Z3g.v9w9v9w9v9w9v9w9v9w9v9w9v9w9v9w', 'reviewer', 'MINTA Dr. Nagy Mária történész', NOW()),
('u0000001-0000-0000-0000-000000000003', 'kutato_peter', 'peter.kutato@oroshaza-minta.hu', '$2y$10$wE8s4bYp9YwZ.GfQn3Z3g.v9w9v9w9v9w9v9w9v9w9v9w9v9w9v9w', 'researcher', 'MINTA Kovács Péter levéltáros', NOW());

-- 2. Levéltári Forrásdokumentumok (MINTA)
INSERT INTO `source_documents` (`id`, `archive_code`, `document_type`, `title`, `date_created`, `original_url`, `downloaded_at`, `sha256_hash`, `license_note`) VALUES
('d0000001-0000-0000-0000-000000000001', 'ÁBTL - 3.1.2. - MINTA-6-OS-KARTON-1959', '6_os_karton', 'MINTA 6-os nyilvántartó karton – Orosházi Politikai Nyomozó Kirendeltség (1959)', '1959-04-12', 'https://nyitottaktak.hu/minta/karton_1959.pdf', NOW(), 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', 'ÁBTL nyilvános minta másolat 2026. évi LI. tv.'),
('d0000001-0000-0000-0000-000000000002', 'ÁBTL - 3.1.2. - MINTA-B-NAPLO-1960', 'b_naplo', 'MINTA Beszervezési Napló – Orosházi Járás (1960)', '1960-01-15', 'https://nyitottaktak.hu/minta/bnaplo_1960.pdf', NOW(), 'a1b2c3d4e5f60718293a4b5c6d7e8f90123456789abcdef0123456789abcdef0', 'ÁBTL nyilvános minta másolat 2026. évi LI. tv.'),
('d0000001-0000-0000-0000-000000000003', 'ÁBTL - 3.1.2. - MINTA-M-9999-DOSSZIE', 'm_dosszie', 'MINTA Munka-dosszié – "Homok" fedőnevű informátor jelentései az orosházi üzemekről (1960–1961)', '1960-09-20', 'https://nyitottaktak.hu/minta/m9999_1960.pdf', NOW(), 'b2c3d4e5f60718293a4b5c6d7e8f90123456789abcdef0123456789abcdef01', 'ÁBTL nyilvános minta másolat 2026. évi LI. tv.');

-- 3. Oldalak és OCR réteg (MINTA)
INSERT INTO `pages` (`id`, `document_id`, `page_number`, `image_path`, `thumbnail_path`, `ocr_text_raw`, `ocr_text_corrected`, `ocr_confidence`, `ocr_boxes_json`) VALUES
('p0000001-0000-0000-0000-000000000001', 'd0000001-0000-0000-0000-000000000001', 1, 'assets/sample_docs/karton_sample_p1.webp', 'assets/sample_docs/karton_sample_p1_thumb.webp',
'ÁLLAMBIZTONSÁGI NYILVÁNTARTÓ KARTON (6-os)\nNév: MINTA Kovács János\nSzületési hely, idő: Orosháza, 1925. március 10.\nAnyja neve: MINTA Kis Julianna\nFoglalkozása: géplakatos, Orosházi Mezőgép Vállalat\nFedőneve: "Homok"\nBeszervezés ideje: 1959. április 12.\nBeszervező tiszt: MINTA Tóth Béla r. fhdgy. (Orosházi PNK)\nBeszervezés alapja: hazafias alapon\nFoglalkoztatási vonal: belső reakció / mezőgazdasági szektor, Orosháza és Gyökeres térsége.',
'ÁLLAMBIZTONSÁGI NYILVÁNTARTÓ KARTON (6-os)\nNév: MINTA Kovács János\nSzületési hely, idő: Orosháza, 1925. március 10.\nAnyja neve: MINTA Kis Julianna\nFoglalkozása: géplakatos, Orosházi Mezőgép Vállalat\nFedőneve: "Homok"\nBeszervezés ideje: 1959. április 12.\nBeszervező tiszt: MINTA Tóth Béla r. fhdgy. (Orosházi PNK)\nBeszervezés alapja: hazafias alapon\nFoglalkoztatási vonal: belső reakció / mezőgazdasági szektor, Orosháza és Gyökeres térsége.',
0.98,
'[{"text": "MINTA Kovács János", "x": 120, "y": 85, "w": 340, "h": 28}, {"text": "Homok", "x": 120, "y": 140, "w": 110, "h": 26}, {"text": "MINTA Tóth Béla r. fhdgy.", "x": 280, "y": 200, "w": 320, "h": 26}]'
),
('p0000001-0000-0000-0000-000000000002', 'd0000001-0000-0000-0000-000000000003', 14, 'assets/sample_docs/m_dosszie_p14.webp', 'assets/sample_docs/m_dosszie_p14_thumb.webp',
'JELENTÉS\nOrosháza, 1960. szeptember 20.\nVette: MINTA Tóth Béla r. százados\nAdta: "Homok" fn. informátor\nTalálkozás helye: Orosháza, Beloiannisz utcai ' + 'K' + ' lakás\n\nJelentem, hogy 1960. szeptember 18-án este találkoztam MINTA Dr. Szabó István gimnáziumi tanárral a Szabadság téri presszóban. Szabó tanár úr az 1956-os eseményekről és a helyi egyházi ifjúsági körökről beszélt, kifejezve aggodalmát a kollektivizálás orosházi kényszermódszerei miatt. Felesége, MINTA Kis Éva nem vett részt a beszélgetésben.',
'JELENTÉS\nOrosháza, 1960. szeptember 20.\nVette: MINTA Tóth Béla r. százados\nAdta: "Homok" fn. informátor\nTalálkozás helye: Orosháza, Beloiannisz utcai ' + 'K' + ' lakás\n\nJelentem, hogy 1960. szeptember 18-án este találkoztam MINTA Dr. Szabó István gimnáziumi tanárral a Szabadság téri presszóban. Szabó tanár úr az 1956-os eseményekről és a helyi egyházi ifjúsági körökről beszélt, kifejezve aggodalmát a kollektivizálás orosházi kényszermódszerei miatt. Felesége, MINTA Kis Éva nem vett részt a beszélgetésben.',
0.96,
'[{"text": "MINTA Dr. Szabó István", "x": 150, "y": 320, "w": 310, "h": 24}, {"text": "Homok fn. informátor", "x": 140, "y": 110, "w": 260, "h": 24}]'
);

-- 4. Entitások (MINTA)
INSERT INTO `entities` (`id`, `type`, `canonical_name`, `aliases`, `time_range_start`, `time_range_end`, `metadata`, `is_sample`) VALUES
('e0000001-0000-0000-0000-000000000001', 'person', 'MINTA Kovács János', '["Kovács J.", "Homok", "Kovács János géplakatos"]', '1925-03-10', '1985-11-04', '{"birth_place": "Orosháza", "occupation": "géplakatos", "mother_name": "MINTA Kis Julianna"}', 1),
('e0000001-0000-0000-0000-000000000002', 'codename', 'Homok (MINTA fedőnév)', '["Homok"]', '1959-04-12', '1968-01-01', '{"category": "hálózati személy fedőneve"}', 1),
('e0000001-0000-0000-0000-000000000003', 'person', 'MINTA Dr. Szabó István', '["Szabó István", "dr. Szabó István gimnáziumi tanár"]', '1918-06-22', '1995-02-14', '{"birth_place": "Szentetornya", "occupation": "történelemtanár", "workplace": "Táncsics Mihály Gimnázium"}', 1),
('e0000001-0000-0000-0000-000000000004', 'person', 'MINTA Tóth Béla', '["Tóth Béla r. fhdgy.", "Tóth Béla r. százados"]', '1922-01-14', '1988-08-30', '{"rank": "rendőr százados", "unit": "Orosházi Politikai Nyomozó Kirendeltség"}', 1),
('e0000001-0000-0000-0000-000000000005', 'person', 'MINTA Kis Éva', '["Szabó Istvánné Kis Éva"]', '1928-09-01', NULL, '{"occupation": "adminisztrátor", "note": "státusza ismeretlen / feltételezhetően élő vagy nem igazolt elhunyt"}', 1),
('e0000001-0000-0000-0000-000000000006', 'organization', 'Orosházi Politikai Nyomozó Kirendeltség', '["Orosházi PNK"]', '1959-01-01', '1960-12-31', '{"type": "állambiztonsági egység"}', 1),
('e0000001-0000-0000-0000-000000000007', 'organization', 'Táncsics Mihály Gimnázium', '["Orosházi Állami Gimnázium"]', '1933-09-01', NULL, '{"type": "oktatási intézmény"}', 1),
('e0000001-0000-0000-0000-000000000008', 'place', 'Szabadság téri presszó (egykori)', '["Lenin téri presszó", "Központi Cukrászda"]', '1950-01-01', '1989-12-31', '{"type": "találkozási helyszín"}', 1);

-- 5. Adatvédelmi Nyilvántartás (2026. évi LI. tv., Halál éve + 5 év szabály)
INSERT INTO `privacy_records` (`id`, `entity_id`, `status`, `death_year`, `legal_release_year`, `is_publicly_visible`, `redaction_reason`, `verified_by`, `verified_at`) VALUES
-- 1985-ben halt meg -> 1985 + 5 = 1990 <= 2026 -> NYILVÁNOS
('pr000001-0000-0000-0000-000000000001', 'e0000001-0000-0000-0000-000000000001', 'deceased', 1985, 1990, 1, NULL, 'u0000001-0000-0000-0000-000000000001', NOW()),
-- Fedőnév: mindig nyilvános
('pr000001-0000-0000-0000-000000000002', 'e0000001-0000-0000-0000-000000000002', 'deceased', 1985, 1990, 1, NULL, 'u0000001-0000-0000-0000-000000000001', NOW()),
-- 1995-ben halt meg -> 1995 + 5 = 2000 <= 2026 -> NYILVÁNOS
('pr000001-0000-0000-0000-000000000003', 'e0000001-0000-0000-0000-000000000003', 'deceased', 1995, 2000, 1, NULL, 'u0000001-0000-0000-0000-000000000001', NOW()),
-- Tiszt: 1988-ban halt meg -> NYILVÁNOS
('pr000001-0000-0000-0000-000000000004', 'e0000001-0000-0000-0000-000000000004', 'deceased', 1988, 1993, 1, NULL, 'u0000001-0000-0000-0000-000000000001', NOW()),
-- Családtag: státusza unknown vagy élő -> SZIGORÚAN VÉDETT / NEM NYILVÁNOS!
('pr000001-0000-0000-0000-000000000005', 'e0000001-0000-0000-0000-000000000005', 'unknown', NULL, NULL, 0, 'Élő személy vagy nem telt el a halál évét követő 5 év (2026. évi LI. tv.). A név anonimizált.', 'u0000001-0000-0000-0000-000000000001', NOW()),
-- Intézmények és helyszínek: nyilvánosak
('pr000001-0000-0000-0000-000000000006', 'e0000001-0000-0000-0000-000000000006', 'deceased', 1960, 1965, 1, NULL, 'u0000001-0000-0000-0000-000000000001', NOW()),
('pr000001-0000-0000-0000-000000000007', 'e0000001-0000-0000-0000-000000000007', 'deceased', 1990, 1995, 1, NULL, 'u0000001-0000-0000-0000-000000000001', NOW()),
('pr000001-0000-0000-0000-000000000008', 'e0000001-0000-0000-0000-000000000008', 'deceased', 1989, 1994, 1, NULL, 'u0000001-0000-0000-0000-000000000001', NOW());

-- 6. Forrásállítások (Claims)
INSERT INTO `claims` (`id`, `document_id`, `page_id`, `quote`, `bounding_box`, `statement`, `subject_entity_id`, `status`, `ai_generated`, `confidence_score`, `created_by`) VALUES
('c0000001-0000-0000-0000-000000000001', 'd0000001-0000-0000-0000-000000000001', 'p0000001-0000-0000-0000-000000000001',
'Név: MINTA Kovács János (...) Fedőneve: "Homok" (...) Beszervezés ideje: 1959. április 12. Beszervező tiszt: MINTA Tóth Béla r. fhdgy.',
'{"x": 120, "y": 85, "w": 480, "h": 140}',
'MINTA Kovács Jánost 1959. április 12-én szervezte be MINTA Tóth Béla rendőr főhadnagy hazafias alapon, "Homok" fedőnéven informátorként az Orosházi Politikai Nyomozó Kirendeltséghez.',
'e0000001-0000-0000-0000-000000000001', 'ellenorzott', 0, 1.0, 'u0000001-0000-0000-0000-000000000001'),

('c0000001-0000-0000-0000-000000000002', 'd0000001-0000-0000-0000-000000000003', 'p0000001-0000-0000-0000-000000000002',
'Jelentem, hogy 1960. szeptember 18-án este találkoztam MINTA Dr. Szabó István gimnáziumi tanárral a Szabadság téri presszóban. Szabó tanár úr az 1956-os eseményekről és a helyi egyházi ifjúsági körökről beszélt...',
'{"x": 140, "y": 280, "w": 520, "h": 160}',
'"Homok" fedőnevű informátor 1960. szeptember 18-i jelentésében megfigyelt személyként számolt be Dr. Szabó István gimnáziumi tanárról a helyi 1956-os megnyilvánulásai kapcsán.',
'e0000001-0000-0000-0000-000000000003', 'ellenorzott', 0, 0.98, 'u0000001-0000-0000-0000-000000000001');

-- 7. Kétlépcsős Lektori Ellenőrzés (Dual Verification) a közzétételhez!
INSERT INTO `claim_verifications` (`id`, `claim_id`, `verifier_id`, `decision`, `notes`, `created_at`) VALUES
('v0000001-0000-0000-0000-000000000001', 'c0000001-0000-0000-0000-000000000001', 'u0000001-0000-0000-0000-000000000002', 'approved', 'Az eredeti 6-os karton szövege és jelzete egyezik. Lektorálva.', NOW()),
('v0000001-0000-0000-0000-000000000002', 'c0000001-0000-0000-0000-000000000001', 'u0000001-0000-0000-0000-000000000003', 'approved', 'Második független ellenőrzés jóváhagyva. Közzétehető.', NOW()),
('v0000001-0000-0000-0000-000000000003', 'c0000001-0000-0000-0000-000000000002', 'u0000001-0000-0000-0000-000000000002', 'approved', 'A jelentés szó szerinti idézete egyezik, szerep helyesen megfigyeltként jelölve.', NOW()),
('v0000001-0000-0000-0000-000000000004', 'c0000001-0000-0000-0000-000000000002', 'u0000001-0000-0000-0000-000000000003', 'approved', 'Második ellenőrzés rendben. Dr. Szabó István áldozat/megfigyelt.', NOW());

-- 8. Szerepkörök az iratban (Szigorúan elválasztva az ügynök és a megfigyelt!)
INSERT INTO `roles` (`id`, `entity_id`, `document_id`, `role_type`, `justification_claim_id`, `created_at`) VALUES
('r0000001-0000-0000-0000-000000000001', 'e0000001-0000-0000-0000-000000000001', 'd0000001-0000-0000-0000-000000000001', 'beszervezett_halozati_szemely', 'c0000001-0000-0000-0000-000000000001', NOW()),
('r0000001-0000-0000-0000-000000000002', 'e0000001-0000-0000-0000-000000000004', 'd0000001-0000-0000-0000-000000000001', 'tiszt', 'c0000001-0000-0000-0000-000000000001', NOW()),
('r0000001-0000-0000-0000-000000000003', 'e0000001-0000-0000-0000-000000000003', 'd0000001-0000-0000-0000-000000000003', 'megfigyelt', 'c0000001-0000-0000-0000-000000000002', NOW()),
('r0000001-0000-0000-0000-000000000004', 'e0000001-0000-0000-0000-000000000005', 'd0000001-0000-0000-0000-000000000003', 'egyeb_emlitett', 'c0000001-0000-0000-0000-000000000002', NOW());

-- 9. Kapcsolatok (Kizárólag ellenőrzött állítás alapján!)
INSERT INTO `relations` (`id`, `source_entity_id`, `target_entity_id`, `relation_type`, `claim_id`, `valid_from`, `valid_to`, `created_at`) VALUES
('rel00001-0000-0000-0000-000000000001', 'e0000001-0000-0000-0000-000000000004', 'e0000001-0000-0000-0000-000000000001', 'beszervezoje', 'c0000001-0000-0000-0000-000000000001', '1959-04-12', NULL, NOW()),
('rel00001-0000-0000-0000-000000000002', 'e0000001-0000-0000-0000-000000000001', 'e0000001-0000-0000-0000-000000000003', 'megfigyeltje', 'c0000001-0000-0000-0000-000000000002', '1960-09-18', NULL, NOW());

-- 10. Térbeli helyszínek (PostGIS / MySQL Spatial - Magánlakás elmosási logikával!)
INSERT INTO `place_geometries` (`id`, `entity_id`, `latitude`, `longitude`, `precision_level`, `is_private_residence`, `public_latitude`, `public_longitude`, `address_text`, `district_id`) VALUES
-- Nyilvános helyszín: presszó a Szabadság téren (pontos koordináta publikus)
('geo00001-0000-0000-0000-000000000001', 'e0000001-0000-0000-0000-000000000008', 46.5658000, 20.6634000, 'exact_building', 0, 46.5658000, 20.6634000, 'Orosháza, Szabadság tér 2.', 'belvaros'),
-- Magánlakás (pl. Dr. Szabó lakása): a valós cím el van rejtve, publikusan csak a városrész közepe jelenik meg!
('geo00001-0000-0000-0000-000000000002', 'e0000001-0000-0000-0000-000000000003', 46.5682000, 20.6675000, 'exact_building', 1, 46.5658000, 20.6634000, 'Orosháza, Belváros (magánlakás, törvényileg elmosva)', 'belvaros');

-- 11. Helytörténeti Interaktív Történet (MINTA)
INSERT INTO `stories` (`id`, `slug`, `title`, `summary`, `cover_image`, `is_published`, `author_name`) VALUES
('s0000001-0000-0000-0000-000000000001', 'oroshazi-tanar-megfigyelese-1960', 'Egy orosházi tanár megfigyelése (1960)', 'Hogyan kísérte figyelemmel az Orosházi Politikai Nyomozó Kirendeltség a helyi gimnázium oktatóit az 1956 utáni években?', 'assets/sample_docs/story_cover.webp', 1, 'Kátai Attila');

INSERT INTO `story_steps` (`id`, `story_id`, `step_number`, `title`, `content`, `focus_lat`, `focus_lng`, `focus_zoom`, `document_id`, `page_id`, `claim_id`) VALUES
('ss000001-0000-0000-0000-000000000001', 's0000001-0000-0000-0000-000000000001', 1, 'Beszervezés a Mezőgép Vállalatnál', '1959 tavaszán a helyi állambiztonsági kirendeltség beszervezte MINTA Kovács Jánost hazafias alapon, "Homok" fedőnéven.', 46.5590000, 20.6710000, 14, 'd0000001-0000-0000-0000-000000000001', 'p0000001-0000-0000-0000-000000000001', 'c0000001-0000-0000-0000-000000000001'),
('ss000001-0000-0000-0000-000000000002', 's0000001-0000-0000-0000-000000000001', 2, 'Találkozó a Főtéren', '1960 szeptemberében "Homok" jelentést adott Dr. Szabó István gimnáziumi tanárról, akivel a helyi cukrászdában beszélgetett az orosházi hangulatról.', 46.5658000, 20.6634000, 15, 'd0000001-0000-0000-0000-000000000003', 'p0000001-0000-0000-0000-000000000002', 'c0000001-0000-0000-0000-000000000002');

-- 12. Kutatói Hipotézis és Feladat
INSERT INTO `hypotheses` (`id`, `title`, `description`, `status`, `created_by`) VALUES
('h0000001-0000-0000-0000-000000000001', 'Orosházi PNK létszáma és hálózata 1959-ben', 'Az orosházi politikai nyomozók kirendeltsége az 1956-os megtorlások és a téeszesítés idején megnövelt ügynökhálózattal működött a járásban.', 'igazolt', 'u0000001-0000-0000-0000-000000000001');

INSERT INTO `research_tasks` (`id`, `title`, `priority`, `status`, `assigned_to`, `related_doc_id`, `deadline`) VALUES
('t0000001-0000-0000-0000-000000000001', 'A 6-os karton B-napló párhuzamainak ellenőrzése', 'high', 'in_progress', 'u0000001-0000-0000-0000-000000000002', 'd0000001-0000-0000-0000-000000000001', '2026-12-01');

SET FOREIGN_KEY_CHECKS = 1;
