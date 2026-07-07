<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    if ($_GET['action'] ?? '' === 'preview') {
        http_response_code(403);
        exit('Akses ditolak');
    }
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../../library/kelulusan_helper.php';

kelulusan_ensure_tables($connection);

// Validasi PDF tanpa membutuhkan finfo extension
function is_valid_pdf_file($tmp_path, $original_name = '') {
    // Cek ekstensi
    if ($original_name !== '') {
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') return false;
    }
    // Cek magic bytes: PDF selalu diawali dengan %PDF
    $handle = @fopen($tmp_path, 'rb');
    if (!$handle) return false;
    $header = fread($handle, 4);
    fclose($handle);
    return $header === '%PDF';
}

$adminId = 0;
if (!empty($_COOKIE['ADMIN_KEY'])) {
    $decoded = @epm_decode($_COOKIE['ADMIN_KEY']);
    $adminId = (int) anti_injection($decoded);
}

$upload_dir = __DIR__ . '/../../../content/e-ijazah/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// -----------------------------------------------------------
// Preview / serve PDF inline
// -----------------------------------------------------------
if ($action === 'preview') {
    $uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
    if ($uid <= 0) { http_response_code(400); exit('User tidak valid'); }

    $q = $connection->query("SELECT file_name FROM kelulusan_ijazah WHERE user_id='" . intval($uid) . "' LIMIT 1");
    if (!$q || $q->num_rows === 0) { http_response_code(404); exit('File tidak ditemukan'); }
    $row = $q->fetch_assoc();

    $file_path = $upload_dir . basename($row['file_name']);
    if (!file_exists($file_path)) { http_response_code(404); exit('File tidak ditemukan di server'); }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}

// JSON responses from here
header('Content-Type: application/json');

