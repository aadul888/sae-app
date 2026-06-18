<?php

/**
 * Proses Penyimpanan Data Buku Tamu
 * Menangani upload foto dan penyimpanan ke database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include config dan functions
require_once '../../library/config.php';
require_once '../../library/function.php';

try {
    // Pastikan request method adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Validasi input yang required
    $required_fields = ['nama', 'instansi', 'keperluan'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field wajib diisi");
        }
    }

    // Sanitize input data
    $nama = mysqli_real_escape_string($connection, trim($_POST['nama']));
    $instansi = mysqli_real_escape_string($connection, trim($_POST['instansi']));
    $telepon = mysqli_real_escape_string($connection, trim($_POST['telepon'] ?? ''));
    $keperluan = mysqli_real_escape_string($connection, trim($_POST['keperluan']));
    $keterangan = mysqli_real_escape_string($connection, trim($_POST['keterangan'] ?? ''));

    // Generate ID tamu unik
    $guest_id = 'GUEST-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Handle file upload untuk foto
    $foto_filename = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto_filename = handlePhotoUpload($_FILES['foto'], $guest_id);
    }

    // Cek apakah tabel buku_tamu ada, jika tidak buat tabel
    createGuestBookTable($connection);

    // Resolusi referensi: tujuan_id & instansi_id (instansi baru otomatis
    // ditambahkan ke master agar dapat dipilih ulang oleh tamu berikutnya).
    $instansi_id = resolveInstansiId($connection, $instansi);
    $tujuan_id = resolveTujuanId($connection, $keperluan);

    // Insert data ke database
    $insert_query = "INSERT INTO buku_tamu (
        guest_id, nama, instansi, instansi_id, telepon, keperluan, tujuan_id, keterangan,
        foto, tanggal_kunjungan, waktu_masuk, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aktif', NOW())";

    $stmt = $connection->prepare($insert_query);
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $connection->error);
    }

    $tanggal_kunjungan = date('Y-m-d');
    $waktu_masuk = date('H:i:s');

    $stmt->bind_param(
        'sssisissssss',
        $guest_id,
        $nama,
        $instansi,
        $instansi_id,
        $telepon,
        $keperluan,
        $tujuan_id,
        $keterangan,
        $foto_filename,
        $tanggal_kunjungan,
        $waktu_masuk
    );

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $insert_id = $connection->insert_id;
    $stmt->close();

    // Log aktivitas
    logGuestActivity($connection, $insert_id, 'CHECKIN', $guest_id);

    // URL checkout (untuk QR code yang dipindai tamu saat keluar)
    $base_path = rtrim(str_replace('proses.php', '', $_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $checkout_url = $scheme . '://' . $host . $base_path . '/checkout.php?id=' . rawurlencode($guest_id);

    // Response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Data tamu berhasil disimpan',
        'guest_id' => $guest_id,
        'checkout_url' => $checkout_url,
        'qr_url' => $scheme . '://' . $host . $base_path . '/qr.php?data=' . rawurlencode($checkout_url),
        'data' => [
            'id' => $insert_id,
            'nama' => $nama,
            'instansi' => $instansi,
            'keperluan' => $keperluan,
            'tanggal' => $tanggal_kunjungan,
            'waktu' => $waktu_masuk,
            'foto' => $foto_filename
        ]
    ]);
} catch (Exception $e) {
    // Log error untuk debugging
    error_log('Guest book error: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'PROCESSING_ERROR'
    ]);
}

/**
 * Handle upload foto selfie
 */
function handlePhotoUpload($file, $guest_id)
{
    $upload_dir = '../../content/tamu/';

    // Buat direktori jika belum ada
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Tidak dapat membuat direktori upload');
        }
    }

    // Validasi file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Format file tidak didukung. Gunakan JPG atau PNG');
    }

    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception('Ukuran file terlalu besar. Maksimal 5MB');
    }

    // Generate nama file unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $guest_id . '_' . date('YmdHis') . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Pindahkan file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Gagal mengupload foto');
    }

    // Resize foto untuk menghemat space
    resizeImage($filepath, 800, 600);

    return $filename;
}

/**
 * Resize gambar untuk menghemat storage
 */
function resizeImage($filepath, $max_width, $max_height)
{
    $image_info = getimagesize($filepath);
    if (!$image_info) return;

    $original_width = $image_info[0];
    $original_height = $image_info[1];
    $image_type = $image_info[2];

    // Hitung dimensi baru
    $ratio = min($max_width / $original_width, $max_height / $original_height);
    $new_width = intval($original_width * $ratio);
    $new_height = intval($original_height * $ratio);

    // Buat image resource
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        default:
            return; // Unsupported type
    }

    if (!$source) return;

    // Buat image baru
    $new_image = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency untuk PNG
    if ($image_type == IMAGETYPE_PNG) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }

    // Resize
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    // Save
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $filepath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $filepath);
            break;
    }

    // Cleanup
    imagedestroy($source);
    imagedestroy($new_image);
}

/**
 * Buat tabel buku_tamu jika belum ada
 */
