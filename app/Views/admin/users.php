<?php
/** @var array<int, \App\Models\User> $users */
/** @var string $csrf */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>Users</h5>
  <table class="striped">
    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u->id ?></td>
        <td><?= htmlspecialchars($u->username ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= !empty($u->is_admin) ? 'yes' : 'no' ?></td>
        <td>
          <form method="post" action="<?= $base ?>/admin/users/<?= (int)$u->id ?>/reset" style="display:inline;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="password" name="new_password" placeholder="Neues Passwort" minlength="12">
            <button class="btn-small">Set password</button>
          </form>
          <form method="post" action="<?= $base ?>/admin/users/<?= (int)$u->id ?>/delete" style="display:inline;margin-left:8px;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn-small red">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>