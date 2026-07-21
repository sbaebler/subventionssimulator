<?php
require_once __DIR__ . '/../includes/Subvention.php';
require_once __DIR__ . '/../includes/auth.php';

$fehler  = [];
$erfolg  = false;
$benutzer_edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aktion = $_POST['aktion'] ?? '';

    if ($aktion === 'speichern') {
        $id           = $_POST['id'] ? (int)$_POST['id'] : null;
        $benutzername = trim($_POST['benutzername'] ?? '');
        $anzeigename  = trim($_POST['anzeigename']  ?? '');
        $passwort     = $_POST['passwort'] ?? '';

        if (empty($benutzername)) $fehler[] = 'Benutzername ist pflicht.';
        if (empty($anzeigename))  $fehler[] = 'Anzeigename ist pflicht.';
        if (!$id && empty($passwort)) $fehler[] = 'Passwort ist pflicht für neue Benutzer.';

        if (empty($fehler)) {
            try {
                if ($id) {
                    if (!empty($passwort)) {
                        $stmt = db()->prepare(
                            'UPDATE benutzer SET benutzername = ?, anzeigename = ?, passwort_hash = ? WHERE id = ?'
                        );
                        $stmt->execute([$benutzername, $anzeigename, password_hash($passwort, PASSWORD_DEFAULT), $id]);
                    } else {
                        $stmt = db()->prepare(
                            'UPDATE benutzer SET benutzername = ?, anzeigename = ? WHERE id = ?'
                        );
                        $stmt->execute([$benutzername, $anzeigename, $id]);
                    }
                } else {
                    $stmt = db()->prepare(
                        'INSERT INTO benutzer (benutzername, anzeigename, passwort_hash) VALUES (?, ?, ?)'
                    );
                    $stmt->execute([$benutzername, $anzeigename, password_hash($passwort, PASSWORD_DEFAULT)]);
                }
                header('Location: /benutzer.php?gespeichert=1');
                exit;
            } catch (Throwable $e) {
                error_log('[Subventionssimulator] benutzer speichern() Fehler: ' . $e->getMessage());
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $fehler[] = 'Dieser Benutzername ist bereits vergeben.';
                } else {
                    $fehler[] = 'Beim Speichern ist ein Fehler aufgetreten.';
                }
            }
        }
    } elseif ($aktion === 'toggle_aktiv') {
        $id    = (int)$_POST['id'];
        $aktiv = (int)$_POST['aktiv'];
        db()->prepare('UPDATE benutzer SET aktiv = ? WHERE id = ?')->execute([$aktiv, $id]);
        header('Location: /benutzer.php');
        exit;
    }
}

if (isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM benutzer WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    $benutzer_edit = $stmt->fetch() ?: null;
}

if (isset($_GET['gespeichert'])) $erfolg = true;

$alle_benutzer = db()->query('SELECT * FROM benutzer ORDER BY benutzername')->fetchAll();

$pageTitle = 'Benutzerverwaltung';
require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-semibold">Benutzerverwaltung</h1>
</div>

<?php if ($erfolg): ?>
<div class="alert alert--success mb-6">
  Benutzer wurde gespeichert.
</div>
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

<!-- Formular: Benutzer anlegen / bearbeiten -->
<section class="card mb-8">
  <h2 class="font-semibold mb-4">
    <?= $benutzer_edit ? 'Benutzer bearbeiten' : 'Neuen Benutzer anlegen' ?>
  </h2>
  <form method="post" action="/benutzer.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="aktion" value="speichern">
    <input type="hidden" name="id" value="<?= $benutzer_edit['id'] ?? '' ?>">

    <div>
      <label class="block text-sm font-medium mb-1">Benutzername *</label>
      <input type="text" name="benutzername" required
             value="<?= htmlspecialchars($benutzer_edit['benutzername'] ?? '') ?>"
             class="input"
             placeholder="z.B. jsmith">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Anzeigename *</label>
      <input type="text" name="anzeigename" required
             value="<?= htmlspecialchars($benutzer_edit['anzeigename'] ?? '') ?>"
             class="input"
             placeholder="z.B. Jana Smith">
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        Passwort <?= $benutzer_edit ? '(leer lassen = unverändert)' : '*' ?>
      </label>
      <input type="password" name="passwort" <?= $benutzer_edit ? '' : 'required' ?>
             class="input"
             autocomplete="new-password">
    </div>

    <div class="md:col-span-2 flex gap-3 justify-end">
      <?php if ($benutzer_edit): ?>
      <a href="/benutzer.php" class="text-sm link-muted px-4 py-2">Abbrechen</a>
      <?php endif; ?>
      <button type="submit"
              class="btn btn--primary">
        <?= $benutzer_edit ? 'Speichern' : 'Benutzer anlegen' ?>
      </button>
    </div>
  </form>
</section>

<!-- Liste aller Benutzer -->
<section class="card p-0 overflow-hidden">
  <table class="table">
    <thead>
      <tr>
        <th>Benutzername</th>
        <th>Anzeigename</th>
        <th>Status</th>
        <th>Erfasst am</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($alle_benutzer as $b): ?>
      <tr class="<?= $b['aktiv'] ? '' : 'opacity-50' ?>">
        <td class="font-mono"><?= htmlspecialchars($b['benutzername']) ?></td>
        <td><?= htmlspecialchars($b['anzeigename']) ?></td>
        <td>
          <?php if ($b['aktiv']): ?>
            <span class="badge badge--success">Aktiv</span>
          <?php else: ?>
            <span class="badge badge--neutral">Inaktiv</span>
          <?php endif; ?>
        </td>
        <td class="text-muted"><?= date('d.m.Y', strtotime($b['erstellt_am'])) ?></td>
        <td class="flex gap-2 justify-end">
          <a href="/benutzer.php?id=<?= $b['id'] ?>" class="btn btn--secondary btn--sm text-xs">
            Bearbeiten
          </a>
          <form method="post" action="/benutzer.php" class="inline">
            <input type="hidden" name="aktion" value="toggle_aktiv">
            <input type="hidden" name="id"    value="<?= $b['id'] ?>">
            <input type="hidden" name="aktiv" value="<?= $b['aktiv'] ? 0 : 1 ?>">
            <button type="submit" class="btn btn--secondary btn--sm text-xs">
              <?= $b['aktiv'] ? 'Deaktivieren' : 'Aktivieren' ?>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
