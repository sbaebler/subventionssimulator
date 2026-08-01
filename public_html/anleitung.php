<?php
$pageTitle = 'Anleitung';
require __DIR__ . '/partials/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl font-semibold">Anleitung</h1>
  <p class="text-sm text-muted mt-1">Subventionssimulator · Zurich Sailing Federation</p>
</div>

<!-- Übersicht -->
<section class="card mb-6">
  <h2 class="font-semibold mb-3">Was macht diese Applikation?</h2>
  <p class="text-sm text-muted leading-relaxed">
    Der Subventionssimulator sammelt das Wissen über alle Förderprogramme (J+S, ZKS, kantonal, verbandsintern)
    an einem Ort: Wer zahlt was, unter welchen Bedingungen, mit welchen Fristen – und wofür wurden erhaltene
    Beiträge verwendet. Für ein konkretes Lager oder Training berechnet der Simulator, welche Beiträge
    voraussichtlich möglich sind.
  </p>
</section>

<!-- Bahnübersicht -->
<section class="card mb-6">
  <h2 class="font-semibold mb-3">Der Weg durch die Applikation</h2>
  <p class="text-sm text-muted leading-relaxed mb-4">
    Der Ablauf folgt denselben Phasen wie eine Wettfahrt: von den Regeln über den Start
    bis ins Ziel und die Auswertung danach. Die Ziffern im Bild entsprechen der Liste darunter.
  </p>

  <a href="/docs/diagramme/bahnuebersicht-subventionssimulator.svg" target="_blank" rel="noopener">
    <img src="/docs/diagramme/bahnuebersicht-subventionssimulator.svg"
         alt="Bahnübersicht des Subventionssimulators: sechs Stationen von der Erfassung der Förderprogramme bis zur Verwendung der Beiträge"
         style="width:100%;max-width:100%;height:auto;border-radius:var(--radius);">
  </a>
  <p class="text-xs text-subtle mt-2 mb-4">Zum Vergrössern auf das Bild tippen.</p>

  <ol class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
    <li class="flex gap-3">
      <span class="step-dot step-dot--mark">1</span>
      <span><strong>Förderprogramme erfassen.</strong> <span class="text-muted">Die Regeln der Fördergeber – wer zahlt was, unter welchen Bedingungen.</span></span>
    </li>
    <li class="flex gap-3">
      <span class="step-dot step-dot--mark">2</span>
      <span><strong>Simulieren.</strong> <span class="text-muted">Für ein konkretes Lager oder Training prüfen, welches Programm wie viel bringt.</span></span>
    </li>
    <li class="flex gap-3">
      <span class="step-dot step-dot--mark">3</span>
      <span><strong>Events erfassen.</strong> <span class="text-muted">Was tatsächlich stattgefunden hat, und welchen Förderprogrammen es zugeordnet ist.</span></span>
    </li>
    <li class="flex gap-3">
      <span class="step-dot step-dot--mark">4</span>
      <span><strong>Fristen und Vollständigkeit.</strong> <span class="text-muted">Was dem Antrag noch fehlt und bis wann er eingereicht sein muss.</span></span>
    </li>
    <li class="flex gap-3">
      <span class="step-dot step-dot--mark">5</span>
      <span><strong>Jahresbeiträge.</strong> <span class="text-muted">Was pro Jahr tatsächlich eingegangen ist.</span></span>
    </li>
    <li class="flex gap-3">
      <span class="step-dot step-dot--mark">6</span>
      <span><strong>Beiträge verwenden.</strong> <span class="text-muted">Verteilung auf Events, Kaderklassen und weitere Empfänger.</span></span>
    </li>
  </ol>
</section>

