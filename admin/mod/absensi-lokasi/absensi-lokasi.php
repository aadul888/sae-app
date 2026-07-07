<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 14;
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
    <div class="col">
      <div class="card user-table-panel module-table-card pb-2">
        <div class="card-header py-3 px-3 user-table-header module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Data Lokasi Absensi</h4>
              <small class="text-muted">Kelola titik lokasi yang diizinkan untuk absensi.</small>
            </div>
            <div class="module-header-actions">';
        if ($data_role['modifikasi'] == 'Y') {
          echo '<button class="btn-mod btn-mod-add btn-add" title="Tambah"><i class="fas fa-plus"></i></button>';
        } else {
          echo '<button class="btn-mod btn-mod-add" disabled title="Tambah"><i class="fas fa-plus"></i></button>';
        }
        echo '
            </div>
          </div>
        </div>
        <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
          <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="5">No</th>
                <th class="text-center">Nama Lokasi</th>
                <th class="text-center">Koordinat</th>
                <th class="text-center">Radius</th>
                <th class="text-center">Status</th>
                <th class="text-center" width="10">Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
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
      <!-- Modal Tambah/Edit -->
      <div class="modal fade modal-add" id="modalLokasi" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <form class="form-add" id="formLokasi" novalidate>
              <input type="hidden" name="lokasi_id" class="id">
              <div class="modal-header">
                <h5 class="modal-title">Form Lokasi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="nama_lokasi">Nama Lokasi</label>
                  <input type="text" class="form-control lokasi-nama" name="nama_lokasi" required>
                </div>
                <div class="form-group">
                  <label for="keterangan">Keterangan</label>
                  <textarea class="form-control lokasi-ket" name="keterangan"></textarea>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="latitude">Latitude</label>
                    <input type="text" class="form-control lokasi-lat" name="latitude" placeholder="-6.92123456">
                  </div>
                  <div class="form-group col-md-6">
                    <label for="longitude">Longitude</label>
                    <input type="text" class="form-control lokasi-lng" name="longitude">
                  </div>
                </div>
                <div class="form-group">
                  <label for="radius">Radius (meter)</label>
                  <input type="number" class="form-control lokasi-radius" name="radius" value="100">
                </div>
                <div class="form-group">
                  <label>Ambil Lokasi dari Peta</label>
                  <div id="map" style="height: 300px; border:1px solid #ccc;"></div>
                </div>
                <div class="form-group">
                  <label for="status">Status</label>
                  <select class="form-control lokasi-status" name="status">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary btn-save"><i class="far fa-save"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
              </div>
            </form>
          </div>
        </div>
      </div>';
        }

        echo '</div>'; // container-fluid
        break;
    }
  } else {
    theme_404();
  }
}
