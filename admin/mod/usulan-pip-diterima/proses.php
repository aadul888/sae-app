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
    $id_clean = $connection->real_escape_string($id);
  $q = $connection->query("SELECT u_p.*, u.avatar AS user_avatar, u.nisn AS user_nisn, u.nama_lengkap AS user_nama, k.nama_kelas, u.jenis_kelamin, CONCAT_WS(', ', u.alamat, CONCAT('RT ', u.rt), CONCAT('RW ', u.rw), u.desa, u.kecamatan, u.kodepos) AS alamat_lengkap, u.telp AS no_hp FROM usulan_pip u_p LEFT JOIN user u ON u_p.user_id=u.user_id LEFT JOIN kelas k ON u.kelas=k.kelas_id WHERE u_p.usulan_pip_id='$id_clean' LIMIT 1");
    if($q && $q->num_rows){
      $row = $q->fetch_assoc();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success'=>true,'data'=>$row]);
    } else {
      header('Content-Type: application/json; charset=utf-8', true, 404);
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
    // Accept optional criteria and alasan from approve modal
    $kriteria_raw = isset($_POST['kriteria']) ? trim($_POST['kriteria']) : '';
    $alasan_usulan = isset($_POST['alasan_usulan']) ? trim($_POST['alasan_usulan']) : '';
  // Map frontend legacy codes to usulan_pip.status values
  // client sends: '-' to reset to Pending, 'Y' for Disetujui, 'N' for Ditolak
  $statusMap = ['-' => 'Pending', 'Y' => 'Disetujui', 'N' => 'Ditolak'];
  $dbStatus = isset($statusMap[$status]) ? $statusMap[$status] : $status;
  $id_clean = $connection->real_escape_string($rawId);
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
          else $names[] = $connection->real_escape_string($c);
        }
      } else {
        $parts = array_filter(array_map('trim', explode(',', $kriteria_raw)));
        foreach ($parts as $c) {
          if ($c === '') continue;
          if (ctype_digit($c)) $ids[] = intval($c);
          else $names[] = $connection->real_escape_string($c);
        }
      }
      $conds = [];
      if (!empty($ids)) $conds[] = 'id IN (' . implode(',', $ids) . ')';
      if (!empty($names)) {
        $qnames = array_map(function($n){ return "'".$n."'"; }, $names);
        $conds[] = 'nama_kriteria IN (' . implode(',', $qnames) . ')';
      }
      if (!empty($conds)) {
        $where = implode(' OR ', $conds);
        $qr = $connection->query("SELECT SUM(poin) AS total FROM kriteria_pip WHERE $where LIMIT 1");
        if ($qr && $qr->num_rows) {
          $r = $qr->fetch_assoc();
          $poin = intval($r['total'] ?? 0);
        }
      }
    }
    $alasan_usulan_clean = $connection->real_escape_string($alasan_usulan);

    // Update status and fields depending on the target state
    $update = "";
    if ($dbStatus_clean === 'Disetujui') {
      // Accepted: set keterangan to 'Diusulkan', save poin and alasan_usulan
      $update = "UPDATE usulan_pip SET status='$dbStatus_clean', keterangan='Diusulkan', poin='" . intval($poin) . "', alasan_usulan='$alasan_usulan_clean' WHERE usulan_pip_id='$id_clean'";
    } elseif ($dbStatus_clean === 'Ditolak') {
      // Rejected: set keterangan to the provided reason and clear poin
      $reason_clean = $connection->real_escape_string($reason);
      $update = "UPDATE usulan_pip SET status='$dbStatus_clean', keterangan='$reason_clean', poin=0 WHERE usulan_pip_id='$id_clean'";
    } elseif ($dbStatus_clean === 'Pending') {
      // Reset to Pending: clear keterangan and set status to Pending
      $update = "UPDATE usulan_pip SET status='$dbStatus_clean', keterangan='' WHERE usulan_pip_id='$id_clean'";
    } else {
      // Other statuses: update only status
      $update = "UPDATE usulan_pip SET status='$dbStatus_clean' WHERE usulan_pip_id='$id_clean'";
    }

    header('Content-Type: application/json; charset=utf-8');
    if($connection->query($update) === false) {
      $msg = 'DB error: ' . $connection->error . ' | QUERY: ' . $update;
      echo json_encode(['success' => false, 'message' => $msg]);
      exit;
    } else {
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
  $id_clean = $connection->real_escape_string($rawId);
  $del = "DELETE FROM usulan_pip WHERE usulan_pip_id='$id_clean'";
  header('Content-Type: application/json; charset=utf-8');
  if($connection->query($del) === false){
    echo json_encode(['success' => false, 'message' => $connection->error]);
    exit;
  } else {
    echo json_encode(['success' => true]);
    exit;
  }

break;
}}