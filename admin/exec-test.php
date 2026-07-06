<?php
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(403); exit('Akses ditolak'); }
header('Content-Type: text/plain');
echo "exec_disabled: " . (in_array('exec', explode(',', ini_get('disable_functions'))) ? 'yes' : 'no') . "\n";
echo "safe_mode: " . ini_get('safe_mode') . "\n";
echo "user: " . exec('whoami 2>&1') . "\n";
echo "git: " . exec('which git 2>&1') . "\n";
echo "pwd: " . exec('pwd 2>&1') . "\n";
echo "ls: " . exec('ls -la / 2>&1') . "\n";
