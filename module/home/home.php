<?php
// Selalu mode terintegrasi — header dan footer ditangani oleh header.php dan footer.php
$is_standalone = false;

// Initialize data variables with neutral fallback values (no demo data)
$total_students = 0;
$total_classes = 0;
$total_majors = 0;
$male_count = 0;
$female_count = 0;
$grade_x = 0;
$grade_xi = 0;
$grade_xii = 0;
$major_data = []; // pastikan selalu terdefinisi
$chart_raw   = []; // data detail per kelas x gender untuk chart interaktif

// Jika dalam mode terintegrasi dan ada koneksi database, ambil data real
if (!$is_standalone && isset($connection)) {
    // Function helper untuk menghitung siswa per tingkat
    function getStudentCountByGrade($connection, $grade)
    {
        try {
            // Decide which user->kelas column exists
            $userKelasCol = 'kelas_id';
            $colCheck = mysqli_query($connection, "SHOW COLUMNS FROM user LIKE 'kelas_id'");
            if (!($colCheck && mysqli_num_rows($colCheck) > 0)) {
                // fallback to `kelas` column
                $colCheck2 = mysqli_query($connection, "SHOW COLUMNS FROM user LIKE 'kelas'");
                if ($colCheck2 && mysqli_num_rows($colCheck2) > 0) {
                    $userKelasCol = 'kelas';
                }
            }

            // If kelas table exists and has tingkat_pendidikan_id, prefer counting by that
            $kelasTable = mysqli_query($connection, "SHOW TABLES LIKE 'kelas'");
            if ($kelasTable && mysqli_num_rows($kelasTable) > 0) {
                $hasTingkat = mysqli_query($connection, "SHOW COLUMNS FROM kelas LIKE 'tingkat_pendidikan_id'");
                $hasNamaKelas = mysqli_query($connection, "SHOW COLUMNS FROM kelas LIKE 'nama_kelas'");

                if ($hasTingkat && mysqli_num_rows($hasTingkat) > 0) {
                    // New logic: determine which tingkat_pendidikan_id corresponds to grade X/XI/XII
                    $gradeMap = []; // grade => array of tingkat_pendidikan_id

                    // 1) If there's a dedicated table `tingkat_pendidikan`, try to map by its name
                    $tpTable = mysqli_query($connection, "SHOW TABLES LIKE 'tingkat_pendidikan'");
                    if ($tpTable && mysqli_num_rows($tpTable) > 0) {
                        $tpQ = "SELECT tingkat_pendidikan_id, nama FROM tingkat_pendidikan";
                        $tpR = mysqli_query($connection, $tpQ);
                        if ($tpR) {
                            while ($r = $tpR->fetch_assoc()) {
                                $tid = $r['tingkat_pendidikan_id'];
                                $name = ' ' . strtoupper(trim($r['nama'] ?? '')) . ' ';
                                if (preg_match('/\b(10|X)\b/', $name)) $gradeMap['X'][] = $tid;
                                if (preg_match('/\b(11|XI)\b/', $name)) $gradeMap['XI'][] = $tid;
                                if (preg_match('/\b(12|XII)\b/', $name)) $gradeMap['XII'][] = $tid;
                            }
                        }
                    }

                    // 2) If mapping from tingkat_pendidikan table not found, inspect distinct tingkat_pendidikan_id from kelas
                    if (empty($gradeMap['X']) && empty($gradeMap['XI']) && empty($gradeMap['XII'])) {
                        $distinctQ = "SELECT DISTINCT tingkat_pendidikan_id FROM kelas WHERE tingkat_pendidikan_id IS NOT NULL ORDER BY tingkat_pendidikan_id ASC";
                        $distinctR = mysqli_query($connection, $distinctQ);
                        $tids = [];
                        if ($distinctR) {
                            while ($r = $distinctR->fetch_assoc()) {
                                $tids[] = intval($r['tingkat_pendidikan_id']);
                            }
                        }

                        // If tingkat ids look like 10/11/12, map directly
                        if (in_array(10, $tids) || in_array(11, $tids) || in_array(12, $tids)) {
                            if (in_array(10, $tids)) $gradeMap['X'][] = 10;
                            if (in_array(11, $tids)) $gradeMap['XI'][] = 11;
                            if (in_array(12, $tids)) $gradeMap['XII'][] = 12;
                        } elseif (count($tids) === 3) {
                            // fallback: assign lowest->X, middle->XI, highest->XII
                            sort($tids, SORT_NUMERIC);
                            $gradeMap['X'][] = $tids[0];
                            $gradeMap['XI'][] = $tids[1];
                            $gradeMap['XII'][] = $tids[2];
                        }
                    }

                    // If we have mapping for requested grade, count users in those tingkat_pendidikan_id
                    $ids = isset($gradeMap[$grade]) ? $gradeMap[$grade] : [];
                    if (!empty($ids)) {
                        $safeIds = array_map('intval', $ids);
                        $in = implode(',', $safeIds);
                        $query = "SELECT COUNT(*) as total FROM user u JOIN kelas k ON u." . $userKelasCol . " = k.kelas_id WHERE k.tingkat_pendidikan_id IN ($in) AND u.status='Aktif'";
                        $result = mysqli_query($connection, $query);
                        if ($result) {
                            return $result->fetch_assoc()['total'];
                        }
                    }
                }

                // Fallback: try to map using nama_kelas ranges (legacy behaviour)
                if ($hasNamaKelas && mysqli_num_rows($hasNamaKelas) > 0) {
                    $range = [40, 49]; // X
                    if ($grade == 'XI') $range = [50, 59];
                    if ($grade == 'XII') $range = [60, 69];
                    $query = "SELECT COUNT(*) as total FROM user u JOIN kelas k ON u." . $userKelasCol . " = k.kelas_id WHERE k.nama_kelas >= {$range[0]} AND k.nama_kelas <= {$range[1]} AND u.status='Aktif'";
                    $result = mysqli_query($connection, $query);
                    if ($result) {
                        return $result->fetch_assoc()['total'];
                    }
                }
            }

            // Last fallback: if user.kelas contains numeric code
            $range = [40, 49]; // X
            if ($grade == 'XI') $range = [50, 59];
            if ($grade == 'XII') $range = [60, 69];
            $query = "SELECT COUNT(*) as total FROM user WHERE kelas >= {$range[0]} AND kelas <= {$range[1]} AND status='Aktif'";
            $result = mysqli_query($connection, $query);
            if ($result) {
                return $result->fetch_assoc()['total'];
            }
        } catch (Exception $e) {
            error_log("Error in getStudentCountByGrade: " . $e->getMessage());
        }

        return 0;
    }
    // Query total siswa aktif
    $student_query = "SELECT COUNT(*) as total FROM user WHERE status='Aktif'";
    $student_result = mysqli_query($connection, $student_query);
    if ($student_result) {
        $total_students = $student_result->fetch_assoc()['total'];
    }

    // Query total kelas
    $class_query = "SELECT COUNT(DISTINCT kelas_id) as total FROM kelas";
    $class_result = mysqli_query($connection, $class_query);
    if ($class_result) {
        $total_classes = $class_result->fetch_assoc()['total'];
    }

    // Query total jurusan
    $major_query = "SELECT COUNT(DISTINCT jurusan_id) as total FROM jurusan";
    $major_result = mysqli_query($connection, $major_query);
    if ($major_result) {
        $total_majors = $major_result->fetch_assoc()['total'];
    }

    // Query jumlah siswa laki-laki
    $male_query = "SELECT COUNT(*) as total FROM user WHERE jenis_kelamin = 'Laki-laki' AND status='Aktif'";
    $male_result = mysqli_query($connection, $male_query);
    if ($male_result) {
        $male_count = $male_result->fetch_assoc()['total'];
    }

    // Query jumlah siswa perempuan
    $female_query = "SELECT COUNT(*) as total FROM user WHERE jenis_kelamin = 'Perempuan' AND status='Aktif'";
    $female_result = mysqli_query($connection, $female_query);
    if ($female_result) {
        $female_count = $female_result->fetch_assoc()['total'];
    }

    // Query siswa per tingkat menggunakan fungsi helper
    $grade_x = getStudentCountByGrade($connection, 'X');
    $grade_xi = getStudentCountByGrade($connection, 'XI');
    $grade_xii = getStudentCountByGrade($connection, 'XII');

    // Query data jurusan
    $major_data = [];
    $major_query = "SELECT j.nama_jurusan, COUNT(u.user_id) as total_siswa 
                   FROM jurusan j 
                   LEFT JOIN user u ON j.jurusan_id = u.jurusan_id AND u.status='Aktif'
                   GROUP BY j.jurusan_id, j.nama_jurusan 
                   ORDER BY total_siswa DESC";
    $major_result = mysqli_query($connection, $major_query);
    if ($major_result) {
        while ($row = $major_result->fetch_assoc()) {
            $major_data[] = $row;
        }
    }

    // Chart detail — dua query sederhana + aggregasi di PHP
    // (menghindari JOIN/GROUP BY kompleks yang bisa error di berbagai mode MySQL)
    $chart_raw = [];
    // 1) Peta kelas_id → nama_kelas
    $kelas_name_map = [];
    $kr = mysqli_query($connection, "SELECT kelas_id, nama_kelas FROM kelas");
    if ($kr) {
        while ($r = $kr->fetch_assoc()) {
            $kelas_name_map[(int)$r['kelas_id']] = $r['nama_kelas'];
        }
    }
    // 2) Data siswa aktif
    $ur = mysqli_query($connection,
        "SELECT u.jurusan_id, j.nama_jurusan, u.kelas, IFNULL(u.tingkat,'') AS tingkat, u.jenis_kelamin
         FROM user u
         JOIN jurusan j ON u.jurusan_id = j.jurusan_id
         WHERE u.status = 'Aktif'"
    );
    if ($ur) {
        $u_agg = [];
        while ($row = $ur->fetch_assoc()) {
            $kid   = trim($row['kelas']);
            $kname = isset($kelas_name_map[(int)$kid]) ? $kelas_name_map[(int)$kid] : $kid;
            $ts    = strtoupper(trim($row['tingkat']));
            if      (strpos($ts,'XII')!==false || $ts==='12') $tg='XII';
            elseif  (strpos($ts,'XI') !==false || $ts==='11') $tg='XI';
            elseif  (strpos($ts,'X')  !==false || $ts==='10') $tg='X';
            else     $tg = $ts ?: '?';
            $gkey = $row['jurusan_id'].'|'.$kid.'|'.$tg;
            if (!isset($u_agg[$gkey])) {
                $u_agg[$gkey] = [
                    'jid' => (int)$row['jurusan_id'],
                    'j'   => $row['nama_jurusan'],
                    'kid' => $kid,
                    'k'   => $kname,
                    'tg'  => $tg,
                    'L'   => 0,
                    'P'   => 0,
                ];
            }
            $jk = strtoupper(substr(trim($row['jenis_kelamin']), 0, 1));
            if     ($jk === 'L') $u_agg[$gkey]['L']++;
            elseif ($jk === 'P') $u_agg[$gkey]['P']++;
        }
        usort($u_agg, function($a, $b) {
            $c = strcmp($a['j'], $b['j']);
            return $c !== 0 ? $c : strcmp($a['k'], $b['k']);
        });
        $chart_raw = array_values($u_agg);
    }
}

