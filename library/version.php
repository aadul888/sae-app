<?php
/**
 * SAE Versioning & Configuration
 * ---------------------------------
 * Satu file untuk semua kebutuhan terkait versi aplikasi.
 *
 * @version 2.0 (consolidated)
 */

if (!isset($connection) || !($connection instanceof mysqli)) {
    // Dummy connection jika file ini di-include tanpa koneksi DB aktif
    // untuk mencegah error fatal pada definisi fungsi.
    $connection = null;
}

// --- Fungsi Helper Utama ---

/**
 * Mendapatkan semester_id default berdasarkan tanggal saat ini.
 * Juli ke atas = semester 1, sebelum Juli = semester 2 tahun sebelumnya.
 */
function sae_get_default_semester(): string {
    return date('Y') . (date('n') >= 7 ? '1' : '2');
}

/**
 * Mendapatkan semester_id aktif dari database.
 *
 * @param mysqli|null $dbConn
 * @return string
 */
function sae_get_active_semester(?mysqli $dbConn): string {
    $fallback = sae_get_default_semester();
    if ($dbConn === null) {
        return $fallback;
    }

    $query = "SELECT semester_id FROM sync_rombongan_belajar WHERE semester_id REGEXP '^[0-9]{5}$' ORDER BY semester_id DESC LIMIT 1";
    $result = $dbConn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return trim((string)$row['semester_id']);
    }

    return $fallback;
}

/**
 * Hash-based deterministic version: MAJOR.MINOR.PATCH
 * MAJOR = semester_id, MINOR & PATCH derived from first 5 hex chars of $commitHash.
 * Same commit → same version everywhere.
 */
function sae_version_from_commit(mysqli $dbConn, string $commitHash): string {
    $major = sae_get_active_semester($dbConn);
    $hash = trim($commitHash);
    if (strlen($hash) < 2) return "$major.1.a";
    // Encode minor(1-9) + patch(a-f) from first 2 hex chars
    $val = hexdec(substr($hash, 0, 2)) % 54;          // 0-53, enough for 9*6=54 combos
    $minor = intdiv($val, 6) + 1;                      // 1-9
    $patch = chr(ord('a') + ($val % 6));               // a-f
    return "$major.$minor.$patch";
}

// --- Definisi Konstanta Global ---

$saeSemesterId = sae_get_active_semester($connection);
$saeAppYear = substr($saeSemesterId, 0, 4);
$saeVersion = $saeSemesterId; // Default version (hanya major)

// Coba ambil versi lengkap dari tabel setting untuk tampilan UI
if ($connection) {
    $q_setting_ver = $connection->query("SELECT app_version FROM setting WHERE site_id=1 LIMIT 1");
    if ($q_setting_ver && $r = $q_setting_ver->fetch_assoc()) {
        if (!empty($r['app_version'])) {
            $saeVersion = $r['app_version'];
        }
    }
}


if (!defined('SAE_SEMESTER_ID')) define('SAE_SEMESTER_ID', $saeSemesterId);
if (!defined('SAE_APP_YEAR')) define('SAE_APP_YEAR', $saeAppYear);
if (!defined('SAE_VERSION')) define('SAE_VERSION', $saeVersion);
if (!defined('SAE_APP_NAME')) define('SAE_APP_NAME', 'Smart Apps Education');
