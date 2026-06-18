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

function pp_has_col($table, $column)
{
    global $connection;
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) return $cache[$key];
    $table_esc = $connection->real_escape_string($table);
    $col_esc = $connection->real_escape_string($column);
    $q = $connection->query("SHOW COLUMNS FROM `$table_esc` LIKE '$col_esc'");
    $cache[$key] = ($q && $q->num_rows > 0);
    return $cache[$key];
}

function pp_bind_dynamic($stmt, $types, &$params)
{
    $bind = [$types];
    foreach ($params as $k => &$v) {
        $bind[] = &$v;
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind);
}

switch ($action) {

    case 'cari_murid':
        $keyword = trim($_POST['keyword'] ?? '');
        if (strlen($keyword) < 3) { echo json_encode(['status'=>'error','message'=>'Minimal 3 karakter']); exit; }
        $keyword_safe = $connection->real_escape_string($keyword);
        $data = [];
        $q = $connection->query("SELECT u.user_id, u.nama_lengkap, u.nisn, k.nama_kelas
            FROM user u LEFT JOIN kelas k ON u.kelas=k.kelas_id
            WHERE u.status='aktif' AND (u.nisn LIKE '%$keyword_safe%' OR u.nama_lengkap LIKE '%$keyword_safe%')
            ORDER BY u.nama_lengkap ASC LIMIT 15");
        if ($q) while ($r = $q->fetch_assoc()) $data[] = $r;
        echo json_encode(['status'=>'success','data'=>$data]);
        break;

    case 'get_murid_info':
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $q = $connection->query("SELECT u.nama_lengkap, u.nisn, u.nama_ayah, u.nama_ibu, u.nama_wali, u.telp_ortu, k.nama_kelas,
            COALESCE((SELECT SUM(poin_diberikan) FROM poin_pelanggaran WHERE user_id=u.user_id AND status='Aktif'),0) AS total_poin,
            COALESCE((SELECT COUNT(*) FROM poin_pelanggaran WHERE user_id=u.user_id AND status='Aktif'),0) AS jumlah_kasus
            FROM user u LEFT JOIN kelas k ON u.kelas=k.kelas_id WHERE u.user_id=$user_id LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $row = $q->fetch_assoc();
            // Handle columns that may not exist
            if (!isset($row['nama_wali'])) $row['nama_wali'] = '';
            echo json_encode(['status'=>'success','data'=>$row]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Murid tidak ditemukan']);
        }
        break;

    case 'get_siswa_by_kelas':
        $kelas_id = intval($_POST['kelas_id'] ?? 0);
        $data = [];
        $q = $connection->query("SELECT user_id, nama_lengkap, nisn FROM user WHERE kelas='$kelas_id' AND status='aktif' ORDER BY nama_lengkap");
        if ($q) while ($r = $q->fetch_assoc()) $data[] = $r;
        echo json_encode(['status'=>'success','data'=>$data]);
        break;

    case 'get_siswa_info':
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $q = $connection->query("SELECT u.nama_lengkap, u.nisn, k.nama_kelas,
            COALESCE((SELECT SUM(poin_diberikan) FROM poin_pelanggaran WHERE user_id=u.user_id AND status='Aktif'),0) AS total_poin,
            COALESCE((SELECT COUNT(*) FROM poin_pelanggaran WHERE user_id=u.user_id AND status='Aktif'),0) AS jumlah_kasus
            FROM user u LEFT JOIN kelas k ON u.kelas=k.kelas_id WHERE u.user_id=$user_id LIMIT 1");
        if ($q && $q->num_rows > 0) {
            echo json_encode(['status'=>'success','data'=>$q->fetch_assoc()]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Siswa tidak ditemukan']);
        }
        break;

    case 'check_repeat':
        $user_id = intval($_POST['user_id'] ?? 0);
        $ayat_id = intval($_POST['ayat_id'] ?? 0);
        $count = 0;
        $q = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE user_id=$user_id AND ayat_id=$ayat_id AND status='Aktif'");
        if ($q) $count = intval($q->fetch_assoc()['c']);
        echo json_encode(['status'=>'success','count'=>$count]);
        break;

    case 'simpan_pelanggaran':
        $id = intval($_POST['pelanggaran_id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $ayat_id = intval($_POST['ayat_id'] ?? 0);
        $poin_diberikan = intval($_POST['poin_diberikan'] ?? 0);
        $tanggal = trim($_POST['tanggal_kejadian'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $perlu_tindak = isset($_POST['perlu_tindak_lanjut']) ? 'Y' : 'N';

        if ($user_id <= 0 || $ayat_id <= 0 || $tanggal === '') {
            echo json_encode(['status'=>'error','message'=>'Data wajib tidak lengkap']); exit;
        }

        // Auto-get kelas_id from user
        $kelas_id = 0;
        $qk = $connection->query("SELECT kelas FROM user WHERE user_id=$user_id LIMIT 1");
        if ($qk && $qk->num_rows > 0) $kelas_id = intval($qk->fetch_assoc()['kelas']);

        // Handle file uploads
        $bukti_foto = null;
        $bukti_video = null;
        $bukti_legacy = null;
        $content_root = realpath(__DIR__ . '/../../../content');
        if ($content_root === false) {
            $content_root = __DIR__ . '/../../../content';
        }
        $upload_base = rtrim($content_root, '/\\') . DIRECTORY_SEPARATOR . 'pelanggaran' . DIRECTORY_SEPARATOR;
        $upload_foto_dir = $upload_base . 'foto' . DIRECTORY_SEPARATOR;
        $upload_video_dir = $upload_base . 'video' . DIRECTORY_SEPARATOR;
        if (!is_dir($upload_foto_dir)) @mkdir($upload_foto_dir, 0777, true);
        if (!is_dir($upload_video_dir)) @mkdir($upload_video_dir, 0777, true);

        $has_bukti_foto_col = pp_has_col('poin_pelanggaran', 'bukti_foto');
        $has_bukti_video_col = pp_has_col('poin_pelanggaran', 'bukti_video');
        $has_bukti_col = pp_has_col('poin_pelanggaran', 'bukti');

        // Foto upload
        if (!empty($_FILES['bukti_foto']['name']) && $_FILES['bukti_foto']['error'] === 0) {
            $foto = $_FILES['bukti_foto'];
            $allowed_foto = ['image/jpeg','image/png','image/jpg'];
            if (!in_array($foto['type'], $allowed_foto)) {
                echo json_encode(['status'=>'error','message'=>'Format foto tidak valid (JPG/PNG)']); exit;
            }
            if ($foto['size'] > 2 * 1024 * 1024) {
                echo json_encode(['status'=>'error','message'=>'Ukuran foto maksimal 2MB']); exit;
            }
            $ext = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $bukti_foto = 'pel_' . $user_id . '_' . time() . '_foto.' . strtolower($ext);
            if (!move_uploaded_file($foto['tmp_name'], $upload_foto_dir . $bukti_foto)) {
                echo json_encode(['status'=>'error','message'=>'Gagal upload foto']); exit;
            }
            $bukti_legacy = $bukti_foto;
        }

        // Video upload
        if (!empty($_FILES['bukti_video']['name']) && $_FILES['bukti_video']['error'] === 0) {
            $video = $_FILES['bukti_video'];
            $allowed_video = ['video/mp4','video/mpeg','video/quicktime'];
            if (!in_array($video['type'], $allowed_video)) {
                echo json_encode(['status'=>'error','message'=>'Format video tidak valid (MP4/MPEG/MOV)']); exit;
            }
            if ($video['size'] > 5 * 1024 * 1024) {
                echo json_encode(['status'=>'error','message'=>'Ukuran video maksimal 5MB']); exit;
            }
            $ext = pathinfo($video['name'], PATHINFO_EXTENSION);
            $bukti_video = 'pel_' . $user_id . '_' . time() . '_video.' . strtolower($ext);
            if (!move_uploaded_file($video['tmp_name'], $upload_video_dir . $bukti_video)) {
                echo json_encode(['status'=>'error','message'=>'Gagal upload video']); exit;
            }
            if ($bukti_legacy === null) $bukti_legacy = $bukti_video;
        }

        // Get semester aktif
        $semester_id = null;
        $qs = $connection->query("SELECT semester_id FROM poin_semester WHERE is_aktif='Y' LIMIT 1");
        if ($qs && $qs->num_rows > 0) $semester_id = intval($qs->fetch_assoc()['semester_id']);

        // Check repeat
        $repeat_count = 0;
        $qr = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE user_id=$user_id AND ayat_id=$ayat_id AND status='Aktif'");
        if ($qr) $repeat_count = intval($qr->fetch_assoc()['c']);
        $is_repeat = $repeat_count > 0 ? 'Y' : 'N';

        if ($id > 0) {
            $sql = "UPDATE poin_pelanggaran SET user_id=?, kelas_id=?, ayat_id=?, semester_id=?, tanggal_kejadian=?, keterangan=?, poin_diberikan=?, is_pengulangan=?, jumlah_pengulangan=?, perlu_tindak_lanjut=?, admin_id=?";
            $params = [$user_id, $kelas_id, $ayat_id, $semester_id, $tanggal, $keterangan, $poin_diberikan, $is_repeat, $repeat_count, $perlu_tindak, $admin_id];
            $types = "iiiissisisi";
            if ($has_bukti_foto_col && $bukti_foto !== null) { $sql .= ", bukti_foto=?"; $params[] = $bukti_foto; $types .= "s"; }
            if ($has_bukti_video_col && $bukti_video !== null) { $sql .= ", bukti_video=?"; $params[] = $bukti_video; $types .= "s"; }
            if ($has_bukti_col && $bukti_legacy !== null) { $sql .= ", bukti=?"; $params[] = $bukti_legacy; $types .= "s"; }
            $sql .= " WHERE pelanggaran_id=?";
            $params[] = $id; $types .= "i";
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                echo json_encode(['status'=>'error','message'=>'Gagal menyiapkan query update: ' . $connection->error]);
                exit;
            }
            pp_bind_dynamic($stmt, $types, $params);
        } else {
            $cols = [
                'user_id', 'kelas_id', 'ayat_id', 'semester_id', 'tanggal_kejadian', 'keterangan',
                'poin_diberikan', 'is_pengulangan', 'jumlah_pengulangan', 'perlu_tindak_lanjut', 'admin_id'
            ];
            $vals = [$user_id, $kelas_id, $ayat_id, $semester_id, $tanggal, $keterangan, $poin_diberikan, $is_repeat, $repeat_count, $perlu_tindak, $admin_id];
            $types = 'iiiissisisi';

            if ($has_bukti_foto_col) {
                $cols[] = 'bukti_foto';
                $vals[] = $bukti_foto;
                $types .= 's';
            }
            if ($has_bukti_video_col) {
                $cols[] = 'bukti_video';
                $vals[] = $bukti_video;
                $types .= 's';
            }
            if ($has_bukti_col) {
                $cols[] = 'bukti';
                $vals[] = $bukti_legacy;
                $types .= 's';
            }

            $placeholder = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO poin_pelanggaran (" . implode(',', $cols) . ") VALUES ($placeholder)";
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                echo json_encode(['status'=>'error','message'=>'Gagal menyiapkan query insert: ' . $connection->error]);
                exit;
            }
            pp_bind_dynamic($stmt, $types, $vals);
        }

        if ($stmt->execute()) {
            // Check if student now >= 70 points
            $total_q = $connection->query("SELECT SUM(poin_diberikan) total FROM poin_pelanggaran WHERE user_id=$user_id AND status='Aktif'");
            $total_poin = 0;
            if ($total_q) $total_poin = intval($total_q->fetch_assoc()['total']);

            $msg = ($id > 0 ? 'Pelanggaran diperbarui' : 'Pelanggaran berhasil dicatat');
            if ($total_poin >= 100) {
                $msg .= '. PERINGATAN: Total poin siswa sudah ' . $total_poin . ' (≥100), perlu pemanggilan orang tua!';
            }
            echo json_encode(['status'=>'success','message'=>$msg,'total_poin'=>$total_poin]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menyimpan: '.$stmt->error]);
        }
        $stmt->close();
        break;

    case 'detail_pelanggaran':
        $id = intval($_POST['pelanggaran_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
        $q = $connection->query("SELECT pp.*, u.nama_lengkap, u.nisn, u.telp_ortu, u.nama_ayah, u.nama_ibu,
            k.nama_kelas, pa.jenis_pelanggaran, pa.kategori, pa.poin AS poin_master, pa.kode_ayat,
            ps.kode_pasal, ps.nama_pasal, a.fullname AS nama_admin,
            sem.nama_semester
            FROM poin_pelanggaran pp
            JOIN user u ON pp.user_id=u.user_id
            LEFT JOIN kelas k ON pp.kelas_id=k.kelas_id
            JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
            JOIN poin_pasal ps ON pa.pasal_id=ps.pasal_id
            LEFT JOIN admin a ON pp.admin_id=a.admin_id
            LEFT JOIN poin_semester sem ON pp.semester_id=sem.semester_id
            WHERE pp.pelanggaran_id=$id LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $row = $q->fetch_assoc();

            // Kompatibilitas skema lama: tabel lama hanya memiliki kolom `bukti`.
            if (!isset($row['bukti_foto'])) $row['bukti_foto'] = '';
            if (!isset($row['bukti_video'])) $row['bukti_video'] = '';
            if (empty($row['bukti_foto']) && empty($row['bukti_video']) && !empty($row['bukti'])) {
                $ext = strtolower(pathinfo($row['bukti'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $row['bukti_foto'] = $row['bukti'];
                } elseif (in_array($ext, ['mp4', 'mpeg', 'mov'])) {
                    $row['bukti_video'] = $row['bukti'];
                }
            }

            // Get total poin siswa
            $qt = $connection->query("SELECT SUM(poin_diberikan) total FROM poin_pelanggaran WHERE user_id=".$row['user_id']." AND status='Aktif'");
            $total = $qt ? intval($qt->fetch_assoc()['total']) : 0;
            $row['total_poin_siswa'] = $total;

            // Sanggah history
            $sanggah = [];
            $qs = $connection->query("SELECT * FROM poin_sanggah WHERE pelanggaran_id=$id ORDER BY created_at DESC");
            if ($qs) while ($rs = $qs->fetch_assoc()) $sanggah[] = $rs;
            $row['sanggah_list'] = $sanggah;

            echo json_encode(['status'=>'success','data'=>$row]);
        } else {
            echo json_encode(['status'=>'error','message'=>'Data tidak ditemukan']);
        }
        break;

    case 'hapus_pelanggaran':
        $id = intval($_POST['pelanggaran_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }

        // Get file paths before deleting (support skema lama/baru)
        $fields = [];
        if (pp_has_col('poin_pelanggaran', 'bukti_foto')) $fields[] = 'bukti_foto';
        if (pp_has_col('poin_pelanggaran', 'bukti_video')) $fields[] = 'bukti_video';
        if (pp_has_col('poin_pelanggaran', 'bukti')) $fields[] = 'bukti';
        $files = [];
        if (!empty($fields)) {
            $qf = $connection->query("SELECT " . implode(',', $fields) . " FROM poin_pelanggaran WHERE pelanggaran_id=$id LIMIT 1");
            $files = ($qf && $qf->num_rows > 0) ? $qf->fetch_assoc() : [];
        }

        $stmt = $connection->prepare("UPDATE poin_pelanggaran SET status='Dihapus' WHERE pelanggaran_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Delete foto file
            if (!empty($files['bukti_foto'])) {
                $foto_path = $_SERVER['DOCUMENT_ROOT'] . '/saev4/content/pelanggaran/foto/' . $files['bukti_foto'];
                if (file_exists($foto_path)) @unlink($foto_path);
            }
            // Delete video file
            if (!empty($files['bukti_video'])) {
                $video_path = $_SERVER['DOCUMENT_ROOT'] . '/saev4/content/pelanggaran/video/' . $files['bukti_video'];
                if (file_exists($video_path)) @unlink($video_path);
            }
            // Legacy: satu kolom bukti
            if (!empty($files['bukti'])) {
                $legacy = $files['bukti'];
                $path1 = $_SERVER['DOCUMENT_ROOT'] . '/saev4/content/pelanggaran/foto/' . $legacy;
                $path2 = $_SERVER['DOCUMENT_ROOT'] . '/saev4/content/pelanggaran/video/' . $legacy;
                if (file_exists($path1)) @unlink($path1);
                if (file_exists($path2)) @unlink($path2);
            }
            echo json_encode(['status'=>'success','message'=>'Pelanggaran dihapus']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Gagal menghapus']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action tidak valid']);
}
