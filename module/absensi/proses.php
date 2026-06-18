<?php
// Set zona waktu ke Asia/Jakarta agar waktu sesuai lokal
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

require_once '../../library/config.php';
require_once '../../library/function.php';
if (!isset($connection) || !$connection || $connection->connect_error) {
    // ob_end_clean(); // HAPUS baris ini
    echo 'Koneksi database gagal';
    exit;
}
// ob_end_clean(); // HAPUS baris ini

function json_response($status, $message, $data = null)
{
    // Ganti semua pemanggilan json_response dengan echo string error saja
    echo $message;
    exit;
}

function hari_indonesia_by_date($tanggal)
{
    $hari_map = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $hari_en = date('l', strtotime($tanggal));
    return isset($hari_map[$hari_en]) ? $hari_map[$hari_en] : $hari_en;
}

function get_jam_pulang_jadwal($connection, $tanggal)
{
    $hari = hari_indonesia_by_date($tanggal);
    $jam_pulang = '23:59:00';

    $stmt = $connection->prepare("SELECT waktu_selesai FROM jadwal WHERE hari=? AND status IN ('Y','Aktif') LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $hari);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (!empty($row['waktu_selesai'])) {
                $jam_pulang = $row['waktu_selesai'];
            }
        }
        $stmt->close();
    }

    return $jam_pulang;
}

