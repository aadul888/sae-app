<?php
// Basic error reporting and output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type for proper response
header('Content-Type: text/plain; charset=utf-8');

// Start output buffering to catch any unwanted output
ob_start();

session_start();

// Debug log with request info
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'action' => $_GET['action'] ?? 'none',
    'files_count' => count($_FILES),
    'session_exists' => session_id() ? 'yes' : 'no',
    'cookie_exists' => isset($_COOKIE['siswa']) ? 'yes' : 'no'
];
if (function_exists('debug_log')) debug_log(["Absensi request" => $debug_info]);

// Check session/cookie
if (!isset($_COOKIE['siswa'])) {
    ob_clean();
    echo 'Session tidak valid - silakan login ulang';
    exit;
}

try {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    // Manual user authentication (instead of requiring user.php)
    $siswa = convert("decrypt", $_COOKIE['siswa']);
    if (empty($siswa)) {
        ob_clean();
        echo 'Session tidak valid - data tidak dapat didekripsi';
        exit;
    }

    // Get user data from database
    $query_user = "SELECT * FROM user WHERE status='Aktif' AND user_id='" . htmlentities($siswa, ENT_QUOTES, 'UTF-8') . "' LIMIT 1";
    $result_user = $connection->query($query_user);
    if (!$result_user || $result_user->num_rows == 0) {
        ob_clean();
        echo 'User tidak ditemukan atau tidak aktif';
        exit;
    }

    $data_user = $result_user->fetch_assoc();

    // Verify user data
    if (empty($data_user)) {
        ob_clean();
        echo 'Data user tidak ditemukan - silakan login ulang';
        exit;
    }

    if (function_exists('debug_log')) debug_log("User verified: " . ($data_user['user_id'] ?? 'unknown'));
} catch (Exception $e) {
    ob_clean();
    echo 'Error sistem: ' . $e->getMessage();
    if (function_exists('debug_log')) debug_log("Include error: " . $e->getMessage());
    exit;
}

// Setup folder dengan error handling
$folder = '../../../content/berkas/';

// Ensure the path resolves correctly to content/berkas
if (!file_exists('../../../content/')) {
    if (!mkdir('../../../content/', 0755, true)) {
        ob_clean();
        echo 'Gagal membuat folder content';
        if (function_exists('debug_log')) debug_log("Cannot create content folder");
        exit;
    }
}

if (!is_dir($folder)) {
    if (!mkdir($folder, 0755, true)) {
        ob_clean();
        echo 'Gagal membuat folder berkas';
        if (function_exists('debug_log')) debug_log("Cannot create folder: $folder");
        exit;
    }
    if (function_exists('debug_log')) debug_log("Created folder: $folder");
}

// Verify folder permissions
if (!is_writable($folder)) {
    ob_clean();
    echo 'Folder berkas tidak dapat ditulis - periksa permission';
    if (function_exists('debug_log')) debug_log("Folder not writable: $folder");
    exit;
}

// Get absolute path for logging
$folder_absolute = realpath($folder);
if (function_exists('debug_log')) debug_log("Folder ready: $folder (absolute: $folder_absolute)");

