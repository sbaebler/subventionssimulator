<?php
require_once __DIR__ . '/../includes/Subvention.php';
require_once __DIR__ . '/../includes/auth.php';

// Session starten und Login erzwingen, BEVOR der POST verarbeitet wird –
// sonst liefert auth_benutzer_id() unten 0 (Session noch nicht gestartet)
// und das Speichern verletzt den Fremdschlüssel auf benutzer(id).
auth_erforderlich();

// CHF-Beträge werden mit Punkt als Dezimaltrennzeichen erfasst.
// Eine allfällige Komma-Eingabe (z.B. "9,00") wird hier auf Punkt
// normalisiert, damit (float) den Nachkommateil nicht verliert.
function dezimal($wert): float {
    return (float) str_replace([',', "'"], ['.', ''], (string)$wert);
}

// Für die Anzeige: 0 bzw. 0.00 als leeres Feld ("leer = unbegrenzt / kein Wert"),
// Dezimalwerte ohne überflüssige Nullen (2.8000 → 2.8).
function betragAnzeige($wert): string {
    $f = (float)str_replace([',', "'"], ['.', ''], (string)$wert);
    if ($f == 0.0) return '';
    $s = rtrim(rtrim(number_format($f, 4, '.', ''), '0'), '.');
    return $s;
}

$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
$subv = $id ? Subvention::laden($id) : null;
$pageTitle = $subv ? 'Förderprogramm bearbeiten' : 'Neues Förderprogramm erfassen';

// POST: Speichern
$fehler = [];
$erfolg = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id'              => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'bezeichnung'     => trim($_POST['bezeichnung'] ?? ''),
        'beschreibung'    => trim($_POST['beschreibung'] ?? ''),
        'foerderstelle'   => trim($_POST['foerderstelle'] ?? ''),
        'kategorie'            => $_POST['kategorie'] ?? 'sonstiges',
        'berechnungstyp'       => $_POST['berechnungstyp'] ?? '',
        'voraussetzungen'      => trim($_POST['voraussetzungen'] ?? ''),
        'berechtigte'          => trim($_POST['berechtigte'] ?? '') ?: null,
        'einschraenkungen'     => trim($_POST['einschraenkungen'] ?? '') ?: null,
        'verlangte_unterlagen' => trim($_POST['verlangte_unterlagen'] ?? '') ?: null,
        'berechnungsgrundlage' => trim($_POST['berechnungsgrundlage'] ?? '') ?: null,
        'antragsfrist'         => ($_POST['antragsfrist'] ?? '') ?: null,
        'gueltig_von'          => ($_POST['gueltig_von']  ?? '') ?: null,
        'gueltig_bis'          => ($_POST['gueltig_bis']  ?? '') ?: null,
        'link_extern'          => trim($_POST['link_extern'] ?? '') ?: null,
        'aktiv'                => isset($_POST['aktiv']) ? 1 : 0,
        'betraege'             => [],
        'trainerarten'         => [],
        'eventarten'           => [],
        'fristen'              => [],
        'historie'             => [],
    ];

    // Betraege aus POST – Zeilen ohne einen einzigen Wert werden nicht gespeichert
    // (ein Förderprogramm ohne Beitragssatz gilt als "unvollständig", nicht als Fehler)
    foreach ($_POST['betraege'] ?? [] as $b) {
        $zeile = [
            'bezeichnung'             => trim($b['bezeichnung'] ?? '') ?: 'Hauptbeitrag',
            'grundbetrag'             => dezimal($b['grundbetrag'] ?? 0),
            'betrag_pro_teilnehmer'   => dezimal($b['betrag_pro_teilnehmer'] ?? 0),
            'betrag_pro_tag'          => dezimal($b['betrag_pro_tag'] ?? 0),
            'max_teilnehmer'          => (int)($b['max_teilnehmer'] ?? 0),
            'max_tage'                => (int)($b['max_tage'] ?? 0),
            'betrag_max_gesamt'       => dezimal($b['betrag_max_gesamt'] ?? 0),
            'satz_mit_uebernachtung'  => dezimal($b['satz_mit_uebernachtung'] ?? 0),
            'satz_ohne_uebernachtung' => dezimal($b['satz_ohne_uebernachtung'] ?? 0),
            'betrag_pro_stunde'       => dezimal($b['betrag_pro_stunde'] ?? 0),
            'max_stunden_pro_tag'     => (int)($b['max_stunden_pro_tag'] ?? 0),
            'betrag_pro_einheit'      => dezimal($b['betrag_pro_einheit'] ?? 0),
            'max_lektionen_pro_tag'   => (int)($b['max_lektionen_pro_tag'] ?? 0),
        ];
        $hatWert = false;
        foreach ($zeile as $feld => $wert) {
            if ($feld !== 'bezeichnung' && (float)$wert != 0.0) { $hatWert = true; break; }
        }
        if ($hatWert) $data['betraege'][] = $zeile;
    }

    // Trainerarten aus POST
    foreach ($_POST['trainerarten'] ?? [] as $t) {
        if (empty($t['trainerart'])) continue;
        $data['trainerarten'][] = [
            'trainerart'   => $t['trainerart'],
            'zusatzbetrag' => dezimal($t['zusatzbetrag'] ?? 0),
            'bemerkung'    => trim($t['bemerkung'] ?? '') ?: null,
        ];
    }

    // Eventarten aus POST
    foreach ($_POST['eventarten'] ?? [] as $e) {
        if (empty($e['eventart'])) continue;
        $data['eventarten'][] = [
            'eventart'     => $e['eventart'],
            'multiplikator'=> dezimal($e['multiplikator'] ?? 1) ?: 1.0,
            'bemerkung'    => trim($e['bemerkung'] ?? '') ?: null,
        ];
    }

    // Fristen aus POST
    foreach ($_POST['fristen'] ?? [] as $f) {
        if (empty(trim($f['bezeichnung'] ?? ''))) continue;
        $data['fristen'][] = [
            'bezeichnung' => trim($f['bezeichnung']),
            'datum'       => $f['datum'] ?: null,
            'hinweis'     => trim($f['hinweis'] ?? '') ?: null,
        ];
    }

    // Betragshistorie aus POST
    foreach ($_POST['historie'] ?? [] as $h) {
        if (empty($h['jahr'])) continue;
        $data['historie'][] = [
            'jahr'      => (int)$h['jahr'],
            'betrag'    => dezimal($h['betrag'] ?? 0),
            'bemerkung' => trim($h['bemerkung'] ?? '') ?: null,
        ];
    }

    // Bewusst nur zwei Pflichtfelder: Wissen soll erfasst werden können,
    // auch wenn Beträge oder Details noch fehlen ("unvollständig" statt blockiert).
    if (empty($data['bezeichnung'])) $fehler[] = 'Bezeichnung ist pflicht.';
    if (empty($data['foerderstelle'])) $fehler[] = 'Förderstelle ist pflicht.';

    if (empty($fehler)) {
        try {
            $newId = Subvention::speichern($data, auth_benutzer_id() ?: null);
            header('Location: /erfassen.php?id=' . $newId . '&gespeichert=1');
            exit;
        } catch (Throwable $e) {
            error_log('[Subventionssimulator] speichern() Fehler: ' . $e->getMessage());
            $fehler[] = 'Beim Speichern ist ein Fehler aufgetreten. Details im Server-Log.';
        }
    }

    // Bei Fehlern die Eingaben behalten statt aus der DB zu überschreiben
    if ($fehler) $subv = array_merge($subv ?? [], $data);
}

