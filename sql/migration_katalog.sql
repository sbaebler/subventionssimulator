-- =============================================================
-- Migration Phase 1: Realer Katalog & strukturierte Metadaten
-- Auf der Prod-DB via phpMyAdmin ausführen (sql/ wird nicht deployed).
--
-- - Erweitert subventionen um strukturierte Metadatenfelder
-- - Neue Tabelle subvention_fristen (mehrere Termine pro Subvention)
-- - Neue Tabelle subvention_betrag_historie (Ist-Beträge pro Jahr)
-- =============================================================

ALTER TABLE subventionen
  ADD COLUMN berechtigte          TEXT NULL AFTER voraussetzungen,
  ADD COLUMN einschraenkungen     TEXT NULL AFTER berechtigte,
  ADD COLUMN verlangte_unterlagen TEXT NULL AFTER einschraenkungen,
  ADD COLUMN berechnungsgrundlage TEXT NULL AFTER verlangte_unterlagen;

CREATE TABLE subvention_fristen (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id INT UNSIGNED NOT NULL,
  bezeichnung   VARCHAR(150) NOT NULL,
  datum         DATE NULL,
  hinweis       VARCHAR(300),
  CONSTRAINT fk_fristen_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subvention_betrag_historie (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id INT UNSIGNED NOT NULL,
  jahr          SMALLINT UNSIGNED NOT NULL,
  betrag        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  bemerkung     VARCHAR(300),
  UNIQUE KEY uq_subvention_jahr (subvention_id, jahr),
  CONSTRAINT fk_betraghist_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
