# CLAUDE.md – Subventionssimulator

Projektkontext und Arbeitsregeln für Claude Code.

-----

## Projekt

**Name:** Subventionssimulator Zurich Sailing Federation  
**Repo:** <https://github.com/sbaebler/subventionssimulator> — **öffentlich**  
**Live-URL:** <https://subventionssimulator.zurich-sailing.ch>  
**Hosting:** cyon.ch – Shared Hosting (Linux, PHP 8.2, MariaDB 10.6, SSD)  
**Zweck:** Erfassung und Simulation von Sportsubventionen (J+S, ZKS, NWF, kantonal, verbandsintern) für den Zurich Sailing Federation

**Schwesterprojekt:** [Class Manager Tool](https://github.com/sbaebler/class-manager-tool) (`/Users/sbaebler/Documents/Coding/ClassManagerTool`). Beide Apps teilen dieselbe MariaDB-Datenbank; das Class Manager Tool nutzt eigene Tabellen mit Präfix `cm_`. Der Subventionssimulator liest daraus die Events.

-----

## Tech-Stack

|Schicht   |Technologie                                        |Hinweis                             |
|----------|---------------------------------------------------|------------------------------------|
|Backend   |PHP 8.2                                            |Kein Composer, kein Framework       |
|Datenbank |MariaDB 10.6                                       |PDO, utf8mb4                        |
|Frontend  |Alpine.js 3.x (CDN)                                |Kein Build-Tool                     |
|Styling   |ZSF Tools UI Kit (`theme.css` + `shared-ui.css`)   |Tailwind CDN nur für Layout/Abstände|
|Deployment|GitHub Actions → FTPS auf cyon                     |`.github/workflows/deploy.yml`      |

**Kein Node.js, kein npm, kein Composer, kein Build-Schritt.** Alles läuft direkt auf cyon Shared Hosting.

Die App ist durchgehend **server-gerendert**. Es gibt keine API-Schicht und kein JavaScript-Bundle; Alpine.js wird nur für kleine Interaktionen im Markup verwendet.

-----

## Verzeichnisstruktur

```
subventionssimulator/
├── public_html/                 ← Web-Root (auf cyon: public_html/subventionssimulator/)
│   ├── index.php                Übersicht aller Förderprogramme
│   ├── erfassen.php             Erfassung als vierstufiger Wizard (neu + bearbeiten)
│   ├── simulieren.php           Simulator-Eingabe und Ergebnis
│   ├── verwendung.php           Beiträge & Verwendung: Verteilung auf Empfänger
│   ├── jahresbeitraege.php      Erhaltene Beträge pro Jahr
│   ├── events.php               Events (read-only aus cm_events)
│   ├── events_zuordnen.php      Event ↔ Förderprogramm verknüpfen
│   ├── papierkorb.php           Soft-gelöschte Programme, Wiederherstellen
│   ├── benutzer.php             Benutzerverwaltung
│   ├── anleitung.php            Anleitung inkl. Bahnübersicht-Diagramm
│   ├── releases.php             Rendert RELEASES.md
│   ├── login.php / logout.php
│   ├── passwort-vergessen.php / passwort-zuruecksetzen.php
│   ├── .htaccess                HTTPS-Redirect, Sicherheitsheader, Verzeichnisschutz
│   ├── assets/css/
│   │   ├── theme.css            Farbvariablen dieser App
│   │   └── shared-ui.css        Layout + Komponenten (farbneutral)
│   ├── docs/
│   │   ├── erste-schritte.html  Statische Einstiegsanleitung
│   │   ├── fachlogik.html       Statische Fachdokumentation
│   │   └── diagramme/*.svg      Bahn-Illustrationen (Skill zsf-tools-diagramme)
│   └── partials/
│       ├── header.php           DOCTYPE, Nav, Alpine/Tailwind CDN, CSS-Einbindung
│       └── footer.php           Closing tags
├── includes/
│   ├── db.php                   PDO-Singleton via db()-Funktion
│   ├── Subvention.php           Model: CRUD, Soft-Delete, Vollständigkeit, Berechnung
│   ├── Event.php                Read-only-Sicht auf cm_events + Zuordnung
│   ├── auth.php                 Session-Auth gegen die Tabelle benutzer
│   ├── mailer.php               Mailversand
│   └── password_reset.php       Token-Flow für Passwort-Reset
├── config/
│   ├── config.php               ← NICHT im Repo (.gitignore), im Deploy generiert
│   └── config.example.php       Vorlage mit Platzhaltern
├── docs/                        Konzeptdokumente, nicht Teil der App
├── sql/
│   ├── schema.sql               Vollständige Tabellenstruktur
│   ├── migration_*.sql          Einzelmigrationen, chronologisch
│   └── seed.sql, seed_katalog.sql
├── tests/                       Standalone-Prüfskripte, `php tests/<datei>.php`
└── .github/workflows/deploy.yml
```

> `config/config.php` nie committen.  
> `sql/` und `tests/` werden nicht deployed — nur lokal.  
> **`docs/` und alles unter `public_html/` wird deployed** (siehe Deployment).

-----

## Datenbankschema

`sql/schema.sql` ist die einzige Wahrheit und enthält alle Tabellen. Die
`migration_*.sql` dokumentieren, wie der Stand entstanden ist, und sind für
bestehende Installationen gedacht.

```
subventionen                 Stammdaten: bezeichnung, foerderstelle, kategorie,
                             berechnungstyp, Gültigkeit, Audit-Spalten,
                             Soft-Delete (geloescht_am, geloescht_von)
  ├── subvention_betraege        Betragsregeln je nach Berechnungstyp
  ├── subvention_trainerarten    Erlaubte Trainerarten + Zusatzbetrag
  ├── subvention_eventarten      Erlaubte Eventarten + Multiplikator
  ├── subvention_fristen         Termine und Eingabefristen
  ├── subvention_betrag_historie Erhaltene Beträge pro Jahr
  ├── subvention_verwendung      Verteilung eines Jahresbetrags auf Empfänger
  └── subvention_events          Zuordnung Förderprogramm ↔ cm_events

simulationen                 Protokoll der Berechnungen
benutzer                     Login, Rolle, Aktivstatus
passwort_reset_tokens        Token-Flow für Passwort-Reset

cm_*                         Gehören dem Class Manager Tool. Hier nur lesend
                             verwenden, nie schreiben.
```

### ENUMs

Verbindlich sind die Klassenkonstanten in `includes/Subvention.php`.

```php
// Subvention::KATEGORIEN
'ausbildung' | 'lager' | 'wettkampf' | 'infrastruktur' | 'jugend' | 'sonstiges'

// Subvention::BERECHNUNGSTYPEN
'additiv'                // Grundbetrag + pro TN + pro Tag
'js_teilnehmertag'       // J+S Teilnehmertag (Satz × TN × Tage)
'js_teilnehmerstunde'    // J+S Teilnehmerstunde (Satz × TN × Tage × Stunden)
'zks_ausbildungseinheit' // ZKS Ausbildungseinheit (Satz × TN × Lektionen × Tage)
'pauschale'              // Fixer Jahresbetrag
'jahresbeitrag'          // Verbandsweite Kennzahl

// Subvention::TRAINERARTEN
'js_trainer' | 'nwf_trainer' | 'ohne'

// Subvention::EVENTARTEN
'lager' | 'training'
```

`Event::STATUS` (`provisorisch_geplant`, `geplant`, `durchgefuehrt`,
`abgeschlossen`) spiegelt bewusst 1:1 die Werte im Class Manager Tool, damit
Labels und Badge-Farben in beiden Apps übereinstimmen. Bei Änderungen **beide**
Apps anpassen.

Gleiches gilt für `Subvention::vollstaendigkeit()` (`includes/Subvention.php`):
Das Class Manager Tool baut die vier Kriterien read-only als SQL nach
(`Budget::vollstaendigeSubventionenFuerEvents()`, dortiges `includes/Budget.php`),
um bei Events anzuzeigen, welche zugeordneten Förderprogramme vollständig
erfasst sind. Bei Änderungen an `vollstaendigkeit()` **immer auch** die
SQL-Nachbildung im Class Manager Tool anpassen. `tests/vollstaendigkeit_matrix.php`
prüft alle 16 Kombinationen der vier Kriterien gegen `vollstaendigkeit()`
selbst (`php tests/vollstaendigkeit_matrix.php`, keine DB nötig); das
gleichnamige Skript im Class Manager Tool prüft dieselben Kombinationen
gegen die SQL-Nachbildung (braucht eine Dev-DB mit beiden Schemas). Beide
Skripte laufen unabhängig, weil beide Repos eine globale Klasse `Event`
und Funktion `db()` deklarieren und sich nicht in einem Prozess laden lassen.

### Berechnung

Einstieg: `Subvention::berechnen(int $id, array $params)` bzw.
`berechneAusSubvention()`. Diese wählen anhand von `berechnungstyp` eine der
sechs privaten Methoden. **Es gibt nicht eine Formel, sondern sechs.**

Beispiel für `'additiv'` (`berechneAdditiv()`):

```
total = ( grundbetrag
        + betrag_pro_teilnehmer × min(anzahl_tn,  max_teilnehmer)
        + betrag_pro_tag        × min(anzahl_tage, max_tage)
        + trainer_zusatzbetrag )
        × eventart_multiplikator
```

Die Begrenzung greift nur, wenn das jeweilige Maximum > 0 ist (`begrenzt()`).
Jede Methode gibt neben dem Betrag eine Aufschlüsselung zurück, die im
Simulator angezeigt wird. Für die übrigen fünf Typen den Code lesen — die
Formeln unterscheiden sich deutlich.

-----

## Coding-Konventionen

- **Sprache:** Deutsch für alle Labels, Fehlermeldungen, Kommentare und Variablennamen (ausser PHP-Klassen/Methoden in PascalCase/camelCase)
- **PHP:** Typisierte Parameter und Rückgabewerte wo möglich; `match` statt `switch`; kein `die()`
- **SQL:** Ausschliesslich Prepared Statements (PDO); kein Query-Building durch String-Konkatenation
- **HTML:** Semantisch korrekt; Komponenten aus `shared-ui.css` bevorzugen, Tailwind nur für Layout und Abstände; Alpine.js `x-data` nur auf dem nächstnötigen Element
- **Farben:** Nie Hex-Werte oder Tailwind-Farbklassen direkt setzen, immer `var(--color-*)` aus `theme.css`
- **Fehlerbehandlung:** Exceptions fangen, sinnvolle Fehlermeldung anzeigen, nie rohe PHP-Fehler ans Frontend
- **Kein `var_dump` / `print_r` / `die()` im committed Code**

### Begriffe im UI

Im Nutzertext heisst das Stammobjekt **«Förderprogramm»**. «Subvention» bleibt
Sammelbegriff und Name der Applikation; Klassen- und Tabellennamen im Code
behalten `Subvention`/`subventionen`.

-----

## Wichtige Dateien

|Datei                            |Zweck                                        |
|---------------------------------|---------------------------------------------|
|`includes/db.php`                |`db(): PDO` – einziger Datenbankzugriffspunkt|
|`includes/Subvention.php`        |CRUD, Soft-Delete, `vollstaendigkeit()`, `berechnen()`|
|`includes/Event.php`             |Read-only auf `cm_events` + `subvention_events`|
|`includes/auth.php`              |`auth_erforderlich()`, `auth_anmelden()`, `auth_abmelden()`, `auth_benutzer_id()`|
|`includes/password_reset.php`    |`passwort_reset_anfordern()`, `_gueltig()`, `_einloesen()`|
|`config/config.example.php`      |Vorlage – bei neuen Konstanten hier mitpflegen|
|`sql/schema.sql`                 |Einzige Wahrheit für die DB-Struktur         |
|`public_html/.htaccess`          |HTTPS, Sicherheitsheader, Sperre für `config/`, `includes/`, `sql/`|
|`public_html/assets/css/theme.css`|Farbvariablen dieser App                    |
|`.github/workflows/deploy.yml`   |Deployment                                   |

-----

## Aktueller Stand

Produktiv im Einsatz. Version 4 veröffentlicht am 18.07.2026; Verlauf in
`RELEASES.md`, im UI unter «Neuigkeiten».

Fertig und live: Erfassung (Wizard), Simulator, Beiträge & Verwendung,
Jahresbeiträge, Events und Zuordnung, Papierkorb, Benutzerverwaltung mit
Passwort-Reset, Anleitung.

Offen / optional: PDF-Export der Simulation.

-----

## Deployment

**Push nach `main` ist der Deploy.** Die GitHub Action
`.github/workflows/deploy.yml` überträgt danach per FTPS auf cyon. Kein rsync,
kein manueller Upload. Nur auf ausdrückliche Anweisung pushen.

- Ziel: `/public_html/subventionssimulator/` auf cyon
- `local-dir: ./` — **das gesamte Repo-Root wird hochgeladen**, abzüglich der Exclude-Liste
- Ausgeschlossen: `.git*`, `sql/`, `tests/`, `*.docx`, `config/config.example.php`, `CLAUDE.md`, `README.md`, `.gitignore`, `.env`, `*.log`, `.DS_Store`
- `config/config.php` wird im Workflow aus GitHub-Secrets erzeugt und mitdeployed
- Manueller Trigger über `workflow_dispatch` möglich

> **Achtung:** Alles, was nicht in der Exclude-Liste steht, landet öffentlich
> auf dem Webserver — auch Dateien im Repo-Root und in `docs/`. Vor dem
> Committen neuer Dateien prüfen, ob sie dort hingehören. Aus genau diesem
> Grund steht `docs/diagramm-beispiele/` in `.gitignore`: fremde,
> urheberrechtlich geschützte Vorlagen, und das Repo ist öffentlich.

PHP-Version in my.cyon auf **8.2** setzen.
SSL (Let's Encrypt) für `subventionssimulator.zurich-sailing.ch` aktivieren.

-----

## Verwandte Skills

|Skill                 |Wofür                                                |
|----------------------|-----------------------------------------------------|
|`zsf-tools-ui-kit`    |UI bauen oder ändern. Regelt `theme.css`/`shared-ui.css` und den Sync mit dem Class Manager Tool|
|`zsf-tools-diagramme` |Bahn-Illustrationen für Anleitung und Doku           |

-----

## Bezeichnungen

- Der Verein heisst immer **Zurich Sailing Federation** – nie «Zurich Sailing Club» oder nur «Zurich Sailing»

-----

## Nicht tun

- Kein `composer require` – cyon Shared Hosting hat kein Composer im PATH
- Kein `npm` / Build-Tools – kein Node.js verfügbar
- Kein `.env` – wir nutzen `config/config.php` mit `define()`
- `config/config.php` nie committen
- Keine externen CDN-Ressourcen ausser: jsdelivr.net, cdn.tailwindcss.com, alpinejs.dev
- Nicht in `cm_*`-Tabellen schreiben – die gehören dem Class Manager Tool
- Ohne ausdrückliche Anweisung nicht nach `main` pushen – das deployt live
