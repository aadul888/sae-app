<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $modul_id = 51;
  include __DIR__ . '/../check_role.php';
  header('Content-Type: application/json');

  function pkl_guard($type)
  {
    global $data_role;
    if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
      echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
      exit;
    }
  }

  $cfg_file = __DIR__ . '/../sync/pkl_sync_config.json';

  function pkl_load_config($cfg_file)
  {
    $cfg = ['pkl_base_url' => '', 'api_token' => ''];
    if (is_file($cfg_file)) {
      $tmp = json_decode((string)file_get_contents($cfg_file), true);
      if (is_array($tmp)) $cfg = array_merge($cfg, $tmp);
    }
    return $cfg;
  }

  // POST JSON with Bearer token; returns [ok(bool), http_code, body, err]
  function pkl_post_json($url, $token, $payload, $timeout = 60)
  {
    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $token,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
      ]);
      $body = curl_exec($ch);
      $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $err = curl_error($ch);
      curl_close($ch);
      return [($err === '' && $code >= 200 && $code < 300), $code, (string)$body, $err];
    }
    // Fallback: stream context
    $ctx = stream_context_create(['http' => [
      'method' => 'POST',
      'header' => implode("\r\n", $headers),
      'content' => $json,
      'timeout' => $timeout,
      'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = intval($m[1]);
    return [($body !== false && $code >= 200 && $code < 300), $code, (string)$body, $body === false ? 'request failed' : ''];
  }

  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  switch ($action) {

    case 'save_config':
      pkl_guard('modifikasi');
      $url = trim($_POST['pkl_base_url'] ?? '');
      $token = trim($_POST['api_token'] ?? '');
      if ($url === '' || $token === '') { echo json_encode(['status' => 'error', 'message' => 'URL dan token wajib diisi']); exit; }
      if (!filter_var($url, FILTER_VALIDATE_URL)) { echo json_encode(['status' => 'error', 'message' => 'URL tidak valid']); exit; }
      $data = ['pkl_base_url' => $url, 'api_token' => $token, 'updated_at' => date('Y-m-d H:i:s')];
      if (file_put_contents($cfg_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan konfigurasi (cek izin file)']);
        exit;
      }
      echo json_encode(['status' => 'success', 'message' => 'Konfigurasi berhasil disimpan']);
      break;

    case 'send':
      pkl_guard('modifikasi');
      $cfg = pkl_load_config($cfg_file);
      if (empty($cfg['pkl_base_url']) || empty($cfg['api_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Endpoint e-PKL belum dikonfigurasi']);
        exit;
      }
      $nisns = $_POST['nisn'] ?? [];
      if (!is_array($nisns) || count($nisns) === 0) { echo json_encode(['status' => 'error', 'message' => 'Tidak ada siswa terpilih']); exit; }
      $nisns = array_values(array_unique(array_filter(array_map('trim', $nisns))));
      if (count($nisns) === 0) { echo json_encode(['status' => 'error', 'message' => 'NISN tidak valid']); exit; }

      // Fetch student records (only active grade XII for safety)
      $place = implode(',', array_fill(0, count($nisns), '?'));
      $types = str_repeat('s', count($nisns));
      $stmt = $connection->prepare(
        "SELECT u.user_id, u.nisn, u.email, u.password, u.nama_lengkap, u.tempat_lahir, u.tanggal_lahir,
                u.jenis_kelamin, u.tingkat, u.telp, u.avatar, u.alamat,
                COALESCE(u.kelas_nama, k.nama_kelas, '') AS kelas,
                COALESCE(j.nama_jurusan, '') AS jurusan
         FROM user u
         LEFT JOIN kelas k ON u.kelas = k.kelas_id
         LEFT JOIN jurusan j ON u.jurusan_id = j.jurusan_id
         WHERE u.nisn IN ($place) AND LOWER(TRIM(u.status))='aktif'"
      );
      $stmt->bind_param($types, ...$nisns);
      $stmt->execute();
      $rs = $stmt->get_result();
      $records = [];
      while ($r = $rs->fetch_assoc()) $records[] = $r;
      $stmt->close();

      if (count($records) === 0) { echo json_encode(['status' => 'error', 'message' => 'Data siswa tidak ditemukan / tidak aktif']); exit; }

      $payload = [
        'source' => 'sae',
        'type' => 'pkl_peserta',
        'sent_at' => date('c'),
        'records' => $records,
      ];

      $url = $cfg['pkl_base_url'];
      $url .= (strpos($url, '?') !== false ? '&' : '?') . 'action=receive';
      list($ok, $code, $body, $err) = pkl_post_json($url, $cfg['api_token'], $payload);

      $now = date('Y-m-d H:i:s');
      $status_kirim = $ok ? 'Terkirim' : 'Gagal';
      $resp_msg = $ok ? ('HTTP ' . $code) : ('HTTP ' . $code . ($err ? ' - ' . $err : '') . ' ' . substr($body, 0, 300));

      // Upsert into pkl_peserta for each record
      $up = $connection->prepare(
        "INSERT INTO pkl_peserta (user_id, nisn, nama_lengkap, kelas, jurusan, status_kirim, response_msg, sent_at)
         VALUES (?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), nama_lengkap=VALUES(nama_lengkap), kelas=VALUES(kelas),
           jurusan=VALUES(jurusan), status_kirim=VALUES(status_kirim), response_msg=VALUES(response_msg), sent_at=VALUES(sent_at)"
      );
      foreach ($records as $r) {
        $uid = intval($r['user_id']);
        $sent = $ok ? $now : null;
        $up->bind_param('isssssss', $uid, $r['nisn'], $r['nama_lengkap'], $r['kelas'], $r['jurusan'], $status_kirim, $resp_msg, $sent);
        $up->execute();
      }
      $up->close();

      if ($ok) {
        echo json_encode(['status' => 'success', 'message' => 'Berhasil mengirim ' . count($records) . ' peserta ke e-PKL.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Pengiriman gagal (' . htmlspecialchars($resp_msg) . '). Status peserta ditandai Gagal.']);
      }
      break;

    default:
      echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
      break;
  }
}
