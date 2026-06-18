<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $modul_id = 49;
  include __DIR__ . '/../check_role.php';

  function bt_guard($type)
  {
    global $data_role;
    if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
      echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
      exit;
    }
  }

  function bt_log($connection, $guest_table_id, $guest_id, $activity)
  {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'admin';
    $stmt = $connection->prepare("INSERT INTO buku_tamu_log (guest_table_id, guest_id, activity, ip_address, user_agent) VALUES (?,?,?,?,?)");
    if ($stmt) {
      $stmt->bind_param('issss', $guest_table_id, $guest_id, $activity, $ip, $ua);
      $stmt->execute();
      $stmt->close();
    }
  }

  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $is_json = in_array($action, ['detail', 'checkout', 'hapus']);
  if ($is_json) header('Content-Type: application/json');

  switch ($action) {

    case 'detail':
      $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
      if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
      $stmt = $connection->prepare("SELECT * FROM buku_tamu WHERE id=?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if (!$row) { echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']); exit; }
      // survey (jika ada)
      $survey = null;
      $sres = $connection->query("SELECT * FROM buku_tamu_survey WHERE guest_table_id=" . intval($id) . " LIMIT 1");
      if ($sres && $sres->num_rows) $survey = $sres->fetch_assoc();
      echo json_encode(['status' => 'success', 'data' => $row, 'survey' => $survey]);
      break;

    case 'edit':
      bt_guard('modifikasi');
      $id = intval($_POST['id'] ?? 0);
      $nama = trim($_POST['nama'] ?? '');
      $instansi = trim($_POST['instansi'] ?? '');
      $telepon = trim($_POST['telepon'] ?? '');
      $keperluan = trim($_POST['keperluan'] ?? '');
      $keterangan = trim($_POST['keterangan'] ?? '');
      $status = in_array($_POST['status'] ?? '', ['Aktif', 'Selesai', 'Batal']) ? $_POST['status'] : 'Aktif';
      if (!$id || $nama === '' || $instansi === '' || $keperluan === '') { echo 'Data tidak lengkap'; exit; }

      $stmt = $connection->prepare("UPDATE buku_tamu SET nama=?, instansi=?, telepon=?, keperluan=?, keterangan=?, status=? WHERE id=?");
      $stmt->bind_param('ssssssi', $nama, $instansi, $telepon, $keperluan, $keterangan, $status, $id);
      if ($stmt->execute()) {
        $g = $connection->query("SELECT guest_id FROM buku_tamu WHERE id=" . intval($id))->fetch_row()[0] ?? '';
        bt_log($connection, $id, $g, 'UPDATE');
        echo 'Data tamu berhasil diupdate';
      } else {
        echo 'Gagal mengupdate: ' . $stmt->error;
      }
      $stmt->close();
      break;

    case 'checkout':
      bt_guard('modifikasi');
      $id = intval($_POST['id'] ?? 0);
      if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
      $row = $connection->query("SELECT guest_id, status FROM buku_tamu WHERE id=" . intval($id))->fetch_assoc();
      if (!$row) { echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']); exit; }
      if ($row['status'] !== 'Aktif') { echo json_encode(['status' => 'error', 'message' => 'Tamu sudah tidak aktif']); exit; }
      $connection->query("UPDATE buku_tamu SET status='Selesai', waktu_keluar=CURTIME() WHERE id=" . intval($id));
      bt_log($connection, $id, $row['guest_id'], 'CHECKOUT');
      echo json_encode(['status' => 'success', 'message' => 'Tamu berhasil check-out']);
      break;

    case 'hapus':
      bt_guard('hapus');
      $id = intval($_POST['id'] ?? 0);
      if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
      $row = $connection->query("SELECT guest_id FROM buku_tamu WHERE id=" . intval($id))->fetch_assoc();
      $connection->query("DELETE FROM buku_tamu_survey WHERE guest_table_id=" . intval($id));
      $connection->query("DELETE FROM buku_tamu_log WHERE guest_table_id=" . intval($id));
      $connection->query("DELETE FROM buku_tamu WHERE id=" . intval($id));
      echo json_encode(['status' => 'success', 'message' => 'Data tamu berhasil dihapus']);
      break;

    default:
      echo 'Action tidak valid';
      break;
  }
}
