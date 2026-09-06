<?php
// ---------------------------------------------------------------------
// Regressionstest für Subvention::vollstaendigkeit() – kein Framework,
// kein Composer, per `php tests/vollstaendigkeit_matrix.php` lauffähig
// (siehe Projekt-Konvention "per php -r testbar", CLAUDE.md).
//
// Prüft alle 2^4 Kombinationen der vier Vollständigkeits-Kriterien plus
// zwei Zusatzfälle für die ODER-Zweige (voraussetzungen statt
// beschreibung, antragsfrist statt subvention_fristen). Läuft komplett
// ohne Datenbank: vollstaendigkeit() nimmt betraege/fristen/historie
// direkt aus dem übergebenen Array, sobald die Schlüssel gesetzt sind
// (auch mit leerem Array), und greift dann nie auf db() zu.
//
// Zweck: Diese Kriterien sind in Budget::vollstaendigeSubventionenFuerEvents()
// im Class Manager Tool (anderes Repo) als reines SQL nachgebaut. Ändert
// sich hier die Logik, ohne dass jemand die SQL-Nachbildung anpasst, laufen
// beide Apps auseinander – die Doku-Notiz in CLAUDE.md warnt davor, dieser
// Test macht es zusätzlich sichtbar, wenn sich vollstaendigkeit() selbst
// unerwartet ändert (die Fixture-Tabelle unten ist die Referenz dafür).
//
// Nicht deployt: tests/ steht im Exclude von .github/workflows/deploy.yml.
// ---------------------------------------------------------------------

// vollstaendigkeit() braucht includes/Subvention.php, das wiederum
// includes/db.php requires, das wiederum config/config.php requires –
// auch wenn nie eine DB-Verbindung aufgebaut wird (db() wird hier nicht
// aufgerufen), muss die Datei zum Requiren existieren. Lokal ohne
// config.php (Normalfall, siehe CLAUDE.md "Lokales Dev-Setup") legen wir
// dafür eine Wegwerf-Version an und räumen sie danach wieder weg.
$configPath = __DIR__ . '/../config/config.php';
$configWarErstellt = false;
if (!file_exists($configPath)) {
    file_put_contents($configPath, <<<'PHP'
<?php
// Automatisch von tests/vollstaendigkeit_matrix.php erzeugt und wieder
// gelöscht – nie eine echte Verbindung, nur damit die require-Kette in
// includes/db.php nicht scheitert.
define('DB_HOST', 'unused');
define('DB_NAME', 'unused');
define('DB_USER', 'unused');
define('DB_PASS', 'unused');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'Testlauf');
define('APP_BASE_URL', 'http://localhost');
define('APP_ENV', 'development');
define('MAIL_FROM_ADDRESS', 'test@example.invalid');
define('MAIL_FROM_NAME', 'Testlauf');
PHP);
    $configWarErstellt = true;
}

require_once __DIR__ . '/../includes/Subvention.php';

function aufraeumen(string $configPath, bool $configWarErstellt): void {
    if ($configWarErstellt && file_exists($configPath)) {
        unlink($configPath);
    }
}
register_shutdown_function('aufraeumen', $configPath, $configWarErstellt);

$aktJahr = (int)date('Y');

// Baut ein $subv-Array für vollstaendigkeit(), das genau die vier
// Kriterien einzeln an-/ausschalten lässt.
function subv(bool $hatBetrag, bool $hatBeschreibung, bool $hatFrist, bool $hatAktuellenBetrag, int $aktJahr): array {
    return [
        'id'              => 0,
        'betraege'        => $hatBetrag ? [['id' => 1, 'grundbetrag' => 100]] : [],
        'beschreibung'    => $hatBeschreibung ? 'Testbeschreibung' : '',
        'voraussetzungen' => '',
        'fristen'         => $hatFrist ? [['id' => 1, 'bezeichnung' => 'Antrag']] : [],
        'antragsfrist'    => null,
        'historie'        => $hatAktuellenBetrag ? [['jahr' => $aktJahr, 'betrag' => 500]] : [],
    ];
}

