<?php
$secret = $_GET['key'] ?? '';
if ($secret !== 'sae-deploy-2025') { http_response_code(403); exit('Akses ditolak'); }
header('Content-Type: text/plain');
$dir = realpath(__DIR__ . '/../');
putenv('HOME=/tmp');
echo "PHP_USER: " . exec('whoami 2>&1') . "\n";
echo "GIT_OWNER: " . exec('stat -c %u:%g ' . escapeshellarg($dir . '/.git') . ' 2>&1') . "\n";
echo "GIT_PERMS: " . exec('stat -c %a ' . escapeshellarg($dir . '/.git') . ' 2>&1') . "\n";
echo "PWD: " . exec('pwd 2>&1') . "\n";
echo "LS_GIT: " . exec('ls -la ' . escapeshellarg($dir . '/.git') . ' 2>&1') . "\n";
