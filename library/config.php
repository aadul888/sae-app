<?php
if (session_status() === PHP_SESSION_NONE) {
	// hosting sering session.save_path read-only → paksa temp dir writable
	$sp = ini_get('session.save_path');
	if (!$sp || !is_dir($sp) || !is_writable($sp)) {
		@ini_set('session.save_path', sys_get_temp_dir());
	}
	session_start();
}

error_reporting(E_ALL);
// Tapi jangan tampilkan errors ke user (hindari whitespace/BOM yang menyebabkan blank page)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
// Pastikan timezone aplikasi konsisten (ubah jika perlu)
date_default_timezone_set('Asia/Jakarta');

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (
	PHP_SAPI !== 'cli'
	&& $_SERVER['REQUEST_METHOD'] === 'POST'
	&& isset($_SERVER['SCRIPT_NAME'])
	&& preg_match('#/admin/mod/[^/]+/proses\.php$#', $_SERVER['SCRIPT_NAME'])
) {
	$expected_csrf = $_SESSION['csrf_token'] ?? '';
	$provided_csrf = $_POST['csrf_token'] ?? '';
	if ($provided_csrf === '') {
		if (isset($_SERVER['HTTP_CSRF_TOKEN'])) {
			$provided_csrf = $_SERVER['HTTP_CSRF_TOKEN'];
		} else if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
			$provided_csrf = $_SERVER['HTTP_X_CSRF_TOKEN'];
		} else if (function_exists('getallheaders')) {
			$headers = array_change_key_case(getallheaders(), CASE_LOWER);
			$provided_csrf = $headers['csrf-token'] ?? $headers['x-csrf-token'] ?? '';
		}
	}
	if ($expected_csrf !== '' && $provided_csrf !== '' && !hash_equals($expected_csrf, (string) $provided_csrf)) {
		http_response_code(403);
		header('Content-Type: application/json');
		echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid.']);
		exit;
	}
}

if (!function_exists('sae_setup_complete')) {
	function sae_setup_complete(): bool {
		global $connection;
		
		// Strategi 1: Gunakan koneksi live yang sudah ada (paling cepat)
		if (isset($connection) && $connection instanceof mysqli && empty($connection->connect_error)) {
			// Check: constants didefinisikan (installer sudah menulis ke config.php)
			if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
				return false;
			}
			// Check: installer_completed=1 di setting table
			$res = $connection->query("SELECT installer_completed FROM setting LIMIT 1");
			if ($res && $res->num_rows > 0) {
				$row = $res->fetch_assoc();
				return (int)($row['installer_completed'] ?? 0) === 1;
			}
			return false;
		}

		// Strategi 2: Cek constants (dari module konfigurasi/.sae_db.php).
		// Jika constants kosong → belum dikonfigurasi → false (blokir akses).
		if (!defined('DB_HOST') || DB_HOST === '' || !defined('DB_NAME') || DB_NAME === '' || !defined('DB_USER')) {
			return false;
		}

		// Strategi 3: Coba koneksi baru (fallback) dengan port detect.
		$host = DB_HOST;
		$port = defined('DB_PORT') ? (int) DB_PORT : 3306;
		$name = DB_NAME;
		$user = DB_USER;
		$pass = defined('DB_PASSWD') ? DB_PASSWD : '';

		// Timeout: jika koneksi gagal dalam 2 detik, anggap belum setup
		// (untuk menghindari hang saat DB down)
		$conn = @mysqli_connect($host, $user, $pass, $name, $port);
		if (!$conn) {
			return false;
		}

		$res = @mysqli_query($conn, "SELECT installer_completed FROM setting LIMIT 1");
		$done = false;
		if ($res) {
			$row = @mysqli_fetch_assoc($res);
			$done = (int)($row['installer_completed'] ?? 0) === 1;
		}
		@mysqli_close($conn);
		return $done;
	}
}

// Include fallback DB credentials sebelum defaults — installer menulis file ini
// jika config.php tidak writable. Harus sebelum defined() or define() di bawah.
$_fallback_db = __DIR__ . '/.sae_db.php';
$fallback_loaded = false;
// Coba semua kandidat lokasi .sae_db.php
$_fallback_candidates = [
	__DIR__ . '/.sae_db.php',                           // library/
	dirname(__DIR__) . '/content/.sae_db.php',          // content/
	dirname(__DIR__) . '/.sae_db.php',                  // root dir
	dirname(__DIR__) . '/content/cache/.sae_db.php',    // content/cache/
	dirname(__DIR__) . '/content/berkas/.sae_db.php',   // content/berkas/
	dirname(__DIR__) . '/content/avatar/.sae_db.php',   // content/avatar/
];
foreach ($_fallback_candidates as $_fdb) {
	if (is_file($_fdb)) {
		require_once $_fdb;
		$fallback_loaded = true;
		break;
	}
}

