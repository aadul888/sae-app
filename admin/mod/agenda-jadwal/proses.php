<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $admin_id = intval(epm_decode($_COOKIE['ADMIN_KEY']));

  switch ($_GET['action'] ?? '') {

    case 'approve':
      $id = intval($_POST['id'] ?? 0);
      if (!$id) { echo 'ID tidak valid'; exit; }
      $connection->query("UPDATE agenda_edit_request SET status='approved', responded_by='$admin_id', responded_at=NOW() WHERE id=$id AND status='pending'");
      echo $connection->affected_rows > 0 ? 'Permintaan berhasil disetujui' : 'Permintaan tidak ditemukan atau sudah direspon';
      break;

    case 'reject':
      $id = intval($_POST['id'] ?? 0);
      if (!$id) { echo 'ID tidak valid'; exit; }
      $connection->query("UPDATE agenda_edit_request SET status='rejected', responded_by='$admin_id', responded_at=NOW() WHERE id=$id AND status='pending'");
      echo $connection->affected_rows > 0 ? 'Permintaan berhasil ditolak' : 'Permintaan tidak ditemukan atau sudah direspon';
      break;

    default:
      echo 'Action tidak valid';
      break;
  }
}
