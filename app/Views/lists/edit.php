<?php
/** @var \App\Models\UserList $list */
/** @var string $csrf */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>Edit list</h5>
  <form method="post" action="<?= $base ?>/lists/<?= (int)$list->id ?>">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="input-field">
      <input id="title" name="title" type="text" required maxlength="120" value="<?= htmlspecialchars($list->title, ENT_QUOTES, 'UTF-8') ?>">
      <label for="title" class="active">Title</label>
    </div>
    <div class="input-field">
      <select id="visibility" name="visibility">
        <option value="public" <?= (int)$list->is_public === 1 ? 'selected' : '' ?>>Public</option>
        <option value="private" <?= (int)$list->is_public !== 1 ? 'selected' : '' ?>>Private</option>
      </select>
      <label for="visibility" class="active">Visibility</label>
    </div>
    <button class="btn waves-effect" type="submit">Save</button>
  </form>

  <form method="post" action="<?= $base ?>/lists/<?= (int)$list->id ?>/delete" style="margin-top:16px;">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token('list_delete_' . (int)$list->id), ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn red">Delete list</button>
  </form>
</div>
