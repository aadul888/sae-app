<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 8;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
        echo '
<!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>
<!-- Page content -->
<div class="container-fluid mt--6 user-module-page">

  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats" id="kelas-stat-row">
              <div class="user-stat-card user-stat-total">
                <div class="info">
                  <span class="label">Total Kelas</span>
                  <span class="value" id="kelas-stat-total">0</span>
                </div>
                <div class="icon"><i class="fas fa-door-open"></i></div>
              </div>
              <div class="user-stat-card user-stat-identitas">
                <div class="info">
                  <span class="label">Total Siswa</span>
                  <span class="value" id="kelas-stat-siswa">0</span>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
              </div>
              <div class="user-stat-card user-stat-belum">
                <div class="info">
                  <span class="label">Jurusan</span>
                  <span class="value" id="kelas-stat-jurusan">0</span>
                </div>
                <div class="icon"><i class="fas fa-graduation-cap"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card user-table-panel pb-2">
    <div class="card-header py-3 px-3 user-table-header">
      <div class="user-table-head-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Kelas / Rombongan Belajar</h4>
          <small class="text-muted">Kelola data kelas dan rombongan belajar.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table">';
        if ($data_role['modifikasi'] == 'Y') {
          echo '
                <a href="./mod/kelas/print.php" target="_blank" class="btn-mod btn-mod-info" title="Unduh"><i class="fas fa-download"></i></a>
                <a href="./mod/kelas/print-xii.php" target="_blank" class="btn-mod btn-mod-warn" title="Kelas XII"><i class="fas fa-file-pdf"></i></a>';
        } else {
          echo '
                <a href="./mod/kelas/print.php" target="_blank" class="btn-mod btn-mod-info" disabled title="Unduh"><i class="fas fa-download"></i></a>
                <a href="./mod/kelas/print-xii.php" target="_blank" class="btn-mod btn-mod-warn" disabled title="Kelas XII"><i class="fas fa-file-pdf"></i></a>';
        }
        echo '
        </div>
      </div>
    </div>';

        echo '
    <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th class="text-center" width="8">Tingkat</th>
                    <th class="text-center">Kelas</th>
                    <th class="text-center">Jurusan</th>
                    <th>Wali Kelas</th>
                    <th class="text-center" width="5">Jumlah</th>
                    <th class="text-center" width="5">Kualitas Data</th>
                    <th class="text-center">Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>';
        } else {
          hak_akses();
        }
        echo '
    </div>
  </div>';


        break;
    }
  } else {
    theme_404();
  }
}
