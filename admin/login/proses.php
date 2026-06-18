<?PHP
require_once'../../library/config.php'; 
include_once'../../library/function.php';
include('../../library/PHPMailer/PHPMailerAutoload.php');

if (!function_exists('sync_pengguna_password_matches')) {
  function sync_pengguna_password_matches($plain, $stored)
  {
    $plain = (string) $plain;
    $stored = (string) $stored;

    if ($stored === '') {
      return false;
    }

    if (password_verify($plain, $stored)) {
      return true;
    }

    return hash_equals($stored, $plain);
  }
}

if (!function_exists('resolve_sync_pengguna_level_id')) {
  function resolve_sync_pengguna_level_id($connection, $penggunaRow)
  {
    $peran = trim((string) ($penggunaRow['peran_id_str'] ?? ''));
    $ptkId = trim((string) ($penggunaRow['ptk_id'] ?? ''));
    $jenisPtk = '';

    if ($peran === 'Kepala Sekolah') {
      return [13, ''];
    }

    if ($peran === 'Operator Sekolah') {
      return [1, ''];
    }

    if ($peran === 'PTK' && $ptkId !== '') {
      $stmt = $connection->prepare("SELECT jenis_ptk_id_str FROM sync_gtk WHERE ptk_id = ? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param('s', $ptkId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && ($row = $result->fetch_assoc())) {
          $jenisPtk = trim((string) ($row['jenis_ptk_id_str'] ?? ''));
        }
        $stmt->close();
      }

      return [(stripos($jenisPtk, 'Guru') !== false ? 2 : 3), $jenisPtk];
    }

    return [3, ''];
  }
}

