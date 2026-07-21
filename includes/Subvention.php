<?php
require_once __DIR__ . '/db.php';

class Subvention {

    // ------------------------------------------------------------------
    // Konstanten für ENUMs
    // ------------------------------------------------------------------
    public const KATEGORIEN = [
        'ausbildung'    => 'Ausbildung',
        'lager'         => 'Lager',
        'wettkampf'     => 'Wettkampf',
        'infrastruktur' => 'Infrastruktur',
        'jugend'        => 'Jugend',
        'sonstiges'     => 'Sonstiges',
    ];

    public const TRAINERARTEN = [
        'js_trainer'  => 'J+S Trainer',
        'nwf_trainer' => 'NWF Trainer',
        'ohne'        => 'Trainer ohne Anerkennung',
    ];

    public const EVENTARTEN = [
        'lager'    => 'Lager',
        'training' => 'Training',
    ];

    public const BERECHNUNGSTYPEN = [
        'additiv'                => 'Additiv (Grundbetrag + pro TN + pro Tag)',
        'js_teilnehmertag'       => 'J+S Teilnehmertag (Satz × TN × Tage)',
        'js_teilnehmerstunde'    => 'J+S Teilnehmerstunde (Satz × TN × Tage × Stunden)',
        'zks_ausbildungseinheit' => 'ZKS Ausbildungseinheit (Satz × TN × Lektionen × Tage)',
        'pauschale'              => 'Pauschale (fixer Jahresbetrag)',
        'jahresbeitrag'          => 'Jahresbeitrag (verbandsweite Kennzahl)',
    ];

    // ------------------------------------------------------------------
    // Alle aktiven Subventionen (für Listenansicht)
    // ------------------------------------------------------------------
    public static function alle(bool $nurAktive = true): array {
        $sql = '
            SELECT s.*,
                   b1.anzeigename AS erstellt_von_name,
                   b2.anzeigename AS geaendert_von_name
            FROM subventionen s
            LEFT JOIN benutzer b1 ON b1.id = s.erstellt_von
            LEFT JOIN benutzer b2 ON b2.id = s.geaendert_von
            WHERE s.geloescht_am IS NULL
        ';
        if ($nurAktive) {
            $sql .= ' AND s.aktiv = 1
                        AND (s.gueltig_bis IS NULL OR s.gueltig_bis >= CURDATE())';
        }
        $sql .= ' ORDER BY s.foerderstelle, s.bezeichnung';
        return db()->query($sql)->fetchAll();
    }

    // ------------------------------------------------------------------
    // Papierkorb: in den Papierkorb verschobene Förderprogramme
    // ------------------------------------------------------------------
    public static function papierkorb(): array {
        $sql = '
            SELECT s.*,
                   b1.anzeigename AS erstellt_von_name,
                   b3.anzeigename AS geloescht_von_name
            FROM subventionen s
            LEFT JOIN benutzer b1 ON b1.id = s.erstellt_von
            LEFT JOIN benutzer b3 ON b3.id = s.geloescht_von
            WHERE s.geloescht_am IS NOT NULL
            ORDER BY s.geloescht_am DESC
        ';
        return db()->query($sql)->fetchAll();
    }

