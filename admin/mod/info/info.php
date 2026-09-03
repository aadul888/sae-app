<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 42;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

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
              <h4 class="mb-1"><i class="fas fa-info-circle text-info mr-2"></i>Info</h4>
              <small class="text-muted">Kelola informasi yang tampil di semua dashboard dan publik.</small>
            </div>
            <div class="module-user-like-head-actions module-header-actions">
              <button class="btn-mod btn-mod-add btn-tambah-info" title="Tambah Info"><i class="fas fa-plus"></i></button>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-items-center table-flush table-striped datatable" id="table-info" style="width:100%">
            <thead class="thead-light">
              <tr>
                <th class="text-center" style="width:50px">No</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Konten</th>
                <th class="text-center" style="width:80px">Aktif</th>
                <th class="text-center" style="width:60px">Urutan</th>
                <th style="width:160px">Tgl. Tampil</th>
                <th class="text-center" style="width:100px">Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalInfo" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <form id="formInfo" method="post">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title text-white">Info</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="infoId">
          <div class="form-group">
            <label>Judul <span class="text-danger">*</span></label>
            <input type="text" name="judul" id="infoJudul" class="form-control" required maxlength="255">
          </div>
          <div class="form-group">
            <label>Kategori</label>
            <select name="kategori" id="infoKategori" class="form-control">
              <option value="">— Umum —</option>
              <option value="pengumuman">Pengumuman</option>
              <option value="kegiatan">Kegiatan</option>
              <option value="peringatan">Peringatan</option>
            </select>
          </div>
          <div class="form-group">
            <label>Isi / Konten</label>
            <textarea name="konten" id="infoKonten" class="form-control" rows="5"></textarea>
            <small class="text-muted">HTML diperbolehkan. Kosongkan jika hanya judul saja.</small>
          </div>
          <div class="form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" name="aktif" id="infoAktif" value="1">
              <label class="custom-control-label" for="infoAktif">Aktif</label>
            </div>
          </div>
          <div class="form-group">
            <label>Urutan</label>
            <input type="number" name="urutan" id="infoUrutan" class="form-control" value="0" min="0" style="width:120px">
            <small class="text-muted">Semakin besar, tampil lebih awal.</small>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Tampil Mulai</label>
                <input type="date" name="tgl_mulai" id="infoTglMulai" class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Tampil Sampai</label>
                <input type="date" name="tgl_selesai" id="infoTglSelesai" class="form-control">
              </div>
            </div>
          </div>
          <small class="text-muted d-block mb-2">Kosongkan jika tidak ada batas tanggal.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>';
        break;
    }
  }
}
