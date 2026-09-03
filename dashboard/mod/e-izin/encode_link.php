<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../library/config.php';
require_once '../../../library/function.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$role = isset($_GET['role']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['role']) : '';
$actionParam = isset($_GET['action']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['action']) : '';
if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID izin tidak valid"]);
    exit;
}

if ($role === 'admin' || $role === 'wali' || $role === 'security') {
    if ($role === 'admin') {
        $stmt = $connection->prepare("SELECT token_admin AS token FROM e_izin WHERE id = ? LIMIT 1");
    } elseif ($role === 'wali') {
        $stmt = $connection->prepare("SELECT token_wali AS token FROM e_izin WHERE id = ? LIMIT 1");
    } else {
        $stmt = $connection->prepare("SELECT token_security AS token FROM e_izin WHERE id = ? LIMIT 1");
    }
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            if (!empty($r['token'])) {
                $token = $r['token'];
                $stmt->close();
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $script_name = $_SERVER['SCRIPT_NAME'];
                $parts = explode('/', trim($script_name, '/'));
                $first = $parts[0] ?? '';
                $root_prefix = ($first === 'dashboard' || $first === 'admin' || $first === '') ? '' : '/' . $first;
                $admin_base = rtrim($protocol . '://' . $host . $root_prefix, '/');
                // Ensure we do not accidentally include a '/dashboard' segment immediately
                // before '/admin' (requests from inside dashboard may cause this).
                $admin_base = preg_replace('#/dashboard(?=/admin)#i', '', $admin_base);
                $link = $admin_base . '/admin/mod/e-izin/approve.php?t=' . $token;
                if (!empty($role)) $link .= '&role=' . urlencode($role);
                echo json_encode(["success" => true, "link" => $link]);
                exit;
            }
        }
        $stmt->close();
    }
}

if (!in_array($role, ['admin', 'wali', 'security'], true)) {
    echo json_encode(["success" => false, "message" => "Role harus 'admin', 'wali' atau 'security' untuk membuat short link"]);
    exit;
}

try {
    $created = false;
    for ($i = 0; $i < 4; $i++) {
        $candidate = bin2hex(random_bytes(4));
        if ($role === 'admin') {
            $ins = $connection->prepare("UPDATE e_izin SET token_admin = ? WHERE id = ? LIMIT 1");
        } elseif ($role === 'wali') {
            $ins = $connection->prepare("UPDATE e_izin SET token_wali = ? WHERE id = ? LIMIT 1");
        } else {
            $ins = $connection->prepare("UPDATE e_izin SET token_security = ? WHERE id = ? LIMIT 1");
        }
        if ($ins) {
            $ins->bind_param('si', $candidate, $id);
            $ok = $ins->execute();
            $ins->close();
            if ($ok) {
                $created = true;
                $token = $candidate;
                break;
            }
        }
    }
    if (!$created) {
        $fallback_token = md5($id . '|' . ($row['tanggal'] ?? '') . '|' . ($row['nama_lengkap'] ?? '') . '|APPROVE2025');
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        $parts = explode('/', trim($script_name, '/'));
        $first = $parts[0] ?? '';
        $root_prefix = ($first === 'dashboard' || $first === 'admin' || $first === '') ? '' : '/' . $first;
        $admin_base = rtrim($protocol . '://' . $host . $root_prefix, '/');
        $admin_base = preg_replace('#/dashboard(?=/admin)#i', '', $admin_base);
        $link = $admin_base . '/admin/mod/e-izin/approve.php?id=' . intval($id) . '&token=' . $fallback_token;
        if (!empty($role)) $link .= '&role=' . urlencode($role);
        echo json_encode(["success" => true, "link" => $link]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
    exit;
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$parts = explode('/', trim($script_name, '/'));
$first = $parts[0] ?? '';
$root_prefix = ($first === 'dashboard' || $first === 'admin' || $first === '') ? '' : '/' . $first;
$admin_base = rtrim($protocol . '://' . $host . $root_prefix, '/');
$admin_base = preg_replace('#/dashboard(?=/admin)#i', '', $admin_base);
$link = $admin_base . '/admin/mod/e-izin/approve.php?t=' . $token;
if (!empty($role)) $link .= '&role=' . urlencode($role);
if (!empty($actionParam)) $link .= '&action=' . urlencode($actionParam);
echo json_encode(["success" => true, "link" => $link]);
exit;
