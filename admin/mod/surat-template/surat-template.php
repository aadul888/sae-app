<?php
/**
 * MODUL: SURAT TEMPLATE — Template Surat dari Google Docs
 * Data disimpan di tabel surat_template.
 * Fitur: CRUD template dengan link Google Docs + variabel tag.
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
$modul_id = 131;
include __DIR__ . '/../check_role.php';
if (!$has_access) { hak_akses(); return; }

$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
$can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

$total = 0;
$r_total = $connection->query("SELECT COUNT(*) c FROM surat_template");
if ($r_total) $total = (int)$r_total->fetch_row()[0];

// Ambil daftar indeks dari surat_index untuk dropdown
$indeks_list = [];
$r_i = $connection->query("SELECT id, indeks, perihal, jenis_surat, contoh_nomor FROM surat_index ORDER BY indeks ASC");
if ($r_i) while ($i = $r_i->fetch_assoc()) $indeks_list[] = $i;

// User yg sedang login (dari login/user.php via index.php)
$userFullName = '';
$userFrontTitle = '';
$userBackTitle = '';
if (isset($current_user)) {
  $userFrontTitle = isset($current_user['gelar_depan']) ? trim(strip_tags($current_user['gelar_depan'])) : '';
  $userFullName   = isset($current_user['fullname']) ? trim(strip_tags($current_user['fullname'])) : '';
  $userBackTitle  = isset($current_user['gelar_belakang']) ? trim(strip_tags($current_user['gelar_belakang'])) : '';
}
$nama_pembuat_auto = trim($userFrontTitle . ' ' . $userFullName . ' ' . $userBackTitle);
?>
<style>
/* Truncate variabel tag with click to expand */
.tag-truncate {
  display: inline-block;
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: pointer;
  vertical-align: middle;
}
.tag-truncate:hover {
  max-width: none;
  white-space: normal;
  word-break: break-word;
  overflow: visible;
  z-index: 10;
  position: relative;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  padding: 4px 8px;
  border-radius: 4px;
}
.tag-truncate.expanded {
  max-width: none;
  white-space: normal;
  word-break: break-word;
  overflow: visible;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  padding: 4px 8px;
  border-radius: 4px;
}
</style>
<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>
<div class="container-fluid mt--6 user-module-page surat-template-page">
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid">
              <div class="user-stat-card module-stat-card user-stat-total"><div class="info"><span class="label">Total Template</span><span class="value"><?php echo $total; ?></span></div><div class="icon"><i class="fas fa-file-alt"></i></div></div>
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
          <h4 class="mb-1">Template Surat <span class="badge badge-info ml-2">Google Docs</span></h4>
          <small class="text-muted">Dokumen template surat dari Google Docs.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Dashboard Surat"><i class="fas fa-arrow-left"></i></a>
          <?php if ($can_edit): ?>
          <button type="button" class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalTemplate" title="Tambah Template"><i class="fas fa-plus"></i></button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card-body pt-3">
      <?php
      // Cek status kredensial Google
      $qs = $connection->query("SELECT google_credentials FROM surat_setting WHERE id=1 LIMIT 1");
      $set = $qs ? $qs->fetch_assoc() : null;
      $creds_ok = $set && !empty($set['google_credentials']) && file_exists(__DIR__ . '/../../../content/' . $set['google_credentials']);
      ?>
      <?php if (!$creds_ok): ?>
      <div class="alert alert-warning d-flex align-items-center">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <span>Kredensial Google API belum dikonfigurasi. <a href="./surat-setting" class="alert-link">Atur di Pengaturan Surat</a> agar bisa mengambil konten dari Google Docs.</span>
      </div>
      <?php else: ?>
      <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="fas fa-info-circle mr-1"></i> Template diambil dari Google Docs. Masukkan link dokumen untuk menyimpan konten HTML-nya.</span>
      </div>
      <?php endif; ?>
      <div class="table-responsive">
        <table class="table align-items-center table-flush table-striped" id="tableSuratTemplate" width="100%">
          <thead class="thead-light">
            <tr>
              <th class="text-center" style="width:10px">No</th>
              <th>Indeks Surat</th>
              <th>Jenis Surat</th>
              <th>Nama Pembuat</th>
              <th>Variabel Tag</th>
              <th>Diperbarui</th>
              <th>Link Dokumen</th>
              <th class="text-center" style="width:130px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $r = $connection->query("SELECT * FROM surat_template ORDER BY indeks_surat ASC, id ASC");
            $no = 1;
            if ($r && $r->num_rows > 0) {
              while ($row = $r->fetch_assoc()) {
                $updated = !empty($row['updated_at']) ? date('d/m/Y H:i', strtotime($row['updated_at'])) : '-';
                $link = $row['link_dokumen'] ?? '';
                $short_link = $link ? (mb_strlen($link) > 50 ? mb_substr($link, 0, 50) . '...' : $link) : '-';
                $drive_file_id = $row['drive_file_id'] ?? '';
                $variabel_tag = $row['variabel_tag'] ?? '';
                // Truncate variabel_tag for display, full version in data-full
                $display_tag = $variabel_tag ? (mb_strlen($variabel_tag) > 60 ? mb_substr($variabel_tag, 0, 60) . '…' : $variabel_tag) : '-';
            ?>
            <tr>
              <td class="text-center"><?php echo $no++; ?></td>
              <td><strong><?php echo htmlspecialchars($row['indeks_surat']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['jenis_surat'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($row['nama_pembuat'] ?? '-'); ?></td>
              <td>
                <?php if ($variabel_tag): ?>
                <span class="tag-truncate" data-full="<?php echo htmlspecialchars($variabel_tag); ?>" title="Klik untuk lihat lengkap">
                  <?php echo htmlspecialchars($display_tag); ?>
                </span>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?php echo $updated; ?></td>
              <td>
                <?php if ($link): ?>
                <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="link-doc-preview" title="<?php echo htmlspecialchars($link); ?>">
                  <i class="fab fa-google-drive text-primary mr-1"></i><?php echo htmlspecialchars($short_link); ?>
                </a>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <button class="table-action table-action-info btn-preview-template" data-docid="<?php echo htmlspecialchars($drive_file_id); ?>" data-indeks="<?php echo htmlspecialchars($row['indeks_surat']); ?>"><i class="fas fa-eye"></i></button>
                <button class="table-action table-action-teal btn-scan-tags" data-id="<?php echo $row['id']; ?>"><i class="fas fa-tags"></i></button>
                <?php if ($can_edit): ?>
                <button class="table-action table-action-warning btn-edit-template" data-id="<?php echo $row['id']; ?>"><i class="fas fa-edit"></i></button>
                <?php endif; ?>
                <?php if ($can_del): ?>
                <button class="table-action table-action-danger btn-delete-template" data-id="<?php echo $row['id']; ?>"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
            <?php } } else { echo '<tr class="dt-empty"><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Belum ada template surat.</td></tr>'; } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Template -->
<div class="modal fade" id="modalTemplate" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header bg-primary"><h5 class="modal-title text-white" id="modalTemplateTitle"><i class="fas fa-plus mr-2"></i>Tambah Template</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="formTemplate" method="post">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="id" id="f_id" value="0">
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label class="font-weight-bold">Indeks Surat <span class="text-danger">*</span></label>
              <select class="form-control" name="indeks_id" id="f_indeks_id" required>
                <option value="">-- Pilih Indeks --</option>
                <?php foreach ($indeks_list as $ix): ?>
                <option value="<?php echo $ix['id']; ?>" data-jenis="<?php echo htmlspecialchars($ix['jenis_surat']); ?>">
                  <?php echo htmlspecialchars($ix['indeks'] . ' — ' . $ix['perihal']); ?>
                </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Pilih indeks yang sudah dibuat di modul Indeks Surat.</small></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label class="font-weight-bold">Jenis Surat</label>
              <input class="form-control" name="jenis_surat" id="f_jenis_surat" placeholder="Terisi otomatis dari indeks" readonly></div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label class="font-weight-bold">Nama Pembuat</label>
              <input class="form-control" name="nama_pembuat" id="f_nama_pembuat" value="<?php echo htmlspecialchars($nama_pembuat_auto); ?>" readonly>
              <small class="text-muted">Terisi otomatis dari user login.</small></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label class="font-weight-bold">Link Dokumen Google Docs <span class="text-danger">*</span></label>
              <div class="input-group">
                <input class="form-control" name="link_dokumen" id="f_link_dokumen" placeholder="Masukkan ID atau link Google Docs" required>
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-info btn-help-docid" title="Cara dapatkan ID"><i class="fas fa-question-circle"></i></button>
                </div>
              </div>
              <small class="text-muted">Masukkan ID dokumen (contoh: <code>1NlfuszmjNCfDrMZu1zkOha8R616qlStE</code>) atau link lengkap Google Docs.</small></div>
          </div>
        </div>
        <div class="form-group"><label class="font-weight-bold">Deskripsi</label>
          <textarea class="form-control" name="deskripsi" id="f_deskripsi" rows="2" placeholder="Deskripsi singkat tentang template ini (opsional)"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Modal Hasil Scan Tags -->
<div class="modal fade" id="modalScanResult" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header bg-success"><h5 class="modal-title text-white"><i class="fas fa-tags mr-2"></i>Scan {{tags}} — <span id="scanResultLabel"></span></h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
      <div id="scanResultContent">
        <p class="text-center text-muted py-4">Memindai...</p>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
  </div></div>
</div>

<!-- Modal Preview / Review Google Docs (Embedded Viewer) -->
<div class="modal fade" id="modalViewDoc" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
    <div class="modal-header bg-info"><h5 class="modal-title text-white"><i class="fas fa-file-alt mr-2"></i>Review Dokumen: <span id="viewDocLabel"></span></h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body p-0" style="height:80vh;">
      <iframe id="viewDocIframe" src="about:blank" style="width:100%;height:100%;border:none;" allowfullscreen></iframe>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
  </div></div>
</div>

<!-- END surat-template.php -->

<?php if ($can_edit): ?>
<script>var suratTemplateCanEdit = true;</script>
<?php endif; ?>
<script src="./mod/surat-template/scripts.js?ver=2"></script>
