-- =============================================================
-- Migration: Multi-User-Unterstützung + Audit-Logging
-- MariaDB 10.6 | utf8mb4
-- Ausführen via phpMyAdmin oder CLI:
--   mysql -u user -p datenbankname < migration_benutzer.sql
-- =============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- -------------------------------------------------------------
-- 1. Benutzertabelle anlegen
-- -------------------------------------------------------------
CREATE TABLE benutzer (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  benutzername  VARCHAR(50)  NOT NULL UNIQUE,
  anzeigename   VARCHAR(100) NOT NULL,
  passwort_hash VARCHAR(255) NOT NULL,
  aktiv         TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  erstellt_am   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. Audit-Spalten auf subventionen
-- -------------------------------------------------------------
ALTER TABLE subventionen
  ADD COLUMN erstellt_von  INT UNSIGNED NULL AFTER aktiv,
  ADD COLUMN geaendert_von INT UNSIGNED NULL AFTER erstellt_von,
  ADD CONSTRAINT fk_sv_erstellt_von
    FOREIGN KEY (erstellt_von)  REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_sv_geaendert_von
    FOREIGN KEY (geaendert_von) REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- -------------------------------------------------------------
-- 3. Audit-Spalte auf simulationen
-- -------------------------------------------------------------
ALTER TABLE simulationen
  ADD COLUMN benutzer_id INT UNSIGNED NULL AFTER session_id,
  ADD CONSTRAINT fk_sim_benutzer
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE;

SET foreign_key_checks = 1;

-- -------------------------------------------------------------
-- 4. Ersten Benutzer anlegen (Passwort-Hash anpassen!)
--
-- Hash erzeugen:
--   php -r "echo password_hash('deinPasswort', PASSWORD_DEFAULT);"
--
-- Dann diese INSERT-Zeile anpassen und ausführen:
-- -------------------------------------------------------------
-- INSERT INTO benutzer (benutzername, anzeigename, passwort_hash)
-- VALUES ('admin', 'Administrator', '$2y$12$HASH_HIER_ERSETZEN');
