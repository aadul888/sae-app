<?php

/**
 * Sidebar Navigation Module
 * Struktur disesuaikan dengan matriks menu utama / submenu sekolah.
 */

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
if ($level_id === '') $level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
if ($tugas_csv === '' && !empty($_COOKIE['tugas_tambahan'])) $tugas_csv = $_COOKIE['tugas_tambahan'];

$levels_to_check = array();
if ($level_id !== '') $levels_to_check[] = intval($level_id);
if (!empty($tugas_csv)) {
    $parts = preg_split('/\s*,\s*/', trim($tugas_csv));
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $levels_to_check[] = intval($p);
    }
}
$levels_to_check = array_values(array_unique($levels_to_check));

if (count($levels_to_check) > 0) {
    $in_op = implode(',', array_map('intval', $levels_to_check));
    $qop = "SELECT level_id FROM level WHERE level_id IN ($in_op) AND level_nama='Operator Sekolah' LIMIT 1";
    $rop = $connection->query($qop);
    if ($rop && $rop->num_rows > 0) $is_operator_superadmin = true;
}

if (count($levels_to_check) > 0) {
    $in = implode(',', array_map('intval', $levels_to_check));
    $query_role = "SELECT modul_id, lihat FROM role WHERE level_id IN ($in)";
    $result_role = $connection->query($query_role);
    if ($result_role) {
        while ($row = $result_role->fetch_assoc()) {
            if (!empty($row['modul_id'])) {
                if (!isset($role_menu[$row['modul_id']]) || strtoupper($row['lihat']) == 'Y') {
                    $role_menu[$row['modul_id']] = strtoupper($row['lihat']) == 'Y' ? 'Y' : 'N';
                }
            }
        }
    }
}

function can_see($modul_id)
{
    global $role_menu, $is_operator_superadmin;
    if ($is_operator_superadmin) return true;
    return !empty($role_menu[$modul_id]) && $role_menu[$modul_id] == 'Y';
}

function can_see_any($modul_ids = array())
{
    foreach ($modul_ids as $id) {
        if (can_see($id)) return true;
    }
    return false;
}

function sidebar_link($href, $icon, $label, $color = 'text-primary')
{
    echo '<li class="nav-item"><a class="nav-link" href="' . htmlspecialchars($href) . '"><i class="' . htmlspecialchars($icon . ' ' . $color) . '"></i> ' . htmlspecialchars($label) . '</a></li>';
}

function sidebar_soon($label, $icon = 'fas fa-clock', $color = 'text-muted')
{
    echo '<li class="nav-item"><span class="nav-link text-muted sidebar-soon-link" style="cursor:not-allowed;opacity:.68"><span class="sidebar-soon-text"><i class="' . htmlspecialchars($icon . ' ' . $color) . '"></i> ' . htmlspecialchars($label) . '</span><span class="badge badge-light sidebar-soon-badge">Segera</span></span></li>';
}

function sidebar_group_open($id, $icon, $label, $color = 'text-primary')
{
    echo '<li class="nav-item"><a class="nav-link collapsed" href="#' . htmlspecialchars($id) . '" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="' . htmlspecialchars($id) . '"><i class="' . htmlspecialchars($icon . ' ' . $color) . '"></i><span class="nav-link-text">' . htmlspecialchars($label) . '</span></a><div class="collapse" id="' . htmlspecialchars($id) . '"><ul class="nav nav-sm flex-column ml-3">';
}

function sidebar_group_close()
{
    echo '</ul></div></li>';
}

$count_belum_validasi = 0;
$count_edit_identitas = 0;
if (isset($connection)) {
    $q = $connection->query("SELECT COUNT(DISTINCT user_id) AS cnt FROM berkas WHERE TRIM(COALESCE(validasi_berkas, '')) = ''");
    if ($q && $r = $q->fetch_assoc()) $count_belum_validasi = intval($r['cnt']);

    $q2 = $connection->query("SELECT COUNT(*) AS cnt FROM perubahan WHERE status_pengajuan = 'Dalam Proses'");
    if ($q2 && $r2 = $q2->fetch_assoc()) $count_edit_identitas = intval($r2['cnt']);
}

