<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_erforderlich();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4">
  <a href="/" class="text-lg font-semibold text-blue-700 hover:text-blue-900">
    <?= APP_NAME ?>
  </a>
  <span class="text-gray-300">|</span>
  <nav class="flex gap-4 text-sm">
    <a href="/"           class="hover:text-blue-600">Übersicht</a>
    <a href="/erfassen.php" class="hover:text-blue-600">Neue Subvention</a>
    <a href="/simulieren.php" class="hover:text-blue-600">Simulator</a>
  </nav>
  <a href="/logout.php" class="ml-auto text-sm text-gray-400 hover:text-red-600">Abmelden</a>
</header>

<main class="max-w-5xl mx-auto px-6 py-8">
