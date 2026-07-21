<?php
require_once __DIR__ . '/../includes/Subvention.php';
$pageTitle = 'Förderprogramme – Übersicht';
$subventionen = Subvention::alle();

// Vollständigkeit pro Programm ermitteln (für Badges und Fortschritt)
$fehltProProgramm = [];
$anzahlVollstaendig = 0;
foreach ($subventionen as $s) {
    $fehlt = Subvention::vollstaendigkeit($s);
    $fehltProProgramm[$s['id']] = $fehlt;
    if (empty($fehlt)) $anzahlVollstaendig++;
}

require __DIR__ . '/partials/header.php';
?>

<div class="flex items-center justify-between mb-2">
  <h1 class="text-2xl font-semibold">Förderprogramme</h1>
  <a href="/erfassen.php" class="btn btn--primary btn--sm">
    + Neues Förderprogramm
  </a>
</div>

<?php if (!empty($subventionen)): ?>
<div class="mb-6">
  <p class="text-sm text-muted mb-2">
    <?= $anzahlVollstaendig ?> von <?= count($subventionen) ?> Förderprogrammen vollständig erfasst
    <?php if ($anzahlVollstaendig < count($subventionen)): ?>
    – die amber markierten warten noch auf Angaben.
    <?php else: ?>
    – alles beisammen. 🎉
    <?php endif; ?>
  </p>
  <div class="progress w-full max-w-md">
    <div class="progress__bar"
         style="width: <?= count($subventionen) ? round($anzahlVollstaendig / count($subventionen) * 100) : 0 ?>%"></div>
  </div>
</div>
<?php endif; ?>

<?php if (empty($subventionen)): ?>
  <p class="empty-state">Noch keine Förderprogramme erfasst.
    <a href="/erfassen.php" class="link">Jetzt erfassen</a>
  </p>
<?php else: ?>
  <div class="grid gap-4">
    <?php foreach ($subventionen as $s): $fehlt = $fehltProProgramm[$s['id']]; ?>
    <div class="card flex items-start justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <?php if (empty($fehlt)): ?>
          <span class="badge badge--success">Vollständig</span>
          <?php else: ?>
          <span class="badge badge--warning">Unvollständig</span>
          <?php endif; ?>
          <span class="badge badge--info">
            <?= htmlspecialchars(Subvention::KATEGORIEN[$s['kategorie']] ?? $s['kategorie']) ?>
          </span>
          <?php if ($s['antragsfrist']): ?>
          <span class="text-xs text-warning">
            Frist: <?= date('d.m.Y', strtotime($s['antragsfrist'])) ?>
          </span>
          <?php endif; ?>
          <?php foreach (Subvention::fristen($s['id']) as $frist): ?>
          <span class="text-xs text-warning">
            <?= htmlspecialchars($frist['bezeichnung']) ?><?php if ($frist['datum']): ?>: <?= date('d.m.Y', strtotime($frist['datum'])) ?><?php endif; ?>
          </span>
          <?php endforeach; ?>
        </div>
        <h2 class="font-semibold"><?= htmlspecialchars($s['bezeichnung']) ?></h2>
        <p class="text-sm text-muted"><?= htmlspecialchars($s['foerderstelle']) ?></p>
        <?php if ($s['beschreibung']): ?>
        <p class="text-sm text-muted mt-1 line-clamp-2"><?= htmlspecialchars($s['beschreibung']) ?></p>
        <?php endif; ?>
        <?php if ($fehlt): ?>
        <p class="text-xs text-warning mt-2">
          Fehlt noch:
          <?php foreach ($fehlt as $idx => $f): ?>
          <a href="/erfassen.php?id=<?= $s['id'] ?>#schritt-<?= (int)$f['schritt'] ?>"
             class="underline"><?= htmlspecialchars($f['text']) ?></a><?= $idx < count($fehlt) - 1 ? ' · ' : '' ?>
          <?php endforeach; ?>
        </p>
        <?php endif; ?>
        <p class="text-xs text-subtle mt-2">
          <?php if ($s['erstellt_von_name']): ?>
            Erfasst von <?= htmlspecialchars($s['erstellt_von_name']) ?> am <?= date('d.m.Y', strtotime($s['erstellt_am'])) ?>
          <?php else: ?>
            Erfasst am <?= date('d.m.Y', strtotime($s['erstellt_am'])) ?>
          <?php endif; ?>
          <?php if ($s['geaendert_von_name'] && $s['geaendert_am'] !== $s['erstellt_am']): ?>
            &middot; Zuletzt geändert von <?= htmlspecialchars($s['geaendert_von_name']) ?> am <?= date('d.m.Y', strtotime($s['geaendert_am'])) ?>
          <?php endif; ?>
        </p>
      </div>
      <div class="flex gap-2 shrink-0">
        <a href="/erfassen.php?id=<?= $s['id'] ?>" class="btn btn--secondary btn--sm">
          Bearbeiten
        </a>
        <a href="/simulieren.php?id=<?= $s['id'] ?>" class="btn btn--primary btn--sm">
          Simulieren
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
