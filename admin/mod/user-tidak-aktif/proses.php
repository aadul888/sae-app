<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
require_once('../../../library/function.php');
require_once '../../login/user.php';
require_once '../../assets/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;


$iB              = getBrowser();
$browser          = $iB['name'] . ' ' . $iB['version'];
$ip              = $_SERVER['REMOTE_ADDR'];
$time_online     = time();


$max_size = 5000000; //5MB
$allowed_ext = array('jpg', 'jpeg', 'gif', 'png');
$uploadPath       = '../../../content/avatar/';

function resizeImage($resourceType, $image_width, $image_height): GdImage
{
  // ...existing code...
  $resizeWidth = 500;
  $resizeHeight = ($image_height / $image_width) * $resizeWidth;
  $imageLayer = imagecreatetruecolor($resizeWidth, $resizeHeight);
  if ($imageLayer === false) {
    throw new RuntimeException('Failed to create a true color image.');
  }
  if (!imagecopyresampled($imageLayer, $resourceType, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $image_width, $image_height)) {
    throw new RuntimeException('Failed to resample the image.');
  }
  return $imageLayer;
}

// Hak akses berdasarkan role
$modul_id = 4;
include __DIR__ . '/../check_role.php';

function check_access($type) {
    global $data_role;
    if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
        echo 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.';
        exit;
    }
}


