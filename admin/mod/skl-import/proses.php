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
$targetDir = realpath(__DIR__ . '/../../../content') . DIRECTORY_SEPARATOR . 'skl';
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0777, true);
}

if ($action === 'single') {
    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    if ($userId <= 0) {
        echo 'Murid belum dipilih';
        exit;
    }

    $q = "SELECT u.user_id, u.nisn, u.nama_lengkap, COALESCE(ks.status,'BELUM_DIPUTUSKAN') AS status_kelulusan
          FROM user u LEFT JOIN kelulusan_status ks ON ks.user_id=u.user_id
          WHERE u.user_id='" . intval($userId) . "' LIMIT 1";
    $r = $connection->query($q);
    if (!$r || $r->num_rows === 0) {
        echo 'Data murid tidak ditemukan';
        exit;
    }

    $student = $r->fetch_assoc();
    if ($student['status_kelulusan'] !== 'LULUS') {
        echo 'SKL hanya boleh diupload untuk murid berstatus Lulus';
        exit;
    }

    if (!isset($_FILES['skl_file']) || $_FILES['skl_file']['error'] !== UPLOAD_ERR_OK) {
        echo 'Upload file gagal';
        exit;
    }

    $ext = strtolower(pathinfo($_FILES['skl_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        echo 'File wajib PDF';
        exit;
    }

    $safeName = 'SKL-' . preg_replace('/[^0-9]/', '', $student['nisn']) . '-' . date('YmdHis') . '.pdf';
    $destination = $targetDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($_FILES['skl_file']['tmp_name'], $destination)) {
        echo 'Gagal memindahkan file';
        exit;
    }

    $relativePath = 'content/skl/' . $safeName;
    if (kelulusan_upsert_skl($connection, $userId, $safeName, $relativePath, $adminId)) {
        echo 'success';
    } else {
        echo 'Gagal menyimpan data SKL';
    }
    exit;
}

if ($action === 'bulk') {
    if (!class_exists('ZipArchive')) {
        echo 'Ekstensi ZipArchive tidak tersedia di server.';
        exit;
    }

    if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
        echo 'File ZIP tidak valid';
        exit;
    }

    $zip = new ZipArchive();
    if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
        echo 'Gagal membuka file ZIP';
        exit;
    }

    $extractDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sklzip_' . uniqid();
    if (!@mkdir($extractDir, 0777, true) && !is_dir($extractDir)) {
        $zip->close();
        echo 'Gagal membuat direktori sementara';
        exit;
    }

    if (!$zip->extractTo($extractDir)) {
        $zip->close();
        echo 'Gagal mengekstrak file ZIP';
        exit;
    }
    $zip->close();

    $ok = 0;
    $fail = 0;
    $skip = 0;

    $files = array();
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile()) {
            $files[] = $fileInfo->getPathname();
        }
    }

    if (empty($files)) {
        echo 'ZIP kosong';
        exit;
    }

    foreach ($files as $sourceFile) {
        $fileName = basename($sourceFile);

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $skip++;
            continue;
        }

        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $firstTen = substr($baseName, 0, 10);
        $nisn = preg_match('/^[0-9]{10}$/', $firstTen) ? $firstTen : '';
        if ($nisn === '') {
            $fail++;
            continue;
        }

        $q = "SELECT u.user_id, COALESCE(ks.status,'BELUM_DIPUTUSKAN') AS status_kelulusan
              FROM user u LEFT JOIN kelulusan_status ks ON ks.user_id=u.user_id
              WHERE u.nisn='" . $connection->real_escape_string($nisn) . "' LIMIT 1";
        $r = $connection->query($q);
        if (!$r || $r->num_rows === 0) {
            $fail++;
            continue;
        }

        $student = $r->fetch_assoc();
        if ($student['status_kelulusan'] !== 'LULUS') {
            $fail++;
            continue;
        }

        $safeName = 'SKL-' . $nisn . '-' . date('YmdHis') . '-' . mt_rand(100, 999) . '.pdf';
        $candidate = $targetDir . DIRECTORY_SEPARATOR . $safeName;
        if (!@copy($sourceFile, $candidate)) {
            $fail++;
            continue;
        }

        $relativePath = 'content/skl/' . $safeName;
        if (kelulusan_upsert_skl($connection, (int) $student['user_id'], $safeName, $relativePath, $adminId)) {
            $ok++;
        } else {
            $fail++;
        }
    }

    $cleanupIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($cleanupIterator as $cleanupItem) {
        if ($cleanupItem->isDir()) {
            @rmdir($cleanupItem->getPathname());
        } else {
            @unlink($cleanupItem->getPathname());
        }
    }
    @rmdir($extractDir);

    echo 'success|' . $ok . '|' . $fail . '|' . $skip;
    exit;
}

echo 'Aksi tidak dikenal';
