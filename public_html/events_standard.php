<?php
require_once __DIR__ . '/../includes/Event.php';
require_once __DIR__ . '/../includes/Subvention.php';
require_once __DIR__ . '/../includes/auth.php';

// Login erzwingen, bevor der POST verarbeitet wird (Muster wie events_zuordnen.php).
auth_erforderlich();

$fehler = [];

// POST: Standard speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gewaehlt = array_map('intval', $_POST['standard'] ?? []);
    $standard = [];
    foreach ($gewaehlt as $sid) {
        $standard[$sid] = [
            'klassen'    => array_map('intval', $_POST['ausschluss'][$sid] ?? []),
            'kategorien' => array_map('strval', $_POST['ausschluss_kategorie'][$sid] ?? []),
        ];
    }
    try {
        Event::standardSpeichern($standard);
        header('Location: /events.php?standard_gespeichert=1');
        exit;
    } catch (Throwable $e) {
        error_log('[Subventionssimulator] standardSpeichern() Fehler: ' . $e->getMessage());
        $fehler[] = 'Beim Speichern ist ein Fehler aufgetreten. Details im Server-Log.';
    }
}

$pageTitle    = 'Standard-Förderprogramme';
$subventionen = Subvention::alle();
$klassen      = Event::klassen();
$standard     = Event::standardProgramme();

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <a href="/events.php" class="text-sm link-muted">&larr; Zurück zu den Events</a>
</div>

<div class="page-header mb-2">
  <h1 class="text-2xl font-semibold">Standard-Förderprogramme</h1>
</div>
<p class="text-sm text-muted mb-6">
  Programme, die hier angehakt sind, gelten für <strong>alle Events</strong> &ndash;
  auch für neue, sobald sie im Class Manager Tool eröffnet werden. Einzelne
  Klassen und Eventkategorien (Breitensport, RLZ, Privat) kannst du ausnehmen. Pro Event lässt sich der Standard weiterhin
  anpassen (&laquo;Subventionen zuteilen&raquo;).
</p>

<?php if ($fehler): ?>
<div class="alert alert--error mb-6">
  <?php foreach ($fehler as $f): ?><div><?= htmlspecialchars($f) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($subventionen)): ?>
  <p class="text-muted text-sm">Noch keine Förderprogramme erfasst.
    <a href="/erfassen.php" class="link">Jetzt erfassen</a>
  </p>
<?php else: ?>
<form method="post" action="/events_standard.php">
  <div class="grid gap-3 mb-6">
    <?php foreach ($subventionen as $s): ?>
    <?php
      $sid     = (int)$s['id'];
      $istStd  = isset($standard[$sid]);
      $ausgen  = $standard[$sid]['klassen'] ?? [];
      $ausgenKat = $standard[$sid]['kategorien'] ?? [];
    ?>
    <div class="card px-4 py-3" x-data="{ aktiv: <?= $istStd ? 'true' : 'false' ?> }">
      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="standard[]" value="<?= $sid ?>"
               class="checkbox mt-1" x-model="aktiv">
        <span>
          <span class="font-medium"><?= htmlspecialchars($s['bezeichnung']) ?></span>
          <span class="block text-sm text-muted"><?= htmlspecialchars($s['foerderstelle']) ?></span>
        </span>
      </label>
      <div x-show="aktiv" x-cloak class="mt-3 ml-7">
        <p class="text-xs text-muted mb-2">Gilt <strong>nicht</strong> für die Kategorie:</p>
        <div class="flex flex-wrap gap-x-4 gap-y-2 mb-3">
          <?php foreach (Event::KATEGORIEN as $kat => $label): ?>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="ausschluss_kategorie[<?= $sid ?>][]" value="<?= $kat ?>"
                   class="checkbox"
                   <?= in_array($kat, $ausgenKat, true) ? 'checked' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </label>
          <?php endforeach; ?>
        </div>
        <?php if ($klassen): ?>
        <p class="text-xs text-muted mb-2">Gilt <strong>nicht</strong> für die Klasse:</p>
        <div class="flex flex-wrap gap-x-4 gap-y-2">
          <?php foreach ($klassen as $k): ?>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="ausschluss[<?= $sid ?>][]" value="<?= (int)$k['id'] ?>"
                   class="checkbox"
                   <?= in_array((int)$k['id'], $ausgen, true) ? 'checked' : '' ?>>
            <?= htmlspecialchars($k['bezeichnung']) ?>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="flex gap-2">
    <button type="submit" class="btn btn--primary btn--sm">Standard speichern</button>
    <a href="/events.php" class="btn btn--secondary">Abbrechen</a>
  </div>
</form>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
