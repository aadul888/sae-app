<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
  $modul_id = 30;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

switch(@$_GET['op']){
default:
// Stats
$stat_diterima_total = 0; $stat_disetujui = 0; $stat_ditolak = 0;
$q_stat = $connection->query("SELECT COUNT(*) AS total, SUM(status='Disetujui') AS disetujui, SUM(status='Ditolak') AS ditolak FROM usulan_pip WHERE status IN ('Disetujui','Ditolak')");
if ($q_stat && $r_stat = $q_stat->fetch_assoc()) {
  $stat_diterima_total = intval($r_stat['total']);
  $stat_disetujui = intval($r_stat['disetujui']);
  $stat_ditolak = intval($r_stat['ditolak']);
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
  <div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
    <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Diterima</span><span class="value">'.$stat_diterima_total.'</span></div><div class="icon"><i class="fas fa-file-check"></i></div></div>
    <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Disetujui</span><span class="value text-success">'.$stat_disetujui.'</span></div><div class="icon"><i class="fas fa-check-circle"></i></div></div>
    <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Ditolak</span><span class="value text-danger">'.$stat_ditolak.'</span></div><div class="icon"><i class="fas fa-times-circle"></i></div></div>
  </div></div></div></div></div></div>
  <!-- Table -->
  <div class="row">
    <div class="col">
      <div class="card user-table-panel module-table-card pb-2">
        <div class="card-header py-3 px-3 module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div><h4 class="mb-1">Usulan PIP Diterima</h4><small class="text-muted">Daftar siswa yang usulan PIP-nya telah disetujui.</small></div>
          </div>
        </div>
        
  <!-- Custom CSS untuk modal diterima -->
  <link rel="stylesheet" href="./mod/usulan-pip-diterima/style.css">
          
            <div class="table-responsive" style="overflow-x:auto;width:100%">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-flush table-striped datatable">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="4">No</th>
                    <th width="6">Foto</th>
                    <th width="8">NISN</th>
                    <th width="20">Nama</th>
                    <th width="10">Kelas</th>
                    <th width="12">Status</th>
                    <th width="20">Poin</th>
                    <th width="6">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>';
            }else{
              hak_akses();
            }
            echo'
            </div>
          </div>
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

