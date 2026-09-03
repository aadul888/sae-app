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
    $q = $connection->query("SELECT il.*, ib.nama_barang, ib.kode_barang, u.nama_lengkap, u.nisn, u.telp, k.nama_kelas, a.nama AS admin_nama
      FROM inv_laporan il
      LEFT JOIN inv_barang ib ON il.barang_id = ib.barang_id
      LEFT JOIN user u ON il.user_id = u.user_id
      LEFT JOIN kelas k ON il.kelas_id = k.kelas_id
      LEFT JOIN admin a ON il.processed_by = a.admin_id
      WHERE il.laporan_id = " . intval($id));

    if ($q && $q->num_rows > 0) {
      $d = $q->fetch_assoc();

      $jenis_badge = 'danger';
      if ($d['jenis_laporan'] === 'Kehilangan') $jenis_badge = 'dark';
      elseif ($d['jenis_laporan'] === 'Kebutuhan') $jenis_badge = 'info';

      $prioritas_badge = 'secondary';
      if ($d['prioritas'] === 'Sedang') $prioritas_badge = 'info';
      elseif ($d['prioritas'] === 'Tinggi') $prioritas_badge = 'warning';
      elseif ($d['prioritas'] === 'Urgent') $prioritas_badge = 'danger';

      $status_badge = 'warning';
      if ($d['status'] === 'Diproses') $status_badge = 'info';
      elseif ($d['status'] === 'Selesai') $status_badge = 'success';
      elseif ($d['status'] === 'Ditolak') $status_badge = 'danger';

      $foto_html = '';
      if (!empty($d['foto'])) {
        $foto_url = '../content/berkas/inventaris/' . htmlspecialchars($d['foto']);
        $foto_html = '<div class="mt-3"><strong>Foto Bukti:</strong><br><img src="' . $foto_url . '" alt="Foto" class="img-fluid rounded mt-2" style="max-height:300px;"></div>';
      }

      // WhatsApp link
      $contact_html = '';
      $tel_raw = trim($d['telp'] ?? '');
      if ($tel_raw !== '') {
        $digits = preg_replace('/[^0-9]/', '', $tel_raw);
        if (strpos($digits, '0') === 0) $digits = '62' . substr($digits, 1);
        elseif (strpos($digits, '8') === 0) $digits = '62' . $digits;
        $contact_html = '<a href="https://wa.me/' . $digits . '" target="_blank" class="btn btn-sm btn-success mt-1"><i class="fab fa-whatsapp mr-1"></i> ' . htmlspecialchars($tel_raw) . '</a>';
      }

      echo '
      <div class="card border-0">
        <div class="card-body">
          <div class="row mb-2"><div class="col-4 text-muted">Kelas</div><div class="col-8"><strong>' . htmlspecialchars($d['nama_kelas']) . '</strong></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Pelapor</div><div class="col-8">' . htmlspecialchars($d['nama_lengkap'] ?? '-') . ' <small class="text-muted">(' . htmlspecialchars($d['nisn'] ?? '') . ')</small>' . ($contact_html ? '<br>' . $contact_html : '') . '</div></div>
          <hr>
          <div class="row mb-2"><div class="col-4 text-muted">Jenis Laporan</div><div class="col-8"><span class="badge badge-' . $jenis_badge . '">' . htmlspecialchars($d['jenis_laporan']) . '</span></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Prioritas</div><div class="col-8"><span class="badge badge-' . $prioritas_badge . '">' . htmlspecialchars($d['prioritas']) . '</span></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Barang Terkait</div><div class="col-8">' . htmlspecialchars($d['nama_barang'] ?? 'Umum / Tidak ada') . '</div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Deskripsi</div><div class="col-8"><div class="border rounded p-2" style="background:#f8f9fb;">' . nl2br(htmlspecialchars($d['deskripsi'])) . '</div></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Tanggal Laporan</div><div class="col-8">' . date('d F Y', strtotime($d['tanggal_laporan'])) . '</div></div>
          <hr>
          <div class="row mb-2"><div class="col-4 text-muted">Status</div><div class="col-8"><span class="badge badge-' . $status_badge . ' badge-lg">' . htmlspecialchars($d['status']) . '</span></div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Catatan Admin</div><div class="col-8">' . nl2br(htmlspecialchars($d['catatan_admin'] ?? '-')) . '</div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Diproses Oleh</div><div class="col-8">' . htmlspecialchars($d['admin_nama'] ?? '-') . '</div></div>
          <div class="row mb-2"><div class="col-4 text-muted">Tanggal Diproses</div><div class="col-8">' . (!empty($d['processed_at']) ? date('d F Y H:i', strtotime($d['processed_at'])) : '-') . '</div></div>
          ' . $foto_html . '
        </div>
      </div>';
    } else {
      echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';
    }
    break;

  case 'proses':
    header('Content-Type: application/json; charset=utf-8');
    $laporan_id = intval($_POST['laporan_id'] ?? 0);
    $status = anti_injection($_POST['status'] ?? '');
    $catatan = anti_injection($_POST['catatan_admin'] ?? '');

    if ($laporan_id <= 0 || empty($status)) {
      echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
      exit;
    }

    $admin_id = 0;
    if (isset($_COOKIE['ADMIN_KEY'])) $admin_id = intval(epm_decode($_COOKIE['ADMIN_KEY']));

    $stmt = $connection->prepare("UPDATE inv_laporan SET status = ?, catatan_admin = ?, processed_by = ?, processed_at = NOW() WHERE laporan_id = ?");
    if ($stmt) {
      $stmt->bind_param('ssii', $status, $catatan, $admin_id, $laporan_id);
      $ok = $stmt->execute();
      $stmt->close();
      echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Laporan berhasil diproses.' : 'Gagal memproses laporan.']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Query error.']);
    }
    break;

  case 'hapus':
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo "ID tidak valid."; exit; }

    // Hapus foto
    $chk = $connection->query("SELECT foto FROM inv_laporan WHERE laporan_id = " . intval($id));
    if ($chk && $chk->num_rows > 0) {
      $row = $chk->fetch_assoc();
      if (!empty($row['foto'])) {
        $foto_path = '../../../content/berkas/inventaris/' . $row['foto'];
        if (file_exists($foto_path)) @unlink($foto_path);
      }
    }

    $del = $connection->query("DELETE FROM inv_laporan WHERE laporan_id = " . intval($id));
    echo $del ? "Laporan berhasil dihapus." : "Gagal menghapus laporan.";
    break;

  default:
    echo "Aksi tidak dikenali.";
    break;
}
