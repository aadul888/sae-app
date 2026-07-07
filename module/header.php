<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$module_root_name = isset($mod) ? explode('/', (string)$mod)[0] : 'home';
$home_logo_file = 'logoweb1.png';
$home_logo_local = __DIR__ . '/../content/' . $home_logo_file;
$home_logo_v = @filemtime($home_logo_local) ?: time();
$home_logo_src = (isset($base_url) ? $base_url : './') . 'content/' . $home_logo_file . '?v=' . $home_logo_v;
$body_class = 'module-' . preg_replace('/[^a-z0-9\-]/i', '-', $module_root_name);
if ($module_root_name === 'home') {
  $body_class .= ' home-page';
} elseif ($module_root_name === 'spmb') {
  $body_class .= ' spmb-page';
}
// Landing modules that share the same layout components (sae-hero, sae-kpi-strip, sae-nisn-panel, etc.)
// also get .module-home class so all CSS scoped to body.module-home applies automatically
$landing_modules = ['home', 'absensi', 'agenda', 'realtime', 'tentang', 'kelulusan', 'tamu'];
if (in_array($module_root_name, $landing_modules)) {
  $body_class .= ' module-home';
}

$public_fullpage_modules = ['home', 'absensi', 'realtime', 'agenda', 'tentang', 'kelulusan', 'login', 'nisn', 'registrasi', 'tamu'];

