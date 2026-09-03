<?php
/* ═══ MODULE: ABSENSI — integrated mode only ═══════════════════════ */
if (empty($connection)) { header('location:./'); exit; }

date_default_timezone_set('Asia/Jakarta');

/* ── Hari map ───────────────────────────────────────────────────── */
$hari_map = [
    'Sunday'   => 'Minggu', 'Monday'  => 'Senin',   'Tuesday'  => 'Selasa',
    'Wednesday'=> 'Rabu',   'Thursday'=> 'Kamis',   'Friday'   => 'Jumat',
    'Saturday' => 'Sabtu',
];
$hari_ini = $hari_map[date('l')] ?? date('l');

/* ── Query jadwal ───────────────────────────────────────────────── */
$jadwal_data = [];
$sql_jadwal = "SELECT * FROM jadwal ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')";
$result_jadwal = $connection->query($sql_jadwal);
if ($result_jadwal && $result_jadwal->num_rows > 0) {
    while ($row_j = $result_jadwal->fetch_assoc()) { $jadwal_data[] = $row_j; }
}

/* ── Query summary hari ini ─────────────────────────────────────── */
$today = date('Y-m-d');
$summary = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'alpha' => 0, 'total_scan' => 0];
$total_siswa_aktif = 0;

$stmt_sum = $connection->prepare(
    "SELECT
        SUM(CASE WHEN status_masuk = 'Tepat Waktu' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN status_masuk = 'Terlambat'   THEN 1 ELSE 0 END) AS terlambat,
        SUM(CASE WHEN status_masuk = 'Izin'         THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN status_masuk IN ('Alpha','alpha') THEN 1 ELSE 0 END) AS alpha,
        COUNT(*) AS total_scan
    FROM absensi WHERE tanggal = ?"
);
if ($stmt_sum) {
    $stmt_sum->bind_param('s', $today);
    $stmt_sum->execute();
    $res_sum = $stmt_sum->get_result();
    if ($res_sum && ($row_sum = $res_sum->fetch_assoc())) { $summary = $row_sum; }
    $stmt_sum->close();
}
$res_total = $connection->query("SELECT COUNT(*) AS cnt FROM user WHERE status='Aktif'");
if ($res_total && ($rt = $res_total->fetch_assoc())) { $total_siswa_aktif = (int)$rt['cnt']; }

/* ── Jadwal hari ini ────────────────────────────────────────────── */
$hari_short_map = ['Senin'=>'Sen','Selasa'=>'Sel','Rabu'=>'Rab','Kamis'=>'Kam','Jumat'=>'Jum','Sabtu'=>'Sab','Minggu'=>'Min'];
$jadwal_hari_ini = null;
$jadwal_by_hari = [];
foreach ($jadwal_data as $jd) {
    $jadwal_by_hari[$jd['hari']] = $jd;
    if ($jd['hari'] === $hari_ini) { $jadwal_hari_ini = $jd; }
}

/* ── Kalender 3 hari lalu-3 hari ke depan ──────────────────────── */
$range_start = date('Y-m-d', strtotime('-15 day'));
$range_end   = date('Y-m-d', strtotime('+15 day'));

$hari_libur_rows = [];
$stmt_libur = $connection->prepare(
    "SELECT tanggal_mulai, tanggal_selesai, nama_libur, keterangan FROM hari_libur WHERE tanggal_mulai <= ? AND tanggal_selesai >= ?"
);
if ($stmt_libur) {
    $stmt_libur->bind_param('ss', $range_end, $range_start);
    $stmt_libur->execute();
    $res_libur = $stmt_libur->get_result();
    while ($res_libur && ($lib_row = $res_libur->fetch_assoc())) { $hari_libur_rows[] = $lib_row; }
    $stmt_libur->close();
}

