-- =============================================================
-- Migration: Zuordnung Event <-> Subvention
-- Auf der Prod-DB via phpMyAdmin ausführen (sql/ wird nicht deployed).
--
-- Legt fest, welche Subventionen für welches Event (cm_events aus dem
-- ClassManagerTool) zum Tragen kommen. Setzt voraus, dass cm_events in
-- derselben Datenbank existiert (ClassManagerTool-Schema).
-- =============================================================

CREATE TABLE subvention_events (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id      INT UNSIGNED NOT NULL,         -- referenziert cm_events(id)
  subvention_id INT UNSIGNED NOT NULL,
  bemerkung     VARCHAR(300),
  erstellt_von  INT UNSIGNED NULL,
  erstellt_am   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_event_subvention (event_id, subvention_id),
  CONSTRAINT fk_se_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_se_event
    FOREIGN KEY (event_id) REFERENCES cm_events(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_se_erstellt_von
    FOREIGN KEY (erstellt_von) REFERENCES benutzer(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
