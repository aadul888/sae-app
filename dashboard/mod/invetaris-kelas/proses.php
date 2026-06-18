<?php
session_start();

if (!isset($_COOKIE['siswa'])) {
  header('location:../../login');
  exit;
}

require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../oauth/user.php';

$user_id = $data_user['user_id'] ?? 0;
$kelas_id = $data_user['kelas'] ?? 0;

if (empty($user_id) || empty($kelas_id)) {
  echo 'Data tidak valid.';
  exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

  // ===== TAMBAH INVENTARIS =====
  case 'tambah_inventaris':
    $barang_id = intval($_POST['barang_id'] ?? 0);
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $kondisi = anti_injection($_POST['kondisi'] ?? 'Baik');
    $keterangan = anti_injection($_POST['keterangan'] ?? '');
    $tahun_ajaran = anti_injection($_POST['tahun_ajaran'] ?? '');
    $tanggal_input = date('Y-m-d');

    if ($barang_id <= 0) {
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'Pilih barang terlebih dahulu.']);
      exit;
    }

    // Handle foto upload
    $foto_name = null;
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['jpg', 'jpeg', 'png', 'webp'];
      $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, $allowed)) {
        $upload_dir = '../../../content/berkas/inventaris/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $foto_name = 'inv_' . $kelas_id . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
        $target = $upload_dir . $foto_name;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
          $foto_name = null;
        }
      }
    }

    $stmt = $connection->prepare("INSERT INTO inv_kelas (kelas_id, barang_id, jumlah, kondisi, keterangan, foto, user_id, tahun_ajaran, tanggal_input) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
      $stmt->bind_param('iiisssiss', $kelas_id, $barang_id, $jumlah, $kondisi, $keterangan, $foto_name, $user_id, $tahun_ajaran, $tanggal_input);
      $ok = $stmt->execute();
      $stmt->close();
      header('Content-Type: application/json');
      echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Data inventaris berhasil disimpan.' : 'Gagal menyimpan data.']);
    } else {
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query.']);
    }
    break;

  // ===== EDIT INVENTARIS =====
  case 'edit_inventaris':
    $inv_id = intval($_POST['inv_id'] ?? 0);
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $kondisi = anti_injection($_POST['kondisi'] ?? 'Baik');
    $keterangan = anti_injection($_POST['keterangan'] ?? '');

    // Hanya bisa edit milik sendiri
    $check = $connection->prepare("SELECT inv_id FROM inv_kelas WHERE inv_id = ? AND user_id = ? LIMIT 1");
    if ($check) {
      $check->bind_param('ii', $inv_id, $user_id);
      $check->execute();
      $res = $check->get_result();
      if ($res && $res->num_rows > 0) {
        $up = $connection->prepare("UPDATE inv_kelas SET jumlah = ?, kondisi = ?, keterangan = ? WHERE inv_id = ? AND user_id = ?");
        if ($up) {
          $up->bind_param('issii', $jumlah, $kondisi, $keterangan, $inv_id, $user_id);
          $ok = $up->execute();
          $up->close();
          header('Content-Type: application/json');
          echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Data berhasil diperbarui.' : 'Gagal memperbarui data.']);
        } else {
          header('Content-Type: application/json');
          echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query update.']);
        }
      } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
      }
      $check->close();
    }
    break;

  // ===== HAPUS INVENTARIS =====
  case 'hapus_inventaris':
    $inv_id = intval($_POST['inv_id'] ?? 0);

    $check = $connection->prepare("SELECT inv_id, foto FROM inv_kelas WHERE inv_id = ? AND user_id = ? LIMIT 1");
    if ($check) {
      $check->bind_param('ii', $inv_id, $user_id);
      $check->execute();
      $res = $check->get_result();
      if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        // Hapus foto jika ada
        if (!empty($row['foto'])) {
          $foto_path = '../../../content/berkas/inventaris/' . $row['foto'];
          if (file_exists($foto_path)) @unlink($foto_path);
        }
        $del = $connection->prepare("DELETE FROM inv_kelas WHERE inv_id = ? AND user_id = ?");
        if ($del) {
          $del->bind_param('ii', $inv_id, $user_id);
          $ok = $del->execute();
          $del->close();
          header('Content-Type: application/json');
          echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Data berhasil dihapus.' : 'Gagal menghapus data.']);
        }
      } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
      }
      $check->close();
    }
    break;

  // ===== TAMBAH LAPORAN =====
  case 'tambah_laporan':
    $jenis_laporan = anti_injection($_POST['jenis_laporan'] ?? '');
    $deskripsi = anti_injection($_POST['deskripsi'] ?? '');
    $prioritas = anti_injection($_POST['prioritas'] ?? 'Sedang');
    $barang_id = !empty($_POST['barang_id']) ? intval($_POST['barang_id']) : null;
    $tanggal_laporan = date('Y-m-d');

    if (empty($jenis_laporan) || empty($deskripsi)) {
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'Jenis laporan dan deskripsi wajib diisi.']);
      exit;
    }

    // Handle foto upload
    $foto_name = null;
    if (!empty($_FILES['foto_laporan']['name']) && $_FILES['foto_laporan']['error'] === UPLOAD_ERR_OK) {
      $allowed = ['jpg', 'jpeg', 'png', 'webp'];
      $ext = strtolower(pathinfo($_FILES['foto_laporan']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, $allowed)) {
        $upload_dir = '../../../content/berkas/inventaris/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $foto_name = 'lap_' . $kelas_id . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
        $target = $upload_dir . $foto_name;
        if (!move_uploaded_file($_FILES['foto_laporan']['tmp_name'], $target)) {
          $foto_name = null;
        }
      }
    }

    $stmt = $connection->prepare("INSERT INTO inv_laporan (kelas_id, barang_id, jenis_laporan, deskripsi, prioritas, foto, user_id, tanggal_laporan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
      $stmt->bind_param('iissssis', $kelas_id, $barang_id, $jenis_laporan, $deskripsi, $prioritas, $foto_name, $user_id, $tanggal_laporan);
      $ok = $stmt->execute();
      $stmt->close();
      header('Content-Type: application/json');
      echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Laporan berhasil dikirim.' : 'Gagal mengirim laporan.']);
    } else {
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query.']);
    }
    break;

  default:
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
    break;
}
