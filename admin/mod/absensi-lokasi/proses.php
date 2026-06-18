<?php
session_start();
require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../login/user.php';

// Cek sesi login
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesi login Anda telah habis. Silakan login ulang.']);
    exit;
}

$modul_id = 14;
include __DIR__ . '/../check_role.php';

function check_access($type)
{
    global $data_role;
    if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
        echo json_encode(['error' => 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.']);
        exit;
    }
}

$aksi = $_GET['action'] ?? '';

switch ($aksi) {
    case 'load':
        check_access('lihat');
        $sql = "SELECT * FROM lokasi ORDER BY lokasi_id DESC";
        $query = $connection->query($sql);
        $data = [];
        $no = 1;
        while ($row = $query->fetch_assoc()) {
            $data[] = [
                $no++,
                htmlspecialchars($row['nama_lokasi']),
                $row['latitude'] . ', ' . $row['longitude'],
                $row['radius'] . ' m',
                '<span class="badge badge-' . ($row['status'] == 'aktif' ? 'success' : 'secondary') . '">' . $row['status'] . '</span>',
                '
                <button class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $row['lokasi_id'] . '" 
                    data-nama="' . htmlspecialchars($row['nama_lokasi']) . '"
                    data-ket="' . htmlspecialchars($row['keterangan']) . '"
                    data-lat="' . $row['latitude'] . '" 
                    data-lng="' . $row['longitude'] . '" 
                    data-radius="' . $row['radius'] . '"
                    data-status="' . $row['status'] . '">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $row['lokasi_id'] . '" 
                    data-name="' . htmlspecialchars($row['nama_lokasi']) . '">
                    <i class="fas fa-trash"></i>
                </button>'
            ];
        }
        echo json_encode(['data' => $data]);
        break;

    case 'simpan':
        check_access('modifikasi');
        header('Content-Type: application/json');
        $id     = anti_injection($_POST['lokasi_id']);
        $nama   = anti_injection($_POST['nama_lokasi']);
        $ket    = anti_injection($_POST['keterangan']);
        $lat    = $_POST['latitude'];
        $lng    = $_POST['longitude'];
        $rad    = $_POST['radius'];
        $stat   = anti_injection($_POST['status']);

        $error = [];
        if ($nama == '') {
            $error[] = 'Nama lokasi tidak boleh kosong';
        }
        if (!is_numeric($lat) || !is_numeric($lng) || !is_numeric($rad)) {
            $error[] = 'Koordinat dan radius harus berupa angka';
        }

        if (empty($error)) {
            if ($id == '') {
                $insert = "INSERT INTO lokasi (nama_lokasi, keterangan, latitude, longitude, radius, status)
                           VALUES ('$nama', '$ket', '$lat', '$lng', '$rad', '$stat')";
                $exec = $connection->query($insert);
            } else {
                $update = "UPDATE lokasi SET 
                            nama_lokasi='$nama', 
                            keterangan='$ket',
                            latitude='$lat', 
                            longitude='$lng', 
                            radius='$rad',
                            status='$stat'
                          WHERE lokasi_id='$id'";
                $exec = $connection->query($update);
            }

            if ($exec) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Gagal menyimpan data!']);
            }
        } else {
            echo json_encode(['error' => implode("\n", $error)]);
        }
        break;

    case 'edit':
        check_access('lihat');
        header('Content-Type: application/json');
        $id = (int)$_POST['lokasi_id'];
        $query = $connection->query("SELECT * FROM lokasi WHERE lokasi_id='$id'");
        if ($query && $query->num_rows > 0) {
            echo json_encode($query->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan!']);
        }
        break;


    case 'delete':
        check_access('hapus');
        header('Content-Type: application/json');
        $id = (int)$_POST['lokasi_id'];
        $delete = $connection->query("DELETE FROM lokasi WHERE lokasi_id='$id'");
        if ($delete) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Gagal menghapus data!']);
        }
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Aksi tidak valid']);
        break;
}
