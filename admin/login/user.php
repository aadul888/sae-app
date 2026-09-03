<?PHP
if (empty($_COOKIE['ADMIN_KEY'])) {
    secure_setcookie("ADMIN_KEY", "", time()-3600, '/');
    secure_setcookie('ADMIN_KEY', '', 0, '/');
} else {

if (isset($_COOKIE['ADMIN_KEY']) && isset($_COOKIE['KEY'])) {
    // Verify session-bound cookie signature to prevent cookie theft reuse
    $expected_bind = isset($_COOKIE['cookie_bind']) ? $_COOKIE['cookie_bind'] : '';
    $computed_bind = cookie_bind_session($_COOKIE['ADMIN_KEY'] . '|' . $_COOKIE['KEY']);
    if ($expected_bind !== '' && !hash_equals($expected_bind, $computed_bind)) {
        secure_setcookie("ADMIN_KEY", "", time()-3600, '/');
        secure_setcookie('ADMIN_KEY', '', 0, '/');
        secure_setcookie("KEY", "", time()-3600, '/');
        secure_setcookie('KEY', '', 0, '/');
        secure_setcookie('cookie_bind', '', time()-3600, '/');
        header('location:./login');
        exit;
    }

    $ADMIN_KEY = htmlentities(epm_decode($_COOKIE['ADMIN_KEY']));
    $KEY = htmlentities($_COOKIE['KEY']);

    // Use prepared statement
    $stmt = $connection->prepare("SELECT * FROM admin WHERE admin_id=? AND active='Y'");
    if (!$stmt) {
        header('location:./login');
        exit;
    }
    $stmt->bind_param('i', $ADMIN_KEY);
    $stmt->execute();
    $result_login = $stmt->get_result();
    if ($result_login->num_rows > 0) {
        $current_user = $result_login->fetch_assoc();
        $stmt->close();
        $admin_id = htmlentities($current_user['admin_id']);
        $level_id = $current_user['level_id'];
        $expired_cookie = time()+60*60*24*7;

        if ($KEY === hash('sha256', $current_user['username'])) {
            // Login Berhasil
            $time_online = time();
            $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $client_browser = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $update_stmt = $connection->prepare("UPDATE admin SET tanggal_login=NOW(), time=?, status='Online', last_login_ip=?, browser=?, ip=? WHERE admin_id=?");
            if ($update_stmt) {
                $update_stmt->bind_param('isssi', $time_online, $client_ip, $client_browser, $client_ip, $admin_id);
                $update_stmt->execute();
                $update_stmt->close();
            }

            // Catat aktivitas login
            if (file_exists(__DIR__ . '/../../library/activity.php')) {
                require_once __DIR__ . '/../../library/activity.php';
                log_activity($connection, $admin_id, $current_user['nama'] ?? $current_user['username'] ?? '', 'login', 'Login ke sistem');
            }

            secure_setcookie('level_id', $level_id, $expired_cookie, '/');
            /** Cek Siapa aja yg stausnya online */
            $query_online  = "SELECT tanggal_login,time FROM admin WHERE status='Online' AND  active='Y'";
            $result_online = $connection->query($query_online);
            if ($result_online && $result_online->num_rows > 0) {
                while ($data = $result_online->fetch_assoc()) {
                    $batas_time = 100;
                    $timeout = time() - $batas_time;
                    if ($data['time'] > $timeout) {
                        $upd = $connection->prepare("UPDATE admin SET tanggal_login=NOW(), time=?, status='Online' WHERE admin_id=?");
                        if ($upd) {
                            $upd->bind_param('ii', $time_online, $admin_id);
                            $upd->execute();
                            $upd->close();
                        }
                    } else {
                        $connection->query("UPDATE admin SET status='Offline' WHERE status='Online' AND time < $timeout");
                    }
                 }
            }

        } else {
            secure_setcookie("ADMIN_KEY", "", time()-3600, '/');
            secure_setcookie('ADMIN_KEY', '', 0, '/');
            header('location:./login');
        }
    } else {
        $stmt->close();
        // JANGAN echo di sini — file ini di-include oleh endpoint AJAX (datatable.php)
        // yang harus mengembalikan JSON murni. Echo di sini mengontaminasi output.
        secure_setcookie("ADMIN_KEY", "", time()-3600, '/');
        secure_setcookie('ADMIN_KEY', '', 0, '/');
        secure_setcookie("KEY", "", time()-3600, '/');
        secure_setcookie('KEY', '', 0, '/');
        secure_setcookie('cookie_bind', '', time()-3600, '/');
        header('location:./login');
    }

}

}