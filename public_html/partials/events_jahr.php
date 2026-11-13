<?php
// Ansicht «Events pro Jahr» des Simulators (aufgerufen aus simulieren.php
// mit ?ansicht=events): welche Förderprogramme die Events eines Jahres
// ziehen und welche Beträge dafür bereits zugeteilt sind.
require_once __DIR__ . '/../../includes/Event.php';

$pageTitle = 'Simulator – Events pro Jahr';

$jahr = (int)($_GET['jahr'] ?? date('Y'));
if ($jahr < 2000 || $jahr > 2100) {
    $jahr = (int)date('Y');
}

$fehler = null;
$u = ['events' => [], 'programme' => [], 'total' => 0.0];
try {
    $u = Event::jahresuebersicht($jahr);
} catch (Throwable $e) {
    error_log('[Subventionssimulator] Jahresübersicht Fehler: ' . $e->getMessage());
    $fehler = 'Die Übersicht konnte nicht geladen werden.';
}

$chf = fn(float $b): string => 'CHF ' . number_format($b, 2, '.', "'");

require __DIR__ . '/header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-semibold">Simulator</h1>
  <p class="text-sm text-muted mt-1">Gezogene Förderprogramme aller Events eines Jahres</p>
</div>

<?php $simulatorAnsicht = 'events'; require __DIR__ . '/simulator_tabs.php'; ?>

<form method="get" action="/simulieren.php" class="card mb-6">
  <input type="hidden" name="ansicht" value="events">
  <div class="flex items-end gap-4">
    <div>
      <label class="block text-sm font-medium mb-1" for="jahr">Jahr</label>
      <input type="number" id="jahr" name="jahr" min="2000" max="2100" value="<?= $jahr ?>" class="input w-32">
    </div>
    <button type="submit" class="btn btn--secondary">Anzeigen</button>
  </div>
  <p class="text-xs text-subtle mt-3">
    Pro Event die wirksam zugeordneten Förderprogramme (Standard und Anpassungen).
    Beträge stammen aus «Beiträge &amp; Verwendung» (Verteilung auf Events im gewählten Jahr).
  </p>
</form>

<?php if ($fehler): ?>
<div class="alert alert--error mb-6"><?= htmlspecialchars($fehler) ?></div>
<?php elseif (empty($u['events'])): ?>
<p class="empty-state">Für <?= $jahr ?> sind keine Events vorhanden.</p>
<?php else: ?>

<div class="alert alert--info px-5 py-4 mb-6">
  <div class="flex flex-wrap gap-6 items-center">
    <div>
      <p class="text-xs uppercase tracking-wide font-medium opacity-70">Events <?= $jahr ?></p>
      <p class="text-2xl font-bold"><?= count($u['events']) ?></p>
    </div>
    <div>
      <p class="text-xs uppercase tracking-wide font-medium opacity-70">Zugeteilt total</p>
      <p class="text-2xl font-bold"><?= $chf($u['total']) ?></p>
    </div>
  </div>
</div>

<h2 class="font-semibold mb-3">Pro Förderprogramm</h2>
<section class="card mb-8 overflow-x-auto">
  <table class="w-full text-sm">
    <thead>
      <tr class="text-left text-muted">
        <th class="py-1 pr-4">Förderprogramm</th>
        <th class="py-1 pr-4 text-right">Events</th>
        <th class="py-1 text-right">Zugeteilt</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
      <?php foreach ($u['programme'] as $p): ?>
      <tr>
        <td class="py-1 pr-4">
          <?= htmlspecialchars($p['bezeichnung']) ?>
          <span class="text-xs text-subtle"><?= htmlspecialchars($p['foerderstelle']) ?></span>
        </td>
        <td class="py-1 pr-4 text-right"><?= (int)$p['anzahl_events'] ?></td>
        <td class="py-1 text-right font-mono"><?= $p['total'] > 0 ? $chf($p['total']) : '–' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="font-semibold border-t border-gray-200">
        <td class="pt-2" colspan="2">Total</td>
        <td class="pt-2 text-right font-mono"><?= $chf($u['total']) ?></td>
      </tr>
    </tfoot>
  </table>
</section>

<h2 class="font-semibold mb-3">Pro Event</h2>
<div class="grid gap-4">
<?php foreach ($u['events'] as $e): ?>
  <div class="card">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="badge <?= Event::STATUS_BADGE[$e['status']] ?? 'badge--neutral' ?>">
            <?= htmlspecialchars(Event::STATUS[$e['status']] ?? $e['status']) ?>
          </span>
          <?php if ($e['klasse_bezeichnung']): ?>
          <span class="text-xs text-muted"><?= htmlspecialchars($e['klasse_bezeichnung']) ?></span>
          <?php endif; ?>
        </div>
        <h3 class="font-semibold"><?= htmlspecialchars($e['bezeichnung']) ?></h3>
        <p class="text-sm text-muted">
          <?= date('d.m.Y', strtotime($e['datum_von'])) ?>
          <?php if ($e['datum_bis'] && $e['datum_bis'] !== $e['datum_von']): ?>
            &ndash; <?= date('d.m.Y', strtotime($e['datum_bis'])) ?>
          <?php endif; ?>
        </p>
      </div>
      <div class="sm:text-right sm:shrink-0">
        <p class="text-xl font-bold <?= $e['total'] > 0 ? 'text-success' : 'text-subtle' ?>">
          <?= $e['total'] > 0 ? $chf($e['total']) : '–' ?>
        </p>
        <p class="text-xs text-subtle">zugeteilt</p>
      </div>
    </div>

    <?php if (empty($e['programme'])): ?>
    <p class="text-sm text-subtle mt-3">Keine Förderprogramme zugeordnet.</p>
    <?php else: ?>
    <ul class="mt-3 pt-3 border-t border-gray-100 divide-y divide-gray-50 text-sm">
      <?php foreach ($e['programme'] as $p): ?>
      <li class="flex justify-between gap-4 py-1">
        <span><?= htmlspecialchars($p['bezeichnung']) ?>
          <span class="text-xs text-subtle"><?= htmlspecialchars($p['foerderstelle']) ?></span></span>
        <span class="font-mono shrink-0 <?= $p['betrag'] === null ? 'text-subtle' : '' ?>">
          <?= $p['betrag'] === null ? 'nicht zugeteilt' : $chf($p['betrag']) ?>
        </span>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