<!-- Kurzregel -->
<section class="card mb-6">
  <h2 class="font-semibold mb-3">Das Wichtigste in Kürze</h2>
  <ul class="text-sm text-muted space-y-2">
    <li>✅ <strong>Nur Name und Förderstelle sind Pflicht.</strong> Trage ein, was du weisst – alles andere kann jederzeit ergänzt werden. Unvollständige Programme sind in der Übersicht amber markiert.</li>
    <li>🧮 Wenn du die Berechnung nicht kennst: Wähle <strong>«Weiss ich noch nicht»</strong> und beschreibe sie in eigenen Worten. Jemand anderes ergänzt die Zahlen später.</li>
    <li>🎯 Trainer- oder Eventarten nur ankreuzen, wenn die Förderstelle sie wirklich vorschreibt. <strong>Keine Auswahl = gilt für alle.</strong></li>
    <li>💰 <strong>Erhaltene Beträge pro Jahr</strong> = was tatsächlich ausbezahlt oder erwartet wurde. Die Verteilung auf Lager, Klassen usw. erfolgt unter «Beiträge &amp; Verwendung».</li>
    <li>🔢 Dezimalzahlen mit Punkt oder Komma, Tausendertrennzeichen mit Apostroph – alles wird akzeptiert.</li>
  </ul>
</section>

<!-- Schritt 1 -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="step-dot">1</span>
    <h2 class="font-semibold">Förderprogramm erfassen</h2>
  </div>
  <p class="text-sm text-muted leading-relaxed mb-4">
    In der Übersicht auf <strong>«+ Neues Förderprogramm»</strong> klicken. Die Erfassung führt dich in vier Schritten durch:
  </p>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div class="card--muted">
      <p class="font-medium mb-2">① Beitrag</p>
      <p class="text-muted">Name, Förderstelle und Kategorie – die beiden ersten sind die einzigen Pflichtfelder.</p>
    </div>
    <div class="card--muted">
      <p class="font-medium mb-2">② Berechnung</p>
      <p class="text-muted">Berechnungsmuster als Karte wählen (fixer Betrag, pro Teilnehmertag, pro Ausbildungseinheit …).
      Danach erscheinen nur die passenden Felder, und eine Probe-Rechnung zeigt sofort, was deine Sätze ergeben würden.</p>
    </div>
    <div class="card--muted">
      <p class="font-medium mb-2">③ Bedingungen</p>
      <p class="text-muted">Voraussetzungen, Berechtigte, verlangte Unterlagen – und falls nötig Einschränkungen
      auf bestimmte Event- oder Trainerarten (Checkboxen).</p>
    </div>
    <div class="card--muted">
      <p class="font-medium mb-2">④ Termine &amp; Beträge</p>
      <p class="text-muted">Eingabefrist, weitere Termine, Gültigkeit und die tatsächlich erhaltenen Beträge pro Jahr.</p>
    </div>
  </div>
</section>

