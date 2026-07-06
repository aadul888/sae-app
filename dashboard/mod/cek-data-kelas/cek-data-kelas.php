<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
        $cookie_val = $_COOKIE['siswa'];

        // Gunakan fungsi convert('decrypt', ...) untuk mendapatkan user_id
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'rer54etrg5eysdkj9832h2rh3784y632hr';
        $secret_iv = 'g5gtghh45dsnf53785728372hjhfb38b83fb873fb8';
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        $decoded_id = convert('decrypt', $cookie_val);

        if (is_numeric($decoded_id)) {
            $user_id = intval($decoded_id);
            $q_user = mysqli_query($connection, "SELECT kelas, koordinator FROM user WHERE user_id='$user_id' LIMIT 1");
            $d_user = mysqli_fetch_assoc($q_user);
        } else {
            $d_user = null;
        }

        // Cek login dan koordinator (pastikan tipe data benar)
        if (!$d_user || ($d_user['koordinator'] != 1 && $d_user['koordinator'] != '1')) {
            echo '<div class="container mt-5">
                                <div class="alert alert-danger text-center">
                                    <h4><i class="fas fa-exclamation-triangle"></i> Akses Ditolak</h4>
                                    <p>Halaman ini hanya bisa diakses oleh koordinator kelas.</p>
                                    <a href="../dashboard" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Kembali
                                    </a>
                                </div>
                            </div>';
            exit();
        }

        $kelas_id = trim($d_user['kelas']);

        // Definisi label untuk status validasi berkas
        $validasi_opsi = [
            'valid' => 'Valid',
            'tidak_valid' => 'Tidak Valid',
            'revisi' => 'Revisi',
            'belum_upload' => 'Belum Upload',
            '' => 'Menunggu Validasi'
        ];

        // Ambil data kelas dari tabel kelas berdasarkan kelas_id
        $q_kelas = mysqli_query($connection, "SELECT * FROM kelas WHERE kelas_id='$kelas_id' LIMIT 1");
        $d_kelas = mysqli_fetch_assoc($q_kelas);
        $nama_kelas_full = isset($d_kelas['nama_kelas']) ? $d_kelas['nama_kelas'] : $kelas_id;
        $wali_kelas_nama = isset($d_kelas['wali_kelas_nama']) ? $d_kelas['wali_kelas_nama'] : '-';

        // Ambil data siswa di kelas yang sama (hanya yang berstatus Aktif)
        $q_siswa = mysqli_query($connection, "SELECT * FROM user WHERE kelas='$kelas_id' AND status = 'Aktif' ORDER BY nama_lengkap ASC");

        // Hitung statistik dan ambil status berkas
        $jml_laki = 0;
        $jml_perempuan = 0;
        $jml_total = 0;
        $rows = [];
        while ($row = mysqli_fetch_assoc($q_siswa)) {
            $jml_total++;
            if (strtolower(trim($row['jenis_kelamin'])) == 'laki-laki') $jml_laki++;
            if (strtolower(trim($row['jenis_kelamin'])) == 'perempuan') $jml_perempuan++;

            // Ambil status berkas dari tabel berkas
            $berkas_status = 'belum_upload';
            $q_berkas = mysqli_query($connection, "SELECT validasi_berkas FROM berkas WHERE user_id='" . $row['user_id'] . "' LIMIT 1");
            if ($q_berkas && $d_berkas = mysqli_fetch_assoc($q_berkas)) {
                $berkas_status = ($d_berkas['validasi_berkas'] !== null) ? $d_berkas['validasi_berkas'] : '';
            }
            $row['berkas_status'] = $berkas_status;
            $rows[] = $row;
        }

        // Debug info jika tidak ada data
        if (empty($kelas_id) || $jml_total == 0) {
            echo '<div class="container mt-5">
                                <div class="alert alert-warning text-center">
                                    <h4><i class="fas fa-exclamation-triangle"></i> Data Tidak Ditemukan</h4>
                                    <p>Kelas: <b>' . htmlspecialchars($nama_kelas_full) . '</b> tidak memiliki data siswa.</p>
                                </div>
                            </div>';
            exit();
        }

        // Hitung kualitas data menggunakan metode yang sama dengan modul admin > kelas
        // yaitu: bobot 0.5 untuk konfirmasi (Sesuai/Belum Sesuai) dan 0.5 untuk berkas yang divalidasi
        $jumlah_kualitas = 0.0;
        foreach ($rows as $row) {
            $user_id = $row['user_id'];
            // konfirmasi dianggap valid jika bernilai 'Sesuai' atau 'Belum Sesuai'
            $konfirmasi_ok = false;
            if (isset($row['konfirmasi'])) {
                $val = trim($row['konfirmasi']);
                if ($val === 'Sesuai' || $val === 'Belum Sesuai') $konfirmasi_ok = true;
            }
            $score = 0.0;
            if ($konfirmasi_ok) $score += 0.5;
            // periksa apakah ada baris berkas dengan validasi = 'valid'
            $q_bv = mysqli_query($connection, "SELECT 1 FROM berkas b WHERE b.user_id='" . intval($user_id) . "' AND b.validasi_berkas='valid' LIMIT 1");
            if ($q_bv && mysqli_num_rows($q_bv) > 0) {
                $score += 0.5;
            }
            $jumlah_kualitas += $score;
        }
        $persen_valid = $jml_total > 0 ? round(($jumlah_kualitas / $jml_total) * 100, 1) : 0;