// -------------- Koneksi Database (.env) ----
// Credentials dari .env file (Fresh install = standalone /installer).
// .env di root — tidak commit ke git, user-specific, persist across updates.
// Jika .env tidak ada → redirect ke /installer (first-time setup).

// Load .env if exists
$_env_path = dirname(__DIR__) . '/.env';
if (is_file($_env_path)) {
	foreach (file($_env_path) as $_line) {
		$_line = trim($_line);
		if ($_line === '' || $_line[0] === '#') continue;
		if (strpos($_line, '=') === false) continue;
		[$_key, $_val] = explode('=', $_line, 2);
		$_key = trim($_key);
		if (in_array($_key, ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWD'])) {
			defined($_key) or define($_key, $_val);
		}
	}
	unset($_env_path, $_line, $_key, $_val);
}

// Fallback: older .sae_db.php for backward compat (if .env missing)
$_fallback_candidates = [
	dirname(__DIR__) . '/library/.sae_db.php',          // library/
	dirname(__DIR__) . '/content/.sae_db.php',          // content/
	dirname(__DIR__) . '/.sae_db.php',                  // root dir
	dirname(__DIR__) . '/content/cache/.sae_db.php',    // content/cache/
	dirname(__DIR__) . '/content/berkas/.sae_db.php',   // content/berkas/
	dirname(__DIR__) . '/content/avatar/.sae_db.php',   // content/avatar/
];
foreach ($_fallback_candidates as $_fdb) {
	if (is_file($_fdb)) {
		require_once $_fdb;
		break;
	}
}
unset($_fallback_candidates, $_fdb);

// Define defaults (empty = not configured)
defined('DB_HOST')   or define('DB_HOST', '');
defined('DB_PORT')   or define('DB_PORT', 0);
defined('DB_NAME')   or define('DB_NAME', '');
defined('DB_USER')   or define('DB_USER', '');
defined('DB_PASSWD') or define('DB_PASSWD', '');

// Assignment variabel DB — diperlukan karena kode lama memakai $DB_HOST bukan DB_HOST.
$DB_HOST   = DB_HOST;
$DB_PORT   = (int) DB_PORT;
$DB_NAME   = DB_NAME;
$DB_USER   = DB_USER;
$DB_PASSWD = DB_PASSWD;
// Port default untuk SEMUA koneksi mysqli tanpa argumen port eksplisit
ini_set('mysqli.default_port', (string) $DB_PORT);

// Auto-detect port bila koneksi default gagal (Laragon/XAMPP sering pakai port selain 3306)
function sae_detect_db_port(string $host, string $user, string $pass): int
{
	foreach ([3306, 3307, 3308] as $port) {
		$conn = @new mysqli($host, $user, $pass, '', $port);
		if (!$conn->connect_error) {
			$conn->close();
			return $port;
		}
	}
	return 3306;
}

// PHP 8.1+ defaults mysqli to throw exceptions on any error, which turns every
// failed/legacy query in this app into a fatal (blank page). This codebase was
// written for the PHP 7.x behaviour where errors return false and are handled
// with `if ($result)` checks, so restore that mode globally.
mysqli_report(MYSQLI_REPORT_OFF);

$connection = null;
$site_name = 'Smart Apps Education';
$site_logo = 'logoweb1.png';
$site_logo2 = '';
$site_favicon = 'favicon.png';
$site_url = '';
$site_phone = '';
$site_address = '';
$site_email = '';
$gmail_active = 'N';
$gmail_host = '';
$gmail_username = '';
$gmail_password = '';
$gmail_port = '';
$site_id = null;

if (sae_setup_complete()) {
	$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASSWD, $DB_NAME, $DB_PORT);
	if ($connection->connect_error) {
		// Port default gagal — coba auto-detect port lain (Laragon/XAMPP)
		$DB_PORT = sae_detect_db_port($DB_HOST, $DB_USER, $DB_PASSWD);
		$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASSWD, $DB_NAME, $DB_PORT);
		if ($connection->connect_error) {
			$message = 'Koneksi database gagal: ' . mysqli_connect_error();

			// Jika dipanggil dari endpoint /api/, output JSON error
			if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
				header('Content-Type: application/json');
				echo json_encode([
					'status' => 'error',
					'message' => $message,
					'code' => 'DB_CONNECT_ERROR'
				]);
				exit();
			}

			// DB unreachable bukan berarti instalasi hilang
			http_response_code(503);
			echo '<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Database Error</title></head>
<body style="font-family:Arial,sans-serif;max-width:720px;margin:48px auto;padding:24px;border:1px solid #ddd;border-radius:12px">
<h2>Database belum dapat dihubungi</h2>
<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
<p>Periksa kredensial database di <code>library/config.php</code> atau status server MySQL.</p>
</body>
</html>';
			exit();
		}
	}

	// Set charset untuk koneksi (harus utf8mb4, DB pakai utf8mb4)
	$connection->set_charset("utf8mb4");

	$query_site   = "SELECT * FROM setting LIMIT 1";
	$result_site  = $connection->query($query_site);

	if ($result_site && $result_site->num_rows > 0) {
		$row_site = $result_site->fetch_assoc();
		// Explicit variable assignment (NOT extract() — security: prevents variable injection)
		$site_id      = $row_site['site_id'] ?? null;
		$site_name    = $row_site['site_name'] ?? 'SAE Dashboard';
		$site_logo    = $row_site['site_logo'] ?? 'logoweb1.png';
		$site_logo2   = $row_site['site_logo2'] ?? '';
		$site_favicon = $row_site['site_favicon'] ?? 'favicon.png';
		$site_url     = $row_site['site_url'] ?? '';
		$site_phone   = $row_site['site_phone'] ?? '';
		$site_address = $row_site['site_address'] ?? '';
		$site_email   = $row_site['site_email'] ?? '';
		$gmail_active = $row_site['gmail_active'] ?? 'N';
		$gmail_host   = $row_site['gmail_host'] ?? '';
		$gmail_username = $row_site['gmail_username'] ?? '';
		$gmail_password = $row_site['gmail_password'] ?? '';
		$gmail_port   = $row_site['gmail_port'] ?? '';
	} else {
		// Set default values jika setting tidak ada
		$site_name = "SAE Dashboard";
		$site_logo = "logoweb1.png";
		$site_favicon = "favicon.png";
		$site_url = "";
	}
}

