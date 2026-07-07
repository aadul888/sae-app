<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');
  require_once '../../login/user.php';

  // Ambil site_id aktual (tidak hardcode ke 1)
  $real_site_id = 1;
  $_sid_res = $connection->query("SELECT site_id FROM setting ORDER BY site_id ASC LIMIT 1");
  if ($_sid_res && $_sid_res->num_rows > 0) {
    $real_site_id = intval($_sid_res->fetch_assoc()['site_id']);
  }

  if (!function_exists('backup_asset_folder_map')) {
    function backup_asset_folder_map()
    {
      return [
        'agenda' => 'Agenda',
        'avatar' => 'Avatar',
        'berkas' => 'Berkas',
        'capture' => 'Capture',
        'pelanggaran' => 'Pelanggaran',
        'usulan-pip' => 'Usulan PIP'
      ];
    }
  }

  if (!function_exists('backup_content_base_path')) {
    function backup_content_base_path()
    {
      return realpath(__DIR__ . '/../../../content');
    }
  }

  if (!function_exists('backup_normalize_requested_folders')) {
    function backup_normalize_requested_folders($input)
    {
      $allowed = array_keys(backup_asset_folder_map());
      if (is_string($input)) {
        $input = [$input];
      }

      if (!is_array($input)) {
        return [];
      }

      $normalized = [];
      foreach ($input as $folder) {
        $key = trim((string) $folder);
        if ($key !== '' && in_array($key, $allowed, true)) {
          $normalized[] = $key;
        }
      }

      $normalized = array_values(array_unique($normalized));
      return $normalized;
    }
  }

  if (!function_exists('backup_count_folder_payload')) {
    function backup_count_folder_payload($folder)
    {
      $base = backup_content_base_path();
      if ($base === false) {
        return ['file_count' => 0, 'total_size' => 0];
      }

      $target = realpath($base . DIRECTORY_SEPARATOR . $folder);
      if ($target === false || strpos($target, $base) !== 0 || !is_dir($target)) {
        return ['file_count' => 0, 'total_size' => 0];
      }

      $count = 0;
      $size = 0;
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
      );

      foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
          continue;
        }
        $count++;
        $size += (int) $fileInfo->getSize();
      }

      return ['file_count' => $count, 'total_size' => $size];
    }
  }

  if (!function_exists('backup_temp_dir')) {
    function backup_temp_dir()
    {
      $dir = __DIR__ . '/backup-temp';
      if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
      }

      if (is_dir($dir)) {
        foreach (glob($dir . '/*.zip') as $file) {
          if (@filemtime($file) < (time() - 86400)) {
            @unlink($file);
          }
        }
      }

      return $dir;
    }
  }

  if (!function_exists('backup_add_folder_to_zip')) {
    function backup_add_folder_to_zip($zip, $folder)
    {
      $base = backup_content_base_path();
      if ($base === false) {
        return ['added' => 0];
      }

      $target = realpath($base . DIRECTORY_SEPARATOR . $folder);
      if ($target === false || strpos($target, $base) !== 0 || !is_dir($target)) {
        return ['added' => 0];
      }

      $added = 0;
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
      );

      foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
          continue;
        }

        $absolutePath = $fileInfo->getPathname();
        $relativePath = 'content/' . $folder . '/' . str_replace('\\', '/', substr($absolutePath, strlen($target) + 1));

        if (class_exists('ZipArchive') && $zip instanceof ZipArchive) {
          $zip->addFile($absolutePath, $relativePath);
        } elseif (method_exists($zip, 'addFileFromPath')) {
          $zip->addFileFromPath($relativePath, $absolutePath);
        }

        $added++;
      }

      return ['added' => $added];
    }
  }

  if (!function_exists('backup_open_archive_writer')) {
    function backup_open_archive_writer($filePath, &$writerType, &$streamHandle, &$errorMessage)
    {
      $writerType = '';
      $streamHandle = null;
      $errorMessage = '';

      if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
          $errorMessage = 'Gagal membuka writer ZIP (ZipArchive).';
          return null;
        }
        $writerType = 'ziparchive';
        return $zip;
      }

      $autoloadPath = __DIR__ . '/../../assets/vendor/autoload.php';
      if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
      }

      if (class_exists('ZipStream\\ZipStream') && class_exists('ZipStream\\Option\\Archive')) {
        $streamHandle = @fopen($filePath, 'wb');
        if (!$streamHandle) {
          $errorMessage = 'Tidak dapat menulis file ZIP ke folder backup-temp.';
          return null;
        }

        $options = new \ZipStream\Option\Archive();
        $options->setSendHttpHeaders(false);
        $options->setOutputStream($streamHandle);

        $writerType = 'zipstream';
        return new \ZipStream\ZipStream(null, $options);
      }

      $errorMessage = 'Ekstensi ZipArchive tidak tersedia dan fallback ZipStream tidak ditemukan.';
      return null;
    }
  }

  if (!function_exists('backup_close_archive_writer')) {
    function backup_close_archive_writer($zip, $writerType, $streamHandle = null)
    {
      if ($writerType === 'ziparchive' && $zip) {
        $zip->close();
      } elseif ($writerType === 'zipstream' && $zip) {
        $zip->finish();
        if (is_resource($streamHandle)) {
          fclose($streamHandle);
        }
      }
    }
  }

  if (!function_exists('backup_issue_download_token')) {
    function backup_issue_download_token($filePath, $downloadName)
    {
      if (!isset($_SESSION['backup_file_tokens']) || !is_array($_SESSION['backup_file_tokens'])) {
        $_SESSION['backup_file_tokens'] = [];
      }

      $token = bin2hex(random_bytes(16));
      $_SESSION['backup_file_tokens'][$token] = [
        'path' => $filePath,
        'name' => $downloadName,
        'expires' => time() + 1800
      ];

      return $token;
    }
  }

  if (!function_exists('backup_json_response')) {
    function backup_json_response($payload)
    {
      header('Content-Type: application/json');
      echo json_encode($payload);
      exit;
    }
  }

  switch (@$_GET['action']) {

    /** Setting Logo Web  */
    case 'logo':
      $file_name   = $_FILES['file']['name'];
      $size        = $_FILES['file']['size'];
      $error       = $_FILES['file']['error'];
      $tmpName     = $_FILES['file']['tmp_name'];
      $folder      = '../../../content/';
      $valid       = array('jpg', 'png', 'gif', 'jpeg');
      if (strlen($file_name)) {
        // Ambil ekstensi dari nama file (support nama file dengan titik ganda)
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_ext = '.' . $ext;

        if (in_array($ext, $valid)) {
          if ($size < 500000) {
            // Perintah pengganti nama files
            $site_logo = 'logoweb1' . $file_ext;
            $pathFile       = $folder . $site_logo;

            $query = "SELECT site_logo FROM setting WHERE site_id=$real_site_id";
            $result = $connection->query($query);
            $rows = $result->fetch_assoc();
            $logo = $rows['site_logo'];
            if (file_exists("../../../content/$logo")) {
              unlink("../../../content/$logo");
            }
            $update = "UPDATE setting SET site_logo='$site_logo' WHERE site_id=$real_site_id";
            if ($connection->query($update) === false) {
              echo 'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
              die($connection->error . __LINE__);
            } else {
              echo 'success';
              move_uploaded_file($tmpName, $pathFile);
            }
          } else { // Jika Gambar melebihi size 
            echo 'File terlalu besar maksimal files 5MB.!';
          }
        } else {
          echo 'File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!';
        }
      }
      break;

    /** Hapus Modul */
    case 'modul-delete':
      $modul_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
      if ($modul_id <= 0) {
        echo 'ID modul tidak valid';
        exit;
      }
      // Cek apakah modul digunakan di tabel role (hak akses)
      $cek = $connection->prepare("SELECT COUNT(*) FROM role WHERE modul_id = ?");
      $cek->bind_param('i', $modul_id);
      $cek->execute();
      $cek->bind_result($count);
      $cek->fetch();
      $cek->close();
      if ($count > 0) {
        echo 'Modul tidak dapat dihapus karena masih digunakan di hak akses.';
        exit;
      }
      // Lakukan penghapusan
      $del = $connection->prepare("DELETE FROM modul WHERE modul_id = ?");
      $del->bind_param('i', $modul_id);
      if ($del->execute()) {
        echo 'success';
      } else {
        echo 'Gagal menghapus modul';
      }
      $del->close();
      break;

    /** Setting Logo Web 2  */
    case 'logo2':
      $file_name   = $_FILES['file2']['name'];
      $size        = $_FILES['file2']['size'];
      $error       = $_FILES['file2']['error'];
      $tmpName     = $_FILES['file2']['tmp_name'];
      $folder      = '../../../content/';
      $valid       = array('jpg', 'png', 'gif', 'jpeg');
      if (strlen($file_name)) {
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_ext = '.' . $ext;
        if (in_array($ext, $valid)) {
          if ($size < 500000) {
            $site_logo2 = 'logoweb2' . $file_ext;
            $pathFile = $folder . $site_logo2;
            $query = "SELECT site_logo2 FROM setting WHERE site_id=$real_site_id";
            $result = $connection->query($query);
            $rows = $result->fetch_assoc();
            $logo2 = $rows['site_logo2'];
            if (file_exists("../../../content/$logo2")) {
              unlink("../../../content/$logo2");
            }
            $update = "UPDATE setting SET site_logo2='$site_logo2' WHERE site_id=$real_site_id";
            if ($connection->query($update) === false) {
              echo 'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
              die($connection->error . __LINE__);
            } else {
              echo 'success';
              move_uploaded_file($tmpName, $pathFile);
            }
          } else {
            echo 'File terlalu besar maksimal files 5MB.!';
          }
        } else {
          echo 'File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!';
        }
      }
      break;

    /** Setting Favicon */
    case 'favicon':
      $file_name   = $_FILES['file']['name'];
      $size        = $_FILES['file']['size'];
      $error       = $_FILES['file']['error'];
      $tmpName     = $_FILES['file']['tmp_name'];
      $folder      = '../../../content/';
      $valid       = array('jpg', 'png', 'gif', 'jpeg');
      if (strlen($file_name)) {
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_ext = '.' . $ext;

        if (in_array($ext, $valid)) {
          if ($size < 500000) {
            // Perintah pengganti nama files
            $site_logo = 'favicon' . $file_ext . '';
            $pathFile       = $folder . $site_logo;

            $query = "SELECT site_favicon FROM setting WHERE site_id=$real_site_id";
            $result = $connection->query($query);
            $rows = $result->fetch_assoc();
            $favicon = $rows['site_favicon'];
            if (file_exists("../../../content/$favicon")) {
              unlink("../../../content/$favicon");
            }
            $update = "UPDATE setting SET site_favicon='$site_logo' WHERE site_id=$real_site_id";
            if ($connection->query($update) === false) {
              echo 'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
              die($connection->error . __LINE__);
            } else {
              echo 'success';
              move_uploaded_file($tmpName, $pathFile);
            }
          } else { // Jika Gambar melebihi size 
            echo 'File terlalu besar maksimal files 5MB.!';
          }
        } else {
          echo 'File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!';
        }
      }
      break;

    /** Setting Kop Sekolah  */
    case 'kop-sekolah':
      $file_name   = $_FILES['file_kop']['name'];
      $size        = $_FILES['file_kop']['size'];
      $error       = $_FILES['file_kop']['error'];
      $tmpName     = $_FILES['file_kop']['tmp_name'];
      $folder      = '../../../content/';
      $valid       = array('jpg', 'png', 'gif', 'jpeg');
      if (strlen($file_name)) {
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_ext = '.' . $ext;
        if (in_array($ext, $valid)) {
          // Allow up to 2MB to match frontend guidance
          if ($size <= 2 * 1024 * 1024) {
            $site_kop = 'kopsekolah' . $file_ext;
            $pathFile = $folder . $site_kop;

            $query = "SELECT site_kop FROM setting WHERE site_id=$real_site_id";
            $result = $connection->query($query);
            $rows = $result->fetch_assoc();
            $kop = $rows['site_kop'];
            if (!empty($kop) && file_exists("../../../content/$kop")) {
              unlink("../../../content/$kop");
            }

            // First move the uploaded file to the destination. Only update DB if the move succeeds.
            if (move_uploaded_file($tmpName, $pathFile)) {
              $update = "UPDATE setting SET site_kop='$site_kop' WHERE site_id=$real_site_id";
              if ($connection->query($update) === false) {
                // rollback the file if DB update fails
                if (file_exists($pathFile)) {
                  unlink($pathFile);
                }
                echo 'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
                die($connection->error . __LINE__);
              } else {
                echo 'success';
              }
            } else {
              echo 'Gagal memindahkan file ke folder tujuan. Pastikan folder content bisa ditulis.';
            }
          } else {
            echo 'File terlalu besar maksimal files 2MB.!';
          }
        } else {
          echo 'File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!';
        }
      }
      break;

    /** Setting web  */
    case 'setting-web':
      $error = array();
      if (empty($_POST['site_name'])) {
        $error[] = 'Nama web tidak boleh kosong';
      } else {
        $site_name    = htmlspecialchars(ucfirst($_POST['site_name']));
      }

      if (empty($_POST['site_owner'])) {
        $error[] = 'Pemilik web tidak boleh kosong';
      } else {
        $site_owner = anti_injection($_POST['site_owner']);
      }

      if (empty($_POST['site_phone'])) {
        $error[] = 'No. Telp tidak boleh kosong';
      } else {
        $site_phone = anti_injection($_POST['site_phone']);
      }


      if (empty($_POST['site_email'])) {
        $error[] = 'Email tidak boleh kosong';
      } else {
        if (!filter_var($_POST['site_email'], FILTER_VALIDATE_EMAIL)) {
          $error[] = "Email yang Anda masukan tidak valid";
        } else {
          $site_email = htmlentities(strip_tags($_POST['site_email']));
        }
      }

      if (empty($_POST['site_address'])) {
        $error[] = 'Alamat tidak boleh kosong';
      } else {
        $site_address = htmlentities(htmlspecialchars($_POST['site_address']));
      }

      if (empty($_POST['site_url'])) {
        $error[] = 'Domain/Url Web tidak boleh kosong';
      } else {
        $site_url = htmlentities(strip_tags($_POST['site_url']));
      }

      if (empty($error)) {
        $update = "UPDATE setting SET site_name='$site_name',
                site_phone='$site_phone',
                site_address='$site_address',
                site_owner='$site_owner',
                site_url='$site_url',
                site_email='$site_email' WHERE site_id=$real_site_id";
        if ($connection->query($update) === false) {
          echo 'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
          die($connection->error . __LINE__);
        } else {
          echo 'success';
        }
      } else {
        foreach ($error as $key => $values) {
          echo "$values\n";
        }
      }

      // Prevent fall-through to other cases (was causing concatenated outputs)
      break;

    /** Proses Simpan/Edit Modul */
    case 'modul-save':
      $modul_id = isset($_POST['modul_id']) ? intval($_POST['modul_id']) : 0;
      $modul_nama = isset($_POST['modul_nama']) ? htmlentities(trim($_POST['modul_nama'])) : '';
      if (empty($modul_nama)) {
        echo 'Nama modul tidak boleh kosong';
        exit;
      }
      if ($modul_id > 0) {
        // Edit modul
        $update = "UPDATE modul SET modul_nama='$modul_nama' WHERE modul_id='$modul_id'";
        if ($connection->query($update) === false) {
          echo 'Gagal mengedit modul';
        } else {
          echo 'success';
        }
      } else {
        // Tambah modul
        $insert = "INSERT INTO modul (modul_nama) VALUES ('$modul_nama')";
        if ($connection->query($insert) === false) {
          echo 'Gagal menambah modul';
        } else {
          echo 'success';
        }
      }
      break;

      /** Setting Absensi */
      break;
    case 'setting-absensi':
      $error = array();
      if (empty($_POST['timezone'])) {
        $error[] = 'Timezone tidak boleh kosong';
      } else {
        $timezone  = htmlspecialchars(ucfirst($_POST['timezone']));
      }


      if (empty($error)) {
        $update = "UPDATE setting_absen SET timezone='$timezone' WHERE setting_absen_id=1";
        if ($connection->query($update) === false) {
          echo 'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
          die($connection->error . __LINE__);
        } else {
          echo 'success';
        }
      } else {
        foreach ($error as $key => $values) {
          echo "$values\n";
        }
      }


      /** Setting Server */
      break;
    case 'setting-server':
      $error = array();

      if (empty($_POST['gmail_host'])) {
        $error[] = 'Host Email tidak boleh kosong';
      } else {
        $gmail_host = htmlentities(strip_tags($_POST['gmail_host']));
      }

      if (empty($_POST['gmail_username'])) {
        $error[] = 'Username/Email tidak boleh kosong';
      } else {
        if (!filter_var($_POST['gmail_username'], FILTER_VALIDATE_EMAIL)) {
          $error[] = "Email yang Anda masukan tidak valid";
        } else {
          $gmail_username = htmlentities(strip_tags($_POST['gmail_username']));
        }
      }

      if (empty($_POST['gmail_password'])) {
        $error[] = 'No. Telp tidak boleh kosong';
      } else {
        $gmail_password = anti_injection($_POST['gmail_password']);
      }

      if (empty($_POST['gmail_port'])) {
        $error[] = 'Alamat tidak boleh kosong';
      } else {
        $gmail_port = htmlentities(htmlspecialchars($_POST['gmail_port']));
      }

      if (empty($_POST['google_client_id'])) {
        $error[] = 'Client ID tidak boleh kosong';
      } else {
        $google_client_id = htmlentities(strip_tags($_POST['google_client_id']));
      }

      if (empty($_POST['google_client_secret'])) {
        $error[] = 'Secret tidak boleh kosong';
      } else {
        $google_client_secret = htmlentities(strip_tags($_POST['google_client_secret']));
      }

      if (empty($_POST['gmail_active'])) {
        $gmail_active = 'N';
      } else {
        $gmail_active = 'Y';
      }

      if (empty($_POST['google_client_active'])) {
        $google_client_active = 'N';
      } else {
        $google_client_active = 'Y';
      }
      if (empty($error)) {
        $update = "UPDATE setting SET gmail_host='$gmail_host',
                gmail_username='$gmail_username',
                gmail_password='$gmail_password',
                gmail_port='$gmail_port',
                gmail_active='$gmail_active',
                google_client_id='$google_client_id',
                google_client_secret='$google_client_secret',
                google_client_active='$google_client_active' WHERE site_id=$real_site_id";
        if ($connection->query($update) === false) {
          echo 'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
          die($connection->error . __LINE__);
        } else {
          echo 'success';
        }
      } else {
        foreach ($error as $key => $values) {
          echo "$values\n";
        }
      }



      /** Backup Database */
      break;
    case 'backup-assets-scan':
      $requestedFolder = isset($_POST['folder']) ? trim((string) $_POST['folder']) : '';
      $folders = backup_normalize_requested_folders($requestedFolder);
      $labels = backup_asset_folder_map();

      if (empty($folders)) {
        backup_json_response([
          'status' => 'error',
          'message' => 'Folder backup tidak valid.'
        ]);
      }

      $byFolder = [];
      $totalFiles = 0;
      $totalSize = 0;
      foreach ($folders as $folder) {
        $stats = backup_count_folder_payload($folder);
        $byFolder[$folder] = [
          'label' => $labels[$folder],
          'file_count' => (int) $stats['file_count'],
          'total_size' => (int) $stats['total_size']
        ];
        $totalFiles += (int) $stats['file_count'];
        $totalSize += (int) $stats['total_size'];
      }

      backup_json_response([
        'status' => 'success',
        'message' => 'Scan aset backup selesai.',
        'data' => [
          'folders' => $folders,
          'by_folder' => $byFolder,
          'total_files' => $totalFiles,
          'total_size' => $totalSize
        ]
      ]);
      break;

    case 'backup-assets-create':
      $requestedFolder = isset($_POST['folder']) ? trim((string) $_POST['folder']) : '';
      $folders = backup_normalize_requested_folders($requestedFolder);
      $labels = backup_asset_folder_map();

      if (empty($folders)) {
        backup_json_response([
          'status' => 'error',
          'message' => 'Folder backup tidak valid.'
        ]);
      }

      $folder = $folders[0];
      $result = backup_count_folder_payload($folder);
      if ((int) $result['file_count'] < 1) {
        backup_json_response([
          'status' => 'error',
          'message' => 'Tidak ada file di folder aset terpilih.'
        ]);
      }

      backup_json_response([
        'status' => 'success',
        'message' => 'Backup folder ' . $labels[$folder] . ' siap diunduh.',
        'data' => [
          'mode' => 'per-folder',
          'folder' => $folder,
          'label' => $labels[$folder],
          'file_count' => (int) $result['file_count'],
          'download_url' => './mod/pengaturan/proses.php?action=backup-assets-download&folder=' . urlencode($folder)
        ]
      ]);
      break;

    case 'backup-assets-download':
      $folderParam = isset($_GET['folder']) ? trim((string) $_GET['folder']) : '';
      if ($folderParam !== '') {
        $folders = backup_normalize_requested_folders($folderParam);
        $labels = backup_asset_folder_map();

        if (empty($folders)) {
          header('HTTP/1.1 400 Bad Request');
          echo 'Folder backup tidak valid.';
          exit;
        }

        $folder = $folders[0];
        $stats = backup_count_folder_payload($folder);
        if ((int) $stats['file_count'] < 1) {
          header('HTTP/1.1 404 Not Found');
          echo 'Tidak ada file di folder aset terpilih.';
          exit;
        }

        $autoloadPath = __DIR__ . '/../../assets/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
          require_once $autoloadPath;
        }

        if (!class_exists('ZipStream\\ZipStream') || !class_exists('ZipStream\\Option\\Archive')) {
          header('HTTP/1.1 500 Internal Server Error');
          echo 'Library ZIP streaming tidak tersedia.';
          exit;
        }

        $safeFolder = preg_replace('/[^a-zA-Z0-9\-_]/', '', $folder);
        $downloadName = 'backup_' . $safeFolder . '_' . date('Ymd_His') . '.zip';

        $options = new \ZipStream\Option\Archive();
        $options->setSendHttpHeaders(true);
        $options->setContentType('application/zip');
        $options->setContentDisposition('attachment');

        $zip = new \ZipStream\ZipStream($downloadName, $options);
        backup_add_folder_to_zip($zip, $folder);
        $zip->finish();
        exit;
      }

      $token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
      if ($token === '' || !isset($_SESSION['backup_file_tokens'][$token])) {
        header('HTTP/1.1 404 Not Found');
        echo 'File backup tidak ditemukan atau token sudah kadaluarsa.';
        exit;
      }

      $meta = $_SESSION['backup_file_tokens'][$token];
      $path = isset($meta['path']) ? (string) $meta['path'] : '';
      $name = isset($meta['name']) ? (string) $meta['name'] : ('backup_' . date('Ymd_His') . '.zip');
      $expires = isset($meta['expires']) ? (int) $meta['expires'] : 0;

      if ($expires > 0 && time() > $expires) {
        unset($_SESSION['backup_file_tokens'][$token]);
        if ($path !== '' && file_exists($path)) {
          @unlink($path);
        }
        header('HTTP/1.1 410 Gone');
        echo 'File backup telah kadaluarsa.';
        exit;
      }

      if ($path === '' || !file_exists($path)) {
        unset($_SESSION['backup_file_tokens'][$token]);
        header('HTTP/1.1 404 Not Found');
        echo 'File backup tidak tersedia.';
        exit;
      }

      header('Content-Type: application/zip');
      header('Content-Disposition: attachment; filename="' . basename($name) . '"');
      header('Content-Length: ' . filesize($path));
      header('Cache-Control: no-store, no-cache, must-revalidate');
      header('Pragma: no-cache');
      readfile($path);

      @unlink($path);
      unset($_SESSION['backup_file_tokens'][$token]);
      exit;

    case 'backup-database':
      $host = DB_HOST;
      $root = DB_USER;
      $pass = DB_PASSWD;
      $db_name = DB_NAME;
      $mysqli = new mysqli($host, $root, $pass, $db_name);
      $mysqli->select_db($db_name);
      $mysqli->query("SET NAMES 'utf8'");
      //get table list
      $queryTables    = $mysqli->query('SHOW TABLES');
      while ($row = $queryTables->fetch_row()) {
        $target_tables[] = $row[0];
      }
      //get table structure
      foreach ($target_tables as $table) {
        $result = $mysqli->query('SELECT * FROM ' . $table);
        $fields_amount = $result->field_count;
        $rows_num = $mysqli->affected_rows;
        $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
        $TableMLine = $res->fetch_row();
        $content = (!isset($content) ?  '' : $content) . "\n\n" . $TableMLine[1] . ";\n";
        for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
          while ($row = $result->fetch_row()) { //when started (and every after 100 command cycle):
            if ($st_counter % 100 == 0 || $st_counter == 0) {
              $content .= "\nINSERT INTO " . $table . " VALUES";
            }
            $content .= "\n(";
            for ($j = 0; $j < $fields_amount; $j++) {
              $row[$j] = str_replace(array("\r\n\r\n", "\n\r\n", "\r\n", "\n\n", "\n"), array("\\r\\n", "\\r\\n", "\\r\\n", "\\r\\n", "\\r\\n"), addslashes($row[$j]));
              if (isset($row[$j])) {
                $content .= '"' . $row[$j] . '"';
              } else {
                $content .= '""';
              }
              if ($j < ($fields_amount - 1)) {
                $content .= ',';
              }
            }
            $content .= ")";
            //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
            if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
              $content .= ";";
            } else {
              $content .= ",";
            }
            $st_counter = $st_counter + 1;
          }
        }
      }
      // save as .sql file
      //give additional description
      $content_ = "\n-- Database Backup --\n";
      $content_ .= "-- Ver. : 1.0.1\n";
      $content_ .= "-- Host : 127.0.0.1\n";
      $content_ .= "-- Generating Time : " . date("M d") . ", " . date("Y") . " at " . date("H:i:s:") . date("A") . "\n";
      $content_ .= $content;
      //save the file
      $backup_file_name = $db_name . " " . date("Y-m-d H-i-s") . ".sql";
      $fp = fopen($backup_file_name, 'w+');
      $result = fwrite($fp, $content_);
      fclose($fp);
      //download file directly from browser
      $file_path = $backup_file_name;
      if (!empty($file_path) && file_exists($file_path)) {
        header("Pragma:public");
        header("Expired:0");
        header("Cache-Control:must-revalidate");
        header("Content-Control:public");
        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition:attachment; filename=\"" . basename($file_path) . "\"");
        header("Content-Transfer-Encoding:binary");
        header("Content-Length:" . filesize($file_path));
        flush();
        readfile($file_path);
        unlink($file_path);
        exit();
      }


      /** Backup Homepage */
      break;
    case 'backup-homepage':
      $file_folder = "."; // folder to load files
      $zipname = 'adcs.zip';
      $zip = new ZipArchive;
      $zip->open($zipname, ZipArchive::CREATE);
      if ($handle = opendir('.')) {
        while (false !== ($entry = readdir($handle))) {
          if ($entry != "." && $entry != ".." && !strstr($entry, '.php')) {
            $zip->addFile($entry);
          }
        }
        closedir($handle);
      }

      $zip->close();

      header('Content-Type: application/zip');
      header("Content-Disposition: attachment; filename='adcs.zip'");
      header('Content-Length: ' . filesize($zipname));
      header("Location: adcs.zip");

      break;

    /** Proses Simpan/Edit Tahun Pelajaran */
    case 'tapel-save':
      $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
      $tahun = isset($_POST['tahun']) ? htmlentities(trim($_POST['tahun'])) : '';
      $semester = isset($_POST['semester']) ? htmlentities(trim($_POST['semester'])) : '';
      $aktif = isset($_POST['aktif']) ? htmlentities(trim($_POST['aktif'])) : 'N';
      if (empty($tahun) || empty($semester)) {
        echo 'Tahun dan semester tidak boleh kosong';
        exit;
      }
      if ($aktif == 'Y') {
        // Nonaktifkan semua tahun pelajaran lain
        $connection->query("UPDATE tahun_pelajaran SET aktif='N' WHERE 1");
      }
      if ($id > 0) {
        // Edit tahun pelajaran
        $update = "UPDATE tahun_pelajaran SET tahun='$tahun', semester='$semester', aktif='$aktif' WHERE id='$id'";
        if ($connection->query($update) === false) {
          echo 'Gagal mengedit tahun pelajaran';
        } else {
          echo 'success';
        }
      } else {
        // Tambah tahun pelajaran
        $insert = "INSERT INTO tahun_pelajaran (tahun, semester, aktif) VALUES ('$tahun', '$semester', '$aktif')";
        if ($connection->query($insert) === false) {
          echo 'Gagal menambah tahun pelajaran';
        } else {
          echo 'success';
        }
      }
      break;

    /** Proses Update Status Sistem (Buka/Tutup) dengan update kolom aktif di tahun_pelajaran */
    case 'system-status':
      $system_status = isset($_POST['system_status']) ? trim($_POST['system_status']) : 'open';
      $tapel_id = isset($_POST['tapel_id']) ? intval($_POST['tapel_id']) : 0;

      if ($system_status !== 'open' && $system_status !== 'closed') {
        echo 'Status sistem tidak valid';
        break;
      }

      if ($system_status === 'closed') {
        $update = $connection->query("UPDATE tahun_pelajaran SET aktif='N'");
        if ($update === false) {
          echo 'Gagal mengubah status sistem';
          break;
        }
        echo 'success';
        break;
      }

      // mode open: wajib ada data tahun pelajaran yang dipilih / tersedia
      if ($tapel_id <= 0) {
        $result = $connection->query("SELECT id FROM tahun_pelajaran ORDER BY id DESC LIMIT 1");
        if ($result && ($row = $result->fetch_assoc())) {
          $tapel_id = intval($row['id']);
        }
      }

      if ($tapel_id <= 0) {
        echo 'Tidak ada data tahun pelajaran';
        break;
      }

      // Pastikan tapel yang dipilih memang ada
      $cek = $connection->query("SELECT id FROM tahun_pelajaran WHERE id='$tapel_id' LIMIT 1");
      if (!$cek || $cek->num_rows === 0) {
        echo 'Tahun pelajaran tidak ditemukan';
        break;
      }

      if ($connection->query("UPDATE tahun_pelajaran SET aktif='N'") === false) {
        echo 'Gagal mengubah status sistem';
        break;
      }
      if ($connection->query("UPDATE tahun_pelajaran SET aktif='Y' WHERE id='$tapel_id'") === false) {
        echo 'Gagal mengubah status sistem';
        break;
      }

      echo 'success';
      break;

    /** Setting WhatsApp Gateway */
    case 'whatsapp-gateway':
      $error = array();
      
      if (empty($_POST['api_url'])) {

        $error[] = 'API URL tidak boleh kosong';
      } else {
        $api_url = htmlspecialchars($_POST['api_url']);
      }

      if (empty($_POST['api_key'])) {
        $error[] = 'API Key tidak boleh kosong';
      } else {
        $api_key = htmlspecialchars($_POST['api_key']);
      }

      $status = isset($_POST['status']) ? 'Y' : 'N';

      if (empty($error)) {
        // Cek apakah data sudah ada
        $check = $connection->query("SELECT id FROM whatsapp_config WHERE id = 1");
        
        if ($check && $check->num_rows > 0) {
          // Update existing record
          $update = "UPDATE whatsapp_config SET 
                     api_url='$api_url',
                     api_key='$api_key',
                     status='$status',
                     updated_at=NOW()
                     WHERE id=1";
          
          if ($connection->query($update)) {
            echo 'success';
          } else {
            echo 'Gagal mengupdate konfigurasi WhatsApp Gateway';
          }
        } else {
          // Insert new record
          $insert = "INSERT INTO whatsapp_config (api_url, api_key, status, created_at, updated_at) 
                     VALUES ('$api_url', '$api_key', '$status', NOW(), NOW())";
          
          if ($connection->query($insert)) {
            echo 'success';
          } else {
            echo 'Gagal menyimpan konfigurasi WhatsApp Gateway';
          }
        }
      } else {
        foreach ($error as $key => $values) {
          echo $values . '<br>';
        }
      }
      break;

    /** Setting Maintenance */
    case 'maintenance-setting':
      $maintenance_status = isset($_POST['maintenance_status']) ? trim($_POST['maintenance_status']) : 'open';
      if ($maintenance_status !== 'open' && $maintenance_status !== 'closed') {
        echo 'Status maintenance tidak valid';
        break;
      }

      $update = $connection->query("UPDATE setting SET maintenance_status='" . $connection->real_escape_string($maintenance_status) . "' WHERE site_id=$real_site_id");
      if ($update === false) {
        echo 'Gagal menyimpan pengaturan maintenance';
      } else {
        echo 'success';
      }
      break;

    /** Test WhatsApp Gateway */
    case 'whatsapp-test':
      $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
      $message = isset($_POST['message']) ? $_POST['message'] : 'Test pesan dari sistem WhatsApp Gateway';
      
      if (empty($phone)) {
        echo 'Nomor telepon tidak boleh kosong';
        exit;
      }

      // Ambil konfigurasi WhatsApp
      $result_wa = $connection->query("SELECT * FROM whatsapp_config WHERE id = 1");
      if (!$result_wa || $result_wa->num_rows == 0) {
        echo 'Konfigurasi WhatsApp Gateway belum diatur';
        exit;
      }
      
      $wa_config = $result_wa->fetch_assoc();
      
      if ($wa_config['status'] != 'Y') {
        echo 'WhatsApp Gateway sedang tidak aktif';
        exit;
      }

      // Format nomor telepon
      $phone = preg_replace('/[^0-9]/', '', $phone);
      if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
      }
      
      // Log test pesan
      $stmt = $connection->prepare("INSERT INTO whatsapp_logs (phone_number, message, activity_type, status) VALUES (?, ?, 'test_message', 'sent')");
      $stmt->bind_param('ss', $phone, $message);
      $stmt->execute();
      $stmt->close();
      
      echo 'success';
      break;
  }
}
