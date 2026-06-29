<?php
/**
 * MODUL: SURAT INDEX — Referensi Indeks & Penomoran Surat
 * Data disimpan di tabel surat_index.
 * Fitur: CRUD, Import Excel.
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
$modul_id = 130;
include __DIR__ . '/../check_role.php';
if (!$has_access) { hak_akses(); return; }

$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
$can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

$total = 0;
$stat_perihal = 0;
$stat_kategori = 0;
$stat_jenis = 0;
$r_total = $connection->query("SELECT COUNT(*) c FROM surat_index"); if ($r_total) $total = (int)$r_total->fetch_row()[0];
$r_perihal = $connection->query("SELECT COUNT(DISTINCT perihal) c FROM surat_index"); if ($r_perihal) $stat_perihal = (int)$r_perihal->fetch_row()[0];
$r_kategori = $connection->query("SELECT COUNT(DISTINCT kategori) c FROM surat_index"); if ($r_kategori) $stat_kategori = (int)$r_kategori->fetch_row()[0];
$r_jenis = $connection->query("SELECT COUNT(DISTINCT jenis_surat) c FROM surat_index"); if ($r_jenis) $stat_jenis = (int)$r_jenis->fetch_row()[0];

// Ambil daftar kategori unik untuk dropdown
$kategori_list = [];
$rk = $connection->query("SELECT DISTINCT kategori FROM surat_index WHERE kategori != '' ORDER BY kategori ASC");
if ($rk) while ($k = $rk->fetch_assoc()) $kategori_list[] = htmlspecialchars($k['kategori']);

// Ambil daftar jenis surat unik
$jenis_list = [];
$rj = $connection->query("SELECT DISTINCT jenis_surat FROM surat_index WHERE jenis_surat != '' ORDER BY jenis_surat ASC");
if ($rj) while ($j = $rj->fetch_assoc()) $jenis_list[] = htmlspecialchars($j['jenis_surat']);
if (!in_array('Surat Keluar', $jenis_list)) $jenis_list[] = 'Surat Keluar';
if (!in_array('Surat Masuk', $jenis_list)) $jenis_list[] = 'Surat Masuk';
?>
<style>
.surat-index-page .badge { font-weight: 600; font-size:0.8rem; }
.surat-index-page .user-stat-card .label { font-weight: 600; text-transform:uppercase; }
.surat-index-page .table tbody td { vertical-align: middle; }
/* Fix button default border/background on table-action buttons */
.surat-index-page .table-action,
.surat-index-page button.table-action {
  border: none;
  background: none;
  padding: 0;
  cursor: pointer;
  outline: none;
  box-shadow: none;
}
.surat-index-page .table-action:focus,
.surat-index-page button.table-action:focus {
  outline: none;
  box-shadow: none;
}
/* Add missing teal variant */
.surat-index-page .table-action-teal:hover { color: #14b8a6; }
</style>
<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>
<div class="container-fluid mt--6 user-module-page surat-index-page">
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid">
              <div class="user-stat-card module-stat-card user-stat-total"><div class="info"><span class="label">Total Indeks</span><span class="value"><?php echo $total; ?></span></div><div class="icon"><i class="fas fa-list"></i></div></div>
              <div class="user-stat-card module-stat-card user-stat-identitas"><div class="info"><span class="label">Perihal</span><span class="value"><?php echo $stat_perihal; ?></span></div><div class="icon"><i class="fas fa-file-alt"></i></div></div>
              <div class="user-stat-card module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Kategori</span><span class="value"><?php echo $stat_kategori; ?></span></div><div class="icon"><i class="fas fa-tags"></i></div></div>
              <div class="user-stat-card module-stat-card user-stat-belum"><div class="info"><span class="label">Jenis Surat</span><span class="value"><?php echo $stat_jenis; ?></span></div><div class="icon"><i class="fas fa-envelope"></i></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 user-table-header module-table-header">
      <div class="user-table-head-row module-header-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Referensi Indeks Surat <span class="badge badge-info ml-2">Surat Keluar</span></h4>
          <small class="text-muted">Kelola indeks, perihal, kategori, dan contoh penomoran.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Dashboard Surat"><i class="fas fa-arrow-left"></i></a>
          <?php if ($can_edit): ?>
          <button type="button" class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalIndex" title="Tambah"><i class="fas fa-plus"></i></button>
          <button type="button" class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalImportExcel" title="Import Excel"><i class="fas fa-file-excel"></i></button>
          <?php endif; ?>
          <button type="button" class="btn-mod btn-mod-info btn-export-index" title="Export Excel"><i class="fas fa-download"></i></button>
        </div>
      </div>
    </div>

    <div class="card-body pt-3">
      <div class="alert alert-info mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><strong>Contoh Penomoran:</strong> <code>0029/KPG.11.01-SMKN1PGL</code>. Kode sekolah: <code>SMKN1PGL</code>.</span>
      </div>
      <div class="table-responsive">
        <table class="table align-items-center table-flush table-striped" id="tableSuratIndex" width="100%">
          <thead class="thead-light">
            <tr><th class="text-center" style="width:10px">No</th><th>Indeks</th><th>Perihal</th><th>Kategori</th><th>Jenis Surat</th><th>Contoh Nomor</th><th class="text-center" style="width:110px">Aksi</th></tr>
          </thead>
          <tbody>
            <?php
            $r = $connection->query("SELECT * FROM surat_index ORDER BY indeks ASC, id ASC");
            $no = 1;
            if ($r && $r->num_rows > 0) {
              while ($row = $r->fetch_assoc()) {
            ?>
            <tr>
              <td class="text-center"><?php echo $no++; ?></td>
              <td><code><?php echo htmlspecialchars($row['indeks']); ?></code></td>
              <td><strong><?php echo htmlspecialchars($row['perihal']); ?></strong></td>
              <td><span class="badge badge-primary"><?php echo htmlspecialchars($row['kategori']); ?></span></td>
              <td><span class="badge badge-<?php echo $row['jenis_surat'] === 'Surat Masuk' ? 'success' : 'info'; ?>"><?php echo htmlspecialchars($row['jenis_surat']); ?></span></td>
              <td class="text-nowrap"><code><?php echo htmlspecialchars($row['contoh_nomor']); ?></code></td>
              <td class="text-center">
                <button class="table-action table-action-warning btn-edit-index" data-id="<?php echo $row['id']; ?>" title="Edit" data-toggle="tooltip"><i class="fas fa-edit"></i></button>
                <?php if ($can_del): ?>
                <button class="table-action table-action-danger btn-delete-index" data-id="<?php echo $row['id']; ?>" title="Hapus" data-toggle="tooltip"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
            <?php } } else { echo '<tr class="dt-empty"><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Belum ada data referensi.</td></tr>'; } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Index -->
<div class="modal fade" id="modalIndex" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header bg-primary"><h5 class="modal-title text-white" id="modalIndexTitle"><i class="fas fa-plus mr-2"></i>Tambah Indeks</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="formIndex" method="post">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="id" id="f_id" value="0">
      <div class="modal-body">
        <div class="form-group"><label class="font-weight-bold">Indeks <span class="text-danger">*</span></label>
          <input class="form-control" name="indeks" id="f_indeks" placeholder="KPG.11.01" required></div>
        <div class="form-group"><label class="font-weight-bold">Perihal <span class="text-danger">*</span></span></label>
          <input class="form-control" name="perihal" id="f_perihal" placeholder="Surat Keterangan Aktif Sekolah" required></div>
        <div class="form-group"><label class="font-weight-bold">Kategori</label>
          <div class="input-group">
            <select class="form-control" name="kategori" id="f_kategori">
              <option value="">-- Pilih / ketik baru --</option>
              <?php foreach ($kategori_list as $k): ?>
              <option value="<?php echo $k; ?>"><?php echo $k; ?></option>
              <?php endforeach; ?>
            </select>
            <div class="input-group-append"><button type="button" class="btn btn-outline-secondary btn-add-kategori" title="Tambah kategori baru"><i class="fas fa-plus"></i></button></div>
          </div>
        </div>
        <div class="form-group"><label class="font-weight-bold">Jenis Surat</label>
          <div class="input-group">
            <select class="form-control" name="jenis_surat" id="f_jenis">
              <?php foreach ($jenis_list as $j): ?>
              <option value="<?php echo $j; ?>" <?php echo $j === 'Surat Keluar' ? 'selected' : ''; ?>><?php echo $j; ?></option>
              <?php endforeach; ?>
            </select>
            <div class="input-group-append"><button type="button" class="btn btn-outline-secondary btn-add-jenis" title="Tambah jenis surat baru"><i class="fas fa-plus"></i></button></div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button></div>
    </form>
  </div></div>
</div>

<!-- Modal Import Excel -->
<div class="modal fade modal-import" id="modalImportExcel" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document"><div class="modal-content">
    <div class="modal-header bg-teal"><h5 class="modal-title text-white"><i class="fas fa-file-excel mr-2"></i>Import Excel Indeks Surat</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <form class="form-import" id="formImportExcel" enctype="multipart/form-data" action="javascript:void(0);" autocomplete="off">
      <div class="modal-body">
        <div class="alert alert-info alert-dismissible fade show"><span class="alert-text">Upload file Excel (.xlsx) dengan kolom: <strong>Indeks, Perihal, Kategori, Jenis Surat</strong>.</span><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
        <div class="form-group"><label class="font-weight-bold">File Excel</label><input type="file" class="form-control" name="file_excel" accept=".xlsx" required></div>
        <a href="#" class="btn btn-sm btn-outline-secondary" id="downloadTemplateExcel"><i class="fas fa-download mr-1"></i>Unduh Template</a>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="fas fa-upload mr-1"></i>Import</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
    </form>
  </div></div>
</div>

<?php if ($can_edit): ?>
<script>var suratIndexCanEdit = true;</script>
<?php endif; ?>


