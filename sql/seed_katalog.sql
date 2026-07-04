-- =============================================================
-- Seed-Daten: Realer Subventionskatalog ZSF-Nachwuchs
-- Quelle: ZSF-Dokumente (Subventionen_ZSF_Tab, _Verwendung, _Liste_Zahler)
--
-- Nur lokal / via phpMyAdmin einspielen (sql/ wird nicht deployed).
-- Setzt Schema Phase 1 + 2 voraus (berechnungstyp, typspezifische Felder,
-- subvention_fristen, subvention_betrag_historie).
--
-- Betragssätze sind Richtwerte aus den Dokumenten und vor produktiver
-- Nutzung mit den aktuellen Merkblättern der Förderstellen abzugleichen.
-- =============================================================

-- -------------------------------------------------------------
-- 1. ZKS Grundbeitrag (Pauschale, Pool-Anteil)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen, berechtigte,
   einschraenkungen, berechnungsgrundlage, gueltig_von, link_extern)
VALUES
  ('ZKS Grundbeitrag', 'Zürcher Kantonalverband für Sport (ZKS)', 'sonstiges', 'pauschale',
   'Mitglied ZKS. Verband/Vereine mit Sitz im Kanton Zürich. Gemeinnützig. Ethik-Status Swiss Olympic.',
   'ZKS-Mitgliederverbände (ZSF)',
   'Zweckgebunden für angemeldete Aktivität. Malus-System bei fehlender Teilnahme an ZKS-Anlässen.',
   'Sockelbeitrag 10 %, Anzahl Mitglieder 50 %, Anzahl Vereine 20 %, Aktivitäten 20 %. Auszahlung im Folgejahr.',
   '2026-01-01', 'https://www.zks.ch');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege (subvention_id, bezeichnung, grundbetrag) VALUES (@sid, 'Grundbeitrag (Pauschale)', 7776.00);
INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis) VALUES
  (@sid, 'Eingabe an Coach', '2026-02-28', 'via ZKS-Extranet für das Vorjahr'),
  (@sid, 'Eingabe an ZKS', '2026-04-30', NULL);
INSERT INTO subvention_betrag_historie (subvention_id, jahr, betrag, bemerkung) VALUES (@sid, 2024, 7776.00, 'Ist');

-- -------------------------------------------------------------
-- 2. ZKS Ausbildungsbeitrag (Ausbildungseinheit)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen, berechtigte,
   einschraenkungen, verlangte_unterlagen, berechnungsgrundlage, gueltig_von, link_extern)
VALUES
  ('ZKS Ausbildungsbeitrag', 'Zürcher Kantonalverband für Sport (ZKS)', 'ausbildung', 'zks_ausbildungseinheit',
   'Zweckgebunden für Zürcher Sportlerinnen und Sportler, Mitglieder eines Vereins im Kanton Zürich. Ausbildungsinhalt/-ziel vorher definiert.',
   'Teilnehmende von Vereinen mit Sitz im Kanton Zürich. Ausschreibung für den ganzen Kanton unter Patronat des ZSV.',
   'Keine Meisterschaften/Wettkämpfe. Regatten müssen als Ausbildung deklariert werden. Max. 6 Lektionen pro Tag, 1 Lektion = 60 Minuten.',
   'ZKS-Musterteilnehmerliste (aus J+S-Anmeldung), Kursprogramm, Anwesenheitsliste, J+S-Leiterausweise, Ausbildungskosten.',
   'Ausbildungseinheiten = Teilnehmende × ZKS-Lektionen. Beitrag ca. CHF 2.80 pro Ausbildungseinheit.',
   '2026-01-01', 'https://www.zks.ch');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege
  (subvention_id, bezeichnung, betrag_pro_einheit, max_lektionen_pro_tag)