if (isset($_GET['gespeichert'])) $erfolg = true;
if (empty($fehler)) $subv = $id ? Subvention::laden($id) : null;

// Betragszeilen für die Anzeige aufbereiten: 0-Werte als leeres Feld
$betragFelderDezimal = ['grundbetrag','betrag_pro_teilnehmer','betrag_pro_tag','betrag_max_gesamt',
                        'satz_mit_uebernachtung','satz_ohne_uebernachtung','betrag_pro_stunde','betrag_pro_einheit'];
$betragFelderGanz    = ['max_teilnehmer','max_tage','max_stunden_pro_tag','max_lektionen_pro_tag'];
$betraegeAnzeige = [];
foreach ($subv['betraege'] ?? [] as $b) {
    $zeile = ['bezeichnung' => $b['bezeichnung'] ?? 'Hauptbeitrag'];
    foreach ($betragFelderDezimal as $f) $zeile[$f] = betragAnzeige($b[$f] ?? 0);
    foreach ($betragFelderGanz    as $f) $zeile[$f] = ((int)($b[$f] ?? 0)) ?: '';
    $betraegeAnzeige[] = $zeile;
}

$trainerAnzeige = array_map(fn($t) => [
    'trainerart'   => $t['trainerart'],
    'zusatzbetrag' => betragAnzeige($t['zusatzbetrag'] ?? 0),
    'bemerkung'    => $t['bemerkung'] ?? '',
], $subv['trainerarten'] ?? []);

$eventAnzeige = array_map(fn($e) => [
    'eventart'      => $e['eventart'],
    'multiplikator' => betragAnzeige($e['multiplikator'] ?? 1) ?: '1',
    'bemerkung'     => $e['bemerkung'] ?? '',
], $subv['eventarten'] ?? []);

$alpineBetraege     = htmlspecialchars(json_encode($betraegeAnzeige), ENT_QUOTES, 'UTF-8');
$alpineTrainerarten = htmlspecialchars(json_encode($trainerAnzeige), ENT_QUOTES, 'UTF-8');
$alpineEventarten   = htmlspecialchars(json_encode($eventAnzeige), ENT_QUOTES, 'UTF-8');
$alpineFristen      = htmlspecialchars(json_encode($subv['fristen']  ?? []), ENT_QUOTES, 'UTF-8');
$alpineHistorie     = htmlspecialchars(json_encode($subv['historie'] ?? []), ENT_QUOTES, 'UTF-8');

// Berechnungstyp für die Maske: bestehendes Programm ohne Beitragssätze
// wird als "weiss ich noch nicht" angezeigt, neue starten ohne Auswahl.
$initTyp = $subv
    ? (empty($betraegeAnzeige) ? 'unbekannt' : ($subv['berechnungstyp'] ?? 'additiv'))
    : '';

// Wizard nur bei Neuerfassung; beim Bearbeiten (und nach Fehlern) alle Abschnitte zeigen
$wizard = !$subv && empty($fehler);

