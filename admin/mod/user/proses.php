<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
include('../../../library/function.php');
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
$modul_id = 3;
include __DIR__ . '/../check_role.php';

function check_access($type)
{
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
    $id = anti_injection(epm_decode($_POST['id']));
    $set = isset($_POST['set']) ? intval($_POST['set']) : 1;
    // Pastikan hanya satu koordinator per kelas jika diperlukan (optional)
    // $kelas = isset($_POST['kelas']) ? anti_injection($_POST['kelas']) : '';
    // if ($set == 1 && $kelas != '') {
    //   $connection->query("UPDATE user SET koordinator=0 WHERE kelas='$kelas'");
    // }
    $update = $connection->query("UPDATE user SET koordinator='$set' WHERE user_id='$id'");
    if ($update) {
      echo 'success';
    } else {
      echo 'Gagal set koordinator.';
    }
    break;

  /* ----------- SEARCH DUPLICATE DATA ----------- */
  case 'search':
    check_access('lihat');
    // Cari nilai yang dipakai oleh lebih dari 1 user di tabel user
    $query_raw = isset($_POST['query']) ? trim($_POST['query']) : '';
    $search_by = isset($_POST['search_by']) ? trim($_POST['search_by']) : 'nik_no_kk';
    if ($query_raw === '') {
      // No value selected — return empty result. The client will handle showing/clearing results.
      break;
    }
    $q = $connection->real_escape_string($query_raw);
    $where = '';
    if ($search_by === 'email') {
      $where = "email='" . $q . "'";
    } else if ($search_by === 'hp') {
      // cari pada kolom telp dan telp_wali jika ada
      $where = "telp='" . $q . "' OR telp_wali='" . $q . "'";
    } else {
      // default: nik or no_kk
      $where = "nik='" . $q . "' OR no_kk='" . $q . "'";
    }

    $sql = "SELECT user_id, nisn, nama_lengkap, nik, no_kk, email, telp FROM user WHERE " . $where . " ORDER BY nama_lengkap ASC";
    $res = $connection->query($sql);
    if (!$res) {
      echo '<div class="alert alert-danger">Query error: ' . $connection->error . '</div>';
      break;
    }
    $count = $res->num_rows;
    if ($count == 0) {
      echo '<div class="alert alert-info">Tidak ditemukan user dengan nilai yang dicari.</div>';
      break;
    }
    // Jika hanya 1 hasil, beri peringatan kalau nilai hanya dipakai 1 user
    if ($count == 1) {
      $row = $res->fetch_assoc();
      echo '<div class="alert alert-warning">Nilai tersebut saat ini hanya terpakai oleh <strong>1</strong> user.</div>';
      // wrap table in a scrollable responsive container so it fits inside the modal
      echo '<div class="table-responsive" style="max-height:45vh;overflow:auto;">';
      echo '<table class="table table-sm table-bordered" style="white-space:nowrap;">';
      echo '<tr><th>NISN</th><th>Nama</th><th>NIK</th><th>No KK</th><th>Email</th><th>Telp</th><th>Aksi</th></tr>';
      echo '<tr>';
      echo '<td>' . htmlspecialchars($row['nisn']) . '</td>';
      echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
      echo '<td>' . htmlspecialchars($row['nik']) . '</td>';
      echo '<td>' . htmlspecialchars($row['no_kk']) . '</td>';
      echo '<td>' . htmlspecialchars($row['email']) . '</td>';
      echo '<td>' . htmlspecialchars($row['telp']) . '</td>';
      echo '<td><a class="btn btn-sm btn-info" href="./user?op=profile&id=' . epm_encode($row['user_id']) . '">Profil</a> '
        . '<a class="btn btn-sm btn-primary" href="./user?op=update&id=' . epm_encode($row['user_id']) . '">Edit</a></td>';
      echo '</tr>';
      echo '</table>';
      echo '</div>';
      break;
    }

    // Jika lebih dari 1, tampilkan daftar (ini yang berarti duplikat)
    echo '<div class="alert alert-danger">Ditemukan <strong>' . $count . '</strong> user yang memakai nilai tersebut. Periksa data berikut:</div>';
    // wrap table in a scrollable responsive container so it fits inside the modal
    echo '<div class="table-responsive" style="max-height:45vh;overflow:auto;">';
    echo '<table class="table table-sm table-bordered" style="white-space:nowrap;">';
    echo '<thead><tr><th style="width:30px;">No</th><th>NISN</th><th>Nama</th><th>NIK</th><th>No KK</th><th>Email</th><th>Telp</th><th>Aksi</th></tr></thead><tbody>';
    $no = 1;
    while ($row = $res->fetch_assoc()) {
      echo '<tr>';
      echo '<td class="text-center">' . $no . '</td>';
      echo '<td>' . htmlspecialchars($row['nisn']) . '</td>';
      echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
      echo '<td>' . htmlspecialchars($row['nik']) . '</td>';
      echo '<td>' . htmlspecialchars($row['no_kk']) . '</td>';
      echo '<td>' . htmlspecialchars($row['email']) . '</td>';
      echo '<td>' . htmlspecialchars($row['telp']) . '</td>';
      echo '<td><a class="btn btn-sm btn-info" href="./user?op=profile&id=' . epm_encode($row['user_id']) . '">Profil</a> '
        . '<a class="btn btn-sm btn-primary" href="./user?op=update&id=' . epm_encode($row['user_id']) . '">Edit</a></td>';
      echo '</tr>';
      $no++;
    }
    echo '</tbody></table></div>';
    break;

  /* ----------- SEARCH OPTIONS (distinct values) ----------- */
  case 'search_options':
    check_access('lihat');
    $search_by = isset($_POST['search_by']) ? trim($_POST['search_by']) : 'nik_no_kk';
    $options = '';
    if ($search_by === 'email') {
      // Only return values that appear more than once (duplicates)
      $sql = "SELECT email AS val, COUNT(*) AS cnt FROM user WHERE email IS NOT NULL AND email <> '' GROUP BY email HAVING COUNT(*) > 1 ORDER BY cnt DESC, val ASC";
    } else if ($search_by === 'hp') {
      $sql = "SELECT telp AS val, COUNT(*) AS cnt FROM user WHERE telp IS NOT NULL AND telp <> '' GROUP BY telp HAVING COUNT(*) > 1 ORDER BY cnt DESC, val ASC";
    } else {
      // nik and no_kk combined — only duplicates
      $sql = "SELECT val, COUNT(*) AS cnt FROM (
                SELECT nik AS val FROM user WHERE nik IS NOT NULL AND nik <> ''
                UNION ALL
                SELECT no_kk AS val FROM user WHERE no_kk IS NOT NULL AND no_kk <> ''
              ) t GROUP BY val HAVING COUNT(*) > 1 ORDER BY cnt DESC, val ASC";
    }
    $res = $connection->query($sql);
    if (!$res) {
      echo '<option value="">Gagal memuat daftar: ' . htmlspecialchars($connection->error) . '</option>';
      break;
    }
    if ($res->num_rows == 0) {
      echo '<option value="">(Tidak ada data)</option>';
      break;
    }
    echo '<option value="">-- Pilih nilai --</option>';
    while ($r = $res->fetch_assoc()) {
      $val = $r['val'];
      $cnt = isset($r['cnt']) ? intval($r['cnt']) : 1;
      $label = htmlspecialchars($val);
      if ($cnt > 1) $label .= ' (' . $cnt . ' pengguna)';
      echo '<option value="' . htmlspecialchars($val) . '">' . $label . '</option>';
    }
    break;

  case 'update':
    check_access('modifikasi');
    $error = array();
    $id = !empty($_POST['id']) ? $connection->real_escape_string(epm_decode(trim($_POST['id']))) : '';

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
      $$field = isset($_POST[$field]) ? $connection->real_escape_string(trim($_POST[$field])) : '';
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
      $$field = isset($_POST[$field]) ? $connection->real_escape_string(trim($_POST[$field])) : '';
    }

    // --- Blok C: Wali ---
    $blokC = [
      'nama_wali',
      'alamat_wali',
      'telp_wali',
      'pekerjaan_wali'
    ];
    foreach ($blokC as $field) {
      $$field = isset($_POST[$field]) ? $connection->real_escape_string(trim($_POST[$field])) : '';
    }

    // Server-side empty-field validation intentionally skipped for update.
    // Require only a valid ID to proceed; the form may submit partial updates.
    if (empty($id)) {
      echo 'ID tidak ditemukan';
      break;
    }

    // Jika password diisi, hash dan update. Jika kosong, skip/hapus dari query
    $set_password = '';
    if (!empty($password)) {
      $password_hash = password_hash($password, PASSWORD_DEFAULT);
      $set_password = ", password='$password_hash'";
    }
    $update = "UPDATE user SET
    no_kk='$no_kk', nik='$nik', nipd='$nipd', nisn='$nisn', nama_lengkap='$nama_lengkap', sekolah_asal='$sekolah_asal', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', agama='$agama', jenis_kelamin='$jenis_kelamin', status_keluarga='$status_keluarga', kelas='$kelas', diterima_dikelas='$diterima_dikelas', diterima_tanggal='$diterima_tanggal', rt='$rt', rw='$rw', desa='$desa', kecamatan='$kecamatan', kodepos='$kodepos', email='$email', telp='$telp', anak_ke='$anak_ke', alamat='$alamat', status='$status', nik_ayah='$nik_ayah', nama_ayah='$nama_ayah', pekerjaan_ayah='$pekerjaan_ayah', nik_ibu='$nik_ibu', nama_ibu='$nama_ibu', pekerjaan_ibu='$pekerjaan_ibu', nama_wali='$nama_wali', alamat_wali='$alamat_wali', telp_wali='$telp_wali', pekerjaan_wali='$pekerjaan_wali', konfirmasi='Belum Konfirmasi'" . $set_password . " WHERE user_id='$id'";
    if ($connection->query($update)) {
      echo 'success';
    } else {
      echo 'Gagal update: ' . $connection->error;
    }
    break;

  /** Setactive user */
  case 'active':
    check_access('modifikasi');
    $id = htmlentities($_POST['id']);
    $active = htmlentities($_POST['active']);
    // Ubah nilai menjadi string status
    $status = ($active === 'Y') ? 'Aktif' : 'Tidak Aktif';
    $update = "UPDATE user SET status='$status' WHERE user_id='$id'";
    if ($connection->query($update) === false) {
      echo 'error';
      die($connection->error . __LINE__);
    } else {
      echo 'success';
    }
    break;

  /* --------------- Delete ------------*/
  case 'delete':
    check_access('hapus');
    $id = anti_injection(epm_decode($_POST['id']));
    // Hapus avatar
    $query = "SELECT avatar, nisn FROM user WHERE user_id='$id'";
    $result = $connection->query($query);
    $nisn = '';
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $avatar_delete = strip_tags($row['avatar']);
      $nisn = isset($row['nisn']) ? strip_tags($row['nisn']) : '';
      // strip query string from avatar value before removing file on disk
      $avatar_file = preg_replace('/\?.*/', '', $avatar_delete);
      $tmpfile_avatar = "../../../content/avatar/" . $avatar_file;
      if (file_exists($tmpfile_avatar) && $avatar_file !== 'avatar.jpg') {
        @unlink($tmpfile_avatar); // gunakan @ untuk suppress error
      }
    }
    // Hapus semua berkas di berkas_siswa
    $q_berkas = $connection->query("SELECT kk, ijazah, akte, kip, kks FROM berkas_siswa WHERE user_id='$id'");
    $folder_berkas = '../../../content/berkas/';
    if ($q_berkas && $q_berkas->num_rows > 0) {
      $berkas = $q_berkas->fetch_assoc();
      foreach (['kk', 'ijazah', 'akte', 'kip', 'kks'] as $b) {
        if (!empty($berkas[$b]) && file_exists($folder_berkas . $berkas[$b])) {
          @unlink($folder_berkas . $berkas[$b]);
        }
      }
    }
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
    $connection->query("DELETE FROM berkas_siswa WHERE user_id='$id'");
    $connection->query("DELETE FROM statistik WHERE user_id='$id'");
    $connection->query("DELETE FROM usulan WHERE user_id='$id'");
    // Jika ada tabel yang pakai nisn
    if (!empty($nisn)) {
      $connection->query("DELETE FROM berkas_siswa WHERE nisn='$nisn'");
      $connection->query("DELETE FROM statistik WHERE nisn='$nisn'");
      $connection->query("DELETE FROM usulan WHERE nisn='$nisn'");
    }
    // Hapus data user
    $deleted = "DELETE FROM user WHERE user_id='$id'";
    if ($connection->query($deleted) === true) {
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
                      $connection->query("INSERT INTO kelas (nama_kelas) VALUES ('" . $connection->real_escape_string($nama_kelas) . "')");
                      $kelas_id_baru = $connection->insert_id;
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
              // Hapus foto lama jika ada (selain avatar.jpg)
              $query = $connection->query("SELECT avatar FROM user WHERE nisn='" . $nisn . "'");
              if ($query && $query->num_rows > 0) {
                $row = $query->fetch_assoc();
                $old_avatar = $row['avatar'];
                // strip any query string (e.g. ?t=123) before checking/removing file on disk
                $old_avatar_file = preg_replace('/\?.*/', '', $old_avatar);
                $old_path = $target_dir . $old_avatar_file;
                if (file_exists($old_path) && $old_avatar_file !== 'avatar.jpg') {
                  @unlink($old_path);
                }
              }
              $stream = $zip->getStream($zip_file);
              if ($stream) {
                $out_path = $target_dir . $nisn . '.' . $zip_ext;
                $out_file = fopen($out_path, 'w');
                while (!feof($stream)) {
                  fwrite($out_file, fread($stream, 8192));
                }
                fclose($out_file);
                fclose($stream);
                // Update DB: keep file named as nisn.ext, but append timestamp in DB value to bust cache
                $filename = $nisn . '.' . $zip_ext;
                $avatar_db = $filename . '?t=' . time();
                $update = $connection->query("UPDATE user SET avatar='" . $connection->real_escape_string($avatar_db) . "' WHERE nisn='" . $nisn . "'");
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
        // Hapus foto lama jika ada (selain avatar.jpg)
        $query = $connection->query("SELECT avatar FROM user WHERE nisn='" . $nisn . "'");
        if ($query && $query->num_rows > 0) {
          $row = $query->fetch_assoc();
          $old_avatar = $row['avatar'];
          $old_avatar_file = preg_replace('/\?.*/', '', $old_avatar);
          $old_path = $target_dir . $old_avatar_file;
          if (file_exists($old_path) && $old_avatar_file !== 'avatar.jpg') {
            @unlink($old_path);
          }
        }
        $new_name = $nisn . '.' . $file_ext;
        if (move_uploaded_file($file_tmp, $target_dir . $new_name)) {
          // Update DB with timestamp query parameter so record changes and browsers reload
          $avatar_db = $new_name . '?t=' . time();
          $update = $connection->query("UPDATE user SET avatar='" . $connection->real_escape_string($avatar_db) . "' WHERE nisn='" . $nisn . "'");
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

  /* ----------- Reset Password User (Simple - Tanpa WhatsApp) ----------- */
  case 'reset_password_simple':
    check_access('modifikasi');
    $id = anti_injection(epm_decode($_POST['id']));
    $query = "SELECT nisn, nama_lengkap FROM user WHERE user_id='$id'";
    $result = $connection->query($query);
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $nisn = $row['nisn'];
      $nama = $row['nama_lengkap'];
      
      $password_hash = password_hash($nisn, PASSWORD_DEFAULT);
      $update = $connection->query("UPDATE user SET password='$password_hash' WHERE user_id='$id'");
      if ($update) {
        echo 'success';
      } else {
        echo 'Gagal reset password.';
      }
    } else {
      echo 'User tidak ditemukan.';
    }
    break;

  /* ----------- Reset Password User (Dengan WhatsApp) ----------- */
  case 'reset_password':
    check_access('modifikasi');
    $id = anti_injection(epm_decode($_POST['id']));
    $query = "SELECT nisn, nama_lengkap, telp, whatsapp_verified FROM user WHERE user_id='$id'";
    $result = $connection->query($query);
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $nisn = $row['nisn'];
      $nama = $row['nama_lengkap'];
      $telp = $row['telp'];
      $wa_verified = $row['whatsapp_verified'];
      
      $password_hash = password_hash($nisn, PASSWORD_DEFAULT);
      $update = $connection->query("UPDATE user SET password='$password_hash' WHERE user_id='$id'");
      if ($update) {
        // Jika reset berhasil dan nomor HP ada serta terverifikasi, kirim WhatsApp
        if (!empty($telp) && $wa_verified == 1) {
          // Include WhatsApp Gateway library
          require_once '../../../library/whatsapp-gateway.php';
          
          // Format nomor untuk WhatsApp (Indonesia)
          $wa_number = preg_replace('/[^0-9]/', '', $telp);
          if (substr($wa_number, 0, 1) == '0') {
            $wa_number = '62' . substr($wa_number, 1);
          }
          
          // Pesan WhatsApp
          $wa_message = "🔒 *RESET PASSWORD SAE SMK NEGERI 1 PAGELARAN*\n\n";
          $wa_message .= "Halo *{$nama}*,\n\n";
          $wa_message .= "Admin telah mereset password akun Anda!\n\n";
          $wa_message .= "📋 *Detail Akun:*\n";
          $wa_message .= "• Username/NISN: *{$nisn}*\n";
          $wa_message .= "• Password: *{$nisn}*\n";
          $wa_message .= "• Status: Aktif\n\n";
          $wa_message .= "🌐 *Link Login:*\n";
          $wa_message .= "https://sae.smakpal.sch.id/login\n\n";
          $wa_message .= "⚠️ *PENTING:*\n";
          $wa_message .= "• Segera login dan ubah password Anda\n";
          $wa_message .= "• Jangan berikan informasi ini kepada siapa pun\n\n";
          $wa_message .= "_Reset dilakukan oleh Admin pada " . date('d/m/Y H:i') . " WIB_\n";
          $wa_message .= "_SMK Negeri 1 Pagelaran_";
          
          // Kirim WhatsApp
          $wa_result = sendWhatsAppNotification($wa_number, $wa_message, 'admin_reset_password', $connection, $id);
          
          if ($wa_result['success']) {
            echo 'success_with_wa';
          } else {
            echo 'success_no_wa';
          }
        } else {
          echo 'success_no_wa';
        }
      } else {
        echo 'Gagal reset password.';
      }
    } else {
      echo 'User tidak ditemukan.';
    }
    break;

  /* ----------- Reset Konfirmasi Data ----------- */
  case 'reset_konfirmasi':
    check_access('modifikasi');
    // Ambil dan bersihkan id yang dikirim (mengikuti pola yang ada di file ini)
    $id = anti_injection(epm_decode($_POST['id']));
    if (empty($id)) {
      echo 'ID tidak valid.';
      break;
    }
    // Set ulang field konfirmasi menjadi 'Belum Konfirmasi'
    $update = $connection->query("UPDATE user SET konfirmasi='Belum Konfirmasi' WHERE user_id='$id'");
    if ($update) {
      echo 'success';
    } else {
      echo 'Gagal reset konfirmasi: ' . $connection->error;
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
        // Hapus avatar (strip possible query param first)
        $avatar_file = preg_replace('/\?.*/', '', $avatar_delete);
        $tmpfile_avatar = $folder_avatar . $avatar_file;
        if (file_exists($tmpfile_avatar) && $avatar_file !== 'avatar.jpg') {
          @unlink($tmpfile_avatar);
        }
        // Hapus semua berkas di berkas_siswa
        $q_berkas = $connection->query("SELECT kk, ijazah, akte, kip, kks FROM berkas_siswa WHERE user_id='$id'");
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
        $connection->query("DELETE FROM berkas_siswa WHERE user_id='$id'");
        $connection->query("DELETE FROM statistik WHERE user_id='$id'");
        $connection->query("DELETE FROM usulan WHERE user_id='$id'");
        // Jika ada tabel yang pakai nisn
        if (!empty($nisn)) {
          $connection->query("DELETE FROM berkas_siswa WHERE nisn='$nisn'");
          $connection->query("DELETE FROM statistik WHERE nisn='$nisn'");
          $connection->query("DELETE FROM usulan WHERE nisn='$nisn'");
        }
        // Hapus data user
        $connection->query("DELETE FROM user WHERE user_id='$id'");
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
      // Hapus foto lama jika ada (selain avatar.jpg)
      $query = $connection->query("SELECT avatar FROM user WHERE nisn='" . $nisn . "'");
      if ($query && $query->num_rows > 0) {
        $row = $query->fetch_assoc();
        $old_avatar = $row['avatar'];
        $old_avatar_file = preg_replace('/\?.*/', '', $old_avatar);
        $old_path = $target_dir . $old_avatar_file;
        if (file_exists($old_path) && $old_avatar_file !== 'avatar.jpg') {
          @unlink($old_path);
        }
      }
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
      // Update database with timestamp appended to value so browsers reload new image
      $avatar_db = $nisn . '.png?t=' . time();
      $update = $connection->query("UPDATE user SET avatar='" . $connection->real_escape_string($avatar_db) . "' WHERE nisn='" . $nisn . "'");
      if ($update) {
        echo 'success';
      } else {
        echo 'Gagal update database.';
      }
    } else {
      echo 'Data tidak lengkap.';
    }
    break;
}
