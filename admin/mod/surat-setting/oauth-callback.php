<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
include '../../../library/function.php';
require_once '../../login/user.php';

$modul_id = 132;
include __DIR__ . '/../check_role.php';

if (!isset($data_role['modifikasi']) || $data_role['modifikasi'] != 'Y') {
  die('Akses ditolak.');
}

// ====== OAuth Callback dari Google ======
if (isset($_GET['code'])) {
  $code = $_GET['code'];

  // Ambil client_id dan client_secret dari database
  $q = $connection->query("SELECT oauth_client_id, oauth_client_secret FROM surat_setting WHERE id=1 LIMIT 1");
  $set = $q ? $q->fetch_assoc() : null;
  
  $client_id = $set['oauth_client_id'] ?? '';
  $client_secret = $set['oauth_client_secret'] ?? '';
  
  if (empty($client_id) || empty($client_secret)) {
    die('Client ID dan Client Secret belum dikonfigurasi. Kembali ke <a href="../../?mod=surat-setting">Pengaturan Surat</a>.');
  }
  
  // Tentukan redirect URI — harus sama persis dengan yang didaftarkan di Google Cloud Console
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'];
  $redirect_uri = $protocol . '://' . $host . '/admin/mod/surat-setting/oauth-callback.php';
  
  // Tukar authorization code dengan token
  $postData = http_build_query([
    'code' => $code,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
  ]);

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => 'https://oauth2.googleapis.com/token',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
  ]);
  $resp = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode !== 200) {
    die('Gagal mendapatkan token dari Google. HTTP ' . $httpCode . ': ' . htmlspecialchars($resp) . '<br><a href="../../?mod=surat-setting">Kembali ke Pengaturan Surat</a>');
  }

  $tokenData = json_decode($resp, true);
  if (!$tokenData || empty($tokenData['refresh_token'])) {
    die('Tidak ada refresh_token. Pastikan Anda memberikan akses penuh.<br>Response: ' . htmlspecialchars($resp) . '<br><a href="../../?mod=surat-setting">Kembali ke Pengaturan Surat</a>');
  }

  // Simpan token ke database
  $refresh_token = $connection->real_escape_string($tokenData['refresh_token']);
  $token_json = $connection->real_escape_string($resp);
  
  $connection->query("UPDATE surat_setting SET oauth_refresh_token='$refresh_token', oauth_token_json='$token_json' WHERE id=1");

  // Ambil info user Google (email, name)
  $access_token = $tokenData['access_token'];
  $ch2 = curl_init();
  curl_setopt_array($ch2, [
    CURLOPT_URL => 'https://www.googleapis.com/oauth2/v2/userinfo',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access_token],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 15,
  ]);
  $userInfo = curl_exec($ch2);
  $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
  curl_close($ch2);

  if ($httpCode2 === 200) {
    $userData = json_decode($userInfo, true);
    if ($userData && isset($userData['email'])) {
      $email = $connection->real_escape_string($userData['email']);
      $connection->query("UPDATE surat_setting SET oauth_email='$email' WHERE id=1");
    }
  }

  // Redirect kembali ke halaman pengaturan dengan pesan sukses
  header('Location: ../../?mod=surat-setting&oauth=success');
  exit;
}

// Jika tidak ada code, redirect ke pengaturan
header('Location: ../../?mod=surat-setting&oauth=error');
exit;
