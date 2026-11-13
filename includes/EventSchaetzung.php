<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Foerderrechner.php';
require_once __DIR__ . '/Subvention.php';
require_once __DIR__ . '/Event.php';

// ---------------------------------------------------------------------
// Erwartete Subventionen pro Event, geschätzt aus den Daten des Class
// Manager Tools (cm_events, cm_teilnehmer, cm_event_personalaufwand).
//
// ACHTUNG: Spiegel von Budget::subventionenSchaetzungFuerEventLaden() und
// Budget::subventionProgrammSchaetzen() im Class Manager Tool
// (includes/Budget.php dort). Beide Apps müssen dieselben Zahlen liefern –
// bei Änderungen der Regeln (J+S-Voraussetzungen, Lager/Training, Tage,
// Teilnehmerzahl) hier UND dort nachziehen. Die Formeln selbst liegen in
// Foerderrechner.php (identische Kopie). Unterschied: Verbandsweite
// Programme (Pauschale, Jahresbeitrag) sind nicht event-basiert und werden
// hier nicht pro Event geschätzt.
//
// Alles rein lesend.
// ---------------------------------------------------------------------

class EventSchaetzung {

    public const LEKTIONEN_PRO_LAGERTAG = 6;

    public const JS_ALTER_MIN = 5;
    public const JS_ALTER_MAX = 20;
    public const JS_LAGER_MIN_TAGE = 4;
    public const JS_LAGER_MIN_TEILNEHMER = 12;
    public const JS_LAGER_MIN_LEITER = 2;
    public const JS_TRAINING_MIN_TEILNEHMER = 3;
    public const JS_TEILNEHMER_PRO_LEITER = 12;

    // Ab diesem Status zählen die tatsächlich Anwesenden, davor die Planzahl.
    public const DANACH_STATUS = ['durchgefuehrt', 'abgeschlossen'];
    // Ab diesem Status ist die Teilnehmerliste massgebend (statt Planzahlen).
    public const LISTE_WAEHLBAR_AB_STATUS = ['geplant', 'durchgefuehrt', 'abgeschlossen'];

    // ---- reine Regeln (ohne DB) -------------------------------------

    public static function foerderfaehigeTage(string $von, string $bis, bool $anreise, bool $abreise): int {
        $v = strtotime($von);
        $b = strtotime($bis);
        $tage = ($v === false || $b === false || $b < $v) ? 1 : (int)floor(($b - $v) / 86400) + 1;
        return max(0, $tage - ($anreise ? 1 : 0) - ($abreise ? 1 : 0));
    }

    // Ab vier zusammenhängenden Tagen gilt ein Anlass als Lager.
    public static function eventartAusDauer(int $tage): string {
        return $tage >= 4 ? 'lager' : 'training';
    }

    public static function istJsProgramm(array $subv): bool {
        return str_starts_with((string)($subv['berechnungstyp'] ?? ''), 'js_')
            || stripos((string)($subv['foerderstelle'] ?? ''), 'J+S') !== false;
    }

    // Trainerart-Zulassungen einer Personalaufwand-Zeile.
    public static function personalzeileZulassungen(array $zeile): array {
        if (!empty($zeile['benutzer_id'])) {
            return ['js' => (bool)($zeile['konto_js'] ?? false), 'nwf' => (bool)($zeile['konto_nwf'] ?? false)];
        }
        return ['js' => (bool)($zeile['hat_js_zulassung'] ?? false), 'nwf' => false];
    }

