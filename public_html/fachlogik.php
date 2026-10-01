<?php
require_once __DIR__ . '/../includes/Subvention.php';

$pageTitle = 'Fachlogik';

// Beispielwerte pro Berechnungstyp. Die Beträge unten rechnet Foerderrechner
// selbst aus, damit die Doku nie von der Berechnung abweicht.
$beispielTn   = 20;
$beispielTage = 5;
$typen = [
    'additiv' => [
        'formel'  => '(Grundbetrag + Betrag/TN × TN + Betrag/Tag × Tage + Trainer-Zusatz) × Eventart-Faktor',
        'einsatz' => 'Kantonale und vereinsinterne Beiträge, gemischte Pauschalen',
        'regel'   => ['grundbetrag' => 100, 'betrag_pro_teilnehmer' => 10, 'betrag_pro_tag' => 20],
        'params'  => [],
    ],
    'js_teilnehmertag' => [
        'formel'  => 'Satz × TN × Tage (Satz je nach Übernachtung)',
        'einsatz' => 'J+S Lager',
        'regel'   => ['satz_mit_uebernachtung' => 16, 'satz_ohne_uebernachtung' => 6.5],
        'params'  => ['uebernachtung' => true],
    ],
    'js_teilnehmerstunde' => [
        'formel'  => 'Satz × TN × Tage × Stunden/Tag (Stunden gedeckelt)',
        'einsatz' => 'J+S Training',
        'regel'   => ['betrag_pro_stunde' => 1.3, 'max_stunden_pro_tag' => 5],
        'params'  => ['stunden_pro_tag' => 2],
    ],
    'zks_ausbildungseinheit' => [
        'formel'  => 'Satz × (TN × Lektionen/Tag × Tage) (Lektionen gedeckelt)',
        'einsatz' => 'ZKS Ausbildungsbeitrag',
        'regel'   => ['betrag_pro_einheit' => 2.8, 'max_lektionen_pro_tag' => 6],
        'params'  => ['lektionen_pro_tag' => 6],
    ],
    'pauschale' => [
        'formel'  => 'Fixer Betrag (im Grundbetrag hinterlegt)',
        'einsatz' => 'Feste Jahresbeiträge',
        'regel'   => ['grundbetrag' => 5000],
        'params'  => [],
    ],
    'jahresbeitrag' => [
        'formel'  => 'Betrag pro Einheit × Anzahl Einheiten',
        'einsatz' => 'Verbandsweite Kennzahlen (Mitglieder, Trainingstage)',
        'regel'   => ['betrag_pro_einheit' => 2],
        'params'  => ['anzahl_einheiten' => 300],
    ],
];

function chf(float $v): string {
    return 'CHF ' . number_format($v, 2, '.', '’');
}

require __DIR__ . '/partials/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl font-semibold">Fachlogik</h1>
  <p class="text-sm text-muted mt-1">Wie ein Förderprogramm aufgebaut ist und wie der Betrag berechnet wird</p>
</div>

<section class="card mb-6">
  <h2 class="font-semibold mb-3">Aufbau eines Förderprogramms</h2>
  <ul class="space-y-2 text-sm text-muted list-disc list-inside">
    <li><strong>Stammdaten:</strong> Name, Förderstelle, Kategorie, Beschreibung, Gültigkeit.</li>
    <li><strong>Beitragssatz:</strong> Ein Satz pro Programm. Er wird im Wizard je nach Berechnungsmuster mit den passenden Feldern erfasst.</li>
    <li><strong>Bedingungen:</strong> Voraussetzungen, Berechtigte, Einschränkungen, verlangte Unterlagen sowie optional Eventarten und Trainerarten.</li>
    <li><strong>Termine &amp; Beträge:</strong> Eingabefrist, weitere Fristen und die erhaltenen Beträge pro Jahr.</li>
  </ul>
</section>

<section class="card mb-6">
  <h2 class="font-semibold mb-3">Berechnungstypen</h2>
  <p class="text-sm text-muted leading-relaxed mb-4">
    Der Berechnungstyp legt fest, welche Felder des Beitragssatzes gelten und nach welcher Formel gerechnet wird.
    Die Beispielbeträge rechnen mit <?= $beispielTn ?> Teilnehmenden und <?= $beispielTage ?> Tagen.
  </p>
  <div class="overflow-x-auto">
    <table class="text-sm w-full">
      <thead>
        <tr class="text-left text-xs text-subtle uppercase tracking-wide">
          <th class="py-2 pr-4">Typ</th>
          <th class="py-2 pr-4">Formel</th>
          <th class="py-2 pr-4">Typische Anwendung</th>
          <th class="py-2 text-right">Beispiel</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php foreach ($typen as $key => $t):
            [$betrag] = Foerderrechner::betrag($key, $t['regel'], $beispielTn, $beispielTage, $t['params']);
        ?>
        <tr class="text-muted">
          <td class="py-2 pr-4 font-medium whitespace-nowrap"><?= htmlspecialchars(Subvention::BERECHNUNGSTYPEN[$key]) ?></td>
          <td class="py-2 pr-4"><?= htmlspecialchars($t['formel']) ?></td>
          <td class="py-2 pr-4"><?= htmlspecialchars($t['einsatz']) ?></td>
          <td class="py-2 text-right whitespace-nowrap"><?= chf($betrag) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="text-xs text-subtle mt-3">
    Bei jedem Typ gilt: Ein Maximum von 0 oder leer bedeutet «kein Limit». Am Schluss wird auf den
    Maximalbetrag total gedeckelt, sofern einer hinterlegt ist.
  </p>
</section>

<section class="card mb-6">
  <h2 class="font-semibold mb-3">Berechtigung: Eventart und Trainerart</h2>
  <ul class="space-y-2 text-sm text-muted list-disc list-inside">
    <li><strong>Keine Auswahl</strong> bedeutet: Das Programm gilt für alle Events und alle Trainer.</li>
    <li><strong>Eventart gewählt:</strong> Das Programm gilt nur für diese Eventarten (<?= htmlspecialchars(implode(', ', Subvention::EVENTARTEN)) ?>).
        Beim Typ «Additiv» wirkt der Faktor auf den Gesamtbetrag.</li>
    <li><strong>Trainerart gewählt:</strong> Das Programm gilt nur mit dieser Trainerart (<?= htmlspecialchars(implode(', ', Subvention::TRAINERARTEN)) ?>).
        Beim Typ «Additiv» wird der Zusatzbetrag der Trainerart addiert.</li>
    <li>Passt eine Bedingung nicht, zeigt der Simulator das Programm als «nicht berechtigt» mit Grund.</li>
  </ul>
</section>

<section class="card mb-6">
  <h2 class="font-semibold mb-3">Vollständig erfasst</h2>
  <p class="text-sm text-muted leading-relaxed mb-3">Ein Förderprogramm gilt als vollständig, wenn alle vier Punkte erfüllt sind:</p>
  <ul class="space-y-1 text-sm text-muted list-disc list-inside">
    <li>Ein Beitragssatz ist erfasst.</li>
    <li>Beschreibung oder Voraussetzungen sind ausgefüllt.</li>
    <li>Eine Eingabefrist oder ein weiterer Termin ist erfasst.</li>
    <li>Für das letzte oder das laufende Jahr ist ein Betrag erfasst.</li>
  </ul>
</section>

<section class="card mb-6">
  <h2 class="font-semibold mb-3">Kategorien</h2>
  <p class="text-sm text-muted"><?= htmlspecialchars(implode(' · ', Subvention::KATEGORIEN)) ?></p>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