// -----------------------------------------------------------
// Upload single ijazah
// -----------------------------------------------------------
if ($action === 'upload') {
    try {
        $uid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($uid <= 0) { echo json_encode(['success' => false, 'message' => 'User tidak valid']); exit; }

        // Get student NISN
        $q = $connection->query("SELECT nisn FROM user WHERE user_id='" . intval($uid) . "' LIMIT 1");
        if (!$q) { echo json_encode(['success' => false, 'message' => 'Query error: ' . $connection->error]); exit; }
        if ($q->num_rows === 0) { echo json_encode(['success' => false, 'message' => 'Murid tidak ditemukan']); exit; }
        $student = $q->fetch_assoc();
        $nisn = preg_replace('/[^0-9]/', '', $student['nisn']);
        if (empty($nisn)) { echo json_encode(['success' => false, 'message' => 'NISN tidak valid']); exit; }

        if (!isset($_FILES['file_ijazah']) || $_FILES['file_ijazah']['error'] !== UPLOAD_ERR_OK) {
            $err = isset($_FILES['file_ijazah']) ? $_FILES['file_ijazah']['error'] : 'undefined';
            echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload (error: ' . $err . ')']); exit;
        }

        $file = $_FILES['file_ijazah'];
        if ($file['size'] > 10 * 1024 * 1024) { echo json_encode(['success' => false, 'message' => 'Ukuran file melebihi 10MB']); exit; }

        // Validasi PDF (magic bytes, tidak butuh finfo)
        if (!is_valid_pdf_file($file['tmp_name'], $file['name'])) {
            echo json_encode(['success' => false, 'message' => 'File harus berformat PDF']);
            exit;
        }

        $file_name = $nisn . '.pdf';
        $dest = $upload_dir . $file_name;

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori upload']); exit;
            }
        }

        if (!is_writable($upload_dir)) {
            echo json_encode(['success' => false, 'message' => 'Direktori upload tidak dapat ditulis']); exit;
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file ke server (dest: ' . $dest . ')']); exit;
        }

        $now = $connection->real_escape_string(date('Y-m-d H:i:s'));
        $fn_esc = $connection->real_escape_string($file_name);
        $nisn_esc = $connection->real_escape_string($nisn);

        $sql = "INSERT INTO kelulusan_ijazah (user_id, nisn, file_name, uploaded_by, uploaded_at, konfirmasi)
                VALUES ('$uid', '$nisn_esc', '$fn_esc', '$adminId', '$now', 'belum')
                ON DUPLICATE KEY UPDATE file_name='$fn_esc', uploaded_by='$adminId', uploaded_at='$now', konfirmasi='belum', konfirmasi_at=NULL, catatan_kesalahan=NULL";
        if ($connection->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Ijazah berhasil diupload: ' . $file_name]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $connection->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// -----------------------------------------------------------
// Upload batch via ZIP (NISN.pdf files inside)
// -----------------------------------------------------------
if ($action === 'upload-batch') {
    try {
        if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
            $err = isset($_FILES['zip_file']) ? $_FILES['zip_file']['error'] : 'undefined';
            echo json_encode(['success' => false, 'message' => 'Tidak ada file ZIP yang dikirim (error: ' . $err . ')']); exit;
        }

        $zip_file = $_FILES['zip_file'];

        // Max 50MB for ZIP
        if ($zip_file['size'] > 50 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran ZIP melebihi 50MB']); exit;
        }

        // Validate ZIP magic bytes (PK)
        $handle = @fopen($zip_file['tmp_name'], 'rb');
        if (!$handle) { echo json_encode(['success' => false, 'message' => 'Tidak dapat membaca file']); exit; }
        $magic = fread($handle, 2);
        fclose($handle);
        if ($magic !== 'PK') {
            echo json_encode(['success' => false, 'message' => 'File harus berformat ZIP']); exit;
        }

        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'ZipArchive tidak tersedia di server ini']); exit;
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_file['tmp_name']) !== true) {
            echo json_encode(['success' => false, 'message' => 'Gagal membuka file ZIP']); exit;
        }

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $zip->close();
                echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori upload']); exit;
            }
        }

        if (!is_writable($upload_dir)) {
            $zip->close();
            echo json_encode(['success' => false, 'message' => 'Direktori upload tidak dapat ditulis']); exit;
        }

        $results = [];
        $sukses = 0;
        $gagal = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry_name = $zip->getNameIndex($i);

            // Skip directories
            if (substr($entry_name, -1) === '/') continue;

            $basename = basename($entry_name);
            $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

            // Only process .pdf files
            if ($ext !== 'pdf') continue;

            // Extract NISN from filename
            $base_no_ext = pathinfo($basename, PATHINFO_FILENAME);
            $nisn = preg_replace('/[^0-9]/', '', $base_no_ext);
            if (empty($nisn)) {
                $results[] = ['file' => $basename, 'status' => 'gagal', 'keterangan' => 'Nama file bukan NISN'];
                $gagal++; continue;
            }

            // Read content from ZIP
            $content = $zip->getFromIndex($i);
            if ($content === false) {
                $results[] = ['file' => $basename, 'status' => 'gagal', 'keterangan' => 'Gagal membaca dari ZIP'];
                $gagal++; continue;
            }

            // Validate PDF magic bytes
            if (substr($content, 0, 4) !== '%PDF') {
                $results[] = ['file' => $basename, 'status' => 'gagal', 'keterangan' => 'Bukan file PDF'];
                $gagal++; continue;
            }

            // Max 10MB per file
            if (strlen($content) > 10 * 1024 * 1024) {
                $results[] = ['file' => $basename, 'status' => 'gagal', 'keterangan' => 'File melebihi 10MB'];
                $gagal++; continue;
            }

            // Find student by NISN
            $nisn_esc = $connection->real_escape_string($nisn);
            $q = $connection->query("SELECT user_id FROM user WHERE nisn='$nisn_esc' AND (status='1' OR LOWER(status)='aktif') LIMIT 1");
            if (!$q || $q->num_rows === 0) {
                $results[] = ['file' => $basename, 'status' => 'lewati', 'keterangan' => 'NISN ' . $nisn . ' tidak terdaftar'];
                $gagal++; continue;
            }
            $uid = (int)$q->fetch_assoc()['user_id'];

            $file_name = $nisn . '.pdf';
            $dest = $upload_dir . $file_name;

            if (file_put_contents($dest, $content) === false) {
                $results[] = ['file' => $basename, 'status' => 'gagal', 'keterangan' => 'Gagal simpan ke server'];
                $gagal++; continue;
            }

            $fn_esc = $connection->real_escape_string($file_name);
            $now_esc = $connection->real_escape_string(date('Y-m-d H:i:s'));
            $sql = "INSERT INTO kelulusan_ijazah (user_id, nisn, file_name, uploaded_by, uploaded_at, konfirmasi)
                    VALUES ('$uid', '$nisn_esc', '$fn_esc', '$adminId', '$now_esc', 'belum')
                    ON DUPLICATE KEY UPDATE file_name='$fn_esc', uploaded_by='$adminId', uploaded_at='$now_esc', konfirmasi='belum', konfirmasi_at=NULL, catatan_kesalahan=NULL";
            if ($connection->query($sql)) {
                $results[] = ['file' => $basename, 'status' => 'sukses', 'keterangan' => 'Berhasil (NISN: ' . $nisn . ')'];
                $sukses++;
            } else {
                $results[] = ['file' => $basename, 'status' => 'gagal', 'keterangan' => 'DB error'];
                $gagal++;
            }
        }

        $zip->close();
        echo json_encode(['success' => true, 'sukses' => $sukses, 'gagal' => $gagal, 'results' => $results]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// -----------------------------------------------------------
// Hapus ijazah
// -----------------------------------------------------------
if ($action === 'hapus') {
    $uid = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    if ($uid <= 0) { echo json_encode(['success' => false, 'message' => 'User tidak valid']); exit; }

    $q = $connection->query("SELECT file_name FROM kelulusan_ijazah WHERE user_id='" . intval($uid) . "' LIMIT 1");
    if ($q && $r = $q->fetch_assoc()) {
        $fpath = $upload_dir . basename($r['file_name']);
        if (file_exists($fpath)) @unlink($fpath);
    }

    $connection->query("DELETE FROM kelulusan_ijazah WHERE user_id='" . intval($uid) . "'");
    echo json_encode(['success' => true, 'message' => 'Ijazah berhasil dihapus']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
