<?php
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(403); exit('Akses ditolak'); }
header('Content-Type: text/plain');
$dir = realpath(__DIR__ . '/../');
exec('git config --global --add safe.directory ' . escapeshellarg($dir) . ' 2>&1', $o, $rv);
echo "safe.directory: exit=$rv: " . implode("\n", $o) . "\n\n";
chdir($dir);
$o = []; exec('git fetch origin 2>&1', $o, $rv);
echo "fetch: " . implode("\n", $o) . " (exit=$rv)\n";
$o = []; exec('git reset --hard origin/main 2>&1', $o, $rv);
echo "reset: " . implode("\n", $o) . " (exit=$rv)\n";
$o = []; exec('git clean -fd 2>&1', $o, $rv);
echo "clean: " . implode("\n", $o) . " (exit=$rv)\n";
echo "=== Deploy done ===\n";
