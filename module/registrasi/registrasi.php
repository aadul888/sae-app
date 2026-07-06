<?php
if (empty($connection)) {
  header('location:./');
  exit;
}

$registrasi_site_name = 'Smart Apps Education';
$registrasi_base_url = rtrim(isset($base_url) ? $base_url : './', '/');
$registrasi_script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '/index.php';
$registrasi_app_root = rtrim((string) dirname($registrasi_script), '/');
if ($registrasi_app_root === '.' || $registrasi_app_root === DIRECTORY_SEPARATOR) {
  $registrasi_app_root = '';
}

$registrasi_defaults = [
  'base_url' => '',
  'npsn' => '',
  'token' => ''
];
?>
<section class="sae-setup-page">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-7 col-xl-5">
        <div class="sae-setup-shell">
          <div class="sae-setup-form-card">
            <div class="sae-setup-brand-block">
              <span class="sae-setup-badge"><i class="fas fa-download"></i> Penarikan Data Awal</span>
              <div class="sae-setup-brand text-center">
                <div class="sae-setup-logo-icon" aria-hidden="true">
                  <i class="fas fa-download"></i>
                </div>
                <div class="sae-setup-brand-copy">
                  <h2>Form Tarik Data</h2>
                  <p><?php echo htmlspecialchars($registrasi_site_name); ?></p>
                </div>
              </div>
            </div>
            <form class="registrasi-sync-form" action="javascript:void(0);" method="post" autocomplete="off">
              <input type="hidden" id="registrasi-app-root" value="<?php echo htmlspecialchars($registrasi_app_root); ?>">
              <div class="form-group">
                <label class="form-label" for="registrasi-base-url">IP atau Alamat Dapodik</label>
                <div class="input-group input-group-merge">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-network-wired"></i></span>
                  </div>
                  <input type="text" class="form-control" id="registrasi-base-url" name="base_url" value="<?php echo htmlspecialchars($registrasi_defaults['base_url']); ?>" placeholder="Masukkan alamat Dapodik" required>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="registrasi-npsn">NPSN</label>
                <div class="input-group input-group-merge">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-school"></i></span>
                  </div>
                  <input type="text" class="form-control" id="registrasi-npsn" name="npsn" value="<?php echo htmlspecialchars($registrasi_defaults['npsn']); ?>" placeholder="Masukkan NPSN" required>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="registrasi-token">Token Dapodik</label>
                <div class="input-group input-group-merge">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                  </div>
                  <input type="text" class="form-control" id="registrasi-token" name="token" value="<?php echo htmlspecialchars($registrasi_defaults['token']); ?>" placeholder="Masukkan token Dapodik" required>
                </div>
              </div>
              <div class="mt-4">
                <button type="submit" class="btn sae-setup-submit" id="btnRegistrasiSync">Tarik Data Sekarang</button>
              </div>
            </form>
          </div>
        </div>
        <div class="sae-floating-progress" id="registrasiFloatingProgress" aria-live="polite" hidden>
          <div class="sae-floating-progress-backdrop"></div>
          <div class="sae-floating-progress-dialog">
            <div class="sae-floating-progress-ring">
              <span id="registrasiProgressPercent">0%</span>
            </div>
            <div class="sae-floating-progress-copy">
              <strong id="registrasiProgressLabel">Menyiapkan proses...</strong>
              <span id="registrasiProgressMeta">Menunggu penarikan data dimulai.</span>
            </div>
            <div class="sae-reg-step-bar-wrap" id="registrasiStepBarWrap" style="display:none;">
              <div class="progress sae-reg-step-bar-track">
                <div class="progress-bar progress-bar-striped progress-bar-animated sae-reg-step-bar" id="registrasiStepBar" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
              </div>
              <div class="sae-reg-step-bar-text" id="registrasiStepBarText">Memproses data...</div>
            </div>
            <div class="sae-floating-progress-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <span>Jangan tutup, refresh, atau pindahkan halaman ini selama proses tarik data masih berjalan.</span>
            </div>
            <div class="sae-floating-progress-stream" id="registrasiProgressStream">
              <div class="sae-floating-progress-stream-empty">Menunggu aktivitas penarikan data dari server.</div>
            </div>
            <div id="registrasiResetSection" style="display:none;" class="mt-2">
              <button type="button" class="btn btn-outline-danger btn-sm w-100" id="btnResetSyncTables">
                <i class="fas fa-trash-alt"></i> Hapus Data &amp; Ulangi
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>