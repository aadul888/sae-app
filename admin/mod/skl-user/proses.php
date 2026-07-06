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

if ($action === 'update-status') {
    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'BELUM_DIPUTUSKAN';
    $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

    if ($userId <= 0) {
        echo 'User tidak valid';
        exit;
    }

    if (kelulusan_upsert_status($connection, $userId, $status, $catatan, $adminId)) {
        echo 'success';
    } else {
        echo 'Gagal menyimpan keputusan';
    }
    exit;
}

if ($action === 'toggle-skl-visibility') {
    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $isVisible = (isset($_POST['is_visible']) && $_POST['is_visible'] === 'N') ? 'N' : 'Y';

    if ($userId <= 0) {
        echo 'User tidak valid';
        exit;
    }

    $check = $connection->query("SELECT user_id FROM kelulusan_skl WHERE user_id='" . intval($userId) . "' LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        echo 'SKL belum tersedia untuk murid ini';
        exit;
    }

    if (kelulusan_set_skl_visibility($connection, $userId, $isVisible)) {
        echo 'success';
    } else {
        echo 'Gagal mengubah visibilitas SKL';
    }
    exit;
}

if ($action === 'mass-lulus') {
    $students = kelulusan_get_final_grade_students($connection);
    $updated = 0;
    foreach ($students as $row) {
        $userId = (int) $row['user_id'];
        if ($userId <= 0) {
            continue;
        }

        $catatan = 'Keputusan masal: Lulus semua oleh admin.';
        if (kelulusan_upsert_status($connection, $userId, 'LULUS', $catatan, $adminId)) {
            $updated++;
        }
    }

    echo 'success|' . $updated;
    exit;
}

echo 'Aksi tidak dikenal';
