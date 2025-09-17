<?php
/** @var string $csrf */
/** @var \App\Models\User $user */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>My Account</h5>
  <form method="post" action="<?= $base ?>/account">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="input-field">
      <input id="username" name="username" type="text" required minlength="3" maxlength="32" pattern="^[a-z0-9_.-]{3,32}$" value="<?= htmlspecialchars($user->username ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <label for="username" class="active">Username</label>
    </div>

    <div class="card-panel grey lighten-3">Change password (optional)</div>
    <div class="input-field">
      <input id="current_password" name="current_password" type="password" minlength="12" autocomplete="current-password">
      <label for="current_password">Current password</label>
    </div>
    <div class="input-field">
      <input id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password">
      <label for="new_password">New password</label>
    </div>
    <div class="input-field">
      <input id="new_password_confirm" name="new_password_confirm" type="password" minlength="12" autocomplete="new-password">
      <label for="new_password_confirm">New password bestätigen</label>
    </div>

    <button class="btn waves-effect" type="submit">Save</button>
  </form>
</div>