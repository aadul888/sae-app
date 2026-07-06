<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 2;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    // Cek update
    $gtk_upd_avail = false;
    if (isset($connection) && $connection && defined('SAE_VERSION')) {
      $q_u = $connection->query("SELECT version FROM pembaharuan ORDER BY release_date DESC LIMIT 1");
      if ($q_u && $r_u = $q_u->fetch_assoc()) {
        $gtk_upd_avail = version_compare(SAE_VERSION, $r_u['version'], '<');
      }
    }

    switch (@$_GET['op']) {
      default:
        ?>
<!-- Header -->
<div class="header bg-gradient-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
            </div>
            <div class="col-lg-6 col-5 text-right">
              <?php if ($gtk_upd_avail): ?>
              <span class="badge badge-danger badge-lg mr-2">Pembaruan Tersedia</span>
              <a href="javascript:void(0)" class="btn btn-sm btn-success btn-update-gtk" data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"><i class="fas fa-cloud-download-alt mr-1"></i> Update</a>
              <?php endif; ?>
            </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Page content -->
    <div class="container-fluid mt--6">
      <div class="row">
        <div class="col">
          <div class="card shadow">
            <!-- Card header -->
            <div class="card-header module-table-header">
              <div class="module-header-row" style="gap:10px;">
                <div>
                  <h4 class="mb-1"><i class="fas fa-th-large text-primary mr-2"></i>Portal Aplikasi GTK</h4>
                  <small class="text-muted">Kelola berbagai aplikasi untuk guru dan tenaga kependidikan.</small>
                </div>
                <div class="module-header-actions">
                  <?php if ($data_role['modifikasi'] == 'Y'): ?>
                  <button class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalAddApp" title="Tambah Aplikasi"><i class="fas fa-plus"></i></button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
<?php
        // Semua level bisa melihat halaman ini dengan batasan fitur
        if ($data_role['lihat'] == 'Y') {
          // Admin/Superadmin melihat semua aplikasi, user biasa hanya yang aktif
          if ($data_role['modifikasi'] == 'Y') {
            $apps_query = "SELECT app_id, app_name, app_url, app_icon, custom_icon, app_description, app_category, sort_order, is_active FROM portal_apps ORDER BY sort_order ASC, app_name ASC";
          } else {
            $apps_query = "SELECT app_id, app_name, app_url, app_icon, custom_icon, app_description, app_category, sort_order, is_active FROM portal_apps WHERE is_active='Y' ORDER BY sort_order ASC, app_name ASC";
          }
          
          $apps_result = $connection->query($apps_query);
          
          // Ambil kredensial user yang tersimpan
          $admin_id = isset($_COOKIE['ADMIN_KEY']) ? intval(epm_decode($_COOKIE['ADMIN_KEY'])) : 0;
          $credentials_query = "SELECT app_id, id as credential_id FROM user_app_credentials WHERE admin_id=? AND is_active='Y'";
          $credentials_stmt = $connection->prepare($credentials_query);
          $credentials_stmt->bind_param('i', $admin_id);
          $credentials_stmt->execute();
          $credentials_result = $credentials_stmt->get_result();
          
          $user_credentials = [];
          while ($cred = $credentials_result->fetch_assoc()) {
            $user_credentials[$cred['app_id']] = $cred['credential_id'];
          }
          $credentials_stmt->close();
          
          echo '
                <div class="card-body">
                  <div class="portal-apps-grid">
                    <div class="row" id="appsContainer">';
          
          if ($apps_result && $apps_result->num_rows > 0) {
            while ($app = $apps_result->fetch_assoc()) {
              echo '
                      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-6" data-app-id="' . $app['app_id'] . '">
                        <div class="portal-app-card" data-app="' . htmlentities($app['app_name']) . '" data-url="' . htmlentities($app['app_url']) . '" data-status="' . $app['is_active'] . '">
                          
                          <!-- Admin Actions (Top Right) - Only for users with modifikasi permission -->
                          ' . ($data_role['modifikasi'] == 'Y' ? '
                          <div class="app-actions">
                            <div class="dropdown">
                              <button class="btn btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                              </button>
                              <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item btn-edit-app" href="#" data-id="' . $app['app_id'] . '" data-name="' . htmlentities($app['app_name']) . '" data-url="' . htmlentities($app['app_url']) . '" data-icon="' . htmlentities($app['app_icon']) . '" data-custom-icon="' . htmlentities($app['custom_icon'] ?? '') . '" data-description="' . htmlentities($app['app_description'] ?? '') . '" data-category="' . htmlentities($app['app_category']) . '">
                                  <i class="fas fa-edit"></i> Edit
                                </a>
                                <a class="dropdown-item btn-delete-app" href="#" data-id="' . $app['app_id'] . '" data-name="' . htmlentities($app['app_name']) . '" data-custom-icon="' . htmlentities($app['custom_icon'] ?? '') . '">
                                  <i class="fas fa-trash"></i> Hapus
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item btn-toggle-app" href="#" data-id="' . $app['app_id'] . '" data-status="' . ($app['is_active'] == 'Y' ? 'N' : 'Y') . '">
                                  <i class="fas fa-' . ($app['is_active'] == 'Y' ? 'eye-slash' : 'eye') . '"></i> 
                                  ' . ($app['is_active'] == 'Y' ? 'Nonaktifkan' : 'Aktifkan') . '
                                </a>
                              </div>
                            </div>
                          </div>
                          ' : '') . '
                          
                          <!-- Credential Badge (Top Left) -->
                          ' . (isset($user_credentials[$app['app_id']]) ? 
                             '<div class="credential-badge"><i class="fas fa-key"></i> Tersimpan</div>' : 
                             '<div class="credential-badge no-credential"><i class="fas fa-key-slash"></i> Belum disimpan</div>') . '
                          
                          <!-- App Content -->
                          <div class="app-icon">
                            ';
                            
                            // Handle custom icon
                            if (!empty($app['custom_icon'])) {
                              $icon_file = basename(trim($app['custom_icon']));
                              $icon_path = '../content/icon-apps/' . $icon_file;
                              echo '<img src="' . htmlspecialchars($icon_path) . '" alt="' . htmlentities($app['app_name']) . '" class="custom-app-icon" style="width: 64px; height: 64px; border-radius: 12px; object-fit: cover;">';
                            } else {
                              echo '<i class="' . htmlentities($app['app_icon']) . '" style="font-size: 48px; color: #4A90E2;"></i>';
                            }
                            
                            echo '
                          </div>
                          
                          ' . ($data_role['modifikasi'] == 'Y' ? '
                          <!-- Status Badge (Below Icon) -->
                          <div class="status-badge-below status-' . ($app['is_active'] == 'Y' ? 'active' : 'inactive') . '">
                            <i class="fas fa-' . ($app['is_active'] == 'Y' ? 'check-circle' : 'times-circle') . '"></i> 
                            ' . ($app['is_active'] == 'Y' ? 'Aktif' : 'Nonaktif') . '
                          </div>
                          ' : '') . '
                          
                          <div class="app-name">' . htmlentities($app['app_name']) . '</div>
                          <div class="app-description">' . htmlentities($app['app_description']) . '</div>
                          
                          <!-- User Action Buttons (Bottom) -->
                          <div class="user-actions">
                            <button class="btn btn-sm btn-primary btn-access-app" data-url="' . htmlentities($app['app_url']) . '" data-id="' . $app['app_id'] . '" data-name="' . htmlentities($app['app_name']) . '">
                              <i class="fas fa-external-link-alt"></i> Akses
                            </button>
                            <button class="btn btn-sm btn-primary btn-manage-credential" data-id="' . $app['app_id'] . '" data-name="' . htmlentities($app['app_name']) . '" data-credential-id="' . (isset($user_credentials[$app['app_id']]) ? $user_credentials[$app['app_id']] : '0') . '">
                              <i class="fas fa-key"></i> Kredensial
                            </button>
                          </div>
                        </div>
                      </div>';
            }
          } else {
            echo '
                    </div>
                  </div>
                </div>
                
                <!-- Empty state full width -->
                <div class="empty-state-full">
                  <div class="text-center">
                    ' . ($data_role['modifikasi'] == 'Y' ? '
                    <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                    <h4>Belum Ada Aplikasi</h4>
                    <p class="text-muted">Klik "Tambah Aplikasi" untuk mulai menambahkan portal aplikasi.</p>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalAddApp">
                      <i class="fas fa-plus mr-1"></i> Tambah Aplikasi Pertama
                    </button>
                    ' : '
                    <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                    <h4>Belum Ada Aplikasi</h4>
                    <p class="text-muted">Saat ini belum ada portal aplikasi yang tersedia.</p>
                    ') . '
                  </div>
                </div>
                
                <div class="card-body" style="display:none;">
                  <div class="portal-apps-grid">
                    <div class="row" id="appsContainer">';
          }
          
          echo '
                    </div>
                  </div>
                </div>';
        } else {
          // show 404 theme when user doesn't have view or modify permission
          if (function_exists('theme_404')) {
            theme_404();
          } else {
            header("HTTP/1.0 404 Not Found");
            echo '<div class="container mt-5"><h3>404 Not Found</h3><p>Anda tidak memiliki hak akses untuk melihat halaman ini.</p></div>';
            exit;
          }
        }
        echo '
          </div>
        </div>
      </div>';
