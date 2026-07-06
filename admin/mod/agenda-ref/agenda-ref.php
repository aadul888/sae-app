<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 20;
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

<div class="container-fluid mt--6 user-module-page">
  <div class="row">
    <div class="col">
      <div class="card shadow">
        <div class="card-header module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Daftar Mata Pelajaran &amp; Guru</h4>
              <small class="text-muted">Kelola daftar mata pelajaran dan guru pengampunya.</small>
            </div>
            <div class="module-header-actions">';
        if (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y') {
          echo '
          <button class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalTambah" title="Tambah Mapel"><i class="fas fa-plus"></i></button>';
        }
        echo '
            </div>
          </div>
        </div>
        <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
          <table class="table align-items-center table-flush table-striped datatable" width="auto">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="4">No</th>
                <th class="text-center">Kode Mapel</th>
                <th class="text-center">Nama Mapel</th>
                <th class="text-center">Guru Pengampu</th>
                <th class="text-center">Status</th>
                <th class="text-center">Aksi</th>
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
  </div>
</div>';

        // Modal Tambah
        if (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y') {
          // Ambil daftar guru (admin level_id=2)
          $guru_list = $connection->query("SELECT admin_id, fullname, gelar_depan, gelar_belakang FROM admin WHERE level_id=2 AND active='Y' ORDER BY fullname ASC");
          echo '
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Tambah Mata Pelajaran</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formTambah">
        <div class="modal-body">
          <div class="form-group">
            <label>Kode Mapel <small class="text-muted">(opsional)</small></label>
            <input type="text" name="kode_mapel" class="form-control" placeholder="Contoh: MTK, BIG, FIS">
          </div>
          <div class="form-group">
            <label>Nama Mata Pelajaran <span class="text-danger">*</span></label>
            <input type="text" name="nama_mapel" class="form-control" required placeholder="Contoh: Matematika">
          </div>
          <div class="form-group">
            <label>Guru Pengampu <span class="text-danger">*</span></label>
            <select name="guru_id" class="form-control">
              <option value="0">-- Tanpa Guru --</option>';
          while ($g = $guru_list->fetch_assoc()) {
            $nama_guru = trim(($g['gelar_depan'] ? $g['gelar_depan'] . ' ' : '') . $g['fullname'] . ($g['gelar_belakang'] ? ', ' . $g['gelar_belakang'] : ''));
            echo '<option value="' . $g['admin_id'] . '">' . htmlspecialchars($nama_guru) . '</option>';
          }
          echo '
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>';

          // Modal Edit
          $guru_list2 = $connection->query("SELECT admin_id, fullname, gelar_depan, gelar_belakang FROM admin WHERE level_id=2 AND active='Y' ORDER BY fullname ASC");
          echo '
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Mata Pelajaran</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formEdit">
        <input type="hidden" name="mapel_id" id="edit_mapel_id">
        <div class="modal-body">
          <div class="form-group">
            <label>Kode Mapel</label>
            <input type="text" name="kode_mapel" id="edit_kode_mapel" class="form-control">
          </div>
          <div class="form-group">
            <label>Nama Mata Pelajaran <span class="text-danger">*</span></label>
            <input type="text" name="nama_mapel" id="edit_nama_mapel" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Guru Pengampu <span class="text-danger">*</span></label>
            <select name="guru_id" id="edit_guru_id" class="form-control">
              <option value="0">-- Tanpa Guru --</option>';
          while ($g = $guru_list2->fetch_assoc()) {
            $nama_guru = trim(($g['gelar_depan'] ? $g['gelar_depan'] . ' ' : '') . $g['fullname'] . ($g['gelar_belakang'] ? ', ' . $g['gelar_belakang'] : ''));
            echo '<option value="' . $g['admin_id'] . '">' . htmlspecialchars($nama_guru) . '</option>';
          }
          echo '
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="aktif" id="edit_aktif" class="form-control">
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
