<?php require_once '../../library/config.php';
include_once '../../library/function.php';

if (sae_registration_sync_required($connection)) {
    sae_redirect_to_registrasi();
}

$admin_login_mode = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
$admin_site_name = trim((string)($site_name ?? ''));
if ($admin_site_name === '') {
    $admin_site_name = defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Smart Apps Education';
}
$admin_app_version = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';
$admin_app_year = defined('SAE_APP_YEAR') ? SAE_APP_YEAR : date('Y');
$admin_logo_file = !empty($site_logo1) ? $site_logo1 : (!empty($site_logo) ? $site_logo : 'logoweb1.png');
$admin_logo_path = '../../content/' . $admin_logo_file;
$admin_forgot_mode = $admin_login_mode === 'forgot';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="© <?php echo htmlspecialchars((string)$admin_app_year); ?> <?php echo htmlspecialchars($admin_site_name); ?> | <?php echo htmlspecialchars($admin_app_version); ?>">
    <meta name="author" content="Creative Tim">
    <title><?php echo htmlspecialchars($admin_site_name); ?> - Login Dashboard <?php echo htmlspecialchars($admin_app_version); ?></title>
    <!-- Favicon -->
    <link rel="icon" href="../assets/img/brand/favicon.png" type="image/png">
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/nucleo/css/nucleo.css" type="text/css">
    <link rel="stylesheet" href="../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css" type="text/css">
    <!-- Argon CSS -->
    <link rel="stylesheet" href="../assets/css/argon.css?v=1.1.0" type="text/css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>" type="text/css">
</head>

<body class="admin-login-page">
    <main class="admin-login-shell">
        <div class="admin-login-wrap">
            <div class="admin-login-card">
                <div class="admin-login-grid">
                    <section class="admin-login-aside">
                        <div>
                            <span class="admin-login-badge"><i class="fas fa-user-shield"></i> Panel Admin</span>
                            <h1 class="admin-login-title"><?php echo $admin_forgot_mode ? 'Reset akses admin' : 'Masuk dengan akun Dapodik'; ?></h1>
                            <p class="admin-login-lead"><?php echo $admin_forgot_mode ? 'Gunakan email yang terdaftar untuk meminta reset password administrator secara aman.' : 'Gunakan username dan password yang tersimpan di Dapodik untuk mengakses dashboard pengelolaan sistem sekolah.'; ?></p>
                            <div class="admin-login-points">
                                <div class="admin-login-point">
                                    <strong>Akses Terpisah</strong>
                                    <span>Login admin dipisahkan dari login murid agar hak akses dan pengelolaan data tetap terjaga.</span>
                                </div>
                                <div class="admin-login-point">
                                    <strong>Kontrol Operasional</strong>
                                    <span>Kelola data siswa, absensi, layanan sekolah, dan konfigurasi sistem dari satu dashboard kerja.</span>
                                </div>
                            </div>
                        </div>
                        <div class="admin-login-meta">
                            <span><i class="fas fa-shield-alt"></i> Autentikasi admin</span>
                            <span><i class="fas fa-school"></i> <?php echo htmlspecialchars($admin_site_name); ?></span>
                        </div>
                    </section>

                    <section class="admin-login-main">
                        <div class="admin-login-panel">
                            <div class="admin-login-brand">
                                <img src="<?php echo htmlspecialchars($admin_logo_path); ?>" alt="Logo <?php echo htmlspecialchars($admin_site_name); ?>" class="admin-login-logo">
                                <div class="admin-login-brand-copy">
                                    <h1><?php echo htmlspecialchars($admin_site_name); ?></h1>
                                    <p><?php echo $admin_forgot_mode ? 'Form reset password administrator.' : 'Form login dengan akun Dapodik.'; ?></p>
                                </div>
                            </div>

                            <div class="admin-login-form-card">
                                <?php if ($admin_forgot_mode): ?>
                                    <h2>Reset Password</h2>
                                    <p>Masukkan email administrator yang terdaftar untuk meminta reset password baru.</p>
                                    <form class="forgot" role="form" method="post" action="#" autocomplete="off">
                                        <div class="form-group">
                                            <label class="form-label" for="email">Email</label>
                                            <div class="input-group input-group-merge">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                                </div>
                                                <input type="email" class="form-control" id="email" name="email" value="" placeholder="Masukkan email admin" required>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn admin-login-submit">Kirim Reset Password</button>
                                        </div>
                                    </form>
                                    <div class="admin-login-links">
                                        <a href="./" class="admin-login-link"><i class="fas fa-arrow-left"></i>Kembali ke Login</a>
                                        <a href="../../" class="admin-login-link"><i class="fas fa-home"></i>Ke Halaman Publik</a>
                                    </div>
                                <?php else: ?>
                                    <h2>Login Dashboard</h2>
                                    <p>Masukkan username dan password Dapodik untuk melanjutkan ke dashboard.</p>
                                    <form class="login" role="form" method="post" action="#" autocomplete="off">
                                        <div class="form-group">
                                            <label class="form-label" for="username">Username Dapodik</label>
                                            <div class="input-group input-group-merge">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                </div>
                                                <input type="text" class="form-control username" id="username" name="username" value="" placeholder="Masukkan username Dapodik" required>
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label" for="password">Password</label>
                                            <div class="input-group input-group-merge">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                                </div>
                                                <input type="password" class="form-control password" id="password" name="password" placeholder="Masukkan password" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-white border-left-0">
                                                        <button class="btn btn-sm btn-link p-0 toggle-password" type="button" aria-label="Toggle password visibility">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mt-4">
                                            <button type="submit" class="btn admin-login-submit">Masuk ke Dashboard</button>
                                        </div>
                                    </form>
                                    <div class="admin-login-links">
                                        <a href="./?op=forgot" class="admin-login-link"><i class="fas fa-key"></i>Lupa Password</a>
                                        <a href="../../" class="admin-login-link"><i class="fas fa-home"></i>Ke Halaman Publik</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <!-- Core -->
    <script src="../assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/js-cookie/js.cookie.js"></script>
    <script src="../assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
    <script src="../assets/js/sweetalert.min.js"></script>
    <!-- Argon JS -->
    <script src="../assets/js/argon.js?v=1.1.0"></script>
    <!-- Demo JS - remove this in your project -->
    <script src="../assets/js/demo.js"></script>
    <script src="./script.js?v=<?= filemtime('script.js') ?>"></script>
</body>

</html>