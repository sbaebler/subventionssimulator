<?php
require_once __DIR__ . '/../includes/Subvention.php';

$pageTitle = 'Simulator';

$params = [
    'anlassname'        => '',
    'anzahl_teilnehmer' => 10,
    'anzahl_tage'       => 3,
    'trainerart'        => 'js_trainer',
    'eventart'          => 'lager',
    'uebernachtung'     => 1,
    'stunden_pro_tag'   => 4,
    'lektionen_pro_tag' => 6,
];

// Aktive Subventionen laden. Der Event-Simulator behandelt nur event-basierte
// Berechnungstypen – verbandsweite Pauschalen/Jahresbeiträge gehören in die
// separate Ansicht "Jahresbeiträge" und würden ein Event-Total verfälschen.
$JAHRESTYPEN = ['pauschale', 'jahresbeitrag'];
$alleSubv = array_values(array_filter(
    Subvention::alle(),
    fn($s) => !in_array($s['berechnungstyp'] ?? 'additiv', $JAHRESTYPEN, true)
));
$typen    = array_column($alleSubv, 'berechnungstyp');
$brauchtUebernachtung = in_array('js_teilnehmertag', $typen, true);
$brauchtStunden       = in_array('js_teilnehmerstunde', $typen, true);
$brauchtLektionen     = in_array('zks_ausbildungseinheit', $typen, true);