    // Rückgabe: ['status' => 'ok'|'nicht_berechtigt'|'nicht_berechenbar', 'grund' => ?string, 'teilnehmer' => int]
    public static function jsVoraussetzungen(string $eventart, int $tage, int $jsTn, int $jsLeiter, bool $leiterErfasst): array {
        $nein = static fn(string $status, string $grund): array =>
            ['status' => $status, 'grund' => $grund, 'teilnehmer' => 0];
        $alter = self::JS_ALTER_MIN . '–' . self::JS_ALTER_MAX;
        $ohneLeiter = $leiterErfasst ? '' : ' (noch keine Personalaufwand-Zeile erfasst)';

        if ($eventart === 'lager') {
            if ($tage < self::JS_LAGER_MIN_TAGE) {
                return $nein('nicht_berechtigt', 'Lager: mindestens ' . self::JS_LAGER_MIN_TAGE . ' aufeinanderfolgende Tage');
            }
            if ($jsTn < self::JS_LAGER_MIN_TEILNEHMER) {
                return $nein('nicht_berechtigt', 'Lager: mindestens ' . self::JS_LAGER_MIN_TEILNEHMER . " Teilnehmende im J+S-Alter ($alter)");
            }
            if ($jsLeiter < self::JS_LAGER_MIN_LEITER) {
                return $nein($leiterErfasst ? 'nicht_berechtigt' : 'nicht_berechenbar',
                    'Lager: mindestens ' . self::JS_LAGER_MIN_LEITER . ' Leiter mit J+S-Anerkennung' . $ohneLeiter);
            }
            return ['status' => 'ok', 'grund' => null, 'teilnehmer' => $jsTn];
        }

        if ($jsTn < self::JS_TRAINING_MIN_TEILNEHMER) {
            return $nein('nicht_berechtigt', 'Training: mindestens ' . self::JS_TRAINING_MIN_TEILNEHMER . " Teilnehmende im J+S-Alter ($alter)");
        }
        if ($jsLeiter < 1) {
            return $nein($leiterErfasst ? 'nicht_berechtigt' : 'nicht_berechenbar',
                'Training: mindestens 1 Leiter mit J+S-Anerkennung' . $ohneLeiter);
        }
        return ['status' => 'ok', 'grund' => null,
                'teilnehmer' => min($jsTn, self::JS_TEILNEHMER_PRO_LEITER * $jsLeiter)];
    }

    // Ein Programm für eine Eventart schätzen. $subv ist Subvention::laden().
    // Rückgabe: ['bezeichnung','foerderstelle','status' => 'berechnet'|'nicht_berechtigt'|'nicht_berechenbar',
    //            'betrag' => ?float, 'grund' => ?string, 'aufschluesselung' => array]
    public static function programmSchaetzen(array $subv, string $eventart, int $teilnehmer, int $tage,
                                              array $personalzeilen, bool $hatJs, bool $hatNwf,
                                              int $jsTn, int $jsLeiter): array {
        $basis = ['bezeichnung' => (string)$subv['bezeichnung'], 'foerderstelle' => (string)($subv['foerderstelle'] ?? '')];
        $typ   = $subv['berechnungstyp'] ?? 'additiv';
        $offen = fn(string $status, string $grund): array =>
            $basis + ['status' => $status, 'betrag' => null, 'grund' => $grund, 'aufschluesselung' => []];

        if ($typ === 'js_teilnehmerstunde') {
            return $offen('nicht_berechenbar', 'Braucht Stunden pro Tag – im Event-Simulator prüfen');
        }
        if ($typ === 'zks_ausbildungseinheit' && $eventart !== 'lager') {
            return $offen('nicht_berechenbar', 'Lektionen pro Tag werden nur für Lagertage automatisch angenommen – bei Training im Event-Simulator prüfen');
        }
        $regel = $subv['betraege'][0] ?? null;
        if ($regel === null) {
            return $offen('nicht_berechenbar', 'Kein Beitragssatz hinterlegt');
        }

        if (self::istJsProgramm($subv)) {
            $js = self::jsVoraussetzungen($eventart, $tage, $jsTn, $jsLeiter, $personalzeilen !== []);
            if ($js['status'] !== 'ok') {
                return $offen($js['status'], (string)$js['grund']);
            }
            $teilnehmer = $js['teilnehmer'];
        }

        $trainerRow = null;
        $trainerarten = $subv['trainerarten'] ?? [];
        if ($trainerarten) {
            $passend = array_values(array_filter($trainerarten, static fn(array $t): bool => match ($t['trainerart']) {
                'ohne'        => true,
                'js_trainer'  => $hatJs,
                'nwf_trainer' => $hatNwf,
                default       => false,
            }));
            if (!$passend) {
                return $personalzeilen
                    ? $offen('nicht_berechtigt', 'Trainerart nicht berechtigt')
                    : $offen('nicht_berechenbar', 'Noch keine Personalaufwand-Zeile mit Trainerart-Angabe erfasst');
            }
            usort($passend, static fn(array $a, array $b): int => (float)$b['zusatzbetrag'] <=> (float)$a['zusatzbetrag']);
            $trainerRow = $passend[0];
        }

        $eventRow = null;
        $eventarten = $subv['eventarten'] ?? [];
        foreach ($eventarten as $e) {
            if ($e['eventart'] === $eventart) { $eventRow = $e; break; }
        }
        if ($eventarten && !$eventRow) {
            return $offen('nicht_berechtigt', 'Eventart (' . ($eventart === 'lager' ? 'Lager' : 'Training') . ') nicht berechtigt');
        }

        [$betrag, $auf] = Foerderrechner::betrag(
            $typ, $regel, $teilnehmer, $tage,
            ['uebernachtung' => $tage > 1, 'lektionen_pro_tag' => self::LEKTIONEN_PRO_LAGERTAG],
            $trainerRow, $eventRow
        );
        return $basis + ['status' => 'berechnet', 'betrag' => round($betrag, 2), 'grund' => null, 'aufschluesselung' => $auf];
    }

