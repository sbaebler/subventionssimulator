-- Swiss-Sailing-Rapport: Zusatzangaben zu Athletinnen und Athleten, die das
-- Class Manager Tool nicht führt. Vor dem Push in phpMyAdmin einspielen.

CREATE TABLE swisssailing_athleten (
  schluessel     CHAR(64) NOT NULL PRIMARY KEY,   -- sha256 aus E-Mail, Vorname, Nachname
  geschlecht     ENUM('M','F') NULL,
  lizenz_nr      VARCHAR(30) NULL,                -- Swiss-Sailing-Lizenznummer
  js_nr          VARCHAR(30) NULL,                -- J+S-Personennummer
  segelklasse    VARCHAR(50) NULL,                -- überschreibt die Klasse des Events
  hat_sotc       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,  -- Swiss Olympic Talent Card
  geaendert_von  INT UNSIGNED NULL,
  geaendert_am   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_swsa_benutzer
    FOREIGN KEY (geaendert_von) REFERENCES benutzer(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
