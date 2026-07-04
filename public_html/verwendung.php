<?php
require_once __DIR__ . '/../includes/Subvention.php';
require_once __DIR__ . '/../includes/Event.php';
require_once __DIR__ . '/../includes/auth.php';

// Login vor der POST-Verarbeitung erzwingen (Schreibvorgang, Audit-Spalte).
auth_erforderlich();

function dezimal($wert): float {
    return (float) str_replace([',', "'"], ['.', ''], (string)$wert);
}

$pageTitle = 'Verwendung';

$subventionen = Subvention::alle(false);
$events       = Event::alle();
$klassen      = db()->query('SELECT id, bezeichnung FROM cm_klassen ORDER BY bezeichnung')->fetchAll();

// Auswahl: Subvention + Jahr
$subventionId = (int)($_POST['subvention_id'] ?? $_GET['subvention_id'] ?? ($subventionen[0]['id'] ?? 0));
$jahr         = (int)($_POST['jahr'] ?? $_GET['jahr'] ?? date('Y'));

$fehler = [];
$erfolg = isset($_GET['gespeichert']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['speichern'])) {
    $rows = [];
    foreach ($_POST['verwendung'] ?? [] as $v) {
        $rows[] = [
            'ziel_typ'       => $v['ziel_typ'] ?? 'frei',
            'ziel_event_id'  => $v['ziel_event_id']  ?? null,
            'ziel_klasse_id' => $v['ziel_klasse_id'] ?? null,
            'ziel_text'      => $v['ziel_text'] ?? '',
            'betrag'         => dezimal($v['betrag'] ?? 0),
            'bemerkung'      => $v['bemerkung'] ?? '',
        ];
    }
    if ($subventionId <= 0) {
        $fehler[] = 'Bitte eine Subvention wählen.';
    } else {
        try {
            Subvention::verwendungSpeichern($subventionId, $jahr, $rows, auth_benutzer_id() ?: null);
            header('Location: /verwendung.php?subvention_id=' . $subventionId . '&jahr=' . $jahr . '&gespeichert=1');
            exit;
        } catch (Throwable $e) {
            error_log('[Subventionssimulator] verwendungSpeichern() Fehler: ' . $e->getMessage());
            $fehler[] = 'Beim Speichern ist ein Fehler aufgetreten. Details im Server-Log.';
        }
    }
}

$subv        = $subventionId ? Subvention::laden($subventionId) : null;
$verwendung  = $subv ? Subvention::verwendung($subventionId, $jahr) : [];

// Erhaltener Betrag als Referenz: hinterlegte Betragshistorie für das Jahr
$erhalten = 0.0;
foreach ($subv['historie'] ?? [] as $h) {
    if ((int)$h['jahr'] === $jahr) { $erhalten = (float)$h['betrag']; break; }
}

// Alpine-Startwert der Verwendungszeilen
$alpineRows = htmlspecialchars(json_encode(array_map(fn($v) => [
    'ziel_typ'       => $v['ziel_typ'],
    'ziel_event_id'  => $v['ziel_event_id'],
    'ziel_klasse_id' => $v['ziel_klasse_id'],
    'ziel_text'      => $v['ziel_text'],
    'betrag'         => $v['betrag'],
    'bemerkung'      => $v['bemerkung'],
], $verwendung)), ENT_QUOTES, 'UTF-8');

require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-semibold">Verwendung erhaltener Beiträge</h1>
  <p class="text-sm text-gray-500 mt-1">Festhalten, wohin ein Subventionsbeitrag pro Jahr verteilt wird (Lager, Trainings, Kaderklassen, Reserve)</p>
</div>

