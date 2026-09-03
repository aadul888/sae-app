<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../../library/config.php');
require_once('../../../library/function.php');

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$admin_id = 0;
if (!empty($_COOKIE['ADMIN_KEY'])) $admin_id = intval(@epm_decode($_COOKIE['ADMIN_KEY']));

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {

    case 'buat_panggilan':
        $user_id = intval($_POST['user_id'] ?? 0);
        $total_poin = intval($_POST['total_poin'] ?? 0);
        $jenis = $_POST['jenis_panggilan'] ?? 'Pemanggilan Orang Tua';
        $alasan = trim($_POST['alasan'] ?? '');
        $tanggal = trim($_POST['tanggal_panggil'] ?? '');

        $valid_jenis = ['Pemanggilan Orang Tua','Surat Peringatan','Skorsing','Dikeluarkan'];
        if (!in_array($jenis, $valid_jenis)) $jenis = 'Pemanggilan Orang Tua';

        if ($user_id <= 0 || $alasan === '' || $tanggal === '') {
            echo json_encode(['status'=>'error','message'=>'Data wajib tidak lengkap']); exit;
        }

        // Get kelas_id from user
        $kelas_id = 0;
        $qk = $connection->query("SELECT kelas FROM user WHERE user_id=$user_id LIMIT 1");
        if ($qk && $qk->num_rows > 0) $kelas_id = intval($qk->fetch_assoc()['kelas']);

        $stmt = $connection->prepare("INSERT INTO poin_panggil (user_id, kelas_id, alasan, total_poin, jenis_panggilan, tanggal_panggil, admin_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("iisisis", $user_id, $kelas_id, $alasan, $total_poin, $jenis, $tanggal, $admin_id);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'Pemanggilan berhasil dijadwalkan']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal: '.$stmt->error]);
        }
        $stmt->close();
        break;

    case 'isi_hasil':
        $id = intval($_POST['panggil_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $tgl_hadir = trim($_POST['tanggal_hadir'] ?? '');
        $hasil = trim($_POST['hasil_pertemuan'] ?? '');
        $tindakan = trim($_POST['tindakan'] ?? '');

        if ($id <= 0 || !in_array($status, ['Hadir','Tidak Hadir'])) {
            echo json_encode(['status'=>'error','message'=>'Data tidak valid']); exit;
        }

        $stmt = $connection->prepare("UPDATE poin_panggil SET status=?, tanggal_hadir=?, hasil_pertemuan=?, tindakan=? WHERE panggil_id=? AND status='Menunggu'");
        $tgl_hadir_val = $tgl_hadir ?: null;
        $stmt->bind_param("ssssi", $status, $tgl_hadir_val, $hasil, $tindakan, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['status'=>'success','message'=>'Hasil pertemuan disimpan']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal atau sudah diproses']);
        }
        $stmt->close();
        break;

    case 'selesai_panggilan':
        $id = intval($_POST['panggil_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $stmt = $connection->prepare("UPDATE poin_panggil SET status='Selesai' WHERE panggil_id=? AND status='Hadir'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['status'=>'success','message'=>'Pemanggilan ditandai selesai']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action tidak valid']);
}
