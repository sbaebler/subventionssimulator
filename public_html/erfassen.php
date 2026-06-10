<?php
require_once __DIR__ . '/../includes/Subvention.php';

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
        'kategorie'       => $_POST['kategorie'] ?? 'sonstiges',
        'voraussetzungen' => trim($_POST['voraussetzungen'] ?? ''),
        'antragsfrist'    => $_POST['antragsfrist'] ?: null,
        'gueltig_von'     => $_POST['gueltig_von']  ?: null,
        'gueltig_bis'     => $_POST['gueltig_bis']  ?: null,
        'link_extern'     => trim($_POST['link_extern'] ?? '') ?: null,
        'aktiv'           => isset($_POST['aktiv']) ? 1 : 0,
        'betraege'        => [],
        'trainerarten'    => [],
        'eventarten'      => [],
    ];

    // Betraege aus POST
    foreach ($_POST['betraege'] ?? [] as $b) {
        if (empty($b['bezeichnung'])) continue;
        $data['betraege'][] = [
            'bezeichnung'           => trim($b['bezeichnung']),
            'grundbetrag'           => (float)($b['grundbetrag'] ?? 0),
            'betrag_pro_teilnehmer' => (float)($b['betrag_pro_teilnehmer'] ?? 0),
            'betrag_pro_tag'        => (float)($b['betrag_pro_tag'] ?? 0),
            'max_teilnehmer'        => (int)($b['max_teilnehmer'] ?? 0),
            'max_tage'              => (int)($b['max_tage'] ?? 0),
            'betrag_max_gesamt'     => (float)($b['betrag_max_gesamt'] ?? 0),
        ];
    }

    // Trainerarten aus POST
    foreach ($_POST['trainerarten'] ?? [] as $t) {
        if (empty($t['trainerart'])) continue;
        $data['trainerarten'][] = [
            'trainerart'   => $t['trainerart'],
            'zusatzbetrag' => (float)($t['zusatzbetrag'] ?? 0),
            'bemerkung'    => trim($t['bemerkung'] ?? '') ?: null,
        ];
    }

    // Eventarten aus POST
    foreach ($_POST['eventarten'] ?? [] as $e) {
        if (empty($e['eventart'])) continue;
        $data['eventarten'][] = [
            'eventart'     => $e['eventart'],
            'multiplikator'=> (float)($e['multiplikator'] ?? 1),
            'bemerkung'    => trim($e['bemerkung'] ?? '') ?: null,
        ];
    }

    if (empty($data['bezeichnung'])) $fehler[] = 'Bezeichnung ist pflicht.';
    if (empty($data['foerderstelle'])) $fehler[] = 'Förderstelle ist pflicht.';
    if (empty($data['betraege'])) $fehler[] = 'Mindestens eine Betragsregel erforderlich.';

    if (empty($fehler)) {
        $newId = Subvention::speichern($data);
        header('Location: /erfassen.php?id=' . $newId . '&gespeichert=1');
        exit;
    }
}

if (isset($_GET['gespeichert'])) $erfolg = true;
$subv = $id ? Subvention::laden($id) : null;

// Alpine.js Startwert
$alpineBetraege     = json_encode($subv['betraege']     ?? [['bezeichnung'=>'Standardbetrag','grundbetrag'=>0,'betrag_pro_teilnehmer'=>0,'betrag_pro_tag'=>0,'max_teilnehmer'=>0,'max_tage'=>0,'betrag_max_gesamt'=>0]]);
$alpineTrainerarten = json_encode($subv['trainerarten'] ?? []);
$alpineEventarten   = json_encode($subv['eventarten']   ?? []);

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
        betraege:     <?= $alpineBetraege ?>,
        trainerarten: <?= $alpineTrainerarten ?>,
        eventarten:   <?= $alpineEventarten ?>,
        addBetrag()    { this.betraege.push({bezeichnung:'Neue Regel',grundbetrag:0,betrag_pro_teilnehmer:0,betrag_pro_tag:0,max_teilnehmer:0,max_tage:0,betrag_max_gesamt:0}) },
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
        rmEvent(i)     { this.eventarten.splice(i,1) }
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
    <p class="text-xs text-gray-400 mb-4">
      Formel: Grundbetrag + (Betrag/TN × Anzahl TN) + (Betrag/Tag × Anzahl Tage) + Trainer-Zusatz × Eventart-Faktor → max. Gesamtbetrag
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
          <div>
            <label class="block text-xs text-gray-500 mb-1">Grundbetrag (CHF)</label>
            <input type="number" step="0.01" min="0" :name="'betraege['+i+'][grundbetrag]'" x-model="b.grundbetrag"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Betrag / Teilnehmer (CHF)</label>
            <input type="number" step="0.01" min="0" :name="'betraege['+i+'][betrag_pro_teilnehmer]'" x-model="b.betrag_pro_teilnehmer"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Betrag / Tag (CHF)</label>
            <input type="number" step="0.01" min="0" :name="'betraege['+i+'][betrag_pro_tag]'" x-model="b.betrag_pro_tag"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Max. Teilnehmer (0 = unbegrenzt)</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_teilnehmer]'" x-model="b.max_teilnehmer"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Max. Tage (0 = unbegrenzt)</label>
            <input type="number" step="1" min="0" :name="'betraege['+i+'][max_tage]'" x-model="b.max_tage"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Max. Gesamtbetrag (0 = kein Limit)</label>
            <input type="number" step="0.01" min="0" :name="'betraege['+i+'][betrag_max_gesamt]'" x-model="b.betrag_max_gesamt"
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
          <input type="number" step="0.01" min="0" :name="'trainerarten['+i+'][zusatzbetrag]'" x-model="t.zusatzbetrag"
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
          <input type="number" step="0.001" min="0.001" :name="'eventarten['+i+'][multiplikator]'" x-model="e.multiplikator"
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
