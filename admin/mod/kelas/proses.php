<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');
  require_once '../../login/user.php';
  require_once '../../assets/vendor/autoload.php';

  switch (@$_GET['action']) {
    /* ---------- ADD  ---------- */
    case 'add':
      echo 'Aksi tambah/update kelas dinonaktifkan. Data kelas otomatis dari sinkronisasi Dapodik.';

      break;
    /* --------------- Export PDF ------------*/
    case 'export_pdf':
      error_reporting(E_ALL);
      ini_set('display_errors', 1);
      // mPDF is loaded via Composer autoload, no need for manual require
      try {
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
        $mpdf->SetTitle('Export Data Kelas');
        $html = '<h2 style="text-align:center;">Data Kelas</h2>';
        $html .= '<table border="1" cellpadding="6" style="width:100%; border-collapse:collapse; font-size:12px;">';
        $html .= '<thead><tr style="background:#f1f1f1;">
            <th>No</th>
            <th>ID</th>
            <th>Kelas</th>
            <th>Jurusan</th>
            <th>Wali Kelas</th>
            <th>Jumlah Siswa</th>
            <th>Kualitas Data (%)</th>
        </tr></thead><tbody>';
        $query = "SELECT k.kelas_id, k.nama_kelas, j.nama_jurusan, a.fullname AS wali_kelas, COUNT(DISTINCT u.user_id) AS jumlah_siswa,
          SUM(
            CASE WHEN (
              u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai' 
              OR EXISTS(SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid')
            ) THEN 1 ELSE 0 END
          ) AS jumlah_kualitas
          FROM kelas k
          LEFT JOIN user u ON k.kelas_id = u.kelas
          LEFT JOIN jurusan j ON k.jurusan_id = j.jurusan_id
          LEFT JOIN admin a ON k.wali_kelas_nama = a.admin_id AND a.level = 3
          GROUP BY k.kelas_id ORDER BY k.nama_kelas ASC";
        $result = $connection->query($query);
        $no = 1;
        while ($row = $result->fetch_assoc()) {
          $persen = 0;
          if ($row['jumlah_siswa'] > 0) {
            $persen = round(($row['jumlah_kualitas'] / $row['jumlah_siswa']) * 100, 1);
          }
          $html .= '<tr>';
          $html .= '<td style="text-align:center;">' . $no++ . '</td>';
          $html .= '<td style="text-align:center;">' . $row['kelas_id'] . '</td>';
          $html .= '<td>' . htmlspecialchars($row['nama_kelas']) . '</td>';
          $html .= '<td>' . htmlspecialchars($row['nama_jurusan']) . '</td>';
          $html .= '<td>' . htmlspecialchars($row['wali_kelas']) . '</td>';
          $html .= '<td style="text-align:center;">' . $row['jumlah_siswa'] . '</td>';
          $html .= '<td style="text-align:center;">' . $persen . '%</td>';
          $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $mpdf->WriteHTML($html);
        $mpdf->Output('kelas_export.pdf', 'D');
        exit;
      } catch (Error $e) {
        echo '<div style="color:red;font-weight:bold;padding:20px;">Error: mPDF belum terinstall. Silakan install dengan <code>composer require mpdf/mpdf</code> di folder project Anda.</div>';
        exit;
      } catch (Exception $e) {
        echo '<div style="color:red;font-weight:bold;padding:20px;">Gagal generate PDF: ' . htmlspecialchars($e->getMessage()) . '</div>';
        exit;
      }
      break;


    /* --------------- Delete ------------*/
    case 'delete':
      echo 'Aksi hapus kelas dinonaktifkan. Data kelas otomatis dari sinkronisasi Dapodik.';


      break;
    case 'update_jurusan_wali':
      echo 'Aksi ubah jurusan/wali kelas dinonaktifkan. Data kelas otomatis dari sinkronisasi Dapodik.';
      break;
  }
}
