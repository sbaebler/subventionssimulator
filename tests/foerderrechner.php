<?php
// Prüft die Formeln in includes/Foerderrechner.php (ohne DB):
//   php tests/foerderrechner.php
// Dieselbe Datei liegt im Class Manager Tool; dort prüft
// tests/foerderrechner_sync.php, dass beide Kopien identisch sind.

require_once __DIR__ . '/../includes/Foerderrechner.php';

$fehler = 0;
function pruefe(string $name, float $ist, float $soll): void {
    global $fehler;
    if (abs($ist - $soll) > 0.005) { $fehler++; echo "FEHLER $name: $ist statt $soll\n"; }
    else echo "ok     $name\n";
}

$add = ['grundbetrag' => 100, 'betrag_pro_teilnehmer' => 10, 'betrag_pro_tag' => 20];
pruefe('additiv',             Foerderrechner::betrag('additiv', $add, 20, 5)[0], 400.0);
pruefe('additiv Trainer',     Foerderrechner::betrag('additiv', $add, 20, 5, [], ['zusatzbetrag' => 50])[0], 450.0);
pruefe('additiv Faktor',      Foerderrechner::betrag('additiv', $add, 20, 5, [], null, ['multiplikator' => 1.2])[0], 480.0);
pruefe('additiv begrenzt',    Foerderrechner::betrag('additiv', $add + ['max_teilnehmer' => 10, 'max_tage' => 2], 20, 5)[0], 240.0);
pruefe('additiv Deckelung',   Foerderrechner::betrag('additiv', $add + ['betrag_max_gesamt' => 300], 20, 5)[0], 300.0);

$tag = ['satz_mit_uebernachtung' => 16, 'satz_ohne_uebernachtung' => 8];
pruefe('teilnehmertag mit',   Foerderrechner::betrag('js_teilnehmertag', $tag, 10, 4, ['uebernachtung' => 1])[0], 640.0);
pruefe('teilnehmertag ohne',  Foerderrechner::betrag('js_teilnehmertag', $tag, 10, 4)[0], 320.0);

pruefe('teilnehmerstunde',    Foerderrechner::betrag('js_teilnehmerstunde', ['betrag_pro_stunde' => 1.5, 'max_stunden_pro_tag' => 3], 10, 2, ['stunden_pro_tag' => 5])[0], 90.0);
pruefe('ausbildungseinheit',  Foerderrechner::betrag('zks_ausbildungseinheit', ['betrag_pro_einheit' => 2.8, 'max_lektionen_pro_tag' => 6], 20, 5, ['lektionen_pro_tag' => 8])[0], 1680.0);
pruefe('pauschale',           Foerderrechner::betrag('pauschale', ['grundbetrag' => 777], 1, 1)[0], 777.0);
pruefe('jahresbeitrag Einh.', Foerderrechner::betrag('jahresbeitrag', ['betrag_pro_einheit' => 2, 'grundbetrag' => 9], 1, 1, ['anzahl_einheiten' => 300])[0], 600.0);
pruefe('jahresbeitrag Ref.',  Foerderrechner::betrag('jahresbeitrag', ['betrag_pro_einheit' => 2, 'grundbetrag' => 9], 1, 1)[0], 9.0);

exit($fehler ? 1 : 0);