<!-- Beispiel ZKS -->
<section class="card mb-6">
  <h2 class="font-semibold mb-3">Konkretes Beispiel: ZKS Ausbildungsbeitrag</h2>
  <p class="text-sm text-muted leading-relaxed mb-4">
    Der ZKS-Ausbildungsbeitrag fördert Ausbildungstätigkeit (Lager, Kadertrainings, Coach2Coach),
    berechnet über Ausbildungseinheiten. So wird er erfasst:
  </p>
  <div class="overflow-x-auto">
    <table class="text-sm text-muted w-full">
      <tbody class="divide-y divide-gray-100">
        <tr><td class="py-1.5 pr-4 font-medium whitespace-nowrap">Schritt 1</td>
            <td class="py-1.5">Name «ZKS Ausbildungsbeitrag», Förderstelle «Zürcher Kantonalverband für Sport (ZKS)», Kategorie «Ausbildung»</td></tr>
        <tr><td class="py-1.5 pr-4 font-medium whitespace-nowrap">Schritt 2</td>
            <td class="py-1.5">Muster «Pro Ausbildungseinheit», Satz <strong>2.80</strong>, max. <strong>6 Lektionen pro Tag</strong></td></tr>
        <tr><td class="py-1.5 pr-4 font-medium whitespace-nowrap">Schritt 3</td>
            <td class="py-1.5">Keine Meisterschaften/Wettkämpfe; Regatten nur, wenn als Ausbildung deklariert. Unterlagen: ZKS-Musterteilnehmerliste, Kursprogramm, Anwesenheitsliste, J+S-Leiterausweise. Eventarten Lager + Training angekreuzt, Trainerart J+S.</td></tr>
        <tr><td class="py-1.5 pr-4 font-medium whitespace-nowrap">Schritt 4</td>
            <td class="py-1.5">Termine: Musterteilnehmerliste an Leiter Subvention bis 28.02., Einreichung bis 31.03. des Folgejahres. Erhaltene Beträge: 2024 CHF 12'949.00, 2025 ca. CHF 30'000.00.</td></tr>
        <tr><td class="py-1.5 pr-4 font-medium whitespace-nowrap">Verwendung</td>
            <td class="py-1.5">Unter «Beiträge &amp; Verwendung»: CHF 30'000 für 2025 verteilt auf Auffahrtslager (10'000), Sommerlager (12'000), ZSTeam (5'000) und Coach2Coach (3'000) – Rest CHF 0.00.</td></tr>
      </tbody>
    </table>
  </div>
</section>

<!-- Schritt 2: Übersicht -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="step-dot">2</span>
    <h2 class="font-semibold">Übersicht nutzen</h2>
  </div>
  <p class="text-sm text-muted leading-relaxed">
    Die Übersicht <strong>«Förderprogramme»</strong> zeigt alle aktiven Programme und den Fortschritt
    («x von y vollständig erfasst»). Bei unvollständigen Programmen steht direkt dabei, was noch fehlt –
    ein Klick darauf springt an die richtige Stelle im Formular. Inaktive oder abgelaufene Programme
    werden automatisch ausgeblendet.
  </p>
</section>

<!-- Schritt 3: Simulation -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="step-dot">3</span>
    <h2 class="font-semibold">Simulation durchführen</h2>
  </div>
  <p class="text-sm text-muted leading-relaxed mb-4">
    Unter <strong>«Simulator»</strong> gibst du die Eckdaten deines Events ein (Teilnehmende, Tage, Trainer- und Eventart)
    und klickst auf <strong>«Berechnen»</strong>. Der Simulator prüft automatisch alle aktiven Förderprogramme und zeigt
    pro berechtigtem Programm den erwarteten Betrag mit Aufschlüsselung – und bei nicht berechtigten den Grund.
  </p>
  <div class="alert alert--info text-sm">
    <strong>Tipp:</strong> In der Übersicht kannst du bei jedem Förderprogramm direkt auf
    <strong>«Simulieren»</strong> klicken — der Simulator öffnet sich mit vorausgefüllten Standardwerten.
  </div>
</section>

<!-- Schritt 4: Verwendung -->
<section class="card mb-6">
  <div class="flex items-center gap-3 mb-3">
    <span class="step-dot">4</span>
    <h2 class="font-semibold">Erhaltene Beiträge verteilen</h2>
  </div>
  <p class="text-sm text-muted leading-relaxed">
    Unter <strong>«Beiträge &amp; Verwendung»</strong> wählst du Förderprogramm und Jahr, erfasst den erhaltenen
    Betrag und verteilst ihn mit <strong>«+ Zuweisung»</strong> auf Events, Kaderklassen, Reserven oder freie Zwecke.
    Ideal ist ein Rest von CHF 0.00 – dann ist der Beitrag vollständig dokumentiert.
  </p>
</section>

<!-- Hinweise -->
<section class="card">
  <h2 class="font-semibold mb-3">Hinweise</h2>
  <ul class="text-sm text-muted space-y-2">
    <li>⚠️ Die berechneten Beträge sind <strong>Schätzwerte</strong> auf Basis der erfassten Regeln. Massgebend sind stets die offiziellen Richtlinien der jeweiligen Förderstelle.</li>
    <li>📅 Förderprogramme mit abgelaufenem Gültigkeitsdatum werden in der Übersicht und im Simulator nicht mehr angezeigt.</li>
    <li>🔒 Die Applikation ist passwortgeschützt. Teile den Zugang nur mit berechtigten Personen des Verbands.</li>
  </ul>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
