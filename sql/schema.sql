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
  voraussetzungen TEXT,
  antragsfrist    DATE,                          -- NULL = laufend
  gueltig_von     DATE,
  gueltig_bis     DATE,
  link_extern     VARCHAR(500),
  aktiv           TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  erstellt_von    INT UNSIGNED NULL,
  geaendert_von   INT UNSIGNED NULL,
  erstellt_am     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  geaendert_am    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sv_erstellt_von
    FOREIGN KEY (erstellt_von)  REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_sv_geaendert_von
    FOREIGN KEY (geaendert_von) REFERENCES benutzer(id) ON DELETE SET NULL ON UPDATE CASCADE
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


SET foreign_key_checks = 1;
