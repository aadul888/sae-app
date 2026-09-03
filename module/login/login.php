<?php
if (empty($connection)) {
  header('location:./');
} else {
  $login_site_name  = isset($site_name) ? $site_name : 'Smart Apps Education';
  $login_logo_file  = isset($site_logo2) && $site_logo2 ? $site_logo2 : (isset($site_logo) && $site_logo ? $site_logo : 'logoweb1.png');
  $login_base_url   = rtrim(isset($base_url) ? $base_url : './', '/');
  $login_logo_path  = __DIR__ . '/../../content/' . $login_logo_file;
  $login_logo_src   = ($login_base_url === '' ? '' : $login_base_url) . '/content/' . $login_logo_file;
  if (file_exists($login_logo_path)) {
    $v = defined('SAE_VERSION') ? SAE_VERSION : filemtime($login_logo_path);
    $login_logo_src .= '?v=' . substr((string)$v, 0, 16);
  }
  $admin_url        = ($login_base_url === '' ? './admin/' : $login_base_url . '/admin/');
  $login_home_url   = ($login_base_url === '' ? './' : $login_base_url . '/');
?>
<div class="sae-landing login-page">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10 col-xl-8">

      <div class="auth-shell auth-form-shell">
        <div class="row g-0 align-items-stretch justify-content-center">
          <div class="col-12">
            <div class="login-card auth-login-card h-100 auth-login-card--single">
              <div class="login-header">
                <div class="fingerprint-icon">
                  <i class="fas fa-fingerprint"></i>
                </div>
                <h2 class="app-title">Login Murid</h2>
              </div>
              <div class="login-form-container">
                <div id="loginPaneMurid" class="login-pane">
                  <p class="login-pane-copy">Silakan masukkan <em>NISN atau username</em> dan password untuk masuk ke <?php echo htmlspecialchars($login_site_name); ?>.</p>
                  <form class="form-login" action="javascript:void(0);" role="form" method="post">
                    <div class="form-group-modern">
                      <label for="username" class="form-label">NISN / Username</label>
                      <div class="input-wrapper">
                        <input type="text"
                               class="form-control-modern"
                               id="username"
                               name="username"
                               placeholder="Masukkan NISN atau username Anda"
                               autocomplete="username"
                               required>
                        <i class="fas fa-user input-icon" aria-hidden="true"></i>
                      </div>
                    </div>
                    <div class="form-group-modern">
                      <label for="password" class="form-label">Password</label>
                      <div class="input-wrapper">
                        <input type="password"
                               class="form-control-modern password"
                               id="password"
                               name="password"
                               placeholder="Masukkan password Anda"
                               autocomplete="current-password"
                               required>
                        <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                        <i class="fas fa-eye toggle-password"
                           toggle="#password"
                           aria-label="Toggle password visibility"
                           role="button"
                           tabindex="0"></i>
                      </div>
                    </div>
                    <div class="login-actions">
                      <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text">Masuk ke Sistem</span>
                        <i class="fas fa-arrow-right btn-icon" aria-hidden="true"></i>
                      </button>
                      <div class="forgot-password-link text-center">
                        <a href="javascript:void(0)" id="lupaPasswordBtn" class="login-helper-link">
                          <i class="fas fa-key me-1"></i>Lupa Password?
                        </a>
                      </div>
                    </div>
                  </form>
                </div>

                <div class="auth-side-actions auth-side-actions--footer">
                  <a href="<?php echo htmlspecialchars($login_home_url); ?>" class="auth-side-link"><i class="fas fa-home"></i>Kembali ke Beranda</a>
                  <a href="<?php echo htmlspecialchars($login_home_url . 'tentang/'); ?>" class="auth-side-link"><i class="fas fa-info-circle"></i>Tentang Aplikasi</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal Update Password -->
  <div class="modal fade" id="updatePasswordModal" tabindex="-1" aria-labelledby="updatePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updatePasswordModalLabel">Update Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="formUpdatePassword" method="post" action="javascript:void(0);">
            <div class="mb-3">
              <label for="newPassword" class="form-label">Password Baru</label>
              <div class="input-wrapper position-relative">
                <input type="password" class="form-control password-strength" id="newPassword" name="newPassword" placeholder="Password Baru" required autocomplete="new-password">
                <i class="fas fa-eye toggle-password" toggle="#newPassword" aria-label="Toggle password visibility" role="button" tabindex="0" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);cursor:pointer;"></i>
              </div>
              <div class="password-requirements mt-2">
                <div class="requirement-item" id="req-length">
                  <i class="fas fa-times text-danger requirement-icon"></i>
                  <span class="requirement-text">Minimal 6 karakter</span>
                </div>
                <div class="requirement-item" id="req-uppercase">
                  <i class="fas fa-times text-danger requirement-icon"></i>
                  <span class="requirement-text">Satu huruf besar</span>
                </div>
                <div class="requirement-item" id="req-lowercase">
                  <i class="fas fa-times text-danger requirement-icon"></i>
                  <span class="requirement-text">Satu huruf kecil</span>
                </div>
                <div class="requirement-item" id="req-number">
                  <i class="fas fa-times text-danger requirement-icon"></i>
                  <span class="requirement-text">Satu angka</span>
                </div>
                <div class="requirement-item" id="req-special">
                  <i class="fas fa-times text-danger requirement-icon"></i>
                  <span class="requirement-text">Satu karakter khusus</span>
                </div>
              </div>
              <small class="form-text text-muted mt-2">Silakan lengkapi semua karakter yang diperlukan untuk membuat password yang aman.</small>
            </div>
            <div class="mb-3">
              <label for="confirmPassword" class="form-label">Konfirmasi Password Baru</label>
              <div class="input-wrapper position-relative">
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Konfirmasi Password Baru" required autocomplete="new-password">
                <i class="fas fa-eye toggle-password" toggle="#confirmPassword" aria-label="Toggle password visibility" role="button" tabindex="0" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);cursor:pointer;"></i>
              </div>
              <div class="password-match-indicator mt-2" id="passwordMatchIndicator" style="display: none;">
                <div class="requirement-item" id="password-match">
                  <i class="fas fa-times text-danger requirement-icon"></i>
                  <span class="requirement-text">Password tidak sama</span>
                </div>
              </div>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal Lupa Password -->
  <div class="modal fade" id="lupaPasswordModal" tabindex="-1" aria-labelledby="lupaPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lupaPasswordModalLabel">
            <i class="fas fa-key text-primary"></i> Reset Password
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info" role="alert">
            <i class="fab fa-whatsapp me-2"></i>
            <strong>Reset Via WhatsApp</strong><br>
            Password akan direset dan informasi akan dikirim via WhatsApp.
          </div>
          <form id="formLupaPassword" method="post" action="javascript:void(0);">
            <div class="mb-3">
              <label for="nomorHP" class="form-label">Nomor HP (WhatsApp)</label>
              <div class="input-wrapper position-relative">
                <input type="tel" class="form-control" id="nomorHP" name="nomor_hp"
                       placeholder="08123456789" required autocomplete="tel">
                <i class="fab fa-whatsapp"
                   style="position:absolute;top:50%;right:12px;transform:translateY(-50%);color:#25d366;"></i>
              </div>
              <small class="form-text text-muted mt-2">
                Masukkan nomor WhatsApp yang sudah terverifikasi di akun Anda.
              </small>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="fab fa-whatsapp me-2"></i>
                Kirim Reset Password
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
}
