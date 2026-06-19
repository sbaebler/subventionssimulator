<?php
require_once __DIR__ . '/../includes/Event.php';
require_once __DIR__ . '/../includes/Subvention.php';
require_once __DIR__ . '/../includes/auth.php';

// Session starten und Login erzwingen, BEVOR der POST verarbeitet wird –
// sonst liefert auth_benutzer_id() unten 0 und das Speichern verletzt den
// Fremdschlüssel auf benutzer(id). Gleiches Muster wie in erfassen.php.
auth_erforderlich();

$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$fehler = [];

// POST: Zuordnung speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)($_POST['event_id'] ?? 0);
    try {
        Event::zuordnungSpeichern($event_id, $_POST['subventionen'] ?? [], auth_benutzer_id() ?: null);
        header('Location: /events.php?gespeichert=1');
        exit;
    } catch (Throwable $e) {
        error_log('[Subventionssimulator] zuordnungSpeichern() Fehler: ' . $e->getMessage());
        $fehler[] = 'Beim Speichern ist ein Fehler aufgetreten. Details im Server-Log.';
    }
}

$event = $id ? Event::laden($id) : null;
$pageTitle = 'Subventionen zuteilen';

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <a href="/events.php" class="text-sm text-gray-400 hover:text-blue-600">&larr; Zurück zu den Events</a>
</div>

<?php if (!$event): ?>
  <p class="text-gray-500">Event nicht gefunden.</p>
<?php else: ?>

  <?php
    $subventionen = Subvention::alle();
    $zugeordnet   = Event::zugeordneteSubventionIds($id);
  ?>

  <div class="mb-6">
    <div class="flex items-center gap-2 mb-1">
      <span class="text-xs font-medium px-2 py-0.5 rounded <?= Event::STATUS_BADGE[$event['status']] ?? 'bg-gray-100 text-gray-500' ?>">
        <?= htmlspecialchars(Event::STATUS[$event['status']] ?? $event['status']) ?>
      </span>
      <?php if ($event['klasse_bezeichnung']): ?>
      <span class="text-xs text-gray-500"><?= htmlspecialchars($event['klasse_bezeichnung']) ?></span>
      <?php endif; ?>
    </div>
    <h1 class="text-2xl font-semibold"><?= htmlspecialchars($event['bezeichnung']) ?></h1>
    <p class="text-sm text-gray-500 mt-1">
      <?= date('d.m.Y', strtotime($event['datum_von'])) ?>
      <?php if ($event['datum_bis'] && $event['datum_bis'] !== $event['datum_von']): ?>
        &ndash; <?= date('d.m.Y', strtotime($event['datum_bis'])) ?>
      <?php endif; ?>
      <?php if ($event['ort']): ?>
        &middot; <?= htmlspecialchars($event['ort']) ?>
      <?php endif; ?>
    </p>
  </div>

  <?php if ($fehler): ?>
  <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 mb-6">
    <?php foreach ($fehler as $f): ?><div><?= htmlspecialchars($f) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="post" action="/events_zuordnen.php?id=<?= $event['id'] ?>">
    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">

    <h2 class="text-sm font-medium text-gray-700 mb-3">Welche Subventionen kommen für dieses Event zum Tragen?</h2>

    <?php if (empty($subventionen)): ?>
      <p class="text-gray-500 text-sm">Noch keine Subventionen erfasst.
        <a href="/erfassen.php" class="text-blue-600 underline">Jetzt erfassen</a>
      </p>
    <?php else: ?>
      <div class="grid gap-2 mb-6">
        <?php foreach ($subventionen as $s): ?>
        <label class="flex items-start gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-50">
          <input type="checkbox" name="subventionen[]" value="<?= $s['id'] ?>"
                 class="mt-1"
                 <?= in_array((int)$s['id'], $zugeordnet, true) ? 'checked' : '' ?>>
          <span>
            <span class="font-medium text-gray-900"><?= htmlspecialchars($s['bezeichnung']) ?></span>
            <span class="block text-sm text-gray-500"><?= htmlspecialchars($s['foerderstelle']) ?></span>
          </span>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="flex gap-2">
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg">
          Zuordnung speichern
        </button>
        <a href="/events.php" class="text-sm text-gray-500 hover:text-gray-800 border border-gray-200 rounded-lg px-4 py-2">
          Abbrechen
        </a>
      </div>
    <?php endif; ?>
  </form>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
