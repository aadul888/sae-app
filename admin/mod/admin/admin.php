<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 38;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {


    switch (@$_GET['op']) {
      default:
        $admin_total = 0;
        $admin_active = 0;
        $admin_nonactive = 0;
        $admin_tugas = 0;

        if ($q = $connection->query("SELECT COUNT(*) AS total FROM admin")) {
          $r = $q->fetch_assoc();
          $admin_total = intval($r['total'] ?? 0);
        }
        if ($q = $connection->query("SELECT COUNT(*) AS total FROM admin WHERE active='Y'")) {
          $r = $q->fetch_assoc();
          $admin_active = intval($r['total'] ?? 0);
        }
        if ($q = $connection->query("SELECT COUNT(*) AS total FROM admin WHERE active='N'")) {
          $r = $q->fetch_assoc();
          $admin_nonactive = intval($r['total'] ?? 0);
        }
        if ($q = $connection->query("SELECT COUNT(*) AS total FROM admin WHERE tugas_tambahan IS NOT NULL AND tugas_tambahan<>''")) {
          $r = $q->fetch_assoc();
          $admin_tugas = intval($r['total'] ?? 0);
        }

        echo '
<!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-3"></div>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--6 user-module-page module-user-like-page">';

        echo '
      <div class="row">
        <div class="col-12">
          <div class="card user-stats-panel module-stats-shell mb-3">
            <div class="card-body py-2 px-2 px-md-3">
              <div class="user-stats-wrap">
                <div class="user-stats module-stats-grid" id="admin-stat-row">
                  <div class="module-stat-card user-stat-total">
                    <div class="info">
                      <span class="label">Total Admin</span>
                      <span class="value">' . intval($admin_total) . '</span>
                      <span class="sub-info">Seluruh akun admin</span>
                    </div>
                    <div class="icon"><i class="fas fa-users-cog"></i></div>
                  </div>
                  <div class="module-stat-card user-stat-identitas">
                    <div class="info">
                      <span class="label">Admin Aktif</span>
                      <span class="value">' . intval($admin_active) . '</span>
                      <span class="sub-info">Bisa login dan akses modul</span>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                  </div>
                  <div class="module-stat-card user-stat-belum-sesuai">
                    <div class="info">
                      <span class="label">Admin Nonaktif</span>
                      <span class="value">' . intval($admin_nonactive) . '</span>
                      <span class="sub-info">Status akun dimatikan</span>
                    </div>
                    <div class="icon"><i class="fas fa-user-slash"></i></div>
                  </div>
                  <div class="module-stat-card user-stat-belum">
                    <div class="info">
                      <span class="label">Tugas Tambahan</span>
                      <span class="value">' . intval($admin_tugas) . '</span>
                      <span class="sub-info">Admin dengan multi peran</span>
                    </div>
                    <div class="icon"><i class="fas fa-tasks"></i></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <div class="card user-table-panel module-table-card module-user-like-table pb-2">
            <div class="card-header py-3 px-3 user-table-header module-table-header">
              <div class="module-user-like-head">
                <div class="module-user-like-head-main">
                  <h4 class="mb-1">Data Admin</h4>
                  <small class="text-muted">Kelola akun administrator dan hak akses sistem.</small>
                </div>
                <div class="module-user-like-toolbar">';
        if ($data_role['modifikasi'] == 'Y') {
          echo '
    <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterAdmin" title="Filter"><i class="fas fa-filter"></i></button>
    <a href="./hak-akses" class="btn-mod btn-mod-secondary" title="Hak Akses"><i class="fas fa-user-lock"></i></a>
    <button type="button" class="btn-mod btn-mod-success" data-toggle="modal" data-target="#importAdminModal" title="Import Admin"><i class="fas fa-file-import"></i></button>
    <a href="./' . $mod . '?op=add" class="btn-mod btn-mod-add" title="Tambah"><i class="fas fa-plus"></i></a>';
        } else {
          echo '
    <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterAdmin" title="Filter"><i class="fas fa-filter"></i></button>
    <button class="btn-mod btn-mod-secondary" disabled title="Hak Akses"><i class="fas fa-user-lock"></i></button>
    <button class="btn-mod btn-mod-success" disabled title="Import Admin"><i class="fas fa-file-import"></i></button>
    <button class="btn-mod btn-mod-add" disabled title="Tambah"><i class="fas fa-plus"></i></button>';
        }
        echo '
                </div>
              </div>
            </div><!-- end card-header -->';

        // Query level untuk filter
        $query_level_utama = "SELECT level_id, level_nama FROM level WHERE tipe='utama' ORDER BY level_nama ASC";
        $result_level_utama = $connection->query($query_level_utama);
        $query_level_tugas = "SELECT level_id, level_nama FROM level WHERE tipe='tugas' ORDER BY level_nama ASC";
        $result_level_tugas = $connection->query($query_level_tugas);

        $level_utama_opts = '';
        while ($lv = $result_level_utama->fetch_assoc()) {
          $level_utama_opts .= '<option value="' . $lv['level_id'] . '">' . htmlspecialchars($lv['level_nama']) . '</option>';
        }
        $level_tugas_opts = '';
        while ($lt = $result_level_tugas->fetch_assoc()) {
          $level_tugas_opts .= '<option value="' . $lt['level_id'] . '">' . htmlspecialchars($lt['level_nama']) . '</option>';
        }

        echo '
            <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
              <table class="table align-items-center table-flush table-striped datatable-user">
                <thead class="thead-light">
                  <tr>
                      <th width="8">No</th>
                      <th class="text-center">Avatar</th>
                      <th>Nama</th>
                      <th>Email</th>
                      <th>Telp</th>
                      <th>Level Utama</th>
                      <th>Tugas Tambahan</th>
                      <th>Aktif</th>
                      <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>';
        } else {
          hak_akses();
        }
        echo '
            </div>
          </div>
        </div>
      </div>';

        // Modal Import Admin
        echo '
          <!-- Modal Filter Admin -->
          <div class="modal fade" id="modalFilterAdmin" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
              <div class="modal-header"><h5 class="modal-title"><i class="fas fa-filter mr-2"></i>Filter Admin</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
              <div class="modal-body">
                <div class="form-group"><label class="font-weight-bold">Level Utama</label>
                  <select class="form-control" id="filter-level"><option value="">-- Semua Level --</option>' . $level_utama_opts . '</select></div>
                <div class="form-group"><label class="font-weight-bold">Tugas Tambahan</label>
                  <select class="form-control" id="filter-tugas"><option value="">-- Semua Tugas --</option>' . $level_tugas_opts . '</select></div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary btn-sm btn-reset-filter-admin"><i class="fas fa-times mr-1"></i>Reset</button>
                <button class="btn btn-primary btn-sm btn-apply-filter-admin"><i class="fas fa-check mr-1"></i>Terapkan</button>
              </div>
            </div></div>
          </div>
          <div class="modal fade modal-import" id="importAdminModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="importAdminModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-md">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="importAdminModalLabel">Import Data Admin</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <form id="form-import-admin" class="form-import" method="post" action="./' . $mod . '?op=import" enctype="multipart/form-data" autocomplete="off">
                  <div class="modal-body">
                    <div class="form-group">
                      <label>Upload file</label>
                      <input type="file" class="form-control" name="importFile" accept=".xlsx" placeholder="Import data" required>
                    </div>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                      <span class="alert-text">Silahkan Import data dengan template dibawah ini</span>
                      <a href="../content/template-admin.xlsx" class="btn btn-info btn-sm">Download Template</a>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                      </button>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-import-admin-save"><i class="fas fa-file-import"></i> Import</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                  </div>
                </form>
              </div>
            </div>
          </div>';


        /* -------------- Add -------------- */
        break;
      case 'add':
        echo '
    <!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-3"></div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="container-fluid mt--6 user-module-page">
    <div class="card-wrapper">
    <!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Tambah Data Admin</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
        if ($data_role['modifikasi'] == 'Y') {
          echo '
        <form class="form-add" role="form" method="post" action="#" autocomplete="off">
        <div class="form-group row">
            <label  class="col-sm-2 col-form-label">Nama lengkap</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="fullname" required>
            </div>
        </div>

        <div class="form-group row">
            <label  class="col-sm-2 col-form-label">No. Telp</label>
            <div class="col-sm-6">
                <input type="number" class="form-control" name="phone" required>
            </div>
        </div>

        <div class="form-group row">
            <label  class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-6">
                <input type="email" class="form-control email" name="email" required>
                <div class="email-response"></div>
            </div>
        </div>


        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Username</label>
            <div class="col-sm-6">
                <input type="text" class="form-control password" name="username" required>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Password</label>
            <div class="col-sm-6">
              <div class="input-group input-group-merge">
                    <input type="password" class="form-control password" id="password-field"  name="password" required>
                <div class="input-group-append">
                  <span class="input-group-text"><span toggle="#password-field" class="fas fa-eye toggle-password"></span></span>
                </div>
              </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Level Utama</label>
            <div class="col-sm-6">
              <div class="input-group mb-2">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-gradient-primary text-white">
                    <i class="fas fa-user-tag"></i>
                  </span>
                </div>
                <select class="form-control custom-select" name="level" required>
                  <option value="">-- Pilih level utama --</option>';
          $query_level = "SELECT * FROM level WHERE tipe='utama' ORDER BY level_id ASC";
          $result_level = $connection->query($query_level);
          while ($data_level = $result_level->fetch_assoc()) {
            echo '<option value="' . $data_level['level_id'] . '">' . $data_level['level_nama'] . '</option>';
          }
          echo '
                </select>
              </div>
              <small class="form-text text-muted">Level utama menentukan hak akses utama admin.</small>
            </div>
        </div>

        <div class="form-group row">
          <label class="col-sm-2 col-form-label">Tugas Tambahan</label>
          <div class="col-sm-6">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-gradient-info text-white">
                  <i class="fas fa-tasks"></i>
                </span>
              </div>
              <div class="form-control d-flex flex-column" style="height: auto; min-height: 38px; padding: 6px 12px;">
                <!-- Area untuk menampilkan tugas yang dipilih -->
                <div class="selected-tags d-flex flex-wrap align-items-center" style="gap: 6px; min-height: 26px;">
                  <!-- Selected tags will be added here dynamically -->
                  
                  <!-- Dropdown for adding new tasks - inline dengan badges -->
                  <select id="add-tugas-select" class="form-select-inline" style="font-size: 13px; border: 1px dashed #dee2e6; background: #f8f9fa; padding: 4px 8px; margin: 0; min-width: 220px; border-radius: 4px; color: #6c757d;">
                    <option value="">-- Pilih untuk menambah tugas tambahan --</option>';
                    
                    // Get only tugas tambahan levels (tipe='tugas')
                    $query_level_add = "SELECT * FROM level WHERE tipe='tugas' ORDER BY level_id ASC";
                    $result_level_add = $connection->query($query_level_add);
                    while ($data_level_add = $result_level_add->fetch_assoc()) {
                      echo '<option value="' . $data_level_add['level_id'] . '" 
                                   data-name="' . htmlspecialchars($data_level_add['level_nama']) . '"
                                   data-need-jurusan="' . $data_level_add['need_jurusan'] . '">
                                   ' . ucwords($data_level_add['level_nama']) . '
                            </option>';
                    }
                    
                    echo '</select>
                </div>
              </div>
            </div>
            
            <small class="form-text text-muted mt-1">
              <i class="fas fa-info-circle"></i> 
              Pilih dari dropdown untuk menambah tugas tambahan. Klik 
              <i class="fas fa-times text-danger"></i> pada badge untuk menghapus.
            </small>
          </div>
        </div>';

        // Inject jurusan data as hidden JSON for JavaScript
        $query_jurusan = "SELECT jurusan_id, kode_jurusan, nama_jurusan FROM jurusan ORDER BY jurusan_id ASC";
        $result_jurusan = $connection->query($query_jurusan);
        $jurusan_list = array();
        while ($jrs = $result_jurusan->fetch_assoc()) {
          $jurusan_list[] = $jrs;
        }
        echo '<script>var JURUSAN_DATA = ' . json_encode($jurusan_list) . ';</script>';

        echo '
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Aktif</label>
            <div class="col-sm-6">
              <label class="custom-toggle mt-2">
                <input type="checkbox" name="active" value="Y" checked>
                 <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>
            </div>
        </div>
      <hr>

        <div class="form-group row">
            <label class="col-sm-2 col-form-label"></label>
            <div class="col-sm-6">
                <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                <a href="./' . $mod . '" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
            </div>
        </div>
    
      </form>';
        } else {
          hak_akses();
        }
        echo '
      </div>
    </div>
  </div>';


        /** Update Admin/User*/
        break;
      case 'update':
        if (!empty($_GET['id'])) {
          $id     =  anti_injection(epm_decode($_GET['id']));
          $query_user = "SELECT * FROM admin WHERE admin.admin_id='$id'";
          $result_user = $connection->query($query_user);

          echo '
  <div class="header bg-primary pb-4 user-page-header-compact">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-3"></div>
        </div>
      </div>
    </div>

      <!-- Page content -->
      <div class="container-fluid mt--6 user-module-page">
      <div class="card-wrapper">
      <!-- Form controls -->
      <div class="card">
        <!-- Card header -->
        <div class="card-header">
          <h3 class="mb-0">Ubah Data Admin</h3>
        </div>
        <!-- Card body -->
        <div class="card-body">';
          if ($result_user->num_rows > 0) {
            $data_user  = $result_user->fetch_assoc();
            if ($data_role['modifikasi'] == 'Y') {
              echo '
          <form class="form-update" role="form" method="post" action="#" autocomplete="off">
          <input type="hidden" class="d-none" name="id" value="' . epm_encode($data_user['admin_id']) . '" required readonly>
          <div class="form-group row">
          <label  class="col-sm-2 col-form-label">Nama lengkap</label>
              <div class="col-sm-6">
                  <input type="text" class="form-control" name="fullname" value="' . strip_tags($data_user['fullname']) . '" required>
              </div>
          </div>

      <div class="form-group row">
          <label  class="col-sm-2 col-form-label">No. Telp</label>
          <div class="col-sm-6">
              <input type="number" class="form-control" name="phone" value="' . strip_tags($data_user['phone']) . '" required>
          </div>
      </div>

      <div class="form-group row">
          <label  class="col-sm-2 col-form-label">Email</label>
          <div class="col-sm-6">
              <input type="email" class="form-control" name="email" value="' . strip_tags($data_user['email']) . '" required>
          </div>
      </div>


      <div class="form-group row">
          <label class="col-sm-2 col-form-label">Username</label>
          <div class="col-sm-6">
              <input type="text" class="form-control password" name="username" value="' . strip_tags($data_user['username']) . '" required>
          </div>
      </div>



    <div class="form-group row">
      <label class="col-sm-2 col-form-label">Level Utama</label>
      <div class="col-sm-6">
        <div class="input-group mb-2">
          <div class="input-group-prepend">
            <span class="input-group-text bg-gradient-primary text-white"><i class="fas fa-user-tag"></i></span>
          </div>

          <select class="form-control custom-select" name="level" required>
            <option value="">-- Pilih level utama --</option>';
            $level_selected = isset($data_user['level_id']) ? $data_user['level_id'] : '';
            $query_level = "SELECT * FROM level WHERE tipe='utama' ORDER BY level_id ASC";
            $result_level = $connection->query($query_level);
            while ($data_level = $result_level->fetch_assoc()) {
              $selected = ($data_level['level_id'] == $level_selected && $level_selected != '') ? 'selected' : '';
              echo '<option value="' . $data_level['level_id'] . '" ' . $selected . '>' . $data_level['level_nama'] . '</option>';
            }
            echo '
          </select>
        </div>
        <small class="form-text text-muted">Level utama menentukan hak akses utama admin.</small>
      </div>
    </div>

    <div class="form-group row">
      <label class="col-sm-2 col-form-label">Tugas Tambahan</label>
      <div class="col-sm-6">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text bg-gradient-info text-white">
              <i class="fas fa-tasks"></i>
            </span>
          </div>
          <div class="form-control d-flex flex-column" style="height: auto; min-height: 38px; padding: 6px 12px;">
            <!-- Area untuk menampilkan tugas yang dipilih -->
            <div class="selected-tags d-flex flex-wrap align-items-center" style="gap: 6px; min-height: 26px;">';
              
              // Initialize variables
              $selected_tugas = array();
              $available_levels = array();
              
              // Parse existing selected tasks (format: "8:3,9,12:2" where :jurusan_id is optional)
              if (!empty($data_user['tugas_tambahan'])) {
                $selected_tugas_raw = explode(',', $data_user['tugas_tambahan']);
                foreach ($selected_tugas_raw as $item) {
                  $parts = explode(':', $item);
                  $lid = trim($parts[0]);
                  $jid = isset($parts[1]) ? trim($parts[1]) : '';
                  $selected_tugas[] = $lid;
                  if ($jid !== '') {
                    $tugas_jurusan_map[$lid] = $jid;
                  }
                }
              }
              $tugas_jurusan_map = isset($tugas_jurusan_map) ? $tugas_jurusan_map : array();
              
              // Get jurusan lookup
              $jurusan_lookup = array();
              $qjrs = "SELECT jurusan_id, kode_jurusan, nama_jurusan FROM jurusan ORDER BY jurusan_id";
              $rjrs = $connection->query($qjrs);
              while ($jrs = $rjrs->fetch_assoc()) {
                $jurusan_lookup[$jrs['jurusan_id']] = $jrs;
              }
              
              // Get current level utama
              $level_utama = isset($data_user['level_id']) ? $data_user['level_id'] : $data_user['level'];
              
              // Get only tugas tambahan levels (tipe='tugas')
              $query_level2 = "SELECT * FROM level WHERE tipe='tugas' ORDER BY level_id ASC";
              $result_level2 = $connection->query($query_level2);
              
              while ($data_level2 = $result_level2->fetch_assoc()) {
                $available_levels[] = $data_level2;
              }
              
              // Display selected tags
              foreach ($available_levels as $level) {
                if (in_array($level['level_id'], $selected_tugas)) {
                  $tag_label = strtoupper($level['level_nama']);
                  $tag_value = $level['level_id'];
                  $jrs_id = '';
                  // If this level has jurusan assigned, append jurusan info
                  if (isset($tugas_jurusan_map[$level['level_id']])) {
                    $jrs_id = $tugas_jurusan_map[$level['level_id']];
                    if (isset($jurusan_lookup[$jrs_id])) {
                      $tag_label .= ' - ' . $jurusan_lookup[$jrs_id]['kode_jurusan'];
                    }
                    $tag_value = $level['level_id'] . ':' . $jrs_id;
                  }
                  echo '<span class="badge badge-info tugas-tag d-inline-flex align-items-center" 
                          data-level-id="' . $level['level_id'] . '" 
                          data-jurusan-id="' . $jrs_id . '"
                          style="font-size: 12px; padding: 6px 10px; border-radius: 15px; gap: 5px; line-height: 1;">
                          <span>' . $tag_label . '</span>
                          <i class="fas fa-times remove-tag" 
                             style="cursor: pointer; font-size: 10px; opacity: 0.8;" 
                             title="Hapus tugas tambahan"></i>
                          <input type="hidden" name="tugas_tambahan[]" value="' . $tag_value . '">
                        </span>';
                }
              }
              
              echo '
              <!-- Dropdown for adding new tasks - inline dengan badges -->
              <select id="add-tugas-select" class="form-select-inline" style="font-size: 13px; border: 1px dashed #dee2e6; background: #f8f9fa; padding: 4px 8px; margin: 0; min-width: 220px; border-radius: 4px; color: #6c757d;">
                <option value="">-- Pilih untuk menambah tugas tambahan --</option>';
                
                // Display available options in dropdown
                foreach ($available_levels as $level) {
                  if (!in_array($level['level_id'], $selected_tugas)) {
                    echo '<option value="' . $level['level_id'] . '" 
                                 data-name="' . htmlspecialchars($level['level_nama']) . '"
                                 data-need-jurusan="' . ($level['need_jurusan'] ?? 0) . '">
                                 ' . ucwords($level['level_nama']) . '
                          </option>';
                  }
                }
                
                echo '</select>
            </div>
          </div>
        </div>
        
            <small class="form-text text-muted mt-1">
              <i class="fas fa-info-circle"></i> 
              Pilih dari dropdown untuk menambah tugas tambahan. Klik 
              <i class="fas fa-times text-danger"></i> pada badge untuk menghapus.
            </small>
          </div>
        </div>';

        // Inject jurusan data as hidden JSON for JavaScript (update form)
        if (!isset($GLOBALS['jurusan_injected'])) {
          $query_jurusan2 = "SELECT jurusan_id, kode_jurusan, nama_jurusan FROM jurusan ORDER BY jurusan_id ASC";
          $result_jurusan2 = $connection->query($query_jurusan2);
          $jurusan_list2 = array();
          while ($jrs2 = $result_jurusan2->fetch_assoc()) {
            $jurusan_list2[] = $jrs2;
          }
          echo '<script>var JURUSAN_DATA = ' . json_encode($jurusan_list2) . ';</script>';
          $GLOBALS['jurusan_injected'] = true;
        }

        echo '
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Aktif</label>
            <div class="col-sm-6">
              <label class="custom-toggle mt-2">';
              if ($data_user['active'] == 'Y') {
                echo '<input type="checkbox" name="active" value="Y" checked>';
              } else {
                echo '<input type="checkbox" name="active" value="Y">';
              }
              echo '
                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>
            </div>
        </div>
      <hr>

        <div class="form-group row">
            <label class="col-sm-2 col-form-label"></label>
            <div class="col-sm-6">
                <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                <a href="./' . $mod . '" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
            </div>
        </div>
      </form>';
            } else {
              hak_akses();
            }
          } else {
            theme_404();
          }
          echo '
      </div>
    </div>
  </div>';
        }

        break;
    }
    
    
  } else {
    theme_404();
  }
}
