<?php if(empty($connection)){
	header('location:/./');
} else {
$siswa = '';
if (empty($_COOKIE['siswa'])) {

    setcookie('siswa', '', time() - 3600, '/'); 
    header('Location:../'); 
    exit();
} else {
    if (!empty($_COOKIE['siswa'])) {
        $siswa = convert("decrypt", $_COOKIE['siswa']);
    }

    $query_user = "SELECT * FROM user WHERE status='Aktif' AND user_id='".htmlentities($siswa, ENT_QUOTES, 'UTF-8')."' LIMIT 1";
    $result_user = $connection->query($query_user);
    if ($result_user->num_rows > 0) {
        $data_user = $result_user->fetch_assoc();
        extract($data_user);
    } else {
        echo 'Tidak ada user yang Login';
        setcookie('siswa', '', time() - 3600, '/'); 
        header('Location: ../'); 
        exit();
    }
}
}
?>