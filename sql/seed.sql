-- =============================================================
-- Seed-Daten: Beispiel-Subventionstöpfe
-- =============================================================

-- -------------------------------------------------------------
-- Subvention 1: J+S Lager-Beitrag
-- -------------------------------------------------------------
INSERT INTO subventionen (bezeichnung, foerderstelle, kategorie, voraussetzungen, gueltig_von, link_extern)
VALUES (
  'J+S Lagerbeitrag Segeln',
  'Bundesamt für Sport BASPO / J+S',
  'lager',
  'Anerkannter J+S Trainer erforderlich. Mindestdauer 3 Tage. Min. 10 Teilnehmende.',
  '2025-01-01',
  'https://www.jugendundsport.ch'
);
-- ID = 1

-- Betragsregel: Grundbetrag + pro TN + pro Tag
INSERT INTO subvention_betraege (subvention_id, bezeichnung, grundbetrag, betrag_pro_teilnehmer, betrag_pro_tag, max_teilnehmer, max_tage, betrag_max_gesamt)
VALUES (1, 'J+S Lagerpauschale', 0.00, 12.00, 8.00, 40, 14, 3000.00);

-- Nur J+S Trainer berechtigt
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag, bemerkung)
VALUES (1, 'js_trainer', 150.00, 'Trainerbeitrag wird zusätzlich ausbezahlt');

-- Nur Lager-Events
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator, bemerkung)
VALUES (1, 'lager', 1.000, 'Mindestdauer 3 Tage');


-- -------------------------------------------------------------
-- Subvention 2: Kantonale Nachwuchsförderung ZH
-- -------------------------------------------------------------
INSERT INTO subventionen (bezeichnung, foerderstelle, kategorie, voraussetzungen, gueltig_von, gueltig_bis, link_extern)
VALUES (
  'NWF Kanton Zürich – Trainingsförderung',
  'Sportamt Kanton Zürich',
  'jugend',
  'NWF Trainer oder J+S Trainer. Teilnehmende unter 20 Jahren. Antrag bis 31.10. des Jahres.',
  '2025-01-01',
  '2025-12-31',
  'https://www.sport.zh.ch'
);
-- ID = 2

-- Betragsregel: Grundbetrag + pro TN
INSERT INTO subvention_betraege (subvention_id, bezeichnung, grundbetrag, betrag_pro_teilnehmer, betrag_pro_tag, max_teilnehmer, betrag_max_gesamt)
VALUES (2, 'NWF Trainingspauschale', 50.00, 8.00, 0.00, 25, 1500.00);

-- NWF und J+S Trainer berechtigt
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag)
VALUES (2, 'nwf_trainer', 0.00);
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag)
VALUES (2, 'js_trainer',  50.00);

-- Lager und Training
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator)
VALUES (2, 'lager',    1.200);  -- 20% Bonus für Lager
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator)
VALUES (2, 'training', 1.000);


-- -------------------------------------------------------------
-- Subvention 3: Vereinseigener Fördertopf Zurich Sailing
-- -------------------------------------------------------------
INSERT INTO subventionen (bezeichnung, foerderstelle, kategorie, voraussetzungen, gueltig_von)
VALUES (
  'Vereinsinterner Trainingsbeitrag',
  'Zurich Sailing Club',
  'ausbildung',
  'Mitglieder des Vereins. Kein spezifischer Trainer erforderlich.',
  '2025-01-01'
);
-- ID = 3

-- Nur Grundbetrag + pro Tag
INSERT INTO subvention_betraege (subvention_id, bezeichnung, grundbetrag, betrag_pro_teilnehmer, betrag_pro_tag, betrag_max_gesamt)
VALUES (3, 'Vereinspauschale', 100.00, 0.00, 20.00, 0, 800.00);

-- Alle Trainerarten akzeptiert
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag)
VALUES (3, 'js_trainer',  0.00);
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag)
VALUES (3, 'nwf_trainer', 0.00);
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag)
VALUES (3, 'ohne',        0.00);

-- Lager und Training
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator)
VALUES (3, 'lager',    1.000);
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator)
VALUES (3, 'training', 1.000);
