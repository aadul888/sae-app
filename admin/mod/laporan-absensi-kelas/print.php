<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once '../../../library/config.php';
require_once('../../../library/function.php');
require_once '../../login/user.php';

$modul_id = 18;
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

/** Filter Kelas */
if(!empty($_GET['kelas'])){
  $kelas  = anti_injection($_GET['kelas']);
  $filter_siswa = "WHERE user.kelas='$kelas'";
}else{
  if($level_id == '2'){
    $filter_siswa = "WHERE user.admin_id='$level_id[admin_id]'";
  }else{
    $filter_siswa = "";
  } 
}


$query_siswa ="SELECT user.user_id,user.nama_lengkap,kelas.nama_kelas FROM user
INNER JOIN kelas  ON user.kelas = kelas.kelas_id $filter_siswa ORDER BY user.user_id ASC";
$result_siswa = $connection->query($query_siswa);
if($result_siswa->num_rows > 0){
$hari       = date("d");
$jumlahhari = date("t",mktime(0,0,0,$bulan,$hari,$tahun));

if(!empty($_GET['tipe']=='pdf')){
  $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L']);
  ob_start();
}

if(!empty($_GET['tipe']=='excel')){
  header('Content-Type: application/vnd.ms-excel');  
  header('Content-disposition: attachment; filename=Laporan-'.$bulan.'.xls'); 
}


echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>Laporan Absensi/Bulan</title>
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
      font-size: 66%;
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

    .badge-warning{
      color: #ff3709;
      background-color: #fee6e0;
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

    </style>
</head>
<body>

  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="kop text-center">
          <img src="../../../library/content/'.strip_tags($site_kop).'" class="imaged w100 rounded">
        </div>
        <div class="mt-3">Laporan Absensi Bulan : '.ambilbulan($bulan).' '.$tahun.'</div>
      </div>

      <div class="col-md-12">
      <table class="datatable mt-3">
      <thead>
        <tr>
          <th rowspan="2" width="40" class="text-center" style="vertical-align: middle;">No</th>
          <th rowspan="2" style="vertical-align: middle;">Nama Siswa</th>
          <th rowspan="2" style="vertical-align: middle;">Kelas</th>
          <th rowspan="2" style="vertical-align: middle;">Status</th>
          <th class="text-center" colspan="'.$jumlahhari.'">'.ambilbulan($month).'</th>
          <th class="text-center" colspan="4">Keterangan</th>
        </tr>
        <tr>';
            for ($d=1;$d<=$jumlahhari;$d++) {
                $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
                $hari_libur     = date('D',strtotime($tanggal));
            
              /** Menentukan Hari Libur Umum */
                $query_sabtu ="SELECT libur_hari FROM libur WHERE libur_hari='Sabtu' AND active='Y'";
                $result_sabtu= $connection->query($query_sabtu);
                if($result_sabtu->num_rows >0 ){
                  $sabtu = 'Sat';
                }else{
                  $sabtu ='';
                }
            
                $query_minggu ="SELECT libur_hari FROM libur WHERE libur_hari='Minggu' AND active='Y'";
                $result_minggu = $connection->query($query_minggu);
                if($result_minggu->num_rows >0 ){
                  $minggu = 'Sun';
                }else{
                  $minggu ='';
                }
            /** End Menentukan Hari Libur Umum */
            
            
                if($hari_libur == $sabtu OR $hari_libur == $minggu){
                  $warna      ='#ffffff';
                  $background ='#FF0000';
                  $status     = 'Libur';
                  $jumlah_libur++;
                }else{
                  $query_libur  = "SELECT libur_tanggal,keterangan FROM libur_nasional WHERE libur_tanggal='$tanggal'";
                  $result_libur = $connection->query($query_libur);
                  if($result_libur->num_rows > 0){
                    $data_libur = $result_libur->fetch_assoc();
                    $warna='#ffffff';
                    $background ='#FF0000';
                    $jumlah_libur_nasional++;
                    $status     = strip_tags($data_libur['keterangan']);
                  }else{
                    $warna      = '#FFFFFF';
                    $background = '#666666';
                    $status     = '-';
                  }
                }
              echo'
                <th width="50" class="text-center" style="background:'.$background.';color:'.$warna.'">'.date('D', strtotime($tanggal)).'<br>'.$d.'</th>';
            }
              echo'
                <th width="50" class="text-center">H</th>
                <th width="50" class="text-center">T</th>
                <th width="50" class="text-center">A</th>
                <th width="50" class="text-center">I</th>
              </tr>
          </thead>
        <tbody>';
                          
          while ($data_siswa = $result_siswa->fetch_assoc()){$no++;

            echo'
            <tr>
              <td rowspan="2" class="text-center">'.$no.'</td>
              <td rowspan="2" width="150">'.strip_tags($data_siswa['nama_lengkap']).'</td>
              <td rowspan="2" width="150">'.strip_tags($data_siswa['nama_kelas']).'</td>
              <td width="60">Masuk</td>';
              for ($d=1;$d<=$jumlahhari;$d++){
                $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
                $filter = "WHERE tanggal='$tanggal' AND MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";
      
                $query_absen ="SELECT tanggal,jam_masuk,jam_pulang,status_masuk,status_pulang,kehadiran FROM absensi $filter";
                $result_absen = $connection->query($query_absen);
                  if($result_absen->num_rows > 0){
                    $data_absen = $result_absen->fetch_assoc();
      
                    if($data_absen['status_masuk']=='Tepat Waktu'){
                      $status_masuk ='<span class="badge badge-success">H</span>';
                    }elseif($data_absen['status_masuk']=='Terlambat'){
                        $status_masuk ='<span class="badge badge-warning">T</span>';
                    }else{
                      if($data_absen['kehadiran'] =='Izin' || $data_absen['status_masuk']=='Izin'){
                        $status_masuk ='<span class="badge badge-info">I</span>';
                      }else{
                        $status_masuk ='<span class="badge badge-danger">A</span>';
                      }
                      
                    }
          
                  echo'
                    <td class="text-center">'.$data_absen['jam_masuk'].'<br>'.$status_masuk.'</td>';
                  }else{
                    echo'
                    <td class="text-center">X</td>';
                  }
                }

                $filter_jumlah = "MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";

                // Hitung Hadir (Tepat Waktu + Terlambat)
                $query_hadir  = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk IN ('Tepat Waktu', 'Terlambat')";
                $result_hadir = $connection->query($query_hadir);
                $hadir_count = $result_hadir ? $result_hadir->fetch_assoc()['jumlah'] : 0;

                // Hitung Terlambat
                $query_telat  = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk='Terlambat'";
                $result_telat = $connection->query($query_telat);
                $terlambat_count = $result_telat ? $result_telat->fetch_assoc()['jumlah'] : 0;

                // Hitung Izin
                $query_izin  = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk='Izin'";
                $result_izin = $connection->query($query_izin);
                $izin_count = $result_izin ? $result_izin->fetch_assoc()['jumlah'] : 0;

                // Hitung Alpha (total hari kerja - hadir - izin)
                $total_hari_kerja = $jumlahhari - $jumlah_libur - $jumlah_libur_nasional;
                $alpha = $total_hari_kerja - $hadir_count - $izin_count;
                if ($alpha < 0) $alpha = 0;

                echo'
                <td width="50" rowspan="2" class="text-center">'.$hadir_count.'</td>
                <td width="50" rowspan="2" class="text-center">'.$terlambat_count.'</td>
                <td width="50" rowspan="2" class="text-center">'.$alpha.'</td>
                <td width="50" rowspan="2" class="text-center">'.$izin_count.'</td>
            </tr>
              
            <tr>
              <td width="60">Pulang</td>';
              for ($d=1;$d<=$jumlahhari;$d++){
                $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
                $filter = "WHERE tanggal='$tanggal' AND MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";
      
                $query_absen ="SELECT tanggal,jam_pulang,status_pulang,kehadiran FROM absensi $filter";
                $result_absen = $connection->query($query_absen);
                if($result_absen->num_rows > 0){
                    $data_absen = $result_absen->fetch_assoc();
                      if(empty($data_absen['jam_pulang']) || $data_absen['jam_pulang']=='00:00:00'){
                          $status_pulang='';
                      }else{
                        if($data_absen['status_pulang']=='Pulang'){
                            $status_pulang ='<span class="badge badge-success">P</span>';
                        }elseif($data_absen['status_pulang']=='Pulang Cepat'){
                            $status_pulang ='<span class="badge badge-warning">PC</span>';
                        }else{
                            $status_pulang ='';
                        }
                      }
              echo'
                <td class="text-center">'.$data_absen['jam_pulang'].'<br>'.$status_pulang.'</td>';
              }else{
              echo'
                <td class="text-center">X</td>';
              }
            }
            echo'
            </tr>';
          }
        echo'
          </tbody>
        </table>
        
        <div style="margin-top:10px;">
          <span class="badge badge-info">H: Hadir</span>
          <span class="badge badge-warning">T: Terlambat</span>
          <span class="badge badge-danger">A: Alpha</span>
          <span class="badge badge-success">I: Izin</span>
        </div>
      </div>
    </div>
  </div>
</body>
</html>';
      $former = error_reporting(E_ALL ^ E_NOTICE);
      $mpdf->debug = true;
      $mpdf->useSubstitutions=false;
      $mpdf->simpleTables = true;
      $html = ob_get_contents(); 
      ob_end_clean();
      $mpdf->WriteHTML(utf8_encode($html));
      $mpdf->Output("Laporan-bulan-$date.pdf" ,'I');
  }else{
    echo'<h3>Data Tidak ditemukan</h3>';     
  }

  


/** Print */
break;
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

/** Filter Kelas */
if(!empty($_GET['kelas'])){
  $kelas  = anti_injection($_GET['kelas']);
  $filter_siswa = "WHERE user.kelas='$kelas'";
}else{
  $filter_siswa = "";
}


$query_siswa ="SELECT user.user_id,user.nama_lengkap,kelas.nama_kelas FROM user
INNER JOIN kelas  ON user.kelas = kelas.kelas_id $filter_siswa ORDER BY user.user_id ASC";
$result_siswa = $connection->query($query_siswa);
if($result_siswa->num_rows > 0){
$hari       = date("d");
$jumlahhari = date("t",mktime(0,0,0,$bulan,$hari,$tahun));


echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>Laporan Absensi/Bulan</title>
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
      font-size: 66%;
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

    .badge-warning{
      color: #ff3709;
      background-color: #fee6e0;
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

    </style>
    <script>
    window.onafterprint = window.close;
    window.print();
  </script>
  
</head>
<body>

  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="kop text-center">
          <img src="../../../content/'.strip_tags($site_kop).'" class="imaged w100 rounded">
        </div>
        <div class="mt-3">Laporan Absensi Bulan : '.ambilbulan($bulan).' '.$tahun.'</div>
      </div>

      <div class="col-md-12">
      <table class="datatable mt-3">
      <thead>
        <tr>
          <th rowspan="2" width="40" class="text-center" style="vertical-align: middle;">No</th>
          <th rowspan="2" style="vertical-align: middle;">Nama Siswa</th>
          <th rowspan="2" style="vertical-align: middle;">Kelas</th>
          <th rowspan="2" style="vertical-align: middle;">Status</th>
          <th class="text-center" colspan="'.$jumlahhari.'">'.ambilbulan($month).'</th>
          <th class="text-center" colspan="4">Keterangan</th>
        </tr>
        <tr>';
            for ($d=1;$d<=$jumlahhari;$d++) {
                $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
                $hari_libur     = date('D',strtotime($tanggal));
            
              /** Menentukan Hari Libur Umum */
                $query_sabtu ="SELECT libur_hari FROM libur WHERE libur_hari='Sabtu' AND active='Y'";
                $result_sabtu= $connection->query($query_sabtu);
                if($result_sabtu->num_rows >0 ){
                  $sabtu = 'Sat';
                }else{
                  $sabtu ='';
                }
            
                $query_minggu ="SELECT libur_hari FROM libur WHERE libur_hari='Minggu' AND active='Y'";
                $result_minggu = $connection->query($query_minggu);
                if($result_minggu->num_rows >0 ){
                  $minggu = 'Sun';
                }else{
                  $minggu ='';
                }
            /** End Menentukan Hari Libur Umum */
            
            
                if($hari_libur == $sabtu OR $hari_libur == $minggu){
                  $warna      ='#ffffff';
                  $background ='#FF0000';
                  $status     = 'Libur';
                  $jumlah_libur++;
                }else{
                  $query_libur  = "SELECT libur_tanggal,keterangan FROM libur_nasional WHERE libur_tanggal='$tanggal'";
                  $result_libur = $connection->query($query_libur);
                  if($result_libur->num_rows > 0){
                    $data_libur = $result_libur->fetch_assoc();
                    $warna='#ffffff';
                    $background ='#FF0000';
                    $jumlah_libur_nasional++;
                    $status     = strip_tags($data_libur['keterangan']);
                  }else{
                    $warna      = '#FFFFFF';
                    $background = '#666666';
                    $status     = '-';
                  }
                }
              echo'
                <th width="50" class="text-center" style="background:'.$background.';color:'.$warna.'">'.date('D', strtotime($tanggal)).'<br>'.$d.'</th>';
            }
              echo'
                <th width="50" class="text-center">H</th>
                <th width="50" class="text-center">T</th>
                <th width="50" class="text-center">A</th>
                <th width="50" class="text-center">I</th>
              </tr>
          </thead>
        <tbody>';
                          
          while ($data_siswa = $result_siswa->fetch_assoc()){$no++;

            echo'
            <tr>
              <td rowspan="2" class="text-center">'.$no.'</td>
              <td rowspan="2" width="150">'.strip_tags($data_siswa['nama_lengkap']).'</td>
              <td rowspan="2" width="150">'.strip_tags($data_siswa['nama_kelas']).'</td>
              <td width="60">Masuk</td>';
              for ($d=1;$d<=$jumlahhari;$d++){
                $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
                $filter = "WHERE tanggal='$tanggal' AND MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";
      
                $query_absen ="SELECT tanggal,jam_masuk,jam_pulang,status_masuk,status_pulang,kehadiran FROM absensi $filter";
                $result_absen = $connection->query($query_absen);
                  if($result_absen->num_rows > 0){
                    $data_absen = $result_absen->fetch_assoc();
      
                    if($data_absen['status_masuk']=='Tepat Waktu'){
                      $status_masuk ='<span class="badge badge-success">H</span>';
                    }elseif($data_absen['status_masuk']=='Terlambat'){
                        $status_masuk ='<span class="badge badge-warning">T</span>';
                    }else{
                      if($data_absen['kehadiran'] =='Izin' || $data_absen['status_masuk']=='Izin'){
                        $status_masuk ='<span class="badge badge-info">I</span>';
                      }else{
                        $status_masuk ='<span class="badge badge-danger">A</span>';
                      }
                      
                    }
          
                  echo'
                    <td class="text-center">'.$data_absen['jam_masuk'].'<br>'.$status_masuk.'</td>';
                  }else{
                    echo'
                    <td class="text-center">X</td>';
                  }
                }

                $filter_jumlah = "MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";

                $query_hadir  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND kehadiran='Hadir'";
                $hadir        = $connection->query($query_hadir);

                $query_telat  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND status_masuk='Telat'";
                $terlambat   = $connection->query($query_telat);

                $query_telat  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND status_masuk='Telat'";
                $terlambat   = $connection->query($query_telat);

        
                $alpha = $jumlahhari - $jumlah_libur - $jumlah_libur_nasional - $jumlah_izin;

                $query_izin  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND kehadiran='Izin'";
                $izin   = $connection->query($query_izin);

                echo'
                <td width="50" rowspan="2" class="text-center">'.$hadir_count.'</td>
                <td width="50" rowspan="2" class="text-center">'.$terlambat_count.'</td>
                <td width="50" rowspan="2" class="text-center">'.$alpha.'</td>
                <td width="50" rowspan="2" class="text-center">'.$izin_count.'</td>
            </tr>
              
            <tr>
              <td width="60">Pulang</td>';
              for ($d=1;$d<=$jumlahhari;$d++){
                $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
                $filter = "WHERE tanggal='$tanggal' AND MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";
      
                $query_absen ="SELECT tanggal,jam_pulang,status_pulang,kehadiran FROM absensi $filter";
                $result_absen = $connection->query($query_absen);
                if($result_absen->num_rows > 0){
                    $data_absen = $result_absen->fetch_assoc();
                      if(empty($data_absen['jam_pulang']) || $data_absen['jam_pulang']=='00:00:00'){
                          $status_pulang='';
                      }else{
                        if($data_absen['status_pulang']=='Pulang'){
                            $status_pulang ='<span class="badge badge-success">P</span>';
                        }elseif($data_absen['status_pulang']=='Pulang Cepat'){
                            $status_pulang ='<span class="badge badge-warning">PC</span>';
                        }else{
                            $status_pulang ='';
                        }
                      }
              echo'
                <td class="text-center">'.$data_absen['jam_pulang'].'<br>'.$status_pulang.'</td>';
              }else{
              echo'
                <td class="text-center">X</td>';
              }
            }
            echo'
            </tr>';
          }
        echo'
          </tbody>
        </table>
        
        <div style="margin-top:10px;">
          <span class="badge badge-info">H: Hadir</span>
          <span class="badge badge-warning">T: Terlambat</span>
          <span class="badge badge-danger">A: Alpha</span>
          <span class="badge badge-success">I: Izin</span>
        </div>
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