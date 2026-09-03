<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';

  // Load setting data
  $q_set = $connection->query("SELECT license_key, app_version, last_sync_at, license_status, license_school_name, license_npsn, license_expired_at FROM setting LIMIT 1");
  $set_data = $q_set ? $q_set->fetch_assoc() : array();
  $license_key = $set_data['license_key'] ?? '';
  $app_version = $set_data['app_version'] ?? (defined('SAE_VERSION') ? SAE_VERSION : 'v5.0');
  $last_sync_at = $set_data['last_sync_at'] ?? '';
  
  $db_license_status = $set_data['license_status'] ?? 'unverified';
  $db_school_name = $set_data['license_school_name'] ?? '';
  $db_npsn = $set_data['license_npsn'] ?? '';
  $db_expired_at = $set_data['license_expired_at'] ?? '';

  // Real-time validation on every tab-1 load
  if ((int)$_GET['id'] === 1 && !empty($license_key)) {
    $validation = indukApiCall('license/validate', 'POST', ['license_key' => $license_key]);
    if (isset($validation['valid'])) {
      if ($validation['valid']) {
        $status = $connection->real_escape_string($validation['status'] ?? 'active');
        $school = $connection->real_escape_string($validation['school_name'] ?? '');
        $npsn   = $connection->real_escape_string($validation['npsn'] ?? '');
        $expired = !empty($validation['expired_at']) ? "'" . $connection->real_escape_string($validation['expired_at']) . "'" : "NULL";
        $connection->query("UPDATE setting SET license_status='$status', license_school_name='$school', license_npsn='$npsn', license_expired_at=$expired WHERE site_id=1");
      } else {
        $status = $connection->real_escape_string($validation['status'] ?? 'invalid');
        $connection->query("UPDATE setting SET license_status='$status', license_school_name=NULL, license_npsn=NULL, license_expired_at=NULL WHERE site_id=1");
      }
      // Re-read fresh data
      $q_set = $connection->query("SELECT license_key, app_version, last_sync_at, license_status, license_school_name, license_npsn, license_expired_at FROM setting LIMIT 1");
      $set_data = $q_set ? $q_set->fetch_assoc() : array();
      $db_license_status = $set_data['license_status'] ?? 'unverified';
      $db_school_name = $set_data['license_school_name'] ?? '';
      $db_npsn = $set_data['license_npsn'] ?? '';
      $db_expired_at = $set_data['license_expired_at'] ?? '';
    }
  }

  switch ((int)$_GET['id']) {
    case 1: // Informasi Lisensi
      $l_status = $db_license_status;
      $l_school = $db_school_name;
      $l_npsn = $db_npsn;
      $l_expired = $db_expired_at;
      
      switch ($l_status) {
        case 'active':
          $l_label = 'Aktif';
          $l_class = 'badge-success';
          break;
        case 'suspended':
          $l_label = 'Ditangguhkan';
          $l_class = 'badge-danger';
          break;
        case 'expired':
          $l_label = 'Kadaluarsa';
          $l_class = 'badge-danger';
          break;
        case 'invalid':
          $l_label = 'Tidak Valid';
          $l_class = 'badge-danger';
          break;
        default:
          $l_label = 'Belum Diverifikasi';
          $l_class = 'badge-warning';
          break;
      }

      echo '
      <form class="form-setting" role="form" method="post" action="./mod/lisensi/proses.php?action=save_license" autocomplete="off">
        <?php echo csrf_field(); ?>
        <div class="row">
          <div class="col-12">
            <div class="card shadow-sm">
              <div class="card-header bg-white">
                <h4 class="mb-0"><i class="fas fa-key mr-2 text-primary"></i>Informasi Lisensi</h4>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <label class="col-md-3 col-form-label form-control-label font-weight-bold">NPSN</label>
                  <div class="col-md-9">
                    <input type="text" class="form-control" value="' . htmlspecialchars($npsn ?? '') . '" readonly>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-md-3 col-form-label form-control-label font-weight-bold">Sekolah</label>
                  <div class="col-md-9">
                    <input type="text" class="form-control" value="' . htmlspecialchars($site_name ?? '') . '" readonly>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-md-3 col-form-label form-control-label font-weight-bold">License Key</label>
                  <div class="col-md-9">
                    <div class="input-group">
                      <input type="text" class="form-control" id="license_key" name="license_key" value="' . htmlspecialchars($license_key) . '" placeholder="Masukkan license key">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById(\'license_key\').value=\'\'" title="Kosongkan"><i class="fas fa-eraser"></i></button>
                      </div>
                    </div>
                    <small class="form-text text-muted">Masukkan license key yang diterbitkan oleh SAE Induk</small>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-md-3 col-form-label form-control-label font-weight-bold">Status Lisensi</label>
                  <div class="col-md-9">
                    <span class="badge ' . $l_class . ' badge-lg">' . $l_label . '</span>
                    ' . ($l_status === 'active' ? '<small class="text-muted ml-2">Tervalidasi oleh SAE Induk</small>' : ($l_status === 'unverified' ? '<small class="text-muted ml-2">Klik "Simpan" untuk verifikasi otomatis</small>' : '<small class="text-muted ml-2">Lisensi bermasalah</small>')) . '
                  </div>
                </div>
                ' . (!empty($l_school) ? '
                <div class="form-group row">
                  <label class="col-md-3 col-form-label form-control-label font-weight-bold">Sekolah (Induk)</label>
                  <div class="col-md-9">
                    <input type="text" class="form-control" value="' . htmlspecialchars($l_school) . '" readonly>
                  </div>
                </div>
                ' : '') . '
                ' . (!empty($l_npsn) ? '
                <div class="form-group row">
                  <label class="col-md-3 col-form-label form-control-label font-weight-bold">NPSN (Induk)</label>
                  <div class="col-md-9">
                    <input type="text" class="form-control" value="' . htmlspecialchars($l_npsn) . '" readonly>
                  </div>
                </div>
                ' : '') . '
                ' . (!empty($l_expired) ? '
                <div class="form-group row">
                  <label class="col-md-3 col-form-label form-control-label font-weight-bold">Masa Berlaku</label>
                  <div class="col-md-9">
                    <input type="text" class="form-control" value="' . htmlspecialchars($l_expired) . '" readonly>
                  </div>
                </div>
                ' : '') . '
                <div class="form-group row">
                  <div class="col-md-9 offset-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <a href="./mod/lisensi/proses.php?action=validate_license" class="btn btn-outline-info"><i class="fas fa-check-double mr-1"></i> Validasi ke Induk</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>';
      break;
  } // end switch
}
