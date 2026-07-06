<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
  http_response_code(403);
  exit('Akses ditolak');
}
header('Content-Type: application/json');
$git_dir = realpath(__DIR__ . '/../');
chdir($git_dir);
$out = []; $rv = -1;
exec('git fetch origin 2>&1', $out, $rv);
if ($rv !== 0) { echo json_encode(['ok'=>false,'step'=>'fetch','out'=>$out]); exit; }
exec('git reset --hard origin/main 2>&1', $out, $rv);
if ($rv !== 0) { echo json_encode(['ok'=>false,'step'=>'reset','out'=>$out]); exit; }
exec('git clean -fd 2>&1', $out, $rv);
echo json_encode(['ok'=>true,'out'=>$out]);
