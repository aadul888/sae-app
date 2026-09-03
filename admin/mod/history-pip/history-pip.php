<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
  $modul_id = 32;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

switch(@$_GET['op']){
default:
// Stats
$hist_total = 0; $hist_disetujui = 0; $hist_ditolak = 0; $hist_pending = 0;
$q_hs = $connection->query("SELECT COUNT(*) AS total, SUM(CASE WHEN status='Disetujui' THEN 1 ELSE 0 END) AS disetujui, SUM(CASE WHEN status='Ditolak' THEN 1 ELSE 0 END) AS ditolak, SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending FROM usulan_pip");
if ($q_hs && $r_hs = $q_hs->fetch_assoc()) {
  $hist_total = intval($r_hs['total']); $hist_disetujui = intval($r_hs['disetujui']);
  $hist_ditolak = intval($r_hs['ditolak']); $hist_pending = intval($r_hs['pending']);
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
    <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Riwayat</span><span class="value">'.$hist_total.'</span></div><div class="icon"><i class="fas fa-history"></i></div></div>
    <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Disetujui</span><span class="value text-success">'.$hist_disetujui.'</span></div><div class="icon"><i class="fas fa-check-circle"></i></div></div>
    <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Ditolak</span><span class="value text-danger">'.$hist_ditolak.'</span></div><div class="icon"><i class="fas fa-times-circle"></i></div></div>
    <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Pending</span><span class="value text-warning">'.$hist_pending.'</span></div><div class="icon"><i class="fas fa-clock"></i></div></div>
  </div></div></div></div></div></div>
  <!-- Main Card -->
  <div class="row">
    <div class="col">
      <div class="card shadow module-table-card pb-2">
        <div class="card-header py-3 px-3 module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div><h4 class="mb-1">Riwayat PIP</h4><small class="text-muted">Rekap riwayat dan arsip keputusan usulan Program Indonesia Pintar.</small></div>
            <div class="module-header-actions">
              <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterHistoryPip" title="Filter"><i class="fas fa-filter"></i></button>
            </div>
          </div>
        </div>';
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
                  <th width="10">Tanggal</th>
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

<!-- Modal Filter Riwayat PIP -->
<div class="modal fade" id="modalFilterHistoryPip" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Riwayat PIP</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body pb-2">
        <div class="form-group">
          <label class="filter-label">Status</label>
          <select class="form-control form-control-sm" id="filter-status-history">
            <option value="">Semua Status</option>
            <option value="Pending">Pending</option>
            <option value="Diproses">Diproses</option>
            <option value="Disetujui">Disetujui</option>
            <option value="Ditolak">Ditolak</option>
          </select>
        </div>
        <div class="form-group">
          <label class="filter-label">Kelas</label>
          <select class="form-control form-control-sm" id="filter-kelas-history">
            <option value="">Semua Kelas</option>
            <?php
            $q_kls = $connection->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");
            if($q_kls) while($rk = $q_kls->fetch_assoc()) {
              echo '<option value="'.$rk['kelas_id'].'">'.htmlspecialchars($rk['nama_kelas']).'</option>';
            }
            ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-history">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-history">Terapkan</button>
      </div>
    </div>
  </div>
</div>
