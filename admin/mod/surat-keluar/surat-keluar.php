<?php
/**
 * MODUL: SURAT KELUAR — Buat, kelola, cetak surat keluar.
 * Penomoran otomatis dari indeks, template Word dari database.
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
$modul_id = 54;
include __DIR__ . '/../check_role.php';
if (!$has_access) { hak_akses(); return; }

$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
$can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

// Generate nomor surat otomatis via AJAX, no pre-gen needed.

// Ambil semua indeks untuk dropdown
$indeks_list = [];
$r_i = $connection->query("SELECT id, indeks, perihal, kategori, contoh_nomor, format_template FROM surat_index WHERE jenis_surat='Surat Keluar' ORDER BY indeks ASC");
if ($r_i) while ($i = $r_i->fetch_assoc()) $indeks_list[] = $i;

// Ambil data referensi untuk template: kepsek, guru, murid
$kepsek = [];
$rk = $connection->query("SELECT a.fullname, a.gelar_depan, a.gelar_belakang, a.nip FROM admin a JOIN level l ON a.level_id=l.level_id WHERE l.level_nama='Kepala Sekolah' LIMIT 1");
if ($rk && $rk->num_rows > 0) $kepsek = $rk->fetch_assoc();

$sekolah = [];
$rs = $connection->query("SELECT site_name, site_alamat, site_kelurahan, site_kecamatan, site_kota, site_provinsi, site_kodepos, site_phone, site_email FROM setting LIMIT 1");
if ($rs) $sekolah = $rs->fetch_assoc();
$alamat_sekolah = trim(($sekolah['site_alamat'] ?? '') . ', ' . ($sekolah['site_kelurahan'] ?? '') . ', ' . ($sekolah['site_kecamatan'] ?? '') . ', ' . ($sekolah['site_kota'] ?? ''));
?>
<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>
<div class="container-fluid mt--6 user-module-page surat-keluar-page">
  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 user-table-header module-table-header">
      <div class="user-table-head-row module-header-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Surat Keluar</h4>
          <small class="text-muted">Buat surat keluar dengan penomoran otomatis, pilih template, dan cetak.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <a href="./surat" class="btn-mod btn-mod-secondary" title="Dashboard Surat"><i class="fas fa-arrow-left"></i></a>
          <?php if ($can_edit): ?>
          <button type="button" class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalBuatSurat" title="Buat Surat"><i class="fas fa-plus"></i></button>
          <?php endif; ?>
          <button type="button" class="btn-mod btn-mod-info btn-export-surat-keluar" title="Export Excel"><i class="fas fa-download"></i></button>
        </div>
      </div>
    </div>

    <div class="card-body pt-3">
      <?php if (!count($indeks_list)): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Belum ada referensi indeks surat keluar. Silakan isi dulu di <a href="./surat-index" class="alert-link">Referensi Surat</a>.</div>
      <?php endif; ?>
      <div class="table-responsive">
        <table class="table align-items-center table-flush table-striped surat-keluar-table" width="100%">
          <thead class="thead-light">
            <tr><th class="text-center" style="width:10px">No</th><th>No. Surat</th><th>Indeks</th><th>Perihal</th><th>Tujuan</th><th>Tanggal</th><th>Status</th><th class="text-center" style="width:130px">Aksi</th></tr>
          </thead>
          <tbody>
            <?php
            $q = $connection->query("SELECT sk.*, si.indeks FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id ORDER BY sk.id DESC LIMIT 50");
            $no = 1;
            if ($q && $q->num_rows > 0) {
              while ($sk = $q->fetch_assoc()) {
                $badge = $sk['status'] === 'Terkirim' ? 'success' : 'secondary';
            ?>
            <tr>
              <td class="text-center"><?php echo $no++; ?></td>
              <td><code><?php echo htmlspecialchars($sk['no_surat']); ?></code></td>
              <td><?php echo htmlspecialchars($sk['indeks'] ?? '-'); ?></td>
              <td><strong><?php echo htmlspecialchars(mb_substr($sk['perihal'], 0, 50)); ?></strong></td>
              <td><?php echo htmlspecialchars(mb_substr($sk['tujuan'], 0, 30)); ?></td>
              <td><small><?php echo date('d/m/Y', strtotime($sk['tgl_surat'] ?? $sk['created_at'])); ?></small></td>
              <td><span class="badge badge-<?php echo $badge; ?>"><?php echo htmlspecialchars($sk['status']); ?></span></td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-primary btn-cetak-surat" data-id="<?php echo $sk['id']; ?>" title="Cetak"><i class="fas fa-print"></i></button>
                <a href="./mod/surat-keluar/proses.php?action=download&id=<?php echo $sk['id']; ?>" class="btn btn-sm btn-outline-success" title="Download .docx"><i class="fas fa-file-word"></i></a>
                <?php if ($can_del): ?>
                <button class="btn btn-sm btn-outline-danger btn-delete-keluar" data-id="<?php echo $sk['id']; ?>" title="Hapus"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
            <?php }
            } else { echo '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Belum ada surat keluar.</td></tr>'; } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Buat Surat -->
<div class="modal fade" id="modalBuatSurat" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-primary"><h5 class="modal-title text-white"><i class="fas fa-file mr-2"></i>Buat Surat Keluar</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form id="formBuatSurat" method="post">
      <input type="hidden" name="action" value="buat">
      <div class="modal-body">
        <!-- Step 1: Pilih Indeks -->
        <div class="card bg-light mb-3">
          <div class="card-body py-2">
            <div class="form-group mb-2">
              <label class="font-weight-bold">Pilih Indeks / Jenis Surat</label>
              <select class="form-control" name="indeks_id" id="slcIndeks" required>
                <option value="">-- Pilih indeks --</option>
                <?php foreach ($indeks_list as $ix): ?>
                <option value="<?php echo $ix['id']; ?>" data-indeks="<?php echo htmlspecialchars($ix['indeks']); ?>" data-perihal="<?php echo htmlspecialchars($ix['perihal']); ?>" data-kategori="<?php echo htmlspecialchars($ix['kategori']); ?>" data-contoh="<?php echo htmlspecialchars($ix['contoh_nomor']); ?>" data-template="<?php echo htmlspecialchars($ix['format_template'] ?? ''); ?>"><?php echo htmlspecialchars($ix['indeks'] . ' — ' . $ix['perihal'] . ' (' . $ix['kategori'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Step 2: Auto nomor & detail -->
        <div id="detailSuratWrapper" style="display:none">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group"><label>No. Surat <span class="text-danger">*</span></label><input class="form-control" name="no_surat" id="fNoSurat" readonly></div>
            </div>
            <div class="col-md-3">
              <div class="form-group"><label>Tanggal</label><input type="date" class="form-control" name="tgl_surat" id="fTglSurat" value="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="col-md-3">
              <div class="form-group"><label>Kategori</label><input class="form-control" id="fKategori" readonly></div>
            </div>
          </div>
          <div class="form-group"><label>Perihal <span class="text-danger">*</span></label><input class="form-control" name="perihal" id="fPerihal" required></div>
          <div class="form-group"><label>Tujuan</label><input class="form-control" name="tujuan" id="fTujuan" placeholder="Nama instansi/perusahaan/perorangan"></div>
          <div class="form-group"><label>Lampiran</label><input class="form-control" name="lampiran" placeholder="Jumlah lampiran"></div>

          <!-- Info data dynamic -->
          <div class="card border-info mb-3">
            <div class="card-header bg-info text-white py-2"><h6 class="mb-0"><i class="fas fa-database mr-2"></i>Data Referensi Template</h6></div>
            <div class="card-body py-2">
              <div class="row text-sm">
                <div class="col-md-6">
                  <strong>Kepala Sekolah:</strong>
                  <span id="refKepsek"><?php echo htmlspecialchars(($kepsek['gelar_depan']??'') . ' ' . ($kepsek['fullname']??'') . ', ' . ($kepsek['gelar_belakang']??'') . (empty($kepsek['nip'])?'':' (NIP. '.$kepsek['nip'].')')); ?></span>
                  <button class="btn btn-sm btn-link py-0" onclick="$('#editKepsek').toggle()"><i class="fas fa-pen text-muted"></i></button>
                  <div id="editKepsek" style="display:none" class="mt-1"><input class="form-control form-control-sm" name="kepsek_override" placeholder="Nama Kepsek" value="<?php echo htmlspecialchars($kepsek['fullname']??''); ?>"></div>
                </div>
                <div class="col-md-6">
                  <strong>Sekolah:</strong>
                  <span id="refSekolah"><?php echo htmlspecialchars($sekolah['site_name']??$appSiteName??''); ?></span>
                  <button class="btn btn-sm btn-link py-0" onclick="$('#editSekolah').toggle()"><i class="fas fa-pen text-muted"></i></button>
                  <div id="editSekolah" style="display:none" class="mt-1"><input class="form-control form-control-sm" name="sekolah_override" placeholder="Nama Sekolah" value="<?php echo htmlspecialchars($sekolah['site_name']??''); ?>"></div>
                </div>
              </div>
              <small class="text-muted mt-1 d-block">Data diambil dari database. Klik icon pensil untuk override jika diperlukan.</small>
            </div>
          </div>

          <!-- Template preview + edit -->
          <div class="card border-warning" id="templatePreviewCard" style="display:none">
            <div class="card-header bg-warning text-white py-2">
              <h6 class="mb-0"><i class="fas fa-file-word mr-2"></i>Template Surat <span id="templateFileName" class="small"></span></h6>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label>Isi Surat <small class="text-muted">(tambahkan atau edit langsung)</small></label>
                <textarea class="form-control" name="isi_surat" id="fIsiSurat" rows="10" placeholder="Isi surat akan dimuat dari template Word dan bisa diedit langsung di sini."></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="btnSimpanSurat"><i class="fas fa-save mr-1"></i>Simpan & Download</button>
      </div>
    </form>
  </div></div>
</div>
<script>
window.kepsekData = <?php echo json_encode($kepsek ?: new stdClass()); ?>;
window.sekolahData = <?php echo json_encode($sekolah ?: new stdClass()); ?>;
</script>
