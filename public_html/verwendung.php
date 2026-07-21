<?php
require_once __DIR__ . '/../includes/Subvention.php';
require_once __DIR__ . '/../includes/Event.php';
require_once __DIR__ . '/../includes/auth.php';

// Login vor der POST-Verarbeitung erzwingen (Schreibvorgang, Audit-Spalte).
auth_erforderlich();

function dezimal($wert): float {
    return (float) str_replace([',', "'"], ['.', ''], (string)$wert);
}

$pageTitle = 'Beiträge & Verwendung';

$subventionen = Subvention::alle(false);
$events       = Event::alle();
$klassen      = db()->query('SELECT id, bezeichnung FROM cm_klassen ORDER BY bezeichnung')->fetchAll();

// Auswahl: Subvention + Jahr
$subventionId = (int)($_POST['subvention_id'] ?? $_GET['subvention_id'] ?? ($subventionen[0]['id'] ?? 0));
$jahr         = (int)($_POST['jahr'] ?? $_GET['jahr'] ?? date('Y'));

$fehler = [];
$erfolg = isset($_GET['gespeichert']);
$betragErfolg = isset($_GET['betrag_gespeichert']);

// Erhaltenen Jahresbetrag direkt hier setzen (Upsert in die Betragshistorie),
// damit der Umweg über die Erfassungsmaske entfällt.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['betrag_setzen'])) {
    if ($subventionId <= 0) {
        $fehler[] = 'Bitte ein Förderprogramm wählen.';
    } else {
        try {
            Subvention::historieSetzen($subventionId, $jahr, dezimal($_POST['erhalten_betrag'] ?? 0));
            header('Location: /verwendung.php?subvention_id=' . $subventionId . '&jahr=' . $jahr . '&betrag_gespeichert=1');
            exit;
        } catch (Throwable $e) {
            error_log('[Subventionssimulator] historieSetzen() Fehler: ' . $e->getMessage());
            $fehler[] = 'Beim Speichern des Jahresbetrags ist ein Fehler aufgetreten. Details im Server-Log.';
        }
    }
}

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
        $fehler[] = 'Bitte ein Förderprogramm wählen.';
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
  <h1 class="text-2xl font-semibold">Beiträge &amp; Verwendung</h1>
  <p class="text-sm text-muted mt-1">Pro Förderprogramm und Jahr: Wie viel wurde erhalten – und wohin wurde es verteilt (Lager, Trainings, Kaderklassen, Reserve)?</p>
</div>