$logo_path = !empty($site_logo1) ? $site_logo1 : 'logoweb1.png';
$logo_path_url = @filemtime("../content/$logo_path") > 0 ? "../content/$logo_path?v=" . filemtime("../content/$logo_path") : "../content/$logo_path?v=" . time();
?>

<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white" id="sidenav-main">
    <div class="scrollbar-inner">
        <div class="sidenav-header d-flex align-items-center">
            <a class="navbar-brand" href="./">
                <img src="<?php echo htmlspecialchars($logo_path_url); ?>" class="navbar-brand-img" alt="Logo">
            </a>
            <div class="ml-auto">
                <div class="sidenav-toggler d-none d-xl-block" data-action="sidenav-unpin" data-target="#sidenav-main">
                    <div class="sidenav-toggler-inner"><i class="sidenav-toggler-line"></i><i class="sidenav-toggler-line"></i><i class="sidenav-toggler-line"></i></div>
                </div>
            </div>
        </div>
        <div class="navbar-inner">
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                <ul class="navbar-nav">

                    <?php if (can_see(1)) sidebar_link('./', 'fas fa-tachometer-alt', 'Dashboard', 'text-primary'); ?>
                    <?php if (can_see(2)) sidebar_link('./portal-gtk', 'fas fa-chalkboard-teacher', 'Portal GTK', 'text-info'); ?>

                    <?php if (can_see_any(array(3,4,5,31,18,19,34))) {
                        sidebar_group_open('navbar-wali-kelas', 'fas fa-user-shield', 'Wali Kelas', 'text-warning');
                            sidebar_group_open('navbar-wali-administrasi', 'fas fa-school', 'Administrasi', 'text-info');
                                if (can_see(3)) sidebar_link('./user', 'fas fa-user-check', 'Murid Aktif', 'text-success');
                                if (can_see(4)) sidebar_link('./user-tidak-aktif', 'fas fa-user-times', 'Murid Tidak Aktif', 'text-danger');
                                if (can_see(5)) sidebar_link('./berkas', 'fas fa-folder-open', 'Dokumen', 'text-warning');
                            sidebar_group_close();
                            sidebar_group_open('navbar-wali-beasiswa', 'fas fa-money-bill-wave', 'Beasiswa', 'text-success');
                                if (can_see(31)) sidebar_link('./usulan-pip-ranking', 'fas fa-sort-numeric-down', 'Ranking PIP', 'text-warning');
                            sidebar_group_close();
                            sidebar_group_open('navbar-wali-kehadiran', 'fas fa-calendar-check', 'Kehadiran', 'text-primary');
                                if (can_see(18)) sidebar_link('./laporan-absensi-kelas', 'fas fa-chalkboard-teacher', 'Per-Kelas', 'text-info');
                                if (can_see(19)) sidebar_link('./laporan-absensi-siswa', 'fas fa-user', 'Per-Murid', 'text-success');
                            sidebar_group_close();
                            if (can_see(34)) sidebar_link('./poin', 'fas fa-gavel', 'Pelanggaran', 'text-danger');
                        sidebar_group_close();
                    } ?>

                    <?php if (can_see_any(array(20,22,47,21,8,7,48))) {
                        sidebar_group_open('navbar-akademik', 'fas fa-graduation-cap', 'Akademik', 'text-indigo');
                            sidebar_group_open('navbar-akademik-agenda', 'fas fa-calendar-alt', 'Agenda Kelas', 'text-info');
                                if (can_see(20)) sidebar_link('./agenda-ref', 'fas fa-list', 'Referensi', 'text-primary');
                                if (can_see(22)) sidebar_link('./agenda-laporan', 'fas fa-chart-line', 'Laporan', 'text-success');
                            sidebar_group_close();
                            if (can_see(47)) sidebar_link('./pembelajaran', 'fas fa-book-reader', 'Pembelajaran', 'text-primary');
                            if (can_see(21)) sidebar_link('./agenda-jadwal', 'fas fa-clock', 'Jadwal Pelajaran', 'text-info');
                            if (can_see(8)) sidebar_link('./kelas', 'fas fa-chalkboard', 'Rombel/Kelas', 'text-success');
                            if (can_see(7)) sidebar_link('./jurusan', 'fas fa-medal', 'Kompetensi Keahlian', 'text-warning');
                            if (can_see(48)) {
                                sidebar_group_open('navbar-akademik-kelulusan', 'fas fa-user-graduate', 'Kelulusan', 'text-danger');
                                    sidebar_link('./skl-settings', 'fas fa-bullhorn', 'Pengaturan Rilis', 'text-success');
                                    sidebar_link('./skl-user', 'fas fa-users', 'Data Kelulusan', 'text-primary');
                                    sidebar_link('./skl-import', 'fas fa-file-import', 'Import SKL', 'text-warning');
                                    sidebar_link('./skl-history', 'fas fa-history', 'History', 'text-info');
                                    sidebar_link('./skl-ijazah', 'fas fa-file-pdf', 'E-Ijazah', 'text-danger');
                                sidebar_group_close();
                            }
                        sidebar_group_close();
                    } ?>

                    <?php if (can_see_any(array(3,4,5,6,33,34,35,36,28,29,30,31,32,15))) {
                        sidebar_group_open('navbar-kesiswaan', 'fas fa-users', 'Kesiswaan', 'text-danger');
                            if (can_see(3)) sidebar_link('./user', 'fas fa-user-check', 'Murid Aktif', 'text-success');
                            if (can_see(4)) sidebar_link('./user-tidak-aktif', 'fas fa-user-times', 'Murid Tidak Aktif', 'text-danger');
                            if (can_see(5)) sidebar_link('./berkas', 'fas fa-folder-open', 'Dokumen Murid', 'text-warning');
                            if (can_see(6)) sidebar_link('./edit-identitas', 'fas fa-id-card', 'Usulan Perubahan', 'text-purple');
                            sidebar_soon('Prestasi', 'fas fa-trophy', 'text-warning');
                            sidebar_group_open('navbar-kesiswaan-pelanggaran', 'fas fa-gavel', 'Pelanggaran', 'text-danger');
                                if (can_see(33)) sidebar_link('./poin-tatib', 'fas fa-book', 'Point/Tata Tertib', 'text-primary');
                                if (can_see(34)) sidebar_link('./poin', 'fas fa-list', 'Data Pelanggaran', 'text-warning');
                                if (can_see(36)) sidebar_link('./poin-sanggah', 'fas fa-hand-paper', 'Sanggahan', 'text-info');
                                if (can_see(35)) sidebar_link('./poin-panggil', 'fas fa-phone', 'Pemanggilan', 'text-danger');
                            sidebar_group_close();
                            sidebar_group_open('navbar-kesiswaan-beasiswa', 'fas fa-money-bill-wave', 'Beasiswa', 'text-success');
                                if (can_see(28)) sidebar_link('./kriteria-pip', 'fas fa-clipboard-list', 'Kriteria PIP', 'text-warning');
                                if (can_see(29)) sidebar_link('./usulan-pip-semua', 'fas fa-list', 'Usulan PIP Semua', 'text-info');
                                if (can_see(31)) sidebar_link('./usulan-pip-ranking', 'fas fa-sort-numeric-down', 'Usulan PIP Ranking', 'text-warning');
                                if (can_see(30)) sidebar_link('./usulan-pip-diterima', 'fas fa-check-circle', 'Usulan PIP Diterima', 'text-success');
                                if (can_see(32)) sidebar_link('./history-pip', 'fas fa-history', 'History PIP', 'text-secondary');
                            sidebar_group_close();
                            if (can_see(15)) sidebar_link('./cetak-absensi', 'fas fa-print', 'Cetak Absensi (Manual)', 'text-primary');
                        sidebar_group_close();
                    } ?>

                    <?php if (can_see_any(array(45,46))) {
                        sidebar_group_open('navbar-kepegawaian', 'fas fa-user-tie', 'Kepegawaian', 'text-info');
                            if (can_see(45)) sidebar_link('./guru', 'fas fa-user-check', 'GTK Aktif', 'text-success');
                            if (can_see(46)) sidebar_link('./guru-tidak-aktif', 'fas fa-user-slash', 'GTK Tidak Aktif', 'text-warning');
                        sidebar_group_close();
                    } ?>

                    <?php sidebar_group_open('navbar-keuangan', 'fas fa-coins', 'Keuangan', 'text-success'); sidebar_soon('Keuangan', 'fas fa-coins', 'text-success'); sidebar_group_close(); ?>

                    <?php if (can_see_any(array(24,25,26,27))) {
                        sidebar_group_open('navbar-sarpras', 'fas fa-boxes', 'Sarana & Prasarana', 'text-teal');
                            sidebar_group_open('navbar-sarpras-inventaris', 'fas fa-th-list', 'Inventaris', 'text-primary');
                                if (can_see(24)) sidebar_link('./inv-master', 'fas fa-cogs', 'Referensi', 'text-info');
                                if (can_see(25)) sidebar_link('./inv-kelas', 'fas fa-door-open', 'Data Ruangan', 'text-primary');
                            sidebar_group_close();
                            if (can_see(26)) sidebar_link('./inv-pinjam', 'fas fa-exchange-alt', 'Peminjaman', 'text-warning');
                            sidebar_soon('Pemeliharaan', 'fas fa-tools', 'text-info');
                            if (can_see(27)) sidebar_link('./inv-report', 'fas fa-flag', 'Laporan', 'text-danger');
                        sidebar_group_close();
                    } ?>

                    <?php if (can_see_any(array(52,53,54,55,130,131,132))) {
                        sidebar_group_open('navbar-persuratan', 'fas fa-envelope', 'Persuratan', 'text-primary');
                            if (can_see(52)) sidebar_link('./surat', 'fas fa-tachometer-alt', 'Surat', 'text-primary');
                            if (can_see(130)) sidebar_link('./surat-index', 'fas fa-list', 'Referensi', 'text-info');
                            if (can_see(131)) sidebar_link('./surat-template', 'fas fa-file-code', 'Template', 'text-teal');
                            if (can_see(132)) sidebar_link('./surat-setting', 'fas fa-cog', 'Pengaturan', 'text-pink');
                            if (can_see(54)) sidebar_link('./surat-keluar', 'fas fa-paper-plane', 'Surat Keluar', 'text-warning');
                            if (can_see(53)) sidebar_link('./surat-masuk', 'fas fa-envelope-open-text', 'Surat Masuk', 'text-success');
                            if (can_see(55)) sidebar_link('./surat-arsip', 'fas fa-archive', 'Arsip Surat', 'text-secondary');
                            sidebar_soon('Laporan', 'fas fa-chart-bar', 'text-cyan');
                        sidebar_group_close();
                    } ?>

                    <?php if (can_see_any(array(51))) {
                        sidebar_group_open('navbar-hubin', 'fas fa-handshake', 'Hubungan Industri (Hubin)', 'text-success');
                            sidebar_soon('Data Dunia Industri', 'fas fa-building', 'text-primary');
                            if (can_see(51)) sidebar_link('./peserta-pkl', 'fas fa-user-graduate', 'Praktik Kerja Lapangan', 'text-warning');
                        sidebar_group_close();
                    } ?>

                    <?php sidebar_group_open('navbar-humas', 'fas fa-bullhorn', 'Humas', 'text-warning'); sidebar_soon('Humas', 'fas fa-bullhorn', 'text-warning'); sidebar_group_close(); ?>
                    <?php sidebar_group_open('navbar-perpustakaan', 'fas fa-book', 'Perpustakaan', 'text-indigo'); sidebar_soon('Perpustakaan', 'fas fa-book', 'text-indigo'); sidebar_group_close(); ?>
                    <?php sidebar_group_open('navbar-arsip-dokumen', 'fas fa-archive', 'Arsip & Dokumen', 'text-secondary'); sidebar_soon('Arsip & Dokumen', 'fas fa-archive', 'text-secondary'); sidebar_group_close(); ?>

                    <?php if (can_see_any(array(11,14,12,13,10,17,18,19,50,49,23))) {
                        sidebar_group_open('navbar-layanan', 'fas fa-concierge-bell', 'Layanan Terpadu', 'text-danger');
                            sidebar_soon('Informasi', 'fas fa-info-circle', 'text-info');
                            sidebar_soon('Survei Kepuasan', 'fas fa-star', 'text-warning');
                            sidebar_group_open('navbar-layanan-absensi', 'fas fa-calendar-check', 'Absensi Digital', 'text-primary');
                                if (can_see(11)) sidebar_link('./absensi-registrasi', 'fas fa-user-plus', 'Registrasi', 'text-success');
                                if (can_see(14)) sidebar_link('./absensi-lokasi', 'fas fa-map-marker-alt', 'Lokasi', 'text-danger');
                                if (can_see(12)) sidebar_link('./jadwal', 'fas fa-clock', 'Jadwal', 'text-info');
                                if (can_see(13)) sidebar_link('./libur', 'fas fa-calendar-times', 'Hari Libur', 'text-danger');
                                if (can_see(10)) sidebar_link('./absensi-izin', 'fas fa-envelope-open-text', 'Izin', 'text-warning');
                            sidebar_group_close();
                            sidebar_group_open('navbar-layanan-laporan', 'fas fa-chart-bar', 'Laporan', 'text-cyan');
                                if (can_see(17)) sidebar_link('./laporan-absensi', 'fas fa-file-alt', 'Per-Hari-Ini', 'text-primary');
                                if (can_see(18)) sidebar_link('./laporan-absensi-kelas', 'fas fa-chalkboard-teacher', 'Per-Kelas', 'text-info');
                                if (can_see(19)) sidebar_link('./laporan-absensi-siswa', 'fas fa-user', 'Per-Murid', 'text-success');
                            sidebar_group_close();
                            sidebar_group_open('navbar-layanan-buku-tamu', 'fas fa-book-open', 'Buku Tamu', 'text-warning');
                                if (can_see(50)) sidebar_link('./tamu-referensi', 'fas fa-sitemap', 'Referensi', 'text-info');
                                if (can_see(49)) sidebar_link('./buku-tamu', 'fas fa-book-open', 'Buku Tamu', 'text-primary');
                            sidebar_group_close();
                            if (can_see(23)) sidebar_link('./e-izin', 'fas fa-file-signature', 'e-Izin', 'text-info');
                        sidebar_group_close();
                    } ?>

                </ul>

                <hr class="my-3">

                <ul class="navbar-nav mb-md-3">
                    <?php if (can_see_any(array(37,38,43,39,40,41,133))) {
                        sidebar_group_open('navbar-pengaturan', 'ni ni-settings-gear-65', 'Pengaturan', 'text-pink');
                            if (can_see(37)) sidebar_link('./pengaturan', 'fas fa-cog', 'Pengaturan Web', 'text-pink');
                            if (can_see(38)) sidebar_link('./admin', 'fas fa-users-cog', 'Admin', 'text-primary');
                            if (can_see(43)) sidebar_link('./hak-akses', 'fas fa-user-lock', 'Hak Akses', 'text-warning');
                            if (can_see(39)) sidebar_link('./menu-siswa', 'fas fa-list', 'Menu/Fitur Murid', 'text-info');
                            if (can_see(41)) sidebar_link('./sync', 'fas fa-sync', 'Tarik Data Dapodik', 'text-success');
                            if (can_see(133)) sidebar_link('./lisensi_pembaruan', 'fas fa-key', 'Lisensi', 'text-warning');
                            if (can_see(40)) sidebar_link('./pembaharuan', 'fas fa-cloud-download-alt', 'Pembaruan', 'text-info');
                        sidebar_group_close();
                    } ?>
                    <li class="nav-item"><a class="nav-link" href="./tentang"><i class="fas fa-info-circle text-info"></i><span class="nav-link-text">Tentang &amp; Privasi</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="./logout"><i class="fas fa-sign-out-alt text-danger"></i><span class="nav-link-text">Keluar</span></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