VALUES (@sid, 'Ausbildungsbeitrag', 2.8000, 6);
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag, bemerkung)
VALUES (@sid, 'js_trainer', 0.00, 'J+S-Leiter erforderlich');
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator, bemerkung) VALUES
  (@sid, 'lager', 1.000, 'Lager als Ausbildung deklariert'),
  (@sid, 'training', 1.000, 'Kadertrainings, Coach2Coach');
INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis) VALUES
  (@sid, 'Liste an Leiter Subvention', '2026-02-28', 'Musterteilnehmerliste'),
  (@sid, 'Einreichen Folgejahr', '2026-03-31', NULL);
INSERT INTO subvention_betrag_historie (subvention_id, jahr, betrag, bemerkung) VALUES
  (@sid, 2024, 12949.00, 'Ist'),
  (@sid, 2025, 30000.00, 'ca.');

-- -------------------------------------------------------------
-- 3. Sportamt Kanton Zürich – Kantonaler Förderbeitrag NWLS (Pauschale)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen, berechtigte,
   einschraenkungen, verlangte_unterlagen, berechnungsgrundlage, gueltig_von, link_extern)
VALUES
  ('Sportamt ZH – Kantonaler Förderbeitrag', 'Sportamt Kanton Zürich', 'jugend', 'pauschale',
   'Swiss Olympic anerkanntes RLZ gemäss Nachwuchsförderkonzept (NWF). Haupttrainingsort im Kanton Zürich.',
   'Im nationalen Nachwuchsförderkonzept erfasste Trägerschaft (Leistungszentrum/Trainingsstützpunkt).',
   'Für Anstellung & Weiterbildung von Trainern (Coach2Coach). Logo Sportamt auf Dokumenten.',
   'Label-Bestätigung Swiss Sailing, Budget NWF, Trainer-Anstellungsverträge, PISTE-Ranglisten.',
   'Anteil Nachwuchstrainer (Berufstrainer-Stellenprozente) 60 %, Anteil Sporttalente SOTC 40 %.',
   '2026-01-01', 'https://www.zh.ch/de/sport-kultur/sport.html');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege (subvention_id, bezeichnung, grundbetrag) VALUES (@sid, 'Förderbeitrag (Pauschale)', 7700.00);
INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis)
VALUES (@sid, 'Einreichen', '2026-11-30', 'für das Folgejahr, Auszahlung anfangs Jahr');
INSERT INTO subvention_betrag_historie (subvention_id, jahr, betrag, bemerkung) VALUES
  (@sid, 2025, 7000.00, 'Ist'),
  (@sid, 2026, 7700.00, 'zugesagt');

-- -------------------------------------------------------------
-- 4. NWF Swiss Olympic – Sockelbeitrag (Pauschale, Pool-Anteil)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen,
   berechnungsgrundlage, gueltig_von)
VALUES
  ('NWF Swiss Olympic – Sockelbeitrag', 'Swiss Olympic / Swiss Sailing', 'jugend', 'pauschale',
   'Einstufung des ZSV als regionales Leistungszentrum / Trägerschaft durch Swiss Sailing.',
   'Kriterium: Anzahl aller aktiven Kinder/Jugendlichen 0–20 Jahre (J+S-Zahlen). Pool 3 Mio. Zweck: strukturierte Nachwuchsförderung.',
   '2026-01-01');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege (subvention_id, bezeichnung, grundbetrag) VALUES (@sid, 'Sockelbeitrag (Pauschale)', 0.00);
INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis)
VALUES (@sid, 'Einreichung Swiss Sailing', NULL, 'koordiniert im Herbst');

-- -------------------------------------------------------------
-- 5. NWF Swiss Olympic – Variabler Anteil (Jahresbeitrag, pro Trainingstag)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen,
   berechnungsgrundlage, gueltig_von)
