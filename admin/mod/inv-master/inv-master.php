<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 24;
  include __DIR__ . '/../check_role.php';

  // Ambil data
  $cnt_kategori = 0; $cnt_barang = 0;
  $q = $connection->query("SELECT COUNT(*) c FROM inv_kategori"); if ($q) $cnt_kategori = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM inv_barang"); if ($q) $cnt_barang = intval($q->fetch_assoc()['c']);

  $kategori_list = [];
  $qk = $connection->query("SELECT k.*, (SELECT COUNT(*) FROM inv_barang WHERE kategori_id = k.kategori_id) AS jml_barang FROM inv_kategori k ORDER BY k.nama_kategori ASC");
  if ($qk) { while ($rk = $qk->fetch_assoc()) { $kategori_list[] = $rk; } }

  $barang_list = [];
  $qb = $connection->query("SELECT b.*, k.nama_kategori FROM inv_barang b LEFT JOIN inv_kategori k ON b.kategori_id = k.kategori_id ORDER BY k.nama_kategori, b.nama_barang ASC");
  if ($qb) { while ($rb = $qb->fetch_assoc()) { $barang_list[] = $rb; } }

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

<div class="container-fluid mt--6 user-module-page">';

      if ($data_role['lihat'] == 'Y') {

        // Stats cards
        echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Kategori</span><span class="value">'.$cnt_kategori.'</span></div><div class="icon"><i class="fas fa-folder"></i></div></div>
      <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Jenis Barang</span><span class="value">'.$cnt_barang.'</span></div><div class="icon"><i class="fas fa-boxes"></i></div></div>
    </div></div></div></div></div></div>';

        echo '
  <!-- Tab Navigation -->
  <div class="card shadow">
    <div class="card-header border-0 pb-0">
      <div style="width:100%;">
        <div class="module-header-row mb-2" style="gap:10px;">
          <div><h4 class="mb-1">Manajemen Inventaris</h4><small class="text-muted">Kelola kategori dan data barang/sarana sekolah.</small></div>
        </div>
        <div class="d-flex align-items-center" style="gap:8px;">
          <ul class="nav nav-pills nav-fill flex-column flex-md-row tab-responsive justify-content-center mb-2" id="masterTab" role="tablist" style="flex:1;">
            <li class="nav-item">
              <a class="nav-link mb-sm-3 mb-md-0 active" id="tab-kategori" data-toggle="tab" href="#panel-kategori" role="tab">
                <i class="fas fa-folder mr-1"></i> Kategori
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link mb-sm-3 mb-md-0" id="tab-barang" data-toggle="tab" href="#panel-barang" role="tab">
                <i class="fas fa-boxes mr-1"></i> Barang / Sarana
              </a>
            </li>
          </ul>
        </div>
        ', ($data_role['modifikasi'] == 'Y' ? '<div class="d-flex justify-content-center mb-2" style="gap:8px;"><button class="btn-mod btn-mod-add btn-tambah-from-header" id="btn-add-kategori-hdr" title="Tambah Kategori"><i class="fas fa-plus"></i></button><button class="btn-mod btn-mod-teal btn-tambah-from-header" id="btn-add-barang-hdr" title="Tambah Barang"><i class="fas fa-boxes"></i></button></div>' : ''), '
      </div>
    </div>
    <div class="card-body pt-3">
      <div class="tab-content">
        
        <!-- TAB KATEGORI -->
        <div class="tab-pane fade show active" id="panel-kategori" role="tabpanel">

          <div class="table-responsive">
            <table class="table table-hover table-striped" id="tbl-kategori">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" width="40">No</th>
                  <th>Nama Kategori</th>
                  <th>Keterangan</th>
                  <th class="text-center">Jumlah Barang</th>
                  <th class="text-center" width="100">Aksi</th>
                </tr>
              </thead>
              <tbody>';
        $no = 1;
        foreach ($kategori_list as $kat) {
          echo '<tr>
            <td class="text-center">' . $no++ . '</td>
            <td><strong>' . htmlspecialchars($kat['nama_kategori']) . '</strong></td>
            <td>' . htmlspecialchars($kat['keterangan'] ?? '-') . '</td>
            <td class="text-center"><span class="badge badge-primary">' . intval($kat['jml_barang']) . '</span></td>
            <td class="text-center">';
          if ($data_role['modifikasi'] == 'Y') {
            echo '<a href="javascript:void(0)" class="table-action table-action-warning btn-edit-kategori btn-tooltip" data-id="' . $kat['kategori_id'] . '" data-nama="' . htmlspecialchars($kat['nama_kategori']) . '" data-keterangan="' . htmlspecialchars($kat['keterangan'] ?? '') . '" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a>';
          }
          if ($data_role['hapus'] == 'Y') {
            echo '<a href="javascript:void(0)" class="table-action table-action-delete btn-hapus-kategori btn-tooltip" data-id="' . $kat['kategori_id'] . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a>';
          }
          echo '</td></tr>';
        }
        echo '
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB BARANG -->
        <div class="tab-pane fade" id="panel-barang" role="tabpanel">

          <div class="table-responsive">
            <table class="table table-hover table-striped" id="tbl-barang">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" width="40">No</th>
                  <th>Kode</th>
                  <th>Nama Barang</th>
                  <th class="text-center">Kategori</th>
                  <th class="text-center">Satuan</th>
                  <th>Keterangan</th>
                  <th class="text-center" width="100">Aksi</th>
                </tr>
              </thead>
              <tbody>';
        $no = 1;
        foreach ($barang_list as $brg) {
          echo '<tr>
            <td class="text-center">' . $no++ . '</td>
            <td><code>' . htmlspecialchars($brg['kode_barang'] ?? '-') . '</code></td>
            <td><strong>' . htmlspecialchars($brg['nama_barang']) . '</strong></td>
            <td class="text-center"><span class="badge badge-light">' . htmlspecialchars($brg['nama_kategori'] ?? '-') . '</span></td>
            <td class="text-center">' . htmlspecialchars($brg['satuan']) . '</td>
            <td>' . htmlspecialchars($brg['keterangan'] ?? '-') . '</td>
            <td class="text-center">';
          if ($data_role['modifikasi'] == 'Y') {
            echo '<a href="javascript:void(0)" class="table-action table-action-warning btn-edit-barang btn-tooltip" data-id="' . $brg['barang_id'] . '" data-kategori="' . $brg['kategori_id'] . '" data-kode="' . htmlspecialchars($brg['kode_barang'] ?? '') . '" data-nama="' . htmlspecialchars($brg['nama_barang']) . '" data-satuan="' . htmlspecialchars($brg['satuan']) . '" data-keterangan="' . htmlspecialchars($brg['keterangan'] ?? '') . '" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a>';
          }
          if ($data_role['hapus'] == 'Y') {
            echo '<a href="javascript:void(0)" class="table-action table-action-delete btn-hapus-barang btn-tooltip" data-id="' . $brg['barang_id'] . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a>';
          }
          echo '</td></tr>';
        }
        echo '
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>';
      } else {
        hak_akses();
      }

      echo '</div>';

      // Modals
      echo '
