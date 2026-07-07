<?php
/** Standalone deploy — no deps, just git force sync */
$allowed_ips = ['127.0.0.1', '::1'];
$secret = $_GET['key'] ?? '';
if ($secret !== 'sae-deploy-2025' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips)) {
  http_response_code(403);
  exit('Forbidden');
}
header('Content-Type: text/plain');
chdir(__DIR__);
putenv('GIT_TERMINAL_PROMPT=0');

echo "=== Deploy start ===\n";

// fetch + merge --ff-only (tidak hapus file untracked, tidak timpa config lokal)
exec('git -c safe.directory=\'*\' fetch origin 2>&1', $out, $rv);
echo "fetch: " . implode("\n", $out) . " (exit=$rv)\n";
if ($rv !== 0) exit;
$out = [];
exec('git -c safe.directory=\'*\' merge --ff-only origin/main 2>&1', $out, $rv);
echo "merge: " . implode("\n", $out) . " (exit=$rv)\n";
if ($rv !== 0) {
  echo "merge gagal — coba pull biasa\n";
  exec('git -c safe.directory=\'*\' pull --ff-only 2>&1', $out, $rv);
  echo "pull: " . implode("\n", $out) . " (exit=$rv)\n";
}

echo "=== Deploy done ===\n";

// Auto-insert pembaharuan record so dashboard shows notification
$cfg = __DIR__ . '/library/config.php';
if (file_exists($cfg)) {
  require_once $cfg;
  $version = defined('SAE_VERSION') ? SAE_VERSION : 'v20252.2';
  $desc = 'Pembaruan otomatis: git safe.directory + perbaikan JS.';
  $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
  if (!$conn->connect_error) {
    $check = $conn->query("SELECT id FROM pembaharuan WHERE version='$version' LIMIT 1");
    if ($check && $check->num_rows === 0) {
      $conn->query("INSERT INTO pembaharuan (version, pembaharuan, release_date, created_at) VALUES ('$version', '$desc', NOW(), NOW())");
      echo "Pembaharuan $version inserted.\n";
    }
    $conn->close();
  }
}
