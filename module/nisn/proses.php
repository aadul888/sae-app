<?php 
require_once '../../library/config.php';
require_once '../../library/function.php';

switch (@$_GET['action']){
case 'cari':
if(empty(htmlentities($_POST['nisn']))){
  $nisn ='';
}else{
  $nisn = htmlentities($_POST['nisn']);
}


$query_user = "SELECT nisn FROM user WHERE nisn='$nisn'";
$result_user = $connection->query($query_user);
if($result_user -> num_rows > 0){
    $data_user = $result_user->fetch_assoc();
    $nisn = convert("encrypt", strip_tags($data_user['nisn']));
    echo'success/'.$nisn.'';

    //convert("decrypt", $data_user['nisn']);
}else{
  echo'NISN yang Anda cari tidak ditemukan, silahkan periksa kembali';
}
   
break;
}
