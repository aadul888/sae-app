<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 15;
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
      <!-- Table -->
      <div class="row">
        <div class="col">
          <div class="card user-table-panel module-table-card pb-2 no-print">
            <!-- Card header -->
            <div class="card-header py-3 px-3 user-table-header module-table-header no-print">
              <div class="module-header-row">
                <div>
                  <h4 class="mb-1">Laporan Absensi Kelas</h4>
                  <small class="text-muted">Cetak dan unduh laporan absensi per kelas.</small>
                </div>
              </div>
            </div>

            <div class="card-body no-print">
            <div class="row">
            
              <div class="col-md-3">
                <div class="form-group">
                  <select class="form-control kelas" data-toggle="select" name="kelas" required>
                    <option value="">Pilih Kelas</option>';
        // Query untuk mengambil data kelas
        $query_kelas = "SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
        $result_kelas = $connection->query($query_kelas);
        if ($result_kelas->num_rows > 0) {
          while ($data_kelas = $result_kelas->fetch_assoc()) {
            echo '<option value="' . $data_kelas['kelas_id'] . '">' . strip_tags($data_kelas['nama_kelas']) . '</option>';
          }
        } else {
          echo '<option value="">Data tidak ditemukan</option>';
        }
        echo '
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                  <div class="form-group">
                      <select class="form-control bulan" required>';
        $bulan_nama = array(1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
        for ($bulan = 1; $bulan <= 12; $bulan++) {
          if ($bulan <= $month) {
            echo '<option value="' . $bulan . '" selected>' . $bulan_nama[$bulan] . '</option>';
          } else {
            echo '<option value="' . $bulan . '">' . $bulan_nama[$bulan] . '</option>';
          }
        }
        echo '
                      </select>
                  </div>
              </div>


              <div class="col-md-2">
                  <div class="form-group">
                      <select class="form-control tahun" required>';
        $mulai = date('Y') - 1;
        for ($i = $mulai; $i < $mulai + 50; $i++) {
          $sel = $i == date('Y') ? ' selected="selected"' : '';
          echo '<option value="' . $i . '"' . $sel . '>' . $i . '</option>';
        }
        echo '
                    </select>
                  </div>
              </div>


        
            </div>
            </div>';
        if ($data_role['lihat'] == 'Y') {
          echo '<div class="load-data"></div>';
        } else {
          hak_akses();
        }
        echo '
            </div>
          </div>
        </div>
      </div>';


        break;
    }
  } else {
    theme_404();
  }
}
