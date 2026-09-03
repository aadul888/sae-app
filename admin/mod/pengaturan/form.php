<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';

  // Ambil setting pertama dari database (tidak hardcode)
  $real_site_id = 1;
  $_sid_res = $connection->query("SELECT site_id FROM setting ORDER BY site_id ASC LIMIT 1");
  if ($_sid_res && $_sid_res->num_rows > 0) {
    $real_site_id = intval($_sid_res->fetch_assoc()['site_id']);
  }

  // Fetch semua kolom setting ke variabel untuk form id=1
  $setting = [];
  $_set = $connection->query("SELECT * FROM setting WHERE site_id={$real_site_id} LIMIT 1");
  if ($_set && $_row = $_set->fetch_assoc()) {
    $setting = $_row;
  }
  $site_name        = isset($setting['site_name']) ? $setting['site_name'] : '';
  $site_owner       = isset($setting['site_owner']) ? $setting['site_owner'] : '';
  $site_phone       = isset($setting['site_phone']) ? $setting['site_phone'] : '';
  $site_email       = isset($setting['site_email']) ? $setting['site_email'] : '';
  $site_address     = isset($setting['site_address']) ? $setting['site_address'] : '';
  $site_url         = isset($setting['site_url']) ? $setting['site_url'] : '';
  $site_logo        = !empty($setting['site_logo']) ? $setting['site_logo'] : 'logoweb1.png';
  $site_logo2       = !empty($setting['site_logo2']) ? $setting['site_logo2'] : 'logoweb2.png';
  $site_favicon     = !empty($setting['site_favicon']) ? $setting['site_favicon'] : 'favicon.png';
  $site_kop         = !empty($setting['site_kop']) ? $setting['site_kop'] : 'kopsekolah.jpg';

  // Susun alamat dari semua kolom DB
  $addr_parts = [];
  if (!empty($setting['alamat_jalan'])) $addr_parts[] = trim($setting['alamat_jalan']);
  
  $rtrw = [];
  if (!empty($setting['rt'])) $rtrw[] = 'RT ' . trim($setting['rt']);
  if (!empty($setting['rw'])) $rtrw[] = 'RW ' . trim($setting['rw']);
  if (!empty($rtrw)) $addr_parts[] = implode('/', $rtrw);

  if (!empty($setting['dusun'])) $addr_parts[] = 'Dusun ' . trim($setting['dusun']);
  if (!empty($setting['desa_kelurahan'])) $addr_parts[] = 'Desa/Kel. ' . trim($setting['desa_kelurahan']);
  if (!empty($setting['kecamatan'])) $addr_parts[] = trim($setting['kecamatan']);
  if (!empty($setting['kabupaten_kota'])) $addr_parts[] = trim($setting['kabupaten_kota']);
  if (!empty($setting['provinsi'])) $addr_parts[] = trim($setting['provinsi']);
  if (!empty($setting['kode_pos'])) $addr_parts[] = trim($setting['kode_pos']);

  if (!empty($addr_parts)) {
    $site_address = implode(', ', $addr_parts);
  }

  if (htmlspecialchars($_GET['id']) == 1) {
    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-info" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-globe"></i></span>
          <span class="alert-inner--text">
            <strong>Pengaturan Website</strong><br>
            Kelola informasi dasar website dan konfigurasi umum sistem.
          </span>
        </div>
      </div>
    </div>
    
    <form class="form-setting" role="form" method="post" action="javascript:void(0)" autocomplete="off">
      <div class="row">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h4 class="mb-0">
                <i class="fas fa-cog mr-2 text-primary"></i>
                Informasi Dasar Website
              </h4>
            </div>
            <div class="card-body">
              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Nama Website</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="site_name" value="' . strip_tags($site_name) . '" required>
                  <small class="form-text text-muted">Nama website yang akan ditampilkan</small>
                </div>
              </div>

              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Pemilik</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="site_owner" value="' . strip_tags($site_owner) . '" required>
                  <small class="form-text text-muted">Nama pemilik atau institusi</small>
                </div>
              </div>

              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">No. Telepon</label>
                <div class="col-md-9">
                  <input type="tel" class="form-control" name="site_phone" value="' . strip_tags($site_phone) . '" required>
                  <small class="form-text text-muted">Nomor telepon untuk kontak</small>
                </div>
              </div>
              
              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Email</label>
                <div class="col-md-9">
                  <input type="email" class="form-control" name="site_email" value="' . strip_tags($site_email) . '" required>
                  <small class="form-text text-muted">Email resmi untuk kontak dan notifikasi</small>
                </div>
              </div>

              <!-- Alamat Lengkap (single form) -->
              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Alamat Lengkap</label>
                <div class="col-md-9">
                  <textarea class="form-control" rows="3" name="site_address" id="site_address" placeholder="Jl. Contoh No. 1, RT 01/RW 02, Desa X, Kec. Y, Kota Z, Prov. AA 12345">' . strip_tags($site_address) . '</textarea>
                  <small class="form-text text-muted">Masukan alamat lengkap (jalan, RT/RW, dusun, desa, kecamatan, kabupaten, provinsi, kode pos). Digunakan di kop surat dan dokumen lainnya.</small>
                </div>
              </div>
              <div class="form-group row">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Domain/URL</label>
                <div class="col-md-9">
                  <input type="url" class="form-control" name="site_url" value="' . strip_tags($site_url) . '" required>
                  <small class="form-text text-muted">URL lengkap website (contoh: https://example.com)</small>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Logo Settings Card -->
          <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
              <h4 class="mb-0">
                <i class="fas fa-images mr-2 text-primary"></i>
                Pengaturan Logo & Favicon
              </h4>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-control-label font-weight-bold">Logo Utama <small class="text-muted">(Berwarna)</small></label>
                    <div class="card border">
                      <div class="card-body text-center p-3" style="background-color: #e8e8e8;">';
    if (file_exists("../../../content/$site_logo")) {
      echo '<img src="../content/' . $site_logo . '?v=' . filemtime("../../../content/$site_logo") . '" class="img-fluid" style="max-height: 80px;">';
    } else {
      echo '<img src="./assets/img/media.png" class="img-fluid" style="max-height: 80px;">';
    }
    echo '
                      </div>
                      <div class="card-footer p-2">
                        <label class="btn btn-outline-primary btn-sm btn-block mb-0">
                          <i class="fas fa-upload mr-1"></i> Pilih Logo
                          <input type="file" class="upload logo d-none" name="file" accept=".jpg,.jpeg,.gif,.png">
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-control-label font-weight-bold">Logo Kedua <small class="text-muted">(Putih)</small></label>
                    <div class="card border">
                      <div class="card-body text-center p-3" style="background-color: #1e293b;">';
    if (file_exists("../../../content/$site_logo2")) {
      echo '<img src="../content/' . $site_logo2 . '?v=' . filemtime("../../../content/$site_logo2") . '" class="img-fluid" style="max-height: 80px;">';
    } else {
      echo '<img src="./assets/img/media.png" class="img-fluid" style="max-height: 80px;">';
    }
    echo '
                      </div>
                      <div class="card-footer p-2">
                        <label class="btn btn-outline-primary btn-sm btn-block mb-0">
                          <i class="fas fa-upload mr-1"></i> Pilih Logo
                          <input type="file" class="upload logo2 d-none" name="file2" accept=".jpg,.jpeg,.gif,.png">
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-control-label font-weight-bold">Favicon</label>
                    <div class="card border">
                      <div class="card-body text-center p-3">';
    if (file_exists("../../../content/$site_favicon")) {
      echo '<img src="../content/' . $site_favicon . '?v=' . filemtime("../../../content/$site_favicon") . '" class="img-fluid" style="max-height: 80px;">';
    } else {
      echo '<img src="./assets/img/media.png" class="img-fluid" style="max-height: 80px;">';
    }
    echo '
                      </div>
                      <div class="card-footer p-2">
                        <label class="btn btn-outline-primary btn-sm btn-block mb-0">
                          <i class="fas fa-upload mr-1"></i> Pilih Favicon
                          <input type="file" class="upload favicon d-none" name="file" accept=".jpg,.jpeg,.gif,.png,.ico">
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-control-label font-weight-bold">Kop Sekolah</label>
                    <div class="card border">
                      <div class="card-body text-center p-3">';
    if (file_exists("../../../content/$site_kop")) {
      echo '<img src="../content/' . $site_kop . '?v=' . filemtime("../../../content/$site_kop") . '" class="img-fluid" style="max-height: 80px;">';
    } else {
      echo '<img src="./assets/img/media.png" class="img-fluid" style="max-height: 80px;">';
    }
    echo '
                      </div>
                      <div class="card-footer p-2">
                        <label class="btn btn-outline-primary btn-sm btn-block mb-0">
                          <i class="fas fa-upload mr-1"></i> Pilih Kop Sekolah
                          <input type="file" class="upload kop d-none" name="file_kop" accept=".jpg,.jpeg,.gif,.png">
                        </label>
                      </div>
                    </div>
                  </div>
                </div>                
              </div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="card shadow-sm mt-4">
            <div class="card-body">
              <div class="text-right">
                <button class="btn btn-secondary mr-2" type="reset">
                  <i class="fas fa-undo mr-2"></i>Reset
                </button>
                <button class="btn btn-primary btn-save" type="submit">
                  <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>';
    // ...existing code...
  }

  elseif (htmlspecialchars($_GET['id']) == 5) {
    // Tab ini telah dipindahkan ke Maintenance
    echo '<div class="alert alert-warning"><i class="fas fa-info-circle mr-2"></i>Fitur Buka/Tutup Sistem telah dipindahkan ke tab <strong>Maintenance</strong>.</div>';
  }

  elseif (htmlspecialchars($_GET['id']) == 2) {
    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-success" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-plus-circle"></i></span>
          <span class="alert-inner--text">
            <strong>Manajemen Modul</strong><br>
            Kelola modul sistem untuk mengontrol akses dan navigasi.
          </span>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-12">
        <div class="card shadow">
          <div class="card-header bg-white">
            <h4 class="mb-0">
              <i class="fas fa-plus-circle mr-2 text-success"></i>
              Tambah/Edit Modul
            </h4>
          </div>
          <div class="card-body">
            <form class="form-modul" method="post" action="javascript:void(0)" autocomplete="off">
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label class="form-control-label font-weight-bold">Nama Modul</label>
                    <input type="hidden" name="modul_id" id="modul_id">
                    <input type="text" class="form-control" name="modul_nama" id="modul_nama" placeholder="Masukkan nama modul" required>
                    <small class="form-text text-muted">Nama modul yang akan muncul di menu navigasi</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-control-label">&nbsp;</label>
                  <div class="form-group">
                    <button class="btn btn-success btn-save btn-block" type="submit">
                      <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                    <button class="btn btn-outline-secondary btn-block" type="reset">
                      <i class="fas fa-undo mr-2"></i>Reset
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
        
        <div class="card shadow mt-4">
          <div class="card-header bg-white">
            <h4 class="mb-0">
              <i class="fas fa-list mr-2 text-primary"></i>
              Daftar Modul
            </h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="80">ID</th>
                    <th>Nama Modul</th>
                    <th class="text-center" width="120">Aksi</th>
                  </tr>
                </thead>
                <tbody>';
    // Pagination settings
    $per_page = 5;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;

    // Get total count
    $result_count = $connection->query("SELECT COUNT(*) AS cnt FROM modul");
    $total = 0;
    if ($result_count && $rc = $result_count->fetch_assoc()) {
      $total = intval($rc['cnt']);
    }
    $total_pages = ($total > 0) ? intval(ceil($total / $per_page)) : 1;
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $per_page;

    $result_modul = $connection->query("SELECT * FROM modul ORDER BY modul_id ASC LIMIT $offset, $per_page");
    if ($result_modul && $result_modul->num_rows > 0) {
      while ($row = $result_modul->fetch_assoc()) {
        echo '<tr>
                        <td class="text-center"><span class="badge badge-primary">' . strip_tags($row['modul_id']) . '</span></td>
                        <td>
                          <div class="media align-items-center">
                            <div class="media-body">
                              <span class="font-weight-bold">' . strip_tags($row['modul_nama']) . '</span>
                            </div>
                          </div>
                        </td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-outline-warning btn-edit-modul" data-id="' . strip_tags($row['modul_id']) . '" data-nama="' . htmlspecialchars($row['modul_nama']) . '" title="Edit">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-sm btn-outline-danger btn-delete-modul ml-1" data-id="' . strip_tags($row['modul_id']) . '" title="Hapus">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>';
      }
    } else {
      echo '<tr><td colspan="3" class="text-center text-muted py-4">
                      <i class="fas fa-inbox fa-2x mb-2"></i><br>
                      Belum ada data modul
                    </td></tr>';
    }

    // Pagination controls
    if ($total_pages > 1) {
      echo '<tr><td colspan="3">';
      echo '<nav aria-label="Pagination Modul"><ul class="pagination justify-content-center mb-0">';
      // Previous
      if ($page > 1) {
        $prev = $page - 1;
        echo '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadSetting(2,' . $prev . ')">&laquo; </a></li>';
      } else {
        echo '<li class="page-item disabled"><span class="page-link">&laquo; </span></li>';
      }
      // Page numbers (limit to a reasonable range)
      $start_page = max(1, $page - 3);
      $end_page = min($total_pages, $page + 3);
      for ($p = $start_page; $p <= $end_page; $p++) {
        if ($p == $page) {
          echo '<li class="page-item active"><span class="page-link">' . $p . '</span></li>';
        } else {
          echo '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadSetting(2,' . $p . ')">' . $p . '</a></li>';
        }
      }
      // Next
      if ($page < $total_pages) {
        $next = $page + 1;
        echo '<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadSetting(2,' . $next . ')"> &raquo;</a></li>';
      } else {
        echo '<li class="page-item disabled"><span class="page-link"> &raquo;</span></li>';
      }
      echo '</ul></nav>';
      echo '</td></tr>';
    }
    echo '
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      </div>
    </div>';
  }

  /** Pengaturan Server Email */
  elseif (htmlspecialchars($_GET['id']) == 3) {
    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-warning" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-envelope"></i></span>
          <span class="alert-inner--text">
            <strong>Konfigurasi Email Server</strong><br>
            Atur server email untuk notifikasi sistem dan login Google OAuth.
          </span>
        </div>
      </div>
    </div>
    
    <form class="form-setting-server" role="form" method="post" action="javascript:void(0)" autocomplete="off">
      <div class="row">
        <div class="col-lg-6">
          <div class="card shadow">
            <div class="card-header bg-white">
              <h4 class="mb-0">
                <i class="fas fa-server mr-2 text-warning"></i>
                Pengaturan SMTP Server
              </h4>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-control-label font-weight-bold">Email/Username SMTP</label>
                <input type="email" class="form-control" name="gmail_username" value="' . $gmail_username . '" placeholder="your-email@gmail.com" required>
                <small class="form-text text-muted">Email yang akan digunakan untuk mengirim notifikasi</small>
              </div>

              <div class="form-group">
                <label class="form-control-label font-weight-bold">Password SMTP</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="gmail_password" value="' . $gmail_password . '" placeholder="App Password atau Password SMTP" required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
                <small class="form-text text-muted">Gunakan App Password untuk Gmail</small>
              </div>

              <div class="form-group">
                <label class="form-control-label font-weight-bold">Host Server</label>
                <input type="text" class="form-control" name="gmail_host" value="' . $gmail_host . '" placeholder="smtp.gmail.com" required>
                <small class="form-text text-muted">Host server SMTP</small>
              </div>

              <div class="form-group">
                <label class="form-control-label font-weight-bold">Port Server</label>
                <input type="number" class="form-control" name="gmail_port" value="' . $gmail_port . '" placeholder="587" required>
                <small class="form-text text-muted">Port server SMTP (587 untuk TLS, 465 untuk SSL)</small>
              </div>

              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="gmail_active" name="gmail_active" value="Y"';
    if ($gmail_active == 'Y') echo ' checked';
    echo '>
                  <label class="custom-control-label" for="gmail_active">
                    <span class="font-weight-bold">Aktifkan Email Server</span>
                    <br><small class="text-muted">Enable untuk menggunakan fitur email</small>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card shadow">
            <div class="card-header bg-white">
              <h4 class="mb-0">
                <i class="fab fa-google mr-2 text-danger"></i>
                Google OAuth API
              </h4>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-control-label font-weight-bold">Google Client ID</label>
                <input type="text" class="form-control" name="google_client_id" value="' . $google_client_id . '" placeholder="Google OAuth Client ID">
                <small class="form-text text-muted">Client ID dari Google Cloud Console</small>
              </div>

              <div class="form-group">
                <label class="form-control-label font-weight-bold">Google Client Secret</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="google_client_secret" value="' . $google_client_secret . '" placeholder="Google OAuth Client Secret">
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
                <small class="form-text text-muted">Secret key dari Google Cloud Console</small>
              </div>

              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="google_client_active" name="google_client_active" value="Y"';
    if ($google_client_active == 'Y') echo ' checked';
    echo '>
                  <label class="custom-control-label" for="google_client_active">
                    <span class="font-weight-bold">Aktifkan Google Login</span>
                    <br><small class="text-muted">Enable untuk login dengan Google</small>
                  </label>
                </div>
              </div>

              <div class="alert alert-info">
                <h6><i class="fas fa-info-circle mr-1"></i>Setup Google OAuth</h6>
                <ol class="mb-0">
                  <li>Buka Google Cloud Console</li>
                  <li>Buat project baru atau pilih existing</li>
                  <li>Enable Google+ API</li>
                  <li>Buat OAuth 2.0 Client ID</li>
                  <li>Copy Client ID dan Secret</li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <button class="btn btn-outline-info mr-2" type="button" onclick="testEmail()">
                    <i class="fas fa-paper-plane mr-2"></i>Test Email
                  </button>
                  <button class="btn btn-secondary mr-2" type="reset">
                    <i class="fas fa-undo mr-2"></i>Reset
                  </button>
                </div>
                <button class="btn btn-warning btn-save" type="submit">
                  <i class="fas fa-save mr-2"></i>Simpan Konfigurasi
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>';
  }

  /** Backup Database */
  elseif (htmlspecialchars($_GET['id']) == 4) {
    echo '
<div class="row">
  <div class="col-12">
    <div class="alert alert-info" role="alert">
      <span class="alert-inner--icon"><i class="fas fa-download"></i></span>
      <span class="alert-inner--text">
        <strong>Backup & Restore Data</strong><br>
        Kelola backup database dan file sistem untuk keamanan data.
      </span>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header bg-white">
        <h4 class="mb-0">
          <i class="fas fa-database mr-2 text-primary"></i>
          Backup Database
        </h4>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p class="text-muted mb-4">Buat backup database untuk mengamankan semua data sistem. Backup akan berformat SQL yang dapat digunakan untuk restore.</p>
            
            <div class="alert alert-warning">
              <i class="fas fa-info-circle mr-2"></i>
              <strong>Info:</strong> Backup akan mencakup semua tabel dan data dalam database.
            </div>
          </div>
          <div class="col-md-6 text-center">
            <div class="mb-3">
              <i class="fas fa-database fa-4x text-primary mb-3"></i>
              <h5>Database Backup</h5>
            </div>
            <a href="./mod/pengaturan/proses.php?action=backup-database" class="btn btn-primary btn-lg">
              <i class="fas fa-download mr-2"></i>Download Backup Database
            </a>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card shadow mt-4">
      <div class="card-header bg-white">
        <h4 class="mb-0">
          <i class="fas fa-folder mr-2 text-success"></i>
          Backup File System
        </h4>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p class="text-muted mb-3">Backup file sistem fokus pada aset penting di folder <strong>content</strong>. Anda bisa memilih aset yang ingin diunduh.</p>

            <div class="mb-3">
              <label class="font-weight-bold d-block mb-2" for="backupAssetFolder">Pilih aset content:</label>
              <select id="backupAssetFolder" class="custom-select backup-asset-folder">
                <option value="agenda" selected>agenda</option>
                <option value="avatar">avatar</option>
                <option value="berkas">berkas</option>
                <option value="capture">capture</option>
                <option value="pelanggaran">pelanggaran</option>
                <option value="usulan-pip">usulan-pip</option>
              </select>
              <small class="form-text text-muted mt-2">Satu folder diproses setiap kali unduh.</small>
            </div>
            
            <div class="alert alert-info">
              <i class="fas fa-info-circle mr-2"></i>
              <strong>Catatan:</strong> Sistem akan membuat ZIP untuk satu folder yang dipilih. Proses selesai akan otomatis memulai unduhan.
            </div>
          </div>
          <div class="col-md-6 text-center">
            <div class="mb-3">
              <i class="fas fa-folder-open fa-4x text-success mb-3"></i>
              <h5>File System Backup</h5>
              <small id="backupAssetSummary" class="text-muted d-block">Aset terpilih: agenda</small>
            </div>
            <button class="btn btn-success btn-lg" onclick="backupFiles()">
              <i class="fas fa-download mr-2"></i>Download Backup Files
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card shadow mt-4">
      <div class="card-header bg-white">
        <h4 class="mb-0">
          <i class="fas fa-upload mr-2 text-warning"></i>
          Restore Database
        </h4>
      </div>
      <div class="card-body">
        <form id="restoreForm" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-6">
              <p class="text-muted mb-4">Upload file backup SQL untuk mengembalikan database ke kondisi sebelumnya.</p>
              
              <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Peringatan:</strong> Restore akan menimpa data yang ada. Pastikan sudah melakukan backup terlebih dahulu.
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Pilih File Backup (.sql)</label>
                <div class="custom-file">
                  <input type="file" class="custom-file-input" id="backupFile" name="backup_file" accept=".sql">
                  <label class="custom-file-label" for="backupFile">Choose file...</label>
                </div>
              </div>
              <button type="submit" class="btn btn-warning btn-block">
                <i class="fas fa-upload mr-2"></i>Restore Database
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- sidebar backup removed -->
  <div class="d-none">
    <div class="card">
      <div class="card-header">
        <h3>Riwayat Backup</h3>
      </div>
      <div class="card-body">
        <div class="timeline timeline-sm">
          <div class="timeline-item">
            <span class="timeline-point bg-success"></span>
            <div class="timeline-content">
              <h6 class="mb-1">backup_2025_09_03.sql</h6>
              <small class="text-muted">3 September 2025, 13:30</small>
            </div>
          </div>
          <div class="timeline-item">
            <span class="timeline-point bg-info"></span>
            <div class="timeline-content">
              <h6 class="mb-1">backup_2025_09_02.sql</h6>
              <small class="text-muted">2 September 2025, 14:15</small>
            </div>
          </div>
          <div class="timeline-item">
            <span class="timeline-point bg-warning"></span>
            <div class="timeline-content">
              <h6 class="mb-1">backup_2025_09_01.sql</h6>
              <small class="text-muted">1 September 2025, 10:45</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card bg-secondary shadow mt-4">
      <div class="card-header bg-white border-0">
        <h3 class="mb-0"><i class="fas fa-info-circle text-primary mr-2"></i>Tips Backup</h3>
      </div>
      <div class="card-body">
        <ul class="list-unstyled">
          <li class="mb-2"><i class="fas fa-clock text-primary mr-2"></i>Lakukan backup secara rutin</li>
          <li class="mb-2"><i class="fas fa-hdd text-primary mr-2"></i>Simpan backup di lokasi aman</li>
          <li class="mb-2"><i class="fas fa-shield-alt text-primary mr-2"></i>Test restore secara berkala</li>
          <li class="mb-2"><i class="fas fa-cloud text-primary mr-2"></i>Gunakan cloud storage</li>
        </ul>
        
        <div class="alert alert-success mt-3">
          <small>
            <i class="fas fa-lightbulb mr-1"></i>
            <strong>Pro Tip:</strong> Jadwalkan backup otomatis menggunakan cron job untuk keamanan maksimal.
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Hapus Data Sync Dapodik -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header bg-white">
        <h4 class="mb-0">
          <i class="fas fa-eraser mr-2 text-danger"></i>
          Hapus Data Sync Dapodik
        </h4>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p class="text-muted mb-4">Hapus seluruh data hasil sinkronisasi dari Dapodik. Data yang dihapus meliputi tabel sync_sekolah, sync_gtk, sync_peserta_didik, sync_rombongan_belajar, sync_pengguna, sync_pembelajaran, sync_anggota_rombel, dan log sinkronisasi.</p>
            
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan. Data yang dihapus tidak bisa dikembalikan. Pastikan sudah melakukan backup database sebelum menghapus.
            </div>
          </div>
          <div class="col-md-6 text-center">
            <div class="mb-3">
              <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
              <h5>Hapus Semua Data Sync</h5>
              <small class="text-muted d-block">7 tabel sync + log akan dikosongkan</small>
            </div>
            <button class="btn btn-danger btn-lg" onclick="clearSyncData()">
              <i class="fas fa-eraser mr-2"></i>Hapus Data Sync
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
';
  }

  /** WhatsApp Gateway */
  elseif (htmlspecialchars($_GET['id']) == 7) {
    // Ambil konfigurasi WhatsApp dari database
    $result_wa = $connection->query("SELECT * FROM whatsapp_config WHERE id = 1");
    $wa_config = array(
      'api_url' => '',
      'api_key' => '', 
      'status' => 'N'
    );
    
    if ($result_wa && $result_wa->num_rows > 0) {
      $wa_config = $result_wa->fetch_assoc();
    }
    
    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-info" role="alert">
          <span class="alert-inner--icon"><i class="fab fa-whatsapp"></i></span>
          <span class="alert-inner--text">
            <strong>WhatsApp Gateway</strong><br>
            Konfigurasi API WhatsApp untuk mengirim notifikasi aktivitas sistem seperti verifikasi nomor, reset password, dll.
          </span>
        </div>
      </div>
    </div>
    
    <form class="form-whatsapp-gateway" role="form" method="post" action="javascript:void(0)" autocomplete="off">
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-header bg-white">
              <h4 class="mb-0"><i class="fab fa-whatsapp mr-2 text-success"></i>Konfigurasi WhatsApp Gateway</h4>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-control-label font-weight-bold">API URL</label>
                <input type="url" class="form-control" name="api_url" value="' . htmlspecialchars($wa_config['api_url']) . '" placeholder="https://wa51292.oneapi.my.id/api/v1/messages" required>
                <small class="form-text text-muted">URL endpoint API WhatsApp Gateway (contoh: OneAPI, OneSender, Fonnte, Wablas, dll)</small>
                <div class="alert alert-success mt-2">
                  <strong><i class="fas fa-check-circle mr-1"></i> Format OneAPI Terverifikasi:</strong><br>
                  <small>
                    &bull; API URL: https://wa51292.oneapi.my.id/api/v1/messages<br>
                    &bull; Format: WhatsApp Business API JSON<br>
                    &bull; Header: Authorization Bearer {token}<br>
                    &bull; Structure: messaging_product, to, type, text.body
                  </small>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-control-label font-weight-bold">API Key / Token</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="api_key" value="' . htmlspecialchars($wa_config['api_key']) . '" placeholder="your-api-token" required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
                <small class="form-text text-muted">Token API dari provider WhatsApp Gateway Anda</small>
              </div>
              
              <div class="form-group">
                <label class="form-control-label font-weight-bold">Status Gateway</label>
                <div class="mt-4 pl-4">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="status" id="wa_status" value="Y"';
    if ($wa_config['status'] == 'Y') echo ' checked';
    echo '>
                    <label class="custom-control-label" for="wa_status">
                      <strong>Aktifkan WhatsApp Gateway</strong>
                      <small class="d-block text-muted mt-1">Mengizinkan sistem mengirim notifikasi via WhatsApp</small>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="card shadow-sm mt-4">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i>Simpan Konfigurasi
                  </button>
                  <button type="button" class="btn btn-success btn-sm" onclick="testWhatsApp()">
                    <i class="fas fa-paper-plane mr-1"></i>Test Kirim Pesan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- sidebar wa removed -->
        <div class="d-none">
          <div class="card">
            <div class="card-header">
              <h4>Informasi</h4>
            </div>
            <div class="card-body">
              <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle mr-1"></i>Penting!</h6>
                <ul class="mb-0 small">
                  <li>Pastikan API WhatsApp Gateway Anda aktif</li>
                  <li>Simpan API Key/Token dengan aman</li>
                  <li>Test koneksi sebelum mengaktifkan</li>
                </ul>
              </div>
              
              <div class="alert alert-info">
                <h6><i class="fas fa-cog mr-1"></i>Kegunaan</h6>
                <ul class="mb-0 small">
                  <li>Verifikasi nomor WhatsApp</li>
                  <li>Notifikasi reset password</li>
                  <li>Alert aktivitas login</li>
                  <li>Notifikasi sistem lainnya</li>
                </ul>
              </div>
              
              <div class="card bg-gradient-success text-white">
                <div class="card-body p-3">
                  <div class="row align-items-center">
                    <div class="col">
                      <h6 class="text-white mb-2">Status Gateway</h6>
                      <span class="h4 font-weight-bold mb-0" id="wa-status">';
    if ($wa_config['status'] == 'Y') {
      echo 'Aktif';
    } else {
      echo 'Nonaktif';
    }
    echo '</span>
                    </div>
                    <div class="col-auto">
                      <i class="fab fa-whatsapp fa-2x"></i>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Statistik Singkat -->
              <div class="card mt-3">
                <div class="card-body p-3">
                  <h6>Statistik Hari Ini</h6>';
    
    // Ambil statistik hari ini
    $result_today = $connection->query("SELECT 
      COUNT(*) as total,
      SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
      SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
      FROM whatsapp_logs WHERE DATE(created_at) = CURDATE()");
    
    $stats = array('total' => 0, 'sent' => 0, 'failed' => 0);
    if ($result_today && $result_today->num_rows > 0) {
      $stats = $result_today->fetch_assoc();
    }
    
    echo '
                  <div class="row text-center">
                    <div class="col-4">
                      <div class="text-primary font-weight-bold">' . $stats['total'] . '</div>
                      <small>Total</small>
                    </div>
                    <div class="col-4">
                      <div class="text-success font-weight-bold">' . $stats['sent'] . '</div>
                      <small>Terkirim</small>
                    </div>
                    <div class="col-4">
                      <div class="text-danger font-weight-bold">' . $stats['failed'] . '</div>
                      <small>Gagal</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>';
  } elseif (htmlspecialchars($_GET['id']) == 8) {
    $maintenance_status = 'open';
    $maintenance_result = $connection->query("SELECT maintenance_status FROM setting ORDER BY site_id ASC LIMIT 1");
    if ($maintenance_result && $maintenance_result->num_rows > 0) {
      $maintenance_row = $maintenance_result->fetch_assoc();
      $maintenance_status = strtolower(trim((string) ($maintenance_row['maintenance_status'] ?? 'open')));
      if ($maintenance_status !== 'closed') {
        $maintenance_status = 'open';
      }
    }

    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-warning" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-tools"></i></span>
          <span class="alert-inner--text">
            <strong>Pengaturan Maintenance</strong><br>
            Kontrol apakah halaman maintenance ditampilkan atau tidak. Default sistem adalah terbuka.
          </span>
        </div>
      </div>
    </div>

    <form class="form-maintenance" role="form" method="post" action="javascript:void(0)" autocomplete="off">
      <div class="row">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h4 class="mb-0">
                <i class="fas fa-tools mr-2 text-warning"></i>
                Status Halaman Maintenance
              </h4>
            </div>
            <div class="card-body">
              <div class="form-group row align-items-center">
                <label class="col-md-3 col-form-label form-control-label font-weight-bold">Status</label>
                <div class="col-md-9">
                  <select class="form-control" name="maintenance_status" required>
                    <option value="open"' . ($maintenance_status === 'open' ? ' selected' : '') . '>Terbuka - Maintenance tidak ditampilkan</option>
                    <option value="closed"' . ($maintenance_status === 'closed' ? ' selected' : '') . '>Tutup - Tampilkan halaman maintenance</option>
                  </select>
                  <small class="form-text text-muted">Jika belum pernah diatur, sistem akan memakai status terbuka.</small>
                </div>
              </div>
            </div>
          </div>

          <div class="card shadow-sm mt-4">
            <div class="card-body">
              <div class="text-right">
                <button class="btn btn-secondary mr-2" type="reset">
                  <i class="fas fa-undo mr-2"></i>Reset
                </button>
                <button class="btn btn-warning btn-save" type="submit">
                  <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </form>';
  }
}
