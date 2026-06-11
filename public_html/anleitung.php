<?php
$pageTitle = 'Anleitung';
require __DIR__ . '/partials/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl font-semibold">Anleitung</h1>
  <p class="text-sm text-gray-500 mt-1">Subventionssimulator · Zurich Sailing Federation</p>
</div>

<!-- Übersicht -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <h2 class="font-semibold text-gray-700 mb-3">Was macht diese Applikation?</h2>
  <p class="text-sm text-gray-600 leading-relaxed">
    Der Subventionssimulator hilft dir, Sportsubventionen (J+S, NWF, kantonal, vereinsintern) zu verwalten
    und für ein konkretes Lager oder Training schnell zu berechnen, welche Förderbeiträge beantragt werden können
    und wie hoch sie voraussichtlich ausfallen.
  </p>
</section>

<!-- Schritt 1 -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">1</span>
    <h2 class="font-semibold text-gray-700">Subventionen erfassen</h2>
  </div>
  <p class="text-sm text-gray-600 leading-relaxed mb-4">
    Unter <strong>«Neue Subvention»</strong> kannst du einen Fördertopf mit allen relevanten Parametern anlegen.
  </p>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">Stammdaten</p>
      <ul class="text-gray-600 space-y-1 list-disc list-inside">
        <li>Bezeichnung und Förderstelle</li>
        <li>Kategorie (Ausbildung, Lager, Wettkampf …)</li>
        <li>Voraussetzungen und Fristen</li>
        <li>Gültigkeitszeitraum</li>
      </ul>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">Betragsregel</p>
      <ul class="text-gray-600 space-y-1 list-disc list-inside">
        <li>Grundbetrag (fix)</li>
        <li>Betrag pro Teilnehmer / pro Tag</li>
        <li>Maximale Teilnehmer- und Tageszahl</li>
        <li>Maximaler Gesamtbetrag (0 = kein Limit)</li>
      </ul>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">Trainerarten</p>
      <p class="text-gray-600">Lege fest, welche Traineranerkennungen förderberechtigt sind
      (J+S, NWF, ohne) und ob ein Trainerbonus ausbezahlt wird.</p>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">Eventarten</p>
      <p class="text-gray-600">Bestimme, ob der Beitrag für Lager, Trainings oder beides gilt,
      und setze optional einen Multiplikator (z.B. 1.2 = 20 % Bonus für Lager).</p>
    </div>
  </div>
</section>

<!-- Schritt 2 -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">2</span>
    <h2 class="font-semibold text-gray-700">Übersicht nutzen</h2>
  </div>
  <p class="text-sm text-gray-600 leading-relaxed">
    Die <strong>Übersicht</strong> zeigt alle aktiven Subventionen auf einen Blick.
    Du kannst eine bestehende Subvention bearbeiten, indem du auf den Titel klickst.
    Inaktive oder abgelaufene Subventionen werden automatisch ausgeblendet.
  </p>
</section>

<!-- Schritt 3 -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">3</span>
    <h2 class="font-semibold text-gray-700">Simulation durchführen</h2>
  </div>
  <p class="text-sm text-gray-600 leading-relaxed mb-4">
    Unter <strong>«Simulator»</strong> gibst du die Eckdaten deines Events ein und klickst auf
    <strong>«Berechnen»</strong>. Der Simulator prüft automatisch alle aktiven Subventionen.
  </p>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-5">
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">Eingaben</p>
      <ul class="text-gray-600 space-y-1 list-disc list-inside">
        <li>Anzahl Teilnehmende</li>
        <li>Anzahl Tage</li>
        <li>Trainerart (J+S, NWF, ohne Anerkennung)</li>
        <li>Eventart (Lager oder Training)</li>
      </ul>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">Ergebnis</p>
      <ul class="text-gray-600 space-y-1 list-disc list-inside">
        <li>Zusammenfassung: Anzahl berechtigte Töpfe + Gesamtbetrag</li>
        <li>Grüne Karte pro berechtigter Subvention mit CHF-Betrag</li>
        <li>«Aufschlüsselung» zeigt Grundbetrag, TN-Anteil, Tagesanteil, Trainerbonus und Faktor</li>
        <li>Ausgeblendete Karte mit Begründung für nicht berechtigte Subventionen</li>
      </ul>
    </div>
  </div>

  <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-sm text-blue-700">
    <strong>Tipp:</strong> In der Übersicht kannst du bei jeder Subvention direkt auf
    <strong>«Simulieren»</strong> klicken — der Simulator öffnet sich mit vorausgefüllten Standardwerten.
  </div>
</section>

<!-- Berechnungsformel -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <h2 class="font-semibold text-gray-700 mb-3">Berechnungsformel</h2>
  <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm text-gray-700 leading-relaxed">
    Betrag = Grundbetrag<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;+ (Betrag/TN × min(Anzahl TN, Max. TN))<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;+ (Betrag/Tag × min(Anzahl Tage, Max. Tage))<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;+ Trainerbonus<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;× Eventart-Multiplikator<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;→ max(Betrag, Max. Gesamtbetrag)
  </div>
  <p class="text-xs text-gray-400 mt-2">Max.-Werte von 0 bedeuten «kein Limit».</p>
</section>

<!-- Hinweise -->
<section class="bg-white border border-gray-200 rounded-xl p-6">
  <h2 class="font-semibold text-gray-700 mb-3">Hinweise</h2>
  <ul class="text-sm text-gray-600 space-y-2">
    <li>⚠️ Die berechneten Beträge sind <strong>Schätzwerte</strong> auf Basis der erfassten Regeln. Massgebend sind stets die offiziellen Richtlinien der jeweiligen Förderstelle.</li>
    <li>📅 Subventionen mit abgelaufenem Gültigkeitsdatum werden in der Übersicht und im Simulator nicht mehr angezeigt.</li>
    <li>🔒 Die Applikation ist passwortgeschützt. Teile den Zugang nur mit berechtigten Personen des Vereins.</li>
  </ul>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
