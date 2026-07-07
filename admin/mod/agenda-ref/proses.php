<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  switch ($action) {

    case 'tambah':
      $nama = trim($_POST['nama_mapel'] ?? '');
      $kode = trim($_POST['kode_mapel'] ?? '');
      $guru_id = intval($_POST['guru_id'] ?? 0);
      if (empty($nama)) { echo 'Nama mapel wajib diisi'; exit; }

      $stmt = $connection->prepare("INSERT INTO agenda_mapel (nama_mapel, kode_mapel, guru_id) VALUES (?, ?, ?)");
      $stmt->bind_param("ssi", $nama, $kode, $guru_id);
      echo $stmt->execute() ? 'Mata pelajaran berhasil ditambahkan' : 'Gagal menambahkan: ' . $stmt->error;
      $stmt->close();
      break;

    case 'edit':
      $id = intval($_POST['mapel_id'] ?? 0);
      $nama = trim($_POST['nama_mapel'] ?? '');
      $kode = trim($_POST['kode_mapel'] ?? '');
      $guru_id = intval($_POST['guru_id'] ?? 0);
      $aktif = in_array($_POST['aktif'] ?? '', ['Y','N']) ? $_POST['aktif'] : 'Y';
      if (!$id || empty($nama)) { echo 'Nama mapel wajib diisi'; exit; }

      $stmt = $connection->prepare("UPDATE agenda_mapel SET nama_mapel=?, kode_mapel=?, guru_id=?, aktif=? WHERE mapel_id=?");
      $stmt->bind_param("ssisi", $nama, $kode, $guru_id, $aktif, $id);
      echo $stmt->execute() ? 'Mata pelajaran berhasil diupdate' : 'Gagal mengupdate: ' . $stmt->error;
      $stmt->close();
      break;

    case 'hapus':
      $id = intval($_POST['mapel_id'] ?? 0);
      if (!$id) { echo 'ID tidak valid'; exit; }
      $connection->query("DELETE FROM agenda_mapel WHERE mapel_id=$id");
      echo $connection->affected_rows > 0 ? 'Berhasil dihapus' : 'Gagal menghapus';
      break;

    default:
      echo 'Action tidak valid';
      break;
  }
}