switch (@$_GET['action']) {
  /* ----------- Set Koordinator User ----------- */
  case 'set_koordinator':
    check_access('modifikasi');
    $id = intval(epm_decode($_POST['id']));
    $set = isset($_POST['set']) ? intval($_POST['set']) : 1;
    $stmt_sk = $connection->prepare("UPDATE user SET koordinator=? WHERE user_id=?");
    $stmt_sk->bind_param('ii', $set, $id);
    if ($stmt_sk->execute()) {
      echo 'success';
    } else {
      echo 'Gagal set koordinator.';
    }
    break;

  case 'update':
    check_access('modifikasi');
    $error = array();
    $id = !empty($_POST['id']) ? anti_injection(epm_decode($_POST['id'])) : '';

    // --- Blok A: Identitas Peserta Didik ---
    $blokA = [
      'no_kk',
      'nik',
      'nipd',
      'nisn',
      'nama_lengkap',
      'sekolah_asal',
      'tempat_lahir',
      'tanggal_lahir',
      'agama',
      'jenis_kelamin',
      'status_keluarga',
      'kelas',
      'diterima_dikelas',
      'diterima_tanggal',
      'rt',
      'rw',
      'desa',
      'kecamatan',
      'kodepos',
      'email',
      'password',
      'telp',
      'anak_ke',
      'alamat',
      'status',
    ];
    foreach ($blokA as $field) {
      $$field = isset($_POST[$field]) ? anti_injection($_POST[$field]) : '';
    }

    // Konversi tanggal ke format YYYY-MM-DD
    if (!empty($tanggal_lahir)) {
      $tanggal_lahir = date('Y-m-d', strtotime($tanggal_lahir));
    }
    if (!empty($diterima_tanggal)) {
      $diterima_tanggal = date('Y-m-d', strtotime($diterima_tanggal));
    }

    // --- Blok B: Orangtua Kandung ---
    $blokB = [
      'nik_ayah',
      'nama_ayah',
      'pekerjaan_ayah',
      'nik_ibu',
      'nama_ibu',
      'pekerjaan_ibu'
    ];
    foreach ($blokB as $field) {
      $$field = isset($_POST[$field]) ? anti_injection($_POST[$field]) : '';
    }

    // --- Blok C: Wali ---
    $blokC = [
      'nama_wali',
      'alamat_wali',
      'telp_wali',
      'pekerjaan_wali'
    ];
    foreach ($blokC as $field) {
      $$field = isset($_POST[$field]) ? anti_injection($_POST[$field]) : '';
    }

    // Validasi minimal
    if (empty($id)) $error[] = 'ID tidak ditemukan';
    if (empty($nama_lengkap)) $error[] = 'Nama Lengkap tidak boleh kosong';
    if (empty($nisn)) $error[] = 'NISN tidak boleh kosong';
    if (empty($no_kk)) $error[] = 'Nomor KK tidak boleh kosong';
    if (empty($nik)) $error[] = 'NIK tidak boleh kosong';
    if (empty($email)) $error[] = 'Email tidak boleh kosong';
    if (empty($status)) $error[] = 'Status tidak boleh kosong';
    if (empty($kelas)) $error[] = 'Kelas tidak boleh kosong';
    if (empty($tempat_lahir)) $error[] = 'Tempat Lahir tidak boleh kosong';
    if (empty($tanggal_lahir)) $error[] = 'Tanggal Lahir tidak boleh kosong';
    if (empty($jenis_kelamin)) $error[] = 'Jenis Kelamin tidak boleh kosong';
    if (empty($anak_ke)) $error[] = 'Anak ke tidak boleh kosong';
    if (empty($alamat)) $error[] = 'Alamat tidak boleh kosong';
    if (empty($nama_ayah)) $error[] = 'Nama Ayah tidak boleh kosong';
    if (empty($nama_ibu)) $error[] = 'Nama Ibu tidak boleh kosong';

    if (empty($error)) {
      // Jika password diisi, hash dan update. Jika kosong, skip
      if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt_upd = $connection->prepare("UPDATE user SET no_kk=?, nik=?, nipd=?, nisn=?, nama_lengkap=?, sekolah_asal=?, tempat_lahir=?, tanggal_lahir=?, agama=?, jenis_kelamin=?, status_keluarga=?, kelas=?, diterima_dikelas=?, diterima_tanggal=?, rt=?, rw=?, desa=?, kecamatan=?, kodepos=?, email=?, password=?, telp=?, anak_ke=?, alamat=?, status=?, nik_ayah=?, nama_ayah=?, pekerjaan_ayah=?, nik_ibu=?, nama_ibu=?, pekerjaan_ibu=?, nama_wali=?, alamat_wali=?, telp_wali=?, pekerjaan_wali=?, konfirmasi='Belum Konfirmasi' WHERE user_id=?");
        $stmt_upd->bind_param('sssssssssssssssssssssssssssssssssssss', $no_kk, $nik, $nipd, $nisn, $nama_lengkap, $sekolah_asal, $tempat_lahir, $tanggal_lahir, $agama, $jenis_kelamin, $status_keluarga, $kelas, $diterima_dikelas, $diterima_tanggal, $rt, $rw, $desa, $kecamatan, $kodepos, $email, $password_hash, $telp, $anak_ke, $alamat, $status, $nik_ayah, $nama_ayah, $pekerjaan_ayah, $nik_ibu, $nama_ibu, $pekerjaan_ibu, $nama_wali, $alamat_wali, $telp_wali, $pekerjaan_wali, $id);
      } else {
        $stmt_upd = $connection->prepare("UPDATE user SET no_kk=?, nik=?, nipd=?, nisn=?, nama_lengkap=?, sekolah_asal=?, tempat_lahir=?, tanggal_lahir=?, agama=?, jenis_kelamin=?, status_keluarga=?, kelas=?, diterima_dikelas=?, diterima_tanggal=?, rt=?, rw=?, desa=?, kecamatan=?, kodepos=?, email=?, telp=?, anak_ke=?, alamat=?, status=?, nik_ayah=?, nama_ayah=?, pekerjaan_ayah=?, nik_ibu=?, nama_ibu=?, pekerjaan_ibu=?, nama_wali=?, alamat_wali=?, telp_wali=?, pekerjaan_wali=?, konfirmasi='Belum Konfirmasi' WHERE user_id=?");
        $stmt_upd->bind_param('sssssssssssssssssssssssssssssssssss', $no_kk, $nik, $nipd, $nisn, $nama_lengkap, $sekolah_asal, $tempat_lahir, $tanggal_lahir, $agama, $jenis_kelamin, $status_keluarga, $kelas, $diterima_dikelas, $diterima_tanggal, $rt, $rw, $desa, $kecamatan, $kodepos, $email, $telp, $anak_ke, $alamat, $status, $nik_ayah, $nama_ayah, $pekerjaan_ayah, $nik_ibu, $nama_ibu, $pekerjaan_ibu, $nama_wali, $alamat_wali, $telp_wali, $pekerjaan_wali, $id);
      }
      if ($stmt_upd->execute()) {
        $stmt_upd->close();
        echo 'success';
      } else {
        $stmt_upd->close();
        echo 'Gagal update: ' . $connection->error;
      }
    } else {
      echo implode('<br>', $error);
    }
    break;

  /** Setactive user */
  case 'active':
    check_access('modifikasi');
    $id = $_POST['id'];
    $active = $_POST['active'];
    $status = ($active === 'Y') ? 'Aktif' : 'Tidak Aktif';
    $stmt_act = $connection->prepare("UPDATE user SET status=? WHERE user_id=?");
    $stmt_act->bind_param('ss', $status, $id);
    if (!$stmt_act->execute()) {
      echo 'error';
      die($connection->error . __LINE__);
    } else {
      echo 'success';
    }
    break;

  /* --------------- Delete ------------*/
  case 'delete':
    check_access('hapus');
    $id = intval(epm_decode($_POST['id']));
    // Hapus avatar
    $stmt_del_sel = $connection->prepare("SELECT avatar, nisn FROM user WHERE user_id=?");
    $stmt_del_sel->bind_param('i', $id);
    $stmt_del_sel->execute();
    $result = $stmt_del_sel->get_result();
    $nisn = '';
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $stmt_del_sel->close();
      $avatar_delete = $row['avatar'];
      $nisn = $row['nisn'];
      $tmpfile_avatar = "../../../content/avatar/" . $avatar_delete;
      if (file_exists($tmpfile_avatar) && $avatar_delete !== 'avatar.jpg') {
        @unlink($tmpfile_avatar);
      }
    } else { $stmt_del_sel->close(); }
    // Hapus semua berkas di berkas_siswa
    $stmt_del_b = $connection->prepare("SELECT kk, ijazah, akte, kip, kks FROM berkas_siswa WHERE user_id=?");
    $stmt_del_b->bind_param('i', $id);
    $stmt_del_b->execute();
    $q_berkas = $stmt_del_b->get_result();
    $folder_berkas = '../../../content/berkas/';
    if ($q_berkas && $q_berkas->num_rows > 0) {
      $berkas = $q_berkas->fetch_assoc();
      foreach (['kk', 'ijazah', 'akte', 'kip', 'kks'] as $b) {
        if (!empty($berkas[$b]) && file_exists($folder_berkas . $berkas[$b])) {
          @unlink($folder_berkas . $berkas[$b]);
        }
      }
    }
    $stmt_del_b->close();
    // Hapus file qrcode
    $folder_qrcode = '../../../content/qrcode/';
    if (!empty($nisn)) {
      $qrcode_files = glob($folder_qrcode . $nisn . '*.jpg');
      if ($qrcode_files) {
        foreach ($qrcode_files as $file) {
          if (file_exists($file)) {
            @unlink($file);
          }
        }
      }
    }
    // Hapus data berkas_siswa, statistik, usulan
    $stmt_d1 = $connection->prepare("DELETE FROM berkas_siswa WHERE user_id=?");
    $stmt_d1->bind_param('i', $id); $stmt_d1->execute(); $stmt_d1->close();
    $stmt_d2 = $connection->prepare("DELETE FROM statistik WHERE user_id=?");
    $stmt_d2->bind_param('i', $id); $stmt_d2->execute(); $stmt_d2->close();
    $stmt_d3 = $connection->prepare("DELETE FROM usulan WHERE user_id=?");
    $stmt_d3->bind_param('i', $id); $stmt_d3->execute(); $stmt_d3->close();
    // Jika ada tabel yang pakai nisn
    if (!empty($nisn)) {
      $stmt_d4 = $connection->prepare("DELETE FROM berkas_siswa WHERE nisn=?");
      $stmt_d4->bind_param('s', $nisn); $stmt_d4->execute(); $stmt_d4->close();
      $stmt_d5 = $connection->prepare("DELETE FROM statistik WHERE nisn=?");
      $stmt_d5->bind_param('s', $nisn); $stmt_d5->execute(); $stmt_d5->close();
      $stmt_d6 = $connection->prepare("DELETE FROM usulan WHERE nisn=?");
      $stmt_d6->bind_param('s', $nisn); $stmt_d6->execute(); $stmt_d6->close();
    }
    // Hapus data user
    $stmt_del_u = $connection->prepare("DELETE FROM user WHERE user_id=?");
    $stmt_del_u->bind_param('i', $id);
    if ($stmt_del_u->execute()) {
      echo 'success';
    } else {
      echo 'Data tidak berhasil dihapus.';
    }
    break;

  /* ----- Import -------*/
  case 'import':
    check_access('modifikasi');
    if (!empty($_FILES['files']['name'])) {
      $fileType = $_FILES['files']['type'];
      $fileTmp = $_FILES['files']['tmp_name'];
      $isXLSX = (pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION) == 'xlsx');
      if ($isXLSX) {
        try {
          $spreadsheet = IOFactory::load($fileTmp);
          $sheet = $spreadsheet->getActiveSheet();
          $rows = $sheet->toArray();
          $header = array_map('strtolower', $rows[0]);
          $nisn_idx = array_search('nisn', $header);
          if ($nisn_idx === false) {
            echo 'Kolom NISN wajib ada di file.';
            exit;
          }
          // Ambil kolom user dari database
          $user_columns = [];
          $res = $connection->query("SHOW COLUMNS FROM user");
          while ($col = $res->fetch_assoc()) {
            $user_columns[] = strtolower($col['Field']);
          }
          // Kolom yang akan diimport (hanya yang ada di tabel user dan di excel)
          $import_columns = array_intersect($header, $user_columns);
          if (!in_array('nisn', $import_columns)) $import_columns[] = 'nisn';
          // Pastikan kolom password, time, dan date selalu ikut di-insert
          if (!in_array('password', $import_columns)) $import_columns[] = 'password';
          if (!in_array('time', $import_columns)) $import_columns[] = 'time';
          if (!in_array('date', $import_columns)) $import_columns[] = 'date';
          $kelas_idx = array_search('kelas', $header);
          $data = [];
          $kelas_map = [];
          if ($kelas_idx !== false) {
            // Ambil semua kelas yang sudah ada
            $kelas_result = $connection->query("SELECT kelas_id, nama_kelas FROM kelas");
            while ($row = $kelas_result->fetch_assoc()) {
              $kelas_map[mb_strtolower(trim($row['nama_kelas']))] = $row['kelas_id'];
            }
          }
          for ($i = 1; $i < count($rows); $i++) {
            $row_data = [];
            $nisn = trim($rows[$i][$nisn_idx]);
            if ($nisn == '') continue;
            foreach ($import_columns as $col) {
              $idx = array_search($col, $header);
              if ($idx !== false) {
                $val = trim($rows[$i][$idx]);
                if ($col == 'kelas') {
                  $nama_kelas = $val;
                  $nama_kelas_lc = mb_strtolower($nama_kelas);
                  if ($nama_kelas != '') {
                    if (!isset($kelas_map[$nama_kelas_lc])) {
                      // Insert kelas baru
                      $stmt_kls = $connection->prepare("INSERT INTO kelas (nama_kelas) VALUES (?)");
                      $stmt_kls->bind_param('s', $nama_kelas);
                      $stmt_kls->execute();
                      $kelas_id_baru = $stmt_kls->insert_id;
                      $stmt_kls->close();
                      $kelas_map[$nama_kelas_lc] = $kelas_id_baru;
                    }
                    $row_data[$col] = $kelas_map[$nama_kelas_lc];
                  } else {
                    $row_data[$col] = '';
                  }
                } else if ($col == 'jenis_kelamin') {
                  if (strtolower($val) == 'l') {
                    $row_data[$col] = 'Laki-laki';
                  } else if (strtolower($val) == 'p') {
                    $row_data[$col] = 'Perempuan';
                  } else {
                    $row_data[$col] = $connection->real_escape_string($val);
                  }
                } else {
                  $row_data[$col] = $connection->real_escape_string($val);
                }
              } else {
                $row_data[$col] = '';
              }
            }
            // Set default value untuk kolom NOT NULL jika kosong
            $not_null_defaults = [
              'nik' => '-',
              'nipd' => '-',
              'nama_lengkap' => '-',
              'tempat_lahir' => '-',
              'tanggal_lahir' => '2000-01-01',
              'jenis_kelamin' => '-',
              'kelas' => '-',
              'nama_ayah' => '-',
              'pekerjaan_ayah' => '-',
              'nama_ibu' => '-',
              'pekerjaan_ibu' => '-',
              'alamat' => '-',
              'telp' => '-',
              'anak_ke' => '1',
              'avatar' => 'avatar.jpg',
            ];
            foreach ($not_null_defaults as $col => $def) {
              if (!isset($row_data[$col]) || $row_data[$col] === '') {
                $row_data[$col] = $def;
              }
            }
            // Set password default ke hash(NISN) jika kosong
            if (!isset($row_data['password']) || $row_data['password'] === '' || $row_data['password'] === null) {
              $row_data['password'] = password_hash($nisn, PASSWORD_DEFAULT);
            }
            // Set kolom time dan date
            $row_data['time'] = time();
            $row_data['date'] = date('Y-m-d');
            $data[] = $row_data;
          }
          if (count($data) == 0) {
            echo 'Tidak ada data valid untuk diimport.';
            exit;
          }
          $connection->begin_transaction();
          try {
            $batchSize = 20;
            $col_sql = '`' . implode('`,`', $import_columns) . '`';
            $update_sql = [];
            foreach ($import_columns as $col) {
              // Jangan update password jika sudah ada (biarkan tetap yang lama)
              if ($col != 'nisn' && $col != 'password') $update_sql[] = "`$col`=VALUES(`$col`)";
            }
            $total = count($data);
            for ($start = 0; $start < $total; $start += $batchSize) {
              $values = [];
              $end = min($start + $batchSize, $total);
              for ($i = $start; $i < $end; $i++) {
                $row = $data[$i];
                $row_values = [];
                foreach ($import_columns as $col) {
                  $row_values[] = "'" . $row[$col] . "'";
                }
                $values[] = '(' . implode(',', $row_values) . ')';
              }
              $sql = "INSERT INTO user ($col_sql) VALUES " . implode(',', $values) . " ON DUPLICATE KEY UPDATE " . implode(',', $update_sql);
              if (!$connection->query($sql)) {
                if ($connection->errno > 0) {
                  $connection->rollback();
                  echo 'SQL ERROR: ' . $connection->errno . ' - ' . $connection->error . '<br>Query: ' . $sql;
                  exit;
                }
                // Jika errno 0, lanjutkan (anggap warning saja)
              }
            }
            $connection->commit();
            // Hanya tampilkan success jika tidak ada error fatal
            echo 'success';
          } catch (Exception $e) {
            $connection->rollback();
            echo 'Gagal import: ' . $e->getMessage();
            exit;
          }
        } catch (Exception $e) {
          echo '<pre style="color:red">ERROR XLSX: ' . $e->getMessage() . '</pre>';
          exit;
        }
      } else {
        echo 'File tidak sesuai format, Upload file XLSX!';
      }
    }
    break;

  /* ---------- UPLOAD PHOTO USER ---------- */
  case 'upload_photo':
    check_access('modifikasi');
    if (isset($_FILES['photo_files']) && $_FILES['photo_files']['error'] == 0) {
      $file_name = $_FILES['photo_files']['name'];
      $file_tmp = $_FILES['photo_files']['tmp_name'];
      $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
      $target_dir = '../../../content/avatar/';
      $success = [];
      $failed = [];

      if ($file_ext == 'zip') {
        // Ekstrak ZIP
        $zip = new ZipArchive;
        if ($zip->open($file_tmp) === TRUE) {
          for ($i = 0; $i < $zip->numFiles; $i++) {
            $zip_file = $zip->getNameIndex($i);
            $zip_ext = strtolower(pathinfo($zip_file, PATHINFO_EXTENSION));
            $nisn = pathinfo($zip_file, PATHINFO_FILENAME);
            if (in_array($zip_ext, ['jpg', 'jpeg', 'png'])) {
              $stream = $zip->getStream($zip_file);
              if ($stream) {
                $out_path = $target_dir . $nisn . '.' . $zip_ext;
                $out_file = fopen($out_path, 'w');
                while (!feof($stream)) {
                  fwrite($out_file, fread($stream, 8192));
                }
                fclose($out_file);
                fclose($stream);
                $avatar_val = $nisn . '.' . $zip_ext;
                $stmt_zu = $connection->prepare("UPDATE user SET avatar=? WHERE nisn=?");
                $stmt_zu->bind_param('ss', $avatar_val, $nisn);
                $update = $stmt_zu->execute();
                $stmt_zu->close();
                if ($update) $success[] = $nisn;
                else $failed[] = $nisn;
              }
            }
          }
          $zip->close();
          echo 'Berhasil upload foto untuk: ' . implode(', ', $success) . '. Gagal: ' . implode(', ', $failed);
        } else {
          echo 'Gagal membuka file ZIP.';
        }
      } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
        // Single file
        $nisn = pathinfo($file_name, PATHINFO_FILENAME);
        $new_name = $nisn . '.' . $file_ext;
        if (move_uploaded_file($file_tmp, $target_dir . $new_name)) {
          $stmt_sf = $connection->prepare("UPDATE user SET avatar=? WHERE nisn=?");
          $stmt_sf->bind_param('ss', $new_name, $nisn);
          $update = $stmt_sf->execute();
          $stmt_sf->close();
          if ($update) echo 'Berhasil upload foto untuk NISN: ' . $nisn;
          else echo 'Upload berhasil, update DB gagal.';
        } else {
          echo 'Upload file gagal.';
        }
      } else {
        echo 'Format file tidak didukung.';
      }
    } else {
      echo 'Tidak ada file yang diupload.';
    }
    break;

  /* ----------- Reset Password User ----------- */
  case 'reset_password':
    check_access('modifikasi');
    $id = intval(epm_decode($_POST['id']));
    $stmt_rp = $connection->prepare("SELECT nisn FROM user WHERE user_id=?");
    $stmt_rp->bind_param('i', $id);
    $stmt_rp->execute();
    $result = $stmt_rp->get_result();
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $stmt_rp->close();
      $nisn = $row['nisn'];
      $password_hash = password_hash($nisn, PASSWORD_DEFAULT);
      $stmt_up = $connection->prepare("UPDATE user SET password=? WHERE user_id=?");
      $stmt_up->bind_param('si', $password_hash, $id);
      if ($update) {
        echo 'success';
      } else {
        echo 'Gagal reset password.';
      }
    } else {
      echo 'User tidak ditemukan.';
    }
    break;

  /* --------------- Delete All ------------*/
  case 'delete_all':
    check_access('hapus');
    // Ambil semua user_id dan nisn
    $result = $connection->query("SELECT user_id, nisn, avatar FROM user");
    $folder_avatar = '../../../content/avatar/';
    $folder_berkas = '../../../content/berkas/';
    $folder_qrcode = '../../../content/qrcode/';
    $deleted_count = 0;
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $id = $row['user_id'];
        $nisn = $row['nisn'];
        $avatar_delete = strip_tags($row['avatar']);
        // Hapus avatar
        $tmpfile_avatar = $folder_avatar . $avatar_delete;
        if (file_exists($tmpfile_avatar) && $avatar_delete !== 'avatar.jpg') {
          @unlink($tmpfile_avatar);
        }
        // Hapus semua berkas di berkas_siswa
        $stmt_sel_b = $connection->prepare("SELECT kk, ijazah, akte, kip, kks FROM berkas_siswa WHERE user_id=?");
        $stmt_sel_b->bind_param('s', $id);
        $stmt_sel_b->execute();
        $q_berkas = $stmt_sel_b->get_result();
        $stmt_sel_b->close();
        if ($q_berkas && $q_berkas->num_rows > 0) {
          $berkas = $q_berkas->fetch_assoc();
          foreach (['kk', 'ijazah', 'akte', 'kip', 'kks'] as $b) {
            if (!empty($berkas[$b]) && file_exists($folder_berkas . $berkas[$b])) {
              @unlink($folder_berkas . $berkas[$b]);
            }
          }
        }
        // Hapus file qrcode
        if (!empty($nisn)) {
          $qrcode_files = glob($folder_qrcode . $nisn . '*.jpg');
          if ($qrcode_files) {
            foreach ($qrcode_files as $file) {
              if (file_exists($file)) {
                @unlink($file);
              }
            }
          }
        }
        // Hapus data berkas_siswa, statistik, usulan
        $stmt_d1 = $connection->prepare("DELETE FROM berkas_siswa WHERE user_id=?");
        $stmt_d1->bind_param('s', $id); $stmt_d1->execute(); $stmt_d1->close();
        $stmt_d2 = $connection->prepare("DELETE FROM statistik WHERE user_id=?");
        $stmt_d2->bind_param('s', $id); $stmt_d2->execute(); $stmt_d2->close();
        $stmt_d3 = $connection->prepare("DELETE FROM usulan WHERE user_id=?");
        $stmt_d3->bind_param('s', $id); $stmt_d3->execute(); $stmt_d3->close();
        // Jika ada tabel yang pakai nisn
        if (!empty($nisn)) {
          $stmt_d4 = $connection->prepare("DELETE FROM berkas_siswa WHERE nisn=?");
          $stmt_d4->bind_param('s', $nisn); $stmt_d4->execute(); $stmt_d4->close();
          $stmt_d5 = $connection->prepare("DELETE FROM statistik WHERE nisn=?");
          $stmt_d5->bind_param('s', $nisn); $stmt_d5->execute(); $stmt_d5->close();
          $stmt_d6 = $connection->prepare("DELETE FROM usulan WHERE nisn=?");
          $stmt_d6->bind_param('s', $nisn); $stmt_d6->execute(); $stmt_d6->close();
        }
        // Hapus data user
        $stmt_du = $connection->prepare("DELETE FROM user WHERE user_id=?");
        $stmt_du->bind_param('s', $id);
        $stmt_du->execute();
        $stmt_du->close();
        $deleted_count++;
      }
      echo 'success';
    } else {
      echo 'Tidak ada data user yang ditemukan.';
    }
    break;

  /* ----------- UPLOAD FOTO BERDASARKAN NISN ----------- */
  case 'upload_foto_nisn':
    check_access('modifikasi');
    if (isset($_FILES['foto']) && isset($_POST['nisn'])) {
      $nisn = preg_replace('/[^0-9]/', '', $_POST['nisn']);
      $foto = $_FILES['foto'];
      $allowed_ext = ['png', 'jpg', 'jpeg'];
      $ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed_ext)) {
        echo 'Format file tidak didukung. Hanya PNG/JPG/JPEG.';
        exit;
      }
      $target_dir = '../../../content/avatar/';
      $target_file = $target_dir . $nisn . '.png';
      // Konversi ke PNG jika bukan PNG
      if ($ext !== 'png') {
        $img = null;
        if ($ext === 'jpg' || $ext === 'jpeg') {
          $img = imagecreatefromjpeg($foto['tmp_name']);
        }
        if ($img) {
          imagepng($img, $target_file);
          imagedestroy($img);
        } else {
          echo 'Gagal konversi gambar.';
          exit;
        }
      } else {
        move_uploaded_file($foto['tmp_name'], $target_file);
      }
      // Update database
      $stmt_uft = $connection->prepare("UPDATE user SET avatar=? WHERE nisn=?");
      $filename_db = $nisn . '.png';
      $stmt_uft->bind_param('ss', $filename_db, $nisn);
      if ($stmt_uft->execute()) {
        echo 'success';
      } else {
        echo 'Gagal update database.';
      }
    } else {
      echo 'Data tidak lengkap.';
    }
    break;
}
