<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 25;
  include __DIR__ . '/../check_role.php';

  // Ambil daftar kelas
  $kelas_list = [];
  $qk = $connection->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
  if ($qk) { while ($rk = $qk->fetch_assoc()) { $kelas_list[] = $rk; } }

  // Filter kelas yang dipilih
  $filter_kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 0;

  // Statistik
  $cnt_total = 0; $cnt_baik = 0; $cnt_rusak_ringan = 0; $cnt_rusak_berat = 0; $cnt_hilang = 0;
  $where_kelas = $filter_kelas > 0 ? "WHERE ik.kelas_id = " . intval($filter_kelas) : "";
  
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_kelas ik $where_kelas");
  if ($q) { $cnt_total = intval($q->fetch_assoc()['cnt']); }
  
  $wk = $filter_kelas > 0 ? "WHERE ik.kelas_id = " . intval($filter_kelas) . " AND" : "WHERE";
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_kelas ik $wk ik.kondisi = 'Baik'");
  if ($q) { $cnt_baik = intval($q->fetch_assoc()['cnt']); }
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_kelas ik $wk ik.kondisi = 'Rusak Ringan'");
  if ($q) { $cnt_rusak_ringan = intval($q->fetch_assoc()['cnt']); }
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_kelas ik $wk ik.kondisi = 'Rusak Berat'");
  if ($q) { $cnt_rusak_berat = intval($q->fetch_assoc()['cnt']); }
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_kelas ik $wk ik.kondisi = 'Hilang'");
  if ($q) { $cnt_hilang = intval($q->fetch_assoc()['cnt']); }

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

<div class="container-fluid mt--6 user-module-page">';;

      if ($data_role['lihat'] == 'Y') {
        // Statistik cards
        echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total</span><span class="value">' . $cnt_total . '</span></div><div class="icon"><i class="fas fa-boxes"></i></div></div>
      <div class="module-stat-card user-stat-berkas-valid"><div class="info"><span class="label">Baik</span><span class="value text-success">' . $cnt_baik . '</span></div><div class="icon"><i class="fas fa-check"></i></div></div>
      <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Rusak Ringan</span><span class="value text-warning">' . $cnt_rusak_ringan . '</span></div><div class="icon"><i class="fas fa-tools"></i></div></div>
      <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Rusak Berat</span><span class="value text-danger">' . $cnt_rusak_berat . '</span></div><div class="icon"><i class="fas fa-exclamation-circle"></i></div></div>
      <div class="module-stat-card user-stat-wali"><div class="info"><span class="label">Hilang</span><span class="value">' . $cnt_hilang . '</span></div><div class="icon"><i class="fas fa-ghost"></i></div></div>
    </div></div></div></div></div></div>';

        echo '<div class="module-filter-panel" style="display:none"><select id="filter-kelas-hidden"><option value="">Semua Kelas</option>';
        foreach ($kelas_list as $kls) {
          $sel = ($filter_kelas == $kls['kelas_id']) ? 'selected' : '';
          echo '<option value="' . $kls['kelas_id'] . '" ' . $sel . '>' . htmlspecialchars($kls['nama_kelas']) . '</option>';
        }
        echo '</select></div>';

        echo '
  <div class="card user-table-panel module-table-card shadow">
    <div class="card-header py-3 px-3 module-table-header">
      <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Data Inventaris Per Kelas</h4><small class="text-muted">Pantau distribusi dan kondisi inventaris barang per kelas.</small></div>
      <div class="module-header-actions">
        <button class="btn-mod btn-mod-teal" id="btn-filter-inv-kelas" data-toggle="modal" data-target="#modalFilterInvKelas" title="Filter"><i class="fas fa-filter"></i></button>
      </div></div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-striped datatable align-items-center table-flush" width="auto">
        <thead class="thead-light">
          <tr>
            <th class="text-center" width="40">No</th>
            <th class="text-center">Kelas</th>
            <th>Barang</th>
            <th class="text-center">Jumlah</th>
            <th class="text-center">Kondisi</th>
            <th>Keterangan</th>
            <th class="text-center">Di-input Oleh</th>
            <th class="text-center">Tanggal</th>
            <th class="text-center" width="80">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>';
      } else {
        hak_akses();
      }

      echo '</div>';

      // Modal Detail
      echo '
<div class="modal fade" id="modal-detail-inv" tabindex="-1" role="dialog">', '<!-- Modal Filter -->
<div class="modal fade" id="modalFilterInvKelas" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2"></i>Filter Inventaris Kelas</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="font-weight-bold">Kelas</label>
          <select class="form-control" id="filter-kelas">
            <option value="">Semua Kelas</option>';
      foreach ($kelas_list as $kls) {
        echo '<option value="' . $kls['kelas_id'] . '">' . htmlspecialchars($kls['nama_kelas']) . '</option>';
      }
      echo '
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm btn-reset-filter-inv-kelas"><i class="fas fa-times mr-1"></i>Reset</button>
        <button class="btn btn-primary btn-sm btn-apply-filter-inv-kelas"><i class="fas fa-check mr-1"></i>Terapkan</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal-detail-inv" tabindex="-1" role="dialog">', '
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-search mr-2"></i>Detail Inventaris</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="detail-inv-content"></div>
    </div>
  </div>
</div>';
      break;
  }
}
