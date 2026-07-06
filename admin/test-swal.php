<?php
/**
 * Force trigger SweetAlert update notification (test only).
 * Access after login: /admin/test-swal.php
 */
@session_start();
require_once '../library/config.php';
require_once '../library/function.php';

if (!isset($_COOKIE['ADMIN_KEY'])) {
  header('HTTP/1.0 403 Forbidden');
  exit('Login first');
}

// Insert test row if not exists
$q = $connection->query("SELECT COUNT(*) AS cnt FROM pembaharuan WHERE version='v20252.2'");
$r = $q->fetch_assoc();
if ($r['cnt'] == 0) {
  $connection->query("INSERT INTO pembaharuan (version, pembaharuan, release_date, created_at) VALUES ('v20252.2', 'Pembaruan uji coba SweetAlert', NOW(), NOW())");
}

// Also set last_deploy_at
$connection->query("UPDATE setting SET last_deploy_at = NOW() WHERE id = 1");

setcookie('dismiss_deploy', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html><head><title>Test Swal</title></head>
<body>
<h2>SweetAlert trigger inserted</h2>
<p>Row v20252.2 inserted, dismiss cookie cleared.</p>
<p><a href="./">Go to Dashboard</a></p>
</body>
</html>
