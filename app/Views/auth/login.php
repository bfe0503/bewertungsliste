<?php
/** @var string $csrf */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>Anmelden</h5>
  <form method="post" action="<?= $base ?>/login">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="input-field">
      <input id="email" name="email" type="email" required>
      <label for="email">E-Mail</label>
    </div>
    <div class="input-field">
      <input id="password" name="password" type="password" required minlength="6">
      <label for="password">Passwort</label>
    </div>
    <button class="btn waves-effect" type="submit">Anmelden</button>
    <a class="btn-flat" href="<?= $base ?>/register">Konto erstellen</a>
  </form>
</div>
