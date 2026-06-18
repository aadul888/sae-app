<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 39;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
        $menu_total = 0;
        $menu_active = 0;
        $menu_nonactive = 0;

        if ($q = $connection->query("SELECT COUNT(*) AS total FROM student_menu")) {
          $r = $q->fetch_assoc();
          $menu_total = intval($r['total'] ?? 0);
        }
        if ($q = $connection->query("SELECT COUNT(*) AS total FROM student_menu WHERE aktif='Y'")) {
          $r = $q->fetch_assoc();
          $menu_active = intval($r['total'] ?? 0);
        }
        if ($q = $connection->query("SELECT COUNT(*) AS total FROM student_menu WHERE aktif='N'")) {
          $r = $q->fetch_assoc();
          $menu_nonactive = intval($r['total'] ?? 0);
        }

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
    <div class="container-fluid mt--6 user-module-page module-user-like-page">
      <div class="row">
        <div class="col-12">
          <div class="card user-stats-panel module-stats-shell mb-3">
            <div class="card-body py-2 px-2 px-md-3">
              <div class="user-stats-wrap">
                <div class="user-stats module-stats-grid" id="menu-siswa-stat-row">
                  <div class="module-stat-card user-stat-total">
                    <div class="info">
                      <span class="label">Total Menu</span>
                      <span class="value">' . intval($menu_total) . '</span>
                      <span class="sub-info">Seluruh menu siswa</span>
                    </div>
                    <div class="icon"><i class="fas fa-th-large"></i></div>
                  </div>
                  <div class="module-stat-card user-stat-identitas">
                    <div class="info">
                      <span class="label">Menu Aktif</span>
                      <span class="value">' . intval($menu_active) . '</span>
                      <span class="sub-info">Ditampilkan di dashboard siswa</span>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                  </div>
                  <div class="module-stat-card user-stat-belum-sesuai">
                    <div class="info">
                      <span class="label">Menu Nonaktif</span>
                      <span class="value">' . intval($menu_nonactive) . '</span>
                      <span class="sub-info">Disembunyikan sementara</span>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <div class="card user-table-panel module-table-card module-user-like-table pb-2">
            <div class="card-header py-3 px-3 user-table-header module-table-header">
              <div class="module-user-like-head">
                <div class="module-user-like-head-main">
                  <h4 class="mb-1">Menu Aplikasi Siswa</h4>
                  <small class="text-muted">Kelola menu dan fitur aplikasi yang tersedia bagi siswa.</small>
                </div>
                <div class="module-user-like-toolbar">'; 
        if ($data_role['modifikasi'] == 'Y') {
          echo '
        <button class="btn-mod btn-mod-warn btn-sync" title="Sinkronisasi"><i class="fas fa-sync"></i></button>
        <button class="btn-mod btn-mod-add btn-add" title="Tambah"><i class="fas fa-plus"></i></button>';
        } else {
          echo '
                <a href="./mod/menu-siswa/print.php" target="_blank" class="btn-mod btn-mod-info" disabled title="Print"><i class="fas fa-print"></i></a>
              <button class="btn-mod btn-mod-add" disabled title="Tambah"><i class="fas fa-plus"></i></button>';
        }
        echo '
                </div>
              </div>
            </div><!-- end card-header -->
            <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
              <table id="menu-siswa-table" class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Menu</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" width="150">Action</th>
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
          </div>
        </div>
      </div>';


        if ($data_role['modifikasi'] == 'Y') {
          echo '

  <!-- Modal ADD/EDIT -->
  <div class="modal fade" id="menuSiswaModal" tabindex="-1" role="dialog" aria-labelledby="menuSiswaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form id="menuSiswaForm" class="form-add" role="form" action="#">
          <input type="hidden" name="id" class="id">
          <div class="modal-header">
            <h5 class="modal-title" id="menuSiswaModalLabel"><span class="modal-title-name text-info"></span></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Label <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="label" required>
              <small class="form-text text-muted">Nama menu yang akan ditampilkan di aplikasi siswa</small>
            </div>
            <div class="form-group">
              <label>Slug <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="slug" required>
              <small class="form-text text-muted">Nama folder/modul di dashboard/mod (contoh: identitas, berkas, absensi)</small>
            </div>
            <div class="form-group">
              <label>Position <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="position" value="10" required min="1">
              <small class="form-text text-muted">Urutan tampilan menu (angka lebih kecil akan ditampilkan lebih awal)</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary btn-save">Simpan</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>';
        }

        // include module scripts (ensure DataTable and handlers load)
        echo "<script src='./mod/menu-siswa/scripts.js?v=" . time() . "'></script>";

        break;
    }
  } else {
    theme_404();
  }
}
