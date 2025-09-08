<?php
/** @var string $csrf */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h5>Registrieren</h5>
  <form method="post" action="<?= $base ?>/register">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="input-field">
      <input id="display_name" name="display_name" type="text" maxlength="80">
      <label for="display_name">Anzeigename (optional)</label>
    </div>
    <div class="input-field">
      <input id="email" name="email" type="email" required>
      <label for="email">E-Mail</label>
    </div>
    <div class="input-field">
      <input id="password" name="password" type="password" required minlength="6">
      <label for="password">Passwort</label>
    </div>
    <button class="btn waves-effect" type="submit">Registrieren</button>
    <a class="btn-flat" href="<?= $base ?>/login">Ich habe schon ein Konto</a>
  </form>
</div>
