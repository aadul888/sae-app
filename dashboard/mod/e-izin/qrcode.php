<?php
require_once '../../../library/phpqrcode/phpqrcode.php';
require_once '../../../library/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$roleParam = isset($_GET['role']) ? strtolower(preg_replace('/[^a-z0-9_-]/i', '', $_GET['role'])) : '';
$actionParam = isset($_GET['action']) ? strtolower(preg_replace('/[^a-z0-9_-]/i', '', $_GET['action'])) : '';
$cardFlag = isset($_GET['card']) && intval($_GET['card']) === 1;

if ($id <= 0) {
    if (ob_get_length()) @ob_clean();
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    QRcode::png('ID tidak valid', false, QR_ECLEVEL_L, 6, 2);
    exit;
}

$stmt = $connection->prepare("SELECT e_izin.id, e_izin.tanggal, user.nama_lengkap FROM e_izin LEFT JOIN user ON e_izin.user_id = user.user_id WHERE e_izin.id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!($res && $res->num_rows > 0)) {
    if (ob_get_length()) @ob_clean();
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    QRcode::png('Data izin tidak ditemukan', false, QR_ECLEVEL_L, 6, 2);
    exit;
}
$row = $res->fetch_assoc();

$site_url = '';
$sres = $connection->query("SELECT site_url FROM setting LIMIT 1");
if ($sres) {
    $srow = $sres->fetch_assoc();
    if (!empty($srow['site_url'])) $site_url = trim($srow['site_url']);
}
if (!empty($site_url)) {
    if (!preg_match('#^https?://#i', $site_url)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $site_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($site_url, '/');
    }
    $base_url = rtrim($site_url, '/');
    $admin_base = $base_url; // site_url already points to app root
    // If site_url somehow contains an internal '/dashboard' before '/admin', strip it.
    $admin_base = preg_replace('#/dashboard(?=/admin)#i', '', $admin_base);
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $parts = explode('/', trim($script_name, '/'));
    $first = $parts[0] ?? '';
    // If script is under 'dashboard' or 'admin' at site root, assume app is deployed at root
    $root_prefix = ($first === 'dashboard' || $first === 'admin' || $first === '') ? '' : '/' . $first;
    $admin_base = rtrim($protocol . '://' . $host . $root_prefix, '/');
    $admin_base = preg_replace('#/dashboard(?=/admin)#i', '', $admin_base);
}

$fallback_token = md5($row['id'] . '|' . $row['tanggal'] . '|' . $row['nama_lengkap'] . '|APPROVE2025');

if ($roleParam === 'admin') $token_col = 'token_admin';
elseif ($roleParam === 'security') $token_col = 'token_security';
else $token_col = 'token_wali';

$short_token = '';
if (!empty($token_col)) {
    $sql = "SELECT " . $connection->real_escape_string($token_col) . " AS token FROM e_izin WHERE id = ? LIMIT 1";
    $chk = $connection->prepare($sql);
    if ($chk) {
        $chk->bind_param('i', $id);
        $chk->execute();
        $cres = $chk->get_result();
        if ($cres && $cres->num_rows > 0) {
            $crow = $cres->fetch_assoc();
            $existing = $crow['token'] ?? '';
            if (!empty($existing)) $short_token = $existing;
        }
        $chk->close();
    }
}

if ($short_token === '') {
    try {
        $candidate = bin2hex(random_bytes(4));
        if (!empty($token_col)) {
            $up_sql = "UPDATE e_izin SET " . $token_col . " = ? WHERE id = ? LIMIT 1";
            $up = $connection->prepare($up_sql);
            if ($up) {
                $up->bind_param('si', $candidate, $id);
                $ok = $up->execute();
                $up->close();
                if (!empty($ok)) $short_token = $candidate;
            }
        }
    } catch (Exception $e) {
        $short_token = '';
    }
}

if ($short_token !== '') {
    $link = $admin_base . '/admin/mod/e-izin/approve.php?t=' . $short_token;
    if (!empty($roleParam)) $link .= '&role=' . urlencode($roleParam);
    if (!empty($actionParam)) $link .= '&action=' . urlencode($actionParam);
    if ($cardFlag) $link .= '&card=1';
} else {
    $link = $admin_base . '/admin/mod/e-izin/approve.php?id=' . intval($row['id']) . '&token=' . $fallback_token;
    if (!empty($roleParam)) $link .= '&role=' . urlencode($roleParam);
    if (!empty($actionParam)) $link .= '&action=' . urlencode($actionParam);
    if ($cardFlag) $link .= '&card=1';
}

$stmt->close();
if (ob_get_length()) @ob_clean();
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
QRcode::png($link, false, QR_ECLEVEL_L, 6, 2);
exit;
