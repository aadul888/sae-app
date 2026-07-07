<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 4;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
        $kelas_filter = '';
        $wali_kelas_ids = array();
        $admin_identifier = '';
        if (!empty($_COOKIE['ADMIN_KEY'])) {
          $admin_identifier = @epm_decode($_COOKIE['ADMIN_KEY']);
          $admin_identifier = anti_injection($admin_identifier);
        }

        if (isset($level_id) && intval($level_id) === 4 && $admin_identifier !== '') {
          $q_ck = $connection->query("SELECT kelas_id FROM kelas WHERE nama_wali_kelas='" . $connection->real_escape_string($admin_identifier) . "' OR wali_kelas_nama='" . $connection->real_escape_string($admin_identifier) . "'");
          if ($q_ck && $q_ck->num_rows > 0) {
            while ($r_ck = $q_ck->fetch_assoc()) {
              if (!empty($r_ck['kelas_id'])) $wali_kelas_ids[] = intval($r_ck['kelas_id']);
            }
          }
        }

        if (empty($wali_kelas_ids) && $admin_identifier !== '') {
          $q_u = $connection->query("SELECT DISTINCT kelas FROM user WHERE wali_kelas = " . intval($admin_identifier) . " AND kelas IS NOT NULL AND kelas <> ''");
          if ($q_u && $q_u->num_rows > 0) {
            while ($ru = $q_u->fetch_assoc()) {
              if (!empty($ru['kelas'])) $wali_kelas_ids[] = intval($ru['kelas']);
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
            SUM(CASE WHEN jenis_kelamin='Perempuan' THEN 1 ELSE 0 END) AS perempuan,
            SUM(CASE WHEN konfirmasi='Belum Konfirmasi' OR konfirmasi='' OR konfirmasi IS NULL THEN 1 ELSE 0 END) AS belum_konfirmasi
          FROM user
          WHERE status='Tidak Aktif'" . $kelas_filter;
        $result_stats = $connection->query($query_stats);
        $total_users = 0;
        $laki = 0;
        $perempuan = 0;
        $belum_konfirmasi = 0;
        if ($result_stats && $row_stats = $result_stats->fetch_assoc()) {
          $total_users = intval($row_stats['total']);
          $laki = intval($row_stats['laki']);
          $perempuan = intval($row_stats['perempuan']);
          $belum_konfirmasi = intval($row_stats['belum_konfirmasi']);
        }

        echo '
      <script>
        document.body.classList.add("page-user-module");
      </script>

      <!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-3"></div>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--6 user-module-page">';

        echo '
          <div class="row">
            <div class="col-12">
              <div class="card user-stats-panel module-stats-shell mb-3">
                <div class="card-body py-2 px-2 px-md-3">
                  <div class="user-stats-wrap">
                    <div class="user-stats module-stats-grid" id="user-inactive-stat-row">
                      <div class="user-stat-card module-stat-card user-stat-total">
                        <div class="info">
                          <span class="label">Total Murid Tidak Aktif</span>
                          <span class="value">' . $total_users . '</span>
                          <div class="sub-info"><small><i class="fas fa-mars" aria-hidden="true"></i> : ' . $laki . ' &nbsp;&nbsp;<i class="fas fa-venus" aria-hidden="true"></i> : ' . $perempuan . '</small></div>
                        </div>
                        <div class="icon"><i class="fas fa-user-slash"></i></div>
                      </div>
                      <div class="user-stat-card module-stat-card user-stat-identitas">
                        <div class="info">
                          <span class="label">Laki-laki</span>
                          <span class="value">' . $laki . '</span>
                        </div>
                        <div class="icon"><i class="fas fa-mars"></i></div>
                      </div>
                      <div class="user-stat-card module-stat-card user-stat-belum-sesuai">
                        <div class="info">
                          <span class="label">Perempuan</span>
                          <span class="value">' . $perempuan . '</span>
                        </div>
                        <div class="icon"><i class="fas fa-venus"></i></div>
                      </div>
                      <div class="user-stat-card module-stat-card user-stat-belum">
                        <div class="info">
                          <span class="label">Belum Konfirmasi</span>
                          <span class="value">' . $belum_konfirmasi . '</span>
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
                    <h4 class="mb-1">Daftar Murid Tidak Aktif</h4>
                    <small class="text-muted">Kelola data murid tidak aktif, verifikasi, dan pembaruan data.</small>
                  </div>
                  <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">';
          if (isset($level_id) && intval($level_id) === 1) {
            echo '<button type="button" class="btn-mod btn-mod-teal btn-open-filter-kelas" title="Filter Kelas"><i class="fas fa-filter"></i></button>';
          }
          if ($data_role['modifikasi'] == 'Y') {
            echo '<button type="button" class="btn-mod btn-mod-warn btn-print" title="Print"><i class="fas fa-print"></i></button>';
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
      
      
      <div class="modal fade modal-import" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Import Data Murid</h5>
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
              <h5 class="modal-title">Export ID Card</h5>
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
          <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Import Photo Murid</h5>
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
              <div class="modal-body">
                <div class="form-group mb-0">
                  <label class="form-control-label">Pilih Kelas</label>
                  <select class="form-control modal-filter-kelas-select">';
          echo '<option value="">Semua Kelas</option>';
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
                <button type="button" class="btn btn-secondary btn-reset-filter-kelas">Reset</button>
                <button type="button" class="btn btn-primary btn-apply-filter-kelas">Terapkan</button>
              </div>
            </div>
          </div>
        </div>';
        }

        echo '</div> <!-- End container-fluid -->';

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
            <div class="col-lg-6 col-7">
            <h6 class="h2 text-white text-left mb-0">Murid</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./' . $mod . '">Murid Tidak Aktif</a></li>
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
        <h3 class="mb-0">Ubah Data Murid</h3>
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
              echo '<div class="form-group"><label>Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" value="' . strip_tags($data_user['nama_lengkap']) . '" required></div>';
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
              echo '<div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" value="' . strip_tags($data_user['email']) . '" required></div>';
              echo '</div>';
              echo '</div>';
              echo '</div></div>';

              // Card B
              echo '<div class="card mb-4"><div class="card-header bg-gradient-warning text-white"><h5 class="mb-0">B. Orangtua Kandung</h5></div><div class="card-body">';
              echo '<div class="row">';
              // Kolom kiri
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>NIK Ayah</label><input type="text" class="form-control" name="nik_ayah" value="' . strip_tags($data_user['nik_ayah']) . '"></div>';
              echo '<div class="form-group"><label>Nama Ayah Kandung</label><input type="text" class="form-control" name="nama_ayah" value="' . strip_tags($data_user['nama_ayah']) . '"></div>';
              echo '<div class="form-group"><label>Pekerjaan Ayah</label><input type="text" class="form-control" name="pekerjaan_ayah" value="' . strip_tags($data_user['pekerjaan_ayah']) . '"></div>';
              echo '</div>';
              // Kolom kanan
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>NIK Ibu</label><input type="text" class="form-control" name="nik_ibu" value="' . strip_tags($data_user['nik_ibu']) . '"></div>';
              echo '<div class="form-group"><label>Nama Ibu Kandung</label><input type="text" class="form-control" name="nama_ibu" value="' . strip_tags($data_user['nama_ibu']) . '"></div>';
              echo '<div class="form-group"><label>Pekerjaan Ibu</label><input type="text" class="form-control" name="pekerjaan_ibu" value="' . strip_tags($data_user['pekerjaan_ibu']) . '"></div>';
              echo '</div>';
              echo '</div>';
              echo '</div></div>';

              // Card C
              echo '<div class="card mb-4"><div class="card-header bg-gradient-secondary text-white"><h5 class="mb-0">C. Wali</h5></div><div class="card-body">';
              echo '<div class="row">';
              // Kolom kiri
              echo '<div class="col-md-6">';
              echo '<div class="form-group"><label>Nama Wali</label><input type="text" class="form-control" name="nama_wali" value="' . strip_tags($data_user['nama_wali']) . '"></div>';
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
            <div class="col-lg-6 col-7">
            <h6 class="h2 text-white text-left mb-0">Murid</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./' . $mod . '">Murid Tidak Aktif</a></li>
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
            if ($data_user['avatar'] == NULL or $data_user['avatar'] == 'avatar.jpg') {
              $avatar = '<img src="../content/avatar/avatar.jpg" class="rounded-circle w-150" height="140">';
            } else {
              $avatar = '
        <a class="open-popup-link" href="../content/avatar/' . strip_tags($data_user['avatar']) . '">
            <img src="../content/avatar/' . strip_tags($data_user['avatar']) . '" class="rounded-circle w-150" height="140">
        </a>';
            }

            echo '
      <!-- Page content -->
      <div class="container-fluid mt--6 mb-6">
        <div class="row">
          <div class="col-xl-4 order-xl-2">
            <div class="card card-profile">
              <img src="./assets/img/theme/img-1-1000x600.jpg" alt="Image placeholder" class="card-img-top">
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
                  <h5 class="h3 mt-5">
                  ' . strip_tags($data_user['nama_lengkap']) . '
                  </h5>
                  <div class="h5 font-weight-300">
                    <i class="ni location_pin mr-2"></i>' . strip_tags($data_user['nisn']) . '
                  </div>

                  <div class="h5 font-weight-300">';
            if ($data_user['status'] == 'Aktif') {
              echo '<span class="badge badge-info">Aktif</span>';
            } else {
              echo '<span class="badge badge-danger">Tdak Aktif</span>';
            }
            echo '
                  </div>
                </div>
                  
                  <div class="mt-3">
                    <ul class="list-group list-group-flush">
                      <li class="list-group-item">NIK: ' . $data_user['nik'] . '</li>
                      <li class="list-group-item">NIPD: ' . $data_user['nipd'] . '</li>
                    </ul>
                  </div>

              </div>
            </div>
            <!-- Progress track -->
            
            
          </div>
          <div class="col-xl-8 order-xl-1">
            
            <div class="card">
              <div class="card-header">
                <div class="row align-items-center">
                  <div class="col-8">
                    <h3 class="mb-0">Profil</h3>
                  </div>
                  <div class="col-4 text-right">
                    <a href="user?op=update&id=' . epm_encode($data_user['user_id']) . '" class="btn btn-sm btn-primary">Settings</a>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <!-- Card A -->
                <div class="card mb-4">
                  <div class="card-header bg-gradient-info text-white">
                    <h5 class="mb-0">A. Identitas Peserta Didik</h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group"><label class="font-weight-bold text-primary">Status</label><p>' . strip_tags($data_user['status']) . '</p></div>
                        <div class="form-group"><label class="font-weight-bold text-primary">Kelas Saat Ini</label><p>' . strip_tags($data_user['nama_kelas']) . '</p></div>
                        <div class="form-group"><label>Nama Lengkap</label><p>' . strip_tags($data_user['nama_lengkap']) . '</p></div>
                        <div class="form-group"><label>NIS/NIPD</label><p>' . strip_tags($data_user['nipd']) . '</p></div>
                        <div class="form-group"><label>NISN</label><p>' . strip_tags($data_user['nisn']) . '</p></div>                        
                        <div class="form-group"><label>Nomer KK</label><p>' . strip_tags($data_user['no_kk']) . '</p></div>
                        <div class="form-group"><label>NIK</label><p>' . strip_tags($data_user['nik']) . '</p></div>
                        <div class="form-group"><label>Jenis Kelamin</label><p>' . strip_tags($data_user['jenis_kelamin']) . '</p></div>
                        <div class="form-group"><label>Tempat Lahir</label><p>' . strip_tags($data_user['tempat_lahir']) . '</p></div>
                        <div class="form-group"><label>Tanggal Lahir</label><p>' . tanggal_ind($data_user['tanggal_lahir']) . '</p></div>
                        <div class="form-group"><label>Agama</label><p>' . strip_tags($data_user['agama']) . '</p></div>
                        <div class="form-group"><label>Status dalam keluarga</label><p>' . strip_tags($data_user['status_keluarga']) . '</p></div>
                        <div class="form-group"><label>Anak Ke</label><p>' . strip_tags($data_user['anak_ke']) . '</p></div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group"><label>Alamat (Jl/Kp)</label><p>' . strip_tags($data_user['alamat']) . '</p></div>
                        <div class="form-group"><label>RT</label><p>' . strip_tags($data_user['rt']) . '</p></div>
                        <div class="form-group"><label>RW</label><p>' . strip_tags($data_user['rw']) . '</p></div>
                        <div class="form-group"><label>Desa/Kelurahan</label><p>' . strip_tags($data_user['desa']) . '</p></div>
                        <div class="form-group"><label>Kecamatan</label><p>' . strip_tags($data_user['kecamatan']) . '</p></div>
                        <div class="form-group"><label>Kodepos</label><p>' . strip_tags($data_user['kodepos']) . '</p></div>
                        <div class="form-group"><label>Telp/HP</label><p>' . strip_tags($data_user['telp']) . '</p></div>
                        <div class="form-group"><label>Asal Sekolah</label><p>' . strip_tags($data_user['sekolah_asal']) . '</p></div>
                        <div class="form-group"><label>Diterima dikelas</label><p>' . strip_tags($data_user['diterima_dikelas']) . '</p></div>
                        <div class="form-group"><label>Diterima pada tanggal</label><p>' . tanggal_ind($data_user['diterima_tanggal']) . '</p></div>
                        <div class="form-group"><label>Email</label><p>' . strip_tags($data_user['email']) . '</p></div>
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
                        <div class="form-group"><label>NIK Ayah</label><p>' . strip_tags($data_user['nik_ayah']) . '</p></div>
                        <div class="form-group"><label>Nama Ayah Kandung</label><p>' . strip_tags($data_user['nama_ayah']) . '</p></div>
                        <div class="form-group"><label>Pekerjaan Ayah</label><p>' . strip_tags($data_user['pekerjaan_ayah']) . '</p></div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group"><label>NIK Ibu</label><p>' . strip_tags($data_user['nik_ibu']) . '</p></div>
                        <div class="form-group"><label>Nama Ibu Kandung</label><p>' . strip_tags($data_user['nama_ibu']) . '</p></div>
                        <div class="form-group"><label>Pekerjaan Ibu</label><p>' . strip_tags($data_user['pekerjaan_ibu']) . '</p></div>
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
                        <div class="form-group"><label>Nama Wali</label><p>' . strip_tags($data_user['nama_wali']) . '</p></div>
                        <div class="form-group"><label>Alamat Wali</label><p>' . strip_tags($data_user['alamat_wali']) . '</p></div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group"><label>Telp/HP Wali</label><p>' . strip_tags($data_user['telp_wali']) . '</p></div>
                        <div class="form-group"><label>Pekerjaan Wali</label><p>' . strip_tags($data_user['pekerjaan_wali']) . '</p></div>
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
