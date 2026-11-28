<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/SwissSailingRapport.php';
require_once __DIR__ . '/../includes/SwissSailingDemo.php';

auth_erforderlich();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$quelle = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$jahr = (int)($quelle['jahr'] ?? date('Y'));
if ($jahr < 2000 || $jahr > 2100) $jahr = (int)date('Y');
$maxAlter = max(5, min(99, (int)($quelle['max_alter'] ?? SwissSailingRapport::MAX_ALTER)));
$gewaehlt = array_values(array_unique(array_map('intval', (array)($quelle['event'] ?? []))));
// Demo: fiktive Daten, nichts wird gelesen oder gespeichert.
$demo = (string)($quelle['demo'] ?? '');
if (!isset(SwissSailingDemo::SZENARIEN[$demo])) $demo = '';

$fehler = null;
$events = [];
$auswahl = [];

try {
    $events = $demo ? SwissSailingDemo::events($demo, $jahr) : SwissSailingRapport::events($jahr);
    // In der Demo ohne passende Auswahl gleich alle Lager zeigen.
    if ($demo && !array_intersect($gewaehlt, array_column($events, 'id'))) {
        $gewaehlt = array_column($events, 'id');
    }
    // Nur Events aus der eigenen Liste – fremde IDs fallen weg.
    $auswahl = array_values(array_filter($events, fn($e) => in_array((int)$e['id'], $gewaehlt, true)));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('csrf');
        }
        $eingaben = (array)($_POST['athlet'] ?? []);
        if ($demo) {
            // Eingaben gelten nur für diesen Aufruf.
            foreach ($auswahl as &$e) {
                foreach (SwissSailingRapport::athleten($e) as $a) {
                    if (is_array($eingaben[$a['schluessel']] ?? null)) {
                        $e['zusatz'][$a['schluessel']] = SwissSailingRapport::normalisieren($eingaben[$a['schluessel']]);
                    }
                }
            }
            unset($e);
        } else {
            // Nur Personen der ausgewählten Events dürfen geschrieben werden.
            $erlaubt = [];
            foreach ($auswahl as $e) {
                foreach (SwissSailingRapport::athleten($e) as $a) $erlaubt[$a['schluessel']] = true;
            }
            SwissSailingRapport::speichern(array_intersect_key($eingaben, $erlaubt), auth_benutzer_id() ?: null);
        }

        if (($_POST['aktion'] ?? '') === 'export' && $auswahl) {
            $kopf = [];
            foreach (['organisator', 'verantwortlich', 'kontakt'] as $feld) {
                $kopf[$feld] = mb_substr(trim((string)($_POST[$feld] ?? '')), 0, 150);
            }
            if (!$demo) $_SESSION['swisssailing_kopf'] = $kopf;
            $praefix = $demo ? 'DEMO_' : '';
            $zip = SwissSailingRapport::zipErzeugen($auswahl, $kopf, $maxAlter, $praefix);
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $praefix . 'SwissSailing_Rapport_' . $jahr . '.zip"');
            header('Content-Length: ' . filesize($zip));
            header('Cache-Control: no-store');
            readfile($zip);
            unlink($zip);
            exit;
        }

        header('Location: /swisssailing.php?' . http_build_query(array_filter([
            'demo' => $demo, 'jahr' => $jahr, 'max_alter' => $maxAlter, 'event' => $gewaehlt,
            'gespeichert' => $demo ? null : 1,
        ])));
        exit;
    }
} catch (Throwable $e) {
    error_log('[Subventionssimulator] Swiss-Sailing-Rapport: ' . $e->getMessage());
    $fehler = $e->getMessage() === 'csrf'
        ? 'Die Sitzung ist abgelaufen. Bitte lade die Seite neu und versuche es nochmals.'
        : 'Der Rapport konnte nicht verarbeitet werden.';
}

// Personen über alle gewählten Events, jede nur einmal.
$personen = [];
$coachHinweise = [];
if ($auswahl && !$fehler) {
    try {
        foreach ($auswahl as $e) {
            foreach (SwissSailingRapport::athleten($e) as $a) {
                if (SwissSailingRapport::juniorStatus($a['jahrgang'] ? (int)$a['jahrgang'] : null, $jahr, $maxAlter) === 'nein') {
                    continue;
                }
                $personen[$a['schluessel']] ??= $a + ['events' => []];
                $personen[$a['schluessel']]['events'][] = $e['bezeichnung'];
            }
            $coaches = $e['coaches'] ?? SwissSailingRapport::coaches((int)$e['id']);
            $ohneMail = count(array_filter($coaches, fn($c) => empty($c['email'])));
            if (!$coaches) {
                $coachHinweise[] = $e['bezeichnung'] . ': keine Coaches (kein Personalaufwand erfasst).';
            } elseif (count($coaches) > SwissSailingRapport::MAX_COACHES) {
                $coachHinweise[] = $e['bezeichnung'] . ': ' . count($coaches) . ' Coaches, das Formular hat nur '
                    . SwissSailingRapport::MAX_COACHES . ' Zeilen.';
            }
            if ($ohneMail) {
                $coachHinweise[] = $e['bezeichnung'] . ': ' . $ohneMail . ' Coach(es) ohne E-Mail-Adresse.';
            }
        }
        uasort($personen, fn($a, $b) => [$a['nachname'], $a['vorname']] <=> [$b['nachname'], $b['vorname']]);
    } catch (Throwable $e) {
        error_log('[Subventionssimulator] Swiss-Sailing-Rapport: ' . $e->getMessage());
        $fehler = 'Die Teilnehmenden konnten nicht geladen werden.';
    }
}

