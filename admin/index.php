<?php  @session_start();ob_start();

require_once'../library/config.php';
include_once '../library/function.php';

if (sae_registration_sync_required($connection)) {
  sae_redirect_to_registrasi();
}


if(!isset($_COOKIE['ADMIN_KEY'])){
  header('location:./login');
  exit;
}
else{ 
    require_once'./login/user.php';
}

  //ob_start("minify_html");
    
if(!empty($_GET['mod'])){$mod = mysqli_escape_string($connection,@$_GET['mod']);}else {$mod ='home';}


  include_once 'mod/header.php';
  if(file_exists('./mod/'.$mod.'/'.$mod.'.php')){
        include('./mod/'.$mod.'/'.$mod.'.php');
        include_once 'mod/footer.php';
        //theme_foot();
  }else{
        include('./mod/home/home.php');
        include_once './mod/footer.php';
        //theme_foot();
  }
  function theme_404(){
    echo'
    <div class="text-center">
    <h1 class="display-1 mb-20 text-info"><i class="ni ni-spaceship"></i></h1>
    <h1 class="display-1 mb-10 mt-10">404</h1>
     <h4 class="mb-10">Sepertinya Halaman yang anda tidak ditemukan</h4>
     <button type="button" class="btn btn-primary mt-4" onclick="history.back()">Kembali</button>
    </div>';
  }

  function hak_akses(){
    echo'
    <div class="text-center">
    <h1 class="display-1 mb-20 text-info"><i class="ni ni-spaceship"></i></h1>
    <h1 class="display-1 mb-10 mt-10">Oop</h1>
     <h4 class="mb-10">Anda tidak memiliki hak Akses halaman ini</h4>
     <button type="button" class="btn btn-primary mt-4" onclick="history.back()">Kembali</button>
    </div>';
  }

  //}
  //ob_end_flush(); // minify_html
?>