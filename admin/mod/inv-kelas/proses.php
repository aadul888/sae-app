<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}

require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../login/user.php';

$action = $_GET['action'] ?? '';

switch ($action) {

  case 'detail':
    $id = intval($_POST['id'] ?? 0);
    $q = $connection->query("SELECT ik.*, ib.nama_barang, ib.kode_barang, ib.satuan, ic.nama_kategori, u.nama_lengkap, u.nisn, k.nama_kelas
      FROM inv_kelas ik
      LEFT JOIN inv_barang ib ON ik.barang_id = ib.barang_id
      LEFT JOIN inv_kategori ic ON ib.kategori_id = ic.kategori_id
      LEFT JOIN user u ON ik.user_id = u.user_id
      LEFT JOIN kelas k ON ik.kelas_id = k.kelas_id
      WHERE ik.inv_id = " . intval($id));

    if ($q && $q->num_rows > 0) {
      $d = $q->fetch_assoc();

      $kondisi_badge = 'success';
      if ($d['kondisi'] === 'Rusak Ringan') $kondisi_badge = 'warning';
      elseif ($d['kondisi'] === 'Rusak Berat') $kondisi_badge = 'danger';
      elseif ($d['kondisi'] === 'Hilang') $kondisi_badge = 'dark';

      $foto_html = '';
      if (!empty($d['foto'])) {
        $foto_url = '../content/berkas/inventaris/' . htmlspecialchars($d['foto']);
        $foto_html = '<div class="mt-3"><img src="' . $foto_url . '" alt="Foto" class="img-fluid rounded" style="max-height:300px;"></div>';
      }

      echo '
      <div class="card border-0">
        <div class="card-body">
          <div class="row mb-2"><div class="col-4 text-muted">Kelas</div><div class="col-8"><strong>' . htmlspecialchars($d['nama_kelas']) . '</strong></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Barang</div><div class="col-8"><strong>' . htmlspecialchars($d['nama_barang']) . '</strong> <small class="text-muted">(' . htmlspecialchars($d['kode_barang'] ?? '') . ')</small></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Kategori</div><div class="col-8">' . htmlspecialchars($d['nama_kategori']) . '</div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Jumlah</div><div class="col-8">' . intval($d['jumlah']) . ' ' . htmlspecialchars($d['satuan']) . '</div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Kondisi</div><div class="col-8"><span class="badge badge-' . $kondisi_badge . '">' . htmlspecialchars($d['kondisi']) . '</span></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Keterangan</div><div class="col-8">' . nl2br(htmlspecialchars($d['keterangan'] ?? '-')) . '</div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Tahun Ajaran</div><div class="col-8">' . htmlspecialchars($d['tahun_ajaran'] ?? '-') . '</div></div>
          <hr>
          <div class="row mb-2"><div class="col-4 text-muted">Di-input Oleh</div><div class="col-8">' . htmlspecialchars($d['nama_lengkap'] ?? '-') . ' <small class="text-muted">(' . htmlspecialchars($d['nisn'] ?? '') . ')</small></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Tanggal Input</div><div class="col-8">' . date('d F Y', strtotime($d['tanggal_input'])) . '</div></div>
          ' . $foto_html . '
        </div>
      </div>';
    } else {
      echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';
    }
    break;

  case 'hapus':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo "ID tidak valid."; exit; }

    // Hapus foto jika ada
    $chk = $connection->query("SELECT foto FROM inv_kelas WHERE inv_id = " . intval($id));
    if ($chk && $chk->num_rows > 0) {
      $row = $chk->fetch_assoc();
      if (!empty($row['foto'])) {
        $foto_path = '../../../content/berkas/inventaris/' . $row['foto'];
        if (file_exists($foto_path)) @unlink($foto_path);
      }
    }

    $del = $connection->query("DELETE FROM inv_kelas WHERE inv_id = " . intval($id));
    echo $del ? "Data inventaris berhasil dihapus." : "Gagal menghapus data.";
    break;

  default:
    echo "Aksi tidak dikenali.";
    break;
}
