<?php
$pageTitle = 'Neuigkeiten';
require __DIR__ . '/partials/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl font-semibold">Neuigkeiten</h1>
  <p class="text-sm text-muted mt-1">Was sich im Subventionssimulator geändert hat</p>
</div>

<!-- Version 4 -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-1">
    <span class="badge badge--info">Version 4</span>
    <span class="text-xs text-subtle">18.07.2026</span>
  </div>
  <h2 class="font-semibold mb-3">Erfassen ohne Anleitung: neue Nutzerführung</h2>

  <p class="text-sm text-muted leading-relaxed mb-4">
    Die Erfassung wurde von Grund auf vereinfacht: Wer weiss, wie ein Förderbeitrag funktioniert,
    kann dieses Wissen jetzt <strong>ohne Anleitung</strong> eintragen. In den Masken heisst das
    Stammobjekt neu <strong>«Förderprogramm»</strong>.
  </p>

  <ul class="text-sm text-muted space-y-2 list-disc list-inside mb-4">
    <li><strong>Geführte Erfassung in vier Schritten:</strong> Beitrag → Berechnung → Bedingungen →
        Termine &amp; Beträge. Nur noch <strong>Name und Förderstelle sind Pflicht</strong> – alles
        andere kann später ergänzt werden.</li>
    <li><strong>Berechnungsart als Auswahlkarten</strong> in Alltagssprache – inklusive
        «Weiss ich noch nicht»: Berechnung in Worten beschreiben, Zahlen kommen später.</li>
    <li><strong>Probe-Rechnung live:</strong> Beim Eintippen der Sätze zeigt die Maske sofort,
        was ein Beispiel-Anlass ergeben würde.</li>
    <li><strong>Übersicht als Arbeitsliste:</strong> Fortschritt «x von y vollständig erfasst» und
        pro Programm klickbare Hinweise, was noch fehlt.</li>
    <li><strong>Aufgeräumte Navigation:</strong> vier Punkte statt zehn.</li>
    <li><strong>Jahresbetrag direkt erfassen:</strong> Der erhaltene Betrag pro Jahr wird neu direkt
        unter «Beiträge &amp; Verwendung» eingetragen.</li>
  </ul>

  <div class="card--muted text-sm text-muted">
    <p class="font-medium mb-1">Behobener Fehler</p>
    <p>Beim Speichern gingen die typspezifischen Beitragssätze (z. B. J+S-Satz mit/ohne Übernachtung,
    Satz pro Ausbildungseinheit) verloren. Diese Werte werden jetzt korrekt gespeichert.</p>
  </div>
</section>

<!-- Version 3 -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-1">
    <span class="badge badge--info">Version 3</span>
    <span class="text-xs text-subtle">04.07.2026</span>
  </div>
  <h2 class="font-semibold mb-3">Neue Berechnungsarten, Jahresbeiträge und Verwendung</h2>
  <ul class="text-sm text-muted space-y-2 list-disc list-inside">
    <li><strong>Sechs Berechnungsarten</strong> statt einer Einheitsformel: additiv, J+S Teilnehmertag
        (mit/ohne Übernachtung), J+S Teilnehmerstunde, ZKS Ausbildungseinheit, Pauschale und
        Jahresbeitrag nach Kennzahl.</li>
    <li><strong>Mehr Fachinformationen pro Subvention:</strong> Berechtigte, Einschränkungen,
        verlangte Unterlagen, mehrere Fristen sowie die erhaltenen Beträge pro Jahr.</li>
    <li><strong>Neue Seite «Jahresbeiträge»</strong> für Beiträge, die nicht an ein Event gebunden
        sind (z. B. pro Aktivmitglied).</li>
    <li><strong>Neue Seite «Verwendung»:</strong> Erhaltene Jahresbeträge auf Events, Kaderklassen,
        Reserven oder freie Zwecke verteilen – mit laufender Restkontrolle.</li>
  </ul>
</section>

<!-- Version 2 -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-1">
    <span class="badge badge--info">Version 2</span>
    <span class="text-xs text-subtle">19.06.2026</span>
  </div>
  <h2 class="font-semibold mb-3">Events mit Subventionen verknüpfen</h2>

  <p class="text-sm text-muted leading-relaxed mb-4">
    Der Subventionssimulator ist jetzt mit dem <strong>Class Manager Tool</strong> verbunden.
    Alle Anlässe (Events), die ein Class Manager dort eröffnet – zum Beispiel ein Trainingslager
    oder eine Regatta – erscheinen neu automatisch auch hier. Du musst sie nirgends doppelt erfassen.
    Für jedes Event kannst du festlegen, <strong>welche Subventionen dafür in Frage kommen</strong>.
  </p>

  <p class="font-medium text-sm mb-2">So funktioniert es</p>
  <ol class="text-sm text-muted space-y-2 list-decimal list-inside mb-4">
    <li><strong>Events ansehen:</strong> Über den Navigationspunkt «Events» siehst du alle Anlässe
        mit Datum, Ort, Segelklasse und Status.</li>
    <li><strong>Subventionen zuteilen:</strong> Klicke bei einem Event auf «Subventionen zuteilen»,
        setze bei den passenden Subventionen ein Häkchen und speichere.</li>
    <li><strong>Überblick behalten:</strong> In der Event-Liste steht bei jedem Anlass, wie viele
        Subventionen ihm bereits zugeordnet sind.</li>
  </ol>

  <div class="card--muted text-sm text-muted">
    <p class="font-medium mb-1">Gut zu wissen</p>
    <ul class="space-y-1 list-disc list-inside">
      <li>Events werden weiterhin im Class Manager Tool erstellt und bearbeitet – hier werden sie
          nur angezeigt und mit Subventionen verknüpft.</li>
      <li>Diese Version ordnet Subventionen lediglich zu und berechnet noch keine konkreten Beträge
          pro Event. Dafür nutzt du wie bisher den <strong>Simulator</strong>.</li>
    </ul>
  </div>
</section>

<!-- Version 1 -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-1">
    <span class="badge badge--neutral">Version 1</span>
    <span class="text-xs text-subtle">Erste Version</span>
  </div>
  <h2 class="font-semibold mb-3">Grundfunktionen</h2>
  <ul class="text-sm text-muted space-y-1 list-disc list-inside">
    <li>Erfassen und Verwalten von Subventionstöpfen (J+S, NWF, kantonal)</li>
    <li>Simulator zur Berechnung von Subventionsbeträgen</li>
    <li>Benutzerverwaltung mit Login</li>
  </ul>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
