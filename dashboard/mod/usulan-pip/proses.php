<?php
// Minimal handler untuk Usulan PIP
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

function redirect_with_msg($url, $msg) {
    // Tambah parameter msg (URL sudah relatif terhadap lokasi file)
    header("Location: {$url}" . (strpos($url, '?') === false ? '?' : '&') . "msg=" . urlencode($msg));
    exit;
}

try {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo 'Error sistem: ' . $e->getMessage();
    exit;
}

// Pastikan cookie session ada
if (!isset($_COOKIE['siswa'])) {
    redirect_with_msg('../../?mod=usulan-pip', 'Session tidak valid. Silakan login ulang.');
}

// Dekripsi cookie (gunakan fungsi project)
$siswa = convert("decrypt", $_COOKIE['siswa']);
$uid_lookup = (int)$siswa;
if (empty($uid_lookup)) {
    redirect_with_msg('../../?mod=usulan-pip', 'Session tidak valid.');
}

// Ambil data user (harus aktif dan memiliki avatar valid)
$user_stmt = $connection->prepare("SELECT user_id, nisn, nama_lengkap, kelas, tempat_lahir, tanggal_lahir, nik_ayah, nama_ayah, pekerjaan_ayah, nik_ibu, nama_ibu, pekerjaan_ibu, avatar FROM user WHERE user_id = ? AND status = 'Aktif' LIMIT 1");
$user_stmt->bind_param("i", $uid_lookup);
$user_stmt->execute();
$res_user = $user_stmt->get_result();
if (!$res_user || $res_user->num_rows === 0) {
    $user_stmt->close();
    redirect_with_msg('../../?mod=usulan-pip', 'User tidak ditemukan atau tidak aktif.');
}
$data_user = $res_user->fetch_assoc();
$user_stmt->close();

// Validasi avatar - siswa harus memiliki foto profil yang valid
$avatar_check = $data_user['avatar'] ?? '';
if (empty($avatar_check) || $avatar_check === 'avatar.jpg') {
    redirect_with_msg('../../?mod=usulan-pip', 'Anda harus melengkapi foto profil terlebih dahulu sebelum dapat mengajukan usulan PIP. Silakan hubungi admin untuk mengunggah foto.');
}

// Ambil data berkas dan pastikan validasi_berkas = 'valid'
$berkas_stmt = $connection->prepare("SELECT kk, akte, ijazah, kip, kks, kis, validasi_berkas FROM berkas WHERE user_id = ? LIMIT 1");
$berkas_stmt->bind_param("i", $uid_lookup);
$berkas_stmt->execute();
$res_berkas = $berkas_stmt->get_result();
$berkas_row = $res_berkas && $res_berkas->num_rows ? $res_berkas->fetch_assoc() : null;
$berkas_stmt->close();

if (empty($berkas_row) || strtolower($berkas_row['validasi_berkas'] ?? '') !== 'valid') {
    redirect_with_msg('../../?mod=usulan-pip', 'Berkas belum tervalidasi. Tidak dapat mengajukan usulan PIP.');
}

