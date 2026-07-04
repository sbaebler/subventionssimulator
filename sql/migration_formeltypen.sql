-- =============================================================
-- Migration Phase 2: Berechnungsmodell mit Formeltypen
-- Auf der Prod-DB via phpMyAdmin ausführen (sql/ wird nicht deployed).
--
-- - subventionen.berechnungstyp (Default 'additiv' → Bestand unverändert)
-- - typspezifische Betragsfelder auf subvention_betraege (Default 0)
-- - Simulationsparameter uebernachtung/stunden/lektionen auf simulationen
-- =============================================================

ALTER TABLE subventionen
  ADD COLUMN berechnungstyp ENUM(
    'additiv',
    'js_teilnehmertag',
    'js_teilnehmerstunde',
    'zks_ausbildungseinheit',
    'pauschale',
    'jahresbeitrag'
  ) NOT NULL DEFAULT 'additiv' AFTER kategorie;

ALTER TABLE subvention_betraege
  ADD COLUMN satz_mit_uebernachtung  DECIMAL(8,2)  NOT NULL DEFAULT 0.00   AFTER betrag_max_gesamt,
  ADD COLUMN satz_ohne_uebernachtung DECIMAL(8,2)  NOT NULL DEFAULT 0.00   AFTER satz_mit_uebernachtung,
  ADD COLUMN betrag_pro_stunde       DECIMAL(8,2)  NOT NULL DEFAULT 0.00   AFTER satz_ohne_uebernachtung,
  ADD COLUMN max_stunden_pro_tag     SMALLINT UNSIGNED NOT NULL DEFAULT 0  AFTER betrag_pro_stunde,
  ADD COLUMN betrag_pro_einheit      DECIMAL(10,4) NOT NULL DEFAULT 0.0000 AFTER max_stunden_pro_tag,
  ADD COLUMN max_lektionen_pro_tag   SMALLINT UNSIGNED NOT NULL DEFAULT 0  AFTER betrag_pro_einheit;

ALTER TABLE simulationen
  ADD COLUMN uebernachtung     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER eventart,
  ADD COLUMN stunden_pro_tag   SMALLINT UNSIGNED NOT NULL DEFAULT 0   AFTER uebernachtung,
  ADD COLUMN lektionen_pro_tag SMALLINT UNSIGNED NOT NULL DEFAULT 0   AFTER stunden_pro_tag;
