<?php
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------
// Read-only-Sicht auf die Events (Anlässe) des ClassManagerTool plus die
// Zuordnung Event <-> Subvention.
//
// Die Events selbst werden im ClassManagerTool (Tabelle cm_events) erfasst
// und hier nur gelesen. Welche Subventionen für ein Event gelten, wird in
// der Tabelle subvention_events festgehalten – diese gehört zum
// Subventionssimulator und wird hier geschrieben. Zusätzlich gibt es einen
// Standard (subventionen.gilt_standard): Programme, die für alle Events
// gelten, mit ausgenommenen Klassen und Events. Massgebend ist die
// «wirksame» Zuordnung (wirksameZuordnung()).
//
// STATUS/STATUS_BADGE entsprechen bewusst den Werten im ClassManagerTool
// (includes/Event.php dort), damit dieselben Labels und Farben erscheinen.
// STATUS_BADGE liefert Badge-Modifier aus dem ZSF-UI-Kit (shared-ui.css),
// im Markup zusammen mit der Basisklasse "badge" verwenden.
// ---------------------------------------------------------------------

class Event {

    public const STATUS = [
        'provisorisch_geplant' => 'Provisorisch geplant',
        'geplant'              => 'Geplant',
        'durchgefuehrt'        => 'Durchgeführt',
        'abgeschlossen'        => 'Abgeschlossen',
    ];

    public const STATUS_BADGE = [
        'provisorisch_geplant' => 'badge--warning',
        'geplant'              => 'badge--info',
        'durchgefuehrt'        => 'badge--success',
        'abgeschlossen'        => 'badge--neutral',
    ];

