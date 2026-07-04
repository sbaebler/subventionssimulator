<?php
require_once __DIR__ . '/../includes/Subvention.php';
$pageTitle = 'Subventionen – Übersicht';
$subventionen = Subvention::alle();
require __DIR__ . '/partials/header.php';
?>

<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-semibold">Subventionstöpfe</h1>
  <a href="/erfassen.php"
     class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg">
    + Neue Subvention
  </a>
</div>

<?php if (empty($subventionen)): ?>
  <p class="text-gray-500">Noch keine Subventionen erfasst.
    <a href="/erfassen.php" class="text-blue-600 underline">Jetzt erfassen</a>
  </p>
<?php else: ?>
  <div class="grid gap-4">
    <?php foreach ($subventionen as $s): ?>
    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-start justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <span class="text-xs font-medium bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
            <?= htmlspecialchars(Subvention::KATEGORIEN[$s['kategorie']] ?? $s['kategorie']) ?>
          </span>
          <?php if ($s['antragsfrist']): ?>
          <span class="text-xs text-orange-600">
            Frist: <?= date('d.m.Y', strtotime($s['antragsfrist'])) ?>
          </span>
          <?php endif; ?>
          <?php foreach (Subvention::fristen($s['id']) as $frist): ?>
          <span class="text-xs text-orange-600">
            <?= htmlspecialchars($frist['bezeichnung']) ?><?php if ($frist['datum']): ?>: <?= date('d.m.Y', strtotime($frist['datum'])) ?><?php endif; ?>
          </span>
          <?php endforeach; ?>
        </div>
        <h2 class="font-semibold text-gray-900"><?= htmlspecialchars($s['bezeichnung']) ?></h2>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($s['foerderstelle']) ?></p>
        <?php if ($s['beschreibung']): ?>
        <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?= htmlspecialchars($s['beschreibung']) ?></p>
        <?php endif; ?>
        <p class="text-xs text-gray-400 mt-2">
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
        <a href="/erfassen.php?id=<?= $s['id'] ?>"
           class="text-sm text-gray-500 hover:text-blue-600 border border-gray-200 rounded px-3 py-1.5">
          Bearbeiten
        </a>
        <a href="/simulieren.php?id=<?= $s['id'] ?>"
           class="text-sm bg-green-600 hover:bg-green-700 text-white rounded px-3 py-1.5">
          Simulieren
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
