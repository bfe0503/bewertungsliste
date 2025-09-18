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

  <meta name="theme-color" content="#f5f5f5" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#111111" media="(prefers-color-scheme: dark)">

  <style>
    /* ---------- Theme variables ---------- */
    :root {
      --bg: #f5f6f7;
      --text: #1f2937;
      --muted: #6b7280;
      --card: #ffffff;
      --card-border: rgba(0,0,0,0.08);

      --brand: #00796b;
      --brand-hover: #00695c;

      --table-head: #edf2f7;           /* Header-Hintergrund (Light) */
      --table-row-odd: rgba(0,0,0,0.02);
      --table-row-even: transparent;
      --table-row-hover: rgba(0,0,0,0.05);

      --badge-public: #26a69a;
      --badge-private: #9e9e9e;

      --toast-bg: #323232;
      --toast-text: #fff;

      --toggle-border: rgba(0,0,0,0.2);
      --chrome-line: rgba(0,0,0,0.08);
    }
    .theme-dark {
      --bg: #0f1115;
      --text: #e9eef3;
      --muted: #9aa4af;
      --card: #171a21;
      --card-border: rgba(255,255,255,0.08);

      --brand: #129383;
      --brand-hover: #0f7d70;

      --table-head: #0f1a22;           /* kontrastreicher Header (Dark) */
      --table-row-odd: rgba(255,255,255,0.03);
      --table-row-even: transparent;
      --table-row-hover: rgba(255,255,255,0.07);

      --badge-public: #2fbba9;
      --badge-private: #6b7280;

      --toast-bg: #0f1115;
      --toast-text: #e9eef3;

      --toggle-border: rgba(255,255,255,0.35);
      --chrome-line: rgba(255,255,255,0.08);
    }

    /* ---------- Chrome ---------- */
    html, body { background: var(--bg); color: var(--text); }
    main { min-height: 70vh; }
    .brand-logo { font-weight: 600; color: var(--text) !important; }

    nav.teal {
      background: var(--bg) !important;
      border-bottom: 1px solid var(--chrome-line);
      box-shadow: none;
    }
    .nav-wrapper a, nav a { color: var(--text) !important; }
    .page-footer.teal {
      background: var(--bg) !important;
      color: var(--muted) !important;
      border-top: 1px solid var(--chrome-line);
    }

    /* ---------- Cards & Panels ---------- */
    .card,
    .card-panel {
      background: var(--card) !important;
      color: var(--text) !important;
      border: 1px solid var(--card-border) !important;
      border-radius: 10px;
    }
    .card .card-content,
    .card-panel .card-content,
    .card .card-title { color: var(--text) !important; }

    /* ---------- Forms ---------- */
    .input-field input[type="text"],
    .input-field input[type="password"],
    .materialize-textarea {
      color: var(--text) !important;
    }
    .input-field input::placeholder,
    .materialize-textarea::placeholder { color: var(--muted) !important; opacity: 1; }
    .input-field label { color: var(--muted) !important; }
    .input-field input:focus + label,
    .materialize-textarea:focus + label { color: var(--brand) !important; }
    .input-field input:focus,
    .materialize-textarea:focus { border-bottom: 1px solid var(--brand) !important; box-shadow: 0 1px 0 0 var(--brand) !important; }

    /* Materialize Select */
    .select-wrapper input.select-dropdown {
      color: var(--text) !important;
      background: transparent !important;
      border-bottom: 1px solid var(--card-border) !important;
    }
    .dropdown-content.select-dropdown { background: var(--card) !important; }
    .dropdown-content.select-dropdown li > span { color: var(--text) !important; }
    .dropdown-content.select-dropdown li.active > span { color: #fff !important; background: var(--brand) !important; }

    /* ---------- Buttons ---------- */
    .btn-primary {
      background-color: var(--brand) !important;
      color: #fff !important;
    }
    .btn-primary:hover, .btn-primary:focus {
      background-color: var(--brand-hover) !important;
    }
    .card .btn:not(.red):not(.grey),
    .card-panel .btn:not(.red):not(.grey) {
      background-color: var(--brand) !important;
      color: #fff !important;
    }
    .card .btn:not(.red):not(.grey):hover,
    .card-panel .btn:not(.red):not(.grey):hover,
    .card .btn:not(.red):not(.grey):focus,
    .card-panel .btn:not(.red):not(.grey):focus {
      background-color: var(--brand-hover) !important;
    }

    /* ---------- Tables: Zebra + Hover + Header-Kontrast ---------- */
    table.striped {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: transparent;
    }
    table.striped thead {
      background: var(--table-head);
    }
    table.striped thead th {
      color: var(--text);
      font-weight: 600;
      border-bottom: 1px solid var(--card-border);
    }
    table.striped tbody tr:nth-child(odd) {
      background-color: var(--table-row-odd);
    }
    table.striped tbody tr:nth-child(even) {
      background-color: var(--table-row-even);
    }
    table.striped tbody tr:hover {
      background-color: var(--table-row-hover);
    }
    table.striped td, table.striped th {
      border-bottom: 1px solid var(--card-border);
    }

    /* Mobile: etwas kompakter & horizontales Scrollen erlauben, falls nötig */
    .table-responsive {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 600px) {
      table.striped th, table.striped td { padding: 10px 12px; }
      table.striped { font-size: 14px; }
    }

    /* ---------- Badges ---------- */
    .badge.green { background-color: var(--badge-public) !important; }
    .badge.grey  { background-color: var(--badge-private) !important; }

    /* ---------- Sidenav ---------- */
    .sidenav {
      background: var(--card) !important;
      color: var(--text) !important;
    }
    .sidenav a { color: var(--text) !important; }
    .sidenav .subheader,
    .sidenav .user-view .name,
    .sidenav .user-view .email { color: var(--muted) !important; }
    .sidenav .divider { background-color: var(--card-border) !important; }
    .sidenav .material-icons { color: var(--text) !important; }

    /* ---------- Spacing ---------- */
    .section { margin-top: 18px; }
    .card.small { min-height: 220px; }

    /* ---------- Theme toggle ---------- */
    .theme-toggle-btn {
      border: 1px solid var(--toggle-border);
      border-radius: 8px;
      padding: 0 8px;
      background: transparent;
      display: inline-flex;
      align-items: center;
      height: 32px;
      line-height: 32px;
      color: var(--text) !important;
    }
    .theme-toggle-btn i { vertical-align: middle; color: inherit; }
  </style>
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

        <li>
          <a href="#!" class="theme-toggle-btn" id="themeToggle" title="Theme umschalten">
            <i class="material-icons" id="themeIcon">dark_mode</i>
          </a>
        </li>

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

    <li><a href="#!" id="themeToggleMobile"><i class="material-icons left">dark_mode</i>Theme umschalten</a></li>

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
    // Materialize init
    document.addEventListener('DOMContentLoaded', function () {
      M.AutoInit();
      const sidenavs = document.querySelectorAll('.sidenav');
      M.Sidenav.init(sidenavs);
    });

    // Theme handling
    (function () {
      const LS_KEY = 'theme';
      const htmlEl = document.documentElement;
      const iconEl = document.getElementById('themeIcon');

      function applyTheme(mode) {
        if (mode === 'dark') {
          htmlEl.classList.add('theme-dark');
          if (iconEl) iconEl.textContent = 'light_mode';
        } else {
          htmlEl.classList.remove('theme-dark');
          if (iconEl) iconEl.textContent = 'dark_mode';
        }
      }

      let mode = localStorage.getItem(LS_KEY);
      if (!mode) {
        mode = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
          ? 'dark' : 'light';
      }
      applyTheme(mode);

      function toggleTheme() {
        mode = (htmlEl.classList.contains('theme-dark')) ? 'light' : 'dark';
        localStorage.setItem(LS_KEY, mode);
        applyTheme(mode);
      }

      const btn = document.getElementById('themeToggle');
      const btnMob = document.getElementById('themeToggleMobile');
      if (btn) btn.addEventListener('click', function(e){ e.preventDefault(); toggleTheme(); });
      if (btnMob) btnMob.addEventListener('click', function(e){ e.preventDefault(); toggleTheme(); });
    })();

    // Flash toasts
    <?php if (!empty($flashes)): ?>
      (function(){
        const msgs = <?= json_encode($flashes, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
        msgs.forEach(m => { M.toast({html: m.message}); });
      })();
    <?php endif; ?>
  </script>
</body>
</html>
