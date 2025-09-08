<?php
/** @var string $title */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="hero">
  <h3>Bewerte, was du liebst</h3>
  <p>Erstelle Listen (z. B. Cocktails) und vergebe Bewertungen von 1 bis 5 – öffentlich oder privat.</p>
  <div class="mt-3">
    <a class="btn btn-primary waves-effect" href="<?= $base ?>/lists">
      <i class="material-icons left">playlist_add</i> Jetzt starten
    </a>
  </div>
</div>
