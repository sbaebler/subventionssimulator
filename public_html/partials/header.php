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
  <style>[x-cloak]{display:none !important}</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4">
  <a href="/" class="text-lg font-semibold text-blue-700 hover:text-blue-900">
    <?= APP_NAME ?>
  </a>
  <span class="text-gray-300">|</span>
  <nav class="flex items-center gap-4 text-sm">
    <a href="/"               class="hover:text-blue-600">Förderprogramme</a>
    <a href="/simulieren.php" class="hover:text-blue-600">Simulator</a>
    <a href="/verwendung.php" class="hover:text-blue-600">Beiträge &amp; Verwendung</a>
    <div class="relative" x-data="{ offen: false }" @click.outside="offen = false">
      <button type="button" @click="offen = !offen"
              class="hover:text-blue-600 flex items-center gap-1">
        Mehr
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div x-show="offen" x-cloak
           class="absolute left-0 top-full mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-20">
        <a href="/jahresbeitraege.php" class="block px-4 py-2 hover:bg-gray-50">Jahresbeiträge</a>
        <a href="/events.php"          class="block px-4 py-2 hover:bg-gray-50">Events</a>
        <a href="/anleitung.php"       class="block px-4 py-2 hover:bg-gray-50">Anleitung</a>
        <a href="/docs/fachlogik.html" class="block px-4 py-2 hover:bg-gray-50" target="_blank">Fachlogik</a>
        <a href="/releases.php"        class="block px-4 py-2 hover:bg-gray-50">Neuigkeiten</a>
        <a href="/benutzer.php"        class="block px-4 py-2 hover:bg-gray-50">Benutzer</a>
      </div>
    </div>
  </nav>
  <div class="ml-auto flex items-center gap-3">
    <span class="text-sm text-gray-400"><?= htmlspecialchars(auth_anzeigename()) ?></span>
    <a href="/logout.php" class="text-sm text-gray-400 hover:text-red-600">Abmelden</a>
  </div>
</header>

<main class="max-w-5xl mx-auto px-6 py-8">
