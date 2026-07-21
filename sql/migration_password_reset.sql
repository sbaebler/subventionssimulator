-- =============================================================
-- Migration: Passwort vergessen (Self-Service Reset)
-- MariaDB 10.6 | utf8mb4
-- Ausführen via phpMyAdmin oder CLI:
--   mysql -u user -p datenbankname < migration_password_reset.sql
--
-- Fügt eine E-Mail-Adresse pro Benutzer hinzu (Voraussetzung für den
-- Reset-Versand) sowie eine Tabelle für zeitlich begrenzte, einmal
-- verwendbare Reset-Tokens. Gespeichert wird nur der SHA-256-Hash des
-- Tokens, nie der Klartext (gleiches Prinzip wie passwort_hash).
-- =============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- -------------------------------------------------------------
-- 1. E-Mail-Adresse auf benutzer
--    Nullable: bestehende Benutzer haben nach der Migration noch
--    keine E-Mail hinterlegt; ein Admin trägt sie über die
--    Benutzerverwaltung nach.
-- -------------------------------------------------------------
ALTER TABLE benutzer
  ADD COLUMN email VARCHAR(255) NULL AFTER anzeigename,
  ADD UNIQUE KEY uq_benutzer_email (email);

-- -------------------------------------------------------------
-- 2. Passwort-Reset-Tokens
-- -------------------------------------------------------------
CREATE TABLE passwort_reset_tokens (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  benutzer_id   INT UNSIGNED NOT NULL,
  token_hash    CHAR(64) NOT NULL,               -- SHA-256-Hex des Tokens
  laeuft_ab_am  TIMESTAMP NOT NULL,               -- Ablaufzeitpunkt
  eingeloest_am TIMESTAMP NULL DEFAULT NULL,      -- gesetzt = bereits verwendet
  erstellt_am   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_prt_token_hash (token_hash),
  CONSTRAINT fk_prt_benutzer
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
