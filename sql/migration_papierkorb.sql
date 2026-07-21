-- =============================================================
-- Migration: Papierkorb für Förderprogramme
-- Auf der Prod-DB via phpMyAdmin ausführen (sql/ wird nicht deployed).
--
-- Fügt einen nachvollziehbaren Löschprozess für subventionen ein:
-- "Löschen" verschiebt einen Eintrag zunächst nur in den Papierkorb
-- (geloescht_am/geloescht_von gesetzt, Eintrag bleibt in der DB).
-- Erst aus dem Papierkorb heraus kann er endgültig (hart) gelöscht werden.
-- =============================================================

ALTER TABLE subventionen
  ADD COLUMN geloescht_am  TIMESTAMP NULL DEFAULT NULL AFTER geaendert_am,
  ADD COLUMN geloescht_von INT UNSIGNED NULL           AFTER geloescht_am;

ALTER TABLE subventionen
  ADD CONSTRAINT fk_sv_geloescht_von
    FOREIGN KEY (geloescht_von) REFERENCES benutzer(id)
    ON DELETE SET NULL ON UPDATE CASCADE;
