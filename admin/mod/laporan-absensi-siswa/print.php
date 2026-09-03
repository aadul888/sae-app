<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once '../../../library/config.php';
require_once('../../../library/function.php');
require_once '../../login/user.php';

$modul_id = 19;
include __DIR__ . '/../check_role.php';
if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
  echo 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.';
  exit;
}

include_once '../../../library/vendor/autoload.php';
$no = 0;

switch (@$_GET['action']){
case 'print':
$warna      = '';
$background = '';
$jumlah_libur = 0;
$jumlah_libur_nasional = 0;
$jumlah_izin = 0;

if(isset($_GET['bulan']) OR isset($_GET['tahun'])){
  $bulan    = anti_injection($_GET['bulan']);
  $tahun    = anti_injection($_GET['tahun']);
} else{
  $bulan    = date ("m");
  $tahun    = date("Y");
}

/** Filter Siswa */
if(isset($_GET['siswa'])){
  $siswa  = anti_injection($_GET['siswa']);
  //$filter ="WHERE absen.user_id='$siswa'";
} 
else{
  //$filter = "";
}

$hari       = date("d");
$jumlahhari = date("t",mktime(0,0,0,$bulan,$hari,$tahun));

$query_siswa ="SELECT user.nama_lengkap,kelas.nama_kelas FROM user 
INNER JOIN kelas ON user.kelas = kelas.kelas_id WHERE user.user_id='$siswa'";
$result_siswa = $connection->query($query_siswa);
if($result_siswa->num_rows > 0){
  $data_siswa = $result_siswa->fetch_assoc();
  
  
  if(!empty($_GET['tipe']=='pdf')){
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L']);
    ob_start();
  }

  if(!empty($_GET['tipe']=='excel')){
    header('Content-Type: application/vnd.ms-excel');  
    header('Content-disposition: attachment; filename=Laporan-'.$bulan.'-'.$data_siswa['nama_lengkap'].'.xls'); 
  }

echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>Laporan Absensi Siswa</title>
    <style>

    body{font-family:Arial,Helvetica,sans-serif}
    .text-center{
      text-align: center;
    }
    
    .kop {
      position:relative;
      display:contents;
      margin:20px 0px 20px 0px;
    }

    .kop img{
      width:100%;
      height:auto;
    }

    table.datatable{
      width:100%;
      background-color:#fff;
      border-collapse:collapse;
      border-width:1px;
      border-color:#b3b3b3;
      border-style:solid;
      color:#000;
      margin:10px 0px 0px 0px;
  }
    table.datatable td,table.datatable th{
      border-width:1px;
      border-color:#b3b3b3;
      border-style:solid;
      padding:5px;text-align:left;
      
    }
    table.datatable th{
      background-color:#666666;
      color:#ffffff;
    }
    table.datatable td.text-center,
    table.datatable th.text-center{text-align:center}

    .badge {
      font-weight: 600;
      line-height: 1;
      display: inline-block;
      padding: 0.35rem 0.375rem;
      transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
      text-align: center;
      vertical-align: baseline;
      white-space: nowrap;
      border-radius: 0.375rem;
    }
    .badge-success {
      color: #1aae6f;
      background-color: #b0eed3;
    }
    
    .badge-danger {
      color: #f80031;
      background-color: #fdd1da;
    }
    
    .badge-info{
      color: #0080c0;
      background-color: #4aa5ff;
    }

    .rounded {
      border-radius: 0.375rem !important;
    }

    .footer-count{
      position:relative;
      display: inline-block;
    }
    .footer-count p{
      display: inline-block;
      font-size:14px;
      margin-right:10px;
    }

    </style>';
    if(!empty($_GET['tipe']=='print')){
      echo'
      <script>
        window.onafterprint = window.close;
        window.print();
      </script>';
    }
  echo'
</head>
<body>

  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="kop text-center">
          <img src="../../../content/'.strip_tags($site_kop).'" class="imaged w100 rounded">
        </div>
        
        <div class="mt-3">
        <p>Nama Siswa : '.$data_siswa['nama_lengkap'].'<br>
        Laporan Absensi Bulan : '.ambilbulan($bulan).' '.$tahun.'</p>
        </div>
      </div>

      <div class="col-md-12">
          <table class="datatable mt-3">
              <thead>
                <tr>
                  <th class="text-center" width="10">No</th>
                  <th>Tanggal</th>
                  <th>Jam Masuk</th>
                  <th>Status Masuk</th>
                  <th>Foto Masuk</th>
                  <th>Jam Pulang</th>
                  <th>Status Pulang</th>
                  <th>Foto Pulang</th>
                  <th>Kehadiran</th>
                  <th>Created</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>';
for ($d=1;$d<=$jumlahhari;$d++) {
  $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
  $hari_libur = date('D',strtotime($tanggal));
  $hari_ind = date('l', strtotime($tanggal));

  // Cek hari libur nasional
  $is_libur = false;
  $libur_ket = '';
  $query_libur_nasional = "SELECT * FROM hari_libur WHERE tanggal_mulai <= '$tanggal' AND tanggal_selesai >= '$tanggal'";
  $result_libur_nasional = $connection->query($query_libur_nasional);
  if($result_libur_nasional->num_rows > 0){
    $is_libur = true;
    $data_libur = $result_libur_nasional->fetch_assoc();
    $libur_ket = strip_tags($data_libur['keterangan'] ? $data_libur['keterangan'] : $data_libur['nama_libur']);
    $jumlah_libur_nasional++;
  }

  // Cek izin siswa
  $is_izin = false;
  $izin_ket = '';
  $query_izin = "SELECT * FROM izin WHERE user_id='$siswa' AND status_izin='Disetujui' AND tanggal_mulai <= '$tanggal' AND tanggal_selesai >= '$tanggal'";
  $result_izin = $connection->query($query_izin);
  if($result_izin->num_rows > 0){
    $is_izin = true;
    $izin_ket = 'Izin';
    $jumlah_izin++;
  }

  // Cek jadwal hari kerja berdasarkan status di tabel jadwal
  $is_hari_kerja = false;
  $query_jadwal = "SELECT * FROM jadwal WHERE hari='$hari_ind' AND status='Y'";
  $result_jadwal = $connection->query($query_jadwal);
  if($result_jadwal->num_rows > 0){
    $is_hari_kerja = true;
  }

  // Penentuan status dan styling berdasarkan prioritas
  $row_style = '';
  $status_text = '';
  if($is_libur){
    // Hari libur nasional (prioritas tertinggi)
    $row_style = 'style="background-color:#ffebee;"';
    $status_text = $libur_ket ? $libur_ket : 'Libur Nasional';
    $jumlah_libur_nasional++;
  }elseif($is_izin){
    // Izin siswa
    $row_style = 'style="background-color:#fff3e0;"';
    $status_text = 'Izin';
  }elseif(!$is_hari_kerja){
    // Bukan hari kerja (status N di tabel jadwal)
    $row_style = 'style="background-color:#ffebee;"';
    $status_text = 'Libur';
    $jumlah_libur++;
  }else{
    // Hari kerja normal (status Y di tabel jadwal)
    $row_style = '';
    $status_text = '';
  }

  $query_absensi = "SELECT absensi.*, user.nama_lengkap, user.kelas, user.nisn, k.nama_kelas FROM absensi
    INNER JOIN user ON absensi.user_id = user.user_id 
    LEFT JOIN kelas k ON user.kelas = k.kelas_id 
    WHERE absensi.tanggal='$tanggal' AND MONTH(absensi.tanggal)='$bulan' AND YEAR(absensi.tanggal)='$tahun' AND absensi.user_id='$siswa'";
  $result_absensi = $connection->query($query_absensi);
  
  if($result_absensi->num_rows > 0){
    $row = $result_absensi->fetch_assoc();
    echo '<tr '.$row_style.'>';
    echo '<td>'.$d.'</td>';
    echo '<td>'.tanggal_ind($row['tanggal']).'</td>';
    echo '<td>'.$row['jam_masuk'].'</td>';
    echo '<td>'.$row['status_masuk'].'</td>';
    echo '<td class="text-center">';
    if (!empty($row['foto_masuk'])) {
      echo '<img src="../../../content/capture/'.htmlspecialchars($row['foto_masuk']).'" class="imaged w100 rounded" height="40">';
    } else {
      echo '<img src="../../../content/thumbnail.jpg" class="imaged w100 rounded" height="40">';
    }
    echo '</td>';
    echo '<td>'.$row['jam_pulang'].'</td>';
    echo '<td>'.$row['status_pulang'].'</td>';
    echo '<td class="text-center">';
    if (!empty($row['foto_pulang'])) {
      echo '<img src="../../../content/capture/'.htmlspecialchars($row['foto_pulang']).'" class="imaged w100 rounded" height="40">';
    } else {
      echo '<img src="../../../content/thumbnail.jpg" class="imaged w100 rounded" height="40">';
    }
    echo '</td>';
    echo '<td>'.$row['kehadiran'].'</td>';
    echo '<td>'.$row['created_at'].'</td>';
    echo '<td>'.$row['updated_at'].'</td>';
    echo '</tr>';
  } else {
    echo '<tr '.$row_style.'>';
    echo '<td>'.$d.'</td>';
    echo '<td>'.tanggal_ind($tanggal).'</td>';
    echo '<td>-</td>';
    echo '<td>-</td>';
    echo '<td class="text-center"><img src="../../../content/thumbnail.jpg" class="imaged w100 rounded" height="40"></td>';
    echo '<td>-</td>';
    echo '<td>-</td>';
    echo '<td class="text-center"><img src="../../../content/thumbnail.jpg" class="imaged w100 rounded" height="40"></td>';
    echo '<td>'.$status_text.'</td>';
    echo '<td>-</td>';
    echo '<td>-</td>';
    echo '</tr>';
  }
}
echo '</tbody></table>';
    $filter_jumlah = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun' AND user_id='$siswa'";
    
    // Hitung Hadir (Tepat Waktu + Terlambat)
    $query_hadir = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk IN ('Tepat Waktu', 'Terlambat')";
    $result_hadir = $connection->query($query_hadir);
    $hadir_count = $result_hadir ? $result_hadir->fetch_assoc()['jumlah'] : 0;

    // Hitung Terlambat
    $query_telat = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk='Terlambat'";
    $result_telat = $connection->query($query_telat);
    $terlambat_count = $result_telat ? $result_telat->fetch_assoc()['jumlah'] : 0;

    // Hitung Izin (dari tabel absensi dan izin)
    $query_izin_absensi = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk='Izin'";
    $result_izin_absensi = $connection->query($query_izin_absensi);
    $izin_absensi_count = $result_izin_absensi ? $result_izin_absensi->fetch_assoc()['jumlah'] : 0;

    // Hitung total hari kerja berdasarkan jadwal
    $total_hari_kerja = 0;
    for ($d=1;$d<=$jumlahhari;$d++) {
      $tanggal_check = ''.$tahun.'-'.$bulan.'-'.$d.'';
      $hari_check = date('l', strtotime($tanggal_check));
      
      // Cek apakah hari libur nasional
      $query_libur_check = "SELECT * FROM hari_libur WHERE tanggal_mulai <= '$tanggal_check' AND tanggal_selesai >= '$tanggal_check'";
      $result_libur_check = $connection->query($query_libur_check);
      $is_libur_nasional = $result_libur_check->num_rows > 0;
      
      // Cek jadwal hari kerja berdasarkan status
      $query_jadwal_check = "SELECT * FROM jadwal WHERE hari='$hari_check' AND status='Y'";
      $result_jadwal_check = $connection->query($query_jadwal_check);
      $is_hari_kerja = $result_jadwal_check->num_rows > 0;
      
      // Jika bukan hari libur nasional dan ada jadwal aktif, maka hari kerja
      if (!$is_libur_nasional && $is_hari_kerja) {
        $total_hari_kerja++;
      }
    }

    $total_izin = $izin_absensi_count + $jumlah_izin;
    $alpha = $total_hari_kerja - $hadir_count - $total_izin;

    // Pastikan Alpha tidak negatif
    if ($alpha < 0) {
        $alpha = 0;
    }

    echo'
      <table style="margin-top:15px">
        <tr>
          <td>Hadir : <span class="badge badge-success">'.$hadir_count.'</span></td>
          <td>Terlambat : <span class="badge badge-info">'.$terlambat_count.'</span></td>
          <td>Alpha : <span class="badge badge-danger">'.$alpha.'</span></td>
          <td>Izin : <span class="badge badge-info">'.$total_izin.'</span></td>
        </tr>
      </table>
   
      </div>
    </div>
  </div>
</body>
</html>';

    if(!empty($_GET['tipe']=='pdf')){
      $former = error_reporting(E_ALL ^ E_NOTICE);
      $mpdf->debug = true;
      $mpdf->useSubstitutions=false;
      $mpdf->simpleTables = true;
      $html = ob_get_contents(); 
      ob_end_clean();
      $mpdf->WriteHTML(utf8_encode($html));
      $mpdf->Output("Laporan-$bulan-".strip_tags($data_siswa['nama_lengkap']).".pdf" ,'I');
    }

  }else{
    echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
    <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
  }


break;
}}