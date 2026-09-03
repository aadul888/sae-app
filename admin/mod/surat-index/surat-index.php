<?php
/**
 * MODUL: SURAT INDEX — Referensi Indeks & Penomoran Surat
 * Data disimpan di tabel surat_index, surat_kategori, surat_jenis.
 * Fitur: CRUD Indeks, CRUD Kategori, CRUD Jenis Surat, Import Excel.
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
$r_kategori = $connection->query("SELECT COUNT(*) c FROM surat_kategori"); if ($r_kategori) $stat_kategori = (int)$r_kategori->fetch_row()[0];
$r_jenis = $connection->query("SELECT COUNT(*) c FROM surat_jenis"); if ($r_jenis) $stat_jenis = (int)$r_jenis->fetch_row()[0];

// Ambil daftar kategori dari tabel surat_kategori untuk dropdown
$kategori_list = [];
$rk = $connection->query("SELECT nama_kategori FROM surat_kategori ORDER BY nama_kategori ASC");
if ($rk) while ($k = $rk->fetch_assoc()) $kategori_list[] = htmlspecialchars($k['nama_kategori']);

// Ambil daftar jenis surat dari tabel surat_jenis
$jenis_list = [];
$rj = $connection->query("SELECT nama_jenis FROM surat_jenis ORDER BY nama_jenis ASC");
if ($rj) while ($j = $rj->fetch_assoc()) $jenis_list[] = htmlspecialchars($j['nama_jenis']);
?>
<!-- CSS moved to global style.css -->
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

  <!-- Nav Tabs -->
  <ul class="nav nav-tabs nav-fill mb-3" id="suratTab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="tab-referensi-link" data-toggle="tab" href="#tab-referensi" role="tab">
        <i class="fas fa-list mr-1"></i>Referensi
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="tab-kategori-link" data-toggle="tab" href="#tab-kategori" role="tab">
        <i class="fas fa-tags mr-1"></i>Kategori
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="tab-jenis-link" data-toggle="tab" href="#tab-jenis" role="tab">
        <i class="fas fa-envelope mr-1"></i>Jenis Surat
      </a>
    </li>
  </ul>

  <div class="tab-content" id="suratTabContent">

    <!-- ===== TAB 1: REFERENSI INDEKS ===== -->
    <div class="tab-pane fade show active" id="tab-referensi" role="tabpanel">
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
                <tr><th class="text-center" style="width:10px">No</th><th>Indeks</th><th>Perihal</th><th>Kategori</th><th>Jenis Surat</th><th>Contoh Nomor</th><th class="text-center">Template</th><th class="text-center" style="width:140px">Aksi</th></tr>
              </thead>
              <tbody>
                <?php
                $r = $connection->query("
                  SELECT si.*,
                    (SELECT COUNT(*) FROM surat_template st WHERE st.indeks_id = si.id) AS template_count
                  FROM surat_index si
                  ORDER BY si.indeks ASC, si.id ASC
                ");
                $no = 1;
                if ($r && $r->num_rows > 0) {
                  while ($row = $r->fetch_assoc()) {
                    $has_template = ($row['template_count'] ?? 0) > 0;
                ?>
                <tr>
                  <td class="text-center"><?php echo $no++; ?></td>
                  <td><code><?php echo htmlspecialchars($row['indeks']); ?></code></td>
                  <td><strong><?php echo htmlspecialchars($row['perihal']); ?></strong></td>
                  <td><span class="badge badge-primary"><?php echo htmlspecialchars($row['kategori']); ?></span></td>
                  <td><span class="badge badge-<?php echo $row['jenis_surat'] === 'Surat Masuk' ? 'success' : 'info'; ?>"><?php echo htmlspecialchars($row['jenis_surat']); ?></span></td>
                  <td class="text-nowrap"><code><?php echo htmlspecialchars($row['contoh_nomor']); ?></code></td>
                  <td class="text-center">
                    <?php if ($has_template): ?>
                    <span class="text-success" title="Template terhubung"><i class="fas fa-check-circle"></i></span>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <button class="table-action table-action-warning btn-edit-index" data-id="<?php echo $row['id']; ?>"><i class="fas fa-edit"></i></button>
                    <?php if ($can_del): ?>
                    <button class="table-action table-action-danger btn-delete-index" data-id="<?php echo $row['id']; ?>"><i class="fas fa-trash"></i></button>
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

    <!-- ===== TAB 2: KATEGORI ===== -->
    <div class="tab-pane fade" id="tab-kategori" role="tabpanel">
      <div class="card user-table-panel module-table-card pb-2">
        <div class="card-header py-3 px-3 user-table-header module-table-header">
          <div class="user-table-head-row module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Daftar Kategori Surat</h4>
              <small class="text-muted">Kelola kategori surat yang digunakan pada indeks.</small>
            </div>
            <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
              <?php if ($can_edit): ?>
              <button type="button" class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalKategori" title="Tambah Kategori"><i class="fas fa-plus"></i></button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="card-body pt-3">
          <div class="table-responsive">
            <table class="table align-items-center table-flush table-striped" id="tableKategori" width="100%">
              <thead class="thead-light">
                <tr><th class="text-center" style="width:10px">No</th><th>Nama Kategori</th><th>Dibuat</th><th class="text-center" style="width:120px">Aksi</th></tr>
              </thead>
              <tbody>
                <?php
                $rk = $connection->query("SELECT * FROM surat_kategori ORDER BY nama_kategori ASC");
                $no_k = 1;
                if ($rk && $rk->num_rows > 0) {
                  while ($k = $rk->fetch_assoc()) {
                    $created = !empty($k['created_at']) ? date('d/m/Y H:i', strtotime($k['created_at'])) : '-';
                ?>
                <tr>
                  <td class="text-center"><?php echo $no_k++; ?></td>
                  <td><strong><?php echo htmlspecialchars($k['nama_kategori']); ?></strong></td>
                  <td><?php echo $created; ?></td>
                  <td class="text-center">
                    <button class="table-action table-action-warning btn-edit-kategori" data-id="<?php echo $k['id']; ?>" data-nama="<?php echo htmlspecialchars($k['nama_kategori']); ?>"><i class="fas fa-edit"></i></button>
                    <?php if ($can_del): ?>
                    <button class="table-action table-action-danger btn-delete-kategori" data-id="<?php echo $k['id']; ?>"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php } } else { echo '<tr class="dt-empty"><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Belum ada kategori.</td></tr>'; } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== TAB 3: JENIS SURAT ===== -->
    <div class="tab-pane fade" id="tab-jenis" role="tabpanel">
      <div class="card user-table-panel module-table-card pb-2">
        <div class="card-header py-3 px-3 user-table-header module-table-header">
          <div class="user-table-head-row module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Daftar Jenis Surat</h4>
              <small class="text-muted">Kelola jenis surat yang digunakan pada indeks.</small>
            </div>
            <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
              <?php if ($can_edit): ?>
              <button type="button" class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalJenis" title="Tambah Jenis Surat"><i class="fas fa-plus"></i></button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="card-body pt-3">
          <div class="table-responsive">
            <table class="table align-items-center table-flush table-striped" id="tableJenis" width="100%">
              <thead class="thead-light">
                <tr><th class="text-center" style="width:10px">No</th><th>Nama Jenis Surat</th><th>Dibuat</th><th class="text-center" style="width:120px">Aksi</th></tr>
              </thead>
              <tbody>
                <?php
                $rj = $connection->query("SELECT * FROM surat_jenis ORDER BY nama_jenis ASC");
                $no_j = 1;
                if ($rj && $rj->num_rows > 0) {
                  while ($j = $rj->fetch_assoc()) {
                    $created = !empty($j['created_at']) ? date('d/m/Y H:i', strtotime($j['created_at'])) : '-';
                ?>
                <tr>
                  <td class="text-center"><?php echo $no_j++; ?></td>
                  <td><strong><?php echo htmlspecialchars($j['nama_jenis']); ?></strong></td>
                  <td><?php echo $created; ?></td>
                  <td class="text-center">
                    <button class="table-action table-action-warning btn-edit-jenis" data-id="<?php echo $j['id']; ?>" data-nama="<?php echo htmlspecialchars($j['nama_jenis']); ?>"><i class="fas fa-edit"></i></button>
                    <?php if ($can_del): ?>
                    <button class="table-action table-action-danger btn-delete-jenis" data-id="<?php echo $j['id']; ?>"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php } } else { echo '<tr class="dt-empty"><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Belum ada jenis surat.</td></tr>'; } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /tab-content -->
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
              <option value="">-- Pilih --</option>
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
              <option value="">-- Pilih --</option>
              <?php foreach ($jenis_list as $j): ?>
              <option value="<?php echo $j; ?>"><?php echo $j; ?></option>
              <?php endforeach; ?>
            </select>
            <div class="input-group-append"><button type="button" class="btn btn-outline-secondary btn-add-jenis" title="Tambah jenis surat baru"><i class="fas fa-plus"></i></button></div>
          </div>
        </div>
        <div class="form-group"><label class="font-weight-bold">Google Doc ID</label>
          <div class="input-group">
            <input class="form-control" name="format_template" id="f_docid" placeholder="1ABCxyz...">
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-info btn-help-docid" title="Cara dapatkan ID"><i class="fas fa-question-circle"></i></button>
            </div>
          </div>
          <small class="text-muted">Masukkan ID Google Docs (string panjang dari URL), misal: <code>1ABCxyz123</code></small>
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

<!-- Modal Tambah/Edit Kategori -->
<div class="modal fade" id="modalKategori" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document"><div class="modal-content">
    <div class="modal-header bg-primary"><h5 class="modal-title text-white" id="modalKategoriTitle"><i class="fas fa-tag mr-2"></i>Tambah Kategori</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="f_kategori_id" value="0">
      <div class="form-group"><label class="font-weight-bold">Nama Kategori</label>
        <input class="form-control" id="f_nama_kategori" placeholder="Masukkan nama kategori baru" autocomplete="off"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      <button type="button" class="btn btn-primary" id="btnSimpanKategori"><i class="fas fa-save mr-1"></i>Simpan</button>
    </div>
  </div></div>
</div>

<!-- Modal Tambah/Edit Jenis Surat -->
<div class="modal fade" id="modalJenis" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document"><div class="modal-content">
    <div class="modal-header bg-primary"><h5 class="modal-title text-white" id="modalJenisTitle"><i class="fas fa-envelope mr-2"></i>Tambah Jenis Surat</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="f_jenis_id" value="0">
      <div class="form-group"><label class="font-weight-bold">Nama Jenis Surat</label>
        <input class="form-control" id="f_nama_jenis" placeholder="Masukkan nama jenis surat baru" autocomplete="off"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      <button type="button" class="btn btn-primary" id="btnSimpanJenis"><i class="fas fa-save mr-1"></i>Simpan</button>
    </div>
  </div></div>
</div>

<?php if ($can_edit): ?>
<script>var suratIndexCanEdit = true;</script>
<?php endif; ?>


