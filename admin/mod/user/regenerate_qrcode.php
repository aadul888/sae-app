<?php
// regenerate_qrcode.php
session_start();
require_once __DIR__ . '/../../../library/config.php';

// Cek hak akses admin
if (!isset($_COOKIE['ADMIN_KEY'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$qr_dir = __DIR__ . '/../../../content/qrcode/';
$files = glob($qr_dir . '*'); // Dapatkan semua file
$success = true;
$deleted_count = 0;
$error_count = 0;

foreach ($files as $file) {
    if (is_file($file)) {
        // Jangan hapus file pelindung direktori
        if (basename($file) !== '.htaccess' && basename($file) !== 'index.html' && basename($file) !== 'index.php') {
            if (unlink($file)) {
                $deleted_count++;
            } else {
                $error_count++;
                $success = false;
            }
        }
    }
}

header('Content-Type: application/json');

if ($success) {
    if ($deleted_count > 0) {
        echo json_encode(['status' => 'success', 'message' => $deleted_count . ' file QR code berhasil dihapus. Halaman akan dimuat ulang.']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Tidak ada file QR code untuk dihapus.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus ' . $error_count . ' file.']);
}
