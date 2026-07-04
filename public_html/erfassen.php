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

$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
$subv = $id ? Subvention::laden($id) : null;
$pageTitle = $subv ? 'Subvention bearbeiten' : 'Neue Subvention erfassen';

// POST: Speichern
$fehler = [];
$erfolg = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id'              => $_POST['id'] ? (int)$_POST['id'] : null,
        'bezeichnung'     => trim($_POST['bezeichnung'] ?? ''),
        'beschreibung'    => trim($_POST['beschreibung'] ?? ''),
        'foerderstelle'   => trim($_POST['foerderstelle'] ?? ''),
        'kategorie'            => $_POST['kategorie'] ?? 'sonstiges',
        'voraussetzungen'      => trim($_POST['voraussetzungen'] ?? ''),
        'berechtigte'          => trim($_POST['berechtigte'] ?? '') ?: null,
        'einschraenkungen'     => trim($_POST['einschraenkungen'] ?? '') ?: null,
        'verlangte_unterlagen' => trim($_POST['verlangte_unterlagen'] ?? '') ?: null,
        'berechnungsgrundlage' => trim($_POST['berechnungsgrundlage'] ?? '') ?: null,
        'antragsfrist'         => $_POST['antragsfrist'] ?: null,
        'gueltig_von'          => $_POST['gueltig_von']  ?: null,
        'gueltig_bis'          => $_POST['gueltig_bis']  ?: null,
        'link_extern'          => trim($_POST['link_extern'] ?? '') ?: null,
        'aktiv'                => isset($_POST['aktiv']) ? 1 : 0,
        'betraege'             => [],
        'trainerarten'         => [],
        'eventarten'           => [],
        'fristen'              => [],
        'historie'             => [],
    ];

    // Betraege aus POST
    foreach ($_POST['betraege'] ?? [] as $b) {
        if (empty($b['bezeichnung'])) continue;
        $data['betraege'][] = [
            'bezeichnung'           => trim($b['bezeichnung']),
            'grundbetrag'           => dezimal($b['grundbetrag'] ?? 0),
            'betrag_pro_teilnehmer' => dezimal($b['betrag_pro_teilnehmer'] ?? 0),
            'betrag_pro_tag'        => dezimal($b['betrag_pro_tag'] ?? 0),
            'max_teilnehmer'        => (int)($b['max_teilnehmer'] ?? 0),
            'max_tage'              => (int)($b['max_tage'] ?? 0),
            'betrag_max_gesamt'     => dezimal($b['betrag_max_gesamt'] ?? 0),
        ];
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
            'multiplikator'=> dezimal($e['multiplikator'] ?? 1),
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

    if (empty($data['bezeichnung'])) $fehler[] = 'Bezeichnung ist pflicht.';
    if (empty($data['foerderstelle'])) $fehler[] = 'Förderstelle ist pflicht.';
    if (empty($data['betraege'])) $fehler[] = 'Mindestens eine Betragsregel erforderlich.';

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
}

if (isset($_GET['gespeichert'])) $erfolg = true;
$subv = $id ? Subvention::laden($id) : null;

// Alpine.js Startwert
$alpineBetraege     = htmlspecialchars(json_encode($subv['betraege']     ?? [['bezeichnung'=>'Standardbetrag','grundbetrag'=>0,'betrag_pro_teilnehmer'=>0,'betrag_pro_tag'=>0,'max_teilnehmer'=>0,'max_tage'=>0,'betrag_max_gesamt'=>0,'satz_mit_uebernachtung'=>0,'satz_ohne_uebernachtung'=>0,'betrag_pro_stunde'=>0,'max_stunden_pro_tag'=>0,'betrag_pro_einheit'=>0,'max_lektionen_pro_tag'=>0]]), ENT_QUOTES, 'UTF-8');
$alpineTrainerarten = htmlspecialchars(json_encode($subv['trainerarten'] ?? []), ENT_QUOTES, 'UTF-8');
$alpineEventarten   = htmlspecialchars(json_encode($subv['eventarten']   ?? []), ENT_QUOTES, 'UTF-8');
$alpineFristen      = htmlspecialchars(json_encode($subv['fristen']      ?? []), ENT_QUOTES, 'UTF-8');
$alpineHistorie     = htmlspecialchars(json_encode($subv['historie']     ?? []), ENT_QUOTES, 'UTF-8');

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <a href="/" class="text-sm text-gray-400 hover:text-blue-600">&larr; Zurück zur Übersicht</a>
  <h1 class="text-2xl font-semibold mt-2"><?= htmlspecialchars($pageTitle) ?></h1>
