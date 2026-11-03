-- ==============================================================================
-- Nyitott Múlt – Helytörténeti Kutatóplatform
-- Adatbázis Séma (MySQL 8.0+ / MariaDB 10.6+)
-- Licenc: AGPL-3.0
-- Kezdeményező: Kátai Attila
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Felhasználók és Jogosultságok
CREATE TABLE IF NOT EXISTS `users` (
  `id` CHAR(36) NOT NULL,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('visitor', 'submitter', 'researcher', 'reviewer', 'admin') NOT NULL DEFAULT 'visitor',
  `full_name` VARCHAR(128) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Levéltári Forrásdokumentumok
CREATE TABLE IF NOT EXISTS `source_documents` (
  `id` CHAR(36) NOT NULL,
  `archive_code` VARCHAR(128) NOT NULL UNIQUE,
  `document_type` ENUM(
    '6_os_karton',
    'b_naplo',
    'h_adattar',
    'b_dosszie',
    'm_dosszie',
    'o_dosszie',
    'vizsgalati_dosszie',
    'egyeb'
  ) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `date_created` DATE NULL,
  `original_url` TEXT NULL,
  `downloaded_at` DATETIME NULL,
  `sha256_hash` CHAR(64) NULL,
  `license_note` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_doc_type` (`document_type`),
  INDEX `idx_date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Dokumentum Oldalak és Szövegréteg (OCR)
CREATE TABLE IF NOT EXISTS `pages` (
  `id` CHAR(36) NOT NULL,
  `document_id` CHAR(36) NOT NULL,
  `page_number` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) NULL,
  `ocr_text_raw` MEDIUMTEXT NULL,
  `ocr_text_corrected` MEDIUMTEXT NULL,
  `ocr_confidence` FLOAT NOT NULL DEFAULT 1.0,
  `ocr_boxes_json` JSON NULL,
  `embedding_vector` JSON NULL,
  `version` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_doc_page` (`document_id`, `page_number`),
  CONSTRAINT `fk_page_doc` FOREIGN KEY (`document_id`) REFERENCES `source_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Entitások (Személyek, Szervezetek, Helyek, Események, Fedőnevek)
CREATE TABLE IF NOT EXISTS `entities` (
  `id` CHAR(36) NOT NULL,
  `type` ENUM('person', 'organization', 'place', 'event', 'codename') NOT NULL,
  `canonical_name` VARCHAR(255) NOT NULL,
  `aliases` JSON NULL,
  `time_range_start` DATE NULL,
  `time_range_end` DATE NULL,
  `metadata` JSON NULL,
  `is_sample` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_entity_type` (`type`),
  INDEX `idx_canonical_name` (`canonical_name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Adatvédelmi Nyilvántartás (2026. évi LI. tv., Halál éve + 5 év szabály)
CREATE TABLE IF NOT EXISTS `privacy_records` (
  `id` CHAR(36) NOT NULL,
  `entity_id` CHAR(36) NOT NULL UNIQUE,
  `status` ENUM('alive', 'deceased', 'unknown') NOT NULL DEFAULT 'unknown',
  `death_year` INT NULL,
  `legal_release_year` INT NULL,
  `is_publicly_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `redaction_reason` TEXT NULL,
  `verified_by` CHAR(36) NULL,
  `verified_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_privacy_entity` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_privacy_user` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_privacy_public` (`is_publicly_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Forrásállítások (Claims) – Minden állítás irathoz, oldalhoz és idézethez kötött
CREATE TABLE IF NOT EXISTS `claims` (
  `id` CHAR(36) NOT NULL,
  `document_id` CHAR(36) NOT NULL,
  `page_id` CHAR(36) NOT NULL,
  `quote` TEXT NOT NULL,
  `bounding_box` JSON NULL,
  `statement` TEXT NOT NULL,
  `subject_entity_id` CHAR(36) NULL,
  `status` ENUM('javasolt', 'ellenorzott', 'vitatott') NOT NULL DEFAULT 'javasolt',
  `ai_generated` TINYINT(1) NOT NULL DEFAULT 0,
  `confidence_score` FLOAT NOT NULL DEFAULT 1.0,
  `created_by` CHAR(36) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_claim_doc` FOREIGN KEY (`document_id`) REFERENCES `source_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_claim_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_claim_entity` FOREIGN KEY (`subject_entity_id`) REFERENCES `entities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_claim_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_claim_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Kétlépcsős Ellenőrzés (Dual Verification)
CREATE TABLE IF NOT EXISTS `claim_verifications` (
  `id` CHAR(36) NOT NULL,
  `claim_id` CHAR(36) NOT NULL,
  `verifier_id` CHAR(36) NOT NULL,
  `decision` ENUM('approved', 'rejected', 'disputed') NOT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_claim_verifier` (`claim_id`, `verifier_id`),
  CONSTRAINT `fk_verif_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_verif_user` FOREIGN KEY (`verifier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Szerepkörök az Iratban (Szigorúan forrásalapú szerep-hozzárendelés)
CREATE TABLE IF NOT EXISTS `roles` (
  `id` CHAR(36) NOT NULL,
  `entity_id` CHAR(36) NOT NULL,
  `document_id` CHAR(36) NOT NULL,
  `role_type` ENUM(
    'beszervezett_halozati_szemely',
    'megfigyelt',
    'tiszt',
    'beszervezesi_kiserlet_alanya',
    'egyeb_emlitett',
    'ismeretlen'
  ) NOT NULL DEFAULT 'ismeretlen',
  `justification_claim_id` CHAR(36) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_role_entity` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_doc` FOREIGN KEY (`document_id`) REFERENCES `source_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_claim` FOREIGN KEY (`justification_claim_id`) REFERENCES `claims` (`id`) ON DELETE SET NULL,
  INDEX `idx_role_type` (`role_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Kapcsolatok (Relations) – Kizárólag igazolt Claim alapján létezhet!
CREATE TABLE IF NOT EXISTS `relations` (
  `id` CHAR(36) NOT NULL,
  `source_entity_id` CHAR(36) NOT NULL,
  `target_entity_id` CHAR(36) NOT NULL,
  `relation_type` ENUM(
    'megfigyeltje',
    'beszervezoje',
    'kapcsolattartoja',
    'munkatarsa',
    'csaladtagja',
    'emlitette',
    'talalkozott_vele'
  ) NOT NULL,
  `claim_id` CHAR(36) NOT NULL,
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rel_source` FOREIGN KEY (`source_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rel_target` FOREIGN KEY (`target_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rel_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE CASCADE,
  INDEX `idx_rel_type` (`relation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Térbeli Geometriák és Magánlakás Elmosás (Spatial)
CREATE TABLE IF NOT EXISTS `place_geometries` (
  `id` CHAR(36) NOT NULL,
  `entity_id` CHAR(36) NOT NULL,
  `latitude` DECIMAL(10, 7) NOT NULL,
  `longitude` DECIMAL(10, 7) NOT NULL,
  `precision_level` ENUM('exact_building', 'street_segment', 'district', 'settlement_only') NOT NULL DEFAULT 'exact_building',
  `is_private_residence` TINYINT(1) NOT NULL DEFAULT 0,
  `public_latitude` DECIMAL(10, 7) NOT NULL,
  `public_longitude` DECIMAL(10, 7) NOT NULL,
  `address_text` VARCHAR(255) NULL,
  `district_id` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_place_entity` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  INDEX `idx_public_coords` (`public_latitude`, `public_longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Lakossági Beküldések (Submissions)
CREATE TABLE IF NOT EXISTS `submissions` (
  `id` CHAR(36) NOT NULL,
  `submitter_name` VARCHAR(255) NOT NULL,
  `submitter_email` VARCHAR(191) NOT NULL,
  `gdpr_consent` TINYINT(1) NOT NULL DEFAULT 0,
  `content_type` ENUM('reminiscence', 'photo', 'document', 'location_fix') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `file_paths` JSON NULL,
  `moderation_status` ENUM('pending', 'accepted', 'rejected', 'requires_edit') NOT NULL DEFAULT 'pending',
  `moderator_notes` TEXT NULL,
  `moderated_by` CHAR(36) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sub_mod` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_sub_status` (`moderation_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Helyesbítési Kérelem és Kiegészítő Nyilatkozat (Corrections)
CREATE TABLE IF NOT EXISTS `correction_requests` (
  `id` CHAR(36) NOT NULL,
  `claim_id` CHAR(36) NULL,
  `entity_id` CHAR(36) NULL,
  `applicant_name` VARCHAR(255) NOT NULL,
  `applicant_email` VARCHAR(191) NOT NULL,
  `applicant_relationship` VARCHAR(255) NULL,
  `request_type` ENUM('kiegeszito_nyilatkozat', 'adatjavitas', 'torles_vagy_korlatozas') NOT NULL,
  `legal_ground` TEXT NOT NULL,
  `statement_text` TEXT NOT NULL,
  `status` ENUM('beerkett', 'vizsgalat_alatt', 'elfogadva_kozzetett', 'elutasitva') NOT NULL DEFAULT 'beerkett',
  `deadline` DATE NOT NULL,
  `resolution_text` TEXT NULL,
  `resolved_by` CHAR(36) NULL,
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_corr_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_corr_entity` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_corr_user` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_corr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Helytörténeti Interaktív Történetek (Stories & Steps)
CREATE TABLE IF NOT EXISTS `stories` (
  `id` CHAR(36) NOT NULL,
  `slug` VARCHAR(128) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT NOT NULL,
  `cover_image` VARCHAR(255) NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `author_name` VARCHAR(128) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `story_steps` (
  `id` CHAR(36) NOT NULL,
  `story_id` CHAR(36) NOT NULL,
  `step_number` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `focus_lat` DECIMAL(10, 7) NULL,
  `focus_lng` DECIMAL(10, 7) NULL,
  `focus_zoom` INT NULL,
  `document_id` CHAR(36) NULL,
  `page_id` CHAR(36) NULL,
  `claim_id` CHAR(36) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_step_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_step_doc` FOREIGN KEY (`document_id`) REFERENCES `source_documents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_step_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_step_claim` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Kutatói Munkafelület (Hipotézisek, Feladatok, Jegyzetek)
CREATE TABLE IF NOT EXISTS `hypotheses` (
  `id` CHAR(36) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('nyitott', 'igazolt', 'megcafolt') NOT NULL DEFAULT 'nyitott',
  `created_by` CHAR(36) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_hyp_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_tasks` (
  `id` CHAR(36) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('todo', 'in_progress', 'done') NOT NULL DEFAULT 'todo',
  `assigned_to` CHAR(36) NULL,
  `related_doc_id` CHAR(36) NULL,
  `deadline` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_task_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_task_doc` FOREIGN KEY (`related_doc_id`) REFERENCES `source_documents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `research_notes` (
  `id` CHAR(36) NOT NULL,
  `entity_id` CHAR(36) NULL,
  `document_id` CHAR(36) NULL,
  `title` VARCHAR(255) NOT NULL,
  `note_text` TEXT NOT NULL,
  `is_private` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` CHAR(36) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_note_entity` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_doc` FOREIGN KEY (`document_id`) REFERENCES `source_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Eseménynapló (Audit Log)
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NULL,
  `action` VARCHAR(64) NOT NULL,
  `table_name` VARCHAR(64) NOT NULL,
  `record_id` CHAR(36) NOT NULL,
  `changes` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_audit_table_record` (`table_name`, `record_id`),
  INDEX `idx_audit_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