$ergebnisse = null;
$fehler     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = [
        'anlassname'        => trim($_POST['anlassname'] ?? ''),
        'anzahl_teilnehmer' => max(1, (int)($_POST['anzahl_teilnehmer'] ?? 1)),
        'anzahl_tage'       => max(1, (int)($_POST['anzahl_tage']       ?? 1)),
        'trainerart'        => $_POST['trainerart'] ?? 'js_trainer',
        'eventart'          => $_POST['eventart']   ?? 'lager',
        'uebernachtung'     => isset($_POST['uebernachtung']) ? 1 : 0,
        'stunden_pro_tag'   => max(0, (int)($_POST['stunden_pro_tag']   ?? 0)),
        'lektionen_pro_tag' => max(0, (int)($_POST['lektionen_pro_tag'] ?? 0)),
    ];

    if (!array_key_exists($params['trainerart'], Subvention::TRAINERARTEN)) {
        $fehler = 'Ungültige Trainerart.';
    } elseif (!array_key_exists($params['eventart'], Subvention::EVENTARTEN)) {
        $fehler = 'Ungültige Eventart.';
    } else {
        try {
            $alle = $alleSubv;
            if (empty($alle)) {
                $fehler = 'Keine aktiven Förderprogramme erfasst.';
            } else {
                $ergebnisse = [];
                foreach ($alle as $s) {
                    $ergebnisse[] = Subvention::berechnen($s['id'], $params);
                }
                usort($ergebnisse, function (array $a, array $b): int {
                    if ($a['berechtigt'] !== $b['berechtigt']) {
                        return (int)$b['berechtigt'] - (int)$a['berechtigt'];
                    }
                    return ($b['betrag'] ?? 0) <=> ($a['betrag'] ?? 0);
                });
            }
        } catch (Throwable $e) {
            error_log('[Subventionssimulator] Simulator Fehler: ' . $e->getMessage());
            $fehler = 'Beim Berechnen ist ein Fehler aufgetreten.';
        }
    }

    // CSV-Export: vor jeglichem HTML-Output
    if ($ergebnisse !== null && ($_POST['format'] ?? '') === 'csv') {
        $dateiname = 'simulation_' . date('Y-m-d')
            . ($params['anlassname'] !== '' ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $params['anlassname']) : '')
            . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $dateiname . '"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM für Excel
        fputs($out, "\xEF\xBB\xBF");

        // Metazeilen
        fputcsv($out, ['Anlassname', $params['anlassname']], ';');
        fputcsv($out, ['Datum', date('d.m.Y')], ';');
        fputcsv($out, ['Teilnehmende', $params['anzahl_teilnehmer']], ';');
        fputcsv($out, ['Tage', $params['anzahl_tage']], ';');
        fputcsv($out, ['Trainerart', Subvention::TRAINERARTEN[$params['trainerart']]], ';');
        fputcsv($out, ['Eventart', Subvention::EVENTARTEN[$params['eventart']]], ';');
        fputcsv($out, ['Übernachtung', $params['uebernachtung'] ? 'Ja' : 'Nein'], ';');
        fputcsv($out, ['Stunden/Tag', $params['stunden_pro_tag']], ';');
        fputcsv($out, ['Lektionen/Tag', $params['lektionen_pro_tag']], ';');
        fputcsv($out, [], ';');

        // Titelzeile
        fputcsv($out, [
            'Förderprogramm', 'Förderstelle', 'Berechnungstyp', 'Berechtigt', 'Betrag (CHF)', 'Berechnung',
        ], ';');

        foreach ($ergebnisse as $r) {
            $typLabel = Subvention::BERECHNUNGSTYPEN[$r['berechnungstyp'] ?? 'additiv'] ?? ($r['berechnungstyp'] ?? '');
            if ($r['berechtigt']) {
                // Aufschlüsselung als lesbaren Text zusammensetzen
                $teile = [];
                foreach ($r['aufschluesselung'] as $zeile) {
                    $wert = match ($zeile['format']) {
                        'faktor' => '× ' . number_format($zeile['wert'], 3, '.', ''),
                        'zahl'   => (string)(0 + $zeile['wert']),
                        default  => number_format($zeile['wert'], 2, '.', '') . ' CHF',
                    };
                    $teile[] = $zeile['label'] . ': ' . $wert;
                }
                fputcsv($out, [
                    $r['bezeichnung'],
                    $r['foerderstelle'],
                    $typLabel,
                    'Ja',
                    number_format($r['betrag'], 2, '.', ''),
                    implode(' | ', $teile),
                ], ';');
            } else {
                fputcsv($out, [
                    $r['bezeichnung'],
                    $r['foerderstelle'],
                    $typLabel,
                    'Nein – ' . ($r['grund'] ?? ''),
                    '', '',
                ], ';');
            }
        }

        // Totalszeile
        $total = array_sum(array_map(fn($r) => $r['berechtigt'] ? $r['betrag'] : 0, $ergebnisse));
        fputcsv($out, [], ';');
        fputcsv($out, ['Total förderberechtigt', '', '', '', number_format($total, 2, '.', ''), ''], ';');

        fclose($out);
        exit;
    }
}

$totalBerechtigt = $ergebnisse
    ? array_sum(array_map(fn($r) => $r['berechtigt'] ? $r['betrag'] : 0, $ergebnisse))
    : 0;

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-semibold">Simulator</h1>
  <p class="text-sm text-gray-500 mt-1">Berechne deine Förderbeiträge für ein konkretes Event</p>
</div>

