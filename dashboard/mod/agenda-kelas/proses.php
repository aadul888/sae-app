<?php
/**
 * Proses Agenda Kelas - Backend
 * Actions: simpan_jadwal, simpan_agenda, request_edit_agenda
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

    case 'simpan_jadwal':
        $hari = trim($_POST['hari'] ?? '');
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        $valid_hari = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        if (!in_array($hari, $valid_hari)) {
            echo json_encode(['status' => 'error', 'message' => 'Hari tidak valid']);
            exit;
        }
        if (!is_array($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
            exit;
        }

        // Delete existing jadwal for this kelas+hari
        $esc_hari = $connection->real_escape_string($hari);
        $connection->query("DELETE FROM agenda_jadwal WHERE kelas_id='$my_kelas' AND hari='$esc_hari'");

        $saved = 0;
        $stmt = $connection->prepare("INSERT INTO agenda_jadwal (kelas_id, hari, jam_ke, mapel_id, created_by) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $jam_ke = intval($item['jam_ke'] ?? 0);
            $mapel_id = intval($item['mapel_id'] ?? 0);
            if ($jam_ke < 1 || $jam_ke > 11 || !$mapel_id) continue;
            $stmt->bind_param("isiii", $my_kelas, $hari, $jam_ke, $mapel_id, $my_user_id);
            if ($stmt->execute()) $saved++;
        }
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => "$saved jadwal disimpan untuk hari $hari"]);
        break;

    case 'simpan_agenda':
        $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $jam_list_json = $_POST['jam_list'] ?? '';
        $jam_list = json_decode($jam_list_json, true);
        $mapel_id = intval($_POST['mapel_id'] ?? 0);
        $guru_id = intval($_POST['guru_id'] ?? 0);
        $kehadiran = trim($_POST['kehadiran_guru'] ?? 'Hadir');
        $siswa_hadir = intval($_POST['jumlah_siswa_hadir'] ?? 0);
        $siswa_tidak = intval($_POST['jumlah_siswa_tidak_hadir'] ?? 0);
        $materi = trim($_POST['keterangan_materi'] ?? '');
        $siswa_absen_list = trim($_POST['siswa_tidak_hadir_list'] ?? '[]');

        if (!is_array($jam_list) || empty($jam_list)) {
            echo json_encode(['status' => 'error', 'message' => 'Data jam tidak valid']);
            exit;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !$mapel_id) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
            exit;
        }
        if (!in_array($kehadiran, ['Hadir', 'Tidak Hadir', 'Tidak Hadir + Tugas'])) {
            echo json_encode(['status' => 'error', 'message' => 'Status kehadiran tidak valid']);
            exit;
        }
        if (empty($materi)) {
            echo json_encode(['status' => 'error', 'message' => 'Keterangan materi wajib diisi']);
            exit;
        }

        // Validate jam values
        $valid_jams = [];
        foreach ($jam_list as $j) {
            $j = intval($j);
            if ($j >= 1 && $j <= 11) $valid_jams[] = $j;
        }
        if (empty($valid_jams)) {
            echo json_encode(['status' => 'error', 'message' => 'Jam tidak valid']);
            exit;
        }

        // Check if any jam already has agenda
        $esc_tgl = $connection->real_escape_string($tanggal);
        $jam_in = implode(',', $valid_jams);
        $q_ex = $connection->query("SELECT jam_ke FROM agenda_kelas WHERE kelas_id='$my_kelas' AND tanggal='$esc_tgl' AND jam_ke IN ($jam_in) AND status != 'dihapus'");
        if ($q_ex && $q_ex->num_rows > 0) {
            $filled = [];
            while ($r = $q_ex->fetch_assoc()) $filled[] = $r['jam_ke'];
            echo json_encode(['status' => 'error', 'message' => 'Agenda jam ke-' . implode(',', $filled) . ' sudah diisi']);
            exit;
        }

        // Handle photo upload with compression
        $foto_nama = null;
        if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_bukti'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($file['type'], $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung']);
                exit;
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['status' => 'error', 'message' => 'Ukuran file maksimal 5MB']);
                exit;
            }

            $upload_dir = '../../../content/agenda/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext = 'jpg';
            $foto_nama = 'agenda_' . $my_kelas . '_' . $tanggal . '_' . time() . '.' . $ext;
            $target = $upload_dir . $foto_nama;

            $source_img = null;
            switch ($file['type']) {
                case 'image/jpeg': $source_img = @imagecreatefromjpeg($file['tmp_name']); break;
                case 'image/png':  $source_img = @imagecreatefrompng($file['tmp_name']); break;
                case 'image/webp': $source_img = @imagecreatefromwebp($file['tmp_name']); break;
                case 'image/gif':  $source_img = @imagecreatefromgif($file['tmp_name']); break;
            }

            if ($source_img) {
                $orig_w = imagesx($source_img);
                $orig_h = imagesy($source_img);
                $max_w = 1200;
                if ($orig_w > $max_w) {
                    $new_w = $max_w;
                    $new_h = intval($orig_h * ($max_w / $orig_w));
                    $resized = imagecreatetruecolor($new_w, $new_h);
                    imagecopyresampled($resized, $source_img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
                    imagedestroy($source_img);
                    $source_img = $resized;
                }
                imagejpeg($source_img, $target, 70);
                imagedestroy($source_img);
                if (filesize($target) > 800 * 1024) {
                    $img2 = imagecreatefromjpeg($target);
                    imagejpeg($img2, $target, 50);
                    imagedestroy($img2);
                }
            } else {
                move_uploaded_file($file['tmp_name'], $target);
            }
        }

        // Insert one row per jam_ke
        $saved = 0;
        $foto_db = $foto_nama ?? '';
        $stmt = $connection->prepare("INSERT INTO agenda_kelas (kelas_id, tanggal, jam_ke, mapel_id, guru_id, kehadiran_guru, jumlah_siswa_hadir, jumlah_siswa_tidak_hadir, siswa_tidak_hadir_list, keterangan_materi, foto_bukti, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $connection->error]);
            exit;
        }
        foreach ($valid_jams as $jam_ke) {
            $stmt->bind_param("isiissiisssi", $my_kelas, $tanggal, $jam_ke, $mapel_id, $guru_id, $kehadiran, $siswa_hadir, $siswa_tidak, $siswa_absen_list, $materi, $foto_db, $my_user_id);
            if ($stmt->execute()) $saved++;
        }
        $stmt->close();

        if ($saved > 0) {
            echo json_encode(['status' => 'success', 'message' => "Agenda berhasil disimpan ($saved jam)"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan agenda']);
        }
        break;

    case 'request_edit_agenda':
        $agenda_id = intval($_POST['agenda_id'] ?? 0);
        $catatan = trim($_POST['catatan'] ?? '');

        if (!$agenda_id) {
            echo json_encode(['status' => 'error', 'message' => 'ID agenda tidak valid']);
            exit;
        }

        // Verify agenda belongs to this kelas
        $q_ag = $connection->query("SELECT agenda_id, kelas_id, tanggal FROM agenda_kelas WHERE agenda_id=$agenda_id AND kelas_id='$my_kelas' LIMIT 1");
        if (!$q_ag || $q_ag->num_rows == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Agenda tidak ditemukan']);
            exit;
        }
        $ag = $q_ag->fetch_assoc();

        // Check pending request
        $q_ex = $connection->query("SELECT id, status FROM agenda_edit_request WHERE agenda_id=$agenda_id ORDER BY id DESC LIMIT 1");
        if ($q_ex && $q_ex->num_rows > 0) {
            $ex = $q_ex->fetch_assoc();
            if ($ex['status'] === 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Sudah ada permintaan yang menunggu persetujuan']);
                exit;
            }
        }

        $esc_catatan = $connection->real_escape_string($catatan);
        $esc_tanggal = $connection->real_escape_string($ag['tanggal']);
        $connection->query("INSERT INTO agenda_edit_request (agenda_id, kelas_id, tanggal, catatan, requested_by) VALUES ($agenda_id, $my_kelas, '$esc_tanggal', '$esc_catatan', $my_user_id)");

        if ($connection->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Permintaan edit terkirim, menunggu persetujuan admin']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim permintaan']);
        }
        break;

    case 'update_agenda':
        $agenda_id = intval($_POST['agenda_id'] ?? 0);
        $kehadiran = trim($_POST['kehadiran_guru'] ?? '');
        $siswa_hadir = intval($_POST['jumlah_siswa_hadir'] ?? 0);
        $siswa_tidak = intval($_POST['jumlah_siswa_tidak_hadir'] ?? 0);
        $materi = trim($_POST['keterangan_materi'] ?? '');

        if (!$agenda_id) {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
            exit;
        }

        // Check approved edit request
        $q_ag = $connection->query("SELECT agenda_id FROM agenda_kelas WHERE agenda_id=$agenda_id AND kelas_id='$my_kelas' LIMIT 1");
        if (!$q_ag || $q_ag->num_rows == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Agenda tidak ditemukan']);
            exit;
        }

        $q_req = $connection->query("SELECT status FROM agenda_edit_request WHERE agenda_id=$agenda_id ORDER BY id DESC LIMIT 1");
        if (!$q_req || $q_req->num_rows == 0 || $q_req->fetch_assoc()['status'] !== 'approved') {
            echo json_encode(['status' => 'error', 'message' => 'Edit belum disetujui admin']);
            exit;
        }

        $stmt = $connection->prepare("UPDATE agenda_kelas SET kehadiran_guru=?, jumlah_siswa_hadir=?, jumlah_siswa_tidak_hadir=?, keterangan_materi=?, updated_at=NOW() WHERE agenda_id=?");
        $stmt->bind_param("siisi", $kehadiran, $siswa_hadir, $siswa_tidak, $materi, $agenda_id);
        echo $stmt->execute()
            ? json_encode(['status' => 'success', 'message' => 'Agenda berhasil diupdate'])
            : json_encode(['status' => 'error', 'message' => 'Gagal mengupdate']);
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
        break;
}
