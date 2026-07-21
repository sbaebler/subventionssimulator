<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_reset.php';

if (auth_eingeloggt()) {
    header('Location: /');
    exit;
}

$token  = $_GET['token'] ?? ($_POST['token'] ?? '');
$reset  = passwort_reset_gueltig($token);
$fehler = [];

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwort     = $_POST['passwort'] ?? '';
    $passwortWdh  = $_POST['passwort_bestaetigung'] ?? '';

    if (strlen($passwort) < 8) {
        $fehler[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    }
    if ($passwort !== $passwortWdh) {
        $fehler[] = 'Die Passwörter stimmen nicht überein.';
    }

    if (empty($fehler)) {
        if (passwort_reset_einloesen($token, $passwort)) {
            header('Location: /login.php?zurueckgesetzt=1');
            exit;
        }
        $fehler[] = 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.';
        $reset = null;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Passwort zurücksetzen – <?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Subventionssimulator' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/css/theme.css">
  <link rel="stylesheet" href="/assets/css/shared-ui.css">
</head>
<body class="min-h-screen flex items-center justify-center">

<div class="card w-full max-w-sm shadow-sm p-8">
  <h1 class="text-xl font-semibold text-center mb-1">Passwort zurücksetzen</h1>

  <?php if (!$reset): ?>
    <p class="text-sm text-muted text-center mb-6">
      Dieser Link ist ungültig oder abgelaufen.
    </p>
    <?php if ($fehler): ?>
    <div class="alert alert--error mb-6">
      <ul class="list-disc list-inside">
        <?php foreach ($fehler as $f): ?>
        <li><?= htmlspecialchars($f) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <a href="/passwort-vergessen.php" class="btn btn--primary w-full">
      Neuen Link anfordern
    </a>
  <?php else: ?>
    <p class="text-sm text-muted text-center mb-6">
      Hallo <?= htmlspecialchars($reset['anzeigename']) ?>, setze dein neues Passwort.
    </p>

    <?php if ($fehler): ?>
    <div class="alert alert--error mb-4">
      <ul class="list-disc list-inside">
        <?php foreach ($fehler as $f): ?>
        <li><?= htmlspecialchars($f) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="post" action="/passwort-zuruecksetzen.php">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="mb-4">
        <label for="passwort" class="block text-sm font-medium mb-1">Neues Passwort</label>
        <input type="password" id="passwort" name="passwort" required autofocus
               autocomplete="new-password"
               class="input">
      </div>
      <div class="mb-6">
        <label for="passwort_bestaetigung" class="block text-sm font-medium mb-1">Passwort bestätigen</label>
        <input type="password" id="passwort_bestaetigung" name="passwort_bestaetigung" required
               autocomplete="new-password"
               class="input">
      </div>
      <button type="submit" class="btn btn--primary w-full">
        Passwort speichern
      </button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
