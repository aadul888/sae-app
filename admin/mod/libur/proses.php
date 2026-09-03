<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once('../../../library/function.php');
    require_once '../../login/user.php';

    $modul_id = 13;
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
            /* ---------- TAMBAH ---------- */
        case 'add':
            check_access('modifikasi');
            $error = array();

            // Validasi input
            if (empty($_POST['tanggal_mulai']) || empty($_POST['tanggal_selesai'])) {
                $error[] = 'Tanggal mulai dan tanggal selesai tidak boleh kosong';
            } else {
                $tanggal_mulai = anti_injection($_POST['tanggal_mulai']);
                $tanggal_selesai = anti_injection($_POST['tanggal_selesai']);
            }

            if (empty($_POST['keterangan'])) {
                $error[] = 'Keterangan tidak boleh kosong';
            } else {
                $keterangan = anti_injection($_POST['keterangan']);
            }

            if (empty($error)) {
                // Insert ke database
                $insert = "INSERT INTO hari_libur (tanggal_mulai, tanggal_selesai, keterangan, created_at, updated_at) 
               VALUES (?, ?, ?, NOW(), NOW())";
                $stmt = $connection->prepare($insert);
                $stmt->bind_param('sss', $tanggal_mulai, $tanggal_selesai, $keterangan);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Hari libur berhasil ditambahkan!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan hari libur!']);
                }
                $stmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => implode("\n", $error)]);
            }
            break;

            /* ---------- UPDATE ---------- */
        case 'edit':
            check_access('modifikasi');
            $error = array();
            $id = isset($_POST['id']) ? anti_injection($_POST['id']) : null;

            // Validasi input
            if (empty($_POST['tanggal_mulai']) || empty($_POST['tanggal_selesai'])) {
                $error[] = 'Tanggal mulai dan tanggal selesai tidak boleh kosong';
            } else {
                $tanggal_mulai = anti_injection($_POST['tanggal_mulai']);
                $tanggal_selesai = anti_injection($_POST['tanggal_selesai']);
            }

            if (empty($_POST['keterangan'])) {
                $error[] = 'Keterangan tidak boleh kosong';
            } else {
                $keterangan = anti_injection($_POST['keterangan']);
            }

            if (empty($error)) {
                if ($id) {
                    // Update data di database
                    $update = "UPDATE hari_libur SET tanggal_mulai=?, tanggal_selesai=?, keterangan=?, updated_at=NOW() WHERE id=?";
                    $stmt = $connection->prepare($update);
                    $stmt->bind_param('sssi', $tanggal_mulai, $tanggal_selesai, $keterangan, $id);

                    if ($stmt->execute()) {
                        echo json_encode(['status' => 'success', 'message' => 'Hari libur berhasil diperbarui!']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui hari libur!']);
                    }
                    $stmt->close();
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan!']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => implode("\n", $error)]);
            }
            break;


            /* ---------- HAPUS ---------- */
        case 'delete':
            check_access('hapus');
            $id = isset($_POST['id']) ? anti_injection($_POST['id']) : null;

            if ($id) {
                $delete = "DELETE FROM hari_libur WHERE id=?";
                $stmt = $connection->prepare($delete);
                $stmt->bind_param('i', $id);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Hari libur berhasil dihapus!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Hari libur gagal dihapus!']);
                }
                $stmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID hari libur tidak ditemukan!']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
            break;
    }

    // Tutup koneksi database
    $connection->close();
}
