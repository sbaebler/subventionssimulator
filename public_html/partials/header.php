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
  <link rel="stylesheet" href="/assets/css/theme.css">
  <link rel="stylesheet" href="/assets/css/shared-ui.css">
</head>
<body class="min-h-screen">

<header class="navbar" x-data="{ navOffen: false }">
  <a href="/" class="navbar__brand"><?= APP_NAME ?></a>

  <button type="button" class="navbar__toggle" @click="navOffen = !navOffen"
          :aria-expanded="navOffen.toString()" aria-label="Menü öffnen">
    <svg x-show="!navOffen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    <svg x-show="navOffen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
  </button>

  <nav class="navbar__nav" :class="{ 'is-open': navOffen }">
    <a href="/"               class="navbar__link">Förderprogramme</a>
    <a href="/simulieren.php" class="navbar__link">Simulator</a>
    <a href="/verwendung.php" class="navbar__link">Beiträge &amp; Verwendung</a>
    <div class="relative" x-data="{ offen: false }" @click.outside="offen = false">
      <button type="button" @click="offen = !offen" class="navbar__link navbar__submenu-toggle">
        Mehr
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div x-show="offen" x-cloak
           class="menu absolute left-0 top-full mt-2 w-48 z-20">
        <a href="/jahresbeitraege.php" class="menu__item">Jahresbeiträge</a>
        <a href="/events.php"          class="menu__item">Events</a>
        <a href="/anleitung.php"       class="menu__item">Anleitung</a>
        <a href="/docs/fachlogik.html" class="menu__item" target="_blank">Fachlogik</a>
        <a href="/releases.php"        class="menu__item">Neuigkeiten</a>
        <a href="/benutzer.php"        class="menu__item">Benutzer</a>
        <a href="/papierkorb.php"      class="menu__item">Papierkorb</a>
      </div>
    </div>
  </nav>

  <div class="navbar__user" :class="{ 'is-open': navOffen }">
    <span class="text-sm text-subtle"><?= htmlspecialchars(auth_anzeigename()) ?></span>
    <a href="/logout.php" class="text-sm link--danger">Abmelden</a>
  </div>
</header>

<main class="max-w-5xl mx-auto px-6 py-8">
