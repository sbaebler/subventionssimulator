<?php
require_once __DIR__ . '/../includes/Subvention.php';
require_once __DIR__ . '/../includes/auth.php';

auth_erforderlich();

$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aktion = $_POST['aktion'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($aktion === 'wiederherstellen' && $id) {
        Subvention::wiederherstellen($id);
        header('Location: /papierkorb.php?wiederhergestellt=1');
        exit;
    }

    if ($aktion === 'endgueltig_loeschen' && $id) {
        try {
            Subvention::endgueltigLoeschen($id);
            header('Location: /papierkorb.php?geloescht=1');
            exit;
        } catch (Throwable $e) {
            error_log('[Subventionssimulator] endgueltigLoeschen() Fehler: ' . $e->getMessage());
            $fehler[] = 'Beim endgültigen Löschen ist ein Fehler aufgetreten. Details im Server-Log.';
        }
    }
}

$pageTitle = 'Papierkorb';
$eintraege = Subvention::papierkorb();

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <a href="/" class="text-sm link-muted">&larr; Zurück zur Übersicht</a>
  <h1 class="text-2xl font-semibold mt-2">Papierkorb</h1>
  <p class="text-sm text-muted mt-1">
    In den Papierkorb verschobene Förderprogramme. Sie erscheinen nicht mehr in der Übersicht
    oder im Simulator, können aber wiederhergestellt werden, solange sie hier liegen.
  </p>
</div>

<?php if (isset($_GET['wiederhergestellt'])): ?>
<div class="alert alert--success mb-6">Förderprogramm wurde wiederhergestellt.</div>
<?php endif; ?>

<?php if (isset($_GET['geloescht'])): ?>
<div class="alert alert--success mb-6">Förderprogramm wurde endgültig gelöscht.</div>
<?php endif; ?>

<?php if ($fehler): ?>
<div class="alert alert--error mb-6">
  <ul class="list-disc list-inside">
    <?php foreach ($fehler as $f): ?>
    <li><?= htmlspecialchars($f) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if (empty($eintraege)): ?>
  <p class="empty-state">Der Papierkorb ist leer.</p>
<?php else: ?>
  <div class="grid gap-4">
    <?php foreach ($eintraege as $s): ?>
    <div class="card flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <span class="badge badge--info">
            <?= htmlspecialchars(Subvention::KATEGORIEN[$s['kategorie']] ?? $s['kategorie']) ?>
          </span>
        </div>
        <h2 class="font-semibold"><?= htmlspecialchars($s['bezeichnung']) ?></h2>
        <p class="text-sm text-muted"><?= htmlspecialchars($s['foerderstelle']) ?></p>
        <p class="text-xs text-subtle mt-2">
          Gelöscht<?php if ($s['geloescht_von_name']): ?> von <?= htmlspecialchars($s['geloescht_von_name']) ?><?php endif; ?>
          am <?= date('d.m.Y H:i', strtotime($s['geloescht_am'])) ?> Uhr
        </p>
      </div>
      <div class="flex flex-wrap gap-2 sm:shrink-0">
        <form method="post" action="/papierkorb.php" class="inline">
          <input type="hidden" name="aktion" value="wiederherstellen">
          <input type="hidden" name="id" value="<?= $s['id'] ?>">
          <button type="submit" class="btn btn--secondary btn--sm">
            Wiederherstellen
          </button>
        </form>
        <form method="post" action="/papierkorb.php" class="inline"
              onsubmit="return confirm('«<?= htmlspecialchars(addslashes($s['bezeichnung'])) ?>» unwiderruflich löschen? Dies kann nicht rückgängig gemacht werden.');">
          <input type="hidden" name="aktion" value="endgueltig_loeschen">
          <input type="hidden" name="id" value="<?= $s['id'] ?>">
          <button type="submit" class="btn btn--danger btn--sm">
            Endgültig löschen
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
