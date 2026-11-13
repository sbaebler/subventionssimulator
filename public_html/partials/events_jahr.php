<?php
// Ansicht «Events pro Jahr» des Simulators (aufgerufen aus simulieren.php
// mit ?ansicht=events): erwartete Subventionen aller im Class Manager Tool
// erfassten Events eines Jahres – geschätzt aus Teilnehmenden und Eventtagen.
require_once __DIR__ . '/../../includes/EventSchaetzung.php';

$pageTitle = 'Simulator – Events pro Jahr';

$jahr = (int)($_GET['jahr'] ?? date('Y'));
if ($jahr < 2000 || $jahr > 2100) {
    $jahr = (int)date('Y');
}

$klasseId = (int)($_GET['klasse'] ?? 0) ?: null;
$eventart = $_GET['eventart'] ?? '';
$eventart = array_key_exists($eventart, Subvention::EVENTARTEN) ? $eventart : null;

$fehler = null;
$klassen = [];
$u = ['events' => [], 'programme' => [], 'total' => 0.0, 'unvollstaendig' => 0];
try {
    $klassen = Event::klassen();
    $u = EventSchaetzung::jahresuebersicht($jahr, $klasseId, $eventart);
} catch (Throwable $e) {
    error_log('[Subventionssimulator] Jahresübersicht Fehler: ' . $e->getMessage());
    $fehler = 'Die Übersicht konnte nicht geladen werden.';
}

$chf = fn(float $b): string => 'CHF ' . number_format($b, 2, '.', "'");

require __DIR__ . '/header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-semibold">Simulator</h1>
  <p class="text-sm text-muted mt-1">Erwartete Subventionen der im Class Manager Tool erfassten Events</p>
</div>

<?php $simulatorAnsicht = 'events'; require __DIR__ . '/simulator_tabs.php'; ?>

