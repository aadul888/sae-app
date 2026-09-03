<?php
require_once '../../../library/config.php';
header('Content-Type: application/json');
$user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
$nisn = '';
if ($user_id) {
  $q = $connection->prepare("SELECT nisn FROM user WHERE user_id = ? LIMIT 1");
  $q->bind_param('s', $user_id);
  $q->execute();
  $q->bind_result($nisn);
  $q->fetch();
  $q->close();
}
echo json_encode(['nisn' => $nisn]);
