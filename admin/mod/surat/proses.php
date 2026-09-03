<?php
/**
 * MODUL SURAT — AJAX handler
 * Proses CRUD surat masuk & keluar.
 *
 * TODO: Implementasi koneksi database dan query riil setelah struktur tabel ditentukan.
 */

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Aksi tidak dikenal'];

switch ($action) {
    case 'detail':
        $id = intval($_GET['id'] ?? 0);
        $jenis = $_GET['jenis'] ?? 'masuk';
        // TODO: query database
        $response = ['status' => 'success', 'data' => []];

        if ($id > 0) {
            $response['message'] = 'Data ditemukan (dummy)';
            $response['data'] = [
                'id' => $id,
                'jenis' => $jenis,
                'no_surat' => 'SM/' . str_pad($id, 3, '0', STR_PAD_LEFT) . '/VI/2026',
                'perihal' => 'Contoh Surat (dummy)',
            ];
        }
        break;

    default:
        $response['message'] = 'Aksi "' . htmlspecialchars($action) . '" belum tersedia';
        break;
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
