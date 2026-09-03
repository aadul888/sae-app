<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
require_once('../../../library/function.php');
require_once '../../login/user.php';

$modul_id = 131;
include __DIR__ . '/../check_role.php';

function sx_check($type) {
  global $data_role;
  if (!isset($data_role[$type]) || $data_role[$type] !== 'Y') { echo 'Akses ditolak.'; exit; }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

  // ====== GET SINGLE (for edit / preview) ======
  case 'get':
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $q = $connection->query("SELECT * FROM surat_template WHERE id=$id LIMIT 1");
    if ($q && $r = $q->fetch_assoc()) {
      echo json_encode(['status' => 'success', 'data' => $r]);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
    }
    break;

  // ====== ADD ======
  case 'add':
    sx_check('modifikasi');
    $indeks_id     = (int)($_POST['indeks_id'] ?? 0);
    $link_dokumen  = $connection->real_escape_string($_POST['link_dokumen'] ?? '');
    $deskripsi     = $connection->real_escape_string($_POST['deskripsi'] ?? '');

    if ($indeks_id <= 0 || empty($link_dokumen)) {
      echo 'Indeks Surat dan Link Dokumen wajib diisi.'; break;
    }

    // Ambil indeks_surat & jenis_surat dari surat_index berdasarkan indeks_id
    $q_ix = $connection->query("SELECT indeks, jenis_surat FROM surat_index WHERE id=$indeks_id LIMIT 1");
    if (!$q_ix || !($ix_row = $q_ix->fetch_assoc())) {
      echo 'Indeks tidak ditemukan.'; break;
    }
    $indeks_surat = $connection->real_escape_string($ix_row['indeks']);
    $jenis_surat  = $connection->real_escape_string($ix_row['jenis_surat'] ?? '');

    // Nama pembuat dari user yang login
    $nama_pembuat = '';
    if (isset($current_user)) {
      $front = isset($current_user['gelar_depan']) ? trim($current_user['gelar_depan']) : '';
      $name  = isset($current_user['fullname']) ? trim($current_user['fullname']) : '';
      $back  = isset($current_user['gelar_belakang']) ? trim($current_user['gelar_belakang']) : '';
      $nama_pembuat = trim($front . ' ' . $name . ' ' . $back);
    }
    $nama_pembuat = $connection->real_escape_string($nama_pembuat);
    $variabel_tag = ''; // tidak diisi manual, nanti via scan_tags

    // Ekstrak drive_file_id dari link
    $drive_file_id = '';
    if (file_exists(__DIR__ . '/../../../library/google_drive_helper.php')) {
      require_once __DIR__ . '/../../../library/google_drive_helper.php';
      $drive_file_id = GoogleDriveHelper::extractFileId($link_dokumen);
    }

    $s = $connection->prepare("INSERT INTO surat_template (indeks_id, indeks_surat, jenis_surat, nama_pembuat, variabel_tag, link_dokumen, deskripsi, drive_file_id) VALUES (?,?,?,?,?,?,?,?)");
    $s->bind_param('isssssss', $indeks_id, $indeks_surat, $jenis_surat, $nama_pembuat, $variabel_tag, $link_dokumen, $deskripsi, $drive_file_id);
    echo $s->execute() ? 'success' : 'Gagal: ' . $connection->error;
    $s->close();
    break;

  // ====== UPDATE ======
  case 'update':
    sx_check('modifikasi');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo 'ID tidak valid.'; break; }
    $indeks_id     = (int)($_POST['indeks_id'] ?? 0);
    $link_dokumen  = $connection->real_escape_string($_POST['link_dokumen'] ?? '');
    $deskripsi     = $connection->real_escape_string($_POST['deskripsi'] ?? '');

    if ($indeks_id <= 0 || empty($link_dokumen)) {
      echo 'Indeks Surat dan Link Dokumen wajib diisi.'; break;
    }

    // Ambil indeks_surat & jenis_surat dari surat_index
    $q_ix = $connection->query("SELECT indeks, jenis_surat FROM surat_index WHERE id=$indeks_id LIMIT 1");
    if (!$q_ix || !($ix_row = $q_ix->fetch_assoc())) {
      echo 'Indeks tidak ditemukan.'; break;
    }
    $indeks_surat = $connection->real_escape_string($ix_row['indeks']);
    $jenis_surat  = $connection->real_escape_string($ix_row['jenis_surat'] ?? '');

    // Nama pembuat dari user yang login
    $nama_pembuat = '';
    if (isset($current_user)) {
      $front = isset($current_user['gelar_depan']) ? trim($current_user['gelar_depan']) : '';
      $name  = isset($current_user['fullname']) ? trim($current_user['fullname']) : '';
      $back  = isset($current_user['gelar_belakang']) ? trim($current_user['gelar_belakang']) : '';
      $nama_pembuat = trim($front . ' ' . $name . ' ' . $back);
    }
    $nama_pembuat = $connection->real_escape_string($nama_pembuat);

    // Ekstrak drive_file_id dari link
    $drive_file_id = '';
    if (file_exists(__DIR__ . '/../../../library/google_drive_helper.php')) {
      require_once __DIR__ . '/../../../library/google_drive_helper.php';
      $drive_file_id = GoogleDriveHelper::extractFileId($link_dokumen);
    }

    $u = $connection->prepare("UPDATE surat_template SET indeks_id=?, indeks_surat=?, jenis_surat=?, nama_pembuat=?, link_dokumen=?, deskripsi=?, drive_file_id=? WHERE id=?");
    $u->bind_param('issssssi', $indeks_id, $indeks_surat, $jenis_surat, $nama_pembuat, $link_dokumen, $deskripsi, $drive_file_id, $id);
    echo $u->execute() ? 'success' : 'Gagal: ' . $connection->error;
    $u->close();
    break;

  // ====== DELETE ======
  case 'delete':
    sx_check('hapus');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo 'ID tidak valid.'; break; }
    $stmt_dst = $connection->prepare("DELETE FROM surat_template WHERE id=?");
    $stmt_dst->bind_param('i', $id);
    echo $stmt_dst->execute() ? 'success' : 'Gagal: ' . $connection->error;
    $stmt_dst->close();
    break;

  // ====== SCAN {{tags}} FROM GOOGLE DOCS ======
  case 'scan_tags':
    sx_check('modifikasi');
    header('Content-Type: application/json');
    $template_id = (int)($_POST['template_id'] ?? 0);
    if ($template_id <= 0) { echo json_encode(['status'=>'error','message'=>'Template tidak valid.']); exit; }

    $q = $connection->query("SELECT drive_file_id FROM surat_template WHERE id=$template_id LIMIT 1");
    if (!$q || !($r = $q->fetch_assoc()) || empty($r['drive_file_id'])) {
      echo json_encode(['status'=>'error','message'=>'Google Doc ID belum diatur pada template ini.']); exit;
    }
    $docId = trim($r['drive_file_id']);

    // Ambil setting kredensial
    $qs = $connection->query("SELECT google_credentials FROM surat_setting WHERE id=1 LIMIT 1");
    $set = $qs ? $qs->fetch_assoc() : null;
    if (!$set || empty($set['google_credentials'])) {
      echo json_encode(['status'=>'error','message'=>'Kredensial Google API belum dikonfigurasi.']); exit;
    }
    $creds_path = __DIR__ . '/../../../content/' . $set['google_credentials'];
    if (!file_exists($creds_path)) {
      echo json_encode(['status'=>'error','message'=>'File kredensial tidak ditemukan.']); exit;
    }

    try {
      require_once __DIR__ . '/../../../library/google_drive_helper.php';
      $gdrive = new GoogleDriveHelper($creds_path);

      $html = $gdrive->downloadDirectExport($docId);
      if ($html === false) {
        echo json_encode(['status'=>'error','message'=>'Gagal download dokumen. Pastikan dokumen di-share "Anyone with the link" atau sudah dishare ke Service Account.']);
        exit;
      }

      preg_match_all('/\{\{(\w+)\}\}/', $html, $matches);
      $seen = [];
      $tagNames = [];
      foreach ($matches[1] as $name) {
        if (!isset($seen[$name])) {
          $seen[$name] = true;
          $tagNames[] = $name;
        }
      }

      $fields = [];
      foreach ($tagNames as $name) {
        $label = ucwords(str_replace('_', ' ', $name));
        $type = 'text';
        if (preg_match('/(tanggal|tgl|date)/i', $name)) $type = 'date';
        if (preg_match('/(alamat|address|isi|keterangan|catatan)/i', $name)) $type = 'textarea';
        $fields[] = ['name' => $name, 'label' => $label, 'type' => $type];
      }

      // Simpan ke kolom variabel_tag sebagai daftar {{tag}} dipisah koma
      $tags_str = '';
      foreach ($tagNames as $name) {
        $tags_str .= '{{' . $name . '}}, ';
      }
      $tags_str = rtrim($tags_str, ', ');
      $stmt_vt = $connection->prepare("UPDATE surat_template SET variabel_tag=? WHERE id=?");
    $stmt_vt->bind_param('si', $tags_str, $template_id);
    $stmt_vt->execute();
    $stmt_vt->close();

      echo json_encode(['status'=>'success','fields'=>$fields,'count'=>count($fields)]);
    } catch (Exception $e) {
      echo json_encode(['status'=>'error','message'=>'Exception: '.$e->getMessage()]);
    }
    exit;
    break;

  default:
    echo 'Aksi tidak dikenali.';
    break;
}