    // Schätzung aller wirksam zugeordneten Programme eines Events aus den
    // bereits geladenen Grundlagen (rein, ohne DB). $programme: Subvention::laden()-Zeilen.
    // Rückgabe: ['summe' => ?float, 'programme' => [...], 'teilnehmer' => ?int, 'tage' => int, 'eventart' => string]
    public static function schaetzen(array $programme, ?int $teilnehmer, int $tage, array $personalzeilen, ?int $jsTeilnehmer): array {
        $eventart = self::eventartAusDauer($tage);
        $ergebnis = ['summe' => null, 'programme' => [], 'teilnehmer' => $teilnehmer, 'tage' => $tage, 'eventart' => $eventart];
        if ($teilnehmer === null) {
            return $ergebnis;
        }

        $hatJs = $hatNwf = false;
        $jsLeiter = 0;
        foreach ($personalzeilen as $zeile) {
            $z = self::personalzeileZulassungen($zeile);
            $hatJs  = $hatJs  || $z['js'];
            $hatNwf = $hatNwf || $z['nwf'];
            $jsLeiter += $z['js'] ? 1 : 0;
        }
        $jsTn = $jsTeilnehmer ?? $teilnehmer;

        $summe = null;
        foreach ($programme as $subv) {
            $eintrag = self::programmSchaetzen($subv, $eventart, $teilnehmer, $tage, $personalzeilen, $hatJs, $hatNwf, $jsTn, $jsLeiter);
            // Rückfall: Lager, das als Lager nicht rechenbar ist, wird als Training gefördert.
            if ($eventart === 'lager' && $eintrag['status'] !== 'berechnet') {
                $alsTraining = self::programmSchaetzen($subv, 'training', $teilnehmer, $tage, $personalzeilen, $hatJs, $hatNwf, $jsTn, $jsLeiter);
                if ($alsTraining['status'] === 'berechnet') {
                    $alsTraining['aufschluesselung'][] = [
                        'label' => 'Als Training berechnet (als Lager nicht berechtigt/berechenbar)',
                        'wert' => 1, 'format' => 'zahl',
                    ];
                    $eintrag = $alsTraining;
                }
            }
            if ($eintrag['status'] === 'berechnet') {
                $summe = ($summe ?? 0.0) + $eintrag['betrag'];
            }
            $ergebnis['programme'][] = $eintrag;
        }
        $ergebnis['summe'] = $summe;
        return $ergebnis;
    }

    // ---- Laden aus der DB ---------------------------------------------