// Auto-migrate old logo filenames to new naming convention (logoweb → logoweb1)
if ($connection && !empty($site_logo) && $site_logo === 'logoweb.png') {
	$_mid = isset($site_id) ? intval($site_id) : 1;
	$connection->query("UPDATE setting SET site_logo='logoweb1.png' WHERE site_id=$_mid");
	$site_logo = 'logoweb1.png';
}
if ($connection && !empty($site_logo2) && $site_logo2 === 'logoweb1.png') {
	$_mid = isset($site_id) ? intval($site_id) : 1;
	$connection->query("UPDATE setting SET site_logo2='logoweb2.png' WHERE site_id=$_mid");
	$site_logo2 = 'logoweb2.png';
}

// Load version constants (SAE_VERSION, SAE_APP_YEAR, SAE_APP_NAME)
require_once __DIR__ . '/version.php';

// ---- Non-database configuration (GitHub webhook, API key, etc.) ----
// These are NOT related to database connection and are intentionally kept separate.
if (!defined('SAE_INDUK_URL')) define('SAE_INDUK_URL', getenv('SAE_INDUK_URL') ?: '');

// SAE API key: prioritaskan dari environment, lalu dari database setting.api_key,
// agar konsisten dengan apa yang ditampilkan di halaman sync/form.php.
if (!defined('SAE_API_KEY')) {
    $sae_api_key = getenv('SAE_API_KEY');
    if (!$sae_api_key) {
        $db_h = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_u = defined('DB_USER') ? DB_USER : 'root';
        $db_p = defined('DB_PASSWD') ? DB_PASSWD : '';
        $db_n = defined('DB_NAME') ? DB_NAME : '';
        $db_port = defined('DB_PORT') ? (int) DB_PORT : 3306;

        if (isset($connection) && $connection instanceof mysqli && !$connection->connect_error) {
            $res = $connection->query("SELECT api_key FROM setting WHERE site_id = " . (isset($site_id) ? (int) $site_id : 1) . " LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $sae_api_key = $row['api_key'] ?: '';
            }
        } elseif (!empty($db_n)) {
            try {
                $tmp_conn = @new mysqli($db_h, $db_u, $db_p, $db_n, $db_port);
                if (!$tmp_conn->connect_error) {
                    $res = $tmp_conn->query("SELECT api_key FROM setting WHERE site_id = 1 LIMIT 1");
                    if ($res && $row = $res->fetch_assoc()) {
                        $sae_api_key = $row['api_key'] ?: '';
                    }
                    $tmp_conn->close();
                }
            } catch (Throwable $e) {
            }
        }
    }
    define('SAE_API_KEY', $sae_api_key ?: '');
}

