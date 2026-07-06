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

    // Try git pull (works on local + hosting with git)
    $git_dir = realpath(__DIR__ . '/../../../');
    $output = [];
    $return_var = -1;
    if ($git_dir) {
      chdir($git_dir);
      exec('git pull origin main 2>&1', $output, $return_var);
    }

    if ($return_var === 0) {
      $_SESSION['deploy_result'] = ['success' => true, 'message' => 'Pembaruan berhasil diterapkan. Aplikasi sudah diperbarui ke versi terbaru.'];
      echo json_encode(['success' => true, 'message' => 'Pembaruan berhasil diterapkan.']);
    } else {
      $err = $return_var === -1 ? 'Direktori git tidak ditemukan.' : implode("\n", $output);
      $_SESSION['deploy_result'] = ['success' => false, 'error' => htmlspecialchars($err)];
      echo json_encode(['success' => false, 'message' => $err]);
    }
    exit;
    break;

  default:
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
    break;
}
