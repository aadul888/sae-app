<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../login/user.php';

$modul_id = 17;
include __DIR__ . '/../check_role.php';
if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
  echo 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.';
  exit;
}

include_once '../../../library/vendor/autoload.php';
$no = 0;

switch (@$_GET['action']){
case 'print':
    $tanggal = isset($_GET['tanggal']) ? date('Y-m-d', strtotime($_GET['tanggal'])) : date('Y-m-d');
    $query_absen = "SELECT a.*, u.nisn, u.nama_lengkap, k.nama_kelas FROM absensi a LEFT JOIN user u ON a.user_id = u.user_id LEFT JOIN kelas k ON u.kelas = k.kelas_id WHERE a.tanggal='$tanggal' ORDER BY a.id DESC";
    $result_absen = $connection->query($query_absen);
    if($result_absen->num_rows > 0){

      if(!empty($_GET['tipe']=='pdf')){
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-P']);
        ob_start();
      }

      if(!empty($_GET['tipe']=='excel')){
        header('Content-Type: application/vnd.ms-excel');  
        header('Content-disposition: attachment; filename=Laporan-hari-ini-'.$date.'.xls'); 
      }
      
    echo'
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="s-widodo.com">
        <meta name="author" content="s-widodo.com">
        <title>Laporan Absensi hari ini</title>
        <style>
    
        body{font-family:Arial,Helvetica,sans-serif}
        .text-center{
          text-align: center;
        }
        
        .kop {
          position:relative;
          display:contents;
          margin:0px 0px 20px 0px;
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
    
        .rounded {
          border-radius: 0.375rem !important;
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
    
            <div class="mt-3">Laporan Absensi Tanggal : '.format_hari_tanggal($tanggal).'</div>
          </div>
    
          <div class="col-md-12">
              <table class="datatable mt-3">
                  <thead>
                    <tr>
                      <th style="width:20px" class="text-center">No</th>
                      <th>NISN</th>
                      <th>Nama</th>
                      <th>Kelas</th>
                      <th class="text-center">Jam Masuk</th>
                      <th class="text-center">Jam Pulang</th>
                      <th class="text-center">Status Masuk</th>
                      <th class="text-center">Status Pulang</th>
                      <th class="text-center">Kehadiran</th>
                    </tr>
                  </thead>
                  <tbody>';
                    while ($data_absen = $result_absen->fetch_assoc()){$no++;
                    echo'
                    <tr>
                      <td class="text-center">'.$no.'</td>
                      <td>'.strip_tags($data_absen['nisn']).'</td>
                      <td>'.strip_tags($data_absen['nama_lengkap']).'</td>
                      <td>'.strip_tags($data_absen['nama_kelas']).'</td>
                      <td class="text-center">'.$data_absen['jam_masuk'].'</td>
                      <td class="text-center">'.$data_absen['jam_pulang'].'</td>
                      <td class="text-center">'.$data_absen['status_masuk'].'</td>
                      <td class="text-center">'.$data_absen['status_pulang'].'</td>
                      <td class="text-center">'.$data_absen['kehadiran'].'</td>
                    </tr>';
                    }
                  echo'
                  </tbody>
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
      $mpdf->Output("Laporan-hari-ini-$date.pdf" ,'I');
    }

    }else{
      echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
      <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
    }

    break;
}}