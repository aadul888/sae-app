<?php
/**
 * Proses / API endpoint untuk modul Buku Tamu.
 * Dipanggil dari tamu.php?page=proses (POST/GET).
 */

header('Content-Type: application/json; charset=utf-8');

// ── Helpers ──────────────────────────────────────────────────────────
function jout($data) { echo json_encode($data); exit; }
function jerr($msg) { jout(['status'=>'error','message'=>$msg]); }

$action = trim($_GET['action'] ?? $_POST['action'] ?? '');
if (!$action) jerr('Action tidak valid');

switch ($action) {

    /* ─────── SIMPAN TAMU BARU (form) ─────── */
    case 'simpan_tamu':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jerr('Method not allowed');
        $nama      = trim($_POST['nama'] ?? '');
        $instansi  = trim($_POST['instansi'] ?? '');
        $telepon   = trim($_POST['telepon'] ?? '');
        $keperluan = trim($_POST['keperluan'] ?? '');
        $keterangan= trim($_POST['keterangan'] ?? '');
        if (!$nama || !$instansi || !$keperluan) jerr('Field nama, instansi, dan keperluan wajib diisi');

        $guest_id = 'GUEST-' . date('Ymd') . '-' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);

        // Upload foto
        $foto = null;
        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) jerr('Format foto: JPG/PNG');
            if ($_FILES['foto']['size'] > 5*1024*1024) jerr('Maksimal 5MB');
            $dir = __DIR__ . '/../../content/tamu/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $foto = $guest_id . '_' . date('YmdHis') . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $foto);
        }

        // Auto-create referensi
        $instansi_id = null; $tujuan_id = null;
        if ($connection) {
            $ie = $connection->real_escape_string($instansi);
            $chk = $connection->query("SELECT id FROM tamu_instansi WHERE nama='$ie' LIMIT 1");
            if ($chk && $chk->num_rows) $instansi_id = intval($chk->fetch_row()[0]);
            else { $connection->query("INSERT IGNORE INTO tamu_instansi (nama) VALUES ('$ie')"); $instansi_id = $connection->insert_id ?: null; }

            $ke = $connection->real_escape_string($keperluan);
            $chk2 = $connection->query("SELECT id FROM tamu_tujuan WHERE nama='$ke' LIMIT 1");
            if ($chk2 && $chk2->num_rows) $tujuan_id = intval($chk2->fetch_row()[0]);
        }

        $stmt = $connection->prepare("INSERT INTO buku_tamu (guest_id,nama,instansi,instansi_id,telepon,keperluan,tujuan_id,keterangan,foto,tanggal_kunjungan,waktu_masuk,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),'Aktif',NOW())");
        if (!$stmt) jerr('DB error: '.$connection->error);
        $stmt->bind_param('sssisisiss', $guest_id, $nama, $instansi, $instansi_id, $telepon, $keperluan, $tujuan_id, $keterangan, $foto);
        if (!$stmt->execute()) jerr('Gagal menyimpan: '.$stmt->error);
        $insert_id = $connection->insert_id;
        $stmt->close();

        // Log
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($l = $connection->prepare("INSERT INTO buku_tamu_log (guest_table_id,guest_id,activity,ip_address,user_agent) VALUES (?,?,'CHECKIN',?,?)")) {
            $l->bind_param('isss', $insert_id, $guest_id, $ip, $ua); $l->execute(); $l->close();
        }

        $base_url = rtrim(($base_url ?? './'), '/');
        jout([
            'status'  => 'success',
            'message' => 'Data tamu berhasil disimpan',
            'guest_id'=> $guest_id,
            'checkout_url' => $base_url . '/tamu/checkout?id=' . rawurlencode($guest_id),
            'qr_url'  => $base_url . '/tamu/qr?data=' . rawurlencode($base_url . '/tamu/checkout?id=' . rawurlencode($guest_id)),
        ]);
        break;

    /* ─────── CHECKOUT ─────── */
    case 'checkout':
        $gid = trim($_POST['guest_id'] ?? '');
        if (!$gid) jerr('Guest ID kosong');
        $row = $connection->query("SELECT id, nama, status FROM buku_tamu WHERE guest_id='".$connection->real_escape_string($gid)."' LIMIT 1")->fetch_assoc();
        if (!$row) jerr('Data tamu tidak ditemukan');
        if ($row['status'] !== 'Aktif') jout(['status'=>'done','message'=>'Sudah check-out sebelumnya.','guest_id'=>$gid,'nama'=>$row['nama']]);
        $connection->query("UPDATE buku_tamu SET status='Selesai', waktu_keluar=CURTIME() WHERE id=".intval($row['id']));
        $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($l = $connection->prepare("INSERT INTO buku_tamu_log (guest_table_id,guest_id,activity,ip_address,user_agent) VALUES (?,?,'CHECKOUT',?,?)")) {
            $l->bind_param('isss', $row['id'], $gid, $ip, $ua); $l->execute(); $l->close();
        }
        jout(['status'=>'success','message'=>'Check-out berhasil. Terima kasih.','guest_id'=>$gid,'nama'=>$row['nama']]);
        break;

    /* ─────── SIMPAN SURVEY ─────── */
    case 'submit_survey':
        $gid = trim($_POST['guest_id'] ?? '');
        $rating    = max(0, min(5, intval($_POST['rating'] ?? 0)));
        $pelayanan = max(0, min(5, intval($_POST['pelayanan'] ?? 0)));
        $kecepatan = max(0, min(5, intval($_POST['kecepatan'] ?? 0)));
        $kenyamanan= max(0, min(5, intval($_POST['kenyamanan'] ?? 0)));
        $komentar  = trim($_POST['komentar'] ?? '');
        if (!$gid) jerr('Guest ID kosong');
        if ($rating < 1) jerr('Rating minimal 1 bintang');
        $g = $connection->query("SELECT id FROM buku_tamu WHERE guest_id='".$connection->real_escape_string($gid)."' LIMIT 1")->fetch_assoc();
        if (!$g) jerr('Data tamu tidak ditemukan');
        $gtid = intval($g['id']);
        $stmt = $connection->prepare("INSERT INTO buku_tamu_survey (guest_table_id,guest_id,rating,pelayanan,kecepatan,kenyamanan,komentar) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), pelayanan=VALUES(pelayanan), kecepatan=VALUES(kecepatan), kenyamanan=VALUES(kenyamanan), komentar=VALUES(komentar)");
        $stmt->bind_param('isiiiis', $gtid, $gid, $rating, $pelayanan, $kecepatan, $kenyamanan, $komentar);
        $stmt->execute(); $stmt->close();
        $connection->query("UPDATE buku_tamu SET survey_done=1 WHERE id=$gtid");
        $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($l = $connection->prepare("INSERT INTO buku_tamu_log (guest_table_id,guest_id,activity,ip_address,user_agent) VALUES (?,?,'SURVEY',?,?)")) {
            $l->bind_param('isss', $gtid, $gid, $ip, $ua); $l->execute(); $l->close();
        }
        jout(['status'=>'success','message'=>'Terima kasih! Survey diterima.']);
        break;

    /* ─────── GET GUEST DETAIL (dashboard) ─────── */
    case 'get_guest':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) jerr('ID tidak valid');
        $g = $connection->query("SELECT * FROM buku_tamu WHERE id=$id")->fetch_assoc();
        if (!$g) jerr('Data tidak ditemukan');
        $survey = $connection->query("SELECT * FROM buku_tamu_survey WHERE guest_table_id=$id LIMIT 1")->fetch_assoc();
        jout(['status'=>'success','data'=>$g,'survey'=>$survey]);
        break;

    default:
        jerr('Action tidak dikenal: ' . $action);
}
