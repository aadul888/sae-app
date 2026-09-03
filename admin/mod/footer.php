<?php
if (empty($connection)) {
    header('location:./');
    exit;
}

$mod = isset($_GET['mod']) ? htmlentities($_GET['mod']) : 'home';
$appSiteName = trim((string)($site_name ?? ''));
if ($appSiteName === '') {
    $appSiteName = defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Smart Apps Education';
}

// Ambil versi terbaru dari setting.app_version
// Format: angka saja (semester_id) atau Major.Minor.Patch (20252.1.a)
$vQuery = $connection->query("SELECT app_version FROM setting LIMIT 1");
$appVersion = '';
if ($vQuery && $vQuery->num_rows > 0) {
    $vRow = $vQuery->fetch_assoc();
    $ver = trim($vRow['app_version'] ?? '');
    if ($ver !== '') {
        $appVersion = $ver;
    }
}
if (empty($appVersion)) {
    // Belum ada deploy/commit, tampilkan semester_id (Major) saja
    $appVersion = defined('SAE_SEMESTER_ID') ? SAE_SEMESTER_ID : (defined('SAE_VERSION') ? SAE_VERSION : 'v5.0');
}

// Ambil commit hash terbaru
$lastCommitHash = '';
$chkQuery = $connection->query("SELECT last_deploy_commit FROM setting WHERE site_id=1 LIMIT 1");
if ($chkQuery && $chkQuery->num_rows > 0) {
    $chkRow = $chkQuery->fetch_assoc();
    $hash = trim($chkRow['last_deploy_commit'] ?? '');
    if ($hash !== '') {
        $lastCommitHash = substr($hash, 0, 7);
    }
}

$appYear = defined('SAE_APP_YEAR') ? SAE_APP_YEAR : date('Y');
$hasAbsensiMenu = function_exists('can_see_any') ? can_see_any(array(9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19)) : false;
$hasPengaturanMenu = function_exists('can_see_any') ? can_see_any(array(37, 38, 39, 40, 41, 43, 44)) : false;

function asset_script_ver($path)
{
    // Prioritaskan filemtime agar cache-bust akurat per file, tidak bergantung
    // pada setting.app_version yang hanya terupdate lewat modul deploy — jadi
    // tetap valid walau kode diperbarui manual (git pull) di server.
    if (file_exists($path)) {
        return filemtime($path);
    }
    return defined('SAE_VERSION') ? SAE_VERSION : time();
}
?>
    <footer class="footer">
        <div class="container-fluid d-none d-md-flex align-items-center">
            <div class="footer-copyright">
                <span class="font-weight-bold text-primary">&copy; <?php echo htmlspecialchars((string)$appYear); ?> <?php echo htmlspecialchars($appSiteName); ?></span>
                <span class="footer-version"><?php echo htmlspecialchars($appVersion); ?></span>
            </div>
            <div class="footer-right d-flex align-items-center ml-auto">
                <small class="footer-server-info text-muted mr-3">
                    <i class="fas fa-server mr-1"></i>
                    Server Time: <span class="font-weight-bold"><?php echo date('d F Y, H:i:s'); ?></span>
                    <span class="mx-1">|</span>
                    Status: <span class="badge badge-success badge-sm">ONLINE</span>
                    <span class="mx-1">|</span>
                    <i class="fas fa-users mr-1"></i>
                    Admin Panel
                </small>
                <ul class="nav nav-footer">
                    <li class="nav-item">
                        <a href="#" class="nav-link" target="_blank" rel="noopener">
                            <i class="fas fa-info-circle mr-1"></i>Bantuan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" target="_blank" rel="noopener">
                            <i class="fas fa-book mr-1"></i>Dokumentasi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" target="_blank" rel="noopener">
                            <i class="fas fa-envelope mr-1"></i>Kontak
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </footer>
    <nav class="mobile-footer-nav d-xl-none" aria-label="Navigasi utama mobile">
        <?php if (function_exists('can_see') && can_see(1)): ?>
            <a href="./" class="mobile-footer-link" title="Home">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
        <?php endif; ?>
        <?php if (function_exists('can_see') && can_see(45)): ?>
            <a href="./guru" class="mobile-footer-link" title="Guru">
                <i class="fas fa-user-tie"></i>
                <span>Guru</span>
            </a>
        <?php endif; ?>
        <?php if (function_exists('can_see') && can_see(2)): ?>
            <a href="./portal-gtk" class="mobile-footer-link mobile-footer-link-featured" title="Portal GTK">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Portal GTK</span>
            </a>
        <?php endif; ?>
        <?php if (function_exists('can_see') && can_see(23)): ?>
            <a href="./e-izin" class="mobile-footer-link" title="E-Izin">
                <i class="fas fa-file-signature"></i>
                <span>E-Izin</span>
            </a>
        <?php endif; ?>
        <?php if (function_exists('can_see') && can_see(18)): ?>
            <a href="./laporan-absensi-kelas" class="mobile-footer-link" title="Laporan Absensi Kelas">
                <i class="fas fa-chart-bar"></i>
                <span>Lap. Kelas</span>
            </a>
        <?php endif; ?>
    </nav>
