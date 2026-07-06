<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
        // Ambil user_id dari konteks aplikasi (diasumsikan $data_user tersedia)
        $user_id = $data_user['user_id'] ?? '';

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if (!empty($base_url)) {
            // Jika sudah terdefinisi, hapus segmen /dashboard dan apa pun setelahnya
            $base_url = preg_replace('#/dashboard.*$#', '', rtrim($base_url, '/'));
            // Jika yang tersisa tidak mengandung scheme/host, tambahkan host
            if (!preg_match('#^https?://#', $base_url)) {
                $base_url = rtrim($proto . '://' . $host . '/' . ltrim($base_url, '/'), '/');
            }
        } else {
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $path = rtrim(dirname($script), '/\\');
            $root = preg_replace('#/dashboard.*$#', '', $path);
            if ($root === '') $root = '/';
            $base_url = rtrim($proto . '://' . $host . $root, '/');
        }

        // Ambil filter bulan & tahun dari GET/POST, default sekarang
        $bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('m'));
        $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));

        // Normalisasi
        if ($bulan < 1 || $bulan > 12) $bulan = intval(date('m'));
        if ($tahun < 1970) $tahun = intval(date('Y'));

        // Hitung rentang tanggal
        $start_date = sprintf('%04d-%02d-01', $tahun, $bulan);
        $days_in_month = date('t', strtotime($start_date));
        $end_date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $days_in_month);

        // Ambil seluruh absensi user untuk bulan ini
        $absensi_by_date = [];
        $stmt = $connection->prepare("SELECT id, tanggal, jam_masuk, status_masuk, jam_pulang, status_pulang, kehadiran, foto_masuk, foto_pulang, created_at, updated_at, metode, manual_note FROM absensi WHERE user_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal ASC");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $start_date, $end_date);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $absensi_by_date[$r['tanggal']] = $r;
            }
            $stmt->close();
        }

        // Ambil semua hari libur untuk bulan ini
        $hari_libur_list = [];
        $stmt_libur = $connection->prepare("SELECT nama_libur, tanggal_mulai, tanggal_selesai, keterangan FROM hari_libur WHERE (tanggal_mulai BETWEEN ? AND ?) OR (tanggal_selesai BETWEEN ? AND ?) OR (tanggal_mulai <= ? AND tanggal_selesai >= ?)");
        if ($stmt_libur) {
            $stmt_libur->bind_param("ssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
            $stmt_libur->execute();
            $res_libur = $stmt_libur->get_result();
            while ($hl = $res_libur->fetch_assoc()) {
                $hari_libur_list[] = $hl;
            }
            $stmt_libur->close();
        }

        // Helper: cek apakah tanggal adalah hari libur
        $is_holiday = function($tanggal) use ($hari_libur_list) {
            foreach ($hari_libur_list as $hl) {
                if ($tanggal >= $hl['tanggal_mulai'] && $tanggal <= $hl['tanggal_selesai']) {
                    return true;
                }
            }
            return false;
        };

        // Ambil jadwal aktif (untuk referensi jam kerja jika diperlukan)
        $jadwal_by_hari = [];
        $stmt_jadwal = $connection->prepare("SELECT hari, waktu_mulai, waktu_selesai FROM jadwal WHERE status IN ('Aktif','Y') ORDER BY hari ASC");
        if ($stmt_jadwal) {
            $stmt_jadwal->execute();
            $res_jadwal = $stmt_jadwal->get_result();
            while ($jd = $res_jadwal->fetch_assoc()) {
                $jadwal_by_hari[$jd['hari']] = $jd;
            }
            $stmt_jadwal->close();
        }

        // Siapkan statistik: Kehadiran (Hadir + Terlambat digabung), Alpha, Izin
        $kehadiran_count = 0; // Hadir + Terlambat
        $izin_count = 0;
        $alpha_count = 0;

        // Extra CSS agar konten tidak terhalangi footer fixed dan styling tambahan
        $extra_css = '<style>
          .absensi-page-container { padding-bottom: 140px; }
          @media (min-width: 768px) { .absensi-page-container { padding-bottom: 80px; } }
          .absensi-card { position: relative; z-index: 3; }
          .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
          .table-bordered td, .table-bordered th { vertical-align: middle; white-space: nowrap; }
          .badge { font-weight: 500; }
          .badge-lg { padding: 0.5rem 1rem !important; font-size: 0.95rem !important; }
          @media (max-width: 767.98px) {
            .table-bordered { font-size: 0.85rem; }
            .table-bordered td, .table-bordered th { padding: 0.5rem 0.3rem; }
            .badge-lg { padding: 0.4rem 0.8rem !important; font-size: 0.85rem !important; }
          }
          .gap-2 { gap: 0.5rem !important; }
          .fa-inbox { color: #cbd5e0; }
          .text-muted h5 { font-weight: 600; margin-bottom: 0.5rem; }
          .text-muted p { font-size: 0.95rem; margin-bottom: 0; }
        </style>';

        // Untuk setiap hari, tentukan status dan hitung statistik (abaikan hari libur untuk alpha)
        $rows_html = '';
        for ($d = 1; $d <= $days_in_month; $d++) {
            $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            $day_label = date('D, d M Y', strtotime($tanggal));
            $day_name = date('l', strtotime($tanggal)); // Monday, Tuesday, etc
            $day_name_id = [
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
                'Sunday' => 'Minggu'
            ][$day_name] ?? $day_name;
            
            $is_weekend = in_array(date('N', strtotime($tanggal)), [6,7]); // 6=Sat,7=Sun
            $holiday = $is_holiday($tanggal);
            $jadwal_hari = isset($jadwal_by_hari[$day_name_id]) ? $jadwal_by_hari[$day_name_id] : null;

            $cell_style = '';
            if ($holiday || $is_weekend) {
                $cell_style = ' style="background:#FFEEEE;"';
            }

            if (isset($absensi_by_date[$tanggal])) {
                $a = $absensi_by_date[$tanggal];
                $jam_masuk = htmlspecialchars($a['jam_masuk'] ?: '-');
                $status_masuk = htmlspecialchars($a['status_masuk'] ?: '');
                $jam_pulang = htmlspecialchars($a['jam_pulang'] ?: '-');
                $status_pulang = htmlspecialchars($a['status_pulang'] ?: '');
                $kehadiran = htmlspecialchars($a['kehadiran'] ?: '');

                // Hitung statistik: Kehadiran (Hadir + Terlambat), Alpha, Izin
                $status_lower = strtolower($a['status_masuk']);
                $kehadiran_lower = strtolower($a['kehadiran']);
                
                // Prioritas: cek kehadiran dulu, jika kosong gunakan status_masuk
                if ($kehadiran_lower === 'izin' || $status_lower === 'izin') {
                    $izin_count++;
                } elseif (in_array($status_lower, ['tepat waktu', 'tepatwaktu', 'tepat', 'hadir', 'terlambat'])) {
                    // Hadir atau Terlambat = Kehadiran
                    $kehadiran_count++;
                } elseif ($kehadiran_lower === 'alpha' || $kehadiran_lower === 'alpa') {
                    $alpha_count++;
                }

                // Foto
                $abs_path_masuk = __DIR__ . '/../../../content/capture/' . $a['foto_masuk'];
                $abs_path_pulang = __DIR__ . '/../../../content/capture/' . $a['foto_pulang'];
                $foto_masuk_html = (!empty($a['foto_masuk']) && file_exists($abs_path_masuk))
                    ? '<a href="' . htmlspecialchars($base_url . '/content/capture/' . $a['foto_masuk']) . '" target="_blank" rel="noopener" class="btn btn-sm btn-info">Lihat</a>'
                    : '<span class="text-muted">-</span>';
                $foto_pulang_html = (!empty($a['foto_pulang']) && file_exists($abs_path_pulang))
                    ? '<a href="' . htmlspecialchars($base_url . '/content/capture/' . $a['foto_pulang']) . '" target="_blank" rel="noopener" class="btn btn-sm btn-info">Lihat</a>'
                    : '<span class="text-muted">-</span>';

                // Badge untuk status
                $status_badge = '';
                if ($status_lower === 'tepat waktu' || $status_lower === 'tepatwaktu' || $status_lower === 'tepat' || $status_lower === 'hadir') {
                    $status_badge = '<span class="badge badge-success">' . $status_masuk . '</span>';
                } elseif ($status_lower === 'terlambat') {
                    $status_badge = '<span class="badge badge-warning">' . $status_masuk . '</span>';
                } elseif ($status_lower === 'izin') {
                    $status_badge = '<span class="badge badge-info">' . $status_masuk . '</span>';
                } else {
                    $status_badge = '<span class="badge badge-secondary">' . ($status_masuk ?: '-') . '</span>';
                }

                $rows_html .= '<tr' . $cell_style . '>';
                $rows_html .= '<td class="text-center">' . $d . '</td>';
                $rows_html .= '<td>' . $day_label . '</td>';
                $rows_html .= '<td class="text-center">' . $jam_masuk . '<br>' . $status_badge . '</td>';
                // Metode indicator
                $metode_val = $a['metode'] ?? 'rfid';
                $metode_icon = $metode_val === 'manual' 
                    ? '<br><span class="badge badge-warning badge-sm mt-1" title="Absensi Manual"><i class="fas fa-hand-paper"></i> Manual</span>'
                    : '';
                $rows_html .= '<td class="text-center">' . $jam_pulang . '<br><small class="text-muted">' . $status_pulang . '</small>' . $metode_icon . '</td>';
                $rows_html .= '<td class="text-center">' . ($kehadiran ?: '<span class="text-muted">-</span>') . '</td>';
                $rows_html .= '<td class="text-center">' . $foto_masuk_html . '</td>';
                $rows_html .= '<td class="text-center">' . $foto_pulang_html . '</td>';
                $rows_html .= '</tr>';
            } else {
                // Tidak ada record absensi
                if (!$holiday && !$is_weekend) {
                    // dianggap alpha (tidak hadir)
                    $alpha_count++;
                }
                $label = $holiday ? '<span class="badge badge-danger">Libur</span>' : ($is_weekend ? '<span class="badge badge-secondary">Akhir Pekan</span>' : '<span class="text-muted">-</span>');
                $rows_html .= '<tr' . $cell_style . '>';
                $rows_html .= '<td class="text-center">' . $d . '</td>';
                $rows_html .= '<td>' . $day_label . '</td>';
                $rows_html .= '<td class="text-center" colspan="5">' . $label . '</td>';
                $rows_html .= '</tr>';
            }
        }

        // Cek apakah ada data absensi sama sekali
        $has_absensi_data = !empty($absensi_by_date);
        
        // Jika tidak ada data absensi sama sekali, tampilkan pesan
        if (!$has_absensi_data) {
            $rows_html = '<tr><td colspan="7" class="text-center text-muted py-5">';
            $rows_html .= '<i class="fas fa-inbox fa-3x mb-3 d-block"></i>';
            $rows_html .= '<h5>Belum Ada Riwayat Absensi</h5>';
            $rows_html .= '<p>Tidak ada data absensi untuk bulan ' . htmlspecialchars(ambilbulan($bulan)) . ' ' . htmlspecialchars($tahun) . '</p>';
            $rows_html .= '</td></tr>';
            
            // Reset statistik ke 0
            $kehadiran_count = 0;
            $izin_count = 0;
            $alpha_count = 0;
        }

        // Siapkan HTML untuk select bulan dan tahun (digunakan di header)
        $bulan_select_html = '';
        for ($m = 1; $m <= 12; $m++) {
            $sel = $m == $bulan ? ' selected' : '';
            $bulan_select_html .= '<option value="' . $m . '"' . $sel . '>' . ambilbulan($m) . '</option>';
        }

        $tahun_select_html = '';
        $start_year = date('Y') - 2;
        for ($y = $start_year; $y <= date('Y') + 1; $y++) {
            $sel = $y == $tahun ? ' selected' : '';
            $tahun_select_html .= '<option value="' . $y . '"' . $sel . '>' . $y . '</option>';
        }

        // Tampilkan halaman (filter form dipindahkan ke header agar selalu terlihat)
        echo $extra_css;
        ?>
        <div class="header bg-primary pb-6">
            <div class="container-fluid">
                <div class="header-body">
                    <div class="row align-items-center py-4">
                        <div class="col-lg-6 col-7">
                            <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                                    <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Riwayat Absensi</li>
                                </ol>
                            </nav>
                        </div>
                        <!-- Hapus filter dari header -->
                        <!-- <div class="col-lg-6 col-5 d-flex justify-content-end"> ... </div> -->
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid mt--6 absensi-page-container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card mb-4 absensi-card">
                        <div class="card-header">
                            <h3 class="mb-2">Riwayat Absensi Bulan <?php echo htmlspecialchars(ambilbulan($bulan)) . ' ' . htmlspecialchars($tahun); ?></h3>
                            <?php if (count($hari_libur_list) > 0): ?>
                            <small class="text-muted">
                                <i class="fas fa-calendar-times"></i> Terdapat <?php echo count($hari_libur_list); ?> periode hari libur bulan ini
                            </small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <!-- Filter bulan/tahun di dalam card, di bawah judul -->
                            <form id="absensiFilterForm" method="get" class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center mb-4 gap-2 gap-md-0">
                                <input type="hidden" name="mod" value="absensi">
                                <div class="mb-2 mb-md-0 mr-md-2" style="min-width:120px;">
                                    <select name="bulan" class="form-control">
                                        <?php echo $bulan_select_html; ?>
                                    </select>
                                </div>
                                <div class="mb-2 mb-md-0 mr-md-2" style="min-width:100px;">
                                    <select name="tahun" class="form-control">
                                        <?php echo $tahun_select_html; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-light" style="min-width:80px;">Filter</button>
                            </form>
                            <style>
                                #absensiFilterForm {
                                    gap: 8px;
                                    padding: 1rem;
                                    background: #f8f9fa;
                                    border-radius: 0.5rem;
                                    border: 1px solid #e3e6f0;
                                }
                                #absensiFilterForm .form-control {
                                    border: 1px solid #d1d3e2;
                                    border-radius: 0.35rem;
                                    transition: all 0.2s ease;
                                }
                                #absensiFilterForm .form-control:focus {
                                    border-color: #14b8a6;
                                    box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.25);
                                }
                                #absensiFilterForm .btn {
                                    border-radius: 0.35rem;
                                    transition: all 0.2s ease;
                                }
                                @media (max-width: 767.98px) {
                                    #absensiFilterForm {
                                        flex-direction: column;
                                        align-items: stretch;
                                    }
                                    #absensiFilterForm > div,
                                    #absensiFilterForm > select,
                                    #absensiFilterForm > button {
                                        margin-right: 0 !important;
                                        margin-bottom: 8px;
                                        width: 100%;
                                    }
                                    #absensiFilterForm > button {
                                        margin-bottom: 0;
                                    }
                                }
                                @media (min-width: 768px) {
                                    #absensiFilterForm {
                                        flex-direction: row;
                                        align-items: center;
                                    }
                                    #absensiFilterForm > div,
                                    #absensiFilterForm > select,
                                    #absensiFilterForm > button {
                                        margin-bottom: 0 !important;
                                        margin-right: 8px;
                                    }
                                    #absensiFilterForm > button {
                                        margin-right: 0;
                                    }
                                }
                            </style>
                            <!-- Tabel absensi -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light text-center">
                                        <tr>
                                            <th>Hari</th>
                                            <th>Tanggal</th>
                                            <th>Jam Masuk</th>
                                            <th>Jam Pulang</th>
                                            <th>Kehadiran</th>
                                            <th>Foto Masuk</th>
                                            <th>Foto Pulang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php echo $rows_html; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 p-3 bg-light rounded">
                                <h5 class="mb-3">Ringkasan Kehadiran:</h5>
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="mb-2">
                                        <span class="badge badge-success badge-lg px-3 py-2" style="font-size: 1rem;">
                                            <i class="fas fa-check-circle"></i> Kehadiran: <?php echo intval($kehadiran_count); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge badge-info badge-lg px-3 py-2" style="font-size: 1rem;">
                                            <i class="fas fa-file-medical"></i> Izin: <?php echo intval($izin_count); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge badge-danger badge-lg px-3 py-2" style="font-size: 1rem;">
                                            <i class="fas fa-times-circle"></i> Alpha: <?php echo intval($alpha_count); ?>
                                        </span>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle"></i> Kehadiran = Hadir + Terlambat
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- spacer tambahan agar tidak tertutup footer -->
            <div style="height:140px" aria-hidden="true"></div>
        </div>
    <?php
    // Pastikan scripts.js dimuat agar filter AJAX berjalan
    echo '<script src="mod/absensi/scripts.js"></script>';
    } else {
        ?>
        <div class="container mt-5">
            <div class="alert alert-warning text-center">
                <h4><i class="fas fa-exclamation-triangle"></i> Akses Ditolak</h4>
                <p>Silakan login untuk mengakses riwayat absensi.</p>
                <a href="../" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login Sekarang
                </a>
            </div>
        </div>
        <?php
    }
}
?>