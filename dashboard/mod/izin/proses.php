<?php
// Basic error reporting and output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type for proper response (plain text for AJAX-friendly response)
header('Content-Type: text/plain; charset=utf-8');

// Start output buffering to catch any unwanted output
ob_start();

session_start();

// Minimal debug log
if (function_exists('debug_log')) debug_log("Izin request: " . date('Y-m-d H:i:s') . " METHOD=" . $_SERVER['REQUEST_METHOD'] . " ACTION=" . ($_GET['action'] ?? ''));

// Check session/cookie quickly
if (!isset($_COOKIE['siswa'])) {
    ob_clean();
    echo 'Session tidak valid - silakan login ulang';
    exit;
}

try {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    // Manual user authentication
    $siswa = convert("decrypt", $_COOKIE['siswa']);
    if (empty($siswa)) {
        ob_clean();
        echo 'Session tidak valid - data tidak dapat didekripsi';
        exit;
    }

    $query_user = "SELECT * FROM user WHERE status='Aktif' AND user_id='" . htmlentities($siswa, ENT_QUOTES, 'UTF-8') . "' LIMIT 1";
    $result_user = $connection->query($query_user);
    if (!$result_user || $result_user->num_rows == 0) {
        ob_clean();
        echo 'User tidak ditemukan atau tidak aktif';
        exit;
    }

    $data_user = $result_user->fetch_assoc();
    if (empty($data_user)) {
        ob_clean();
        echo 'Data user tidak ditemukan - silakan login ulang';
        exit;
    }

    if (function_exists('debug_log')) debug_log("User verified for izin: " . ($data_user['user_id'] ?? 'unknown'));
} catch (Exception $e) {
    ob_clean();
    if (function_exists('debug_log')) debug_log("Include error (izin): " . $e->getMessage());
    echo 'Error sistem: ' . $e->getMessage();
    exit;
}

