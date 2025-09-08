<?php
/** @var bool $isLogged */
/** @var string $csrf */
/** @var array<int, \App\Models\UserList> $own */
/** @var array<int, \App\Models\UserList> $public */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h4>Listen</h4>

  <?php if ($isLogged): ?>
    <div class="card">
      <div class="card-content">
        <span class="card-title">Neue Liste erstellen</span>
        <form method="post" action="<?= $base ?>/lists">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <div class="input-field">
            <input id="title" name="title" type="text" required maxlength="150">
            <label for="title">Titel</label>
          </div>
          <div class="input-field">
            <textarea id="description" name="description" class="materialize-textarea" maxlength="2000"></textarea>
            <label for="description">Beschreibung (optional)</label>
          </div>
          <div class="input-field">
            <select name="visibility">
              <option value="public" selected>Öffentlich</option>
              <option value="private">Privat</option>
            </select>
            <label>Sichtbarkeit</label>
          </div>
          <button class="btn waves-effect" type="submit">Erstellen</button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="card yellow lighten-5">
      <div class="card-content">
        <span class="card-title">Anmeldung erforderlich</span>
        <p>Bitte <a href="<?= $base ?>/login">melde dich an</a>, um eigene Listen zu erstellen.</p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($own)): ?>
    <h5 class="section">Meine Listen</h5>
    <div class="row">
      <?php foreach ($own as $l): ?>
        <div class="col s12 m6 l4">
          <div class="card">
            <div class="card-content">
              <span class="card-title"><?= htmlspecialchars($l->title, ENT_QUOTES, 'UTF-8') ?></span>
              <p class="grey-text">Sichtbarkeit: <?= htmlspecialchars($l->visibility, ENT_QUOTES, 'UTF-8') ?></p>
              <?php if ($l->description): ?>
                <p><?= nl2br(htmlspecialchars($l->description, ENT_QUOTES, 'UTF-8')) ?></p>
              <?php endif; ?>
            </div>
            <div class="card-action">
              <a href="<?= $base ?>/lists/<?= (int)$l->id ?>">Öffnen</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h5 class="section">Öffentliche Listen</h5>
  <div class="row">
    <?php foreach ($public as $l): ?>
      <div class="col s12 m6 l4">
        <div class="card">
          <div class="card-content">
            <span class="card-title"><?= htmlspecialchars($l->title, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($l->description): ?>
              <p><?= nl2br(htmlspecialchars($l->description, ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>
          </div>
          <div class="card-action">
            <a href="<?= $base ?>/lists/<?= (int)$l->id ?>">Öffnen</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($public)): ?>
      <div class="col s12"><p class="grey-text">Keine öffentlichen Listen vorhanden.</p></div>
    <?php endif; ?>
  </div>
</div>
