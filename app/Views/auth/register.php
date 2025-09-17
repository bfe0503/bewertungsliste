<?php
/** @var string $csrf */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>Register</h5>
  <form method="post" action="<?= $base ?>/register">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="input-field">
      <input id="username" name="username" type="text" required minlength="3" maxlength="32" pattern="^[a-z0-9_.-]{3,32}$" autocomplete="username">
      <label for="username">Username</label>
      <span class="helper-text">Allowed: a-z, 0-9, _ . - (3–32 Zeichen)</span>
    </div>
    <div class="input-field">
      <input id="password" name="password" type="password" required minlength="12" autocomplete="new-password">
      <label for="password">Password</label>
    </div>
    <button class="btn waves-effect" type="submit">Register</button>
    <a class="btn-flat" href="<?= $base ?>/login">I already have an account</a>
  </form>
</div>