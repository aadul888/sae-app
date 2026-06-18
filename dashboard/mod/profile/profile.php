<?php
// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('location:../');
  exit();
} else {
  if (isset($_COOKIE['siswa'])) {
    // Get user ID from cookie menggunakan decrypt yang sama seperti oauth/user.php
    $siswa_cookie = convert("decrypt", $_COOKIE['siswa']);
    $siswa_id = $siswa_cookie;
    $data = [];
    if (!empty($siswa_id)) {
      $q = $connection->query("SELECT u.*, k.nama_kelas, COALESCE(u.whatsapp_verified, 0) as whatsapp_verified, u.whatsapp_verified_at FROM user u LEFT JOIN kelas k ON u.kelas = k.kelas_id WHERE u.user_id='$siswa_id'");
      if ($q && $q->num_rows > 0) {
        $data = $q->fetch_assoc();
      }
    }
    
    // Cek foto avatar (prioritaskan kolom DB `avatar` yang mungkin berisi "file?t=timestamp")
    $foto_src = '../content/avatar/avatar.jpg';
    $nisn = $data['nisn'] ?? '';

    // Jika kolom avatar dari DB ada, gunakan itu (bentuk yang disimpan: "filename.ext?t=..." atau "filename.ext")
    if (!empty($data['avatar'])) {
      $avatar_db = $data['avatar'];
      // Ambil nama file tanpa query string untuk pengecekan filesystem
      $avatar_file = preg_replace('/\?.*/', '', $avatar_db);
      $avatar_path = '../content/avatar/' . $avatar_file;
      if (!empty($avatar_file) && file_exists($avatar_path)) {
        // Gunakan nilai DB utuh (termasuk ?t=...) sebagai src sehingga browser akan mem-bust cache
        $foto_src = '../content/avatar/' . $avatar_db;
      }
    }

    // Jika belum ditemukan dari DB, fallback ke file berdasarkan NISN (tambahkan filemtime sebagai cache-buster)
    if ($foto_src === '../content/avatar/avatar.jpg') {
      $foto_jpg = '../content/avatar/' . $nisn . '.jpg';
      $foto_png = '../content/avatar/' . $nisn . '.png';
      if (!empty($nisn)) {
        if (file_exists($foto_jpg)) {
          $foto_src = $foto_jpg . '?t=' . filemtime($foto_jpg);
        } elseif (file_exists($foto_png)) {
          $foto_src = $foto_png . '?t=' . filemtime($foto_png);
        }
      }
    }

?>

<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-12">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Profile Saya</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Page content -->

<div class="container-fluid mt--6">
  <div class="nisn-profile-page">
    <!-- Profile Picture Card - Moved to top -->
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-8">
        <div class="info-card mb-6 nisn-main-card">
          <!-- Student Info Header -->
          <div class="student-header">
            <div class="student-identity">
              <h4 class="student-name"><?php echo htmlspecialchars($data['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h4>
              <p class="student-nisn">NISN: <?php echo htmlspecialchars($data['nisn'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
              <?php 
                $status = (isset($data['status']) && ($data['status'] == 'Aktif' || strtolower($data['status']) == 'aktif')) ? 'Aktif' : 'Tidak Aktif';
                $status_class = $status == 'Aktif' ? 'bg-success' : 'bg-danger';
              ?>
              <span class="student-status badge <?php echo $status_class; ?>"><?php echo $status; ?></span>
            </div>
          </div>
          <!-- Profile Content -->
          <div class="row">
            <!-- Left Column - Photo -->
            <div class="col-lg-4 mb-4">
              <div class="photo-section">
                <div class="student-photo text-center">
                  <img src="<?php echo $foto_src; ?>" class="student-avatar" id="profileImage" alt="Avatar" 
                       onclick="Swal.fire({icon: 'info', title: 'Informasi', text: 'Untuk mengganti foto profil, silahkan hubungi Administrator', confirmButtonText: 'OK'})" 
                       style="cursor: pointer;">
                </div>
                <div class="photo-warning">
                  <div class="warning-card d-flex align-items-start">
                    <div class="warning-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="warning-content">
                      <div class="warning-title">Catatan</div>
                      <div class="warning-text">Untuk mengganti foto profil, silakan hubungi Administrator.</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Right Column - Information -->
            <div class="col-lg-8">
              <!-- Personal Information -->
              <div class="info-card mb-3">
                <div class="info-header">
                  <i class="fas fa-user-circle"></i>
                  <span>Informasi Personal</span>
                </div>
                <div class="info-body">
                  <div class="info-row">
                    <span class="info-label">NIPD/NIS</span>
                    <span class="info-value"><?php echo htmlspecialchars($data['nipd'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <div class="info-row">
                    <span class="info-label">NIK</span>
                    <span class="info-value"><?php echo htmlspecialchars($data['nik'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <div class="info-row">
                    <span class="info-label">Kelas Saat Ini</span>
                    <span class="info-value"><?php echo htmlspecialchars($data['nama_kelas'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                </div>
              </div>
              <!-- Contact Information -->
              <div class="info-card mb-3">
                <div class="info-header">
                  <i class="fas fa-address-book"></i>
                  <span>Informasi Kontak</span>
                  <button type="button" class="btn btn-sm btn-primary float-right" id="btnEditProfile" onclick="toggleEditProfile()">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                </div>
                <div class="info-body">
                  <form id="formProfile" method="post">
                    <div class="info-row mb-3">
                      <span class="info-label">Email</span>
                      <div class="info-value-input">
                        <div class="input-group input-group-contact">
                          <input type="email" id="email" name="email" class="form-control form-control-sm" 
                                 value="<?php echo htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                          <div class="input-group-append">
                            <span class="input-group-text contact-input-icon" style="min-width: 80px; justify-content: center;">
                              <i class="fas fa-envelope"></i>
                            </span>
                          </div>
                        </div>
                        <div class="mt-1">
                          <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Alamat email untuk komunikasi resmi
                          </small>
                        </div>
                      </div>
                    </div>
                    <div class="info-row mb-3">
                      <span class="info-label">Nomor Telepon</span>
                      <div class="info-value-input">
                        <div class="input-group input-group-contact">
                          <input type="text" id="telp" name="telp" class="form-control form-control-sm" 
                                 value="<?php echo htmlspecialchars($data['telp'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                          <div class="input-group-append" id="whatsappVerifySection">
                            <?php 
                              $whatsapp_verified = isset($data['whatsapp_verified']) ? intval($data['whatsapp_verified']) : 0;
                              if ($whatsapp_verified == 1) {
                                $verified_date = '';
                                if (!empty($data['whatsapp_verified_at'])) {
                                  $verified_date = date('d/m/Y H:i', strtotime($data['whatsapp_verified_at']));
                                }
                                echo '<span class="input-group-text contact-input-verified" title="WhatsApp terverifikasi pada ' . $verified_date . '" style="min-width: 80px; justify-content: center;">
                                        <i class="fab fa-whatsapp"></i>
                                        <small class="ml-1">Verified</small>
                                      </span>';
                              } else {
                                echo '<button type="button" class="btn btn-success btn-sm contact-verify-btn" id="btnVerifyWhatsApp" onclick="verifyWhatsApp()" 
                                             title="Klik untuk verifikasi nomor WhatsApp" 
                                             style="min-width: 80px; padding: 0.375rem 0.5rem; font-size: 0.75rem; justify-content: center; display: flex; align-items: center;">
                                        <i class="fab fa-whatsapp mr-1"></i>Verifikasi
                                      </button>';
                              }
                            ?>
                          </div>
                        </div>
                        <div class="mt-1">
                          <?php if ($whatsapp_verified == 1): ?>
                            <small class="text-success">
                              <i class="fas fa-check-circle"></i> Nomor WhatsApp telah diverifikasi
                              <?php if (!empty($data['whatsapp_verified_at'])): ?>
                                pada <?php echo date('d/m/Y H:i', strtotime($data['whatsapp_verified_at'])); ?>
                              <?php endif; ?>
                            </small>
                          <?php else: ?>
                            <small class="text-muted">
                              <i class="fas fa-info-circle"></i> Klik "Verifikasi" untuk memverifikasi nomor WhatsApp Anda
                            </small>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <div class="info-row" id="editActions" style="display: none;">
                      <div class="col-12 text-right">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditProfile()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btnSaveProfile">
                          <i class="fas fa-save"></i> Simpan
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Change Password Section -->
    <div id="ubah-password" class="row justify-content-center mt--5">
      <div class="col-12 col-lg-10 col-xl-8">
        <div class="info-card mb-8">
          <div class="info-header">
            <i class="fas fa-key"></i>
            <span>Ubah Password</span>
          </div>
          <div class="info-body">
            <form id="formChangePassword" method="post">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group mb-3">
                    <label class="form-control-label text-sm" for="new_password">Password Baru</label>
                    <div class="input-group">
                      <input type="password" id="new_password" name="new_password" 
                             class="form-control form-control-alternative" placeholder="Masukkan password baru" required>
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                          <i class="fas fa-eye" id="toggleNewPassword"></i>
                        </button>
                      </div>
                    </div>
                    <small class="text-muted">Password minimal 6 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol</small>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group mb-3">
                    <label class="form-control-label text-sm" for="confirm_password">Konfirmasi Password</label>
                    <div class="input-group">
                      <input type="password" id="confirm_password" name="confirm_password" 
                             class="form-control form-control-alternative" placeholder="Ulangi password baru" required>
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                          <i class="fas fa-eye" id="toggleConfirmPassword"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div id="passwordStrength" style="display: none;">
                    <div class="progress mb-2" style="height: 5px;">
                      <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted">
                      <i class="fas fa-times text-danger" id="pw-length"></i> Minimal 6 karakter<br>
                      <i class="fas fa-times text-danger" id="pw-upper"></i> Huruf besar<br>
                      <i class="fas fa-times text-danger" id="pw-lower"></i> Huruf kecil<br>
                      <i class="fas fa-times text-danger" id="pw-digit"></i> Angka<br>
                      <i class="fas fa-times text-danger" id="pw-symbol"></i> Simbol (!@#$%^&*)
                    </small>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12 text-center">
                  <button type="submit" class="btn btn-primary" id="btnChangePassword">
                    <i class="fas fa-key"></i> Ubah Password
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

<!-- WhatsApp Verification Modal -->
<div class="modal fade" id="whatsappVerificationModal" tabindex="-1" role="dialog" aria-labelledby="whatsappVerificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="whatsappVerificationModalLabel">
          <i class="fab fa-whatsapp text-success"></i> Verifikasi WhatsApp
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeVerificationModal()">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="verificationStep1">
          <div class="text-center mb-4">
            <div class="avatar avatar-xl">
              <i class="fab fa-whatsapp text-success" style="font-size: 3rem;"></i>
            </div>
          </div>
          <p class="text-center">Kami akan mengirim kode verifikasi 6 digit ke nomor WhatsApp Anda:</p>
          <div class="alert alert-info text-center">
            <strong id="phoneNumberDisplay"></strong>
          </div>
          <p class="text-muted small text-center">Pastikan nomor telepon di atas sudah benar sebelum melanjutkan verifikasi.</p>
        </div>
        
        <div id="verificationStep2" style="display: none;">
          <div class="text-center mb-4">
            <div class="avatar avatar-xl">
              <i class="fas fa-sms text-primary" style="font-size: 3rem;"></i>
            </div>
          </div>
          <p class="text-center">Masukkan kode verifikasi 6 digit yang telah dikirim ke WhatsApp Anda:</p>
          <form id="verificationCodeForm">
            <div class="form-group">
              <div class="row justify-content-center">
                <div class="col-8">
                  <input type="text" class="form-control form-control-lg text-center" id="verificationCode" 
                         placeholder="123456" maxlength="6" pattern="[0-9]{6}" required>
                </div>
              </div>
            </div>
            <div class="text-center">
              <small class="text-muted">Tidak menerima kode? 
                <a href="javascript:void(0)" id="resendCode" onclick="sendVerificationCode()" class="text-primary">Kirim ulang</a>
              </small>
            </div>
            <div id="countdown" class="text-center mt-2" style="display: none;">
              <small class="text-muted">Kirim ulang dalam <span id="countdownTime">60</span> detik</small>
            </div>
          </form>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closeVerificationModal()">Batal</button>
        <button type="button" class="btn btn-success" id="btnSendCode" onclick="sendVerificationCode()">
          <i class="fas fa-paper-plane"></i> Kirim Kode
        </button>
        <button type="button" class="btn btn-primary" id="btnVerifyCode" onclick="verifyCode()" style="display: none;">
          <i class="fas fa-check"></i> Verifikasi
        </button>
      </div>
    </div>
  </div>
</div>

<?php
  } else {
    echo 'Session tidak valid';
    exit();
  }
}
?>