VALUES
  ('NWF Swiss Olympic – Variabler Anteil', 'Swiss Olympic / Swiss Sailing', 'jugend', 'jahresbeitrag',
   'Anzahl Berufstrainer (BTL mit Prüfung oder Berufsprüfung Trainer Leistungssport). Min. Anstellungsprozent regional 10 %.',
   'CHF 202 pro Trainingstag (100 % Arbeitszeit = 2016 h; min. 40.32 Trainingstage regional). Pool 9 Mio. Auszahlung in 2 Tranchen.',
   '2026-01-01');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege (subvention_id, bezeichnung, betrag_pro_einheit)
VALUES (@sid, 'Beitrag pro Trainingstag', 202.0000);
INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis)
VALUES (@sid, 'Eingabe', '2026-06-30', 'alle 2 Jahre (gerade Jahre)');

-- -------------------------------------------------------------
-- 6. Swiss Sailing NWFK (Pauschale, Pool-Anteil SOTC)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen,
   verlangte_unterlagen, berechnungsgrundlage, gueltig_von)
VALUES
  ('Swiss Sailing NWFK', 'Swiss Sailing', 'jugend', 'pauschale',
   'Gilt für offizielle olympische und vorolympische Juniorenklassen (420er, ILCA, iQFOiL).',
   'Jahres- und Regattaplanung, Zielvereinbarungen der Kadersegler, Trainerberichte, Rapportformular mit Teilnehmernummern.',
   'CHF 28 000 / Gesamtzahl SOTC × SOTC der Region. Verwendung: Breitensport (Aktivitäten Junioren ohne SOTC).',
   '2026-01-01');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege (subvention_id, bezeichnung, grundbetrag) VALUES (@sid, 'NWFK-Beitrag (Pauschale)', 4958.00);
INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis) VALUES
  (@sid, 'Anmeldung Aktivitätsplanung', '2026-04-30', 'Förderlager anmelden'),
  (@sid, 'Report', '2026-10-31', 'Rapportformular an Swiss Sailing');
INSERT INTO subvention_betrag_historie (subvention_id, jahr, betrag, bemerkung) VALUES (@sid, 2025, 4958.00, 'Ist');

-- -------------------------------------------------------------
-- 7. J+S Lager Verband (Teilnehmertag, mit/ohne Übernachtung)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen,
   einschraenkungen, verlangte_unterlagen, berechnungsgrundlage, gueltig_von, link_extern)
VALUES
  ('J+S Lager (Jugendverband ZSF)', 'Bundesamt für Sport BASPO / J+S', 'lager', 'js_teilnehmertag',
   'Mind. 12 Teilnehmende (5–20 Jahre). Mind. 2 J+S-Leitende Lagersport, davon eine mit Anerkennung Lagerleiter. Mind. ein Lager mit 4 aufeinanderfolgenden Tagen; weitere Lager mind. 3 Tage.',
   'Mind. 2 Einheiten J+S-Aktivitäten pro Tag, insgesamt mind. 4 Stunden pro Tag. Pro 12 Teilnehmende eine Leiterperson.',
   'Anwesenheitskontrolle (AWK), Detailprogramm, Sicherheitskonzept (Notfallblatt).',
   'CHF 16 pro Teilnehmer und Tag mit auswärtiger Übernachtung, CHF 6.50 ohne Übernachtung.',
   '2026-01-01', 'https://www.jugendundsport.ch');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege
  (subvention_id, bezeichnung, satz_mit_uebernachtung, satz_ohne_uebernachtung, max_tage)
VALUES (@sid, 'J+S Lagerbeitrag', 16.00, 6.50, 14);
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag, bemerkung)
VALUES (@sid, 'js_trainer', 0.00, 'Zwei anerkannte J+S-Leiter Segeln');
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator, bemerkung)
VALUES (@sid, 'lager', 1.000, 'Mind. 4 zusammenhängende Lagertage');
INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis) VALUES
  (@sid, 'Anmeldung', NULL, '30 Tage vor Lagerbeginn (oder Jahresprogramm)'),
  (@sid, 'Abrechnung', NULL, '30 Tage nach Lagerende via NDS');

