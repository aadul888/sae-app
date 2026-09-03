<?php 
session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

require_once'../../../library/config.php';
require_once('../../../library/function.php');
require_once'../../login/user.php';

// Set error reporting to prevent HTML errors in JSON response
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Ensure JSON response headers
header('Content-Type: application/json; charset=utf-8');

// Handle action from GET or POST
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action){
  case 'test':
    echo json_encode(['success' => true, 'message' => 'Test berhasil', 'timestamp' => date('Y-m-d H:i:s')]);
    exit;
    
  case 'get':
    $id = !empty($_GET['id']) ? $_GET['id'] : null;
    if(function_exists('convert')){
      $maybe = @convert('decrypt', $id);
      if($maybe) $id = $maybe;
    }
    $id_clean = $connection->real_escape_string($id);
  $q = $connection->query("SELECT u_p.*, u.avatar AS user_avatar, u.nisn AS user_nisn, u.nama_lengkap AS user_nama, u.tempat_lahir, u.tanggal_lahir, u.no_kk, u.nama_ayah, u.pekerjaan_ayah, u.nama_ibu, u.pekerjaan_ibu, u.nama_wali, u.pekerjaan_wali, u.telp, u.email, k.nama_kelas, u.jenis_kelamin, b.kk AS berkas_kk, b.kks AS berkas_kks, b.kip AS berkas_kip, CONCAT_WS(', ', u.alamat, CONCAT('RT ', u.rt), CONCAT('RW ', u.rw), u.desa, u.kecamatan, u.kodepos) AS alamat_lengkap, u.telp AS no_hp FROM usulan_pip u_p LEFT JOIN user u ON u_p.user_id=u.user_id LEFT JOIN kelas k ON u.kelas=k.kelas_id LEFT JOIN berkas b ON u.user_id=b.user_id WHERE u_p.usulan_pip_id='$id_clean' LIMIT 1");
    if($q && $q->num_rows){
      $row = $q->fetch_assoc();
      echo json_encode(['success'=>true,'data'=>$row]);
    } else {
      http_response_code(404);
      echo json_encode(['success'=>false,'message'=>'Not found']);
    }
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
  // Map frontend legacy codes to usulan_pip.status values
  // client sends: '-' for Diproses, 'Y' for Disetujui, 'N' for Ditolak
  $statusMap = ['-' => 'Diproses', 'Y' => 'Disetujui', 'N' => 'Ditolak'];
  $dbStatus = isset($statusMap[$status]) ? $statusMap[$status] : $status;
  $id_clean = $connection->real_escape_string($rawId);
  $dbStatus_clean = $connection->real_escape_string($dbStatus);
    $no_kks_clean = $connection->real_escape_string($no_kks);
    $no_kip_clean = $connection->real_escape_string($no_kip);
  
    // Update status dan keterangan untuk semua kondisi
    $update = "";
    if ($dbStatus_clean === 'Disetujui') {
      // Accepted: set keterangan to 'Diusulkan'
      $update = "UPDATE usulan_pip SET status='$dbStatus_clean', keterangan='Diusulkan' WHERE usulan_pip_id='$id_clean'";
    } elseif ($dbStatus_clean === 'Ditolak') {
      // Rejected: set keterangan to the provided reason
      // Note: some installations don't have an `alasan_penolakan` column; avoid updating non-existent columns
      $reason_clean = $connection->real_escape_string($reason);
      $update = "UPDATE usulan_pip SET status='$dbStatus_clean', keterangan='$reason_clean' WHERE usulan_pip_id='$id_clean'";
    } else {
      // Status lain
      $update = "UPDATE usulan_pip SET status='$dbStatus_clean' WHERE usulan_pip_id='$id_clean'";
    }

    if($connection->query($update) === false) {
      $msg = 'DB error: ' . $connection->error . ' | QUERY: ' . $update;
      echo json_encode(['success' => false, 'message' => $msg]);
      exit;
    } else {
      echo json_encode(['success' => true]);
      exit;
    }

break;
case 'set_rank':
    // expects POST: id (usulan_pip_id), posisi (int)
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $posisi = isset($_POST['posisi']) ? intval($_POST['posisi']) : null;
    if (empty($id) || $posisi === null || $posisi < 1) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Missing parameters or invalid position']);
      exit;
    }
    // try decrypt id if needed
    $rawId = $id;
    if (function_exists('convert')) {
      $maybe = @convert('decrypt', $id);
      if ($maybe) $rawId = $maybe;
    }
    $id_clean = $connection->real_escape_string($rawId);
    $pos_clean = intval($posisi);

    // Load current user info untuk deteksi wali kelas
    if (file_exists(__DIR__ . '/../../login/user.php')) {
        require_once __DIR__ . '/../../login/user.php';
    }

    // determine current admin's levels and kelas using same logic as datatable.php
    $current_level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
    $current_tugas_csv = '';
    if (!empty($_COOKIE['ADMIN_KEY'])) {
        $_tmp_aid = @epm_decode($_COOKIE['ADMIN_KEY']);
        $_tmp_aid = anti_injection($_tmp_aid);
        if (!empty($_tmp_aid)) {
            $q_tmp = $connection->query("SELECT level_id, tugas_tambahan FROM admin WHERE admin_id='" . intval($_tmp_aid) . "' LIMIT 1");
            if ($q_tmp && $q_tmp->num_rows > 0) {
                $r_tmp = $q_tmp->fetch_assoc();
                $current_level_id = isset($r_tmp['level_id']) ? $r_tmp['level_id'] : $current_level_id;
                $current_tugas_csv = isset($r_tmp['tugas_tambahan']) ? $r_tmp['tugas_tambahan'] : '';
            }
        }
    }
    $all_levels = array();
    if ($current_level_id !== '') $all_levels[] = intval($current_level_id);
    if (!empty($current_tugas_csv)) {
        $parts = preg_split('/\s*,\s*/', trim($current_tugas_csv));
        foreach ($parts as $p) { $p = trim($p); if ($p !== '') $all_levels[] = intval($p); }
    }
    $all_levels = array_values(array_unique($all_levels));
    $is_wali_kelas = in_array(9, $all_levels);
    $is_superadmin = in_array(1, $all_levels);
    
    $kelas_id = '';
    
    // Auto-detect kelas berdasarkan wali kelas
    if (isset($current_user) && (isset($current_user['ptk_id']) || isset($current_user['admin_id']))) {
        $ptk_id = isset($current_user['ptk_id']) ? $current_user['ptk_id'] : '';
        $admin_id = isset($current_user['admin_id']) ? $current_user['admin_id'] : '';

        if (!empty($ptk_id)) {
            $q_wali = $connection->query("SELECT kelas_id FROM kelas WHERE wali_kelas_ptk_id='" . mysqli_real_escape_string($connection, $ptk_id) . "' LIMIT 1");
            if ($q_wali && $r_w = $q_wali->fetch_assoc()) {
                $kelas_id = $r_w['kelas_id'];
            }
        }

        // fallback: try matching by admin_id
        if ($kelas_id === '' && !empty($admin_id)) {
            $q_wali2 = $connection->query("SELECT kelas_id FROM kelas WHERE wali_kelas_admin_id='" . mysqli_real_escape_string($connection, $admin_id) . "' LIMIT 1");
            if ($q_wali2 && $r2 = $q_wali2->fetch_assoc()) {
                $kelas_id = $r2['kelas_id'];
            }
        }
    }
    
    // Cek permission: role modifikasi=Y untuk modul_id=31 (Usulan PIP Ranking)
    $is_allowed = false;
    if (count($all_levels) > 0) {
        $in_levels = implode(',', array_map('intval', $all_levels));
        $q_perm = $connection->query("SELECT modifikasi FROM role WHERE modul_id=31 AND level_id IN ($in_levels) AND modifikasi='Y' LIMIT 1");
        if ($q_perm && $q_perm->num_rows > 0) $is_allowed = true;
    }
    
    if (!$is_allowed) {
      header('Content-Type: application/json; charset=utf-8', true, 403);
      echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda tidak memiliki izin modifikasi untuk modul ini.']);
      exit;
    }

    // Update ranking position - wali kelas hanya bisa update kelas yang diwali
    if ($is_wali_kelas && !$is_superadmin && $kelas_id != '') {
        $upd = "UPDATE usulan_pip u_p JOIN user u ON u_p.user_id=u.user_id SET u_p.ranking_position='" . $pos_clean . "' WHERE u_p.usulan_pip_id='" . $id_clean . "' AND u.kelas='" . mysqli_real_escape_string($connection, $kelas_id) . "'";
    } else {
        $upd = "UPDATE usulan_pip SET ranking_position='" . $pos_clean . "' WHERE usulan_pip_id='" . $id_clean . "'";
    }

    if ($connection->query($upd) === false) {
      echo json_encode(['success' => false, 'message' => $connection->error]);
      exit;
    } else {
      echo json_encode(['success' => true]);
      exit;
    }