<!-- Modal Kategori -->
<div class="modal fade" id="modal-kategori" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-kategori">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-kategori-title"><i class="fas fa-folder mr-2"></i>Tambah Kategori</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="kat-id" name="kategori_id" value="">
          <input type="hidden" id="kat-action" name="action" value="tambah_kategori">
          <div class="form-group">
            <label class="font-weight-bold">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="kat-nama" name="nama_kategori" required maxlength="100">
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Keterangan</label>
            <textarea class="form-control" id="kat-keterangan" name="keterangan" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Barang -->
<div class="modal fade" id="modal-barang" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-barang">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-barang-title"><i class="fas fa-boxes mr-2"></i>Tambah Barang</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="brg-id" name="barang_id" value="">
          <input type="hidden" id="brg-action" name="action" value="tambah_barang">
          <div class="form-group">
            <label class="font-weight-bold">Kategori <span class="text-danger">*</span></label>
            <select class="form-control" id="brg-kategori" name="kategori_id" required>
              <option value="">-- Pilih Kategori --</option>';
      foreach ($kategori_list as $kat) {
        echo '<option value="' . $kat['kategori_id'] . '">' . htmlspecialchars($kat['nama_kategori']) . '</option>';
      }
      echo '
            </select>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Kode Barang</label>
            <input type="text" class="form-control" id="brg-kode" name="kode_barang" maxlength="50" placeholder="Contoh: MBL-001">
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Nama Barang <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="brg-nama" name="nama_barang" required maxlength="200">
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Satuan</label>
            <input type="text" class="form-control" id="brg-satuan" name="satuan" value="Unit" maxlength="30">
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Keterangan</label>
            <textarea class="form-control" id="brg-keterangan" name="keterangan" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>';
      break;
  }
}
