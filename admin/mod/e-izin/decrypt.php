<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../../../library/config.php';
require_once '../../../library/function.php';

$e = isset($_GET['e']) ? $_GET['e'] : '';
if (empty($e)) {
    http_response_code(400);
    echo 'Parameter tidak ditemukan.';
    exit;
}
function base64url_decode($data)
{
    $b64 = strtr($data, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad > 0) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    return base64_decode($b64, true);
}

$decoded = base64url_decode($e);
if ($decoded === false || strlen($decoded) <= 16) {
    http_response_code(400);
    echo 'Payload tidak valid.';
    exit;
}

$iv = substr($decoded, 0, 16);
$ct = substr($decoded, 16);

$key = hash('sha256', APP_ENC_KEY, true);
$plain_compressed = openssl_decrypt($ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
if ($plain_compressed === false || strlen($plain_compressed) === 0) {
    http_response_code(400);
    echo 'Gagal mendekripsi payload.';
    exit;
}

$plain = @gzinflate($plain_compressed);
if ($plain === false) {
    $plain = $plain_compressed;
}

parse_str($plain, $params);

if (empty($params['id']) || empty($params['token'])) {
    http_response_code(400);
    echo 'Parameter approve tidak lengkap.';
    exit;
}

$_GET['id'] = intval($params['id']);
$_GET['token'] = $params['token'];
if (!empty($params['role'])) {
    $_GET['role'] = preg_replace('/[^a-z0-9_-]/i', '', $params['role']);
}

$approve_file = __DIR__ . DIRECTORY_SEPARATOR . 'approve.php';
if (!is_file($approve_file) || !is_readable($approve_file)) {
    http_response_code(500);
    echo 'Handler approve tidak tersedia.';
    exit;
}

ob_start();
include $approve_file;
$out = ob_get_clean();
echo $out;
exit;