</div>

<?php if ($erfolg): ?>
<div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 mb-6">
  Subvention wurde gespeichert.
</div>
<?php endif; ?>

<?php if ($fehler): ?>
<div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 mb-6">
  <ul class="list-disc list-inside">
    <?php foreach ($fehler as $f): ?>
    <li><?= htmlspecialchars($f) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="post" action="/erfassen.php"
      x-data="{
        berechnungstyp: '<?= htmlspecialchars($subv['berechnungstyp'] ?? 'additiv') ?>',
        betraege:     <?= $alpineBetraege ?>,
        trainerarten: <?= $alpineTrainerarten ?>,
        eventarten:   <?= $alpineEventarten ?>,
        fristen:      <?= $alpineFristen ?>,
        historie:     <?= $alpineHistorie ?>,
        addBetrag()    { this.betraege.push({bezeichnung:'Neue Regel',grundbetrag:0,betrag_pro_teilnehmer:0,betrag_pro_tag:0,max_teilnehmer:0,max_tage:0,betrag_max_gesamt:0,satz_mit_uebernachtung:0,satz_ohne_uebernachtung:0,betrag_pro_stunde:0,max_stunden_pro_tag:0,betrag_pro_einheit:0,max_lektionen_pro_tag:0}) },
        rmBetrag(i)    { this.betraege.splice(i,1) },
        addTrainer(ta) {
          if(!this.trainerarten.find(t=>t.trainerart===ta))
            this.trainerarten.push({trainerart:ta,zusatzbetrag:0,bemerkung:''})
        },
        rmTrainer(i)   { this.trainerarten.splice(i,1) },
        addEvent(ea)   {
          if(!this.eventarten.find(e=>e.eventart===ea))
            this.eventarten.push({eventart:ea,multiplikator:1,bemerkung:''})
        },
        rmEvent(i)     { this.eventarten.splice(i,1) },
        addFrist()     { this.fristen.push({bezeichnung:'',datum:'',hinweis:''}) },
        rmFrist(i)     { this.fristen.splice(i,1) },
        addHistorie()  { this.historie.push({jahr:new Date().getFullYear(),betrag:0,bemerkung:''}) },
        rmHistorie(i)  { this.historie.splice(i,1) }
      }">

  <input type="hidden" name="id" value="<?= $subv['id'] ?? '' ?>">

  <!-- ── Stammdaten ─────────────────────────────────── -->
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <h2 class="font-semibold text-gray-700 mb-4">Stammdaten</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Bezeichnung *</label>
        <input type="text" name="bezeichnung" required
               value="<?= htmlspecialchars($subv['bezeichnung'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Förderstelle *</label>
        <input type="text" name="foerderstelle" required
               value="<?= htmlspecialchars($subv['foerderstelle'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
               placeholder="z.B. J+S / BASPO, Kanton ZH">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Kategorie</label>
        <select name="kategorie"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
          <?php foreach (Subvention::KATEGORIEN as $k => $label): ?>
          <option value="<?= $k ?>" <?= ($subv['kategorie'] ?? '') === $k ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Berechnungstyp</label>
        <select name="berechnungstyp" x-model="berechnungstyp"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
          <?php foreach (Subvention::BERECHNUNGSTYPEN as $k => $label): ?>
          <option value="<?= $k ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-400 mt-1">Bestimmt, welche Betragsfelder unten relevant sind und wie der Simulator rechnet.</p>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Beschreibung</label>
        <textarea name="beschreibung" rows="2"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"><?= htmlspecialchars($subv['beschreibung'] ?? '') ?></textarea>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Voraussetzungen</label>
        <textarea name="voraussetzungen" rows="3"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                  placeholder="z.B. Mindestalter, Mitgliedschaft, Anerkennungen..."><?= htmlspecialchars($subv['voraussetzungen'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Berechtigte</label>
        <textarea name="berechtigte" rows="2"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                  placeholder="Wer ist antrags-/teilnahmeberechtigt?"><?= htmlspecialchars($subv['berechtigte'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Einschränkungen / Vorgaben</label>
        <textarea name="einschraenkungen" rows="2"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                  placeholder="Einschränkungen der Förderstelle"><?= htmlspecialchars($subv['einschraenkungen'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Verlangte Unterlagen</label>
        <textarea name="verlangte_unterlagen" rows="2"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                  placeholder="z.B. Anwesenheitsliste, Trainerausweise"><?= htmlspecialchars($subv['verlangte_unterlagen'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Berechnungsgrundlage</label>
        <textarea name="berechnungsgrundlage" rows="2"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                  placeholder="Wie berechnet die Förderstelle den Beitrag?"><?= htmlspecialchars($subv['berechnungsgrundlage'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Gültig von</label>
        <input type="date" name="gueltig_von"
               value="<?= htmlspecialchars($subv['gueltig_von'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Gültig bis</label>
        <input type="date" name="gueltig_bis"
               value="<?= htmlspecialchars($subv['gueltig_bis'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Antragsfrist</label>
        <input type="date" name="antragsfrist"
               value="<?= htmlspecialchars($subv['antragsfrist'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Externer Link</label>
        <input type="url" name="link_extern"
               value="<?= htmlspecialchars($subv['link_extern'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
               placeholder="https://...">
      </div>

      <div class="flex items-center gap-2 mt-1">
        <input type="checkbox" name="aktiv" id="aktiv" value="1"
               <?= ($subv['aktiv'] ?? 1) ? 'checked' : '' ?>
               class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
        <label for="aktiv" class="text-sm font-medium">Subvention aktiv</label>
      </div>
    </div>
  </section>

  <!-- ── Betragsregeln ──────────────────────────────── -->
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold text-gray-700">Betragsregeln</h2>
      <button type="button" @click="addBetrag()"
              class="text-sm text-blue-600 hover:text-blue-800 border border-blue-300 rounded px-3 py-1">
        + Regel
      </button>
    </div>
    <p class="text-xs text-gray-400 mb-4" x-show="berechnungstyp === 'additiv'">
      Formel: Grundbetrag + (Betrag/TN × Anzahl TN) + (Betrag/Tag × Anzahl Tage) + Trainer-Zusatz × Eventart-Faktor → max. Gesamtbetrag
    </p>
    <p class="text-xs text-gray-400 mb-4" x-show="berechnungstyp === 'js_teilnehmertag'">
      Formel: Satz (je nach Übernachtung) × anrechenbare Teilnehmer × anrechenbare Tage → max. Gesamtbetrag
    </p>
    <p class="text-xs text-gray-400 mb-4" x-show="berechnungstyp === 'js_teilnehmerstunde'">
      Formel: Satz pro Teilnehmerstunde × anrechenbare Teilnehmer × Tage × Stunden/Tag → max. Gesamtbetrag
    </p>
    <p class="text-xs text-gray-400 mb-4" x-show="berechnungstyp === 'zks_ausbildungseinheit'">
      Formel: Satz pro Einheit × Ausbildungseinheiten (Teilnehmer × Lektionen/Tag × Tage) → max. Gesamtbetrag
    </p>
    <p class="text-xs text-gray-400 mb-4" x-show="berechnungstyp === 'pauschale'">
      Fixer Jahresbetrag – im Feld «Grundbetrag / Pauschalbetrag» hinterlegen.
    </p>
    <p class="text-xs text-gray-400 mb-4" x-show="berechnungstyp === 'jahresbeitrag'">
      Verbandsweite Kennzahl: Betrag pro Einheit × Anzahl Einheiten (Eingabe im Jahresbeitrags-Rechner). Ohne Satz gilt der Pauschalbetrag als Referenz.
    </p>

    <template x-for="(b, i) in betraege" :key="i">
      <div class="border border-gray-100 rounded-lg p-4 mb-3 bg-gray-50">
        <div class="flex justify-between items-center mb-3">
          <input type="text" :name="'betraege['+i+'][bezeichnung]'" x-model="b.bezeichnung"
                 placeholder="Bezeichnung der Regel"
                 class="text-sm font-medium border-0 bg-transparent focus:outline-none focus:ring-1 focus:ring-blue-300 rounded px-1 w-64">
          <button type="button" @click="rmBetrag(i)" class="text-red-400 hover:text-red-600 text-xs">
            Entfernen
          </button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
          <div x-show="berechnungstyp === 'additiv' || berechnungstyp === 'pauschale' || berechnungstyp === 'jahresbeitrag'">
            <label class="block text-xs text-gray-500 mb-1" x-text="berechnungstyp === 'additiv' ? 'Grundbetrag (CHF)' : 'Grundbetrag / Pauschalbetrag (CHF)'">Grundbetrag (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][grundbetrag]'" x-model="b.grundbetrag"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div x-show="berechnungstyp === 'additiv'">
            <label class="block text-xs text-gray-500 mb-1">Betrag / Teilnehmer (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_teilnehmer]'" x-model="b.betrag_pro_teilnehmer"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div x-show="berechnungstyp === 'additiv'">
            <label class="block text-xs text-gray-500 mb-1">Betrag / Tag (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_tag]'" x-model="b.betrag_pro_tag"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>

          <!-- js_teilnehmertag -->
          <div x-show="berechnungstyp === 'js_teilnehmertag'">
            <label class="block text-xs text-gray-500 mb-1">Satz mit Übernachtung (CHF/TN/Tag)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][satz_mit_uebernachtung]'" x-model="b.satz_mit_uebernachtung"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div x-show="berechnungstyp === 'js_teilnehmertag'">
            <label class="block text-xs text-gray-500 mb-1">Satz ohne Übernachtung (CHF/TN/Tag)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][satz_ohne_uebernachtung]'" x-model="b.satz_ohne_uebernachtung"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>

          <!-- js_teilnehmerstunde -->
          <div x-show="berechnungstyp === 'js_teilnehmerstunde'">
            <label class="block text-xs text-gray-500 mb-1">Betrag / Teilnehmerstunde (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_stunde]'" x-model="b.betrag_pro_stunde"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div x-show="berechnungstyp === 'js_teilnehmerstunde'">
            <label class="block text-xs text-gray-500 mb-1">Max. Stunden / Tag (0 = unbegrenzt)</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_stunden_pro_tag]'" x-model="b.max_stunden_pro_tag"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>

          <!-- zks_ausbildungseinheit / jahresbeitrag -->
          <div x-show="berechnungstyp === 'zks_ausbildungseinheit' || berechnungstyp === 'jahresbeitrag'">
            <label class="block text-xs text-gray-500 mb-1" x-text="berechnungstyp === 'jahresbeitrag' ? 'Betrag / Einheit (CHF)' : 'Satz / Ausbildungseinheit (CHF)'">Satz / Ausbildungseinheit (CHF)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_pro_einheit]'" x-model="b.betrag_pro_einheit"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div x-show="berechnungstyp === 'zks_ausbildungseinheit'">
            <label class="block text-xs text-gray-500 mb-1">Max. Lektionen / Tag (0 = unbegrenzt)</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_lektionen_pro_tag]'" x-model="b.max_lektionen_pro_tag"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>

          <div x-show="berechnungstyp === 'additiv' || berechnungstyp === 'js_teilnehmertag' || berechnungstyp === 'js_teilnehmerstunde' || berechnungstyp === 'zks_ausbildungseinheit'">
            <label class="block text-xs text-gray-500 mb-1">Max. Teilnehmer (0 = unbegrenzt)</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_teilnehmer]'" x-model="b.max_teilnehmer"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div x-show="berechnungstyp === 'additiv' || berechnungstyp === 'js_teilnehmertag' || berechnungstyp === 'js_teilnehmerstunde' || berechnungstyp === 'zks_ausbildungseinheit'">
            <label class="block text-xs text-gray-500 mb-1">Max. Tage (0 = unbegrenzt)</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_tage]'" x-model="b.max_tage"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Max. Gesamtbetrag (0 = kein Limit)</label>
            <input type="text" inputmode="decimal" :name="'betraege['+i+'][betrag_max_gesamt]'" x-model="b.betrag_max_gesamt"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
        </div>
      </div>
    </template>
  </section>

  <!-- ── Trainerarten ───────────────────────────────── -->
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <h2 class="font-semibold text-gray-700 mb-2">Erlaubte Trainerarten</h2>
    <p class="text-xs text-gray-400 mb-4">Nur Trainings mit den gewählten Trainerarten sind förderberechtigt.</p>

    <div class="flex gap-2 mb-4 flex-wrap">
      <?php foreach (Subvention::TRAINERARTEN as $key => $label): ?>
      <button type="button" @click="addTrainer('<?= $key ?>')"
              class="text-sm border border-blue-300 text-blue-600 hover:bg-blue-50 rounded px-3 py-1">
        + <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>

    <template x-for="(t, i) in trainerarten" :key="i">
      <div class="border border-gray-100 rounded-lg p-3 mb-2 bg-gray-50 flex flex-wrap gap-3 items-end">
        <input type="hidden" :name="'trainerarten['+i+'][trainerart]'" x-model="t.trainerart">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Trainerart</label>
          <span class="text-sm font-medium" x-text="
            t.trainerart === 'js_trainer'  ? 'J+S Trainer'  :
            t.trainerart === 'nwf_trainer' ? 'NWF Trainer'  : 'Ohne Anerkennung'
          "></span>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Zusatzbetrag (CHF)</label>
          <input type="text" inputmode="decimal" :name="'trainerarten['+i+'][zusatzbetrag]'" x-model="t.zusatzbetrag"
                 class="w-32 border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>
        <div class="flex-1">
          <label class="block text-xs text-gray-500 mb-1">Bemerkung</label>
          <input type="text" :name="'trainerarten['+i+'][bemerkung]'" x-model="t.bemerkung"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm"
                 placeholder="Optional">
        </div>
        <button type="button" @click="rmTrainer(i)"
                class="text-red-400 hover:text-red-600 text-xs pb-2">Entfernen</button>
      </div>
    </template>
    <p x-show="trainerarten.length === 0" class="text-sm text-gray-400">Noch keine Trainerart hinzugefügt.</p>
  </section>

  <!-- ── Eventarten ─────────────────────────────────── -->
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <h2 class="font-semibold text-gray-700 mb-2">Erlaubte Eventarten</h2>
    <p class="text-xs text-gray-400 mb-4">Multiplikator wird auf den berechneten Betrag angewendet (1.0 = keine Änderung).</p>

    <div class="flex gap-2 mb-4">
      <?php foreach (Subvention::EVENTARTEN as $key => $label): ?>
      <button type="button" @click="addEvent('<?= $key ?>')"
              class="text-sm border border-blue-300 text-blue-600 hover:bg-blue-50 rounded px-3 py-1">
        + <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>

    <template x-for="(e, i) in eventarten" :key="i">
      <div class="border border-gray-100 rounded-lg p-3 mb-2 bg-gray-50 flex flex-wrap gap-3 items-end">
        <input type="hidden" :name="'eventarten['+i+'][eventart]'" x-model="e.eventart">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Eventart</label>
          <span class="text-sm font-medium" x-text="e.eventart === 'lager' ? 'Lager' : 'Training'"></span>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Multiplikator</label>
          <input type="text" inputmode="decimal" :name="'eventarten['+i+'][multiplikator]'" x-model="e.multiplikator"
                 class="w-24 border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>
        <div class="flex-1">
          <label class="block text-xs text-gray-500 mb-1">Bemerkung</label>
          <input type="text" :name="'eventarten['+i+'][bemerkung]'" x-model="e.bemerkung"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm"
                 placeholder="Optional">
        </div>
        <button type="button" @click="rmEvent(i)"
                class="text-red-400 hover:text-red-600 text-xs pb-2">Entfernen</button>
      </div>
    </template>
    <p x-show="eventarten.length === 0" class="text-sm text-gray-400">Noch keine Eventart hinzugefügt.</p>
  </section>

  <!-- ── Fristen & Termine ──────────────────────────── -->
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold text-gray-700">Fristen &amp; Termine</h2>
      <button type="button" @click="addFrist()"
              class="text-sm text-blue-600 hover:text-blue-800 border border-blue-300 rounded px-3 py-1">
        + Frist
      </button>
    </div>
    <p class="text-xs text-gray-400 mb-4">Mehrere Termine möglich (z.B. Anmeldung 30.4., Rapport 31.10.). Nur als Hinweis – ohne Einfluss auf die Berechnung.</p>

    <template x-for="(f, i) in fristen" :key="i">
      <div class="border border-gray-100 rounded-lg p-3 mb-2 bg-gray-50 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Bezeichnung</label>
          <input type="text" :name="'fristen['+i+'][bezeichnung]'" x-model="f.bezeichnung"
                 placeholder="z.B. Anmeldung"
                 class="w-40 border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Datum</label>
          <input type="date" :name="'fristen['+i+'][datum]'" x-model="f.datum"
                 class="border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>
        <div class="flex-1">
          <label class="block text-xs text-gray-500 mb-1">Hinweis</label>
          <input type="text" :name="'fristen['+i+'][hinweis]'" x-model="f.hinweis"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm"
                 placeholder="Optional, z.B. via ZKS-Extranet">
        </div>
        <button type="button" @click="rmFrist(i)"
                class="text-red-400 hover:text-red-600 text-xs pb-2">Entfernen</button>
      </div>
    </template>
    <p x-show="fristen.length === 0" class="text-sm text-gray-400">Noch keine Frist erfasst.</p>
  </section>

  <!-- ── Betragshistorie ────────────────────────────── -->
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold text-gray-700">Betragshistorie</h2>
      <button type="button" @click="addHistorie()"
              class="text-sm text-blue-600 hover:text-blue-800 border border-blue-300 rounded px-3 py-1">
        + Jahr
      </button>
    </div>
    <p class="text-xs text-gray-400 mb-4">Tatsächlich erhaltene Beträge pro Jahr (Referenz und Basis für Pauschal-Beiträge).</p>

    <template x-for="(h, i) in historie" :key="i">
      <div class="border border-gray-100 rounded-lg p-3 mb-2 bg-gray-50 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Jahr</label>
          <input type="number" step="1" min="2000" max="2100" :name="'historie['+i+'][jahr]'" x-model="h.jahr"
                 class="w-24 border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Betrag (CHF)</label>
          <input type="text" inputmode="decimal" :name="'historie['+i+'][betrag]'" x-model="h.betrag"
                 class="w-32 border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>
        <div class="flex-1">
          <label class="block text-xs text-gray-500 mb-1">Bemerkung</label>
          <input type="text" :name="'historie['+i+'][bemerkung]'" x-model="h.bemerkung"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm"
                 placeholder="Optional">
        </div>
        <button type="button" @click="rmHistorie(i)"
                class="text-red-400 hover:text-red-600 text-xs pb-2">Entfernen</button>
      </div>
    </template>
    <p x-show="historie.length === 0" class="text-sm text-gray-400">Noch keine Beträge erfasst.</p>
  </section>

  <!-- ── Speichern ──────────────────────────────────── -->
  <div class="flex justify-end gap-3">
    <a href="/" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Abbrechen</a>
    <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-6 py-2 rounded-lg">
      Speichern
    </button>
  </div>

</form>

<?php require __DIR__ . '/partials/footer.php'; ?>
