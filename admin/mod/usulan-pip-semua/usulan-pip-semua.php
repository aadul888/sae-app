<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
  $modul_id = 29;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

switch(@$_GET['op']){
default:
// Rekap statistik usulan PIP
$stats_q = "SELECT COUNT(*) AS total, SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status='Disetujui' THEN 1 ELSE 0 END) AS disetujui, SUM(CASE WHEN status='Ditolak' THEN 1 ELSE 0 END) AS ditolak FROM usulan_pip";
$stats_r = $connection->query($stats_q);
$pip_total = $pip_pending = $pip_disetujui = $pip_ditolak = 0;
if ($stats_r && $stats_r->num_rows) {
  $sr = $stats_r->fetch_assoc();
  $pip_total = intval($sr['total']); $pip_pending = intval($sr['pending']);
  $pip_disetujui = intval($sr['disetujui']); $pip_ditolak = intval($sr['ditolak']);
}
echo'
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
  <!-- Stats -->
  <div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid" id="pip-stat-row">
    <div id="pip-stat-total" class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Usulan</span><span class="value">'.$pip_total.'</span></div><div class="icon"><i class="fas fa-file-alt"></i></div></div>
    <div id="pip-stat-pending" class="module-stat-card user-stat-belum"><div class="info"><span class="label">Pending</span><span class="value">'.$pip_pending.'</span></div><div class="icon"><i class="fas fa-clock"></i></div></div>
    <div id="pip-stat-disetujui" class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Disetujui</span><span class="value">'.$pip_disetujui.'</span></div><div class="icon"><i class="fas fa-check-circle"></i></div></div>
    <div id="pip-stat-ditolak" class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Ditolak</span><span class="value">'.$pip_ditolak.'</span></div><div class="icon"><i class="fas fa-times-circle"></i></div></div>
  </div></div></div></div></div></div>
  <!-- Main Card -->
  <div class="row">
    <div class="col">
      <div class="card user-table-panel module-table-card shadow pb-2">
        <div class="card-header py-3 px-3 module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div><h4 class="mb-1">Usulan PIP Semua</h4><small class="text-muted">Kelola seluruh usulan PIP dari semua kelas dan angkatan.</small></div>
            <div class="module-header-actions">
              <button class="btn-mod btn-mod-teal" id="btnFilterUsulan" data-toggle="modal" data-target="#modalFilterUsulanPip" title="Filter"><i class="fas fa-filter"></i></button>
              <button class="btn-mod btn-mod-add" id="btnTambahUsulan" title="Tambah Usulan PIP"><i class="fas fa-plus"></i></button>
            </div>
          </div>
        </div>';
  echo '<link rel="stylesheet" href="./mod/usulan-pip-semua/style.css">';
  if($data_role['lihat']=='Y'){
    echo'
    <div class="table-responsive">
      <table class="table align-items-center table-flush table-striped datatable">
        <thead class="thead-light">
          <tr>
            <th class="text-center" width="4">No</th>
            <th width="6">Foto</th>
            <th width="8">NISN</th>
            <th width="20">Nama</th>
            <th width="10">Kelas</th>
            <th width="12">Status</th>
            <th width="20">Keterangan</th>
            <th width="6">Aksi</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>';
  }else{
    hak_akses();
  }
  echo'
      </div>
    </div>
  </div>
</div>';
  break;
  }
  }else{
    theme_404();
  }
}?>

<!-- Modal Filter Usulan PIP -->
<div class="modal fade" id="modalFilterUsulanPip" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Usulan PIP</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body pb-2">
        <div class="form-group">
          <label class="filter-label">Kelas</label>
          <select class="form-control form-control-sm filter-kelas">
            <option value="">Semua Kelas</option>
            <?php
            $query_kelas = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
            $result_kelas = $connection->query($query_kelas);
            if($result_kelas) {
              while ($data_kelas = $result_kelas->fetch_assoc()) {
                echo '<option value="' . $data_kelas['kelas_id'] . '">' . htmlspecialchars($data_kelas['nama_kelas']) . '</option>';
              }
            }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label class="filter-label">Status</label>
          <select class="form-control form-control-sm filter-status">
            <option value="">Semua Status</option>
            <option value="Pending">Pending</option>
            <option value="Diproses">Diproses</option>
            <option value="Disetujui">Disetujui</option>
            <option value="Ditolak">Ditolak</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-pip-semua">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-pip-semua">Terapkan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Usulan PIP (template, konten diisi via JS/AJAX) -->