<?php if ($erfolg): ?>
<div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 mb-6">Verwendung gespeichert.</div>
<?php endif; ?>
<?php if ($fehler): ?>
<div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 mb-6">
  <ul class="list-disc list-inside"><?php foreach ($fehler as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- Auswahl Subvention + Jahr (GET) -->
<form method="get" action="/verwendung.php" class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
  <div class="flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-[240px]">
      <label class="block text-sm font-medium mb-1">Subvention</label>
      <select name="subvention_id" onchange="this.form.submit()"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
        <?php foreach ($subventionen as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $s['id'] == $subventionId ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['bezeichnung']) ?> (<?= htmlspecialchars($s['foerderstelle']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Jahr</label>
      <input type="number" name="jahr" min="2000" max="2100" value="<?= (int)$jahr ?>" onchange="this.form.submit()"
             class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
    </div>
    <noscript><button type="submit" class="bg-gray-200 text-sm px-4 py-2 rounded-lg">Anzeigen</button></noscript>
  </div>
</form>

<?php if ($subv): ?>
<form method="post" action="/verwendung.php"
      x-data="{
        erhalten: <?= json_encode($erhalten) ?>,
        rows: <?= $alpineRows ?>,
        addRow() { this.rows.push({ziel_typ:'frei',ziel_event_id:'',ziel_klasse_id:'',ziel_text:'',betrag:0,bemerkung:''}) },
        rmRow(i) { this.rows.splice(i,1) },
        get verteilt() { return this.rows.reduce((s,r)=> s + (parseFloat(String(r.betrag).replace(',', '.'))||0), 0) },
        get rest() { return this.erhalten - this.verteilt }
      }">
  <input type="hidden" name="subvention_id" value="<?= (int)$subventionId ?>">
  <input type="hidden" name="jahr" value="<?= (int)$jahr ?>">
  <input type="hidden" name="speichern" value="1">

  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="font-semibold text-gray-700"><?= htmlspecialchars($subv['bezeichnung']) ?> · <?= (int)$jahr ?></h2>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($subv['foerderstelle']) ?></p>
      </div>
      <button type="button" @click="addRow()"
              class="text-sm text-blue-600 hover:text-blue-800 border border-blue-300 rounded px-3 py-1">+ Zuweisung</button>
    </div>

    <!-- Summenkontrolle -->
    <div class="flex flex-wrap gap-6 items-center bg-gray-50 border border-gray-100 rounded-lg px-4 py-3 mb-4 text-sm">
      <div>
        <span class="text-gray-400">Erhaltener Betrag (<?= (int)$jahr ?>):</span>
        <span class="font-semibold">CHF <?= number_format($erhalten, 2, '.', "'") ?></span>
        <?php if ($erhalten == 0): ?><span class="text-xs text-gray-400">(in Betragshistorie hinterlegen)</span><?php endif; ?>
      </div>
      <div>
        <span class="text-gray-400">Verteilt:</span>
        <span class="font-semibold font-mono" x-text="'CHF ' + verteilt.toFixed(2)"></span>
      </div>
      <div>
        <span class="text-gray-400">Rest:</span>
        <span class="font-semibold font-mono"
              :class="Math.abs(rest) < 0.005 ? 'text-green-700' : (rest < 0 ? 'text-red-600' : 'text-amber-600')"
              x-text="'CHF ' + rest.toFixed(2)"></span>
      </div>
    </div>

    <template x-for="(r, i) in rows" :key="i">
      <div class="border border-gray-100 rounded-lg p-3 mb-2 bg-gray-50 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <div class="md:col-span-2">
          <label class="block text-xs text-gray-500 mb-1">Ziel-Typ</label>
          <select :name="'verwendung['+i+'][ziel_typ]'" x-model="r.ziel_typ"
                  class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
            <option value="event">Event</option>
            <option value="klasse">Kaderklasse</option>
            <option value="reserve">Reserve</option>
            <option value="frei">Frei</option>
          </select>
        </div>

        <div class="md:col-span-4">
          <label class="block text-xs text-gray-500 mb-1">Ziel</label>
          <select x-show="r.ziel_typ === 'event'" :name="'verwendung['+i+'][ziel_event_id]'" x-model="r.ziel_event_id"
                  class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
            <option value="">– Event wählen –</option>
            <?php foreach ($events as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['bezeichnung']) ?><?= $e['klasse_bezeichnung'] ? ' (' . htmlspecialchars($e['klasse_bezeichnung']) . ')' : '' ?></option>
            <?php endforeach; ?>
          </select>
          <select x-show="r.ziel_typ === 'klasse'" :name="'verwendung['+i+'][ziel_klasse_id]'" x-model="r.ziel_klasse_id"
                  class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
            <option value="">– Klasse wählen –</option>
            <?php foreach ($klassen as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['bezeichnung']) ?></option>
            <?php endforeach; ?>
          </select>
          <input x-show="r.ziel_typ === 'reserve' || r.ziel_typ === 'frei'" type="text"
                 :name="'verwendung['+i+'][ziel_text]'" x-model="r.ziel_text"
                 placeholder="Bezeichnung (z.B. Reserve Breitensport)"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs text-gray-500 mb-1">Betrag (CHF)</label>
          <input type="text" inputmode="decimal" :name="'verwendung['+i+'][betrag]'" x-model="r.betrag"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
        </div>
        <div class="md:col-span-3">
          <label class="block text-xs text-gray-500 mb-1">Bemerkung</label>
          <input type="text" :name="'verwendung['+i+'][bemerkung]'" x-model="r.bemerkung"
                 class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" placeholder="Optional">
        </div>
        <div class="md:col-span-1 flex md:justify-end">
          <button type="button" @click="rmRow(i)" class="text-red-400 hover:text-red-600 text-xs pb-2">Entfernen</button>
        </div>
      </div>
    </template>
    <p x-show="rows.length === 0" class="text-sm text-gray-400">Noch keine Zuweisung erfasst.</p>

    <div class="flex justify-end mt-4">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-6 py-2 rounded-lg">Speichern</button>
    </div>
  </section>
</form>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
