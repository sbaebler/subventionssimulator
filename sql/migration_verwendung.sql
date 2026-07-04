-- =============================================================
-- Migration Phase 4: Verwendung / Verteilung erhaltener Beiträge
-- Auf der Prod-DB via phpMyAdmin ausführen (sql/ wird nicht deployed).
--
-- Setzt cm_events und cm_klassen (ClassManagerTool) in derselben DB voraus.
-- =============================================================

CREATE TABLE subvention_verwendung (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id  INT UNSIGNED NOT NULL,
  jahr           SMALLINT UNSIGNED NOT NULL,
  ziel_typ       ENUM('event','klasse','reserve','frei') NOT NULL DEFAULT 'frei',
  ziel_event_id  INT UNSIGNED NULL,
  ziel_klasse_id INT UNSIGNED NULL,
  ziel_text      VARCHAR(200),
  betrag         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  bemerkung      VARCHAR(300),
  erstellt_von   INT UNSIGNED NULL,
  erstellt_am    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_verw_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_verw_event
    FOREIGN KEY (ziel_event_id) REFERENCES cm_events(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_verw_klasse
    FOREIGN KEY (ziel_klasse_id) REFERENCES cm_klassen(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_verw_erstellt_von
    FOREIGN KEY (erstellt_von) REFERENCES benutzer(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
