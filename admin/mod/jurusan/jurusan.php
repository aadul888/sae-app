<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_route = 'jurusan';
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
  <div class="card user-table-panel pb-2">
    <div class="card-header py-3 px-3 user-table-header">
      <div class="user-table-head-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Jurusan</h4>
          <small class="text-muted">Data otomatis dari Dapodik.</small>
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
                    <th class="text-center" width="6">ID</th>
                    <th class="text-center" width="8">Tingkat</th>
                    <th>Kode Jurusan</th>
                    <th>Nama Jurusan</th>
                    <th class="text-center">Jumlah Siswa</th>
                    <th class="text-center">Logo</th>
                    <th class="text-center">Aksi</th>
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
</div>';

        if ($data_role['modifikasi'] == 'Y') {
          echo '

    <!-- Modal Update Kode + Logo -->
    <div class="modal fade modal-add" id="modalAddJurusan" tabindex="-1" role="dialog" aria-labelledby="modalAddJurusanTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <form class="form-add" role="form" action="#" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">
            <input type="hidden" class="form-control id d-none" name="id" readonly>
            <div class="modal-header">
              <h5 class="modal-title" id="modalAddJurusanTitle">Update Kode/Logo Jurusan <span class="modal-title-name text-info"></span></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="font-weight-bold">Kode Jurusan</label>
                <input type="text" class="form-control jurusan-kode" name="kode_jurusan" maxlength="20" readonly style="background-color: #e9ecef;">
                <small class="form-text text-muted">Kode jurusan dari Dapodik (readonly).</small>
              </div>
              <div class="form-group">
                <label class="font-weight-bold">Nama Jurusan</label>
                <input type="text" class="form-control jurusan-nama" name="nama_jurusan" readonly style="background-color: #e9ecef;">
              </div>
              <div class="form-group">
                <label class="font-weight-bold">Logo Jurusan (PNG, max 1MB, opsional)</label>
                <input type="file" class="form-control-file jurusan-logo" name="logo_jurusan" accept="image/png">
                <small class="form-text text-muted">Data jurusan (ID/nama/kategori) tetap otomatis dari Dapodik. Yang bisa diubah hanya kode singkatan dan logo.</small>
                <div id="logo-preview" class="mt-2"></div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary btn-save"><i class="far fa-save mr-1"></i> Simpan Perubahan</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </form>
        </div>
      </div>
    </div>';
        }
        break;
    }
  } else {
    theme_404();
  }
}
