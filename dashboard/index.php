<?php ob_start();
session_start();

require_once '../library/config.php';
include_once '../library/function.php';

// Cek status sistem untuk maintenance
checkSystemMaintenance();

if (!isset($_COOKIE['siswa'])) {
   header('location:../login');
   exit;
} else {
   require_once './oauth/user.php';
}

//ob_start("minify_html");
$website_url        = strip_tags($row_site['site_url']);
$website_name       = strip_tags($row_site['site_name']);
$website_phone      = strip_tags($row_site['site_phone']);
$website_addres     = strip_tags($row_site['site_address']);
$website_logo       = strip_tags($row_site['site_logo']);
$website_email      = strip_tags($row_site['site_email']);

$mod = 'home';
if (!empty($_GET['mod'])) {
   $mod = htmlspecialchars($_GET['mod']);
} else {
   $mod = 'home';
}

// Central gate: if a menu record exists for this slug and it's not active, show 404
$module_allowed = true;
if (isset($connection)) {
   $stmt = $connection->prepare("SELECT aktif FROM student_menu WHERE slug = ? LIMIT 1");
   if ($stmt) {
      $stmt->bind_param('s', $mod);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $row = $res->fetch_assoc()) {
         if ($row['aktif'] !== 'Y') {
            $module_allowed = false;
         }
      }
      $stmt->close();
   }
}

require_once 'mod/header.php';

if (!$module_allowed) {
   theme_404();
} else {
   if (file_exists('./mod/' . $mod . '/' . $mod . '.php')) {
      require_once './mod/' . $mod . '/' . $mod . '.php';
   } else {
      //echo'404';
      theme_404();
   }
}
require_once 'mod/footer.php';
function theme_404()
{
   echo '
   <div class="container-fluid mt-6">
      <div class="text-center row">
         <div class="col-xl-12">
            <div class="card">
               <div class="card-body">
                  <h1 class="display-1 mb-20 text-warning"><i class="ni ni-lock-circle-open"></i></h1>
                  <h1 class="display-2 mb-10 mt-10">Tutup Sementara</h1>
                  <h4 class="mb-10">Halaman ini sedang ditutup sementara oleh administrator. Silakan coba lagi nanti.</h4>
                  <button type="button" class="btn btn-primary mt-4" onclick="history.back()">Kembali</button>
               </div>
            </div>
         </div>
      </div>
   </div>';
}
