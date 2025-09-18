<?php
$baseUri   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$flashes   = \App\Core\Flash::consume();
$isLogged  = \App\Core\Auth::check();
$logoutToken = $isLogged ? \App\Core\Csrf::token('logout') : null;
$isAdmin   = !empty($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1; // admin-only nav
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Bewertung', ENT_QUOTES, 'UTF-8') ?></title>

  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="<?= $baseUri ?>/assets/app.css">

  <meta name="theme-color" content="#424242" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#111111" media="(prefers-color-scheme: dark)">
  <style>main{min-height:70vh;}</style>
</head>
<body>

  <nav class="teal">
    <div class="nav-wrapper container">
      <a href="<?= $baseUri !== '' ? $baseUri : '/' ?>" class="brand-logo">Bewertung</a>
      <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
      <ul class="right hide-on-med-and-down">
        <li><a href="<?= $baseUri ?>/lists"><i class="material-icons left">list</i>Listen</a></li>

        <?php if ($isAdmin): ?>
          <li><a href="<?= $baseUri ?>/admin"><i class="material-icons left">admin_panel_settings</i>Admin</a></li>
          <li><a href="<?= $baseUri ?>/admin/users"><i class="material-icons left">group</i>Users</a></li>
          <li><a href="<?= $baseUri ?>/admin/lists"><i class="material-icons left">folder</i>Lists</a></li>
        <?php endif; ?>

        <?php if (!$isLogged): ?>
          <li><a href="<?= $baseUri ?>/login">Anmelden</a></li>
          <li><a class="btn btn-small btn-primary" href="<?= $baseUri ?>/register">Registrieren</a></li>
        <?php else: ?>
          <li>
            <form method="post" action="<?= $baseUri ?>/logout" style="display:inline">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$logoutToken, ENT_QUOTES, 'UTF-8') ?>">
              <button class="btn btn-small btn-primary" type="submit">Abmelden</button>
            </form>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <ul class="sidenav" id="mobile-nav">
    <li><a href="<?= $baseUri ?>/lists"><i class="material-icons left">list</i>Listen</a></li>

    <?php if ($isAdmin): ?>
      <li><a href="<?= $baseUri ?>/admin"><i class="material-icons left">admin_panel_settings</i>Admin</a></li>
      <li><a href="<?= $baseUri ?>/admin/users"><i class="material-icons left">group</i>Users</a></li>
      <li><a href="<?= $baseUri ?>/admin/lists"><i class="material-icons left">folder</i>Lists</a></li>
    <?php endif; ?>

    <?php if (!$isLogged): ?>
      <li><a href="<?= $baseUri ?>/login"><i class="material-icons left">login</i>Anmelden</a></li>
      <li><a href="<?= $baseUri ?>/register"><i class="material-icons left">person_add</i>Registrieren</a></li>
    <?php else: ?>
      <li>
        <form method="post" action="<?= $baseUri ?>/logout" style="padding:12px">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$logoutToken, ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-primary" type="submit" style="width:100%">Abmelden</button>
        </form>
      </li>
    <?php endif; ?>
  </ul>

  <main class="container">
    <?php \App\Core\View::include($__view ?? 'home/index', get_defined_vars()); ?>
  </main>

  <footer class="page-footer teal">
    <div class="container"><div class="row"><div class="col s12">© <?= date('Y') ?> Bewertung</div></div></div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      M.AutoInit();
      const sidenavs = document.querySelectorAll('.sidenav');
      M.Sidenav.init(sidenavs);
    });
    <?php if (!empty($flashes)): ?>
      (function(){
        const msgs = <?= json_encode($flashes, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
        msgs.forEach(m => { M.toast({html: m.message}); });
      })();
    <?php endif; ?>
  </script>
</body>
</html>
