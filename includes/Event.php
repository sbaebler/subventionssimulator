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
// ---------------------------------------------------------------------

class Event {

    public const STATUS = [
        'provisorisch_geplant' => 'Provisorisch geplant',
        'geplant'              => 'Geplant',
        'durchgefuehrt'        => 'Durchgeführt',
        'abgeschlossen'        => 'Abgeschlossen',
    ];

    public const STATUS_BADGE = [
        'provisorisch_geplant' => 'bg-amber-100 text-amber-700',
        'geplant'              => 'bg-blue-100 text-blue-700',
        'durchgefuehrt'        => 'bg-green-100 text-green-700',
        'abgeschlossen'        => 'bg-gray-100 text-gray-500',
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
