<?php

// Set zona waktu ke Asia/Jakarta agar waktu sesuai lokal
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

require_once '../../library/config.php';
require_once '../../library/function.php';

// Headers untuk API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

switch (@$_GET['action']) {
    case 'get_statistics':
        // Mendapatkan statistik keseluruhan kualitas data sesuai permintaan
        $stats = array();

        // Filter berdasarkan parameter
        $filter_jurusan = isset($_GET['jurusan']) ? $_GET['jurusan'] : '';
        $filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

        $where_clause_user = "WHERE 1=1";
        $where_clause_berkas = "WHERE 1=1";
        if (!empty($filter_jurusan)) {
            $where_clause_user .= " AND k.jurusan_id = '" . mysqli_real_escape_string($connection, $filter_jurusan) . "'";
            $where_clause_berkas .= " AND k.jurusan_id = '" . mysqli_real_escape_string($connection, $filter_jurusan) . "'";
        }
        if (!empty($filter_kelas)) {
            $where_clause_user .= " AND u.kelas = '" . mysqli_real_escape_string($connection, $filter_kelas) . "'";
            $where_clause_berkas .= " AND u.kelas = '" . mysqli_real_escape_string($connection, $filter_kelas) . "'";
        }

        // Total siswa
        // Count only active users (allow '1' or 'aktif' with case/whitespace tolerance)
        $active_check = "(u.status = '1' OR LOWER(TRIM(u.status)) = 'aktif')";
        $query_total = "SELECT COUNT(DISTINCT u.user_id) as total_siswa FROM user u LEFT JOIN kelas k ON u.kelas = k.kelas_id $where_clause_user AND " . $active_check;
        $result_total = $connection->query($query_total);
        $total_siswa = 0;
        if ($result_total && $row = $result_total->fetch_assoc()) {
            $total_siswa = intval($row['total_siswa']);
        }

        // Hitung konfirmasi-only (user.konfirmasi = 'Sesuai' OR 'Belum Sesuai')
        $query_konfirmasi_only = "SELECT COUNT(DISTINCT u.user_id) as data_konfirmasi FROM user u LEFT JOIN kelas k ON u.kelas = k.kelas_id $where_clause_user AND " . $active_check . " AND (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai')";
        $result_konfirmasi_only = $connection->query($query_konfirmasi_only);
        $data_konfirmasi = 0;
        if ($result_konfirmasi_only && $row = $result_konfirmasi_only->fetch_assoc()) {
            $data_konfirmasi = intval($row['data_konfirmasi']);
        }

        // Data valid (union): tetap hitung sebagai integer untuk kartu informasi
        $query_union = "SELECT COUNT(DISTINCT u.user_id) as data_valid FROM user u LEFT JOIN berkas b ON b.user_id = u.user_id LEFT JOIN kelas k ON u.kelas = k.kelas_id $where_clause_user AND " . $active_check . " AND (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai' OR b.validasi_berkas = 'valid')";
        $result_union = $connection->query($query_union);
        $data_valid = 0;
        if ($result_union && $row = $result_union->fetch_assoc()) {
            $data_valid = intval($row['data_valid']);
        }

        // Hitung jumlah_kualitas berbobot: 50% identitas + 50% berkas valid (dapat bernilai desimal)
        $query_kualitas = "SELECT SUM((CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5 + (CASE WHEN EXISTS(SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND LOWER(b.validasi_berkas) = 'valid') THEN 1 ELSE 0 END) * 0.5) AS jumlah_kualitas FROM user u LEFT JOIN kelas k ON u.kelas = k.kelas_id $where_clause_user AND " . $active_check;
        $result_kualitas = $connection->query($query_kualitas);
        $jumlah_kualitas = 0.0;
        if ($result_kualitas && $row = $result_kualitas->fetch_assoc()) {
            $jumlah_kualitas = floatval($row['jumlah_kualitas']);
        }

        // Berkas Valid (distinct users with valid berkas)
        // Count distinct users with valid berkas but only when the user is active
        $query_berkas = "SELECT COUNT(DISTINCT b.user_id) as data_review FROM berkas b LEFT JOIN user u ON b.user_id = u.user_id LEFT JOIN kelas k ON u.kelas = k.kelas_id $where_clause_berkas AND LOWER(b.validasi_berkas) = 'valid' AND (u.status = '1' OR LOWER(TRIM(u.status)) = 'aktif')";
        $result_berkas = $connection->query($query_berkas);
        $data_review = 0;
        if ($result_berkas && $row = $result_berkas->fetch_assoc()) {
            $data_review = intval($row['data_review']);
        }

        $data_tidak_valid = $total_siswa - $data_valid;
        // persen berdasarkan jumlah_kualitas berbobot
        $persen_kualitas = $total_siswa > 0 ? round(($jumlah_kualitas / $total_siswa) * 100, 1) : 0;

        $stats = array(
            'total_siswa' => $total_siswa,
            // konfirmasi-only (for the Konfirmasi card)
            'data_konfirmasi' => $data_konfirmasi,
            // union (konfirmasi OR berkas valid) used for Kualitas
            'data_valid' => $data_valid,
            'data_review' => $data_review,
            'data_tidak_valid' => $data_tidak_valid,
            // expose jumlah_kualitas (float) dan persen berbasis bobot 50/50
            'jumlah_kualitas' => $jumlah_kualitas,
            'persen_kualitas' => $persen_kualitas
        );

        echo json_encode($stats);
        break;

    case 'get_chart_data':
        // Data untuk chart kualitas
        $filter_jurusan = isset($_GET['jurusan']) ? $_GET['jurusan'] : '';
        $filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

        $where_clause = "WHERE 1=1";
        if (!empty($filter_jurusan)) {
            $where_clause .= " AND k.jurusan_id = '" . mysqli_real_escape_string($connection, $filter_jurusan) . "'";
        }
        if (!empty($filter_kelas)) {
            $where_clause .= " AND u.kelas = '" . mysqli_real_escape_string($connection, $filter_kelas) . "'";
        }

        $query_chart = "SELECT 
            SUM(
                (
                    (CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5
                    +
                    (CASE WHEN EXISTS(SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid') THEN 1 ELSE 0 END) * 0.5
                )
            ) as valid,
            SUM(
                CASE WHEN (
                    NOT (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai' OR EXISTS(SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid'))
                    AND EXISTS(SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND (b.validasi_berkas = 'revisi' OR b.validasi_berkas = '' OR b.validasi_berkas IS NULL))
                ) THEN 1 ELSE 0 END
            ) as review,
            COUNT(DISTINCT u.user_id) as total
            FROM user u 
            LEFT JOIN kelas k ON u.kelas = k.kelas_id 
            $where_clause AND (u.status = '1' OR LOWER(TRIM(u.status)) = 'aktif')";

        $result_chart = $connection->query($query_chart);
        if ($result_chart && $row = $result_chart->fetch_assoc()) {
            // valid mungkin berupa nilai desimal karena bobot 50/50
            $valid = floatval($row['valid']);
            $review = intval($row['review']);
            $total = intval($row['total']);
            // tidak_valid dapat menjadi desimal jika valid adalah desimal
            $tidak_valid = $total - $valid - $review;

            $chart_data = array(
                'labels' => ['Valid', 'Review', 'Tidak Valid'],
                'data' => [$valid, $review, $tidak_valid],
                'colors' => ['#10b981', '#f59e0b', '#f43f5e']
            );

            echo json_encode($chart_data);
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
        break;

    case 'get_jurusan_chart':
        // Data chart per jurusan
        $query_jurusan = "SELECT 
            j.nama_jurusan,
            COUNT(DISTINCT u.user_id) as total_siswa,
            SUM(
                (
                    (CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5
                    +
                    (CASE WHEN EXISTS(SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid') THEN 1 ELSE 0 END) * 0.5
                )
            ) as valid_siswa
            FROM jurusan j
            LEFT JOIN kelas k ON j.jurusan_id = k.jurusan_id
            LEFT JOIN user u ON k.kelas_id = u.kelas AND (u.status = '1' OR LOWER(TRIM(u.status)) = 'aktif')
            GROUP BY j.jurusan_id, j.nama_jurusan
            ORDER BY j.nama_jurusan";

        $result_jurusan = $connection->query($query_jurusan);
        $jurusan_data = array(
            'labels' => array(),
            'data' => array(),
            'colors' => array('#3b82f6', '#10b981', '#f59e0b', '#f43f5e', '#6366f1', '#14b8a6')
        );

        if ($result_jurusan) {
            while ($row = $result_jurusan->fetch_assoc()) {
                $total = intval($row['total_siswa']);
                // valid_siswa sekarang bernilai desimal (bobots)
                $valid = floatval($row['valid_siswa']);
                $persen = $total > 0 ? round(($valid / $total) * 100, 1) : 0;

                // Singkat nama jurusan
                $nama_jurusan_singkat = $row['nama_jurusan'];
                if (strpos($nama_jurusan_singkat, 'Agribisnis Tanaman Pangan dan Hortikultura') !== false) {
                    $nama_jurusan_singkat = 'ATPH';
                } elseif (strpos($nama_jurusan_singkat, 'Kehutanan') !== false) {
                    $nama_jurusan_singkat = 'KHT';
                } elseif (strpos($nama_jurusan_singkat, 'Desain Komunikasi Visual') !== false) {
                    $nama_jurusan_singkat = 'DKV';
                } elseif (strpos($nama_jurusan_singkat, 'Teknik Komputer dan Jaringan') !== false) {
                    $nama_jurusan_singkat = 'TKJ';
                } elseif (strpos($nama_jurusan_singkat, 'Teknik Kendaraan Ringan') !== false) {
                    $nama_jurusan_singkat = 'TKR';
                }

                $jurusan_data['labels'][] = $nama_jurusan_singkat;
                $jurusan_data['data'][] = $persen;
            }
        }

        echo json_encode($jurusan_data);
        break;

    case 'get_top_classes':
        // Top 10 kelas dengan kualitas data terbaik
        // Use same aggregation logic as admin/mod/kelas/datatable.php to ensure consistent rating
        $query_top = "SELECT 
            k.nama_kelas,
            j.nama_jurusan,
            COUNT(DISTINCT u.user_id) as total_siswa,
            SUM(
                (
                    (CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5
                    +
                    (CASE WHEN EXISTS (
                        SELECT 1 FROM berkas b
                        WHERE b.user_id = u.user_id
                        AND b.validasi_berkas = 'valid'
                    ) THEN 1 ELSE 0 END) * 0.5
                )
            ) as valid_siswa
            FROM kelas k
            LEFT JOIN jurusan j ON k.jurusan_id = j.jurusan_id
            LEFT JOIN user u ON k.kelas_id = u.kelas AND (u.status = '1' OR LOWER(TRIM(u.status)) = 'aktif')
            GROUP BY k.kelas_id, k.nama_kelas, j.nama_jurusan
            HAVING COUNT(DISTINCT u.user_id) > 0
            ORDER BY (SUM((CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5 + (CASE WHEN EXISTS (SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid') THEN 1 ELSE 0 END) * 0.5) / COUNT(DISTINCT u.user_id)) DESC,
                     SUM((CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5 + (CASE WHEN EXISTS (SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid') THEN 1 ELSE 0 END) * 0.5) DESC";

        $result_top = $connection->query($query_top);
        $top_classes = array();

        // If query succeeded, collect rows, compute ratio in PHP, sort and take top 10
        if ($result_top) {
            $rows = array();
            while ($row = $result_top->fetch_assoc()) {
                $total = intval($row['total_siswa']);
                // valid_siswa sekarang bernilai desimal karena bobot 50/50
                $valid = floatval($row['valid_siswa']);
                if ($total <= 0) continue; // skip classes without students
                $ratio = $total > 0 ? ($valid / $total) : 0;
                $rows[] = array(
                    'nama_kelas' => $row['nama_kelas'],
                    'nama_jurusan' => $row['nama_jurusan'],
                    'total_siswa' => $total,
                    'valid_siswa' => $valid,
                    'ratio' => $ratio,
                    'persen_kualitas' => round($ratio * 100, 1)
                );
            }

            // Sort by ratio desc, then by valid_siswa desc
            usort($rows, function ($a, $b) {
                if ($a['ratio'] == $b['ratio']) {
                    return $b['valid_siswa'] - $a['valid_siswa'];
                }
                return ($a['ratio'] < $b['ratio']) ? 1 : -1;
            });

            // Return all classes sorted and add numbering
            $no = 1;
            foreach ($rows as $r) {
                $top_classes[] = array(
                    'no' => $no++,
                    'nama_kelas' => $r['nama_kelas'],
                    'nama_jurusan' => $r['nama_jurusan'],
                    'total_siswa' => $r['total_siswa'],
                    'valid_siswa' => $r['valid_siswa'],
                    'persen_kualitas' => $r['persen_kualitas']
                );
            }
        }

        echo json_encode($top_classes);
        break;

    case 'get_filters':
        // Mendapatkan data untuk filter
        $filters = array(
            'jurusan' => array(),
            'kelas' => array()
        );

        // Ambil data jurusan
        $query_jurusan = "SELECT jurusan_id, nama_jurusan FROM jurusan ORDER BY nama_jurusan";
        $result_jurusan = $connection->query($query_jurusan);
        if ($result_jurusan) {
            while ($row = $result_jurusan->fetch_assoc()) {
                $filters['jurusan'][] = array(
                    'id' => $row['jurusan_id'],
                    'nama' => $row['nama_jurusan']
                );
            }
        }

        // Ambil data kelas
        $query_kelas = "SELECT k.kelas_id, k.nama_kelas, k.jurusan_id, j.nama_jurusan 
                       FROM kelas k 
                       LEFT JOIN jurusan j ON k.jurusan_id = j.jurusan_id 
                       ORDER BY k.nama_kelas";
        $result_kelas = $connection->query($query_kelas);
        if ($result_kelas) {
            while ($row = $result_kelas->fetch_assoc()) {
                $filters['kelas'][] = array(
                    'id' => $row['kelas_id'],
                    'nama' => $row['nama_kelas'],
                    'jurusan' => $row['nama_jurusan'],
                    'jurusan_id' => $row['jurusan_id']
                );
            }
        }

        echo json_encode($filters);
        break;

    case 'get_class_detail':
        // Mendapatkan detail data siswa per kelas dengan sensor data
        $kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
        $kelas_nama = isset($_GET['kelas_nama']) ? $_GET['kelas_nama'] : '';

        // Jika menggunakan nama kelas, cari ID-nya terlebih dahulu
        if (!empty($kelas_nama) && empty($kelas_id)) {
            $query_kelas_id = "SELECT kelas_id FROM kelas WHERE nama_kelas = '" . mysqli_real_escape_string($connection, $kelas_nama) . "' LIMIT 1";
            $result_kelas_id = $connection->query($query_kelas_id);
            if ($result_kelas_id && $row_kelas = $result_kelas_id->fetch_assoc()) {
                $kelas_id = $row_kelas['kelas_id'];
            }
        }

        if (empty($kelas_id)) {
            echo json_encode(['error' => 'Kelas tidak ditemukan']);
            break;
        }

        // Query untuk mendapatkan data siswa dalam kelas tertentu
        // Gabungkan berkas per user (aggregat) agar setiap user hanya muncul sekali
        $query_detail = "SELECT 
            u.user_id,
            u.nisn,
            u.nama_lengkap,
            u.konfirmasi,
            k.nama_kelas,
            j.nama_jurusan,
            bagg.has_valid,
            bagg.has_tidak_valid,
            bagg.has_revisi,
            bagg.kk,
            bagg.ijazah
            FROM user u
            LEFT JOIN kelas k ON u.kelas = k.kelas_id
            LEFT JOIN jurusan j ON k.jurusan_id = j.jurusan_id
            LEFT JOIN (
                SELECT user_id,
                       MAX(CASE WHEN validasi_berkas = 'valid' THEN 1 ELSE 0 END) AS has_valid,
                       MAX(CASE WHEN validasi_berkas = 'tidak_valid' THEN 1 ELSE 0 END) AS has_tidak_valid,
                       MAX(CASE WHEN validasi_berkas = 'revisi' THEN 1 ELSE 0 END) AS has_revisi,
                       MAX(kk) AS kk,
                       MAX(ijazah) AS ijazah
                FROM berkas
                GROUP BY user_id
            ) bagg ON u.user_id = bagg.user_id
            WHERE u.kelas = '" . mysqli_real_escape_string($connection, $kelas_id) . "' 
            AND u.status = 'Aktif'
            ORDER BY u.nama_lengkap ASC";

        $result_detail = $connection->query($query_detail);
        $detail_data = array(
            'kelas_info' => array(),
            'siswa' => array(),
            'stats' => array()
        );

        if ($result_detail) {
            $total_siswa = 0;
            // data_valid akan menampung jumlah_kualitas berbobot (float)
            $data_valid = 0.0;
            $kelas_nama = '';
            $jurusan_nama = '';

            while ($row = $result_detail->fetch_assoc()) {
                if (empty($kelas_nama)) {
                    $kelas_nama = $row['nama_kelas'];
                    $jurusan_nama = $row['nama_jurusan'];
                }

                $total_siswa++;

                // Sensor data NISN - tampilkan 5 digit pertama, sisanya *
                $nisn = $row['nisn'];
                $sensor_nisn = strlen($nisn) > 5 ? substr($nisn, 0, 5) . str_repeat('*', strlen($nisn) - 5) : $nisn;

                // Sensor data nama - tampilkan 2 karakter pertama, sisanya *
                $nama = $row['nama_lengkap'];
                $sensor_nama = strlen($nama) > 2 ? substr($nama, 0, 2) . str_repeat('*', strlen($nama) - 2) : $nama;

                // Cek status identitas
                $status_identitas = 'Belum Konfirmasi';
                $badge_identitas = 'secondary';
                if (!empty($row['konfirmasi'])) {
                    if ($row['konfirmasi'] == 'Sesuai') {
                        $status_identitas = 'Sesuai';
                        $badge_identitas = 'success';
                    } else {
                        $status_identitas = 'Belum Sesuai';
                        $badge_identitas = 'warning';
                    }
                }

                // Cek status berkas menggunakan flag agregat dari subquery
                $status_berkas = 'Belum Upload';
                $badge_berkas = 'secondary';
                if (isset($row['has_valid']) && intval($row['has_valid']) === 1) {
                    $status_berkas = 'Valid';
                    $badge_berkas = 'success';
                } else if (isset($row['has_tidak_valid']) && intval($row['has_tidak_valid']) === 1) {
                    $status_berkas = 'Tidak Valid';
                    $badge_berkas = 'danger';
                } else if (isset($row['has_revisi']) && intval($row['has_revisi']) === 1) {
                    $status_berkas = 'Revisi';
                    $badge_berkas = 'warning';
                } else if (!empty($row['kk']) || !empty($row['ijazah'])) {
                    $status_berkas = 'Menunggu Validasi';
                    $badge_berkas = 'info';
                }

                // Tentukan status keseluruhan (sama seperti sebelumnya)
                $is_valid = (isset($row['konfirmasi']) && ($row['konfirmasi'] == 'Sesuai' || $row['konfirmasi'] == 'Belum Sesuai')) || (isset($row['has_valid']) && intval($row['has_valid']) === 1);

                if ($is_valid) {
                    $status_keseluruhan = 'Valid';
                    $badge_keseluruhan = 'success';
                } else if (
                    !empty($row['konfirmasi'])
                    || !empty($row['kk'])
                    || !empty($row['ijazah'])
                    || (isset($row['has_valid']) && intval($row['has_valid']) === 1)
                    || (isset($row['has_tidak_valid']) && intval($row['has_tidak_valid']) === 1)
                    || (isset($row['has_revisi']) && intval($row['has_revisi']) === 1)
                ) {
                    $status_keseluruhan = 'Proses';
                    $badge_keseluruhan = 'warning';
                } else {
                    $status_keseluruhan = 'Belum Lengkap';
                    $badge_keseluruhan = 'danger';
                }

                // Hitung kontribusi kualitas per siswa: 0.5 * identitas + 0.5 * berkas_valid
                $identitas_flag = (isset($row['konfirmasi']) && ($row['konfirmasi'] == 'Sesuai' || $row['konfirmasi'] == 'Belum Sesuai')) ? 1 : 0;
                $berkas_flag = (isset($row['has_valid']) && intval($row['has_valid']) === 1) ? 1 : 0;
                $contrib = ($identitas_flag * 0.5) + ($berkas_flag * 0.5);
                $data_valid += $contrib;

                $detail_data['siswa'][] = array(
                    'nisn_sensor' => $sensor_nisn,
                    'nama_sensor' => $sensor_nama,
                    'konfirmasi' => $row['konfirmasi'],
                    'status_identitas' => $status_identitas,
                    'badge_identitas' => $badge_identitas,
                    'status_berkas' => $status_berkas,
                    'badge_berkas' => $badge_berkas,
                    'status_keseluruhan' => $status_keseluruhan,
                    'badge_keseluruhan' => $badge_keseluruhan,
                    // kontribusi kualitas per siswa (float, 0.0 - 1.0)
                    'kualitas_contrib' => $contrib
                );
            }

            $persen_kualitas = $total_siswa > 0 ? round(($data_valid / $total_siswa) * 100, 1) : 0;

            $detail_data['kelas_info'] = array(
                'nama_kelas' => $kelas_nama,
                'nama_jurusan' => $jurusan_nama
            );

            $detail_data['stats'] = array(
                'total_siswa' => $total_siswa,
                'data_valid' => $data_valid,
                'persen_kualitas' => $persen_kualitas
            );
        }

        echo json_encode($detail_data);
        break;

    case 'cari':
        // Backward compatibility - pencarian NISN lama
        if (empty(htmlentities($_POST['nisn']))) {
            echo 'NISN tidak boleh kosong.';
        } else {
            $nisn = htmlentities($_POST['nisn']);

            $query_user = "SELECT nisn, user_id FROM user WHERE nisn='$nisn'";
            $result_user = $connection->query($query_user);

            if ($result_user->num_rows > 0) {
                $data_user = $result_user->fetch_assoc();
                $user_id = $data_user['user_id'];
                $nisn_encrypted = convert("encrypt", strip_tags($data_user['nisn']));

                $current_date = date('Y-m-d');
                $current_time = date('H:i:s');
                $one_month_ago = date('Y-m-d', strtotime('-1 month'));

                $check_statistik = "SELECT * FROM statistik WHERE user_id='$user_id' AND date BETWEEN '$one_month_ago' AND '$current_date'";
                $result_statistik = $connection->query($check_statistik);

                if ($result_statistik->num_rows > 0) {
                    $data_statistik = $result_statistik->fetch_assoc();
                    $new_jumlah = $data_statistik['jumlah'] + 1;

                    $update_statistik = "UPDATE statistik SET jumlah='$new_jumlah', date='$current_date', time='$current_time' WHERE user_id='$user_id' AND statistik_id='" . $data_statistik['statistik_id'] . "'";
                    if ($connection->query($update_statistik)) {
                        echo 'success/' . $nisn_encrypted;
                    } else {
                        echo 'Error memperbarui statistik: ' . $connection->error;
                    }
                } else {
                    $insert_statistik = "INSERT INTO statistik (user_id, jumlah, date, time) VALUES ('$user_id', 1, '$current_date', '$current_time')";
                    if ($connection->query($insert_statistik)) {
                        echo 'success/' . $nisn_encrypted;
                    } else {
                        echo 'Error memasukkan data ke statistik: ' . $connection->error;
                    }
                }
            } else {
                echo 'NISN yang Anda cari tidak ditemukan, silahkan periksa kembali.';
            }
        }
        break;

    default:
        echo json_encode(['error' => 'Action tidak valid']);
        break;
}
