<?php
/** @var array<int, array{ id:int, title:string, user_id:int }> $lists */
/** @var string $csrf */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>Lists</h5>
  <table class="striped">
    <thead><tr><th>ID</th><th>Title</th><th>Owner (user_id)</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($lists as $l): ?>
      <tr>
        <td><?= (int)$l['id'] ?></td>
        <td><?= htmlspecialchars($l['title'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int)$l['user_id'] ?></td>
        <td>
          <form method="post" action="<?= $base ?>/admin/lists/<?= (int)$l['id'] ?>/delete" style="display:inline;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn-small red">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
