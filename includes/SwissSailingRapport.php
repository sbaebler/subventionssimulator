<?php
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------
// «Promotion camp activity»-Rapport an Swiss Sailing (Regionenbeitrag):
// ein Formular pro Förderlager mit Coaches und den Junioren ohne Swiss
// Olympic Talent Card (SOTC).
//
// cm_* wird nur gelesen. Geschlecht, Lizenz-Nr., AHV-Nr. (= J+S-Nr.) und
// Segelklasse kommen aus der Anmeldung (cm_teilnehmer); Korrekturen und
// SOTC liegen in swisssailing_athleten und haben Vorrang.
// ---------------------------------------------------------------------

class SwissSailingRapport {

    public const ORGANISATOR = 'Zurich Sailing Federation';
    public const MAX_ALTER = 18;          // U19: Jahrgang des Lagerjahres
    public const MAX_ATHLETEN = 45;       // Zeilen 17–61 der Vorlage
    public const MAX_COACHES = 4;         // Zeilen 10–13
    public const VORLAGE = __DIR__ . '/vorlagen/swisssailing_promotion_activity.xlsx';
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    // ---- reine Regeln (ohne DB) -------------------------------------

    // Identität einer Person über Events hinweg. Vor- und Nachname gehören
    // dazu, weil Geschwister dieselbe (Eltern-)E-Mail haben können.
    public static function schluessel(string $email, string $vorname, string $nachname): string {
        $teile = array_map(fn($t) => mb_strtolower(trim($t)), [$email, $vorname, $nachname]);
        return hash('sha256', implode("\n", $teile));
    }

    // 'ja' | 'nein' | 'unbekannt' (ohne Jahrgang)
    public static function juniorStatus(?int $jahrgang, int $lagerjahr, int $maxAlter = self::MAX_ALTER): string {
        if (!$jahrgang) return 'unbekannt';
        return ($lagerjahr - $jahrgang) <= $maxAlter ? 'ja' : 'nein';
    }

    // Wer ins Formular kommt: ohne SOTC und nicht älter als die Grenze.
    // Unbekannter Jahrgang bleibt drin (wird als fehlend ausgewiesen).
    public static function filtern(array $athleten, int $lagerjahr, int $maxAlter = self::MAX_ALTER): array {
        return array_values(array_filter($athleten, fn($a) =>
            empty($a['hat_sotc'])
            && self::juniorStatus(isset($a['jahrgang']) ? (int)$a['jahrgang'] : null, $lagerjahr, $maxAlter) !== 'nein'
        ));
    }

    // Die J+S-Nummer ist die AHV-Nummer. Gültig (756, 13 Ziffern, Prüfziffer
    // EAN-13) ergibt die Schreibweise 756.1234.5678.97, sonst null.
    public static function ahvNummer(string $eingabe): ?string {
        $z = preg_replace('/[\s.]/', '', $eingabe);
        if (!preg_match('/^756\d{10}$/', $z)) return null;
        $summe = 0;
        for ($i = 0; $i < 12; $i++) {
            $summe += (int)$z[$i] * ($i % 2 ? 3 : 1);
        }
        if ((10 - $summe % 10) % 10 !== (int)$z[12]) return null;
        return substr($z, 0, 3) . '.' . substr($z, 3, 4) . '.' . substr($z, 7, 4) . '.' . substr($z, 11);
    }

    // Was ins Formular kommt: Erfasstes ($zusatz) vor Anmeldung ($anmeldung),
    // bei der Klasse zuletzt die des Events. Rein.
    public static function wirksam(array $anmeldung, array $zusatz, string $eventKlasse): array {
        $erster = fn(?string ...$w) => array_values(array_filter($w, fn($x) => trim((string)$x) !== ''))[0] ?? null;
        $ahv = $erster($zusatz['js_nr'] ?? null, $anmeldung['ahv_nr'] ?? null);
        return [
            'geschlecht' => $erster($zusatz['geschlecht'] ?? null, $anmeldung['geschlecht'] ?? null),
            'lizenz_nr'  => $erster($zusatz['lizenz_nr'] ?? null, $anmeldung['swisssailing_nr'] ?? null),
            'js_nr'      => $ahv === null ? null : (self::ahvNummer($ahv) ?? $ahv),
            'klasse'     => (string)$erster($zusatz['segelklasse'] ?? null, $anmeldung['segelklasse'] ?? null, $eventKlasse),
        ];
    }

    // Namen der leeren Pflichtangaben des Formulars.
    public static function fehlendeAngaben(array $a): array {
        $felder = [
            'jahrgang' => 'Jahrgang', 'geschlecht' => 'Geschlecht', 'segelclub' => 'Club',
            'lizenz_nr' => 'Lizenz-Nr.', 'js_nr' => 'J+S-/AHV-Nr.', 'klasse' => 'Segelklasse',
        ];
        $fehlt = array_values(array_filter($felder, fn($f) => empty($a[$f]), ARRAY_FILTER_USE_KEY));
        if (!empty($a['js_nr']) && self::ahvNummer((string)$a['js_nr']) === null) {
            $fehlt[] = 'AHV-Nr. ungültig';
        }
        return $fehlt;
    }