if (!function_exists('sync_pengguna_bootstrap_admin_account')) {
  function sync_pengguna_bootstrap_admin_account($connection, $penggunaRow)
  {
    $penggunaId = trim((string) ($penggunaRow['pengguna_id'] ?? ''));
    $username = trim((string) ($penggunaRow['username'] ?? ''));
    if ($penggunaId === '' || $username === '') {
      return null;
    }

    $fullname = trim((string) ($penggunaRow['nama'] ?? ''));
    $phone = trim((string) ($penggunaRow['no_hp'] ?? $penggunaRow['no_telepon'] ?? ''));
    $password = (string) ($penggunaRow['password'] ?? '');
    if ($password === '') {
      $password = password_hash('12345', PASSWORD_DEFAULT);
    }

    list($levelId, $jenisPtkIdStr) = resolve_sync_pengguna_level_id($connection, $penggunaRow);
    $peranIdStr = trim((string) ($penggunaRow['peran_id_str'] ?? ''));
    $ptkId = trim((string) ($penggunaRow['ptk_id'] ?? ''));
    $avatar = 'avatar.jpg';
    $gelarDepan = '';
    $gelarBelakang = '';
    $tugasTambahan = '';
    $active = 'Y';
    $status = 'Offline';
    $now = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    $browserInfo = getBrowser();
    $browser = isset($browserInfo['name'], $browserInfo['version']) ? ($browserInfo['name'] . ' ' . $browserInfo['version']) : 'Unknown';
    $timeOnline = (string) time();
    $email = $username;

    $stmt = $connection->prepare("SELECT admin_id, avatar, gelar_depan, gelar_belakang, tugas_tambahan FROM admin WHERE pengguna_id = ? LIMIT 1");
    if (!$stmt) {
      return null;
    }

    $stmt->bind_param('s', $penggunaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($existing) {
      $avatar = !empty($existing['avatar']) ? $existing['avatar'] : $avatar;
      $gelarDepan = trim((string) ($existing['gelar_depan'] ?? ''));
      $gelarBelakang = trim((string) ($existing['gelar_belakang'] ?? ''));
      $tugasTambahan = trim((string) ($existing['tugas_tambahan'] ?? ''));

      $update = $connection->prepare("UPDATE admin SET username=?, email=?, password=?, fullname=?, phone=?, avatar=?, gelar_depan=?, gelar_belakang=?, level_id=?, peran_id_str=?, ptk_id=?, jenis_ptk_id_str=?, active='Y', status='Offline', sync_status='synced', last_sync_at=NOW(), updated_at=NOW() WHERE pengguna_id=?");
      if (!$update) {
        return null;
      }

      $update->bind_param('ssssssssissss', $username, $email, $password, $fullname, $phone, $avatar, $gelarDepan, $gelarBelakang, $levelId, $peranIdStr, $ptkId, $jenisPtkIdStr, $penggunaId);
      $ok = $update->execute();
      $update->close();
      if (!$ok) {
        return null;
      }
    } else {
      $insert = $connection->prepare("INSERT INTO admin (pengguna_id, username, email, password, fullname, phone, avatar, gelar_depan, gelar_belakang, level_id, peran_id_str, ptk_id, jenis_ptk_id_str, active, status, tugas_tambahan, time, ip, browser, sync_status, last_sync_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'synced', NOW(), NOW(), NOW())");
      if (!$insert) {
        return null;
      }

      $insert->bind_param('sssssssssissssssssss', $penggunaId, $username, $email, $password, $fullname, $phone, $avatar, $gelarDepan, $gelarBelakang, $levelId, $peranIdStr, $ptkId, $jenisPtkIdStr, $active, $status, $tugasTambahan, $timeOnline, $ip, $browser);
      $ok = $insert->execute();
      $insert->close();
      if (!$ok) {
        return null;
      }
    }

    $query = $connection->prepare("SELECT * FROM admin WHERE pengguna_id = ? LIMIT 1");
    if (!$query) {
      return null;
    }

    $query->bind_param('s', $penggunaId);
    $query->execute();
    $result = $query->get_result();
    $adminRow = $result ? $result->fetch_assoc() : null;
    $query->close();

    return $adminRow;
  }
}

if (!function_exists('ensure_default_admin_account')) {
  function ensure_default_admin_account($connection)
  {
    $default_username = 'adminsae';
    $default_password = 'adminsae123';
    $default_fullname = 'Administrator SAE';
    $default_email = 'adminsae@local.sae';
    $default_avatar = 'avatar.jpg';
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $time_online = (string) time();
    $default_ip = '127.0.0.1';
    $default_browser = 'System Seed';
    $default_level_id = 1;
    $default_status = 'Offline';
    $default_active = 'Y';

    $check_stmt = $connection->prepare("SELECT admin_id FROM admin WHERE username=? LIMIT 1");
    if (!$check_stmt) {
      return false;
    }

    $check_stmt->bind_param('s', $default_username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $check_stmt->close();

    if ($exists) {
      return true;
    }

    $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
    $insert_stmt = $connection->prepare("INSERT INTO admin (fullname, gelar_depan, gelar_belakang, username, phone, email, password, avatar, registrasi_date, tanggal_login, time, status, level_id, ip, browser, active, sync_status) VALUES (?, '', '', ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'manual')");
    if (!$insert_stmt) {
      return false;
    }

    $insert_stmt->bind_param(
      'sssssssssisss',
      $default_fullname,
      $default_username,
      $default_email,
      $password_hash,
      $default_avatar,
      $today,
      $now,
      $time_online,
      $default_status,
      $default_level_id,
      $default_ip,
      $default_browser,
      $default_active
    );

    $saved = $insert_stmt->execute();
    $insert_stmt->close();
    return $saved;
  }
}

$ip_login 		    = $_SERVER['REMOTE_ADDR'];
$created_login	 = date('Y-m-d H:i:s');
$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];
$expired_cookie = time()+60*60*24*7;

switch (@$_GET['action']){
case 'login':
  if (sae_registration_sync_required($connection)) {
    echo 'redirect:registrasi';
    break;
  }

  $error = array();
  if (empty($_POST['username'])) { 
      $error[] = 'Username Dapodik tidak boleh kosong';
    } else { 
      $username = mysqli_real_escape_string($connection,$_POST['username']);
  }

  if (empty($_POST['password'])) { 
      $error[] = 'Password tidak boleh kosong';
    } else {
      $password_hash = mysqli_real_escape_string($connection,$_POST['password']);
  }

if (empty($error)){
  $time_online = time();
  $query_login = "SELECT * FROM admin WHERE username='$username' AND active='Y' LIMIT 1";
  $result_login       = $connection->query($query_login);

  if($result_login->num_rows > 0){
    $admin_row = $result_login->fetch_assoc();
    if(sync_pengguna_password_matches($password_hash, $admin_row['password'])) {
      $ADMIN_KEY          = htmlentities(epm_encode($admin_row['admin_id']));
      $KEY                = hash('sha256',$admin_row['username']);
      /* ---------- Update Status Online --------- */
      $update_admin = "UPDATE admin SET time='$time_online', status='Online' WHERE admin_id='" . intval($admin_row['admin_id']) . "'";
      $connection->query($update_admin);
      /* ---------- Update Status Online --------- */
      setcookie('ADMIN_KEY', $ADMIN_KEY, $expired_cookie, '/');
      setcookie('KEY', $KEY, $expired_cookie, '/');
      setcookie('level_id', $admin_row['level_id'], $expired_cookie, '/');
      if (!empty($admin_row['tugas_tambahan'])) {
        setcookie('tugas_tambahan', $admin_row['tugas_tambahan'], $expired_cookie, '/');
      }
      echo'success';
    }else{
      echo "Username dan password yang Anda masukkan salah!";
    }
  }
  else {
    echo'Akun tidak ditemukan!';
    }
  }

 else{       
  	 foreach ($error as $key => $values) {            
          echo"$values\n";
        }
  }



/* ------------  FORGOT -------------*/
break;
case 'forgot':


$error = array();
  if (empty($_POST['email'])) {
      $error[] = 'Email tidak boleh kosong';
    } else {
      $email= mysqli_real_escape_string($connection, $_POST['email']);
  }

  $password_baru = randomPassword();
  $password      = password_hash($password_baru,PASSWORD_DEFAULT);

  if (empty($error)) {
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $query="SELECT fullname,email from admin where email='$email'";
  $result= $connection->query($query);
  if($result ->num_rows >0){
    $row_user = $result->fetch_assoc();

    // Konfigurasi SMTP
    if($gmail_active =='Y'){
      $mail = new PHPMailer;
      $mail->isSMTP();
      $mail->Host = $gmail_host;
      $mail->Username = $gmail_username; // Email Pengirim
      $mail->Password = $gmail_password; // Isikan dengan Password email pengirim
      $mail->Port = $gmail_port;
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = 'ssl';
      $mail->SMTPDebug = 2; // Aktifkan untuk melakukan debugging

      $mail->setFrom($gmail_username, $site_name);  //Email Pengirim
      $mail->addAddress($row_user['email'], $row_user['fullname']); // Email Penerima

      $mail->isHTML(true); // Aktifkan jika isi emailnya berupa html
      // Subjek email
      $mail->Subject = 'Resset password Baru | '.$site_name.'';

      $mailContent = '<h1>'.$site_name.'</h1><br>
          <h3>Halo, '.$row_user['fullname'].'</h3><br>
          <p>Selamat akun anda berahsil kami reset ulang, silahkan login dengan password baru dibawah ini<br>
          Email : '.$row_user['email'].'
          <b>Password Baru Anda : '.$password_baru.'</b><br>
          IP : '.$ip.'<br>Browser : '.$browser.'<br><br><br><br>
          Harap simpan baik-baik akun Anda.<br><br>
          Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
      $mail->Body = $mailContent;
      //$mail->AddEmbeddedImage(''.$site_name.'', '../../../content/'.$site_logo.''); //Logo 
    }

    $update="UPDATE admin SET password='$password' WHERE email='$email'"; 
    if($connection->query($update) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan.!';
    } else{
        echo'success';
        if($mail->send()){
          //echo 'Pesan telah terkirim';
        }else{
          echo 'Mailer Error: ' . $mail->ErrorInfo;
        }
    }}
    else   {
       echo'Untuk Email "'.$email.'" belum terdaftar, silahkan cek kembali.!';
    }}

    else {
     echo'Email yang Anda masukkan salah.!';
    }}

    else{           
       echo"$values\n";
  }
  break;
}