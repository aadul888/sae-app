<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../../library/config.php');
require_once('../../../library/function.php');

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$admin_id = 0;
if (!empty($_COOKIE['ADMIN_KEY'])) {
    $admin_id = intval(@epm_decode($_COOKIE['ADMIN_KEY']));
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    // ========== PASAL ==========
    case 'simpan_pasal':
        $id = intval($_POST['pasal_id'] ?? 0);
        $kode = trim($_POST['kode_pasal'] ?? '');
        $nama = trim($_POST['nama_pasal'] ?? '');
        $desk = trim($_POST['deskripsi'] ?? '');
        $urutan = intval($_POST['urutan'] ?? 0);
        $aktif = ($_POST['aktif'] ?? 'Y') === 'Y' ? 'Y' : 'N';

        if ($kode === '' || $nama === '') {
            echo json_encode(['status'=>'error','message'=>'Kode dan nama pasal wajib diisi']); exit;
        }

        if ($id > 0) {
            $stmt = $connection->prepare("UPDATE poin_pasal SET kode_pasal=?, nama_pasal=?, deskripsi=?, urutan=?, aktif=? WHERE pasal_id=?");
            $stmt->bind_param("sssisi", $kode, $nama, $desk, $urutan, $aktif, $id);
        } else {
            $stmt = $connection->prepare("INSERT INTO poin_pasal (kode_pasal, nama_pasal, deskripsi, urutan, aktif) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssis", $kode, $nama, $desk, $urutan, $aktif);
        }
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>($id>0?'Pasal berhasil diperbarui':'Pasal berhasil ditambahkan')]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menyimpan: '.$stmt->error]);
        }
        $stmt->close();
        break;

    case 'hapus_pasal':
        $id = intval($_POST['pasal_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        // Check if pasal has ayat linked to pelanggaran records
        $qc = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran pp JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id WHERE pa.pasal_id=$id");
        if ($qc && $qc->fetch_assoc()['c'] > 0) {
            echo json_encode(['status'=>'error','message'=>'Pasal tidak bisa dihapus karena masih terkait dengan data pelanggaran']); exit;
        }
        $stmt = $connection->prepare("DELETE FROM poin_pasal WHERE pasal_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'Pasal berhasil dihapus']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menghapus']);
        }
        $stmt->close();
        break;

    // ========== AYAT ==========
    case 'simpan_ayat':
        $id = intval($_POST['ayat_id'] ?? 0);
        $pasal_id = intval($_POST['pasal_id'] ?? 0);
        $kode = trim($_POST['kode_ayat'] ?? '');
        $jenis = trim($_POST['jenis_pelanggaran'] ?? '');
        $desk = trim($_POST['deskripsi'] ?? '');
        $kategori = $_POST['kategori'] ?? 'Ringan';
        $poin = intval($_POST['poin'] ?? 0);
        $urutan = intval($_POST['urutan'] ?? 0);
        $aktif = ($_POST['aktif'] ?? 'Y') === 'Y' ? 'Y' : 'N';

        $valid_kat = ['Ringan','Sedang','Berat','Sangat Berat'];
        if (!in_array($kategori, $valid_kat)) $kategori = 'Ringan';

        if ($pasal_id <= 0 || $kode === '' || $jenis === '') {
            echo json_encode(['status'=>'error','message'=>'Data wajib tidak lengkap']); exit;
        }

        if ($id > 0) {
            $stmt = $connection->prepare("UPDATE poin_ayat SET pasal_id=?, kode_ayat=?, jenis_pelanggaran=?, deskripsi=?, kategori=?, poin=?, urutan=?, aktif=? WHERE ayat_id=?");
            $stmt->bind_param("issssisis", $pasal_id, $kode, $jenis, $desk, $kategori, $poin, $urutan, $aktif, $id);
        } else {
            $stmt = $connection->prepare("INSERT INTO poin_ayat (pasal_id, kode_ayat, jenis_pelanggaran, deskripsi, kategori, poin, urutan, aktif) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issssiis", $pasal_id, $kode, $jenis, $desk, $kategori, $poin, $urutan, $aktif);
        }
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>($id>0?'Ayat berhasil diperbarui':'Ayat berhasil ditambahkan')]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menyimpan: '.$stmt->error]);
        }
        $stmt->close();
        break;

    case 'hapus_ayat':
        $id = intval($_POST['ayat_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $qc = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE ayat_id=$id");
        if ($qc && $qc->fetch_assoc()['c'] > 0) {
            echo json_encode(['status'=>'error','message'=>'Ayat tidak bisa dihapus karena masih terkait data pelanggaran']); exit;
        }
        $stmt = $connection->prepare("DELETE FROM poin_ayat WHERE ayat_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'Ayat berhasil dihapus']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menghapus']);
        }
        $stmt->close();
        break;

    // ========== SEMESTER ==========
    case 'simpan_semester':
        $id = intval($_POST['semester_id'] ?? 0);
        $nama = trim($_POST['nama_semester'] ?? '');
        $tahun = trim($_POST['tahun_ajaran'] ?? '');
        $jenis = ($_POST['jenis'] ?? 'Ganjil');
        $mulai = trim($_POST['tanggal_mulai'] ?? '');
        $selesai = trim($_POST['tanggal_selesai'] ?? '');
        $is_aktif = isset($_POST['is_aktif']) ? 'Y' : 'N';

        if (!in_array($jenis, ['Ganjil','Genap'])) $jenis = 'Ganjil';
        if ($nama === '' || $tahun === '' || $mulai === '' || $selesai === '') {
            echo json_encode(['status'=>'error','message'=>'Semua field wajib diisi']); exit;
        }

        // If setting as active, deactivate others
        if ($is_aktif === 'Y') {
            $connection->query("UPDATE poin_semester SET is_aktif='N' WHERE is_aktif='Y'");
        }

        if ($id > 0) {
            $stmt = $connection->prepare("UPDATE poin_semester SET nama_semester=?, tahun_ajaran=?, jenis=?, tanggal_mulai=?, tanggal_selesai=?, is_aktif=? WHERE semester_id=?");
            $stmt->bind_param("ssssssi", $nama, $tahun, $jenis, $mulai, $selesai, $is_aktif, $id);
        } else {
            $stmt = $connection->prepare("INSERT INTO poin_semester (nama_semester, tahun_ajaran, jenis, tanggal_mulai, tanggal_selesai, is_aktif) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $nama, $tahun, $jenis, $mulai, $selesai, $is_aktif);
        }
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>($id>0?'Semester diperbarui':'Semester ditambahkan')]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menyimpan']);
        }
        $stmt->close();
        break;

    case 'aktifkan_semester':
        $id = intval($_POST['semester_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $connection->query("UPDATE poin_semester SET is_aktif='N' WHERE is_aktif='Y'");
        $stmt = $connection->prepare("UPDATE poin_semester SET is_aktif='Y' WHERE semester_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['status'=>'success','message'=>'Semester diaktifkan']);
        $stmt->close();
        break;

    case 'hapus_semester':
        $id = intval($_POST['semester_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $qc = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE semester_id=$id");
        if ($qc && $qc->fetch_assoc()['c'] > 0) {
            echo json_encode(['status'=>'error','message'=>'Semester tidak bisa dihapus karena ada data pelanggaran']); exit;
        }
        $stmt = $connection->prepare("DELETE FROM poin_semester WHERE semester_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'success','message'=>'Semester dihapus']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menghapus']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action tidak valid']);
}
