<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 47;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {
    $stats_q = $connection->query("SELECT SUM(COALESCE(jam_mengajar_per_minggu, 0)) AS total_jam, COUNT(DISTINCT NULLIF(COALESCE(nama_mata_pelajaran, ''), '')) AS total_mapel, COUNT(DISTINCT NULLIF(COALESCE(rombongan_belajar_id, ''), '')) AS total_rombel, COUNT(DISTINCT NULLIF(COALESCE(ptk_id, ''), '')) AS total_guru FROM sync_pembelajaran");
    $stats = $stats_q ? $stats_q->fetch_assoc() : ['total_jam' => 0, 'total_mapel' => 0, 'total_rombel' => 0, 'total_guru' => 0];

    $rombel_list = [];
    $rombel_q = $connection->query("SELECT DISTINCT rb.nama FROM sync_pembelajaran p LEFT JOIN sync_rombongan_belajar rb ON rb.rombongan_belajar_id = p.rombongan_belajar_id WHERE rb.nama IS NOT NULL AND rb.nama != '' ORDER BY rb.nama ASC");
    if ($rombel_q) {
      while ($r = $rombel_q->fetch_assoc()) $rombel_list[] = $r['nama'];
    }

    $guru_list = [];
    $guru_q = $connection->query("SELECT DISTINCT g.nama FROM sync_pembelajaran p LEFT JOIN sync_gtk g ON g.ptk_id = p.ptk_id WHERE g.nama IS NOT NULL AND g.nama != '' ORDER BY g.nama ASC");
    if ($guru_q) {
      while ($r = $guru_q->fetch_assoc()) $guru_list[] = $r['nama'];
    }

    $status_kur_list = [];
    $status_q = $connection->query("SELECT DISTINCT status_di_kurikulum_str FROM sync_pembelajaran WHERE status_di_kurikulum_str IS NOT NULL AND status_di_kurikulum_str != '' ORDER BY status_di_kurikulum_str ASC");
    if ($status_q) {
      while ($r = $status_q->fetch_assoc()) $status_kur_list[] = $r['status_di_kurikulum_str'];
    }
    ?>

    <div class="header bg-primary pb-4 user-page-header-compact">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-3"></div>
        </div>
      </div>
    </div>

    <div class="container-fluid mt--6 user-module-page">

      <div class="row">
        <div class="col-12">
          <div class="card user-stats-panel mb-3">
            <div class="card-body py-2 px-2 px-md-3">
              <div class="user-stats-wrap">
                <div class="user-stats" id="pemb-stat-row">
                  <div class="user-stat-card user-stat-total">
                    <div class="info">
                      <span class="label">Jam/Minggu</span>
                      <span class="value" id="pemb-card-jam"><?php echo intval($stats['total_jam']); ?></span>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                  </div>
                  <div class="user-stat-card user-stat-identitas">
                    <div class="info">
                      <span class="label">Mata Pelajaran</span>
                      <span class="value" id="pemb-card-mapel"><?php echo intval($stats['total_mapel']); ?></span>
                    </div>
                    <div class="icon"><i class="fas fa-book"></i></div>
                  </div>
                  <div class="user-stat-card user-stat-belum-sesuai">
                    <div class="info">
                      <span class="label">Rombel</span>
                      <span class="value" id="pemb-card-rombel"><?php echo intval($stats['total_rombel']); ?></span>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                  </div>
                  <div class="user-stat-card user-stat-belum">
                    <div class="info">
                      <span class="label">Guru Pengampu</span>
                      <span class="value" id="pemb-card-guru"><?php echo intval($stats['total_guru']); ?></span>
                    </div>
                    <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card user-table-panel module-table-card pb-2">
        <div class="card-header py-3 px-3 user-table-header">
          <div class="user-table-head-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Data Pembelajaran</h4>
              <small class="text-muted">Data otomatis dari Dapodik.</small>
            </div>
            <div class="user-toolbar-actions user-toolbar-actions-table">
              <button type="button" class="btn-mod btn-mod-teal btn-open-filter-pembelajaran" title="Filter"><i class="fas fa-filter"></i></button>
            </div>
          </div>
        </div>
        <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable-pembelajaran" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" style="width:10px;">No</th>
                    <th style="min-width:170px;">ID Pembelajaran</th>
                    <th style="min-width:240px;">Mata Pelajaran</th>
                    <th style="min-width:160px;">Kelas/Rombel</th>
                    <th style="min-width:190px;">Guru Pengampu</th>
                    <th style="min-width:110px;">Jam/Minggu</th>
                    <th style="min-width:200px;">Status Kurikulum</th>
                    <th style="min-width:150px;">Update Sinkron</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
        </div>
      </div>
    </div>

    <!-- Modal Filter Pembelajaran -->
    <div class="modal fade modal-filter-pembelajaran" tabindex="-1" role="dialog" aria-labelledby="modalFilterPembelajaranLabel" aria-hidden="true">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalFilterPembelajaranLabel">Filter Data Pembelajaran</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body pb-2">
            <div class="form-group">
              <label class="form-control-label">Rombongan Belajar</label>
              <select class="form-control filter-rombel">
                <option value="">Semua Rombel</option>
                <?php foreach ($rombel_list as $v) { ?>
                  <option value="<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-control-label">Guru Pengampu</label>
              <select class="form-control filter-guru">
                <option value="">Semua Guru</option>
                <?php foreach ($guru_list as $v) { ?>
                  <option value="<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group mb-0">
              <label class="form-control-label">Status Kurikulum</label>
              <select class="form-control filter-status-kurikulum">
                <option value="">Semua Status Kurikulum</option>
                <?php foreach ($status_kur_list as $v) { ?>
                  <option value="<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-reset-filter-pembelajaran">Reset</button>
            <button type="button" class="btn btn-primary btn-apply-filter-pembelajaran">Terapkan</button>
          </div>
        </div>
      </div>
    </div>

    <?php
  } else {
    hak_akses();
  }
}