break;

case 'move_rank':
    // expects POST: id (usulan_pip_id), dir ('up'|'down')
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $dir = isset($_POST['dir']) ? $_POST['dir'] : null;
    if (empty($id) || !in_array($dir, ['up','down'], true)) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Missing parameters']);
      exit;
    }
    // decrypt id if necessary
    $rawId = $id;
    if (function_exists('convert')) {
      $maybe = @convert('decrypt', $id);
      if ($maybe) $rawId = $maybe;
    }
    $id_clean = $connection->real_escape_string($rawId);

    // Load current user info untuk deteksi wali kelas (sama seperti set_rank)
    if (file_exists(__DIR__ . '/../../login/user.php')) {
        require_once __DIR__ . '/../../login/user.php';
    }

    // Resolve admin levels dan wali kelas
    $current_level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
    $current_tugas_csv = '';
    if (!empty($_COOKIE['ADMIN_KEY'])) {
        $_tmp_aid = @epm_decode($_COOKIE['ADMIN_KEY']);
        $_tmp_aid = anti_injection($_tmp_aid);
        if (!empty($_tmp_aid)) {
            $q_tmp = $connection->query("SELECT level_id, tugas_tambahan FROM admin WHERE admin_id='" . intval($_tmp_aid) . "' LIMIT 1");
            if ($q_tmp && $q_tmp->num_rows > 0) {
                $r_tmp = $q_tmp->fetch_assoc();
                $current_level_id = isset($r_tmp['level_id']) ? $r_tmp['level_id'] : $current_level_id;
                $current_tugas_csv = isset($r_tmp['tugas_tambahan']) ? $r_tmp['tugas_tambahan'] : '';
            }
        }
    }
    $all_levels = array();
    if ($current_level_id !== '') $all_levels[] = intval($current_level_id);
    if (!empty($current_tugas_csv)) {
        $parts = preg_split('/\s*,\s*/', trim($current_tugas_csv));
        foreach ($parts as $p) { $p = trim($p); if ($p !== '') $all_levels[] = intval($p); }
    }
    $all_levels = array_values(array_unique($all_levels));
    $is_wali_kelas = in_array(9, $all_levels);
    $is_superadmin = in_array(1, $all_levels);
    
    $kelas_id = '';
    
    // Auto-detect kelas berdasarkan wali kelas
    if (isset($current_user) && (isset($current_user['ptk_id']) || isset($current_user['admin_id']))) {
        $ptk_id = isset($current_user['ptk_id']) ? $current_user['ptk_id'] : '';
        $admin_id = isset($current_user['admin_id']) ? $current_user['admin_id'] : '';

        if (!empty($ptk_id)) {
            $q_wali = $connection->query("SELECT kelas_id FROM kelas WHERE wali_kelas_ptk_id='" . mysqli_real_escape_string($connection, $ptk_id) . "' LIMIT 1");
            if ($q_wali && $r_w = $q_wali->fetch_assoc()) {
                $kelas_id = $r_w['kelas_id'];
            }
        }

        if ($kelas_id === '' && !empty($admin_id)) {
            $q_wali2 = $connection->query("SELECT kelas_id FROM kelas WHERE wali_kelas_admin_id='" . mysqli_real_escape_string($connection, $admin_id) . "' LIMIT 1");
            if ($q_wali2 && $r2 = $q_wali2->fetch_assoc()) {
                $kelas_id = $r2['kelas_id'];
            }
        }
    }
    
    // Cek permission: role modifikasi=Y untuk modul_id=31
    $is_allowed = false;
    if (count($all_levels) > 0) {
        $in_levels = implode(',', array_map('intval', $all_levels));
        $q_perm = $connection->query("SELECT modifikasi FROM role WHERE modul_id=31 AND level_id IN ($in_levels) AND modifikasi='Y' LIMIT 1");
        if ($q_perm && $q_perm->num_rows > 0) $is_allowed = true;
    }
    
    if (!$is_allowed) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda tidak memiliki izin modifikasi.']);
      exit;
    }

    // Get usulan details
    if ($is_wali_kelas && !$is_superadmin && $kelas_id != '') {
        $q = $connection->query("SELECT u_p.usulan_pip_id, u.user_id, u.kelas FROM usulan_pip u_p JOIN user u ON u_p.user_id=u.user_id WHERE u_p.usulan_pip_id='" . $id_clean . "' AND u.kelas='" . mysqli_real_escape_string($connection, $kelas_id) . "' LIMIT 1");
    } else {
        $q = $connection->query("SELECT u_p.usulan_pip_id, u.user_id, u.kelas FROM usulan_pip u_p JOIN user u ON u_p.user_id=u.user_id WHERE u_p.usulan_pip_id='" . $id_clean . "' LIMIT 1");
    }
    
    if (!$q || $q->num_rows == 0) {
      header('Content-Type: application/json; charset=utf-8', true, 403);
      echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau tidak diizinkan']);
      exit;
    }
    $row = $q->fetch_assoc();
    $user_kelas_id = $row['kelas'];

    // get ordered list of usulan untuk kelas ini
    $accepted = "u_p.status IN ('Disetujui','Diterima')";
    $orderSql = "ORDER BY (CASE WHEN u_p.ranking_position IS NULL THEN 1000000 ELSE u_p.ranking_position END) ASC, u_p.poin DESC, u_p.tanggal_pengajuan ASC";
    
    if ($is_wali_kelas && !$is_superadmin && $kelas_id != '') {
        $listQ = $connection->query("SELECT u_p.usulan_pip_id FROM usulan_pip u_p JOIN user u ON u_p.user_id=u.user_id WHERE u.kelas='" . mysqli_real_escape_string($connection, $kelas_id) . "' AND " . $accepted . " " . $orderSql);
    } else {
        $listQ = $connection->query("SELECT u_p.usulan_pip_id FROM usulan_pip u_p JOIN user u ON u_p.user_id=u.user_id WHERE u.kelas='" . mysqli_real_escape_string($connection, $user_kelas_id) . "' AND " . $accepted . " " . $orderSql);
    }
    
    if (!$listQ) {
      echo json_encode(['success' => false, 'message' => $connection->error]);
      exit;
    }
    $ids = [];
    while ($r = $listQ->fetch_assoc()) $ids[] = $r['usulan_pip_id'];
    $index = array_search($rawId, $ids);
    if ($index === false) {
      echo json_encode(['success' => false, 'message' => 'Item not in list']);
      exit;
    }
    if ($dir === 'up' && $index === 0) {
      echo json_encode(['success' => false, 'message' => 'Already at top']);
      exit;
    }
    if ($dir === 'down' && $index === count($ids)-1) {
      echo json_encode(['success' => false, 'message' => 'Already at bottom']);
      exit;
    }

    // swap by adjusting order in array
    if ($dir === 'up') {
      $tmp = $ids[$index-1];
      $ids[$index-1] = $ids[$index];
      $ids[$index] = $tmp;
    } else {
      $tmp = $ids[$index+1];
      $ids[$index+1] = $ids[$index];
      $ids[$index] = $tmp;
    }

    // apply sequential ranking_position = 1..N for this ordered list
    $connection->begin_transaction();
    $ok = true;
    foreach ($ids as $pos => $usulan_id) {
      $pos1 = intval($pos) + 1;
      $uEsc = $connection->real_escape_string($usulan_id);
      $upd = "UPDATE usulan_pip SET ranking_position='" . $pos1 . "' WHERE usulan_pip_id='" . $uEsc . "'";
      if ($connection->query($upd) === false) { $ok = false; break; }
    }
    if ($ok) {
      $connection->commit();
      echo json_encode(['success' => true]);
      exit;
    } else {
      $connection->rollback();
      echo json_encode(['success' => false, 'message' => $connection->error]);
      exit;
    }

