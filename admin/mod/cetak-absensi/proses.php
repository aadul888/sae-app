<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../library/config.php';
include('../../../library/function.php');
require_once'../../login/user.php';

switch (@$_GET['action']){

case 'preview':

if(isset($_POST['bulan']) OR isset($_POST['tahun'])){
  $bulan    = anti_injection($_POST['bulan']);
  $tahun    = anti_injection($_POST['tahun']);
} else{
  $bulan    = date ("m");
  $tahun    = date("Y");
}

/** Filter Kelas & Build WHERE clauses secara aman */
$where_clauses = array();
$where_clauses[] = "user.status='Aktif'";

if(!empty($_POST['kelas'])){
  $kelas_id = anti_injection($_POST['kelas']);
  $where_clauses[] = "user.kelas='$kelas_id'";
  
  // Get class info and wali kelas, nip/nuptk from admin (join via wali_kelas_ptk_id)
  $query_kelas_info = "SELECT k.nama_kelas, k.wali_kelas_nama, a.nip, a.nuptk FROM kelas k LEFT JOIN admin a ON k.wali_kelas_ptk_id = a.ptk_id WHERE k.kelas_id='$kelas_id'";
  $result_kelas_info = $connection->query($query_kelas_info);
  if($result_kelas_info && $result_kelas_info->num_rows > 0) {
    $kelas_info = $result_kelas_info->fetch_assoc();
    $nama_kelas = $kelas_info['nama_kelas'];
    $nama_wali_kelas = !empty($kelas_info['wali_kelas_nama']) ? $kelas_info['wali_kelas_nama'] : 'Belum ada wali kelas';
    if (!empty($kelas_info['nip'])) {
      $nip_wali_kelas = 'NIP. ' . $kelas_info['nip'];
    } elseif (!empty($kelas_info['nuptk'])) {
      $nip_wali_kelas = 'NUPTK. ' . $kelas_info['nuptk'];
    } else {
      $nip_wali_kelas = '';
    }
  } else {
    $nama_kelas = "Kelas tidak ditemukan";
    $nama_wali_kelas = "-";
    $nip_wali_kelas = "-";
  }
}else{
  $nama_kelas = "Semua Kelas";
  $nama_wali_kelas = "-";
  $nip_wali_kelas = "-";
}

// Build final WHERE SQL
$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

// Get active academic year
$query_tahun_pelajaran = "SELECT tahun, semester FROM tahun_pelajaran WHERE aktif='Y' LIMIT 1";
$result_tahun_pelajaran = $connection->query($query_tahun_pelajaran);
if($result_tahun_pelajaran && $result_tahun_pelajaran->num_rows > 0) {
  $data_tahun_pelajaran = $result_tahun_pelajaran->fetch_assoc();
  $tahun_pelajaran = $data_tahun_pelajaran['tahun'];
  $semester = $data_tahun_pelajaran['semester'];
} else {
  $tahun_pelajaran = date('Y') . '/' . (date('Y') + 1);
  $semester = 'Ganjil';
}

// Get student data - only active students (gunakan $where_sql)
$query_siswa = "SELECT user.user_id, user.nama_lengkap, user.nisn, user.nipd, user.jenis_kelamin, kelas.nama_kelas 
               FROM user
               INNER JOIN kelas ON user.kelas = kelas.kelas_id 
               $where_sql
               ORDER BY user.nama_lengkap ASC";

$result_siswa = $connection->query($query_siswa);

if($result_siswa && $result_siswa->num_rows > 0){
    // Muat CSS cetak-absensi
    echo '<link rel="stylesheet" href="./mod/cetak-absensi/cetak.css">';
  $jumlahhari = date("t",mktime(0,0,0,$bulan,1,$tahun));
  $jumlah_siswa = $result_siswa->num_rows;

  // Count gender menggunakan klausa yang sama
  $laki_clauses = $where_clauses;
  $laki_clauses[] = "user.jenis_kelamin='Laki-laki'";
  $query_laki = "SELECT COUNT(*) as jumlah FROM user 
                INNER JOIN kelas ON user.kelas = kelas.kelas_id 
                WHERE " . implode(' AND ', $laki_clauses);
  $result_laki = $connection->query($query_laki);
  $laki_count = ($result_laki && $result_laki->num_rows) ? (int)$result_laki->fetch_assoc()['jumlah'] : 0;
  
  $perempuan_clauses = $where_clauses;
  $perempuan_clauses[] = "user.jenis_kelamin='Perempuan'";
  $query_perempuan = "SELECT COUNT(*) as jumlah FROM user 
                     INNER JOIN kelas ON user.kelas = kelas.kelas_id 
                     WHERE " . implode(' AND ', $perempuan_clauses);
  $result_perempuan = $connection->query($query_perempuan);
  $perempuan_count = ($result_perempuan && $result_perempuan->num_rows) ? (int)$result_perempuan->fetch_assoc()['jumlah'] : 0;

echo'

<div class="attendance-preview-container fade-in">
  <div class="print-actions no-print">
    <button type="button" class="btn btn-primary print-btn">
      <i class="fas fa-print"></i> CETAK ABSENSI
    </button>
    <small style="display:block; margin-top:8px; color:#fff;">
      <i class="fas fa-info-circle"></i> Pastikan ukuran kertas diatur ke F4 Landscape
    </small>
  </div>

  <div class="print-content">
    <div class="print-header clearfix" style="margin-bottom: 5px; padding-bottom: 0;">
      <img src="../content/kopsekolah.jpg" alt="Kop Sekolah" style="width: 100%; height: auto; max-height: 150px; object-fit: contain; margin-bottom: 0px; display: block;">
      <hr style="border: 1.5px solid #333; margin: 2px 0 0 0; width: 100%; border-top: 1.5px solid #333; border-bottom: none;">
    </div>

    <div class="report-header-wrapper">
      <div class="report-title">
        DAFTAR HADIR PESERTA DIDIK<br>
        TAHUN PELAJARAN '.$tahun_pelajaran.'
      </div>
      <div class="report-period"><strong>Periode: '.ambilbulan($bulan).' '.$tahun.'</strong></div>
    </div>

    <table class="attendance-table">
      <thead>
        <tr>
          <th rowspan="2" style="width: 25px;">No</th>
          <th rowspan="2" style="width: 90px;">NIPD / NISN</th>
          <th rowspan="2" style="width: 180px;">Nama Siswa</th>
          <th rowspan="2" style="width: 25px;">L/P</th>
          <th colspan="'.$jumlahhari.'">Tanggal</th>
          <th rowspan="2" style="width: 25px;">S</th>
          <th rowspan="2" style="width: 25px;">I</th>
          <th rowspan="2" style="width: 25px;">A</th>
        </tr>
        <tr>';
        
        for ($d = 1; $d <= $jumlahhari; $d++) {
          // Menentukan hari dalam seminggu (0=Minggu, 6=Sabtu)
          $day_of_week = date('w', mktime(0,0,0,$bulan,$d,$tahun));
          $weekend_class = '';
          
          if ($day_of_week == 6) { // Sabtu
            $weekend_class = 'weekend-saturday';
          } elseif ($day_of_week == 0) { // Minggu  
            $weekend_class = 'weekend-sunday';
          }
          
          echo '<th class="'.$weekend_class.'" style="width: 18px; font-size: 8px;">'.$d.'</th>';
        }
        
echo '
        </tr>
      </thead>
      <tbody>';

$no = 1;
while ($data_siswa = $result_siswa->fetch_assoc()) {
  // Format NIPD/NISN
  $nipd = !empty($data_siswa['nipd']) ? strip_tags($data_siswa['nipd']) : '';
  $nisn = !empty($data_siswa['nisn']) ? strip_tags($data_siswa['nisn']) : '';
  $nipd_nisn = '';
  
  if (!empty($nipd) && !empty($nisn)) {
    $nipd_nisn = $nipd . '/' . $nisn;
  } elseif (!empty($nisn)) {
    $nipd_nisn = $nisn;
  } elseif (!empty($nipd)) {
    $nipd_nisn = $nipd;
  }
  
  echo '
        <tr>
          <td>'.$no.'</td>
          <td class="student-nisn">'.$nipd_nisn.'</td>
          <td class="student-name">'.strip_tags($data_siswa['nama_lengkap']).'</td>
          <td>'.substr($data_siswa['jenis_kelamin'], 0, 1).'</td>';
          
  // Empty cells for each day of the month with weekend styling
  for ($d = 1; $d <= $jumlahhari; $d++) {
    $day_of_week = date('w', mktime(0,0,0,$bulan,$d,$tahun));
    $weekend_class = '';
    
    if ($day_of_week == 6) { // Sabtu
      $weekend_class = 'weekend-saturday';
    } elseif ($day_of_week == 0) { // Minggu  
      $weekend_class = 'weekend-sunday';
    }
    
    echo '<td class="'.$weekend_class.'">&nbsp;</td>';
  }
  
  echo '
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>';
  $no++;
}

echo '
      </tbody>
    </table>

    <div class="bottom-section">
      <div class="report-info-bottom">
        <div class="info-card">
          <div class="info-card-label">Kelas</div>
          <div class="info-card-value"><strong>'.$nama_kelas.'</strong></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Total Siswa</div>
          <div class="info-card-value"><strong>'.$jumlah_siswa.' Siswa | L: '.$laki_count.' | P: '.$perempuan_count.'</strong></div>
        </div>
      </div>
      
      <div class="signature-section">
        <div class="signature-right">
          <div class="signature-title">'.strip_tags($site_kota ?? 'Pagelaran').', '.date('d').' '.ambilbulan($bulan).' '.$tahun.'</div>
          <div class="signature-title">Wali Kelas</div>
          <div class="signature-name">'.strip_tags($nama_wali_kelas).'</div>
          '.(!empty($nip_wali_kelas) ? '<div class="signature-nip">'.strip_tags($nip_wali_kelas).'</div>' : '').'
        </div>
      </div>
    </div>
  </div>
  </div>
</div>

  ';

}else{
  if($result_siswa === false) {
    echo '<div class="alert alert-danger text-center">Terjadi kesalahan pada query database: ' . htmlspecialchars($connection->error) . '</div>';
  } else {
    echo '<div class="alert alert-warning text-center">Tidak ada data siswa untuk ditampilkan di kelas ini</div>';
  }
}

break;

default:
  echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
  break;
}

}
?>