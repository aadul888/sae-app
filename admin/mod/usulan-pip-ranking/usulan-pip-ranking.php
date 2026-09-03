<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
  $modul_id = 31;
  include __DIR__ . '/../check_role.php';
  
  if(!$has_access) {
    echo '<div class="container-fluid mt-5">
            <div class="row justify-content-center">
              <div class="col-md-6">
                <div class="card border-danger">
                  <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Akses Ditolak</h5>
                  </div>
                  <div class="card-body text-center">
                    <i class="fas fa-ban text-danger" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                    <h6>Anda tidak memiliki akses ke halaman ini</h6>
                    <a href="./" class="btn btn-primary btn-sm">
                      <i class="fas fa-home mr-1"></i> Kembali ke Dashboard
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>';
    exit;
  }
  
  if ($has_access) {

switch(@$_GET['op']){ 
default:

// Tentukan judul dan konteks berdasarkan level dan access type
$page_title = 'Usulan PIP Ranking';
$page_desc = 'Lihat dan kelola ranking usulan PIP berdasarkan poin kriteria.';
if($access_type == 'superadmin') {
  $page_title = 'Usulan PIP Ranking - Semua Kelas';
} elseif($access_type == 'admin') {
  $page_title = 'Usulan PIP Ranking - Semua Kelas';
} elseif($access_type == 'wali_kelas') {
  $page_title = 'Usulan PIP Ranking - Kelas Wali';
  $page_desc = 'Ranking usulan PIP untuk kelas yang Anda wali.';
}

// Stats
$stat_rank_total = 0; $stat_rank_disetujui = 0; $stat_rank_dapodik = 0;
$q_rt = $connection->query("SELECT COUNT(*) c FROM usulan_pip"); if ($q_rt) $stat_rank_total = intval($q_rt->fetch_assoc()['c']);
$q_rd = $connection->query("SELECT COUNT(*) c FROM usulan_pip WHERE status='Disetujui'"); if ($q_rd) $stat_rank_disetujui = intval($q_rd->fetch_assoc()['c']);
$q_rda = $connection->query("SELECT COUNT(*) c FROM usulan_pip WHERE dapodik='Y'"); if ($q_rda) $stat_rank_dapodik = intval($q_rda->fetch_assoc()['c']);

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
    <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Usulan</span><span class="value">'.$stat_rank_total.'</span></div><div class="icon"><i class="fas fa-file-alt"></i></div></div>
    <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Disetujui</span><span class="value text-success">'.$stat_rank_disetujui.'</span></div><div class="icon"><i class="fas fa-check-circle"></i></div></div>
    <div class="module-stat-card user-stat-berkas-valid"><div class="info"><span class="label">Input Dapodik</span><span class="value">'.$stat_rank_dapodik.'</span></div><div class="icon"><i class="fas fa-database"></i></div></div>
  </div></div></div></div></div></div>
  <!-- Main Card -->
  <div class="row">
    <div class="col">
      <div class="card user-table-panel module-table-card pb-2">
        <div class="card-header py-3 px-3 module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div><h4 class="mb-1">'.$page_title.'</h4><small class="text-muted">'.$page_desc.'</small></div>
            <div class="module-header-actions">';
if($access_type == 'superadmin' || $access_type == 'admin') {
  echo '<button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterRanking" title="Filter"><i class="fas fa-filter"></i></button>';
}
echo'
            </div>
          </div>
        </div>';

if($access_type == 'wali_kelas') {
  echo '<div class="px-3 pt-2"><div class="alert alert-info alert-sm mb-2 py-2"><i class="fas fa-info-circle mr-1"></i><small><strong>Info:</strong> Anda dapat mengatur ranking untuk kelas yang Anda wali</small></div></div>';
} elseif($access_type == 'superadmin') {
  echo '<div class="px-3 pt-2"><div class="alert alert-success alert-sm mb-2 py-2"><i class="fas fa-check-circle mr-1"></i><small><strong>Superadmin:</strong> Dapat melihat semua data dan mengedit status konfirmasi Dapodik</small></div></div>';
} elseif($access_type == 'admin') {
  echo '<div class="px-3 pt-2"><div class="alert alert-success alert-sm mb-2 py-2"><i class="fas fa-check-circle mr-1"></i><small><strong>Admin:</strong> Dapat melihat semua data dan mengedit status konfirmasi Dapodik</small></div></div>';
}

echo '<link rel="stylesheet" href="./mod/usulan-pip-diterima/style.css">';

if($data_role['lihat']=='Y'){
  echo'
  <div class="table-responsive" style="overflow-x:auto;width:100%">
  <table class="table align-items-center table-flush table-striped datatable">
    <thead class="thead-light">
      <tr>
        <th class="text-center" width="2">No</th>
        <th class="text-center" width="4">Set</th>
        <th width="6">Foto</th>
        <th width="8">NISN</th>
        <th width="15">Nama</th>
        <th width="10">Kelas</th>
        <th width="10">Status</th>
        <th width="8">Poin</th>
        <th width="10">Dapodik</th>
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

<!-- Modal Filter Ranking -->
<div class="modal fade" id="modalFilterRanking" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Ranking PIP</h5>
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
            <option value="">Disetujui (default)</option>
            <option value="all">Semua Status</option>
            <option value="Pending">Pending</option>
            <option value="Diproses">Diproses</option>
            <option value="Disetujui">Disetujui</option>
            <option value="Ditolak">Ditolak</option>
          </select>
        </div>
        <div class="form-group">
          <label class="filter-label">Dapodik</label>
          <select class="form-control form-control-sm filter-dapodik">
            <option value="">Semua</option>
            <option value="Y">Sudah Input</option>
            <option value="N">Belum Input</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-ranking">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-ranking">Terapkan</button>
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
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">
          <i class="fas fa-eye mr-2"></i>Preview Berkas
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center" id="pipPreviewModalBody" style="min-height: 400px; display: flex; align-items: center; justify-content: center;">
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

<!-- Scripts -->

<script src="./mod/usulan-pip-ranking/scripts.js?v=<?php echo time(); ?>"></script>