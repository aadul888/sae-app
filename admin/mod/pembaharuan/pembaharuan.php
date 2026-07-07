<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 40;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {
    require_once __DIR__ . '/../../../library/commit_logger.php';

    switch (@$_GET['op']) {
      default:
        echo '
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>
<div class="container-fluid mt--6 user-module-page module-user-like-page">
  <div class="row">
    <div class="col">
      <div class="card user-table-panel module-table-card module-user-like-table">
        <div class="card-header py-3 px-3 user-table-header module-table-header">
          <div class="module-user-like-head">
            <div class="module-user-like-head-main">
              <h4 class="mb-1"><i class="fas fa-cloud-download-alt text-info mr-2"></i>Pembaruan</h4>
              <small class="text-muted">Riwayat pembaruan dan catatan rilis aplikasi.</small>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-items-center table-flush table-striped" style="width:100%">
            <thead class="thead-light">
              <tr>
                <th style="width:80px">Versi</th>
                <th>Keterangan</th>
                <th style="width:160px">Tanggal</th>
              </tr>
            </thead>
            <tbody>' . get_commit_log_rows($connection) . '</tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>';
        break;
    }
  } else {
    theme_404();
  }
}