?>
      
      <!-- Modal Add/Edit App -->
      <?php if ($data_role['modifikasi'] == 'Y'): ?>
      <div class="modal fade" id="modalAddApp" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">
                <i class="fas fa-plus-circle mr-2"></i>
                <span id="modalTitle">Tambah Aplikasi Portal</span>
              </h5>
              <button type="button" class="close text-white" data-dismiss="modal">
                <span>&times;</span>
              </button>
            </div>
            <form id="formApp" class="needs-validation" novalidate>
              <div class="modal-body">
                <input type="hidden" id="app_id" name="app_id">
                
                <div class="row">
                  <div class="col-md-8">
                    <div class="form-group">
                      <label for="app_name" class="form-label required">Nama Aplikasi</label>
                      <input type="text" class="form-control" id="app_name" name="app_name" required>
                      <div class="invalid-feedback">Nama aplikasi harus diisi</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="app_category" class="form-label">Kategori</label>
                      <select class="form-control" id="app_category" name="app_category">
                        <option value="education">Pendidikan</option>
                        <option value="government">Pemerintahan</option>
                        <option value="communication">Komunikasi</option>
                        <option value="productivity">Produktivitas</option>
                        <option value="other">Lainnya</option>
                      </select>
                    </div>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="app_url" class="form-label required">URL Aplikasi</label>
                  <input type="url" class="form-control" id="app_url" name="app_url" placeholder="https://" required>
                  <div class="invalid-feedback">URL aplikasi harus diisi dan valid</div>
                </div>
                
                <div class="row">
                  <div class="col-md-8">
                    <div class="form-group">
                      <label class="form-label required">Icon Aplikasi</label>
                      <div class="card">
                        <div class="card-body">
                          <!-- Icon Selection Section -->
                          <div class="icon-selection-wrapper">
                            <!-- FontAwesome Icon Option -->
                            <div class="icon-option">
                              <div class="form-check">
                                <input class="form-check-input" type="radio" name="icon_option" id="iconOptionFont" value="font" checked>
                                <label class="form-check-label font-weight-bold" for="iconOptionFont">
                                  <i class="fab fa-font-awesome mr-2"></i>FontAwesome Icon
                                </label>
                              </div>
                              <div class="icon-input-group mt-2" id="fontIconGroup">
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                    <span class="input-group-text" id="iconPreview">
                                      <i class="fas fa-globe"></i>
                                    </span>
                                  </div>
                                  <input type="text" class="form-control" id="app_icon" name="app_icon" value="fas fa-globe" placeholder="fas fa-graduation-cap">
                                </div>
                                <small class="form-text text-muted">
                                  Contoh: fas fa-graduation-cap, fas fa-database, fab fa-google
                                </small>
                              </div>
                            </div>
                            
                            <!-- Upload Icon Option -->
                            <div class="icon-option mt-3">
                              <div class="form-check">
                                <input class="form-check-input" type="radio" name="icon_option" id="iconOptionUpload" value="upload">
                                <label class="form-check-label font-weight-bold" for="iconOptionUpload">
                                  <i class="fas fa-upload mr-2"></i>Upload Custom Icon
                                </label>
                              </div>
                              <div class="icon-input-group mt-2" id="uploadIconGroup" style="display: none;">
                                <div class="custom-file">
                                  <input type="file" class="custom-file-input" id="icon_file" name="icon_file" accept=".png,.jpg,.jpeg">
                                  <label class="custom-file-label" for="icon_file">Pilih file icon (PNG/JPG, max 1MB)</label>
                                </div>
                                <div class="mt-2" id="uploadPreview" style="display:none;">
                                  <img id="previewImg" src="" class="img-thumbnail" style="max-width: 60px; max-height: 60px;">
                                  <button type="button" class="btn btn-sm btn-danger ml-2" id="removePreview">
                                    <i class="fas fa-trash"></i>
                                  </button>
                                </div>
                                <small class="form-text text-muted">
                                  File maksimal 1MB, format PNG/JPG untuk hasil terbaik
                                </small>
                              </div>
                            </div>
                          </div>
                          
                          <input type="hidden" id="icon_type" name="icon_type" value="font">
                          <input type="hidden" id="current_icon_file" name="current_icon_file">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="sort_order" class="form-label">Urutan</label>
                      <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="0">
                      <small class="form-text text-muted">0 = otomatis</small>
                    </div>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="app_description" class="form-label">Deskripsi</label>
                  <textarea class="form-control" id="app_description" name="app_description" rows="2" placeholder="Deskripsi singkat aplikasi"></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                  <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                  <i class="fas fa-save"></i> Simpan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>
    
    <!-- Modal Manage Credentials -->
    <div class="modal fade" id="modalCredential" tabindex="-1" role="dialog" aria-labelledby="modalCredentialLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="modalCredentialLabel">Kelola Kredensial</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="formCredential" novalidate>
              <input type="hidden" id="credential_id" name="credential_id">
              <input type="hidden" id="credential_app_id" name="app_id">
              
              <div class="form-group">
                <label for="credential_app_name" class="form-label">Aplikasi</label>
                <input type="text" class="form-control" id="credential_app_name" readonly>
              </div>
              
              <div class="form-group">
                <label for="app_username" class="form-label required">Username / Email</label>
                <input type="text" class="form-control" id="app_username" name="app_username" required>
                <div class="invalid-feedback">Username harus diisi</div>
              </div>
              
              <div class="form-group">
                <label for="app_password" class="form-label required">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="app_password" name="app_password" required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
                <div class="invalid-feedback">Password harus diisi</div>
              </div>
              
              <div class="form-group">
                <label for="notes" class="form-label">Catatan (Opsional)</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan tambahan untuk kredensial ini..."></textarea>
              </div>
              
              <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <small>Password akan dienkripsi dan disimpan dengan aman. Hanya Anda yang dapat mengakses kredensial ini.</small>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteCredential" style="display:none;">
              <i class="fas fa-trash"></i> Hapus
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" form="formCredential" class="btn btn-success" id="btnSaveCredential">
              <i class="fas fa-save"></i> Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
      
      <!-- Portal GTK JavaScript -->
      <script src="mod/portal-gtk/scripts.js"></script>
      <script>
      document.querySelector(".btn-update-gtk")?.addEventListener("click", function(e){
        e.preventDefault();
        var btn = this;
        if (btn.disabled) return;
        if (!confirm("Yakin ingin memperbarui aplikasi?")) return;
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Mengupdate...';
        var csrf = btn.getAttribute("data-csrf");
        fetch("./mod/lisensi_pembaruan/proses.php?action=deploy&csrf=" + encodeURIComponent(csrf))
          .then(function(r){ return r.json(); })
          .then(function(d){
            if(d.success) { location.reload(); }
            else { alert("Gagal: " + (d.message||"Error")); btn.disabled=false; btn.innerHTML='<i class="fas fa-cloud-download-alt mr-1"></i> Update'; }
          }).catch(function(){ alert("Gagal terhubung ke server."); btn.disabled=false; btn.innerHTML='<i class="fas fa-cloud-download-alt mr-1"></i> Update'; });
      });
      </script>
<?php
        break;
    }
  } else {
    theme_404();
  }
}
