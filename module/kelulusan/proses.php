<?php
require_once '../../library/config.php';
require_once '../../library/function.php';
require_once '../../library/kelulusan_helper.php';

kelulusan_ensure_tables($connection);

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'cek') {
    $settings = kelulusan_get_settings($connection);
    $showSklToUser = (!isset($settings['show_skl_to_user']) || $settings['show_skl_to_user'] === 'Y');
    $allowDownloadSkl = (!isset($settings['allow_download_skl']) || $settings['allow_download_skl'] === 'Y');
    if (!kelulusan_is_open_now($settings)) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Pengumuman kelulusan belum dibuka oleh admin.'
        ));
        exit;
    }

    $nisn = isset($_POST['nisn']) ? trim($_POST['nisn']) : '';
    $tanggal_lahir = isset($_POST['tanggal_lahir']) ? trim($_POST['tanggal_lahir']) : '';

    if ($nisn === '' || $tanggal_lahir === '') {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'NISN dan tanggal lahir wajib diisi.'
        ));
        exit;
    }

    if (!preg_match('/^[0-9]{10}$/', $nisn)) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'NISN harus tepat 10 digit angka.'
        ));
        exit;
    }

    $student = kelulusan_find_student_by_identity($connection, $nisn, $tanggal_lahir);
    if (!$student) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Data tidak cocok. Periksa kembali NISN dan tanggal lahir Anda.'
        ));
        exit;
    }

    kelulusan_log_history($connection, $student, 'OPEN_ENVELOPE', array(
        'status' => $student['status_kelulusan']
    ));

    $downloadUrl = '';
    $viewUrl = '';
    $token = base64_encode($student['user_id'] . '|' . date('Ymd') . '|' . md5($student['nisn'] . APP_ENC_KEY));
    $viewUrl = './?mod=hasil-kelulusan&token=' . rawurlencode($token) . '&nisn=' . rawurlencode($student['nisn']);
    $visiblePerUser = (!isset($student['is_visible_to_user']) || $student['is_visible_to_user'] === 'Y');
    $berkasValid = kelulusan_is_user_berkas_valid($connection, (int) $student['user_id']);
    if ($student['status_kelulusan'] === 'LULUS' && !empty($student['file_path']) && $showSklToUser && $allowDownloadSkl && $visiblePerUser && $berkasValid) {
        $downloadUrl = './module/kelulusan/proses.php?action=download&token=' . rawurlencode($token) . '&nisn=' . rawurlencode($student['nisn']);
    }

    echo json_encode(array(
        'status' => 'success',
        'message' => 'Data ditemukan',
        'data' => array(
            'nama_lengkap' => $student['nama_lengkap'],
            'kelas' => !empty($student['nama_kelas']) ? $student['nama_kelas'] : '-',
            'status' => $student['status_kelulusan'],
            'status_label' => kelulusan_status_label($student['status_kelulusan']),
            'status_badge' => kelulusan_status_badge_class($student['status_kelulusan']),
            'catatan' => !empty($student['catatan']) ? $student['catatan'] : 'Tetap semangat untuk langkah berikutnya.',
            'download_url' => $downloadUrl,
            'redirect_url' => $viewUrl
        )
    ));
    exit;
}

if ($action === 'download') {
    $settings = kelulusan_get_settings($connection);
    $showSklToUser = (!isset($settings['show_skl_to_user']) || $settings['show_skl_to_user'] === 'Y');
    $allowDownloadSkl = (!isset($settings['allow_download_skl']) || $settings['allow_download_skl'] === 'Y');
    if (!$showSklToUser || !$allowDownloadSkl) {
        http_response_code(403);
        echo 'Unduh SKL dinonaktifkan oleh admin.';
        exit;
    }

    $token = isset($_GET['token']) ? $_GET['token'] : '';
    $nisn = isset($_GET['nisn']) ? trim($_GET['nisn']) : '';
    if ($token === '' || $nisn === '') {
        http_response_code(400);
        echo 'Token tidak valid.';
        exit;
    }

    $decoded = base64_decode($token, true);
    if (!$decoded || strpos($decoded, '|') === false) {
        http_response_code(400);
        echo 'Token tidak valid.';
        exit;
    }

    $parts = explode('|', $decoded);
    if (count($parts) < 3) {
        http_response_code(400);
        echo 'Token tidak valid.';
        exit;
    }

    $userId = (int) $parts[0];
    $signature = $parts[2];

    $q = "SELECT u.user_id, u.nisn, u.nama_lengkap, ks.status, skl.file_name, skl.file_path, skl.is_visible_to_user
          FROM user u
          LEFT JOIN kelulusan_status ks ON ks.user_id=u.user_id
          LEFT JOIN kelulusan_skl skl ON skl.user_id=u.user_id
          WHERE u.user_id='" . intval($userId) . "' AND u.nisn='" . $connection->real_escape_string($nisn) . "' LIMIT 1";
    $r = $connection->query($q);
    if (!$r || $r->num_rows === 0) {
        http_response_code(404);
        echo 'Data siswa tidak ditemukan.';
        exit;
    }

    $row = $r->fetch_assoc();
    $validSignature = md5($row['nisn'] . APP_ENC_KEY);
    if (!hash_equals($validSignature, $signature)) {
        http_response_code(403);
        echo 'Akses ditolak.';
        exit;
    }

    if ($row['status'] !== 'LULUS' || empty($row['file_path'])) {
        http_response_code(403);
        echo 'SKL belum tersedia untuk diunduh.';
        exit;
    }

    if (isset($row['is_visible_to_user']) && $row['is_visible_to_user'] === 'N') {
        http_response_code(403);
        echo 'SKL untuk user ini sedang disembunyikan oleh admin.';
        exit;
    }

    if (!kelulusan_is_user_berkas_valid($connection, (int) $row['user_id'])) {
        http_response_code(403);
        echo 'SKL disembunyikan karena berkas belum valid.';
        exit;
    }

    $relativePath = str_replace('..', '', $row['file_path']);
    $fullPath = realpath(__DIR__ . '/../../' . ltrim($relativePath, '/'));

    if (!$fullPath || !file_exists($fullPath)) {
        http_response_code(404);
        echo 'File SKL tidak ditemukan.';
        exit;
    }

    kelulusan_log_history($connection, $row, 'DOWNLOAD_SKL', array(
        'file' => $row['file_name']
    ));

    $downloadName = !empty($row['file_name']) ? $row['file_name'] : basename($fullPath);
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    readfile($fullPath);
    exit;
}

http_response_code(404);
echo 'Aksi tidak dikenal.';
