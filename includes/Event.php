<?php
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------
// Read-only-Sicht auf die Events (Anlässe) des ClassManagerTool plus die
// Zuordnung Event <-> Subvention.
//
// Die Events selbst werden im ClassManagerTool (Tabelle cm_events) erfasst
// und hier nur gelesen. Welche Subventionen für ein Event gelten, wird in
// der Tabelle subvention_events festgehalten – diese gehört zum
// Subventionssimulator und wird hier geschrieben.
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
    // zugeordneten Subventionen. Neueste zuerst.
    public static function alle(): array {
        return db()->query('
            SELECT e.*,
                   k.bezeichnung AS klasse_bezeichnung,
                   COUNT(se.id)  AS anzahl_subventionen
            FROM cm_events e
            LEFT JOIN cm_klassen k        ON k.id        = e.klasse_id
            LEFT JOIN subvention_events se ON se.event_id = e.id
            GROUP BY e.id
            ORDER BY e.datum_von DESC, e.erstellt_am DESC
        ')->fetchAll();
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

    // IDs der aktuell zugeordneten Subventionen (für Checkbox-Vorbelegung).
    public static function zugeordneteSubventionIds(int $event_id): array {
        $stmt = db()->prepare('SELECT subvention_id FROM subvention_events WHERE event_id = ?');
        $stmt->execute([$event_id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
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

        $platzhalter = implode(',', array_fill(0, count($event_ids), '?'));

        // 1) Zuordnungen je Event
        $stmt = db()->prepare("
            SELECT event_id, subvention_id FROM subvention_events
            WHERE event_id IN ($platzhalter)
        ");
        $stmt->execute(array_map('intval', $event_ids));
        $subventionIdsProEvent = [];
        foreach ($stmt->fetchAll() as $z) {
            $subventionIdsProEvent[(int)$z['event_id']][] = (int)$z['subvention_id'];
        }

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

    // Zuordnung speichern: bestehende Zuordnungen ersetzen (DELETE + INSERT),
    // analog zum Muster in Subvention::trainerartenSpeichern().
    public static function zuordnungSpeichern(int $event_id, array $subvention_ids, ?int $benutzer_id = null): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM subvention_events WHERE event_id = ?')->execute([$event_id]);
            $stmt = $pdo->prepare('
                INSERT INTO subvention_events (event_id, subvention_id, erstellt_von)
                VALUES (?, ?, ?)
            ');
            // Doppelte IDs herausfiltern, damit der UNIQUE-Key nicht greift.
            foreach (array_unique(array_map('intval', $subvention_ids)) as $sid) {
                if ($sid > 0) {
                    $stmt->execute([$event_id, $sid, $benutzer_id]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
