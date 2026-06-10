# CLAUDE.md – Subventionssimulator

Projektkontext und Arbeitsregeln für Claude Code.

-----

## Projekt

**Name:** Subventionssimulator Zurich Sailing  
**Repo:** <https://github.com/sbaebler/subventionssimulator>  
**Live-URL:** <https://subventionssimulator.zurich-sailing.ch>  
**Hosting:** cyon.ch – Shared Hosting (Linux, PHP 8.2, MariaDB 10.6, SSD)  
**Zweck:** Erfassung und Simulation von Sportsubventionen (J+S, NWF, kantonal) für den Zurich Sailing Federation

-----

## Tech-Stack

|Schicht   |Technologie        |Hinweis                      |
|----------|-------------------|-----------------------------|
|Backend   |PHP 8.2            |Kein Composer, kein Framework|
|Datenbank |MariaDB 10.6       |PDO, utf8mb4                 |
|Frontend  |Alpine.js 3.x (CDN)|Kein Build-Tool              |
|Styling   |Tailwind CSS (CDN) |Kein Build-Tool              |
|Deployment|FTP / SSH auf cyon |Kein CI/CD                   |

**Kein Node.js, kein npm, kein Composer, kein Build-Schritt.** Alles läuft direkt auf cyon Shared Hosting.

-----

## Verzeichnisstruktur

```
subventionssimulator/
├── public_html/            ← Web-Root auf cyon
│   ├── index.php           Übersicht aller Subventionen
│   ├── erfassen.php        CRUD-Formular (neu + bearbeiten)
│   ├── simulieren.php      Simulator-Eingabe und Ergebnis
│   ├── .htaccess           HTTPS-Redirect, Sicherheitsheader, Zugriffsschutz
│   ├── assets/
│   │   ├── css/app.css
│   │   └── js/app.js
│   └── partials/
│       ├── header.php      DOCTYPE, Nav, Alpine/Tailwind CDN
│       └── footer.php      Closing tags
├── api/
│   ├── subventionen.php    GET: JSON-Liste aller Subventionen
│   ├── subvention_save.php POST: Subvention speichern
│   └── simulate.php        POST: Simulation berechnen
├── includes/
│   ├── db.php              PDO-Singleton via db()-Funktion
│   ├── Subvention.php      Model: CRUD + Berechnung
│   └── helpers.php         (noch zu erstellen)
├── config/
│   ├── config.php          ← NICHT im Repo (.gitignore)
│   └── config.example.php  Vorlage mit Platzhaltern
└── sql/
    ├── schema.sql          Tabellenstruktur
    └── seed.sql            Beispieldaten
```

> `config/config.php` ist in `.gitignore` — nie committen.  
> `sql/` wird nie auf den Server deployed — nur lokal / phpMyAdmin.

-----

## Datenbankschema (Übersicht)

```
subventionen            Stammdaten (bezeichnung, foerderstelle, kategorie, ...)
  └── subvention_betraege      Betragsregeln (grundbetrag, pro_tn, pro_tag, max_gesamt)
  └── subvention_trainerarten  Erlaubte Trainerarten + Zusatzbetrag
  └── subvention_eventarten    Erlaubte Eventarten + Multiplikator
  └── simulationen             Protokoll der Berechnungen (Phase 2)
```

### ENUMs

```php
// Subvention::KATEGORIEN
'ausbildung' | 'lager' | 'wettkampf' | 'infrastruktur' | 'jugend' | 'sonstiges'

// Subvention::TRAINERARTEN
'js_trainer'   // J+S Trainer
'nwf_trainer'  // NWF Trainer
'ohne'         // Trainer ohne Anerkennung

// Subvention::EVENTARTEN
'lager' | 'training'
```

### Berechnungsformel

```
total = grundbetrag
      + (betrag_pro_teilnehmer × min(anzahl_tn, max_tn))
      + (betrag_pro_tag        × min(anzahl_tage, max_tage))
      + trainer_zusatzbetrag
total = total × eventart_multiplikator
total = min(total, betrag_max_gesamt)   // nur wenn max_gesamt > 0
```

Implementiert in `Subvention::berechnen(int $id, array $params)`.

-----

## Coding-Konventionen

- **Sprache:** Deutsch für alle Labels, Fehlermeldungen, Kommentare und Variablennamen (ausser PHP-Klassen/Methoden in PascalCase/camelCase)
- **PHP:** Typisierte Parameter und Rückgabewerte wo möglich; `match` statt `switch`; kein `die()`
- **SQL:** Ausschliesslich Prepared Statements (PDO); kein Query-Building durch String-Konkatenation
- **HTML:** Semantisch korrekt; Tailwind-Utility-Klassen; Alpine.js `x-data` nur auf dem nächstnötigen Element
- **Fehlerbehandlung:** Exceptions fangen, sinnvolle Fehlermeldung anzeigen, nie rohe PHP-Fehler ans Frontend
- **Kein `var_dump` / `print_r` / `die()` im committed Code**

-----

## Wichtige Dateien

|Datei                      |Zweck                                        |
|---------------------------|---------------------------------------------|
|`includes/db.php`          |`db(): PDO` – einziger Datenbankzugriffspunkt|
|`includes/Subvention.php`  |Alle DB-Operationen + Berechnungslogik       |
|`includes/auth.php`        |Session-Auth: `auth_erforderlich()`, `auth_anmelden()`, `auth_abmelden()`|
|`config/config.example.php`|Vorlage – bei Änderungen hier auch anpassen  |
|`sql/schema.sql`           |Einzige Wahrheit für die DB-Struktur         |
|`public_html/.htaccess`    |HTTPS, Sicherheitsheader, Verzeichnisschutz  |
|`public_html/login.php`    |Login-Formular                               |
|`public_html/logout.php`   |Session beenden und auf Login umleiten       |

-----

## Aktueller Stand

|Phase            |Inhalt                                               |Status   |
|-----------------|-----------------------------------------------------|---------|
|**1 – Erfassung**|Schema, `Subvention.php`, `erfassen.php`, `index.php`|In Arbeit|
|**2 – Simulator**|`simulieren.php`, `api/simulate.php`                 |Geplant  |
|**3 – Export**   |PDF-Export der Simulation                            |Optional |

-----

## Deployment auf cyon

```bash
# Nur public_html/, api/, includes/ deployen
# config/ und sql/ bleiben lokal
rsync -av --exclude='config/config.php' --exclude='sql/' . user@cyon-server:/pfad/
```

PHP-Version in my.cyon auf **8.2** setzen.  
SSL (Let’s Encrypt) für `subventionssimulator.zurich-sailing.ch` aktivieren.

-----

## Nicht tun

- Kein `composer require` – cyon Shared Hosting hat kein Composer im PATH
- Kein `npm` / Build-Tools – kein Node.js verfügbar
- Kein `.env` – wir nutzen `config/config.php` mit `define()`
- `config/config.php` nie committen
- Keine externen CDN-Ressourcen ausser: jsdelivr.net, cdn.tailwindcss.com, alpinejs.dev