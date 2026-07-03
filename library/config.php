<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
error_reporting(0);
// Pastikan timezone aplikasi konsisten (ubah jika perlu)
date_default_timezone_set('Asia/Jakarta');

// -------------- Koneksi Database ------------
$DB_HOST   = 'localhost';
$DB_NAME   = 'saev5'; // Nama database
$DB_USER   = 'root'; // User Database
$DB_PASSWD = ''; // Password Database
// -------------- Koneksi Database ------------
if (!defined('DB_HOST'))   define('DB_HOST', $DB_HOST);
if (!defined('DB_NAME'))   define('DB_NAME', $DB_NAME);
if (!defined('DB_USER'))   define('DB_USER', $DB_USER);
if (!defined('DB_PASSWD')) define('DB_PASSWD', $DB_PASSWD);

// PHP 8.1+ defaults mysqli to throw exceptions on any error, which turns every
// failed/legacy query in this app into a fatal (blank page). This codebase was
// written for the PHP 7.x behaviour where errors return false and are handled
// with `if ($result)` checks, so restore that mode globally.
mysqli_report(MYSQLI_REPORT_OFF);

$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASSWD, $DB_NAME);
if ($connection->connect_error) {
	// Jika dipanggil dari endpoint /api/, output JSON error
	if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
		header('Content-Type: application/json');
		echo json_encode([
			'status' => 'error',
			'message' => 'Koneksi database gagal: ' . mysqli_connect_error(),
			'code' => 'DB_CONNECT_ERROR'
		]);
		exit();
	}
	// Tampilkan halaman error yang jelas (bukan blank page)
	http_response_code(503);
	echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title>';
	echo '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f5f5;margin:0}';
	echo '.box{background:#fff;padding:2rem 3rem;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center;max-width:500px}';
	echo 'h1{color:#e74c3c;margin-bottom:.5rem}p{color:#666}</style></head><body>';
	echo '<div class="box"><h1>&#9888; Database Error</h1>';
	echo '<p>Koneksi database gagal. Pastikan DB_HOST, DB_NAME, DB_USER, dan DB_PASSWD sudah benar di <code>library/config.php</code></p>';
	echo '<p style="font-size:.85rem;color:#999;margin-top:1rem">Error: ' . htmlspecialchars(mysqli_connect_error()) . '</p>';
	echo '</div></body></html>';
	exit();
} else {
	// Set charset untuk koneksi
	$connection->set_charset("utf8");

	$query_site  = "SELECT * FROM setting LIMIT 1";
	$result_site = $connection->query($query_site);

	if ($result_site && $result_site->num_rows > 0) {
		$row_site = $result_site->fetch_assoc();
		extract($row_site);
	} else {
		// Set default values jika setting tidak ada
		$site_name = "SAEV3 Dashboard";
		$site_logo = "logoweb1.png";
		$site_favicon = "favicon.png";
		$site_url = "";
	}
}

// Auto-migrate old logo filenames to new naming convention (logoweb → logoweb1, logoweb1 → logoweb2)
if (!empty($site_logo) && $site_logo === 'logoweb.png') {
	$_mid = isset($site_id) ? intval($site_id) : 1;
	$connection->query("UPDATE setting SET site_logo='logoweb1.png' WHERE site_id=$_mid");
	$site_logo = 'logoweb1.png';
}
if (!empty($site_logo2) && $site_logo2 === 'logoweb1.png') {
	$_mid = isset($site_id) ? intval($site_id) : 1;
	$connection->query("UPDATE setting SET site_logo2='logoweb2.png' WHERE site_id=$_mid");
	$site_logo2 = 'logoweb2.png';
}

// Load version constants (SAE_VERSION, SAE_APP_YEAR, SAE_APP_NAME)
require_once __DIR__ . '/version.php';

// SAE Induk (master server) configuration
// Laragon vhost: http://SmartAppsEducation.test
if (!defined('SAE_INDUK_URL')) define('SAE_INDUK_URL', 'http://SmartAppsEducation.test');
if (!defined('SAE_API_KEY'))  define('SAE_API_KEY',  'SAE_20260422090959_d6be672e5e92c802adeca5d8a4eccf0c');

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('is_https_request')) {
	function is_https_request()
	{
		if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
			return true;
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
			return true;
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
			return true;
		}

		if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower((string)$_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
			return true;
		}

		if (!empty($_SERVER['REQUEST_SCHEME']) && strtolower((string)$_SERVER['REQUEST_SCHEME']) === 'https') {
			return true;
		}

		if (!empty($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') {
			return true;
		}

		if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
			$cfVisitor = json_decode((string)$_SERVER['HTTP_CF_VISITOR'], true);
			if (is_array($cfVisitor) && isset($cfVisitor['scheme']) && strtolower((string)$cfVisitor['scheme']) === 'https') {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('base_url')) {
	function base_url($atRoot = false, $atCore = false, $parse = false)
	{
		if (isset($_SERVER['HTTP_HOST'])) {
			$http = is_https_request() ? 'https' : 'http';
			$hostname = $_SERVER['HTTP_HOST'];
			$dir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
			$core = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__) ?: '')), -1, PREG_SPLIT_NO_EMPTY);
			$core = $core[0] ?? '';
			$tmplt = $atRoot ? ($atCore ? "%s://%s/%s/" : "%s://%s/") : ($atCore ? "%s://%s/%s/" : "%s://%s%s");
			$end = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
			$base_url = sprintf($tmplt, $http, $hostname, $end);
		} else {
			$base_url = 'http://localhost/';
		}
		if ($parse) {
			$base_url = parse_url($base_url);
			if (isset($base_url['path']) && $base_url['path'] === '/') {
				$base_url['path'] = '';
			}
		}
		return $base_url;
	}
}
$base_url = base_url();

if (isset($_SERVER['HTTP_HOST'])) {
	$host = strtolower((string)$_SERVER['HTTP_HOST']);
	// Strip port from host for localhost detection (e.g. localhost:8080 → localhost)
	$hostWithoutPort = preg_replace('/:\d+$/', '', $host);
	$isLocalHost = in_array($hostWithoutPort, ['localhost', '127.0.0.1', '::1'], true);
	if (!$isLocalHost) {
		$base_url = preg_replace('/^http:\/\//i', 'https://', (string)$base_url);
	}
}

if (!empty($site_url) && is_https_request()) {
	$site_url = preg_replace('/^http:\/\//i', 'https://', (string)$site_url);
}

// Application debug flag - set to true to enable developer debug logging (not recommended on production)
if (!defined('APP_DEBUG')) {
	define('APP_DEBUG', false);
}
// Encryption key for e-izin approve links. Change this to a secure secret in production.
if (!defined('APP_ENC_KEY')) {
	define('APP_ENC_KEY', 'change_this_to_secure_key_2025');
}
// NOTE: Do NOT close PHP tag at end of this configuration file to avoid accidental output (whitespace/BOM).