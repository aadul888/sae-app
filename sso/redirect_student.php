<?php
require_once __DIR__ . '/../library/config.php';
require_once __DIR__ . '/../library/function.php';
require_once __DIR__ . '/../library/sso_config.php';

function b64url_encode_sae($value)
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function redirect_login_sae($is_admin = false)
{
    global $base_url;
    if ($is_admin) {
        $login = (isset($base_url) && $base_url) ? rtrim($base_url, '/') . '/admin/login/' : '/admin/login/';
    } else {
        $login = (isset($base_url) && $base_url) ? rtrim($base_url, '/') . '/module/login/login.php' : '/module/login/login.php';
    }
    header('Location: ' . $login);
    exit;
}

$payload = null;
$target = PKL_USER_TARGET;

// 1) Prioritas admin SAE
if (!empty($_COOKIE['ADMIN_KEY']) && !empty($_COOKIE['KEY'])) {
    $admin_id = (int) epm_decode($_COOKIE['ADMIN_KEY']);
    $key_cookie = (string) $_COOKIE['KEY'];

    if ($admin_id > 0) {
        $stmt = $connection->prepare("SELECT admin_id, username, email, active FROM admin WHERE admin_id=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $admin_id);
            $stmt->execute();
            $ra = $stmt->get_result();
            if ($ra && $ra->num_rows > 0) {
                $admin = $ra->fetch_assoc();
                $expected_key = hash('sha256', (string) ($admin['username'] ?? ''));
                if (($admin['active'] ?? '') === 'Y' && hash_equals($expected_key, $key_cookie)) {
                    $payload = [
                        'role' => 'admin',
                        'admin_id' => (int) $admin['admin_id'],
                        'username' => (string) ($admin['username'] ?? ''),
                        'email' => (string) ($admin['email'] ?? ''),
                        'iat' => time(),
                        'exp' => time() + 120,
                    ];
                    $target = PKL_ADMIN_TARGET;
                }
            }
            $stmt->close();
        }
    }
}

// 2) Jika bukan admin SAE, pakai akun siswa SAE (hanya tingkat 12 + aktif)
if ($payload === null && !empty($_COOKIE['siswa'])) {
    $user_id = (int) convert('decrypt', (string) $_COOKIE['siswa']);
    if ($user_id > 0) {
        $stmt = $connection->prepare("SELECT u.user_id, u.nisn, u.status, u.tingkat, u.kelas_nama, k.nama_kelas, k.tingkat_pendidikan_id
               FROM user u
               LEFT JOIN kelas k ON u.kelas = k.kelas_id
               WHERE u.user_id=? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param('i', $user_id);
          $stmt->execute();
          $ru = $stmt->get_result();
          $stmt->close();
        } else { $ru = false; }
        if ($ru && $ru->num_rows > 0) {
            $user = $ru->fetch_assoc();
            $is_active = strtolower(trim((string) ($user['status'] ?? ''))) === 'aktif';
            $kelas_nama = strtoupper(trim((string) (($user['kelas_nama'] ?? '') ?: ($user['nama_kelas'] ?? ''))));
            $is_tingkat12 = (
                (string)($user['tingkat'] ?? '') === '12'
                || (string)($user['tingkat_pendidikan_id'] ?? '') === '12'
                || strpos($kelas_nama, 'XII') === 0
            );

            if ($is_active && $is_tingkat12 && !empty($user['nisn'])) {
                $payload = [
                    'role' => 'user12',
                    'user_id' => (int) $user['user_id'],
                    'nisn' => (string) $user['nisn'],
                    'iat' => time(),
                    'exp' => time() + 120,
                ];
                $target = PKL_USER_TARGET;
            }
        }
    }
}

if ($payload === null) {
    $is_admin_cookie = !empty($_COOKIE['ADMIN_KEY']) && !empty($_COOKIE['KEY']);
    redirect_login_sae($is_admin_cookie);
}

$body = b64url_encode_sae(json_encode($payload));
$sig = b64url_encode_sae(hash_hmac('sha256', $body, SSO_SECRET, true));
$token = $body . '.' . $sig;

$url = PKL_SSO_URL . '?token=' . urlencode($token) . '&target=' . urlencode($target);
header('Location: ' . $url);
exit;
