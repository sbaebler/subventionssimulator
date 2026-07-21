-- =============================================================
-- Subventionssimulator zurich-sailing.ch
-- MariaDB 10.6 | utf8mb4
-- =============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- -------------------------------------------------------------
-- 0. Benutzer
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
-- 1. Stammdaten Subvention
-- -------------------------------------------------------------
CREATE TABLE subventionen (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bezeichnung     VARCHAR(200) NOT NULL,
  beschreibung    TEXT,
  foerderstelle   VARCHAR(150) NOT NULL,         -- z.B. "J+S", "Kanton ZH"
  kategorie       ENUM(
                    'ausbildung',
                    'lager',
                    'wettkampf',
                    'infrastruktur',
                    'jugend',
                    'sonstiges'
                  ) NOT NULL DEFAULT 'sonstiges',
  berechnungstyp  ENUM(
                    'additiv',                   -- Grundbetrag + pro TN + pro Tag (Standard)
                    'js_teilnehmertag',          -- Satz × TN × Tage (J+S Lager)
                    'js_teilnehmerstunde',       -- Satz × TN × Tage × Stunden (J+S Training)
                    'zks_ausbildungseinheit',    -- Satz × TN × Lektionen × Tage (ZKS Ausbildung)
                    'pauschale',                 -- fixer Jahresbetrag
                    'jahresbeitrag'              -- Kennzahl-basiert (verbandsweit, Phase 3)
                  ) NOT NULL DEFAULT 'additiv',
  voraussetzungen TEXT,
  berechtigte          TEXT,                     -- Wer ist antragsberechtigt / Teilnahmeberechtigt
  einschraenkungen     TEXT,                     -- Einschränkungen / Vorgaben der Förderstelle
  verlangte_unterlagen TEXT,                     -- Verlangte Daten & Unterlagen
  berechnungsgrundlage TEXT,                     -- Beschreibung der Berechnungsgrundlage
  antragsfrist    DATE,                          -- NULL = laufend
  gueltig_von     DATE,
  gueltig_bis     DATE,
  link_extern     VARCHAR(500),
  aktiv           TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  erstellt_von    INT UNSIGNED NULL,
  geaendert_von   INT UNSIGNED NULL,
  erstellt_am     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  geaendert_am    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Papierkorb: gesetzt = in den Papierkorb verschoben, NULL = aktiv/normal sichtbar.
  -- Erst nach Verweilen im Papierkorb wird ein Eintrag endgültig (hart) gelöscht.
  geloescht_am    TIMESTAMP NULL DEFAULT NULL,
  geloescht_von   INT UNSIGNED NULL,
  CONSTRAINT fk_sv_erstellt_von
    FOREIGN KEY (erstellt_von)  REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_sv_geaendert_von
    FOREIGN KEY (geaendert_von) REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_sv_geloescht_von
    FOREIGN KEY (geloescht_von) REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 2. Betragsmodell (ein Topf kann mehrere Betragsregeln haben)
--
-- Formel pro Regel:
--   total = grundbetrag
--         + (betrag_pro_teilnehmer * anzahl_teilnehmer)
--         + (betrag_pro_tag        * anzahl_tage)
--
-- Dann: MIN(total, betrag_max_gesamt) falls betrag_max_gesamt > 0
-- -------------------------------------------------------------
CREATE TABLE subvention_betraege (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id         INT UNSIGNED NOT NULL,

  -- Bezeichnung der Regel (z.B. "Grundbeitrag", "Tagessatz Lager")
  bezeichnung           VARCHAR(100) NOT NULL DEFAULT 'Standardbetrag',

  -- Grundbetrag (fix, unabhängig von TN / Tagen)
  grundbetrag           DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  -- Variable Bestandteile
  betrag_pro_teilnehmer DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  betrag_pro_tag        DECIMAL(8,2)  NOT NULL DEFAULT 0.00,

  -- Obergrenzen (0 = kein Limit)
  max_teilnehmer        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_tage              SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  betrag_max_gesamt     DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  -- Typspezifische Felder (0 = nicht genutzt / je nach berechnungstyp)
  satz_mit_uebernachtung   DECIMAL(8,2) NOT NULL DEFAULT 0.00,  -- js_teilnehmertag: Satz mit Übernachtung
  satz_ohne_uebernachtung  DECIMAL(8,2) NOT NULL DEFAULT 0.00,  -- js_teilnehmertag: Satz ohne Übernachtung
  betrag_pro_stunde        DECIMAL(8,2) NOT NULL DEFAULT 0.00,  -- js_teilnehmerstunde
  max_stunden_pro_tag      SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- js_teilnehmerstunde (0 = kein Limit)
  betrag_pro_einheit       DECIMAL(10,4) NOT NULL DEFAULT 0.0000, -- zks_ausbildungseinheit
  max_lektionen_pro_tag    SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- zks_ausbildungseinheit (0 = kein Limit)

  CONSTRAINT fk_betraege_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 3. Erlaubte Trainerarten pro Subvention
--
-- Eine Subvention kann für mehrere Trainerarten gelten,
-- mit optional unterschiedlichem Zusatzbetrag.
-- -------------------------------------------------------------
CREATE TABLE subvention_trainerarten (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id   INT UNSIGNED NOT NULL,

  trainerart      ENUM(
                    'js_trainer',    -- J+S Trainer (mit J+S-Anerkennung)
                    'nwf_trainer',   -- NWF Trainer (Nachwuchsförderung)
                    'ohne'           -- Trainer ohne spezifische Anerkennung
                  ) NOT NULL,

  -- Optionaler Zusatzbetrag für diese Trainerart (kann 0 sein)
  zusatzbetrag    DECIMAL(8,2) NOT NULL DEFAULT 0.00,

  -- Bemerkung (z.B. "Nur mit gültigem J+S-Ausweis")
  bemerkung       VARCHAR(300),

  UNIQUE KEY uq_subvention_trainerart (subvention_id, trainerart),
  CONSTRAINT fk_trainerarten_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 4. Erlaubte Eventarten pro Subvention
--
-- Eine Subvention gilt für bestimmte Eventarten,
-- mit optionalem Multiplikator auf den Gesamtbetrag.
-- -------------------------------------------------------------
CREATE TABLE subvention_eventarten (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id   INT UNSIGNED NOT NULL,

  eventart        ENUM(
                    'lager',     -- Mehrtägiges Lager
                    'training'   -- Einzeltraining / Trainingsblock
                  ) NOT NULL,

  -- Multiplikator auf den berechneten Betrag (1.00 = keine Änderung)
  multiplikator   DECIMAL(5,3) NOT NULL DEFAULT 1.000,

  -- Bemerkung (z.B. "Nur bei mind. 3 Tagen Lagerdauer")
  bemerkung       VARCHAR(300),

  UNIQUE KEY uq_subvention_eventart (subvention_id, eventart),
  CONSTRAINT fk_eventarten_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 5. Simulationsprotokoll (Phase 2)
--
-- Jede Simulation wird gespeichert, damit man sieht,
-- welche Berechnung zu welchem Ergebnis geführt hat.
-- -------------------------------------------------------------
CREATE TABLE simulationen (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id          VARCHAR(64),               -- Browser-Session (kein Login nötig)
  benutzer_id         INT UNSIGNED NULL,
  subvention_id       INT UNSIGNED NOT NULL,

  -- Eingabeparameter der Simulation
  anzahl_teilnehmer   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  anzahl_tage         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  trainerart          ENUM('js_trainer','nwf_trainer','ohne') NOT NULL,
  eventart            ENUM('lager','training')  NOT NULL,
  uebernachtung       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  stunden_pro_tag     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  lektionen_pro_tag   SMALLINT UNSIGNED NOT NULL DEFAULT 0,

  -- Berechnetes Ergebnis
  grundbetrag_calc    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tn_betrag_calc      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tage_betrag_calc    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  trainer_zusatz_calc DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  multiplikator_calc  DECIMAL(5,3)  NOT NULL DEFAULT 1.000,
  betrag_total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  erstellt_am         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_sim_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sim_benutzer
    FOREIGN KEY (benutzer_id) REFERENCES benutzer(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 6. Zuordnung Event <-> Subvention
--
-- Legt fest, welche Subventionen für welches Event (Anlass aus dem
-- ClassManagerTool, Tabelle cm_events) zum Tragen kommen.
-- Beide Projekte teilen sich dieselbe Datenbank, daher kann der
-- Fremdschlüssel direkt auf cm_events(id) zeigen. Wird ein Event im
-- ClassManagerTool gelöscht, verschwinden die Zuordnungen automatisch.
-- -------------------------------------------------------------
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


-- -------------------------------------------------------------
-- 7. Fristen / Termine pro Subvention
--
-- Die realen Subventionen haben oft mehrere Termine (z.B. Anmeldung
-- 30.4., Report 31.10.). Ergänzt das einzelne subventionen.antragsfrist.
-- -------------------------------------------------------------
CREATE TABLE subvention_fristen (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id INT UNSIGNED NOT NULL,
  bezeichnung   VARCHAR(150) NOT NULL,           -- z.B. "Anmeldung", "Rapport"
  datum         DATE NULL,                        -- konkretes Datum (NULL wenn nur Hinweis)
  hinweis       VARCHAR(300),                     -- Freitext, z.B. "jährlich nach Sommerferien"
  CONSTRAINT fk_fristen_subvention
    FOREIGN KEY (subvention_id) REFERENCES subventionen(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -------------------------------------------------------------
-- 8. Betragshistorie pro Subvention
--
-- Erfasste Ist-Beträge pro Jahr (z.B. ZKS Ausbildung 2024: 12'949 Fr).
-- Dient als Referenz und als Grundlage für Pauschal-Beiträge (Phase 2/3).
-- -------------------------------------------------------------
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


-- -------------------------------------------------------------
-- 9. Verwendung / Verteilung erhaltener Beiträge
--
-- Hält fest, wohin ein (erhaltener) Subventionsbetrag pro Jahr verteilt
-- wird: auf ein Event (cm_events), eine Kaderklasse (cm_klassen), eine
-- Reserve oder ein frei benanntes Ziel. ziel_event_id/ziel_klasse_id sind
-- nur je nach ziel_typ gesetzt – die Konsistenz wird in PHP erzwungen
-- (cyon lehnt spaltenübergreifende CHECK-Constraints ab).
-- -------------------------------------------------------------
CREATE TABLE subvention_verwendung (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subvention_id  INT UNSIGNED NOT NULL,
  jahr           SMALLINT UNSIGNED NOT NULL,
  ziel_typ       ENUM('event','klasse','reserve','frei') NOT NULL DEFAULT 'frei',
  ziel_event_id  INT UNSIGNED NULL,             -- referenziert cm_events(id)
  ziel_klasse_id INT UNSIGNED NULL,             -- referenziert cm_klassen(id)
  ziel_text      VARCHAR(200),                  -- freies Ziel / Bezeichnung
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


SET foreign_key_checks = 1;