switch (@$_GET['action']) {
    /* ---------- ADD BERKAS ---------- */
    case 'add':
        try {
            // Clear any previous output
            ob_clean();

            if (function_exists('debug_log')) debug_log("Processing upload for user: " . $data_user['user_id']);

            $user_id = $data_user['user_id'] ?? '';
            $nisn = $data_user['nisn'] ?? '';

            if (empty($user_id) || empty($nisn)) {
                echo 'Data user tidak lengkap';
                exit;
            }

            // Get uploaded files
            $files = [];
            foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                $files[$field] = $_FILES[$field] ?? null;
            }

            if (function_exists('debug_log')) debug_log(["Files received" => array_map(function ($f) {
                return $f ? ['name' => $f['name'], 'size' => $f['size'], 'error' => $f['error']] : null;
            }, $files)]);

            // Helper function untuk upload
            function uploadFile($file, $jenis, $nisn, $folder)
            {
                if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                    return '';
                }

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Error upload $jenis: " . $file['error']);
                }

                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime_type, $allowed_types)) {
                    throw new Exception("Tipe file $jenis tidak didukung. Hanya JPG, PNG, atau PDF yang diizinkan.");
                }

                // Validate file size (5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception("File $jenis terlalu besar (max 5MB)");
                }

                // Validate file name
                $original_name = basename($file['name']);
                if (empty($original_name)) {
                    throw new Exception("Nama file $jenis tidak valid");
                }

                // Generate secure filename
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                if (empty($ext)) {
                    $ext = $mime_type === 'application/pdf' ? 'pdf' : 'jpg';
                }

                $filename = preg_replace('/[^a-zA-Z0-9]/', '', $nisn) . '_' . $jenis . '_' . date('YmdHis') . '.' . $ext;
                $destination = $folder . $filename;

                // Ensure unique filename
                $counter = 1;
                while (file_exists($destination)) {
                    $filename = preg_replace('/[^a-zA-Z0-9]/', '', $nisn) . '_' . $jenis . '_' . date('YmdHis') . '_' . $counter . '.' . $ext;
                    $destination = $folder . $filename;
                    $counter++;
                }

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception("Gagal menyimpan file $jenis ke server");
                }

                // Set proper file permissions
                chmod($destination, 0644);

                if (function_exists('debug_log')) debug_log("File uploaded: $filename");
                return $filename;
            }

            // Check existing data
            $existing_data = null;
            $check_query = "SELECT * FROM berkas WHERE user_id = '" . mysqli_real_escape_string($connection, $user_id) . "'";
            $check_result = $connection->query($check_query);
            if ($check_result && $check_result->num_rows > 0) {
                $existing_data = $check_result->fetch_assoc();
            }

            // Validate required fields for new users - allow either KK or Ijazah
            if (!$existing_data) {
                $has_kk = $files['kk'] && $files['kk']['error'] === UPLOAD_ERR_OK;
                $has_ijazah = $files['ijazah'] && $files['ijazah']['error'] === UPLOAD_ERR_OK;

                if (!$has_kk && !$has_ijazah) {
                    echo 'Minimal upload salah satu: Kartu Keluarga atau Ijazah (untuk pendaftaran pertama)';
                    exit;
                }
            }

            // Check if any files uploaded
            $has_upload = false;
            foreach ($files as $file) {
                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $has_upload = true;
                    break;
                }
            }

            if (!$has_upload) {
                echo 'Pilih minimal satu file untuk diupload';
                exit;
            }

            // Process uploads
            $uploaded_files = [];
            foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                try {
                    $uploaded_files[$field] = uploadFile($files[$field], $field, $nisn, $folder);
                } catch (Exception $e) {
                    // If one file fails, clean up already uploaded files
                    foreach ($uploaded_files as $uploaded_file) {
                        if (!empty($uploaded_file) && file_exists($folder . $uploaded_file)) {
                            @unlink($folder . $uploaded_file);
                        }
                    }
                    throw $e;
                }
            }

            // Database operations with transaction
            $connection->autocommit(FALSE);

            try {
                if ($existing_data) {
                    // Update existing record
                    // Delete old files if new ones uploaded
                    foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                        if ($uploaded_files[$field] && !empty($existing_data[$field])) {
                            $old_file = $folder . $existing_data[$field];
                            if (file_exists($old_file)) {
                                @unlink($old_file);
                            }
                        }
                    }

                    // Build update query
                    $update_parts = [];
                    foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                        if ($uploaded_files[$field]) {
                            $update_parts[] = "$field = '" . mysqli_real_escape_string($connection, $uploaded_files[$field]) . "'";
                        }
                    }

                    if (!empty($update_parts)) {
                        $update_parts[] = "validasi_berkas = ''"; // reset validation when file changed
                        $update_parts[] = "validasi_by = NULL";
                        $update_parts[] = "keterangan = ''";
                        $update_parts[] = "updated_at = NOW()";
                        $update_sql = "UPDATE berkas SET " . implode(', ', $update_parts) . " WHERE user_id = '" . mysqli_real_escape_string($connection, $user_id) . "'";

                        if (!$connection->query($update_sql)) {
                            throw new Exception('Gagal update database: ' . $connection->error);
                        }
                    }
                } else {
                    // Insert new record
                    $insert_sql = "INSERT INTO berkas (user_id, kk, akte, ijazah, kip, kks, kis, validasi_berkas, keterangan, validasi_by, created_at, updated_at) VALUES (
                    '" . mysqli_real_escape_string($connection, $user_id) . "', 
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kk']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['akte']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['ijazah']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kip']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kks']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kis']) . "',
                    '',
                    '',
                    NULL,
                    NOW(), 
                    NOW()
                )";

                    if (!$connection->query($insert_sql)) {
                        throw new Exception('Gagal simpan ke database: ' . $connection->error);
                    }
                }

                $connection->commit();
                $connection->autocommit(TRUE);

                if (function_exists('debug_log')) debug_log("Upload completed successfully for user: $user_id");
                echo 'success';
            } catch (Exception $e) {
                $connection->rollback();
                $connection->autocommit(TRUE);
                throw $e;
            }
        } catch (Exception $e) {
            if (function_exists('debug_log')) debug_log("Upload error: " . $e->getMessage());
            echo $e->getMessage();
        }
        break;

    /* ---------- GET DATA BERKAS ---------- */
    case 'get-data':
        try {
            ob_clean();

            $user_id = $data_user['user_id'] ?? '';
            if (empty($user_id)) {
                echo json_encode(['error' => 'User tidak valid']);
                exit;
            }

            $query = "SELECT * FROM berkas WHERE user_id = '$user_id'";
            $result = $connection->query($query);

            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                // Convert empty strings to null
                foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                    if (empty($data[$field])) {
                        $data[$field] = null;
                    }
                }
                echo json_encode($data);
            } else {
                echo json_encode([
                    'kk' => null,
                    'akte' => null,
                    'ijazah' => null,
                    'kip' => null,
                    'kks' => null,
                    'kis' => null
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    /* ---------- DELETE BERKAS ---------- */
    case 'delete':
        try {
            ob_clean();

            $user_id = $data_user['user_id'] ?? '';
            if (empty($user_id)) {
                echo 'User tidak valid';
                exit;
            }

            // Get existing files
            $query = "SELECT * FROM berkas WHERE user_id = '$user_id'";
            $result = $connection->query($query);

            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                // Delete physical files
                foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                    if (!empty($data[$field])) {
                        $file_path = $folder . $data[$field];
                        if (file_exists($file_path)) {
                            @unlink($file_path);
                        }
                    }
                }
            }

            // Delete database record
            $delete_sql = "DELETE FROM berkas WHERE user_id = '$user_id'";
            if ($connection->query($delete_sql)) {
                echo 'success';
            } else {
                echo 'Gagal menghapus data: ' . $connection->error;
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
        break;

    /* ---------- FILTER RIWAYAT ABSENSI (AJAX) ---------- */
    case 'filter':
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            require_once '../../../library/config.php';
            require_once '../../../library/function.php';

            // Ambil user dari session/cookie
            $siswa = convert("decrypt", $_COOKIE['siswa'] ?? '');
            if (empty($siswa)) throw new Exception('Session tidak valid');
            $query_user = "SELECT * FROM user WHERE status='Aktif' AND user_id='" . htmlentities($siswa, ENT_QUOTES, 'UTF-8') . "' LIMIT 1";
            $result_user = $connection->query($query_user);
            if (!$result_user || $result_user->num_rows == 0) throw new Exception('User tidak ditemukan');
            $data_user = $result_user->fetch_assoc();
            $user_id = $data_user['user_id'] ?? '';
            if (empty($user_id)) throw new Exception('User tidak valid');

            // Ambil filter bulan/tahun
            $bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('m'));
            $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));
            if ($bulan < 1 || $bulan > 12) $bulan = intval(date('m'));
            if ($tahun < 1970) $tahun = intval(date('Y'));

            $start_date = sprintf('%04d-%02d-01', $tahun, $bulan);
            $days_in_month = date('t', strtotime($start_date));
            $end_date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $days_in_month);

            // Ambil absensi user
            $absensi_by_date = [];
            $stmt = $connection->prepare("SELECT id, tanggal, jam_masuk, status_masuk, jam_pulang, status_pulang, kehadiran, foto_masuk, foto_pulang, created_at, updated_at FROM absensi WHERE user_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal ASC");
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
            $is_holiday = function ($tanggal) use ($hari_libur_list) {
                foreach ($hari_libur_list as $hl) {
                    if ($tanggal >= $hl['tanggal_mulai'] && $tanggal <= $hl['tanggal_selesai']) {
                        return true;
                    }
                }
                return false;
            };

            // Ambil jadwal aktif
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
            $kehadiran_count = 0;
            $izin_count = 0;
            $alpha_count = 0;
            $rows_html = '';
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

            for ($d = 1; $d <= $days_in_month; $d++) {
                $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                $day_label = date('D, d M Y', strtotime($tanggal));
                $day_name = date('l', strtotime($tanggal));
                $day_name_id = [
                    'Monday' => 'Senin',
                    'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu',
                    'Thursday' => 'Kamis',
                    'Friday' => 'Jumat',
                    'Saturday' => 'Sabtu',
                    'Sunday' => 'Minggu'
                ][$day_name] ?? $day_name;

                $is_weekend = in_array(date('N', strtotime($tanggal)), [6, 7]);
                $holiday = $is_holiday($tanggal);
                $jadwal_hari = isset($jadwal_by_hari[$day_name_id]) ? $jadwal_by_hari[$day_name_id] : null;

                $cell_style = '';
                if ($holiday || $is_weekend) $cell_style = ' style="background:#FFEEEE;"';

                if (isset($absensi_by_date[$tanggal])) {
                    $a = $absensi_by_date[$tanggal];
                    $jam_masuk = htmlspecialchars($a['jam_masuk'] ?: '-');
                    $status_masuk = htmlspecialchars($a['status_masuk'] ?: '');
                    $jam_pulang = htmlspecialchars($a['jam_pulang'] ?: '-');
                    $status_pulang = htmlspecialchars($a['status_pulang'] ?: '');
                    $kehadiran = htmlspecialchars($a['kehadiran'] ?: '');

                    // Hitung statistik: Kehadiran (Hadir + Terlambat), Izin, Alpha
                    $status_lower = strtolower($a['status_masuk']);
                    $kehadiran_lower = strtolower($a['kehadiran']);

                    if ($kehadiran_lower === 'izin' || $status_lower === 'izin') {
                        $izin_count++;
                    } elseif (in_array($status_lower, ['tepat waktu', 'tepatwaktu', 'tepat', 'hadir', 'terlambat'])) {
                        $kehadiran_count++;
                    } elseif ($kehadiran_lower === 'alpha' || $kehadiran_lower === 'alpa') {
                        $alpha_count++;
                    }

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
                    $rows_html .= '<td class="text-center">' . $jam_pulang . '<br><small class="text-muted">' . $status_pulang . '</small></td>';
                    $rows_html .= '<td class="text-center">' . ($kehadiran ?: '<span class="text-muted">-</span>') . '</td>';
                    $rows_html .= '<td class="text-center">' . $foto_masuk_html . '</td>';
                    $rows_html .= '<td class="text-center">' . $foto_pulang_html . '</td>';
                    $rows_html .= '</tr>';
                } else {
                    if (!$holiday && !$is_weekend) $alpha_count++;
                    $label = $holiday ? '<span class="badge badge-danger">Libur</span>' : ($is_weekend ? '<span class="badge badge-secondary">Akhir Pekan</span>' : '<span class="text-muted">-</span>');
                    $rows_html .= '<tr' . $cell_style . '>';
                    $rows_html .= '<td class="text-center">' . $d . '</td>';
                    $rows_html .= '<td>' . $day_label . '</td>';
                    $rows_html .= '<td class="text-center" colspan="5">' . $label . '</td>';
                    $rows_html .= '</tr>';
                }
            }

            // Cek apakah ada data absensi sama sekali
            $has_data = !empty($absensi_by_date);

            // Jika tidak ada data absensi sama sekali untuk bulan ini
            if (!$has_data) {
                $rows_html = '<tr><td colspan="7" class="text-center text-muted py-5">';
                $rows_html .= '<i class="fas fa-inbox fa-3x mb-3 d-block"></i>';
                $rows_html .= '<h5>Belum Ada Riwayat Absensi</h5>';
                $rows_html .= '<p>Tidak ada data absensi untuk bulan ' . htmlspecialchars(ambilbulan($bulan)) . ' ' . htmlspecialchars($tahun) . '</p>';
                $rows_html .= '</td></tr>';
            }

            $stats_html = '<div class="d-flex flex-wrap gap-2">';
            $stats_html .= '<div class="mb-2"><span class="badge badge-success badge-lg px-3 py-2" style="font-size: 1rem;"><i class="fas fa-check-circle"></i> Kehadiran: ' . intval($kehadiran_count) . '</span></div>';
            $stats_html .= '<div class="mb-2"><span class="badge badge-info badge-lg px-3 py-2" style="font-size: 1rem;"><i class="fas fa-file-medical"></i> Izin: ' . intval($izin_count) . '</span></div>';
            $stats_html .= '<div class="mb-2"><span class="badge badge-danger badge-lg px-3 py-2" style="font-size: 1rem;"><i class="fas fa-times-circle"></i> Alpha: ' . intval($alpha_count) . '</span></div>';
            $stats_html .= '</div>';
            $stats_html .= '<small class="text-muted d-block mt-2"><i class="fas fa-info-circle"></i> Kehadiran = Hadir + Terlambat</small>';
            $title_html = 'Riwayat Absensi Bulan ' . htmlspecialchars(ambilbulan($bulan)) . ' ' . htmlspecialchars($tahun);

            echo json_encode([
                'rows_html' => $rows_html,
                'stats_html' => $stats_html,
                'title_html' => $title_html,
                'has_data' => $has_data,
                'bulan' => $bulan,
                'tahun' => $tahun
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        ob_clean();
        echo 'Action tidak valid';
        break;
}