$kopf = $demo ? SwissSailingDemo::KOPF : ($_SESSION['swisssailing_kopf']
    ?? ['organisator' => SwissSailingRapport::ORGANISATOR, 'verantwortlich' => auth_anzeigename(), 'kontakt' => '']);
$h = fn($w) => htmlspecialchars((string)$w);

$pageTitle = 'Swiss-Sailing-Rapport';
require __DIR__ . '/partials/header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-semibold">Swiss-Sailing-Rapport</h1>
  <p class="text-sm text-muted mt-1">
    Rapportformular «Promotion camp activity» für den Regionenbeitrag: ein Excel pro Förderlager mit
    den anwesenden Junioren ohne Swiss Olympic Talent Card (SOTC).
  </p>
</div>

<?php if ($fehler): ?>
<div class="alert alert--error mb-6"><?= $h($fehler) ?></div>
<?php endif; ?>
<?php if (isset($_GET['gespeichert'])): ?>
<div class="alert alert--success mb-6">Angaben wurden gespeichert.</div>
<?php endif; ?>

<?php if ($demo): ?>
<div class="alert alert--info mb-6">
  Demo mit fiktiven Daten. Nichts wird gespeichert; die Dateinamen beginnen mit «DEMO_».
</div>
<?php endif; ?>

<form method="get" action="/swisssailing.php" class="card mb-6">
  <div class="flex flex-wrap items-end gap-4 mb-4">
    <div>
      <label class="block text-sm font-medium mb-1" for="demo">Daten</label>
      <select id="demo" name="demo" class="input" onchange="this.form.submit()">
        <option value="">Echte Daten (Class Manager Tool)</option>
        <?php foreach (SwissSailingDemo::SZENARIEN as $key => $label): ?>
        <option value="<?= $key ?>" <?= $demo === $key ? 'selected' : '' ?>><?= $h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="jahr">Jahr</label>
      <input type="number" id="jahr" name="jahr" min="2000" max="2100" value="<?= $jahr ?>" class="input w-32">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="max_alter">Höchstalter (U19 = 18)</label>
      <input type="number" id="max_alter" name="max_alter" min="5" max="99" value="<?= $maxAlter ?>" class="input w-32">
    </div>
  </div>
  <fieldset class="mb-4">
    <legend class="block text-sm font-medium mb-1">Förderlager (durchgeführte Breitensport-Events <?= $jahr ?>)</legend>
    <?php if (!$events): ?>
    <p class="text-sm text-muted">Für dieses Jahr gibt es keine durchgeführten Breitensport-Events.</p>
    <?php endif; ?>
    <?php foreach ($events as $e): ?>
    <label class="flex items-center gap-2 text-sm mb-1">
      <input type="checkbox" name="event[]" value="<?= (int)$e['id'] ?>" class="checkbox"
             <?= in_array((int)$e['id'], $gewaehlt, true) ? 'checked' : '' ?>>
      <span>
        <?= $h($e['bezeichnung']) ?>
        <span class="text-muted">
          &middot; <?= $h(SwissSailingRapport::datumText($e['datum_von'], $e['datum_bis'])) ?>
          &middot; <?= $h($e['klasse_bezeichnung']) ?>
          &middot; <?= (int)$e['anwesend'] ?> anwesend
        </span>
        <?php if (!(int)$e['anwesend']): ?><span class="badge badge--warning">Anwesenheit fehlt</span><?php endif; ?>
      </span>
    </label>
    <?php endforeach; ?>
  </fieldset>
  <button type="submit" class="btn btn--secondary">Teilnehmende anzeigen</button>
</form>

