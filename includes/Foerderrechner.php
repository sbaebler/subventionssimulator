<?php
// ---------------------------------------------------------------------
// Rechenkern der Förderprogramme: reine Formeln, ohne Datenbankzugriff.
//
// ACHTUNG: Diese Datei liegt IDENTISCH in beiden Repos
//   - Subventionssimulator: includes/Foerderrechner.php  (Original)
//   - Class Manager Tool:   includes/Foerderrechner.php  (Kopie)
// Änderungen immer im Subventionssimulator machen und die Datei 1:1
// ins Class Manager Tool kopieren. `php tests/foerderrechner.php` prüft
// im Subventionssimulator die Formeln; im Class Manager Tool prüft
// tests/foerderrechner_sync.php, dass beide Dateien gleich sind.
//
// Bewusst keine Abhängigkeiten (kein db(), kein Event), damit sich die
// Datei in beiden Apps laden lässt.
//
// Jede Formel gibt [Betrag, Aufschlüsselung] zurück. Die Aufschlüsselung
// ist eine Liste von ['label' =>, 'wert' =>, 'format' => 'chf'|'zahl'|'faktor'].
// ---------------------------------------------------------------------

class Foerderrechner {

    // Anrechenbare Menge unter Berücksichtigung einer Obergrenze (0 = kein Limit)
    public static function begrenzt(int $wert, int $max): int {
        return ($max > 0) ? min($wert, $max) : $wert;
    }

    // Einstieg: wählt anhand des Berechnungstyps die Formel und deckelt auf
    // betrag_max_gesamt (0 = kein Limit). $regel ist die erste Zeile aus
    // subvention_betraege.
    //
    // $p: 'uebernachtung' bool, 'stunden_pro_tag' int, 'lektionen_pro_tag' int,
    //     'anzahl_einheiten' float
    public static function betrag(
        string $typ, array $regel, int $tn, int $tage, array $p = [],
        ?array $trainerRow = null, ?array $eventRow = null
    ): array {
        [$betrag, $auf] = match ($typ) {
            'js_teilnehmertag'       => self::teilnehmertag($regel, $tn, $tage, !empty($p['uebernachtung'])),
            'js_teilnehmerstunde'    => self::teilnehmerstunde($regel, $tn, $tage, max(0, (int)($p['stunden_pro_tag'] ?? 0))),
            'zks_ausbildungseinheit' => self::ausbildungseinheit($regel, $tn, $tage, max(0, (int)($p['lektionen_pro_tag'] ?? 0))),
            'pauschale'              => self::pauschale($regel),
            'jahresbeitrag'          => self::jahresbeitrag($regel, (float)($p['anzahl_einheiten'] ?? 0)),
            default                  => self::additiv($regel, $tn, $tage, $trainerRow, $eventRow),
        };

        $maxGesamt = (float)($regel['betrag_max_gesamt'] ?? 0);
        if ($maxGesamt > 0 && $betrag > $maxGesamt) {
            $betrag = $maxGesamt;
            $auf[] = ['label' => 'Deckelung auf Maximalbetrag', 'wert' => $maxGesamt, 'format' => 'chf'];
        }
        return [$betrag, $auf];
    }

    // additiv: Grundbetrag + pro TN + pro Tag + Trainer-Zusatz, × Eventart-Faktor
    public static function additiv(array $r, int $tn, int $tage, ?array $trainerRow, ?array $eventRow): array {
        $effTN   = self::begrenzt($tn,   (int)($r['max_teilnehmer'] ?? 0));
        $effTage = self::begrenzt($tage, (int)($r['max_tage'] ?? 0));

        $grund   = (float)($r['grundbetrag'] ?? 0);
        $tnAnt   = (float)($r['betrag_pro_teilnehmer'] ?? 0) * $effTN;
        $tageAnt = (float)($r['betrag_pro_tag'] ?? 0) * $effTage;
        $zusatz  = (float)($trainerRow['zusatzbetrag'] ?? 0);
        $faktor  = (float)($eventRow['multiplikator'] ?? 1);

        $betrag = ($grund + $tnAnt + $tageAnt + $zusatz) * $faktor;

        $auf = [
            ['label' => 'Grundbetrag',       'wert' => $grund,   'format' => 'chf'],
            ['label' => 'Teilnehmer-Anteil', 'wert' => $tnAnt,   'format' => 'chf'],
            ['label' => 'Tages-Anteil',      'wert' => $tageAnt, 'format' => 'chf'],
            ['label' => 'Trainer-Bonus',     'wert' => $zusatz,  'format' => 'chf'],
        ];
        if ($faktor != 1.0) {
            $auf[] = ['label' => 'Eventart-Faktor', 'wert' => $faktor, 'format' => 'faktor'];
        }
        return [$betrag, $auf];
    }

