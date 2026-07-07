<?php
require_once'../../../library/config.php';


if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
  require_once'../../../library/function.php';
  //include_once'../../../library/PDF/autoload.php';

if(empty(htmlentities($_GET['kelas']))){
  $kelas ='';
}else{
  $kelas = htmlentities($_GET['kelas']);
}

$query_user ="SELECT nisn,nama_lengkap,tanggal_lahir,tempat_lahir,jenis_kelamin,alamat,avatar FROM user WHERE kelas='$kelas' AND status='Tidak Aktif' ORDER BY nama_lengkap ASC";
$result_user = $connection->query($query_user);
if($result_user->num_rows > 0){
  

          
echo'
<!DOCTYPE html>
<html>
<head>
  <title>Print ID Card</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <meta http-equiv="cache-control" content="no-cache">
  <meta http-equiv="Pragma" content="no-cache">
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="icon" href="../content/'.$site_favicon.'" type="image/png">
  <style>
    body{
      font-family:Arial,Helvetica,sans-serif;
    }
  

    @page {
      size: A4;
      margin:2cm 1cm;
    }

    @media print {
      html, body {
        width: 210mm;
        height: 297mm;
      }
      
    }


    
  /* -----  ID Card -----------*/
  .body-card{
    background-size:cover!important;
    position: relative;
    width:350px;
    height:225px;
    margin:auto;
    border-radius:5px;
    overflow: hidden;
    float:left;
    margin:2px;
    border:solid 1px #eeeeee;
  }

  .body-card .body{
    position:relative;
    display:flex;
    padding:85px 10px 10px;
  }

  .body-card .body .body-front-left{
    float:left; 
  }



  .body-card .body .body-front-left .avatar{
      width:80px;
      height:110px;
      border:solid 1px #111111;
      object-fit: cover;
  }

  .body-card .body .body-front-left .avatar img{
    width:80px;
    height:110px;
    object-fit: cover;
  }

  .body-card .body .body-front-right{
    float:left;
    margin:0px 0px 0px 10px;
  }

  .body-card .body .body-front-right h3{
      font-size:15px;
      margin:0px 0px 5px 0px;
      font-weight:600;
  }

  .body-card .body .body-front-right ul{
    margin:0px;
    padding:0px;
  }

  .body-card .body .body-front-right ul li{
    margin:1px 0px;
    list-style:none;
    padding:0px;
    font-size:12.5px;
  }

  .body-card .body .body-front-right li span{
    display: inline-block;
    width: 80px;
  }

  .body-card .body-back {
    display: block;
    margin: 20px 0px;
  }

  .body-card .body-back .qrcode {
      width :150px;
      height :150px;
      display: block;
      margin-left: auto;
      margin-right: auto;
  }

</style>

<script>
  window.onafterprint = window.close;
  window.print();
</script>  

</head>
  <body>
    <div class="container">';
      while ($data_user = $result_user->fetch_assoc()){
        if($data_user['avatar'] == NULL OR $data_user['avatar']=='avatar.jpg'){
          $avatar ='<img src="../../../content/avatar/avatar.jpg" width="80">';
          }else{
          $avatar ='<img src="../../../content/avatar/'.strip_tags($data_user['avatar']).'">';
        }

      echo'
      <div class="body-card" style="background:url(../../../content/assets/Kartu-pelajar-front.jpg) no-repeat center;">
        <div class="body">
            <div class="body-front-left">
              <div class="avatar">
                  '.$avatar.'
              </div>
            </div>
            <div class="body-front-right">
                <h3>NIPD/NISN</h3>
                <ul>
                  <li><span>Nama</span> : '.strip_tags($data_user['nama_lengkap']).'</li>
                  <li><span>Tempat Lahir</span> : '.strip_tags($data_user['tempat_lahir']).'</li>
                  <li>Tanggal Lahir : '.strip_tags($data_user['tanggal_lahir']).'</li>
                  <li>Jenis Kelamin : '.strip_tags($data_user['jenis_kelamin']).'</li>
                  <li>Alamat : '.strip_tags($data_user['alamat']).'</li>
                </ul>
            </div>
        </div>
      </div>
   
      <div class="body-card" style="background:url(../../../content/assets/Kartu-pelajar-back.jpg) no-repeat center;">
          <div class="body-back">
              <div class="qrcode">
                    <img src="../../../content/qrcode/'.strip_tags($data_user['nisn']).'.jpg"  height="150">
              </div>
          </div>
      </div>';
     }
  echo'
  </body>
</html>';
    }else{
      echo'Data yang Anda cari tidak ditemukan';
    }
}?>