?>
        <div class="home-dashboard-container">
            <div class="container py-5">
                <h2 class="text-center font-weight-bold mb-2">Data Kelas</h2>
                <p class="text-center text-muted mb-5">Ringkasan kualitas data dan daftar siswa pada kelas Anda</p>
                <!-- Desktop Layout -->
                <div class="row justify-content-center mb-5 d-none d-md-flex">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-card card-blue">
                            <div class="card-body">
                                <div class="modern-icon-wrapper mb-3">
                                    <div class="icon-bg bg-primary-gradient">
                                        <i class="fas fa-male fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">SISWA</h6>
                                <h5 class="card-title font-weight-bold">Laki-laki</h5>
                                <h2 class="display-4 font-weight-bold text-primary mb-0"><?php echo $jml_laki; ?></h2>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted"><i class="fas fa-male me-1"></i>Total siswa laki-laki</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-card card-pink">
                            <div class="card-body">
                                <div class="modern-icon-wrapper mb-3">
                                    <div class="icon-bg bg-danger-gradient">
                                        <i class="fas fa-female fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">SISWA</h6>
                                <h5 class="card-title font-weight-bold">Perempuan</h5>
                                <h2 class="display-4 font-weight-bold text-danger mb-0"><?php echo $jml_perempuan; ?></h2>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted"><i class="fas fa-female me-1"></i>Total siswa perempuan</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-card card-green">
                            <div class="card-body">
                                <div class="modern-icon-wrapper mb-3">
                                    <div class="icon-bg bg-success-gradient">
                                        <i class="fas fa-users fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">KESELURUHAN</h6>
                                <h5 class="card-title font-weight-bold">Total Siswa</h5>
                                <h2 class="display-4 font-weight-bold text-success mb-0"><?php echo $jml_total; ?></h2>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted"><i class="fas fa-graduation-cap me-1"></i>Total semua siswa</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 text-center modern-card card-teal">
                            <div class="card-body">
                                <div class="modern-icon-wrapper mb-3">
                                    <div class="chart-container" style="height:80px;width:80px;margin:0 auto;position:relative;">
                                        <canvas id="chartKualitasData"></canvas>
                                        <div class="chart-center-text">
                                            <i class="fas fa-chart-pie text-info"></i>
                                        </div>
                                    </div>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">VALIDASI</h6>
                                <h5 class="card-title font-weight-bold">Kualitas Data</h5>
                                <h2 class="display-4 font-weight-bold text-info mb-0"><?php echo $persen_valid; ?>%</h2>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted"><i class="fas fa-check-circle me-1"></i>Valid: <?php echo $jml_valid; ?> dari <?php echo $jml_total; ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Layout (2x2 Grid) -->
                <div class="d-md-none mobile-stats-grid mb-5">
                    <div class="row g-3">
                        <!-- Baris Pertama -->
                        <div class="col-6 mb-3">
                            <div class="mobile-stats-card stats-card-blue">
                                <div class="mobile-stats-icon">
                                    <div class="stats-icon bg-primary-gradient">
                                        <i class="fas fa-male text-white"></i>
                                    </div>
                                </div>
                                <div class="mobile-stats-content">
                                    <h6 class="mobile-stats-label">Laki-laki</h6>
                                    <h3 class="mobile-stats-number text-primary"><?php echo $jml_laki; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="mobile-stats-card stats-card-pink">
                                <div class="mobile-stats-icon">
                                    <div class="stats-icon bg-danger-gradient">
                                        <i class="fas fa-female text-white"></i>
                                    </div>
                                </div>
                                <div class="mobile-stats-content">
                                    <h6 class="mobile-stats-label">Perempuan</h6>
                                    <h3 class="mobile-stats-number text-danger"><?php echo $jml_perempuan; ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Baris Kedua -->
                        <div class="col-6">
                            <div class="mobile-stats-card stats-card-green">
                                <div class="mobile-stats-icon">
                                    <div class="stats-icon bg-success-gradient">
                                        <i class="fas fa-users text-white"></i>
                                    </div>
                                </div>
                                <div class="mobile-stats-content">
                                    <h6 class="mobile-stats-label">Total Siswa</h6>
                                    <h3 class="mobile-stats-number text-success"><?php echo $jml_total; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mobile-stats-card stats-card-teal">
                                <div class="mobile-stats-icon">
                                    <div class="mobile-chart-container">
                                        <canvas id="chartKualitasDataMobile" width="40" height="40"></canvas>
                                    </div>
                                </div>
                                <div class="mobile-stats-content">
                                    <h6 class="mobile-stats-label">Kualitas Data</h6>
                                    <h3 class="mobile-stats-number text-info"><?php echo $persen_valid; ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card modern-table-card">
                    <div class="card-header bg-gradient-primary text-white">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="mb-2 text-white font-weight-bold"><i class="fas fa-table me-2"></i>Data Siswa Kelas</h5>
                                <div class="class-info-container">
                                    <div class="class-info-item">
                                        <i class="fas fa-graduation-cap me-1"></i>
                                        <span class="info-label">Kelas:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($nama_kelas_full); ?></span>
                                    </div>
                                    <div class="class-info-divider">|</div>
                                    <div class="class-info-item">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>
                                        <span class="info-label">Wali Kelas:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($wali_kelas_nama); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="kelas-q" class="btn btn-outline-light btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali
                                </a>
                                <a href="mod/cek-data-kelas/pdf.php" class="btn btn-light btn-sm text-primary modern-export-btn" target="_blank">
                                    <i class="fas fa-file-pdf me-1"></i>Unduh PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive modern-table">
                            <table class="table table-hover align-middle mb-0" id="dataTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50" class="text-center"><i class="fas fa-hashtag"></i></th>
                                        <th><i class="fas fa-id-card me-2"></i>NISN</th>
                                        <th><i class="fas fa-user me-2"></i>Nama Lengkap</th>
                                        <th class="text-center"><i class="fas fa-check-circle me-2"></i>Identitas</th>
                                        <th class="text-center"><i class="fas fa-folder me-2"></i>Berkas</th>
                                        <th class="text-center"><i class="fas fa-calendar-check me-2"></i>Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($rows as $row) {
                                        $identitas = $row['konfirmasi'] ? $row['konfirmasi'] : '-';
                                        $berkas_status = $row['berkas_status'];
                                        $label_berkas = isset($validasi_opsi[$berkas_status]) ? $validasi_opsi[$berkas_status] : $berkas_status;
                                        $badge_berkas = 'secondary';
                                        if ($berkas_status == 'valid') $badge_berkas = 'success';
                                        elseif ($berkas_status == 'tidak_valid') $badge_berkas = 'danger';
                                        elseif ($berkas_status == 'revisi') $badge_berkas = 'warning';
                                        elseif ($berkas_status == 'belum_upload') $badge_berkas = 'secondary';
                                        elseif ($berkas_status == '') $badge_berkas = 'info';
                                        echo '<tr>
                                            <td>' . $no++ . '</td>
                                            <td>' . $row['nisn'] . '</td>
                                            <td>' . $row['nama_lengkap'] . '</td>
                                            <td class="text-center"><span class="badge badge-' . ($identitas == 'Sesuai' ? 'success' : ($identitas == 'Belum Sesuai' ? 'warning' : 'secondary')) . '">' . $identitas . '</span></td>
                                            <td class="text-center"><span class="badge badge-' . $badge_berkas . '">' . $label_berkas . '</span></td>
                                            <td class="text-center">-</td>
                                        </tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    (function() {
                        // Desktop chart
                        var canvas = document.getElementById('chartKualitasData');
                        if (canvas) {
                            var ctx = canvas.getContext('2d');
                            new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: ['Valid', 'Belum Valid'],
                                    datasets: [{
                                        data: [<?= $jml_valid ?>, <?= $jml_total - $jml_valid ?>],
                                        backgroundColor: ['#17a2b8', '#e0e0e0'],
                                        borderWidth: 2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    cutout: '70%',
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            enabled: true
                                        }
                                    }
                                }
                            });
                        }

                        // Mobile chart
                        var canvasMobile = document.getElementById('chartKualitasDataMobile');
                        if (canvasMobile) {
                            var ctxMobile = canvasMobile.getContext('2d');
                            new Chart(ctxMobile, {
                                type: 'doughnut',
                                data: {
                                    labels: ['Valid', 'Belum Valid'],
                                    datasets: [{
                                        data: [<?= $jml_valid ?>, <?= $jml_total - $jml_valid ?>],
                                        backgroundColor: ['#17a2b8', '#e0e0e0'],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: false,
                                    maintainAspectRatio: false,
                                    cutout: '65%',
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            enabled: false
                                        }
                                    }
                                }
                            });
                        }
                    })();
                </script>
                <script>
                    window.jmlLaki = <?php echo json_encode($jml_laki); ?>;
                    window.jmlPerempuan = <?php echo json_encode($jml_perempuan); ?>;
                    window.jmlTotal = <?php echo json_encode($jml_total); ?>;
                    window.jmlValid = <?php echo json_encode($jml_valid); ?>;
                </script>
            </div>
        </div>

        <style>
            /* Modern Card Styles */
            .modern-card {
                border: none;
                border-radius: 20px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
                overflow: hidden;
                position: relative;
            }

            .modern-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #0f4c81 0%, #0f766e 100%);
                transition: height 0.3s ease;
            }

            .modern-card.card-blue::before {
                background: linear-gradient(90deg, #4e73df 0%, #224abe 100%);
            }

            .modern-card.card-pink::before {
                background: linear-gradient(90deg, #e74a3b 0%, #c0392b 100%);
            }

            .modern-card.card-green::before {
                background: linear-gradient(90deg, #1cc88a 0%, #17a673 100%);
            }

            .modern-card.card-teal::before {
                background: linear-gradient(90deg, #36b9cc 0%, #258391 100%);
            }

            .modern-card:hover {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            }

            .modern-card:hover::before {
                height: 8px;
            }

            .modern-icon-wrapper {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-bottom: 1.5rem;
            }

            .icon-bg {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .modern-card:hover .icon-bg {
                transform: scale(1.1) rotate(5deg);
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            }

            .bg-primary-gradient {
                background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            }

            .bg-danger-gradient {
                background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);
            }

            .bg-success-gradient {
                background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
            }

            .bg-info-gradient {
                background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
            }

            .chart-container {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .chart-center-text {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 1;
                pointer-events: none;
            }

            .card-subtitle {
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .card-title {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }

            .display-4 {
                font-size: 2.5rem;
                line-height: 1;
            }

            .card-footer {
                border-top: 1px solid rgba(0, 0, 0, 0.05);
                padding: 0.75rem 1.25rem;
            }

            /* Original styles */
            .card-body {
                padding: 2rem;
            }

            .btn {
                border-radius: 25px;
                padding: 8px 25px;
                margin-top: 1rem;
            }

            .badge {
                padding: 8px 12px;
                font-size: 12px;
            }

            .table thead th {
                border-top: none;
                border-bottom: 2px solid #dee2e6;
                background-color: #f8f9fa;
                vertical-align: middle;
            }

            .table td {
                vertical-align: middle;
            }

            /* Chart responsiveness */
            .chart-container canvas {
                width: 100% !important;
                height: 100% !important;
            }

            /* Mobile Stats Cards */
            .mobile-stats-grid {
                padding: 0 1rem;
            }

            .mobile-stats-card {
                background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
                border-radius: 18px;
                padding: 1.25rem;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
                height: 100%;
                min-height: 110px;
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .mobile-stats-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                transition: height 0.3s ease;
            }

            .mobile-stats-card.stats-card-blue::before {
                background: linear-gradient(90deg, #4e73df 0%, #224abe 100%);
            }

            .mobile-stats-card.stats-card-pink::before {
                background: linear-gradient(90deg, #e74a3b 0%, #c0392b 100%);
            }

            .mobile-stats-card.stats-card-green::before {
                background: linear-gradient(90deg, #1cc88a 0%, #17a673 100%);
            }

            .mobile-stats-card.stats-card-teal::before {
                background: linear-gradient(90deg, #36b9cc 0%, #258391 100%);
            }

            .mobile-stats-card:active {
                transform: scale(0.97);
                box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
            }

            .mobile-stats-card:active::before {
                height: 6px;
            }

            .mobile-stats-icon {
                flex-shrink: 0;
            }

            .stats-icon {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                transition: transform 0.3s ease;
            }

            .mobile-stats-card:active .stats-icon {
                transform: scale(1.05);
            }

            .mobile-chart-container {
                width: 45px;
                height: 45px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-chart-container canvas {
                width: 40px !important;
                height: 40px !important;
            }

            .mobile-stats-content {
                flex: 1;
                text-align: left;
            }

            .mobile-stats-label {
                font-size: 0.8rem;
                font-weight: 600;
                color: #6c757d;
                margin-bottom: 0.25rem;
                line-height: 1.2;
            }

            .mobile-stats-number {
                font-size: 1.75rem;
                font-weight: 700;
                line-height: 1;
                margin: 0;
            }

            @media (max-width: 576px) {
                .card-body {
                    padding: 1.25rem;
                }

                #dataTable {
                    font-size: 0.9rem;
                }

                .display-4 {
                    font-size: 2rem;
                }

                .icon-bg {
                    width: 60px;
                    height: 60px;
                }

                .icon-bg i {
                    font-size: 1.5rem !important;
                }

                .mobile-stats-grid {
                    padding: 0;
                }

                .mobile-stats-card {
                    padding: 1rem;
                    min-height: 100px;
                }

                .stats-icon {
                    width: 40px;
                    height: 40px;
                }

                .mobile-chart-container {
                    width: 40px;
                    height: 40px;
                }

                .mobile-chart-container canvas {
                    width: 35px !important;
                    height: 35px !important;
                }

                .mobile-stats-label {
                    font-size: 0.75rem;
                }

                .mobile-stats-number {
                    font-size: 1.5rem;
                }

                .container {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }

                .py-5 {
                    padding-top: 2rem !important;
                    padding-bottom: 2rem !important;
                }

                h2 {
                    font-size: 1.5rem;
                }

                .class-info-container {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0.5rem;
                }

                .class-info-item {
                    padding: 0.3rem 0.6rem;
                    font-size: 0.8rem;
                }

                .info-value {
                    font-size: 0.8rem;
                }

                .class-info-divider {
                    display: none;
                }
            }

            /* Show divider on medium screens and up */
            @media (min-width: 768px) {
                .class-info-divider {
                    display: block !important;
                }
            }

            /* Modern Export Button */
            .modern-export-btn {
                background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
                border: 2px solid rgba(255, 255, 255, 0.3) !important;
                color: #4e73df !important;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                position: relative;
                overflow: hidden;
            }

            .modern-export-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(78, 115, 223, 0.2), transparent);
                transition: left 0.5s ease;
            }

            .modern-export-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
                border-color: rgba(255, 255, 255, 0.5) !important;
            }

            .modern-export-btn:hover::before {
                left: 100%;
            }

            .modern-export-btn:active {
                transform: translateY(0);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            /* Modern Table Card */
            .modern-table-card {
                border: none;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .bg-gradient-primary {
                background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
            }

            .modern-table {
                border-radius: 0 0 20px 20px;
                overflow: hidden;
            }

            .table-dark th {
                background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
                border: none;
                font-weight: 600;
                font-size: 0.9rem;
                padding: 1rem 0.75rem;
            }

            .table tbody tr {
                transition: all 0.3s ease;
                border: none;
            }

            .table tbody tr:hover {
                background-color: rgba(78, 115, 223, 0.05) !important;
                transform: scale(1.01);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .table tbody td {
                border: none;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                padding: 1rem 0.75rem;
                font-size: 0.9rem;
            }

            /* Table Badge Styles - Fixed Conflicts */
            .table .badge {
                padding: 0.4rem 0.6rem;
                font-size: 0.7rem;
                font-weight: 600;
                border-radius: 12px;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                color: white !important;
                border: none;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: all 0.2s ease;
            }

            .table .badge:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }

            .table .badge-success {
                background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%) !important;
            }

            .table .badge-warning {
                background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important;
                color: #212529 !important;
            }

            .table .badge-danger {
                background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%) !important;
            }

            .table .badge-secondary {
                background: linear-gradient(135deg, #6c757d 0%, #545b62 100%) !important;
            }

            .table .badge-info {
                background: linear-gradient(135deg, #36b9cc 0%, #258391 100%) !important;
            }

            /* Class Info Styles */
            .class-info-container {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .class-info-item {
                display: flex;
                align-items: center;
                gap: 0.25rem;
                background: rgba(255, 255, 255, 0.1);
                padding: 0.4rem 0.75rem;
                border-radius: 20px;
                backdrop-filter: blur(5px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .class-info-item i {
                color: rgba(255, 255, 255, 0.9);
                font-size: 0.85rem;
            }

            .info-label {
                color: rgba(255, 255, 255, 0.8);
                font-size: 0.8rem;
                font-weight: 500;
            }

            .info-value {
                color: #ffffff;
                font-size: 0.85rem;
                font-weight: 700;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            }

            .class-info-divider {
                color: rgba(255, 255, 255, 0.5);
                font-weight: 300;
                display: none;
                /* Hidden by default, show on larger screens */
            }

            /* Sticky header for long tables */
            .table-responsive thead th {
                position: sticky;
                top: 0;
                z-index: 10;
            }

            /* Animation keyframes */
            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(78, 115, 223, 0.7);
                }

                70% {
                    box-shadow: 0 0 0 10px rgba(78, 115, 223, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(78, 115, 223, 0);
                }
            }

            .modern-card:hover .bg-primary-gradient {
                animation: pulse 2s infinite;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .modern-card {
                animation: fadeInUp 0.6s ease forwards;
            }

            .modern-card:nth-child(1) {
                animation-delay: 0.1s;
            }

            .modern-card:nth-child(2) {
                animation-delay: 0.2s;
            }

            .modern-card:nth-child(3) {
                animation-delay: 0.3s;
            }

            .modern-card:nth-child(4) {
                animation-delay: 0.4s;
            }
        </style>
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