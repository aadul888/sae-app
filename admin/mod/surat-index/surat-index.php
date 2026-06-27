<?php
/**
 * MODUL: SURAT INDEX — Referensi Indeks & Penomoran Surat Keluar
 * Data disimpan di tabel surat_index.
 * Fitur: CRUD, Import Excel, Upload Template Word.
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

$main_url = rtrim(($base_url ?? './'), '/');
?>
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
          <small class="text-muted">Kelola indeks, perihal, kategori, dan contoh penomoran. Referensi ini digunakan untuk penomoran otomatis surat keluar.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Dashboard Surat"><i class="fas fa-arrow-left"></i></a>
          <button type="button" class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalIndex" title="Tambah"><i class="fas fa-plus"></i></button>
          <button type="button" class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalImportExcel" title="Import Excel"><i class="fas fa-file-excel"></i></button>
          <button type="button" class="btn-mod btn-mod-warn" data-toggle="modal" data-target="#modalUploadTemplate" title="Upload Template Word"><i class="fas fa-file-word"></i></button>
          <button type="button" class="btn-mod btn-mod-info btn-export-index" title="Export Excel"><i class="fas fa-download"></i></button>
        </div>
      </div>
    </div>

    <div class="card-body pt-3">
      <div class="alert alert-info mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><strong>Contoh Penomoran:</strong> <code>0029/KPG.11.01-SMKN1PGL</code>. Kode sekolah: <code>SMKN1PGL</code>.</span>
        <form method="get" action="./surat-index" class="form-inline">
          <input type="hidden" name="mod" value="surat-index">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari indeks/perihal..." value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES); ?>">
          <button class="btn btn-sm btn-primary ml-2"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table align-items-center table-flush table-striped" id="tableSuratIndex" width="100%">
          <thead class="thead-light">
            <tr><th class="text-center" style="width:10px">No</th><th>Indeks</th><th>Perihal</th><th>Kategori</th><th>Jenis Surat</th><th>Contoh Nomor</th><th>Template</th><th class="text-center" style="width:110px">Aksi</th></tr>
          </thead>
          <tbody>
            <?php
            $search = trim($_GET['search'] ?? '');
            $r = $connection->query("SELECT * FROM surat_index ORDER BY indeks ASC");
            $no = 1;
            if ($r && $r->num_rows > 0) {
              while ($row = $r->fetch_assoc()) {
                if ($search && stripos($row['indeks'].$row['perihal'], $search) === false) continue;
                $has_template = !empty($row['format_template']);
            ?>
            <tr>
              <td class="text-center"><?php echo $no++; ?></td>
              <td><code><?php echo htmlspecialchars($row['indeks']); ?></code></td>
              <td><strong><?php echo htmlspecialchars($row['perihal']); ?></strong></td>
              <td><span class="badge badge-primary"><?php echo htmlspecialchars($row['kategori']); ?></span></td>
              <td><span class="badge badge-<?php echo $row['jenis_surat'] === 'Surat Masuk' ? 'success' : 'info'; ?>"><?php echo htmlspecialchars($row['jenis_surat']); ?></span></td>
              <td><code><?php echo htmlspecialchars($row['contoh_nomor']); ?></code>
                <button class="btn btn-sm btn-link btn-copy-index py-0" data-value="<?php echo htmlspecialchars($row['contoh_nomor'], ENT_QUOTES); ?>" title="Copy"><i class="fas fa-copy text-muted"></i></button>
              </td>
              <td class="text-center"><?php echo $has_template ? '<span class="text-success"><i class="fas fa-check-circle"></i></span>' : '<span class="text-muted">-</span>'; ?></td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-primary btn-edit-index" data-id="<?php echo $row['id']; ?>" title="Edit"><i class="fas fa-edit"></i></button>
                <?php if ($can_del): ?>
                <button class="btn btn-sm btn-outline-danger btn-delete-index" data-id="<?php echo $row['id']; ?>" title="Hapus"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
            <?php } } else { echo '<tr class="dt-empty"><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Belum ada data referensi.</td></tr>'; } ?>
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
        <div class="form-group"><label>Indeks <span class="text-danger">*</span></label><input class="form-control" name="indeks" id="f_indeks" placeholder="KPG.11.01" required></div>
        <div class="form-group"><label>Perihal <span class="text-danger">*</span></label><input class="form-control" name="perihal" id="f_perihal" placeholder="Surat Keterangan Aktif Sekolah" required></div>
        <div class="form-row">
          <div class="form-group col-md-6"><label>Kategori</label><select class="form-control" name="kategori" id="f_kategori"><option>Tata Usaha</option><option>Akademik</option><option>Kesiswaan</option><option>Hubin</option><option>Sarpras</option></select></div>
          <div class="form-group col-md-6"><label>Jenis Surat</label><select class="form-control" name="jenis_surat" id="f_jenis"><option value="Surat Keluar">Surat Keluar</option><option value="Surat Masuk">Surat Masuk</option></select></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button></div>
    </form>
  </div></div>
</div>

<!-- Modal Import Excel -->
<div class="modal fade modal-import" id="modalImportExcel" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header bg-teal"><h5 class="modal-title text-white"><i class="fas fa-file-excel mr-2"></i>Import Excel Indeks Surat</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <form class="form-import" id="formImportExcel" enctype="multipart/form-data" action="javascript:void(0);" autocomplete="off">
      <div class="modal-body">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <span class="alert-text">Upload file Excel (.xlsx) dengan kolom: <strong>Indeks, Perihal, Kategori, Jenis Surat</strong>.</span>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="form-group"><label>File Excel</label><input type="file" class="form-control" name="file_excel" accept=".xlsx" required></div>
        <a href="#" class="btn btn-sm btn-outline-secondary" id="downloadTemplateExcel"><i class="fas fa-download mr-1"></i>Unduh Template Excel</a>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="fas fa-upload mr-1"></i>Import</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
    </form>
  </div></div>
</div>

<!-- Modal Upload Template Word -->
<div class="modal fade" id="modalUploadTemplate" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header bg-warning"><h5 class="modal-title text-white"><i class="fas fa-file-word mr-2"></i>Upload Template Word (Surat Keluar)</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="formUploadTemplate" enctype="multipart/form-data" method="post">
      <input type="hidden" name="action" value="upload_template">
      <div class="modal-body">
        <div class="alert alert-warning">Upload file .docx template surat keluar. Gunakan placeholder <code>{{kepala_sekolah}}</code>, <code>{{nama_sekolah}}</code>, <code>{{alamat_sekolah}}</code>, <code>{{tanggal}}</code>, <code>{{perihal}}</code>, <code>{{no_surat}}</code>.</div>
        <div class="form-group"><label>Indeks Tujuan</label><select class="form-control" name="indeks_id" required>
          <option value="">-- Pilih indeks --</option>
          <?php $r_i = $connection->query("SELECT id, indeks, perihal FROM surat_index ORDER BY indeks ASC"); if ($r_i) while ($i = $r_i->fetch_assoc()) echo '<option value="'.$i['id'].'">'.htmlspecialchars($i['indeks'].' — '.$i['perihal']).'</option>'; ?>
        </select></div>
        <div class="form-group"><label>File Template (.docx)</label><input type="file" class="form-control" name="file_template" accept=".docx" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button><button type="submit" class="btn btn-warning"><i class="fas fa-upload mr-1"></i>Upload</button></div>
    </form>
  </div></div>
</div>

<?php if ($can_edit): ?>
<script>
var suratIndexCanEdit = true;
</script>
<?php endif; ?>