// Simpel switch hanya untuk operasi izin
$action = $_GET['action'] ?? '';
switch ($action) {
    case 'add_izin':
        // Bersihkan output yang mungkin ada
        ob_clean();

        // Deteksi apakah request via AJAX
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        // Jika AJAX, kembalikan header JSON agar jQuery dapat mem-parse response dengan konsisten
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
        }

        // Hanya terima POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($is_ajax) {
                echo 'Metode request tidak diperbolehkan';
            } else {
                header('Location: izin.php?error=' . urlencode('Metode request tidak diperbolehkan'));
            }
            exit;
        }

        $user_id = $data_user['user_id'] ?? '';
        if (empty($user_id)) {
            if ($is_ajax) {
                echo 'User tidak valid';
            } else {
                header('Location: izin.php?error=' . urlencode('User tidak valid'));
            }
            exit;
        }

        // Ambil input
        $jenis_izin = trim($_POST['jenis_izin'] ?? '');
        $tanggal_mulai = trim($_POST['tanggal_mulai'] ?? '');
        $tanggal_selesai = trim($_POST['tanggal_selesai'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');

        // Validasi input
        $errors = [];

        // Validasi jenis izin
        if ($jenis_izin === '') {
            $errors[] = 'Jenis izin wajib diisi.';
        } elseif (strlen($jenis_izin) > 100) {
            $errors[] = 'Jenis izin maksimal 100 karakter.';
        }

        // Validasi tanggal mulai
        if ($tanggal_mulai === '') {
            $errors[] = 'Tanggal mulai wajib diisi.';
        } else {
            $d1 = DateTime::createFromFormat('Y-m-d', $tanggal_mulai);
            if (!$d1 || $d1->format('Y-m-d') !== $tanggal_mulai) {
                $errors[] = 'Format tanggal mulai tidak valid.';
            } else {
                // Cek tanggal tidak boleh lampau
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                if ($d1 < $today) {
                    $errors[] = 'Tanggal mulai tidak boleh tanggal yang sudah lewat.';
                }
            }
        }

        // Validasi tanggal selesai
        if ($tanggal_selesai !== '') {
            $d2 = DateTime::createFromFormat('Y-m-d', $tanggal_selesai);
            if (!$d2 || $d2->format('Y-m-d') !== $tanggal_selesai) {
                $errors[] = 'Format tanggal selesai tidak valid.';
            } else {
                // Validasi tanggal selesai >= tanggal mulai
                if (isset($d1) && $d2 < $d1) {
                    $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
                }
            }
        } else {
            // Jika kosong, samakan dengan tanggal mulai
            $tanggal_selesai = $tanggal_mulai;
        }

        // Validasi keterangan (Wajib diisi, maksimal 500)
        if ($keterangan === '') {
            $errors[] = 'Keterangan / alasan wajib diisi.';
        } elseif (strlen($keterangan) > 500) {
            $errors[] = 'Keterangan maksimal 500 karakter.';
        }

        // Tidak membatasi jenis izin di server — terima string bebas (tapi kosong tidak boleh)
        // (Client-side e-izin dapat menggunakan input teks; server hanya memastikan bukan kosong dan panjang <= 100)

        if (!empty($errors)) {
            $err_msg = implode('<br>', $errors);
            if ($is_ajax) {
                echo json_encode([
                    'success' => false,
                    'message' => $err_msg
                ]);
            } else {
                echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    html: '$err_msg',
                    confirmButtonText: 'OK'
                }).then(function() { 
                    window.location.href = '?mod=izin'; 
                });
                </script>";
            }
            exit;
        }

        // --- NEW: Cek apakah sudah ada permohonan 'Menunggu' ---
        $chk = $connection->prepare("SELECT COUNT(*) AS cnt FROM izin WHERE user_id = ? AND status_izin = 'Menunggu'");
        if ($chk) {
            $uid_chk = (int)$user_id;
            $chk->bind_param("i", $uid_chk);
            $chk->execute();
            $res_chk = $chk->get_result();
            $row_chk = $res_chk ? $res_chk->fetch_assoc() : null;
            $pending_count = (int)($row_chk['cnt'] ?? 0);
            $chk->close();

            if ($pending_count > 0) {
                $msg = 'Anda masih memiliki permohonan izin dengan status Menunggu. Tunggu sampai diproses sebelum mengajukan izin baru.';
                if ($is_ajax) {
                    echo json_encode([
                        'success' => false,
                        'message' => $msg
                    ]);
                } else {
                    echo "<script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Permohonan Menunggu',
                        text: '$msg',
                        confirmButtonText: 'OK'
                    }).then(function() { 
                        window.location.href = '?mod=izin'; 
                    });
                    </script>";
                }
                exit;
            }
        } else {
            // Jika query cek gagal, hindari memproses lebih lanjut
            $msg = 'Gagal melakukan verifikasi permohonan sebelumnya.';
            if ($is_ajax) {
                echo json_encode([
                    'success' => false,
                    'message' => $msg
                ]);
            } else {
                echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '$msg',
                    confirmButtonText: 'OK'
                }).then(function() { 
                    window.location.href = '?mod=izin'; 
                });
                </script>";
            }
            exit;
        }

        // Simpan ke DB menggunakan prepared statement
        $stmt = $connection->prepare("INSERT INTO izin (user_id, jenis_izin, tanggal_mulai, tanggal_selesai, keterangan, status_izin, date_submitted) VALUES (?, ?, ?, ?, ?, 'Menunggu', NOW())");
        if (!$stmt) {
            $err = 'Gagal menyiapkan query: ' . $connection->error;
            if ($is_ajax) {
                echo json_encode([
                    'success' => false,
                    'message' => $err
                ]);
            } else {
                echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Database Error',
                    text: '$err',
                    confirmButtonText: 'OK'
                }).then(function() { 
                    window.location.href = '?mod=izin'; 
                });
                </script>";
            }
            exit;
        }

        // Bind parameter (user_id diasumsikan integer)
        $uid = (int)$user_id;
        $stmt->bind_param("issss", $uid, $jenis_izin, $tanggal_mulai, $tanggal_selesai, $keterangan);

        if ($stmt->execute()) {
            $stmt->close();
            if ($is_ajax) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permohonan izin berhasil diajukan dan menunggu persetujuan.'
                ]);
            } else {
                echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Permohonan izin berhasil diajukan dan menunggu persetujuan.',
                    confirmButtonText: 'OK'
                }).then(function() { 
                    window.location.href = '?mod=izin'; 
                });
                </script>";
            }
            exit;
        } else {
            $err = 'Gagal menyimpan data: ' . $stmt->error;
            $stmt->close();
            if ($is_ajax) {
                echo json_encode([
                    'success' => false,
                    'message' => $err
                ]);
            } else {
                echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    text: '$err',
                    confirmButtonText: 'OK'
                }).then(function() { 
                    window.location.href = '?mod=izin'; 
                });
                </script>";
            }
            exit;
        }

        break;

    default:
        ob_clean();
        echo 'Action tidak valid';
        break;
}
