<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
$modul_id = 54;
include __DIR__ . '/../check_role.php';
if (!$has_access) { hak_akses(); return; }
$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
$can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

$indeks_list = [];
$r_i = $connection->query("SELECT id, indeks, perihal, kategori, contoh_nomor FROM surat_index ORDER BY indeks ASC");
if ($r_i) while ($i = $r_i->fetch_assoc()) $indeks_list[] = $i;

// Ambil daftar template yang punya variabel_tag untuk dropdown
$template_list = [];
$r_t = $connection->query("SELECT st.id, st.indeks_id, st.indeks_surat, st.variabel_tag, st.drive_file_id, si.perihal FROM surat_template st JOIN surat_index si ON st.indeks_id=si.id WHERE st.variabel_tag IS NOT NULL AND st.variabel_tag != '' ORDER BY st.indeks_surat ASC");
if ($r_t) while ($t = $r_t->fetch_assoc()) $template_list[] = $t;

$total_surat = 0; $total_draf = 0; $total_terkirim = 0;
$r = $connection->query("SELECT COUNT(*) c FROM surat_keluar"); if ($r) $total_surat = (int)$r->fetch_row()[0];
$r = $connection->query("SELECT COUNT(*) c FROM surat_keluar WHERE status='Draf'"); if ($r) $total_draf = (int)$r->fetch_row()[0];
$r = $connection->query("SELECT COUNT(*) c FROM surat_keluar WHERE status='Terkirim'"); if ($r) $total_terkirim = (int)$r->fetch_row()[0];
?>
<style>
/* ─── Surat Keluar — Simple Recording ─── */
.sk-form-card .form-group { margin-bottom:12px; }
.sk-form-card label { font-weight:600; font-size:13px; color:#344767; margin-bottom:3px; }
.sk-form-card .row-form { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media (max-width:640px) { .sk-form-card .row-form { grid-template-columns:1fr; } }
.sk-gen-form .form-group { margin-bottom:10px; }
.sk-gen-form label { font-weight:600; font-size:13px; color:#344767; margin-bottom:2px; }
.sk-gen-form .row-dynamic { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
@media (max-width:640px) { .sk-gen-form .row-dynamic { grid-template-columns:1fr; } }
.sk-preview-frame { width:100%; height:600px; border:1px solid #dee2e6; border-radius:6px; background:#fff; }
.sk-preview-placeholder { display:flex; align-items:center; justify-content:center; height:600px; color:#adb5bd; flex-direction:column; gap:12px; }
.sk-preview-placeholder i { font-size:48px; }
</style>

<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>
<div class="container-fluid mt--6 user-module-page surat-keluar-page sk-container">

  <!-- Stats -->
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid">
              <div class="user-stat-card module-stat-card user-stat-total">
                <div class="info"><span class="label">Total Surat</span><span class="value"><?php echo $total_surat; ?></span></div>
                <div class="icon"><i class="fas fa-envelope"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-identitas">
                <div class="info"><span class="label">Draf</span><span class="value"><?php echo $total_draf; ?></span></div>
                <div class="icon"><i class="fas fa-pen"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-belum-sesuai">
                <div class="info"><span class="label">Terkirim</span><span class="value"><?php echo $total_terkirim; ?></span></div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-belum">
                <div class="info"><span class="label">Template Indeks</span><span class="value"><?php echo count($indeks_list); ?></span></div>
                <div class="icon"><i class="fas fa-list"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Daftar Surat -->
  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 user-table-header module-table-header">
      <div class="user-table-head-row module-header-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Surat Keluar</h4>
          <small class="text-muted">Riwayat dan daftar surat yang telah dibuat.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <button type="button" class="btn-mod btn-mod-primary btn-baru-surat" title="Buat Surat Baru"><i class="fas fa-plus"></i></button>
          <button type="button" class="btn-mod btn-mod-info btn-export-surat-keluar" title="Export Excel"><i class="fas fa-file-excel"></i></button>
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Kembali"><i class="fas fa-arrow-left"></i></a>
        </div>
      </div>
    </div>
    <div class="card-body pt-3">
      <div class="table-responsive">
        <table class="table align-items-center table-flush table-striped surat-keluar-table" width="100%">
          <thead class="thead-light">
            <tr><th class="text-center">No</th><th>No. Surat</th><th>Indeks</th><th>Perihal</th><th>Tanggal</th><th>Status</th><th class="text-center">Link Dokumen</th><th class="text-center">Aksi</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ===== MODAL 1: Buat Surat Baru / Edit Surat ===== -->
<div class="modal fade" id="modalBuatSurat" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBuatTitle">Buat Surat Baru</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form id="formSurat" method="post">
        <input type="hidden" name="id" id="fId" value="0">
        <input type="hidden" name="no_surat" id="fNoSurat">
        <input type="hidden" name="status" id="fStatus" value="Draf">
        <div class="modal-body">
          <div class="form-group">
            <label>Indeks Surat</label>
            <select name="indeks_id" id="fIndeks" class="form-control" required>
              <option value="">— Pilih —</option>
              <?php foreach ($indeks_list as $ix): ?>
              <option value="<?php echo $ix['id']; ?>" data-indeks="<?php echo htmlspecialchars($ix['indeks']); ?>">
                <?php echo htmlspecialchars($ix['indeks'] . ' — ' . $ix['perihal']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Nomor Surat</label>
            <div class="input-group">
              <input type="text" id="fNoSuratDisplay" class="form-control" readonly placeholder="Otomatis">
              <div class="input-group-append">
                <button type="button" class="btn btn-info btn-gen-nomor" title="Generate Nomor"><i class="fas fa-sync-alt"></i></button>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Tanggal Surat</label>
            <input type="date" name="tgl_surat" id="fTglSurat" class="form-control" value="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="form-group">
            <label>Perihal</label>
            <input type="text" name="perihal" id="fPerihal" class="form-control" placeholder="Perihal surat" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="btnSimpan"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== MODAL 2: Generate PDF ===== -->
<div class="modal fade" id="modalGenerateSurat" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-pdf mr-1"></i> Generate Surat dari Template</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="font-weight-bold">Nomor Surat</label>
            <input type="text" id="genNoSurat" class="form-control" readonly>
          </div>
          <div class="col-md-6">
            <label class="font-weight-bold">Template</label>
            <select id="genTemplateSelect" class="form-control">
              <option value="">— Pilih Template —</option>
              <?php foreach ($template_list as $tmpl):
                $tags_count = !empty($tmpl['variabel_tag']) ? count(array_filter(array_map('trim', explode(',', $tmpl['variabel_tag'])))) : 0;
              ?>
              <option value="<?php echo $tmpl['id']; ?>" data-indeks-id="<?php echo $tmpl['indeks_id']; ?>" data-indeks="<?php echo htmlspecialchars($tmpl['indeks_surat']); ?>" data-tags-count="<?php echo $tags_count; ?>">
                <?php echo htmlspecialchars($tmpl['indeks_surat'] . ' — ' . $tmpl['perihal'] . ' (' . $tags_count . ' variabel)'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        
        <div class="row">
          <!-- Kiri: Form Variabel -->
          <div class="col-md-5 border-right">
            <h6 class="font-weight-bold"><i class="fas fa-edit mr-1"></i> Form Variabel</h6>
            <div id="genDynamicFields" class="pr-2" style="max-height: 400px; overflow-y: auto;">
              <div class="text-center text-muted py-4">
                <i class="fas fa-arrow-left mb-2" style="font-size:32px;"></i>
                <p class="mb-0">Pilih template untuk menampilkan form variabel.</p>
              </div>
            </div>
          </div>
          
          <!-- Kanan: Preview PDF -->
          <div class="col-md-7">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="font-weight-bold mb-0"><i class="fas fa-eye mr-1"></i> Preview PDF</h6>
              <div>
                <a id="btnDownloadPdf" class="btn btn-sm btn-danger d-none" href="#" download><i class="fas fa-download mr-1"></i> Download PDF</a>
              </div>
            </div>
            <div id="genPreviewArea" class="bg-light d-flex align-items-center justify-content-center" style="width:100%; height:400px; border:1px solid #dee2e6; border-radius:6px;">
              <div class="text-center text-muted" id="genPreviewPlaceholder">
                <i class="fas fa-file-pdf mb-2" style="font-size:32px;"></i>
                <p class="mb-0">PDF belum di-generate.</p>
              </div>
              <iframe id="pdfPreview" class="d-none" style="width:100%;height:100%;border:none;border-radius:6px;" src="about:blank"></iframe>
            </div>
          </div>
        </div>
        
      </div>
      <div class="modal-footer" id="genActions" style="display:none;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-success" id="btnGenerate"><i class="fas fa-file-pdf mr-1"></i> Generate PDF</button>
        <button type="button" class="btn btn-primary" id="btnSaveToSpreadsheet"><i class="fas fa-save mr-1"></i> Simpan &amp; Spreadsheet</button>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="genSuratId" value="0">
<input type="hidden" id="genPdfPath" value="">

<script>
var SK_CAN_EDIT = <?php echo $can_edit ? 'true' : 'false'; ?>;
</script>
