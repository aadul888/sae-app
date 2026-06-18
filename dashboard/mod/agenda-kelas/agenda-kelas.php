<?php
/**
 * Agenda Kelas - Dashboard Siswa (Koordinator)
 * Tab 1: Input Jadwal Mapel (jam ke 1-11)
 * Tab 2: Input Agenda Harian
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
$my_user_id = intval($me['user_id']);

// Load mapel list
$mapel_list = $connection->query("SELECT m.mapel_id, m.nama_mapel, m.kode_mapel, a.fullname, a.gelar_depan, a.gelar_belakang, a.admin_id as guru_id
    FROM agenda_mapel m
    LEFT JOIN admin a ON m.guru_id = a.admin_id
    WHERE m.aktif='Y'
    ORDER BY m.nama_mapel ASC");
$mapels = [];
while ($row = $mapel_list->fetch_assoc()) {
    $row['nama_guru'] = $row['fullname']
        ? trim(($row['gelar_depan'] ? $row['gelar_depan'] . ' ' : '') . $row['fullname'] . ($row['gelar_belakang'] ? ', ' . $row['gelar_belakang'] : ''))
        : '';
    $mapels[] = $row;
}

// Load existing jadwal for this class
$jadwal_data = [];
$q_jadwal = $connection->query("SELECT j.*, m.nama_mapel, a.fullname, a.gelar_depan, a.gelar_belakang
    FROM agenda_jadwal j
    LEFT JOIN agenda_mapel m ON j.mapel_id = m.mapel_id
    LEFT JOIN admin a ON m.guru_id = a.admin_id
    WHERE j.kelas_id='$kelas_id'
    ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_ke ASC");
while ($row = $q_jadwal->fetch_assoc()) {
    $jadwal_data[$row['hari']][$row['jam_ke']] = $row;
}

// Today's agenda
$tanggal_hari_ini = date('Y-m-d');
$tanggal_aktif = $tanggal_hari_ini;
if (!empty($_GET['tanggal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'])) {
    $req_tanggal = $_GET['tanggal'];
    if (strtotime($req_tanggal) <= strtotime($tanggal_hari_ini)) {
        $tanggal_aktif = $req_tanggal;
    }
}

$hari_map = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hari_aktif = $hari_map[date('l', strtotime($tanggal_aktif))];
$tanggal_display = $hari_aktif . ', ' . date('d M Y', strtotime($tanggal_aktif));

// Get jadwal for active day
$jadwal_hari_ini = $jadwal_data[$hari_aktif] ?? [];

// Get existing agenda for active date
$agenda_data = [];
$q_agenda = $connection->query("SELECT ak.*, m.nama_mapel, a.fullname as guru_nama, a.gelar_depan, a.gelar_belakang,
    (SELECT status FROM agenda_edit_request WHERE agenda_id=ak.agenda_id ORDER BY id DESC LIMIT 1) as edit_status
    FROM agenda_kelas ak
    LEFT JOIN agenda_mapel m ON ak.mapel_id = m.mapel_id
    LEFT JOIN admin a ON ak.guru_id = a.admin_id
    WHERE ak.kelas_id='$kelas_id' AND ak.tanggal='$tanggal_aktif' AND ak.status != 'dihapus'
    ORDER BY ak.jam_ke ASC");
while ($row = $q_agenda->fetch_assoc()) {
    $agenda_data[$row['jam_ke']] = $row;
}

$hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'jadwal' ? 'jadwal' : 'agenda';

// Load active days from jadwal (kehadiran) table
$jadwal_aktif = [];
$q_jdw_aktif = $connection->query("SELECT hari, status FROM jadwal");
if ($q_jdw_aktif) {
    while ($rj = $q_jdw_aktif->fetch_assoc()) $jadwal_aktif[$rj['hari']] = $rj['status'];
}
// Check hari_libur for today
$is_libur_today = false;
$libur_nama = '';
$stmt_libur = $connection->prepare("SELECT nama_libur FROM hari_libur WHERE ? BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
if ($stmt_libur) {
    $stmt_libur->bind_param('s', $tanggal_aktif);
    $stmt_libur->execute();
    $res_libur = $stmt_libur->get_result();
    if ($res_libur && $res_libur->num_rows > 0) {
        $is_libur_today = true;
        $libur_nama = $res_libur->fetch_assoc()['nama_libur'] ?: 'Hari Libur';
    }
    $stmt_libur->close();
}
// Filter hari_list to only active days
$hari_list_filtered = [];
foreach ($hari_list as $h) {
    if (!isset($jadwal_aktif[$h]) || $jadwal_aktif[$h] === 'Y') {
        $hari_list_filtered[] = $h;
    }
}
$hari_list = $hari_list_filtered;

// Load students in this class for attendance
$q_siswa = $connection->query("SELECT user_id, nama_lengkap FROM user WHERE kelas='$kelas_id' ORDER BY nama_lengkap ASC");
$siswa_list = [];
while ($s = $q_siswa->fetch_assoc()) $siswa_list[] = $s;

// Group jadwal for active day by mapel_id (for agenda tab)
$grouped_jadwal_today = [];
foreach ($jadwal_hari_ini as $jam => $jdw) {
    $mid = $jdw['mapel_id'];
    if (!isset($grouped_jadwal_today[$mid])) {
        $nama_guru_g = trim(($jdw['gelar_depan'] ? $jdw['gelar_depan'] . ' ' : '') . $jdw['fullname'] . ($jdw['gelar_belakang'] ? ', ' . $jdw['gelar_belakang'] : ''));
        $grouped_jadwal_today[$mid] = [
            'mapel' => $jdw,
            'nama_guru' => $nama_guru_g,
            'jam_list' => [],
            'agendas' => [],
        ];
    }
    $grouped_jadwal_today[$mid]['jam_list'][] = $jam;
    if (isset($agenda_data[$jam])) {
        $grouped_jadwal_today[$mid]['agendas'][$jam] = $agenda_data[$jam];
    }
}
?>

<div class="home-dashboard-container" style="padding-bottom:140px;">
    <input type="hidden" id="kelas-id" value="<?= $kelas_id ?>">
    <input type="hidden" id="my-user-id" value="<?= $my_user_id ?>">

    <!-- Header -->
    <div class="absensi-header">
        <div class="d-flex align-items-center mb-2">
            <div>
                <h4 class="mb-0 font-weight-bold text-white">Agenda Kelas</h4>
                <small class="text-white-50"><?= $kelas_nama ?></small>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="mx-3 mt-3">
        <ul class="nav nav-pills nav-fill" id="agendaTab">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'agenda' ? 'active' : '' ?>" data-toggle="tab" href="#tab-agenda">
                    <i class="fas fa-book-open mr-1"></i>Agenda Harian
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'jadwal' ? 'active' : '' ?>" data-toggle="tab" href="#tab-jadwal">
                    <i class="fas fa-calendar-alt mr-1"></i>Jadwal Mapel
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content mx-3 mt-3" id="agendaTabContent">
        <!-- ============ TAB: Agenda Harian ============ -->
        <div class="tab-pane fade <?= $active_tab === 'agenda' ? 'show active' : '' ?>" id="tab-agenda">
            <!-- Date Selector -->
            <div class="mb-3">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
                    <input type="date" id="tanggal-agenda" class="form-control" value="<?= $tanggal_aktif ?>" max="<?= $tanggal_hari_ini ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary btn-sm" id="btn-load-agenda"><i class="fas fa-sync-alt mr-1"></i>Muat</button>
                    </div>
                </div>
                <small class="text-muted"><?= $tanggal_display ?></small>
            </div>

            <?php if ($is_libur_today): ?>
                <div class="alert alert-info"><i class="fas fa-calendar-times mr-1"></i><strong>Hari Libur:</strong> <?= htmlspecialchars($libur_nama) ?> (<?= $tanggal_display ?>)</div>
            <?php elseif (empty($jadwal_hari_ini) && !in_array($hari_aktif, ['Sabtu','Minggu'])): ?>
                <div class="alert alert-warning"><i class="fas fa-info-circle mr-1"></i>Belum ada jadwal untuk hari <?= $hari_aktif ?>. Silakan isi jadwal terlebih dahulu di tab "Jadwal Mapel".</div>
            <?php elseif ($hari_aktif === 'Minggu'): ?>
                <div class="alert alert-info"><i class="fas fa-calendar-times mr-1"></i>Hari Minggu tidak ada jadwal pelajaran.</div>
            <?php else: ?>
                <div id="agenda-list">
                <?php foreach ($grouped_jadwal_today as $mid => $group):
                    $jdw = $group['mapel'];
                    $jam_list = $group['jam_list'];
                    $nama_guru_jdw = $group['nama_guru'];
                    $jam_label = count($jam_list) === 1
                        ? 'Jam ke-' . $jam_list[0]
                        : 'Jam ke-' . implode(', ', $jam_list);

                    // Check if ALL jams in group are filled
                    $all_filled = count($group['agendas']) === count($jam_list);
                    $any_filled = count($group['agendas']) > 0;

                    // Use first agenda for display if filled
                    $first_agenda = !empty($group['agendas']) ? reset($group['agendas']) : null;
                    $has_edit_request = $first_agenda && ($first_agenda['edit_status'] ?? '') === 'pending';
                    $has_approved_edit = $first_agenda && ($first_agenda['edit_status'] ?? '') === 'approved';
                    $is_locked = $all_filled && !$has_approved_edit;
                ?>
                    <div class="card mb-2 agenda-card <?= $all_filled ? 'border-left-success' : 'border-left-warning' ?>" data-mapel-id="<?= $mid ?>">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge badge-primary mr-1"><?= $jam_label ?></span>
                                    <strong><?= htmlspecialchars($jdw['nama_mapel']) ?></strong>
                                    <?php if ($nama_guru_jdw): ?>
                                    <br><small class="text-muted"><i class="fas fa-user-tie mr-1"></i><?= htmlspecialchars($nama_guru_jdw) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($all_filled): ?>
                                        <?php
                                        $badge_cls = 'success'; $badge_txt = 'Hadir';
                                        if ($first_agenda['kehadiran_guru'] === 'Tidak Hadir') { $badge_cls = 'danger'; $badge_txt = 'Tidak Hadir'; }
                                        elseif ($first_agenda['kehadiran_guru'] === 'Tidak Hadir + Tugas') { $badge_cls = 'warning'; $badge_txt = 'Tidak Hadir + Tugas'; }
                                        ?>
                                        <span class="badge badge-<?= $badge_cls ?>"><?= $badge_txt ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Belum diisi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($all_filled): ?>
                                <hr class="my-2">
                                <small><strong>Materi:</strong> <?= htmlspecialchars($first_agenda['keterangan_materi'] ?: '-') ?></small>
                                <?php if (!empty($first_agenda['foto_bukti'])): ?>
                                    <br><a href="../content/agenda/<?= $first_agenda['foto_bukti'] ?>" target="_blank" class="text-primary"><small><i class="fas fa-image mr-1"></i>Lihat Foto</small></a>
                                <?php endif; ?>
                                <br><small class="text-muted">Hadir: <?= $first_agenda['jumlah_siswa_hadir'] ?> | Tidak Hadir: <?= $first_agenda['jumlah_siswa_tidak_hadir'] ?></small>
                                <?php if ($has_edit_request): ?>
                                    <br><small class="text-info"><i class="fas fa-hourglass-half mr-1"></i>Menunggu persetujuan edit</small>
                                <?php elseif ($is_locked): ?>
                                    <br><button class="btn btn-xs btn-outline-warning mt-1 btn-request-edit-agenda" data-agenda-id="<?= $first_agenda['agenda_id'] ?>"><i class="fas fa-paper-plane mr-1"></i>Minta Edit</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <hr class="my-2">
                                <button class="btn btn-sm btn-primary btn-isi-agenda"
                                    data-jam-list="<?= htmlspecialchars(json_encode($jam_list)) ?>"
                                    data-mapel-id="<?= $jdw['mapel_id'] ?>"
                                    data-mapel="<?= htmlspecialchars($jdw['nama_mapel']) ?>"
                                    data-guru="<?= htmlspecialchars($nama_guru_jdw) ?>"
                                    data-guru-id="<?= $jdw['guru_id'] ?? 0 ?>">
                                    <i class="fas fa-pen mr-1"></i>Isi Agenda
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============ TAB: Jadwal Mapel ============ -->
        <div class="tab-pane fade <?= $active_tab === 'jadwal' ? 'show active' : '' ?>" id="tab-jadwal">
            <div class="alert alert-info mb-3"><i class="fas fa-info-circle mr-1"></i>Atur jadwal mata pelajaran untuk setiap hari.</div>

            <!-- Day selector -->
            <div class="mb-3">
                <?php
                // If hari_aktif not in filtered list, use first available day
                $hari_jadwal_selected = in_array($hari_aktif, $hari_list) ? $hari_aktif : (isset($hari_list[0]) ? $hari_list[0] : '');
                ?>
                <select id="jadwal-hari" class="form-control">
                    <?php foreach ($hari_list as $h): ?>
                        <option value="<?= $h ?>" <?= $h === $hari_jadwal_selected ? 'selected' : '' ?>><?= $h ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="jadwal-form">
                <?php foreach ($hari_list as $h):
                    $has_jadwal = !empty($jadwal_data[$h]);
                ?>
                <div class="jadwal-day-panel" id="panel-<?= $h ?>" style="display:<?= $h === $hari_jadwal_selected ? 'block' : 'none' ?>;">
                    <table class="table table-sm table-bordered jadwal-table">
                        <thead class="thead-light">
                            <tr>
                                <th width="55" class="text-center">Jam</th>
                                <th>Mapel & Guru</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php for ($j = 1; $j <= 11; $j++):
                            $existing = $jadwal_data[$h][$j] ?? null;
                            $existing_guru = $existing ? trim(($existing['gelar_depan'] ? $existing['gelar_depan'] . ' ' : '') . $existing['fullname'] . ($existing['gelar_belakang'] ? ', ' . $existing['gelar_belakang'] : '')) : '';
                        ?>
                            <tr>
                                <td class="text-center align-middle"><strong><?= $j ?></strong></td>
                                <td>
                                    <select class="form-control jadwal-mapel-select" data-hari="<?= $h ?>" data-jam="<?= $j ?>" <?= $has_jadwal ? 'disabled' : '' ?>>
                                        <option value="">-- Kosong --</option>
                                        <?php foreach ($mapels as $mp): ?>
                                            <option value="<?= $mp['mapel_id'] ?>"
                                                data-guru="<?= htmlspecialchars($mp['nama_guru']) ?>"
                                                data-nama-mapel="<?= htmlspecialchars($mp['nama_mapel']) ?>"
                                                <?= ($existing && $existing['mapel_id'] == $mp['mapel_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mp['kode_mapel'] ?: $mp['nama_mapel']) ?><?= $mp['nama_guru'] ? ' - ' . htmlspecialchars($mp['nama_guru']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($existing): ?>
                                    <small class="text-muted jadwal-guru-info" id="guru-<?= $h ?>-<?= $j ?>"><?= htmlspecialchars($existing['nama_mapel']) ?><?= $existing_guru ? ' &middot; ' . htmlspecialchars($existing_guru) : '' ?></small>
                                    <?php else: ?>
                                    <small class="text-muted jadwal-guru-info" id="guru-<?= $h ?>-<?= $j ?>"></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                    <div class="d-flex gap-2 mt-2 mb-3">
                        <?php if ($has_jadwal): ?>
                        <button class="btn btn-sm btn-warning btn-edit-jadwal mr-2" data-hari="<?= $h ?>">
                            <i class="fas fa-edit mr-1"></i>Edit Jadwal <?= $h ?>
                        </button>
                        <button class="btn btn-sm btn-success btn-simpan-jadwal mr-2" data-hari="<?= $h ?>" style="display:none;">
                            <i class="fas fa-save mr-1"></i>Simpan Jadwal <?= $h ?>
                        </button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-success btn-simpan-jadwal mr-2" data-hari="<?= $h ?>">
                            <i class="fas fa-save mr-1"></i>Simpan Jadwal <?= $h ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Isi Agenda -->
<div class="modal fade" id="modalAgenda" tabindex="-1" data-backdrop="static" data-bs-backdrop="static" data-keyboard="false" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-book-open mr-2"></i>Input Agenda</h5>
                <button type="button" class="close text-white btn-close-modal"><span>&times;</span></button>
            </div>
            <form id="formAgenda" class="form-upload" enctype="multipart/form-data">
                <input type="hidden" name="jam_list" id="agenda_jam_list">
                <input type="hidden" name="mapel_id" id="agenda_mapel_id">
                <input type="hidden" name="guru_id" id="agenda_guru_id">
                <input type="hidden" name="tanggal" id="agenda_tanggal">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Mata Pelajaran</label>
                        <input type="text" id="agenda_mapel_nama" class="form-control form-control-lg" readonly style="background:#f7fafc;">
                    </div>
                    <div class="form-group" id="group_guru_nama">
                        <label class="font-weight-bold">Guru</label>
                        <input type="text" id="agenda_guru_nama" class="form-control form-control-lg" readonly style="background:#f7fafc;">
                    </div>
                    <div class="form-group" id="group_kehadiran_guru">
                        <label class="font-weight-bold">Kehadiran Guru <span class="text-danger">*</span></label>
                        <select name="kehadiran_guru" id="agenda_kehadiran" class="form-control form-control-lg" required>
                            <option value="Hadir">Hadir</option>
                            <option value="Tidak Hadir">Tidak Hadir</option>
                            <option value="Tidak Hadir + Tugas">Tidak Hadir + Memberikan Tugas</option>
                        </select>
                    </div>

                    <!-- Kehadiran Siswa -->
                    <div class="form-group">
                        <label class="font-weight-bold">Kehadiran Siswa <span class="text-danger">*</span></label>
                        <div class="mb-2">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="radio_semua_hadir" name="mode_kehadiran" class="custom-control-input" value="semua_hadir" checked>
                                <label class="custom-control-label" for="radio_semua_hadir">Semua Hadir</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="radio_ada_tidak_hadir" name="mode_kehadiran" class="custom-control-input" value="ada_tidak_hadir">
                                <label class="custom-control-label" for="radio_ada_tidak_hadir">Ada Yang Tidak Hadir</label>
                            </div>
                        </div>
                        <div id="panel-siswa-tidak-hadir" style="display:none;">
                            <small class="text-muted mb-2 d-block">Centang siswa yang <strong>tidak hadir</strong>:</small>
                            <div class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                                <?php foreach ($siswa_list as $s): ?>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input cb-siswa-absen" id="cb_<?= $s['user_id'] ?>" name="siswa_tidak_hadir[]" value="<?= $s['user_id'] ?>">
                                    <label class="custom-control-label" for="cb_<?= $s['user_id'] ?>"><?= htmlspecialchars($s['nama_lengkap']) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted mt-1 d-block">Tidak hadir: <strong id="count-tidak-hadir">0</strong> siswa</small>
                        </div>
                        <input type="hidden" name="jumlah_siswa_hadir" id="hid_siswa_hadir" value="<?= count($siswa_list) ?>">
                        <input type="hidden" name="jumlah_siswa_tidak_hadir" id="hid_siswa_tidak" value="0">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Keterangan / Materi <span class="text-danger">*</span></label>
                        <textarea name="keterangan_materi" class="form-control" rows="3" required placeholder="Tuliskan materi yang disampaikan..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Foto Bukti <small class="text-muted">(maks 5MB)</small></label>
                        <input type="file" name="foto_bukti" id="agenda_foto" class="form-control-file" accept="image/*">
                        <div id="foto-preview" class="mt-2" style="display:none;">
                            <img id="foto-preview-img" src="" class="img-fluid rounded" style="max-height:200px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-close-modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-agenda"><i class="fas fa-save mr-1"></i>Simpan Agenda</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Siswa data for JS -->
<script>var SISWA_TOTAL = <?= count($siswa_list) ?>;</script>

<style>
.agenda-card { border-radius: 12px; overflow: hidden; }
.agenda-card.border-left-success { border-left: 4px solid #1cc88a !important; }
.agenda-card.border-left-warning { border-left: 4px solid #f6c23e !important; }
.btn-xs { padding: 2px 8px; font-size: 0.75rem; }
.nav-pills .nav-link { border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
.nav-pills .nav-link.active { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
.jadwal-table { font-size: 0.88rem; }
.jadwal-table select { height: auto !important; min-height: 42px; font-size: 0.85rem; padding: 6px 28px 6px 10px !important; white-space: normal !important; overflow: hidden; text-overflow: ellipsis; width: 100% !important; max-width: 100%; }
.jadwal-table td { padding: 6px 8px !important; vertical-align: middle !important; }
.jadwal-guru-info { display:block; margin-top:2px; font-size:.75rem; }
#jadwal-hari { height: 44px !important; font-size: 0.95rem; }
.form-control-lg { font-size: 1rem !important; padding: 0.6rem 0.75rem !important; }
#modalAgenda .modal-body { max-height: 70vh; overflow-y: auto; }
#modalAgenda .modal-content { max-height: 90vh; }
@media (min-width: 768px) {
    .home-dashboard-container { padding-bottom: 80px !important; }
}
</style>
