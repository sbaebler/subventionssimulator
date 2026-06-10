<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (auth_eingeloggt()) {
    header('Location: /');
    exit;
}

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
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex items-center justify-center">

<div class="w-full max-w-sm bg-white border border-gray-200 rounded-xl shadow-sm p-8">
  <h1 class="text-xl font-semibold text-center mb-1">
    <?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Subventionssimulator' ?>
  </h1>
  <p class="text-sm text-gray-500 text-center mb-6">Bitte melde dich an</p>

  <?php if ($fehler): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
    Benutzername oder Passwort falsch.
  </div>
  <?php endif; ?>

  <form method="post" action="/login.php<?= isset($_GET['weiter']) ? '?weiter=' . urlencode($_GET['weiter']) : '' ?>">
    <div class="mb-4">
      <label for="benutzer" class="block text-sm font-medium mb-1">Benutzername</label>
      <input type="text" id="benutzer" name="benutzer" required autofocus
             value="<?= htmlspecialchars($_POST['benutzer'] ?? '') ?>"
             class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
    </div>
    <div class="mb-6">
      <label for="passwort" class="block text-sm font-medium mb-1">Passwort</label>
      <input type="password" id="passwort" name="passwort" required
             class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
    </div>
    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-4 py-2 rounded-lg">
      Anmelden
    </button>
  </form>
</div>

</body>
</html>