$faelle = [];

// Alle 16 Kombinationen der vier Kriterien.
foreach ([false, true] as $hatBetrag) {
    foreach ([false, true] as $hatBeschreibung) {
        foreach ([false, true] as $hatFrist) {
            foreach ([false, true] as $hatAktuellenBetrag) {
                $label = sprintf(
                    'Betrag=%s Beschreibung=%s Frist=%s Jahresbetrag=%s',
                    $hatBetrag ? 'ja' : 'nein',
                    $hatBeschreibung ? 'ja' : 'nein',
                    $hatFrist ? 'ja' : 'nein',
                    $hatAktuellenBetrag ? 'ja' : 'nein'
                );
                $faelle[$label] = [
                    'subv'     => subv($hatBetrag, $hatBeschreibung, $hatFrist, $hatAktuellenBetrag, $aktJahr),
                    'erwartet' => $hatBetrag && $hatBeschreibung && $hatFrist && $hatAktuellenBetrag,
                ];
            }
        }
    }
}

// Zusatzfälle für die ODER-Zweige, die die Matrix oben nicht abdeckt:
// je ein sonst vollständiger Fall, bei dem nur der jeweilige Alternativweg
// gesetzt ist.
$vollstaendigBasis = subv(true, true, true, true, $aktJahr);

$nurVoraussetzungen = $vollstaendigBasis;
$nurVoraussetzungen['beschreibung']    = '';
$nurVoraussetzungen['voraussetzungen'] = 'Testvoraussetzung';
$faelle['Nur voraussetzungen statt beschreibung gesetzt'] = ['subv' => $nurVoraussetzungen, 'erwartet' => true];

$nurAntragsfrist = $vollstaendigBasis;
$nurAntragsfrist['fristen']      = [];
$nurAntragsfrist['antragsfrist'] = '2027-01-01';
$faelle['Nur antragsfrist statt subvention_fristen gesetzt'] = ['subv' => $nurAntragsfrist, 'erwartet' => true];

// Vorjahres-Betrag zählt noch als aktuell (jahr >= aktJahr - 1).
$vorjahr = $vollstaendigBasis;
$vorjahr['historie'] = [['jahr' => $aktJahr - 1, 'betrag' => 500]];
$faelle['Jahresbetrag aus dem Vorjahr zählt noch'] = ['subv' => $vorjahr, 'erwartet' => true];

// Betrag vor zwei Jahren zählt nicht mehr.
$vorvorjahr = $vollstaendigBasis;
$vorvorjahr['historie'] = [['jahr' => $aktJahr - 2, 'betrag' => 500]];
$faelle['Jahresbetrag von vor zwei Jahren zählt nicht mehr'] = ['subv' => $vorvorjahr, 'erwartet' => false];

$fehler = 0;
foreach ($faelle as $label => $fall) {
    $offeneePunkte = Subvention::vollstaendigkeit($fall['subv']);
    $tatsaechlich  = $offeneePunkte === [];
    $ok            = $tatsaechlich === $fall['erwartet'];
    if (!$ok) {
        $fehler++;
    }
    printf(
        "%s %-55s erwartet=%s tatsächlich=%s\n",
        $ok ? 'OK  ' : 'FEHL',
        $label,
        $fall['erwartet'] ? 'vollständig' : 'unvollständig',
        $tatsaechlich ? 'vollständig' : 'unvollständig'
    );
}

printf("\n%d von %d Fällen bestanden.\n", count($faelle) - $fehler, count($faelle));

if ($fehler > 0) {
    echo "\nSubvention::vollstaendigkeit() hat sich geändert – bitte auch\n";
    echo "Budget::vollstaendigeSubventionenFuerEvents() im Class Manager Tool\n";
    echo "(includes/Budget.php dort) und diese Fixture-Tabelle anpassen.\n";
    exit(1);
}

exit(0);