// AJAX handler: cek apakah user sudah mengajukan pada semester berjalan
// Letakkan sebelum pemeriksaan action utama sehingga bisa dipanggil terpisah dari form submit.
$ajax_action = $_GET['action'] ?? '';
if ($ajax_action === 'check_semester') {
    header('Content-Type: application/json; charset=utf-8');
    // Hanya cek usulan Pending/Disetujui, tidak termasuk Ditolak
    $semester_sql = "SELECT COUNT(*) AS cnt FROM usulan_pip WHERE user_id = ? AND status IN ('Pending','Disetujui')";
    $stmt_semester = $connection->prepare($semester_sql);
    $stmt_semester->bind_param("i", $uid_lookup);
    $stmt_semester->execute();
    $res_semester = $stmt_semester->get_result();
    $row_semester = $res_semester ? $res_semester->fetch_assoc() : null;
    $stmt_semester->close();
    $cnt = (int)($row_semester['cnt'] ?? 0);
    if ($cnt > 0) {
        // Ambil data usulan terbaru untuk menentukan status spesifik
        $data_stmt = $connection->prepare("SELECT usulan_pip_id AS usulan_id, user_id, nisn, nama_lengkap, kelas, penghasilan_ayah, penghasilan_ibu, pertanyaan_1, kks_file, pertanyaan_2, kip_file, status, keterangan, tanggal_pengajuan FROM usulan_pip WHERE user_id = ? AND status IN ('Pending','Disetujui') ORDER BY tanggal_pengajuan DESC, usulan_pip_id DESC LIMIT 1");
        if ($data_stmt) {
            $data_stmt->bind_param("i", $uid_lookup);
            $data_stmt->execute();
            $res_data = $data_stmt->get_result();
            $usulan_row = $res_data && $res_data->num_rows ? $res_data->fetch_assoc() : null;
            $data_stmt->close();
        } else {
            $usulan_row = null;
        }

        $current_status = $usulan_row['status'] ?? '';
        if (strtolower($current_status) === 'disetujui') {
            echo json_encode([
                'allowed' => false,
                'status' => 'approved',
                'message' => 'Usulan PIP Anda telah disetujui dan sedang dalam proses.',
                'usulan' => $usulan_row
            ]);
        } else {
            echo json_encode([
                'allowed' => false,
                'status' => 'pending', 
                'message' => 'Usulan sudah terkirim sebelumnya dan sedang dalam proses Verval dan Input.',
                'usulan' => $usulan_row
            ]);
        }
    } else {
        // Cek apakah ada usulan yang ditolak untuk memberikan informasi
        $rejected_stmt = $connection->prepare("SELECT usulan_pip_id, penghasilan_ayah, penghasilan_ibu, pertanyaan_1, no_kks, pertanyaan_2, no_kip, alasan_usulan, keterangan, tanggal_pengajuan FROM usulan_pip WHERE user_id = ? AND status = 'Ditolak' ORDER BY tanggal_pengajuan DESC LIMIT 1");
        $rejected_data = null;
        if ($rejected_stmt) {
            $rejected_stmt->bind_param("i", $uid_lookup);
            $rejected_stmt->execute();
            $res_rejected = $rejected_stmt->get_result();
            if ($res_rejected && $res_rejected->num_rows > 0) {
                $rejected_data = $res_rejected->fetch_assoc();
            }
            $rejected_stmt->close();
        }
        
        echo json_encode([
            'allowed' => true,
            'rejected_usulan' => $rejected_data
        ]);
    }
    exit;
}

// Hanya terima action add_usulan untuk endpoint ini (selain AJAX check_semester di atas)
$action = $_GET['action'] ?? '';
if ($action !== 'add_usulan') {
    redirect_with_msg('../../?mod=usulan-pip', 'Action tidak valid.');
}

// Pastikan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_msg('../../?mod=usulan-pip', 'Metode request tidak diperbolehkan.');
}

    // NOTE: we'll store the label text directly (e.g. 'Tidak Berpenghasilan')
    // Ensure the database column is VARCHAR; see SQL below to alter table.

// Ambil input dari form

