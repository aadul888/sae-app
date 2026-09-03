<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';
  include('../../../library/PHPMailer/PHPMailerAutoload.php');
  $max_size = 3000000; //2MB
  $allowed_ext = array('jpg', 'jpeg', 'gif', 'png');
  $iB              = getBrowser();
  $browser          = $iB['name'] . ' ' . $iB['version'];
  $ip              = $_SERVER['REMOTE_ADDR'];

  switch (@$_GET['action']) {
    case 'load-data':
      $query_user = "SELECT * FROM admin WHERE admin.admin_id='$current_user[admin_id]'";
      $result_user = $connection->query($query_user);
      if ($result_user->num_rows > 0) {
        $data_user  = $result_user->fetch_assoc();

        if (htmlentities($_GET['id'] == 1)) {
          echo '
<div class="form-group">
  <h4>PROFIL ' . strip_tags(strtoupper(trim($data_user['gelar_depan'] . ' ' . $data_user['fullname']) . (trim($data_user['gelar_belakang']) != '' ? ', ' . trim($data_user['gelar_belakang']) : ''))) . '</h4>
</div>

<form class="form-update" role="form" method="post" action="javascript:void(0);" autocomplete="off">
    <div class="form-group">
    <label>Nama lengkap</label>
        <input type="text" class="form-control" name="fullname" value="' . strip_tags($data_user['fullname']) . '" required>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label>Gelar Depan</label>
        <input type="text" class="form-control" name="gelar_depan" value="' . strip_tags($data_user['gelar_depan']) . '">
      </div>
      <div class="form-group col-md-6">
        <label>Gelar Belakang</label>
        <input type="text" class="form-control" name="gelar_belakang" value="' . strip_tags($data_user['gelar_belakang']) . '">
      </div>
    </div>

<div class="form-group">
    <label>No. Telp</label>
        <input type="number" class="form-control" name="phone" value="' . strip_tags($data_user['phone']) . '" required>
</div>

<div class="form-group">
    <label>Email</label>
        <input type="email" class="form-control" name="email" value="' . strip_tags($data_user['email']) . '" required>
</div>

<div class="form-group">
    <label>Username</label>
        <input type="text" class="form-control password" name="username" value="' . strip_tags($data_user['username']) . '" required>
</div>
<hr>
  <div class="form-group">
    <button class="btn btn-primary btn-save submitBtn"><i class="far fa-save"></i> Simpan</button>
  </div>
</form>';
        } elseif (htmlentities($_GET['id'] == 2)) {
          echo '
  <div class="form-group">
    <h4>RESET PASSWORD</h4>
  </div>

      <form class="form-password" role="form" action="javascript:void(0);" autocomplete="off">
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control email" name="email" value="' . strip_tags($data_user['email']) . '" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-group input-group-merge">
              <input type="password" class="form-control password" id="password-field"  name="password" required>
              <div class="input-group-append">
                <span class="input-group-text"><span toggle="#password-field" class="fas fa-eye toggle-password"></span></span>
              </div>
            </div>
        </div>

      <hr>
        <div class="form-group">
          <button class="btn btn-primary btn-save submitForgot"><i class="far fa-save"></i> Simpan</button>
        </div>
      </form>';
        }
      }

      /* -------------- Update ----------*/
      break;
    case 'update':
      $error = array();
      if (empty($_POST['fullname'])) {
        $error[] = 'Nama Lengkap tidak boleh kosong';
      } else {
        $fullname = anti_injection($_POST['fullname']);
      }

        // Validasi dan ambil gelar depan
        $gelar_depan = isset($_POST['gelar_depan']) ? anti_injection($_POST['gelar_depan']) : '';
        // Validasi dan ambil gelar belakang
        $gelar_belakang = isset($_POST['gelar_belakang']) ? anti_injection($_POST['gelar_belakang']) : '';

      if (empty($_POST['username'])) {
        $error[] = 'Username tidak boleh kosong';
      } else {
        $username = anti_injection($_POST['username']);
      }

      if (empty($_POST['phone'])) {
        $error[] = 'No. email tidak boleh kosong';
      } else {
        $phone = anti_injection($_POST['phone']);
      }

      if (empty($_POST['email'])) {
        $error[] = 'Email tidak boleh kosong';
      } else {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
          $error[] = "Email yang Anda masukan tidak valid";
        } else {
          $email = htmlentities(strip_tags($_POST['email']));
        }
      }


      if (empty($error)) {
        $stmt_upd = $connection->prepare("UPDATE admin SET fullname=?, gelar_depan=?, gelar_belakang=?, username=?, phone=?, email=? WHERE admin_id=?");
        $stmt_upd->bind_param('sssssss', $fullname, $gelar_depan, $gelar_belakang, $username, $phone, $email, $current_user['admin_id']);
        if (!$stmt_upd->execute()) {
          $stmt_upd->close();
          echo 'Data tidak berhasil disimpan!';
          die($connection->error . __LINE__);
        } else {
          $stmt_upd->close();
          // Auto-sync wali_kelas_nama in kelas table using current admin's ptk_id
          $updatedCount = 0;
          // Build display name: include gelar_depan before name and prepend ", " before gelar_belakang when present
          $gelar_depan_disp = trim($gelar_depan);
          $gelar_belakang_disp = trim($gelar_belakang);
          $display_name = trim(($gelar_depan_disp !== '' ? $gelar_depan_disp . ' ' : '') . $fullname);
          if ($gelar_belakang_disp !== '') {
            $display_name .= ', ' . $gelar_belakang_disp;
          }

          // update kelas rows where wali_kelas_ptk_id matches this admin's ptk_id
          if (!empty($current_user['ptk_id'])) {
            $ptk = $current_user['ptk_id'];
            $u_stmt = $connection->prepare("UPDATE kelas SET wali_kelas_nama = ? WHERE wali_kelas_ptk_id = ?");
            $u_stmt->bind_param('ss', $display_name, $ptk);
            if ($u_stmt->execute()) {
              $updatedCount = $connection->affected_rows;
            }
            $u_stmt->close();
          }

          if ($updatedCount > 0) {
            echo 'success-with-sync|' . $updatedCount . ' kelas wali berhasil disinkronkan';
            exit;
          }

          echo 'success';
          exit;
        }
      } else {
        foreach ($error as $key => $values) {
          echo "$values\n";
        }
      }


      /* ----------------- Forgot/Resset Password -----------*/
      break;
    case 'forgot':
      $error = array();

      if (empty($_POST['password'])) {
        $error[] = 'Password tidak boleh kosong';
      } else {
        $password = htmlentities(strip_tags($_POST['password']));
        $password = password_hash($password, PASSWORD_DEFAULT);
      }

      if (empty($error)) {
        $stmt_q = $connection->prepare("SELECT fullname,email,username FROM admin WHERE admin_id=?");
        $stmt_q->bind_param('s', $current_user['admin_id']);
        $stmt_q->execute();
        $result_user = $stmt_q->get_result();
        if ($result_user->num_rows > 0) {
          $row_user = $result_user->fetch_assoc();
          $stmt_q->close();
          // Konfigurasi SMTP
          if ($gmail_active == 'Y') {
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = $gmail_host;
            $mail->Username = $gmail_username; // Email Pengirim
            $mail->Password = $gmail_password; // Isikan dengan Password email pengirim
            $mail->Port = $gmail_port;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPDebug = 0; // disable debug output for AJAX response

            $mail->setFrom($gmail_username, $site_name);  //Email Pengirim
            $mail->addAddress($row_user['email'], $row_user['fullname']); // Email Penerima

            $mail->isHTML(true); // Aktifkan jika isi emailnya berupa html
            // Subjek email
            $mail->Subject = 'Resset password Baru | ' . $site_name . '';

            $mailContent = '<h1>' . $site_name . '</h1><br>
            <h3>Halo, ' . $row_user['fullname'] . '</h3><br>
            <p>Selamat akun anda berahsil kami reset ulang, silahkan login dengan password baru<br>
            Username : ' . $row_user['username'] . '<br>
            Email : ' . $row_user['email'] . '<br><b>Password Baru Anda : 123456</b><br>IP : ' . $ip . '<br>Browser : ' . $browser . '<br><br><br><br>
            Harap simpan baik-baik akun Anda.<br><br>
            Hormat Kami,<br>' . $site_name . '<br>Email otomatis, Mohon tidak membalas email ini</p>';
            $mail->Body = $mailContent;
            $mail->AddEmbeddedImage('image/logo.png', '' . $site_name . '', '../../../content/' . $site_logo . ''); //Logo 
          }

          $stmt_upd_pw = $connection->prepare("UPDATE admin SET password=? WHERE admin_id=?");
          $stmt_upd_pw->bind_param('ss', $password, $current_user['admin_id']);
          if (!$stmt_upd_pw->execute()) {
            echo 'Sepertinya Sistem Kami sedang error!';
            die($connection->error . __LINE__);
          } else {
            // Try sending email but don't output mailer debug/errors to AJAX response
            if ($gmail_active == 'Y') {
              // reduce debug level to avoid inline SMTP debug output
              $mail->SMTPDebug = 0;
              try {
                @$mail->send();
              } catch (Exception $e) {
                // ignore mail exceptions for AJAX response
              }
            }
            echo 'success';
            exit;
          }
        } else {
          echo 'Akun Anda tidak ditemukan, silahkan cek kembali.!';
        }
      } else {
        foreach ($error as $key => $values) {
          echo $values;
        }
      }


      /** Avatar */
      break;
    case 'avatar':
      $error = array();
      function resizeImage($resourceType, $image_width, $image_height)
      {
        $resizeWidth = 500;
        $resizeHeight = ($image_height / $image_width) * $resizeWidth;
        $imageLayer = imagecreatetruecolor($resizeWidth, $resizeHeight);
        imagecopyresampled($imageLayer, $resourceType, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $image_width, $image_height);
        return $imageLayer;
      }

      if (empty($_FILES['avatar']['name'])) {
        $error[]    = 'Foto belum di unggah.!';
      } else {
        $file_name        = $_FILES['avatar']['name'];
        $fileExt          = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $file_size        = $_FILES['avatar']['size'];
        $file_tmp         = $_FILES['avatar']['tmp_name'];
      }
      if (empty($error)) {
        if (in_array($fileExt, $allowed_ext) === true) {
          if ($file_size <= $max_size) {

            $sourceProperties = getimagesize($file_tmp);
            $uploadImageType  = $sourceProperties[2];
            $sourceImageWidth = $sourceProperties[0];
            $sourceImageHeight = $sourceProperties[1];

            $resizeFileName   = 'avatar-' . $current_user['username'] . '-' . time() . '';
            $uploadPath       = '../../assets/avatar/';
            $foto            = '' . $resizeFileName . '.' . $fileExt . '';

            $query = "SELECT avatar FROM admin WHERE admin_id='$current_user[admin_id]'";
            $result = $connection->query($query);
            $rows = $result->fetch_assoc();
            $avatar = $rows['avatar'];
            if (file_exists("../../assets/avatar/$avatar")) {
              if ($avatar == 'avatar.jpg') {
                //Jika avatar.kpg makan tidak hapus file
              } else {
                unlink("../../assets/avatar/$avatar");
              }
            }
            $update = "UPDATE admin SET avatar='$foto' WHERE admin_id='$current_user[admin_id]'";
            if ($connection->query($update) === false) {
              echo 'Sepertinya Sistem Kami sedang error!';
              die($connection->error . __LINE__);
            } else {
              // process and save image first, then respond
              switch ($uploadImageType) {
                case IMAGETYPE_JPEG:
                  $resourceType = imagecreatefromjpeg($file_tmp);
                  $imageLayer = resizeImage($resourceType, $sourceImageWidth, $sourceImageHeight);
                  imagejpeg($imageLayer, $uploadPath . $resizeFileName . '.' . $fileExt);
                  break;

                case IMAGETYPE_GIF:
                  $resourceType = imagecreatefromgif($file_tmp);
                  $imageLayer = resizeImage($resourceType, $sourceImageWidth, $sourceImageHeight);
                  imagegif($imageLayer, $uploadPath . $resizeFileName . '.' . $fileExt);
                  break;

                case IMAGETYPE_PNG:
                  $resourceType = imagecreatefrompng($file_tmp);
                  $imageLayer = resizeImage($resourceType, $sourceImageWidth, $sourceImageHeight);
                  imagepng($imageLayer, $uploadPath . $resizeFileName . '.' . $fileExt);
                  break;

                default:
                  $imageProcess = 0;
                  break;
              }
              echo 'success';
              exit;
            }
          } else {
            echo 'Foto terlalu besar Maksimal Size 5MB.!';
          }
        } else {
          echo 'Gambar/Foto yang di unggah tidak sesuai dengan format, Berkas harus berformat JPG,JPEG,GIF..!';
        }
      } else {
        foreach ($error as $key => $values) {
          echo $values;
        }
      }
  }
}