    // js_teilnehmertag: Satz × TN × Tage (Satz je nach Übernachtung)
    public static function teilnehmertag(array $r, int $tn, int $tage, bool $uebernachtung): array {
        $satz    = $uebernachtung
            ? (float)($r['satz_mit_uebernachtung'] ?? 0)
            : (float)($r['satz_ohne_uebernachtung'] ?? 0);
        $effTN   = self::begrenzt($tn,   (int)($r['max_teilnehmer'] ?? 0));
        $effTage = self::begrenzt($tage, (int)($r['max_tage'] ?? 0));

        $betrag = $satz * $effTN * $effTage;

        $auf = [
            ['label' => 'Satz pro Teilnehmer/Tag (' . ($uebernachtung ? 'mit' : 'ohne') . ' Übernachtung)', 'wert' => $satz, 'format' => 'chf'],
            ['label' => 'Anrechenbare Teilnehmer', 'wert' => $effTN,   'format' => 'zahl'],
            ['label' => 'Anrechenbare Tage',       'wert' => $effTage, 'format' => 'zahl'],
        ];
        return [$betrag, $auf];
    }

    // js_teilnehmerstunde: Satz × TN × Tage × Stunden/Tag
    public static function teilnehmerstunde(array $r, int $tn, int $tage, int $stunden): array {
        $satz       = (float)($r['betrag_pro_stunde'] ?? 0);
        $effTN      = self::begrenzt($tn,      (int)($r['max_teilnehmer'] ?? 0));
        $effTage    = self::begrenzt($tage,    (int)($r['max_tage'] ?? 0));
        $effStunden = self::begrenzt($stunden, (int)($r['max_stunden_pro_tag'] ?? 0));

        $betrag = $satz * $effTN * $effTage * $effStunden;

        $auf = [
            ['label' => 'Satz pro Teilnehmerstunde', 'wert' => $satz,       'format' => 'chf'],
            ['label' => 'Anrechenbare Teilnehmer',   'wert' => $effTN,      'format' => 'zahl'],
            ['label' => 'Anrechenbare Tage',         'wert' => $effTage,    'format' => 'zahl'],
            ['label' => 'Anrechenbare Stunden/Tag',  'wert' => $effStunden, 'format' => 'zahl'],
        ];
        return [$betrag, $auf];
    }

    // zks_ausbildungseinheit: Satz × Ausbildungseinheiten (= TN × Lektionen/Tag × Tage)
    public static function ausbildungseinheit(array $r, int $tn, int $tage, int $lektionen): array {
        $satz         = (float)($r['betrag_pro_einheit'] ?? 0);
        $effTN        = self::begrenzt($tn,        (int)($r['max_teilnehmer'] ?? 0));
        $effTage      = self::begrenzt($tage,      (int)($r['max_tage'] ?? 0));
        $effLektionen = self::begrenzt($lektionen, (int)($r['max_lektionen_pro_tag'] ?? 0));

        $einheiten = $effTN * $effLektionen * $effTage;
        $betrag    = $satz * $einheiten;

        $auf = [
            ['label' => 'Satz pro Ausbildungseinheit', 'wert' => $satz,         'format' => 'chf'],
            ['label' => 'Anrechenbare Teilnehmer',     'wert' => $effTN,        'format' => 'zahl'],
            ['label' => 'Anrechenbare Lektionen/Tag',  'wert' => $effLektionen, 'format' => 'zahl'],
            ['label' => 'Anrechenbare Tage',           'wert' => $effTage,      'format' => 'zahl'],
            ['label' => 'Ausbildungseinheiten',        'wert' => $einheiten,    'format' => 'zahl'],
        ];
        return [$betrag, $auf];
    }

    // pauschale: fixer Betrag (im Grundbetrag hinterlegt)
    public static function pauschale(array $r): array {
        $betrag = (float)($r['grundbetrag'] ?? 0);
        return [$betrag, [
            ['label' => 'Pauschalbetrag', 'wert' => $betrag, 'format' => 'chf'],
        ]];
    }

    // jahresbeitrag: Satz pro Einheit × Anzahl Einheiten. Ohne Einheiten fällt
    // der Beitrag auf den Pauschalbetrag (Grundbetrag) als Referenz zurück.
    public static function jahresbeitrag(array $r, float $einheiten = 0.0): array {
        $satz = (float)($r['betrag_pro_einheit'] ?? 0);
        if ($satz > 0 && $einheiten > 0) {
            $betrag = $satz * $einheiten;
            return [$betrag, [
                ['label' => 'Betrag pro Einheit', 'wert' => $satz,      'format' => 'chf'],
                ['label' => 'Anzahl Einheiten',   'wert' => $einheiten, 'format' => 'zahl'],
            ]];
        }
        $betrag = (float)($r['grundbetrag'] ?? 0);
        return [$betrag, [
            ['label' => 'Pauschalbetrag (Referenz)', 'wert' => $betrag, 'format' => 'chf'],
        ]];
    }
}
