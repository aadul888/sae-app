<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 43;
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
      <!-- Table -->
      <div class="row">
        <div class="col">
          <div class="card shadow" style="overflow:visible;">
            <div class="card-body pb-2" style="overflow:visible;">
              <div class="alert alert-info mb-2" role="alert">
                <strong>Aturan Level:</strong> Operator Sekolah bersifat superadmin. Tugas tambahan hanya dapat memakai modul yang belum dipakai level utama Guru dan Tenaga Administrasi.
              </div>
            </div>
            <!-- Card header -->


            <div class="pt-2 pl-2 mb-0" style="overflow:visible;">
              <div style="position:relative; z-index:300; overflow:visible;">
                <small class="text-muted font-weight-bold ml-2 mb-1 d-block">LEVEL UTAMA</small>
                <ul class="nav nav-pills flex-wrap tab-responsive">';
        $query_level_utama = "SELECT * FROM level WHERE tipe='utama' AND level_nama IN ('Operator Sekolah','Guru','Tenaga Administrasi') ORDER BY FIELD(level_nama,'Operator Sekolah','Guru','Tenaga Administrasi'), level_id ASC";
        $result_level_utama = $connection->query($query_level_utama);
        while ($data_level = $result_level_utama->fetch_assoc()) {
          echo '
                        <li class="nav-item">
                            <a href="javascript:void(0);" onclick="loadData(' . $data_level['level_id'] . ');" data-id="' . (int) $data_level['level_id'] . '" data-name="' . strip_tags($data_level['level_nama']) . '" class="nav-link text-uppercase btn-tab" data-toggle="tab" aria-controls="home" aria-selected="true">' . strip_tags($data_level['level_nama']) . '</a>
                        </li>';
        }
        echo '
              </ul>
              </div>
              <div style="position:relative; z-index:200; overflow:visible;">
                <small class="text-muted font-weight-bold ml-2 mb-1 mt-3 d-block">TUGAS TAMBAHAN</small>
                <ul class="nav nav-pills flex-wrap tab-responsive">';
        $query_level_tugas = "SELECT * FROM level WHERE tipe='tugas' AND level_nama IN ('Kepala Sekolah','Waka Kurikulum','Waka Humas','Waka Sarpras','Waka Kesiswaan','Kepala Program Keahlian','Wali Kelas','Guru Piket','Security','Toolman/Teknisi') ORDER BY FIELD(level_nama,'Kepala Sekolah','Waka Kurikulum','Waka Humas','Waka Sarpras','Waka Kesiswaan','Kepala Program Keahlian','Wali Kelas','Guru Piket','Security','Toolman/Teknisi'), level_id ASC";
        $result_level_tugas = $connection->query($query_level_tugas);
        while ($data_level = $result_level_tugas->fetch_assoc()) {
          echo '
                        <li class="nav-item">
                            <a href="javascript:void(0);" onclick="loadData(' . $data_level['level_id'] . ');" data-id="' . (int) $data_level['level_id'] . '" data-name="' . strip_tags($data_level['level_nama']) . '" class="nav-link text-uppercase btn-tab" data-toggle="tab" aria-controls="home" aria-selected="true">' . strip_tags($data_level['level_nama']) . '</a>
                        </li>';
        }
        echo '
                </ul>
              </div>
            </div>

            <div class="card-header module-table-header">
              <div class="module-header-row" style="gap:10px;">
                <div><h4 class="mb-1 title-header">Admin</h4><small class="text-muted">Pilih level di atas untuk melihat dan kelola hak akses.</small></div>
                <div class="module-header-actions">';
        if ($data_role['modifikasi'] == 'Y') {
          echo '
                <button class="btn-mod btn-mod-add btn-add" title="Tambah"><i class="fas fa-plus"></i></button>
                <button class="btn-mod btn-mod-add btn-sync-modul" id="btnSyncModul" title="Sinkronisasi modul baru dari sidebar"><i class="fas fa-sync"></i></button>';
        } else {
          echo '
                <button class="btn-mod btn-mod-add" disabled title="Tambah"><i class="fas fa-plus"></i></button>';
        }
        echo '
              </div></div>
            </div>';
        if ($data_role['modifikasi'] == 'Y') {
          echo '
                <div class="card-body load-data">

                </div>';
        } else {
          hak_akses();
        }
        echo '
                <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                <form class="form-add" role="form" action="#">
                                    <input type="hidden" class="form-control level d-none" name="level" readonly>
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modal Title</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Pilih Modul/Menu</label>
                                            <select class="form-control" name="modul_id" required>
                                                <option value="">-- Pilih modul --</option>';
        echo '
                                            </select>
                                        <small class="text-muted module-hint d-block mt-2">Daftar modul menyesuaikan level yang dipilih.</small>
                                        </div>

                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn  btn-primary btn-save">Simpan</button>
                                        <button type="button" class="btn  btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </form>
                                </div>
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
?>