// Bekannte Förderstellen als Eingabehilfe (frei überschreibbar)
$foerderstellen = [
    'Zürcher Kantonalverband für Sport (ZKS)',
    'BASPO / Jugend+Sport',
    'Sportamt Kanton Zürich',
    'Swiss Olympic',
    'Swiss Sailing',
    'Zurich Sailing Federation (ZSF)',
];

// Berechnungstypen als verständliche Auswahlkarten
$typKarten = [
    'pauschale' => [
        'titel'    => 'Fixer Betrag',
        'text'     => 'Ein fester Betrag pro Jahr oder Projekt.',
        'beispiel' => 'z.B. ZKS Grundbeitrag',
    ],
    'js_teilnehmertag' => [
        'titel'    => 'Pro Teilnehmer und Tag',
        'text'     => 'Satz × Teilnehmer × Tage, mit oder ohne Übernachtung.',
        'beispiel' => 'z.B. J+S Lager',
    ],
    'js_teilnehmerstunde' => [
        'titel'    => 'Pro Teilnehmerstunde',
        'text'     => 'Satz × Teilnehmer × Tage × Stunden pro Tag.',
        'beispiel' => 'z.B. J+S Training',
    ],
    'zks_ausbildungseinheit' => [
        'titel'    => 'Pro Ausbildungseinheit',
        'text'     => 'Satz × Teilnehmer × Lektionen pro Tag × Tage.',
        'beispiel' => 'z.B. ZKS Ausbildungsbeitrag',
    ],
    'additiv' => [
        'titel'    => 'Grundbetrag plus Zuschläge',
        'text'     => 'Grundbetrag + Betrag pro Teilnehmer + Betrag pro Tag.',
        'beispiel' => 'z.B. kantonale Beiträge',
    ],
    'jahresbeitrag' => [
        'titel'    => 'Nach Jahreskennzahl',
        'text'     => 'Betrag pro Einheit einer Verbandskennzahl.',
        'beispiel' => 'z.B. pro Aktivmitglied',
    ],
    'unbekannt' => [
        'titel'    => 'Weiss ich noch nicht',
        'text'     => 'Berechnung in eigenen Worten beschreiben – Zahlen können später ergänzt werden.',
        'beispiel' => 'kein Problem, einfach speichern',
    ],
];

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <a href="/" class="text-sm link-muted">&larr; Zurück zur Übersicht</a>
  <h1 class="text-2xl font-semibold mt-2"><?= htmlspecialchars($pageTitle) ?></h1>
  <?php if (!$subv): ?>
  <p class="text-sm text-muted mt-1">Trage ein, was du weisst – alles ausser Name und Förderstelle kannst du jederzeit später ergänzen.</p>
  <?php endif; ?>
</div>

<?php if ($erfolg): ?>
<div class="alert alert--success mb-6">
  Förderprogramm wurde gespeichert.
</div>
<?php endif; ?>

