<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
?>

        <div class="container-fluid min-vh-100 py-5">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="page-title">Aplikasi Lainnya</h1>
                <p class="page-subtitle">Akses semua aplikasi sekolah dalam satu dashboard</p>
            </div>

            <!-- Aplikasi Utama -->
            <div class="apps-section mb-5">
                <h3 class="section-title">Aplikasi Utama</h3>
                <div class="row g-4">
                    <!-- e-PKL -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="app-card" data-app="e-PKL">
                            <?php
                                $app_root = isset($base_url) ? preg_replace('#/(dashboard|admin)(/.*)?$#', '', rtrim($base_url, '/')) : '';
                                // Use dynamic base URL so this works on any host/port (localhost:8080, production, etc.)
                                $sso_link = $app_root . '/sso/redirect_student.php';
                                // Debug: show the actual URL being generated
                                echo '<!-- DEBUG SSO Link: ' . htmlspecialchars($sso_link) . ' -->';
                            ?>
                            <a href="<?php echo htmlspecialchars($sso_link); ?>" target="_blank" class="app-link">
                                <div class="app-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="app-content">
                                    <h4 class="app-title">e-PKL</h4>
                                    <p class="app-description">Praktik Kerja Lapangan</p>
                                    <div class="app-status available">
                                        <i class="fas fa-check-circle"></i>
                                        Tersedia
                                    </div>
                                </div>
                                <div class="app-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- e-KPD Old -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="app-card" data-app="e-KPD Old">
                            <a href="https://kp.smakpal.sch.id/" target="_blank" class="app-link">
                                <div class="app-icon">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <div class="app-content">
                                    <h4 class="app-title">e-KPD Old</h4>
                                    <p class="app-description">Kartu Pelajar Digital</p>
                                    <div class="app-status available">
                                        <i class="fas fa-check-circle"></i>
                                        Tersedia
                                    </div>
                                </div>
                                <div class="app-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- eAbsen (Coming Soon) -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="app-card disabled" data-app="eAbsen">
                            <div class="app-link">
                                <div class="app-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="app-content">
                                    <h4 class="app-title">eAbsen</h4>
                                    <p class="app-description">Sistem Absensi Digital</p>
                                    <div class="app-status coming-soon">
                                        <i class="fas fa-clock"></i>
                                        Segera Hadir
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SPMB -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="app-card" data-app="SPMB">
                            <a href="https://spmb.smakpal.sch.id" target="_blank" class="app-link">
                                <div class="app-icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="app-content">
                                    <h4 class="app-title">SPMB</h4>
                                    <p class="app-description">Penerimaan Siswa Baru</p>
                                    <div class="app-status available">
                                        <i class="fas fa-check-circle"></i>
                                        Tersedia
                                    </div>
                                </div>
                                <div class="app-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kontak & Bantuan -->
            <div class="support-section mb-5" style="margin-bottom:50px !important;">
                <h3 class="section-title">Butuh Bantuan?</h3>
                <div class="row g-4 justify-content-center">
                    <!-- WhatsApp Admin -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="contact-card" data-contact="whatsapp">
                            <a href="https://wa.me/628151800116" target="_blank" class="contact-link">
                                <div class="contact-icon">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <div class="contact-content">
                                    <h5 class="contact-title">WhatsApp Admin</h5>
                                    <p class="contact-description">Chat langsung dengan admin</p>
                                    <span class="contact-number">+62 815-1800-116</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Email Sekolah -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="contact-card" data-contact="email">
                            <a href="mailto:smkn01pgl@gmail.com" class="contact-link">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-content">
                                    <h5 class="contact-title">Email Sekolah</h5>
                                    <p class="contact-description">Kirim pertanyaan via email</p>
                                    <span class="contact-number">smkn01pgl@gmail.com</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p class="loading-text">Membuka aplikasi...</p>
            </div>
        </div>

<?php
        // Load footer dashboard jika ada
        if (file_exists(__DIR__ . '/../mod/footer.php')) {
            include_once __DIR__ . '/../mod/footer.php';
        }
    } else {
        echo '<div class="container mt-5">
            <div class="alert alert-warning text-center">
              <h4><i class="fas fa-exclamation-triangle"></i> Akses Ditolak</h4>
              <p>Silakan login untuk mengakses dashboard.</p>
              <a href="../" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login Sekarang
              </a>
            </div>
          </div>';
    }
}
?>
</body>

</html>