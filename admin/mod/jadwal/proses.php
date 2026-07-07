<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');
    require_once '../../login/user.php';

    $modul_id = 12;
    include __DIR__ . '/../check_role.php';

    function check_access($type)
    {
        global $data_role;
        if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.']);
            exit;
        }
    }

    // Koneksi ke database
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
    if ($connection->connect_error) {
        die("Koneksi gagal: " . $connection->connect_error);
    }

    switch (@$_GET['action']) {
            /* ---------- UPDATE ---------- */
        case 'update':
            check_access('modifikasi');
            $error = array();
            $id = isset($_POST['id']) ? anti_injection($_POST['id']) : null; // ID jadwal

            // Validasi input
            if (empty($_POST['waktu_mulai'])) {
                $error[] = 'Waktu mulai tidak boleh kosong';
            } else {
                $waktu_mulai = anti_injection($_POST['waktu_mulai']);
            }

            if (empty($_POST['waktu_selesai'])) {
                $error[] = 'Waktu selesai tidak boleh kosong';
            } else {
                $waktu_selesai = anti_injection($_POST['waktu_selesai']);
            }

            if (empty($error)) {
                if ($id) {
                    // Update waktu jadwal
                    $update = "UPDATE jadwal SET waktu_mulai=?, waktu_selesai=? WHERE id=?";
                    $stmt = $connection->prepare($update);
                    $stmt->bind_param('ssi', $waktu_mulai, $waktu_selesai, $id);

                    if ($stmt->execute()) {
                        echo json_encode(['status' => 'success', 'message' => 'Waktu berhasil diperbarui!']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Waktu tidak berhasil diperbarui!']);
                    }
                    $stmt->close();
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'ID jadwal tidak ditemukan!']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => implode("\n", $error)]);
            }
            break;

        case 'update_status':
            check_access('modifikasi');
            $id = isset($_POST['id']) ? anti_injection($_POST['id']) : null;
            $status = isset($_POST['status']) ? anti_injection($_POST['status']) : null;

            if ($id && ($status == 'Y' || $status == 'N')) {
                $update = "UPDATE jadwal SET status=? WHERE id=?";
                $stmt = $connection->prepare($update);
                $stmt->bind_param('si', $status, $id);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Status berhasil diperbarui!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Status tidak berhasil diperbarui!']);
                }
                $stmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Data tidak valid!']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
            break;

        case 'active':
            check_access('modifikasi');
            $id = htmlentities($_POST['id']);
            $active = htmlentities($_POST['active']);
            $update = "UPDATE admin SET active='$active' WHERE admin_id='$id'";
            if ($connection->query($update) === false) {
                echo 'error';
                die($connection->error . __LINE__);
            } else {
                echo 'success';
            }
    }

    // Tutup koneksi database
    $connection->close();
}