<?php if ($fehler): ?>
<div class="alert alert--error mb-6">
  <ul class="list-disc list-inside">
    <?php foreach ($fehler as $f): ?>
    <li><?= htmlspecialchars($f) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="post" action="/erfassen.php"
      x-data="{
        wizard: <?= $wizard ? 'true' : 'false' ?>,
        schritt: 1,
        bezeichnung:   <?= htmlspecialchars(json_encode($subv['bezeichnung'] ?? ''), ENT_QUOTES, 'UTF-8') ?>,
        foerderstelle: <?= htmlspecialchars(json_encode($subv['foerderstelle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>,
        berechnungstyp: '<?= htmlspecialchars($initTyp) ?>',
        betraege:     <?= $alpineBetraege ?>,
        trainerarten: <?= $alpineTrainerarten ?>,
        eventarten:   <?= $alpineEventarten ?>,
        fristen:      <?= $alpineFristen ?>,
        historie:     <?= $alpineHistorie ?>,
        beispielOffen: false,
        detailsTrainer: {},
        detailsEvent: {},

        get speicherbar() { return this.bezeichnung.trim() !== '' && this.foerderstelle.trim() !== '' },

        gehe(s) { this.schritt = s; window.scrollTo({top: 0}) },

        waehleTyp(t) {
          this.berechnungstyp = t;
          if (t !== 'unbekannt' && this.betraege.length === 0) this.addBetrag();
        },
        leereZeile() {
          return {bezeichnung:'Hauptbeitrag',grundbetrag:'',betrag_pro_teilnehmer:'',betrag_pro_tag:'',
                  max_teilnehmer:'',max_tage:'',betrag_max_gesamt:'',satz_mit_uebernachtung:'',
                  satz_ohne_uebernachtung:'',betrag_pro_stunde:'',max_stunden_pro_tag:'',
                  betrag_pro_einheit:'',max_lektionen_pro_tag:''}
        },
        addBetrag()    { const z = this.leereZeile(); if (this.betraege.length > 0) z.bezeichnung = 'Weitere Komponente'; this.betraege.push(z) },
        rmBetrag(i)    { this.betraege.splice(i,1) },

        zahl(v) { return parseFloat(String(v ?? '').replace(/'/g,'').replace(',', '.')) || 0 },
        cap(wert, max) { const m = this.zahl(max); return m > 0 ? Math.min(wert, m) : wert },
        get beispielText() {
          const b = this.betraege[0];
          if (!b) return '';
          const chf = v => {
            const max = this.zahl(b.betrag_max_gesamt);
            if (max > 0 && v > max) v = max;
            return 'CHF ' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, &quot;'&quot;);
          };
          const tn = this.cap(20, b.max_teilnehmer), tage = this.cap(5, b.max_tage);
          switch (this.berechnungstyp) {
            case 'additiv':
              return 'Beispiel: 20 Teilnehmer, 5 Tage ergeben ' + chf(this.zahl(b.grundbetrag) + this.zahl(b.betrag_pro_teilnehmer)*tn + this.zahl(b.betrag_pro_tag)*tage);
            case 'js_teilnehmertag':
              return 'Beispiel: 20 Teilnehmer, 5 Tage mit Übernachtung ergeben ' + chf(this.zahl(b.satz_mit_uebernachtung)*tn*tage);
            case 'js_teilnehmerstunde': {
              const std = this.cap(2, b.max_stunden_pro_tag);
              return 'Beispiel: 20 Teilnehmer, 5 Tage à 2 Stunden ergeben ' + chf(this.zahl(b.betrag_pro_stunde)*tn*tage*std);
            }
            case 'zks_ausbildungseinheit': {
              const lek = this.cap(4, b.max_lektionen_pro_tag);
              return 'Beispiel: 20 Teilnehmer, 4 Lektionen/Tag, 5 Tage ergeben ' + chf(this.zahl(b.betrag_pro_einheit)*tn*lek*tage);
            }
            case 'pauschale':
              return 'Fixer Betrag: ' + chf(this.zahl(b.grundbetrag));
            case 'jahresbeitrag': {
              const satz = this.zahl(b.betrag_pro_einheit);
              return satz > 0
                ? 'Beispiel: 300 Einheiten ergeben ' + chf(satz*300)
                : 'Referenzbetrag: ' + chf(this.zahl(b.grundbetrag));
            }
            default: return '';
          }
        },

        hatTrainer(ta)    { return this.trainerarten.some(t => t.trainerart === ta) },
        trainerIndex(ta)  { return this.trainerarten.findIndex(t => t.trainerart === ta) },
        toggleTrainer(ta) {
          const i = this.trainerIndex(ta);
          if (i >= 0) this.trainerarten.splice(i,1);
          else this.trainerarten.push({trainerart:ta,zusatzbetrag:'',bemerkung:''});
        },
        hatEvent(ea)    { return this.eventarten.some(e => e.eventart === ea) },
        eventIndex(ea)  { return this.eventarten.findIndex(e => e.eventart === ea) },
        toggleEvent(ea) {
          const i = this.eventIndex(ea);
          if (i >= 0) this.eventarten.splice(i,1);
          else this.eventarten.push({eventart:ea,multiplikator:'1',bemerkung:''});
        },

        addFrist()     { this.fristen.push({bezeichnung:'',datum:'',hinweis:''}) },
        rmFrist(i)     { this.fristen.splice(i,1) },
        addHistorie()  { this.historie.push({jahr:new Date().getFullYear(),betrag:'',bemerkung:''}) },
        rmHistorie(i)  { this.historie.splice(i,1) }
      }">

  <input type="hidden" name="id" value="<?= $subv['id'] ?? '' ?>">
  <input type="hidden" name="berechnungstyp" :value="berechnungstyp === 'unbekannt' ? '' : berechnungstyp">

  <!-- ── Fortschritt (nur Wizard) ───────────────────── -->
  <div x-show="wizard" class="flex items-center gap-2 mb-6 text-sm" x-cloak>
    <template x-for="(name, i) in ['Beitrag', 'Berechnung', 'Bedingungen', 'Termine & Beträge']" :key="i">
      <button type="button" @click="gehe(i+1)"
              class="wizard-step"
              :class="{ 'is-active': schritt === i+1 }">
        <span class="font-semibold" x-text="i+1"></span>
        <span x-text="name"></span>
      </button>
    </template>
  </div>

  <!-- ── Schritt 1: Was für ein Beitrag? ─────────────── -->
  <section id="schritt-1" x-show="!wizard || schritt === 1"
           class="card mb-6">
    <h2 class="font-semibold mb-1">Was für ein Beitrag ist es?</h2>
    <p class="text-xs text-subtle mb-4">Nur Name und Förderstelle sind Pflicht – alles andere kannst du später ergänzen.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Name des Förderprogramms *</label>
        <input type="text" name="bezeichnung" x-model="bezeichnung"
               placeholder="z.B. ZKS Ausbildungsbeitrag"
               class="input">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Förderstelle *</label>
        <input type="text" name="foerderstelle" x-model="foerderstelle" list="foerderstellen-liste"
               class="input"
               placeholder="Wer zahlt den Beitrag aus?">
        <datalist id="foerderstellen-liste">
          <?php foreach ($foerderstellen as $fs): ?>
          <option value="<?= htmlspecialchars($fs) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Kategorie</label>
        <select name="kategorie"
                class="input">
          <?php foreach (Subvention::KATEGORIEN as $k => $label): ?>
          <option value="<?= $k ?>" <?= ($subv['kategorie'] ?? '') === $k ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Worum geht es? (Beschreibung)</label>
        <textarea name="beschreibung" rows="2"
                  placeholder="In ein, zwei Sätzen: Wofür zahlt die Förderstelle diesen Beitrag?"
                  class="input"><?= htmlspecialchars($subv['beschreibung'] ?? '') ?></textarea>
      </div>
    </div>
  </section>

  <!-- ── Schritt 2: Wie wird gerechnet? ──────────────── -->
  <section id="schritt-2" x-show="!wizard || schritt === 2"
           class="card mb-6">
    <h2 class="font-semibold mb-1">Wie wird der Beitrag berechnet?</h2>
    <p class="text-xs text-subtle mb-4">Wähle das passende Muster. Danach erscheinen nur die Felder, die dafür nötig sind.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
      <?php foreach ($typKarten as $typ => $karte): ?>
      <button type="button" @click="waehleTyp('<?= $typ ?>')"
              class="choice-card text-left"
              :class="{ 'is-selected': berechnungstyp === '<?= $typ ?>' }">
        <p class="text-sm font-semibold"><?= htmlspecialchars($karte['titel']) ?></p>
        <p class="text-xs text-muted mt-1"><?= htmlspecialchars($karte['text']) ?></p>
        <p class="text-xs text-primary mt-1"><?= htmlspecialchars($karte['beispiel']) ?></p>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Beispiel-Panel (Inline-Hilfe) -->
    <div class="mb-5">
      <button type="button" @click="beispielOffen = !beispielOffen"
              class="link text-sm">
        <span x-text="beispielOffen ? '▾' : '▸'"></span> Beispiel ansehen: ZKS Ausbildungsbeitrag
      </button>
      <div x-show="beispielOffen" x-cloak class="alert alert--info mt-2 p-4">
        <p class="mb-2"><strong>So wurde der ZKS Ausbildungsbeitrag erfasst:</strong></p>
        <ul class="space-y-1 list-disc list-inside">
          <li>Muster: <em>Pro Ausbildungseinheit</em> (Teilnehmer × Lektionen × Tage)</li>
          <li>Satz pro Ausbildungseinheit: <strong>2.80</strong>, max. <strong>6 Lektionen pro Tag</strong></li>
          <li>Gilt für Lager und Trainings, die als Ausbildung deklariert sind – keine Wettkämpfe</li>
          <li>Fristen: Musterteilnehmerliste bis 28.02., Einreichung bis 31.03. des Folgejahres</li>
          <li>Erhaltene Beträge: 2024 CHF 12'949.00, 2025 ca. CHF 30'000.00</li>
        </ul>
      </div>
    </div>

    <!-- Hinweis, solange kein Typ gewählt -->
    <p x-show="berechnungstyp === ''" class="text-sm text-subtle" x-cloak>
      Noch kein Muster gewählt. Wenn du unsicher bist, nimm «Weiss ich noch nicht» und beschreibe die Berechnung unten in Worten.
    </p>

    <!-- Freitext bei "weiss ich noch nicht" oder als Ergänzung -->
    <div x-show="berechnungstyp !== ''" class="mb-5">
      <label class="block text-sm font-medium mb-1"
             x-text="berechnungstyp === 'unbekannt' ? 'Beschreibe die Berechnung in eigenen Worten' : 'Berechnung in eigenen Worten (optional)'"></label>
      <textarea name="berechnungsgrundlage" rows="2"
                placeholder="z.B. «CHF 2.80 pro Teilnehmer und Lektion, maximal 6 Lektionen pro Tag»"
                class="input"><?= htmlspecialchars($subv['berechnungsgrundlage'] ?? '') ?></textarea>
    </div>

    <!-- Betragsfelder je Typ -->
    <template x-for="(b, i) in betraege" :key="i">
      <div x-show="berechnungstyp !== '' && berechnungstyp !== 'unbekannt'"
           class="card card--muted mb-3">
        <input type="hidden" :name="'betraege['+i+'][bezeichnung]'" x-model="b.bezeichnung" x-show="i === 0">
        <div x-show="i > 0" class="flex justify-between items-center mb-3">
          <input type="text" :name="i > 0 ? 'betraege['+i+'][bezeichnung]' : null" x-model="b.bezeichnung"
                 placeholder="Name der Komponente"
                 class="text-sm font-medium border-0 bg-transparent focus:outline-none focus:ring-1 rounded px-1 w-64">
          <button type="button" @click="rmBetrag(i)" class="link--danger text-xs">
            Entfernen
          </button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
          <div x-show="berechnungstyp === 'additiv' || berechnungstyp === 'pauschale' || berechnungstyp === 'jahresbeitrag'">
            <label class="block text-xs text-muted mb-1" x-text="berechnungstyp === 'additiv' ? 'Grundbetrag (CHF)' : 'Betrag (CHF)'">Grundbetrag (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][grundbetrag]'" x-model="b.grundbetrag"
                   class="input input--sm">
          </div>
          <div x-show="berechnungstyp === 'additiv'">
            <label class="block text-xs text-muted mb-1">Betrag pro Teilnehmer (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_teilnehmer]'" x-model="b.betrag_pro_teilnehmer"
                   class="input input--sm">
          </div>
          <div x-show="berechnungstyp === 'additiv'">
            <label class="block text-xs text-muted mb-1">Betrag pro Tag (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_tag]'" x-model="b.betrag_pro_tag"
                   class="input input--sm">
          </div>

          <!-- js_teilnehmertag -->
          <div x-show="berechnungstyp === 'js_teilnehmertag'">
            <label class="block text-xs text-muted mb-1">Satz mit Übernachtung (CHF pro Teilnehmer und Tag)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][satz_mit_uebernachtung]'" x-model="b.satz_mit_uebernachtung"
                   class="input input--sm">
          </div>
          <div x-show="berechnungstyp === 'js_teilnehmertag'">
            <label class="block text-xs text-muted mb-1">Satz ohne Übernachtung (CHF pro Teilnehmer und Tag)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][satz_ohne_uebernachtung]'" x-model="b.satz_ohne_uebernachtung"
                   class="input input--sm">
          </div>

          <!-- js_teilnehmerstunde -->
          <div x-show="berechnungstyp === 'js_teilnehmerstunde'">
            <label class="block text-xs text-muted mb-1">Betrag pro Teilnehmerstunde (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_stunde]'" x-model="b.betrag_pro_stunde"
                   class="input input--sm">
          </div>
          <div x-show="berechnungstyp === 'js_teilnehmerstunde'">
            <label class="block text-xs text-muted mb-1">Max. Stunden pro Tag</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_stunden_pro_tag]'" x-model="b.max_stunden_pro_tag"
                   placeholder="leer = unbegrenzt"
                   class="input input--sm">
          </div>

          <!-- zks_ausbildungseinheit / jahresbeitrag -->
          <div x-show="berechnungstyp === 'zks_ausbildungseinheit' || berechnungstyp === 'jahresbeitrag'">
            <label class="block text-xs text-muted mb-1" x-text="berechnungstyp === 'jahresbeitrag' ? 'Betrag pro Einheit (CHF)' : 'Satz pro Ausbildungseinheit (CHF)'">Satz pro Ausbildungseinheit (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_einheit]'" x-model="b.betrag_pro_einheit"
                   class="input input--sm">
          </div>
          <div x-show="berechnungstyp === 'zks_ausbildungseinheit'">
            <label class="block text-xs text-muted mb-1">Max. Lektionen pro Tag</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_lektionen_pro_tag]'" x-model="b.max_lektionen_pro_tag"
                   placeholder="leer = unbegrenzt"
                   class="input input--sm">
          </div>

          <div x-show="berechnungstyp === 'additiv' || berechnungstyp === 'js_teilnehmertag' || berechnungstyp === 'js_teilnehmerstunde' || berechnungstyp === 'zks_ausbildungseinheit'">
            <label class="block text-xs text-muted mb-1">Max. Teilnehmer</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_teilnehmer]'" x-model="b.max_teilnehmer"
                   placeholder="leer = unbegrenzt"
                   class="input input--sm">
          </div>
          <div x-show="berechnungstyp === 'additiv' || berechnungstyp === 'js_teilnehmertag' || berechnungstyp === 'js_teilnehmerstunde' || berechnungstyp === 'zks_ausbildungseinheit'">
            <label class="block text-xs text-muted mb-1">Max. Tage</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_tage]'" x-model="b.max_tage"
                   placeholder="leer = unbegrenzt"
                   class="input input--sm">
          </div>
          <div>
            <label class="block text-xs text-muted mb-1">Max. Gesamtbetrag (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_max_gesamt]'" x-model="b.betrag_max_gesamt"
                   placeholder="leer = kein Limit"
                   class="input input--sm">
          </div>
        </div>
      </div>
    </template>

    <!-- Live-Beispielrechnung -->
    <div x-show="berechnungstyp !== '' && berechnungstyp !== 'unbekannt' && betraege.length > 0 && beispielText"
         class="alert alert--success" x-cloak>
      <span x-text="beispielText"></span>
      <span class="text-xs opacity-75">(Probe-Rechnung mit deinen Sätzen)</span>
    </div>

    <button type="button" x-show="berechnungstyp !== '' && berechnungstyp !== 'unbekannt' && betraege.length >= 1"
            @click="addBetrag()"
            class="btn btn--secondary btn--sm mt-4" x-cloak>
      + weitere Beitragskomponente
    </button>
  </section>

  <!-- ── Schritt 3: Bedingungen (optional) ───────────── -->
  <section id="schritt-3" x-show="!wizard || schritt === 3"
           class="card mb-6">
    <h2 class="font-semibold mb-1">Bedingungen <span class="text-xs font-normal text-subtle">(optional)</span></h2>
    <p class="text-xs text-subtle mb-4">Nur einschränken, wenn die Förderstelle es wirklich vorschreibt. Keine Auswahl = gilt für alle.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div>
        <label class="block text-sm font-medium mb-1">Voraussetzungen</label>
        <textarea name="voraussetzungen" rows="2"
                  class="input"
                  placeholder="z.B. Mindestalter, Mitgliedschaft, Anerkennungen"><?= htmlspecialchars($subv['voraussetzungen'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Wer ist berechtigt?</label>
        <textarea name="berechtigte" rows="2"
                  class="input"
                  placeholder="z.B. Vereine mit Sitz im Kanton Zürich"><?= htmlspecialchars($subv['berechtigte'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Einschränkungen / Vorgaben</label>
        <textarea name="einschraenkungen" rows="2"
                  class="input"
                  placeholder="z.B. keine Wettkämpfe, max. 6 Lektionen pro Tag"><?= htmlspecialchars($subv['einschraenkungen'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Verlangte Unterlagen</label>
        <textarea name="verlangte_unterlagen" rows="2"
                  class="input"
                  placeholder="z.B. Teilnehmerliste, Kursprogramm, Leiterausweise"><?= htmlspecialchars($subv['verlangte_unterlagen'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Eventarten als Checkboxen -->
    <div class="mb-6">
      <p class="text-sm font-medium mb-2">Gilt der Beitrag nur für bestimmte Eventarten?</p>
      <div class="space-y-2">
        <?php foreach (Subvention::EVENTARTEN as $key => $label): ?>
        <div class="card card--muted px-3 py-2">
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" :checked="hatEvent('<?= $key ?>')" @change="toggleEvent('<?= $key ?>')"
                   class="checkbox">
            <span>Nur für <?= $label ?></span>
            <button type="button" x-show="hatEvent('<?= $key ?>')" @click.prevent="detailsEvent['<?= $key ?>'] = !detailsEvent['<?= $key ?>']"
                    class="link text-xs ml-2" x-cloak>Details</button>
          </label>
          <template x-if="hatEvent('<?= $key ?>')">
            <div>
              <input type="hidden" :name="'eventarten['+eventIndex('<?= $key ?>')+'][eventart]'" value="<?= $key ?>">
              <div x-show="detailsEvent['<?= $key ?>']" class="flex flex-wrap gap-3 mt-2 pl-6" x-cloak>
                <div>
                  <label class="block text-xs text-muted mb-1">Faktor auf den Betrag (1 = normal)</label>
                  <input type="text" inputmode="decimal"
                         :name="'eventarten['+eventIndex('<?= $key ?>')+'][multiplikator]'"
                         x-model="eventarten[eventIndex('<?= $key ?>')].multiplikator"
                         class="input input--sm w-24">
                </div>
                <div class="flex-1 min-w-[200px]">
                  <label class="block text-xs text-muted mb-1">Bemerkung</label>
                  <input type="text"
                         :name="'eventarten['+eventIndex('<?= $key ?>')+'][bemerkung]'"
                         x-model="eventarten[eventIndex('<?= $key ?>')].bemerkung"
                         class="input input--sm" placeholder="Optional">
                </div>
              </div>
            </div>
          </template>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Trainerarten als Checkboxen -->
    <div>
      <p class="text-sm font-medium mb-2">Braucht es eine bestimmte Trainer-Anerkennung?</p>
      <div class="space-y-2">
        <?php foreach (Subvention::TRAINERARTEN as $key => $label): ?>
        <div class="card card--muted px-3 py-2">
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" :checked="hatTrainer('<?= $key ?>')" @change="toggleTrainer('<?= $key ?>')"
                   class="checkbox">
            <span><?= $label ?></span>
            <button type="button" x-show="hatTrainer('<?= $key ?>')" @click.prevent="detailsTrainer['<?= $key ?>'] = !detailsTrainer['<?= $key ?>']"
                    class="link text-xs ml-2" x-cloak>Details</button>
          </label>
          <template x-if="hatTrainer('<?= $key ?>')">
            <div>
              <input type="hidden" :name="'trainerarten['+trainerIndex('<?= $key ?>')+'][trainerart]'" value="<?= $key ?>">
              <div x-show="detailsTrainer['<?= $key ?>']" class="flex flex-wrap gap-3 mt-2 pl-6" x-cloak>
                <div>
                  <label class="block text-xs text-muted mb-1">Zusatzbetrag (CHF)</label>
                  <input type="text" inputmode="decimal"
                         :name="'trainerarten['+trainerIndex('<?= $key ?>')+'][zusatzbetrag]'"
                         x-model="trainerarten[trainerIndex('<?= $key ?>')].zusatzbetrag"
                         class="input input--sm w-32">
                </div>
                <div class="flex-1 min-w-[200px]">
                  <label class="block text-xs text-muted mb-1">Bemerkung</label>
                  <input type="text"
                         :name="'trainerarten['+trainerIndex('<?= $key ?>')+'][bemerkung]'"
                         x-model="trainerarten[trainerIndex('<?= $key ?>')].bemerkung"
                         class="input input--sm" placeholder="Optional, z.B. J+S-Leiter erforderlich">
                </div>
              </div>
            </div>
          </template>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ── Schritt 4: Termine & Beträge (optional) ─────── -->
  <section id="schritt-4" x-show="!wizard || schritt === 4"
           class="card mb-6">
    <h2 class="font-semibold mb-1">Termine &amp; Beträge <span class="text-xs font-normal text-subtle">(optional)</span></h2>
    <p class="text-xs text-subtle mb-4">Fristen sind reine Hinweise. Erhaltene Beträge pro Jahr kannst du auch später unter «Beiträge &amp; Verwendung» erfassen.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div>
        <label class="block text-sm font-medium mb-1">Eingabefrist bei der Förderstelle</label>
        <input type="date" name="antragsfrist"
               value="<?= htmlspecialchars($subv['antragsfrist'] ?? '') ?>"
               class="input">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Link zur Förderstelle</label>
        <input type="url" name="link_extern"
               value="<?= htmlspecialchars($subv['link_extern'] ?? '') ?>"
               class="input"
               placeholder="https://...">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Gültig von</label>
        <input type="date" name="gueltig_von"
               value="<?= htmlspecialchars($subv['gueltig_von'] ?? '') ?>"
               class="input">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Gültig bis</label>
        <input type="date" name="gueltig_bis"
               value="<?= htmlspecialchars($subv['gueltig_bis'] ?? '') ?>"
               class="input">
        <p class="text-xs text-subtle mt-1">Leer lassen, wenn unbefristet.</p>
      </div>
    </div>

    <!-- Weitere Termine -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-2">
        <p class="text-sm font-medium">Weitere Termine</p>
        <button type="button" @click="addFrist()"
                class="btn btn--secondary btn--sm">
          + Termin
        </button>
      </div>
      <template x-for="(f, i) in fristen" :key="i">
        <div class="card card--muted p-3 mb-2 flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-xs text-muted mb-1">Bezeichnung</label>
            <input type="text" :name="'fristen['+i+'][bezeichnung]'" x-model="f.bezeichnung"
                   placeholder="z.B. Anmeldung"
                   class="input input--sm w-40">
          </div>
          <div>
            <label class="block text-xs text-muted mb-1">Datum</label>
            <input type="date" :name="'fristen['+i+'][datum]'" x-model="f.datum"
                   class="input input--sm w-40">
          </div>
          <div class="flex-1">
            <label class="block text-xs text-muted mb-1">Hinweis</label>
            <input type="text" :name="'fristen['+i+'][hinweis]'" x-model="f.hinweis"
                   class="input input--sm"
                   placeholder="Optional, z.B. via ZKS-Extranet">
          </div>
          <button type="button" @click="rmFrist(i)"
                  class="link--danger text-xs pb-2">Entfernen</button>
        </div>
      </template>
      <p x-show="fristen.length === 0" class="text-sm text-subtle">Kein weiterer Termin erfasst.</p>
    </div>

    <!-- Erhaltene Beträge pro Jahr -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-2">
        <p class="text-sm font-medium">Erhaltene Beträge pro Jahr</p>
        <button type="button" @click="addHistorie()"
                class="btn btn--secondary btn--sm">
          + Jahr
        </button>
      </div>
      <p class="text-xs text-subtle mb-2">Was wurde in einem Jahr tatsächlich erhalten oder erwartet? Basis für die Verteilung unter «Beiträge &amp; Verwendung».</p>
      <template x-for="(h, i) in historie" :key="i">
        <div class="card card--muted p-3 mb-2 flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-xs text-muted mb-1">Jahr</label>
            <input type="number" step="1" min="2000" max="2100" :name="'historie['+i+'][jahr]'" x-model="h.jahr"
                   class="input input--sm w-24">
          </div>
          <div>
            <label class="block text-xs text-muted mb-1">Betrag (CHF)</label>
            <input type="text" inputmode="decimal" :name="'historie['+i+'][betrag]'" x-model="h.betrag"
                   class="input input--sm w-32">
          </div>
          <div class="flex-1">
            <label class="block text-xs text-muted mb-1">Bemerkung</label>
            <input type="text" :name="'historie['+i+'][bemerkung]'" x-model="h.bemerkung"
                   class="input input--sm"
                   placeholder="Optional, z.B. «ca.» oder «definitiv»">
          </div>
          <button type="button" @click="rmHistorie(i)"
                  class="link--danger text-xs pb-2">Entfernen</button>
        </div>
      </template>
      <p x-show="historie.length === 0" class="text-sm text-subtle">Noch keine Beträge erfasst.</p>
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" name="aktiv" id="aktiv" value="1"
             <?= ($subv['aktiv'] ?? 1) ? 'checked' : '' ?>
             class="checkbox">
      <label for="aktiv" class="text-sm font-medium">Aktiv (in Übersicht und Simulator sichtbar)</label>
    </div>
  </section>

  <!-- ── Navigation + Speichern ─────────────────────── -->
  <div class="flex items-center justify-between gap-3">
    <div class="flex gap-2" x-show="wizard" x-cloak>
      <button type="button" x-show="schritt > 1" @click="gehe(schritt - 1)"
              class="btn btn--secondary">
        &larr; Zurück
      </button>
      <button type="button" x-show="schritt < 4" @click="gehe(schritt + 1)"
              class="btn btn--primary">
        Weiter &rarr;
      </button>
    </div>
    <div class="flex items-center gap-3 ml-auto">
      <span x-show="!speicherbar" class="text-xs text-subtle" x-cloak>Zum Speichern fehlen noch Name und Förderstelle (Schritt 1).</span>
      <a href="/" class="text-sm link-muted px-4 py-2">Abbrechen</a>
      <button type="submit" :disabled="!speicherbar" class="btn btn--primary">
        Speichern
      </button>
    </div>
  </div>

</form>

<?php require __DIR__ . '/partials/footer.php'; ?>