$penghasilan_ayah_raw = trim($_POST['penghasilan_ayah'] ?? '');
$penghasilan_ibu_raw = trim($_POST['penghasilan_ibu'] ?? '');
$penerima_kps = trim($_POST['penerima_kps'] ?? 'N'); // pertanyaan_1
$nomor_kks = trim($_POST['nomor_kks'] ?? '');
$punya_kip = trim($_POST['punya_kip'] ?? 'N'); // pertanyaan_2
$nomor_kip = trim($_POST['nomor_kip'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

$tempat_tinggal = trim($_POST['tempat_tinggal'] ?? '');
$nama_wali = trim($_POST['nama_wali'] ?? '');
$pekerjaan_wali = trim($_POST['pekerjaan_wali'] ?? '');
$alasan_usulan = trim($_POST['alasan_usulan'] ?? '');

// Pastikan field nomor KKS dan KIP ikut disimpan

// Ambil data siswa dari DB (trusted)
$nisn = $data_user['nisn'] ?? '';
$nama_lengkap = $data_user['nama_lengkap'] ?? '';
$kelas = $data_user['kelas'] ?? '';
$tempat_lahir = $data_user['tempat_lahir'] ?? '';
$tanggal_lahir = $data_user['tanggal_lahir'] ?? null;
$nik_ayah = $data_user['nik_ayah'] ?? '';
$nama_ayah = $data_user['nama_ayah'] ?? '';
$pekerjaan_ayah = $data_user['pekerjaan_ayah'] ?? '';
$nik_ibu = $data_user['nik_ibu'] ?? '';
$nama_ibu = $data_user['nama_ibu'] ?? '';
$pekerjaan_ibu = $data_user['pekerjaan_ibu'] ?? '';

// Ambil nama file KKS/KIP dari berkas yang sudah ada (tidak menerima upload baru)
$kks_file = $berkas_row['kks'] ?? '';
$kip_file = $berkas_row['kip'] ?? '';

// Validasi server-side
$errors = [];
$allowed_penghasilan = [
    "", "Tidak Berpenghasilan",
    "Kurang dari Rp. 500,000",
    "Rp. 500,000 - Rp. 999,999",
    "Rp. 1,000,000 - Rp. 1,999,999",
    "Rp. 2,000,000 - Rp. 4,999,999",
    "Rp. 5,000,000 - Rp. 20,000,000",
    "Lebih dari Rp. 20,000,000"
];
if (!in_array($penghasilan_ayah_raw, $allowed_penghasilan, true)) {
    $errors[] = 'Pilihan penghasilan ayah tidak valid.';
}
if (!in_array($penghasilan_ibu_raw, $allowed_penghasilan, true)) {
    $errors[] = 'Pilihan penghasilan ibu tidak valid.';
}
if (!in_array($penerima_kps, ['Y','N'], true)) {
    $errors[] = 'Pilihan penerima KPS tidak valid.';
}
if (!in_array($punya_kip, ['Y','N'], true)) {
    $errors[] = 'Pilihan punya KIP tidak valid.';
}
if ($penerima_kps === 'Y' && $nomor_kks === '') {
    $errors[] = 'Nomor KKS wajib diisi jika memilih penerima KPS.';
}
if ($penerima_kps === 'Y' && $kks_file === '') {
    $errors[] = 'Berkas KKS tidak ditemukan di sistem. Hubungi admin.';
}
if ($punya_kip === 'Y' && $nomor_kip === '') {
    $errors[] = 'Nomor KIP wajib diisi jika memilih punya KIP.';
}
if ($punya_kip === 'Y' && $kip_file === '') {
    $errors[] = 'Berkas KIP tidak ditemukan di sistem. Hubungi admin.';
}
if (mb_strlen($keterangan) > 1000) {
    $errors[] = 'Keterangan terlalu panjang.';
}
if ($alasan_usulan === '') {
    $errors[] = 'Alasan usulan wajib diisi.';
}
if (mb_strlen($alasan_usulan) > 255) {
    $errors[] = 'Alasan usulan maksimal 255 karakter.';
}

// Konfigurasi panjang KKS/KIP (dapat disesuaikan)
$KKS_MIN = 6;
$KKS_MAX = 16;
$KIP_MIN = 6;
$KIP_MAX = 16;

// Normalisasi dan validasi format KKS/KIP
$nomor_kks_clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $nomor_kks));
$nomor_kip_up = strtoupper(preg_replace('/[^A-Z0-9]/', '', $nomor_kip));

if ($penerima_kps === 'Y') {
    if ($nomor_kks_clean === '') {
        $errors[] = 'Nomor KKS wajib diisi jika memilih penerima KPS.';
    } elseif (!preg_match('/^[A-Z0-9]{' . $KKS_MIN . ',' . $KKS_MAX . '}$/', $nomor_kks_clean)) {
        $errors[] = 'Nomor KKS harus berupa huruf/angka kapital dengan panjang ' . $KKS_MIN . ' hingga ' . $KKS_MAX . ' karakter.';
    }
}

if ($punya_kip === 'Y') {
    if ($nomor_kip_up === '') {
        $errors[] = 'Nomor KIP wajib diisi jika memilih punya KIP.';
    } elseif (!preg_match('/^[A-Z0-9]{' . $KIP_MIN . ',' . $KIP_MAX . '}$/', $nomor_kip_up)) {
        $errors[] = 'Nomor KIP harus berupa huruf/angka kapital dengan panjang ' . $KIP_MIN . ' hingga ' . $KIP_MAX . ' karakter.';
    }
}

