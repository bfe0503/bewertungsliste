<?php
$baseUri   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$flashes   = \App\Core\Flash::consume();
$isLogged  = \App\Core\Auth::check();
$logoutToken = $isLogged ? \App\Core\Csrf::token('logout') : null;
$isAdmin   = !empty($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;
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
    :root{
      --bg:#f5f6f7; --text:#1f2937; --muted:#6b7280;
      --card:#ffffff; --card-border:rgba(0,0,0,.08);
      --brand:#00796b; --brand-hover:#00695c;
      --table-head:#edf2f7; --table-row-odd:rgba(0,0,0,.02); --table-row-even:transparent; --table-row-hover:rgba(0,0,0,.05);
      --badge-public:#26a69a; --badge-private:#9e9e9e;
      --danger:#e53935; --danger-hover:#c62828;
      --toast-bg:#323232; --toast-text:#fff;
      --toggle-border:rgba(0,0,0,.2); --chrome-line:rgba(0,0,0,.08);
      --space-1:6px; --space-2:10px; --space-3:14px; --space-4:18px; --space-5:24px; --radius:10px; --focus:2px;
      --input-bg:#ffffff; --input-border:rgba(0,0,0,.12);
    }
    .theme-dark{
      --bg:#0f1115; --text:#e9eef3; --muted:#9aa4af;
      --card:#171a21; --card-border:rgba(255,255,255,.08);
      --brand:#129383; --brand-hover:#0f7d70;
      --table-head:#0f1a22; --table-row-odd:rgba(255,255,255,.03); --table-row-even:transparent; --table-row-hover:rgba(255,255,255,.07);
      --badge-public:#2fbba9; --badge-private:#6b7280;
      --danger:#ef5350; --danger-hover:#d32f2f;
      --toast-bg:#0f1115; --toast-text:#e9eef3;
      --toggle-border:rgba(255,255,255,.35); --chrome-line:rgba(255,255,255,.08);
      --input-bg:#10151c; --input-border:rgba(255,255,255,.10);
    }

    /* ---------- Chrome ---------- */
    html,body{background:var(--bg);color:var(--text)}
    main{min-height:70vh;padding:var(--space-5) 0}
    .brand-logo{font-weight:600;color:var(--text)!important}
    nav.teal{background:var(--bg)!important;border-bottom:1px solid var(--chrome-line);box-shadow:none}
    .nav-wrapper a,nav a{color:var(--text)!important}
    .page-footer.teal{background:var(--bg)!important;color:var(--muted)!important;border-top:1px solid var(--chrome-line)}
    .container{max-width:1120px}

    /* ---------- Typography ---------- */
    h1{font-size:1.9rem;line-height:1.25;margin:0 0 var(--space-4)}
    h2{font-size:1.6rem;line-height:1.3;margin:var(--space-4) 0 var(--space-3)}
    h3{font-size:1.35rem;line-height:1.3;margin:var(--space-3) 0 var(--space-2)}
    p{margin:0 0 var(--space-3)}

    /* ---------- Cards ---------- */
    .card,.card-panel{background:var(--card)!important;color:var(--text)!important;border:1px solid var(--card-border)!important;border-radius:var(--radius)}
    .card .card-content,.card-panel .card-content{padding:var(--space-5)}
    .card .card-title{color:var(--text)!important;margin:0;font-weight:700}
    .card .card-header{display:flex;align-items:center;justify-content:space-between;gap:var(--space-3);padding:var(--space-5) var(--space-5) var(--space-3);border-bottom:1px solid var(--card-border)}
    .card .card-header .title{display:inline-flex;align-items:center;gap:var(--space-2);min-width:0;font-size:1.15rem;font-weight:700}
    .card .card-header .title .material-icons{font-size:20px;color:var(--muted)}
    .card .card-header .actions{display:inline-flex;gap:var(--space-2);flex-shrink:0}
    .card .card-header + .card-content{padding-top:var(--space-3)}
    .card .card-content>.card-title:first-child{display:block;font-size:1.15rem;font-weight:700;padding-bottom:var(--space-3);margin-bottom:var(--space-3);border-bottom:1px solid var(--card-border)}

    /* ---------- Forms ---------- */
    .input-field{margin:0 0 var(--space-4)!important}
    /* Labels als Block + einheitlicher Abstand => symmetrische Felder */
    .input-field > label{
      display:block!important;
      position:static!important;
      transform:none!important;
      left:auto!important; top:auto!important;
      margin:0 0 8px!important;
      color:var(--muted)!important;
      line-height:1.2!important;
    }
    .input-field input[type="text"],
    .input-field input[type="password"],
    .materialize-textarea,
    .select-wrapper input.select-dropdown{
      color:var(--text)!important;
      background:var(--input-bg)!important;
      border:1px solid var(--input-border)!important;
      border-radius:8px!important;
      padding:10px 12px!important;
      height:44px;
      box-shadow:none!important;
      -webkit-appearance:none; appearance:none;
      background-clip:padding-box!important;
    }
    .materialize-textarea{min-height:110px;height:auto}
    .input-field input::placeholder,.materialize-textarea::placeholder{color:var(--muted)!important;opacity:1}
    .input-field input:focus + label,
    .materialize-textarea:focus + label{color:var(--brand)!important}
    .input-field input:focus,
    .materialize-textarea:focus,
    .select-wrapper input.select-dropdown:focus{
      border-color:var(--brand)!important;
      box-shadow:0 0 0 2px color-mix(in oklab, var(--brand) 25%, transparent) !important;
    }

    /* Dark-Mode: Inputs sicher dunkel & lesbar (auch ohne Autofill) */
    .theme-dark .input-field input[type="text"],
    .theme-dark .input-field input[type="password"],
    .theme-dark .materialize-textarea,
    .theme-dark .select-wrapper input.select-dropdown{
      background:var(--input-bg)!important;
      border-color:var(--input-border)!important;
      -webkit-box-shadow:inset 0 0 0 1000px var(--input-bg) !important;
      box-shadow:inset 0 0 0 1000px var(--input-bg) !important;
      background-clip:padding-box!important;
      -webkit-text-fill-color:var(--text)!important;
      color:var(--text)!important;
      caret-color:var(--text)!important;
    }
    /* Dark-Mode: Autofill knallhart überschreiben */
    .theme-dark input:-webkit-autofill,
    .theme-dark input:-webkit-autofill:hover,
    .theme-dark input:-webkit-autofill:focus,
    .theme-dark input:autofill,
    .theme-dark input:autofill:hover,
    .theme-dark input:autofill:focus,
    .theme-dark input:-moz-autofill{
      -webkit-text-fill-color:var(--text)!important;
      color:var(--text)!important; caret-color:var(--text)!important;
      -webkit-box-shadow:inset 0 0 0 1000px var(--input-bg) !important;
      box-shadow:inset 0 0 0 1000px var(--input-bg) !important;
      background-color:var(--input-bg)!important;
      border:1px solid var(--input-border)!important;
      border-radius:8px!important;
      transition:background-color 9999s ease-in-out 0s !important;
    }

    .helper-text.muted{color:var(--muted)!important}

    /* ---------- Buttons ---------- */
    .btn,.btn-small,.btn-large{border-radius:8px}
    .btn,.btn-small{height:36px;line-height:36px}
    .btn .material-icons,.btn-small .material-icons{vertical-align:middle;line-height:inherit;margin-right:6px}
    .btn.icon-right .material-icons{margin-left:6px;margin-right:0}
    .btn-primary{background:var(--brand)!important;color:#fff!important}
    .btn-primary:hover,.btn-primary:focus{background:var(--brand-hover)!important}
    .btn-outline{background:transparent!important;color:var(--brand)!important;border:1px solid var(--brand)!important;box-shadow:none!important}
    .btn-outline:hover,.btn-outline:focus{background:var(--brand)!important;color:#fff!important}
    .btn-ghost{background:transparent!important;color:var(--text)!important;border:1px solid transparent!important}
    .btn-ghost:hover,.btn-ghost:focus{background:var(--table-row-hover)!important}
    .btn-danger,.btn.red{background:var(--danger)!important;color:#fff!important}
    .btn-danger:hover,.btn.red:hover,.btn-danger:focus,.btn.red:focus{background:var(--danger-hover)!important}
    .btn:disabled,.btn.disabled{opacity:.55!important;cursor:not-allowed!important;filter:grayscale(.2)}

    /* ---------- Tables ---------- */
    .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
    table.striped{width:100%;border-collapse:separate;border-spacing:0;background:transparent}
    table.striped thead{background:var(--table-head)}
    table.striped thead th{color:var(--text);font-weight:600;border-bottom:1px solid var(--card-border);padding:12px 16px}
    table.striped tbody tr:nth-child(odd){background-color:var(--table-row-odd)}
    table.striped tbody tr:nth-child(even){background-color:var(--table-row-even)}
    table.striped tbody tr:hover{background-color:var(--table-row-hover)}
    table.striped td,table.striped th{border-bottom:1px solid var(--card-border)}
    table.striped td{padding:12px 16px}

    @media (max-width:600px){
      main{padding:var(--space-4) 0}
      h1{font-size:1.6rem} h2{font-size:1.35rem}
      .card .card-content{padding:var(--space-4)}
      .card .card-header{padding:var(--space-4) var(--space-4) var(--space-3)}
      table.striped th,table.striped td{padding:10px 12px}
    }

    /* ---------- Badges ---------- */
    .badge{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-weight:600;letter-spacing:.2px;line-height:20px;height:22px}
    .badge .material-icons{font-size:16px;line-height:20px}
    .badge.green{background:var(--badge-public)!important;color:#fff!important}
    .badge.grey{background:var(--badge-private)!important;color:#fff!important}
    .badge.outline{background:transparent!important;color:var(--text)!important;border:1px solid var(--card-border)!important}
    .badge.teal{background:var(--brand)!important;color:#fff!important}
    .badge.red{background:var(--danger)!important;color:#fff!important}
    .badge.muted{background:transparent!important;color:var(--muted)!important;border:1px solid var(--card-border)!important}
    .badge.sm{height:18px;line-height:18px;padding:0 8px;font-size:12px}
    .badge.lg{height:26px;line-height:26px;padding:0 12px;font-size:14px}

    /* ---------- Sidenav ---------- */
    .sidenav{background:var(--card)!important;color:var(--text)!important}
    .sidenav a{color:var(--text)!important}
    .sidenav .subheader,.sidenav .user-view .name,.sidenav .user-view .email{color:var(--muted)!important}
    .sidenav .divider{background-color:var(--card-border)!important}
    .sidenav .material-icons{color:var(--text)!important}

    /* ---------- Focus (A11y) ---------- */
    :is(a,button,[role="button"],input,select,textarea):focus{outline:var(--focus) solid var(--brand);outline-offset:2px}
    .sidenav a:focus{background:var(--table-row-hover)}

    /* ---------- Theme toggle ---------- */
    .theme-toggle-btn{border:1px solid var(--toggle-border);border-radius:8px;padding:0 8px;background:transparent;display:inline-flex;align-items:center;height:32px;line-height:32px;color:var(--text)!important}
    .theme-toggle-btn i{vertical-align:middle;color:inherit}
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
              <button class="btn btn-small btn-primary" type="submit"><i class="material-icons left">logout</i>Abmelden</button>
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
          <button class="btn btn-primary" type="submit" style="width:100%"><i class="material-icons left">logout</i>Abmelden</button>
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
      M.Sidenav.init(document.querySelectorAll('.sidenav'));
    });

    (function () {
      const LS_KEY='theme'; const html=document.documentElement; const icon=document.getElementById('themeIcon');
      function apply(mode){ if(mode==='dark'){ html.classList.add('theme-dark'); if(icon) icon.textContent='light_mode'; } else { html.classList.remove('theme-dark'); if(icon) icon.textContent='dark_mode'; } }
      let mode=localStorage.getItem(LS_KEY) || ((matchMedia && matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');
      apply(mode);
      function toggle(){ mode=html.classList.contains('theme-dark')?'light':'dark'; localStorage.setItem(LS_KEY,mode); apply(mode); }
      const a=document.getElementById('themeToggle'), b=document.getElementById('themeToggleMobile');
      if(a) a.addEventListener('click',e=>{e.preventDefault();toggle()}); if(b) b.addEventListener('click',e=>{e.preventDefault();toggle()});
    })();

    <?php if (!empty($flashes)): ?>
    (function(){ const msgs=<?= json_encode($flashes, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>; msgs.forEach(m=>M.toast({html:m.message})); })();
    <?php endif; ?>
  </script>
</body>
</html>