// $major_data diisi dari database, hapus data dummy
if ($is_standalone) {
    // Mode standalone tidak digunakan lagi; selalu terintegrasi via index.php
}
    ?>
    <?php
        $home_login_url       = $base_url . 'login';
        $home_admin_login_url = rtrim($base_url, '/') . '/admin/';
        $home_dashboard_url   = $base_url . 'dashboard';
        $home_realtime_url    = $base_url . 'realtime';
        $home_absensi_url     = $base_url . 'absensi';
        $home_tentang_url     = $base_url . 'tentang';
        $home_privasi_url     = $base_url . 'tentang?tab=privasi';
        $home_agenda_url      = $base_url . 'agenda';
        $home_kelulusan_url   = $base_url . 'kelulusan';
        $male_percent = $total_students > 0 ? round(($male_count / $total_students) * 100, 1) : 0;
        $female_percent = $total_students > 0 ? round(($female_count / $total_students) * 100, 1) : 0;
    ?>
    <script>
        window.homeStats = {
            male_count: <?php echo json_encode($male_count); ?>,
            female_count: <?php echo json_encode($female_count); ?>,
            major_data: <?php echo isset($major_data) ? json_encode($major_data) : '{}'; ?>,
            total_students: <?php echo json_encode($total_students); ?>,
            total_classes: <?php echo json_encode($total_classes); ?>,
            total_majors: <?php echo json_encode($total_majors); ?>,
            grade_x: <?php echo json_encode($grade_x); ?>,
            grade_xi: <?php echo json_encode($grade_xi); ?>,
            grade_xii: <?php echo json_encode($grade_xii); ?>,
            chart_detail: <?php echo json_encode($chart_raw); ?>
        };
    </script>
    <div class="module-home-container">
        <div class="module-home-content">
            <div class="sae-landing">

                <!-- ═══ HERO ═══════════════════════════════════════ -->
                <section class="sae-hero">
                    <div class="sae-hero-bg" aria-hidden="true"></div>
                    <div class="sae-hero-inner">
                        <!-- Copy side -->
                        <div class="sae-hero-copy">
                            <span class="sae-hero-kicker">
                                <i class="fas fa-circle" aria-hidden="true"></i>
                                Platform Digital Sekolah
                            </span>
                            <h1 class="sae-hero-title">
                                Smart Apps<br>
                                <span class="sae-hero-accent">Education</span>
                            </h1>
                            <p class="sae-hero-subtitle">
                                Platform digital terpadu untuk administrasi sekolah, absensi, monitoring data murid, layanan publik, dan integrasi pendidikan modern.
                            </p>
                            <div class="sae-hero-cta-grid">
                                <a href="<?php echo $home_login_url; ?>" class="btn btn-primary">
                                    <i class="fas fa-user-graduate me-2"></i>Login Murid
                                </a>
                                <a href="<?php echo $home_admin_login_url; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>Login Guru
                                </a>
                            </div>
                            <div class="sae-tech-strip">
                                <span class="sae-tech-badge"><i class="fab fa-php"></i> PHP</span>
                                <span class="sae-tech-badge"><i class="fas fa-database"></i> MySQL</span>
                                <span class="sae-tech-badge"><i class="fas fa-wifi"></i> RFID</span>
                                <span class="sae-tech-badge"><i class="fas fa-cloud"></i> Cloud</span>
                                <span class="sae-tech-badge"><i class="fas fa-bolt"></i> Realtime</span>
                                <span class="sae-tech-badge"><i class="fas fa-key"></i> SSO</span>
                            </div>
                        </div>

                        <!-- Panel side -->
                        <div class="sae-hero-right">
                            <div class="sae-nisn-panel card">
                                <div class="sae-nisn-panel-head">
                                    <h6 class="mb-0"><i class="fas fa-search me-2"></i>Cek NISN Cepat</h6>
                                    <button type="button" id="openNisnInfo" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-info-circle me-1"></i>Apa itu NISN?
                                    </button>
                                </div>
                                <div class="sae-nisn-panel-body">
                                    <form class="form-nisn" method="post" action="#" autocomplete="off">
                                        <div class="form-group mb-3">
                                            <label class="module-home-form-label">Nomor NISN (10 digit)</label>
                                            <input type="number" class="module-home-form-control form-control" id="nisn-input" name="nisn" required placeholder="Contoh: 1234567890" maxlength="10">
                                        </div>
                                        <button class="btn btn-primary w-100" type="submit">
                                            <i class="fas fa-search me-2"></i>Periksa Data Murid
                                        </button>
                                    </form>
                                    <div class="home-panel-note mt-2">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>Pencarian aman untuk validasi administrasi sekolah.</span>
                                    </div>
                                </div>
                            </div>
                    </div>
                </section>

                <!-- ═══ KPI STRIP ════════════════════════════════════ -->
                <section class="sae-kpi-strip" aria-label="Statistik Sekolah">
                    <div class="sae-kpi-card">
                        <span class="sae-kpi-icon blue"><i class="fas fa-users"></i></span>
                        <div>
                            <div class="sae-kpi-value"><?php echo number_format($total_students); ?></div>
                            <p class="sae-kpi-label">Murid Aktif</p>
                        </div>
                    </div>
                    <div class="sae-kpi-card">
                        <span class="sae-kpi-icon teal"><i class="fas fa-door-open"></i></span>
                        <div>
                            <div class="sae-kpi-value"><?php echo number_format($total_classes); ?></div>
                            <p class="sae-kpi-label">Kelas</p>
                        </div>
                    </div>
                    <div class="sae-kpi-card">
                        <span class="sae-kpi-icon purple"><i class="fas fa-graduation-cap"></i></span>
                        <div>
                            <div class="sae-kpi-value"><?php echo number_format($total_majors); ?></div>
                            <p class="sae-kpi-label">Jurusan</p>
                        </div>
                    </div>
                    <div class="sae-kpi-card">
                        <span class="sae-kpi-icon orange"><i class="fas fa-venus-mars"></i></span>
                        <div>
                            <div class="sae-kpi-value" id="genderRatio"><?php echo $male_count . ' : ' . $female_count; ?></div>
                            <p class="sae-kpi-label">Rasio L/P</p>
                        </div>
                    </div>
                </section>

                <!-- ═══ FEATURES 6-GRID ══════════════════════════════ -->
                <section class="sae-features-section">
                    <div class="sae-section-header">
                        <span class="sae-section-kicker">Layanan Unggulan</span>
                        <h2 class="sae-section-title">Semua Kebutuhan Digital Sekolah dalam Satu Platform</h2>
                        <p class="sae-section-desc">Dari absensi harian hingga integrasi data nasional, SAE hadir lengkap untuk seluruh ekosistem sekolah Anda.</p>
                    </div>
                    <div class="sae-features-grid">
                        <a href="<?php echo $home_absensi_url; ?>" class="saf-card saf-blue">
                            <div class="saf-icon-wrap"><i class="fas fa-fingerprint"></i></div>
                            <h3 class="saf-title">Sistem Absensi</h3>
                            <ul class="saf-list">
                                <li>Absensi RFID / QR / Online</li>
                                <li>Monitoring hadir, terlambat, izin, sakit</li>
                                <li>Statistik realtime &amp; rekap otomatis</li>
                            </ul>
                            <span class="saf-cta">Buka Absensi <i class="fas fa-arrow-right ms-1"></i></span>
                        </a>
                        <a href="<?php echo $home_kelulusan_url; ?>" class="saf-card saf-green">
                            <div class="saf-icon-wrap"><i class="fas fa-graduation-cap"></i></div>
                            <h3 class="saf-title">Kelulusan Online</h3>
                            <ul class="saf-list">
                                <li>Pengecekan kelulusan berbasis NISN</li>
                                <li>Download surat kelulusan resmi</li>
                                <li>Aman, cepat, dan terverifikasi</li>
                            </ul>
                            <span class="saf-cta">Cek Kelulusan <i class="fas fa-arrow-right ms-1"></i></span>
                        </a>
                        <a href="<?php echo $home_agenda_url; ?>" class="saf-card saf-orange">
                            <div class="saf-icon-wrap"><i class="fas fa-calendar-alt"></i></div>
                            <h3 class="saf-title">Agenda Sekolah</h3>
                            <ul class="saf-list">
                                <li>Informasi proses pembelajaran</li>
                                <li>Jadwal akademik sekolah</li>
                                <li>Kalender kegiatan rutin</li>
                            </ul>
                            <span class="saf-cta">Lihat Agenda <i class="fas fa-arrow-right ms-1"></i></span>
                        </a>
                        <a href="<?php echo $home_realtime_url; ?>" class="saf-card saf-purple">
                            <div class="saf-icon-wrap"><i class="fas fa-wave-square"></i></div>
                            <h3 class="saf-title">Monitoring Realtime</h3>
                            <ul class="saf-list">
                                <li>Kualitas data pengguna realtime</li>
                                <li>Statistik konfirmasi kesesuaian data</li>
                                <li>Monitoring validasi berkas</li>
                            </ul>
                            <span class="saf-cta">Pantau Realtime <i class="fas fa-arrow-right ms-1"></i></span>
                        </a>
                        <a href="<?php echo $home_dashboard_url; ?>" class="saf-card saf-teal">
                            <div class="saf-icon-wrap"><i class="fas fa-layer-group"></i></div>
                            <h3 class="saf-title">Sistem Administrasi</h3>
                            <ul class="saf-list">
                                <li>Pengelolaan data murid &amp; guru</li>
                                <li>Manajemen kelas &amp; dokumen</li>
                                <li>Administrasi digital terintegrasi</li>
                            </ul>
                            <span class="saf-cta">Ke Dashboard <i class="fas fa-arrow-right ms-1"></i></span>
                        </a>
                        <a href="<?php echo $home_tentang_url; ?>" class="saf-card saf-indigo">
                            <div class="saf-icon-wrap"><i class="fas fa-plug"></i></div>
                            <h3 class="saf-title">Integrasi API &amp; SSO</h3>
                            <ul class="saf-list">
                                <li>Integrasi Dapodik / REST API</li>
                                <li>Single Sign On (SSO)</li>
                                <li>Sinkronisasi data pendidikan</li>
                            </ul>
                            <span class="saf-cta">Pelajari Lebih <i class="fas fa-arrow-right ms-1"></i></span>
                        </a>
                    </div>
                </section>

                <!-- ═══ STATISTIK GRAFIK ════════════════════════════════ -->
                <!-- genderPieChart (hidden) — used by legacy scripts.js -->
                <canvas id="genderPieChart" style="display:none" width="1" height="1"></canvas>

                <section class="home-insight-board glass-card card" id="statGrafik">
                    <div class="home-insight-head">
                        <h5><i class="fas fa-chart-bar me-2"></i>Statistik Data Murid</h5>
                        <p>Komposisi murid berdasarkan jurusan, tingkat, dan jenis kelamin. Gunakan filter untuk menyaring data.</p>
                    </div>

                    <!-- Horizontal grouped bar (L/P per jurusan) -->
                    <div class="sg-bar-wrap">
                        <canvas id="sgBarChart"></canvas>
                    </div>

                    <!-- Pie (tingkat) + Donut (jurusan) -->
                    <div class="sg-bottom-row">
                        <div class="sg-pie-wrap">
                            <div class="sg-canvas-box">
                                <canvas id="sgPieChart"></canvas>
                            </div>
                            <div id="sgPieLegend" class="sg-legend sg-legend-center"></div>
                        </div>
                        <div class="sg-donut-wrap">
                            <div class="sg-donut-inner">
                                <div class="sg-canvas-box">
                                    <canvas id="sgDonutChart"></canvas>
                                </div>
                                <div id="sgDonutLegend" class="sg-legend sg-legend-side"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="sg-filter-row">
                        <div class="sg-filter-item">
                            <select id="sgFilterJurusan" class="sg-select">
                                <option value="">Filter Jurusan</option>
                            </select>
                        </div>
                        <div class="sg-filter-item">
                            <select id="sgFilterTingkat" class="sg-select">
                                <option value="">Filter Tingkat</option>
                                <option value="X">Kelas X</option>
                                <option value="XI">Kelas XI</option>
                                <option value="XII">Kelas XII</option>
                            </select>
                        </div>
                        <div class="sg-filter-item">
                            <select id="sgFilterKelas" class="sg-select">
                                <option value="">Filter Kelas</option>
                            </select>
                        </div>
                    </div>

                    <script>
                    (function() {
                        var raw = (window.homeStats && window.homeStats.chart_detail) ? window.homeStats.chart_detail : [];

                        /* Warna — diselaraskan dengan palet admin/module */
                        var CL = '#3b82f6', CP = '#14b8a6';
                        var tgColors  = { 'X':'#3b82f6','XI':'#14b8a6','XII':'#6366f1' };
                        var jurPalette= ['#3b82f6','#14b8a6','#10b981','#fb923c','#f43f5e','#6366f1','#0ea5e9','#ec4899','#f59e0b'];
                        var barChart, pieChart, donutChart;

                        /* ─── Plugin: label di dalam bar horizontal ─── */
                        var barLabelPlugin = {
                            afterDatasetsDraw: function(chart) {
                                if (chart.config.type !== 'horizontalBar') return;
                                var ctx = chart.ctx;
                                chart.data.datasets.forEach(function(ds, di) {
                                    var meta = chart.getDatasetMeta(di);
                                    if (meta.hidden) return;
                                    meta.data.forEach(function(bar, i) {
                                        var v = ds.data[i];
                                        if (!v) return;
                                        var m = bar._model, w = Math.abs(m.x - m.base);
                                        if (w < 18) return;
                                        ctx.save();
                                        ctx.fillStyle = '#fff';
                                        ctx.font = 'bold 11px sans-serif';
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'middle';
                                        ctx.fillText(v, m.base + w / 2, m.y);
                                        ctx.restore();
                                    });
                                });
                            }
                        };

                        /* ─── Plugin: label di dalam arc (pie/donut) ── */
                        var arcLabelPlugin = {
                            afterDatasetsDraw: function(chart) {
                                if (chart.config.type !== 'pie' && chart.config.type !== 'doughnut') return;
                                var ctx = chart.ctx;
                                var meta = chart.getDatasetMeta(0);
                                var ds   = chart.data.datasets[0];
                                meta.data.forEach(function(arc, i) {
                                    var v = ds.data[i];
                                    if (!v) return;
                                    var m = arc._model;
                                    var midA = (m.startAngle + m.endAngle) / 2;
                                    var r    = (m.outerRadius + (m.innerRadius || 0)) / 2;
                                    var x    = m.x + r * Math.cos(midA);
                                    var y    = m.y + r * Math.sin(midA);
                                    ctx.save();
                                    ctx.fillStyle = '#fff';
                                    ctx.font = 'bold 12px sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(v, x, y);
                                    ctx.restore();
                                });
                            }
                        };

                        /* ─── Populate selects ─────────────────────── */
                        function populateFilters() {
                            var jSel = document.getElementById('sgFilterJurusan');
                            var jurMap = {};
                            raw.forEach(function(r){ if (!jurMap[r.jid]) jurMap[r.jid] = r.j; });
                            Object.keys(jurMap).forEach(function(id){
                                var o = document.createElement('option');
                                o.value = id; o.textContent = jurMap[id];
                                jSel.appendChild(o);
                            });
                            window._sgAllKelas = raw.map(function(r){
                                return { kid: r.kid, k: r.k, jid: r.jid, tg: r.tg };
                            });
                            rebuildKelas(window._sgAllKelas);
                        }

                        function rebuildKelas(items) {
                            var sel = document.getElementById('sgFilterKelas');
                            while (sel.options.length > 1) sel.remove(1);
                            var seen = {};
                            items.forEach(function(r){
                                if (!seen[r.kid]) {
                                    seen[r.kid] = 1;
                                    var o = document.createElement('option');
                                    o.value = r.kid; o.textContent = r.k;
                                    sel.appendChild(o);
                                }
                            });
                        }

                        /* ─── Get filtered rows ───────────────────── */
                        function filtered() {
                            var fJ = document.getElementById('sgFilterJurusan').value;
                            var fT = document.getElementById('sgFilterTingkat').value;
                            var fK = document.getElementById('sgFilterKelas').value;
                            return raw.filter(function(r){
                                if (fJ && String(r.jid) !== fJ) return false;
                                if (fT && r.tg !== fT) return false;
                                if (fK && String(r.kid) !== fK) return false;
                                return true;
                            });
                        }

                        /* ─── Aggregate ───────────────────────────── */
                        function byJurusan(rows) {
                            var m = {};
                            rows.forEach(function(r){
                                if (!m[r.j]) m[r.j] = { L:0, P:0 };
                                m[r.j].L += r.L; m[r.j].P += r.P;
                            });
                            return m;
                        }
                        function byTingkat(rows) {
                            var m = {};
                            rows.forEach(function(r){
                                var k = ['X','XI','XII'].indexOf(r.tg) >= 0 ? r.tg : '?';
                                m[k] = (m[k] || 0) + r.L + r.P;
                            });
                            var out = {};
                            ['X','XI','XII','?'].forEach(function(k){ if (m[k]) out[k] = m[k]; });
                            return out;
                        }

                        /* ─── Build bar chart ─────────────────────── */
                        function buildBar(rows) {
                            var agg = byJurusan(rows);
                            var lbs = Object.keys(agg);
                            /* tinggi dinamis: min 260px, 70px per jurusan */
                            var wrap = document.getElementById('sgBarChart').parentElement;
                            wrap.style.height = Math.max(260, lbs.length * 70) + 'px';
                            var ctx = document.getElementById('sgBarChart').getContext('2d');
                            if (barChart) barChart.destroy();
                            barChart = new Chart(ctx, {
                                type: 'horizontalBar',
                                data: {
                                    labels: lbs,
                                    datasets: [
                                        { label:'L', data: lbs.map(function(l){ return agg[l].L; }), backgroundColor: CL },
                                        { label:'P', data: lbs.map(function(l){ return agg[l].P; }), backgroundColor: CP }
                                    ]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    legend: { display:true, position:'top', labels:{ usePointStyle:true, padding:16, fontSize:12 } },
                                    tooltips: { mode:'index', intersect:false },
                                    scales: {
                                        xAxes:[{ ticks:{ beginAtZero:true }, gridLines:{ drawBorder:false } }],
                                        yAxes:[{ gridLines:{ drawBorder:false, drawOnChartArea:false } }]
                                    }
                                },
                                plugins: [barLabelPlugin]
                            });
                        }

                        /* ─── Build pie (tingkat) ─────────────────── */
                        function buildPie(rows) {
                            var agg = byTingkat(rows);
                            var lbs = Object.keys(agg);
                            var vals = lbs.map(function(k){ return agg[k]; });
                            var cols = lbs.map(function(k){ return tgColors[k] || '#ccc'; });
                            var ctx = document.getElementById('sgPieChart').getContext('2d');
                            if (pieChart) pieChart.destroy();
                            pieChart = new Chart(ctx, {
                                type: 'pie',
                                data: { labels: lbs, datasets:[{ data:vals, backgroundColor:cols, borderWidth:2, borderColor:'#fff' }] },
                                options: {
                                    responsive:true, maintainAspectRatio:false,
                                    legend:{ display:false },
                                    tooltips:{ callbacks:{ label:function(ti,d){ return ' '+d.labels[ti.index]+': '+d.datasets[0].data[ti.index]; } } }
                                },
                                plugins: [arcLabelPlugin]
                            });
                            var leg = document.getElementById('sgPieLegend');
                            leg.innerHTML = lbs.map(function(k,i){
                                return '<span class="sg-leg-item"><em style="background:'+cols[i]+'"></em>'+k+'</span>';
                            }).join('');
                        }

                        /* ─── Build donut (jurusan) ───────────────── */
                        function buildDonut(rows) {
                            var agg = byJurusan(rows);
                            var lbs = Object.keys(agg);
                            var vals = lbs.map(function(l){ return agg[l].L + agg[l].P; });
                            var cols = lbs.map(function(_,i){ return jurPalette[i % jurPalette.length]; });
                            var ctx = document.getElementById('sgDonutChart').getContext('2d');
                            if (donutChart) donutChart.destroy();
                            donutChart = new Chart(ctx, {
                                type: 'doughnut',
                                data: { labels: lbs, datasets:[{ data:vals, backgroundColor:cols, borderWidth:2, borderColor:'#fff' }] },
                                options: {
                                    responsive:true, maintainAspectRatio:false, cutoutPercentage:58,
                                    legend:{ display:false },
                                    tooltips:{ callbacks:{ label:function(ti,d){ return ' '+d.labels[ti.index]+': '+d.datasets[0].data[ti.index]; } } }
                                },
                                plugins: [arcLabelPlugin]
                            });
                            var leg = document.getElementById('sgDonutLegend');
                            leg.innerHTML = lbs.map(function(l,i){
                                return '<span class="sg-leg-item"><em style="background:'+cols[i]+'"></em>'+l+'</span>';
                            }).join('');
                        }

                        /* ─── Refresh ─────────────────────────────── */
                        function refresh() {
                            var rows = filtered();
                            buildBar(rows); buildPie(rows); buildDonut(rows);
                        }

                        /* ─── Filter events ───────────────────────── */
                        function onFilterChange() { refresh(); }

                        document.getElementById('sgFilterJurusan').addEventListener('change', function(){
                            var fJ = this.value, fT = document.getElementById('sgFilterTingkat').value;
                            rebuildKelas((window._sgAllKelas||[]).filter(function(r){
                                if (fJ && String(r.jid)!==fJ) return false;
                                if (fT && r.tg!==fT) return false;
                                return true;
                            }));
                            document.getElementById('sgFilterKelas').value = '';
                            onFilterChange();
                        });
                        document.getElementById('sgFilterTingkat').addEventListener('change', function(){
                            var fJ = document.getElementById('sgFilterJurusan').value, fT = this.value;
                            rebuildKelas((window._sgAllKelas||[]).filter(function(r){
                                if (fJ && String(r.jid)!==fJ) return false;
                                if (fT && r.tg!==fT) return false;
                                return true;
                            }));
                            document.getElementById('sgFilterKelas').value = '';
                            onFilterChange();
                        });
                        document.getElementById('sgFilterKelas').addEventListener('change', onFilterChange);

                        /* ─── Init ────────────────────────────────── */
                        if (raw.length > 0) populateFilters();
                        (function wait(){ typeof Chart!=='undefined' ? refresh() : setTimeout(wait,80); })();
                    })();
                    </script>
                </section>

                <!-- ═══ HISTORY (existing logic) ════════════════════ -->
                <section class="home-history-board glass-card card">
                    <div class="home-insight-head">
                        <h5><i class="fas fa-history me-2"></i>Riwayat Data Terbaru</h5>
                        <p>5 aktivitas data siswa terbaru yang terekam di sistem.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="module-home-table table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>NISN</th>
                                    <th>Nama</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!$is_standalone && isset($connection)) {
                                    $query = "SELECT user_id, nisn, nama_lengkap, status, date
                                              FROM user
                                              WHERE status='Aktif'
                                              ORDER BY time DESC LIMIT 5";
                                    $result = $connection->query($query);
                                    if ($result && $result->num_rows > 0) {
                                        $no = 1;
                                        while ($row = $result->fetch_assoc()) {
                                            $nisn = $row['nisn'] ?? '';
                                            $masked_nisn = strlen($nisn) > 4 ? substr($nisn, 0, 4) . str_repeat('*', strlen($nisn) - 4) : $nisn;
                                            echo '<tr>';
                                            echo '<td>' . $no++ . '</td>';
                                            echo '<td>' . htmlspecialchars($masked_nisn) . '</td>';
                                            echo '<td>' . htmlspecialchars(substr($row['nama_lengkap'] ?? '', 0, 18)) . '</td>';
                                            echo '<td><span class="badge badge-' . ($row['status'] == 'Aktif' ? 'success' : 'secondary') . '">' . htmlspecialchars($row['status'] ?? '') . '</span></td>';
                                            echo '<td>' . htmlspecialchars($row['date'] ?? '') . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>Belum ada data riwayat</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>Belum ada data riwayat</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- ═══ BOTTOM CTA ════════════════════════════════════ -->
                <section class="sae-cta-bottom glass-card card">
                    <div class="sae-cta-copy">
                        <h5>Siap menggunakan SAE?</h5>
                        <p>Masuk ke sistem atau jelajahi layanan yang tersedia untuk murid, guru, dan orang tua.</p>
                    </div>
                    <div class="sae-cta-actions">
                        <a href="<?php echo $home_login_url; ?>" class="btn btn-primary"><i class="fas fa-user-graduate me-2"></i>Login Murid</a>
                        <a href="<?php echo $home_admin_login_url; ?>" class="btn btn-outline-primary"><i class="fas fa-chalkboard-teacher me-2"></i>Login Guru</a>
                    </div>
                </section>

            </div><!-- /.sae-landing -->
        </div><!-- /.module-home-content -->

        <?php if (false): // standalone footer dihapus ?>
        <div class="module-footer">
            &copy; <?php echo date('Y'); ?>
            <a href="#"><?php echo htmlspecialchars($site_name ?? 'Smart Apps Education'); ?></a>
            &nbsp;|&nbsp; Developed by <a href="https://s.id/smakpalapik" target="_blank" rel="noopener noreferrer">SMAKPALAPIK</a>
        </div>
        <?php endif; ?>
    </div><!-- /.module-home-container -->

    <!-- NISN Information Modal styles moved to CSS; debug button removed -->
    <div class="modal fade" id="nisnInfoModal" tabindex="-1" aria-labelledby="nisnInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content glass-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="nisnInfoModalLabel">
                        <i class="fas fa-id-card me-2"></i>Apa itu NISN?
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="(function(){var m=document.getElementById('nisnInfoModal'); if(m){m.classList.remove('show'); m.style.display='none'; m.setAttribute('aria-hidden','true');} document.querySelectorAll('.modal-backdrop').forEach(function(b){ if(b&&b.parentNode) b.parentNode.removeChild(b); }); document.body.classList.remove('modal-open'); if(document.body.style&&document.body.style.overflow==='hidden'){document.body.style.overflow='';} })();"></button>
                </div>
                <div class="modal-body">
                    <div class="nisn-modal-content">
                        <h3 class="modal-title-main"><i class="fas fa-id-card-alt me-2"></i> Apa itu NISN?</h3>
                        <section class="nisn-section">
                            <div class="nisn-section-title"><i class="fas fa-lightbulb me-1"></i> Pengertian NISN</div>
                            <div class="nisn-section-desc">
                                NISN (<b>Nomor Induk Siswa Nasional</b>) adalah nomor identitas unik yang diberikan kepada setiap murid di Indonesia oleh Kementerian Pendidikan dan Kebudayaan. NISN memudahkan sekolah dan pemerintah dalam mengelola data murid secara nasional.
                            </div>
                        </section>
                        <section class="nisn-section">
                            <div class="nisn-section-title"><i class="fas fa-check-circle me-1"></i> Manfaat NISN</div>
                            <ul class="nisn-list">
                                <li><span class="text-success fw-bold"><i class="fas fa-user-check me-1"></i> Identifikasi unik</span> setiap murid</li>
                                <li><span class="text-success fw-bold"><i class="fas fa-shield-alt me-1"></i> Validasi</span> data resmi</li>
                                <li><span class="text-primary fw-bold"><i class="fas fa-tasks me-1"></i> Administrasi</span> sekolah yang mudah</li>
                                <li><span class="text-primary fw-bold"><i class="fas fa-search-location me-1"></i> Pelacakan</span> riwayat pendidikan</li>
                                <li><span class="text-warning fw-bold"><i class="fas fa-exchange-alt me-1"></i> Mutasi</span> antar sekolah</li>
                                <li><span class="text-warning fw-bold"><i class="fas fa-unlock-alt me-1"></i> Akses layanan</span> publik</li>
                            </ul>
                        </section>
                        <section class="nisn-section">
                            <div class="nisn-section-title"><i class="fas fa-info-circle me-1"></i> Cara Menggunakan</div>
                            <ol class="nisn-list">
                                <li>Masukkan <b>NISN</b> Anda (10 digit) pada form di sebelah kiri</li>
                                <li>Klik <b>"Periksa"</b> untuk memvalidasi data NISN</li>
                                <li>Lihat hasil validasi dan informasi lengkap profil murid</li>
                            </ol>
                        </section>
                        <section class="nisn-section">
                            <div class="nisn-section-title"><i class="fas fa-lock me-1"></i> Keamanan Data</div>
                            <div class="alert alert-info nisn-alert">
                                <i class="fas fa-shield-alt me-1"></i> Data pribadi Anda dijamin keamanannya dan hanya digunakan untuk keperluan administrasi sekolah sesuai kebijakan privasi yang berlaku.
                            </div>
                        </section>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="dontShowNisnInfo">
                        <label class="form-check-label" for="dontShowNisnInfo" style="font-size:0.98rem;">Jangan tampilkan lagi</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="closeNisnInfoModal" onclick="(function(){var m=document.getElementById('nisnInfoModal'); if(m){m.classList.remove('show'); m.style.display='none'; m.setAttribute('aria-hidden','true');} document.querySelectorAll('.modal-backdrop').forEach(function(b){ if(b&&b.parentNode) b.parentNode.removeChild(b); }); document.body.classList.remove('modal-open'); if(document.body.style&&document.body.style.overflow==='hidden'){document.body.style.overflow='';} })();"><i class="fas fa-times me-1"></i> Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fab-container" id="fabContainer" role="navigation" aria-label="Menu navigasi cepat">
        <button class="fab-main pulse"
            id="fabMain"
            type="button"
            title="Menu Akses Cepat - Klik untuk membuka"
            aria-label="Buka menu akses cepat"
            aria-expanded="false"
            aria-haspopup="menu">
            <i class="fas fa-plus" aria-hidden="true"></i>
        </button>
        <div class="fab-items" role="menu">
            <a href="<?php echo htmlspecialchars(rtrim($site_url ?? $base_url, '/')); ?>/realtime"
                target="_blank"
                rel="noopener noreferrer"
                class="fab-item realtime-item"
                role="menuitem"
                title="Monitoring Real-time SAE - Buka di tab baru"
                aria-label="Akses monitoring real-time sistem SAE">
                <i class="fas fa-bolt" aria-hidden="true"></i>
                <span>Realtime SAE</span>
            </a>
            <a href="https://s.id/smakpalapik"
                target="_blank"
                rel="noopener noreferrer"
                class="fab-item microsite-item"
                role="menuitem"
                title="Microsite Sekolah - Informasi Lengkap"
                aria-label="Kunjungi microsite sekolah">
                <i class="fas fa-globe" aria-hidden="true"></i>
                <span>Microsite</span>
            </a>
            <a href="https://wa.me/62<?php echo ltrim(preg_replace('/[^0-9]/','',$site_phone??'08151800116'),'0'); ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="fab-item whatsapp-item"
                role="menuitem"
                title="Chat WhatsApp Admin - Hubungi untuk info lebih lanjut"
                aria-label="Hubungi admin via WhatsApp">
                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                <span>WhatsApp</span>
            </a>
            <a href="mailto:<?php echo htmlspecialchars($site_email ?? 'smkn1pagelaran@gmail.com'); ?>"
                class="fab-item email-item"
                role="menuitem"
                title="Email Sekolah - Kirim email kepada kami"
                aria-label="Kirim email ke sekolah">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                <span>Email</span>
            </a>
            <a href="<?php echo $base_url; ?>tentang"
                class="fab-item microsite-item"
                role="menuitem"
                title="Tentang aplikasi SAE"
                aria-label="Buka halaman tentang aplikasi">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>Tentang</span>
            </a>
            <a href="<?php echo $base_url; ?>tentang?tab=privasi"
                class="fab-item email-item"
                role="menuitem"
                title="Privasi dan kebijakan aplikasi"
                aria-label="Buka halaman privasi dan kebijakan aplikasi">
                <i class="fas fa-user-shield" aria-hidden="true"></i>
                <span>Privasi</span>
            </a>
            <a href="<?php echo $base_url; ?>login"
                class="fab-item login-item"
                role="menuitem"
                title="Login ke Dashboard Administrasi"
                aria-label="Masuk ke sistem dashboard">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                <span>Login</span>
            </a>
            <a href="<?php echo $base_url; ?>dashboard"
                class="fab-item dashboard-item"
                role="menuitem"
                title="Dashboard Murid"
                aria-label="Akses dashboard murid">
                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <?php
    if (false) {
        // Mode standalone tidak digunakan lagi
    } else {
        // Mode terintegrasi
?>    <!-- Chart.js is loaded by the site's footer (v2.9.4) to stay compatible with Argon.js -->
<?php
    }
