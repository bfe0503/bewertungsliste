<?php
/**
 * Variables:
 * @var array<int, array{id:int,title:string,user_id:int,owner_username:string}> $lists
 * @var string $csrf
 */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h4>Admin – Lists</h4>

  <?php if (empty($lists)): ?>
    <p>No lists found.</p>
  <?php else: ?>
    <table class="striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Owner</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($lists as $l): ?>
        <tr>
          <td><?= (int)$l['id'] ?></td>
          <td><?= htmlspecialchars($l['title'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?= htmlspecialchars($l['owner_username'] ?? ('user#' . (int)$l['user_id']), ENT_QUOTES, 'UTF-8') ?>
            <small style="color:#666;">(id: <?= (int)$l['user_id'] ?>)</small>
          </td>
          <td>
            <form method="post" action="<?= $base ?>/admin/lists/<?= (int)$l['id'] ?>/delete" onsubmit="return confirm('Liste wirklich löschen?');" style="display:inline;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <button class="btn red">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