<div class="modal fade" id="usulanPipDetailModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl" style="max-width: 95%; margin: 1.75rem auto;">
    <div class="modal-content">
      <div class="modal-header py-3 bg-primary text-white">
        <h5 class="modal-title mb-0">
          <i class="fas fa-file-alt mr-2"></i>Detail Usulan Program Indonesia Pintar (PIP)
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
        </button>
      </div>
      <div class="modal-body py-3" id="usulanPipDetailModalBody" style="max-height: 80vh; overflow-y: auto; background-color: #f8f9fa;">
        <!-- Konten detail akan diisi via JS -->
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Memuat data usulan...</p>
        </div>
      </div>
      <div class="modal-footer py-2 bg-light">
        <button type="button" class="btn btn-secondary btn-sm modal-close-btn" data-dismiss="modal">
          <i class="fas fa-times mr-1"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Preview Berkas -->
<div class="modal fade" id="pipPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">
          <i class="fas fa-eye mr-2"></i>Preview Berkas
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center" id="pipPreviewModalBody">
        <!-- Preview content akan diisi via JS -->
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times mr-1"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah Usulan PIP -->
<div class="modal fade" id="tambahUsulanModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-3 bg-primary text-white">
        <h5 class="modal-title mb-0">
          <i class="fas fa-plus mr-2"></i>Tambah Usulan Program Indonesia Pintar (PIP)
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
        </button>
      </div>
      <div class="modal-body py-4" id="tambahUsulanModalBody">
        <!-- Step 1: Input NISN -->
        <div id="step-input-nisn">
          <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Petunjuk:</strong> Masukkan NISN siswa yang ingin diajukan untuk Program Indonesia Pintar (PIP).
          </div>
          
          <div class="form-group">
            <label class="font-weight-bold">
              <i class="fas fa-id-card mr-1"></i>NISN Siswa
            </label>
            <input type="text" class="form-control form-control-lg" id="inputNISN" placeholder="Masukkan 10 digit NISN siswa..." 
                   autocomplete="off" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" style="font-size: 1rem;">
            <div id="nisn-feedback" class="feedback" style="display:none; margin-top:5px; font-size:0.875rem;"></div>
            <small class="form-text text-muted">NISN (Nomor Induk Siswa Nasional) - 10 digit angka</small>
          </div>
          
          <div class="text-center">
            <button type="button" class="btn btn-primary btn-lg px-4" id="btnCariSiswa">
              <i class="fas fa-search mr-2"></i>Cari Data Siswa
            </button>
          </div>
        </div>
        
        <!-- Step 2: Konfirmasi Data Siswa (akan diisi via JS) -->
        <div id="step-konfirmasi-siswa" style="display: none;">
          <div class="alert alert-success">
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Data siswa ditemukan!</strong> Periksa data berikut sebelum membuat usulan.
          </div>
          
          <div id="dataSiswaContainer">
            <!-- Data siswa akan diisi via JavaScript -->
          </div>
          
          <div class="form-group mt-4">
            <label class="font-weight-bold">
              <i class="fas fa-comment-alt mr-1"></i>Alasan Usulan PIP
            </label>
            <div class="alert alert-info mb-2">
              <i class="fas fa-info-circle mr-2"></i>
              <strong>Usulan dari Wali Kelas</strong>
            </div>
            <input type="hidden" id="alasanUsulan" value="Usulan dari Wali Kelas">
            <small class="form-text text-muted">Alasan usulan akan otomatis diset sebagai "Usulan dari Wali Kelas"</small>
          </div>
          
          <div class="text-center">
            <button type="button" class="btn btn-secondary mr-2" id="btnKembaliNISN">
              <i class="fas fa-arrow-left mr-2"></i>Kembali
            </button>
            <button type="button" class="btn btn-success btn-lg px-4" id="btnBuatUsulan">
              <i class="fas fa-paper-plane mr-2"></i>Buat Usulan PIP
            </button>
          </div>
        </div>
        
        <!-- Loading indicator -->
        <div id="loading-indicator" style="display: none;">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Sedang memproses...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript -->
<script src="./mod/usulan-pip-semua/scripts.js"></script>

