<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 27;
  include __DIR__ . '/../check_role.php';

  // Statistik
  $cnt_total = 0; $cnt_menunggu = 0; $cnt_diproses = 0; $cnt_selesai = 0; $cnt_ditolak = 0;
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_laporan"); if ($q) $cnt_total = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_laporan WHERE status='Menunggu'"); if ($q) $cnt_menunggu = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_laporan WHERE status='Diproses'"); if ($q) $cnt_diproses = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_laporan WHERE status='Selesai'"); if ($q) $cnt_selesai = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_laporan WHERE status='Ditolak'"); if ($q) $cnt_ditolak = intval($q->fetch_assoc()['cnt']);

  // Daftar kelas untuk filter
  $kelas_list = [];
  $qk = $connection->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
  if ($qk) { while ($rk = $qk->fetch_assoc()) { $kelas_list[] = $rk; } }

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

<div class="container-fluid mt--6 user-module-page">';

      if ($data_role['lihat'] == 'Y') {
        // Stats
        echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Laporan</span><span class="value">' . $cnt_total . '</span></div><div class="icon"><i class="fas fa-flag"></i></div></div>
      <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Menunggu</span><span class="value text-warning">' . $cnt_menunggu . '</span></div><div class="icon"><i class="fas fa-clock"></i></div></div>
      <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Diproses</span><span class="value text-info">' . $cnt_diproses . '</span></div><div class="icon"><i class="fas fa-spinner"></i></div></div>
      <div class="module-stat-card user-stat-berkas-valid"><div class="info"><span class="label">Selesai</span><span class="value text-success">' . $cnt_selesai . '</span></div><div class="icon"><i class="fas fa-check-circle"></i></div></div>
      <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Ditolak</span><span class="value text-danger">' . $cnt_ditolak . '</span></div><div class="icon"><i class="fas fa-times-circle"></i></div></div>
    </div></div></div></div></div></div>';

        // Hidden filter selects for JS access
        echo '<div style="display:none">
          <select id="filter-kelas-hidden"><option value=""></option>';
        foreach ($kelas_list as $kls) {
          echo '<option value="' . $kls['kelas_id'] . '">' . htmlspecialchars($kls['nama_kelas']) . '</option>';
        }
        echo '</select>
          <select id="filter-status-hidden"><option value=""></option><option value="Menunggu">Menunggu</option><option value="Diproses">Diproses</option><option value="Selesai">Selesai</option><option value="Ditolak">Ditolak</option></select>
        </div>';

        echo '
  <div class="card user-table-panel module-table-card shadow">
    <div class="card-header py-3 px-3 module-table-header">
      <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Laporan Kerusakan, Kehilangan &amp; Kebutuhan</h4><small class="text-muted">Laporan kondisi inventaris dan kebutuhan barang dari kelas.</small></div>
      <div class="module-header-actions">
        <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterInvReport" title="Filter"><i class="fas fa-filter"></i></button>
      </div></div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-striped datatable align-items-center table-flush" width="auto">
        <thead class="thead-light">
          <tr>
            <th class="text-center" width="40">No</th>
            <th class="text-center">Kelas</th>
            <th>Jenis</th>
            <th>Deskripsi</th>
            <th class="text-center">Prioritas</th>
            <th class="text-center">Status</th>
            <th class="text-center">Pelapor</th>
            <th class="text-center">Tanggal</th>
            <th class="text-center" width="120">Aksi</th>
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

      // Modal Filter
      echo '
<!-- Modal Filter Laporan -->
<div class="modal fade" id="modalFilterInvReport" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2"></i>Filter Laporan</h5>
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
        <div class="form-group">
          <label class="font-weight-bold">Status</label>
          <select class="form-control" id="filter-status">
            <option value="">Semua Status</option>
            <option value="Menunggu">Menunggu</option>
            <option value="Diproses">Diproses</option>
            <option value="Selesai">Selesai</option>
            <option value="Ditolak">Ditolak</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm btn-reset-filter-report"><i class="fas fa-times mr-1"></i>Reset</button>
        <button class="btn btn-primary btn-sm btn-apply-filter-report"><i class="fas fa-check mr-1"></i>Terapkan</button>
      </div>
    </div>
  </div>
</div>';

      // Modal Detail
      echo '
<div class="modal fade" id="modal-detail-laporan" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-search mr-2"></i>Detail Laporan</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="detail-laporan-content"></div>
    </div>
  </div>
</div>

<!-- Modal Proses -->
<div class="modal fade" id="modal-proses" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-proses">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-cog mr-2"></i>Proses Laporan</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="proses-id" name="laporan_id">
          <div class="form-group">
            <label class="font-weight-bold">Ubah Status</label>
            <select class="form-control" id="proses-status" name="status" required>
              <option value="Menunggu">Menunggu</option>
              <option value="Diproses">Diproses</option>
              <option value="Selesai">Selesai</option>
              <option value="Ditolak">Ditolak</option>
            </select>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Catatan Tindak Lanjut</label>
            <textarea class="form-control" id="proses-catatan" name="catatan_admin" rows="4" placeholder="Tulis catatan tindak lanjut..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>';
      break;
  }
}
