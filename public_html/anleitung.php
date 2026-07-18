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
    Der Subventionssimulator sammelt das Wissen über alle Förderprogramme (J+S, ZKS, kantonal, verbandsintern)
    an einem Ort: Wer zahlt was, unter welchen Bedingungen, mit welchen Fristen – und wofür wurden erhaltene
    Beiträge verwendet. Für ein konkretes Lager oder Training berechnet der Simulator, welche Beiträge
    voraussichtlich möglich sind.
  </p>
</section>

<!-- Kurzregel -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <h2 class="font-semibold text-gray-700 mb-3">Das Wichtigste in Kürze</h2>
  <ul class="text-sm text-gray-600 space-y-2">
    <li>✅ <strong>Nur Name und Förderstelle sind Pflicht.</strong> Trage ein, was du weisst – alles andere kann jederzeit ergänzt werden. Unvollständige Programme sind in der Übersicht amber markiert.</li>
    <li>🧮 Wenn du die Berechnung nicht kennst: Wähle <strong>«Weiss ich noch nicht»</strong> und beschreibe sie in eigenen Worten. Jemand anderes ergänzt die Zahlen später.</li>
    <li>🎯 Trainer- oder Eventarten nur ankreuzen, wenn die Förderstelle sie wirklich vorschreibt. <strong>Keine Auswahl = gilt für alle.</strong></li>
    <li>💰 <strong>Erhaltene Beträge pro Jahr</strong> = was tatsächlich ausbezahlt oder erwartet wurde. Die Verteilung auf Lager, Klassen usw. erfolgt unter «Beiträge &amp; Verwendung».</li>
    <li>🔢 Dezimalzahlen mit Punkt oder Komma, Tausendertrennzeichen mit Apostroph – alles wird akzeptiert.</li>
  </ul>
</section>

<!-- Schritt 1 -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">1</span>
    <h2 class="font-semibold text-gray-700">Förderprogramm erfassen</h2>
  </div>
  <p class="text-sm text-gray-600 leading-relaxed mb-4">
    In der Übersicht auf <strong>«+ Neues Förderprogramm»</strong> klicken. Die Erfassung führt dich in vier Schritten durch:
  </p>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">① Beitrag</p>
      <p class="text-gray-600">Name, Förderstelle und Kategorie – die beiden ersten sind die einzigen Pflichtfelder.</p>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">② Berechnung</p>
      <p class="text-gray-600">Berechnungsmuster als Karte wählen (fixer Betrag, pro Teilnehmertag, pro Ausbildungseinheit …).
      Danach erscheinen nur die passenden Felder, und eine Probe-Rechnung zeigt sofort, was deine Sätze ergeben würden.</p>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">③ Bedingungen</p>
      <p class="text-gray-600">Voraussetzungen, Berechtigte, verlangte Unterlagen – und falls nötig Einschränkungen
      auf bestimmte Event- oder Trainerarten (Checkboxen).</p>
    </div>
    <div class="bg-gray-50 rounded-lg p-4">
      <p class="font-medium text-gray-700 mb-2">④ Termine &amp; Beträge</p>
      <p class="text-gray-600">Eingabefrist, weitere Termine, Gültigkeit und die tatsächlich erhaltenen Beträge pro Jahr.</p>
    </div>
  </div>
</section>

