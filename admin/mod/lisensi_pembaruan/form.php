<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');
  require_once '../../../library/commit_logger.php';
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

  // Latest release from pembaharuan
  $latest_release = null;
  $q_rel = $connection->query("SELECT version, release_date, mandatory, download_link, pembaharuan, perbaikan FROM pembaharuan ORDER BY release_date DESC LIMIT 1");
  if ($q_rel && $q_rel->num_rows > 0) {
    $latest_release = $q_rel->fetch_assoc();
  }

  $current_version = defined('SAE_VERSION') ? SAE_VERSION : $app_version;
  $update_available = false;
  $update_status_label = 'Terbaru';
  $update_status_class = 'badge-success';
  if ($latest_release) {
    if (version_compare($current_version, $latest_release['version'], '<')) {
      $update_available = true;
      $update_status_label = 'Pembaruan Tersedia';
      $update_status_class = 'badge-danger';
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
      <form class="form-setting" role="form" method="post" action="./mod/lisensi_pembaruan/proses.php?action=save_license" autocomplete="off">
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
                    <a href="./mod/lisensi_pembaruan/proses.php?action=validate_license" class="btn btn-outline-info"><i class="fas fa-check-double mr-1"></i> Validasi ke Induk</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>';
      break;

    case 2: // Status Pembaruan
      echo '
      <div class="row">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h4 class="mb-0"><i class="fas fa-sync-alt mr-2 text-info"></i>Status Pembaruan</h4>
            </div>
            <div class="card-body">
              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Versi Saat Ini</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" value="' . htmlspecialchars($current_version) . '" readonly>
                </div>
              </div>
              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Sinkronisasi Terakhir</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" value="' . (!empty($last_sync_at) ? tgl_indo($last_sync_at) . ' ' . jam_indo($last_sync_at) : 'Belum pernah sinkron') . '" readonly>
                </div>
              </div>
              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Status Pembaruan</label>
                <div class="col-md-9">
                  <span class="badge ' . $update_status_class . ' badge-lg">' . $update_status_label . '</span>
                </div>
              </div>
              ' . ($update_available && $latest_release ? '
              <div class="alert alert-info mt-3" role="alert">
                <h5 class="alert-heading"><i class="fas fa-download mr-1"></i> Versi Baru Tersedia: ' . htmlspecialchars($latest_release['version']) . '</h5>
                <p class="mb-1">Rilis: ' . tgl_indo($latest_release['release_date']) . '</p>
                ' . (!empty($latest_release['pembaharuan']) ? '<p class="mb-1"><strong>Fitur Baru:</strong><br>' . nl2br(htmlspecialchars($latest_release['pembaharuan'])) . '</p>' : '') . '
                ' . (!empty($latest_release['perbaikan']) ? '<p class="mb-0"><strong>Perbaikan:</strong><br>' . nl2br(htmlspecialchars($latest_release['perbaikan'])) . '</p>' : '') . '
                ' . (!empty($latest_release['download_link']) ? '<a href="' . htmlspecialchars($latest_release['download_link']) . '" target="_blank" class="btn btn-sm btn-primary mt-2"><i class="fas fa-download mr-1"></i> Unduh Pembaruan</a>' : '') . '
              </div>
              ' : '') . '
              <div class="form-group row">
                <div class="col-md-9 offset-md-3">
                  <button type="button" class="btn btn-info" onclick="checkUpdate(this)"><i class="fas fa-search mr-1"></i> Periksa Pembaruan</button>
                  ' . ($update_available ? '<a href="./mod/lisensi_pembaruan/proses.php?action=deploy&csrf=' . $_SESSION['csrf_token'] . '" class="btn btn-success btn-deploy"><i class="fas fa-cloud-download-alt mr-1"></i> Terapkan Pembaruan</a>' : '') . '
                  <a href="./mod/lisensi_pembaruan/proses.php?action=deploy&csrf=' . $_SESSION['csrf_token'] . '" class="btn btn-outline-primary btn-deploy-force ml-1"><i class="fas fa-download mr-1"></i> Tarik Pembaruan</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Riwayat Commit -->
      <div class="row mt-3">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h4 class="mb-0"><i class="fas fa-history mr-2 text-secondary"></i>Riwayat Pembaruan</h4>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th style="width:80px">Versi</th>
                      <th>Keterangan</th>
                      <th style="width:160px">Tanggal</th>
                    </tr>
                  </thead>
                  <tbody>
                    ' . get_commit_log_rows($connection) . '
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

<script>
document.querySelector(".btn-deploy")?.addEventListener("click", function(e){
  e.preventDefault();
  if (!confirm("Yakin ingin menerapkan pembaruan?")) return;
  var btn = this;
  btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Menerapkan...';
  fetch(this.href).then(function(r){ return r.json(); }).then(function(d){
    if(d.success) { location.reload(); }
    else { swal({title:"Gagal",text:d.message||"Gagal menerapkan pembaruan.",icon:"error"}); btn.disabled=false; btn.innerHTML='<i class="fas fa-cloud-download-alt mr-1"></i> Terapkan Pembaruan'; }
  }).catch(function(){ swal({title:"Gagal",text:"Gagal terhubung ke server.",icon:"error"}); btn.disabled=false; btn.innerHTML='<i class="fas fa-cloud-download-alt mr-1"></i> Terapkan Pembaruan'; });
});
document.querySelector(".btn-deploy-force")?.addEventListener("click", function(e){
  e.preventDefault();
  if (!confirm("Tarik pembaruan terbaru dari GitHub?")) return;
  var btn = this;
  btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Menarik...';
  fetch(this.href).then(function(r){ return r.json(); }).then(function(d){
    if(d.success) { location.reload(); }
    else { swal({title:"Gagal",text:d.message||"Gagal menarik pembaruan.",icon:"error"}); btn.disabled=false; btn.innerHTML='<i class="fas fa-download mr-1"></i> Tarik Pembaruan'; }
  }).catch(function(){ swal({title:"Gagal",text:"Gagal terhubung ke server.",icon:"error"}); btn.disabled=false; btn.innerHTML='<i class="fas fa-download mr-1"></i> Tarik Pembaruan'; });
});
</script>';
      break;

    case 3: // Notifikasi — migrated to header global banner
      echo '<div class="text-center py-5 text-muted"><i class="fas fa-bell-slash fa-2x mb-2"></i><p>Notifikasi pembaruan kini ditampilkan di seluruh halaman admin.</p></div>';
      break;

    default:
      echo '<div class="alert alert-danger">Tab tidak dikenal.</div>';
      break;
  }
}
