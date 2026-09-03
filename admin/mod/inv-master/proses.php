<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}

require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../login/user.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

switch ($action) {

  // ===== KATEGORI =====
  case 'tambah_kategori':
    $nama = anti_injection(trim($_POST['nama_kategori'] ?? ''));
    $keterangan = anti_injection(trim($_POST['keterangan'] ?? ''));
    if (empty($nama)) { echo json_encode(['status' => 'error', 'message' => 'Nama kategori wajib diisi.']); exit; }

    $stmt = $connection->prepare("INSERT INTO inv_kategori (nama_kategori, keterangan) VALUES (?, ?)");
    if ($stmt) {
      $stmt->bind_param('ss', $nama, $keterangan);
      $ok = $stmt->execute();
      $stmt->close();
      echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Kategori berhasil ditambahkan.' : 'Gagal menambahkan kategori.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Query error.']);
    }
    break;

  case 'edit_kategori':
    $id = intval($_POST['kategori_id'] ?? 0);
    $nama = anti_injection(trim($_POST['nama_kategori'] ?? ''));
    $keterangan = anti_injection(trim($_POST['keterangan'] ?? ''));
    if ($id <= 0 || empty($nama)) { echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']); exit; }

    $stmt = $connection->prepare("UPDATE inv_kategori SET nama_kategori = ?, keterangan = ? WHERE kategori_id = ?");
    if ($stmt) {
      $stmt->bind_param('ssi', $nama, $keterangan, $id);
      $ok = $stmt->execute();
      $stmt->close();
      echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Kategori berhasil diperbarui.' : 'Gagal memperbarui kategori.']);
    }
    break;

  case 'hapus_kategori':
    $id = intval($_POST['kategori_id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit; }

    // Cek apakah ada barang di kategori ini
    $chk = $connection->prepare("SELECT COUNT(*) AS cnt FROM inv_barang WHERE kategori_id = ?");
    $chk->bind_param('i', $id);
    $chk->execute();
    $res = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (intval($res['cnt']) > 0) {
      echo json_encode(['status' => 'error', 'message' => 'Kategori tidak bisa dihapus karena masih memiliki ' . $res['cnt'] . ' barang.']);
      exit;
    }

    $stmt = $connection->prepare("DELETE FROM inv_kategori WHERE kategori_id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Kategori berhasil dihapus.' : 'Gagal menghapus kategori.']);
    break;

  // ===== BARANG =====
  case 'tambah_barang':
    $kategori_id = intval($_POST['kategori_id'] ?? 0);
    $kode = anti_injection(trim($_POST['kode_barang'] ?? ''));
    $nama = anti_injection(trim($_POST['nama_barang'] ?? ''));
    $satuan = anti_injection(trim($_POST['satuan'] ?? 'Unit'));
    $keterangan = anti_injection(trim($_POST['keterangan'] ?? ''));

    if ($kategori_id <= 0 || empty($nama)) {
      echo json_encode(['status' => 'error', 'message' => 'Kategori dan nama barang wajib diisi.']);
      exit;
    }

    $stmt = $connection->prepare("INSERT INTO inv_barang (kategori_id, kode_barang, nama_barang, satuan, keterangan) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
      $stmt->bind_param('issss', $kategori_id, $kode, $nama, $satuan, $keterangan);
      $ok = $stmt->execute();
      $stmt->close();
      echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Barang berhasil ditambahkan.' : 'Gagal menambahkan barang.']);
    }
    break;

  case 'edit_barang':
    $id = intval($_POST['barang_id'] ?? 0);
    $kategori_id = intval($_POST['kategori_id'] ?? 0);
    $kode = anti_injection(trim($_POST['kode_barang'] ?? ''));
    $nama = anti_injection(trim($_POST['nama_barang'] ?? ''));
    $satuan = anti_injection(trim($_POST['satuan'] ?? 'Unit'));
    $keterangan = anti_injection(trim($_POST['keterangan'] ?? ''));

    if ($id <= 0 || $kategori_id <= 0 || empty($nama)) {
      echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
      exit;
    }

    $stmt = $connection->prepare("UPDATE inv_barang SET kategori_id = ?, kode_barang = ?, nama_barang = ?, satuan = ?, keterangan = ? WHERE barang_id = ?");
    if ($stmt) {
      $stmt->bind_param('issssi', $kategori_id, $kode, $nama, $satuan, $keterangan, $id);
      $ok = $stmt->execute();
      $stmt->close();
      echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Barang berhasil diperbarui.' : 'Gagal memperbarui barang.']);
    }
    break;

  case 'hapus_barang':
    $id = intval($_POST['barang_id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit; }

    // Cek apakah barang dipakai di inv_kelas
    $chk = $connection->prepare("SELECT COUNT(*) AS cnt FROM inv_kelas WHERE barang_id = ?");
    $chk->bind_param('i', $id);
    $chk->execute();
    $res = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (intval($res['cnt']) > 0) {
      echo json_encode(['status' => 'error', 'message' => 'Barang tidak bisa dihapus karena sudah digunakan di ' . $res['cnt'] . ' data inventaris kelas.']);
      exit;
    }

    $stmt = $connection->prepare("DELETE FROM inv_barang WHERE barang_id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Barang berhasil dihapus.' : 'Gagal menghapus barang.']);
    break;

  default:
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
    break;
}