<!-- Beispiel ZKS -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <h2 class="font-semibold text-gray-700 mb-3">Konkretes Beispiel: ZKS Ausbildungsbeitrag</h2>
  <p class="text-sm text-gray-600 leading-relaxed mb-4">
    Der ZKS-Ausbildungsbeitrag fördert Ausbildungstätigkeit (Lager, Kadertrainings, Coach2Coach),
    berechnet über Ausbildungseinheiten. So wird er erfasst:
  </p>
  <div class="overflow-x-auto">
    <table class="text-sm text-gray-600 w-full">
      <tbody class="divide-y divide-gray-100">
        <tr><td class="py-1.5 pr-4 font-medium text-gray-700 whitespace-nowrap">Schritt 1</td>
            <td class="py-1.5">Name «ZKS Ausbildungsbeitrag», Förderstelle «Zürcher Kantonalverband für Sport (ZKS)», Kategorie «Ausbildung»</td></tr>
        <tr><td class="py-1.5 pr-4 font-medium text-gray-700 whitespace-nowrap">Schritt 2</td>
            <td class="py-1.5">Muster «Pro Ausbildungseinheit», Satz <strong>2.80</strong>, max. <strong>6 Lektionen pro Tag</strong></td></tr>
        <tr><td class="py-1.5 pr-4 font-medium text-gray-700 whitespace-nowrap">Schritt 3</td>
            <td class="py-1.5">Keine Meisterschaften/Wettkämpfe; Regatten nur, wenn als Ausbildung deklariert. Unterlagen: ZKS-Musterteilnehmerliste, Kursprogramm, Anwesenheitsliste, J+S-Leiterausweise. Eventarten Lager + Training angekreuzt, Trainerart J+S.</td></tr>
        <tr><td class="py-1.5 pr-4 font-medium text-gray-700 whitespace-nowrap">Schritt 4</td>
            <td class="py-1.5">Termine: Musterteilnehmerliste an Leiter Subvention bis 28.02., Einreichung bis 31.03. des Folgejahres. Erhaltene Beträge: 2024 CHF 12'949.00, 2025 ca. CHF 30'000.00.</td></tr>
        <tr><td class="py-1.5 pr-4 font-medium text-gray-700 whitespace-nowrap">Verwendung</td>
            <td class="py-1.5">Unter «Beiträge &amp; Verwendung»: CHF 30'000 für 2025 verteilt auf Auffahrtslager (10'000), Sommerlager (12'000), ZSTeam (5'000) und Coach2Coach (3'000) – Rest CHF 0.00.</td></tr>
      </tbody>
    </table>
  </div>
</section>

<!-- Schritt 2: Übersicht -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">2</span>
    <h2 class="font-semibold text-gray-700">Übersicht nutzen</h2>
  </div>
  <p class="text-sm text-gray-600 leading-relaxed">
    Die Übersicht <strong>«Förderprogramme»</strong> zeigt alle aktiven Programme und den Fortschritt
    («x von y vollständig erfasst»). Bei unvollständigen Programmen steht direkt dabei, was noch fehlt –
    ein Klick darauf springt an die richtige Stelle im Formular. Inaktive oder abgelaufene Programme
    werden automatisch ausgeblendet.
  </p>
</section>

<!-- Schritt 3: Simulation -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">3</span>
    <h2 class="font-semibold text-gray-700">Simulation durchführen</h2>
  </div>
  <p class="text-sm text-gray-600 leading-relaxed mb-4">
    Unter <strong>«Simulator»</strong> gibst du die Eckdaten deines Events ein (Teilnehmende, Tage, Trainer- und Eventart)
    und klickst auf <strong>«Berechnen»</strong>. Der Simulator prüft automatisch alle aktiven Förderprogramme und zeigt
    pro berechtigtem Programm den erwarteten Betrag mit Aufschlüsselung – und bei nicht berechtigten den Grund.
  </p>
  <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-sm text-blue-700">
    <strong>Tipp:</strong> In der Übersicht kannst du bei jedem Förderprogramm direkt auf
    <strong>«Simulieren»</strong> klicken — der Simulator öffnet sich mit vorausgefüllten Standardwerten.
  </div>
</section>

<!-- Schritt 4: Verwendung -->
<section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">4</span>
    <h2 class="font-semibold text-gray-700">Erhaltene Beiträge verteilen</h2>
  </div>
  <p class="text-sm text-gray-600 leading-relaxed">
    Unter <strong>«Beiträge &amp; Verwendung»</strong> wählst du Förderprogramm und Jahr, erfasst den erhaltenen
    Betrag und verteilst ihn mit <strong>«+ Zuweisung»</strong> auf Events, Kaderklassen, Reserven oder freie Zwecke.
    Ideal ist ein Rest von CHF 0.00 – dann ist der Beitrag vollständig dokumentiert.
  </p>
</section>

<!-- Hinweise -->
<section class="bg-white border border-gray-200 rounded-xl p-6">
  <h2 class="font-semibold text-gray-700 mb-3">Hinweise</h2>
  <ul class="text-sm text-gray-600 space-y-2">
    <li>⚠️ Die berechneten Beträge sind <strong>Schätzwerte</strong> auf Basis der erfassten Regeln. Massgebend sind stets die offiziellen Richtlinien der jeweiligen Förderstelle.</li>
    <li>📅 Förderprogramme mit abgelaufenem Gültigkeitsdatum werden in der Übersicht und im Simulator nicht mehr angezeigt.</li>
    <li>🔒 Die Applikation ist passwortgeschützt. Teile den Zugang nur mit berechtigten Personen des Verbands.</li>
  </ul>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
