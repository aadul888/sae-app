<?php
session_start();

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    echo 'Akses ditolak';
    exit;
}

require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../../library/kelulusan_helper.php';

kelulusan_ensure_tables($connection);

$adminId = 0;
if (!empty($_COOKIE['ADMIN_KEY'])) {
    $decoded = @epm_decode($_COOKIE['ADMIN_KEY']);
    $adminId = (int) anti_injection($decoded);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'update-setting') {
    $isOpen = (isset($_POST['is_open']) && $_POST['is_open'] === 'Y') ? 'Y' : 'N';
    $showSklToUser = (isset($_POST['show_skl_to_user']) && $_POST['show_skl_to_user'] === 'Y') ? 'Y' : 'N';
    $allowDownloadSkl = (isset($_POST['allow_download_skl']) && $_POST['allow_download_skl'] === 'Y') ? 'Y' : 'N';
    $openAt = isset($_POST['open_at']) ? trim($_POST['open_at']) : '';
    $closeAt = isset($_POST['close_at']) ? trim($_POST['close_at']) : '';
    $countdownTo = isset($_POST['countdown_to']) ? trim($_POST['countdown_to']) : '';

    $openAtSql = $openAt !== '' ? date('Y-m-d H:i:s', strtotime($openAt)) : null;
    $closeAtSql = $closeAt !== '' ? date('Y-m-d H:i:s', strtotime($closeAt)) : null;
    $countdownSql = $countdownTo !== '' ? date('Y-m-d H:i:s', strtotime($countdownTo)) : null;
    $now = date('Y-m-d H:i:s');

    $stmt = $connection->prepare("UPDATE kelulusan_settings SET is_open=?, show_skl_to_user=?, allow_download_skl=?, open_at=?, close_at=?, countdown_to=?, updated_by=?, updated_at=? WHERE id=1");
    if ($stmt) {
        $stmt->bind_param('ssssssis', $isOpen, $showSklToUser, $allowDownloadSkl, $openAtSql, $closeAtSql, $countdownSql, $adminId, $now);
        $ok = $stmt->execute();
        $stmt->close();
        echo $ok ? 'success' : 'Gagal menyimpan pengaturan';
    } else {
        echo 'Gagal menyimpan pengaturan';
    }
    exit;
}

if ($action === 'save') {
    $text = isset($_POST['announcement_text']) ? trim($_POST['announcement_text']) : '';
    $now = date('Y-m-d H:i:s');

    $stmt = $connection->prepare("UPDATE kelulusan_settings SET announcement_text=?, updated_by=?, updated_at=? WHERE id=1");
    if ($stmt) {
        $stmt->bind_param('sis', $text, $adminId, $now);
        $ok = $stmt->execute();
        $stmt->close();
        echo $ok ? 'success' : 'Gagal menyimpan';
    } else {
        echo 'Gagal menyimpan';
    }
    exit;
}

echo 'Aksi tidak dikenal';
