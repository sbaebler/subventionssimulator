# Release-Notes – Subventionssimulator

## Version 5 – Swiss-Sailing-Rapport und erwartete Subventionen pro Jahr

*Veröffentlicht am 28.11.2026*

### Neu: Swiss-Sailing-Rapport

Unter «Mehr → Swiss-Sailing-Rapport» entsteht das Rapportformular «Promotion
camp activity» für den Regionenbeitrag im Originallayout von Swiss Sailing.

- **Ein Excel pro Förderlager**, gesammelt als ZIP. Wählbar sind durchgeführte
  und abgeschlossene Breitensport-Events.
- **Angaben aus der Anmeldung:** Name, Jahrgang, Club, Geschlecht,
  Swiss-Sailing-Nummer, AHV-Nummer (= J+S-Nummer) und Segelklasse kommen aus dem
  Class Manager Tool, soweit sie in der Anmeldung stehen. Fehlendes oder
  Falsches ergänzt du auf der Seite.
- **Nur wer zählt:** Ins Formular kommen die Anwesenden bis 18 Jahre (U19) ohne
  Swiss Olympic Talent Card (SOTC). Eine Spalte zeigt, was noch fehlt.
- **Demo zum Ausprobieren:** In der Auswahl «Daten» stehen drei fiktive
  Szenarien. Dabei wird nichts gespeichert.
- Versendet wird nichts automatisch. Die Dateien enthalten Personendaten
  Minderjähriger.

### Neu: Erwartete Subventionen aus den Events

- **Simulator, Ansicht «Events pro Jahr»:** schätzt pro Event und Jahr die
  Subventionen aus Teilnehmenden und förderfähigen Tagen, filterbar nach
  Klasse und Eventart.
- **Standard-Förderprogramme:** gelten für alle Events; Klassen, Eventkategorien
  und einzelne Events lassen sich ausnehmen.
- **Förderstatus bei den Events:** «Förderfähig» oder der zugeteilte Betrag.

### Berechnung

- Neue Berechnungsart **NWF Trainertag** (Satz × NWF-Trainer × Tage).
- Pro Anlass zählt **J+S Lager oder J+S Training**, nie beides; Lager hat Vorrang.
- ZKS Ausbildungseinheiten werden auch für Trainings geschätzt.

### Bedienung

- **Papierkorb:** Gelöschte Förderprogramme lassen sich wiederherstellen.
- **Passwort vergessen:** neues Passwort selbst per E-Mail-Link setzen;
  deaktivierte Benutzer können gelöscht werden.
- «Jahresbeiträge» ist jetzt eine Ansicht des Simulators.
- Neue Anleitung mit Bahnübersicht, Fachlogik mit nachgerechneten Beispielen.
- Besser auf dem Smartphone: Menü und Karten passen sich an.

### Entfernt

- Der CSV-Export im Simulator.

---

## Version 4 – Erfassen ohne Anleitung: neue Nutzerführung

*Veröffentlicht am 18.07.2026*

### Was ist neu?

Die Erfassung wurde von Grund auf vereinfacht. Ziel: Wer weiss, wie ein
Förderbeitrag funktioniert, kann dieses Wissen **ohne Anleitung** eintragen.
In den Masken heisst das Stammobjekt neu **«Förderprogramm»** –
«Subvention» bleibt als Sammelbegriff verständlich.

- **Geführte Erfassung in vier Schritten:** ① Was für ein Beitrag?
  ② Wie wird gerechnet? ③ Bedingungen ④ Termine & Beträge. Beim Bearbeiten
  siehst du weiterhin alles auf einen Blick.
- **Nur noch zwei Pflichtfelder:** Name und Förderstelle. Alles andere kann
  später ergänzt werden – unvollständige Programme werden in der Übersicht
  amber markiert statt blockiert.
- **Berechnungsart als Auswahlkarten** in Alltagssprache («Fixer Betrag»,
  «Pro Teilnehmer und Tag», «Pro Ausbildungseinheit» …) – inklusive
  **«Weiss ich noch nicht»**: Berechnung einfach in Worten beschreiben,
  Zahlen ergänzt später jemand anderes.
- **Probe-Rechnung live:** Beim Eintippen der Sätze zeigt die Maske sofort,
  was ein Beispiel-Anlass (20 Teilnehmer, 5 Tage) ergeben würde.
- **Einfachere Einschränkungen:** Trainer- und Eventarten sind jetzt
  Checkboxen. Keine Auswahl = gilt für alle. Leere Maximalfelder = unbegrenzt.
