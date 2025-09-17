<?php
/** @var string $csrf */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>Login</h5>
  <form method="post" action="<?= $base ?>/login">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="input-field">
      <input id="username" name="username" type="text" required minlength="3" maxlength="32" autocomplete="username">
      <label for="username">Username</label>
    </div>
    <div class="input-field">
      <input id="password" name="password" type="password" required minlength="12" autocomplete="current-password">
      <label for="password">Password</label>
    </div>
    <button class="btn waves-effect" type="submit">Login</button>
    <a class="btn-flat" href="<?= $base ?>/register">Create account</a>
  </form>
</div>