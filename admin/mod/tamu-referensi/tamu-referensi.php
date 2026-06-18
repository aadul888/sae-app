<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 50;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    $can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');

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

<div class="container-fluid mt--6 user-module-page">
  <div class="row">
    <div class="col">
      <div class="card shadow">
        <div class="card-header module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Referensi Buku Tamu</h4>
              <small class="text-muted">Kelola master data Instansi dan Tujuan kunjungan yang dapat dipilih ulang oleh tamu.</small>
            </div>
          </div>
        </div>
        <div class="card-body pb-0">
          <ul class="nav nav-pills mb-3" id="refTabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="tab-instansi-link" data-toggle="pill" href="#tab-instansi" role="tab">
                <i class="fas fa-building mr-1"></i> Instansi
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="tab-tujuan-link" data-toggle="pill" href="#tab-tujuan" role="tab">
                <i class="fas fa-bullseye mr-1"></i> Tujuan / Keperluan
              </a>
            </li>
          </ul>
        </div>

        <div class="tab-content" id="refTabsContent">
          <!-- ============ TAB INSTANSI ============ -->
          <div class="tab-pane fade show active" id="tab-instansi" role="tabpanel">
            <div class="module-header-row px-4 pb-2" style="gap:10px;">
              <small class="text-muted">Daftar instansi/perusahaan tamu.</small>
              <div class="module-header-actions">';
        if ($can_edit) {
          echo '<button class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalTambahInstansi" title="Tambah Instansi"><i class="fas fa-plus"></i></button>';
        }
        echo '
              </div>
            </div>
            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped" id="tableInstansi" width="100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="4">No</th>
                    <th class="text-center">Nama Instansi</th>
                    <th class="text-center">Jenis</th>
                    <th class="text-center">Telepon</th>
                    <th class="text-center">Alamat</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- ============ TAB TUJUAN ============ -->
          <div class="tab-pane fade" id="tab-tujuan" role="tabpanel">
            <div class="module-header-row px-4 pb-2" style="gap:10px;">
              <small class="text-muted">Daftar tujuan/keperluan kunjungan.</small>
              <div class="module-header-actions">';
        if ($can_edit) {
          echo '<button class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalTambahTujuan" title="Tambah Tujuan"><i class="fas fa-plus"></i></button>';
        }
        echo '
              </div>
            </div>
            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped" id="tableTujuan" width="100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="4">No</th>
                    <th class="text-center">Nama Tujuan</th>
                    <th class="text-center">Keterangan</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>';

        if ($can_edit) {
          // ===== Modal Tambah Instansi =====
          echo '
<div class="modal fade" id="modalTambahInstansi" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Tambah Instansi</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formTambahInstansi">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Instansi <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required placeholder="Contoh: PT Maju Jaya">
          </div>
          <div class="form-group">
            <label>Jenis</label>
            <select name="jenis" class="form-control">
              <option value="">- Pilih -</option>
              <option value="Perusahaan">Perusahaan</option>
              <option value="Instansi Pemerintah">Instansi Pemerintah</option>
              <option value="Sekolah/Kampus">Sekolah/Kampus</option>
              <option value="Perorangan">Perorangan</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div class="form-group">
            <label>Telepon</label>
            <input type="text" name="telepon" class="form-control" placeholder="Opsional">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" placeholder="Opsional">
          </div>
          <div class="form-group">
            <label>Alamat</label>
            <textarea name="alamat" class="form-control" rows="2" placeholder="Opsional"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditInstansi" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Instansi</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formEditInstansi">
        <input type="hidden" name="id" id="ei_id">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Instansi <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="ei_nama" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Jenis</label>
            <select name="jenis" id="ei_jenis" class="form-control">
              <option value="">- Pilih -</option>
              <option value="Perusahaan">Perusahaan</option>
              <option value="Instansi Pemerintah">Instansi Pemerintah</option>
              <option value="Sekolah/Kampus">Sekolah/Kampus</option>
              <option value="Perorangan">Perorangan</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div class="form-group">
            <label>Telepon</label>
            <input type="text" name="telepon" id="ei_telepon" class="form-control">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="ei_email" class="form-control">
          </div>
          <div class="form-group">
            <label>Alamat</label>
            <textarea name="alamat" id="ei_alamat" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="active" id="ei_active" class="form-control">
              <option value="Y">Aktif</option>
              <option value="N">Tidak Aktif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambahTujuan" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Tambah Tujuan</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formTambahTujuan">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Tujuan <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required placeholder="Contoh: Konsultasi">
          </div>
          <div class="form-group">
            <label>Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditTujuan" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Tujuan</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formEditTujuan">
        <input type="hidden" name="id" id="et_id">
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Tujuan <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="et_nama" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Keterangan</label>
            <textarea name="keterangan" id="et_keterangan" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="active" id="et_active" class="form-control">
              <option value="Y">Aktif</option>
              <option value="N">Tidak Aktif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Update</button>
        </div>
      </form>
    </div>
  </div>
</div>';
        }
        break;
    }
  } else {
    hak_akses();
  }
}
?>
