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
          <button type="button" class="btn-mod btn-mod-info btn-export-surat-keluar" title="Export Excel"><i class="fas fa-download"></i></button>
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Kembali"><i class="fas fa-arrow-left"></i></a>
        </div>
      </div>
    </div>
    <div class="card-body pt-3">
      <div class="table-responsive">
        <table class="table align-items-center table-flush table-striped surat-keluar-table" width="100%">
          <thead class="thead-light">
            <tr><th class="text-center">No</th><th>No. Surat</th><th>Indeks</th><th>Perihal</th><th>Tujuan</th><th>Tanggal</th><th>Status</th><th class="text-center">Aksi</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Form Pencatatan Surat -->
  <div class="card user-table-panel module-table-card sk-form-card pb-0 mb-3">
    <div class="card-header py-3 px-3 user-table-header module-table-header">
      <div class="user-table-head-row module-header-row" style="gap:10px;">
        <div>
          <h4 class="mb-1"><span id="frmTitle">Catat Surat Baru</span></h4>
          <small class="text-muted">Pencatatan dan penomoran surat keluar.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <span id="fStatusBadge" class="badge badge-warning mr-2 d-none">Draf</span>
        </div>
      </div>
    </div>

    <form id="formSurat" method="post">
      <input type="hidden" name="id" id="fId" value="0">
      <input type="hidden" name="no_surat" id="fNoSurat">
      <input type="hidden" name="lampiran" id="fLampiran">
      <input type="hidden" name="status" id="fStatus" value="Draf">

      <div class="card-body">
        <div class="row-form">
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
          <div class="form-group">
            <label>Tujuan / Penerima</label>
            <input type="text" name="tujuan" id="fTujuan" class="form-control" placeholder="Nama instansi / penerima">
          </div>
          <div class="form-group">
            <label>Lampiran</label>
            <input type="text" id="fLampiranDisplay" class="form-control" placeholder="-">
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label>Isi Surat (opsional — catatan saja)</label>
            <textarea name="isi_surat" id="fIsiSurat" class="form-control" rows="4" placeholder="Catatan isi surat jika diperlukan..."></textarea>
          </div>
          <div class="form-group" style="grid-column:1/-1; text-align:right;">
            <?php if ($can_edit): ?>
            <button type="submit" class="btn btn-primary" id="btnSimpan"><i class="fas fa-save mr-1"></i> Simpan</button>
            <button type="button" class="btn btn-secondary btn-batal" id="btnBatal"><i class="fas fa-times mr-1"></i> Batal</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
var SK_CAN_EDIT = <?php echo $can_edit ? 'true' : 'false'; ?>;
</script>
