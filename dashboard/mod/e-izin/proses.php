<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

ob_start();

session_start();

if (function_exists('debug_log')) debug_log("Izin request: " . date('Y-m-d H:i:s') . " METHOD=" . $_SERVER['REQUEST_METHOD'] . " ACTION=" . ($_GET['action'] ?? ''));

if (!isset($_COOKIE['siswa'])) {
    ob_clean();
    echo 'Session tidak valid - silakan login ulang';
    exit;
}

try {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

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

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'add_izin':
        ob_clean();

        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

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

        $jenis_izin = trim($_POST['jenis_izin'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');

        $errors = [];

        if ($jenis_izin === '') {
            $errors[] = 'Jenis izin wajib diisi.';
        } elseif (strlen($jenis_izin) > 100) {
            $errors[] = 'Jenis izin maksimal 100 karakter.';
        }

        if ($tanggal === '') {
            $errors[] = 'Tanggal e-izin wajib diisi.';
        } else {
            $d1 = DateTime::createFromFormat('Y-m-d', $tanggal);
            if (!$d1 || $d1->format('Y-m-d') !== $tanggal) {
                $errors[] = 'Format tanggal tidak valid.';
            } else {
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                $d1->setTime(0, 0, 0);
                if ($d1 != $today) {
                    $errors[] = 'Tanggal e-izin harus untuk hari ini.';
                }
            }
        }

        if (strlen($keterangan) > 500) {
            $errors[] = 'Keterangan maksimal 500 karakter.';
        }

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

        $chk = $connection->prepare("SELECT COUNT(*) AS cnt FROM e_izin WHERE user_id = ? AND (status_izin = 'Menunggu' OR status_izin_wali = 'Menunggu')");
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

        $stmt = $connection->prepare("INSERT INTO e_izin (user_id, jenis_izin, tanggal, keterangan, status_izin, status_izin_wali, date_submitted) VALUES (?, ?, ?, ?, 'Menunggu', 'Menunggu', NOW())");
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

        $uid = (int)$user_id;
        $stmt->bind_param("isss", $uid, $jenis_izin, $tanggal, $keterangan);

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
