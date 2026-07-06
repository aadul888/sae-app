<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}

// Cek role untuk modul Dashboard (modul_id=1)
$modul_id = 1;
include __DIR__ . '/../check_role.php';
if (!$has_access) {
    hak_akses();
    return;
}

// Set default values
$total_user = 0;
$total_kelas = 0;
$total_admin = 0;
$siswa_aktif = 0;
$absensi_hari_ini = 0;
// role-related defaults
$is_wali = false;
$current_level = 0;
$persen_kualitas = 0;
$pending_izin = 0;
// Flag to decide whether to replace "Total Kelas" with "Kualitas Data"
$show_kualitas_card = false;

// Try to get real data
try {
    if (isset($connection) && $connection) {
        // Load current user info to detect wali kelas
        if (file_exists(__DIR__ . '/../../login/user.php')) {
            require_once __DIR__ . '/../../login/user.php';
        }

        // Determine if current user adalah wali kelas
        $is_wali = false;
        $wali_kelas_ids = array();
        $current_level = isset($current_user['level_id']) ? intval($current_user['level_id']) : 0;

        // Prepare display name (try several common fields)
        $display_name = '';
        if (isset($current_user) && is_array($current_user)) {
            if (!empty($current_user['fullname'])) $display_name = $current_user['fullname'];
            elseif (!empty($current_user['nama'])) $display_name = $current_user['nama'];
            elseif (!empty($current_user['admin_nama'])) $display_name = $current_user['admin_nama'];
            elseif (!empty($current_user['username'])) $display_name = $current_user['username'];
            elseif (!empty($current_user['name'])) $display_name = $current_user['name'];
        }

        // Fetch level info (level_nama and tugas_tambahan if available)
        $level_name = '';
        $tugas_tambahan = '';
        if ($current_level > 0 && isset($connection) && $connection) {
            $q_level = $connection->query("SELECT level_nama, tugas_tambahan FROM level WHERE level_id = " . intval($current_level) . " LIMIT 1");
            if (!$q_level) {
                // fallback: maybe column tugas_tambahan tidak ada
                $q_level = $connection->query("SELECT level_nama FROM level WHERE level_id = " . intval($current_level) . " LIMIT 1");
            }
            if ($q_level && $q_level->num_rows > 0) {
                $r_level = $q_level->fetch_assoc();
                $level_name = isset($r_level['level_nama']) ? $r_level['level_nama'] : '';
                $tugas_tambahan = isset($r_level['tugas_tambahan']) ? $r_level['tugas_tambahan'] : '';
            }
        }
        // Additional fallbacks: if level_name still empty, try common fields on current_user
        if (empty($level_name) && isset($current_user) && is_array($current_user)) {
            // direct name fields
            if (!empty($current_user['level_nama'])) {
                $level_name = $current_user['level_nama'];
            } elseif (!empty($current_user['level_name'])) {
                $level_name = $current_user['level_name'];
            } elseif (!empty($current_user['level'])) {
                // if this is numeric, try to fetch from level table
                if (is_numeric($current_user['level'])) {
                    $lvlid = intval($current_user['level']);
                    $ql = $connection->query("SELECT level_nama FROM level WHERE level_id = " . $lvlid . " LIMIT 1");
                    if ($ql && $ql->num_rows > 0) {
                        $rl = $ql->fetch_assoc();
                        $level_name = isset($rl['level_nama']) ? $rl['level_nama'] : '';
                    }
                } else {
                    $level_name = $current_user['level'];
                }
            } elseif (!empty($current_user['role'])) {
                $level_name = $current_user['role'];
            }
        }
        if (isset($current_user['admin_id']) && $current_level === 4) {
            $is_wali = true;
            $wali_admin_id = $connection->real_escape_string($current_user['admin_id']);
            // dapatkan semua kelas yang diawali oleh wali ini
            $q_ck = $connection->query("SELECT kelas_id FROM kelas WHERE nama_wali_kelas='" . $wali_admin_id . "'");
            if ($q_ck && $q_ck->num_rows > 0) {
                while ($r_ck = $q_ck->fetch_assoc()) {
                    $wali_kelas_ids[] = $r_ck['kelas_id'];
                }
            }
        }

        // Selain wali (level 4), ada juga kasus admin/guru dengan tugas_tambahan yang
        // mengindikasikan tanggung jawab bidang-kesiswaan (mis. '4'). Untuk level 3
        // dengan tugas_tambahan berisi '4', kumpulkan kelas yang berhubungan dengan
        // admin ini (cocokkan beberapa kolom yang mungkin ada pada tabel `kelas`).
        $is_kesiswaan_admin = false;
        $kesiswaan_kelas_ids = array();
        if ($current_level === 3) {
            $tt_val = '';
            if (!empty($current_user['tugas_tambahan'])) $tt_val = $current_user['tugas_tambahan'];
            elseif (!empty($tugas_tambahan)) $tt_val = $tugas_tambahan;

            if ($tt_val !== '') {
                // lebih toleran: cek token '4' sebagai kata/batasan atau sebagai bagian dari daftar
                if (preg_match('/\b4\b/', $tt_val) || preg_match('/(^|[,;\/|])\s*4\s*($|[,;\/|])/', $tt_val)) {
                    $is_kesiswaan_admin = true;
                } else {
                    // fallback: pisah berdasarkan delimiter seperti sebelumnya
                    $parts_tt = preg_split('/\s*[,;\/|]\s*/', $tt_val);
                    foreach ($parts_tt as $p) {
                        if (trim($p) === '4') {
                            $is_kesiswaan_admin = true;
                            break;
                        }
                    }
                }
            }
        }

        if ($is_kesiswaan_admin) {
            // dapatkan beberapa kandidat identitas: PTK UUID dan numeric admin_id
            $admin_ptk = '';
            $admin_admin_id = 0;
            $admin_name_candidate = '';
            if (!empty($current_user['ptk_id'])) $admin_ptk = $connection->real_escape_string($current_user['ptk_id']);
            if (!empty($current_user['admin_id'])) $admin_admin_id = intval($current_user['admin_id']);
            elseif (!empty($current_user['id'])) $admin_admin_id = intval($current_user['id']);

            if (!empty($current_user['nama_lengkap'])) $admin_name_candidate = $connection->real_escape_string($current_user['nama_lengkap']);
            elseif (!empty($current_user['fullname'])) $admin_name_candidate = $connection->real_escape_string($current_user['fullname']);
            elseif (!empty($current_user['nama'])) $admin_name_candidate = $connection->real_escape_string($current_user['nama']);

            // 1) Cari kelas dari tabel `user` berdasarkan kolom numeric `wali_kelas` (admin_id)
            $user_kelas_ids = array();
            if ($admin_admin_id > 0) {
                $q_u_sql = "SELECT DISTINCT kelas FROM user WHERE wali_kelas = " . intval($admin_admin_id) . " AND kelas IS NOT NULL AND kelas <> ''";
                $q_u = $connection->query($q_u_sql);
                if ($q_u && $q_u->num_rows > 0) {
                    while ($ru = $q_u->fetch_assoc()) {
                        if (!empty($ru['kelas'])) $user_kelas_ids[] = $ru['kelas'];
                    }
                }
            }

            // 2) Cari kelas dari tabel `kelas` berdasarkan PTK UUID atau nama wali
            $kelas_kelas_ids = array();
            $q_ck2_sql = "SELECT kelas_id FROM kelas WHERE 1=0";
            if ($admin_ptk !== '') {
                $q_ck2_sql .= " OR wali_kelas_ptk_id='" . $admin_ptk . "'";
            }
            if ($admin_admin_id > 0) {
                // some systems may store numeric admin reference in nama fields — include equality on string
                $q_ck2_sql .= " OR wali_kelas_nama='" . $connection->real_escape_string((string)$admin_admin_id) . "'";
                $q_ck2_sql .= " OR nama_wali_kelas='" . $connection->real_escape_string((string)$admin_admin_id) . "'";
            }
            if ($admin_name_candidate !== '') {
                $like_name = $connection->real_escape_string($admin_name_candidate);
                $q_ck2_sql .= " OR wali_kelas_nama LIKE '%" . $like_name . "%'";
                $q_ck2_sql .= " OR nama_wali_kelas LIKE '%" . $like_name . "%'";
            }
            $q_ck2 = $connection->query($q_ck2_sql);
            if ($q_ck2 && $q_ck2->num_rows > 0) {
                while ($r_ck2 = $q_ck2->fetch_assoc()) {
                    $kelas_kelas_ids[] = $r_ck2['kelas_id'];
                }
            }

            // Gabungkan hasil dari user.kelas dan kelas.kelas_id, pastikan numeric
            $combined = array();
            foreach (array_merge($user_kelas_ids, $kelas_kelas_ids) as $cid) {
                if ($cid === null || $cid === '') continue;
                $combined[] = intval($cid);
            }
            $combined = array_values(array_unique($combined));

            if (count($combined) > 0) {
                $wali_kelas_ids = array_values(array_unique(array_merge($wali_kelas_ids, $combined)));
                $is_wali = true;
                // debug write
                try {
                    $dbg_path = __DIR__ . '/../../logs/kesiswaan_debug.log';
                    $dbg_msg = "[" . date('Y-m-d H:i:s') . "] USER_KELAS_IDS: " . json_encode($user_kelas_ids) . " KELAS_TABLE_IDS: " . json_encode($kelas_kelas_ids) . " COMBINED: " . json_encode($combined) . "\n";
                    file_put_contents($dbg_path, $dbg_msg, FILE_APPEND);
                } catch (Exception $e) {
                    // ignore
                }
            }
        }
        // Total siswa: untuk wali batasi ke kelasnya, selainnya total semua
        if ($is_wali && count($wali_kelas_ids) > 0) {
            $in = implode(',', array_map('intval', $wali_kelas_ids));
            $query_user = "SELECT COUNT(*) as total FROM user WHERE (status='1' OR LOWER(status)='aktif') AND kelas IN (" . $in . ")";
        } else {
            $query_user = "SELECT COUNT(*) as total FROM user";
        }
        $result_user = $connection->query($query_user);
        $total_user = $result_user ? $result_user->fetch_assoc()['total'] : 0;

        // Total kelas (global) — kept as overall unless you want per-wali count
        $query_kelas = "SELECT COUNT(*) as total FROM kelas";
        $result_kelas = $connection->query($query_kelas);
        $total_kelas = $result_kelas ? $result_kelas->fetch_assoc()['total'] : 0;

        // Total admin (kept for non-wali). For wali, we'll replace with pending izin count
        $query_admin = "SELECT COUNT(*) as total FROM admin";
        $result_admin = $connection->query($query_admin);
        $total_admin = $result_admin ? $result_admin->fetch_assoc()['total'] : 0;

        // total siswa aktif — if wali, scope to their kelas(es)
        if ($is_wali && count($wali_kelas_ids) > 0) {
            $in = implode(',', array_map('intval', $wali_kelas_ids));
            $query_siswa_aktif = "SELECT COUNT(*) as total FROM user WHERE (status = '1' OR LOWER(status)='aktif') AND kelas IN (" . $in . ")";
        } else {
            $query_siswa_aktif = "SELECT COUNT(*) as total FROM user WHERE status = '1' OR LOWER(status)='aktif'";
        }
        $result_siswa_aktif = $connection->query($query_siswa_aktif);
        $siswa_aktif = $result_siswa_aktif ? $result_siswa_aktif->fetch_assoc()['total'] : 0;

        // siswa tidak aktif (scoped to wali when applicable)
        if ($is_wali && count($wali_kelas_ids) > 0) {
            $in = implode(',', array_map('intval', $wali_kelas_ids));
            $query_siswa_tidak = "SELECT COUNT(*) as total FROM user WHERE NOT (status = '1' OR LOWER(status)='aktif') AND kelas IN (" . $in . ")";
        } else {
            $query_siswa_tidak = "SELECT COUNT(*) as total FROM user WHERE NOT (status = '1' OR LOWER(status)='aktif')";
        }
        $result_siswa_tidak = $connection->query($query_siswa_tidak);
        $siswa_tidak_aktif = $result_siswa_tidak ? $result_siswa_tidak->fetch_assoc()['total'] : 0;

        // Absensi hari ini: jika wali, batasi pada kelasnya
        if ($is_wali && count($wali_kelas_ids) > 0) {
            $in = implode(',', array_map('intval', $wali_kelas_ids));
            $query_absensi_hari_ini = "SELECT COUNT(*) as total FROM absensi a JOIN user u ON a.user_id=u.user_id WHERE DATE(a.tanggal) = CURDATE() AND u.kelas IN (" . $in . ")";
        } else {
            $query_absensi_hari_ini = "SELECT COUNT(*) as total FROM absensi WHERE DATE(tanggal) = CURDATE()";
        }
        $result_absensi_hari_ini = $connection->query($query_absensi_hari_ini);
        $absensi_hari_ini = $result_absensi_hari_ini ? $result_absensi_hari_ini->fetch_assoc()['total'] : 0;

        // Show "Kualitas Data" card when the user is effectively a wali (either level 4
        // or a guru/admin with tugas_tambahan that maps them to kelas). This replaces the
        // previous level-based check so guru with tugas tambahan wali kelas also see it.
        $show_kualitas_card = false;
        $persen_kualitas = 0;
        if ($is_wali && count($wali_kelas_ids) > 0) {
            $show_kualitas_card = true;
            $in = implode(',', array_map('intval', $wali_kelas_ids));
            // Compute kualitas per-user (0.5 for konfirmasi sesuai/belum sesuai, 0.5 for existence of a valid berkas)
            // This mirrors the logic used in `module/kelas/datatable.php` to avoid duplicate counts caused by joins.
            $q_kualitas_sql = "SELECT 
                COUNT(DISTINCT u.user_id) AS total_users,
                SUM(
                    (CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 0.5 ELSE 0 END)
                    +
                    (CASE WHEN EXISTS (SELECT 1 FROM berkas b2 WHERE b2.user_id = u.user_id AND LOWER(b2.validasi_berkas) = 'valid') THEN 0.5 ELSE 0 END)
                ) AS jumlah_kualitas
            FROM user u
            WHERE (u.status='1' OR LOWER(u.status)='aktif') AND u.kelas IN (" . $in . ")";

            $q_kualitas = $connection->query($q_kualitas_sql);
            if ($q_kualitas && $q_kualitas->num_rows > 0) {
                $r_k = $q_kualitas->fetch_assoc();
                $num_total_users = intval($r_k['total_users']);
                $num_kualitas_sum = floatval($r_k['jumlah_kualitas']);
                if ($num_total_users > 0) {
                    // jumlah_kualitas is sum of 0..1 per user (since 0.5+0.5), so divide by total_users to get fraction
                    $persen_kualitas = round(($num_kualitas_sum / $num_total_users) * 100, 1);
                }
            } else {
                // Log SQL error to server log for debugging (won't display to user)
                if (isset($connection) && $connection) {
                    error_log('home.php kualitas query error: ' . $connection->error . ' -- SQL: ' . $q_kualitas_sql);
                }
            }
        }

        // Pending izin untuk wali (replace Total Admin card)
        $pending_izin = 0;
        if ($is_wali && count($wali_kelas_ids) > 0) {
            $in = implode(',', array_map('intval', $wali_kelas_ids));
            $q_izin = $connection->query("SELECT COUNT(e.id) AS pending FROM e_izin e JOIN user u ON e.user_id = u.user_id WHERE e.status_izin = 'Menunggu' AND u.kelas IN (" . $in . ")");
            if ($q_izin && $q_izin->num_rows > 0) {
                $pending_izin = intval($q_izin->fetch_assoc()['pending']);
            }
        }
    }
} catch (Exception $e) {
    // Keep default values
}
?>

