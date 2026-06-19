<?php
// Validasi koneksi database
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
}

// Validasi sesi siswa
if (!isset($_COOKIE['siswa'])) {
    echo '<div class="alert alert-danger">Sesi siswa tidak ditemukan.</div>';
    exit();
}

$user_id = $data_user['user_id'] ?? '';
if (empty($user_id)) {
    echo '<div class="alert alert-warning">Data siswa tidak ditemukan.</div>';
    exit();
}

// Ambil data user dengan prepared statement untuk keamanan
$stmt = $connection->prepare("SELECT u.*, j.nama_jurusan, j.kode_jurusan FROM user u LEFT JOIN jurusan j ON u.jurusan_id = j.jurusan_id WHERE u.user_id = ? LIMIT 1");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc() ?? [];


if (empty($user)) {
    echo '<div class="alert alert-warning">Data siswa tidak ditemukan.</div>';
    exit();
}


// Inisialisasi data siswa

$nama = htmlspecialchars($user['nama_lengkap'] ?? '-', ENT_QUOTES, 'UTF-8');
$nisn = htmlspecialchars($user['nisn'] ?? '-', ENT_QUOTES, 'UTF-8');
$jurusan = htmlspecialchars($user['nama_jurusan'] ?? '-', ENT_QUOTES, 'UTF-8');
$jurusan_id = $user['jurusan_id'] ?? '';

// Cegah akses jika avatar masih avatar.jpg (belum upload foto)
$avatar_file = $user['avatar'] ?? '';
if (empty($avatar_file) || $avatar_file === 'avatar.jpg') {
    echo '<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#6c7ae0;">
        <div style="background:#f75c7a;color:#fff;padding:32px 16px 32px 16px;border-radius:10px;max-width:600px;width:100%;text-align:center;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
            <div style="font-size:1.5rem;margin-bottom:8px;color:#7a263a;"><i class=\'fas fa-ban\' style=\'margin-right:6px;\'></i> Akses Ditolak</div>
            <div style="font-size:1.1rem;margin-bottom:24px;">Anda belum mengunggah Foto. Silakan hubungi admin untuk mengunggah foto Anda.</div>
            <a href="../dashboard/home" style="display:inline-block;padding:10px 28px;background:#6c7ae0;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;box-shadow:0 1px 4px rgba(0,0,0,0.07);transition:background 0.2s;"><i class=\'fas fa-home\' style=\'margin-right:6px;\'></i>Kembali ke Dashboard</a>
        </div>
    </div>';
    exit();
}

// Default paths
$paths = [
    'foto' => "../content/avatar/avatar.jpg",
    'logo_jurusan' => "../content/assets/logo-jurusan/default.png",
    'kartu_depan' => "../content/assets/kartu-pelajar/depan.jpg",
    'kartu_belakang' => "../content/assets/kartu-pelajar/belakang.jpg",
    'qrcode' => ''
];

// Prioritaskan nilai `avatar` dari DB (bisa berisi "file.ext?t=timestamp")
if (!empty($user['avatar'])) {
    $avatar_db = $user['avatar'];
    $avatar_file_only = preg_replace('/\?.*/', '', $avatar_db);
    $avatar_fs_path = "../content/avatar/" . $avatar_file_only;
    if (!empty($avatar_file_only) && file_exists($avatar_fs_path)) {
        // Gunakan string DB (termasuk query string) sebagai src sehingga cache busting dipakai
        $paths['foto'] = "../content/avatar/" . $avatar_db;
    }
}

// Jika belum ada foto dari DB, coba berdasarkan NISN (cari .png lalu .jpg), tambahkan filemtime sebagai cache-buster
if ($paths['foto'] === "../content/avatar/avatar.jpg") {
    if (!empty($user['nisn'])) {
        $foto_png = "../content/avatar/" . $user['nisn'] . '.png';
        $foto_jpg = "../content/avatar/" . $user['nisn'] . '.jpg';
        if (file_exists($foto_png)) {
            $paths['foto'] = $foto_png . '?t=' . filemtime($foto_png);
        } elseif (file_exists($foto_jpg)) {
            $paths['foto'] = $foto_jpg . '?t=' . filemtime($foto_jpg);
        }
    }
}

// Logo jurusan: gunakan file sesuai jurusan jika ada, tambahkan filemtime untuk cache-busting
$logo_jurusan_file = (!empty($jurusan_id) ? $jurusan_id : 'default') . '.png';
$logo_jurusan_path = "../content/assets/logo-jurusan/" . $logo_jurusan_file;
if (file_exists($logo_jurusan_path)) {
    $paths['logo_jurusan'] = $logo_jurusan_path . '?t=' . filemtime($logo_jurusan_path);
}

// QR code: jika ada file QR di server, gunakan dan tambahkan filemtime
$qrcode_file = "../content/qrcode/{$nisn}.jpg";
if (file_exists($qrcode_file)) {
    $paths['qrcode'] = $qrcode_file . '?t=' . filemtime($qrcode_file);
}

$kartu_front_inline = "../admin/mod/user/download_kartu.php?user_id=" . rawurlencode($user_id) . "&side=depan&inline=1&t=" . time();
$kartu_back_inline = "../admin/mod/user/download_kartu.php?user_id=" . rawurlencode($user_id) . "&side=belakang&inline=1&t=" . time();
$kartu_front_download = "../admin/mod/user/download_kartu.php?user_id=" . rawurlencode($user_id) . "&side=depan&t=" . time();
$kartu_back_download = "../admin/mod/user/download_kartu.php?user_id=" . rawurlencode($user_id) . "&side=belakang&t=" . time();

?>

<div class="container-fluid py-3 px-1 px-sm-2 px-md-4 ekpd-page-container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 d-flex flex-column align-items-center">
            <div class="kartu-pelajar-portrait-wrapper kartu-wrapper-limit w-100">

                <!-- Kartu Depan -->
                <div class="kartu-pelajar-portrait position-relative mx-auto">
                    <img src="<?= $kartu_front_inline ?>" alt="Kartu Pelajar Depan" class="kartu-bg" loading="lazy">
                </div>

                <!-- Kartu Belakang -->
                <div class="kartu-pelajar-portrait position-relative mx-auto">
                    <img src="<?= $kartu_back_inline ?>" alt="Kartu Pelajar Belakang" class="kartu-bg" loading="lazy">
                </div>

                <!-- Tombol Unduh Kartu Depan & Belakang -->
                <div class="mt-3 text-center ekpd-download-actions">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-download-kartu">
                        <i class="fas fa-download me-1" aria-hidden="true"></i>Depan
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm ml-2" id="btn-download-kartu-belakang">
                        <i class="fas fa-download me-1" aria-hidden="true"></i>Belakang
                    </button>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var btnDepan = document.getElementById('btn-download-kartu');
                        var btnBelakang = document.getElementById('btn-download-kartu-belakang');
                        if (btnDepan) {
                            btnDepan.addEventListener('click', function() {
                                window.location.href = "<?= $kartu_front_download ?>";
                            });
                        }
                        if (btnBelakang) {
                            btnBelakang.addEventListener('click', function() {
                                window.location.href = "<?= $kartu_back_download ?>";
                            });
                        }
                    });
                </script>

            </div>
        </div>
    </div>
</div>