<?php
/**
 * MODUL: PENGATURAN SURAT — Integrasi Google API & Kop Surat
 * Menyimpan file kredensial JSON Google Service Account, pengaturan kop surat,
 * serta data kepala sekolah (gelar depan/belakang).
 */

// Jika diakses langsung (bukan melalui index.php), redirect ke router
if (!isset($connection)) {
  header('Location: ../../?mod=surat-setting');
  exit;
}

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

// Handle disconnect OAuth
if (isset($_GET['action']) && $_GET['action'] === 'disconnect_oauth') {
  $connection->query("UPDATE surat_setting SET oauth_refresh_token=NULL, oauth_token_json=NULL, oauth_email=NULL WHERE id=1");
  header('Location: ?mod=surat-setting&oauth=disconnected');
  exit;
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

/**
 * Buat URL OAuth untuk login Google.
 * @param string $clientId
 * @param string $redirectUri
 * @return string
 */
function getOAuthUrl($clientId, $redirectUri) {
  if (empty($clientId)) return '#';
  $params = http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/userinfo.email',
    'access_type' => 'offline',
    'prompt' => 'consent',
  ]);
  return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
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
            <small class="text-muted">Konfigurasi identitas kepala sekolah dan integrasi Google API.</small>
          </div>
          <div>
            <a href="../../?mod=surat-template" class="btn-mod btn-mod-secondary" title="Template Surat"><i class="fas fa-file-code"></i></a>
            <a href="../../?mod=surat" class="btn-mod btn-mod-secondary" title="Dashboard Surat"><i class="fas fa-arrow-left"></i></a>
          </div>
        </div>

        <div class="card-body">
          <form id="formSuratSetting" method="post" autocomplete="off">
            <input type="hidden" name="action" value="save_settings">

            <!-- ===== SECTION 1: Kepala Sekolah ===== -->
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

            <!-- ===== SECTION 2: OAuth 2.0 (Google Login) ===== -->
            <div class="form-section-card">
              <div class="card-header">
                <h5><i class="fab fa-google text-primary mr-2"></i> OAuth 2.0 — Login Google</h5>
              </div>
              <div class="card-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Gunakan OAuth 2.0 (login Google pribadi) untuk mengakses Google Drive. Buat kredensial OAuth 2.0 di <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a> &rarr; APIs &amp; Services &rarr; Credentials &rarr; Buat <strong>OAuth 2.0 Client ID</strong> (tipe: Web Application).</span>
                </div>

                <?php
                $oauth_client_id = $setting['oauth_client_id'] ?? '';
                $oauth_client_secret = $setting['oauth_client_secret'] ?? '';
                $oauth_email = $setting['oauth_email'] ?? '';
                $oauth_refresh = $setting['oauth_refresh_token'] ?? '';
                $is_oauth_connected = !empty($oauth_refresh);

                // Tentukan redirect URI — hardcode path ke modul surat-setting
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $redirect_uri = $protocol . '://' . $host . '/saev5/admin/mod/surat-setting/oauth-callback.php';
                ?>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">OAuth Client ID</label>
                      <input type="text" class="form-control" name="oauth_client_id" id="oauth_client_id" value="<?php echo htmlspecialchars($oauth_client_id); ?>" placeholder="123456789-xxxxx.apps.googleusercontent.com">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">OAuth Client Secret</label>
                      <input type="text" class="form-control" name="oauth_client_secret" id="oauth_client_secret" value="<?php echo htmlspecialchars($oauth_client_secret); ?>" placeholder="GOCSPX-xxxxx">
                    </div>
                  </div>
                </div>

                <div class="p-3 bg-light rounded mb-3">
                  <strong>Redirect URI:</strong>
                  <code class="ml-2"><?php echo htmlspecialchars($redirect_uri); ?></code>
                  <small class="form-text text-muted">Daftarkan URI ini di <strong>Authorized redirect URIs</strong> pada OAuth 2.0 Client ID di Google Cloud Console.</small>
                </div>

                <?php if ($is_oauth_connected): ?>
                  <div class="alert alert-success d-flex align-items-center">
                    <i class="fas fa-check-circle mr-2 fa-lg"></i>
                    <span>
                      <strong>Terhubung!</strong>
                      <?php if (!empty($oauth_email)): ?>
                        Akun: <strong><?php echo htmlspecialchars($oauth_email); ?></strong>
                      <?php endif; ?>
                      <br><small>Refresh token tersimpan. Klik tombol di bawah untuk menghubungkan ulang.</small>
                    </span>
                  </div>
                  <div class="d-flex gap-2" style="gap:8px;">
                    <a href="<?php echo htmlspecialchars(getOAuthUrl($oauth_client_id, $redirect_uri)); ?>" class="btn btn-primary">
                      <i class="fab fa-google mr-1"></i> Hubungkan Ulang
                    </a>
                    <a href="#" class="btn btn-outline-danger btn-disconnect-oauth">
                      <i class="fas fa-unlink mr-1"></i> Putuskan
                    </a>
                  </div>
                <?php elseif (!empty($oauth_client_id) && !empty($oauth_client_secret)): ?>
                  <a href="<?php echo htmlspecialchars(getOAuthUrl($oauth_client_id, $redirect_uri)); ?>" class="btn btn-primary">
                    <i class="fab fa-google mr-1"></i> Login dengan Google
                  </a>
                  <small class="form-text text-muted mt-1">Klik untuk memberikan izin akses Google Drive.</small>
                <?php else: ?>
                  <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Isi Client ID dan Client Secret terlebih dahulu, simpan, lalu halaman akan reload dengan tombol login.
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- ===== SECTION 3: Google Spreadsheet ===== -->
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

            <!-- ===== SECTION 4: Google Drive PDF Folder ===== -->
            <div class="form-section-card">
              <div class="card-header">
                <h5><i class="fab fa-google-drive text-primary mr-2"></i> Google Drive — Folder PDF Surat Keluar</h5>
              </div>
              <div class="card-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Masukkan Folder ID tujuan penyimpanan PDF. Folder ID bisa didapat dari URL folder Google Drive: <code>https://drive.google.com/drive/folders/FOLDER_ID</code>. Folder harus sudah dibuat manual di Google Drive.</span>
                </div>
                <div class="row">
                  <div class="col-md-8">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Folder ID</label>
                      <input type="text" class="form-control" name="drive_folder_id" id="drive_folder_id" value="<?php echo htmlspecialchars($setting['drive_folder_id'] ?? ''); ?>" placeholder="Masukkan Folder ID">
                      <small class="form-text text-muted">ID folder Google Drive tempat PDF akan disimpan.</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="form-control-label font-weight-bold">Cek Folder</label>
                      <button type="button" class="btn btn-outline-primary btn-block" id="btnCheckDriveFolder">
                        <i class="fas fa-folder-open mr-1"></i> Buka di Drive
                      </button>
                      <small class="form-text text-muted">Klik untuk membuka folder Google Drive.</small>
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
