<?php
session_start();
require_once '../../../library/config.php';
require_once('../../../library/function.php');

$modul_id = 133;
include __DIR__ . '/../check_role.php';
if (!$has_access || $data_role['modifikasi'] != 'Y') {
  echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
  exit;
}

switch (@$_GET['action']) {
  case 'save_license':
    $license_key = $_POST['license_key'] ?? '';

    $stmt = $connection->prepare("UPDATE setting SET license_key=? WHERE site_id=1");
    $stmt->bind_param('s', $license_key);
    $q = $stmt->execute();
    if (!$q) {
      echo 'Gagal menyimpan lisensi: ' . $stmt->error;
      exit;
    }
    $stmt->close();

    if (!empty($license_key)) {
      $result = indukApiCall('license/validate', 'POST', ['license_key' => $license_key]);
      $_SESSION['license_validation'] = $result;
    } else {
      $_SESSION['license_validation'] = ['valid' => false, 'message' => 'License key dikosongkan.'];
    }

    header('location:../../lisensi?status=ok');
    exit;
    break;

  case 'validate_license':
    $q_set = $connection->query("SELECT license_key FROM setting LIMIT 1");
    $row = $q_set ? $q_set->fetch_assoc() : [];
    $license_key = $row['license_key'] ?? '';

    if (empty($license_key)) {
      $_SESSION['license_validation'] = ['valid' => false, 'message' => 'Belum ada license key.'];
      $stmt_ls1 = $connection->prepare("UPDATE setting SET license_status='unverified', license_school_name=NULL, license_npsn=NULL, license_expired_at=NULL WHERE site_id=?");
      $stmt_ls1->bind_param('i', $site_id);
      $stmt_ls1->execute();
      $stmt_ls1->close();
    } else {
      $result = indukApiCall('license/validate', 'POST', ['license_key' => $license_key]);
      $_SESSION['license_validation'] = $result;

      if (isset($result['valid']) && $result['valid']) {
        $status = $result['status'] ?? '';
        $school = $result['school_name'] ?? '';
        $npsn = $result['npsn'] ?? '';
        $expired = !empty($result['expired_at']) ? $result['expired_at'] : null;
        $stmt_ls2 = $connection->prepare("UPDATE setting SET license_status=?, license_school_name=?, license_npsn=?, license_expired_at=? WHERE site_id=?");
        $stmt_ls2->bind_param('ssssi', $status, $school, $npsn, $expired, $site_id);
        $stmt_ls2->execute();
        $stmt_ls2->close();
      } else {
        $status = $result['status'] ?? 'invalid';
        $stmt_ls3 = $connection->prepare("UPDATE setting SET license_status=? WHERE site_id=?");
        $stmt_ls3->bind_param('si', $status, $site_id);
        $stmt_ls3->execute();
        $stmt_ls3->close();
      }
    }

    header('location:../../lisensi');
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

  default:
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
    break;
}