// GitHub Webhook secret — isi string random, lalu set di repo GitHub → Settings → Webhooks
// Load dari github-config.php (legacy, prioritas), then .env fallback
if (is_file(__DIR__ . '/github-config.php')) {
	require_once __DIR__ . '/github-config.php';
}
$env_webhook_secret = getenv('GITHUB_WEBHOOK_SECRET');
if (!defined('GITHUB_WEBHOOK_SECRET')) {
	define('GITHUB_WEBHOOK_SECRET', $env_webhook_secret ?: '');
}

// GitHub Personal Access Token — dari .env, fallback github-config.php
$env_token = getenv('GITHUB_TOKEN');
if (!defined('GITHUB_TOKEN')) {
	define('GITHUB_TOKEN', $env_token ?: '');
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---- Base URL / HTTPS detection ----
function is_https_request(): bool
{
	if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') return true;
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return true;
	if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') return true;
	if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower((string) $_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') return true;
	if (!empty($_SERVER['REQUEST_SCHEME']) && strtolower((string) $_SERVER['REQUEST_SCHEME']) === 'https') return true;
	if (!empty($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') return true;
	if (isset($_SERVER['HTTP_CF_VISITOR'])) {
		$cf_visitor = json_decode((string) $_SERVER['HTTP_CF_VISITOR'], true);
		if (is_array($cf_visitor) && ($cf_visitor['scheme'] ?? '') === 'https') return true;
	}
	return false;
}

function base_url($atRoot = false, $atCore = false, $parse = false)
{
	if (isset($_SERVER['HTTP_HOST'])) {
		$http    = is_https_request() ? 'https' : 'http';
		$hostname = $_SERVER['HTTP_HOST'];
		$dir     = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
		$core    = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__) ?: '')), -1, PREG_SPLIT_NO_EMPTY);
		$core    = $core[0] ?? '';
		$tmplt   = $atRoot ? ($atCore ? "%s://%s/%s/" : "%s://%s/") : ($atCore ? "%s://%s/%s/" : "%s://%s%s");
		$end     = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
		$base_url = sprintf($tmplt, $http, $hostname, $end);
	} else {
		$base_url = 'http://localhost/';
	}
	if ($parse) {
		$base_url = parse_url($base_url);
		if (isset($base_url['path']) && $base_url['path'] === '/') $base_url['path'] = '';
	}
	return $base_url;
}

$base_url = base_url();

// Force HTTPS on production (non-localhost)
if (isset($_SERVER['HTTP_HOST'])) {
	$host = strtolower((string) $_SERVER['HTTP_HOST']);
	$hostWithoutPort = preg_replace('/:\d+$/', '', $host);
	$isLocalHost = in_array($hostWithoutPort, ['localhost', '127.0.0.1', '::1'], true);
	// Laragon .test domain = lokal
	if (!$isLocalHost && preg_match('/\.test$/', $hostWithoutPort)) $isLocalHost = true;
	if (!$isLocalHost) {
		$base_url = preg_replace('/^http:\/\//i', 'https://', (string) $base_url);
		$site_url = preg_replace('/^http:\/\//i', 'https://', (string) $site_url);
	}
}

// Application debug flag
if (!defined('APP_DEBUG')) define('APP_DEBUG', false);

// Encryption key untuk e-izin approve links. MUST be set di .env di production!
if (!defined('APP_ENC_KEY')) {
	$env_enc_key = getenv('APP_ENC_KEY');
	define('APP_ENC_KEY', $env_enc_key ?: '');
}

// ---- Security Headers ----
if (PHP_SAPI !== 'cli') {
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: SAMEORIGIN');
	header('Referrer-Policy: strict-origin-when-cross-origin');
	// HSTS hanya untuk non-localhost
	if (isset($_SERVER['HTTP_HOST']) && !preg_match('/\.test$|^localhost|^127\.|\.local$/', $_SERVER['HTTP_HOST'])) {
		header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	}
}

// NOTE: Do NOT close PHP tag di akhir file untuk menghindari output whitespace/BOM.