<!-- ── Eingabeformular ─────────────────────────────────────────── -->
<form method="post" action="/simulieren.php">
  <input type="hidden" name="format" value="">
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <h2 class="font-semibold text-gray-700 mb-4">Event-Parameter</h2>
    <div class="grid grid-cols-1 gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium mb-1">Anlassname</label>
        <input type="text" name="anlassname" required maxlength="200"
               value="<?= htmlspecialchars($params['anlassname']) ?>"
               placeholder="z.B. Sommerlager Zürichsee 2025"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

      <div>
        <label class="block text-sm font-medium mb-1">Teilnehmende</label>
        <input type="number" name="anzahl_teilnehmer" min="1" max="999" required
               value="<?= (int)$params['anzahl_teilnehmer'] ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Tage</label>
        <input type="number" name="anzahl_tage" min="1" max="365" required
               value="<?= (int)$params['anzahl_tage'] ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Trainerart</label>
        <select name="trainerart"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
          <?php foreach (Subvention::TRAINERARTEN as $key => $label): ?>
          <option value="<?= $key ?>" <?= $params['trainerart'] === $key ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Eventart</label>
        <select name="eventart"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
          <?php foreach (Subvention::EVENTARTEN as $key => $label): ?>
          <option value="<?= $key ?>" <?= $params['eventart'] === $key ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

    </div>

    <?php if ($brauchtUebernachtung || $brauchtStunden || $brauchtLektionen): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-100">
      <?php if ($brauchtUebernachtung): ?>
      <div class="flex items-center gap-2 pt-6">
        <input type="checkbox" name="uebernachtung" id="uebernachtung" value="1"
               <?= $params['uebernachtung'] ? 'checked' : '' ?>
               class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
        <label for="uebernachtung" class="text-sm font-medium">Mit Übernachtung</label>
      </div>
      <?php endif; ?>
      <?php if ($brauchtStunden): ?>
      <div>
        <label class="block text-sm font-medium mb-1">Stunden / Tag</label>
        <input type="number" name="stunden_pro_tag" min="0" max="24"
               value="<?= (int)$params['stunden_pro_tag'] ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
        <p class="text-xs text-gray-400 mt-1">Für J+S Training (Teilnehmerstunden)</p>
      </div>
      <?php endif; ?>
      <?php if ($brauchtLektionen): ?>
      <div>
        <label class="block text-sm font-medium mb-1">Lektionen / Tag</label>
        <input type="number" name="lektionen_pro_tag" min="0" max="24"
               value="<?= (int)$params['lektionen_pro_tag'] ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
        <p class="text-xs text-gray-400 mt-1">Für ZKS Ausbildungseinheiten</p>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="mt-4 flex justify-end">
      <button type="submit"
              class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-6 py-2 rounded-lg">
        Berechnen
      </button>
    </div>
  </section>
</form>

<?php if ($fehler): ?>
<div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 mb-6">
  <?= htmlspecialchars($fehler) ?>
</div>
<?php endif; ?>

<?php if ($ergebnisse !== null): ?>

<!-- ── Zusammenfassung ─────────────────────────────────────────── -->
<?php
$anzahlBerechtigt = count(array_filter($ergebnisse, fn($r) => $r['berechtigt']));
?>
<div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-4 mb-6">
  <div class="flex flex-wrap gap-6 items-center">
    <?php if ($params['anlassname'] !== ''): ?>
    <div class="w-full mb-1">
      <p class="text-xs text-blue-500 uppercase tracking-wide font-medium">Anlass</p>
      <p class="text-lg font-semibold text-blue-900"><?= htmlspecialchars($params['anlassname']) ?></p>
    </div>
    <?php endif; ?>
    <div>
      <p class="text-xs text-blue-500 uppercase tracking-wide font-medium">Förderberechtigt</p>
      <p class="text-2xl font-bold text-blue-700"><?= $anzahlBerechtigt ?> von <?= count($ergebnisse) ?></p>
    </div>
    <div>
      <p class="text-xs text-blue-500 uppercase tracking-wide font-medium">Möglicher Gesamtbetrag</p>
      <p class="text-2xl font-bold text-blue-700">CHF <?= number_format($totalBerechtigt, 2, '.', "'") ?></p>
    </div>
    <div class="text-xs text-blue-400 ml-auto">
      <?= (int)$params['anzahl_teilnehmer'] ?> TN &nbsp;·&nbsp;
      <?= (int)$params['anzahl_tage'] ?> Tage &nbsp;·&nbsp;
      <?= htmlspecialchars(Subvention::TRAINERARTEN[$params['trainerart']]) ?> &nbsp;·&nbsp;
      <?= htmlspecialchars(Subvention::EVENTARTEN[$params['eventart']]) ?>
    </div>
  </div>
</div>

