<?php

/**
 * Sidebar Navigation Module
 * Handles role-based menu access control
 */

// Determine admin level(s) to use for menu visibility. Prefer DB-backed values
// using `ADMIN_KEY` (epm-encoded admin_id). Fall back to cookie values.
$level_id = '';
$tugas_csv = '';
$role_menu = array();
$is_operator_superadmin = false;
if (!empty($_COOKIE['ADMIN_KEY'])) {
    $admin_id = @epm_decode($_COOKIE['ADMIN_KEY']);
    $admin_id = anti_injection($admin_id);
    if (!empty($admin_id)) {
        $qadm = "SELECT level_id, tugas_tambahan FROM admin WHERE admin_id='" . intval($admin_id) . "' LIMIT 1";
        $radm = $connection->query($qadm);
        if ($radm && $radm->num_rows > 0) {
            $adm = $radm->fetch_assoc();
            $level_id = isset($adm['level_id']) ? $adm['level_id'] : '';
            $tugas_csv = isset($adm['tugas_tambahan']) ? $adm['tugas_tambahan'] : '';
        }
    }
}
// Fallback to cookies
if ($level_id === '') {
    $level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
}
if ($tugas_csv === '' && !empty($_COOKIE['tugas_tambahan'])) {
    $tugas_csv = $_COOKIE['tugas_tambahan'];
}

// Build levels array and query role table for all matching level_ids
$levels_to_check = array();
if ($level_id !== '') $levels_to_check[] = intval($level_id);
if (!empty($tugas_csv)) {
    $parts = preg_split('/\s*,\s*/', trim($tugas_csv));
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $levels_to_check[] = intval($p);
    }
}
$levels_to_check = array_values(array_unique($levels_to_check));

if (count($levels_to_check) > 0) {
    $in_op = implode(',', array_map('intval', $levels_to_check));
    $qop = "SELECT level_id FROM level WHERE level_id IN ($in_op) AND level_nama='Operator Sekolah' LIMIT 1";
    $rop = $connection->query($qop);
    if ($rop && $rop->num_rows > 0) {
        $is_operator_superadmin = true;
    }
}

if (count($levels_to_check) > 0) {
    $in = implode(',', array_map('intval', $levels_to_check));
    $query_role = "SELECT modul_id, lihat FROM role WHERE level_id IN ($in)";
    $result_role = $connection->query($query_role);
    if ($result_role) {
        while ($row = $result_role->fetch_assoc()) {
            // OR-merge: if any matching role row has lihat='Y', the menu is visible
            if (!empty($row['modul_id'])) {
                if (!isset($role_menu[$row['modul_id']]) || strtoupper($row['lihat']) == 'Y') {
                    $role_menu[$row['modul_id']] = strtoupper($row['lihat']) == 'Y' ? 'Y' : 'N';
                }
            }
        }
    }
}

/**
 * Helper function untuk cek akses menu
 * @param int $modul_id ID modul yang akan dicek
 * @return bool
 */
function can_see($modul_id)
{
    global $role_menu, $is_operator_superadmin;
    if ($is_operator_superadmin) {
        return true;
    }
    return !empty($role_menu[$modul_id]) && $role_menu[$modul_id] == 'Y';
}

/**
 * Helper function untuk cek apakah minimal satu modul dalam array diizinkan
 * @param array $modul_ids
 * @return bool
 */
function can_see_any($modul_ids = array())
{
    foreach ($modul_ids as $id) {
        if (can_see($id)) {
            return true;
        }
    }
    return false;
}

// Hitung notifikasi untuk sidebar
$count_belum_validasi = 0;
$count_edit_identitas = 0;
// Jumlah siswa yang punya berkas tetapi belum divalidasi (kosong atau NULL)
if (isset($connection)) {
    $q = $connection->query("SELECT COUNT(DISTINCT user_id) AS cnt FROM berkas WHERE TRIM(COALESCE(validasi_berkas, '')) = ''");
    if ($q && $r = $q->fetch_assoc()) {
        $count_belum_validasi = intval($r['cnt']);
    }

    // Jumlah pengajuan edit identitas yang masih dalam proses (Hanya 'Dalam Proses')
    $q2 = $connection->query("SELECT COUNT(*) AS cnt FROM perubahan WHERE status_pengajuan = 'Dalam Proses'");
    if ($q2 && $r2 = $q2->fetch_assoc()) {
        $count_edit_identitas = intval($r2['cnt']);
    }
}