-- -------------------------------------------------------------
-- 8. J+S Lager Verein/privat (Teilnehmertag, 5.20 / 6.50 nach Sportstunden)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen,
   einschraenkungen, berechnungsgrundlage, gueltig_von, link_extern)
VALUES
  ('J+S Lager (Verein / privat)', 'Bundesamt für Sport BASPO / J+S', 'lager', 'js_teilnehmertag',
   'Mind. 2 Einheiten à mind. 2 Stunden pro Tag. Für Lager (mind. 4 Tage) mind. 2 J+S-Leitende, für Trainingstage (1–3 Tage) mind. 1.',
   'Mit oder ohne auswärtige Übernachtung. Leiter-Teilnehmer-Schlüssel gemäss Sportart-Leitfaden.',
   'CHF 6.50 pro Teilnehmer/Tag ab 5 Stunden Sport, CHF 5.20 bei 4 Stunden Sport. Hier: Feld "mit Übernachtung" = ab 5 Std, "ohne" = 4 Std.',
   '2026-01-01', 'https://www.jugendundsport.ch');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege
  (subvention_id, bezeichnung, satz_mit_uebernachtung, satz_ohne_uebernachtung)
VALUES (@sid, 'J+S Lagerbeitrag Verein', 6.50, 5.20);
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag, bemerkung)
VALUES (@sid, 'js_trainer', 0.00, NULL);
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator, bemerkung) VALUES
  (@sid, 'lager', 1.000, NULL),
  (@sid, 'training', 1.000, 'Trainingstage 1–3 Tage');

-- -------------------------------------------------------------
-- 9. J+S Training (Teilnehmerstunde)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen,
   einschraenkungen, verlangte_unterlagen, berechnungsgrundlage, gueltig_von, link_extern)
VALUES
  ('J+S Training', 'Bundesamt für Sport BASPO / J+S', 'jugend', 'js_teilnehmerstunde',
   'Regelmässiges Training (Nutzergruppe 1/2). Mind. 5 Tage / 5 Trainings innert 5 Monaten, mind. 45 Teilnehmerstunden, mind. 1 J+S-Leiter.',
   'Pro Tag und Kind darf nur eine J+S-Einheit abgerechnet werden. Max. 5 Stunden pro Tag. Leiter-Kind-Schlüssel muss stimmen.',
   'Trainingsplan, laufend geführte digitale Anwesenheitskontrolle (NDS).',
   'CHF 1.30 pro Teilnehmerstunde, max. 5 Stunden pro Tag. Regatta kann als Training deklariert werden.',
   '2026-01-01', 'https://www.jugendundsport.ch');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege
  (subvention_id, bezeichnung, betrag_pro_stunde, max_stunden_pro_tag)
VALUES (@sid, 'J+S Trainingsbeitrag', 1.30, 5);
INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag, bemerkung)
VALUES (@sid, 'js_trainer', 0.00, NULL);
INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator, bemerkung)
VALUES (@sid, 'training', 1.000, NULL);

-- -------------------------------------------------------------
-- 10. ZSF Beitrag ZSTeam Kader (Jahresbeitrag, pro Aktivmitglied)
-- -------------------------------------------------------------
INSERT INTO subventionen
  (bezeichnung, foerderstelle, kategorie, berechnungstyp, voraussetzungen,
   berechnungsgrundlage, gueltig_von)
VALUES
  ('ZSF Beitrag ZSTeam', 'Zurich Sailing Federation', 'jugend', 'jahresbeitrag',
   'Unterstützung ZSTeam (Kader).',
   'CHF 5 pro Aktivmitglied eines ZSF-Clubs (ohne Jugendliche). Gesamtbetrag pro Kadermitglied jeder Klasse zugeteilt.',
   '2026-01-01');
SET @sid = LAST_INSERT_ID();
INSERT INTO subvention_betraege (subvention_id, bezeichnung, betrag_pro_einheit)
VALUES (@sid, 'Beitrag pro Aktivmitglied', 5.0000);
