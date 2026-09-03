<?php session_start();
// Include file gpconfig

require_once'../library/function.php';
include_once '../google/google-config.php';


if(isset($_GET['code'])){
	$gclient->authenticate($_GET['code']);
	$_SESSION['token'] = $gclient->getAccessToken();
	header('Location: ' . filter_var($redirect_url, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
	$gclient->setAccessToken($_SESSION['token']);
}

if ($gclient->getAccessToken()) {
	include_once '../../library/config.php';

	// Get user profile data from google
	$gpuserprofile 	= $google_oauthv2->userinfo->get();
	$name 			= $gpuserprofile['given_name']." ".$gpuserprofile['family_name']; // Ambil nama dari Akun Google
	$email 			= $gpuserprofile['email']; // Ambil email Akun Google nya
	$created_cookies=  md5($email);
	// Buat query untuk mengecek apakah data user dengan email tersebut sudah ada atau belum
	// Jika ada, ambil id, username, dan nama dari user tersebut
	$query_user ="SELECT user_id,email FROM employees WHERE email='$email'";
    $result_user = $connection->query($query_user);
    $row_user   = $result->fetch_assoc();

			if(empty($row_user)){
				// Jika User dengan email tersebut belum ada
				// Ambil username dari kata sebelum simbol @ pada email
				//$ex = explode('@', $email); // Pisahkan berdasarkan "@"
				//$username = $ex[0]; // Ambil kata pertama

				// Lakukan insert data user baru tanpa password

				$add ="INSERT INTO user(email,
                      password,
                      nip,
                      nama_lengkap,
                      tempat_lahir,
                      tanggal_lahir,
                      jenis_kelamin,
                      telp,
                      alamat,
                      lokasi_id,
                      posisi_id,
                      avatar,
                      tanggal_registrasi,
                      tanggal_login,
                      time,
                      ip,
                      browser,
                      status,
                      active) values('$email',
                      '$password',
                      '$nip',
                      '$nama_lengkap',
                      '', /** Tempat Lahir */
                      '$date', /** Tanggal Lahir */
                      '', /** Jenis Kelamin */
                      '', /** No. Telp */
                      '', /** Alamat */
                      '', /** Lokasi */
                      '', /** Posisi */ 
                      'avatar.jpg',
                      '$date $time',
                      '$date $time',
                      '$time_online',
                      '$ip',
                      '$browser',
                      'Offline',
                      'Y')";
		        $connection->query($add);
				$id = mysqli_insert_id($connection); // Ambil id user yang baru saja di insert
			}else{
				$id 				= convert("encrypt", strip_tags($row_user['user_id'])); 
			}

			setcookie('user', $id, $expired_cookie, '/');

    	header("location:../");
}else {
	$authUrl = $gclient->createAuthUrl();
	header("location: ".$authUrl);
}
?>