<!-- Header -->
<div class="header bg-gradient-primary pb-8 dashboard-header-shell">
    <div class="container-fluid">
        <div class="header-body dashboard-hero">
            <div class="row align-items-center py-4 dashboard-hero-row">
                <div class="col-lg-6 col-12 dashboard-hero-intro">
                    <h6 class="h2 text-white d-inline-block mb-0 dashboard-title">Dashboard Admin</h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-6 col-12 text-center text-lg-right header-welcome dashboard-hero-welcome">
                    <?php
                    $name_out = !empty($display_name) ? $display_name : 'Pengguna';
                    $level_out = !empty($level_name) ? $level_name : '';
                    $tugas_out = !empty($tugas_tambahan) ? $tugas_tambahan : '';

                    // Fallbacks: if tugas_tambahan empty, try common fields on current_user
                    $tugas_display = '';
                    if (!empty($tugas_out)) {
                        $tugas_display = $tugas_out;
                    } else {
                        $possible = array('tugas', 'jabatan', 'position', 'role', 'tugas_tambahan', 'keterangan');
                        if (isset($current_user) && is_array($current_user)) {
                            foreach ($possible as $k) {
                                if (!empty($current_user[$k])) {
                                    $tugas_display = $current_user[$k];
                                    break;
                                }
                            }
                        }
                    }
                    $tugas_display = trim(strip_tags((string)$tugas_display));

                    if ($tugas_display !== '') {
                        if (preg_match('/\d/', $tugas_display) && preg_match('/[,;\/|]/', $tugas_display)) {
                            $parts = preg_split('/\s*[,;\/|]\s*/', $tugas_display);
                            $labels = array();
                            $level_map_for_ids = array();
                            if (isset($connection) && $connection) {
                                $qmap = $connection->query("SELECT level_id, level_nama FROM level");
                                if ($qmap && $qmap->num_rows > 0) {
                                    while ($rm = $qmap->fetch_assoc()) {
                                        $level_map_for_ids[intval($rm['level_id'])] = trim($rm['level_nama']);
                                    }
                                }
                            }
                            foreach ($parts as $p) {
                                $p = trim($p);
                                if ($p === '') continue;
                                if (ctype_digit($p)) {
                                    $id = intval($p);
                                    if (isset($level_map_for_ids[$id]) && $level_map_for_ids[$id] !== '') {
                                        $labels[] = $level_map_for_ids[$id];
                                    }
                                } else {
                                    $labels[] = $p;
                                }
                            }
                            if (count($labels) > 0) {
                                $tugas_display = implode(', ', array_unique($labels));
                            }
                        }

                        $tmp = str_replace(array(',', '.', ' ', "\xc2\xa0", "\u00A0"), '', $tugas_display);
                        if ($tmp !== '' && ctype_digit($tmp)) {
                            $mapped_label = '';
                            $num_id = intval($tmp);
                            if (isset($connection) && $connection) {
                                $qmap_single = $connection->query("SELECT level_nama FROM level WHERE level_id = " . $num_id . " LIMIT 1");
                                if ($qmap_single && $qmap_single->num_rows > 0) {
                                    $rmap_single = $qmap_single->fetch_assoc();
                                    $mapped_label = isset($rmap_single['level_nama']) ? trim($rmap_single['level_nama']) : '';
                                }
                            }
                            if ($mapped_label !== '') {
                                $tugas_display = $mapped_label;
                            } else {
                                $tugas_display = '';
                            }
                        }
                    }

                    if (empty($tugas_display) && !empty($current_level)) {
                        $lvlid = intval($current_level);

                        $level_map = array();
                        if (isset($connection) && $connection) {
                            $qmap = $connection->query("SELECT level_id, tugas_tambahan FROM level");
                            if ($qmap && $qmap->num_rows > 0) {
                                while ($rmap = $qmap->fetch_assoc()) {
                                    $id = isset($rmap['level_id']) ? intval($rmap['level_id']) : 0;
                                    $val = '';
                                    if (isset($rmap['tugas_tambahan']) && trim($rmap['tugas_tambahan']) !== '') {
                                        $val = trim($rmap['tugas_tambahan']);
                                    }
                                    $level_map[$id] = $val;
                                }
                            }
                        }

                        if (empty($level_map)) {
                            $level_map = array(
                                1 => 'Superadmin',
                                2 => 'Admin',
                                3 => 'Guru',
                                4 => 'Wali Kelas',
                                5 => 'Bidang Kesiswaan',
                                6 => 'Piket KBM',
                                7 => 'Security'
                            );
                        }

                        if (isset($level_map[$lvlid]) && $level_map[$lvlid] !== '') {
                            $candidate = $level_map[$lvlid];
                            if (strtolower(trim($candidate)) !== strtolower(trim($level_out))) {
                                $tugas_display = $candidate;
                            }
                        }
                    }
                    ?>
                    <div class="text-white dashboard-welcome-card">
                        <h6 class="mb-1 text-white">Selamat Datang</h6>
                        <h3 class="mb-0 font-weight-bold text-white" style="letter-spacing:0.2px"><?php echo htmlspecialchars($name_out); ?></h3>
                        <div class="welcome-meta mt-2">
                            <?php if ($level_out !== ''): ?>
                                <span class="level-badge">
                                    <?php echo htmlspecialchars($level_out); ?>
                                </span>

                            <?php endif; ?>
                            <?php if (!empty($tugas_display)): ?>
                                <?php
                                $parts = preg_split('/\s*[,;\/|]\s*/', $tugas_display);
                                foreach ($parts as $p) {
                                    $p = trim($p);
                                    if ($p === '') continue;
                                    echo ' <span class="level-badge tugas-badge">' . htmlspecialchars($p) . '</span>';
                                }
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card stats -->
            <div class="row mt-5 dashboard-stats-row dashboard-stats-slider" data-auto-slide="true">
                <div class="col-xl-3 col-lg-6 col-md-6 dashboard-stat-col">
                    <div class="card card-stats dashboard-stat-card mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Total Murid</h5>
                                    <span class="h2 font-weight-bold mb-0"><?php echo $total_user; ?></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-red text-white rounded-circle shadow">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm stats-inline">
                                <span class="stat stat-active text-success">
                                    <i class="fa fa-arrow-up"></i>
                                    <?php echo $siswa_aktif; ?>
                                    <span class="stat-label d-none d-sm-inline">Aktif</span>
                                </span>

                                <span class="stat stat-inactive text-danger ml-3">
                                    <i class="fa fa-arrow-down"></i>
                                    <?php echo isset($siswa_tidak_aktif) ? $siswa_tidak_aktif : 0; ?>
                                    <span class="stat-label d-none d-sm-inline">Non-Aktif</span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 dashboard-stat-col">
                    <div class="card card-stats dashboard-stat-card mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <?php
                                    if (!empty($show_kualitas_card)) {
                                        echo '<h5 class="card-title text-uppercase text-muted mb-0">Kualitas Data</h5>';
                                        echo '<span class="h2 font-weight-bold mb-0">' . $persen_kualitas . '%</span>';
                                    } else {
                                        echo '<h5 class="card-title text-uppercase text-muted mb-0">Total Kelas</h5>';
                                        echo '<span class="h2 font-weight-bold mb-0">' . $total_kelas . '</span>';
                                    }
                                    ?>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-orange text-white rounded-circle shadow">
                                        <i class="fas fa-school"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-info mr-2"><i class="fas fa-chart-line"></i></span>
                                <span class="text-nowrap">
                                    <?php echo (!empty($show_kualitas_card) ? 'Persentase kualitas data' : 'Kelas tersedia'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 dashboard-stat-col">
                    <div class="card card-stats dashboard-stat-card mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Absensi Hari Ini</h5>
                                    <span class="h2 font-weight-bold mb-0"><?php echo $absensi_hari_ini; ?></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-green text-white rounded-circle shadow">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-success mr-2"><i class="fas fa-calendar-check"></i></span>
                                <span class="text-nowrap"><?php echo date('d M Y'); ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 dashboard-stat-col">
                    <div class="card card-stats dashboard-stat-card mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <?php if ($is_wali) {
                                        echo '<h5 class="card-title text-uppercase text-muted mb-0">e-Izin</h5>';
                                        echo '<span class="h2 font-weight-bold mb-0">' . $pending_izin . '</span>';
                                    } else {
                                        echo '<h5 class="card-title text-uppercase text-muted mb-0">Total Admin</h5>';
                                        echo '<span class="h2 font-weight-bold mb-0">' . $total_admin . '</span>';
                                    }
                                    ?>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-info mr-2"><i class="fas fa-cog"></i></span>
                                <span class="text-nowrap"><?php echo ($is_wali ? 'Izin belum disetujui' : 'Administrator'); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--8 pb-8 dashboard-content-shell">
    <div class="row dashboard-content-row">
        <!-- Card Utama -->
        <div class="col-xl-8 col-lg-8 dashboard-main-col">
            <div class="card shadow dashboard-panel dashboard-main-panel">
                <div class="card-header border-0 dashboard-panel-header">
                    <div class="row align-items-center dashboard-panel-header-row">
                        <div class="col">
                            <h3 class="mb-0">Statistik Siswa & Absensi</h3>
                        </div>
                        <div class="col text-right">
                            <a href="laporan-absensi" class="btn btn-sm btn-primary disabled" aria-disabled="false" tabindex="-1" onclick="return false;">Lihat Laporan</a>
                        </div>
                    </div>
                </div>
                <div class="card-body dashboard-panel-body">
                    <div class="load-table dashboard-live-panel"></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-xl-4 col-lg-4 quick-actions-col dashboard-side-col">
            <div class="card shadow dashboard-panel dashboard-side-panel">
                <div class="card-header border-0 dashboard-panel-header">
                    <h3 class="mb-0">Quick Actions</h3>
                </div>
                <div class="card-body dashboard-panel-body">
                    <!-- Portal GTK - Featured Card -->
                    <div class="mb-4 dashboard-feature-wrap">
                        <a href="portal-gtk" class="portal-gtk-card" title="Portal GTK">
                            <div class="portal-gtk-content">
                                <div class="portal-gtk-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="portal-gtk-text">
                                    <div class="portal-gtk-title">Portal GTK</div>
                                    <div class="portal-gtk-subtitle">Akses Portal Guru dan Tenaga Kependidikan ke berbagai Aplikasi Pendidikan/Pemerintah/Komunikasi/Produktivitas dan lainnya.</div>
                                    <div class="portal-gtk-badge">
                                        <i class="fas fa-external-link-alt mr-1"></i>Buka Portal
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="quick-actions-grid">
                        <a href="user" class="quick-action-tile" title="Murid">
                            <div class="qa-icon qa-color-blue"><i class="fas fa-users"></i></div>
                            <div class="qa-title">Murid</div>
                        </a>
                        <a href="kelas" class="quick-action-tile" title="Kelas">
                            <div class="qa-icon qa-color-teal"><i class="fas fa-school"></i></div>
                            <div class="qa-title">Kelas</div>
                        </a>
                        <a href="berkas" class="quick-action-tile" title="Berkas">
                            <div class="qa-icon qa-color-amber"><i class="fas fa-folder-open"></i></div>
                            <div class="qa-title">Berkas</div>
                        </a>
                        <a href="laporan-absensi" class="quick-action-tile" title="Absensi Hari Ini">
                            <div class="qa-icon qa-color-green"><i class="fas fa-calendar-check"></i></div>
                            <div class="qa-title">Absensi</div>
                        </a>
                        <a href="guru" class="quick-action-tile" title="Guru">
                            <div class="qa-icon qa-color-indigo"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="qa-title">Guru</div>
                        </a>
                        <a href="agenda-jadwal" class="quick-action-tile" title="Agenda & Laporan">
                            <div class="qa-icon qa-color-sky"><i class="fas fa-chart-bar"></i></div>
                            <div class="qa-title">Laporan</div>
                        </a>
                        <a href="e-izin" class="quick-action-tile" title="E-Izin">
                            <div class="qa-icon qa-color-purple"><i class="fas fa-file-signature"></i></div>
                            <div class="qa-title">E-Izin</div>
                        </a>
                        <a href="poin-tatib" class="quick-action-tile" title="Tata Tertib">
                            <div class="qa-icon qa-color-red"><i class="fas fa-gavel"></i></div>
                            <div class="qa-title">Tatib</div>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- Info Sistem -->
    <div class="row mt-4 pb-4">
        <div class="col-12">
            <div class="card shadow info-card dashboard-panel dashboard-info-panel">
                <div class="card-header border-0 dashboard-panel-header">
                    <h3 class="mb-0">Informasi Sistem</h3>
                </div>
                <div class="card-body dashboard-panel-body dashboard-info-body">
                    <div class="row align-items-center">
                        <div class="col-sm-4 mb-2 mb-sm-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-sm font-weight-bold">Tanggal Hari Ini</span>
                                <span class="text-sm text-muted"><?php echo date('d F Y'); ?></span>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-2 mb-sm-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-sm font-weight-bold">Jam Server</span>
                                <span class="text-sm text-muted"><?php echo date('H:i:s'); ?></span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-sm font-weight-bold">Status Sistem</span>
                                <span class="badge badge-success">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-center mt-2 pt-2 border-top border-light">
                        <div class="col-sm-4 mb-2 mb-sm-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-sm font-weight-bold">Versi Aplikasi</span>
                                <span class="text-sm text-muted"><?php echo defined('SAE_VERSION') ? htmlspecialchars(SAE_VERSION) : 'v5.0'; ?></span>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-2 mb-sm-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-sm font-weight-bold">Sinkronisasi Terakhir</span>
                                <span class="text-sm text-muted"><?php
                                    $last_sync = '';
                                    if (isset($connection) && $connection) {
                                        $q_s = $connection->query("SELECT last_sync_at FROM setting LIMIT 1");
                                        if ($q_s && $r_s = $q_s->fetch_assoc()) {
                                            $last_sync = $r_s['last_sync_at'] ?? '';
                                        }
                                    }
                                    echo !empty($last_sync) ? htmlspecialchars(tgl_indo($last_sync) . ' ' . jam_indo($last_sync)) : 'Belum sinkron';
                                ?></span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-sm font-weight-bold">Pembaruan</span>
                                <span class="badge badge-<?php
                                    $upd_avail = false;
                                    if (isset($connection) && $connection && defined('SAE_VERSION')) {
                                        $q_u = $connection->query("SELECT version FROM pembaharuan ORDER BY release_date DESC LIMIT 1");
                                        if ($q_u && $r_u = $q_u->fetch_assoc()) {
                                            $upd_avail = version_compare(SAE_VERSION, $r_u['version'], '<');
                                        }
                                    }
                                    echo $upd_avail ? 'danger' : 'success';
                                ?>"><?php echo $upd_avail ? 'Pembaruan Tersedia' : 'Terbaru'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