    // In den Papierkorb verschieben (weiche Löschung) – Eintrag bleibt in der
    // DB und verschwindet nur aus den regulären Listen (alle()).
    public static function inPapierkorbVerschieben(int $id, ?int $benutzer_id = null): void {
        $stmt = db()->prepare('
            UPDATE subventionen
            SET geloescht_am = NOW(), geloescht_von = ?
            WHERE id = ?
        ');
        $stmt->execute([$benutzer_id, $id]);
    }

    // Aus dem Papierkorb zurückholen
    public static function wiederherstellen(int $id): void {
        $stmt = db()->prepare('
            UPDATE subventionen
            SET geloescht_am = NULL, geloescht_von = NULL
            WHERE id = ?
        ');
        $stmt->execute([$id]);
    }

    // Endgültig (hart) löschen – nur aus dem Papierkorb heraus zulässig.
    // simulationen verweist mit ON DELETE RESTRICT auf subventionen, daher
    // werden protokollierte Simulationen hier bewusst mitgelöscht; alle
    // anderen Detailtabellen räumt die DB selbst per ON DELETE CASCADE auf.
    public static function endgueltigLoeschen(int $id): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $check = $pdo->prepare('SELECT geloescht_am FROM subventionen WHERE id = ?');
            $check->execute([$id]);
            $geloescht_am = $check->fetchColumn();
            if ($geloescht_am === false || $geloescht_am === null) {
                throw new RuntimeException('Endgültiges Löschen ist nur aus dem Papierkorb heraus möglich.');
            }

            $pdo->prepare('DELETE FROM simulationen WHERE subvention_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM subventionen WHERE id = ?')->execute([$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Eine Subvention mit allen Detail-Tabellen laden
    // ------------------------------------------------------------------
    public static function laden(int $id): ?array {
        $subv = db()->prepare('
            SELECT s.*,
                   b1.anzeigename AS erstellt_von_name,
                   b2.anzeigename AS geaendert_von_name
            FROM subventionen s
            LEFT JOIN benutzer b1 ON b1.id = s.erstellt_von
            LEFT JOIN benutzer b2 ON b2.id = s.geaendert_von
            WHERE s.id = ?
        ');
        $subv->execute([$id]);
        $row = $subv->fetch();
        if (!$row) return null;

        $row['betraege']     = self::betraege($id);
        $row['trainerarten'] = self::trainerarten($id);
        $row['eventarten']   = self::eventarten($id);
        $row['fristen']      = self::fristen($id);
        $row['historie']     = self::betragHistorie($id);
        return $row;
    }

    // ------------------------------------------------------------------
    // Detailtabellen
    // ------------------------------------------------------------------
    public static function betraege(int $id): array {
        $s = db()->prepare('SELECT * FROM subvention_betraege WHERE subvention_id = ? ORDER BY id');
        $s->execute([$id]);
        return $s->fetchAll();
    }

    public static function trainerarten(int $id): array {
        $s = db()->prepare('SELECT * FROM subvention_trainerarten WHERE subvention_id = ? ORDER BY trainerart');
        $s->execute([$id]);
        return $s->fetchAll();
    }

    public static function eventarten(int $id): array {
        $s = db()->prepare('SELECT * FROM subvention_eventarten WHERE subvention_id = ? ORDER BY eventart');
        $s->execute([$id]);
        return $s->fetchAll();
    }

    public static function fristen(int $id): array {
        $s = db()->prepare('SELECT * FROM subvention_fristen WHERE subvention_id = ? ORDER BY datum IS NULL, datum, id');
        $s->execute([$id]);
        return $s->fetchAll();
    }

    public static function betragHistorie(int $id): array {
        $s = db()->prepare('SELECT * FROM subvention_betrag_historie WHERE subvention_id = ? ORDER BY jahr DESC');
        $s->execute([$id]);
        return $s->fetchAll();
    }

    // ------------------------------------------------------------------
    // Verwendung / Verteilung erhaltener Beiträge (pro Subvention & Jahr)
    // ------------------------------------------------------------------
    public static function verwendung(int $id, int $jahr): array {
        $s = db()->prepare('
            SELECT v.*,
                   e.bezeichnung AS event_bezeichnung,
                   k.bezeichnung AS klasse_bezeichnung
            FROM subvention_verwendung v
            LEFT JOIN cm_events  e ON e.id = v.ziel_event_id
            LEFT JOIN cm_klassen k ON k.id = v.ziel_klasse_id
            WHERE v.subvention_id = ? AND v.jahr = ?
            ORDER BY v.id
        ');
        $s->execute([$id, $jahr]);
        return $s->fetchAll();
    }

    // Ersetzt die Verwendung einer Subvention für ein Jahr (DELETE + INSERT),
    // analog zu Event::zuordnungSpeichern(). ziel_event_id/ziel_klasse_id werden
    // je nach ziel_typ gesetzt, die übrigen Zielfelder geleert.
    public static function verwendungSpeichern(int $id, int $jahr, array $rows, ?int $benutzer_id = null): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM subvention_verwendung WHERE subvention_id = ? AND jahr = ?');
            $del->execute([$id, $jahr]);

            $stmt = $pdo->prepare('
                INSERT INTO subvention_verwendung
                    (subvention_id, jahr, ziel_typ, ziel_event_id, ziel_klasse_id,
                     ziel_text, betrag, bemerkung, erstellt_von)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($rows as $r) {
                $typ = in_array($r['ziel_typ'] ?? '', ['event', 'klasse', 'reserve', 'frei'], true)
                    ? $r['ziel_typ'] : 'frei';
                $eventId  = ($typ === 'event'  && !empty($r['ziel_event_id']))  ? (int)$r['ziel_event_id']  : null;
                $klasseId = ($typ === 'klasse' && !empty($r['ziel_klasse_id'])) ? (int)$r['ziel_klasse_id'] : null;
                $text     = trim((string)($r['ziel_text'] ?? '')) ?: null;
                $betrag   = (float)($r['betrag'] ?? 0);
                $bemerkung = trim((string)($r['bemerkung'] ?? '')) ?: null;

                // Leere Zeile (kein Ziel und kein Betrag) überspringen
                if ($eventId === null && $klasseId === null && $text === null && $betrag == 0.0) {
                    continue;
                }
                $stmt->execute([$id, $jahr, $typ, $eventId, $klasseId, $text, $betrag, $bemerkung, $benutzer_id]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Subvention speichern (neu oder update)
    // ------------------------------------------------------------------
    public static function speichern(array $data, ?int $benutzer_id = null): int {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Stammdaten
            $stammdaten = [
                'bezeichnung'          => $data['bezeichnung'],
                'beschreibung'         => $data['beschreibung'],
                'foerderstelle'        => $data['foerderstelle'],
                'kategorie'            => $data['kategorie'],
                'berechnungstyp'       => array_key_exists($data['berechnungstyp'] ?? '', self::BERECHNUNGSTYPEN)
                                          ? $data['berechnungstyp'] : 'additiv',
                'voraussetzungen'      => $data['voraussetzungen'],
                'berechtigte'          => $data['berechtigte']          ?? null,
                'einschraenkungen'     => $data['einschraenkungen']     ?? null,
                'verlangte_unterlagen' => $data['verlangte_unterlagen'] ?? null,
                'berechnungsgrundlage' => $data['berechnungsgrundlage'] ?? null,
                'antragsfrist'         => $data['antragsfrist'],
                'gueltig_von'          => $data['gueltig_von'],
                'gueltig_bis'          => $data['gueltig_bis'],
                'link_extern'          => $data['link_extern'],
                'aktiv'                => $data['aktiv'],
            ];

            if (!empty($data['id'])) {
                $stmt = $pdo->prepare('
                    UPDATE subventionen SET
                        bezeichnung          = :bezeichnung,
                        beschreibung         = :beschreibung,
                        foerderstelle        = :foerderstelle,
                        kategorie            = :kategorie,
                        berechnungstyp       = :berechnungstyp,
                        voraussetzungen      = :voraussetzungen,
                        berechtigte          = :berechtigte,
                        einschraenkungen     = :einschraenkungen,
                        verlangte_unterlagen = :verlangte_unterlagen,
                        berechnungsgrundlage = :berechnungsgrundlage,
                        antragsfrist         = :antragsfrist,
                        gueltig_von          = :gueltig_von,
                        gueltig_bis          = :gueltig_bis,
                        link_extern          = :link_extern,
                        aktiv                = :aktiv,
                        geaendert_von        = :geaendert_von
                    WHERE id = :id
                ');
                $stmt->execute($stammdaten + ['geaendert_von' => $benutzer_id, 'id' => (int)$data['id']]);
                $id = (int)$data['id'];
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO subventionen
                        (bezeichnung, beschreibung, foerderstelle, kategorie,
                         berechnungstyp, voraussetzungen, berechtigte, einschraenkungen,
                         verlangte_unterlagen, berechnungsgrundlage,
                         antragsfrist, gueltig_von, gueltig_bis,
                         link_extern, aktiv, erstellt_von, geaendert_von)
                    VALUES
                        (:bezeichnung, :beschreibung, :foerderstelle, :kategorie,
                         :berechnungstyp, :voraussetzungen, :berechtigte, :einschraenkungen,
                         :verlangte_unterlagen, :berechnungsgrundlage,
                         :antragsfrist, :gueltig_von, :gueltig_bis,
                         :link_extern, :aktiv, :erstellt_von, :geaendert_von)
                ');
                $stmt->execute($stammdaten + ['erstellt_von' => $benutzer_id, 'geaendert_von' => $benutzer_id]);
                $id = (int)$pdo->lastInsertId();
            }

            // Detailtabellen neu schreiben
            self::betraegeSpeichern($id, $data['betraege'] ?? []);
            self::trainerartenSpeichern($id, $data['trainerarten'] ?? []);
            self::eventartenSpeichern($id, $data['eventarten'] ?? []);
            self::fristenSpeichern($id, $data['fristen'] ?? []);
            self::betragHistorieSpeichern($id, $data['historie'] ?? []);

            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function betraegeSpeichern(int $id, array $rows): void {
        db()->prepare('DELETE FROM subvention_betraege WHERE subvention_id = ?')->execute([$id]);
        $stmt = db()->prepare('
            INSERT INTO subvention_betraege
                (subvention_id, bezeichnung, grundbetrag, betrag_pro_teilnehmer,
                 betrag_pro_tag, max_teilnehmer, max_tage, betrag_max_gesamt,
                 satz_mit_uebernachtung, satz_ohne_uebernachtung, betrag_pro_stunde,
                 max_stunden_pro_tag, betrag_pro_einheit, max_lektionen_pro_tag)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        foreach ($rows as $r) {
            $stmt->execute([
                $id,
                $r['bezeichnung']           ?? 'Standardbetrag',
                (float)($r['grundbetrag']           ?? 0),
                (float)($r['betrag_pro_teilnehmer'] ?? 0),
                (float)($r['betrag_pro_tag']        ?? 0),
                (int)  ($r['max_teilnehmer']        ?? 0),
                (int)  ($r['max_tage']              ?? 0),
                (float)($r['betrag_max_gesamt']     ?? 0),
                (float)($r['satz_mit_uebernachtung']  ?? 0),
                (float)($r['satz_ohne_uebernachtung'] ?? 0),
                (float)($r['betrag_pro_stunde']       ?? 0),
                (int)  ($r['max_stunden_pro_tag']     ?? 0),
                (float)($r['betrag_pro_einheit']      ?? 0),
                (int)  ($r['max_lektionen_pro_tag']   ?? 0),
            ]);
        }
    }

    private static function trainerartenSpeichern(int $id, array $rows): void {
        db()->prepare('DELETE FROM subvention_trainerarten WHERE subvention_id = ?')->execute([$id]);
        $stmt = db()->prepare('
            INSERT INTO subvention_trainerarten (subvention_id, trainerart, zusatzbetrag, bemerkung)
            VALUES (?, ?, ?, ?)
        ');
        foreach ($rows as $r) {
            $stmt->execute([
                $id,
                $r['trainerart'],
                (float)($r['zusatzbetrag'] ?? 0),
                $r['bemerkung'] ?? null,
            ]);
        }
    }

    private static function eventartenSpeichern(int $id, array $rows): void {
        db()->prepare('DELETE FROM subvention_eventarten WHERE subvention_id = ?')->execute([$id]);
        $stmt = db()->prepare('
            INSERT INTO subvention_eventarten (subvention_id, eventart, multiplikator, bemerkung)
            VALUES (?, ?, ?, ?)
        ');
        foreach ($rows as $r) {
            $stmt->execute([
                $id,
                $r['eventart'],
                (float)($r['multiplikator'] ?? 1.0),
                $r['bemerkung'] ?? null,
            ]);
        }
    }

    private static function fristenSpeichern(int $id, array $rows): void {
        db()->prepare('DELETE FROM subvention_fristen WHERE subvention_id = ?')->execute([$id]);
        $stmt = db()->prepare('
            INSERT INTO subvention_fristen (subvention_id, bezeichnung, datum, hinweis)
            VALUES (?, ?, ?, ?)
        ');
        foreach ($rows as $r) {
            $bezeichnung = trim((string)($r['bezeichnung'] ?? ''));
            if ($bezeichnung === '') continue;
            $stmt->execute([
                $id,
                $bezeichnung,
                !empty($r['datum']) ? $r['datum'] : null,
                trim((string)($r['hinweis'] ?? '')) ?: null,
            ]);
        }
    }

    private static function betragHistorieSpeichern(int $id, array $rows): void {
        db()->prepare('DELETE FROM subvention_betrag_historie WHERE subvention_id = ?')->execute([$id]);
        $stmt = db()->prepare('
            INSERT INTO subvention_betrag_historie (subvention_id, jahr, betrag, bemerkung)
            VALUES (?, ?, ?, ?)
        ');
        foreach ($rows as $r) {
            $jahr = (int)($r['jahr'] ?? 0);
            if ($jahr <= 0) continue;
            $stmt->execute([
                $id,
                $jahr,
                (float)($r['betrag'] ?? 0),
                trim((string)($r['bemerkung'] ?? '')) ?: null,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Einzelnen Jahresbetrag setzen (Upsert auf uq_subvention_jahr) –
    // erlaubt das Erfassen des erhaltenen Betrags direkt auf der
    // Verwendungsseite, ohne Umweg über die Erfassungsmaske.
    // ------------------------------------------------------------------
    public static function historieSetzen(int $id, int $jahr, float $betrag, ?string $bemerkung = null): void {
        $stmt = db()->prepare('
            INSERT INTO subvention_betrag_historie (subvention_id, jahr, betrag, bemerkung)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                betrag    = VALUES(betrag),
                bemerkung = COALESCE(VALUES(bemerkung), bemerkung)
        ');
        $stmt->execute([$id, $jahr, $betrag, $bemerkung]);
    }

    // ------------------------------------------------------------------
    // Vollständigkeits-Check für die Übersicht: liefert fehlende Angaben
    // als Liste von ['text' => ..., 'schritt' => Wizard-Schritt 1-4].
    // Leere Liste = Förderprogramm vollständig erfasst.
    // ------------------------------------------------------------------
    public static function vollstaendigkeit(array $subv): array {
        $id       = (int)($subv['id'] ?? 0);
        $betraege = $subv['betraege'] ?? self::betraege($id);
        $fristen  = $subv['fristen']  ?? self::fristen($id);
        $historie = $subv['historie'] ?? self::betragHistorie($id);

        $fehlt = [];
        if (empty($betraege)) {
            $fehlt[] = ['text' => 'Beitragssatz fehlt', 'schritt' => 2];
        }
        if (trim((string)($subv['beschreibung'] ?? '')) === ''
            && trim((string)($subv['voraussetzungen'] ?? '')) === '') {
            $fehlt[] = ['text' => 'Beschreibung fehlt', 'schritt' => 3];
        }
        if (empty($fristen) && empty($subv['antragsfrist'])) {
            $fehlt[] = ['text' => 'Keine Frist erfasst', 'schritt' => 4];
        }
        $aktJahr = (int)date('Y');
        $hatAktuellenBetrag = false;
        foreach ($historie as $h) {
            if ((int)$h['jahr'] >= $aktJahr - 1) { $hatAktuellenBetrag = true; break; }
        }
        if (!$hatAktuellenBetrag) {
            $fehlt[] = ['text' => 'Kein Jahresbetrag (' . ($aktJahr - 1) . '/' . $aktJahr . ')', 'schritt' => 4];
        }
        return $fehlt;
    }

    // ------------------------------------------------------------------
    // Betrag berechnen (Kernlogik für den Simulator)
    //
    // $params = [
    //   'anzahl_teilnehmer' => int,
    //   'anzahl_tage'       => int,
    //   'trainerart'        => 'js_trainer'|'nwf_trainer'|'ohne',
    //   'eventart'          => 'lager'|'training',
    //   'uebernachtung'     => bool,   (js_teilnehmertag)
    //   'stunden_pro_tag'   => int,    (js_teilnehmerstunde)
    //   'lektionen_pro_tag' => int,    (zks_ausbildungseinheit)
    //   'anzahl_einheiten'  => float,  (jahresbeitrag – Phase 3)
    // ]
    //
    // Rückgabe berechtigt: [
    //   'berechtigt' => true, 'bezeichnung', 'foerderstelle', 'berechnungstyp',
    //   'betrag' => float,
    //   'aufschluesselung' => [ ['label'=>, 'wert'=>float, 'format'=>'chf'|'zahl'|'faktor'], ... ],
    // ]
    // ------------------------------------------------------------------
    public static function berechnen(int $id, array $params): array {
        $subv = self::laden($id);
        if (!$subv) throw new RuntimeException("Subvention $id nicht gefunden");
        return self::berechneAusSubvention($subv, $params);
    }

    // Reine Berechnung auf einer bereits geladenen Subvention (ohne DB-Zugriff –
    // dadurch direkt testbar). Gates für Trainer-/Eventart greifen nur, wenn die
    // Subvention entsprechende Einträge hat.
    public static function berechneAusSubvention(array $subv, array $params): array {
        $tn        = max(0, (int)($params['anzahl_teilnehmer'] ?? 0));
        $tage      = max(0, (int)($params['anzahl_tage'] ?? 0));
        $trainer   = $params['trainerart'] ?? 'ohne';
        $event     = $params['eventart']   ?? '';
        $typ       = $subv['berechnungstyp'] ?? 'additiv';

        $meta = [
            'bezeichnung'    => $subv['bezeichnung']    ?? '',
            'foerderstelle'  => $subv['foerderstelle']  ?? '',
            'berechnungstyp' => $typ,
        ];

        // Gate Trainerart – nur prüfen, wenn Trainerarten hinterlegt sind
        $trainerRow = null;
        foreach ($subv['trainerarten'] ?? [] as $t) {
            if ($t['trainerart'] === $trainer) { $trainerRow = $t; break; }
        }
        if (!empty($subv['trainerarten']) && !$trainerRow) {
            return $meta + ['berechtigt' => false, 'grund' => 'Trainerart nicht berechtigt'];
        }

        // Gate Eventart – nur prüfen, wenn Eventarten hinterlegt sind
        $eventRow = null;
        foreach ($subv['eventarten'] ?? [] as $e) {
            if ($e['eventart'] === $event) { $eventRow = $e; break; }
        }
        if (!empty($subv['eventarten']) && !$eventRow) {
            return $meta + ['berechtigt' => false, 'grund' => 'Eventart nicht berechtigt'];
        }

        $regel = $subv['betraege'][0] ?? [];

        [$betrag, $aufschluesselung] = match ($typ) {
            'js_teilnehmertag'       => self::berechneTeilnehmertag($regel, $tn, $tage, !empty($params['uebernachtung'])),
            'js_teilnehmerstunde'    => self::berechneTeilnehmerstunde($regel, $tn, $tage, max(0, (int)($params['stunden_pro_tag'] ?? 0))),
            'zks_ausbildungseinheit' => self::berechneAusbildungseinheit($regel, $tn, $tage, max(0, (int)($params['lektionen_pro_tag'] ?? 0))),
            'pauschale'              => self::berechnePauschale($regel),
            'jahresbeitrag'          => self::berechneJahresbeitrag($regel, (float)($params['anzahl_einheiten'] ?? 0)),
            default                  => self::berechneAdditiv($regel, $tn, $tage, $trainerRow, $eventRow),
        };

        // Deckelung auf Maximalbetrag (0 = kein Limit) – für alle Typen einheitlich
        $maxGesamt = (float)($regel['betrag_max_gesamt'] ?? 0);
        if ($maxGesamt > 0 && $betrag > $maxGesamt) {
            $betrag = $maxGesamt;
            $aufschluesselung[] = ['label' => 'Deckelung auf Maximalbetrag', 'wert' => $maxGesamt, 'format' => 'chf'];
        }

        return $meta + [
            'berechtigt'       => true,
            'betrag'           => round($betrag, 2),
            'aufschluesselung' => $aufschluesselung,
        ];
    }

    // Anrechenbare Menge unter Berücksichtigung einer Obergrenze (0 = kein Limit)
    private static function begrenzt(int $wert, int $max): int {
        return ($max > 0) ? min($wert, $max) : $wert;
    }

    // additiv: Grundbetrag + pro TN + pro Tag + Trainer-Zusatz, × Eventart-Faktor
    private static function berechneAdditiv(array $r, int $tn, int $tage, ?array $trainerRow, ?array $eventRow): array {
        $effTN   = self::begrenzt($tn,   (int)($r['max_teilnehmer'] ?? 0));
        $effTage = self::begrenzt($tage, (int)($r['max_tage'] ?? 0));

        $grund   = (float)($r['grundbetrag'] ?? 0);
        $tnAnt   = (float)($r['betrag_pro_teilnehmer'] ?? 0) * $effTN;
        $tageAnt = (float)($r['betrag_pro_tag'] ?? 0) * $effTage;
        $zusatz  = (float)($trainerRow['zusatzbetrag'] ?? 0);
        $faktor  = (float)($eventRow['multiplikator'] ?? 1);

        $betrag = ($grund + $tnAnt + $tageAnt + $zusatz) * $faktor;

        $auf = [
            ['label' => 'Grundbetrag',       'wert' => $grund,   'format' => 'chf'],
            ['label' => 'Teilnehmer-Anteil', 'wert' => $tnAnt,   'format' => 'chf'],
            ['label' => 'Tages-Anteil',      'wert' => $tageAnt, 'format' => 'chf'],
            ['label' => 'Trainer-Bonus',     'wert' => $zusatz,  'format' => 'chf'],
        ];
        if ($faktor != 1.0) {
            $auf[] = ['label' => 'Eventart-Faktor', 'wert' => $faktor, 'format' => 'faktor'];
        }
        return [$betrag, $auf];
    }

    // js_teilnehmertag: Satz × TN × Tage (Satz je nach Übernachtung)
    private static function berechneTeilnehmertag(array $r, int $tn, int $tage, bool $uebernachtung): array {
        $satz    = $uebernachtung
            ? (float)($r['satz_mit_uebernachtung'] ?? 0)
            : (float)($r['satz_ohne_uebernachtung'] ?? 0);
        $effTN   = self::begrenzt($tn,   (int)($r['max_teilnehmer'] ?? 0));
        $effTage = self::begrenzt($tage, (int)($r['max_tage'] ?? 0));

        $betrag = $satz * $effTN * $effTage;

        $auf = [
            ['label' => 'Satz pro Teilnehmer/Tag (' . ($uebernachtung ? 'mit' : 'ohne') . ' Übernachtung)', 'wert' => $satz, 'format' => 'chf'],
            ['label' => 'Anrechenbare Teilnehmer', 'wert' => $effTN,   'format' => 'zahl'],
            ['label' => 'Anrechenbare Tage',       'wert' => $effTage, 'format' => 'zahl'],
        ];
        return [$betrag, $auf];
    }

    // js_teilnehmerstunde: Satz × TN × Tage × Stunden/Tag
    private static function berechneTeilnehmerstunde(array $r, int $tn, int $tage, int $stunden): array {
        $satz       = (float)($r['betrag_pro_stunde'] ?? 0);
        $effTN      = self::begrenzt($tn,      (int)($r['max_teilnehmer'] ?? 0));
        $effTage    = self::begrenzt($tage,    (int)($r['max_tage'] ?? 0));
        $effStunden = self::begrenzt($stunden, (int)($r['max_stunden_pro_tag'] ?? 0));

        $betrag = $satz * $effTN * $effTage * $effStunden;

        $auf = [
            ['label' => 'Satz pro Teilnehmerstunde', 'wert' => $satz,       'format' => 'chf'],
            ['label' => 'Anrechenbare Teilnehmer',   'wert' => $effTN,      'format' => 'zahl'],
            ['label' => 'Anrechenbare Tage',         'wert' => $effTage,    'format' => 'zahl'],
            ['label' => 'Anrechenbare Stunden/Tag',  'wert' => $effStunden, 'format' => 'zahl'],
        ];
        return [$betrag, $auf];
    }

    // zks_ausbildungseinheit: Satz × Ausbildungseinheiten (= TN × Lektionen/Tag × Tage)
    private static function berechneAusbildungseinheit(array $r, int $tn, int $tage, int $lektionen): array {
        $satz         = (float)($r['betrag_pro_einheit'] ?? 0);
        $effTN        = self::begrenzt($tn,        (int)($r['max_teilnehmer'] ?? 0));
        $effTage      = self::begrenzt($tage,      (int)($r['max_tage'] ?? 0));
        $effLektionen = self::begrenzt($lektionen, (int)($r['max_lektionen_pro_tag'] ?? 0));

        $einheiten = $effTN * $effLektionen * $effTage;
        $betrag    = $satz * $einheiten;

        $auf = [
            ['label' => 'Satz pro Ausbildungseinheit', 'wert' => $satz,         'format' => 'chf'],
            ['label' => 'Anrechenbare Teilnehmer',     'wert' => $effTN,        'format' => 'zahl'],
            ['label' => 'Anrechenbare Lektionen/Tag',  'wert' => $effLektionen, 'format' => 'zahl'],
            ['label' => 'Anrechenbare Tage',           'wert' => $effTage,      'format' => 'zahl'],
            ['label' => 'Ausbildungseinheiten',        'wert' => $einheiten,    'format' => 'zahl'],
        ];
        return [$betrag, $auf];
    }

    // pauschale: fixer Betrag (im Grundbetrag hinterlegt)
    private static function berechnePauschale(array $r): array {
        $betrag = (float)($r['grundbetrag'] ?? 0);
        return [$betrag, [
            ['label' => 'Pauschalbetrag', 'wert' => $betrag, 'format' => 'chf'],
        ]];
    }

    // jahresbeitrag: Satz pro Einheit × Anzahl Einheiten (Kennzahl-basiert, Phase 3).
    // Ohne Einheiten fällt der Beitrag auf den Pauschalbetrag (Grundbetrag) zurück.
    private static function berechneJahresbeitrag(array $r, float $einheiten): array {
        $satz = (float)($r['betrag_pro_einheit'] ?? 0);
        if ($satz > 0 && $einheiten > 0) {
            $betrag = $satz * $einheiten;
            return [$betrag, [
                ['label' => 'Betrag pro Einheit', 'wert' => $satz,      'format' => 'chf'],
                ['label' => 'Anzahl Einheiten',   'wert' => $einheiten, 'format' => 'zahl'],
            ]];
        }
        $betrag = (float)($r['grundbetrag'] ?? 0);
        return [$betrag, [
            ['label' => 'Pauschalbetrag (Referenz)', 'wert' => $betrag, 'format' => 'chf'],
        ]];
    }
}
