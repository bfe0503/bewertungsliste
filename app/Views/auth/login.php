<?php
$base  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$title = $title ?? 'Anmelden';
/** Ensure a CSRF token is available even if not passed explicitly */
$csrf  = $csrf ?? \App\Core\Csrf::token('login');
?>
<div class="row">
  <div class="col s12 m8 l6 offset-m2 offset-l3">
    <div class="card">
      <div class="card-header">
        <div class="title">
          <i class="material-icons">login</i>
          <span class="text">Anmelden</span>
        </div>
        <div class="actions">
          <a class="btn btn-ghost" href="<?= $base ?>/register"><i class="material-icons left">person_add</i>Registrieren</a>
        </div>
      </div>

      <div class="card-content">
        <form method="post" action="<?= $base ?>/login" autocomplete="on">
          <!-- CSRF token (required by AuthController::login) -->
          <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>">

          <div class="input-field">
            <label for="username">Nutzername</label>
            <input
              id="username"
              name="username"
              type="text"
              minlength="3"
              maxlength="32"
              required
              autocomplete="username"
              inputmode="text"
              placeholder="Nutzername"
              autofocus
            >
          </div>

          <div class="input-field">
            <label for="password">Passwort</label>
            <input
              id="password"
              name="password"
              type="password"
              minlength="8"
              required
              autocomplete="current-password"
              placeholder="Passwort"
            >
            <span class="helper-text muted">Mindestens 8 Zeichen empfohlen.</span>
          </div>

          <div class="section" style="display:flex; gap:12px; align-items:center;">
            <button class="btn btn-primary" type="submit">
              <i class="material-icons left">login</i>Anmelden
            </button>
            <a class="btn btn-outline" href="<?= $base ?>/register">
              <i class="material-icons left">person_add</i>Registrieren
            </a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
