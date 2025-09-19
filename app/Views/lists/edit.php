<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
/** @var object $list */
/** @var string $csrf */
/** @var string $deleteCsrf */
$id          = (int)($list->id ?? 0);
$title       = (string)($list->title ?? '');
$description = isset($list->description) && $list->description !== null ? (string)$list->description : '';
$isPublic    = (int)($list->is_public ?? 1) === 1;
?>
<div class="row">
  <div class="col s12 m10 l8 offset-m1 offset-l2">
    <div class="card">
      <div class="card-header">
        <div class="title">
          <i class="material-icons">edit</i>
          <span class="text">Liste bearbeiten</span>
        </div>
        <div class="actions">
          <a class="btn btn-ghost" href="<?= $base ?>/lists/<?= $id ?>">
            <i class="material-icons left">arrow_back</i>Zurück
          </a>
        </div>
      </div>

      <div class="card-content">
        <!-- Update form -->
        <form method="post" action="<?= $base ?>/lists/<?= $id ?>/update" autocomplete="off" style="margin-bottom:16px;">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>">

          <div class="input-field">
            <label for="title">Titel</label>
            <input id="title" name="title" type="text" maxlength="150" required
                   value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="input-field">
            <label for="description">Beschreibung (optional)</label>
            <textarea id="description" name="description" class="materialize-textarea"
                      rows="3" style="min-height:90px"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="input-field">
            <label for="visibility">Sichtbarkeit</label>
            <select id="visibility" name="visibility" class="browser-default">
              <option value="public"  <?= $isPublic ? 'selected' : '' ?>>Öffentlich (für angemeldete Nutzer sichtbar)</option>
              <option value="private" <?= !$isPublic ? 'selected' : '' ?>>Privat (nur für mich)</option>
            </select>
          </div>

          <div class="section" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">
              <i class="material-icons left">save</i>Speichern
            </button>
          </div>
        </form>

        <!-- Delete form (separat, NICHT verschachtelt) -->
        <form method="post" action="<?= $base ?>/lists/<?= $id ?>/delete" onsubmit="return confirm('Liste wirklich löschen?');">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$deleteCsrf, ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-danger" type="submit">
            <i class="material-icons left">delete</i>Löschen
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
