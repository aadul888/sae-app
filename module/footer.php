<?php if (empty($connection)) {
  $mod = htmlentities($_GET['mod'] ?? 'home');
  if ($mod !== 'konfigurasi') {
    header('location:./');
    exit;
  }

  // Halaman konfigurasi pra-instalasi: koneksi DB belum ada, jadi blok utama
  // footer dilewati. Tetap muat jQuery + scripts.js milik modul konfigurasi
  // agar toggle password / tombol salin tetap berfungsi.
  $_konfigurasi_base = rtrim(isset($base_url) ? $base_url : './', '/') . '/';
  echo '
    <script src="' . $_konfigurasi_base . 'admin/assets/vendor/jquery/dist/jquery.min.js"></script>';
  $_konfigurasi_script = './module/konfigurasi/scripts.js';
  if (file_exists($_konfigurasi_script)) {
    echo '
    <script src="' . $_konfigurasi_base . 'module/konfigurasi/scripts.js?v=' . (defined('SAE_VERSION') ? SAE_VERSION : filemtime($_konfigurasi_script)) . '"></script>';
  }
  echo '
</body>
</html>';
} else {
  $mod = "home";
  $mod = htmlentities($_GET['mod'] ?? 'home');
  // Get number
  function get_numbers()
  {
    for ($i = 1; $i <= 500; $i++) {
      yield $i;
    }
  }
  $result = get_numbers();
  function convertkb($size)
  {
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    if (!is_numeric($size) || $size <= 0) {
      return '0 b';
    }
    $i = (int) floor(log($size, 1024));
    if ($i < 0) $i = 0;
    if ($i >= count($unit)) $i = count($unit) - 1;
    return round($size / pow(1024, $i), 2) . ' ' . $unit[$i];
  }

  // Check if current module is a full-page module (home, absensi, realtime)
  $module_root_footer = explode('/', (string)$mod)[0];
  $is_fullpage_module = in_array($module_root_footer, ['home', 'absensi', 'realtime', 'agenda', 'tentang', 'login', 'nisn', 'konfigurasi', 'tamu']);

  $appSiteName = trim((string)($site_name ?? ''));
  if ($appSiteName === '') {
    $appSiteName = defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Smart Apps Education';
  }
  $appVersion = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';
  $appYear = defined('SAE_APP_YEAR') ? SAE_APP_YEAR : date('Y');

  echo '
    </div>
  </div>';

  if ($is_fullpage_module) {
    // Full-page modules: compact footer matching module design
    echo '
  <footer class="footer pt-0 module-footer-public module-footer-compact">
    <div class="container-fluid">
      <div class="row align-items-center justify-content-center">
        <div class="col-12">
          <div class="copyright text-center module-footer-copy">
            <span class="text-muted small module-footer-links">
              <span>&copy; ' . htmlspecialchars((string)$appYear) . '</span>
              <a href="#" class="font-weight-bold text-primary">' . htmlspecialchars($appSiteName) . '</a>
              ' . ($appVersion ? '<span class="footer-version ml-1">' . htmlspecialchars($appVersion) . '</span>' : '') . '
              <span>|</span>
              <a href="' . $base_url . 'tentang" class="text-primary">Tentang Aplikasi</a>
              <span>|</span>
              <a href="' . $base_url . 'tentang?tab=privasi" class="text-primary">Privasi &amp; Kebijakan</a>
              <span>|</span>
              <span>Developed by <a href="https://www.instagram.com/aadul888/" target="_blank" class="text-primary">aadul888</a></span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </footer>';
  } else {
    // Other modules: original Argon footer
    echo '
  <footer class="footer pt-0 mt-4 module-footer-public">
    <div class="container-fluid">
      <div class="row align-items-center justify-content-center">
        <div class="col-12">
          <div class="copyright text-center">
            <div class="d-flex align-items-center justify-content-center flex-wrap">
              <span class="text-muted small">
                &copy; ' . htmlspecialchars((string)$appYear) . '
                <a href="#" class="font-weight-bold text-primary ml-1" target="_blank">' . htmlspecialchars($appSiteName) . '</a>
                ' . ($appVersion ? '<span class="footer-version ml-1">' . htmlspecialchars($appVersion) . '</span>' : '') . '
              </span>
              <span class="text-muted mx-2">|</span>
              <span class="text-muted small">
                Developed by <a href="https://www.instagram.com/aadul888/" target="_blank" class="text-primary font-weight-500">aadul888</a>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>';
  }
  ?>
  
  <?php echo '
    <script src="' . $base_url . 'admin/assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="' . $base_url . 'admin/assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="' . $base_url . 'admin/assets/vendor/js-cookie/js.cookie.js"></script>
    <script src="' . $base_url . 'admin/assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
    <script src="' . $base_url . 'admin/assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
    <!-- Optional JS -->
    <script src="' . $base_url . 'admin/assets/vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="' . $base_url . 'admin/assets/vendor/Magnific-Popup/jquery.magnific-popup.min.js"></script>
    <script src="' . $base_url . 'module/assets/js/jquery.validate.min.js"></script>
    <script src="' . $base_url . 'module/assets/js/sweetalert.min.js"></script>
    <!-- Chart.js CDN (v2 for Argon) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <!-- Argon JS -->
    <script src="' . $base_url . 'module/assets/js/argon.js?v=1.1.0"></script>
    <script src="' . $base_url . 'module/assets/js/demo.min.js"></script>';
  $module_script = './module/' . $mod . '/scripts.js';
  if (!file_exists($module_script)) {
    $module_root = explode('/', (string)$mod)[0];
    $module_script = './module/' . $module_root . '/scripts.js';
  }
  if (!file_exists($module_script)) {
    $module_script = './module/home/scripts.js';
  }

  $script_path = str_replace('./', '', $module_script);
  $js_version = defined('SAE_VERSION') ? SAE_VERSION : $commit_hash;
  echo '    <script src="' . $base_url . $script_path . '?v=' . substr((string)$js_version, 0, 16) . '"></script>
</body>
</html>';
}
