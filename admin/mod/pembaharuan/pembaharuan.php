<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 40;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
        $pemb_total = 0;
        $pemb_mandatory = 0;
        $pemb_optional = 0;

        if ($q = $connection->query("SELECT COUNT(*) AS total FROM pembaharuan")) {
          $r = $q->fetch_assoc();
          $pemb_total = intval($r['total'] ?? 0);
        }
        if ($q = $connection->query("SELECT COUNT(*) AS total FROM pembaharuan WHERE mandatory='Y'")) {
          $r = $q->fetch_assoc();
          $pemb_mandatory = intval($r['total'] ?? 0);
        }
        if ($q = $connection->query("SELECT COUNT(*) AS total FROM pembaharuan WHERE mandatory='N'")) {
          $r = $q->fetch_assoc();
          $pemb_optional = intval($r['total'] ?? 0);
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
                <div class="user-stats module-stats-grid" id="pembaharuan-stat-row">
                  <div class="module-stat-card user-stat-total">
                    <div class="info">
                      <span class="label">Total Rilis</span>
                      <span class="value">' . intval($pemb_total) . '</span>
                      <span class="sub-info">Versi pembaharuan tercatat</span>
                    </div>
                    <div class="icon"><i class="fas fa-code-branch"></i></div>
                  </div>
                  <div class="module-stat-card user-stat-belum-sesuai">
                    <div class="info">
                      <span class="label">Wajib Update</span>
                      <span class="value">' . intval($pemb_mandatory) . '</span>
                      <span class="sub-info">Rilis dengan status wajib</span>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                  </div>
                  <div class="module-stat-card user-stat-identitas">
                    <div class="info">
                      <span class="label">Opsional</span>
                      <span class="value">' . intval($pemb_optional) . '</span>
                      <span class="sub-info">Rilis tanpa kewajiban update</span>
                    </div>
                    <div class="icon"><i class="fas fa-check-double"></i></div>
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
                  <h4 class="mb-1">Pembaharuan Aplikasi</h4>
                  <small class="text-muted">Riwayat dan catatan pembaruan versi aplikasi.</small>
                </div>
      <div class="module-user-like-toolbar">';
        if ($data_role['modifikasi'] == 'Y') {
          echo '
                <a href="./mod/pembaharuan/print.php" target="_blank" class="btn-mod btn-mod-info" title="Print"><i class="fas fa-print"></i></a>
              <a href="./mod/pembaharuan/proses.php?action=export_pdf" class="btn-mod btn-mod-secondary" title="Export PDF"><i class="fas fa-file-pdf"></i></a>
              <button class="btn-mod btn-mod-add btn-add" title="Tambah"><i class="fas fa-plus"></i></button>';
        } else {
          echo '
                <a href="./mod/pembaharuan/print.php" target="_blank" class="btn-mod btn-mod-info" disabled title="Print"><i class="fas fa-print"></i></a>
              <button class="btn-mod btn-mod-add" disabled title="Tambah"><i class="fas fa-plus"></i></button>';
        }
        echo '
              </div>
              </div>
            </div><!-- end card-header -->
            <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Versi</th>
                    <th>Tanggal Rilis</th>
                    <th class="text-center">Wajib</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Link</th>
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
        </div>
      </div>';


        if ($data_role['modifikasi'] == 'Y') {
          echo '

  <!-- Modal ADD/EDIT -->
  <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                <form class="form-add" role="form" action="#">
                    <input type="hidden" class="form-control id d-none" name="id" readonly>
                    <div class="modal-header">
                        <h5 class="modal-title"><span class="modal-title-name text-info"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
            <div class="form-group">
              <label>Versi</label>
              <input type="text" class="form-control version" name="version" required>
            </div>
            
                        <div class="form-group">
                            <label>Tanggal Rilis</label>
                            <input type="date" class="form-control release_date" name="release_date" required>
                        </div>
                        <div class="form-group">
                            <label>Wajib?</label>
                            <select class="form-control mandatory" name="mandatory">
                                <option value="N">Tidak</option>
                                <option value="Y">Ya</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Link Download</label>
                            <input type="url" class="form-control download_link" name="download_link" placeholder="https://...">
                        </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Poin Pembaharuan (satu poin per baris)</label>
                <textarea class="form-control pembaharuan" name="pembaharuan" rows="6" placeholder="Contoh:\n- Perbaikan tampilan login\n- Penambahan fitur export PDF\n- Penyesuaian layout"></textarea>
                <small class="form-text text-muted">Tuliskan setiap poin pembaharuan pada baris baru.</small>
              </div>
              <div class="form-group col-md-6">
                <label>Poin Perbaikan (satu poin per baris)</label>
                <textarea class="form-control perbaikan" name="perbaikan" rows="6" placeholder="Isi perbaikan jika ada, satu poin per baris"></textarea>
                <small class="form-text text-muted">Kosongkan jika tidak ada perbaikan spesifik.</small>
              </div>
            </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn  btn-primary btn-save">Simpan</button>
                        <button type="button" class="btn  btn-secondary" data-dismiss="modal">Close</button>
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
