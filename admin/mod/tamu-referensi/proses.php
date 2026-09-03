<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $modul_id = 50;
  include __DIR__ . '/../check_role.php';

  function ref_guard($type)
  {
    global $data_role;
    if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
      echo 'Akses ditolak: Anda tidak memiliki hak akses.';
      exit;
    }
  }

  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  switch ($action) {

    // ---------- INSTANSI ----------
    case 'tambah_instansi':
      ref_guard('modifikasi');
      $nama = trim($_POST['nama'] ?? '');
      $jenis = trim($_POST['jenis'] ?? '');
      $telepon = trim($_POST['telepon'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $alamat = trim($_POST['alamat'] ?? '');
      if ($nama === '') { echo 'Nama instansi wajib diisi'; exit; }

      $stmt = $connection->prepare("INSERT INTO tamu_instansi (nama, jenis, telepon, email, alamat) VALUES (?,?,?,?,?)");
      $stmt->bind_param('sssss', $nama, $jenis, $telepon, $email, $alamat);
      if ($stmt->execute()) {
        echo 'Instansi berhasil ditambahkan';
      } else {
        echo ($connection->errno == 1062) ? 'Gagal: nama instansi sudah ada' : 'Gagal menambahkan: ' . $stmt->error;
      }
      $stmt->close();
      break;

    case 'edit_instansi':
      ref_guard('modifikasi');
      $id = intval($_POST['id'] ?? 0);
      $nama = trim($_POST['nama'] ?? '');
      $jenis = trim($_POST['jenis'] ?? '');
      $telepon = trim($_POST['telepon'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $alamat = trim($_POST['alamat'] ?? '');
      $active = in_array($_POST['active'] ?? '', ['Y', 'N']) ? $_POST['active'] : 'Y';
      if (!$id || $nama === '') { echo 'Data tidak valid'; exit; }

      $stmt = $connection->prepare("UPDATE tamu_instansi SET nama=?, jenis=?, telepon=?, email=?, alamat=?, active=? WHERE id=?");
      $stmt->bind_param('ssssssi', $nama, $jenis, $telepon, $email, $alamat, $active, $id);
      if ($stmt->execute()) {
        echo 'Instansi berhasil diupdate';
      } else {
        echo ($connection->errno == 1062) ? 'Gagal: nama instansi sudah ada' : 'Gagal mengupdate: ' . $stmt->error;
      }
      $stmt->close();
      break;

    case 'hapus_instansi':
      ref_guard('hapus');
      $id = intval($_POST['id'] ?? 0);
      if (!$id) { echo 'ID tidak valid'; exit; }
      $stmt_di = $connection->prepare("DELETE FROM tamu_instansi WHERE id=?");
      $stmt_di->bind_param('i', $id);
      echo $stmt_di->execute() && $stmt_di->affected_rows > 0 ? 'Instansi berhasil dihapus' : 'Gagal menghapus';
      $stmt_di->close();
      break;

    // ---------- TUJUAN ----------
    case 'tambah_tujuan':
      ref_guard('modifikasi');
      $nama = trim($_POST['nama'] ?? '');
      $ket = trim($_POST['keterangan'] ?? '');
      if ($nama === '') { echo 'Nama tujuan wajib diisi'; exit; }

      $stmt = $connection->prepare("INSERT INTO tamu_tujuan (nama, keterangan) VALUES (?,?)");
      $stmt->bind_param('ss', $nama, $ket);
      if ($stmt->execute()) {
        echo 'Tujuan berhasil ditambahkan';
      } else {
        echo ($connection->errno == 1062) ? 'Gagal: nama tujuan sudah ada' : 'Gagal menambahkan: ' . $stmt->error;
      }
      $stmt->close();
      break;

    case 'edit_tujuan':
      ref_guard('modifikasi');
      $id = intval($_POST['id'] ?? 0);
      $nama = trim($_POST['nama'] ?? '');
      $ket = trim($_POST['keterangan'] ?? '');
      $active = in_array($_POST['active'] ?? '', ['Y', 'N']) ? $_POST['active'] : 'Y';
      if (!$id || $nama === '') { echo 'Data tidak valid'; exit; }

      $stmt = $connection->prepare("UPDATE tamu_tujuan SET nama=?, keterangan=?, active=? WHERE id=?");
      $stmt->bind_param('sssi', $nama, $ket, $active, $id);
      if ($stmt->execute()) {
        echo 'Tujuan berhasil diupdate';
      } else {
        echo ($connection->errno == 1062) ? 'Gagal: nama tujuan sudah ada' : 'Gagal mengupdate: ' . $stmt->error;
      }
      $stmt->close();
      break;

    case 'hapus_tujuan':
      ref_guard('hapus');
      $id = intval($_POST['id'] ?? 0);
      if (!$id) { echo 'ID tidak valid'; exit; }
      $stmt_dt = $connection->prepare("DELETE FROM tamu_tujuan WHERE id=?");
      $stmt_dt->bind_param('i', $id);
      echo $stmt_dt->execute() && $stmt_dt->affected_rows > 0 ? 'Tujuan berhasil dihapus' : 'Gagal menghapus';
      $stmt_dt->close();
      break;

    default:
      echo 'Action tidak valid';
      break;
  }
}
