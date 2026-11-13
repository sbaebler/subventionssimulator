<?php // Umschalter zwischen den beiden Ansichten des Simulators; erwartet $simulatorAnsicht ('event'|'jahr') ?>
<nav class="flex gap-2 mb-6" aria-label="Ansicht">
  <a href="/simulieren.php" class="btn btn--sm <?= $simulatorAnsicht === 'event' ? 'btn--primary' : 'btn--secondary' ?>">Event</a>
  <a href="/simulieren.php?ansicht=jahr" class="btn btn--sm <?= $simulatorAnsicht === 'jahr' ? 'btn--primary' : 'btn--secondary' ?>">Jahresbeiträge</a>
</nav>
