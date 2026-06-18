<?php
/**
 * Absensi Kelas - Manual Attendance by Koordinator Kelas
 * Supports: today + past dates, lock/unlock via admin token
 */
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
}
if (!isset($_COOKIE['siswa'])) {
    header('location:../');
    exit();
}

$siswa_key = convert("decrypt", $_COOKIE['siswa']);
$q_me = $connection->query("SELECT user_id, nama_lengkap, kelas, kelas_nama, koordinator FROM user WHERE user_id='" . intval($siswa_key) . "' LIMIT 1");
if (!$q_me || $q_me->num_rows == 0) {
    echo '<div class="alert alert-danger m-4">Data user tidak ditemukan.</div>';
    exit;
}
$me = $q_me->fetch_assoc();
if ($me['koordinator'] != 1) {
    echo '<div class="alert alert-warning m-4"><i class="fas fa-lock mr-2"></i>Hanya koordinator kelas yang dapat mengakses fitur ini.</div>';
    exit;
}

$kelas_id = intval($me['kelas']);
$kelas_nama = htmlspecialchars($me['kelas_nama']);

// Determine active date (today or selected)
$tanggal_hari_ini = date('Y-m-d');
$tanggal_aktif = $tanggal_hari_ini;
if (!empty($_GET['tanggal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'])) {
    $req_tanggal = $_GET['tanggal'];
    if (strtotime($req_tanggal) <= strtotime($tanggal_hari_ini)) {
        $tanggal_aktif = $req_tanggal;
    }
}
$is_past_date = ($tanggal_aktif !== $tanggal_hari_ini);

// Get schedule for the active date
$hari_map = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hari_aktif = $hari_map[date('l', strtotime($tanggal_aktif))];
$q_jadwal = $connection->query("SELECT * FROM jadwal WHERE hari='$hari_aktif' AND status='Y' LIMIT 1");
$jadwal = $q_jadwal && $q_jadwal->num_rows > 0 ? $q_jadwal->fetch_assoc() : null;