$appSiteName = trim((string)($site_name ?? ''));
if ($appSiteName === '') {
  $appSiteName = defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Smart Apps Education';
}
$appVersion = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';
$appYear = defined('SAE_APP_YEAR') ? SAE_APP_YEAR : date('Y');
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="© <?php echo htmlspecialchars((string)$appYear); ?> <?php echo htmlspecialchars($appSiteName); ?> | <?php echo htmlspecialchars($appVersion); ?>">
  <meta name="author" content="smakpalapik">
  <title><?php echo htmlspecialchars($appSiteName); ?> <?php echo htmlspecialchars($appVersion); ?></title>
  <!-- Ensure relative asset paths in modules resolve correctly by setting base URL -->
  <?php if (isset($base_url) && $base_url): ?>
    <base href="<?php echo rtrim($base_url, '/') . '/'; ?>">
  <?php endif; ?>
  <!-- Favicon -->
  <link rel="icon" href="content/<?php echo isset($site_favicon) ? $site_favicon : 'favicon.png'; ?>" type="image/png">
  <!-- Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
  <!-- Icons -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>admin/assets/vendor/nucleo/css/nucleo.css" type="text/css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>admin/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css" type="text/css">
  <!-- Page plugins -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>admin/assets/vendor/timepicker/bootstrap-timepicker.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>admin/assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>admin/assets/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>admin/assets/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>admin/assets/vendor/Magnific-Popup/magnific-popup.css">
  <!-- Argon CSS -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>module/assets/css/app.css" type="text/css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>module/assets/css/argon.css" type="text/css">
  <!-- Global module stylesheet (merged: modules-unified, fab, absensi, style, home-style) -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>module/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>" type="text/css">
  <?php // tamu.css dan spmb.css dinonaktifkan sementara sesuai kebutuhan saat ini. ?>
  <!-- Dark mode: terapkan tema sebelum render untuk mencegah flash -->
  <script>(function(){var t=localStorage.getItem('sae-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})()</script>
</head>

<?php
  $fullpage_modules = $public_fullpage_modules;
  $is_fullpage = in_array($module_root_name, $fullpage_modules);
?>

<body class="<?php echo trim($body_class . ($is_fullpage ? ' layout-fullpage' : '')); ?>">
  <!-- Page wrapper for main content -->
  <div class="main-content"><?php
      if ($module_root_name === 'registrasi'):
    ?>
    <?php
      elseif (in_array($module_root_name, $public_fullpage_modules)):
        $public_nav_links = [
          'home'      => ['label' => 'Home',      'url' => $base_url . 'home/'],
          'absensi'   => ['label' => 'Absensi',   'url' => $base_url . 'absensi/'],
          'agenda'    => ['label' => 'Agenda',    'url' => $base_url . 'agenda/'],
          'kelulusan' => ['label' => 'Kelulusan', 'url' => $base_url . 'kelulusan/'],
          'tamu'      => ['label' => 'Buku Tamu', 'url' => $base_url . 'tamu/'],
          'realtime'  => ['label' => 'Monitor',   'url' => $base_url . 'realtime/'],
          'tentang'   => ['label' => 'Tentang',   'url' => $base_url . 'tentang/'],
          'login'     => ['label' => 'Login',     'url' => $base_url . 'login/'],
        ];
    ?>
      <?php
        $nav_items = [
          'home'      => ['label' => 'Home',      'url' => $base_url . 'home/'],
          'absensi'   => ['label' => 'Absensi',   'url' => $base_url . 'absensi/'],
          'agenda'    => ['label' => 'Agenda',    'url' => $base_url . 'agenda/'],
          'kelulusan' => ['label' => 'Kelulusan', 'url' => $base_url . 'kelulusan/'],
          'realtime'  => ['label' => 'Monitor',   'url' => $base_url . 'realtime/'],
          'tamu'      => ['label' => 'Buku Tamu', 'url' => $base_url . 'tamu/'],
          'tentang'   => ['label' => 'Tentang',   'url' => $base_url . 'tentang/'],
        ];
      ?>
      <?php if (in_array($module_root_name, ['home', 'absensi', 'agenda', 'realtime', 'tentang', 'kelulusan', 'login', 'nisn', 'tamu'])): ?>
        <nav class="sae-home-nav" aria-label="Navigasi utama SAE">
          <div class="sae-home-nav-inner container-fluid">
            <a class="sae-home-brand" href="<?php echo $base_url; ?>home/" aria-label="Kembali ke beranda SAE">
              <img src="<?php echo $home_logo_src; ?>" alt="Logo SAE" class="sae-home-brand-logo">
              <span class="sae-home-brand-text"><?php echo htmlspecialchars($appSiteName); ?></span>
            </a>
            <div class="sae-home-nav-links" role="navigation" aria-label="Menu utama">
              <?php foreach ($nav_items as $key => $item): ?>
                <a href="<?php echo $item['url']; ?>"
                   class="sae-home-nav-link<?php echo $module_root_name === $key ? ' is-active' : ''; ?>"
                   <?php echo $module_root_name === $key ? 'aria-current="page"' : ''; ?>>
                  <?php echo $item['label']; ?>
                </a>
              <?php endforeach; ?>
              <a href="<?php echo $base_url; ?>login/" class="sae-home-nav-link sae-home-nav-link--cta">Login</a>
            </div>
            <div class="sae-home-nav-controls">
              <button class="sae-nav-theme-btn" id="saeThemeToggle" type="button" title="Ganti tema" aria-label="Ganti tema terang/gelap">
                <i class="fas fa-moon" id="saeThemeIcon" aria-hidden="true"></i>
              </button>
              <button class="sae-nav-hamburger" id="saeNavHamburger" type="button"
                      aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="saeMobileMenu">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
              </button>
            </div>
          </div>
          <div class="sae-mobile-menu" id="saeMobileMenu" aria-hidden="true">
            <?php foreach ($nav_items as $key => $item): ?>
              <a href="<?php echo $item['url']; ?>"
                 class="sae-mobile-link<?php echo $module_root_name === $key ? ' is-active' : ''; ?>"
                 <?php echo $module_root_name === $key ? 'aria-current="page"' : ''; ?>>
                <?php echo $item['label']; ?>
              </a>
            <?php endforeach; ?>
            <a href="<?php echo $base_url; ?>login/" class="sae-mobile-link sae-mobile-link--cta">Login</a>
          </div>
        </nav>
      <?php else: ?>
        <nav class="sae-public-nav" aria-label="Navigasi modul SAE">
          <div class="sae-public-nav-inner container-fluid">
            <a class="sae-public-brand" href="<?php echo $base_url; ?>home/">
              <i class="fas fa-school"></i>
              <span><?php echo htmlspecialchars($appSiteName); ?></span>
            </a>
            <div class="sae-public-links" role="navigation" aria-label="Menu modul publik">
              <?php foreach ($public_nav_links as $key => $item):
                if ($key === 'login') continue; ?>
                <a href="<?php echo $item['url']; ?>"
                   class="sae-public-link<?php echo $module_root_name === $key ? ' is-active' : ''; ?>"
                   <?php echo $module_root_name === $key ? 'aria-current="page"' : ''; ?>>
                  <?php echo $item['label']; ?>
                </a>
              <?php endforeach; ?>
              <a href="<?php echo $base_url; ?>login/"
                 class="sae-public-link sae-public-link--cta<?php echo $module_root_name === 'login' ? ' is-active' : ''; ?>">
                Login
              </a>
            </div>
            <div class="sae-nav-controls">
              <button class="sae-nav-theme-btn" id="saeThemeToggle" type="button" title="Ganti tema" aria-label="Ganti tema terang/gelap">
                <i class="fas fa-moon" id="saeThemeIcon" aria-hidden="true"></i>
              </button>
              <button class="sae-nav-hamburger" id="saeNavHamburger" type="button"
                      aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="saeMobileMenu">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
              </button>
            </div>
          </div>
          <div class="sae-mobile-menu" id="saeMobileMenu" aria-hidden="true">
            <?php foreach ($public_nav_links as $key => $item):
              if ($key === 'login') continue; ?>
              <a href="<?php echo $item['url']; ?>"
                 class="sae-mobile-link<?php echo $module_root_name === $key ? ' is-active' : ''; ?>"
                 <?php echo $module_root_name === $key ? 'aria-current="page"' : ''; ?>>
                <?php echo $item['label']; ?>
              </a>
            <?php endforeach; ?>
            <a href="<?php echo $base_url; ?>login/" class="sae-mobile-link sae-mobile-link--cta">Login</a>
          </div>
        </nav>
      <?php endif; ?>
      <script>
      (function(){
        var icon = document.getElementById('saeThemeIcon');
        if (icon) icon.className = document.documentElement.getAttribute('data-theme') === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        var btn = document.getElementById('saeThemeToggle');
        if (btn) btn.addEventListener('click', function(){
          var cur = document.documentElement.getAttribute('data-theme') || 'light';
          var next = cur === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-theme', next);
          localStorage.setItem('sae-theme', next);
          var ic = document.getElementById('saeThemeIcon');
          if (ic) ic.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
        var ham = document.getElementById('saeNavHamburger');
        var mob = document.getElementById('saeMobileMenu');
        if (ham && mob) {
          ham.addEventListener('click', function(e){
            e.stopPropagation();
            var open = mob.classList.toggle('open');
            ham.classList.toggle('open', open);
            ham.setAttribute('aria-expanded', String(open));
            mob.setAttribute('aria-hidden', String(!open));
          });
          document.addEventListener('click', function(e){
            if (!ham.contains(e.target) && !mob.contains(e.target)) {
              mob.classList.remove('open'); ham.classList.remove('open');
              ham.setAttribute('aria-expanded','false'); mob.setAttribute('aria-hidden','true');
            }
          });
        }
      })();
      </script>
    <?php else: ?>
      <nav class="navbar navbar-top navbar-horizontal navbar-expand-md navbar-dark">
        <div class="container px-4">
          <a class="navbar-brand" href="<?php echo $base_url; ?>"></a>
        </div>
      </nav>
    <?php endif; ?>