    public static function datumText(string $von, string $bis): string {
        $v = date('d.m.Y', strtotime($von));
        $b = date('d.m.Y', strtotime($bis));
        return $v === $b ? $v : "$v – $b";
    }

    public static function dateiname(array $event): string {
        $name = strtr($event['bezeichnung'], ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue']);
        $name = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_') ?: 'Event';
        return substr($event['datum_von'], 0, 10) . '_' . substr($name, 0, 60) . '_' . (int)$event['id'];
    }

    // ---- Daten ------------------------------------------------------

    // Durchgeführte Breitensport-Events eines Jahres (ohne Demo-Klasse).
    public static function events(int $jahr): array {
        $stmt = db()->prepare("
            SELECT e.id, e.bezeichnung, e.ort, e.datum_von, e.datum_bis,
                   k.bezeichnung AS klasse_bezeichnung, k.ist_uebergreifend,
                   (SELECT COUNT(*) FROM cm_teilnehmer t WHERE t.event_id = e.id AND t.anwesend = 1) AS anwesend
            FROM cm_events e
            JOIN cm_klassen k ON k.id = e.klasse_id
            WHERE e.kategorie = 'breitensport' AND k.ist_demo = 0
              AND e.status IN ('durchgefuehrt', 'abgeschlossen')
              AND YEAR(e.datum_von) = ?
            ORDER BY e.datum_von, e.id
        ");
        $stmt->execute([$jahr]);
        return $stmt->fetchAll();
    }

    // Anwesende eines Events. Die Felder geschlecht/lizenz_nr/js_nr/klasse
    // sind die wirksamen Werte, 'erfasst' die hier gespeicherten Korrekturen,
    // 'vorgabe' das, was ohne Korrektur gälte.
    public static function athleten(array $event): array {
        $stmt = db()->prepare('
            SELECT vorname, nachname, email_norm, jahrgang, segelclub,
                   geschlecht, ahv_nr, swisssailing_nr, segelklasse
            FROM cm_teilnehmer
            WHERE event_id = ? AND anwesend = 1
            ORDER BY nachname, vorname
        ');
        $stmt->execute([$event['id']]);
        $zeilen = $stmt->fetchAll();
        foreach ($zeilen as &$z) {
            $z['schluessel'] = self::schluessel($z['email_norm'], $z['vorname'], $z['nachname']);
        }
        unset($z);

        $zusatz = self::zusatzangaben(array_column($zeilen, 'schluessel'));
        // Bei klassenübergreifenden Events ist die Klasse pro Person offen.
        $eventKlasse = empty($event['ist_uebergreifend']) ? $event['klasse_bezeichnung'] : '';
        return array_map(function (array $z) use ($zusatz, $eventKlasse): array {
            $erfasst = $zusatz[$z['schluessel']]
                ?? ['geschlecht' => null, 'lizenz_nr' => null, 'js_nr' => null, 'segelklasse' => null, 'hat_sotc' => 0];
            return array_merge($z, self::wirksam($z, $erfasst, $eventKlasse), [
                'hat_sotc' => $erfasst['hat_sotc'],
                'erfasst'  => $erfasst,
                'vorgabe'  => self::wirksam($z, [], $eventKlasse),
            ]);
        }, $zeilen);
    }

    private static function zusatzangaben(array $schluessel): array {
        if (!$schluessel) return [];
        $platzhalter = implode(',', array_fill(0, count($schluessel), '?'));
        $stmt = db()->prepare("
            SELECT schluessel, geschlecht, lizenz_nr, js_nr, segelklasse, hat_sotc
            FROM swisssailing_athleten WHERE schluessel IN ($platzhalter)
        ");
        $stmt->execute($schluessel);
        $ergebnis = [];
        foreach ($stmt->fetchAll() as $z) {
            $s = $z['schluessel'];
            unset($z['schluessel']);
            $ergebnis[$s] = $z;
        }
        return $ergebnis;
    }

    // $eingaben: [schluessel => ['geschlecht','lizenz_nr','js_nr','segelklasse','hat_sotc']]
    public static function speichern(array $eingaben, ?int $benutzerId): void {
        $setzen = db()->prepare('
            INSERT INTO swisssailing_athleten
              (schluessel, geschlecht, lizenz_nr, js_nr, segelklasse, hat_sotc, geaendert_von)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              geschlecht = VALUES(geschlecht), lizenz_nr = VALUES(lizenz_nr), js_nr = VALUES(js_nr),
              segelklasse = VALUES(segelklasse), hat_sotc = VALUES(hat_sotc), geaendert_von = VALUES(geaendert_von)
        ');
        $loeschen = db()->prepare('DELETE FROM swisssailing_athleten WHERE schluessel = ?');
        $text = fn($w, int $max) => mb_substr(trim((string)$w), 0, $max) ?: null;

        db()->beginTransaction();
        try {
            foreach ($eingaben as $schluessel => $e) {
                if (!preg_match('/^[0-9a-f]{64}$/', (string)$schluessel) || !is_array($e)) continue;
                $werte = [
                    in_array($e['geschlecht'] ?? '', ['M', 'F'], true) ? $e['geschlecht'] : null,
                    $text($e['lizenz_nr'] ?? '', 30),
                    self::ahvNummer((string)($e['js_nr'] ?? '')) ?? $text($e['js_nr'] ?? '', 30),
                    $text($e['segelklasse'] ?? '', 50),
                    empty($e['hat_sotc']) ? 0 : 1,
                ];
                if (array_filter($werte)) {
                    $setzen->execute([$schluessel, ...$werte, $benutzerId]);
                } else {
                    $loeschen->execute([$schluessel]);   // keine leeren Zeilen behalten
                }
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }

    // Eingesetzte Personen laut Personalaufwand (E-Mail nur mit Konto).
    public static function coaches(int $eventId): array {
        $stmt = db()->prepare('
            SELECT p.person_name AS name, u.email
            FROM cm_event_personalaufwand p
            LEFT JOIN cm_users u ON u.id = p.benutzer_id
            WHERE p.event_id = ?
            ORDER BY p.id
        ');
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    // ---- Excel ------------------------------------------------------

    // Zellwerte eines Formulars: [Zellbezug => Wert]. Rein.
    public static function zellen(array $event, array $kopf, array $coaches, array $athleten): array {
        $z = [
            'C2' => $kopf['organisator'],
            'C3' => $kopf['verantwortlich'],
            'C4' => $kopf['kontakt'],
            'C5' => self::datumText($event['datum_von'], $event['datum_bis']),
            'C6' => (string)($event['ort'] ?? ''),
            'C7' => count($athleten),
        ];
        foreach (array_slice($coaches, 0, self::MAX_COACHES) as $i => $c) {
            $z['B' . (10 + $i)] = $c['name'];
            $z['C' . (10 + $i)] = (string)($c['email'] ?? '');
        }
        foreach (array_slice($athleten, 0, self::MAX_ATHLETEN) as $i => $a) {
            $r = 17 + $i;
            $z["B$r"] = trim($a['vorname'] . ' ' . $a['nachname']);
            $z["C$r"] = $a['jahrgang'] ? (int)$a['jahrgang'] : '';
            $z["D$r"] = (string)($a['geschlecht'] ?? '');
            $z["E$r"] = (string)($a['segelclub'] ?? '');
            $z["F$r"] = (string)($a['lizenz_nr'] ?? '');
            $z["G$r"] = (string)($a['js_nr'] ?? '');
            $z["H$r"] = (string)($a['klasse'] ?? '');
        }
        return $z;
    }

    // Füllt die Originalvorlage; Formatierung und Logo bleiben. Gibt den
    // Dateiinhalt zurück.
    public static function xlsx(array $zellen): string {
        $tmp = tempnam(sys_get_temp_dir(), 'sws');
        try {
            if (!copy(self::VORLAGE, $tmp)) throw new RuntimeException('Vorlage nicht lesbar.');
            $zip = new ZipArchive();
            if ($zip->open($tmp) !== true) throw new RuntimeException('Vorlage nicht lesbar.');

            $dom = new DOMDocument();
            $dom->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('m', self::NS);

            foreach ($zellen as $bezug => $wert) {
                if ($wert === '' || $wert === null) continue;
                $c = $xpath->query("//m:c[@r='$bezug']")->item(0);
                if (!$c) throw new RuntimeException("Zelle $bezug fehlt in der Vorlage.");
                while ($c->firstChild) $c->removeChild($c->firstChild);
                if (is_int($wert)) {
                    $c->removeAttribute('t');
                    $c->appendChild($dom->createElementNS(self::NS, 'v', (string)$wert));
                } else {
                    // inlineStr: nie als Formel interpretiert.
                    $c->setAttribute('t', 'inlineStr');
                    $is = $c->appendChild($dom->createElementNS(self::NS, 'is'));
                    $t = $is->appendChild($dom->createElementNS(self::NS, 't'));
                    $t->appendChild($dom->createTextNode(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $wert)));
                }
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $dom->saveXML());
            $zip->close();
            return (string)file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    // ZIP mit einem Formular je Event (bei mehr als 45 Athleten mehrere
    // Teile). Gibt den Pfad einer temporären Datei zurück.
    public static function zipErzeugen(array $events, array $kopf, int $maxAlter): string {
        $tmp = tempnam(sys_get_temp_dir(), 'swz');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ZIP konnte nicht erstellt werden.');
        }
        foreach ($events as $event) {
            $jahr = (int)substr($event['datum_von'], 0, 4);
            $athleten = self::filtern(self::athleten($event), $jahr, $maxAlter);
            $coaches = self::coaches((int)$event['id']);
            $teile = array_chunk($athleten, self::MAX_ATHLETEN) ?: [[]];
            foreach ($teile as $i => $teil) {
                $suffix = count($teile) > 1 ? '_Teil' . ($i + 1) : '';
                $zip->addFromString(
                    self::dateiname($event) . $suffix . '.xlsx',
                    self::xlsx(self::zellen($event, $kopf, $coaches, $teil))
                );
            }
        }
        $zip->close();
        return $tmp;
    }
}
