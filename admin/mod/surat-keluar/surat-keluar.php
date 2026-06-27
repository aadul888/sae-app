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
$r_i = $connection->query("SELECT id, indeks, perihal, kategori, contoh_nomor, format_template FROM surat_index ORDER BY indeks ASC");
if ($r_i) while ($i = $r_i->fetch_assoc()) $indeks_list[] = $i;

$kepsek = []; $sekolah = []; $a = '';
$rk = $connection->query("SELECT a.fullname, a.gelar_depan, a.gelar_belakang, a.nip FROM admin a JOIN level l ON a.level_id=l.level_id WHERE l.level_nama='Kepala Sekolah' LIMIT 1");
if ($rk && $rk->num_rows > 0) $kepsek = $rk->fetch_assoc();
$rs = $connection->query("SELECT site_name, site_alamat, site_kelurahan, site_kecamatan, site_kota, site_phone, site_email FROM setting LIMIT 1");
if ($rs) { $sekolah = $rs->fetch_assoc(); $a = trim(($sekolah['site_alamat']??'') . ', ' . ($sekolah['site_kelurahan']??'') . ', ' . ($sekolah['site_kecamatan']??'') . ', ' . ($sekolah['site_kota']??'')); }
$nk = trim(($kepsek['gelar_depan']??'') . ' ' . ($kepsek['fullname']??'') . (!empty($kepsek['gelar_belakang']) ? ', ' . $kepsek['gelar_belakang'] : ''));
$ns = $sekolah['site_name'] ?? 'SMK Negeri 1 Pagelaran';
$ks = $sekolah['site_kota'] ?? '';
$ts = $sekolah['site_phone'] ?? '';
$es = $sekolah['site_email'] ?? '';
?>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<style>
.sk-bar { background:#fff; border-bottom:1px solid #ddd; padding:8px 12px; display:flex; flex-wrap:wrap; gap:6px; align-items:end; position:sticky; top:0; z-index:99; }
.sk-bar label { font-size:9px; font-weight:600; color:#666; text-transform:uppercase; display:block; margin:0; }
.sk-bar select, .sk-bar input { font-size:11px; padding:3px 6px; border:1px solid #ccc; border-radius:3px; height:26px; }
.sk-bar .sk-grp { display:flex; align-items:center; gap:6px; padding-right:10px; border-right:1px solid #eee; }
.sk-bar .sk-grp:last-child { border-right:0; }
.sk-info { background:#f8f9fa; border-bottom:1px solid #eee; padding:4px 12px; font-size:9.5px; color:#555; display:flex; flex-wrap:wrap; gap:2px 14px; font-family:Consolas; }
.sk-body { background:#e8e8e8; padding:16px; display:flex; justify-content:center; min-height:500px; }
.sk-paper { background:#fff; width:100%; max-width:794px; box-shadow:0 2px 12px rgba(0,0,0,.12); border:1px solid #bbb; }
.sk-paper .tox-tinymce { border:0 !important; }
.sk-foot { background:#f0f0f0; border-top:1px solid #ddd; padding:3px 12px; display:flex; justify-content:space-between; font-size:10px; color:#666; position:sticky; bottom:0; }
.tox-fullscreen { z-index:9999 !important; }
.sk-list { background:#fff; margin:8px 12px 0; border:1px solid #ddd; border-radius:4px; }
</style>

<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>
<div class="container-fluid mt--6" style="padding:0!important">
<div class="sk-page" style="padding:8px 0;background:#e8e8e8;min-height:100vh">

<div class="sk-list">
  <div style="padding:6px 10px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
    <strong style="font-size:12px"><i class="fas fa-history mr-1"></i>Surat Keluar</strong>
    <button class="btn btn-sm btn-outline-info btn-export-surat-keluar" style="font-size:10px;padding:2px 8px"><i class="fas fa-download"></i> Export</button>
  </div>
  <div style="padding:0 8px 6px">
    <table class="table align-items-center table-flush table-striped surat-keluar-table" width="100%">
      <thead class="thead-light"><tr><th>No</th><th>No. Surat</th><th>Indeks</th><th>Perihal</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="sk-list" style="margin-top:8px">
  <div style="padding:6px 10px;border-bottom:1px solid #eee"><strong style="font-size:12px"><i class="fas fa-file-alt mr-1"></i>Buat Surat</strong></div>
  <form id="formBuatSurat" method="post">
    <input type="hidden" name="action" value="buat">
    <input type="hidden" name="no_surat" id="fNoSurat">

    <div class="sk-bar">
      <div class="sk-grp">
        <div><label>Indeks</label>
          <select name="indeks_id" id="slcIndeks" style="min-width:180px" required>
            <option value="">-- Cari --</option>
            <?php foreach ($indeks_list as $ix): ?>
            <option value="<?php echo $ix['id']; ?>"
              data-indeks="<?php echo htmlspecialchars($ix['indeks']); ?>"
              data-perihal="<?php echo htmlspecialchars($ix['perihal']); ?>"
              data-kategori="<?php echo htmlspecialchars($ix['kategori']); ?>"
              data-template="<?php echo htmlspecialchars($ix['format_template'] ?? ''); ?>">
              <?php echo htmlspecialchars($ix['indeks'] . ' — ' . $ix['perihal']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="sk-grp">
        <div><label>Kategori</label><input id="fKategori" readonly style="width:100px" value="Surat Keluar"></div>
        <div><label>No. Surat</label><input id="fNoSuratDisplay" readonly style="width:150px;font-family:monospace;font-size:10px" placeholder="Otomatis"></div>
      </div>
      <div class="sk-grp">
        <div><label>Tanggal</label><input type="date" name="tgl_surat" value="<?php echo date('Y-m-d'); ?>" style="width:110px"></div>
        <div><label>Tujuan</label><input name="tujuan" placeholder="Instansi" style="width:120px"></div>
      </div>
      <div class="sk-grp" style="border:0">
        <button type="submit" class="btn btn-primary btn-sm" style="font-size:11px"><i class="fas fa-download mr-1"></i>Simpan</button>
        <a href="./surat" class="btn btn-sm btn-outline-secondary" style="font-size:10px;padding:3px 6px"><i class="fas fa-arrow-left"></i></a>
      </div>
    </div>

    <div class="sk-info">
      <span><code>{{kepala_sekolah}}</code> <?php echo htmlspecialchars($nk); ?></span>
      <span><code>{{nip_kepsek}}</code> <?php echo htmlspecialchars($kepsek['nip']??'-'); ?></span>
      <span><code>{{nama_sekolah}}</code> <?php echo htmlspecialchars($ns); ?></span>
      <span><code>{{alamat}}</code> <?php echo htmlspecialchars($a); ?></span>
    </div>

    <div class="sk-body">
      <div class="sk-paper">
        <div id="skPlaceholder" style="padding:80px 40px;text-align:center;color:#bbb">
          <i class="fas fa-file-alt fa-4x mb-2 d-block"></i>
          <h5 style="color:#aaa">Pilih Indeks Surat</h5>
        </div>
        <div id="skEditorWrap" style="display:none">
          <textarea name="isi_surat" id="fIsiSurat"></textarea>
        </div>
      </div>
    </div>

    <div class="sk-foot">
      <span>Surat Keluar <?php echo htmlspecialchars($ns); ?></span>
      <span id="skWordCount">0 kata</span>
    </div>
  </form>
</div>

</div></div>
<script>
window.SK = {
  nk: <?php echo json_encode($nk); ?>,
  nip: <?php echo json_encode($kepsek['nip']??''); ?>,
  ns: <?php echo json_encode($ns); ?>,
  a: <?php echo json_encode($a); ?>,
  ks: <?php echo json_encode($ks); ?>,
  ts: <?php echo json_encode($ts); ?>,
  es: <?php echo json_encode($es); ?>,
  noSurat: '', perihal: '', tujuan: '.........................'
};
</script>
