<?PHP session_start();
require_once '../../library/config.php';
require_once '../../library/function.php';

if (!empty($_COOKIE['siswa'])) {
    $siswa = convert("decrypt", $_COOKIE['siswa']);
    $query_user = "SELECT user_id FROM user WHERE user_id='" . htmlentities($siswa, ENT_QUOTES, 'UTF-8') . "' LIMIT 1";
    $result_user = $connection->query($query_user);
    if ($result_user->num_rows > 0) {
        $data_user = $result_user->fetch_assoc();

        setcookie('siswa', '', time() - 3600, '/');
        header('Location: ./');
        exit();
    } else {
        echo 'Akun Anda tidak ditemukan';
        exit();
    }
} else {
    echo 'Cookie tidak ditemukan';
    setcookie('siswa', '', time() - 3600, '/');
    header('Location: ./');
    exit();
}