<form method="get" action="/simulieren.php" class="card mb-6">
  <input type="hidden" name="ansicht" value="events">
  <div class="flex flex-wrap items-end gap-4">
    <div>
      <label class="block text-sm font-medium mb-1" for="jahr">Jahr</label>
      <input type="number" id="jahr" name="jahr" min="2000" max="2100" value="<?= $jahr ?>" class="input w-32">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="klasse">Klasse</label>
      <select id="klasse" name="klasse" class="input">
        <option value="">Alle Klassen</option>
        <?php foreach ($klassen as $k): ?>
        <option value="<?= (int)$k['id'] ?>" <?= $klasseId === (int)$k['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($k['bezeichnung']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="eventart">Eventart</label>
      <select id="eventart" name="eventart" class="input">
        <option value="">Alle Eventarten</option>
        <?php foreach (Subvention::EVENTARTEN as $key => $label): ?>
        <option value="<?= $key ?>" <?= $eventart === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn--secondary">Anzeigen</button>
  </div>
  <p class="text-xs text-subtle mt-3">
    Geschätzt nach den aktuellen Förderprogrammen: Teilnehmende (geplant, nach dem Event die
    Anwesenden) × förderfähige Eventtage, mit denselben Regeln wie im Class Manager Tool.
    Es zählen alle Events, die im gewählten Jahr beginnen. Die Eventart ergibt sich wie im
    Class Manager Tool aus der Dauer (ab 4 Tagen Lager, sonst Training). Total und Summen
    gelten für die gefilterten Events.
  </p>
</form>

<?php if ($fehler): ?>
<div class="alert alert--error mb-6"><?= htmlspecialchars($fehler) ?></div>
<?php elseif (empty($u['events'])): ?>
<p class="empty-state">Für <?= $jahr ?> sind keine Events<?= ($klasseId || $eventart) ? ' mit diesem Filter' : '' ?> erfasst.</p>
<?php else: ?>

<div class="alert alert--info px-5 py-4 mb-6">
  <div class="flex flex-wrap gap-6 items-center">
    <div>
      <p class="text-xs uppercase tracking-wide font-medium opacity-70">Erwartete Subventionen <?= $jahr ?></p>
      <p class="text-2xl font-bold"><?= $chf($u['total']) ?></p>
    </div>
    <div>
      <p class="text-xs uppercase tracking-wide font-medium opacity-70">Events</p>
      <p class="text-2xl font-bold"><?= count($u['events']) ?></p>
    </div>
    <?php if ($u['unvollstaendig'] > 0): ?>
    <p class="text-xs opacity-70 ml-auto">
      <?= (int)$u['unvollstaendig'] ?> Event<?= $u['unvollstaendig'] === 1 ? '' : 's' ?> ohne Schätzung
      (z.&nbsp;B. keine Teilnehmenden erfasst, kein Programm berechenbar) – nicht im Total.
    </p>
    <?php endif; ?>
  </div>
</div>

<?php if ($u['programme']): ?>
<h2 class="font-semibold mb-3">Pro Förderprogramm</h2>
<section class="card mb-8 overflow-x-auto">
  <table class="w-full text-sm">
    <thead>
      <tr class="text-left text-muted">
        <th class="py-1 pr-4">Förderprogramm</th>
        <th class="py-1 pr-4 text-right">Events</th>
        <th class="py-1 text-right">Erwartet</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
      <?php foreach ($u['programme'] as $p): ?>
      <tr>
        <td class="py-1 pr-4">
          <?= htmlspecialchars($p['bezeichnung']) ?>
          <span class="text-xs text-subtle"><?= htmlspecialchars($p['foerderstelle']) ?></span>
        </td>
        <td class="py-1 pr-4 text-right"><?= (int)$p['anzahl'] ?></td>
        <td class="py-1 text-right font-mono"><?= $p['anzahl'] > 0 ? $chf($p['summe']) : '–' ?></td>
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
<?php endif; ?>

<h2 class="font-semibold mb-3">Pro Event</h2>
<div class="grid gap-4">
<?php foreach ($u['events'] as $e): $s = $e['schaetzung']; ?>
  <div class="card" x-data="{ offen: false }">
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
          <?php if ($s['teilnehmer'] !== null): ?>
            &middot; <?= (int)$s['teilnehmer'] ?> TN &middot; <?= (int)$s['tage'] ?> Tage
            &middot; <?= $s['eventart'] === 'lager' ? 'Lager' : 'Training' ?>
          <?php endif; ?>
        </p>
      </div>
      <div class="sm:text-right sm:shrink-0">
        <?php if ($s['summe'] !== null): ?>
        <p class="text-xl font-bold text-success"><?= $chf($s['summe']) ?></p>
        <p class="text-xs text-subtle">erwartet</p>
        <?php else: ?>
        <p class="text-sm text-subtle">
          <?= $s['teilnehmer'] === null ? 'Keine Teilnehmenden erfasst' : 'Keine Schätzung möglich' ?>
        </p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($s['programme']): ?>
    <button type="button" @click="offen = !offen" class="link text-xs mt-3 cursor-pointer">
      <span x-text="offen ? 'Programme verbergen' : 'Programme zeigen (<?= count($s['programme']) ?>)'">Programme zeigen</span>
    </button>
    <ul x-show="offen" x-transition class="mt-2 pt-2 border-t border-gray-100 divide-y divide-gray-50 text-sm">
      <?php foreach ($s['programme'] as $p): ?>
      <li class="py-2" x-data="{ det: false }">
        <div class="flex justify-between gap-4">
          <span>
            <?= htmlspecialchars($p['bezeichnung']) ?>
            <span class="text-xs text-subtle"><?= htmlspecialchars($p['foerderstelle']) ?></span>
          </span>
          <?php if ($p['status'] === 'berechnet'): ?>
          <button type="button" @click="det = !det" class="font-mono shrink-0 cursor-pointer"><?= $chf($p['betrag']) ?></button>
          <?php else: ?>
          <span class="badge badge--neutral shrink-0">
            <?= $p['status'] === 'nicht_berechtigt' ? 'Nicht berechtigt' : 'Nicht berechenbar' ?>
          </span>
          <?php endif; ?>
        </div>
        <?php if ($p['status'] !== 'berechnet' && $p['grund']): ?>
        <p class="text-xs text-subtle mt-1"><?= htmlspecialchars($p['grund']) ?></p>
        <?php elseif ($p['aufschluesselung']): ?>
        <table x-show="det" x-transition class="w-full text-xs mt-2">
          <?php foreach ($p['aufschluesselung'] as $z): ?>
          <tr class="<?= $z['wert'] == 0 ? 'text-subtle' : 'text-muted' ?>">
            <td class="py-0.5"><?= htmlspecialchars($z['label']) ?></td>
            <td class="py-0.5 text-right font-mono"><?php
              echo match ($z['format']) {
                  'faktor' => '× ' . number_format($z['wert'], 3),
                  'zahl'   => htmlspecialchars((string)(0 + $z['wert'])),
                  default  => 'CHF ' . number_format($z['wert'], 2, '.', "'"),
              };
            ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="text-xs text-subtle mt-3">Keine Förderprogramme zugeordnet.</p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
