<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
  require_once'../../../library/config.php';
  require_once('../../../library/function.php');
  require_once'../../login/user.php';

switch (@$_GET['action']){
  case 'get':
    $id = !empty($_GET['id']) ? $_GET['id'] : null;
    if(function_exists('convert')){
      $maybe = @convert('decrypt', $id);
      if($maybe) $id = $maybe;
    }
    $stmt = $connection->prepare("SELECT u_p.*, u.avatar AS user_avatar, u.nisn AS user_nisn, u.nama_lengkap AS user_nama, u.tempat_lahir, u.tanggal_lahir, u.nama_ayah, u.pekerjaan_ayah, u.nama_ibu, u.pekerjaan_ibu, u.nama_wali, u.pekerjaan_wali, u.telp, u.email, k.nama_kelas, u.jenis_kelamin, CONCAT_WS(', ', u.alamat, CONCAT('RT ', u.rt), CONCAT('RW ', u.rw), u.desa, u.kecamatan, u.kodepos) AS alamat_lengkap FROM usulan_pip u_p LEFT JOIN user u ON u_p.user_id=u.user_id LEFT JOIN kelas k ON u.kelas=k.kelas_id WHERE u_p.usulan_pip_id=? LIMIT 1");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $q = $stmt->get_result();
    if($q && $q->num_rows){
      $row = $q->fetch_assoc();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success'=>true,'data'=>$row]);
    } else {
      header('Content-Type: application/json; charset=utf-8', true, 404);
      echo json_encode(['success'=>false,'message'=>'Not found']);
    }
    $stmt->close();
    exit;

  case 'search_siswa':
    $nisn = !empty($_POST['nisn']) ? trim($_POST['nisn']) : '';
    if (empty($nisn)) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'NISN tidak boleh kosong']);
      exit;
    }
    
    $stmt = $connection->prepare("
      SELECT u.*, k.nama_kelas, b.validasi_berkas,
             CONCAT_WS(', ', u.alamat, CONCAT('RT ', u.rt), CONCAT('RW ', u.rw), u.desa, u.kecamatan, u.kodepos) AS alamat_lengkap,
             (SELECT COUNT(*) FROM usulan_pip WHERE user_id = u.user_id AND status IN ('Pending', 'Disetujui', 'Diproses')) AS has_active_usulan
      FROM user u 
      LEFT JOIN kelas k ON u.kelas = k.kelas_id 
      LEFT JOIN berkas b ON u.user_id = b.user_id
      WHERE u.nisn = ? 
      LIMIT 1
    ");
    $stmt->bind_param('s', $nisn);
    $stmt->execute();
    $q = $stmt->get_result();
    
    header('Content-Type: application/json; charset=utf-8');
    if ($q && $q->num_rows > 0) {
      $data = $q->fetch_assoc();
      if ($data['has_active_usulan'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Siswa sudah memiliki usulan PIP yang aktif (Pending/Diproses/Disetujui)']);
      } else {
        echo json_encode(['success' => true, 'data' => $data]);
      }
    } else {
      echo json_encode(['success' => false, 'message' => 'Data siswa dengan NISN tersebut tidak ditemukan']);
    }
    exit;

  case 'create':
    $nisn = !empty($_POST['nisn']) ? trim($_POST['nisn']) : '';
    $alasan_usulan = !empty($_POST['alasan_usulan']) ? trim($_POST['alasan_usulan']) : 'Usulan dari Wali Kelas';
    
    if (empty($nisn)) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'NISN harus diisi']);
      exit;
    }
    
    // Debug: log NISN yang diterima
    error_log("Action create menerima NISN: " . $nisn);
    
    $nisn_clean = $connection->real_escape_string($nisn);
    $alasan_usulan_clean = $connection->real_escape_string($alasan_usulan);
    
    // Cek duplikasi berdasarkan NISN - cari apakah sudah ada usulan aktif dengan NISN yang sama
    $stmt_check = $connection->prepare("
      SELECT up.usulan_pip_id, u.nama_lengkap, u.nisn, up.status 
      FROM usulan_pip up 
      JOIN user u ON up.user_id = u.user_id 
      WHERE u.nisn = ? 
      AND up.status IN ('Pending', 'Disetujui', 'Diproses') 
      LIMIT 1
    ");
    $stmt_check->bind_param('s', $nisn_clean);
    $stmt_check->execute();
    $q_check_nisn = $stmt_check->get_result();
    
    if ($q_check_nisn && $q_check_nisn->num_rows > 0) {
      $existing = $q_check_nisn->fetch_assoc();
      $stmt_check->close();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'success' => false, 
        'message' => 'Siswa dengan NISN ' . $nisn . ' (' . $existing['nama_lengkap'] . ') sudah memiliki usulan PIP yang aktif dengan status: ' . $existing['status']
      ]);
      exit;
    }
    $stmt_check->close();
    
    // Cari user_id berdasarkan NISN dengan query yang persis sama dengan search_siswa
    $stmt_user = $connection->prepare("
      SELECT u.*, k.nama_kelas, b.validasi_berkas, b.kks, b.kip,
             CONCAT_WS(', ', u.alamat, CONCAT('RT ', u.rt), CONCAT('RW ', u.rw), u.desa, u.kecamatan, u.kodepos) AS alamat_lengkap,
             (SELECT COUNT(*) FROM usulan_pip WHERE user_id = u.user_id AND status IN ('Pending', 'Disetujui', 'Diproses')) AS has_active_usulan
      FROM user u 
      LEFT JOIN kelas k ON u.kelas = k.kelas_id 
      LEFT JOIN berkas b ON u.user_id = b.user_id
      WHERE u.nisn = ? 
      LIMIT 1
    ");
    $stmt_user->bind_param('s', $nisn_clean);
    $stmt_user->execute();
    $q_user = $stmt_user->get_result();
    
    if (!$q_user || $q_user->num_rows == 0) {
      // Test query sederhana untuk debug
      $stmt_test = $connection->prepare("SELECT COUNT(*) as count FROM user WHERE nisn = ?");
      $stmt_test->bind_param('s', $nisn_clean);
      $stmt_test->execute();
      $test_q = $stmt_test->get_result();
      $test_result = $test_q ? $test_q->fetch_assoc()['count'] : 0;
      $stmt_test->close();
      
      $stmt_user->close();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Data siswa tidak ditemukan di action create. NISN: ' . $nisn . ', Count di tabel user: ' . $test_result]);
      exit;
    }
    
    $user_data = $q_user->fetch_assoc();
    $stmt_user->close();
    $user_id = $user_data['user_id'];
    $user_nisn = $user_data['nisn'];
    $user_nama = $user_data['nama_lengkap'];
    $user_kelas = $user_data['nama_kelas'] ? $user_data['nama_kelas'] : 'Tidak Diketahui';
    $user_kks_file = $user_data['kks'] ? $user_data['kks'] : '';
    $user_kip_file = $user_data['kip'] ? $user_data['kip'] : '';
    
    // Set status kepemilikan berkas berdasarkan file yang ada
    $pertanyaan_1 = $user_kks_file ? 'Ya' : 'Tidak'; // Kepemilikan KKS/PKH
    $pertanyaan_2 = $user_kip_file ? 'Ya' : 'Tidak'; // Kepemilikan KIP
    
    // Insert usulan baru dengan field yang sesuai struktur tabel usulan_pip
    $tanggal_pengajuan = date('Y-m-d H:i:s');
    $keterangan = 'Diusulkan oleh admin';
    $stmt_insert = $connection->prepare("INSERT INTO usulan_pip (user_id, nisn, nama_lengkap, kelas, tempat_lahir, tanggal_lahir, pertanyaan_1, kks_file, pertanyaan_2, kip_file, status, alasan_usulan, tanggal_pengajuan, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Disetujui', ?, ?, ?)");
    $stmt_insert->bind_param('sssssssssssss', $user_id, $user_nisn, $user_nama, $user_kelas, $user_data['tempat_lahir'], $user_data['tanggal_lahir'], $pertanyaan_1, $user_kks_file, $pertanyaan_2, $user_kip_file, $alasan_usulan_clean, $tanggal_pengajuan, $keterangan);
    
    header('Content-Type: application/json; charset=utf-8');
    if ($stmt_insert->execute()) {
      echo json_encode(['success' => true, 'message' => 'Usulan PIP berhasil dibuat']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Gagal membuat usulan: ' . $stmt_insert->error]);
    }
    $stmt_insert->close();
    exit;

  case 'status':
  $id = !empty($_POST['id']) ? htmlentities($_POST['id']) : null;
  // Try to decrypt id if frontend sent an encrypted id
  $rawId = $id;
  if (function_exists('convert')) {
    $maybe = @convert('decrypt', $id);
    if ($maybe) $rawId = $maybe;
  }
  $status = !empty($_POST['status']) ? htmlentities($_POST['status']) : null;
  $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $no_kks = isset($_POST['no_kks']) ? trim($_POST['no_kks']) : '';
    $no_kip = isset($_POST['no_kip']) ? trim($_POST['no_kip']) : '';
    // Additional fields from approval modal
    $kriteria_raw = isset($_POST['kriteria']) ? trim($_POST['kriteria']) : '';
    $alasan_usulan = isset($_POST['alasan_usulan']) ? trim($_POST['alasan_usulan']) : '';
  // Map frontend legacy codes to usulan_pip.status values
  // client sends: '-' for Diproses, 'Y' for Disetujui, 'N' for Ditolak
  $statusMap = ['-' => 'Diproses', 'Y' => 'Disetujui', 'N' => 'Ditolak'];
  $dbStatus = isset($statusMap[$status]) ? $statusMap[$status] : $status;
  $dbStatus_clean = $connection->real_escape_string($dbStatus);
    $no_kks_clean = $connection->real_escape_string($no_kks);
    $no_kip_clean = $connection->real_escape_string($no_kip);
  
    // compute poin from criteria by summing values from kriteria_pip table
    $poin = 0;
    if ($kriteria_raw !== '') {
      $decoded = json_decode($kriteria_raw, true);
      $ids = [];
      $names = [];
      if (is_array($decoded)) {
        foreach ($decoded as $c) {
          $c = trim((string)$c);
          if ($c === '') continue;
          if (ctype_digit($c)) $ids[] = intval($c);
          else $names[] = $c;
        }
      } else {
        // fallback: comma separated names
        $parts = array_filter(array_map('trim', explode(',', $kriteria_raw)));
        foreach ($parts as $c) {
          if ($c === '') continue;
          if (ctype_digit($c)) $ids[] = intval($c);
          else $names[] = $c;
        }
      }
      $conds = [];
      $poin_params = [];
      $poin_types = '';
      if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $conds[] = "id IN ($placeholders)";
        foreach ($ids as $v) { $poin_params[] = $v; $poin_types .= 'i'; }
      }
      if (!empty($names)) {
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $conds[] = "nama_kriteria IN ($placeholders)";
        foreach ($names as $v) { $poin_params[] = $v; $poin_types .= 's'; }
      }
      if (!empty($conds)) {
        $where = implode(' OR ', $conds);
        $stmt_poin = $connection->prepare("SELECT SUM(poin) AS total FROM kriteria_pip WHERE $where LIMIT 1");
        if ($stmt_poin) {
          if (!empty($poin_params)) {
            $stmt_poin->bind_param($poin_types, ...$poin_params);
          }
          $stmt_poin->execute();
          $qr = $stmt_poin->get_result();
          if ($qr && $qr->num_rows) {
            $r = $qr->fetch_assoc();
            $poin = intval($r['total'] ?? 0);
          }
          $stmt_poin->close();
        }
      }
    }

    if ($dbStatus_clean === 'Disetujui') {
      // Accepted: set keterangan to 'Diusulkan', save poin and alasan_usulan
      $keterangan = 'Diusulkan';
      $stmt_upd = $connection->prepare("UPDATE usulan_pip SET status=?, keterangan=?, poin=?, alasan_usulan=? WHERE usulan_pip_id=?");
      $stmt_upd->bind_param('ssiss', $dbStatus_clean, $keterangan, $poin, $alasan_usulan, $rawId);
    } elseif ($dbStatus_clean === 'Ditolak') {
      // Rejected: set keterangan to the provided reason
      $reason_clean = $connection->real_escape_string($reason);
      $poin_zero = 0;
      $stmt_upd = $connection->prepare("UPDATE usulan_pip SET status=?, keterangan=?, poin=? WHERE usulan_pip_id=?");
      $stmt_upd->bind_param('ssis', $dbStatus_clean, $reason_clean, $poin_zero, $rawId);
    } else {
      // Status lain
      $stmt_upd = $connection->prepare("UPDATE usulan_pip SET status=? WHERE usulan_pip_id=?");
      $stmt_upd->bind_param('ss', $dbStatus_clean, $rawId);
    }

    header('Content-Type: application/json; charset=utf-8');
    if($stmt_upd->execute() === false) {
      $msg = 'DB error: ' . $stmt_upd->error;
      echo json_encode(['success' => false, 'message' => $msg]);
      $stmt_upd->close();
      exit;
    } else {
      $stmt_upd->close();
      echo json_encode(['success' => true]);
      exit;
    }

break;
case 'delete':
  $id = !empty($_POST['id']) ? htmlentities($_POST['id']) : null;
  // decrypt id if needed (frontend encrypts id)
  // try to decrypt using convert('decrypt', ...), if function exists
  $rawId = $id;
  if(function_exists('convert')){
    $maybe = @convert('decrypt', $id);
    if($maybe) $rawId = $maybe;
  }
  
  // Get file information before deleting
  $stmt_files = $connection->prepare("SELECT berkas_kks, berkas_kip FROM usulan_pip WHERE usulan_pip_id=? LIMIT 1");
  $stmt_files->bind_param('s', $rawId);
  $stmt_files->execute();
  $q_files = $stmt_files->get_result();
  $files_to_delete = [];
  if($q_files && $q_files->num_rows > 0) {
    $file_row = $q_files->fetch_assoc();
    if(!empty($file_row['berkas_kks']) && $file_row['berkas_kks'] != '') {
      $files_to_delete[] = '../../../content/berkas/' . $file_row['berkas_kks'];
    }
    if(!empty($file_row['berkas_kip']) && $file_row['berkas_kip'] != '') {
      $files_to_delete[] = '../../../content/berkas/' . $file_row['berkas_kip'];
    }
  }
  $stmt_files->close();
  
  // Delete from database
  $stmt_del = $connection->prepare("DELETE FROM usulan_pip WHERE usulan_pip_id=?");
  $stmt_del->bind_param('s', $rawId);
  header('Content-Type: application/json; charset=utf-8');
  if($stmt_del->execute() === false){
    echo json_encode(['success' => false, 'message' => $stmt_del->error]);
    $stmt_del->close();
    exit;
  } else {
    // Delete associated files after successful database deletion
    $deleted_files = [];
    $failed_files = [];
    foreach($files_to_delete as $file_path) {
      if(file_exists($file_path)) {
        if(unlink($file_path)) {
          $deleted_files[] = basename($file_path);
        } else {
          $failed_files[] = basename($file_path);
        }
      }
    }
    
    $message = 'Data usulan berhasil dihapus dari database';
    if(!empty($deleted_files)) {
      $message .= '. File dihapus: ' . implode(', ', $deleted_files);
    }
    if(!empty($failed_files)) {
      $message .= '. Gagal menghapus file: ' . implode(', ', $failed_files);
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    $stmt_del->close();
    exit;
  }

break;
}}