// Tentukan logo yang akan digunakan
$logo_path = 'logoweb1.png';
if (!empty($site_logo1)) {
    $logo_path = $site_logo1;
}
// Tambahkan cache busting
$logo_path_url = @filemtime("../content/$logo_path") > 0 ? "../content/$logo_path?v=" . filemtime("../content/$logo_path") : "../content/$logo_path?v=" . time();
?>

<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white" id="sidenav-main">
    <div class="scrollbar-inner">
        <!-- Brand Header -->
        <div class="sidenav-header d-flex align-items-center">
            <a class="navbar-brand" href="./">
                <img src="<?php echo htmlspecialchars($logo_path_url); ?>" class="navbar-brand-img" alt="Logo">
            </a>
            <div class="ml-auto">
                <!-- Sidenav toggler -->
                <div class="sidenav-toggler d-none d-xl-block" data-action="sidenav-unpin" data-target="#sidenav-main">
                    <div class="sidenav-toggler-inner">
                        <i class="sidenav-toggler-line"></i>
                        <i class="sidenav-toggler-line"></i>
                        <i class="sidenav-toggler-line"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="navbar-inner">
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                <ul class="navbar-nav">

                    <?php if (can_see(1)) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="./">
                                <i class="fas fa-tachometer-alt text-primary"></i>
                                <span class="nav-link-text">Dashboard</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (can_see(2)) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="./portal-gtk">
                                <i class="fas fa-chalkboard-teacher text-info"></i>
                                <span class="nav-link-text">Portal GTK</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (can_see_any(array(3, 4, 5, 6, 7, 8, 45, 46))) { ?>
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="#navbar-administrasi" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-administrasi">
                                <i class="fas fa-school text-info"></i>
                                <span class="nav-link-text">Administrasi</span>
                            </a>
                            <div class="collapse" id="navbar-administrasi">
                                <ul class="nav nav-sm flex-column ml-3">
                                    <?php if (can_see(3)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./user">
                                                <i class="fas fa-user-check text-success"></i>
                                                Murid Aktif
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(4)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./user-tidak-aktif">
                                                <i class="fas fa-user-times text-danger"></i>
                                                Murid Tidak Aktif
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(45)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./guru">
                                                <i class="fas fa-user-tie text-primary"></i>
                                                Guru Aktif
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(46)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./guru-tidak-aktif">
                                                <i class="fas fa-user-slash text-warning"></i>
                                                Guru Tidak Aktif
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(5)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./berkas">
                                                <i class="fas fa-file-alt text-success"></i>
                                                Berkas/Dokumen Murid
                                                <?php if (!empty($count_belum_validasi) && intval($count_belum_validasi) > 0) { ?>
                                                    <span class="badge badge-pill badge-danger ml-2"><?php echo intval($count_belum_validasi); ?></span>
                                                <?php } ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(6)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./edit-identitas">
                                                <i class="fas fa-id-card text-purple"></i>
                                                Usulan Perubahan Data
                                                <?php if (!empty($count_edit_identitas) && intval($count_edit_identitas) > 0) { ?>
                                                    <span class="badge badge-pill badge-warning ml-2"><?php echo intval($count_edit_identitas); ?></span>
                                                <?php } ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(7)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./jurusan">
                                                <i class="fas fa-graduation-cap text-warning"></i>
                                                Jurusan
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(8)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./kelas">
                                                <i class="fas fa-chalkboard text-info"></i>
                                                Kelas/Rombel
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (can_see_any(array(9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19))) { ?>
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="#navbar-absensi" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-absensi">
                                <i class="fas fa-calendar-check text-primary"></i>
                                <span class="nav-link-text">Absensi Digital</span>
                            </a>
                            <div class="collapse" id="navbar-absensi">
                                <ul class="nav nav-sm flex-column ml-3">
                                    <?php if (can_see(10)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./absensi-izin">
                                                <i class="fas fa-envelope-open-text text-warning"></i>
                                                Kelola Izin Absensi
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(11)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./absensi-registrasi">
                                                <i class="fas fa-user-plus text-success"></i>
                                                Registrasi RFID
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(12)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./jadwal">
                                                <i class="fas fa-clock text-info"></i>
                                                Jadwal
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(13)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./libur">
                                                <i class="fas fa-calendar-times text-danger"></i>
                                                Hari Libur
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(14)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./absensi-lokasi">
                                                <i class="fas fa-map-marker-alt text-danger"></i>
                                                Lokasi Absen
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(15)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./cetak-absensi">
                                                <i class="fas fa-print text-primary"></i>
                                                Cetak Absensi Manual
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see_any(array(17, 18, 19))) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link collapsed" href="#navbar-absensi-laporan" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-absensi-laporan">
                                                <i class="fas fa-chart-bar text-cyan"></i>
                                                Laporan Absensi
                                            </a>
                                            <div class="collapse" id="navbar-absensi-laporan">
                                                <ul class="nav nav-sm flex-column ml-3">
                                                    <?php if (can_see(17)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./laporan-absensi">
                                                                <i class="fas fa-file-alt text-primary"></i>
                                                                Hari Ini
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(18)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./laporan-absensi-kelas">
                                                                <i class="fas fa-chalkboard-teacher text-info"></i>
                                                                Per Kelas
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(19)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./laporan-absensi-siswa">
                                                                <i class="fas fa-user text-success"></i>
                                                                Per Murid
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (can_see(23)) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="./e-izin">
                                <i class="fas fa-file-signature text-info"></i>
                                <span class="nav-link-text">E-Izin</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (can_see_any(array(20, 21, 22, 47, 48))) { ?>
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="#navbar-kurikulum" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-kurikulum">
                                <i class="fas fa-book-open text-indigo"></i>
                                <span class="nav-link-text">Kurikulum</span>
                            </a>
                            <div class="collapse" id="navbar-kurikulum">
                                <ul class="nav nav-sm flex-column ml-3">
                                    <?php if (can_see(47)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./pembelajaran">
                                                <i class="fas fa-book-reader text-primary"></i>
                                                Pembelajaran
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see_any(array(20, 21, 22))) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link collapsed" href="#navbar-kurikulum-agenda" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-kurikulum-agenda">
                                                <i class="fas fa-calendar-alt text-info"></i>
                                                Agenda Kelas
                                            </a>
                                            <div class="collapse" id="navbar-kurikulum-agenda">
                                                <ul class="nav nav-sm flex-column ml-3">
                                                    <?php if (can_see(20)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./agenda-ref">
                                                                <i class="fas fa-list text-primary"></i>
                                                                Referensi Agenda
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(21)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./agenda-jadwal">
                                                                <i class="fas fa-calendar-alt text-info"></i>
                                                                Jadwal Kelas
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(22)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./agenda-laporan">
                                                                <i class="fas fa-chart-line text-success"></i>
                                                                Laporan Agenda
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(48)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link collapsed" href="#navbar-kurikulum-kelulusan" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-kurikulum-kelulusan">
                                                <i class="fas fa-user-graduate text-danger"></i>
                                                Kelulusan
                                            </a>
                                            <div class="collapse" id="navbar-kurikulum-kelulusan">
                                                <ul class="nav nav-sm flex-column ml-3">
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="./skl-settings">
                                                            <i class="fas fa-bullhorn text-success"></i>
                                                            Pengaturan Rilis
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="./skl-import">
                                                            <i class="fas fa-file-import text-warning"></i>
                                                            Import SKL
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="./skl-history">
                                                            <i class="fas fa-history text-info"></i>
                                                            History
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="./skl-ijazah">
                                                            <i class="fas fa-file-pdf text-danger"></i>
                                                            E-Ijazah
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (can_see_any(array(28, 29, 30, 31, 32, 33, 34, 35, 36))) { ?>
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="#navbar-kesiswaan" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-kesiswaan">
                                <i class="fas fa-user-shield text-warning"></i>
                                <span class="nav-link-text">Kesiswaan</span>
                            </a>
                            <div class="collapse" id="navbar-kesiswaan">
                                <ul class="nav nav-sm flex-column ml-3">
                                    <?php if (can_see_any(array(28, 29, 30, 31, 32))) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link collapsed" href="#navbar-kesiswaan-pip" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-kesiswaan-pip">
                                                <i class="fas fa-money-bill-wave text-success"></i>
                                                Program Indonesia Pintar
                                            </a>
                                            <div class="collapse" id="navbar-kesiswaan-pip">
                                                <ul class="nav nav-sm flex-column ml-3">
                                                    <?php if (can_see(28)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./kriteria-pip">
                                                                <i class="fas fa-clipboard-list text-warning"></i>
                                                                Kriteria
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(29)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./usulan-pip-semua">
                                                                <i class="fas fa-list text-info"></i>
                                                                Usulan Semua
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(30)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./usulan-pip-diterima">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                                Usulan Diterima
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(31)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./usulan-pip-ranking">
                                                                <i class="fas fa-sort-numeric-down text-warning"></i>
                                                                Usulan Ranking
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(32)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./history-pip">
                                                                <i class="fas fa-history text-secondary"></i>
                                                                Riwayat
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see_any(array(33, 34, 35, 36))) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link collapsed" href="#navbar-kesiswaan-tatib" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-kesiswaan-tatib">
                                                <i class="fas fa-gavel text-primary"></i>
                                                Tata Tertib
                                            </a>
                                            <div class="collapse" id="navbar-kesiswaan-tatib">
                                                <ul class="nav nav-sm flex-column ml-3">
                                                    <?php if (can_see(33)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./poin-tatib">
                                                                <i class="fas fa-book text-primary"></i>
                                                                Ayat &amp; Pasal
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(34)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./poin">
                                                                <i class="fas fa-list text-warning"></i>
                                                                Data Pelanggaran
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(35)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./poin-panggil">
                                                                <i class="fas fa-phone text-danger"></i>
                                                                Pemanggilan
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if (can_see(36)) { ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" href="./poin-sanggah">
                                                                <i class="fas fa-hand-paper text-info"></i>
                                                                Sanggahan
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (can_see_any(array(24, 25, 26, 27))) { ?>
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="#navbar-sarpras" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-sarpras">
                                <i class="fas fa-boxes text-teal"></i>
                                <span class="nav-link-text">Sarpras</span>
                            </a>
                            <div class="collapse" id="navbar-sarpras">
                                <ul class="nav nav-sm flex-column ml-3">
                                    <?php if (can_see(24)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./inv-master">
                                                <i class="fas fa-cogs text-info"></i>
                                                Referensi Data
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(25)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./inv-kelas">
                                                <i class="fas fa-th-list text-primary"></i>
                                                Inventaris Kelas
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(26)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./inv-pinjam">
                                                <i class="fas fa-exchange-alt text-warning"></i>
                                                Peminjaman Inventaris
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(27)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./inv-report">
                                                <i class="fas fa-flag text-danger"></i>
                                                Laporan Inventaris
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (can_see_any(array(49, 50, 51))) { ?>
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="#navbar-hubin" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-hubin">
                                <i class="fas fa-handshake text-success"></i>
                                <span class="nav-link-text">Hubin</span>
                            </a>
                            <div class="collapse" id="navbar-hubin">
                                <ul class="nav nav-sm flex-column ml-3">
                                    <?php if (can_see(49)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./buku-tamu">
                                                <i class="fas fa-book-open text-primary"></i>
                                                Buku Tamu
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(50)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./tamu-referensi">
                                                <i class="fas fa-sitemap text-info"></i>
                                                Referensi Tamu
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(51)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./tarik-peserta-pkl">
                                                <i class="fas fa-user-graduate text-warning"></i>
                                                Tarik Peserta PKL
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (can_see_any(array(37, 38, 39, 40, 41, 43, 44))) { ?>
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="#navbar-pengaturan" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-pengaturan">
                                <i class="ni ni-settings-gear-65 text-pink"></i>
                                <span class="nav-link-text">Pengaturan</span>
                            </a>
                            <div class="collapse" id="navbar-pengaturan">
                                <ul class="nav nav-sm flex-column ml-3">
                                    <?php if (can_see(37)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./pengaturan">
                                                <i class="fas fa-cog text-pink"></i>
                                                Pengaturan Web
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(38)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./admin">
                                                <i class="fas fa-users-cog text-primary"></i>
                                                Admin
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(43)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./hak-akses">
                                                <i class="fas fa-user-lock text-warning"></i>
                                                Hak Akses
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(39)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./menu-siswa">
                                                <i class="fas fa-list text-info"></i>
                                                Menu/Fitur Murid
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(40)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./pembaharuan">
                                                <i class="fas fa-bullhorn text-info"></i>
                                                Pemberitahuan
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (can_see(41)) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="./sync">
                                                <i class="fas fa-sync text-success"></i>
                                                Tarik Data Dapodik
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                </ul>
                <hr class="my-3">

                <ul class="navbar-nav mb-md-3">
                    <li class="nav-item">
                        <a class="nav-link" href="./tentang">
                            <i class="fas fa-info-circle text-info"></i>
                            <span class="nav-link-text">Tentang</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./privasi-kebijakan">
                            <i class="fas fa-user-shield text-primary"></i>
                            <span class="nav-link-text">Privasi &amp; Kebijakan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./logout">
                            <i class="fas fa-sign-out-alt text-danger"></i>
                            <span class="nav-link-text">Keluar</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>