<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_reset.php';

if (auth_eingeloggt()) {
    header('Location: /');
    exit;
}

$gesendet = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eingabe = trim($_POST['eingabe'] ?? '');
    if ($eingabe !== '') {
        passwort_reset_anfordern($eingabe);
    }
    $gesendet = true; // immer gleiche Antwort, unabhängig vom Treffer
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Passwort vergessen – <?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Subventionssimulator' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/css/theme.css">
  <link rel="stylesheet" href="/assets/css/shared-ui.css">
</head>
<body class="min-h-screen flex items-center justify-center">

<div class="card w-full max-w-sm shadow-sm p-8">
  <h1 class="text-xl font-semibold text-center mb-1">Passwort vergessen</h1>

  <?php if ($gesendet): ?>
    <p class="text-sm text-muted text-center mb-6">
      Falls ein Konto mit diesen Angaben existiert, haben wir dir eine E-Mail
      mit einem Link zum Zurücksetzen des Passworts gesendet.
    </p>
    <p class="text-sm text-center">
      <a href="/login.php" class="link-muted">Zurück zur Anmeldung</a>
    </p>
  <?php else: ?>
    <p class="text-sm text-muted text-center mb-6">
      Gib deinen Benutzernamen oder deine E-Mail-Adresse ein.
      Wir senden dir einen Link zum Zurücksetzen des Passworts.
    </p>

    <form method="post" action="/passwort-vergessen.php">
      <div class="mb-6">
        <label for="eingabe" class="block text-sm font-medium mb-1">Benutzername oder E-Mail</label>
        <input type="text" id="eingabe" name="eingabe" required autofocus
               class="input">
      </div>
      <button type="submit" class="btn btn--primary w-full">
        Link anfordern
      </button>
    </form>

    <p class="text-sm text-center mt-4">
      <a href="/login.php" class="link-muted">Zurück zur Anmeldung</a>
    </p>
  <?php endif; ?>
</div>

</body>
</html>
