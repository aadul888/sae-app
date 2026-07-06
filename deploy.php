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
exec('git config --global --add safe.directory ' . __DIR__ . ' 2>&1');
$out = [];
$rv = -1;
echo "=== Deploy start ===\n";
exec('git fetch origin 2>&1', $out, $rv);
echo "fetch: " . implode("\n", $out) . " (exit=$rv)\n";
if ($rv !== 0) exit;
$out = [];
exec('git reset --hard origin/main 2>&1', $out, $rv);
echo "reset: " . implode("\n", $out) . " (exit=$rv)\n";
if ($rv !== 0) exit;
$out = [];
exec('git clean -fd 2>&1', $out, $rv);
echo "clean: " . implode("\n", $out) . " (exit=$rv)\n";
echo "=== Deploy done ===\n";
