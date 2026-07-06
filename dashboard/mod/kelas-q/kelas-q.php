<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
?>
        <div class="home-dashboard-container kelas-q-container">
            <div class="container py-5">
                <h2 class="text-center font-weight-bold mb-2">Menu Kelas</h2>
                <p class="text-center text-muted mb-5">Akses cepat ke fitur pengelolaan kelas</p>

                <!-- Desktop/Tablet Layout -->
                <div class="row justify-content-center d-none d-md-flex" style="gap: 0;">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-menu-card menu-card-blue">
                            <div class="card-body">
                                <div class="modern-menu-icon mb-3">
                                    <div class="icon-bg bg-primary-gradient">
                                        <i class="fas fa-calendar-check fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title font-weight-bold mb-2">Absensi</h5>
                                <p class="card-text text-muted mb-3">Kelola absensi kelas dan lihat rekap kehadiran siswa</p>
                                <a href="absensi-kelas" class="btn btn-primary btn-modern">
                                    <i class="fas fa-arrow-right me-2"></i>Akses Absensi
                                </a>
                            </div>
                            <div class="card-overlay"></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-menu-card menu-card-green">
                            <div class="card-body">
                                <div class="modern-menu-icon mb-3">
                                    <div class="icon-bg bg-success-gradient">
                                        <i class="fas fa-users fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title font-weight-bold mb-2">Struktur Organisasi</h5>
                                <p class="card-text text-muted mb-3">Lihat dan kelola struktur organisasi kelas</p>
                                <a href="struktur-organisasi" class="btn btn-success btn-modern">
                                    <i class="fas fa-arrow-right me-2"></i>Lihat Struktur
                                </a>
                            </div>
                            <div class="card-overlay"></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-menu-card menu-card-orange">
                            <div class="card-body">
                                <div class="modern-menu-icon mb-3">
                                    <div class="icon-bg bg-warning-gradient">
                                        <i class="fas fa-exclamation-triangle fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title font-weight-bold mb-2">Poin Pelanggaran</h5>
                                <p class="card-text text-muted mb-3">Kelola dan pantau poin pelanggaran siswa</p>
                                <a href="poin" class="btn btn-warning btn-modern">
                                    <i class="fas fa-arrow-right me-2"></i>Kelola Poin
                                </a>
                            </div>
                            <div class="card-overlay"></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-menu-card menu-card-teal">
                            <div class="card-body">
                                <div class="modern-menu-icon mb-3">
                                    <div class="icon-bg bg-info-gradient">
                                        <i class="fas fa-chart-line fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title font-weight-bold mb-2">Cek Data</h5>
                                <p class="card-text text-muted mb-3">Periksa dan validasi kualitas data siswa</p>
                                <a href="cek-data-kelas" class="btn btn-info btn-modern">
                                    <i class="fas fa-arrow-right me-2"></i>Cek Data
                                </a>
                            </div>
                            <div class="card-overlay"></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4 mt-3">
                        <div class="card h-100 text-center modern-menu-card menu-card-purple">
                            <div class="card-body">
                                <div class="modern-menu-icon mb-3">
                                    <div class="icon-bg bg-purple-gradient">
                                        <i class="fas fa-boxes fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title font-weight-bold mb-2">Inventaris Kelas</h5>
                                <p class="card-text text-muted mb-3">Kelola dan catat inventaris barang di kelas</p>
                                <a href="?mod=invetaris-kelas" class="btn btn-purple btn-modern">
                                    <i class="fas fa-arrow-right me-2"></i>Kelola Inventaris
                                </a>
                            </div>
                            <div class="card-overlay"></div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4 mt-3">
                        <div class="card h-100 text-center modern-menu-card menu-card-indigo">
                            <div class="card-body">
                                <div class="modern-menu-icon mb-3">
                                    <div class="icon-bg bg-indigo-gradient">
                                        <i class="fas fa-book-open fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="card-title font-weight-bold mb-2">Agenda Kelas</h5>
                                <p class="card-text text-muted mb-3">Catat agenda harian, kehadiran guru & materi</p>
                                <a href="agenda-kelas" class="btn btn-indigo btn-modern">
                                    <i class="fas fa-arrow-right me-2"></i>Kelola Agenda
                                </a>
                            </div>
                            <div class="card-overlay"></div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Layout (2x2 Grid) -->
                <div class="d-md-none mobile-menu-grid">
                    <div class="row g-3">
                        <div class="col-6 mb-3">
                            <div class="mobile-menu-item menu-card-blue">
                                <a href="absensi-kelas" class="text-decoration-none">
                                    <div class="mobile-icon-wrapper">
                                        <div class="mobile-icon bg-primary-gradient">
                                            <i class="fas fa-calendar-check fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="mobile-title">Absensi</h6>
                                    <small class="mobile-desc">Kelola absensi kelas</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="mobile-menu-item menu-card-green">
                                <a href="struktur-organisasi" class="text-decoration-none">
                                    <div class="mobile-icon-wrapper">
                                        <div class="mobile-icon bg-success-gradient">
                                            <i class="fas fa-users fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="mobile-title">Struktur Organisasi</h6>
                                    <small class="mobile-desc">Organisasi kelas</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mobile-menu-item menu-card-orange">
                                <a href="poin" class="text-decoration-none">
                                    <div class="mobile-icon-wrapper">
                                        <div class="mobile-icon bg-warning-gradient">
                                            <i class="fas fa-exclamation-triangle fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="mobile-title">Poin Pelanggaran</h6>
                                    <small class="mobile-desc">Pantau pelanggaran</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mobile-menu-item menu-card-teal">
                                <a href="cek-data-kelas" class="text-decoration-none">
                                    <div class="mobile-icon-wrapper">
                                        <div class="mobile-icon bg-info-gradient">
                                            <i class="fas fa-chart-line fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="mobile-title">Cek Data</h6>
                                    <small class="mobile-desc">Validasi data siswa</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-6 mt-3 mb-3">
                            <div class="mobile-menu-item menu-card-purple">
                                <a href="?mod=invetaris-kelas" class="text-decoration-none">
                                    <div class="mobile-icon-wrapper">
                                        <div class="mobile-icon bg-purple-gradient">
                                            <i class="fas fa-boxes fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="mobile-title">Inventaris Kelas</h6>
                                    <small class="mobile-desc">Catat inventaris kelas</small>
                                </a>
                            </div>
                        </div>
                        <div class="col-6 mt-3 mb-3">
                            <div class="mobile-menu-item menu-card-indigo">
                                <a href="agenda-kelas" class="text-decoration-none">
                                    <div class="mobile-icon-wrapper">
                                        <div class="mobile-icon bg-indigo-gradient">
                                            <i class="fas fa-book-open fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="mobile-title">Agenda Kelas</h6>
                                    <small class="mobile-desc">Agenda & kehadiran guru</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
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