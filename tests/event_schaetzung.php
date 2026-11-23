<?php
// Prüft die Regeln der Event-Schätzung (includes/EventSchaetzung.php) ohne DB:
//   php tests/event_schaetzung.php
// Spiegel von Budget::subventionenSchaetzungFuerEvent() im Class Manager Tool.

// Wie tests/vollstaendigkeit_matrix.php: ohne config.php eine Wegwerf-Version
// anlegen (nur für die require-Kette, es wird nie verbunden) und danach löschen.
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    file_put_contents($configPath, "<?php\ndefine('DB_HOST','x');define('DB_NAME','x');define('DB_USER','x');"
        . "define('DB_PASS','x');define('DB_CHARSET','utf8mb4');define('APP_NAME','Testlauf');"
        . "define('APP_BASE_URL','http://localhost');define('APP_ENV','development');\n");
    register_shutdown_function(static fn() => @unlink(__DIR__ . '/../config/config.php'));
}

require_once __DIR__ . '/../includes/EventSchaetzung.php';

$fehler = 0;
function pruefe(string $name, $ist, $soll): void {
    global $fehler;
    if ($ist === $soll) { echo "ok     $name\n"; return; }
    $fehler++;
    echo "FEHLER $name: " . var_export($ist, true) . ' statt ' . var_export($soll, true) . "\n";
}

pruefe('Tage ohne Reisetage', EventSchaetzung::foerderfaehigeTage('2026-07-10', '2026-07-14', true, true), 3);
pruefe('Tage ein Tag',        EventSchaetzung::foerderfaehigeTage('2026-07-10', '2026-07-10', false, false), 1);
pruefe('Lager ab 4 Tagen',    EventSchaetzung::eventartAusDauer(4), 'lager');
pruefe('Training bei 3',      EventSchaetzung::eventartAusDauer(3), 'training');

$js = ['bezeichnung' => 'J+S Lager', 'foerderstelle' => 'J+S', 'berechnungstyp' => 'js_teilnehmertag',
       'betraege' => [['betrag_pro_teilnehmer' => 0, 'betrag_pro_tag' => 0, 'satz' => 16, 'satz_ohne_uebernachtung' => 8]],
       'trainerarten' => [], 'eventarten' => []];
$pauschal = ['bezeichnung' => 'Kanton', 'foerderstelle' => 'Kanton', 'berechnungstyp' => 'additiv',
       'betraege' => [['grundbetrag' => 100, 'betrag_pro_teilnehmer' => 10, 'betrag_pro_tag' => 20]],
       'trainerarten' => [], 'eventarten' => []];

$leiter = fn(bool $js) => ['benutzer_id' => null, 'hat_js_zulassung' => $js];

// Nicht-J+S-Programm: 100 + 10×20 + 20×5 = 400
$r = EventSchaetzung::schaetzen([$pauschal], 20, 5, [], null);
pruefe('additiv Summe', $r['summe'], 400.0);
pruefe('Lager erkannt', $r['eventart'], 'lager');

// NWF: Satz × NWF-Trainer × Tage, unabhängig von der Teilnehmerzahl
$nwf = ['bezeichnung' => 'NWF', 'foerderstelle' => 'NWF', 'berechnungstyp' => 'nwf_trainertag',
        'betraege' => [['betrag_pro_trainertag' => 100]], 'trainerarten' => [], 'eventarten' => []];
$nwfKonto = fn() => ['benutzer_id' => 7, 'konto_js' => 0, 'konto_nwf' => 1];
$r = EventSchaetzung::schaetzen([$nwf], 20, 3, [$nwfKonto()], null);
pruefe('NWF 1 Trainer', $r['programme'][0]['betrag'], 300.0);
$r = EventSchaetzung::schaetzen([$nwf], 20, 3, [$nwfKonto(), $nwfKonto(), $nwfKonto()], null);
pruefe('NWF 3 Trainer multipliziert', $r['programme'][0]['betrag'], 900.0);
$r = EventSchaetzung::schaetzen([$nwf], 5, 3, [$nwfKonto(), $nwfKonto(), $leiter(true)], null);
pruefe('NWF zählt nur NWF-Trainer', $r['programme'][0]['betrag'], 600.0);
$r = EventSchaetzung::schaetzen([$nwf], 20, 3, [$leiter(true)], null);
pruefe('NWF ohne NWF-Trainer: nicht berechtigt', $r['programme'][0]['status'], 'nicht_berechtigt');
$r = EventSchaetzung::schaetzen([$nwf], 20, 3, [], null);
pruefe('NWF ohne Personal: nicht berechenbar', $r['programme'][0]['status'], 'nicht_berechenbar');