<?php if ($auswahl && !$fehler): ?>
<form method="post" action="/swisssailing.php">
  <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
  <input type="hidden" name="demo" value="<?= $h($demo) ?>">
  <input type="hidden" name="jahr" value="<?= $jahr ?>">
  <input type="hidden" name="max_alter" value="<?= $maxAlter ?>">
  <?php foreach ($auswahl as $e): ?>
  <input type="hidden" name="event[]" value="<?= (int)$e['id'] ?>">
  <?php endforeach; ?>

  <?php if ($coachHinweise): ?>
  <div class="alert alert--warning mb-6">
    <ul class="list-disc list-inside">
      <?php foreach ($coachHinweise as $t): ?><li><?= $h($t) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <section class="card mb-6">
    <h2 class="font-semibold mb-1">Angaben zu den Athletinnen und Athleten (<?= count($personen) ?>)</h2>
    <p class="text-sm text-muted mb-4">
      Die Angaben kommen aus der Anmeldung (Class Manager Tool) und erscheinen hier grau. Fülle nur aus,
      was fehlt oder falsch ist; deine Eingabe hat Vorrang<?= $demo ? ' (in der Demo wird sie nicht gespeichert)' : ' und bleibt für spätere Rapporte gespeichert' ?>.
      Wer eine SOTC hat, erscheint nicht im Formular.
    </p>
    <div class="table-scroll">
      <table class="table text-sm">
        <thead>
          <tr><th>Name</th><th>Geschlecht</th><th>Lizenz-Nr.</th><th>J+S-Nr. (AHV)</th><th>Segelklasse</th><th>SOTC</th><th>Fehlt</th></tr>
        </thead>
        <tbody>
          <?php foreach ($personen as $schluessel => $p): ?>
          <?php $n = 'athlet[' . $schluessel . ']'; $wer = $p['vorname'] . ' ' . $p['nachname']; ?>
          <tr>
            <td>
              <?= $h($wer) ?>
              <div class="text-xs text-subtle">
                <?= $p['jahrgang'] ? (int)$p['jahrgang'] : 'Jahrgang unbekannt' ?>
                <?= $p['segelclub'] ? '&middot; ' . $h($p['segelclub']) : '' ?>
                <br><?= $h(implode(', ', $p['events'])) ?>
              </div>
            </td>
            <td>
              <select name="<?= $n ?>[geschlecht]" class="input input--sm" aria-label="Geschlecht <?= $h($wer) ?>">
                <option value=""><?= $h($p['vorgabe']['geschlecht'] ?: '–') ?></option>
                <?php foreach (['M', 'F'] as $g): ?>
                <option value="<?= $g ?>" <?= $p['erfasst']['geschlecht'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="text" name="<?= $n ?>[lizenz_nr]" value="<?= $h($p['erfasst']['lizenz_nr']) ?>" placeholder="<?= $h($p['vorgabe']['lizenz_nr']) ?>" maxlength="30" class="input input--sm w-28" aria-label="Lizenz-Nr. <?= $h($wer) ?>"></td>
            <td><input type="text" name="<?= $n ?>[js_nr]" value="<?= $h($p['erfasst']['js_nr']) ?>" maxlength="30" placeholder="<?= $h($p['vorgabe']['js_nr'] ?: '756.…') ?>" class="input input--sm w-40" aria-label="J+S-Nr. (AHV) <?= $h($wer) ?>"></td>
            <td><input type="text" name="<?= $n ?>[segelklasse]" value="<?= $h($p['erfasst']['segelklasse']) ?>" placeholder="<?= $h($p['vorgabe']['klasse']) ?>" maxlength="50" class="input input--sm w-28" aria-label="Segelklasse <?= $h($wer) ?>"></td>
            <td><input type="checkbox" name="<?= $n ?>[hat_sotc]" value="1" class="checkbox" <?= $p['hat_sotc'] ? 'checked' : '' ?> aria-label="SOTC <?= $h($wer) ?>"></td>
            <td class="text-xs">
              <?php if ($p['hat_sotc']): ?>
              <span class="badge badge--neutral">nicht im Rapport</span>
              <?php else: ?>
              <?= $h(implode(', ', SwissSailingRapport::fehlendeAngaben($p)) ?: '–') ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="card mb-6">
    <h2 class="font-semibold mb-4">Kopf des Formulars</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1" for="organisator">Organisator</label>
        <input type="text" id="organisator" name="organisator" maxlength="150" value="<?= $h($kopf['organisator']) ?>" class="input">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="verantwortlich">Verantwortliche Person</label>
        <input type="text" id="verantwortlich" name="verantwortlich" maxlength="150" value="<?= $h($kopf['verantwortlich']) ?>" class="input">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="kontakt">Kontakt (E-Mail, Telefon)</label>
        <input type="text" id="kontakt" name="kontakt" maxlength="150" value="<?= $h($kopf['kontakt']) ?>" class="input">
      </div>
    </div>
  </section>

  <div class="flex flex-wrap gap-2">
    <?php if (!$demo): ?>
    <button type="submit" name="aktion" value="speichern" class="btn btn--secondary">Angaben speichern</button>
    <?php endif; ?>
    <button type="submit" name="aktion" value="export" class="btn btn--primary">
      <?= $demo ? 'Demo-Excel herunterladen (ZIP)' : 'Speichern und Excel herunterladen (ZIP)' ?>
    </button>
  </div>
  <p class="text-xs text-subtle mt-3">
    <?= $demo
      ? 'Alle Angaben sind erfunden. Schick diese Dateien nicht an Swiss Sailing.'
      : 'Die Dateien enthalten Personendaten Minderjähriger. Gib sie nur an Swiss Sailing weiter. Versendet wird nichts automatisch.' ?>
  </p>
</form>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