function finalize_absensi_lintas_hari($connection, $tanggal_referensi = null)
{
    if (empty($tanggal_referensi)) {
        $tanggal_referensi = date('Y-m-d');
    }

    $total_affected = 0;

    $stmt_tanggal = $connection->prepare("SELECT DISTINCT tanggal FROM absensi WHERE tanggal < ? AND jam_masuk IS NOT NULL AND (jam_pulang IS NULL OR jam_pulang='' OR jam_pulang='00:00:00') AND status_masuk IN ('Tepat Waktu','Terlambat') ORDER BY tanggal ASC");
    if (!$stmt_tanggal) {
        return 0;
    }

    $stmt_tanggal->bind_param('s', $tanggal_referensi);
    $stmt_tanggal->execute();
    $res_tanggal = $stmt_tanggal->get_result();

    if ($res_tanggal && $res_tanggal->num_rows > 0) {
        while ($row_tanggal = $res_tanggal->fetch_assoc()) {
            $tanggal_absen = $row_tanggal['tanggal'];
            $jam_pulang = get_jam_pulang_jadwal($connection, $tanggal_absen);

            $stmt_update = $connection->prepare("UPDATE absensi SET jam_pulang=?, status_pulang='Pulang Cepat', kehadiran=CASE WHEN kehadiran IS NULL OR kehadiran='' OR LOWER(kehadiran)='hadir' THEN 'Lupa absen pulang' ELSE kehadiran END, updated_at=NOW() WHERE tanggal=? AND jam_masuk IS NOT NULL AND (jam_pulang IS NULL OR jam_pulang='' OR jam_pulang='00:00:00') AND status_masuk IN ('Tepat Waktu','Terlambat')");
            if ($stmt_update) {
                $stmt_update->bind_param('ss', $jam_pulang, $tanggal_absen);
                $stmt_update->execute();
                $total_affected += (int)$stmt_update->affected_rows;
                $stmt_update->close();
            }
        }
    }

    $stmt_tanggal->close();
    return $total_affected;
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'scan_rfid':
        // Pastikan data lintas-hari yang belum checkout otomatis ditutup sebagai Pulang Cepat.
        finalize_absensi_lintas_hari($connection, date('Y-m-d'));
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo '[DEBUG] RFID POST: "' . $rfid . '"<br>';
        }
        $rfid = trim($_POST['rfid'] ?? ($_GET['rfid'] ?? ''));
        $foto_capture = $_POST['foto_capture'] ?? '';
        if (empty($rfid)) {
            echo 'RFID tidak boleh kosong.';
            exit;
        }

        // Simpan foto capture jika ada
        $foto_path = '';
        if (!empty($foto_capture) && strpos($foto_capture, 'data:image/jpeg;base64,') === 0) {
            $base64 = str_replace('data:image/jpeg;base64,', '', $foto_capture);
            $base64 = str_replace(' ', '+', $base64);
            $img_data = base64_decode($base64);
            if ($img_data !== false) {
                $nisn = '';
                // Ambil nisn dari user jika sudah ada
                $sql_nisn = "SELECT nisn FROM user WHERE rfid = '$rfid' LIMIT 1";
                $result_nisn = $connection->query($sql_nisn);
                if ($result_nisn && $result_nisn->num_rows > 0) {
                    $row_nisn = $result_nisn->fetch_assoc();
                    $nisn = $row_nisn['nisn'];
                } else {
                    $nisn = preg_replace('/[^0-9]/', '', $rfid);
                }
                $filename = $nisn . '_' . date('Ymd_His') . '.jpg';
                $save_path = '../../content/capture/' . $filename;
                // Pastikan folder ada
                if (!is_dir('../../content/capture')) {
                    mkdir('../../content/capture', 0777, true);
                }
                if (file_put_contents($save_path, $img_data)) {
                    $foto_path = $filename;
                }
            }
        }
        // Ambil data user beserta nama kelas
        $sql = "SELECT u.nisn, u.nama_lengkap, u.status, u.user_id, k.nama_kelas, u.avatar
                FROM user u
                LEFT JOIN kelas k ON u.kelas = k.kelas_id
                WHERE u.rfid = '$rfid' LIMIT 1";
        $result_user = $connection->query($sql);

        if (!$result_user || $result_user->num_rows == 0) {
            echo 'Kartu RFID tidak terdaftar dalam sistem.';
            exit;
        }
        $data_user = $result_user->fetch_assoc();
        if (strtolower($data_user['status']) !== 'aktif') {
            echo 'Siswa tidak aktif';
            exit;
        }
        $user_id = $data_user['user_id'];
        $nama_kelas = !empty($data_user['nama_kelas']) ? $data_user['nama_kelas'] : '-';
        $tanggal = date('Y-m-d');
        $jam_sekarang = date('H:i:s');
        // 1. Cek hari libur
        $q_libur = $connection->query("SELECT * FROM hari_libur WHERE '$tanggal' BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
        if ($q_libur && $q_libur->num_rows > 0) {
            $libur = $q_libur->fetch_assoc();
            echo 'Hari ini adalah hari libur: ' . $libur['keterangan'];
            exit;
        }
        // 2. Cek jadwal
        $hari = date('l');
        $hari_indonesia = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        $hari_ini = $hari_indonesia[$hari];
        $q_jadwal = $connection->query("SELECT * FROM jadwal WHERE hari='$hari_ini' AND status='Y' LIMIT 1");
        if (!$q_jadwal || $q_jadwal->num_rows == 0) {
            echo 'Tidak ada jadwal aktif untuk hari ' . $hari_ini;
            exit;
        }
        $jadwal = $q_jadwal->fetch_assoc();
        $jadwal_masuk = $jadwal['waktu_mulai'];
        $jadwal_pulang = $jadwal['waktu_selesai'];
        // 3. Cek lokasi (opsional, jika ada data lokasi aktif)
        $q_lokasi = $connection->query("SELECT * FROM lokasi WHERE status='aktif' LIMIT 1");
        if ($q_lokasi && $q_lokasi->num_rows > 0) {
            $lokasi = $q_lokasi->fetch_assoc();
            $latitude = floatval($_POST['latitude'] ?? 0);
            $longitude = floatval($_POST['longitude'] ?? 0);

            // Validasi: jika data lokasi tidak valid, beri peringatan tapi tetap lanjutkan
            if ($latitude == 0 || $longitude == 0) {
                // Log warning tapi tidak blokir absensi
                error_log("GPS Warning: Data lokasi tidak valid untuk user_id $user_id (lat: $latitude, lng: $longitude)");
                // Tetap lanjutkan proses absensi tanpa validasi jarak
            } else {
                // Validasi jarak jika GPS data valid
                $earth_radius = 6371000;
                $lat1 = deg2rad($latitude);
                $lon1 = deg2rad($longitude);
                $lat2 = deg2rad($lokasi['latitude']);
                $lon2 = deg2rad($lokasi['longitude']);
                $dlat = $lat2 - $lat1;
                $dlon = $lon2 - $lon1;
                $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $jarak = $earth_radius * $c;
                if ($jarak > $lokasi['radius']) {
                    echo 'Anda berada di luar area absensi (jarak: ' . round($jarak) . ' meter)';
                    exit;
                }
            }
        }
        // 4. Cek izin
        $q_izin = $connection->query("SELECT * FROM izin WHERE user_id='$user_id' AND '$tanggal' BETWEEN tanggal_mulai AND tanggal_selesai AND status_izin='Disetujui' LIMIT 1");
        if ($q_izin && $q_izin->num_rows > 0) {
            $izin_row = $q_izin->fetch_assoc();
            $jenis_izin = strtolower($izin_row['jenis_izin'] ?? '');
            $kehadiran = 'Izin';
            if ($jenis_izin == 'sakit' || strtolower($izin_row['keterangan'] ?? '') == 'sakit') {
                $kehadiran = 'Sakit';
            }
            // Izin atau sakit, update/insert absensi
            $q_absen = $connection->query("SELECT * FROM absensi WHERE user_id='$user_id' AND tanggal='$tanggal' LIMIT 1");
            if ($q_absen && $q_absen->num_rows > 0) {
                $connection->query("UPDATE absensi SET kehadiran='$kehadiran', status_masuk='$kehadiran', status_pulang='$kehadiran', updated_at=NOW(), foto_masuk='$foto_path' WHERE user_id='$user_id' AND tanggal='$tanggal'");
            } else {
                $connection->query("INSERT INTO absensi (user_id, tanggal, jam_masuk, kehadiran, status_masuk, status_pulang, created_at, foto_masuk) VALUES ('$user_id', '$tanggal', '$jam_sekarang', '$kehadiran', '$kehadiran', '$kehadiran', NOW(), '$foto_path')");
            }
            echo 'success/' . $data_user['nisn'] . '/' . $data_user['nama_lengkap'] . '/' . $nama_kelas . '/' . $jam_sekarang . '/' . (!empty($data_user['avatar']) ? $data_user['avatar'] : '');
            exit;
        }
        // 5. Cek absensi hari ini
        $q_absen = $connection->query("SELECT * FROM absensi WHERE user_id='$user_id' AND tanggal='$tanggal' LIMIT 1");
        if ($q_absen && $q_absen->num_rows > 0) {
            $absen = $q_absen->fetch_assoc();
            // Cek jeda minimal 10 menit dari jam_masuk
            $waktu_masuk = strtotime($absen['jam_masuk']);
            $waktu_sekarang = strtotime($jam_sekarang);
            $selisih_menit = ($waktu_sekarang - $waktu_masuk) / 60;
            if ($selisih_menit < 10 && empty($absen['jam_pulang'])) {
                echo 'Anda sudah absen masuk, silakan absen pulang atau tunggu minimal 10 menit.';
                exit;
            }
            if (empty($absen['jam_pulang'])) {
                // Belum absen pulang, update jam_pulang
                // Cek: tidak boleh lebih dari 2 jam setelah jadwal pulang
                $batas_akhir_pulang = strtotime($jadwal_pulang) + (2 * 3600);
                if (strtotime($jam_sekarang) > $batas_akhir_pulang) {
                    echo 'Waktu absen pulang sudah ditutup (maks 2 jam setelah jadwal pulang ' . date('H:i', strtotime($jadwal_pulang)) . ').';
                    exit;
                }
                $status_pulang = '';
                if (strtotime($jam_sekarang) < strtotime($jadwal_pulang)) {
                    $status_pulang = 'Pulang Cepat';
                } else {
                    $status_pulang = 'Pulang';
                }
                $connection->query("UPDATE absensi SET jam_pulang='$jam_sekarang', status_pulang='$status_pulang', updated_at=NOW(), foto_pulang='$foto_path' WHERE id='{$absen['id']}'");
                echo 'success/' . $data_user['nisn'] . '/' . $data_user['nama_lengkap'] . '/' . $nama_kelas . '/' . $jam_sekarang . '/' . (!empty($data_user['avatar']) ? $data_user['avatar'] : '');
                exit;
            } else {
                // Sudah absen masuk dan pulang
                echo 'Absensi hari ini sudah lengkap (masuk: ' . $absen['jam_masuk'] . ', pulang: ' . $absen['jam_pulang'] . ')';
                exit;
            }
        } else {
            // Belum absen hari ini, proses absen masuk
            // Cek: tidak boleh lebih dari 2 jam sebelum jadwal masuk
            $batas_awal = strtotime($jadwal_masuk) - (2 * 3600);
            if (strtotime($jam_sekarang) < $batas_awal) {
                echo 'Absensi masuk belum dibuka. Maksimal 2 jam sebelum jadwal masuk (' . date('H:i', strtotime($jadwal_masuk)) . ').';
                exit;
            }
            // Cek: tidak boleh lebih dari 2 jam setelah jadwal pulang (sudah terlalu malam)
            $batas_akhir_masuk = strtotime($jadwal_pulang) + (2 * 3600);
            if (strtotime($jam_sekarang) > $batas_akhir_masuk) {
                echo 'Waktu absensi sudah ditutup untuk hari ini.';
                exit;
            }
            $status_masuk = (strtotime($jam_sekarang) <= strtotime($jadwal_masuk)) ? 'Tepat Waktu' : 'Terlambat';
            $connection->query("INSERT INTO absensi (user_id, tanggal, jam_masuk, kehadiran, status_masuk, created_at, foto_masuk) VALUES ('$user_id', '$tanggal', '$jam_sekarang', 'Hadir', '$status_masuk', NOW(), '$foto_path')");
            echo 'success/' . $data_user['nisn'] . '/' . $data_user['nama_lengkap'] . '/' . $nama_kelas . '/' . $jam_sekarang . '/' . (!empty($data_user['avatar']) ? $data_user['avatar'] : '');
            exit;
        }

    case 'finalize_rollover':
        $affected = finalize_absensi_lintas_hari($connection, date('Y-m-d'));
        echo 'success/' . $affected;
        exit;

    case 'get_stats':
        // Mendapatkan statistik absensi hari ini
        $tanggal = $_POST['date'] ?? date('Y-m-d');

        // Statistik hari ini
        $stats_today = [
            'tepat_waktu' => 0,
            'terlambat' => 0,
            'pulang' => 0,
            'pulang_cepat' => 0,
            'izin' => 0,
            'alpha' => 0
        ];

        $stmt_today = $connection->prepare("SELECT status_masuk, COUNT(*) as jumlah FROM absensi WHERE tanggal=? GROUP BY status_masuk");
        $stmt_today->bind_param("s", $tanggal);
        $stmt_today->execute();
        $result_today = $stmt_today->get_result();
        if ($result_today) {
            while ($row = $result_today->fetch_assoc()) {
                $status = strtolower(str_replace(' ', '_', $row['status_masuk']));
                if (isset($stats_today[$status])) {
                    $stats_today[$status] = (int)$row['jumlah'];
                }
            }
        }
        $stmt_today->close();

        // Statistik bulan ini
        $bulan_ini = date('Y-m');
        $stats_month = [
            'tepat_waktu' => 0,
            'terlambat' => 0,
            'pulang' => 0,
            'pulang_cepat' => 0,
            'izin' => 0,
            'alpha' => 0
        ];

        $bulan_pattern = $bulan_ini . '%';
        $stmt_month = $connection->prepare("SELECT status_masuk, COUNT(*) as jumlah FROM absensi WHERE tanggal LIKE ? GROUP BY status_masuk");
        $stmt_month->bind_param("s", $bulan_pattern);
        $stmt_month->execute();
        $result_month = $stmt_month->get_result();
        if ($result_month) {
            while ($row = $result_month->fetch_assoc()) {
                $status = strtolower(str_replace(' ', '_', $row['status_masuk']));
                if (isset($stats_month[$status])) {
                    $stats_month[$status] = (int)$row['jumlah'];
                }
            }
        }
        $stmt_month->close();

        // Total siswa aktif untuk persentase
        $query_total = "SELECT COUNT(*) as total FROM user WHERE status='Aktif'";
        $result_total = $connection->query($query_total);
        $total_siswa = $result_total ? $result_total->fetch_assoc()['total'] : 0;

        // Hitung persentase kehadiran hari ini dan bulan ini
        $kehadiran_hari_ini = $stats_today['tepat_waktu'] + $stats_today['terlambat'];
        $kehadiran_bulan_ini = $stats_month['tepat_waktu'] + $stats_month['terlambat'];

        $persentase_hari_ini = $total_siswa > 0 ? round(($kehadiran_hari_ini / $total_siswa) * 100, 1) : 0;
        $persentase_bulan_ini = $total_siswa > 0 ? round(($kehadiran_bulan_ini / ($total_siswa * date('j'))) * 100, 1) : 0;

        json_response('success', 'Statistik berhasil dimuat', [
            'today' => $stats_today,
            'month' => $stats_month,
            'total_siswa' => $total_siswa,
            'persentase_hari_ini' => $persentase_hari_ini,
            'persentase_bulan_ini' => $persentase_bulan_ini
        ]);
        break;

    case 'get_recent_attendance':
        // Mendapatkan riwayat absensi terbaru
        $limit = (int)($_POST['limit'] ?? 10);
        $tanggal = $_POST['date'] ?? date('Y-m-d');

        $stmt_recent = $connection->prepare("SELECT a.*, u.nama_lengkap, u.nisn, u.kelas 
                  FROM absensi a 
                  JOIN user u ON a.user_id = u.user_id 
                  WHERE a.tanggal = ? 
                  ORDER BY a.created_at DESC, a.jam_masuk DESC 
                  LIMIT ?");
        $stmt_recent->bind_param("si", $tanggal, $limit);
        $stmt_recent->execute();
        $result = $stmt_recent->get_result();
        $attendance = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $attendance[] = [
                    'nama' => $row['nama_lengkap'],
                    'nisn' => $row['nisn'],
                    'kelas' => $row['kelas'],
                    'jam_masuk' => $row['jam_masuk'],
                    'jam_pulang' => $row['jam_pulang'],
                    'status_masuk' => $row['status_masuk'],
                    'status_pulang' => $row['status_pulang'],
                    'kehadiran' => $row['kehadiran'],
                    'tanggal' => $row['tanggal']
                ];
            }
        }
        $stmt_recent->close();

        json_response('success', 'Riwayat berhasil dimuat', $attendance);
        break;

    case 'close_attendance':
        // Tutup absensi untuk tanggal tertentu lalu jalankan finalisasi lintas-hari.
        $tanggal = $_POST['date'] ?? date('Y-m-d');
        $jam_tutup = $_POST['close_time'] ?? '';
        if (empty($jam_tutup)) {
            $jam_tutup = get_jam_pulang_jadwal($connection, $tanggal);
        }

        // Update tanggal target.
        $stmt_close = $connection->prepare("UPDATE absensi 
                 SET jam_pulang=?, status_pulang='Pulang Cepat', kehadiran=CASE WHEN kehadiran IS NULL OR kehadiran='' OR LOWER(kehadiran)='hadir' THEN 'Lupa absen pulang' ELSE kehadiran END, updated_at=NOW()
                 WHERE tanggal=? 
                 AND jam_masuk IS NOT NULL 
                 AND (jam_pulang IS NULL OR jam_pulang='' OR jam_pulang='00:00:00')
                 AND status_masuk IN ('Tepat Waktu', 'Terlambat')");
        $stmt_close->bind_param("ss", $jam_tutup, $tanggal);
        $stmt_close->execute();
        $affected_manual = $stmt_close->affected_rows;
        $stmt_close->close();

        // Finalisasi semua data tanggal lampau yang belum checkout.
        $affected_auto = finalize_absensi_lintas_hari($connection, date('Y-m-d'));

        $total_affected = $affected_manual + $affected_auto;
        json_response('success', "$total_affected siswa yang lupa absen pulang telah ditandai sebagai 'Pulang Cepat'");
        break;

    default:
        json_response('error', 'Action tidak dikenali');
        break;
}