break;

case 'update_dapodik':
    // Debug logging
    error_log('Dapodik update request received: ' . print_r($_POST, true));
    
    // Cek permission modifikasi dari role table untuk modul_id=31
    $current_level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
    $current_tugas_csv = '';
    if (!empty($_COOKIE['ADMIN_KEY'])) {
        $_tmp_aid = @epm_decode($_COOKIE['ADMIN_KEY']);
        $_tmp_aid = anti_injection($_tmp_aid);
        if (!empty($_tmp_aid)) {
            $q_tmp = $connection->query("SELECT level_id, tugas_tambahan FROM admin WHERE admin_id='" . intval($_tmp_aid) . "' LIMIT 1");
            if ($q_tmp && $q_tmp->num_rows > 0) {
                $r_tmp = $q_tmp->fetch_assoc();
                $current_level_id = isset($r_tmp['level_id']) ? $r_tmp['level_id'] : $current_level_id;
                $current_tugas_csv = isset($r_tmp['tugas_tambahan']) ? $r_tmp['tugas_tambahan'] : '';
            }
        }
    }
    $all_levels = array();
    if ($current_level_id !== '') $all_levels[] = intval($current_level_id);
    if (!empty($current_tugas_csv)) {
        $parts = preg_split('/\s*,\s*/', trim($current_tugas_csv));
        foreach ($parts as $p) { $p = trim($p); if ($p !== '') $all_levels[] = intval($p); }
    }
    $all_levels = array_values(array_unique($all_levels));
    
    $dapodik_allowed = false;
    if (count($all_levels) > 0) {
        $in_levels = implode(',', array_map('intval', $all_levels));
        $q_perm = $connection->query("SELECT modifikasi FROM role WHERE modul_id=31 AND level_id IN ($in_levels) AND modifikasi='Y' LIMIT 1");
        if ($q_perm && $q_perm->num_rows > 0) $dapodik_allowed = true;
    }
    error_log('Current level_id: ' . $current_level_id);
    
    if (!$dapodik_allowed) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda tidak memiliki izin modifikasi untuk mengubah status dapodik.']);
        exit;
    }
    
    $id = !empty($_POST['usulan_pip_id']) ? $_POST['usulan_pip_id'] : (!empty($_POST['id']) ? $_POST['id'] : null);
    $status = !empty($_POST['dapodik_status']) ? $_POST['dapodik_status'] : (!empty($_POST['status']) ? $_POST['status'] : 'N');
    
    error_log('Processing dapodik update - ID: ' . $id . ', Status: ' . $status);
    
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID usulan tidak valid']);
        exit;
    }
    
    // Validasi status (Y atau N)
    $status = in_array($status, ['Y', 'N']) ? $status : 'N';
    
    $id_clean = $connection->real_escape_string($id);
    $status_clean = $connection->real_escape_string($status);
    
    // Cek apakah kolom dapodik_status ada, jika tidak ada maka buat
    $checkCol = $connection->query("SHOW COLUMNS FROM usulan_pip LIKE 'dapodik_status'");
    if (!$checkCol || $checkCol->num_rows == 0) {
        error_log('Creating dapodik_status column...');
        $addCol = "ALTER TABLE usulan_pip ADD COLUMN dapodik_status ENUM('Y','N') DEFAULT 'N' COMMENT 'Status konfirmasi input ke Dapodik'";
        if (!$connection->query($addCol)) {
            http_response_code(500);
            error_log('Failed to create dapodik_status column: ' . $connection->error);
            echo json_encode(['success' => false, 'message' => 'Gagal menambah kolom dapodik_status: ' . $connection->error]);
            exit;
        }
        error_log('dapodik_status column created successfully');
    }
    
    $update = "UPDATE usulan_pip SET dapodik_status='$status_clean' WHERE usulan_pip_id='$id_clean'";
    error_log('Executing update query: ' . $update);
    
    if ($connection->query($update) === false) {
        error_log('Update failed: ' . $connection->error);
        echo json_encode(['success' => false, 'message' => 'Update gagal: ' . $connection->error]);
        exit;
    } else {
        $affected_rows = $connection->affected_rows;
        
        // Verify update dengan query ulang
        $verify = $connection->query("SELECT dapodik_status FROM usulan_pip WHERE usulan_pip_id='$id_clean'");
        $current_status = 'N';
        if ($verify && $verify->num_rows > 0) {
            $row = $verify->fetch_assoc();
            $current_status = $row['dapodik_status'];
        }
        
        error_log('Update successful, affected rows: ' . $affected_rows . ', current status in DB: ' . $current_status);
        echo json_encode([
            'success' => true, 
            'message' => 'Status dapodik berhasil diupdate',
            'affected_rows' => $affected_rows,
            'id' => $id,
            'status' => $status,
            'db_status' => $current_status
        ]);
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
  $id_clean = $connection->real_escape_string($rawId);
  $del = "DELETE FROM usulan_pip WHERE usulan_pip_id='$id_clean'";
  if($connection->query($del) === false){
    echo json_encode(['success' => false, 'message' => $connection->error]);
    exit;
  } else {
    echo json_encode(['success' => true]);
    exit;
  }

break;

default:
  echo json_encode(['success' => false, 'message' => 'Invalid action']);
  exit;
}
?>