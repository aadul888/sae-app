<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}
require_once '../../../library/config.php';
require_once('../../../library/function.php');

$modul_id = 42;
include __DIR__ . '/../check_role.php';
if (!$has_access || $data_role['modifikasi'] != 'Y') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'simpan':
        $id = (int)($_POST['id'] ?? 0);
        $judul = $_POST['judul'] ?? '';
        $konten = $_POST['konten'] ?? '';
        $kategori = $_POST['kategori'] ?? '';
        $aktif = isset($_POST['aktif']) && $_POST['aktif'] == '1' ? 1 : 0;
        $urutan = (int)($_POST['urutan'] ?? 0);
        $tgl_mulai = !empty($_POST['tgl_mulai']) ? $_POST['tgl_mulai'] : null;
        $tgl_selesai = !empty($_POST['tgl_selesai']) ? $_POST['tgl_selesai'] : null;

        if ($id > 0) {
            $stmt = $connection->prepare("UPDATE info SET judul=?, kategori=?, konten=?, aktif=?, urutan=?, tgl_mulai=?, tgl_selesai=? WHERE id=?");
            $stmt->bind_param('sssisssi', $judul, $kategori, $konten, $aktif, $urutan, $tgl_mulai, $tgl_selesai, $id);
        } else {
            $stmt = $connection->prepare("INSERT INTO info (judul, kategori, konten, aktif, urutan, tgl_mulai, tgl_selesai) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssisss', $judul, $kategori, $konten, $aktif, $urutan, $tgl_mulai, $tgl_selesai);
        }
        $q = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $q ? true : false, 'message' => $q ? 'Data tersimpan' : 'Gagal: ' . $connection->error]);
        break;

    case 'hapus':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $connection->prepare("DELETE FROM info WHERE id=?");
            $stmt->bind_param('i', $id);
            $q = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $q ? true : false, 'message' => $q ? 'Data dihapus' : 'Gagal: ' . $connection->error]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
        break;
}
