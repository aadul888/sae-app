<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}

require_once '../../../library/config.php';
require_once '../../../library/function.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== 'edit_gelar') {
  echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
  exit;
}

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid.']);
  exit;
}

$admin_id = intval($_POST['admin_id'] ?? 0);
$gelar_d = trim($_POST['gelar_depan'] ?? '');
$gelar_b = trim($_POST['gelar_belakang'] ?? '');

if ($admin_id <= 0) {
  echo json_encode(['status' => 'error', 'message' => 'ID admin tidak valid.']);
  exit;
}

// Batasi panjang
$gelar_d = mb_substr($gelar_d, 0, 50);
$gelar_b = mb_substr($gelar_b, 0, 50);

$stmt = $connection->prepare("UPDATE admin SET gelar_depan = ?, gelar_belakang = ?, updated_at = NOW() WHERE admin_id = ?");
$stmt->bind_param('ssi', $gelar_d, $gelar_b, $admin_id);

if ($stmt->execute()) {
  echo json_encode(['status' => 'success', 'message' => 'Gelar berhasil diperbarui.']);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui gelar: ' . $stmt->error]);
}
$stmt->close();