$show_modal = true;
$latest_updated_at = null;
if (isset($connection) && $connection) {
    $q = $connection->query("SELECT COALESCE(MAX(updated_at), MAX(created_at)) AS latest FROM pembaharuan");
    if ($q) {
        $row = $q->fetch_assoc();
        $latest_updated_at = $row ? $row['latest'] : null;
    } else {
        echo '<div style="color:red;font-size:13px;">SQL Error: ' . htmlspecialchars($connection->error) . '</div>';
    }
}

// Only hide the modal if the user explicitly hid this exact latest update
$last_seen_update = isset($_COOKIE['last_seen_pembaharuan']) ? $_COOKIE['last_seen_pembaharuan'] : '';
$cookie_hide_raw = isset($_COOKIE['hide_pembaharuan']) ? $_COOKIE['hide_pembaharuan'] : null;
$cookie_hide_decoded = $cookie_hide_raw !== null ? rawurldecode($cookie_hide_raw) : null;
$cookie_hide = null;
if ($cookie_hide_decoded !== null) {
    if (strpos($cookie_hide_decoded, 'v2:') === 0) {
        // v2:<timestamp> format
        $cookie_hide = substr($cookie_hide_decoded, 3);
    } else {
        // legacy plain value (timestamp or '1') — accept it if it exactly equals latest
        $cookie_hide = $cookie_hide_decoded;
    }
}
if ($latest_updated_at && $cookie_hide !== null && $cookie_hide === $latest_updated_at) {
    $show_modal = false;
}

