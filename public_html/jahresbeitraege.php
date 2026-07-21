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
  <p class="text-sm text-muted mt-1">Verbandsweite Beiträge (Pauschalen &amp; kennzahlbasierte Jahresbeiträge) – nicht event-basiert</p>
</div>

<?php if (empty($beitraege)): ?>
<p class="empty-state">Keine Jahresbeiträge erfasst. Lege ein Förderprogramm mit Berechnungsmuster «Fixer Betrag» oder «Nach Jahreskennzahl» an.</p>
<?php else: ?>

<form method="post" action="/jahresbeitraege.php">
  <section class="card mb-6">
    <div class="flex items-end gap-4 mb-2">
      <div>
        <label class="block text-sm font-medium mb-1">Bezugsjahr</label>
        <input type="number" name="jahr" min="2000" max="2100"
               value="<?= (int)$jahr ?>"
               class="input w-32">
      </div>
      <p class="text-xs text-subtle pb-2">Trage die Kennzahlen des Jahres ein (z.&nbsp;B. Aktivmitglieder, SOTC, Trainingstage). Pauschalen benötigen keine Eingabe.</p>
    </div>
  </section>

  <div class="grid gap-4">
  <?php foreach ($ergebnisse as $r): ?>
    <div class="card">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <span class="badge badge--info">
              <?= htmlspecialchars(Subvention::BERECHNUNGSTYPEN[$r['berechnungstyp']] ?? $r['berechnungstyp']) ?>
            </span>
          </div>
          <h3 class="font-semibold"><?= htmlspecialchars($r['bezeichnung']) ?></h3>
          <p class="text-sm text-muted"><?= htmlspecialchars($r['foerderstelle']) ?></p>
          <?php if ($r['berechnungsgrundlage']): ?>
          <p class="text-xs text-subtle mt-1"><?= htmlspecialchars($r['berechnungsgrundlage']) ?></p>
          <?php endif; ?>

          <?php if ($r['berechnungstyp'] === 'jahresbeitrag'): ?>
          <div class="mt-3">
            <label class="block text-xs text-muted mb-1">Anzahl Einheiten</label>
            <input type="text" inputmode="decimal" name="einheiten[<?= (int)$r['id'] ?>]"
                   value="<?= htmlspecialchars((string)($r['eingabe'] ?: '')) ?>"
                   placeholder="z.B. Aktivmitglieder / Trainingstage"
                   class="input w-56">
          </div>
          <?php endif; ?>
        </div>

        <div class="text-right shrink-0">
          <p class="text-2xl font-bold text-success">CHF <?= number_format($r['betrag'], 2, '.', "'") ?></p>
          <?php if ($r['referenz']): ?>
          <p class="text-xs text-subtle mt-1">
            Referenz <?= (int)$r['referenz']['jahr'] ?>: CHF <?= number_format((float)$r['referenz']['betrag'], 2, '.', "'") ?>
          </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>

  <div class="alert alert--info px-5 py-4 mt-6 flex items-center justify-between">
    <div>
      <p class="text-xs uppercase tracking-wide font-medium opacity-70">Total Jahresbeiträge<?= $berechnet ? ' ' . (int)$jahr : '' ?></p>
      <p class="text-2xl font-bold">CHF <?= number_format($total, 2, '.', "'") ?></p>
    </div>
    <button type="submit" class="btn btn--primary">
      Berechnen
    </button>
  </div>
</form>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