- **Übersicht als Arbeitsliste:** Fortschritt «x von y Förderprogrammen
  vollständig erfasst»; bei jedem Programm steht, was noch fehlt – ein Klick
  springt an die richtige Stelle.
- **Aufgeräumte Navigation:** Vier Punkte statt zehn (Förderprogramme,
  Simulator, Beiträge & Verwendung, Mehr).
- **Jahresbetrag direkt erfassen:** Der erhaltene oder erwartete Betrag pro
  Jahr wird neu direkt unter «Beiträge & Verwendung» eingetragen – kein
  Umweg mehr über die Erfassungsmaske.
- **Anleitung integriert:** Die wichtigsten Hinweise samt Beispiel
  «ZKS Ausbildungsbeitrag» stehen jetzt direkt in der Maske und auf der
  überarbeiteten Anleitungsseite.

### Behobene Fehler

- Beim Speichern gingen die typspezifischen Beitragssätze (z. B. J+S-Satz
  mit/ohne Übernachtung, Satz pro Ausbildungseinheit) verloren. Diese Werte
  werden jetzt korrekt gespeichert.

---

## Version 3 – Neue Berechnungsarten, Jahresbeiträge und Verwendung

*Veröffentlicht am 04.07.2026*

- **Sechs Berechnungsarten** statt einer Einheitsformel: additiv, J+S
  Teilnehmertag (mit/ohne Übernachtung), J+S Teilnehmerstunde, ZKS
  Ausbildungseinheit, Pauschale und Jahresbeitrag nach Kennzahl.
- **Mehr Fachinformationen pro Subvention:** Berechtigte, Einschränkungen,
  verlangte Unterlagen, Berechnungsgrundlage, mehrere Fristen sowie die
  tatsächlich erhaltenen Beträge pro Jahr.
- **Neue Seite «Jahresbeiträge»:** Beiträge, die nicht an ein Event gebunden
  sind (z. B. pro Aktivmitglied), werden separat berechnet und verfälschen
  den Event-Simulator nicht mehr.
- **Neue Seite «Verwendung»:** Erhaltene Jahresbeträge auf Events,
  Kaderklassen, Reserven oder freie Zwecke verteilen – mit laufender
  Summen- und Restkontrolle.

---

## Version 2 – Events mit Subventionen verknüpfen

*Veröffentlicht am 19.06.2026*

### Was ist neu?

Der Subventionssimulator ist jetzt mit dem **Class Manager Tool** verbunden.
Alle Anlässe (Events), die ein Class Manager dort eröffnet – zum Beispiel ein
Trainingslager oder eine Regatta – erscheinen neu automatisch auch im
Subventionssimulator. Du musst sie nirgends doppelt erfassen.

Das Beste daran: Du kannst jetzt für jedes Event festlegen, **welche
Subventionen dafür in Frage kommen**.

### Wie funktioniert das?

1. **Events ansehen:** In der oberen Navigation gibt es den neuen Punkt
   **«Events»**. Dort siehst du eine Liste aller Anlässe mit Datum, Ort,
   Segelklasse und Status (z. B. «Geplant» oder «Durchgeführt»).

2. **Subventionen zuteilen:** Klicke bei einem Event auf
   **«Subventionen zuteilen»**. Es erscheint eine Liste aller erfassten
   Subventionen. Setze einfach bei jeder Subvention ein Häkchen, die für
   dieses Event zum Tragen kommt – und speichere.

3. **Überblick behalten:** In der Event-Liste steht bei jedem Anlass, wie
   viele Subventionen ihm bereits zugeordnet sind. So siehst du auf einen
   Blick, was noch offen ist.

Möchtest du eine Zuordnung wieder ändern, öffnest du das Event erneut, passt
die Häkchen an und speicherst – fertig.

### Gut zu wissen

- Die Events selbst werden weiterhin **im Class Manager Tool** erstellt und
  bearbeitet. Im Subventionssimulator werden sie nur angezeigt und mit
  Subventionen verknüpft.
- Diese Version ordnet Subventionen lediglich **zu** – sie berechnet noch
  keine konkreten Beträge pro Event. Für die Beträge nutzt du wie bisher den
  **Simulator**.

---

## Version 1 – Grundfunktionen

*Erste Version*

- Erfassen und Verwalten von Subventionstöpfen (J+S, NWF, kantonal)
- Simulator zur Berechnung von Subventionsbeträgen
- Benutzerverwaltung mit Login
