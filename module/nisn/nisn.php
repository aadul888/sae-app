<?php
if (empty($connection)) {
    echo '<div class="alert alert-danger text-center mt-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Koneksi database belum terhubung
          </div>';
} else {
    $nisn_param = empty($_GET['nisn']) ? '' : htmlentities($_GET['nisn']);
    if (preg_match('/^[0-9]+$/', $nisn_param)) {
        $nisn = $nisn_param;
    } else {
        $nisn = convert("decrypt", $nisn_param);
    }
    $query_user = "SELECT user.*, kelas.nama_kelas FROM user 
                   INNER JOIN kelas ON user.kelas = kelas.kelas_id 
                   WHERE user.nisn = '$nisn' AND user.status IN ('Aktif', 'Tidak Aktif')";
    $result_user = $connection->query($query_user);
    $base = isset($base_url) ? rtrim($base_url, '/') : '';
    $home_href = $base !== '' ? $base . '/' : './';
    $login_href = $base !== '' ? $base . '/login/?nisn=' . urlencode($nisn) : './login/?nisn=' . urlencode($nisn);
    if ($result_user->num_rows > 0) {
        $data_user = $result_user->fetch_assoc();
        $status_warna = $data_user['status'] == 'Aktif' ? 'bg-success' : 'bg-danger';
        $status_text = $data_user['status'] == 'Aktif' ? 'Aktif' : 'Tidak Aktif';
?>
        <?php
        // Logo: prefer content/logoweb1.png with filemtime as cache-buster when present
        $logo_fs = __DIR__ . '/../../content/logoweb1.png';
        $logo_src = ($base ? $base : '') . '/content/logoweb1.png';
        if (file_exists($logo_fs)) {
            $logo_src .= '?t=' . filemtime($logo_fs);
        }

        // Avatar: prefer DB-stored avatar if available, otherwise try nisn.png/jpg, else default avatar.jpg
        $avatar_src = ($base ? $base : '') . '/content/avatar/avatar.jpg';
        if (!empty($data_user['avatar']) && $data_user['avatar'] != 'avatar.jpg') {
            $stored = strip_tags($data_user['avatar']);
            $stored_file = preg_replace('/\?.*/', '', $stored);
            $avatar_fs = __DIR__ . '/../../content/avatar/' . $stored_file;
            if (file_exists($avatar_fs)) {
                $avatar_src = ($base ? $base : '') . '/content/avatar/' . $stored;
            }
        }
        if ($avatar_src === ($base ? $base : '') . '/content/avatar/avatar.jpg') {
            $try_png = __DIR__ . '/../../content/avatar/' . $data_user['nisn'] . '.png';
            $try_jpg = __DIR__ . '/../../content/avatar/' . $data_user['nisn'] . '.jpg';
            if (file_exists($try_png)) {
                $avatar_src = ($base ? $base : '') . '/content/avatar/' . $data_user['nisn'] . '.png?t=' . filemtime($try_png);
            } elseif (file_exists($try_jpg)) {
                $avatar_src = ($base ? $base : '') . '/content/avatar/' . $data_user['nisn'] . '.jpg?t=' . filemtime($try_jpg);
            }
        }

        // QR code src with filemtime if exists
        $qrcode_fs = __DIR__ . '/../../content/qrcode/' . $data_user['nisn'] . '.jpg';
        $qrcode_src = ($base ? $base : '') . '/content/qrcode/' . $data_user['nisn'] . '.jpg';
        if (file_exists($qrcode_fs)) {
            $qrcode_src .= '?t=' . filemtime($qrcode_fs);
        }
        ?>

        <div class="sae-landing nisn-profile-page">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-11">
                    <section class="sae-hero nisn-hero mb-0">
                        <div class="row align-items-center g-4">
                            <div class="col-12 col-lg-7">
                                <span class="nisn-kicker"><i class="fas fa-id-card-alt"></i> Profil Murid</span>
                                <h1 class="nisn-title"><?= htmlspecialchars($data_user['nama_lengkap']) ?></h1>
                                <div class="nisn-hero-meta">
                                    <span><i class="fas fa-hashtag"></i>NISN: <?= htmlspecialchars($data_user['nisn']) ?></span>
                                    <span><i class="fas fa-school"></i>Kelas: <?= htmlspecialchars($data_user['nama_kelas']) ?></span>
                                    <span class="nisn-status-chip <?= $status_warna ?>"><?= $status_text ?></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="nisn-shell">
                        <div class="row g-4 align-items-stretch">
                            <div class="col-12 col-lg-4">
                                <div class="nisn-main-card h-100">
                                    <div class="photo-section">
                                        <div class="student-photo text-center">
                                            <img src="<?= htmlspecialchars($avatar_src) ?>"
                                                class="student-avatar"
                                                alt="Foto <?= htmlspecialchars($data_user['nama_lengkap']) ?>"
                                                onerror="this.src='<?= htmlspecialchars(($base ? $base : '') . '/content/avatar/avatar.jpg') ?>'">
                                            <div class="photo-label">Foto Siswa</div>
                                        </div>
                                        <div class="qr-section text-center">
                                            <img src="<?= htmlspecialchars($qrcode_src) ?>"
                                                class="qr-code"
                                                alt="QR Code NISN"
                                                onerror="this.style.display='none'">
                                            <div class="qr-label">QR Code NISN</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-8">
                                <div class="nisn-content-grid">
                                    <div class="info-card">
                                        <div class="info-header">
                                            <i class="fas fa-user-circle"></i>
                                            <span>Informasi Personal</span>
                                        </div>
                                        <div class="info-body">
                                            <div class="info-row">
                                                <span class="info-label">NIPD</span>
                                                <span class="info-value"><?= htmlspecialchars($data_user['nipd']) ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Jenis Kelamin</span>
                                                <span class="info-value"><?= htmlspecialchars($data_user['jenis_kelamin']) ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Kelas Saat Ini</span>
                                                <span class="info-value"><?= htmlspecialchars($data_user['nama_kelas']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-card">
                                        <div class="info-header">
                                            <i class="fas fa-address-book"></i>
                                            <span>Informasi Kontak</span>
                                        </div>
                                        <div class="info-body">
                                            <div class="info-row">
                                                <span class="info-label">Telp</span>
                                                <span class="info-value"><?= htmlspecialchars($data_user['telp'] ?: 'Tidak tersedia') ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Alamat</span>
                                                <span class="info-value"><?= htmlspecialchars($data_user['alamat'] ?: 'Tidak tersedia') ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="notice-card nisn-wide-card">
                                        <div class="notice-header">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Informasi Penting</span>
                                        </div>
                                        <div class="notice-body">
                                            <p class="notice-text">Data yang ditampilkan merupakan informasi ringkas profil siswa. Untuk mengakses data lengkap dan melakukan perubahan informasi, silakan login menggunakan akun siswa melalui sistem.</p>
                                            <div class="update-info">
                                                <small>
                                                    <strong><i class="fas fa-calendar-alt me-1"></i>Terakhir Diperbarui:</strong>
                                                    <?= htmlspecialchars(date('d F Y', strtotime($data_user['date']))) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="action-buttons nisn-wide-card">
                                        <a href="<?= htmlspecialchars($login_href) ?>" class="btn-action btn-primary">
                                            <i class="fas fa-sign-in-alt"></i>
                                            Login untuk Data Lengkap
                                        </a>
                                        <a href="<?= htmlspecialchars($home_href) ?>" class="btn-action btn-outline-secondary">
                                            <i class="fas fa-home"></i>
                                            Kembali ke Beranda
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    } else {
        echo '<div class="sae-landing nisn-profile-page">
                <div class="row justify-content-center">
                    <div class="col-12 col-xl-8">
                        <div class="nisn-empty-state text-center">
                            <div class="nisn-empty-icon"><i class="fas fa-search"></i></div>
                            <h2>Data Tidak Ditemukan</h2>
                            <p>NISN yang Anda cari tidak terdapat dalam database sistem kami. Silakan periksa kembali NISN yang dimasukkan atau hubungi administrator sekolah untuk bantuan lebih lanjut.</p>
                            <div class="action-buttons justify-content-center">
                                <a href="' . htmlspecialchars($home_href, ENT_QUOTES, 'UTF-8') . '" class="btn-action btn-primary">
                                    <i class="fas fa-home"></i>
                                    Kembali ke Beranda
                                </a>
                                <a href="' . htmlspecialchars($home_href . 'login/', ENT_QUOTES, 'UTF-8') . '" class="btn-action btn-outline-secondary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Ke Halaman Login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
              </div>';
    }
}
?>