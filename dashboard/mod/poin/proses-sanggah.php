<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/saev4/library/config.php';

if (!isset($_COOKIE['siswa'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/saev4/library/function.php';
$user_id = intval(convert('decrypt', $_COOKIE['siswa']));
$pelanggaran_id = isset($_POST['pelanggaran_id']) ? intval($_POST['pelanggaran_id']) : 0;
$jenis_sanggah = isset($_POST['jenis_sanggah']) ? $_POST['jenis_sanggah'] : '';
$alasan = isset($_POST['alasan']) ? trim($_POST['alasan']) : '';

// Validasi input
if ($pelanggaran_id <= 0 || empty($alasan)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}
if (!in_array($jenis_sanggah, ['Pengurangan', 'Penghapusan'])) {
    echo json_encode(['status' => 'error', 'message' => 'Jenis sanggahan tidak valid']);
    exit;
}

// Cek pelanggaran milik siswa dan aktif
$stmt = $connection->prepare("SELECT pelanggaran_id FROM poin_pelanggaran WHERE pelanggaran_id=? AND user_id=? AND status='Aktif'");
$stmt->bind_param("ii", $pelanggaran_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Pelanggaran tidak ditemukan atau sudah tidak aktif']);
    exit;
}
$stmt->close();

// Cek sanggahan duplikat (menunggu)
$stmt = $connection->prepare("SELECT sanggah_id FROM poin_sanggah WHERE pelanggaran_id=? AND user_id=? AND status='Menunggu'");
$stmt->bind_param("ii", $pelanggaran_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Kamu sudah memiliki sanggahan yang sedang menunggu untuk pelanggaran ini']);
    exit;
}
$stmt->close();

// Insert sanggahan
$stmt = $connection->prepare("INSERT INTO poin_sanggah (pelanggaran_id, user_id, jenis_sanggah, alasan, tanggal_pengajuan, status, created_at) VALUES (?, ?, ?, ?, CURDATE(), 'Menunggu', NOW())");
$stmt->bind_param("iiss", $pelanggaran_id, $user_id, $jenis_sanggah, $alasan);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Sanggahan berhasil dikirim. Menunggu keputusan admin.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $connection->error]);
}
$stmt->close();
$connection->close();
?>
