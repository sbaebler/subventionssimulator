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

    // ------------------------------------------------------------------
    // Alle aktiven Subventionen (für Listenansicht)
    // ------------------------------------------------------------------
    public static function alle(bool $nurAktive = true): array {
        $sql = 'SELECT * FROM subventionen';
        if ($nurAktive) {
            $sql .= ' WHERE aktiv = 1
                        AND (gueltig_bis IS NULL OR gueltig_bis >= CURDATE())';
        }
        $sql .= ' ORDER BY foerderstelle, bezeichnung';
        return db()->query($sql)->fetchAll();
    }

    // ------------------------------------------------------------------
    // Eine Subvention mit allen Detail-Tabellen laden
    // ------------------------------------------------------------------
    public static function laden(int $id): ?array {
        $subv = db()->prepare('SELECT * FROM subventionen WHERE id = ?');
        $subv->execute([$id]);
        $row = $subv->fetch();
        if (!$row) return null;

        $row['betraege']     = self::betraege($id);
        $row['trainerarten'] = self::trainerarten($id);
        $row['eventarten']   = self::eventarten($id);
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

    // ------------------------------------------------------------------
    // Subvention speichern (neu oder update)
    // ------------------------------------------------------------------
    public static function speichern(array $data): int {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Stammdaten
            $stammdaten = [
                'bezeichnung'     => $data['bezeichnung'],
                'beschreibung'    => $data['beschreibung'],
                'foerderstelle'   => $data['foerderstelle'],
                'kategorie'       => $data['kategorie'],
                'voraussetzungen' => $data['voraussetzungen'],
                'antragsfrist'    => $data['antragsfrist'],
                'gueltig_von'     => $data['gueltig_von'],
                'gueltig_bis'     => $data['gueltig_bis'],
                'link_extern'     => $data['link_extern'],
                'aktiv'           => $data['aktiv'],
            ];

            if (!empty($data['id'])) {
                $stmt = $pdo->prepare('
                    UPDATE subventionen SET
                        bezeichnung   = :bezeichnung,
                        beschreibung  = :beschreibung,
                        foerderstelle = :foerderstelle,
                        kategorie     = :kategorie,
                        voraussetzungen = :voraussetzungen,
                        antragsfrist  = :antragsfrist,
                        gueltig_von   = :gueltig_von,
                        gueltig_bis   = :gueltig_bis,
                        link_extern   = :link_extern,
                        aktiv         = :aktiv
                    WHERE id = :id
                ');
                $stmt->execute($stammdaten + ['id' => (int)$data['id']]);
                $id = (int)$data['id'];
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO subventionen
                        (bezeichnung, beschreibung, foerderstelle, kategorie,
                         voraussetzungen, antragsfrist, gueltig_von, gueltig_bis,
                         link_extern, aktiv)
                    VALUES
                        (:bezeichnung, :beschreibung, :foerderstelle, :kategorie,
                         :voraussetzungen, :antragsfrist, :gueltig_von, :gueltig_bis,
                         :link_extern, :aktiv)
                ');
                $stmt->execute($stammdaten);
                $id = (int)$pdo->lastInsertId();
            }

            // Detailtabellen neu schreiben
            self::betraegeSpeichern($id, $data['betraege'] ?? []);
            self::trainerartenSpeichern($id, $data['trainerarten'] ?? []);
            self::eventartenSpeichern($id, $data['eventarten'] ?? []);

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
                 betrag_pro_tag, max_teilnehmer, max_tage, betrag_max_gesamt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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

    // ------------------------------------------------------------------
    // Betrag berechnen (Kernlogik für den Simulator)
    //
    // $params = [
    //   'anzahl_teilnehmer' => int,
    //   'anzahl_tage'       => int,
    //   'trainerart'        => 'js_trainer'|'nwf_trainer'|'ohne',
    //   'eventart'          => 'lager'|'training',
    // ]
    // Gibt ein Array zurück: ['betrag' => float, 'aufschluesselung' => [...]]
    // ------------------------------------------------------------------
    public static function berechnen(int $id, array $params): array {
        $subv = self::laden($id);
        if (!$subv) throw new RuntimeException("Subvention $id nicht gefunden");

        $tn    = max(0, (int)$params['anzahl_teilnehmer']);
        $tage  = max(0, (int)$params['anzahl_tage']);
        $trainer = $params['trainerart'];
        $event   = $params['eventart'];

        // Trainerart berechtigt?
        $trainerRow = null;
        foreach ($subv['trainerarten'] as $t) {
            if ($t['trainerart'] === $trainer) { $trainerRow = $t; break; }
        }
        if (!$trainerRow) {
            return ['berechtigt' => false, 'grund' => 'Trainerart nicht berechtigt'];
        }

        // Eventart berechtigt?
        $eventRow = null;
        foreach ($subv['eventarten'] as $e) {
            if ($e['eventart'] === $event) { $eventRow = $e; break; }
        }
        if (!$eventRow) {
            return ['berechtigt' => false, 'grund' => 'Eventart nicht berechtigt'];
        }

        // Betragsberechnung (erste/einzige Betragsregel)
        $betrag = null;
        foreach ($subv['betraege'] as $regel) {
            $effTN   = ($regel['max_teilnehmer'] > 0) ? min($tn, $regel['max_teilnehmer']) : $tn;
            $effTage = ($regel['max_tage'] > 0)       ? min($tage, $regel['max_tage'])     : $tage;

            $b  = (float)$regel['grundbetrag'];
            $b += (float)$regel['betrag_pro_teilnehmer'] * $effTN;
            $b += (float)$regel['betrag_pro_tag']        * $effTage;
            $b += (float)$trainerRow['zusatzbetrag'];
            $b *= (float)$eventRow['multiplikator'];

            if ($regel['betrag_max_gesamt'] > 0) {
                $b = min($b, (float)$regel['betrag_max_gesamt']);
            }
            $betrag = $b;
            break; // erste Regel verwenden
        }

        return [
            'berechtigt'        => true,
            'bezeichnung'       => $subv['bezeichnung'],
            'foerderstelle'     => $subv['foerderstelle'],
            'betrag'            => round($betrag ?? 0, 2),
            'aufschluesselung'  => [
                'grundbetrag'        => (float)($subv['betraege'][0]['grundbetrag'] ?? 0),
                'tn_anteil'          => (float)($subv['betraege'][0]['betrag_pro_teilnehmer'] ?? 0) * $tn,
                'tage_anteil'        => (float)($subv['betraege'][0]['betrag_pro_tag'] ?? 0) * $tage,
                'trainer_zusatz'     => (float)$trainerRow['zusatzbetrag'],
                'eventart_faktor'    => (float)$eventRow['multiplikator'],
            ],
        ];
    }
}
