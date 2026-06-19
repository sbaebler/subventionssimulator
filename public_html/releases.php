<?php
$pageTitle = 'Neuigkeiten';
require __DIR__ . '/partials/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl font-semibold">Neuigkeiten</h1>
  <p class="text-sm text-gray-500 mt-1">Was sich im Subventionssimulator geändert hat</p>
</div>

<!-- Version 2 -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-1">
    <span class="text-xs font-medium bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Version 2</span>
    <span class="text-xs text-gray-400">19.06.2026</span>
  </div>
  <h2 class="font-semibold text-gray-800 mb-3">Events mit Subventionen verknüpfen</h2>

  <p class="text-sm text-gray-600 leading-relaxed mb-4">
    Der Subventionssimulator ist jetzt mit dem <strong>Class Manager Tool</strong> verbunden.
    Alle Anlässe (Events), die ein Class Manager dort eröffnet – zum Beispiel ein Trainingslager
    oder eine Regatta – erscheinen neu automatisch auch hier. Du musst sie nirgends doppelt erfassen.
    Für jedes Event kannst du festlegen, <strong>welche Subventionen dafür in Frage kommen</strong>.
  </p>

  <p class="font-medium text-gray-700 text-sm mb-2">So funktioniert es</p>
  <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside mb-4">
    <li><strong>Events ansehen:</strong> Über den Navigationspunkt «Events» siehst du alle Anlässe
        mit Datum, Ort, Segelklasse und Status.</li>
    <li><strong>Subventionen zuteilen:</strong> Klicke bei einem Event auf «Subventionen zuteilen»,
        setze bei den passenden Subventionen ein Häkchen und speichere.</li>
    <li><strong>Überblick behalten:</strong> In der Event-Liste steht bei jedem Anlass, wie viele
        Subventionen ihm bereits zugeordnet sind.</li>
  </ol>

  <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
    <p class="font-medium text-gray-700 mb-1">Gut zu wissen</p>
    <ul class="space-y-1 list-disc list-inside">
      <li>Events werden weiterhin im Class Manager Tool erstellt und bearbeitet – hier werden sie
          nur angezeigt und mit Subventionen verknüpft.</li>
      <li>Diese Version ordnet Subventionen lediglich zu und berechnet noch keine konkreten Beträge
          pro Event. Dafür nutzt du wie bisher den <strong>Simulator</strong>.</li>
    </ul>
  </div>
</section>

<!-- Version 1 -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-1">
    <span class="text-xs font-medium bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Version 1</span>
    <span class="text-xs text-gray-400">Erste Version</span>
  </div>
  <h2 class="font-semibold text-gray-800 mb-3">Grundfunktionen</h2>
  <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
    <li>Erfassen und Verwalten von Subventionstöpfen (J+S, NWF, kantonal)</li>
    <li>Simulator zur Berechnung von Subventionsbeträgen</li>
    <li>Benutzerverwaltung mit Login</li>
  </ul>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
