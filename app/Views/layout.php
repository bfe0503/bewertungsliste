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

      --table-head: #edf2f7;
      --table-row-odd: rgba(0,0,0,0.02);
      --table-row-even: transparent;
      --table-row-hover: rgba(0,0,0,0.05);

      --badge-public: #26a69a;
      --badge-private: #9e9e9e;

      --toast-bg: #323232;
      --toast-text: #fff;

      --toggle-border: rgba(0,0,0,0.2);
      --chrome-line: rgba(0,0,0,0.08);

      /* Spacing scale */
      --space-1: 6px;
      --space-2: 10px;
      --space-3: 14px;
      --space-4: 18px;
      --space-5: 24px;
      --radius: 10px;
      --focus: 2px;
    }
    .theme-dark {
      --bg: #0f1115;
      --text: #e9eef3;
      --muted: #9aa4af;
      --card: #171a21;
      --card-border: rgba(255,255,255,0.08);

      --brand: #129383;
      --brand-hover: #0f7d70;

      --table-head: #0f1a22;
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
    main { min-height: 70vh; padding-top: var(--space-5); padding-bottom: var(--space-5); }
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

    /* ---------- Container-Breite ---------- */
    .container { max-width: 1120px; }

    /* ---------- Typografie ---------- */
    h1, .h1 { font-size: 1.9rem; line-height: 1.25; margin: 0 0 var(--space-4); }
    h2, .h2 { font-size: 1.6rem; line-height: 1.3;  margin: var(--space-4) 0 var(--space-3); }
    h3, .h3 { font-size: 1.35rem; line-height: 1.3; margin: var(--space-3) 0 var(--space-2); }
    p { margin: 0 0 var(--space-3); color: var(--text); }
    .muted { color: var(--muted); }

    /* ---------- Cards & Panels ---------- */
    .card,
    .card-panel {
      background: var(--card) !important;
      color: var(--text) !important;
      border: 1px solid var(--card-border) !important;
      border-radius: var(--radius);
    }
    .card .card-content,
    .card-panel .card-content { padding: var(--space-5); }
    .card .card-title { color: var(--text) !important; margin: 0; font-weight: 700; }

    /* Card header: consistent title bar with bottom divider */
    .card .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: var(--space-3);
      padding: var(--space-5) var(--space-5) var(--space-3);
      border-bottom: 1px solid var(--card-border);
    }
    .card .card-header .title {
      display: inline-flex;
      align-items: center;
      gap: var(--space-2);
      min-width: 0; /* for truncation */
      font-size: 1.15rem;
      line-height: 1.3;
      font-weight: 700;
    }
    .card .card-header .title .material-icons {
      font-size: 20px;
      color: var(--muted);
    }
    .card .card-header .title .text {
      display: inline-block;
      max-width: 100%;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .card .card-header .actions {
      display: inline-flex;
      gap: var(--space-2);
      flex-shrink: 0;
    }
    .card .card-header + .card-content {
      padding-top: var(--space-3);
    }

    /* Backward-compat: when view still uses .card-title inside .card-content */
    .card .card-content > .card-title:first-child {
      display: block;
      font-size: 1.15rem;
      font-weight: 700;
      padding-bottom: var(--space-3);
      margin-bottom: var(--space-3);
      border-bottom: 1px solid var(--card-border);
    }

    .card.small { min-height: 220px; }

    /* ---------- Forms ---------- */
    .input-field { margin-bottom: var(--space-4); }
    .input-field input[type="text"],
    .input-field input[type="password"],
    .materialize-textarea {
      color: var(--text) !important;
      padding-bottom: 6px;
    }
    .input-field input::placeholder,
    .materialize-textarea::placeholder { color: var(--muted) !important; opacity: 1; }
    .input-field label { color: var(--muted) !important; }
    .input-field input:focus + label,
    .materialize-textarea:focus + label { color: var(--brand) !important; }
    .input-field input:focus,
    .materialize-textarea:focus { border-bottom: 1px solid var(--brand) !important; box-shadow: 0 1px 0 0 var(--brand) !important; }

    /* Select */
    .select-wrapper input.select-dropdown {
      color: var(--text) !important;
      background: transparent !important;
      border-bottom: 1px solid var(--card-border) !important;
      padding-bottom: 6px;
    }
    .dropdown-content.select-dropdown { background: var(--card) !important; }
    .dropdown-content.select-dropdown li > span { color: var(--text) !important; }
    .dropdown-content.select-dropdown li.active > span { color: #fff !important; background: var(--brand) !important; }

    /* ---------- Buttons ---------- */
    .btn, .btn-small, .btn-large { border-radius: 8px; }
    .btn, .btn-small { height: 36px; line-height: 36px; }
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

    /* ---------- Fokus sichtbar ---------- */
    :is(a, button, [role="button"], input, select, textarea):focus {
      outline: var(--focus) solid var(--brand);
      outline-offset: 2px;
    }
    .sidenav a:focus { background: var(--table-row-hover); }

    /* ---------- Tables ---------- */
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.striped { width: 100%; border-collapse: separate; border-spacing: 0; background: transparent; }
    table.striped thead { background: var(--table-head); }
    table.striped thead th {
      color: var(--text); font-weight: 600; border-bottom: 1px solid var(--card-border);
      padding: 12px 16px;
    }
    table.striped tbody tr:nth-child(odd)   { background-color: var(--table-row-odd); }
    table.striped tbody tr:nth-child(even)  { background-color: var(--table-row-even); }
    table.striped tbody tr:hover            { background-color: var(--table-row-hover); }
    table.striped td, table.striped th      { border-bottom: 1px solid var(--card-border); }
    table.striped td { padding: 12px 16px; }

    @media (max-width: 600px) {
      main { padding-top: var(--space-4); padding-bottom: var(--space-4); }
      h1 { font-size: 1.6rem; }
      h2 { font-size: 1.35rem; }
      .card .card-content, .card-panel .card-content { padding: var(--space-4); }
      .card .card-header { padding: var(--space-4) var(--space-4) var(--space-3); }
      table.striped th, table.striped td { padding: 10px 12px; }
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