<!-- ── Ergebnis-Karten ─────────────────────────────────────────── -->
<div class="grid gap-4">
<?php foreach ($ergebnisse as $r): ?>

  <?php if ($r['berechtigt']): ?>
  <div class="bg-white border border-green-200 rounded-xl p-5" x-data="{ offen: false }">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-medium bg-green-100 text-green-800 px-2 py-0.5 rounded">Förderberechtigt</span>
        </div>
        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($r['bezeichnung']) ?></h3>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($r['foerderstelle']) ?></p>
      </div>
      <div class="text-right shrink-0">
        <p class="text-2xl font-bold text-green-700">CHF <?= number_format($r['betrag'], 2, '.', "'") ?></p>
        <button type="button" @click="offen = !offen"
                class="text-xs text-blue-600 hover:underline mt-1">
          <span x-text="offen ? 'Aufschlüsselung verbergen' : 'Aufschlüsselung zeigen'">Aufschlüsselung zeigen</span>
        </button>
      </div>
    </div>

    <div x-show="offen" x-transition class="mt-4 border-t border-gray-100 pt-4">
      <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($r['aufschluesselung'] as $zeile): ?>
          <tr class="<?= $zeile['wert'] == 0 ? 'text-gray-300' : 'text-gray-600' ?>">
            <td class="py-1"><?= htmlspecialchars($zeile['label']) ?></td>
            <td class="py-1 text-right font-mono"><?php
              echo match ($zeile['format']) {
                  'faktor' => '× ' . number_format($zeile['wert'], 3),
                  'zahl'   => htmlspecialchars((string)(0 + $zeile['wert'])),
                  default  => 'CHF ' . number_format($zeile['wert'], 2, '.', "'"),
              };
            ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="font-semibold text-gray-900 border-t border-gray-200">
            <td class="pt-2">Total</td>
            <td class="pt-2 text-right font-mono text-green-700">CHF <?= number_format($r['betrag'], 2, '.', "'") ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <?php else: ?>
  <div class="bg-white border border-gray-200 rounded-xl p-5 opacity-60">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-medium bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Nicht berechtigt</span>
          <span class="text-xs text-gray-400"><?= htmlspecialchars($r['grund']) ?></span>
        </div>
        <h3 class="font-semibold text-gray-500"><?= htmlspecialchars($r['bezeichnung']) ?></h3>
        <p class="text-sm text-gray-400"><?= htmlspecialchars($r['foerderstelle']) ?></p>
      </div>
      <p class="text-gray-300 font-mono shrink-0">–</p>
    </div>
  </div>
  <?php endif; ?>

<?php endforeach; ?>
</div>

<!-- ── CSV-Download ────────────────────────────────────────────── -->
<form method="post" action="/simulieren.php" class="mt-6 flex justify-end">
  <input type="hidden" name="format"             value="csv">
  <input type="hidden" name="anlassname"         value="<?= htmlspecialchars($params['anlassname']) ?>">
  <input type="hidden" name="anzahl_teilnehmer"  value="<?= (int)$params['anzahl_teilnehmer'] ?>">
  <input type="hidden" name="anzahl_tage"        value="<?= (int)$params['anzahl_tage'] ?>">
  <input type="hidden" name="trainerart"         value="<?= htmlspecialchars($params['trainerart']) ?>">
  <input type="hidden" name="eventart"           value="<?= htmlspecialchars($params['eventart']) ?>">
  <?php if ($params['uebernachtung']): ?><input type="hidden" name="uebernachtung" value="1"><?php endif; ?>
  <input type="hidden" name="stunden_pro_tag"    value="<?= (int)$params['stunden_pro_tag'] ?>">
  <input type="hidden" name="lektionen_pro_tag"  value="<?= (int)$params['lektionen_pro_tag'] ?>">
  <button type="submit"
          class="flex items-center gap-2 border border-gray-300 hover:border-blue-400 text-gray-600 hover:text-blue-600 text-sm px-4 py-2 rounded-lg">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
    </svg>
    Als CSV herunterladen
  </button>
</form>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