    // Schätzung für ein Event (Zeile aus cm_events) mit allen Grundlagen.
    // $programmeJeEvent: wirksame Programm-IDs (Event::wirksameZuordnung()),
    // $programmCache: [id => Subvention::laden()] wird mitgefüllt.
    public static function fuerEvent(array $event, array $subventionIds, array &$programmCache): array {
        $eid = (int)$event['id'];
        $pdo = db();

        $programme = [];
        foreach ($subventionIds as $sid) {
            if (!array_key_exists($sid, $programmCache)) {
                $s = Subvention::laden($sid);
                $programmCache[$sid] = ($s && $s['aktiv'] && $s['geloescht_am'] === null
                    && !in_array($s['berechnungstyp'] ?? 'additiv', Subvention::JAHRESTYPEN, true)) ? $s : null;
            }
            if ($programmCache[$sid] !== null) {
                $programme[] = $programmCache[$sid];
            }
        }
        usort($programme, fn($a, $b) => [$a['foerderstelle'], $a['bezeichnung']] <=> [$b['foerderstelle'], $b['bezeichnung']]);

        $status = (string)$event['status'];
        $danach = in_array($status, self::DANACH_STATUS, true);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS angemeldet, COALESCE(SUM(anwesend = 1), 0) AS anwesend
            FROM cm_teilnehmer WHERE event_id = ? AND status = 'angemeldet'
        ");
        $stmt->execute([$eid]);
        $tn = $stmt->fetch() ?: ['angemeldet' => 0, 'anwesend' => 0];

        if ($danach) {
            $basis = (int)$tn['anwesend'];
        } else {
            $kader  = ($event['erwartet_kader']  ?? null) === null ? null : max(0, (int)$event['erwartet_kader']);
            $gaeste = ($event['erwartet_gaeste'] ?? null) === null ? null : max(0, (int)$event['erwartet_gaeste']);
            $basis  = ($kader === null && $gaeste === null) ? null : (int)$kader + (int)$gaeste;
        }

        $tage = self::foerderfaehigeTage((string)$event['datum_von'], (string)$event['datum_bis'],
                                          !empty($event['anreisetag']), !empty($event['abreisetag']));

        // Erfassungsmodus: 'liste' (Teilnehmerliste massgebend) oder 'zahlen' (Planzahlen).
        $modus = 'zahlen';
        if ($danach) {
            $modus = 'liste';
        } elseif (in_array($status, self::LISTE_WAEHLBAR_AB_STATUS, true)) {
            $modus = ($event['teilnehmer_erfassung'] ?? 'zahlen') === 'liste' ? 'liste' : 'zahlen';
        } elseif ((int)$tn['angemeldet'] > 0) {
            $modus = 'liste';
        }
        $jsTn = null;
        if ($modus === 'liste' || $danach) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM cm_teilnehmer
                WHERE event_id = ? AND status = 'angemeldet'" . ($danach ? ' AND anwesend = 1' : '') . "
                  AND (jahrgang IS NULL OR (? - jahrgang) BETWEEN ? AND ?)
            ");
            $stmt->execute([$eid, (int)substr((string)$event['datum_von'], 0, 4), self::JS_ALTER_MIN, self::JS_ALTER_MAX]);
            $jsTn = (int)$stmt->fetchColumn();
        }

        // Privat-Events kennen keinen Personalaufwand.
        $personal = [];
        if (($event['kategorie'] ?? '') !== 'privat') {
            $stmt = $pdo->prepare('
                SELECT p.*, u.js AS konto_js, u.nwf AS konto_nwf
                FROM cm_event_personalaufwand p
                LEFT JOIN cm_users u ON u.id = p.benutzer_id
                WHERE p.event_id = ?
            ');
            $stmt->execute([$eid]);
            $personal = $stmt->fetchAll();
        }

        return self::schaetzen($programme, $basis, $tage, $personal, $jsTn);
    }

    // Jahresübersicht: alle Events, die im Jahr beginnen, mit ihrer Schätzung,
    // dazu Summen pro Förderprogramm und gesamt.
    // Rückgabe: ['events' => [event + 'schaetzung'], 'programme' => [bezeichnung-Schlüssel => ...], 'total' => float,
    //            'unvollstaendig' => int (Events ohne berechenbare Summe)]
    public static function jahresuebersicht(int $jahr): array {
        $stmt = db()->prepare('
            SELECT e.*, k.bezeichnung AS klasse_bezeichnung
            FROM cm_events e
            LEFT JOIN cm_klassen k ON k.id = e.klasse_id
            WHERE YEAR(e.datum_von) = ?
            ORDER BY e.datum_von, e.id
        ');
        $stmt->execute([$jahr]);
        $events = $stmt->fetchAll();

        $zuordnung = Event::wirksameZuordnung($events);
        $cache = [];
        $programme = [];
        $total = 0.0;
        $unvollstaendig = 0;

        foreach ($events as &$e) {
            $e['schaetzung'] = self::fuerEvent($e, $zuordnung[(int)$e['id']] ?? [], $cache);
            if ($e['schaetzung']['summe'] === null) {
                $unvollstaendig++;
            } else {
                $total += $e['schaetzung']['summe'];
            }
            foreach ($e['schaetzung']['programme'] as $p) {
                $key = $p['foerderstelle'] . "\0" . $p['bezeichnung'];
                $programme[$key] ??= $p + ['summe' => 0.0, 'anzahl' => 0];
                if ($p['status'] === 'berechnet') {
                    $programme[$key]['summe'] += $p['betrag'];
                    $programme[$key]['anzahl']++;
                }
            }
        }
        unset($e);
        ksort($programme);

        return ['events' => $events, 'programme' => array_values($programme), 'total' => $total, 'unvollstaendig' => $unvollstaendig];
    }
}
