<?php
session_start();
require_once '../../../library/config.php';
include('../../../library/function.php');

$modul_id = 133;
include __DIR__ . '/../check_role.php';
if (!$has_access || $data_role['modifikasi'] != 'Y') {
  echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
  exit;
}

switch (@$_GET['action']) {
  case 'save_license':
    $license_key = $_POST['license_key'] ?? '';
    $license_key = $connection->real_escape_string($license_key);

    $q = $connection->query("UPDATE setting SET license_key='$license_key' WHERE site_id=1");
    if (!$q) {
      echo 'Gagal menyimpan lisensi: ' . $connection->error;
      exit;
    }

    if (!empty($license_key)) {
      $result = indukApiCall('license/validate', 'POST', ['license_key' => $license_key]);
      $_SESSION['license_validation'] = $result;
    } else {
      $_SESSION['license_validation'] = ['valid' => false, 'message' => 'License key dikosongkan.'];
    }

    header('location:../../lisensi_pembaruan?status=ok');
    exit;
    break;

  case 'validate_license':
    $q_set = $connection->query("SELECT license_key FROM setting LIMIT 1");
    $row = $q_set ? $q_set->fetch_assoc() : [];
    $license_key = $row['license_key'] ?? '';

    if (empty($license_key)) {
      $_SESSION['license_validation'] = ['valid' => false, 'message' => 'Belum ada license key.'];
      $connection->query("UPDATE setting SET license_status='unverified', license_school_name=NULL, license_npsn=NULL, license_expired_at=NULL WHERE site_id=1");
    } else {
      $result = indukApiCall('license/validate', 'POST', ['license_key' => $license_key]);
      $_SESSION['license_validation'] = $result;

      if (isset($result['valid']) && $result['valid']) {
        $status = $connection->real_escape_string($result['status']);
        $school = $connection->real_escape_string($result['school_name'] ?? '');
        $npsn = $connection->real_escape_string($result['npsn'] ?? '');
        $expired = !empty($result['expired_at']) ? "'" . $connection->real_escape_string($result['expired_at']) . "'" : "NULL";
        $connection->query("UPDATE setting SET license_status='$status', license_school_name='$school', license_npsn='$npsn', license_expired_at=$expired WHERE site_id=1");
      } else {
        $status = $result['status'] ?? 'invalid';
        $connection->query("UPDATE setting SET license_status='$status' WHERE site_id=1");
      }
    }

    header('location:../../lisensi_pembaruan');
    exit;
    break;

  case 'checkin':
    $q_set = $connection->query("SELECT license_key, app_version FROM setting LIMIT 1");
    $row = $q_set ? $q_set->fetch_assoc() : [];
    $license_key = $row['license_key'] ?? '';
    $app_version = $row['app_version'] ?? (defined('SAE_VERSION') ? SAE_VERSION : 'v5.0');

    $result = indukApiCall('sync/checkin', 'POST', [
      'license_key' => $license_key,
      'app_version' => $app_version,
      'php_version' => phpversion(),
      'db_version' => $connection->server_info ?? '',
    ]);

    if ($result['success']) {
      $connection->query("UPDATE setting SET last_sync_at=NOW() WHERE site_id=1");
    }

    echo json_encode([
      'success' => $result['success'] ?? false,
      'message' => $result['message'] ?? 'Check-in selesai',
      'update_available' => !empty($result['update_available']),
      'latest_version' => $result['latest_version'] ?? '',
    ]);
    break;

  case 'check_update':
    $result = indukApiCall('updates/latest');
    $current = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';
    $update_available = false;
    $message = 'Aplikasi sudah menggunakan versi terbaru.';
    $latest_version = $current;

    if ($result['success'] && !empty($result['available'])) {
      $latest_version = $result['version'];
      if (version_compare($current, $result['version'], '<')) {
        $update_available = true;
        $message = 'Pembaruan tersedia: ' . $result['version'];
      }
    } elseif (!$result['success']) {
      $message = $result['message'] ?? 'Gagal menghubungi server induk.';
    }

    echo json_encode([
      'success' => true,
      'update_available' => $update_available,
      'current_version' => $current,
      'latest_version' => $latest_version,
      'message' => $message,
      'api_result' => $result,
    ]);
    break;

  case 'deploy':
    header('Content-Type: application/json');
    $csrf = $_GET['csrf'] ?? '';
    if ($csrf !== $_SESSION['csrf_token']) {
      echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
      exit;
    }

    $git_dir = realpath(__DIR__ . '/../../../');
    $output = [];
    $return_var = -1;
    $deployed = false;
    $err = '';

    // Priority 1: git pull
    if ($git_dir) {
      chdir($git_dir);
      exec('git pull origin main 2>&1', $output, $return_var);
      if ($return_var === 0) {
        $deployed = true;
      } else {
        $err = implode("\n", $output);
      }
    }

    // Priority 2: fallback download zip from GitHub (hosting tanpa git)
    if (!$deployed) {
      $repo = 'aadul888/sae-app';
      $token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : (defined('SAE_API_KEY') ? SAE_API_KEY : '');
      $branch = 'main';
      $zip_url = "https://api.github.com/repos/$repo/zipball/$branch";

      $ch = curl_init();
      curl_setopt_array($ch, [
        CURLOPT_URL => $zip_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => [
          'Accept: application/vnd.github+json',
          'User-Agent: SAE-Deploy/1.0',
          $token ? "Authorization: Bearer $token" : '',
        ],
      ]);
      $zip_data = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curl_err = curl_error($ch);
      curl_close($ch);

      if ($curl_err || $http_code >= 400) {
        $err = 'Git tidak tersedia dan download ZIP gagal: ' . ($curl_err ?: "HTTP $http_code");
      } else {
        $tmp_zip = sys_get_temp_dir() . '/sae-deploy-' . uniqid() . '.zip';
        file_put_contents($tmp_zip, $zip_data);

        $zip = new ZipArchive;
        if ($zip->open($tmp_zip) === true) {
          // GitHub zip has a root folder like aadul888-sae-app-<sha>/ — extract to temp
          $tmp_extract = sys_get_temp_dir() . '/sae-extract-' . uniqid();
          $zip->extractTo($tmp_extract);
          $zip->close();

          // Find the inner folder
          $items = scandir($tmp_extract);
          $inner = null;
          foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir("$tmp_extract/$item")) {
              $inner = "$tmp_extract/$item";
              break;
            }
          }

          if ($inner) {
            $target = realpath(__DIR__ . '/../../../');
            // Use recursive copy
            $iterator = new RecursiveIteratorIterator(
              new RecursiveDirectoryIterator($inner, RecursiveDirectoryIterator::SKIP_DOTS),
              RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
              $dest = $target . '/' . $iterator->getSubPathname();
              if ($item->isDir()) {
                if (!is_dir($dest)) mkdir($dest, 0755, true);
              } else {
                copy($item, $dest);
              }
            }
            $deployed = true;

            // Bersihkan temp
            $rmdir = function($dir) use (&$rmdir) {
              foreach (scandir($dir) as $f) {
                if ($f === '.' || $f === '..') continue;
                $p = "$dir/$f";
                is_dir($p) ? $rmdir($p) : unlink($p);
              }
              rmdir($dir);
            };
            $rmdir($tmp_extract);
          }
          unlink($tmp_zip);
        } else {
          $err = 'Gagal membuka file ZIP.';
        }
      }
    }

    if ($deployed) {
      // Jalankan migrasi database yang tertunda
      require_once __DIR__ . '/../../../library/migrate.php';
      $mig_result = run_pending_migrations($connection);

      // Catat commit log
      $log_file = __DIR__ . '/../../../library/commit_logger.php';
      if (file_exists($log_file)) {
        require_once $log_file;
        save_commit_log($connection, 3);
      }

      $msg = 'Pembaruan berhasil diterapkan.';
      if ($mig_result['ran'] > 0) {
        $msg .= ' ' . $mig_result['ran'] . ' migrasi database dijalankan.';
      }
      if (!$mig_result['success']) {
        $msg .= ' Ada error migrasi: ' . implode('; ', $mig_result['errors']);
      }

      $_SESSION['deploy_result'] = ['success' => true, 'message' => $msg];
      echo json_encode(['success' => true, 'message' => $msg]);
    } else {
      if (empty($err)) $err = 'Gagal memperbarui aplikasi.';
      $_SESSION['deploy_result'] = ['success' => false, 'error' => htmlspecialchars($err)];
      echo json_encode(['success' => false, 'message' => $err]);
    }
    exit;
    break;

  default:
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
    break;
}
