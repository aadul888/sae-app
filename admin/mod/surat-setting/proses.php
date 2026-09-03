<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
require_once('../../../library/function.php');
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

    // Simpan OAuth Client ID & Client Secret
    $oauth_client_id     = $connection->real_escape_string(trim($_POST['oauth_client_id'] ?? ''));
    $oauth_client_secret = $connection->real_escape_string(trim($_POST['oauth_client_secret'] ?? ''));
    $spreadsheet_id      = $connection->real_escape_string(trim($_POST['spreadsheet_id'] ?? ''));
    $spreadsheet_range   = $connection->real_escape_string(trim($_POST['spreadsheet_range'] ?? 'Sheet1'));
    $drive_folder_id     = $connection->real_escape_string(trim($_POST['drive_folder_id'] ?? ''));

    // Handle file upload for Google credentials JSON
    if (isset($_FILES['google_credentials']) && $_FILES['google_credentials']['error'] === UPLOAD_ERR_OK) {
      // Old Service Account upload code â€” kept for backward compatibility
      // but not needed for OAuth; we skip it now
    }

    // Save OAuth + Spreadsheet + Drive Folder settings
    $s = $connection->prepare("UPDATE surat_setting SET oauth_client_id=?, oauth_client_secret=?, spreadsheet_id=?, spreadsheet_range=?, drive_folder_id=? WHERE id=1");
    $s->bind_param('sssss', $oauth_client_id, $oauth_client_secret, $spreadsheet_id, $spreadsheet_range, $drive_folder_id);
    $s->execute();
    $s->close();

    // Update admin gelar_depan / gelar_belakang
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