$calendar_days = [];
$iter_date = strtotime($range_start);
$iter_end  = strtotime($range_end);
while ($iter_date <= $iter_end) {
    $ymd        = date('Y-m-d', $iter_date);
    $hari_full  = $hari_map[date('l', $iter_date)] ?? date('l', $iter_date);
    $hari_short = $hari_short_map[$hari_full] ?? substr($hari_full, 0, 3);
    $jadwal_hari = $jadwal_by_hari[$hari_full] ?? null;
    $jadwal_st = strtoupper(trim((string)($jadwal_hari['status'] ?? 'N')));
    $is_jadwal_aktif = $jadwal_hari && in_array($jadwal_st, ['Y', 'AKTIF']);
    $is_holiday = false; $holiday_reason = '';
    foreach ($hari_libur_rows as $hl) {
        if ($ymd >= $hl['tanggal_mulai'] && $ymd <= $hl['tanggal_selesai']) {
            $is_holiday = true;
            $nm = trim((string)($hl['nama_libur'] ?? ''));
            $kt = trim((string)($hl['keterangan'] ?? ''));
            $holiday_reason = $nm ?: ($kt ?: 'Libur Nasional');
            break;
        }
    }

    $status_type = 'off';
    $status_label = 'Libur Jadwal';
    $status_note = 'Tidak ada jadwal absensi';
    if ($is_holiday) {
        $status_type = 'holiday';
        $status_label = 'Hari Libur';
        $status_note = $holiday_reason ?: 'Libur Nasional';
    } elseif ($is_jadwal_aktif) {
        $status_type = 'active';
        $status_label = 'Jadwal Aktif';
        $jam_mulai = substr((string)($jadwal_hari['waktu_mulai'] ?? '00:00'), 0, 5);
        $jam_selesai = substr((string)($jadwal_hari['waktu_selesai'] ?? '00:00'), 0, 5);
        $status_note = 'Jam ' . $jam_mulai . ' - ' . $jam_selesai . ' WIB';
    } elseif ($jadwal_hari) {
        $status_note = 'Jadwal dinonaktifkan';
    }

    $calendar_days[] = [
        'date' => $ymd, 'day' => $hari_full, 'day_short' => $hari_short,
        'is_holiday' => $is_holiday, 'holiday_reason' => $holiday_reason,
        'is_today' => ($ymd === $today),
        'is_schedule_active' => $is_jadwal_aktif,
        'status_type' => $status_type,
        'status_label' => $status_label,
        'status_note' => $status_note
    ];
    $iter_date = strtotime('+1 day', $iter_date);
}

/* ── URL helpers ────────────────────────────────────────────────── */
$base        = isset($base_url) ? $base_url : './';
$content_url = $base . 'content/';
$phone_raw   = preg_replace('/[^0-9]/', '', $site_phone ?? '08151800116');
$wa_number   = '62' . ltrim($phone_raw, '0');
?>

