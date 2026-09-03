<?PHP
// Query siswa untuk autocomplete (pindah ke atas)
$siswa_list = [];
$query_siswa = "SELECT nisn, nama_lengkap FROM user WHERE rfid IS NULL";
$result_siswa = $connection->query($query_siswa);
while ($row = $result_siswa->fetch_assoc()) {
  $siswa_list[] = $row;
}

$total_rfid = 0;
$total_belum = 0;
$total_semua = 0;
$q_total_rfid = $connection->query("SELECT COUNT(*) AS jumlah FROM user WHERE rfid IS NOT NULL AND TRIM(rfid) <> ''");
if ($q_total_rfid && $r = $q_total_rfid->fetch_assoc()) $total_rfid = intval($r['jumlah']);
$q_total_belum = $connection->query("SELECT COUNT(*) AS jumlah FROM user WHERE rfid IS NULL OR TRIM(rfid) = ''");
if ($q_total_belum && $r = $q_total_belum->fetch_assoc()) $total_belum = intval($r['jumlah']);
$q_total_semua = $connection->query("SELECT COUNT(*) AS jumlah FROM user");
if ($q_total_semua && $r = $q_total_semua->fetch_assoc()) $total_semua = intval($r['jumlah']);

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 11;
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
<div class="container-fluid mt--6 user-module-page">
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid" id="rfid-stat-row">
              <div class="user-stat-card module-stat-card user-stat-total">
                <div class="info">
                  <span class="label">Total Siswa</span>
                  <span class="value">' . $total_semua . '</span>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-identitas">
                <div class="info">
                  <span class="label">RFID Terdaftar</span>
                  <span class="value">' . $total_rfid . '</span>
                </div>
                <div class="icon"><i class="fas fa-id-card"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-belum">
                <div class="info">
                  <span class="label">Belum Registrasi</span>
                  <span class="value">' . $total_belum . '</span>
                </div>
                <div class="icon"><i class="fas fa-user-clock"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <div class="card user-table-panel module-table-card pb-2">
        
        <!-- Card header -->
        <div class="card-header py-3 px-3 user-table-header module-table-header">
          <div class="user-table-head-row module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Registrasi RFID</h4>
              <small class="text-muted">Kelola penautan kartu RFID siswa secara cepat dan akurat.</small>
            </div>
            <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">';

        if ($data_role['modifikasi'] == 'Y') {
          echo '<button class="btn-mod btn-mod-add btn-add" title="Tambah"><i class="fas fa-plus"></i></button>';
        } else {
          echo '<button class="btn-mod btn-mod-add" disabled title="Tambah"><i class="fas fa-plus"></i></button>';
        }

        echo '
            </div>
          </div>
        </div>

        <!-- Tabel Registrasi RFID -->
        <div class="table-responsive">';

        if ($data_role['lihat'] == 'Y') {
          echo '
          <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
            <thead class="thead-light">
              <tr>
                <th class="text-center">No</th>
                <th class="text-center">NISN</th>
                <th class="text-center">Nama Lengkap</th>
                <th class="text-center">Kelas</th>
                <th class="text-center">RFID</th>
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
        </div> <!-- End Table Responsive -->
      </div> <!-- End Card -->
    </div> <!-- End Col -->
  </div> <!-- End Row -->';

        if ($data_role['modifikasi'] == 'Y') {
          echo '
    <!-- Modal ADD -->
    <div class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form class="form-add" role="form" action="#">
            <input type="hidden" class="form-control id d-none" name="id" readonly>
            <div class="modal-header">
              <h5 class="modal-title">Tambah RFID <span class="modal-title-name text-info"></span></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-12 mb-3">
                  <label for="siswa-nisn" class="form-label">Pilih Siswa</label>
                  <!-- Autocomplete input -->
                  <input type="text" class="form-control siswa-nisn-input" id="siswa-nisn-input" name="nisn" required autocomplete="off" placeholder="Ketik NISN atau nama siswa">
                  <div class="autocomplete-results" style="position:relative;z-index:10;"></div>
                  <!-- Hidden input for NISN value -->
                  <input type="hidden" class="form-control siswa-nisn-hidden" name="nisn_hidden">
                </div>
                <div class="col-12 mb-3">
                  <label for="rfid" class="form-label">Scan RFID</label>
                  <input type="text" class="form-control rfid" id="rfid" name="rfid" required placeholder="Tempelkan kartu RFID" autofocus>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary btn-save">Simpan</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </form>
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
?>
<!-- Output data siswa ke JS global untuk autocomplete -->
<script>
window.siswaList = <?php echo json_encode($siswa_list); ?>;
</script>