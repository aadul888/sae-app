<?php
require_once'../../library/config.php';
require_once'../../library/function.php';

$expired_cookie = time() + 60 * 60 * 24 * 30;

switch (@$_GET['action']){
case 'login':
$error = array();
  if (empty($_POST['username'])) { 
    $error[] = 'error';
  } else { 
    $username = anti_injection($_POST['username']);
  }

  if (empty($_POST['password'])) { 
    $error[] = 'error';
  } else {
    $password_hash = htmlentities(strip_tags($_POST['password']));
  }
  

if (empty($error)){
  $time_online = time();

  if(filter_var($username, FILTER_VALIDATE_EMAIL)){
    $query_login  = "SELECT guru_id,email,nama_lengkap,password FROM guru WHERE email='$username'";
    $result_login = $connection->query($query_login);
  }else{
    $query_login  = "SELECT guru_id,email,nama_lengkap,password FROM guru WHERE nip='$username'";
    $result_login = $connection->query($query_login);
  }

  if($result_login->num_rows > 0){
    $row_user    = $result_login->fetch_assoc();

    $USER_KEY   = convert("encrypt", strip_tags($row_user['guru_id']));
    $TOKEN_KEY  = convert("encrypt", strip_tags($row_user['email']));

    /* ---------- Update Status Online --------- */
    $update_user = "UPDATE guru SET tanggal_login='$date $time', time='$time_online', status='Online' WHERE guru_id='$row_user[guru_id]'";
    $connection->query($update_user);
    /* ---------- Update Status Online --------- */
    //verify password 
      if(password_verify($password_hash,$row_user['password'])) {
        setcookie('user', $USER_KEY, $expired_cookie, '/');
        setcookie('token', $USER_KEY, $expired_cookie, '/');
        echo'success';
      }else{
        echo "error";
      }
  }else {
    echo'error';
  }

  }else{       
    echo'error';
 }

break;
}
