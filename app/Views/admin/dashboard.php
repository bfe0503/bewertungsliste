<?php
/**
 * Admin dashboard with quick links.
 * Visible nur für Admins (Access-Guard liegt im Controller).
 */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<div class="section">
  <h4>Admin</h4>
  <p>Wähle einen Bereich:</p>

  <div class="row" style="margin-top:12px;">
    <div class="col s12 m6">
      <div class="card">
        <div class="card-content">
          <span class="card-title">Users verwalten</span>
          <p>Passwörter setzen, Nutzer löschen/bearbeiten.</p>
        </div>
        <div class="card-action">
          <a class="btn" href="<?= $base ?>/admin/users">Zu Users</a>
        </div>
      </div>
    </div>

    <div class="col s12 m6">
      <div class="card">
        <div class="card-content">
          <span class="card-title">Lists verwalten</span>
          <p>Listen ansehen, bearbeiten und löschen.</p>
        </div>
        <div class="card-action">
          <a class="btn" href="<?= $base ?>/admin/lists">Zu Lists</a>
        </div>
      </div>
    </div>
  </div>
</div>
