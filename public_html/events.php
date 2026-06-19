<?php
require_once __DIR__ . '/../includes/Event.php';
$pageTitle = 'Events – Übersicht';
$events = Event::alle();
require __DIR__ . '/partials/header.php';
?>

<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-semibold">Events</h1>
</div>

<?php if (isset($_GET['gespeichert'])): ?>
<div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 mb-6">
  Zuordnung wurde gespeichert.
</div>
<?php endif; ?>

<p class="text-sm text-gray-500 mb-6">
  Events werden im Class Manager Tool eröffnet. Hier legst du fest, welche
  Subventionen für welches Event zum Tragen kommen.
</p>

<?php if (empty($events)): ?>
  <p class="text-gray-500">Noch keine Events vorhanden. Events werden im Class Manager Tool erfasst.</p>
<?php else: ?>
  <div class="grid gap-4">
    <?php foreach ($events as $e): ?>
    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-start justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-medium px-2 py-0.5 rounded <?= Event::STATUS_BADGE[$e['status']] ?? 'bg-gray-100 text-gray-500' ?>">
            <?= htmlspecialchars(Event::STATUS[$e['status']] ?? $e['status']) ?>
          </span>
          <?php if ($e['klasse_bezeichnung']): ?>
          <span class="text-xs text-gray-500"><?= htmlspecialchars($e['klasse_bezeichnung']) ?></span>
          <?php endif; ?>
        </div>
        <h2 class="font-semibold text-gray-900"><?= htmlspecialchars($e['bezeichnung']) ?></h2>
        <p class="text-sm text-gray-500">
          <?= date('d.m.Y', strtotime($e['datum_von'])) ?>
          <?php if ($e['datum_bis'] && $e['datum_bis'] !== $e['datum_von']): ?>
            &ndash; <?= date('d.m.Y', strtotime($e['datum_bis'])) ?>
          <?php endif; ?>
          <?php if ($e['ort']): ?>
            &middot; <?= htmlspecialchars($e['ort']) ?>
          <?php endif; ?>
        </p>
        <p class="text-xs text-gray-400 mt-2">
          <?php $anz = (int)$e['anzahl_subventionen']; ?>
          <?= $anz === 1 ? '1 Subvention zugeordnet' : $anz . ' Subventionen zugeordnet' ?>
        </p>
      </div>
      <div class="flex gap-2 shrink-0">
        <a href="/events_zuordnen.php?id=<?= $e['id'] ?>"
           class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1.5">
          Subventionen zuteilen
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
