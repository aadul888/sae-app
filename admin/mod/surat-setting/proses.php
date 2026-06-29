<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../login/user.php';

$modul_id = 132;
include __DIR__ . '/../check_role.php';

function sx_check($type) {
  global $data_role;
  if (!isset($data_role[$type]) || $data_role[$type] !== 'Y') { echo 'Akses ditolak.'; exit; }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

  // ====== SAVE ALL SETTINGS ======
  case 'save_settings':
    sx_check('modifikasi');

    $kop_nama_sekolah = $connection->real_escape_string($_POST['kop_nama_sekolah'] ?? '');
    $kop_alamat       = $connection->real_escape_string($_POST['kop_alamat'] ?? '');
    $google_creds     = '';

    // Handle file upload for Google credentials JSON
    if (isset($_FILES['google_credentials']) && $_FILES['google_credentials']['error'] === UPLOAD_ERR_OK) {
      $file_name = $_FILES['google_credentials']['name'];
      $tmp_name  = $_FILES['google_credentials']['tmp_name'];
      $size      = $_FILES['google_credentials']['size'];
      $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

      if ($ext !== 'json') {
        echo 'Hanya file JSON yang diizinkan.'; exit;
      }
      if ($size > 2 * 1024 * 1024) {
        echo 'Ukuran file maksimal 2 MB.'; exit;
      }

      // Validate JSON content
      $content = file_get_contents($tmp_name);
      $parsed = json_decode($content, true);
      if (!$parsed || !isset($parsed['type']) || $parsed['type'] !== 'service_account') {
        echo 'File JSON tidak valid. Pastikan file adalah Service Account JSON dari Google Cloud.'; exit;
      }

      $folder = '../../../content/';
      $dest_name = 'google-credentials-' . time() . '.json';
      $dest_path = $folder . $dest_name;

      // Hapus file lama jika ada
      $q_old = $connection->query("SELECT google_credentials FROM surat_setting WHERE id=1 LIMIT 1");
      if ($q_old && $r_old = $q_old->fetch_assoc()) {
        $old_file = $r_old['google_credentials'] ?? '';
        if (!empty($old_file) && file_exists($folder . $old_file)) {
          @unlink($folder . $old_file);
        }
      }

      // Extract client_email from JSON
      $client_email = $parsed['client_email'] ?? '';

      if (move_uploaded_file($tmp_name, $dest_path)) {
        $google_creds = $dest_name;
      } else {
        echo 'Gagal menyimpan file. Coba lagi.'; exit;
      }
    }

    // Build query dynamically — only update google_credentials if a new file was uploaded
    $spreadsheet_id    = $connection->real_escape_string(trim($_POST['spreadsheet_id'] ?? ''));
    $spreadsheet_range = $connection->real_escape_string(trim($_POST['spreadsheet_range'] ?? 'Sheet1'));

    if (!empty($google_creds)) {
      $s = $connection->prepare("UPDATE surat_setting SET kop_nama_sekolah=?, kop_alamat=?, google_credentials=?, client_email=?, spreadsheet_id=?, spreadsheet_range=? WHERE id=1");
      $s->bind_param('ssssss', $kop_nama_sekolah, $kop_alamat, $google_creds, $client_email, $spreadsheet_id, $spreadsheet_range);
    } else {
      $s = $connection->prepare("UPDATE surat_setting SET kop_nama_sekolah=?, kop_alamat=?, spreadsheet_id=?, spreadsheet_range=? WHERE id=1");
      $s->bind_param('ssss', $kop_nama_sekolah, $kop_alamat, $spreadsheet_id, $spreadsheet_range);
    }
    $s->execute();
    $s->close();

    // 2) Update admin gelar_depan / gelar_belakang
    $kepsek_admin_id = (int)($_POST['kepsek_admin_id'] ?? 0);
    $gelar_depan     = $connection->real_escape_string(trim($_POST['kepsek_gelar_depan'] ?? ''));
    $gelar_belakang  = $connection->real_escape_string(trim($_POST['kepsek_gelar_belakang'] ?? ''));

    if ($kepsek_admin_id > 0) {
      $u = $connection->prepare("UPDATE admin SET gelar_depan = ?, gelar_belakang = ? WHERE admin_id = ?");
      $u->bind_param('ssi', $gelar_depan, $gelar_belakang, $kepsek_admin_id);
      $u->execute();
      $u->close();
    }

    echo 'success';
    break;

  // ====== LOAD SINGLE KEPSEK DATA ======
  case 'load_kepsek':
    $admin_id = (int)($_GET['admin_id'] ?? 0);
    if ($admin_id <= 0) { echo json_encode(['status' => 'error']); exit; }

    $q = $connection->query("SELECT admin_id, fullname, gelar_depan, gelar_belakang, gtk_nip FROM admin WHERE admin_id=$admin_id LIMIT 1");
    if ($q && $r = $q->fetch_assoc()) {
      echo json_encode(['status' => 'success', 'data' => $r]);
    } else {
      echo json_encode(['status' => 'error']);
    }
    break;

  // ====== GET CURRENT SETTINGS ======
  case 'get_settings':
    $q = $connection->query("SELECT * FROM surat_setting WHERE id=1 LIMIT 1");
    $data = [];
    if ($q && $r = $q->fetch_assoc()) {
      $data = $r;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    break;

  default:
    echo 'Action tidak dikenali.';
    break;
}
