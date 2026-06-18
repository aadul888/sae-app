<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../login/user.php';

$modul_id = 49;
include __DIR__ . '/../check_role.php';
if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
  http_response_code(403);
  echo 'Akses ditolak.';
  exit;
}

$dari   = preg_replace('/[^0-9\-]/', '', $_GET['dari'] ?? '');
$sampai = preg_replace('/[^0-9\-]/', '', $_GET['sampai'] ?? '');
$status = in_array($_GET['status'] ?? '', ['Aktif', 'Selesai', 'Batal']) ? $_GET['status'] : '';
$format = ($_GET['format'] ?? 'print') === 'excel' ? 'excel' : 'print';

if ($dari === '' || $sampai === '') {
  echo 'Rentang tanggal wajib diisi.';
  exit;
}

$conds = ["tanggal_kunjungan BETWEEN '" . $connection->real_escape_string($dari) . "' AND '" . $connection->real_escape_string($sampai) . "'"];
if ($status !== '') $conds[] = "status='" . $connection->real_escape_string($status) . "'";
$where = 'WHERE ' . implode(' AND ', $conds);

$res = $connection->query("SELECT * FROM buku_tamu $where ORDER BY tanggal_kunjungan ASC, waktu_masuk ASC");

$sekolah = trim((string)($site_name ?? '')) ?: (defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Sekolah');
$judul = 'Laporan Buku Tamu ' . htmlspecialchars($dari) . ' s/d ' . htmlspecialchars($sampai);
$filename = 'Laporan-Buku-Tamu-' . $dari . '_' . $sampai;

if ($format === 'excel') {
  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
  header('Cache-Control: max-age=0');
}

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?php echo $judul; ?></title>
<?php if ($format === 'print') : ?>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; margin: 24px; color: #222; }
  h2, h4 { text-align: center; margin: 2px 0; }
  table { border-collapse: collapse; width: 100%; margin-top: 14px; }
  th, td { border: 1px solid #555; padding: 5px 7px; }
  th { background: #f0f0f0; }
  .text-center { text-align: center; }
  @media print { .noprint { display: none; } }
</style>
<?php endif; ?>
</head>
<body>
  <h2><?php echo htmlspecialchars($sekolah); ?></h2>
  <h4>Laporan Buku Tamu</h4>
  <p class="text-center">Periode: <?php echo htmlspecialchars($dari); ?> s/d <?php echo htmlspecialchars($sampai); ?><?php echo $status ? ' &middot; Status: ' . htmlspecialchars($status) : ''; ?></p>
  <table>
    <thead>
      <tr>
        <th>No</th><th>Guest ID</th><th>Nama</th><th>Instansi</th><th>Telepon</th>
        <th>Keperluan</th><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      if ($res && $res->num_rows) {
        while ($r = $res->fetch_assoc()) {
          echo '<tr>';
          echo '<td class="text-center">' . ($no++) . '</td>';
          echo '<td>' . htmlspecialchars($r['guest_id']) . '</td>';
          echo '<td>' . htmlspecialchars($r['nama']) . '</td>';
          echo '<td>' . htmlspecialchars($r['instansi']) . '</td>';
          echo '<td>' . htmlspecialchars($r['telepon'] ?? '') . '</td>';
          echo '<td>' . htmlspecialchars($r['keperluan']) . '</td>';
          echo '<td class="text-center">' . htmlspecialchars($r['tanggal_kunjungan']) . '</td>';
          echo '<td class="text-center">' . htmlspecialchars(substr((string)$r['waktu_masuk'], 0, 5)) . '</td>';
          echo '<td class="text-center">' . ($r['waktu_keluar'] ? htmlspecialchars(substr((string)$r['waktu_keluar'], 0, 5)) : '-') . '</td>';
          echo '<td class="text-center">' . htmlspecialchars($r['status']) . '</td>';
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="10" class="text-center">Tidak ada data pada periode ini.</td></tr>';
      }
      ?>
    </tbody>
  </table>
  <?php if ($format === 'print') : ?>
    <p style="margin-top:18px" class="noprint text-center"><button onclick="window.print()">Cetak / Simpan PDF</button></p>
    <script>window.onload = function () { setTimeout(function(){ window.print(); }, 400); };</script>
  <?php endif; ?>
</body>
</html>