// Check holiday
$q_libur = $connection->query("SELECT * FROM hari_libur WHERE '$tanggal_aktif' BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
$is_holiday = ($q_libur && $q_libur->num_rows > 0);

// Get students + attendance for active date
$q_siswa = $connection->query("SELECT u.user_id, u.nisn, u.nama_lengkap, u.rfid, u.avatar,
    a.id as absensi_id, a.jam_masuk, a.status_masuk, a.jam_pulang, a.status_pulang,
    a.kehadiran, a.metode, a.foto_masuk, a.keterangan
    FROM user u
    LEFT JOIN absensi a ON u.user_id = a.user_id AND a.tanggal = '$tanggal_aktif'
    WHERE u.kelas = '$kelas_id' AND u.status = 'Aktif'
    ORDER BY u.nama_lengkap ASC");

$students = [];
$has_any_manual = false;
while ($row = $q_siswa->fetch_assoc()) {
    $students[] = $row;
    if (!empty($row['kehadiran']) && $row['metode'] !== 'rfid') $has_any_manual = true;
}

// Determine lock state
$has_saved_data = false;
foreach ($students as $s) {
    if (!empty($s['kehadiran']) && $s['metode'] !== 'rfid') {
        $has_saved_data = true;
        break;
    }
}

// Check edit request status for this kelas+tanggal
$edit_request = null;
$q_req = $connection->query("SELECT * FROM absensi_edit_request WHERE kelas_id='$kelas_id' AND tanggal='$tanggal_aktif' ORDER BY id DESC LIMIT 1");
if ($q_req && $q_req->num_rows > 0) {
    $edit_request = $q_req->fetch_assoc();
}
$has_approved = ($edit_request && $edit_request['status'] === 'approved');
$has_pending = ($edit_request && $edit_request['status'] === 'pending');

// Locked if data already saved AND no approved edit request
$is_locked = ($is_past_date || $has_saved_data) && !$has_approved;

// Stats
$total = count($students);
$hadir = 0; $izin_count = 0; $sakit_count = 0; $alpha_count = 0; $belum = 0;
foreach ($students as $s) {
    if ($s['kehadiran'] == 'Hadir') $hadir++;
    elseif ($s['kehadiran'] == 'Izin') $izin_count++;
    elseif ($s['kehadiran'] == 'Sakit') $sakit_count++;
    elseif ($s['kehadiran'] == 'Alpha') $alpha_count++;
    else $belum++;
}

// Format display date
$tanggal_display = $hari_aktif . ', ' . date('d M Y', strtotime($tanggal_aktif));
?>

<div class="absensi-kelas-container">
    <input type="hidden" id="kelas-id" value="<?= $kelas_id ?>">
    <input type="hidden" id="is-locked" value="<?= $is_locked ? '1' : '0' ?>">
    <input type="hidden" id="is-past-date" value="<?= $is_past_date ? '1' : '0' ?>">

    <!-- Header -->
    <div class="absensi-header">
        <div class="d-flex align-items-center mb-2">
            <a href="?mod=kelas-q" class="btn-back mr-3"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h4 class="mb-0 font-weight-bold text-white">Absensi Kelas</h4>
                <small class="text-white-50"><?= $kelas_nama ?> &bull; <?= $tanggal_display ?></small>
            </div>
        </div>
    </div>

    <?php if ($is_holiday): ?>
        <div class="alert alert-info mx-3 mt-3"><i class="fas fa-calendar-times mr-2"></i>Hari libur. Absensi tidak tersedia.</div>
    <?php elseif (!$jadwal): ?>
        <div class="alert alert-warning mx-3 mt-3"><i class="fas fa-clock mr-2"></i>Tidak ada jadwal aktif untuk hari <?= $hari_aktif ?>.</div>
    <?php else: ?>

    <!-- Schedule Info -->
    <div class="schedule-bar">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="fas fa-clock mr-1"></i> Masuk: <strong><?= substr($jadwal['waktu_mulai'], 0, 5) ?></strong></span>
            <span><i class="fas fa-clock mr-1"></i> Pulang: <strong><?= substr($jadwal['waktu_selesai'], 0, 5) ?></strong></span>
            <span class="badge badge-light" id="live-clock"><?= date('H:i:s') ?></span>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card stat-hadir"><div class="stat-num" id="stat-hadir"><?= $hadir ?></div><div class="stat-label">Hadir</div></div>
        <div class="stat-card stat-izin"><div class="stat-num" id="stat-izin"><?= $izin_count ?></div><div class="stat-label">Izin</div></div>
        <div class="stat-card stat-sakit"><div class="stat-num" id="stat-sakit"><?= $sakit_count ?></div><div class="stat-label">Sakit</div></div>
        <div class="stat-card stat-alpha"><div class="stat-num" id="stat-alpha"><?= $alpha_count ?></div><div class="stat-label">Alpha</div></div>
        <div class="stat-card stat-belum"><div class="stat-num" id="stat-belum"><?= $belum ?></div><div class="stat-label">Belum</div></div>
    </div>

    <!-- Date Selector -->
    <div class="date-selector mx-3 mb-2">
        <div class="input-group input-group-sm">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
            <input type="date" id="tanggal-absensi" class="form-control" value="<?= $tanggal_aktif ?>" max="<?= $tanggal_hari_ini ?>">
            <div class="input-group-append">
                <button class="btn btn-primary btn-sm" id="btn-load-date"><i class="fas fa-sync-alt mr-1"></i>Muat</button>
            </div>
        </div>
    </div>

    <?php if ($is_past_date): ?>
    <div class="warning-past-date mx-3 mb-2">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <span class="warning-highlight">Ini adalah data absensi hari sebelumnya.</span>
    </div>
    <?php endif; ?>

    <?php if ($has_pending): ?>
    <div class="alert alert-info mx-3 mb-2" style="border-radius:10px; font-size:0.85rem;">
        <i class="fas fa-hourglass-half mr-1"></i>
        Permintaan edit sedang <strong>menunggu persetujuan admin</strong>.
    </div>
    <?php endif; ?>

    <?php if ($has_approved): ?>
    <div class="alert alert-success mx-3 mb-2" style="border-radius:10px; font-size:0.85rem;">
        <i class="fas fa-check-circle mr-1"></i>
        Permintaan edit <strong>disetujui</strong>. Silakan edit dan simpan.
    </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="action-bar mx-3 mb-2">
        <?php if ($is_locked && !$has_pending): ?>
            <button class="btn btn-sm btn-warning" id="btn-request-edit">
                <i class="fas fa-paper-plane mr-1"></i>Minta Izin Edit
            </button>
        <?php elseif (!$is_locked): ?>
            <button class="btn btn-sm btn-info" id="btn-daring">
                <i class="fas fa-laptop mr-1"></i>Pembelajaran Daring
            </button>
            <button class="btn btn-sm btn-success" id="btn-hadir-semua">
                <i class="fas fa-check-double mr-1"></i>Hadir Semua
            </button>
            <button class="btn btn-sm btn-primary" id="btn-simpan-absensi">
                <i class="fas fa-save mr-1"></i>Simpan
            </button>
        <?php endif; ?>
    </div>

    <!-- Student List -->
    <div class="student-list mx-3" id="student-list">
        <?php foreach ($students as $i => $s):
            $has_rfid = !empty($s['jam_masuk']) && $s['metode'] === 'rfid';
            $has_absensi = !empty($s['kehadiran']);
            $current_status = '';
            if ($s['kehadiran'] == 'Hadir') $current_status = 'Hadir';
            elseif ($s['kehadiran'] == 'Izin') $current_status = 'Izin';
            elseif ($s['kehadiran'] == 'Sakit') $current_status = 'Sakit';
            elseif ($s['kehadiran'] == 'Alpha') $current_status = 'Alpha';

            $metode_icon = '';
            if ($s['metode'] === 'rfid') $metode_icon = '<i class="fas fa-id-card text-primary" title="RFID"></i>';
            elseif ($s['metode'] === 'manual') $metode_icon = '<i class="fas fa-hand-paper text-warning" title="Manual"></i>';
        ?>
        <div class="student-row <?= $has_absensi ? 'done' : '' ?>" data-user-id="<?= $s['user_id'] ?>">
            <div class="student-top">
                <div class="student-info">
                    <div class="student-num"><?= $i + 1 ?></div>
                    <div class="student-avatar">
                        <?php if (!empty($s['avatar']) && $s['avatar'] != 'default.png'): ?>
                            <img src="../content/avatar/<?= $s['avatar'] ?>" alt="">
                        <?php else: ?>
                            <div class="avatar-initial"><?= strtoupper(substr($s['nama_lengkap'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="student-detail">
                        <div class="student-name"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                        <div class="student-meta">
                            <small class="text-muted"><?= $s['nisn'] ?></small>
                            <?= $metode_icon ?>
                            <?php if (!empty($s['jam_masuk'])): ?>
                                <small class="text-success"><i class="fas fa-sign-in-alt"></i> <?= substr($s['jam_masuk'], 0, 5) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($s['jam_pulang'])): ?>
                                <small class="text-danger"><i class="fas fa-sign-out-alt"></i> <?= substr($s['jam_pulang'], 0, 5) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($s['keterangan'])): ?>
                                <br><span class="badge badge-info" style="font-size:0.7rem;"><i class="fas fa-laptop mr-1"></i><?= htmlspecialchars($s['keterangan']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!$has_rfid): ?>
            <div class="student-radios" data-user-id="<?= $s['user_id'] ?>">
                <?php $disabled = $is_locked ? 'disabled' : ''; ?>
                <label class="radio-pill pill-hadir <?= $current_status == 'Hadir' ? 'active' : '' ?>">
                    <input type="radio" name="status_<?= $s['user_id'] ?>" value="Hadir" <?= $current_status == 'Hadir' ? 'checked' : '' ?> <?= $disabled ?>>
                    <span>Hadir</span>
                </label>
                <label class="radio-pill pill-sakit <?= $current_status == 'Sakit' ? 'active' : '' ?>">
                    <input type="radio" name="status_<?= $s['user_id'] ?>" value="Sakit" <?= $current_status == 'Sakit' ? 'checked' : '' ?> <?= $disabled ?>>
                    <span>Sakit</span>
                </label>
                <label class="radio-pill pill-izin <?= $current_status == 'Izin' ? 'active' : '' ?>">
                    <input type="radio" name="status_<?= $s['user_id'] ?>" value="Izin" <?= $current_status == 'Izin' ? 'checked' : '' ?> <?= $disabled ?>>
                    <span>Izin</span>
                </label>
                <label class="radio-pill pill-alpha <?= $current_status == 'Alpha' ? 'active' : '' ?>">
                    <input type="radio" name="status_<?= $s['user_id'] ?>" value="Alpha" <?= $current_status == 'Alpha' ? 'checked' : '' ?> <?= $disabled ?>>
                    <span>Alpha</span>
                </label>
            </div>
            <?php else: ?>
            <div class="student-rfid-status">
                <span class="badge badge-success badge-pill"><i class="fas fa-id-card mr-1"></i><?= $s['status_masuk'] ?: 'Hadir' ?> via RFID</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<style>
.absensi-kelas-container { max-width: 600px; margin: 0 auto; padding-bottom: 100px; }
.absensi-header { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); padding: 20px; border-radius: 0 0 20px 20px; }
.btn-back { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; }
.btn-back:hover { background: rgba(255,255,255,0.3); color: #fff; }
.schedule-bar { background: #f0f3ff; margin: 12px; padding: 10px 15px; border-radius: 12px; font-size: 0.85rem; color: #4e73df; }
.stats-row { display: flex; gap: 8px; padding: 0 12px; margin-bottom: 12px; overflow-x: auto; }
.stat-card { flex: 1; min-width: 60px; text-align: center; padding: 10px 5px; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.stat-num { font-size: 1.3rem; font-weight: 700; }
.stat-label { font-size: 0.65rem; color: #888; text-transform: uppercase; }
.stat-hadir .stat-num { color: #1cc88a; }
.stat-izin .stat-num { color: #36b9cc; }
.stat-sakit .stat-num { color: #f6c23e; }
.stat-alpha .stat-num { color: #e74a3b; }
.stat-belum .stat-num { color: #858796; }
.student-list { display: flex; flex-direction: column; gap: 8px; }
.student-row { background: #fff; border-radius: 12px; padding: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); transition: all 0.2s; }
.student-row.done { background: #f8fdf8; }
.student-top { display: flex; align-items: center; }
.student-info { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.student-num { font-size: 0.75rem; color: #aaa; width: 20px; text-align: center; flex-shrink: 0; }
.student-avatar { width: 36px; height: 36px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #e9ecef; }
.student-avatar img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initial { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #4e73df; color: #fff; font-weight: 600; font-size: 0.85rem; }
.student-detail { min-width: 0; flex: 1; }
.student-name { font-weight: 600; font-size: 0.85rem; color: #2d3748; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.student-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.student-meta small { font-size: 0.7rem; }
.student-radios { display: flex; gap: 6px; margin-top: 8px; padding-left: 30px; }
.radio-pill { flex: 1; text-align: center; margin: 0; cursor: pointer; }
.radio-pill input[type="radio"] { display: none; }
.radio-pill span { display: block; padding: 6px 4px; border-radius: 8px; font-size: 0.7rem; font-weight: 600; border: 2px solid #e2e8f0; color: #718096; background: #f7fafc; transition: all 0.2s ease; user-select: none; }
.radio-pill:active span { transform: scale(0.95); }
.pill-hadir.active span, .pill-hadir input:checked + span { border-color: #1cc88a; background: #e6f9f0; color: #1cc88a; }
.pill-sakit.active span, .pill-sakit input:checked + span { border-color: #f6c23e; background: #fef9e7; color: #d4a017; }
.pill-izin.active span, .pill-izin input:checked + span { border-color: #36b9cc; background: #e8f8fa; color: #36b9cc; }
.pill-alpha.active span, .pill-alpha input:checked + span { border-color: #e74a3b; background: #fde8e6; color: #e74a3b; }
.radio-pill input:disabled + span { opacity: 0.6; cursor: not-allowed; }
.student-rfid-status { margin-top: 8px; padding-left: 30px; display: flex; align-items: center; gap: 6px; }
.date-selector .form-control { font-size: 0.85rem; }
.action-bar { display: flex; gap: 8px; justify-content: flex-end; }
.action-bar .btn { border-radius: 10px; font-size: 0.8rem; font-weight: 600; padding: 6px 14px; }
.warning-past-date { background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 8px 12px; font-size: 0.8rem; color: #dc3545; font-weight: 600; }
.warning-highlight { background: #ffeeba; padding: 2px 6px; border-radius: 4px; }
.locked-overlay { position: relative; }
.lock-badge { display: inline-flex; align-items: center; gap: 4px; background: #f0f0f0; color: #888; font-size: 0.7rem; padding: 3px 8px; border-radius: 6px; margin-top: 8px; margin-left: 30px; }
@media (max-width: 576px) {
    .absensi-kelas-container { max-width: 100%; }
    .stat-card { padding: 8px 3px; }
    .stat-num { font-size: 1.1rem; }
    .student-name { font-size: 0.8rem; }
    .student-radios { padding-left: 26px; gap: 4px; }
    .radio-pill span { font-size: 0.65rem; padding: 5px 2px; }
}
</style>