// Keine Teilnehmenden -> keine Schätzung
pruefe('ohne TN', EventSchaetzung::schaetzen([$pauschal], null, 5, [], null)['summe'], null);

// J+S Lager ohne Leiter erfasst -> nicht berechenbar (kein Rückfall auf Training möglich -> ebenfalls offen)
$r = EventSchaetzung::schaetzen([$js], 20, 5, [], null);
pruefe('J+S ohne Leiter', $r['programme'][0]['status'] !== 'berechnet', true);

// J+S Training: 3 Teilnehmende, ein Leiter mit Zulassung
$r = EventSchaetzung::schaetzen([$js], 3, 1, [$leiter(true)], null);
pruefe('J+S Training berechnet', $r['programme'][0]['status'], 'berechnet');

// Leiter-Schlüssel: 30 TN, 1 Leiter -> höchstens 12 anrechenbar
$v = EventSchaetzung::jsVoraussetzungen('training', 1, 30, 1, true);
pruefe('Leiter-Schlüssel', $v['teilnehmer'], 12);

// J+S Training mit zu wenig J+S-Teilnehmenden
$r = EventSchaetzung::schaetzen([$js], 10, 1, [$leiter(true)], 2);
pruefe('J+S zu wenige TN', $r['programme'][0]['status'], 'nicht_berechtigt');

// J+S Lager und J+S Training schliessen sich aus: Lager hat Vorrang
$jsLager = $js + [];
$jsLager['eventarten'] = [['eventart' => 'lager', 'multiplikator' => 1]];
$jsTraining = ['bezeichnung' => 'J+S Training'] + $js;
$jsTraining['eventarten'] = [['eventart' => 'training', 'multiplikator' => 1]];
$r = EventSchaetzung::schaetzen([$jsLager, $jsTraining], 20, 5, [$leiter(true), $leiter(true)], null);
pruefe('Lager+Training: Lager zählt',      $r['programme'][0]['status'], 'berechnet');
pruefe('Lager+Training: Training entfällt', $r['programme'][1]['status'], 'nicht_berechtigt');
pruefe('Lager+Training: Summe nur Lager',  $r['summe'], $r['programme'][0]['betrag']);
$r = EventSchaetzung::schaetzen([$jsTraining, $pauschal], 20, 5, [$leiter(true), $leiter(true)], null);
pruefe('Nur Training: bleibt (Rückfall)',  $r['programme'][0]['status'], 'berechnet');
pruefe('Nicht-J+S bleibt unberührt',       $r['programme'][1]['status'], 'berechnet');

// ZKS Ausbildungseinheit: auch im Training berechnet (6 Lektionen/Tag angenommen)
$zks = ['bezeichnung' => 'ZKS Ausbildung', 'foerderstelle' => 'ZKS', 'berechnungstyp' => 'zks_ausbildungseinheit',
        'betraege' => [['betrag_pro_einheit' => 2.5, 'max_lektionen_pro_tag' => 0]], 'trainerarten' => [], 'eventarten' => []];
$r = EventSchaetzung::schaetzen([$zks], 20, 2, [], null);
pruefe('ZKS Training berechnet', $r['programme'][0]['status'], 'berechnet');
pruefe('ZKS Training: 2.50 × (20×6×2)', $r['summe'], 600.0);
$zksLager = $zks;
$zksLager['eventarten'] = [['eventart' => 'lager', 'multiplikator' => 1]];
$r = EventSchaetzung::schaetzen([$zksLager], 20, 2, [], null);
pruefe('ZKS nur Lager erfasst: Training nicht berechtigt', $r['programme'][0]['status'], 'nicht_berechtigt');

echo $fehler === 0 ? "\nAlle Prüfungen bestanden.\n" : "\n$fehler Fehler.\n";
exit($fehler === 0 ? 0 : 1);
