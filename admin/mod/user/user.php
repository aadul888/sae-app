<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 3;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
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
    <!-- Adjusted container spacing to match berkas layout -->
    <div class="container-fluid mt--6 user-module-page">';

        // Statistik singkat (apply berkas-style, centered)
        // Hitung total siswa dan jumlah berdasarkan jenis kelamin
        // Jika user saat ini adalah wali kelas, batasi statistik ke kelas-kelas yang diaampu
        $kelas_filter = '';
        $wali_kelas_ids = array();
        $admin_identifier = '';
        if (!empty($_COOKIE['ADMIN_KEY'])) {
          $admin_identifier = @epm_decode($_COOKIE['ADMIN_KEY']);
          $admin_identifier = anti_injection($admin_identifier);
        }

        // Jika level 4 (wali) gunakan referensi pada tabel `kelas` untuk mencari kelas yang diasuh
        if (isset($level_id) && intval($level_id) === 4 && $admin_identifier !== '') {
          $q_ck = $connection->query("SELECT kelas_id FROM kelas WHERE nama_wali_kelas='" . $connection->real_escape_string($admin_identifier) . "' OR wali_kelas_nama='" . $connection->real_escape_string($admin_identifier) . "'");
          if ($q_ck && $q_ck->num_rows > 0) {
            while ($r_ck = $q_ck->fetch_assoc()) {
              if (!empty($r_ck['kelas_id'])) $wali_kelas_ids[] = intval($r_ck['kelas_id']);
            }
          }
        }

        // Jika belum ada kelas, coba cari dari tabel `user` (kolom `wali_kelas`)
        if (empty($wali_kelas_ids) && $admin_identifier !== '') {
          $q_u = $connection->query("SELECT DISTINCT kelas FROM user WHERE wali_kelas = " . intval($admin_identifier) . " AND kelas IS NOT NULL AND kelas <> ''");
          if ($q_u && $q_u->num_rows > 0) {
            while ($ru = $q_u->fetch_assoc()) {
              if (!empty($ru['kelas'])) $wali_kelas_ids[] = intval($ru['kelas']);
            }
          }
        }

        // Selain level 4: jika level 3 dengan tugas_tambahan yang mengandung '4', juga kumpulkan kelas dari user.wali_kelas
        if (empty($wali_kelas_ids) && isset($level_id) && intval($level_id) === 3) {
          $tt_val = '';
          if (!empty($tugas_csv)) $tt_val = $tugas_csv;
          if ($tt_val !== '' && (preg_match('/\b4\b/', $tt_val) || preg_match('/(^|[,;\/|])\s*4\s*($|[,;\/|])/', $tt_val))) {
            $q_u2 = $connection->query("SELECT DISTINCT kelas FROM user WHERE wali_kelas = " . intval($admin_identifier) . " AND kelas IS NOT NULL AND kelas <> ''");
            if ($q_u2 && $q_u2->num_rows > 0) {
              while ($r2 = $q_u2->fetch_assoc()) {
                if (!empty($r2['kelas'])) $wali_kelas_ids[] = intval($r2['kelas']);
              }
            }
          }
        }

        if (!empty($wali_kelas_ids)) {
          $in = implode(',', array_map('intval', array_values(array_unique($wali_kelas_ids))));
          if ($in !== '') $kelas_filter = " AND kelas IN (" . $in . ")";
        }

        $query_stats = "SELECT 
            COUNT(*) AS total, 
            SUM(CASE WHEN jenis_kelamin='Laki-laki' THEN 1 ELSE 0 END) AS laki,
            SUM(CASE WHEN jenis_kelamin='Perempuan' THEN 1 ELSE 0 END) AS perempuan
          FROM user
          WHERE status='Aktif'" . $kelas_filter;
        $result_stats = $connection->query($query_stats);
        $total_users = 0;
        $laki = 0;
        $perempuan = 0;
        if ($result_stats && $row_stats = $result_stats->fetch_assoc()) {
          $total_users = intval($row_stats['total']);
          $laki = intval($row_stats['laki']);
          $perempuan = intval($row_stats['perempuan']);
        }

        echo '
          <div class="row">
            <div class="col-12">
              <div class="card user-stats-panel module-stats-shell mb-3">
                <div class="card-body py-2 px-2 px-md-3">
                  <div class="user-stats-wrap">
                    <div class="user-stats module-stats-grid" id="user-stat-row"> 
                  <div id="user-stat-total" class="user-stat-card module-stat-card user-stat-total">
                    <div class="info">
                      <span class="label">Total Siswa</span>';
        echo '      <span class="value">' . $total_users . '</span>';
        echo '      <div class="sub-info"><small><i class="fas fa-mars" aria-hidden="true"></i> : ' . $laki . ' &nbsp;&nbsp;<i class="fas fa-venus" aria-hidden="true"></i> : ' . $perempuan . '</small></div>';
        echo '    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                  </div>
                  <div id="user-stat-identitas" class="user-stat-card module-stat-card user-stat-identitas">
                    <div class="info">
                      <span class="label">Identitas Sesuai</span>
                      <span class="value">0</span>
                    </div>
                    <div class="icon"><i class="fas fa-id-card"></i></div>
                  </div>
                  <div id="user-stat-belum-sesuai" class="user-stat-card module-stat-card user-stat-belum-sesuai">
                    <div class="info">
                      <span class="label">Belum Sesuai</span>
                      <span class="value">0</span>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                  </div>
                  <div id="user-stat-belum" class="user-stat-card module-stat-card user-stat-belum">
                    <div class="info">
                      <span class="label">Belum Konfirmasi</span>
                      <span class="value">0</span>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>';

        echo '<input type="hidden" class="filter-kelas" value="">';
        if ($data_role['lihat'] == 'Y') {
          echo '
            <div class="card user-table-panel module-table-card pb-2">
              <div class="card-header py-3 px-3 user-table-header module-table-header">
                <div class="user-table-head-row module-header-row" style="gap:10px;">
                  <div>
                    <h4 class="mb-1">Daftar Murid Aktif</h4>
                    <small class="text-muted">Kelola data murid aktif, verifikasi, dan pembaruan data.</small>
                  </div>
                  <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
                    ';
          if (isset($level_id) && intval($level_id) === 1) {
            echo '
                    <button type="button" class="btn-mod btn-mod-teal btn-open-filter-kelas" title="Filter Kelas"><i class="fas fa-filter"></i></button>';
          }
          if ($data_role['modifikasi'] == 'Y') {
            echo '
                    <button type="button" class="btn-mod btn-mod-info btn-import" title="Import"><i class="fas fa-file-import"></i></button>
                    <button type="button" class="btn-mod btn-mod-search btn-search-data" title="Cari"><i class="fas fa-search"></i></button>
                    <button type="button" class="btn-mod btn-mod-secondary btn-import-photo" title="Import Foto"><i class="fas fa-image"></i></button>
                    <button type="button" class="btn-mod btn-mod-warn btn-print" title="Print"><i class="fas fa-print"></i></button>';
          }
          echo '
                  </div>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table align-items-center table-flush table-striped datatable-user">
                  <thead class="thead-light">
                    <tr>
                      <th class="text-center" style="width:10px;">No</th>
                      <th class="text-center" style="width:40px;">Avatar</th>
                      <th class="text-center" style="width:40px;">QRCODE</th>
                      <th style="width:70px;">NISN</th>
                      <th style="min-width:160px;max-width:220px;">Nama</th>
                      <th style="width:40px;">Jenis Kelamin</th>
                      <th style="width:40px;">Kelas</th>
                      <th style="width:40px;">Status</th>
                      <th style="width:40px;">Kontak</th>
                      <th style="width:40px;">Konfirmasi Data</th>
                      <th class="text-center" style="width:110px;min-width:100px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>';
        } else {
          hak_akses();
        }
        echo '
      
      </div>
      
  <div class="modal fade modal-import" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Import Data User/Siswa</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form class="form-import" action="javascript:void(0);" autocomplete="of">
              <div class="modal-body">
                <div class="form-group">
                  <label>Upload file</label>
                      <input type="file" class="form-control" name="files" accept=".xlsx" placeholder="Import data" required>
                </div>

                <div class="alert alert-info alert-dismissible fade show" role="alert">
                  <span class="alert-text">Silahkan Import data dengan template dibawah ini</span>
                  <a href="../content/template.xlsx" class="btn btn-info btn-sm">Download Template</a>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                  </button>
                </div>
            

              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Import</span></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </form>
            </div>
          </div>
        </div>
        
        <!-- Modal Cetak Qr Code -->
  <div class="modal fade modal-qrcode" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Export Data</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
           
              <div class="modal-body">
                <div class="form-group">
                  <label class="form-control-label">Kelas</label>
                    <select class="form-control kelas" name="kelas" required>';
        $query_kelas = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
        $result_kelas = $connection->query($query_kelas);
        while ($data_kelas = $result_kelas->fetch_assoc()) {
          echo '<option value="' . $data_kelas['kelas_id'] . '">' . $data_kelas['nama_kelas'] . '</option>';
        }
        echo '
                    </select>
                </div>
                
                
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-export"><i class="fas fa-file-export"></i> Export</span></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>

            </div>
          </div>
        </div>

        <!-- Modal Import Photo -->
        <div class="modal fade modal-import-photo" tabindex="-1" role="dialog" aria-labelledby="modalImportPhotoLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Import Photo User/Siswa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form class="form-import-photo" action="javascript:void(0);" autocomplete="off">
                <div class="modal-body">
                  <div class="form-group">
                    <label>Upload file foto (zip/jpg/png)</label>
                    <input type="file" class="form-control" name="photo_files" accept=".zip,.jpg,.jpeg,.png" required>
                  </div>
                  <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <span class="alert-text">Silahkan upload file foto user/pegawai dalam format zip/jpg/png. Penamaan file harus sesuai NISN.</span>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">×</span>
                    </button>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary"><i class="fas fa-image"></i> Import Photo</button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
              </form>
            </div>
          </div>
        </div>';

        if (isset($level_id) && intval($level_id) === 1) {
          echo '
        <div class="modal fade modal-filter-kelas" tabindex="-1" role="dialog" aria-labelledby="modalFilterKelasLabel" aria-hidden="true">
          <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalFilterKelasLabel">Filter Kelas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body pb-2">
                <div class="form-group mb-0">
                  <label class="form-control-label">Pilih Kelas</label>
                  <select class="form-control modal-filter-kelas-select">
                    <option value="">Semua Kelas</option>';
          $query_kelas_modal = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
          $result_kelas_modal = $connection->query($query_kelas_modal);
          while ($data_kelas_modal = $result_kelas_modal->fetch_assoc()) {
            echo '<option value="' . $data_kelas_modal['kelas_id'] . '">' . $data_kelas_modal['nama_kelas'] . '</option>';
          }
          echo '
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-reset-filter-kelas">Reset</button>
                <button type="button" class="btn btn-primary btn-apply-filter-kelas">Terapkan</button>
              </div>
            </div>
          </div>
        </div>';
        }

        // Modal Pencarian Data
        echo '
          <!-- Modal Search -->
          <div class="modal fade modal-search" tabindex="-1" role="dialog" aria-labelledby="modalSearchLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Pencarian Data Ganda</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <!-- Form 1: Pilih kategori dan nilai -->
                  <form class="form-search-type" action="javascript:void(0);" autocomplete="off">
                    <div class="form-row">
                      <div class="form-group col-5">
                        <label>Pilih Kategori</label>
                        <select class="form-control search-type-select" name="search_type">
                          <option value="nik_no_kk">NIK / No KK</option>
                          <option value="email">Email</option>
                          <option value="hp">Nomor HP</option>
                        </select>
                      </div>
                      <div class="form-group col-7">
                        <label>Pilih Nilai</label>
                        <select class="form-control search-select" name="query" required>
                          <option value="">Memuat daftar...</option>
                        </select>
                      </div>
                      <input type="hidden" name="search_by" class="search-by" value="nik_no_kk">
                    </div>
                  </form>

                  <!-- Form 2: Tampilkan hasil pencarian -->
                  <form class="form-search" action="javascript:void(0);" autocomplete="off">
                    <div class="search-results mb-2"></div>
                    <div class="modal-footer p-0 pt-2">
                      <button type="submit" class="btn btn-primary btn-do-search"><i class="fas fa-search"></i> Cari</button>
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>';

        break;

      /** Update Siswa/User*/
      case 'update':
        if (!empty($_GET['id'])) {
          $id     =  anti_injection(epm_decode($_GET['id']));
          $query_user  = "SELECT * from user WHERE user_id='$id'";
          $result_user = $connection->query($query_user);

          echo '
  <div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7 d-flex align-items-center">
              <h6 class="h2 text-white d-inline-block mb-0">Siswa</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4 mb-0">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./' . $mod . '">Siswa</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Update</li>
                </ol>
              </nav>
            </div>
            
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="container-fluid mt--6 mb-6">
    <div class="card-wrapper">
    <!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Ubah Data Siswa/User</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
          if ($result_user->num_rows > 0) {
            $data_user  = $result_user->fetch_assoc();
            if (strip_tags($data_user['avatar']) == 'avatar.jpg') {
              $imageuploadwrap = 'display:block';
              $display_none = 'display:none';
            } else {
              $imageuploadwrap = 'display:none';
              $display_none = 'display:block';
            }

            if ($data_role['modifikasi'] == 'Y') {
              echo '<form class="form-update" role="form" method="post" action="#" autocomplete="off">';
              echo '<input type="hidden" class="d-none" name="id" value="' . epm_encode($data_user['user_id']) . '" required readonly>';

              // Card A
              echo '<div class="card mb-4"><div class="card-header bg-gradient-info text-white"><h5 class="mb-0">A. Identitas Peserta Didik</h5></div><div class="card-body">';
              echo '<div class="row">';
              // Kolom kiri
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label class="font-weight-bold text-primary">Status</label><select class="form-control" name="status" required><option value="Aktif" ' . ($data_user['status'] == 'Aktif' ? 'selected' : '') . '>Aktif</option><option value="Tidak Aktif" ' . ($data_user['status'] == 'Tidak Aktif' ? 'selected' : '') . '>Tidak Aktif</option></select></div>';
              echo '<div class="form-group"><label class="font-weight-bold text-primary">Kelas Saat Ini</label><select class="form-control border border-primary" name="kelas" required>';
              $query_kelas = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
              $result_kelas = $connection->query($query_kelas);
              while ($data_kelas = $result_kelas->fetch_assoc()) {
                $selected = ($data_user['kelas'] == $data_kelas['kelas_id']) ? 'selected' : '';
                echo '<option value="' . $data_kelas['kelas_id'] . '" ' . $selected . '>' . $data_kelas['nama_kelas'] . '</option>';
              }
              echo '</select></div>';
              echo '<div class="form-group"><label>Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" placeholder="Contoh: A\'zis" value="' . strip_tags($data_user['nama_lengkap']) . '" required></div>';
              echo '<div class="form-group"><label>NIS/NIPD</label><input type="text" class="form-control" name="nipd" value="' . strip_tags($data_user['nipd']) . '" required></div>';
              echo '<div class="form-group"><label>NISN</label><input type="text" class="form-control" name="nisn" value="' . strip_tags($data_user['nisn']) . '" required></div>';
              echo '<div class="form-group"><label>Nomer KK</label><input type="text" class="form-control" name="no_kk" value="' . strip_tags($data_user['no_kk']) . '" required></div>';
              echo '<div class="form-group"><label>NIK</label><input type="text" class="form-control" name="nik" value="' . strip_tags($data_user['nik']) . '" required></div>';
              echo '<div class="form-group"><label>Jenis Kelamin</label><select class="form-control" name="jenis_kelamin" required><option value="Laki-laki" ' . ($data_user['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '') . '>Laki-laki</option><option value="Perempuan" ' . ($data_user['jenis_kelamin'] == 'Perempuan' ? 'selected' : '') . '>Perempuan</option></select></div>';
              echo '<div class="form-group"><label>Tempat Lahir</label><input type="text" class="form-control" name="tempat_lahir" value="' . strip_tags($data_user['tempat_lahir']) . '" required></div>';
              echo '<div class="form-group"><label>Tanggal Lahir</label><input type="text" class="form-control datepicker" name="tanggal_lahir" value="' . tanggal_ind($data_user['tanggal_lahir']) . '" required></div>';
              echo '<div class="form-group"><label>Agama</label><input type="text" class="form-control" name="agama" value="' . strip_tags($data_user['agama']) . '" required></div>';
              echo '<div class="form-group"><label>Status dalam keluarga</label><input type="text" class="form-control" name="status_keluarga" value="' . strip_tags($data_user['status_keluarga']) . '" required></div>';
              echo '<div class="form-group"><label>Anak Ke</label><input type="number" class="form-control" name="anak_ke" value="' . strip_tags($data_user['anak_ke']) . '" required></div>';
              echo '</div>';
              // Kolom kanan
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>Alamat (Jl/Kp)</label><input type="text" class="form-control" name="alamat" value="' . strip_tags($data_user['alamat']) . '" required></div>';
              echo '<div class="form-group"><label>RT</label><input type="text" class="form-control" name="rt" value="' . strip_tags($data_user['rt']) . '" required></div>';
              echo '<div class="form-group"><label>RW</label><input type="text" class="form-control" name="rw" value="' . strip_tags($data_user['rw']) . '" required></div>';
              echo '<div class="form-group"><label>Desa/Kelurahan</label><input type="text" class="form-control" name="desa" value="' . strip_tags($data_user['desa']) . '" required></div>';
              echo '<div class="form-group"><label>Kecamatan</label><input type="text" class="form-control" name="kecamatan" value="' . strip_tags($data_user['kecamatan']) . '" required></div>';
              echo '<div class="form-group"><label>Kodepos</label><input type="text" class="form-control" name="kodepos" value="' . strip_tags($data_user['kodepos']) . '" required></div>';
              echo '<div class="form-group"><label>Telp/HP</label><input type="text" class="form-control" name="telp" value="' . strip_tags($data_user['telp']) . '" required></div>';
              echo '<div class="form-group"><label>Asal Sekolah</label><input type="text" class="form-control" name="sekolah_asal" value="' . strip_tags($data_user['sekolah_asal']) . '" required></div>';
              echo '<div class="form-group"><label>Diterima dikelas</label><input type="text" class="form-control" name="diterima_dikelas" value="' . strip_tags($data_user['diterima_dikelas']) . '" required></div>';
              echo '<div class="form-group"><label>Diterima pada tanggal</label><input type="text" class="form-control datepicker" name="diterima_tanggal" value="' . tanggal_ind($data_user['diterima_tanggal']) . '" required></div>';
              echo '<div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" value="' . strip_tags($data_user['email']) . '" ></div>';
              echo '</div>';
              echo '</div>';
              echo '</div></div>';

              // Card B
              echo '<div class="card mb-4"><div class="card-header bg-gradient-warning text-white"><h5 class="mb-0">B. Orangtua Kandung</h5></div><div class="card-body">';
              echo '<div class="row">';
              // Kolom kiri
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>NIK Ayah</label><input type="text" class="form-control" name="nik_ayah" value="' . strip_tags($data_user['nik_ayah']) . '"></div>';
              echo '<div class="form-group"><label>Nama Ayah Kandung</label><input type="text" class="form-control" name="nama_ayah" placeholder="Contoh: A\'zis" value="' . strip_tags($data_user['nama_ayah']) . '"></div>';
              echo '<div class="form-group"><label>Pekerjaan Ayah</label><input type="text" class="form-control" name="pekerjaan_ayah" value="' . strip_tags($data_user['pekerjaan_ayah']) . '"></div>';
              echo '</div>';
              // Kolom kanan
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>NIK Ibu</label><input type="text" class="form-control" name="nik_ibu" value="' . strip_tags($data_user['nik_ibu']) . '"></div>';
              echo '<div class="form-group"><label>Nama Ibu Kandung</label><input type="text" class="form-control" name="nama_ibu" placeholder="Contoh: A\'zis" value="' . strip_tags($data_user['nama_ibu']) . '"></div>';
              echo '<div class="form-group"><label>Pekerjaan Ibu</label><input type="text" class="form-control" name="pekerjaan_ibu" value="' . strip_tags($data_user['pekerjaan_ibu']) . '"></div>';
              echo '</div>';
              echo '</div>';
              echo '</div></div>';

              // Card C
              echo '<div class="card mb-4"><div class="card-header bg-gradient-secondary text-white"><h5 class="mb-0">C. Wali</h5></div><div class="card-body">';
              echo '<div class="row">';
              // Kolom kiri
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>Nama Wali</label><input type="text" class="form-control" name="nama_wali" placeholder="Contoh: A\'zis" value="' . strip_tags($data_user['nama_wali']) . '"></div>';
              echo '<div class="form-group"><label>Alamat Wali</label><input type="text" class="form-control" name="alamat_wali" value="' . strip_tags($data_user['alamat_wali']) . '"></div>';
              echo '</div>';
              // Kolom kanan
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>Telp/HP Wali</label><input type="text" class="form-control" name="telp_wali" value="' . strip_tags($data_user['telp_wali']) . '"></div>';
              echo '<div class="form-group"><label>Pekerjaan Wali</label><input type="text" class="form-control" name="pekerjaan_wali" value="' . strip_tags($data_user['pekerjaan_wali']) . '"></div>';
              echo '</div>';
              echo '</div>';
              echo '</div></div>';

              // Tombol
              echo '<hr><button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button> <a href="./' . $mod . '" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>';
              echo '</form>';
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

      /** Update Siswa/User*/
      case 'profile':
        if (!empty($_GET['id'])) {
          $id     =  anti_injection(epm_decode($_GET['id']));
          $query_user = "SELECT user.*, kelas.nama_kelas FROM user 
    LEFT JOIN kelas
    ON user.kelas = kelas.kelas_id WHERE user.user_id='$id'";
          $result_user = $connection->query($query_user);

          echo '
  <div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7 d-flex align-items-center">
              <h6 class="h2 text-white d-inline-block mb-0">Siswa</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4 mb-0">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./' . $mod . '">Siswa</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Profil</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>';

          if ($result_user->num_rows > 0) {
            $data_user  = $result_user->fetch_assoc();

            // Generate avatar with better fallback and naming
            $nama_singkat = '';
            if (!empty($data_user['nama_lengkap'])) {
              $nama_parts = explode(' ', trim($data_user['nama_lengkap']));
              $nama_singkat = strtoupper(substr($nama_parts[0], 0, 1));
              if (count($nama_parts) > 1) {
                $nama_singkat .= strtoupper(substr(end($nama_parts), 0, 1));
              }
            }

            // Check for custom avatar or use NISN-based avatar
            $avatar_file = '';
            // Prefer NISN-based PNG if present on disk
            if (!empty($data_user['nisn']) && file_exists('../content/avatar/' . $data_user['nisn'] . '.png')) {
              $avatar_file = '../content/avatar/' . $data_user['nisn'] . '.png';
            } elseif (!empty($data_user['avatar']) && $data_user['avatar'] != 'avatar.jpg') {
              // Stored avatar may include a query string for cache-busting (e.g. nisn.jpg?t=123)
              $stored_avatar = strip_tags($data_user['avatar']);
              // Strip query string to check file on disk
              $avatar_filename = preg_replace('/\?.*/', '', $stored_avatar);
              if (file_exists('../content/avatar/' . $avatar_filename)) {
                // Use the stored value (with query param) for the src so browser cache is busted
                $avatar_file = '../content/avatar/' . $stored_avatar;
              }
            }

            if (!empty($avatar_file)) {
              $avatar = '<a class="open-popup-link" href="' . $avatar_file . '" title="Klik untuk memperbesar foto">'
                . '<div class="avatar-frame">'
                . '<img src="' . $avatar_file . '" alt="Foto ' . htmlspecialchars($data_user['nama_lengkap']) . '" loading="lazy">'
                . '</div></a>';
            } else {
              // Default avatar with initials
              $avatar = '<div class="avatar-frame" title="Foto belum tersedia">'
                . '<div class="avatar-placeholder">' . $nama_singkat . '</div>'
                . '</div>';
            }

            // Avatar inline CSS removed — move styling to the global stylesheet if needed

            // Combine address fields for better display
            $alamat_lengkap = '';
            $alamat_parts = array();

            if (!empty($data_user['alamat'])) $alamat_parts[] = trim(strip_tags($data_user['alamat']));
            if (!empty($data_user['rt']) && !empty($data_user['rw'])) {
              $alamat_parts[] = 'RT ' . trim(strip_tags($data_user['rt'])) . '/RW ' . trim(strip_tags($data_user['rw']));
            } else {
              if (!empty($data_user['rt'])) $alamat_parts[] = 'RT ' . trim(strip_tags($data_user['rt']));
              if (!empty($data_user['rw'])) $alamat_parts[] = 'RW ' . trim(strip_tags($data_user['rw']));
            }
            if (!empty($data_user['desa'])) $alamat_parts[] = trim(strip_tags($data_user['desa']));
            if (!empty($data_user['kecamatan'])) $alamat_parts[] = trim(strip_tags($data_user['kecamatan']));
            if (!empty($data_user['kodepos'])) $alamat_parts[] = trim(strip_tags($data_user['kodepos']));

            $alamat_lengkap = implode(', ', array_filter($alamat_parts));

            echo '
      <!-- Page content -->
      <div class="container-fluid mt--6 mb-6">
        <div class="row">
          <div class="col-xl-4 order-xl-2 mb-4">
            <div class="card card-profile shadow">
              <img src="./assets/img/theme/img-1-1000x600.jpg" alt="Background" class="card-img-top">
              <div class="row justify-content-center">
                <div class="col-lg-3 order-lg-2">
                  <div class="card-profile-image">
                    ' . $avatar . '
                  </div>
                </div>
              </div>
              <div class="card-header text-center border-0 pt-8 pt-md-4 pb-0 pb-md-4"></div>
              <div class="card-body pt-0">
                <div class="text-center">
                  <h5 class="h3 mt-4 mb-2">
                  ' . strip_tags($data_user['nama_lengkap']) . '
                  </h5>
                  <div class="h6 font-weight-300 text-muted mb-2">
                    <i class="ni ni-badge mr-2"></i>' . strip_tags($data_user['nisn']) . '
                  </div>

                  <div class="mb-3">';
            if ($data_user['status'] == 'Aktif') {
              echo '<span class="badge badge-success badge-lg px-3 py-2"><i class="fa fa-check-circle mr-1"></i>Aktif</span>';
            } else {
              echo '<span class="badge badge-danger badge-lg px-3 py-2"><i class="fa fa-times-circle mr-1"></i>Tidak Aktif</span>';
            }
            echo '
                  </div>
                </div>
                  
                  <div class="mt-4">
                    <div class="row text-center">
                      <div class="col-6">
                        <div class="border-right">
                          <span class="text-sm font-weight-bold text-uppercase text-muted">Kelas</span>
                          <div class="h6 font-weight-700 mt-1">' . strip_tags($data_user['nama_kelas']) . '</div>
                        </div>
                      </div>
                      <div class="col-6">
                        <span class="text-sm font-weight-bold text-uppercase text-muted">Status Konfirmasi</span>
                        <div class="h6 font-weight-700 mt-1">';

            // Add confirmation status badge
            $konfirmasi = strtolower(trim($data_user['konfirmasi'] ?? 'belum konfirmasi'));
            if ($konfirmasi === 'sesuai') {
              echo '<span class="badge badge-success"><i class="fa fa-check"></i> Sesuai</span>';
            } elseif ($konfirmasi === 'belum sesuai') {
              echo '<span class="badge badge-warning"><i class="fa fa-exclamation"></i> Belum Sesuai</span>';
            } else {
              echo '<span class="badge badge-secondary"><i class="fa fa-clock"></i> Belum Konfirmasi</span>';
            }

            echo '</div>
                      </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="quick-info">
                      <div class="row">
                        <div class="col-12 mb-2">
                          <small class="text-muted font-weight-bold">NIK</small>
                          <div class="text-sm">' . strip_tags($data_user['nik']) . '</div>
                        </div>
                        <div class="col-12 mb-2">
                          <small class="text-muted font-weight-bold">NIPD</small>
                          <div class="text-sm">' . strip_tags($data_user['nipd']) . '</div>
                        </div>
                        <div class="col-12">
                          <small class="text-muted font-weight-bold">Jenis Kelamin</small>
                          <div class="text-sm">';

            if (strtolower($data_user['jenis_kelamin']) === 'laki-laki') {
              echo '<i class="fa fa-mars text-primary mr-1"></i>Laki-laki';
            } else {
              echo '<i class="fa fa-venus text-danger mr-1"></i>Perempuan';
            }

            echo '</div>
                        </div>
                      </div>
                    </div>
                  </div>

              </div>
            </div>
          </div>
          <div class="col-xl-8 order-xl-1">
            
            <div class="card shadow">
              <div class="card-header bg-white border-0">
                <div class="row align-items-center">
                  <div class="col-sm-8">
                    <h3 class="mb-0"><i class="fa fa-user text-primary mr-2"></i>Detail Profil Siswa</h3>
                  </div>
                  <div class="col-sm-4 text-right mt-2 mt-sm-0">';
            if (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y') {
              echo '<a href="user?op=update&id=' . epm_encode($data_user['user_id']) . '" class="btn btn-sm btn-primary"><i class="fa fa-edit mr-1"></i>Edit Profil</a>';
            }
            echo '</div>
                </div>
              </div>
              <div class="card-body pt-0">
                <!-- Card A -->
                <div class="card mb-4">
                  <div class="card-header bg-gradient-info text-white">
                    <h5 class="mb-0">A. Identitas Peserta Didik</h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group"><label class="font-weight-bold text-primary">Status</label><p><span class="copy-value">' . strip_tags($data_user['status']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label class="font-weight-bold text-primary">Kelas Saat Ini</label><p><span class="copy-value">' . strip_tags($data_user['nama_kelas']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Nama Lengkap</label><p><span class="copy-value">' . strip_tags($data_user['nama_lengkap']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>NIS/NIPD</label><p><span class="copy-value">' . strip_tags($data_user['nipd']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>NISN</label><p><span class="copy-value">' . strip_tags($data_user['nisn']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>                        
                        <div class="form-group"><label>Nomer KK</label><p><span class="copy-value">' . strip_tags($data_user['no_kk']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>NIK</label><p><span class="copy-value">' . strip_tags($data_user['nik']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Jenis Kelamin</label><p><span class="copy-value">' . strip_tags($data_user['jenis_kelamin']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Tempat Lahir</label><p><span class="copy-value">' . strip_tags($data_user['tempat_lahir']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Tanggal Lahir</label><p><span class="copy-value">' . tanggal_ind($data_user['tanggal_lahir']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Agama</label><p><span class="copy-value">' . strip_tags($data_user['agama']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Status dalam keluarga</label><p><span class="copy-value">' . strip_tags($data_user['status_keluarga']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Anak Ke</label><p><span class="copy-value">' . strip_tags($data_user['anak_ke']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group"><label class="font-weight-bold">Alamat Lengkap</label><p><span class="copy-value">' . ($alamat_lengkap ?: '-') . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy Alamat Lengkap"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Telp/HP</label><p><span class="copy-value">' . strip_tags($data_user['telp']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Asal Sekolah</label><p><span class="copy-value">' . strip_tags($data_user['sekolah_asal']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Diterima dikelas</label><p><span class="copy-value">' . strip_tags($data_user['diterima_dikelas']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Diterima pada tanggal</label><p><span class="copy-value">' . tanggal_ind($data_user['diterima_tanggal']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Email</label><p><span class="copy-value">' . strip_tags($data_user['email']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Card B -->
                <div class="card mb-4">
                  <div class="card-header bg-gradient-warning text-white">
                    <h5 class="mb-0">B. Orangtua Kandung</h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group"><label>NIK Ayah</label><p><span class="copy-value">' . strip_tags($data_user['nik_ayah']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Nama Ayah Kandung</label><p><span class="copy-value">' . strip_tags($data_user['nama_ayah']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Pekerjaan Ayah</label><p><span class="copy-value">' . strip_tags($data_user['pekerjaan_ayah']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group"><label>NIK Ibu</label><p><span class="copy-value">' . strip_tags($data_user['nik_ibu']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Nama Ibu Kandung</label><p><span class="copy-value">' . strip_tags($data_user['nama_ibu']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Pekerjaan Ibu</label><p><span class="copy-value">' . strip_tags($data_user['pekerjaan_ibu']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Card C -->
                <div class="card mb-4">
                  <div class="card-header bg-gradient-secondary text-white">
                    <h5 class="mb-0">C. Wali</h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group"><label>Nama Wali</label><p><span class="copy-value">' . strip_tags($data_user['nama_wali']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Alamat Wali</label><p><span class="copy-value">' . strip_tags($data_user['alamat_wali']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group"><label>Telp/HP Wali</label><p><span class="copy-value">' . strip_tags($data_user['telp_wali']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                        <div class="form-group"><label>Pekerjaan Wali</label><p><span class="copy-value">' . strip_tags($data_user['pekerjaan_wali']) . '</span> <button type="button" class="btn btn-sm btn-outline-secondary btn-copy" title="Copy"><i class="fa fa-copy"></i></button></p></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>';
          } else {
            echo ' <div class="container-fluid mt--6">
      <!-- Table -->
      <div class="row">
        <div class="col">
          <div class="card pb-6 pt-6">';
            theme_404();
            echo '</div>
            </div>
      </div>';
          }
        }
        break;
    }
  } else {
    /** Modul tidak ditemukan */
  }
}
