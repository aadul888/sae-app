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
    $query_role = "SELECT m.modul_route, r.lihat FROM role r INNER JOIN modul m ON m.modul_id = r.modul_id WHERE r.level_id IN ($in) AND m.modul_route <> ''";
    $result_role = $connection->query($query_role);
    if ($result_role) {
        while ($row = $result_role->fetch_assoc()) {
            $route = trim($row['modul_route']);
            if ($route !== '' && (!isset($role_menu[$route]) || strtoupper($row['lihat']) == 'Y')) {
                $role_menu[$route] = strtoupper($row['lihat']) == 'Y' ? 'Y' : 'N';
            }
        }
    }
}

function legacy_sidebar_route($modul_id)
{
    static $routes = array(
        1 => 'home', 2 => 'portal-gtk', 3 => 'user', 4 => 'user-tidak-aktif', 5 => 'berkas', 6 => 'edit-identitas', 7 => 'jurusan', 8 => 'kelas',
        10 => 'absensi-izin', 11 => 'absensi-registrasi', 12 => 'jadwal', 13 => 'libur', 14 => 'absensi-lokasi', 15 => 'cetak-absensi',
        17 => 'laporan-absensi', 18 => 'laporan-absensi-kelas', 19 => 'laporan-absensi-siswa', 20 => 'agenda-ref', 21 => 'agenda-jadwal', 22 => 'agenda-laporan', 23 => 'e-izin',
        24 => 'inv-master', 25 => 'inv-kelas', 26 => 'inv-pinjam', 27 => 'inv-report', 28 => 'kriteria-pip', 29 => 'usulan-pip-semua', 30 => 'usulan-pip-diterima', 31 => 'usulan-pip-ranking', 32 => 'history-pip',
        33 => 'poin-tatib', 34 => 'poin', 35 => 'poin-panggil', 36 => 'poin-sanggah', 37 => 'pengaturan', 38 => 'admin', 39 => 'menu-siswa', 40 => 'pembaharuan', 41 => 'sync', 42 => 'info',
        45 => 'guru', 46 => 'guru-tidak-aktif', 47 => 'pembelajaran', 48 => 'skl-history', 49 => 'buku-tamu', 50 => 'tamu-referensi', 51 => 'peserta-pkl',
        52 => 'surat', 53 => 'surat-masuk', 54 => 'surat-keluar', 55 => 'surat-arsip', 56 => 'aktivitas', 130 => 'surat-index', 131 => 'surat-template', 132 => 'surat-setting', 133 => 'lisensi'
    );
    return isset($routes[$modul_id]) ? $routes[$modul_id] : '';
}

function can_see_route($route)
{
    global $role_menu, $is_operator_superadmin;
    if ($is_operator_superadmin) return true;
    return !empty($role_menu[$route]) && $role_menu[$route] == 'Y';
}

function can_see($modul_id)
{
    return can_see_route(legacy_sidebar_route(intval($modul_id)));
}

function can_see_any($modul_ids = array())
{
    foreach ($modul_ids as $id) {
        if (can_see($id)) return true;
    }
    return false;
}

function can_see_any_route($routes = array())
{
    foreach ($routes as $route) {
        if (can_see_route($route)) return true;
    }
    return false;
}

// Detect current module for active state
$current_mod = isset($_GET['mod']) ? $_GET['mod'] : 'home';

function is_active($mod_name) {
    global $current_mod;
    return $current_mod === $mod_name;
}

function is_active_any($mods = array()) {
    global $current_mod;
    return in_array($current_mod, $mods);
}

function sidebar_link($href, $icon, $label, $color = 'text-primary')
{
    global $current_mod;
    $mod_name = str_replace('./', '', $href);
    if ($mod_name === '' || $mod_name === './') $mod_name = 'home';
    $active_class = ($current_mod === $mod_name) ? ' active' : '';
    echo '<li class="nav-item"><a class="nav-link' . $active_class . '" href="' . htmlspecialchars($href) . '"><i class="' . htmlspecialchars($icon . ' ' . $color) . '"></i> ' . htmlspecialchars($label) . '</a></li>';
}

function sidebar_soon($label, $icon = 'fas fa-clock', $color = 'text-muted')
{
    echo '<li class="nav-item"><span class="nav-link text-muted sidebar-soon-link" style="cursor:not-allowed;opacity:.68"><span class="sidebar-soon-text"><i class="' . htmlspecialchars($icon . ' ' . $color) . '"></i> ' . htmlspecialchars($label) . '</span><span class="badge badge-light sidebar-soon-badge">Segera</span></span></li>';
}

