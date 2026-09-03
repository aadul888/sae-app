<?php
/**
 * Proses Absensi Manual - Backend for class coordinator manual attendance
 * Actions: absen_batch, validate_token
 */
date_default_timezone_set('Asia/Jakarta');
require_once '../../../library/config.php';
require_once '../../../library/function.php';

if (!isset($connection) || !$connection || $connection->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit;
}
header('Content-Type: application/json');

if (!isset($_COOKIE['siswa'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi tidak valid']);
    exit;
}
$siswa_key = convert("decrypt", $_COOKIE['siswa']);
$q_me = $connection->query("SELECT user_id, nama_lengkap, kelas, koordinator FROM user WHERE user_id='" . intval($siswa_key) . "' LIMIT 1");
if (!$q_me || $q_me->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
    exit;
}
$me = $q_me->fetch_assoc();
if ($me['koordinator'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Hanya koordinator kelas']);
    exit;
}

$my_kelas = intval($me['kelas']);
$my_user_id = intval($me['user_id']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'absen_batch':
        $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        if (!is_array($items) || empty($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Data kosong']);
            exit;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid']);
            exit;
        }
        if (strtotime($tanggal) > strtotime(date('Y-m-d'))) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak dapat absensi tanggal mendatang']);
            exit;
        }

        // Check holiday
        $esc_tgl = $connection->real_escape_string($tanggal);
        $q_libur = $connection->query("SELECT id FROM hari_libur WHERE '$esc_tgl' BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
        if ($q_libur && $q_libur->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak dapat absensi pada hari libur']);
            exit;
        }

        // Get schedule
        $hari_map = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $hari_target = $hari_map[date('l', strtotime($tanggal))];
        $q_jadwal = $connection->query("SELECT * FROM jadwal WHERE hari='$hari_target' AND status='Y' LIMIT 1");
        if (!$q_jadwal || $q_jadwal->num_rows == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada jadwal aktif untuk hari ' . $hari_target]);
            exit;
        }
        $jadwal = $q_jadwal->fetch_assoc();

        // Approval status
        $is_past = ($tanggal !== date('Y-m-d'));
        $approval_status = $is_past ? 'pending' : 'approved';

        $jam_sekarang = date('H:i:s');
        $saved = 0; $updated = 0; $skipped = 0;

        // Prepare statements
        $stmt_insert = $connection->prepare("INSERT INTO absensi
            (user_id, tanggal, jam_masuk, status_masuk, jam_pulang, status_pulang, kehadiran,
             created_at, metode, manual_by, manual_note, approval_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'manual', ?, 'Batch oleh koordinator', ?)");

        $stmt_update = $connection->prepare("UPDATE absensi
            SET jam_masuk=?, status_masuk=?, jam_pulang=?, status_pulang=?, kehadiran=?, updated_at=NOW(), manual_by=?
            WHERE id=?");

        foreach ($items as $item) {
            $uid = intval($item['user_id'] ?? 0);
            $st = trim($item['status'] ?? '');
            if (!$uid || !in_array($st, ['Hadir', 'Izin', 'Sakit', 'Alpha'])) { $skipped++; continue; }

            // Verify student in class
            $q_ck = $connection->query("SELECT user_id FROM user WHERE user_id='$uid' AND kelas='$my_kelas' AND status='Aktif' LIMIT 1");
            if (!$q_ck || $q_ck->num_rows == 0) { $skipped++; continue; }

            // Determine values
            $jam_masuk_val = null; $status_masuk = ''; $jam_pulang_val = null; $status_pulang = null;
            switch ($st) {
                case 'Hadir':
                    $jam_cek = ($tanggal == date('Y-m-d')) ? $jam_sekarang : $jadwal['waktu_mulai'];
                    $status_masuk = (strtotime($jam_cek) <= strtotime($jadwal['waktu_mulai'])) ? 'Tepat Waktu' : 'Terlambat';
                    $jam_masuk_val = $jam_cek;
                    break;
                case 'Izin':  $status_masuk = 'Izin'; $status_pulang = 'Izin'; break;
                case 'Sakit': $status_masuk = 'Sakit'; $status_pulang = 'Sakit'; break;
                case 'Alpha': $status_masuk = 'Alpha'; break;
            }
            $kehadiran = $st;

            // Check existing
            $q_ex = $connection->query("SELECT id, metode FROM absensi WHERE user_id='$uid' AND tanggal='$esc_tgl' LIMIT 1");
            if ($q_ex && $q_ex->num_rows > 0) {
                $existing = $q_ex->fetch_assoc();
                if ($existing['metode'] === 'rfid') { $skipped++; continue; }
                // UPDATE existing manual record
                $ex_id = intval($existing['id']);
                $stmt_update->bind_param("sssssii", $jam_masuk_val, $status_masuk, $jam_pulang_val, $status_pulang, $kehadiran, $my_user_id, $ex_id);
                if ($stmt_update->execute()) { $updated++; } else { $skipped++; }
            } else {
                // INSERT new record
                $stmt_insert->bind_param("issssssis", $uid, $tanggal, $jam_masuk_val, $status_masuk, $jam_pulang_val, $status_pulang, $kehadiran, $my_user_id, $approval_status);
                if ($stmt_insert->execute()) { $saved++; } else { $skipped++; }
            }
        }
        $stmt_insert->close();
        $stmt_update->close();

        $parts = [];
        if ($saved > 0) $parts[] = "$saved siswa baru disimpan";
        if ($updated > 0) $parts[] = "$updated siswa diperbarui";
        if ($skipped > 0) $parts[] = "$skipped dilewati";
        $msg = implode(', ', $parts);
        if (empty($msg)) $msg = "Tidak ada perubahan";
        if ($approval_status === 'pending') $msg .= ' (menunggu persetujuan admin)';

        echo json_encode(['status' => 'success', 'message' => $msg, 'saved' => $saved, 'updated' => $updated, 'skipped' => $skipped]);
        break;

    case 'absen_daring':
        $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid']);
            exit;
        }
        if (strtotime($tanggal) > strtotime(date('Y-m-d'))) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak dapat absensi tanggal mendatang']);
            exit;
        }

        // Check holiday
        $esc_tgl = $connection->real_escape_string($tanggal);
        $q_libur = $connection->query("SELECT id FROM hari_libur WHERE '$esc_tgl' BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
        if ($q_libur && $q_libur->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak dapat absensi pada hari libur']);
            exit;
        }

        // Get schedule
        $hari_map_d = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $hari_target_d = $hari_map_d[date('l', strtotime($tanggal))];
        $q_jadwal_d = $connection->query("SELECT * FROM jadwal WHERE hari='$hari_target_d' AND status='Y' LIMIT 1");
        if (!$q_jadwal_d || $q_jadwal_d->num_rows == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada jadwal aktif untuk hari ' . $hari_target_d]);
            exit;
        }
        $jadwal_d = $q_jadwal_d->fetch_assoc();
        $jam_masuk_d = $jadwal_d['waktu_mulai'];
        $jam_pulang_d = $jadwal_d['waktu_selesai'];

        // Get all active students in class
        $q_siswa_d = $connection->query("SELECT user_id FROM user WHERE kelas='$my_kelas' AND status='Aktif'");
        if (!$q_siswa_d || $q_siswa_d->num_rows == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada siswa aktif di kelas ini']);
            exit;
        }

        $is_past_d = ($tanggal !== date('Y-m-d'));
        $approval_d = $is_past_d ? 'pending' : 'approved';
        $keterangan_d = 'Pembelajaran Daring';
        $saved_d = 0; $updated_d = 0; $skipped_d = 0;

        $stmt_ins_d = $connection->prepare("INSERT INTO absensi
            (user_id, tanggal, jam_masuk, status_masuk, jam_pulang, status_pulang, kehadiran, keterangan,
             created_at, metode, manual_by, manual_note, approval_status)
            VALUES (?, ?, ?, 'Tepat Waktu', ?, 'Pulang', 'Hadir', ?, NOW(), 'manual', ?, 'Pembelajaran Daring', ?)");

        $stmt_upd_d = $connection->prepare("UPDATE absensi
            SET jam_masuk=?, status_masuk='Tepat Waktu', jam_pulang=?, status_pulang='Pulang',
                kehadiran='Hadir', keterangan=?, updated_at=NOW(), manual_by=?
            WHERE id=?");

        while ($row_d = $q_siswa_d->fetch_assoc()) {
            $uid_d = intval($row_d['user_id']);

            // Check existing
            $q_ex_d = $connection->query("SELECT id, metode FROM absensi WHERE user_id='$uid_d' AND tanggal='$esc_tgl' LIMIT 1");
            if ($q_ex_d && $q_ex_d->num_rows > 0) {
                $existing_d = $q_ex_d->fetch_assoc();
                if ($existing_d['metode'] === 'rfid') { $skipped_d++; continue; }
                $ex_id_d = intval($existing_d['id']);
                $stmt_upd_d->bind_param("sssii", $jam_masuk_d, $jam_pulang_d, $keterangan_d, $my_user_id, $ex_id_d);
                if ($stmt_upd_d->execute()) { $updated_d++; } else { $skipped_d++; }
            } else {
                $stmt_ins_d->bind_param("isssssis", $uid_d, $tanggal, $jam_masuk_d, $jam_pulang_d, $keterangan_d, $my_user_id, $approval_d);
                if ($stmt_ins_d->execute()) { $saved_d++; } else { $skipped_d++; }
            }
        }
        $stmt_ins_d->close();
        $stmt_upd_d->close();

        $parts_d = [];
        if ($saved_d > 0) $parts_d[] = "$saved_d siswa baru disimpan";
        if ($updated_d > 0) $parts_d[] = "$updated_d siswa diperbarui";
        if ($skipped_d > 0) $parts_d[] = "$skipped_d dilewati (RFID)";
        $msg_d = implode(', ', $parts_d);
        if (empty($msg_d)) $msg_d = "Tidak ada perubahan";
        $msg_d = "Pembelajaran Daring: " . $msg_d;
        if ($approval_d === 'pending') $msg_d .= ' (menunggu persetujuan admin)';

        echo json_encode(['status' => 'success', 'message' => $msg_d, 'saved' => $saved_d, 'updated' => $updated_d, 'skipped' => $skipped_d]);
        break;

    case 'request_edit':
        $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $catatan = trim($_POST['catatan'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid']);
            exit;
        }

        // Check if already has pending request
        $esc_tgl = $connection->real_escape_string($tanggal);
        $q_ex = $connection->query("SELECT id, status FROM absensi_edit_request WHERE kelas_id='$my_kelas' AND tanggal='$esc_tgl' ORDER BY id DESC LIMIT 1");
        if ($q_ex && $q_ex->num_rows > 0) {
            $ex = $q_ex->fetch_assoc();
            if ($ex['status'] === 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Sudah ada permintaan yang menunggu persetujuan']);
                exit;
            }
            if ($ex['status'] === 'approved') {
                echo json_encode(['status' => 'error', 'message' => 'Sudah disetujui, silakan edit langsung']);
                exit;
            }
        }

        $esc_catatan = $connection->real_escape_string($catatan);
        $sql = "INSERT INTO absensi_edit_request (kelas_id, tanggal, requested_by, catatan, status, created_at)
                VALUES ('$my_kelas', '$esc_tgl', '$my_user_id', '$esc_catatan', 'pending', NOW())";
        if ($connection->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Permintaan edit berhasil dikirim. Menunggu persetujuan admin.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim permintaan']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
        break;
}