<?php if ($erfolg): ?>
<div class="alert alert--success mb-6">Verwendung gespeichert.</div>
<?php endif; ?>
<?php if ($betragErfolg): ?>
<div class="alert alert--success mb-6">Jahresbetrag gespeichert.</div>
<?php endif; ?>
<?php if ($fehler): ?>
<div class="alert alert--error mb-6">
  <ul class="list-disc list-inside"><?php foreach ($fehler as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- Auswahl Subvention + Jahr (GET) -->
<form method="get" action="/verwendung.php" class="card mb-6">
  <div class="flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-[240px]">
      <label class="block text-sm font-medium mb-1">Förderprogramm</label>
      <select name="subvention_id" onchange="this.form.submit()"
              class="input">
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
             class="input w-28">
    </div>
    <noscript><button type="submit" class="btn btn--secondary">Anzeigen</button></noscript>
  </div>
</form>

<?php if ($subv): ?>

<!-- Erhaltenen Jahresbetrag direkt erfassen (schreibt in die Betragshistorie) -->
<form method="post" action="/verwendung.php" class="card mb-6">
  <input type="hidden" name="subvention_id" value="<?= (int)$subventionId ?>">
  <input type="hidden" name="jahr" value="<?= (int)$jahr ?>">
  <input type="hidden" name="betrag_setzen" value="1">
  <div class="flex flex-wrap items-end gap-4">
    <div>
      <label class="block text-sm font-medium mb-1">Erhaltener / erwarteter Betrag <?= (int)$jahr ?> (CHF)</label>
      <input type="text" inputmode="decimal" name="erhalten_betrag"
             value="<?= $erhalten != 0 ? number_format($erhalten, 2, '.', '') : '' ?>"
             placeholder="z.B. 30000.00"
             class="input w-44">
    </div>
    <button type="submit" class="btn btn--primary btn--sm">
      Jahresbetrag speichern
    </button>
    <p class="text-xs text-subtle pb-2">Was hat die Förderstelle für <?= (int)$jahr ?> ausbezahlt oder in Aussicht gestellt? Dieser Betrag wird unten verteilt.</p>
  </div>
</form>

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

  <section class="card mb-6">
    <div class="page-header mb-4">
      <div>
        <h2 class="font-semibold"><?= htmlspecialchars($subv['bezeichnung']) ?> · <?= (int)$jahr ?></h2>
        <p class="text-sm text-muted"><?= htmlspecialchars($subv['foerderstelle']) ?></p>
      </div>
      <button type="button" @click="addRow()"
              class="btn btn--secondary btn--sm">+ Zuweisung</button>
    </div>

    <!-- Summenkontrolle -->
    <div class="card card--muted flex flex-wrap gap-6 items-center px-4 py-3 mb-4 text-sm">
      <div>
        <span class="text-subtle">Erhaltener Betrag (<?= (int)$jahr ?>):</span>
        <span class="font-semibold">CHF <?= number_format($erhalten, 2, '.', "'") ?></span>
        <?php if ($erhalten == 0): ?><span class="text-xs text-subtle">(oben erfassen)</span><?php endif; ?>
      </div>
      <div>
        <span class="text-subtle">Verteilt:</span>
        <span class="font-semibold font-mono" x-text="'CHF ' + verteilt.toFixed(2)"></span>
      </div>
      <div>
        <span class="text-subtle">Rest:</span>
        <span class="font-semibold font-mono"
              :class="Math.abs(rest) < 0.005 ? 'text-success' : (rest < 0 ? 'text-error' : 'text-warning')"
              x-text="'CHF ' + rest.toFixed(2)"></span>
      </div>
    </div>

    <template x-for="(r, i) in rows" :key="i">
      <div class="card card--muted p-3 mb-2 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <div class="md:col-span-2">
          <label class="block text-xs text-muted mb-1">Ziel-Typ</label>
          <select :name="'verwendung['+i+'][ziel_typ]'" x-model="r.ziel_typ"
                  class="input input--sm">
            <option value="event">Event</option>
            <option value="klasse">Kaderklasse</option>
            <option value="reserve">Reserve</option>
            <option value="frei">Frei</option>
          </select>
        </div>

        <div class="md:col-span-4">
          <label class="block text-xs text-muted mb-1">Ziel</label>
          <select x-show="r.ziel_typ === 'event'" :name="'verwendung['+i+'][ziel_event_id]'" x-model="r.ziel_event_id"
                  class="input input--sm">
            <option value="">– Event wählen –</option>
            <?php foreach ($events as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['bezeichnung']) ?><?= $e['klasse_bezeichnung'] ? ' (' . htmlspecialchars($e['klasse_bezeichnung']) . ')' : '' ?></option>
            <?php endforeach; ?>
          </select>
          <select x-show="r.ziel_typ === 'klasse'" :name="'verwendung['+i+'][ziel_klasse_id]'" x-model="r.ziel_klasse_id"
                  class="input input--sm">
            <option value="">– Klasse wählen –</option>
            <?php foreach ($klassen as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['bezeichnung']) ?></option>
            <?php endforeach; ?>
          </select>
          <input x-show="r.ziel_typ === 'reserve' || r.ziel_typ === 'frei'" type="text"
                 :name="'verwendung['+i+'][ziel_text]'" x-model="r.ziel_text"
                 placeholder="Bezeichnung (z.B. Reserve Breitensport)"
                 class="input input--sm">
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs text-muted mb-1">Betrag (CHF)</label>
          <input type="text" inputmode="decimal" :name="'verwendung['+i+'][betrag]'" x-model="r.betrag"
                 class="input input--sm">
        </div>
        <div class="md:col-span-3">
          <label class="block text-xs text-muted mb-1">Bemerkung</label>
          <input type="text" :name="'verwendung['+i+'][bemerkung]'" x-model="r.bemerkung"
                 class="input input--sm" placeholder="Optional">
        </div>
        <div class="md:col-span-1 flex md:justify-end">
          <button type="button" @click="rmRow(i)" class="link--danger text-xs pb-2">Entfernen</button>
        </div>
      </div>
    </template>
    <p x-show="rows.length === 0" class="text-sm text-subtle">Noch keine Zuweisung erfasst.</p>

    <div class="flex justify-end mt-4">
      <button type="submit" class="btn btn--primary">Speichern</button>
    </div>
  </section>
</form>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
