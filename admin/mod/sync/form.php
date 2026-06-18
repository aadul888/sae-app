<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');
  require_once '../../login/user.php';

  // Halaman Koneksi Dapodik langsung
  if (htmlspecialchars($_GET['id']) == 7) {
    $server_info = $_SERVER['HTTP_HOST'];
    $is_localhost = (strpos($server_info, 'localhost') !== false || strpos($server_info, '127.0.0.1') !== false);

    $dapodik_config_file = __DIR__ . '/dapodik_sync_config.json';
    $dapodik_config = [
      'base_url' => 'http://localhost:5774',
      'npsn' => '20252031',
      'token' => '',
      'timeout' => 30,
      'updated_at' => ''
    ];
    if (file_exists($dapodik_config_file)) {
      $raw_dapodik = @file_get_contents($dapodik_config_file);
      $decoded_dapodik = json_decode($raw_dapodik, true);
      if (is_array($decoded_dapodik)) {
        $dapodik_config = array_merge($dapodik_config, $decoded_dapodik);
      }
    }

    $connection_file = __DIR__ . '/connection_status.json';
    $connection_status = 'Belum Diuji';
    if (file_exists($connection_file)) {
      $raw_connection = @file_get_contents($connection_file);
      $decoded_connection = json_decode($raw_connection, true);
      if (is_array($decoded_connection) && !empty($decoded_connection['connection_status'])) {
        $connection_status = $decoded_connection['connection_status'];
      }
    }

    $status_badge = '<span class="badge badge-secondary px-3 py-2" id="statusAPI"><i class="fas fa-question-circle mr-1"></i>BELUM DIUJI</span>';
    if ($connection_status === 'berhasil') {
      $status_badge = '<span class="badge badge-success px-3 py-2" id="statusAPI"><i class="fas fa-check-circle mr-1"></i>BERHASIL</span>';
    } elseif ($connection_status === 'gagal') {
      $status_badge = '<span class="badge badge-danger px-3 py-2" id="statusAPI"><i class="fas fa-times-circle mr-1"></i>GAGAL</span>';
    }

    echo '
    <div class="row">
      <div class="col-12">
        <div class="alert alert-info" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-info-circle"></i></span>
          <span class="alert-inner--text">
            <strong>Koneksi Dapodik Langsung:</strong> Halaman ini dipakai untuk mengisi konfigurasi Dapodik dan menarik data langsung tanpa loader, baik saat SAE berjalan lokal maupun di hosting.
          </span>
        </div>
      </div>
    </div>

    <!-- Konfigurasi Dapodik -->
    <div class="row mb-4">
      <div class="col-lg-6">
        <div class="card shadow border-left-primary">
          <div class="card-header py-3 px-3 module-table-header">
            <h5 class="mb-0"><i class="fas fa-network-wired text-primary mr-2"></i>Test Koneksi Dapodik</h5>
          </div>
          <div class="card-body">
            <div class="mb-3 text-center">
              <label class="form-label d-block"><strong>Environment:</strong></label>
              <span class="badge badge-' . ($is_localhost ? 'warning' : 'success') . ' px-3 py-2">
                <i class="fas fa-' . ($is_localhost ? 'desktop' : 'cloud') . ' mr-1"></i>' . ($is_localhost ? 'Localhost' : 'Hosting') . '
              </span>
              <small class="text-muted ml-2">' . $server_info . '</small>
            </div>
            
            <div class="mb-3 text-center">
              <label class="form-label d-block"><strong>Status Koneksi:</strong></label>
              ' . $status_badge . '
            </div>

            <div class="form-group">
              <label class="form-control-label"><strong>URL Dapodik</strong></label>
              <input type="text" class="form-control" id="dapodik-base-url" value="' . htmlspecialchars($dapodik_config['base_url']) . '" placeholder="http://localhost:5774">
              <small class="form-text text-muted">Gunakan URL Dapodik yang bisa dijangkau dari server SAE.</small>
            </div>

            <div class="form-group">
              <label class="form-control-label"><strong>NPSN</strong></label>
              <input type="text" class="form-control" id="dapodik-npsn" value="' . htmlspecialchars($dapodik_config['npsn']) . '" placeholder="20252031">
            </div>

            <div class="form-group mb-3">
              <label class="form-control-label"><strong>Token Dapodik</strong></label>
              <input type="text" class="form-control" id="dapodik-token" value="' . htmlspecialchars($dapodik_config['token']) . '" placeholder="Masukkan token Dapodik">
            </div>
            
            <div class="row">
              <div class="col-6">
                <button type="button" class="btn btn-primary btn-block" id="btn-test-api">
                  <i class="fas fa-play-circle mr-1"></i>Test Koneksi
                </button>
              </div>
              <div class="col-6">
                <button type="button" class="btn btn-info btn-block" id="btn-generate-key">
                  <i class="fas fa-save mr-1"></i>Simpan
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card shadow border-left-success">
          <div class="card-header py-3 px-3 module-table-header">
            <h5 class="mb-0"><i class="fas fa-cog text-success mr-2"></i>Catatan Penting</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              <strong>Penting:</strong> Simpan URL, token, dan NPSN Dapodik yang benar. Jika server SAE online, URL Dapodik harus dapat diakses dari hosting.
            </div>

            <div class="alert alert-light border">
              <i class="fas fa-sync-alt mr-1 text-success"></i>
              Setelah konfigurasi disimpan, gunakan tab <strong>Tarik Data</strong> untuk mengambil data langsung dari Dapodik ke tabel sync lalu ke data utama.
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
        $count_query = "SELECT COUNT(*) as total FROM $table WHERE sync_status = 'synced'";
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
    $path = $is_localhost ? "/saev4/api/receive-data" : "/api/receive-data";
    $api_endpoint = $protocol . $domain . $path;

    echo '
    <style>
      #syncFloatingProgress {
        --registrasi-progress: 0%;
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      #syncFloatingProgress[hidden] {
        display: none !important;
      }

      #syncFloatingProgress .sae-floating-progress-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.34);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
      }

      #syncFloatingProgress .sae-floating-progress-dialog {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        width: min(540px, calc(100vw - 2rem));
        max-height: calc(100vh - 2rem);
        overflow: auto;
        padding: 1.35rem 1.2rem 1.15rem;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        opacity: 0;
        transform: translateY(18px);
        transition: opacity 0.2s ease, transform 0.2s ease;
      }

      #syncFloatingProgress.is-visible .sae-floating-progress-dialog {
        opacity: 1;
        transform: translateY(0);
      }

      #syncFloatingProgress .sae-floating-progress-ring {
        position: relative;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: conic-gradient(#2563eb var(--registrasi-progress), rgba(226, 232, 240, 0.95) 0);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      #syncFloatingProgress .sae-floating-progress-ring::before {
        content: "";
        position: absolute;
        inset: 7px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.98);
      }

      #syncFloatingProgress .sae-floating-progress-ring span {
        position: relative;
        z-index: 1;
        color: #0f172a;
        font-size: 0.86rem;
        font-weight: 800;
      }

      #syncFloatingProgress .sae-floating-progress-copy {
        display: grid;
        gap: 0.15rem;
        text-align: center;
      }

      #syncFloatingProgress .sae-floating-progress-copy strong {
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 800;
      }

      #syncFloatingProgress .sae-floating-progress-copy span {
        color: #64748b;
        font-size: 0.84rem;
        line-height: 1.55;
      }

      #syncFloatingProgress .sae-floating-progress-warning {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        width: 100%;
        padding: 0.85rem 0.95rem;
        border-radius: 18px;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.18);
        color: #92400e;
        font-size: 0.88rem;
        line-height: 1.55;
      }

      #syncFloatingProgress .sae-reg-step-bar-wrap {
        width: 100%;
      }

      #syncFloatingProgress .sae-reg-step-bar-track {
        width: 100%;
        max-width: none;
        height: 10px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(203, 213, 225, 0.55);
      }

      #syncFloatingProgress .sae-reg-step-bar {
        min-width: 0;
        transition: width 0.18s linear;
      }

      #syncFloatingProgress .sae-reg-step-bar-text {
        margin-top: 0.45rem;
        color: #1e3a8a;
        font-weight: 600;
        text-align: center;
        display: none;
      }

      #syncFloatingProgress .sae-floating-progress-stream {
        width: 100%;
        min-height: 64px;
      }

      #syncFloatingProgress .sae-floating-progress-stream-empty {
        padding: 0.9rem 1rem;
        border-radius: 16px;
        background: rgba(248, 250, 252, 0.92);
        border: 1px dashed rgba(148, 163, 184, 0.26);
        color: #64748b;
        font-size: 0.88rem;
        text-align: center;
      }

      #syncFloatingProgress .sae-floating-progress-item {
        position: relative;
        padding: 0.8rem 0.9rem 0.75rem;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        margin-top: 0.45rem;
      }

      #syncFloatingProgress .sae-floating-progress-item::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: #cbd5e1;
      }

      #syncFloatingProgress .sae-floating-progress-item.is-running::before {
        background: linear-gradient(180deg, #2563eb, #0ea5e9);
      }

      #syncFloatingProgress .sae-floating-progress-item.is-success::before {
        background: #16a34a;
      }

      #syncFloatingProgress .sae-floating-progress-item.is-failed::before {
        background: #dc2626;
      }

      #syncFloatingProgress .sae-floating-progress-item-head {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.25rem;
        color: #0f172a;
      }

      #syncFloatingProgress .sae-floating-progress-item-head strong {
        font-size: 0.88rem;
        font-weight: 800;
      }

      #syncFloatingProgress .sae-progress-icon {
        width: 1rem;
        text-align: center;
        color: #2563eb;
        font-size: 0.78rem;
      }

      #syncFloatingProgress .sae-floating-progress-item.is-success .sae-progress-icon {
        color: #16a34a;
      }

      #syncFloatingProgress .sae-floating-progress-item.is-failed .sae-progress-icon {
        color: #dc2626;
      }

      #syncFloatingProgress .sae-floating-progress-item em,
      #syncFloatingProgress .sae-floating-progress-item span {
        display: block;
        font-style: normal;
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.45;
      }

      #syncFloatingProgress .sae-floating-progress-item em {
        margin-top: 0.18rem;
      }
    </style>

    <div class="row">
      <div class="col-12">
        <div class="alert alert-success" role="alert">
          <span class="alert-inner--icon"><i class="fas fa-database"></i></span>
          <span class="alert-inner--text">
            <strong>Tarik Data Dapodik Terbaru:</strong> Klik satu tombol <b>Tarik Data Dapodik</b> untuk menarik semua data terbaru dari Dapodik dan memprosesnya ke tabel lokal secara berurutan.
          </span>
        </div>
      </div>
    </div>

    <div class="card shadow">
      <div class="card-header bg-white">
        <div class="row align-items-center">
          <div class="col">
            <h4 class="mb-0"><i class="fas fa-database text-primary mr-2"></i>Kelola Data Sinkronisasi</h4>
          </div>
          <div class="col-auto">
            <button class="btn btn-success btn-sm mr-2" id="btnSyncAllData">
              <i class="fas fa-download mr-1"></i> Tarik Data Dapodik
            </button>
            <button class="btn btn-primary btn-sm" id="btnRefreshStatus">
              <i class="fas fa-sync-alt mr-1"></i> Refresh Status
            </button>
          </div>
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
                <th class="border-0 text-center">STATUS</th>
                <th class="border-0 text-center">AKSI</th>
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
            <td class="text-center">';

    if ($sekolah_status == 'success') {
      echo '<span class="badge badge-success px-3 py-2">Lengkap</span>';
    } elseif ($sekolah_status == 'failed') {
      echo '<span class="badge badge-danger px-3 py-2">Gagal</span>';
    } else {
      echo '<span class="badge badge-secondary px-3 py-2">Belum Sync</span>';
    }

    echo '</td>
            <td class="text-center">
              <small class="text-muted">
                <i class="fas fa-info-circle mr-1"></i>
                Data Display Only
              </small>
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
            <td class="text-center">';

    if ($gtk_status == 'success') {
      echo '<span class="badge badge-success px-3 py-2">Lengkap</span>';
    } elseif ($gtk_status == 'failed') {
      echo '<span class="badge badge-danger px-3 py-2">Gagal</span>';
    } else {
      echo '<span class="badge badge-secondary px-3 py-2">Belum Sync</span>';
    }

    echo '</td>
            <td class="text-center"><small class="text-muted">Diproses otomatis</small></td>
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
            <td class="text-center">';

    if ($rombel_status == 'success') {
      echo '<span class="badge badge-success px-3 py-2">Lengkap</span>';
    } elseif ($rombel_status == 'failed') {
      echo '<span class="badge badge-danger px-3 py-2">Gagal</span>';
    } else {
      echo '<span class="badge badge-secondary px-3 py-2">Belum Sync</span>';
    }

    echo '</td>
            <td class="text-center"><small class="text-muted">Diproses otomatis</small></td>
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
            <td class="text-center">';

    if ($siswa_status == 'success') {
      echo '<span class="badge badge-success px-3 py-2">Lengkap</span>';
    } elseif ($siswa_status == 'failed') {
      echo '<span class="badge badge-danger px-3 py-2">Gagal</span>';
    } else {
      echo '<span class="badge badge-secondary px-3 py-2">Belum Sync</span>';
    }

    echo '</td>
            <td class="text-center"><small class="text-muted">Diproses otomatis</small></td>
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
            <td class="text-center">';

    if ($user_status == 'success') {
      echo '<span class="badge badge-success px-3 py-2">Lengkap</span>';
    } elseif ($user_status == 'failed') {
      echo '<span class="badge badge-danger px-3 py-2">Gagal</span>';
    } else {
      echo '<span class="badge badge-secondary px-3 py-2">Belum Sync</span>';
    }

    echo '</td>
            <td class="text-center"><small class="text-muted">Diproses otomatis</small></td>
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
        <div class="sae-floating-progress-stream" id="syncProgressStream">
          <div class="sae-floating-progress-stream-empty">Menunggu aktivitas penarikan data dari server.</div>
        </div>
      </div>
    </div>
    ';
  }
  // Halaman Kirim Data ke PKL
  elseif (htmlspecialchars($_GET['id']) == 9) {
    $cfg_file = __DIR__ . '/pkl_sync_config.json';
    $cfg = [
      'pkl_base_url' => '',
      'api_token' => '',
      'updated_at' => ''
    ];
    if (file_exists($cfg_file)) {
      $raw_cfg = @file_get_contents($cfg_file);
      $decoded_cfg = json_decode($raw_cfg, true);
      if (is_array($decoded_cfg)) {
        $cfg = array_merge($cfg, $decoded_cfg);
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
            <input type="text" id="pkl-base-url" class="form-control" placeholder="Contoh: https://pkl.smakpal.sch.id/api/kirim-data" value="' . htmlspecialchars($cfg['pkl_base_url']) . '">
            <small class="text-muted">Endpoint penerima data di PKL (tanpa ekstensi .php). Gunakan URL penuh.</small>
          </div>
          <div class="form-group mb-4">
            <label><strong>Token API PKL</strong></label>
            <input type="text" id="pkl-api-token" class="form-control" placeholder="Masukkan token API dari PKL" value="' . htmlspecialchars($cfg['api_token']) . '">
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
