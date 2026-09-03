<?PHP require_once'../../library/config.php';
	include_once '../../library/function.php';
	require_once'../login/user.php';
    $expired_cookie = time()+60*60*24*7;

	// Catat aktivitas logout sebelum session dihapus
	if (file_exists(__DIR__ . '/../../library/activity.php')) {
	    require_once __DIR__ . '/../../library/activity.php';
	    $admin_id = isset($current_user['admin_id']) ? $current_user['admin_id'] : null;
	    $admin_name = isset($current_user['nama']) ? $current_user['nama'] : (isset($current_user['username']) ? $current_user['username'] : '');
	    if ($admin_id) {
	        log_activity($connection, $admin_id, $admin_name, 'logout', 'Logout dari sistem');
	    }
	}

	$update_stmt = $connection->prepare("UPDATE admin SET tanggal_login=NOW(), status='Offline' WHERE admin_id=?");
    if ($update_stmt) {
        $update_stmt->bind_param('i', $current_user['admin_id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
    secure_setcookie("ADMIN_KEY", "", time()-3600, '/');
	secure_setcookie('ADMIN_KEY', '', 0, '/');
	secure_setcookie("KEY", "", time()-3600, '/');
	secure_setcookie('KEY', '', 0, '/');
	secure_setcookie('level_id', '', time()-3600, '/');
	secure_setcookie('cookie_bind', '', time()-3600, '/');
	header('location:./login');
	//session_destroy();
exit();
?>

		
