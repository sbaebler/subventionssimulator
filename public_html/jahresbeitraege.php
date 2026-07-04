<?php
require_once __DIR__ . '/../includes/Subvention.php';

$pageTitle = 'Jahresbeiträge';

// Nur verbandsweite Beiträge: Pauschalen und kennzahlbasierte Jahresbeiträge.
// Diese sind nicht event-basiert und haben daher eine eigene Ansicht.
$JAHRESTYPEN = ['pauschale', 'jahresbeitrag'];
$beitraege = array_values(array_filter(
    Subvention::alle(),
    fn($s) => in_array($s['berechnungstyp'] ?? 'additiv', $JAHRESTYPEN, true)
));

$jahr      = (int)($_POST['jahr'] ?? date('Y'));
$einheiten = $_POST['einheiten'] ?? [];   // [subvention_id => Anzahl]
$berechnet = ($_SERVER['REQUEST_METHOD'] === 'POST');

$ergebnisse = [];
$total      = 0.0;
foreach ($beitraege as $s) {
    $anzahl = (float)str_replace([',', "'"], ['.', ''], (string)($einheiten[$s['id']] ?? 0));
    $r = Subvention::berechnen($s['id'], ['anzahl_einheiten' => $anzahl]);
    $r['id']              = $s['id'];
    $r['eingabe']         = $anzahl;
    $r['berechnungstyp']  = $s['berechnungstyp'];
    $r['berechnungsgrundlage'] = $s['berechnungsgrundlage'] ?? '';
    // Referenz: neuester hinterlegter Ist-Betrag
    $hist = Subvention::betragHistorie($s['id']);
    $r['referenz'] = $hist[0] ?? null;
    $ergebnisse[] = $r;
    $total += $r['betrag'] ?? 0;
}

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-semibold">Jahresbeiträge</h1>
  <p class="text-sm text-gray-500 mt-1">Verbandsweite Beiträge (Pauschalen &amp; kennzahlbasierte Jahresbeiträge) – nicht event-basiert</p>
</div>

<?php if (empty($beitraege)): ?>
<div class="bg-gray-50 border border-gray-200 text-gray-600 text-sm rounded-lg px-4 py-3">
  Keine Jahresbeiträge erfasst. Lege eine Subvention mit Berechnungstyp «Pauschale» oder «Jahresbeitrag» an.
</div>
<?php else: ?>

<form method="post" action="/jahresbeitraege.php">
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <div class="flex items-end gap-4 mb-2">
      <div>
        <label class="block text-sm font-medium mb-1">Bezugsjahr</label>
        <input type="number" name="jahr" min="2000" max="2100"
               value="<?= (int)$jahr ?>"
               class="w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
      <p class="text-xs text-gray-400 pb-2">Trage die Kennzahlen des Jahres ein (z.&nbsp;B. Aktivmitglieder, SOTC, Trainingstage). Pauschalen benötigen keine Eingabe.</p>
    </div>
  </section>

  <div class="grid gap-4">
  <?php foreach ($ergebnisse as $r): ?>
    <div class="bg-white border border-gray-200 rounded-xl p-5">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-medium bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded">
              <?= htmlspecialchars(Subvention::BERECHNUNGSTYPEN[$r['berechnungstyp']] ?? $r['berechnungstyp']) ?>
            </span>
          </div>
          <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($r['bezeichnung']) ?></h3>
          <p class="text-sm text-gray-500"><?= htmlspecialchars($r['foerderstelle']) ?></p>
          <?php if ($r['berechnungsgrundlage']): ?>
          <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($r['berechnungsgrundlage']) ?></p>
          <?php endif; ?>

          <?php if ($r['berechnungstyp'] === 'jahresbeitrag'): ?>
          <div class="mt-3">
            <label class="block text-xs text-gray-500 mb-1">Anzahl Einheiten</label>
            <input type="text" inputmode="decimal" name="einheiten[<?= (int)$r['id'] ?>]"
                   value="<?= htmlspecialchars((string)($r['eingabe'] ?: '')) ?>"
                   placeholder="z.B. Aktivmitglieder / Trainingstage"
                   class="w-56 border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
          </div>
          <?php endif; ?>
        </div>

        <div class="text-right shrink-0">
          <p class="text-2xl font-bold text-indigo-700">CHF <?= number_format($r['betrag'], 2, '.', "'") ?></p>
          <?php if ($r['referenz']): ?>
          <p class="text-xs text-gray-400 mt-1">
            Referenz <?= (int)$r['referenz']['jahr'] ?>: CHF <?= number_format((float)$r['referenz']['betrag'], 2, '.', "'") ?>
          </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>

  <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-5 py-4 mt-6 flex items-center justify-between">
    <div>
      <p class="text-xs text-indigo-500 uppercase tracking-wide font-medium">Total Jahresbeiträge<?= $berechnet ? ' ' . (int)$jahr : '' ?></p>
      <p class="text-2xl font-bold text-indigo-700">CHF <?= number_format($total, 2, '.', "'") ?></p>
    </div>
    <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-6 py-2 rounded-lg">
      Berechnen
    </button>
  </div>
</form>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