    // Alle Events über alle Klassen, inkl. Klassenname und Anzahl der
    // wirksam zugeordneten Subventionen (Standard + Anpassungen). Neueste zuerst.
    public static function alle(): array {
        $events = db()->query('
            SELECT e.*, k.bezeichnung AS klasse_bezeichnung
            FROM cm_events e
            LEFT JOIN cm_klassen k ON k.id = e.klasse_id
            ORDER BY e.datum_von DESC, e.erstellt_am DESC
        ')->fetchAll();
        $zuordnung = self::wirksameZuordnung($events);
        foreach ($events as &$e) {
            $e['anzahl_subventionen'] = count($zuordnung[(int)$e['id']] ?? []);
        }
        unset($e);
        return $events;
    }

    // Ein Event laden – null wenn nicht gefunden.
    public static function laden(int $id): ?array {
        $stmt = db()->prepare('
            SELECT e.*, k.bezeichnung AS klasse_bezeichnung
            FROM cm_events e
            LEFT JOIN cm_klassen k ON k.id = e.klasse_id
            WHERE e.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // IDs der wirksam zugeordneten Subventionen (für Checkbox-Vorbelegung).
    public static function zugeordneteSubventionIds(int $event_id): array {
        $event = self::laden($event_id);
        if (!$event) return [];
        return self::wirksameZuordnung([$event])[$event_id] ?? [];
    }

    // Alle Klassen für Auswahl und Ausschlüsse.
    public static function klassen(): array {
        return db()->query('SELECT id, bezeichnung FROM cm_klassen ORDER BY bezeichnung')->fetchAll();
    }

    // Standard-Förderprogramme: [subvention_id => [ausgeschlossene klasse_ids]].
    // Nur nicht gelöschte, aktive Programme.
    public static function standardProgramme(): array {
        $ergebnis = [];
        $rows = db()->query('
            SELECT id FROM subventionen
            WHERE gilt_standard = 1 AND aktiv = 1 AND geloescht_am IS NULL
        ')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $sid) {
            $ergebnis[(int)$sid] = [];
        }
        $stmt = db()->query('SELECT subvention_id, klasse_id FROM subvention_klassen_ausschluss');
        foreach ($stmt->fetchAll() as $z) {
            if (isset($ergebnis[(int)$z['subvention_id']])) {
                $ergebnis[(int)$z['subvention_id']][] = (int)$z['klasse_id'];
            }
        }
        return $ergebnis;
    }

    // Gilt das Programm per Standard für diese Klasse? (klasse_id null = keine
    // Klasse: Standard gilt, da keine Klasse ausgenommen werden kann.)
    private static function istStandard(array $standard, int $sid, ?int $klasse_id): bool {
        return isset($standard[$sid])
            && ($klasse_id === null || !in_array($klasse_id, $standard[$sid], true));
    }

    // Wirksame Zuordnung mehrerer Events: Standard der Klasse, abzüglich
    // Event-Ausschlüssen, zuzüglich explizit zugeordneter Programme
    // (subvention_events).
    //
    // Rückgabe: [event_id => [subvention_id, ...]]
    public static function wirksameZuordnung(array $events): array {
        $standard = self::standardProgramme();

        $ausgenommen = [];
        foreach (db()->query('SELECT subvention_id, event_id FROM subvention_event_ausschluss')->fetchAll() as $z) {
            $ausgenommen[(int)$z['event_id']][(int)$z['subvention_id']] = true;
        }
        $explizit = [];
        foreach (db()->query('SELECT subvention_id, event_id FROM subvention_events')->fetchAll() as $z) {
            $explizit[(int)$z['event_id']][] = (int)$z['subvention_id'];
        }

        $ergebnis = [];
        foreach ($events as $e) {
            $eid = (int)$e['id'];
            $klasse = $e['klasse_id'] !== null ? (int)$e['klasse_id'] : null;
            $ids = $explizit[$eid] ?? [];
            foreach ($standard as $sid => $_) {
                if (self::istStandard($standard, $sid, $klasse) && empty($ausgenommen[$eid][$sid])) {
                    $ids[] = $sid;
                }
            }
            $ergebnis[$eid] = array_values(array_unique($ids));
        }
        return $ergebnis;
    }

    // Förderstatus mehrerer Events auf einen Blick: pro Event, ob mindestens
    // eine zugeordnete Subvention vollständig erfasst ist (Subvention::
    // vollstaendigkeit()) und ob dafür bereits ein konkreter Betrag in
    // subvention_verwendung zugeteilt wurde. Für die Badges auf events.php –
    // gezielt für Events mit Status 'geplant'/'durchgefuehrt' gedacht.
    //
    // Rückgabe: [event_id => ['foerderfaehig' => bool, 'betrag_zugeteilt' => ?float]]
    public static function foerderstatusFuerEvents(array $event_ids): array {
        require_once __DIR__ . '/Subvention.php';

        $ergebnis = [];
        foreach ($event_ids as $id) {
            $ergebnis[(int)$id] = ['foerderfaehig' => false, 'betrag_zugeteilt' => null];
        }
        if (empty($event_ids)) return $ergebnis;

        // 1) Wirksame Zuordnungen je Event (Standard + Anpassungen)
        $platzhalter = implode(',', array_fill(0, count($event_ids), '?'));
        $stmt = db()->prepare("SELECT * FROM cm_events WHERE id IN ($platzhalter)");
        $stmt->execute(array_map('intval', $event_ids));
        $subventionIdsProEvent = self::wirksameZuordnung($stmt->fetchAll());

        // 2) Vollständigkeit je distinktem Förderprogramm (Kardinalität =
        // Anzahl Programme, nicht Anzahl Events).
        $alleSubventionIds = array_unique(array_merge(...array_values($subventionIdsProEvent ?: [[]])));
        $vollstaendigJeSubvention = [];
        foreach ($alleSubventionIds as $sid) {
            $subv = Subvention::laden($sid);
            $vollstaendigJeSubvention[$sid] = $subv && Subvention::vollstaendigkeit($subv) === [];
        }

        // 3) Bereits zugeteilte Beträge je Event
        $stmt = db()->prepare("
            SELECT ziel_event_id, SUM(betrag) AS summe
            FROM subvention_verwendung
            WHERE ziel_typ = 'event' AND ziel_event_id IN ($platzhalter)
            GROUP BY ziel_event_id
        ");
        $stmt->execute(array_map('intval', $event_ids));
        foreach ($stmt->fetchAll() as $z) {
            $ergebnis[(int)$z['ziel_event_id']]['betrag_zugeteilt'] = round((float)$z['summe'], 2);
        }

        // 4) Zusammenführen
        foreach ($subventionIdsProEvent as $eventId => $sids) {
            foreach ($sids as $sid) {
                if (!empty($vollstaendigJeSubvention[$sid])) {
                    $ergebnis[$eventId]['foerderfaehig'] = true;
                    break;
                }
            }
        }

        return $ergebnis;
    }

    // Zuordnung eines Events speichern. Gespeichert wird nur die Abweichung
    // vom Standard der Klasse: zusätzliche Programme in subvention_events,
    // abgewählte Standardprogramme in subvention_event_ausschluss. So bleibt
    // der Event bei späteren Änderungen am Standard auf dem Laufenden.
    public static function zuordnungSpeichern(int $event_id, array $subvention_ids, ?int $benutzer_id = null): void {
        $event = self::laden($event_id);
        if (!$event) {
            throw new RuntimeException('Event nicht gefunden.');
        }
        $klasse   = $event['klasse_id'] !== null ? (int)$event['klasse_id'] : null;
        $standard = self::standardProgramme();
        $gewaehlt = array_unique(array_filter(array_map('intval', $subvention_ids), fn($i) => $i > 0));

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM subvention_events WHERE event_id = ?')->execute([$event_id]);
            $pdo->prepare('DELETE FROM subvention_event_ausschluss WHERE event_id = ?')->execute([$event_id]);

            $ein = $pdo->prepare('
                INSERT INTO subvention_events (event_id, subvention_id, erstellt_von)
                VALUES (?, ?, ?)
            ');
            foreach ($gewaehlt as $sid) {
                if (!self::istStandard($standard, $sid, $klasse)) {
                    $ein->execute([$event_id, $sid, $benutzer_id]);
                }
            }
            $aus = $pdo->prepare('INSERT INTO subvention_event_ausschluss (subvention_id, event_id) VALUES (?, ?)');
            foreach (array_keys($standard) as $sid) {
                if (self::istStandard($standard, $sid, $klasse) && !in_array($sid, $gewaehlt, true)) {
                    $aus->execute([$sid, $event_id]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Standard speichern: pro Programm, ob es für alle Events gilt, und
    // welche Klassen ausgenommen sind.
    //   $standard: [subvention_id => [ausgeschlossene klasse_ids]] – nur
    //              Programme, die als Standard gelten, kommen vor.
    public static function standardSpeichern(array $standard): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE subventionen SET gilt_standard = 0');
            $pdo->exec('DELETE FROM subvention_klassen_ausschluss');
            $setzen = $pdo->prepare('UPDATE subventionen SET gilt_standard = 1 WHERE id = ?');
            $aus    = $pdo->prepare('INSERT INTO subvention_klassen_ausschluss (subvention_id, klasse_id) VALUES (?, ?)');
            foreach ($standard as $sid => $klassenIds) {
                $sid = (int)$sid;
                if ($sid <= 0) continue;
                $setzen->execute([$sid]);
                foreach (array_unique(array_map('intval', (array)$klassenIds)) as $kid) {
                    if ($kid > 0) $aus->execute([$sid, $kid]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
