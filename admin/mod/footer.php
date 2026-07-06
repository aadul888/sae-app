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
$appVersion = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';
$appYear = defined('SAE_APP_YEAR') ? SAE_APP_YEAR : date('Y');
$hasAbsensiMenu = function_exists('can_see_any') ? can_see_any(array(9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19)) : false;
$hasPengaturanMenu = function_exists('can_see_any') ? can_see_any(array(37, 38, 39, 40, 41, 43, 44)) : false;

function asset_script_ver($path)
{
    return file_exists($path) ? filemtime($path) : time();
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
<script src="assets/js/argon.js?v=<?php echo asset_script_ver('assets/js/argon.js'); ?>"></script>
<script src="assets/vendor/leatfet/leaflet.js"></script>
<script src="assets/js/demo.js?v=<?php echo asset_script_ver('assets/js/demo.js'); ?>"></script>
<script src="assets/js/tab-responsive.js?v=<?php echo asset_script_ver('assets/js/tab-responsive.js'); ?>"></script>
<?php if ($mod !== 'home' && file_exists('mod/' . $mod . '/scripts.js')): ?>
    <script src="mod/<?php echo htmlspecialchars($mod); ?>/scripts.js?v=<?php echo asset_script_ver('mod/' . $mod . '/scripts.js'); ?>"></script>
<?php endif; ?>
</body>
</html>
