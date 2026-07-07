<?php
// Cek apakah ini adalah standalone request
$is_standalone = !isset($connection) || empty($site_name);
if ($is_standalone) {
    // Mode standalone - tampilkan halaman statik penuh
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Realtime Data | SMK Negeri 1 Pagelaran</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="../assets/css/style.css" rel="stylesheet">
    </head>

    <body>
    <?php
}
    ?>
    <div class="module-home-container module-realtime-container">
        <div class="module-home-content">
            <div class="sae-landing realtime-landing">
                <section class="sae-hero realtime-hero" aria-label="Monitor kualitas data realtime">
                    <div class="sae-hero-bg" aria-hidden="true"></div>
                    <div class="sae-hero-inner">
                        <div class="sae-hero-copy">
                            <span class="sae-hero-kicker"><i class="fas fa-circle" aria-hidden="true"></i> Monitor Data Sekolah</span>
                            <h1 class="sae-hero-title">Kualitas Data <span class="sae-hero-accent">Realtime</span></h1>
                            <p class="sae-hero-subtitle">Pantau kualitas data primer murid, progres validasi, dan performa kelas secara langsung dalam satu dashboard publik.</p>
                            <div class="sae-tech-strip">
                                <span class="sae-tech-badge"><i class="fas fa-chart-pie"></i> Statistik</span>
                                <span class="sae-tech-badge"><i class="fas fa-chart-bar"></i> Grafik Jurusan</span>
                                <span class="sae-tech-badge"><i class="fas fa-school"></i> Ranking Kelas</span>
                            </div>
                        </div>
                        <div class="sae-hero-right realtime-hero-right">
                            <div class="realtime-hero-panel card">
                                <div class="realtime-hero-panel-head">
                                    <div>
                                        <h6 class="mb-1">Panel Pemantauan</h6>
                                        <p class="mb-0">Ringkasan data publik untuk memantau kualitas data murid dan kesiapan administrasi sekolah.</p>
                                    </div>
                                </div>
                                <div class="realtime-hero-panel-body">
                                    <div class="realtime-last-update-label">Terakhir diperbarui</div>
                                    <div class="realtime-last-update-value"><?php echo date('d M Y, H:i'); ?> WIB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="glass-card card realtime-shell">
                    <div class="card-body realtime-shell-body">
                <div class="row">
                    <!-- Left Column: Statistics Dashboard (60%) -->
                    <div class="col-12 col-lg-8 col-xl-8 mb-4">
                        <!-- Overall Statistics Cards -->
                        <div class="row mb-4 g-2">
                            <div class="col-6 col-md-3 mb-2">
                                <div class="stats-card glass-card card">
                                    <div class="card-body text-center">
                                        <div class="stats-icon mb-2">
                                            <i class="fas fa-graduation-cap text-primary"></i>
                                        </div>
                                        <h6 class="stats-title">Total Murid</h6>
                                        <h3 class="stats-number text-primary" id="totalSiswa">-</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="stats-card glass-card card">
                                    <div class="card-body text-center">
                                        <div class="stats-icon mb-2">
                                            <i class="fas fa-check-circle text-success"></i>
                                        </div>
                                        <h6 class="stats-title">Konfirmasi Data</h6>
                                        <h3 class="stats-number text-success" id="dataValid">-</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="stats-card glass-card card">
                                    <div class="card-body text-center">
                                        <div class="stats-icon mb-2">
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        </div>
                                        <h6 class="stats-title">Berkas Valid</h6>
                                        <h3 class="stats-number text-warning" id="dataReview">-</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="stats-card glass-card card">
                                    <div class="card-body text-center">
                                        <div class="stats-icon mb-2">
                                            <i class="fas fa-percentage text-info"></i>
                                        </div>
                                        <h6 class="stats-title">Kualitas Data</h6>
                                        <h3 class="stats-number text-info" id="persenKualitas">-</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Section -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="chart-card glass-card card">
                                    <div class="card-header">
                                        <h6 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Distribusi Kualitas Data</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <canvas id="chartKualitas" width="250" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="chart-card glass-card card">
                                    <div class="card-header">
                                        <h6 class="chart-title"><i class="fas fa-chart-bar me-2"></i>Kualitas Per Jurusan</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chartJurusan" width="250" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Data Per Kelas -->
                        <div class="detail-data-card glass-card card mb-4" id="detailDataSection" style="display: none;">
                            <div class="card-header">
                                <h6 class="detail-title"><i class="fas fa-list me-2"></i>Detail Data Murid <span id="selectedClassName"></span></h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="hideDetailData()">
                                    <i class="fas fa-times"></i> Tutup
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <div class="detail-stats">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="stat-item">
                                                        <small class="text-muted">Total Murid</small>
                                                        <div class="h5 mb-0" id="detailTotalSiswa">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="stat-item">
                                                        <small class="text-muted">Data Valid</small>
                                                        <div class="h5 mb-0 text-success" id="detailDataValid">-</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="stat-item">
                                                        <small class="text-muted">Kualitas</small>
                                                        <div class="h5 mb-0 text-info" id="detailPersenKualitas">-</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="export-options">
                                            <button class="btn btn-sm btn-outline-primary me-2" onclick="refreshDetailData()">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">#</th>
                                                <th width="120">NISN</th>
                                                <th>Nama Murid</th>
                                                <th width="100" class="text-center">Identitas</th>
                                                <th width="100" class="text-center">Berkas</th>

                                            </tr>
                                        </thead>
                                        <tbody id="detailTableBody">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    <i class="fas fa-info-circle me-2"></i>Pilih kelas untuk melihat detail data
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Information & Actions (40%) -->
                    <div class="col-12 col-lg-4 col-xl-4">
                        <!-- Filter removed -->

                        <!-- Information Card -->
                        <div class="info-card glass-card card mb-4">
                            <div class="card-header">
                                <h6 class="info-title"><i class="fas fa-info-circle me-2"></i>Tentang Kualitas Data</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-content">
                                    <h6 class="mb-2">Kriteria Data Valid</h6>
                                    <ul class="list-unstyled mb-3">
                                        <li><i class="fas fa-check text-success me-2"></i>Identitas dikonfirmasi</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Berkas KK lengkap</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Berkas Ijazah lengkap</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Data tervalidasi admin</li>
                                    </ul>

                                    <h6 class="mb-2">Status Validasi</h6>
                                    <div class="status-legend mb-3">
                                        <div class="status-item mb-1">
                                            <span class="badge bg-success me-2">Valid</span>
                                            <small>Data lengkap dan terverifikasi</small>
                                        </div>
                                        <div class="status-item mb-1">
                                            <span class="badge bg-warning me-2">Review</span>
                                            <small>Sedang dalam proses validasi</small>
                                        </div>
                                        <div class="status-item mb-1">
                                            <span class="badge bg-danger me-2">Tidak Valid</span>
                                            <small>Data perlu diperbaiki</small>
                                        </div>
                                        <div class="status-item">
                                            <span class="badge bg-secondary me-2">Belum Upload</span>
                                            <small>Berkas belum diunggah</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Last Update Info -->
                        <div class="update-card glass-card card">
                            <div class="card-body text-center">
                                <div class="update-icon mb-2">
                                    <i class="fas fa-clock text-muted"></i>
                                </div>
                                <h6 class="update-title">Terakhir Diperbarui</h6>
                                <div class="update-time text-muted" id="lastUpdate">
                                    <?php echo date('d M Y, H:i'); ?> WIB
                                </div>
                                <button class="btn btn-sm btn-outline-primary mt-2" onclick="refreshData()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Classes (full width, collapsed by default) -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="performance-card glass-card card">
                            <div class="card-header" style="cursor: pointer;" onclick="togglePerformanceTable()" id="performanceToggleHeader">
                                <h6 class="performance-title"><i class="fas fa-trophy me-2"></i>Kelas Terbaik (Kualitas Data)</h6>
                                <button class="btn btn-sm btn-outline-light" type="button" id="togglePerformanceBtn" title="Tampilkan/Sembunyikan Tabel">
                                    <i class="fas fa-chevron-down" id="performanceChevron"></i>
                                </button>
                            </div>
                            <div class="card-body" id="performanceTableBody" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40">#</th>
                                                <th>Kelas</th>
                                                <th>Jurusan</th>
                                                <th width="60" class="text-center">Total</th>
                                                <th width="60" class="text-center">Valid</th>
                                                <th width="80" class="text-center">Kualitas</th>
                                                <th width="60" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="topClassesTable">
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i>Memuat data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </section>
            </div>
        </div>

        <?php if ($is_standalone): ?>
        <!-- Footer (standalone only - integrated uses footer.php) -->
        <div class="module-footer">
            &copy; <?php echo date('Y'); ?> 
            <a href="#">Smart Apps Education | SMK Negeri 1 Pagelaran</a>
            &nbsp;|&nbsp; Developed by <a href="https://s.id/smakpalapik" target="_blank" rel="noopener noreferrer">SMAKPALAPIK</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Floating Action Button -->
    <div class="fab-container" id="fabContainer" role="navigation" aria-label="Menu navigasi cepat">
        <div class="fab-items" role="menu">
            <a href="<?php echo $is_standalone ? '../home/' : './home'; ?>"
                class="fab-item realtime-item"
                role="menuitem"
                title="Halaman Utama">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Home</span>
            </a>
            <a href="<?php echo $is_standalone ? '../absensi/' : './absensi'; ?>"
                class="fab-item microsite-item"
                role="menuitem"
                title="Sistem Absensi RFID">
                <i class="fas fa-id-card" aria-hidden="true"></i>
                <span>Absensi</span>
            </a>
            <a href="https://wa.me/628151800116"
                target="_blank"
                rel="noopener noreferrer"
                class="fab-item whatsapp-item"
                role="menuitem"
                title="Chat WhatsApp Admin">
                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                <span>WhatsApp</span>
            </a>
            <a href="<?php echo $is_standalone ? '../login/' : './login'; ?>"
                class="fab-item login-item"
                role="menuitem"
                title="Login ke Dashboard">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                <span>Login</span>
            </a>
            <a href="<?php echo $is_standalone ? '../dashboard/' : './dashboard'; ?>"
                class="fab-item dashboard-item"
                role="menuitem"
                title="Dashboard Murid">
                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <button class="fab-main pulse"
            id="fabMain"
            type="button"
            title="Menu Akses Cepat"
            aria-label="Buka menu akses cepat"
            aria-expanded="false"
            aria-haspopup="menu">
            <i class="fas fa-plus" aria-hidden="true"></i>
        </button>
    </div>

    <?php if ($is_standalone): ?>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <!-- FAB Toggle Script + Performance Table Toggle -->
    <script>
    (function() {
        var fabContainer = document.getElementById('fabContainer');
        var fabMain = document.getElementById('fabMain');
        if (fabContainer && fabMain) {
            fabMain.addEventListener('click', function(e) {
                e.stopPropagation();
                fabContainer.classList.toggle('open');
                fabMain.setAttribute('aria-expanded', fabContainer.classList.contains('open'));
            });
            document.addEventListener('click', function(e) {
                if (!fabContainer.contains(e.target)) {
                    fabContainer.classList.remove('open');
                    fabMain.setAttribute('aria-expanded', 'false');
                }
            });
        }
    })();

    function togglePerformanceTable() {
        var body = document.getElementById('performanceTableBody');
        var chevron = document.getElementById('performanceChevron');
        if (body.style.display === 'none') {
            body.style.display = '';
            chevron.classList.remove('fa-chevron-down');
            chevron.classList.add('fa-chevron-up');
        } else {
            body.style.display = 'none';
            chevron.classList.remove('fa-chevron-up');
            chevron.classList.add('fa-chevron-down');
        }
    }
    </script>

<?php
    // Modal for class detail (Bootstrap)
?>
<div class="modal fade" id="classDetailModal" tabindex="-1" aria-labelledby="classDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="classDetailModalLabel">Detail Kelas</h5>
                    <div id="modalClassName" style="font-weight:800; font-size:1.05rem;">&nbsp;</div>
                    <div id="modalJurusanName" style="font-size:0.95rem; color:#6c757d;">&nbsp;</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div><strong>Total Murid:</strong> <span id="modalDetailTotalSiswa">-</span></div>
                    </div>
                    <div class="col-md-4">
                        <div><strong>Data Valid:</strong> <span id="modalDetailDataValid">-</span></div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div><strong>Kualitas:</strong> <span id="modalDetailPersenKualitas">-</span></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th width="120">NISN</th>
                                <th>Nama Murid</th>
                                <th width="100" class="text-center">Identitas</th>
                                <th width="100" class="text-center">Berkas</th>

                            </tr>
                        </thead>
                        <tbody id="modalDetailTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>Belum ada data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php
if ($is_standalone) {
?>
    </body>

    </html>
<?php
}
