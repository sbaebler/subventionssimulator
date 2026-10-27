-- =============================================================
-- Migration: Standard-Zuteilung von Förderprogrammen
-- Auf der Prod-DB via phpMyAdmin ausführen (sql/ wird nicht deployed).
--
-- Ein Förderprogramm kann «für alle Events» gelten (gilt_standard = 1),
-- einzelne Klassen davon ausnehmen (subvention_klassen_ausschluss) und
-- pro Event angepasst werden (subvention_events = zusätzlich,
-- subvention_event_ausschluss = ausgenommen). Neue Events erben so
-- automatisch den Standard ihrer Klasse.
-- Bestehende Zuordnungen in subvention_events bleiben unverändert gültig.
-- =============================================================

ALTER TABLE subventionen
  ADD COLUMN gilt_standard TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER aktiv;

CREATE TABLE subvention_klassen_ausschluss (
  subvention_id INT UNSIGNED NOT NULL,
  klasse_id     INT UNSIGNED NOT NULL,         -- referenziert cm_klassen(id)
  PRIMARY KEY (subvention_id, klasse_id),
  CONSTRAINT fk_ska_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ska_klasse
    FOREIGN KEY (klasse_id) REFERENCES cm_klassen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subvention_event_ausschluss (
  subvention_id INT UNSIGNED NOT NULL,
  event_id      INT UNSIGNED NOT NULL,         -- referenziert cm_events(id)
  PRIMARY KEY (subvention_id, event_id),
  CONSTRAINT fk_sea_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sea_event
    FOREIGN KEY (event_id) REFERENCES cm_events(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
