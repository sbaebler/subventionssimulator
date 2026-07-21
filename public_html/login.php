<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (auth_eingeloggt()) {
    header('Location: /');
    exit;
}

$erfolg = isset($_GET['zurueckgesetzt']);
$fehler = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $benutzer = trim($_POST['benutzer'] ?? '');
    $passwort = $_POST['passwort'] ?? '';
    if (auth_anmelden($benutzer, $passwort)) {
        $weiter = $_GET['weiter'] ?? '/';
        // Nur relative Weiterleitungen erlauben
        if (!str_starts_with($weiter, '/') || str_starts_with($weiter, '//')) {
            $weiter = '/';
        }
        header('Location: ' . $weiter);
        exit;
    }
    $fehler = true;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Anmelden – <?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Subventionssimulator' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/css/theme.css">
  <link rel="stylesheet" href="/assets/css/shared-ui.css">
</head>
<body class="min-h-screen flex items-center justify-center">

<div class="card w-full max-w-sm shadow-sm p-8">
  <h1 class="text-xl font-semibold text-center mb-1">
    <?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Subventionssimulator' ?>
  </h1>
  <p class="text-sm text-muted text-center mb-6">Bitte melde dich an</p>

  <?php if ($erfolg): ?>
  <div class="alert alert--success mb-4">
    Passwort erfolgreich geändert. Du kannst dich jetzt anmelden.
  </div>
  <?php endif; ?>

  <?php if ($fehler): ?>
  <div class="alert alert--error mb-4">
    Benutzername oder Passwort falsch.
  </div>
  <?php endif; ?>

  <form method="post" action="/login.php<?= isset($_GET['weiter']) ? '?weiter=' . urlencode($_GET['weiter']) : '' ?>">
    <div class="mb-4">
      <label for="benutzer" class="block text-sm font-medium mb-1">Benutzername</label>
      <input type="text" id="benutzer" name="benutzer" required autofocus
             value="<?= htmlspecialchars($_POST['benutzer'] ?? '') ?>"
             class="input">
    </div>
    <div class="mb-6">
      <label for="passwort" class="block text-sm font-medium mb-1">Passwort</label>
      <input type="password" id="passwort" name="passwort" required
             class="input">
    </div>
    <button type="submit" class="btn btn--primary w-full">
      Anmelden
    </button>
  </form>

  <p class="text-sm text-center mt-4">
    <a href="/passwort-vergessen.php" class="link-muted">Passwort vergessen?</a>
  </p>
</div>

</body>
</html>