$updates = array();
if (isset($connection) && $connection) {
    $q = $connection->query("SELECT * FROM pembaharuan ORDER BY release_date DESC, id DESC");
    if ($q && $q->num_rows > 0) {
        while ($row = $q->fetch_assoc()) {
            $updates[] = $row;
        }
    }
}
?>

<?php if ($show_modal && count($updates) > 0): ?>
    <div class="modal fade" id="modalPembaharuan" tabindex="-1" role="dialog" data-latest="<?php echo htmlspecialchars(addslashes($latest_updated_at ?? '')); ?>">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">🔔 Informasi Pembaharuan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height:400px;overflow-y:auto;">
                    <?php
                    // Group updates by release_date and version
                    $grouped = array();
                    foreach ($updates as $row) {
                        $key = $row['version'] . '|' . $row['release_date'];
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = array();
                        }
                        $grouped[$key][] = $row;
                    }
                    foreach ($grouped as $key => $items):
                        $first = $items[0];
                    ?>
                        <div class="mb-4 pb-2 border-bottom">
                            <h6 class="mb-2" style="font-weight:bold;font-size:18px;">
                                versi <?php echo htmlspecialchars($first['version']); ?> <span class="text-muted" style="font-size:15px;"><?php echo date('Y', strtotime($first['release_date'])); ?></span>
                            </h6>
                            <ol style="margin-left:18px;">
                                <?php foreach ($items as $row):
                                    // output pembaharuan lines for this row first (if any)
                                    if (isset($row['pembaharuan']) && trim($row['pembaharuan']) !== '') {
                                        $lines = preg_split('/\r?\n/', $row['pembaharuan']);
                                        foreach ($lines as $desc) {
                                            $desc = trim($desc);
                                            if ($desc === '') continue;
                                ?>
                                            <li style="margin-bottom:7px;">
                                                <span class="badge badge-primary" style="color:#fff;background:linear-gradient(90deg,#6a89cc,#b8e994);font-weight:600;letter-spacing:1px;min-width:90px;display:inline-block;text-align:center;margin-right:8px;">
                                                    PEMBAHARUAN
                                                </span>
                                                <?php echo htmlspecialchars($desc); ?>
                                            </li>
                                        <?php }
                                    }
                                    // then output perbaikan lines for this row (if any)
                                    if (isset($row['perbaikan']) && trim($row['perbaikan']) !== '') {
                                        $lines = preg_split('/\r?\n/', $row['perbaikan']);
                                        foreach ($lines as $desc) {
                                            $desc = trim($desc);
                                            if ($desc === '') continue;
                                        ?>
                                            <li style="margin-bottom:7px;">
                                                <span class="badge badge-success" style="color:#219150;background:#d4f5e9;font-weight:600;letter-spacing:1px;min-width:90px;display:inline-block;text-align:center;margin-right:8px;">
                                                    PERBAIKAN
                                                </span>
                                                <?php echo htmlspecialchars($desc); ?>
                                            </li>
                                <?php }
                                    }
                                endforeach; ?>
                            </ol>
                            <div class="text-muted small mt-2">Tanggal Rilis: <?php echo date('d M Y', strtotime($first['release_date'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" id="hidePembaharuan" class="btn btn-secondary btn-sm">Jangan Tampilkan Lagi</button>
                    <button type="button" id="ackPembaharuan" class="btn btn-primary btn-sm" data-dismiss="modal">Mengerti</button>
                </div>
            </div> <!-- /.modal-content -->
        </div> <!-- /.modal-dialog -->
    </div> <!-- /.modal -->
<?php endif; ?>