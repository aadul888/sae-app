<?php
function asset_ver($path)
{
    return file_exists($path) ? filemtime($path) : time();
}

$appSiteName = trim((string)($site_name ?? ''));
if ($appSiteName === '') {
    $appSiteName = defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Smart Apps Education';
}
$appVersion = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';
$appYear = defined('SAE_APP_YEAR') ? SAE_APP_YEAR : date('Y');

$siteLogo = isset($site_logo2) && !empty($site_logo2) ? $site_logo2 : 'logoweb2.png';
$siteLogoPath = '../content/' . $siteLogo;
$userAvatar = isset($current_user['avatar']) && $current_user['avatar'] !== '' ? $current_user['avatar'] : 'default.png';
$userFrontTitle = isset($current_user['gelar_depan']) ? trim(strip_tags($current_user['gelar_depan'])) : '';
$userFullName = isset($current_user['fullname']) ? trim(strip_tags($current_user['fullname'])) : 'Administrator';
$userBackTitle = isset($current_user['gelar_belakang']) ? trim(strip_tags($current_user['gelar_belakang'])) : '';
$userDisplayName = trim($userFrontTitle . ' ' . $userFullName);
if ($userBackTitle !== '') {
    $userDisplayName .= ', ' . $userBackTitle;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="© <?php echo htmlspecialchars((string)$appYear); ?> <?php echo htmlspecialchars($appSiteName); ?> | <?php echo htmlspecialchars($appVersion); ?>">
    <meta name="author" content="Creative Tim">
    <title><?php echo htmlspecialchars($appSiteName); ?> - Dashboard <?php echo htmlspecialchars($appVersion); ?></title>
    <link rel="icon" href="../content/<?php echo htmlspecialchars($site_favicon); ?>" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="assets/vendor/nucleo/css/nucleo.css?v=<?php echo asset_ver('assets/vendor/nucleo/css/nucleo.css'); ?>" type="text/css">
    <link rel="stylesheet" href="assets/vendor/@fortawesome/fontawesome-free/css/all.min.css?v=<?php echo asset_ver('assets/vendor/@fortawesome/fontawesome-free/css/all.min.css'); ?>" type="text/css">
    <link rel="stylesheet" href="assets/vendor/select2/dist/css/select2.min.css?v=<?php echo asset_ver('assets/vendor/select2/dist/css/select2.min.css'); ?>">
    <link rel="stylesheet" href="assets/vendor/timepicker/bootstrap-timepicker.min.css?v=<?php echo asset_ver('assets/vendor/timepicker/bootstrap-timepicker.min.css'); ?>">
    <link rel="stylesheet" href="assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css?v=<?php echo asset_ver('assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="assets/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css?v=<?php echo asset_ver('assets/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="assets/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css?v=<?php echo asset_ver('assets/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css'); ?>">
    <link rel="stylesheet" href="assets/vendor/Magnific-Popup/magnific-popup.css?v=<?php echo asset_ver('assets/vendor/Magnific-Popup/magnific-popup.css'); ?>">
    <link rel="stylesheet" href="assets/vendor/leatfet/leaflet.css?v=<?php echo asset_ver('assets/vendor/leatfet/leaflet.css'); ?>">
    <link rel="stylesheet" href="assets/css/argon.css?v=<?php echo asset_ver('assets/css/argon.css'); ?>" type="text/css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo asset_ver('assets/css/style.css'); ?>" type="text/css">
    <link rel="stylesheet" href="assets/vendor/viewerjs/viewer.min.css?v=<?php echo asset_ver('assets/vendor/viewerjs/viewer.min.css'); ?>">
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<div class="main-content" id="panel">
    <nav class="navbar navbar-top navbar-expand navbar-dark bg-primary border-bottom admin-topbar">
        <div class="container-fluid">
            <div class="collapse navbar-collapse d-flex align-items-center justify-content-between flex-wrap" id="navbarSupportedContent">
                <ul class="navbar-nav align-items-center admin-topbar-left">
                    <li class="nav-item d-xl-none mr-2">
                        <button class="btn nav-link p-0 border-0 shadow-none admin-topbar-toggle" type="button" data-action="sidenav-pin" data-target="#sidenav-main" aria-label="Buka menu utama" title="Menu utama">
                            <span class="admin-topbar-toggle-lines" aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    </li>
                    <li class="nav-item d-none d-xl-block">
                        <a class="nav-link" href="../" target="_blank" rel="noopener" role="button" title="Lihat Halaman Publik">
                            <i class="ni ni-laptop"></i>
                        </a>
                    </li>
                    <?php if (can_see(10)) { ?>
                        <li class="nav-item d-none d-xl-block">
                            <a class="nav-link" href="../absensi" target="_blank" rel="noopener" role="button" title="Sistem Absensi RFID">
                                <i class="fas fa-calendar-check text-white"></i>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <a class="navbar-brand admin-topbar-brand-mobile d-xl-none" href="./" title="Dashboard">
                    <img src="<?php echo htmlspecialchars($siteLogoPath); ?>?v=<?php echo asset_ver($siteLogoPath); ?>" alt="Logo">
                </a>
                <ul class="navbar-nav align-items-center ml-auto admin-topbar-user-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="media align-items-center">
                                <span class="avatar avatar-sm rounded-circle">
                                    <img src="assets/avatar/<?php echo htmlspecialchars($userAvatar); ?>" alt="Avatar pengguna">
                                </span>
                                <div class="media-body ml-2 d-none d-lg-block">
                                    <span class="mb-0 text-sm font-weight-bold text-white"><?php echo htmlspecialchars($userDisplayName); ?></span>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-header noti-title">
                                <h6 class="text-overflow m-0">Welcome!</h6>
                            </div>
                            <a href="./profile" class="dropdown-item">
                                <i class="ni ni-single-02"></i>
                                <span>My profile</span>
                            </a>
                            <a href="./setting" class="dropdown-item">
                                <i class="ni ni-settings-gear-65"></i>
                                <span>Settings</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout" class="dropdown-item">
                                <i class="ni ni-user-run"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php
    // Check for update availability from pembaharuan table
    $update_banner = '';
    if (isset($connection) && $connection && defined('SAE_VERSION')) {
      $q_rel = $connection->query("SELECT version, pembaharuan FROM pembaharuan ORDER BY release_date DESC LIMIT 1");
      if ($q_rel && $r_rel = $q_rel->fetch_assoc()) {
        if (version_compare(SAE_VERSION, $r_rel['version'], '<')) {
          $summary = htmlspecialchars(substr($r_rel['pembaharuan'] ?? 'Pembaruan tersedia', 0, 100));
          $update_banner = '
    <div class="container-fluid" style="margin-top:0">
      <div class="alert alert-info alert-dismissible fade show text-center mb-0 rounded-0" role="alert" style="border-radius:0 !important">
        <i class="fas fa-download mr-1"></i> <strong>Pembaruan tersedia v' . htmlspecialchars($r_rel['version']) . '!</strong> ' . $summary . '
        <a href="./lisensi_pembaruan" class="alert-link ml-2">Lihat &amp; Terapkan</a>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
    </div>';
        }
      }
    }
    echo $update_banner;
    ?>

    <!-- Header -->
