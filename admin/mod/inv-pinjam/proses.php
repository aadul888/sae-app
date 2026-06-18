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

$action = $_GET['action'] ?? '';

switch ($action) {

  case 'cari_siswa':
    $keyword = anti_injection($_POST['q'] ?? '');
    $data = [];
    if (strlen($keyword) >= 2) {
      $kw = '%' . $keyword . '%';
      $stmt = $connection->prepare("SELECT user_id, nama_lengkap, nisn, kelas FROM user WHERE nama_lengkap LIKE ? OR nisn LIKE ? LIMIT 20");
      $stmt->bind_param('ss', $kw, $kw);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) {
        $kelas_nama = '-';
        $qk = $connection->query("SELECT nama_kelas FROM kelas WHERE kelas_id = " . intval($r['kelas']));
        if ($qk && $qk->num_rows > 0) $kelas_nama = $qk->fetch_assoc()['nama_kelas'];
        $data[] = [
          'id' => $r['user_id'],
          'text' => $r['nama_lengkap'] . ' (' . $r['nisn'] . ') - ' . $kelas_nama,
          'kelas' => intval($r['kelas'])
        ];
      }
      $stmt->close();
    }
    echo json_encode(['results' => $data]);
    break;

  case 'tambah':
    $user_id = intval($_POST['user_id'] ?? 0);
    $barang_id = intval($_POST['barang_id'] ?? 0);
    $kelas_id = intval($_POST['kelas_id'] ?? 0);
    $jumlah = intval($_POST['jumlah_pinjam'] ?? 1);
    $tanggal_pinjam = anti_injection($_POST['tanggal_pinjam'] ?? date('Y-m-d'));
    $tanggal_rencana = anti_injection($_POST['tanggal_kembali'] ?? '');
    $keterangan = anti_injection($_POST['keterangan'] ?? '');

    if ($user_id <= 0 || $barang_id <= 0 || $kelas_id <= 0) {
      echo json_encode(['status' => 'error', 'message' => 'Data peminjam, barang, dan kelas wajib diisi.']);
      exit;
    }

    $admin_id = 0;
    if (isset($_COOKIE['ADMIN_KEY'])) $admin_id = intval(epm_decode($_COOKIE['ADMIN_KEY']));

    // Get nama_peminjam from user
    $nama_peminjam = '';
    $qu = $connection->query("SELECT nama_lengkap FROM user WHERE user_id = " . intval($user_id));
    if ($qu && $qu->num_rows > 0) $nama_peminjam = $qu->fetch_assoc()['nama_lengkap'];

    $tanggal_rencana_val = empty($tanggal_rencana) ? null : $tanggal_rencana;

    $stmt = $connection->prepare("INSERT INTO inv_pinjam (user_id, barang_id, kelas_id, nama_peminjam, jumlah_pinjam, tanggal_pinjam, tanggal_kembali, keterangan, status, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Dipinjam', ?)");
    $types = 'iii' . 's' . 'i' . 'sss' . 'i'; // 9 params: int,int,int,str,int,str,str,str,int
    $stmt->bind_param($types, $user_id, $barang_id, $kelas_id, $nama_peminjam, $jumlah, $tanggal_pinjam, $tanggal_rencana_val, $keterangan, $admin_id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Peminjaman berhasil dicatat.' : 'Gagal menyimpan data.']);
    break;

  case 'detail':
    $id = intval($_POST['id'] ?? 0);
    $q = $connection->query("SELECT ip.*, ib.nama_barang, ib.kode_barang, u.nama_lengkap, u.nisn, u.telp, k.nama_kelas, a.nama AS admin_nama
      FROM inv_pinjam ip
      LEFT JOIN inv_barang ib ON ip.barang_id = ib.barang_id
      LEFT JOIN user u ON ip.user_id = u.user_id
      LEFT JOIN kelas k ON ip.kelas_id = k.kelas_id
      LEFT JOIN admin a ON ip.admin_id = a.admin_id
      WHERE ip.pinjam_id = " . intval($id));

    if ($q && $q->num_rows > 0) {
      $d = $q->fetch_assoc();
      echo json_encode(['status' => 'success', 'data' => $d]);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
    }
    break;

  case 'kembalikan':
    $pinjam_id = intval($_POST['pinjam_id'] ?? 0);
    $status = anti_injection($_POST['status'] ?? 'Dikembalikan');
    $tgl_aktual = anti_injection($_POST['tanggal_dikembalikan'] ?? date('Y-m-d'));
    $keterangan = anti_injection($_POST['keterangan'] ?? '');

    if ($pinjam_id <= 0) {
      echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
      exit;
    }

    $stmt = $connection->prepare("UPDATE inv_pinjam SET status = ?, tanggal_dikembalikan = ?, keterangan = ? WHERE pinjam_id = ?");
    $stmt->bind_param('sssi', $status, $tgl_aktual, $keterangan, $pinjam_id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Pengembalian berhasil diproses.' : 'Gagal memproses pengembalian.']);
    break;

  case 'hapus':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
      echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
      exit;
    }
    $del = $connection->query("DELETE FROM inv_pinjam WHERE pinjam_id = " . intval($id));
    echo json_encode(['status' => $del ? 'success' : 'error', 'message' => $del ? 'Data berhasil dihapus.' : 'Gagal menghapus data.']);
    break;

  default:
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
    break;
}
