# Subventionssimulator – Zurich Sailing

Subventionssimulator für zurich-sailing.ch

**URL:** `https://subventionssimulator.zurich-sailing.ch`  
**Stack:** PHP 8.2 · MariaDB 10.6 · Alpine.js · Tailwind CSS (CDN)  
**Hosting:** cyon.ch

---

## Setup

### 1. Repo klonen

```bash
git clone https://github.com/sbaebler/subventionssimulator.git
cd subventionssimulator
```

### 2. Konfiguration anlegen

```bash
cp config/config.example.php config/config.php
# config/config.php bearbeiten und cyon-Zugangsdaten eintragen
```

### 3. Datenbank einrichten (cyon my.cyon)

- Neue MariaDB-Datenbank anlegen (z.B. `sailing_subv`)
- `sql/schema.sql` via phpMyAdmin importieren
- Optional: `sql/seed.sql` für Beispieldaten

### 4. PHP-Version auf cyon

In my.cyon → Erweitert → PHP-Einstellungen → **PHP 8.2** wählen

### 5. Deployment

Dateien per FTP oder SSH hochladen. Der Web-Root auf cyon zeigt auf `public_html/`.

```
/public_html/    → Web-Root (cyon-Einstellung)
/api/            → ausserhalb Web-Root (intern)
/includes/       → ausserhalb Web-Root (intern)
/config/         → ausserhalb Web-Root (intern, nicht commiten!)
/sql/            → nur lokal, nie deployen
```

> **Wichtig:** `config/config.php` ist in `.gitignore` und wird nie committet.

---

## Verzeichnisstruktur

```
subventionssimulator/
├── public_html/          Web-Root
│   ├── index.php         Übersicht
│   ├── erfassen.php      Subvention erfassen / bearbeiten
│   ├── simulieren.php    Simulator (Phase 2)
│   ├── .htaccess
│   ├── assets/
│   └── partials/
├── api/                  JSON-Endpoints
├── includes/
│   ├── db.php
│   └── Subvention.php    Model + Berechnungslogik
├── config/
│   └── config.example.php
└── sql/
    ├── schema.sql
    └── seed.sql
```

---

## Betragsformel

```
total = grundbetrag
      + (betrag_pro_teilnehmer × min(anzahl_tn, max_tn))
      + (betrag_pro_tag        × min(anzahl_tage, max_tage))
      + trainer_zusatzbetrag
total = total × eventart_multiplikator
total = min(total, betrag_max_gesamt)   # falls max > 0
```

---

## Roadmap

| Phase | Inhalt | Status |
|---|---|---|
| 1 | DB-Schema, CRUD Erfassung, Listenansicht | In Arbeit |
| 2 | Simulator: Eingabe → Berechnung → Ausgabe | Geplant |
| 3 | PDF-Export, E-Mail | Optional |
