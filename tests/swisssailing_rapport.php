<?php
// Prüft die Regeln und die Excel-Erzeugung des Swiss-Sailing-Rapports ohne DB:
//   php tests/swisssailing_rapport.php

$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    file_put_contents($configPath, "<?php\ndefine('DB_HOST','x');define('DB_NAME','x');define('DB_USER','x');"
        . "define('DB_PASS','x');define('DB_CHARSET','utf8mb4');define('APP_NAME','Testlauf');"
        . "define('APP_BASE_URL','http://localhost');define('APP_ENV','development');\n");
    register_shutdown_function(static fn() => @unlink(__DIR__ . '/../config/config.php'));
}

require_once __DIR__ . '/../includes/SwissSailingRapport.php';

$fehler = 0;
function pruefe(string $name, $ist, $soll): void {
    global $fehler;
    if ($ist === $soll) { echo "ok     $name\n"; return; }
    $fehler++;
    echo "FEHLER $name: " . var_export($ist, true) . ' statt ' . var_export($soll, true) . "\n";
}
$S = SwissSailingRapport::class;

pruefe('U19: 18 zählt',        $S::juniorStatus(2008, 2026), 'ja');
pruefe('U19: 19 zählt nicht',  $S::juniorStatus(2007, 2026), 'nein');
pruefe('ohne Jahrgang',        $S::juniorStatus(null, 2026), 'unbekannt');
pruefe('Schlüssel ignoriert Schreibweise', $S::schluessel('A@x.ch ', 'Eva', 'Wyss'), $S::schluessel('a@x.ch', 'eva', 'wyss'));
pruefe('Geschwister getrennt', $S::schluessel('e@x.ch', 'Eva', 'Wyss') === $S::schluessel('e@x.ch', 'Tim', 'Wyss'), false);

$a = fn(string $v, ?int $jg, int $sotc = 0) => ['vorname' => $v, 'nachname' => 'Test', 'jahrgang' => $jg,
    'hat_sotc' => $sotc, 'geschlecht' => 'F', 'segelclub' => 'SC <A&B>', 'lizenz_nr' => '=1+1', 'js_nr' => '', 'klasse' => 'ILCA'];
$gefiltert = $S::filtern([$a('Eva', 2012), $a('Alt', 1990), $a('Kader', 2012, 1), $a('Neu', null)], 2026);
pruefe('Filter: ohne SOTC, U19', array_column($gefiltert, 'vorname'), ['Eva', 'Neu']);
pruefe('fehlende Angaben', $S::fehlendeAngaben($a('Eva', 2012)), ['J+S-Nr.']);

$event = ['id' => 7, 'bezeichnung' => 'Zürcher Lager 1', 'datum_von' => '2026-07-06', 'datum_bis' => '2026-07-10', 'ort' => 'Thalwil'];
pruefe('Dateiname', $S::dateiname($event), '2026-07-06_Zuercher_Lager_1_7');
$kopf = ['organisator' => 'ZSF', 'verantwortlich' => 'V', 'kontakt' => 'k@x.ch'];
$viele = array_fill(0, 50, $a('Eva', 2012));
$zellen = $S::zellen($event, $kopf, array_fill(0, 6, ['name' => 'Coach', 'email' => null]), $viele);
pruefe('höchstens 4 Coaches', isset($zellen['B14']), false);
pruefe('höchstens 45 Athleten', [isset($zellen['B61']), isset($zellen['B62'])], [true, false]);

// Jede Zielzelle muss in der Vorlage existieren (xlsx() wirft sonst).
$datei = tempnam(sys_get_temp_dir(), 't');
file_put_contents($datei, $S::xlsx($zellen));
$zip = new ZipArchive();
pruefe('xlsx lesbar', $zip->open($datei), true);
$xml = $zip->getFromName('xl/worksheets/sheet1.xml');
pruefe('XML gültig', simplexml_load_string($xml) !== false, true);
pruefe('Sonderzeichen maskiert', str_contains($xml, 'SC &lt;A&amp;B&gt;'), true);
pruefe('keine Formel aus Eingabe', str_contains($xml, '<f>'), false);
pruefe('Logo bleibt', $zip->locateName('xl/media/image1.png') !== false, true);
$zip->close();
if ($ziel = getenv('MUSTER')) copy($datei, $ziel);
unlink($datei);

echo $fehler ? "\n$fehler Fehler\n" : "\nAlles ok\n";
exit($fehler ? 1 : 0);