function createGuestBookTable($connection)
{
    $check_table = "SHOW TABLES LIKE 'buku_tamu'";
    $result = $connection->query($check_table);

    if ($result->num_rows == 0) {
        $create_table = "
            CREATE TABLE `buku_tamu` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `guest_id` varchar(50) NOT NULL UNIQUE,
                `nama` varchar(100) NOT NULL,
                `instansi` varchar(100) NOT NULL,
                `telepon` varchar(20) DEFAULT NULL,
                `keperluan` varchar(50) NOT NULL,
                `keterangan` text DEFAULT NULL,
                `foto` varchar(255) DEFAULT NULL,
                `tanggal_kunjungan` date NOT NULL,
                `waktu_masuk` time NOT NULL,
                `waktu_keluar` time DEFAULT NULL,
                `status` enum('Aktif','Selesai','Batal') DEFAULT 'Aktif',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_guest_id` (`guest_id`),
                KEY `idx_tanggal` (`tanggal_kunjungan`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        if (!$connection->query($create_table)) {
            throw new Exception('Gagal membuat tabel buku_tamu: ' . $connection->error);
        }
    } else {
        // Tabel sudah ada (mungkin versi lama). Tambah kolom referensi/survey bila belum ada.
        $needed = [
            'instansi_id' => "ADD COLUMN `instansi_id` int(11) DEFAULT NULL",
            'tujuan_id'   => "ADD COLUMN `tujuan_id` int(11) DEFAULT NULL",
            'survey_done' => "ADD COLUMN `survey_done` tinyint(1) NOT NULL DEFAULT 0",
        ];
        foreach ($needed as $col => $ddl) {
            $chk = $connection->query("SHOW COLUMNS FROM buku_tamu LIKE '$col'");
            if ($chk && $chk->num_rows == 0) {
                @$connection->query("ALTER TABLE buku_tamu $ddl");
            }
        }
    }
}

/**
 * Log aktivitas tamu
 */
function logGuestActivity($connection, $guest_table_id, $activity, $guest_id)
{
    // Buat tabel log jika belum ada
    $check_log_table = "SHOW TABLES LIKE 'buku_tamu_log'";
    $result = $connection->query($check_log_table);

    if ($result->num_rows == 0) {
        $create_log_table = "
            CREATE TABLE `buku_tamu_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `guest_table_id` int(11) NOT NULL,
                `guest_id` varchar(50) NOT NULL,
                `activity` enum('CHECKIN','CHECKOUT','UPDATE') NOT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_guest_id` (`guest_id`),
                KEY `idx_activity` (`activity`),
                FOREIGN KEY (`guest_table_id`) REFERENCES `buku_tamu`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $connection->query($create_log_table);
    }

    // Insert log
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $log_query = "INSERT INTO buku_tamu_log (guest_table_id, guest_id, activity, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($log_query);

    if ($stmt) {
        $stmt->bind_param('issss', $guest_table_id, $guest_id, $activity, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Cari id tujuan dari master tamu_tujuan (berdasarkan nama). Return int|null.
 */
function resolveTujuanId($connection, $nama)
{
    if (!ensureRefTable($connection, 'tamu_tujuan')) return null;
    $nama_esc = $connection->real_escape_string($nama);
    $r = $connection->query("SELECT id FROM tamu_tujuan WHERE nama='$nama_esc' LIMIT 1");
    if ($r && $r->num_rows) return intval($r->fetch_row()[0]);
    return null;
}

/**
 * Cari id instansi; jika belum ada, tambahkan ke master agar reusable. Return int|null.
 */
function resolveInstansiId($connection, $nama)
{
    $nama = trim((string)$nama);
    if ($nama === '' || !ensureRefTable($connection, 'tamu_instansi')) return null;
    $nama_esc = $connection->real_escape_string($nama);
    $r = $connection->query("SELECT id FROM tamu_instansi WHERE nama='$nama_esc' LIMIT 1");
    if ($r && $r->num_rows) return intval($r->fetch_row()[0]);
    // Tambah instansi baru hasil input tamu
    if ($connection->query("INSERT INTO tamu_instansi (nama) VALUES ('$nama_esc')")) {
        return intval($connection->insert_id);
    }
    return null;
}

/**
 * Pastikan tabel referensi minimal ada (untuk instalasi yang belum menjalankan
 * database/hubin_schema.sql). Return bool exists/created.
 */
function ensureRefTable($connection, $table)
{
    $check = $connection->query("SHOW TABLES LIKE '" . $connection->real_escape_string($table) . "'");
    if ($check && $check->num_rows) return true;
    if ($table === 'tamu_instansi') {
        return (bool)$connection->query("CREATE TABLE IF NOT EXISTS `tamu_instansi` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nama` varchar(150) NOT NULL,
            `jenis` varchar(40) DEFAULT NULL,
            `alamat` varchar(255) DEFAULT NULL,
            `telepon` varchar(30) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `active` varchar(2) NOT NULL DEFAULT 'Y',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), UNIQUE KEY `uniq_instansi_nama` (`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if ($table === 'tamu_tujuan') {
        return (bool)$connection->query("CREATE TABLE IF NOT EXISTS `tamu_tujuan` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nama` varchar(100) NOT NULL,
            `keterangan` varchar(255) DEFAULT NULL,
            `active` varchar(2) NOT NULL DEFAULT 'Y',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), UNIQUE KEY `uniq_tujuan_nama` (`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    return false;
}
