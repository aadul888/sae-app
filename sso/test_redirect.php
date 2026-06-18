<?php
// Minimal SSO test helper — builds a signed token and redirects to PKL
require_once __DIR__ . '/../library/config.php';
require_once __DIR__ . '/../library/sso_config.php';
require_once __DIR__ . '/../library/function.php';

$user_id = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? $_GET['user_id'] : '';
$nisn = isset($_GET['nisn']) ? $_GET['nisn'] : '';

if (empty($user_id)) {
    if (isset($_COOKIE['siswa'])) {
        $user_id = convert('decrypt', $_COOKIE['siswa']);
    } else {
        $user_id = '1';
    }
}

$payload = [
    'user_id' => $user_id,
    'nisn'    => $nisn,
    'iat'     => time(),
    'exp'     => time() + 60,
];

$b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
$sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $b64, SSO_SECRET, true)), '+/', '-_'), '=');
$token = $b64 . '.' . $sig;

$target = isset($_GET['target']) ? $_GET['target'] : 'http://localhost/pklv2/home';
$pkl_url = defined('PKL_SSO_URL') ? PKL_SSO_URL : 'https://localhost/pkl/sso/receive_student.php';
$redirect = $pkl_url . '?token=' . urlencode($token) . '&target=' . urlencode($target);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>SSO Test Redirect</title>
</head>
<body>
  <h3>SSO Test</h3>
  <p>user_id: <?php echo htmlspecialchars($user_id); ?></p>
  <p>Token expires in 60s. Opening PKL receive endpoint now...</p>
  <p><a id="ssoLink" href="<?php echo htmlspecialchars($redirect); ?>">Open PKL SSO receiver</a></p>
  <script>setTimeout(function(){ location.href = document.getElementById('ssoLink').href; }, 1000);</script>
</body>
</html>
