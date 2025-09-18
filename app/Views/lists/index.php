<?php
/**
 * Variables passed in:
 * @var array<int, mixed> $own     // own lists (array rows or objects)
 * @var array<int, mixed> $public  // public lists (array rows or objects, includes owner_username)
 * @var string $csrf               // CSRF for create_list
 * @var bool $isLogged
 */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

/** Helper to read field from array or object safely */
$F = static function($row, string $key, $default = null) {
    if (is_array($row)) {
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }
    if (is_object($row)) {
        return isset($row->$key) ? $row->$key : $default;
    }
    return $default;
};

/** Helper: resolve visibility using either 'is_public' (int) or 'visibility' (string) */
$visText = static function($row) use ($F): string {
    $isPublic = null;
    $v1 = $F($row, 'is_public', null);
    if ($v1 !== null) {
        $isPublic = ((int)$v1 === 1);
    } else {
        $v2 = (string)$F($row, 'visibility', '');
        if ($v2 !== '') {
            $isPublic = ($v2 === 'public');
        }
    }
    return $isPublic ? 'Public' : 'Private';
};
?>
<div class="section">
  <h4>Lists</h4>
  <?php if (!empty($isLogged)): ?>
    <div class="card-panel">
      <h6>Create a new list</h6>
      <form method="post" action="<?= $base ?>/lists">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="input-field">
          <input id="title" name="title" type="text" required maxlength="150">
          <label for="title">Title</label>
        </div>
        <div class="input-field">
          <textarea id="description" name="description" class="materialize-textarea" maxlength="2000" placeholder="Optional description"></textarea>
          <label for="description" class="active">Description (optional)</label>
        </div>
        <div class="input-field">
          <select id="visibility" name="visibility">
            <option value="public" selected>Public</option>
            <option value="private">Private</option>
          </select>
          <label for="visibility">Visibility</label>
        </div>
        <button class="btn waves-effect" type="submit">Create</button>
      </form>
    </div>
  <?php else: ?>
    <p>Please <a href="<?= $base ?>/login">sign in</a> to create your own lists.</p>
  <?php endif; ?>
</div>

<?php if (!empty($own)): ?>
  <div class="section">
    <h5>Your lists</h5>
    <div class="row">
      <?php foreach ($own as $row): ?>
        <?php
          $id    = (int)$F($row, 'id', 0);
          $title = (string)($F($row, 'title', '') ?? '');
          $desc  = (string)($F($row, 'description', '') ?? '');
          $vis   = $visText($row);
          $owner = (string)($F($row, 'owner_username', '') ?? '');
          if ($owner === '') { $owner = 'you'; }
        ?>
        <div class="col s12 m6 l4">
          <div class="card small">
            <div class="card-content">
              <span class="card-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
              <p style="margin-top:4px;">
                <small>Owner: <strong><?= htmlspecialchars($owner, ENT_QUOTES, 'UTF-8') ?></strong></small>
              </p>
              <?php if ($desc !== ''): ?>
                <p style="margin-top:6px;"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
              <p style="margin-top:8px;">Visibility: <strong><?= htmlspecialchars($vis, ENT_QUOTES, 'UTF-8') ?></strong></p>
            </div>
            <div class="card-action">
              <a href="<?= $base ?>/lists/<?= $id ?>">Open</a>
              <a href="<?= $base ?>/lists/<?= $id ?>/edit" class="right">Edit</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="section">
  <h5>Public lists</h5>
  <?php if (empty($public)): ?>
    <p>No public lists yet.</p>
  <?php else: ?>
    <div class="row">
      <?php foreach ($public as $row): ?>
        <?php
          $id    = (int)$F($row, 'id', 0);
          $title = (string)($F($row, 'title', '') ?? '');
          $desc  = (string)($F($row, 'description', '') ?? '');
          $vis   = $visText($row);
          $owner = (string)($F($row, 'owner_username', '') ?? '');
        ?>
        <div class="col s12 m6 l4">
          <div class="card small">
            <div class="card-content">
              <span class="card-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
              <p style="margin-top:4px;">
                <small>Owner: <strong><?= htmlspecialchars($owner, ENT_QUOTES, 'UTF-8') ?></strong></small>
              </p>
              <?php if ($desc !== ''): ?>
                <p style="margin-top:6px;"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
              <p style="margin-top:8px;">Visibility: <strong><?= htmlspecialchars($vis, ENT_QUOTES, 'UTF-8') ?></strong></p>
            </div>
            <div class="card-action">
              <a href="<?= $base ?>/lists/<?= $id ?>">Open</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