function sidebar_group_open($id, $icon, $label, $child_mods = array(), $color = 'text-primary')
{
    global $current_mod;
    $is_open = in_array($current_mod, $child_mods);
    $collapsed_class = $is_open ? '' : ' collapsed';
    $show_class = $is_open ? ' show' : '';
    $expanded = $is_open ? 'true' : 'false';
    echo '<li class="nav-item"><a class="nav-link' . $collapsed_class . '" href="#' . htmlspecialchars($id) . '" data-toggle="collapse" role="button" aria-expanded="' . $expanded . '" aria-controls="' . htmlspecialchars($id) . '"><i class="' . htmlspecialchars($icon . ' ' . $color) . '"></i><span class="nav-link-text">' . htmlspecialchars($label) . '</span></a><div class="collapse' . $show_class . '" id="' . htmlspecialchars($id) . '"><ul class="nav nav-sm flex-column ml-3">';
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
$logo_path_url = "../content/$logo_path?v=" . asset_ver("../content/$logo_path");
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

                    <?php if (can_see_any(array(8, 7))) {
                        sidebar_group_open('navbar-master-data', 'fas fa-database', 'Master Data', array('kelas', 'jurusan'), 'text-indigo');
                            if (can_see(8)) sidebar_link('./kelas', 'fas fa-chalkboard', 'Rombel/Kelas', 'text-success');
                            if (can_see(7)) sidebar_link('./jurusan', 'fas fa-medal', 'Kompetensi Keahlian', 'text-warning');
                        sidebar_group_close();
                    } ?>

                    <?php if (can_see_any(array(3, 4, 5, 6, 45, 46, 47))) {
                        sidebar_group_open('navbar-manajemen-data', 'fas fa-users-cog', 'Manajemen Data', array('user', 'user-tidak-aktif', 'berkas', 'edit-identitas', 'guru', 'guru-tidak-aktif', 'pembelajaran'), 'text-danger');
                            if (can_see(3)) sidebar_link('./user', 'fas fa-user-check', 'Murid Aktif', 'text-success');
                            if (can_see(4)) sidebar_link('./user-tidak-aktif', 'fas fa-user-times', 'Murid Tidak Aktif', 'text-danger');
                            if (can_see(5)) sidebar_link('./berkas', 'fas fa-folder-open', 'Berkas Murid', 'text-warning');
                            if (can_see(6)) sidebar_link('./edit-identitas', 'fas fa-id-card', 'Usulan Edit Data Murid', 'text-purple');
                            if (can_see(45)) sidebar_link('./guru', 'fas fa-user-check', 'GTK Aktif', 'text-success');
                            if (can_see(46)) sidebar_link('./guru-tidak-aktif', 'fas fa-user-slash', 'GTK Tidak Aktif', 'text-warning');
                            if (can_see(47)) sidebar_link('./pembelajaran', 'fas fa-book-reader', 'Pembelajaran', 'text-primary');
                        sidebar_group_close();
                    } ?>

                    <?php
                    $layanan_routes = array(
                        'info', 'absensi-registrasi', 'absensi-lokasi', 'jadwal', 'libur', 'absensi-izin', 'cetak-absensi',
                        'laporan-absensi', 'laporan-absensi-kelas', 'laporan-absensi-siswa', 'e-izin', 'poin-tatib', 'poin',
                        'poin-panggil', 'poin-sanggah', 'agenda-ref', 'agenda-jadwal', 'agenda-laporan', 'tamu-referensi',
                        'buku-tamu', 'kriteria-pip', 'history-pip', 'usulan-pip-semua', 'usulan-pip-diterima', 'usulan-pip-ranking',
                        'inv-master', 'inv-kelas', 'inv-pinjam', 'inv-report', 'peserta-pkl', 'skl-history', 'surat', 'surat-arsip',
                        'surat-index', 'surat-keluar', 'surat-masuk', 'surat-setting', 'surat-template'
                    );
                    if (can_see_any_route($layanan_routes)) {
                        sidebar_group_open('navbar-layanan-terpadu', 'fas fa-concierge-bell', 'Layanan Terpadu', $layanan_routes, 'text-danger');
                            if (can_see(42)) sidebar_link('./info', 'fas fa-info-circle', 'Informasi', 'text-info');

                            if (can_see_any(array(11, 14, 12, 13, 10, 15))) {
                                sidebar_group_open('navbar-layanan-absensi', 'fas fa-calendar-check', 'Absensi Digital', array('absensi-registrasi', 'absensi-lokasi', 'jadwal', 'libur', 'absensi-izin', 'cetak-absensi'), 'text-primary');
                                    if (can_see(11)) sidebar_link('./absensi-registrasi', 'fas fa-id-card', 'RFID', 'text-success');
                                    if (can_see(14)) sidebar_link('./absensi-lokasi', 'fas fa-map-marker-alt', 'Lokasi', 'text-danger');
                                    if (can_see(12)) sidebar_link('./jadwal', 'fas fa-clock', 'Jadwal', 'text-info');
                                    if (can_see(13)) sidebar_link('./libur', 'fas fa-calendar-times', 'Libur', 'text-danger');
                                    if (can_see(10)) sidebar_link('./absensi-izin', 'fas fa-envelope-open-text', 'Izin', 'text-warning');
                                    if (can_see(15)) sidebar_link('./cetak-absensi', 'fas fa-print', 'Cetak Manual', 'text-primary');
                                sidebar_group_close();
                            }

                            if (can_see_any(array(17, 18, 19))) {
                                sidebar_group_open('navbar-layanan-laporan-absensi', 'fas fa-chart-bar', 'Laporan Absensi', array('laporan-absensi', 'laporan-absensi-kelas', 'laporan-absensi-siswa'), 'text-cyan');
                                    if (can_see(17)) sidebar_link('./laporan-absensi', 'fas fa-file-alt', 'Per Hari Ini', 'text-primary');
                                    if (can_see(18)) sidebar_link('./laporan-absensi-kelas', 'fas fa-chalkboard-teacher', 'Per Kelas', 'text-info');
                                    if (can_see(19)) sidebar_link('./laporan-absensi-siswa', 'fas fa-user', 'Per Murid', 'text-success');
                                sidebar_group_close();
                            }

                            if (can_see(23)) sidebar_link('./e-izin', 'fas fa-file-signature', 'E-Izin', 'text-info');

                            if (can_see_any(array(33, 34, 35, 36))) {
                                sidebar_group_open('navbar-layanan-tata-tertib', 'fas fa-gavel', 'Tata Tertib', array('poin-tatib', 'poin', 'poin-panggil', 'poin-sanggah'), 'text-danger');
                                    if (can_see(33)) sidebar_link('./poin-tatib', 'fas fa-book', 'Tata Tertib', 'text-primary');
                                    if (can_see(34)) sidebar_link('./poin', 'fas fa-list', 'Input Poin', 'text-warning');
                                    if (can_see(35)) sidebar_link('./poin-panggil', 'fas fa-phone', 'Pemanggilan', 'text-danger');
                                    if (can_see(36)) sidebar_link('./poin-sanggah', 'fas fa-hand-paper', 'Sanggah', 'text-info');
                                sidebar_group_close();
                            }

                            if (can_see_any(array(20, 21, 22))) {
                                sidebar_group_open('navbar-layanan-agenda', 'fas fa-calendar-alt', 'Agenda', array('agenda-ref', 'agenda-jadwal', 'agenda-laporan'), 'text-info');
                                    if (can_see(20)) sidebar_link('./agenda-ref', 'fas fa-list', 'Referensi', 'text-primary');
                                    if (can_see(21)) sidebar_link('./agenda-jadwal', 'fas fa-clock', 'Jadwal', 'text-info');
                                    if (can_see(22)) sidebar_link('./agenda-laporan', 'fas fa-chart-line', 'Laporan', 'text-success');
                                sidebar_group_close();
                            }

                            if (can_see_any(array(50, 49))) {
                                sidebar_group_open('navbar-layanan-buku-tamu', 'fas fa-book-open', 'Buku Tamu', array('tamu-referensi', 'buku-tamu'), 'text-warning');
                                    if (can_see(50)) sidebar_link('./tamu-referensi', 'fas fa-sitemap', 'Referensi', 'text-info');
                                    if (can_see(49)) sidebar_link('./buku-tamu', 'fas fa-book-open', 'Buku Tamu', 'text-primary');
                                sidebar_group_close();
                            }

                            if (can_see_any(array(28, 32, 29, 30, 31))) {
                                sidebar_group_open('navbar-layanan-beasiswa', 'fas fa-money-bill-wave', 'Beasiswa', array('kriteria-pip', 'history-pip', 'usulan-pip-semua', 'usulan-pip-diterima', 'usulan-pip-ranking'), 'text-success');
                                    if (can_see(28)) sidebar_link('./kriteria-pip', 'fas fa-clipboard-list', 'Kriteria', 'text-warning');
                                    if (can_see(32)) sidebar_link('./history-pip', 'fas fa-history', 'Riwayat', 'text-secondary');
                                    if (can_see(29)) sidebar_link('./usulan-pip-semua', 'fas fa-list', 'Semua Usulan', 'text-info');
                                    if (can_see(30)) sidebar_link('./usulan-pip-diterima', 'fas fa-check-circle', 'Usulan Diterima', 'text-success');
                                    if (can_see(31)) sidebar_link('./usulan-pip-ranking', 'fas fa-sort-numeric-down', 'Rank', 'text-warning');
                                sidebar_group_close();
                            }

                            if (can_see_any(array(24, 25, 26, 27))) {
                                sidebar_group_open('navbar-layanan-inventaris', 'fas fa-boxes', 'Inventaris', array('inv-master', 'inv-kelas', 'inv-pinjam', 'inv-report'), 'text-teal');
                                    if (can_see(24)) sidebar_link('./inv-master', 'fas fa-cogs', 'Referensi', 'text-info');
                                    if (can_see(25)) sidebar_link('./inv-kelas', 'fas fa-door-open', 'Kelas', 'text-primary');
                                    if (can_see(26)) sidebar_link('./inv-pinjam', 'fas fa-exchange-alt', 'Peminjaman', 'text-warning');
                                    if (can_see(27)) sidebar_link('./inv-report', 'fas fa-flag', 'Laporan', 'text-danger');
                                sidebar_group_close();
                            }

                            if (can_see_any(array(51))) {
                                sidebar_group_open('navbar-layanan-epkl', 'fas fa-handshake', 'ePKL', array('peserta-pkl'), 'text-success');
                                    if (can_see(51)) sidebar_link('./peserta-pkl', 'fas fa-user-graduate', 'Peserta PKL', 'text-warning');
                                sidebar_group_close();
                            }

                            if (can_see_route('skl-history')) {
                                sidebar_group_open('navbar-layanan-kelulusan', 'fas fa-user-graduate', 'Kelulusan', array('skl-history'), 'text-danger');
                                    sidebar_link('./skl-history', 'fas fa-history', 'History', 'text-info');
                                sidebar_group_close();
                            }

                            if (can_see_any(array(52, 53, 54, 55, 130, 131, 132))) {
                                sidebar_group_open('navbar-layanan-persuratan', 'fas fa-envelope', 'Persuratan', array('surat', 'surat-arsip', 'surat-index', 'surat-keluar', 'surat-masuk', 'surat-setting', 'surat-template'), 'text-primary');
                                    if (can_see(52)) sidebar_link('./surat', 'fas fa-tachometer-alt', 'Surat', 'text-primary');
                                    if (can_see(55)) sidebar_link('./surat-arsip', 'fas fa-archive', 'Arsip Surat', 'text-secondary');
                                    if (can_see(130)) sidebar_link('./surat-index', 'fas fa-list', 'Referensi', 'text-info');
                                    if (can_see(54)) sidebar_link('./surat-keluar', 'fas fa-paper-plane', 'Surat Keluar', 'text-warning');
                                    if (can_see(53)) sidebar_link('./surat-masuk', 'fas fa-envelope-open-text', 'Surat Masuk', 'text-success');
                                    if (can_see(132)) sidebar_link('./surat-setting', 'fas fa-cog', 'Pengaturan', 'text-pink');
                                    if (can_see(131)) sidebar_link('./surat-template', 'fas fa-file-code', 'Template', 'text-teal');
                                sidebar_group_close();
                            }
                        sidebar_group_close();
                    } ?>

                    <?php if (can_see_any(array(37, 38, 39, 40, 41, 56, 133))) {
                        sidebar_group_open('navbar-sistem', 'ni ni-settings-gear-65', 'Sistem', array('pengaturan', 'sync', 'admin', 'menu-siswa', 'lisensi', 'pembaharuan', 'aktivitas'), 'text-pink');
                            if (can_see(37)) sidebar_link('./pengaturan', 'fas fa-cog', 'Pengaturan', 'text-pink');
                            if (can_see(41)) sidebar_link('./sync', 'fas fa-sync', 'Tarik Data', 'text-success');
                            if (can_see(38)) sidebar_link('./admin', 'fas fa-users-cog', 'Admin', 'text-primary');
                            if (can_see(39)) sidebar_link('./menu-siswa', 'fas fa-list', 'Menu Murid', 'text-info');
                            if (can_see(133)) sidebar_link('./lisensi', 'fas fa-key', 'Lisensi', 'text-warning');
                            if (can_see(40)) sidebar_link('./pembaharuan', 'fas fa-cloud-download-alt', 'Update', 'text-info');
                            if (can_see(56)) sidebar_link('./aktivitas', 'fas fa-history', 'Log Aktivitas', 'text-secondary');
                        sidebar_group_close();
                    } ?>

                    <li class="nav-item"><a class="nav-link<?php echo is_active('tentang') ? ' active' : ''; ?>" href="./tentang"><i class="fas fa-info-circle text-info"></i><span class="nav-link-text">Tentang</span></a></li>
                    <li class="nav-item"><a class="nav-link<?php echo is_active('privasi-kebijakan') ? ' active' : ''; ?>" href="./privasi-kebijakan"><i class="fas fa-user-shield text-warning"></i><span class="nav-link-text">Privasi &amp; Kebijakan</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="./logout"><i class="fas fa-sign-out-alt text-danger"></i><span class="nav-link-text">Keluar</span></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
