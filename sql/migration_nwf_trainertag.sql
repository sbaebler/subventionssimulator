-- =============================================================
-- Migration: Berechnungstyp «nwf_trainertag» (NWF: Satz × Trainer × Tage)
-- Auf der Prod-DB via phpMyAdmin VOR dem Push ausführen (sql/ wird nicht
-- deployed). Gleiche DB wie das Class Manager Tool.
-- Bestand bleibt unverändert (neuer ENUM-Wert, Spalte Default 0).
-- =============================================================

ALTER TABLE subventionen
  MODIFY COLUMN berechnungstyp ENUM(
    'additiv',
    'js_teilnehmertag',
    'js_teilnehmerstunde',
    'zks_ausbildungseinheit',
    'nwf_trainertag',
    'pauschale',
    'jahresbeitrag'
  ) NOT NULL DEFAULT 'additiv';

ALTER TABLE subvention_betraege
  ADD COLUMN betrag_pro_trainertag DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER max_lektionen_pro_tag;