<div class="sae-landing">

    <!-- HERO (struktur sama seperti Home) -->
    <section class="sae-hero" aria-label="Hero Absensi RFID">
        <div class="sae-hero-bg" aria-hidden="true"></div>
        <div class="sae-hero-inner">
            <div class="sae-hero-copy">
                <span class="sae-hero-kicker"><i class="fas fa-circle"></i> Layanan Absensi</span>
                <h1 class="sae-hero-title">Absensi <span class="sae-hero-accent">RFID</span></h1>
                <p class="sae-hero-subtitle">
                    Absensi berbasis Kartu RFID untuk pengelolaan kehadiran murid secara digital, akurat, dan real-time.
                </p>
                <div class="sae-tech-strip" aria-label="Status layanan absensi">
                    <?php if ($jadwal_hari_ini && in_array(strtoupper($jadwal_hari_ini['status'] ?? ''), ['Y', 'AKTIF'])): ?>
                        <span class="sae-tech-badge"><i class="fas fa-check-circle"></i> Aktif Hari Ini</span>
                    <?php else: ?>
                        <span class="sae-tech-badge"><i class="fas fa-pause-circle"></i> Libur Hari Ini</span>
                    <?php endif; ?>
                    <span class="sae-tech-badge"><i class="far fa-calendar-alt"></i> <span id="current-date"></span></span>
                    <span class="sae-tech-badge"><i class="far fa-clock"></i> <span id="current-time"></span></span>
                </div>
            </div>
            <div class="sae-hero-right">
                <div class="sae-nisn-panel">
                    <div class="sae-nisn-panel-head">
                        <h6><i class="fas fa-id-card-alt me-1"></i>RFID Scanner</h6>
                    </div>
                    <div class="sae-nisn-panel-body">
                        <div class="hero-rfid-showcase">
                            <div class="hero-rfid-animation" aria-hidden="true">
                                <div class="hero-rfid-wave"></div>
                                <div class="hero-rfid-wave"></div>
                                <div class="hero-rfid-wave"></div>
                                <div class="hero-rfid-icon"><i class="fas fa-wifi"></i></div>
                            </div>
                            <div class="hero-rfid-copy">Arahkan kartu RFID ke reader untuk memulai absensi.</div>
                        </div>
                        <p class="home-panel-note mb-0"><i class="fas fa-shield-alt"></i>Sistem siap menerima scan RFID dengan validasi waktu dan lokasi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Help Panel -->
    <div class="help-panel" id="help-panel" style="display:none">
        <strong>Shortcut Keyboard</strong>
        <small><kbd>F11</kbd> Fullscreen</small>
        <small><kbd>M</kbd> Mute/Unmute</small>
        <small><kbd>?</kbd> Bantuan</small>
        <small><kbd>R</kbd> Refresh Riwayat</small>
        <small><kbd>ESC</kbd> Tutup Bantuan</small>
    </div>

    <!-- RINGKASAN HARI INI — struktur sama dengan sae-kpi-strip di home -->
    <section class="sae-kpi-strip" aria-label="Ringkasan Kehadiran Hari Ini">
        <div class="sae-kpi-card">
            <span class="sae-kpi-icon green"><i class="fas fa-check-circle"></i></span>
            <div>
                <div class="sae-kpi-value" id="summary-hadir"><?php echo (int)($summary['hadir'] ?? 0); ?></div>
                <p class="sae-kpi-label">Hadir</p>
            </div>
        </div>
        <div class="sae-kpi-card">
            <span class="sae-kpi-icon orange"><i class="fas fa-clock"></i></span>
            <div>
                <div class="sae-kpi-value" id="summary-terlambat"><?php echo (int)($summary['terlambat'] ?? 0); ?></div>
                <p class="sae-kpi-label">Terlambat</p>
            </div>
        </div>
        <div class="sae-kpi-card">
            <span class="sae-kpi-icon blue"><i class="fas fa-file-alt"></i></span>
            <div>
                <div class="sae-kpi-value" id="summary-izin"><?php echo (int)($summary['izin'] ?? 0); ?></div>
                <p class="sae-kpi-label">Izin</p>
            </div>
        </div>
        <div class="sae-kpi-card">
            <span class="sae-kpi-icon purple"><i class="fas fa-users"></i></span>
            <div>
                <div class="sae-kpi-value"><?php echo number_format($total_siswa_aktif); ?></div>
                <p class="sae-kpi-label">Total Siswa</p>
            </div>
        </div>
    </section>

    <!-- MAIN GRID -->
    <div class="absensi-layout">
        <section class="module-absensi-calendar absensi-overview-card glass-card card">
            <div class="card-body py-2 px-2 px-md-3">
                <div class="home-insight-head mb-1">
                    <h5><i class="fas fa-calendar-week me-2"></i>Jadwal dan Hari Libur</h5>
                    <p>Tampilan terpadu dari tabel jadwal dan hari libur, dengan tanggal aktif selalu di tengah.</p>
                </div>
                <div class="calendar-frame">
                    <button class="calendar-shift-btn" id="calendarPrev" type="button" aria-label="Geser tanggal aktif ke kiri">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="calendar-window" id="calendar-window"></div>
                    <button class="calendar-shift-btn" id="calendarNext" type="button" aria-label="Geser tanggal aktif ke kanan">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

        <section class="glass-card card absensi-camera-card">
            <div class="card-body p-0">
                <div class="absensi-camera-head">
                    <i class="fas fa-camera me-2"></i>Live Camera
                </div>
                <div class="camera-container">
                    <video id="camera-video" autoplay playsinline muted></video>
                    <canvas id="camera-canvas" style="display:none"></canvas>
                    <div class="camera-placeholder" id="camera-placeholder">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Mengaktifkan kamera...</span>
                    </div>
                </div>
                <input type="text" id="rfid-input" class="rfid-hidden-input" autocomplete="off">
            </div>
        </section>

        <section class="module-absensi-history-card glass-card card">
            <div class="card-body p-0 d-flex flex-column">
                <div class="absensi-history-head">
                    <div class="home-insight-head mb-0">
                        <h5><i class="fas fa-history me-2"></i>Riwayat Absensi</h5>
                        <p>10 absensi terbaru hari ini</p>
                    </div>
                    <button class="fullscreen-btn" id="refresh-history" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="module-absensi-table table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="absensi-history-tbody">
                            <?php
                            $sql = "SELECT a.*, u.nisn, u.nama_lengkap, k.nama_kelas
                                    FROM absensi a
                                    LEFT JOIN user u ON a.user_id = u.user_id
                                    LEFT JOIN kelas k ON u.kelas = k.kelas_id
                                    WHERE a.tanggal = ?
                                    ORDER BY a.created_at DESC LIMIT 10";
                            $stmt_hist = $connection->prepare($sql);
                            if ($stmt_hist) {
                                $stmt_hist->bind_param('s', $today);
                                $stmt_hist->execute();
                                $result = $stmt_hist->get_result();
                                $stmt_hist->close();
                            } else {
                                $result = null;
                            }
                            if (!$result) {
                                echo '<tr><td colspan="5" class="text-center text-danger py-3"><i class="fas fa-exclamation-circle"></i> Error</td></tr>';
                            } elseif ($result->num_rows > 0) {
                                $no = 1;
                                while ($row = $result->fetch_assoc()) {
                                    $nama  = htmlspecialchars($row['nama_lengkap'] ?? '-');
                                    $kelas = htmlspecialchars($row['nama_kelas'] ?? '-');
                                    $sm = strtolower($row['status_masuk'] ?? '');
                                    $sp = strtolower($row['status_pulang'] ?? '');
                                    $bm_map = ['tepat waktu'=>'bg-success','terlambat'=>'bg-warning text-dark','izin'=>'bg-info','alpha'=>'bg-danger'];
                                    $bp_map = ['pulang'=>'bg-success','pulang cepat'=>'bg-warning text-dark','izin'=>'bg-info'];
                                    $bm = $bm_map[$sm] ?? 'bg-secondary';
                                    $bp = $bp_map[$sp] ?? 'bg-secondary';
                                    echo '<tr>';
                                    echo '<td>'.$no.'</td>';
                                    echo '<td title="'.$nama.'">'.$nama.'</td>';
                                    echo '<td>'.$kelas.'</td>';
                                    echo '<td><small><span class="badge '.$bm.'">'.ucfirst($sm).'</span><br>';
                                    echo '<span class="badge '.$bp.'">'.ucfirst($sp).'</span></small></td>';
                                    echo '<td><small>'.htmlspecialchars($row['jam_masuk'] ?? '-').'<br>';
                                    echo '<span class="text-muted">'.htmlspecialchars($row['jam_pulang'] ?? '-').'</span></small></td>';
                                    echo '</tr>';
                                    $no++;
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox me-1"></i>Belum ada absensi hari ini</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div class="module-absensi-alerts" id="alert-container"></div>

</div><!-- /sae-landing -->

<!-- Floating controls -->
<div class="absensi-floating-controls" aria-label="Kontrol cepat absensi">
    <button class="absensi-float-btn" id="fullscreen-btn" type="button" title="Fullscreen (F11)">
        <i class="fas fa-expand"></i>
    </button>
    <button class="absensi-float-btn" id="exit-fullscreen-btn" type="button" title="Keluar Fullscreen" style="display:none">
        <i class="fas fa-compress"></i>
    </button>
    <button class="absensi-float-btn" id="toggle-sound" type="button" title="Mute/Unmute (M)">
        <i class="fas fa-volume-up"></i>
    </button>
    <button class="absensi-float-btn" id="toggle-help" type="button" title="Bantuan (?)">
        <i class="fas fa-question-circle"></i>
    </button>
</div>

<!-- FAB -->
<div class="fab-container" id="fabContainer" role="navigation" aria-label="Menu navigasi cepat">
    <button class="fab-main pulse" id="fabMain" type="button"
        title="Menu Akses Cepat" aria-label="Buka menu akses cepat"
        aria-expanded="false" aria-haspopup="menu">
        <i class="fas fa-plus" aria-hidden="true"></i>
    </button>
    <div class="fab-items" role="menu">
        <a href="<?php echo htmlspecialchars($base . 'home'); ?>" class="fab-item realtime-item" role="menuitem" title="Beranda">
            <i class="fas fa-home" aria-hidden="true"></i><span>Home</span>
        </a>
        <a href="<?php echo htmlspecialchars($base . 'realtime'); ?>" target="_blank" rel="noopener noreferrer"
            class="fab-item microsite-item" role="menuitem" title="Monitoring Real-time">
            <i class="fas fa-bolt" aria-hidden="true"></i><span>Realtime</span>
        </a>
        <a href="https://wa.me/<?php echo $wa_number; ?>" target="_blank" rel="noopener noreferrer"
            class="fab-item whatsapp-item" role="menuitem" title="Chat WhatsApp Admin">
            <i class="fab fa-whatsapp" aria-hidden="true"></i><span>WhatsApp</span>
        </a>
        <a href="<?php echo htmlspecialchars($base . 'login'); ?>" class="fab-item login-item" role="menuitem" title="Login">
            <i class="fas fa-sign-in-alt" aria-hidden="true"></i><span>Login</span>
        </a>
        <a href="<?php echo htmlspecialchars($base . 'dashboard'); ?>" class="fab-item dashboard-item" role="menuitem" title="Dashboard">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i><span>Dashboard</span>
        </a>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content success-modal-content">
            <div class="modal-header bg-success text-white text-center">
                <h5 class="modal-title w-100"><i class="fas fa-check-circle me-2"></i>Absensi Berhasil</h5>
            </div>
            <div class="modal-body text-center">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size:4rem"></i>
                </div>
                <div class="user-photo-container mb-3">
                    <img id="modal-foto" src="<?php echo htmlspecialchars($content_url . 'avatar/avatar.jpg'); ?>" alt="Foto" class="user-photo">
                </div>
                <div class="user-info">
                    <h4 id="modal-nama" class="user-name">-</h4>
                    <div class="user-details">
                        <p>NISN: <span id="modal-nisn">-</span></p>
                        <p>Kelas: <span id="modal-kelas">-</span></p>
                        <p>Tanggal: <span id="modal-tanggal">-</span></p>
                    </div>
                    <div class="waktu-absen">
                        <span class="waktu-label">Waktu:</span>
                        <span id="modal-waktu" class="waktu-value">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success btn-lg px-5" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- ERROR MODAL -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-circle me-2"></i>Gagal Absensi</h5>
            </div>
            <div class="modal-body text-center">
                <div class="error-icon mb-3">
                    <i class="fas fa-exclamation-circle text-danger" style="font-size:3rem"></i>
                </div>
                <p id="modal-error-message">Terjadi kesalahan</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Audio Alerts -->
<audio id="sound-success" src="<?php echo htmlspecialchars($content_url . 'sound/success.wav'); ?>" preload="auto"></audio>
<audio id="sound-error"   src="<?php echo htmlspecialchars($content_url . 'sound/error.wav'); ?>"   preload="auto"></audio>

<canvas id="genderPieChart" style="display:none" width="1" height="1"></canvas>

<script>
window.absensiCalendarDays = <?php echo json_encode($calendar_days, JSON_UNESCAPED_UNICODE); ?>;
</script>

<script>
(function () {
    var fc = document.getElementById('fabContainer');
    var fm = document.getElementById('fabMain');
    if (fc && fm) {
        fm.addEventListener('click', function (e) {
            e.stopPropagation();
            fc.classList.toggle('open');
            fm.setAttribute('aria-expanded', fc.classList.contains('open'));
        });
        document.addEventListener('click', function (e) {
            if (!fc.contains(e.target)) {
                fc.classList.remove('open');
                fm.setAttribute('aria-expanded', 'false');
            }
        });
    }
})();
</script>