</div>

<script src="assets/vendor/viewerjs/viewer.min.js"></script>
<script src="assets/vendor/jquery/dist/jquery.min.js"></script>
<script src="assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/js-cookie/js.cookie.js"></script>
<script src="assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
<script src="assets/vendor/select2/dist/js/select2.min.js"></script>
<script src="assets/vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<script src="assets/vendor/timepicker/bootstrap-timepicker.js"></script>
<script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/vendor/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/vendor/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="assets/vendor/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
<script src="assets/vendor/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="assets/vendor/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="assets/vendor/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="assets/vendor/datatables.net-select/js/dataTables.select.min.js"></script>
<script src="assets/vendor/Magnific-Popup/jquery.magnific-popup.min.js"></script>
<script src="assets/js/jquery.validate.min.js"></script>
<script src="assets/js/sweetalert.min.js"></script>
<script>
// CSRF token global untuk semua modul
window.CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
// Kirim token CSRF otomatis di semua request AJAX (jQuery)
if (window.jQuery) {
  jQuery.ajaxSetup({
  beforeSend: function (xhr, settings) {
    if (window.CSRF_TOKEN && !settings.crossDomain) {
      xhr.setRequestHeader('Csrf-Token', window.CSRF_TOKEN);
    }
  },
  dataFilter: function (data, type) {
    if (type === 'json' || type === 'jsonp') {
      var t = (data || '').trim();
      if (t === '' || t.charAt(0) !== '{') {
        return JSON.stringify({ data: [], draw: 0, recordsTotal: 0, recordsFiltered: 0 });
      }
    }
    return data;
  }
});
}
</script>

<?php
// Cek apakah admin saat ini adalah superadmin (level_id=1)
$isSuperAdmin = false;
if (isset($level_id) && $level_id == '1') {
    $isSuperAdmin = true;
} elseif (isset($current_level) && $current_level === 1) {
    $isSuperAdmin = true;
} elseif (!empty($_COOKIE['ADMIN_KEY'])) {
    $tmpAid = @epm_decode($_COOKIE['ADMIN_KEY']);
    if (!empty($tmpAid)) {
        $tmpQ = $connection->query("SELECT level_id FROM admin WHERE admin_id='" . intval($tmpAid) . "' AND active='Y' LIMIT 1");
        if ($tmpQ && $tmpQ->num_rows > 0) {
            $tmpR = $tmpQ->fetch_assoc();
            $isSuperAdmin = ($tmpR['level_id'] == '1');
        }
    }
}
?>

<script>
(function(){
  // Auto-check update di background (hanya superadmin)
  <?php if ($isSuperAdmin): ?>
  // Skip jika sedang di halaman pembaharuan itu sendiri
  if (window.location.pathname.indexOf('/pembaharuan') === -1) {
  var x = new XMLHttpRequest();
  x.open('GET', './mod/pembaharuan/proses.php?action=check_remote_commits&csrf=' + encodeURIComponent(window.CSRF_TOKEN || '') + '&_=' + Date.now(), true);
  x.onload = function() {
    if (x.status !== 200) return;
    try {
      var d = JSON.parse(x.responseText);
      if (d.update_available) {
        var b = document.createElement('div');
        b.className = 'alert alert-info alert-dismissible fade show rounded-0 text-center mb-0';
        b.style.borderLeft = 'none'; b.style.borderRight = 'none';
        b.innerHTML = '<i class="fas fa-sync-alt mr-1"></i> <strong>Pembaruan tersedia!</strong> '
          + '<a href="./pembaharuan" class="alert-link font-weight-bold">Klik di sini</a> untuk memproses update. '
          + '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        var p = document.getElementById('panel');
        if (p) p.insertBefore(b, p.firstChild);
      }
    } catch(e) {}
  };
  x.send();
  } // end skip if pembaharuan
  <?php endif; ?>
})();
</script>
<script src="assets/js/argon.js?v=<?php echo asset_script_ver('assets/js/argon.js'); ?>"></script>
<script src="assets/vendor/leatfet/leaflet.js"></script>
<script src="assets/js/demo.js?v=<?php echo asset_script_ver('assets/js/demo.js'); ?>"></script>
<script src="assets/js/tab-responsive.js?v=<?php echo asset_script_ver('assets/js/tab-responsive.js'); ?>"></script>
<?php if ($mod !== 'home' && file_exists('mod/' . $mod . '/scripts.js')): ?>
    <script src="mod/<?php echo htmlspecialchars($mod); ?>/scripts.js?v=<?php echo asset_script_ver('mod/' . $mod . '/scripts.js'); ?>"></script>
<?php endif; ?>
</body>
</html>

