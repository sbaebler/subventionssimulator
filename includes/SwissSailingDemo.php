<?php
// ---------------------------------------------------------------------
// Fiktive Demo-Daten für den Swiss-Sailing-Rapport (Auswahl «Daten» auf
// swisssailing.php). Reine Daten: nichts wird aus der DB gelesen oder in
// sie geschrieben.
//
// Alle Namen sind erfunden, E-Mails enden auf example.com, die AHV-Nummern
// beginnen mit 756.0000. (gültige Prüfziffer, erkennbar fiktiv).
// ---------------------------------------------------------------------

class SwissSailingDemo {

    public const SZENARIEN = [
        'komplett' => 'Demo: Ein Lager, alles vollständig',
        'luecken'  => 'Demo: Ein Lager mit Lücken und Sonderfällen',
        'saison'   => 'Demo: Ganze Saison (3 Lager, eines mit über 45 Personen)',
    ];

    public const KOPF = [
        'organisator'    => 'DEMO – Zurich Sailing Federation',
        'verantwortlich' => 'Demo Person',
        'kontakt'        => 'demo@example.com',
    ];

    private const VORNAMEN = [
        ['Lia', 'F'], ['Noah', 'M'], ['Mia', 'F'], ['Luca', 'M'], ['Elin', 'F'], ['Finn', 'M'],
        ['Nora', 'F'], ['Jari', 'M'], ['Alva', 'F'], ['Timo', 'M'], ['Yara', 'F'], ['Nils', 'M'],
    ];
    private const NACHNAMEN = [
        'Wellenreiter', 'Windfang', 'Segeltuch', 'Pinnenberg', 'Bugwelle', 'Mastfuss', 'Lotsmann',
        'Ankerli', 'Fockner', 'Halsemann', 'Kielwasser', 'Bojenhof', 'Schotter', 'Luvmann',
    ];
    private const CLUBS = ['SC Musterhafen', 'YC Demoried', 'Segelclub Beispielsee'];

    // Events eines Szenarios in der Form von SwissSailingRapport::events(),
    // ergänzt um 'zeilen' (Anmeldungen), 'zusatz' und 'coaches'.
    public static function events(string $szenario, int $jahr): array {
        $coaches = [
            ['name' => 'Cora Demo-Coach', 'email' => 'cora@example.com'],
            ['name' => 'Beat Demo-Coach', 'email' => 'beat@example.com'],
        ];

        if ($szenario === 'komplett') {
            return [self::event(9001, 'Sommerlager am Zermattersee (Demo)', 'Zermatt', "$jahr-07-06", "$jahr-07-10",
                'Testoli', $coaches, self::personen(0, 14, $jahr, 'Testoli'))];
        }

        if ($szenario === 'luecken') {
            $p = self::personen(20, 12, $jahr, 'Testoli');
            $p[0]['geschlecht'] = null;
            $p[1]['ahv_nr'] = '756.0000.0000.00';          // falsche Prüfziffer
            $p[2]['swisssailing_nr'] = null;
            $p[3]['ahv_nr'] = null;
            $p[4]['jahrgang'] = null;
            $p[5]['segelclub'] = null;
            $p[6]['jahrgang'] = $jahr - 22;                // über U19, fällt weg
            $event = self::event(9002, 'Pfingstlager am Schirmensee (Demo)', 'Schirmensee', "$jahr-05-23", "$jahr-05-25",
                'Testoli', [$coaches[0], ['name' => 'Gast Demo-Coach', 'email' => null]], $p);
            foreach ([7, 8] as $i) {                       // Talent Card, fällt weg
                $event['zusatz'][SwissSailingRapport::schluessel(
                    $p[$i]['email_norm'], $p[$i]['vorname'], $p[$i]['nachname']
                )] = ['geschlecht' => null, 'lizenz_nr' => null, 'js_nr' => null, 'segelklasse' => null, 'hat_sotc' => 1];
            }
            return [$event];
        }

        if ($szenario === 'saison') {
            $gemischt = self::personen(40, 10, $jahr, 'ILCA');
            foreach ($gemischt as $i => &$p) {
                $p['segelklasse'] = ['ILCA', 'Optimist', '420'][$i % 3];
            }
            unset($p);
            return [
                self::event(9003, 'Frühlingstraining (Demo)', 'Imperia', "$jahr-04-13", "$jahr-04-17",
                    'Testoli', $coaches, self::personen(0, 14, $jahr, 'Testoli')),
                self::event(9004, 'Grosses Sommerlager (Demo)', 'Thalwil', "$jahr-07-13", "$jahr-07-17",
                    'Testoli', $coaches, self::personen(60, 52, $jahr, 'Testoli')),
                self::event(9005, 'Klassenübergreifendes Herbstlager (Demo)', 'Stäfa', "$jahr-10-05", "$jahr-10-09",
                    'Klassenübergreifend', $coaches, $gemischt, true),
            ];
        }

        return [];
    }

    private static function event(int $id, string $name, string $ort, string $von, string $bis,
                                  string $klasse, array $coaches, array $zeilen, bool $uebergreifend = false): array {
        return [
            'id' => $id, 'bezeichnung' => $name, 'ort' => $ort, 'datum_von' => $von, 'datum_bis' => $bis,
            'klasse_bezeichnung' => $klasse, 'ist_uebergreifend' => $uebergreifend ? 1 : 0,
            'anwesend' => count($zeilen), 'zeilen' => $zeilen, 'zusatz' => [], 'coaches' => $coaches,
        ];
    }

    // $anzahl erfundene Anmeldungen ab laufender Nummer $ab (gleiche Nummer =
    // gleiche Person, so wiederholen sich Teilnehmende über Events).
    private static function personen(int $ab, int $anzahl, int $jahr, string $klasse): array {
        $personen = [];
        for ($n = $ab; $n < $ab + $anzahl; $n++) {
            [$vorname, $geschlecht] = self::VORNAMEN[$n % count(self::VORNAMEN)];
            $nachname = self::NACHNAMEN[($n + intdiv($n, count(self::NACHNAMEN))) % count(self::NACHNAMEN)];
            $personen[] = [
                'vorname' => $vorname, 'nachname' => $nachname,
                'email_norm' => 'demo' . $n . '@example.com',
                'jahrgang' => $jahr - 10 - ($n % 8),
                'segelclub' => self::CLUBS[$n % count(self::CLUBS)],
                'geschlecht' => $geschlecht,
                'ahv_nr' => self::ahv($n + 1),
                'swisssailing_nr' => sprintf('DEMO-%04d', $n + 1),
                'segelklasse' => $klasse,
            ];
        }
        return $personen;
    }

    // 756.0000.xxxx.xP mit korrekter EAN-13-Prüfziffer.
    private static function ahv(int $n): string {
        $z = '756' . sprintf('%09d', $n);
        $summe = 0;
        for ($i = 0; $i < 12; $i++) {
            $summe += (int)$z[$i] * ($i % 2 ? 3 : 1);
        }
        $z .= (10 - $summe % 10) % 10;
        return substr($z, 0, 3) . '.' . substr($z, 3, 4) . '.' . substr($z, 7, 4) . '.' . substr($z, 11);
    }
}
