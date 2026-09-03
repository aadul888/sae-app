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

    case 'detail_sanggah':
        $id = intval($_POST['sanggah_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $q = $connection->query("SELECT ps.*, u.nama_lengkap, u.nisn, u.telp_ortu,
            pp.poin_diberikan, pp.tanggal_kejadian, pp.keterangan AS ket_pelanggaran, pp.status AS status_pelanggaran,
            pa.jenis_pelanggaran, pa.kategori, pa.kode_ayat, pps.kode_pasal, pps.nama_pasal,
            k.nama_kelas,
            COALESCE((SELECT SUM(poin_diberikan) FROM poin_pelanggaran WHERE user_id=ps.user_id AND status='Aktif'),0) AS total_poin
            FROM poin_sanggah ps
            JOIN user u ON ps.user_id=u.user_id
            JOIN poin_pelanggaran pp ON ps.pelanggaran_id=pp.pelanggaran_id
            LEFT JOIN kelas k ON pp.kelas_id=k.kelas_id
            JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
            JOIN poin_pasal pps ON pa.pasal_id=pps.pasal_id
            WHERE ps.sanggah_id=$id LIMIT 1");
        if ($q && $q->num_rows > 0) {
            echo json_encode(['status'=>'success','data'=>$q->fetch_assoc()]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Data tidak ditemukan']);
        }
        break;

    case 'proses_sanggah':
        $id = intval($_POST['sanggah_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $poin_kurang = intval($_POST['poin_dikurangi'] ?? 0);
        $kesepakatan = trim($_POST['kesepakatan'] ?? '');
        $catatan = trim($_POST['catatan_admin'] ?? '');

        if ($id <= 0 || !in_array($status, ['Disetujui','Ditolak'])) {
            echo json_encode(['status'=>'error','message'=>'Data tidak valid']); exit;
        }

        // Get sanggah info
        $qs = $connection->query("SELECT pelanggaran_id, user_id FROM poin_sanggah WHERE sanggah_id=$id LIMIT 1");
        if (!$qs || $qs->num_rows == 0) { echo json_encode(['status'=>'error','message'=>'Sanggahan tidak ditemukan']); exit; }
        $sanggah = $qs->fetch_assoc();

        $stmt = $connection->prepare("UPDATE poin_sanggah SET status=?, poin_dikurangi=?, kesepakatan=?, catatan_admin=?, admin_id=?, tanggal_proses=NOW() WHERE sanggah_id=?");
        $stmt->bind_param("sissii", $status, $poin_kurang, $kesepakatan, $catatan, $admin_id, $id);

        if ($stmt->execute()) {
            // If approved, update pelanggaran status and reduce poin
            if ($status === 'Disetujui' && $poin_kurang > 0) {
                $pel_id = intval($sanggah['pelanggaran_id']);
                // Get current poin
                $stmt_qp = $connection->prepare("SELECT poin_diberikan FROM poin_pelanggaran WHERE pelanggaran_id=? LIMIT 1");
                $stmt_qp->bind_param('i', $pel_id);
                $stmt_qp->execute();
                $qp = $stmt_qp->get_result();
                if ($qp) {
                    $cur_poin = intval($qp->fetch_assoc()['poin_diberikan']);
                    $new_poin = max(0, $cur_poin - $poin_kurang);
                    $stmt_up = $connection->prepare("UPDATE poin_pelanggaran SET poin_diberikan=?, status='Dikurangi' WHERE pelanggaran_id=?");
                    $stmt_up->bind_param('ii', $new_poin, $pel_id);
                    $stmt_up->execute();
                    $stmt_up->close();
                }
                $stmt_qp->close();
            } elseif ($status === 'Disetujui' && $poin_kurang == 0) {
                // Full removal
                $stmt_rs = $connection->prepare("UPDATE poin_pelanggaran SET status='Disanggah' WHERE pelanggaran_id=?");
                $stmt_rs->bind_param('i', $sanggah['pelanggaran_id']);
                $stmt_rs->execute();
                $stmt_rs->close();
            }
            echo json_encode(['status'=>'success','message'=>'Sanggahan berhasil diproses']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal memproses']);
        }
        $stmt->close();
        break;

    case 'selesai_sanggah':
        $id = intval($_POST['sanggah_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $stmt = $connection->prepare("UPDATE poin_sanggah SET status='Selesai', tanggal_selesai=NOW() WHERE sanggah_id=? AND status='Disetujui'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['status'=>'success','message'=>'Sanggahan ditandai selesai']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal atau sudah selesai']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action tidak valid']);
}
