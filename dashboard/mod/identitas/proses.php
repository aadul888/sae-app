<?php
session_start();
require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../oauth/user.php';

if (!isset($_COOKIE['siswa'])) {
  echo 'error';
  exit;
}

$siswa_id = $data_user['user_id'] ?? '';
if (empty($siswa_id)) {
  echo 'error';
  exit;
}

// Proses konfirmasi identitas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi'])) {
  // Normalize and sanitize inputs
  $konfirmasi = $_POST['konfirmasi'] === 'Sesuai' ? 'Sesuai' : 'Belum Sesuai';
  $konfirmasi_time = time();

  // Ensure siswa_id is an integer to avoid injection via id
  $siswa_id_int = intval($siswa_id);

  $q = $connection->query("SELECT * FROM user WHERE user_id='" . $siswa_id_int . "'");
  $user_data = $q ? $q->fetch_assoc() : [];
  $snapshot = $user_data;
  unset($snapshot['konfirmasi'], $snapshot['konfirmasi_time'], $snapshot['konfirmasi_data']);

  // JSON encode (preserve unicode) then escape for safe insertion into SQL
  $konfirmasi_data_json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
  $konfirmasi_data_escaped = $connection->real_escape_string($konfirmasi_data_json);

  // Escape konfirmasi too (although values are controlled)
  $konfirmasi_escaped = $connection->real_escape_string($konfirmasi);

  $sql = "UPDATE user SET konfirmasi='" . $konfirmasi_escaped . "', konfirmasi_time='" . $konfirmasi_time . "', konfirmasi_data='" . $konfirmasi_data_escaped . "' WHERE user_id='" . $siswa_id_int . "'";
  if ($connection->query($sql) === true) {
    echo 'success';
  } else {
    // For debugging, you can uncomment the next line in development
    // echo $connection->error;
    echo 'error';
  }
  exit;
}

?>
