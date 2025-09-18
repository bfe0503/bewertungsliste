<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$title = $title ?? 'Registrieren';
?>
<div class="row">
  <div class="col s12 m8 l6 offset-m2 offset-l3">
    <div class="card">
      <div class="card-header">
        <div class="title">
          <i class="material-icons">person_add</i>
          <span class="text">Registrieren</span>
        </div>
        <div class="actions">
          <a class="btn btn-ghost" href="<?= $base ?>/login"><i class="material-icons left">login</i>Anmelden</a>
        </div>
      </div>

      <div class="card-content">
        <form method="post" action="<?= $base ?>/register" autocomplete="on">
          <div class="input-field">
            <input id="username" name="username" type="text" minlength="3" maxlength="32" required
                   autocomplete="username" inputmode="text" placeholder="Nutzername (3–32 Zeichen)" autofocus>
            <label for="username">Nutzername</label>
            <span class="helper-text muted">Nur Nutzername &amp; Passwort – keine E-Mail erforderlich.</span>
          </div>

          <div class="input-field">
            <input id="password" name="password" type="password" minlength="8" required
                   autocomplete="new-password" placeholder="Passwort (mind. 8 Zeichen)">
            <label for="password">Passwort</label>
            <span class="helper-text muted">Tipp: Verwende ein starkes Passwort.</span>
          </div>

          <div class="section" style="display:flex; gap:12px; align-items:center;">
            <button class="btn btn-primary" type="submit">
              <i class="material-icons left">person_add</i>Konto erstellen
            </button>
            <a class="btn btn-outline" href="<?= $base ?>/login">
              <i class="material-icons left">login</i>Schon Konto? Anmelden
            </a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
