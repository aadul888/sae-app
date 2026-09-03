<?php
/**
 * check_role.php - Shared role checking helper
 * 
 * Checks permission for a specific modul_id using multi-level approach:
 * - Reads level_id from DB (via ADMIN_KEY cookie)
 * - Includes tugas_tambahan for OR-merge
 * - Returns $data_role array with lihat/modifikasi/hapus
 * 
 * Usage in module page:
 *   $modul_id = 10; // set modul_id before including
 *   include __DIR__ . '/../check_role.php';
 *   // $data_role is now available with 'lihat', 'modifikasi', 'hapus'
 *   // $has_access is true/false
 */

if (!isset($modul_id) && !empty($modul_route)) {
    $route_safe = mysqli_real_escape_string($connection, $modul_route);
    $qmod = $connection->query("SELECT modul_id FROM modul WHERE modul_route='$route_safe' LIMIT 1");
    if ($qmod && $qmod->num_rows > 0) {
        $modul_id = (int) $qmod->fetch_assoc()['modul_id'];
    }
}

if (!isset($modul_id)) {
    die('modul_id not set');
}

// Default
$data_role = array('lihat' => 'N', 'modifikasi' => 'N', 'hapus' => 'N');
$has_access = false;

// Determine admin level(s) from DB (preferred) or cookies (fallback)
$_cr_level_id = '';
$_cr_tugas_csv = '';
if (!empty($_COOKIE['ADMIN_KEY'])) {
    $_cr_admin_id = @epm_decode($_COOKIE['ADMIN_KEY']);
    $_cr_admin_id = anti_injection($_cr_admin_id);
    if (!empty($_cr_admin_id)) {
        $qadm = "SELECT level_id, tugas_tambahan FROM admin WHERE admin_id='" . intval($_cr_admin_id) . "' LIMIT 1";
        $radm = $connection->query($qadm);
        if ($radm && $radm->num_rows > 0) {
            $adm_row = $radm->fetch_assoc();
            $_cr_level_id = isset($adm_row['level_id']) ? $adm_row['level_id'] : '';
            $_cr_tugas_csv = isset($adm_row['tugas_tambahan']) ? $adm_row['tugas_tambahan'] : '';
        }
    }
}
if ($_cr_level_id === '') {
    $_cr_level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
}
if ($_cr_tugas_csv === '' && !empty($_COOKIE['tugas_tambahan'])) {
    $_cr_tugas_csv = $_COOKIE['tugas_tambahan'];
}

// Build levels array
$_cr_levels = array();
if ($_cr_level_id !== '') $_cr_levels[] = intval($_cr_level_id);
if (!empty($_cr_tugas_csv)) {
    $parts = preg_split('/\s*,\s*/', trim($_cr_tugas_csv));
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $_cr_levels[] = intval($p);
    }
}
$_cr_levels = array_values(array_unique($_cr_levels));

// Operator Sekolah bersifat superadmin: akses penuh ke semua modul.
$is_operator_superadmin = false;
if (count($_cr_levels) > 0) {
    $in_operator = implode(',', array_map('intval', $_cr_levels));
    $qop = "SELECT level_id FROM level WHERE level_id IN ($in_operator) AND level_nama='Operator Sekolah' LIMIT 1";
    $rop = $connection->query($qop);
    if ($rop && $rop->num_rows > 0) {
        $is_operator_superadmin = true;
    }
}

if ($is_operator_superadmin) {
    $data_role = array('lihat' => 'Y', 'modifikasi' => 'Y', 'hapus' => 'Y');
    $has_access = true;
} else {

// Query role with OR-merge
if (count($_cr_levels) > 0) {
    $in = implode(',', array_map('intval', $_cr_levels));
    $qr = "SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='" . intval($modul_id) . "' AND level_id IN ($in)";
} else {
    $qr = "SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='" . intval($modul_id) . "' AND level_id='" . intval($_cr_level_id) . "'";
}

$result_role = $connection->query($qr);
if ($result_role && $result_role->num_rows > 0) {
    while ($row = $result_role->fetch_assoc()) {
        if (isset($row['lihat']) && strtoupper($row['lihat']) == 'Y') $data_role['lihat'] = 'Y';
        if (isset($row['modifikasi']) && strtoupper($row['modifikasi']) == 'Y') $data_role['modifikasi'] = 'Y';
        if (isset($row['hapus']) && strtoupper($row['hapus']) == 'Y') $data_role['hapus'] = 'Y';
    }
    $has_access = ($data_role['lihat'] == 'Y');
}
}

// Expose level_id for modules that need it
$level_id = $_cr_level_id;

// Cleanup temp vars
unset($_cr_level_id, $_cr_tugas_csv, $_cr_levels, $_cr_admin_id, $qadm, $radm, $adm_row, $qr, $result_role, $row, $in, $parts, $p, $is_operator_superadmin, $in_operator, $qop, $rop);
