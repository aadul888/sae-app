<?php
session_start();
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_OFF);

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data:; script-src 'self' 'unsafe-inline';");

$root_dir = __DIR__;
$env_path = $root_dir . '/.env';
$site_name = 'Smart Apps Education';
$site_logo_path = '/content/logoweb1.png';
$installer_success_message = (string) ($_SESSION['installer_success_message'] ?? '');
if ($installer_success_message !== '') {
    unset($_SESSION['installer_success_message']);
}

if (empty($_SESSION['installer_token'])) {
    $_SESSION['installer_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['installer_token'];

function parse_env_config($env_path)
{
    if (!is_file($env_path)) {
        return [];
    }

    $config = [];
    foreach ((array) file($env_path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim((string) $line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $config[trim($key)] = $value;
    }

    return $config;
}

function ensure_installer_state_from_env($env_path)
{
    $config = parse_env_config($env_path);
    if (($config['DB_HOST'] ?? '') === '' || ($config['DB_NAME'] ?? '') === '' || ($config['DB_USER'] ?? '') === '') {
        return false;
    }

    $host = $config['DB_HOST'];
    $port = isset($config['DB_PORT']) ? (int) $config['DB_PORT'] : 3306;
    $name = $config['DB_NAME'];
    $user = $config['DB_USER'];
    $pass = $config['DB_PASSWD'] ?? '';

    try {
        $conn = @new mysqli($host, $user, $pass, $name, $port);
    } catch (Throwable $e) {
        return false;
    }

    if ($conn->connect_error) {
        return false;
    }

    $table_exists = $conn->query("SHOW TABLES LIKE 'setting'");
    if (!$table_exists || !$table_exists->num_rows) {
        $conn->close();
        return false;
    }

    $setting_result = $conn->query("SELECT site_id, installer_completed FROM setting ORDER BY site_id ASC LIMIT 1");
    if ($setting_result && $setting_result->num_rows > 0) {
        $row = $setting_result->fetch_assoc();
        if ((int) ($row['installer_completed'] ?? 0) !== 1) {
            $site_id = (int) ($row['site_id'] ?? 1);
            $conn->query("UPDATE setting SET installer_completed=1 WHERE site_id={$site_id}");
        }
        $conn->close();
        return true;
    }

    $init_api_key = 'SAE_' . date('YmdHis') . '_' . bin2hex(random_bytes(16));
    $stmt = $conn->prepare(
        "INSERT INTO setting (site_id, site_name, site_phone, site_address, site_owner, site_logo, site_favicon, site_kop, site_url, site_email, gmail_host, gmail_username, gmail_password, gmail_port, gmail_active, google_client_id, google_client_secret, google_client_active, maintenance_status, license_status, last_deploy_commit, api_key, installer_completed)
         VALUES (1, 'Smart Apps Education', '', '', '', 'logoweb1.png', 'favicon.png', '', '', '', '', '', '', '', 'N', '', '', 'N', 'open', 'unverified', '', ?, 1)"
    );

    if (!$stmt) {
        $conn->close();
        return false;
    }

    $stmt->bind_param('s', $init_api_key);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

$installer_ready = is_file($env_path) && ensure_installer_state_from_env($env_path);
if ($installer_ready && $installer_success_message === '') {
    header('Location: /');
    exit;
}

function detect_db_host($user, $pass)
{
    $candidates = [
        ['localhost', 3306],
        ['localhost', 3307],
        ['localhost', 3308],
        ['127.0.0.1', 3306],
    ];

    foreach ($candidates as [$host, $port]) {
        $conn = @new mysqli($host, $user, $pass, '', $port);
        if (!$conn->connect_error) {
            $conn->close();
            return ['host' => $host, 'port' => $port];
        }
    }

    return ['host' => 'localhost', 'port' => 3306];
}

$error = '';
$form_data = ['db_name' => '', 'db_user' => ''];
$env_config = parse_env_config($env_path);
if ($form_data['db_name'] === '' && isset($env_config['DB_NAME'])) {
    $form_data['db_name'] = (string) $env_config['DB_NAME'];
}
if ($form_data['db_user'] === '' && isset($env_config['DB_USER'])) {
    $form_data['db_user'] = (string) $env_config['DB_USER'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['installer_submit'])) {
    if (empty($_POST['_token']) || $_POST['_token'] !== $csrf_token) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $db_name = trim((string) ($_POST['db_name'] ?? ''));
        $db_user = trim((string) ($_POST['db_user'] ?? ''));
        $db_pass = (string) ($_POST['db_pass'] ?? '');

        $form_data = ['db_name' => $db_name, 'db_user' => $db_user];

        if ($db_name === '' || $db_user === '') {
            $error = 'Nama database dan username database wajib diisi.';
        }

        if ($error === '') {
            $sql_path = $root_dir . '/database/db_sae.sql';
            if (!is_file($sql_path)) {
                $error = 'File database/db_sae.sql tidak ditemukan.';
            }
        }

        if ($error === '') {
            $detected = detect_db_host($db_user, $db_pass);
            $db_host = $detected['host'];
            $db_port = $detected['port'];
        }

        if ($error === '') {
            $admin_conn = @new mysqli($db_host, $db_user, $db_pass, '', $db_port);
            if ($admin_conn->connect_error) {
                $error = 'Koneksi database gagal: ' . htmlspecialchars($admin_conn->connect_error, ENT_QUOTES, 'UTF-8');
            }
        }

        if ($error === '' && isset($admin_conn)) {
            $safe_db_name = preg_replace('/[^A-Za-z0-9_\-]/', '', $db_name);
            if (!$admin_conn->query("CREATE DATABASE IF NOT EXISTS `{$safe_db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $error = 'Gagal membuat database: ' . htmlspecialchars($admin_conn->error, ENT_QUOTES, 'UTF-8');
            } else {
                $admin_conn->select_db($safe_db_name);
                $admin_conn->set_charset('utf8mb4');
            }
        }

        if ($error === '' && isset($admin_conn, $sql_path)) {
            $sql = @file_get_contents($sql_path);
            if ($sql === false || trim($sql) === '') {
                $error = 'File database/db_sae.sql tidak dapat dibaca atau kosong.';
            } else {
                $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
                $sql = preg_replace('/^USE\s+`?[^;\r\n]+`?\s*;\s*$/im', '', $sql);
                $sql = preg_replace('/^CREATE DATABASE\s+`?[^;\r\n]+`?\s*;\s*$/im', '', $sql);
                $sql = preg_replace('/^\s*--.*$/m', '', $sql);
                $sql = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci', $sql);

                if (!mysqli_multi_query($admin_conn, $sql)) {
                    $error = 'Import SQL gagal: ' . htmlspecialchars($admin_conn->error, ENT_QUOTES, 'UTF-8');
                } else {
                    do {
                        if ($admin_conn->errno) {
                            $error .= '[SQL] ' . htmlspecialchars($admin_conn->error, ENT_QUOTES, 'UTF-8') . '; ';
                        }
                    } while ($admin_conn->next_result());
                }
            }
        }

        if ($error === '' && isset($admin_conn)) {
            $setting_check = $admin_conn->query("SELECT 1 FROM setting LIMIT 1");
            if (!$setting_check || $setting_check->num_rows === 0) {
                $init_api_key = 'SAE_' . date('YmdHis') . '_' . bin2hex(random_bytes(16));
                $setting_stmt = $admin_conn->prepare(
                    "INSERT INTO setting (site_id, site_name, site_phone, site_address, site_owner, site_logo, site_favicon, site_kop, site_url, site_email, gmail_host, gmail_username, gmail_password, gmail_port, gmail_active, google_client_id, google_client_secret, google_client_active, maintenance_status, license_status, last_deploy_commit, api_key, installer_completed)
                     VALUES (1, 'Smart Apps Education', '', '', '', 'logoweb1.png', 'favicon.png', '', '', '', '', '', '', '', 'N', '', '', 'N', 'open', 'unverified', '', ?, 1)"
                );

                if (!$setting_stmt) {
                    $error = 'Gagal menyiapkan konfigurasi awal installer.';
                } else {
                    $setting_stmt->bind_param('s', $init_api_key);
                    if (!$setting_stmt->execute()) {
                        $error = 'Database berhasil di-import, tetapi baris konfigurasi awal gagal dibuat.';
                    }
                    $setting_stmt->close();
                }
            } else {
                $admin_conn->query("UPDATE setting SET installer_completed=1 WHERE site_id=1");
            }
        }

        if ($error === '' && isset($admin_conn, $db_host, $db_port, $db_name, $db_user, $db_pass)) {
            $env_content = "# SAE App Configuration\n"
                . "DB_HOST={$db_host}\n"
                . "DB_PORT={$db_port}\n"
                . "DB_NAME={$db_name}\n"
                . "DB_USER={$db_user}\n"
                . "DB_PASSWD={$db_pass}\n";

            if (@file_put_contents($env_path, $env_content) === false) {
                $error = 'File konfigurasi tidak dapat ditulis. Periksa permission folder root.';
            } else {
                @chmod($env_path, 0600);
                @$admin_conn->close();
                $_SESSION['installer_success_message'] = 'Instalasi berhasil. Sistem siap digunakan.';
                unset($_SESSION['installer_token']);
                header('Location: /installer');
                exit;
            }
        }

        if (isset($admin_conn)) {
            @$admin_conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Installer SAE App untuk konfigurasi awal koneksi database.">
    <title>Installer SAE App</title>
    <link rel="icon" href="/admin/assets/img/brand/favicon.png" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="/admin/assets/vendor/nucleo/css/nucleo.css" type="text/css">
    <link rel="stylesheet" href="/admin/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css" type="text/css">
    <link rel="stylesheet" href="/admin/assets/css/argon.css?v=1.1.0" type="text/css">
    <link rel="stylesheet" href="/admin/assets/css/style.css?v=1.0.0" type="text/css">
</head>
<body class="admin-login-page">
    <main class="admin-login-shell">
        <div class="admin-login-wrap">
            <div class="admin-login-card">
                <div class="admin-login-grid">
                    <section class="admin-login-aside">
                        <div>
                            <span class="admin-login-badge"><i class="fas fa-cogs"></i> Installer Sistem</span>
                            <h1 class="admin-login-title">Siapkan aplikasi tanpa mengubah file inti.</h1>
                            <p class="admin-login-lead">Masukkan koneksi database agar sistem membuat basis data awal dan menyimpan konfigurasi server secara terpisah dari source code.</p>
                            <div class="admin-login-points">
                                <div class="admin-login-point">
                                    <strong>Konfigurasi Aman</strong>
                                    <span>Kredensial tidak disimpan di file aplikasi utama sehingga proses update tetap aman untuk data pengguna.</span>
                                </div>
                                <div class="admin-login-point">
                                    <strong>Instalasi Sekali Saja</strong>
                                    <span>Setelah konfigurasi tersimpan, route installer tertutup oleh gate aplikasi dan tidak mengganggu operasional harian.</span>
                                </div>
                            </div>
                        </div>
                        <div class="admin-login-meta">
                            <span><i class="fas fa-shield-alt"></i> CSRF protected</span>
                            <span><i class="fas fa-database"></i> Import otomatis</span>
                        </div>
                    </section>

                    <section class="admin-login-main">
                        <div class="admin-login-panel">
                            <div class="admin-login-brand">
                                <img src="<?php echo htmlspecialchars($site_logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>" class="admin-login-logo">
                                <div class="admin-login-brand-copy">
                                    <h1><?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></h1>
                                    <p>Form instalasi awal untuk menghubungkan aplikasi ke server database.</p>
                                </div>
                            </div>

                            <div class="admin-login-form-card">
                                <h2>Konfigurasi Database</h2>
                                <p>Lengkapi koneksi database untuk melanjutkan proses instalasi.</p>

                                <?php if ($error !== ''): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="/installer" autocomplete="off">
                                    <div class="form-group">
                                        <label class="form-label" for="db_name">Nama Database</label>
                                        <div class="input-group input-group-merge">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-database"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($form_data['db_name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama database" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="db_user">Username Database</label>
                                        <div class="input-group input-group-merge">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($form_data['db_user'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan username database" required>
                                        </div>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="form-label" for="db_pass">Password Database</label>
                                        <div class="input-group input-group-merge">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                            </div>
                                            <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Masukkan password database jika ada">
                                            <div class="input-group-append">
                                                <span class="input-group-text bg-white border-left-0">
                                                    <button class="btn btn-sm btn-link p-0 toggle-password" type="button" aria-label="Tampilkan atau sembunyikan password">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="installer_submit" value="1">
                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn admin-login-submit">Lanjutkan Instalasi</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <script src="/admin/assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="/admin/assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/admin/assets/js/sweetalert.min.js"></script>
    <script src="/admin/login/script.js?v=1.0.0"></script>
    <?php if ($installer_success_message !== ''): ?>
    <script>
        swal({
            title: 'Berhasil!',
            text: <?php echo json_encode($installer_success_message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
            icon: 'success',
            timer: 1800,
            buttons: false
        }).then(function () {
            window.location.href = '/';
        });
        setTimeout(function () {
            window.location.href = '/';
        }, 1900);
    </script>
    <?php endif; ?>
</body>
</html>
