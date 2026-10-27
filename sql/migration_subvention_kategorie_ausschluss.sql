-- =============================================================
-- Migration: Eventkategorien vom Standard ausnehmen
-- Auf der Prod-DB via phpMyAdmin ausführen (sql/ wird nicht deployed).
--
-- Ergänzt die Standard-Zuteilung (migration_subvention_standard.sql):
-- Ein Standard-Förderprogramm kann zusätzlich für Eventkategorien
-- (cm_events.kategorie: breitensport, rlz, privat) ausgenommen werden.
-- =============================================================

CREATE TABLE subvention_kategorie_ausschluss (
  subvention_id INT UNSIGNED NOT NULL,
  kategorie     ENUM('breitensport','rlz','privat') NOT NULL,
  PRIMARY KEY (subvention_id, kategorie),
  CONSTRAINT fk_skat_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
