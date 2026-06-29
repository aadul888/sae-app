<?php
/**
 * MODUL: PENGATURAN SURAT — Integrasi Google API & Kop Surat
 * Menyimpan file kredensial JSON Google Service Account, pengaturan kop surat,
 * serta data kepala sekolah (gelar depan/belakang).
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
$modul_id = 132;
include __DIR__ . '/../check_role.php';
if (!$has_access) { hak_akses(); return; }

// Only allow users with modify permission
if (!isset($data_role['modifikasi']) || $data_role['modifikasi'] != 'Y') {
  theme_404(); return;
}

// Load current surat_setting data
$setting = [];
$q = $connection->query("SELECT * FROM surat_setting WHERE id=1 LIMIT 1");
if ($q && $r = $q->fetch_assoc()) {
  $setting = $r;
}

// Load admin data for Kepala Sekolah (level_id = 13)
$kepsek_list = [];
$q2 = $connection->query("SELECT admin_id, fullname, gelar_depan, gelar_belakang, gtk_nip FROM admin WHERE level_id=13 ORDER BY admin_id ASC");
if ($q2) {
  while ($r2 = $q2->fetch_assoc()) {
    $kepsek_list[] = $r2;
  }
}

// Current kepala sekolah identity (ambil admin_id pertama yang level=13)
$kepsek_selected = !empty($kepsek_list) ? $kepsek_list[0] : ['admin_id'=>0,'fullname'=>'','gelar_depan'=>'','gelar_belakang'=>'','gtk_nip'=>''];

// Kop surat dari tabel setting (site_kop)
$site_kop_img = '';
$qkop = $connection->query("SELECT site_kop, site_name, site_address FROM setting ORDER BY site_id ASC LIMIT 1");
if ($qkop && $rk = $qkop->fetch_assoc()) {
  if (!empty($rk['site_kop']) && file_exists("../content/" . $rk['site_kop'])) {
    $site_kop_img = $rk['site_kop'];
  }
  $kop_nama_sekolah = $rk['site_name'] ?? '';
  $kop_alamat = $rk['site_address'] ?? '';
}
?>
<style>
.surat-setting-page .form-section-card {
  border: 1px solid #e3e6f0;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.surat-setting-page .form-section-card .card-header {
  background: #f8f9fc;
  border-bottom: 1px solid #e3e6f0;
  padding: 0.75rem 1.25rem;
}
.surat-setting-page .form-section-card .card-header h5 {
  margin: 0;
  font-weight: 600;
  font-size: 1rem;
}
.surat-setting-page .form-section-card .card-body {
  padding: 1.25rem;
}
.surat-setting-page .kop-preview {
  border: 1px dashed #d2d6da;
  border-radius: 0.375rem;
  padding: 20px;
  background: #f8f9fc;
  min-height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.surat-setting-page .kop-preview img {
  max-height: 80px;
  object-fit: contain;
}
.surat-setting-page .kop-preview .kop-placeholder {
  color: #8898aa;
  font-size: 0.875rem;
}
.surat-setting-page .gelar-badge {
  font-size: 0.8rem;
  padding: 0.25rem 0.5rem;
  background: #ebf4ff;
  color: #2c5282;
  border-radius: 0.25rem;
  display: inline-block;
}
</style>

<div class="header bg-primary pb-4 user-page-header-compact"><div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div></div>

<div class="container-fluid mt--6 user-module-page surat-setting-page">
  <div class="row">
    <div class="col-12">
      
      <!-- Main Card -->
      <div class="card shadow" id="mainCard">
        <div class="card-header py-3 px-3 module-table-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
          <div>
            <h4 class="mb-1"><i class="fas fa-cog text-primary mr-2"></i> Pengaturan Surat</h4>
            <small class="text-muted">Konfigurasi kop surat, identitas kepala sekolah, dan integrasi Google API.</small>
          </div>
          <div>
            <a href="./surat-template" class="btn-mod btn-mod-secondary" title="Template Surat"><i class="fas fa-file-code"></i></a>
            <a href="./surat" class="btn-mod btn-mod-secondary" title="Dashboard Surat"><i class="fas fa-arrow-left"></i></a>
          </div>
        </div>

        <div class="card-body">
          <form id="formSuratSetting" method="post" autocomplete="off">
            <input type="hidden" name="action" value="save_settings">

            <!-- ===== SECTION 1: Kop Surat ===== -->
            <div class="form-section-card">
              <div class="card-header">
                <h5><i class="fas fa-envelope-open-text text-primary mr-2"></i> Kop Surat</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Nama Sekolah</label>
                      <input type="text" class="form-control" name="kop_nama_sekolah" id="kop_nama_sekolah" value="<?php echo htmlspecialchars($kop_nama_sekolah ?? ''); ?>" placeholder="Nama lengkap sekolah">
                      <small class="form-text text-muted">Nama sekolah yang akan tampil di kop surat</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Logo / Kop Surat</label>
                      <div class="kop-preview" id="kopPreview">
                        <?php if ($site_kop_img): ?>
                          <img src="../content/<?php echo $site_kop_img; ?>?v=<?php echo filemtime("../content/".$site_kop_img); ?>" alt="Kop Surat">
                        <?php else: ?>
                          <span class="kop-placeholder"><i class="fas fa-image mr-1"></i> Belum ada kop surat</span>
                        <?php endif; ?>
                      </div>
                      <small class="form-text text-muted">Kop surat diatur melalui <a href="./pengaturan" target="_blank">Pengaturan Website</a> → Upload Kop Sekolah. Format yang didukung: JPG, PNG, GIF.</small>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Alamat</label>
                      <textarea class="form-control" name="kop_alamat" id="kop_alamat" rows="3" placeholder="Alamat lengkap sekolah"><?php echo htmlspecialchars($kop_alamat ?? ''); ?></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ===== SECTION 2: Kepala Sekolah ===== -->
            <div class="form-section-card">
              <div class="card-header">
                <h5><i class="fas fa-user-tie text-primary mr-2"></i> Kepala Sekolah</h5>
              </div>
              <div class="card-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Data kepala sekolah diambil dari tabel <strong>admin</strong> (level: Kepala Sekolah). Gelar depan/belakang dapat disesuaikan.</span>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Pilih Kepala Sekolah</label>
                      <select class="form-control" name="kepsek_admin_id" id="kepsek_admin_id" onchange="loadKepsekData(this.value)">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($kepsek_list as $k): ?>
                          <option value="<?php echo $k['admin_id']; ?>" data-gelar-depan="<?php echo htmlspecialchars($k['gelar_depan'] ?? ''); ?>" data-gelar-belakang="<?php echo htmlspecialchars($k['gelar_belakang'] ?? ''); ?>" <?php echo ($k['admin_id'] == $kepsek_selected['admin_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k['fullname']); ?> (NIP: <?php echo htmlspecialchars($k['gtk_nip'] ?? '-'); ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Nama Lengkap</label>
                      <input type="text" class="form-control" id="kepsek_fullname" value="<?php echo htmlspecialchars($kepsek_selected['fullname'] ?? ''); ?>" readonly>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">NIP</label>
                      <input type="text" class="form-control" id="kepsek_nip" value="<?php echo htmlspecialchars($kepsek_selected['gtk_nip'] ?? '-'); ?>" readonly>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Gelar Depan</label>
                      <input type="text" class="form-control" name="kepsek_gelar_depan" id="kepsek_gelar_depan" value="<?php echo htmlspecialchars($kepsek_selected['gelar_depan'] ?? ''); ?>" placeholder="Mis: Drs., H." maxlength="50">
                      <small class="form-text text-muted">Gelar depan yang ditampilkan sebelum nama (maks. 50 karakter)</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Gelar Belakang</label>
                      <input type="text" class="form-control" name="kepsek_gelar_belakang" id="kepsek_gelar_belakang" value="<?php echo htmlspecialchars($kepsek_selected['gelar_belakang'] ?? ''); ?>" placeholder="Mis: S.Pd., M.Pd." maxlength="50">
                      <small class="form-text text-muted">Gelar belakang yang ditampilkan setelah nama (maks. 50 karakter)</small>
                    </div>
                  </div>
                </div>
                <div class="mt-2 p-3 bg-light rounded">
                  <strong>Contoh tampilan:</strong>
                  <div class="mt-1" id="kepsekPreview">
                    <?php
                    $gd = trim($kepsek_selected['gelar_depan'] ?? '');
                    $gb = trim($kepsek_selected['gelar_belakang'] ?? '');
                    $nama = $kepsek_selected['fullname'] ?? '';
                    $preview = ($gd ? $gd . ' ' : '') . $nama . ($gb ? ', ' . $gb : '');
                    ?>
                    <span class="gelar-badge"><?php echo htmlspecialchars($preview ?: 'Nama dengan gelar akan tampil di sini'); ?></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- ===== SECTION 3: Google API Credentials ===== -->
            <div class="form-section-card">
              <div class="card-header">
                <h5><i class="fab fa-google text-primary mr-2"></i> Google API Credentials</h5>
              </div>
              <div class="card-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Upload file JSON kredensial Service Account dari <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a>. File ini akan digunakan untuk menghubungkan API Spreadsheet & Docs.</span>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <?php
                    $creds_file = $setting['google_credentials'] ?? '';
                    $creds_valid = false;
                    if (!empty($creds_file) && file_exists('../content/' . $creds_file)) {
                      $creds_valid = true;
                    }
                    ?>
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Upload File JSON Kredensial</label>
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" name="google_credentials" id="google_credentials" accept=".json">
                        <label class="custom-file-label" for="google_credentials"><?php echo $creds_valid ? htmlspecialchars($creds_file) : 'Pilih file JSON...'; ?></label>
                      </div>
                      <small class="form-text text-muted">File JSON Service Account dari Google Cloud (type: service_account).</small>
                      <?php if ($creds_valid): ?>
                        <div class="mt-2">
                          <span class="badge badge-success"><i class="fas fa-check mr-1"></i> File terpasang</span>
                          <small class="text-muted ml-2"><?php echo htmlspecialchars($creds_file); ?></small>
                        </div>
                        <?php
                        $client_email = $setting['client_email'] ?? '';
                        if (!empty($client_email)):
                        ?>
                        <div class="mt-3 p-3 bg-light rounded">
                          <label class="form-control-label font-weight-bold">Client Email (Service Account)</label>
                          <div class="input-group">
                            <input type="text" class="form-control" id="clientEmail" value="<?php echo htmlspecialchars($client_email); ?>" readonly>
                            <div class="input-group-append">
                              <button class="btn btn-primary" type="button" onclick="copyClientEmail()" title="Salin email">
                                <i class="fas fa-copy"></i>
                              </button>
                            </div>
                          </div>
                          <small class="form-text text-muted">Gunakan email ini untuk memberikan akses spreadsheet/docs ke Service Account ini.</small>
                        </div>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ===== SECTION 4: Google Spreadsheet ===== -->
            <div class="form-section-card">
              <div class="card-header">
                <h5><i class="fas fa-table text-primary mr-2"></i> Google Spreadsheet Integration</h5>
              </div>
              <div class="card-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Data surat keluar otomatis akan dicatat ke spreadsheet. Masukkan ID spreadsheet dan range yang diinginkan. Pastikan spreadsheet sudah di-<em>share</em> ke Service Account email di atas dengan akses <strong>Editor</strong>.</span>
                </div>
                <div class="row">
                  <div class="col-md-8">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Spreadsheet ID</label>
                      <input type="text" class="form-control" name="spreadsheet_id" id="spreadsheet_id" value="<?php echo htmlspecialchars($setting['spreadsheet_id'] ?? ''); ?>" placeholder="Contoh: 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms">
                      <small class="form-text text-muted">ID spreadsheet bisa didapat dari URL spreadsheet: <code>https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit</code></small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Range / Sheet</label>
                      <input type="text" class="form-control" name="spreadsheet_range" id="spreadsheet_range" value="<?php echo htmlspecialchars($setting['spreadsheet_range'] ?? 'Sheet1'); ?>" placeholder="Sheet1">
                      <small class="form-text text-muted">Nama sheet dan range (contoh: <code>Sheet1</code> atau <code>Sheet1!A:Z</code>)</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ===== SAVE BUTTON ===== -->
            <div class="text-center mt-3">
              <button type="submit" class="btn btn-primary btn-lg px-5" id="btnSave">
                <i class="fas fa-save mr-2"></i> Simpan Pengaturan
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Include scripts -->
<script src="./mod/surat-setting/scripts.js"></script>
