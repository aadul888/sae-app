<?php if (empty($connection)) {
    header('location:../');
    exit();
} else {
    $appSiteName = trim((string)($site_name ?? ''));
    if ($appSiteName === '') {
        $appSiteName = defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Smart Apps Education';
    }
    $appVersion = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';
    $appYear = defined('SAE_APP_YEAR') ? SAE_APP_YEAR : date('Y');

    $content_dir = realpath(__DIR__ . '/../../content');
    $header_logo = '';
    $logo_candidates = array(
        isset($site_logo2) ? trim((string)$site_logo2) : '',
        isset($site_logo) ? trim((string)$site_logo) : '',
        isset($site_favicon) ? trim((string)$site_favicon) : '',
        'logoweb1.png',
        'logo.png'
    );

    foreach ($logo_candidates as $candidate_logo) {
        if ($candidate_logo === '') {
            continue;
        }
        if ($content_dir && file_exists($content_dir . DIRECTORY_SEPARATOR . $candidate_logo)) {
            $header_logo = $candidate_logo;
            break;
        }
    }

    if ($header_logo === '' && !empty($site_logo2)) {
        $header_logo = $site_logo2;
    } elseif ($header_logo === '' && !empty($site_logo)) {
        $header_logo = $site_logo;
    } elseif ($header_logo === '' && !empty($site_favicon)) {
        $header_logo = $site_favicon;
    } elseif ($header_logo === '') {
        $header_logo = 'logoweb1.png';
    }

    echo '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>' . htmlspecialchars($appSiteName, ENT_QUOTES) . ' - Dashboard ' . htmlspecialchars($appVersion, ENT_QUOTES) . '</title>
    <meta name="robots" content="noindex">
    <meta name="description" content="© ' . htmlspecialchars((string)$appYear, ENT_QUOTES) . ' ' . htmlspecialchars($appSiteName, ENT_QUOTES) . ' | ' . htmlspecialchars($appVersion, ENT_QUOTES) . '">
    <meta name="author" content="smakpalapik">
    <meta http-equiv="Copyright" content="' . htmlspecialchars($appSiteName, ENT_QUOTES) . '">
    <meta name="copyright" content="smakpalapik">
    <!-- Favicon -->
    <link rel="icon" href="../content/' . $site_favicon . '" type="image/png">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="../admin/assets/vendor/nucleo/css/nucleo.css" type="text/css">
    <link rel="stylesheet" href="../admin/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css" type="text/css">
    <link rel="stylesheet" href="../admin/assets/vendor/timepicker/bootstrap-timepicker.min.css">
    <link rel="stylesheet" href="../admin/assets/vendor/select2/dist/css/select2.min.css">
    <link rel="stylesheet" href="../admin/assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../admin/assets/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="../admin/assets/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css">
    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/css/argon.css" type="text/css">
    <link rel="stylesheet" href="assets/css/app.css" type="text/css">
    
    <!-- Custom CSS - Load Last for Override with Cache Busting -->
    <link rel="stylesheet" href="assets/css/style.css?v=' . time() . '" type="text/css">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="../admin/assets/vendor/jquery/dist/jquery.min.js" as="script">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" as="script">
    
    <!-- jQuery - Load Early -->
    <script src="../admin/assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
</head>
<body>
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="spinner"></div>
    </div>';

    if (isset($_COOKIE['siswa'])) {
        echo '
<div class="main-content" id="panel">
    <!-- Enhanced Topnav -->
    <nav class="navbar navbar-top navbar-expand navbar-dark border-bottom navbar-enhanced">
        <div class="container-fluid">
            <div class="collapse navbar-collapse d-flex justify-content-between" id="navbarSupportedContent">
                <ul class="navbar-nav align-items-center flex-row w-100">
                    <li class="nav-item d-xl-none">
                        <!-- Sidenav toggler -->
                    </li>
                    <li class="nav-item">
                        <a href="home" class="navbar-brand m-0 p-0 dashboard-navbar-brand">
                            <img src="../content/' . htmlspecialchars($header_logo, ENT_QUOTES) . '?v=' . (($content_dir && !empty($header_logo) && file_exists($content_dir . DIRECTORY_SEPARATOR . $header_logo)) ? filemtime($content_dir . DIRECTORY_SEPARATOR . $header_logo) : time()) . '" alt="Logo" class="dashboard-navbar-logo" onerror="this.onerror=null;this.src=\'../content/logoweb1.png\';">
                        </a>
                    </li>
                    <li class="nav-item dropdown ml-auto">
                        <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="media align-items-center">
                                <div class="media-body mr-2 d-none d-lg-block">
                                    <span class="mb-0 text-sm font-weight-bold dashboard-navbar-username">' . strip_tags($data_user['nama_lengkap']) . '</span>
                                </div>
                                <span class="avatar avatar-sm rounded-circle">';
        $nisn = isset($data_user['nisn']) ? $data_user['nisn'] : '';
        $avatar_file = $nisn ? $nisn . '.png' : 'avatar.jpg';
        $avatar_path = '../content/avatar/' . $avatar_file;
        if ($avatar_file == 'avatar.jpg' || !file_exists($avatar_path)) {
            echo '<img src="../content/avatar/avatar.jpg" alt="image" class="imaged w36 dashboard-avatar-img" height="36">';
        } else {
            echo '<img src="data:image/png;base64,' . base64_encode(file_get_contents($avatar_path)) . '" class="dashboard-avatar-img" height="36">';
        }
        echo '
                                </span>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-header noti-title">
                                <h6 class="text-overflow m-0 dashboard-dropdown-title">
                                    <i class="fas fa-user-circle me-2"></i>Welcome!
                                </h6>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="profile" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Profile Saya</span>
                            </a>
                            <a href="profile#ubah-password" class="dropdown-item">
                                <i class="fas fa-key"></i>
                                <span>Ubah Password</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="../logout" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Header -->';
    }
}
?>
<!-- Enhanced JavaScript Loading -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Page Enhancement Script -->
<script>
    $(document).ready(function() {
        // Hide page loader when content is ready
        setTimeout(function() {
            $('#pageLoader').addClass('hide');
            $('.main-content').addClass('loaded');
        }, 500);

        // Enhanced dropdown interactions
        $('.dropdown-toggle').on('show.bs.dropdown', function() {
            $(this).find('.avatar img').css('transform', 'scale(1.1)');
        });

        $('.dropdown-toggle').on('hide.bs.dropdown', function() {
            $(this).find('.avatar img').css('transform', 'scale(1)');
        });

        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(event) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Enhanced form validation feedback
        $('form').on('submit', function() {
            $(this).find('.btn[type="submit"]').prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        });

        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    var perfData = performance.getEntriesByType('navigation')[0];
                    if (perfData.loadEventEnd - perfData.loadEventStart > 3000) {
                        console.warn('Page load time is slow: ' + (perfData.loadEventEnd - perfData.loadEventStart) + 'ms');
                    }
                }, 0);
            });
        }
    });

    // Service Worker for caching (if needed)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            // navigator.serviceWorker.register('/sw.js'); // Uncomment if you have a service worker
        });
    }
</script>
</body>

</html>