// Gunakan nilai yang dinormalisasi untuk penyimpanan
$nomor_kks = $nomor_kks_clean;
$nomor_kip = $nomor_kip_up;

// Simpan label penghasilan sesuai input form
$penghasilan_ayah = $penghasilan_ayah_raw;
$penghasilan_ibu = $penghasilan_ibu_raw;

// Map jawaban Y/N ke 'Ya'/'Tidak' sesuai schema enum
$penerima_kps_db = $penerima_kps === 'Y' ? 'Ya' : 'Tidak';
$punya_kip_db = $punya_kip === 'Y' ? 'Ya' : 'Tidak';

if (!empty($errors)) {
    redirect_with_msg('../../?mod=usulan-pip', implode(' ', $errors));
}

// Cek apakah user sudah mengajukan dengan status Menunggu/Disetujui
// Jika ada, tolak submit dan redirect agar form tidak tetap terbuka.
// Usulan dengan status Ditolak diizinkan untuk mengajukan ulang
$chk_sql = "SELECT COUNT(*) AS cnt FROM usulan_pip WHERE user_id = ? AND status IN ('Pending','Disetujui')";
$chk_stmt = $connection->prepare($chk_sql);
$chk_stmt->bind_param("i", $uid_lookup);
$chk_stmt->execute();
$res_chk = $chk_stmt->get_result();
$row_chk = $res_chk ? $res_chk->fetch_assoc() : null;
$exist_count = (int)($row_chk['cnt'] ?? 0);
$chk_stmt->close();

if ($exist_count > 0) {
    redirect_with_msg('../../?mod=usulan-pip', 'Anda masih memiliki pengajuan yang sedang diproses (Menunggu/Disetujui). Tidak dapat mengajukan usulan baru.');
}

// Simpan ke tabel usulan_pip



$insert_sql = "INSERT INTO usulan_pip
    (user_id, nisn, nama_lengkap, kelas, tempat_lahir, tanggal_lahir, nik_ayah, nama_ayah, pekerjaan_ayah, penghasilan_ayah, nik_ibu, nama_ibu, pekerjaan_ibu, penghasilan_ibu, tempat_tinggal, nama_wali, pekerjaan_wali, alasan_usulan, pertanyaan_1, kks_file, no_kks, pertanyaan_2, kip_file, no_kip, status, keterangan, tanggal_pengajuan, tanggal_update, created_by, updated_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)";

$stmt = $connection->prepare($insert_sql);
if (!$stmt) {
    redirect_with_msg('../../?mod=usulan-pip', 'Gagal menyiapkan query: ' . $connection->error);
}

$types = "i" . str_repeat("s", 25) . "ii"; // 25 string params, 2 int
$created_by = $uid_lookup;
$updated_by = null;
$status_db = 'Pending';

$stmt->bind_param(
    $types,
    $uid_lookup,         // i
    $nisn,               // s
    $nama_lengkap,       // s
    $kelas,              // s
    $tempat_lahir,       // s
    $tanggal_lahir,      // s
    $nik_ayah,           // s
    $nama_ayah,          // s
    $pekerjaan_ayah,     // s
    $penghasilan_ayah,   // s
    $nik_ibu,            // s
    $nama_ibu,           // s
    $pekerjaan_ibu,      // s
    $penghasilan_ibu,    // s
    $tempat_tinggal,     // s
    $nama_wali,          // s
    $pekerjaan_wali,     // s
    $alasan_usulan,      // s
    $penerima_kps_db,    // s (pertanyaan_1)
    $kks_file,           // s
    $nomor_kks,          // s (no_kks)
    $punya_kip_db,       // s (pertanyaan_2)
    $kip_file,           // s
    $nomor_kip,          // s (no_kip)
    $status_db,          // s (status)
    $keterangan,         // s
    $created_by,         // i
    $updated_by          // i
);

if ($stmt->execute()) {
    $stmt->close();
    redirect_with_msg('../../?mod=usulan-pip', 'success');
} else {
    $err = 'Gagal menyimpan usulan: ' . $stmt->error;
    $stmt->close();
    redirect_with_msg('../../?mod=usulan-pip', $err);
}

// end of file - duplicate AJAX handler removed
?>
