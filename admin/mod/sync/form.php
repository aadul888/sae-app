<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';
  require_once 'proses.php'; // provides get_sync_config_path() for writable temp storage

  // Baca api_key terkini langsung dari DB agar selalu sinkron, tidak bergantung konstanta yang di-cache saat request.
  $_sae_current_api_key = defined('SAE_API_KEY') ? SAE_API_KEY : '';
  if ($connection instanceof mysqli && !$connection->connect_error) {
    $_res = $connection->query("SELECT api_key FROM setting WHERE site_id = 1 LIMIT 1");
    if ($_res && $_row = $_res->fetch_assoc()) {
      $_sae_current_api_key = (string) ($_row['api_key'] ?? $_sae_current_api_key);
    }
  }

  // Halaman Koneksi Dapodik langsung
  if (htmlspecialchars($_GET['id']) == 7) {
    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-info" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-info-circle"></i></span>
          <span class="alert-inner--text">
            <strong>Koneksi:</strong> Konfigurasi token API SAE untuk aplikasi <b>Loader SAE</b>. Loader mengirim data Dapodik ke tabel sync, lalu Tarik Data memindahkan ke data utama.
          </span>
        </div>
      </div>
    </div>

    <!-- Info API untuk Loader SAE -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card shadow border-left-info">
          <div class="card-header py-3 px-3 module-table-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0"><i class="fas fa-plug text-info mr-2"></i>Info API untuk Loader SAE</h5>
            <a href="../content/LoaderSAE-Installer-Full.zip" class="btn btn-sm btn-success" download>
              <i class="fas fa-download mr-1"></i> Unduh Loader SAE
            </a>
          </div>
          <div class="card-body">
            <div class="alert alert-primary" role="alert">
              <i class="fas fa-info-circle mr-2"></i>
              Gunakan Token API di bawah ini pada aplikasi <b>Loader SAE</b> yang dipasang di komputer operator (satu PC dengan Dapodik). Belum punya aplikasi? <a href="../content/LoaderSAE-Installer-Full.zip" class="font-weight-bold text-white text-underline" download><i class="fas fa-download ml-1 mr-1"></i>Unduh Loader SAE di sini</a>.
            </div>
            <div class="form-group mb-0">
              <label class="form-control-label"><strong>Token API SAE</strong></label>
              <div class="input-group">
                <input type="password" class="form-control" id="sae-api-key-display" value="' . htmlspecialchars($_sae_current_api_key) . '" readonly>
                <div class="input-group-append">
                  <button class="btn btn-primary" type="button" onclick="copyToClipboard(\'sae-api-key-display\')" title="Salin Token"><i class="fas fa-copy"></i></button>
                  <button class="btn btn-warning" type="button" id="btn-generate-api-key" title="Generate Token Baru"><i class="fas fa-key"></i></button> <button class="btn btn-secondary" type="button" id="btn-toogle-api-key" title="Lihat/Sembunyikan Token"><i class="far fa-eye"></i></button>
                
<script>
document.getElementById(\'btn-toogle-api-key\').addEventListener(\'click\',function(){var f=document.getElementById(\'sae-api-key-display\');var i=this.querySelector(\'i\');if(f.type===\'password\'){f.type=\'text\';i.classList.remove(\'fa-eye\');i.classList.add(\'fa-eye-slash\');}else{f.type=\'password\';i.classList.remove(\'fa-eye-slash\');i.classList.add(\'fa-eye\');}});
document.getElementById(\'btn-copy-api-key\').addEventListener(\'click\',function(){copyToClipboard(\'sae-api-key-display\');});
</script></div></div><small class="form-text text-muted">Token autentikasi untuk akses API SAE. Loader SAE membutuhkan token ini untuk mengirim data.</small>
            </div>
          </div>
        </div>
      </div>
    </div>';
  }
  // Halaman Tarik Data (sinkronisasi data dari Dapodik)
  elseif (htmlspecialchars($_GET['id']) == 8) {
    // Ambil data log sinkronisasi terbaru untuk setiap endpoint
    $sync_status = [];
    $endpoints = ['getSekolah', 'getGtk', 'getRombonganBelajar', 'getPesertaDidik', 'getPengguna'];
    foreach ($endpoints as $endpoint) {
      $query = "SELECT status, total_records, created_at FROM sync_log WHERE endpoint='$endpoint' ORDER BY created_at DESC LIMIT 1";
      $result = $connection->query($query);
      if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $sync_status[$endpoint] = $row;
      } else {
        $sync_status[$endpoint] = ['status' => 'never', 'total_records' => 0, 'created_at' => null];
      }
    }

    // Hitung total data dari tabel lokal sesuai mapping
    $data_counts = [];
    $local_tables = [
      'getSekolah' => 'sync_sekolah', // Tetap gunakan sync karena tidak ada tabel sekolah lokal
      'getGtk' => 'admin',
      'getRombonganBelajar' => 'kelas', 
      'getPesertaDidik' => 'user',
      'getPengguna' => 'admin'
    ];
    foreach ($local_tables as $endpoint => $table) {
      // Untuk GTK dan Pengguna, hitung yang sudah sync
      if ($endpoint == 'getGtk') {
        $count_query = "SELECT COUNT(DISTINCT ptk_id) as total FROM admin WHERE sync_status = 'synced' AND ptk_id IS NOT NULL AND TRIM(ptk_id) != ''";
      } elseif ($endpoint == 'getPengguna') {
        $count_query = "SELECT COUNT(*) as total FROM $table WHERE sync_status = 'synced'";
      } elseif ($endpoint == 'getRombonganBelajar') {
        // Hanya hitung kelas hasil sync dengan jenis_rombel = '1'
        $count_query = "SELECT COUNT(*) as total FROM $table WHERE sync_status = 'active' AND created_from_sync = 1 AND jenis_rombel = '1'";
      } elseif ($endpoint == 'getPesertaDidik') {
        $count_query = "SELECT COUNT(*) as total FROM $table WHERE rombel_sync_status = 'active'";
      } else {
        $count_query = "SELECT COUNT(*) as total FROM $table";
      }
      $count_result = $connection->query($count_query);
      $data_counts[$endpoint] = $count_result ? $count_result->fetch_assoc()['total'] : 0;
    }
    
    // Hitung total data dari tabel sync untuk data Dapodik
    $sync_counts = [];
    $sync_tables = [
      'getSekolah' => 'sync_sekolah',
      'getGtk' => 'sync_gtk',
      'getRombonganBelajar' => 'sync_rombongan_belajar',
      'getPesertaDidik' => 'sync_peserta_didik',
      'getPengguna' => 'sync_pengguna'
    ];
    foreach ($sync_tables as $endpoint => $table) {
      $count_query = "SELECT COUNT(*) as total FROM $table";
      $count_result = $connection->query($count_query);
      $sync_counts[$endpoint] = $count_result ? $count_result->fetch_assoc()['total'] : 0;
    }

    // Buat endpoint yang menyesuaikan dengan environment
    $server_info = $_SERVER['HTTP_HOST'];
    $is_localhost = (strpos($server_info, 'localhost') !== false || strpos($server_info, '127.0.0.1') !== false);
    $protocol = ($is_localhost ? "http://" : "https://");
    $domain = $server_info;
    $path = "/api/receive-data";
    $api_endpoint = $protocol . $domain . $path;

    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-success" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-database"></i></span>
          <span class="alert-inner--text">
            <strong>Tarik Data Dapodik:</strong> Klik tombol <b>Tarik Data</b> pada masing-masing data untuk memproses data dari Dapodik ke tabel lokal secara mandiri tanpa mengganggu data lainnya.
          </span>
        </div>
      </div>
    </div>

    <div class="card shadow">
      <div class="card-header bg-white py-3 sync-simple-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <h4 class="mb-0"><i class="fas fa-database text-primary mr-2"></i>Tarik Data Dapodik</h4>
          <button class="btn btn-outline-primary btn-sm" id="btnSyncAllData">
            <i class="fas fa-layer-group mr-1"></i> Tarik Semua Data
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th class="border-0">DATA</th>
                <th class="border-0 text-center">JML DATA DAPODIK</th>
                <th class="border-0 text-center">JML DATA LOKAL</th>
                <th class="border-0 text-center">TERAKHIR SYNC</th>
                <th class="border-0 text-center" style="width: 220px;">AKSI</th>
              </tr>
            </thead>
            <tbody>';

    // Data Sekolah
    $sekolah_status = $sync_status['getSekolah']['status'];
    $sekolah_dapodik_count = $sync_counts['getSekolah'];
    $sekolah_local_count = $data_counts['getSekolah'];
    $sekolah_date = $sync_status['getSekolah']['created_at'];

    echo '<tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="icon-wrapper mr-3">
                  <span class="icon-circle bg-success text-white"><i class="fas fa-school"></i></span>
                </div>
                <div>
                  <h6 class="mb-0">Sekolah</h6>
                  <small class="text-muted">Data profil sekolah</small>
                </div>
              </div>
            </td>
            <td class="text-center font-weight-bold">' . $sekolah_dapodik_count . '</td>
            <td class="text-center font-weight-bold text-primary">' . $sekolah_local_count . '</td>
            <td class="text-center">
              <small class="text-muted">' . ($sekolah_date ? date('d/m/Y H:i', strtotime($sekolah_date)) : 'Belum pernah') . '</small>
            </td>
            <td class="text-center">
              <div class="mb-1">';
    if ($sekolah_status == 'success') {
      echo '<small class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Sinkron berhasil</small>';
    } elseif ($sekolah_status == 'failed') {
      echo '<small class="text-danger font-weight-bold"><i class="fas fa-times-circle mr-1"></i>Sinkron gagal</small>';
    } else {
      echo '<small class="text-muted"><i class="fas fa-clock mr-1"></i>Belum sync</small>';
    }
    echo '    </div>
              <button class="btn btn-sm btn-primary btn-sync-single" data-action="getSekolah" data-label="Sekolah">
                <i class="fas fa-download mr-1"></i> Tarik Data
              </button>
            </td>
          </tr>';

    // Data GTK  
    $gtk_status = $sync_status['getGtk']['status'];
    $gtk_dapodik_count = $sync_counts['getGtk'];
    $gtk_local_count = $data_counts['getGtk'];
    $gtk_date = $sync_status['getGtk']['created_at'];

    echo '<tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="icon-wrapper mr-3">
                  <span class="icon-circle bg-warning text-white"><i class="fas fa-chalkboard-teacher"></i></span>
                </div>
                <div>
                  <h6 class="mb-0">GTK</h6>
                  <small class="text-muted">Guru dan Tenaga Kependidikan</small>
                </div>
              </div>
            </td>
            <td class="text-center font-weight-bold">' . $gtk_dapodik_count . '</td>
            <td class="text-center font-weight-bold text-primary">' . $gtk_local_count . '</td>
            <td class="text-center">
              <small class="text-muted">' . ($gtk_date ? date('d/m/Y H:i', strtotime($gtk_date)) : 'Belum pernah') . '</small>
            </td>
            <td class="text-center">
              <div class="mb-1">';
    if ($gtk_status == 'success') {
      echo '<small class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Sinkron berhasil</small>';
    } elseif ($gtk_status == 'failed') {
      echo '<small class="text-danger font-weight-bold"><i class="fas fa-times-circle mr-1"></i>Sinkron gagal</small>';
    } else {
      echo '<small class="text-muted"><i class="fas fa-clock mr-1"></i>Belum sync</small>';
    }
    echo '    </div>
              <button class="btn btn-sm btn-primary btn-sync-single" data-action="getGtk" data-label="GTK">
                <i class="fas fa-download mr-1"></i> Tarik Data
              </button>
            </td>
          </tr>';

    // Data Rombongan Belajar
    $rombel_status = $sync_status['getRombonganBelajar']['status'];
    $rombel_dapodik_count = $sync_counts['getRombonganBelajar'];
    $rombel_local_count = $data_counts['getRombonganBelajar'];
    $rombel_date = $sync_status['getRombonganBelajar']['created_at'];

    echo '<tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="icon-wrapper mr-3">
                  <span class="icon-circle bg-primary text-white"><i class="fas fa-layer-group"></i></span>
                </div>
                <div>
                  <h6 class="mb-0">Rombongan Belajar</h6>
                  <small class="text-muted">Data kelas dan rombongan belajar</small>
                </div>
              </div>
            </td>
            <td class="text-center font-weight-bold">' . $rombel_dapodik_count . '</td>
            <td class="text-center font-weight-bold text-primary">' . $rombel_local_count . '</td>
            <td class="text-center">
              <small class="text-muted">' . ($rombel_date ? date('d/m/Y H:i', strtotime($rombel_date)) : 'Belum pernah') . '</small>
            </td>
            <td class="text-center">
              <div class="mb-1">';
    if ($rombel_status == 'success') {
      echo '<small class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Sinkron berhasil</small>';
    } elseif ($rombel_status == 'failed') {
      echo '<small class="text-danger font-weight-bold"><i class="fas fa-times-circle mr-1"></i>Sinkron gagal</small>';
    } else {
      echo '<small class="text-muted"><i class="fas fa-clock mr-1"></i>Belum sync</small>';
    }
    echo '    </div>
              <button class="btn btn-sm btn-primary btn-sync-single" data-action="getRombonganBelajar" data-label="Rombongan Belajar">
                <i class="fas fa-download mr-1"></i> Tarik Data
              </button>
            </td>
          </tr>';

    // Data Peserta Didik
    $siswa_status = $sync_status['getPesertaDidik']['status'];
    $siswa_dapodik_count = $sync_counts['getPesertaDidik'];
    // Hitung jumlah peserta didik dari tabel user
    $siswa_local_count = 0;
    $count_result = $connection->query("SELECT COUNT(*) as total FROM user");
    if ($count_result) {
      $row = $count_result->fetch_assoc();
      $siswa_local_count = $row['total'];
    }
    $siswa_date = $sync_status['getPesertaDidik']['created_at'];

    echo '<tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="icon-wrapper mr-3">
                  <span class="icon-circle bg-info text-white"><i class="fas fa-user-graduate"></i></span>
                </div>
                <div>
                  <h6 class="mb-0">Peserta Didik</h6>
                  <small class="text-muted">Data siswa dan peserta didik</small>
                </div>
              </div>
            </td>
            <td class="text-center font-weight-bold">' . $siswa_dapodik_count . '</td>
            <td class="text-center font-weight-bold text-primary">' . $siswa_local_count . '</td>
            <td class="text-center">
              <small class="text-muted">' . ($siswa_date ? date('d/m/Y H:i', strtotime($siswa_date)) : 'Belum pernah') . '</small>
            </td>
            <td class="text-center">
              <div class="mb-1">';
    if ($siswa_status == 'success') {
      echo '<small class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Sinkron berhasil</small>';
    } elseif ($siswa_status == 'failed') {
      echo '<small class="text-danger font-weight-bold"><i class="fas fa-times-circle mr-1"></i>Sinkron gagal</small>';
    } else {
      echo '<small class="text-muted"><i class="fas fa-clock mr-1"></i>Belum sync</small>';
    }
    echo '    </div>
              <button class="btn btn-sm btn-primary btn-sync-single" data-action="getPesertaDidik" data-label="Peserta Didik">
                <i class="fas fa-download mr-1"></i> Tarik Data
              </button>
            </td>
          </tr>';

    // Data Pengguna
    $user_status = $sync_status['getPengguna']['status'];
    $user_dapodik_count = $sync_counts['getPengguna'];
    $user_local_count = $data_counts['getPengguna'];
    $user_date = $sync_status['getPengguna']['created_at'];

    echo '<tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="icon-wrapper mr-3">
                  <span class="icon-circle bg-secondary text-white"><i class="fas fa-user-shield"></i></span>
                </div>
                <div>
                  <h6 class="mb-0">Pengguna</h6>
                  <small class="text-muted">Data pengguna sistem Dapodik</small>
                </div>
              </div>
            </td>
            <td class="text-center font-weight-bold">' . $user_dapodik_count . '</td>
            <td class="text-center font-weight-bold text-primary">' . $user_local_count . '</td>
            <td class="text-center">
              <small class="text-muted">' . ($user_date ? date('d/m/Y H:i', strtotime($user_date)) : 'Belum pernah') . '</small>
            </td>
            <td class="text-center">
              <div class="mb-1">';
    if ($user_status == 'success') {
      echo '<small class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Sinkron berhasil</small>';
    } elseif ($user_status == 'failed') {
      echo '<small class="text-danger font-weight-bold"><i class="fas fa-times-circle mr-1"></i>Sinkron gagal</small>';
    } else {
      echo '<small class="text-muted"><i class="fas fa-clock mr-1"></i>Belum sync</small>';
    }
    echo '    </div>
              <button class="btn btn-sm btn-primary btn-sync-single" data-action="getPengguna" data-label="Pengguna">
                <i class="fas fa-download mr-1"></i> Tarik Data
              </button>
            </td>
          </tr>';

    echo '    </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="sae-floating-progress" id="syncFloatingProgress" aria-live="polite" hidden>
      <div class="sae-floating-progress-backdrop"></div>
      <div class="sae-floating-progress-dialog">
        <div class="sae-floating-progress-ring">
          <span id="syncProgressPercent">0%</span>
        </div>
        <div class="sae-floating-progress-copy">
          <strong id="syncProgressLabel">Menyiapkan proses...</strong>
          <span id="syncProgressMeta">Menunggu penarikan data dimulai.</span>
        </div>
        <div class="sae-reg-step-bar-wrap" id="syncStepBarWrap" style="display:none;">
          <div class="progress sae-reg-step-bar-track">
            <div class="progress-bar progress-bar-striped progress-bar-animated sae-reg-step-bar" id="syncStepBar" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
          </div>
          <div class="sae-reg-step-bar-text" id="syncStepBarText">Memproses data...</div>
        </div>
        <div class="sae-floating-progress-warning">
          <i class="fas fa-exclamation-triangle"></i>
          <span>Jangan tutup, refresh, atau pindah halaman selama proses tarik data sedang berjalan.</span>
        </div>
      </div>
    </div>
    ';
  }
  // Halaman Kirim Data ke PKL
  elseif (htmlspecialchars($_GET['id']) == 9) {
    $cfg_file = (function_exists('get_sync_config_path') ? get_sync_config_path() : __DIR__ . '/sync_config.json');
    $cfg = [
      'pkl_base_url' => '',
      'api_token' => '',
      'updated_at' => ''
    ];
    if (file_exists($cfg_file)) {
      $raw_cfg = @file_get_contents($cfg_file);
      $decoded_cfg = json_decode($raw_cfg, true);
      if (is_array($decoded_cfg) && isset($decoded_cfg['pkl']) && is_array($decoded_cfg['pkl'])) {
        $cfg = array_merge($cfg, $decoded_cfg['pkl']);
      }
    }

    $admin_count = 0;
    $admin_q = $connection->query("SELECT COUNT(*) AS total FROM admin WHERE active='Y'");
    if ($admin_q && $admin_q->num_rows > 0) {
      $admin_count = (int) $admin_q->fetch_assoc()['total'];
    }

    $user12_count = 0;
    $user12_sql = "SELECT COUNT(*) AS total
                   FROM user u
                   LEFT JOIN kelas k ON u.kelas = k.kelas_id
                   WHERE LOWER(TRIM(u.status)) = 'aktif'
                     AND (
                       u.tingkat = '12'
                       OR k.tingkat_pendidikan_id = '12'
                       OR UPPER(COALESCE(u.kelas_nama, k.nama_kelas, '')) LIKE 'XII%'
                     )";
    $user12_q = $connection->query($user12_sql);
    if ($user12_q && $user12_q->num_rows > 0) {
      $user12_count = (int) $user12_q->fetch_assoc()['total'];
    }

    echo '
    <div class="sync-pkl-page">
      <div class="row">
        <div class="col-12">
          <div class="alert alert-primary sync-pkl-intro-alert" role="alert">
            <span class="alert-inner--icon"><i class="fas fa-paper-plane"></i></span>
            <span class="alert-inner--text">
              <strong>Kirim Data SAE ke PKL:</strong> Isi URL API PKL dan Token API PKL, lalu kirim data akun <b>admin</b> dan <b>user tingkat 12</b> dari SAE.
            </span>
          </div>
        </div>
      </div>

      <div class="module-stats-grid sync-pkl-stats mb-3">
        <div class="module-stat-card user-stat-total">
          <div class="info">
            <span class="label">Admin Aktif</span>
            <span class="value">' . $admin_count . '</span>
            <span class="sub-info">Akun admin siap sinkron</span>
          </div>
          <div class="icon"><i class="fas fa-user-shield"></i></div>
        </div>
        <div class="module-stat-card user-stat-identitas">
          <div class="info">
            <span class="label">User Tingkat 12</span>
            <span class="value">' . $user12_count . '</span>
            <span class="sub-info">Data siswa tingkat akhir</span>
          </div>
          <div class="icon"><i class="fas fa-user-graduate"></i></div>
        </div>
        <div class="module-stat-card user-stat-belum">
          <div class="info">
            <span class="label">Konfigurasi Terakhir</span>
            <span class="value sync-pkl-small-value">' . (!empty($cfg['updated_at']) ? htmlspecialchars($cfg['updated_at']) : '-') . '</span>
            <span class="sub-info">Waktu penyimpanan endpoint PKL</span>
          </div>
          <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
      </div>

      <div class="card shadow-sm module-table-card sync-pkl-card mb-4">
        <div class="card-header py-3 px-3 module-table-header">
          <div class="module-header-row">
            <div>
              <h5 class="mb-1"><i class="fas fa-link text-primary mr-2"></i>Konfigurasi Koneksi PKL</h5>
              <small class="text-muted">Simpan endpoint PKL dan token API sebelum pengiriman data.</small>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label><strong>URL API PKL</strong></label>
            <input type="password" id="pkl-base-url" class="form-control" placeholder="Contoh: https://pkl.smakpal.sch.id/api/kirim-data" value="' . htmlspecialchars($cfg['pkl_base_url']) . '">
            <small class="text-muted">Endpoint penerima data di PKL (tanpa ekstensi .php). Gunakan URL penuh.</small>
          </div>
          <div class="form-group mb-4">
            <label><strong>Token API PKL</strong></label>
            <input type="password" id="pkl-api-token" class="form-control" placeholder="Masukkan token API dari PKL" value="' . htmlspecialchars($cfg['api_token']) . '">
            <small class="text-muted">Token harus sama dengan yang dibuat pada modul PKL.</small>
          </div>
          <div class="sync-pkl-toolbar">
            <button type="button" class="btn btn-primary sync-pkl-btn" id="btn-save-pkl-config">
              <i class="fas fa-save mr-1"></i>Simpan Konfigurasi
            </button>
            <button type="button" class="btn btn-info sync-pkl-btn" id="btn-test-pkl-config">
              <i class="fas fa-network-wired mr-1"></i>Test Koneksi PKL
            </button>
          </div>
        </div>
      </div>

      <div class="card shadow-sm module-table-card sync-pkl-card mb-4">
        <div class="card-header py-3 px-3 module-table-header">
          <div class="module-header-row">
            <div>
              <h5 class="mb-1"><i class="fas fa-database text-success mr-2"></i>Aksi Kirim Data</h5>
              <small class="text-muted">Kirim data secara bertahap: admin terlebih dulu, lalu user tingkat 12.</small>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="sync-pkl-toolbar">
            <button type="button" class="btn btn-success sync-pkl-btn" id="btn-send-admin-pkl">
              <i class="fas fa-user-shield mr-1"></i>Kirim Data Admin
            </button>
            <button type="button" class="btn btn-success sync-pkl-btn" id="btn-send-user12-pkl">
              <i class="fas fa-user-graduate mr-1"></i>Kirim Data User Tingkat 12
            </button>
          </div>
        </div>
      </div>

      <div class="card shadow">
        <div class="card-body">
          <p class="mb-1"><strong>Direct Login SAE &rarr; PKL:</strong></p>
          <small class="text-muted">Gunakan endpoint SAE berikut untuk menu Portal GTK/applain: <code>/sso/redirect_student</code> &mdash; URL API PKL yang perlu diisi: <code>https://[domain-pkl]/api/kirim-data</code></small>
        </div>
      </div>
    </div>';
  }
}
