<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 46;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {
    $stats_q = $connection->query("SELECT COUNT(*) AS total, COUNT(DISTINCT COALESCE(jenis_ptk_id_str, '')) AS jenis, COUNT(DISTINCT COALESCE(status_kepegawaian_id_str, '')) AS kepegawaian FROM sync_gtk g LEFT JOIN admin a ON a.ptk_id = g.ptk_id WHERE g.nama IS NOT NULL AND g.nama != '' AND COALESCE(a.active, 'N') != 'Y'");
    $stats = $stats_q ? $stats_q->fetch_assoc() : ['total' => 0, 'jenis' => 0, 'kepegawaian' => 0];

    $jenis_list = [];
    $jenis_q = $connection->query("SELECT DISTINCT jenis_ptk_id_str FROM sync_gtk g LEFT JOIN admin a ON a.ptk_id = g.ptk_id WHERE g.nama IS NOT NULL AND g.nama != '' AND COALESCE(a.active, 'N') != 'Y' AND jenis_ptk_id_str IS NOT NULL AND jenis_ptk_id_str != '' ORDER BY jenis_ptk_id_str ASC");
    if ($jenis_q) {
      while ($r = $jenis_q->fetch_assoc()) $jenis_list[] = $r['jenis_ptk_id_str'];
    }

    $kepeg_list = [];
    $kepeg_q = $connection->query("SELECT DISTINCT status_kepegawaian_id_str FROM sync_gtk g LEFT JOIN admin a ON a.ptk_id = g.ptk_id WHERE g.nama IS NOT NULL AND g.nama != '' AND COALESCE(a.active, 'N') != 'Y' AND status_kepegawaian_id_str IS NOT NULL AND status_kepegawaian_id_str != '' ORDER BY status_kepegawaian_id_str ASC");
    if ($kepeg_q) {
      while ($r = $kepeg_q->fetch_assoc()) $kepeg_list[] = $r['status_kepegawaian_id_str'];
    }

    $jabatan_list = [];
    $jabatan_q = $connection->query("SELECT DISTINCT jabatan_ptk_id_str FROM sync_gtk g LEFT JOIN admin a ON a.ptk_id = g.ptk_id WHERE g.nama IS NOT NULL AND g.nama != '' AND COALESCE(a.active, 'N') != 'Y' AND jabatan_ptk_id_str IS NOT NULL AND jabatan_ptk_id_str != '' ORDER BY jabatan_ptk_id_str ASC");
    if ($jabatan_q) {
      while ($r = $jabatan_q->fetch_assoc()) $jabatan_list[] = $r['jabatan_ptk_id_str'];
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
                <div class="user-stats" id="guru-nonaktif-stat-row">
                  <div class="user-stat-card user-stat-total">
                    <div class="info">
                      <span class="label">Total Guru Tidak Aktif</span>
                      <span class="value" id="guru-nonaktif-card-total"><?php echo intval($stats['total']); ?></span>
                    </div>
                    <div class="icon"><i class="fas fa-user-slash"></i></div>
                  </div>
                  <div class="user-stat-card user-stat-identitas">
                    <div class="info">
                      <span class="label">Jenis PTK</span>
                      <span class="value" id="guru-nonaktif-card-jenis"><?php echo intval($stats['jenis']); ?></span>
                    </div>
                    <div class="icon"><i class="fas fa-id-badge"></i></div>
                  </div>
                  <div class="user-stat-card user-stat-belum-sesuai">
                    <div class="info">
                      <span class="label">Status Kepegawaian</span>
                      <span class="value" id="guru-nonaktif-card-kepegawaian"><?php echo intval($stats['kepegawaian']); ?></span>
                    </div>
                    <div class="icon"><i class="fas fa-briefcase"></i></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <div class="card user-table-panel pb-2">
            <div class="card-header py-3 px-3 user-table-header">
              <div class="user-table-head-row" style="gap:10px;">
                <div>
                  <h4 class="mb-1">Data Guru Tidak Aktif</h4>
                  <small class="text-muted">Kelola data guru tidak aktif dan tenaga kependidikan.</small>
                </div>
                <div class="user-toolbar-actions user-toolbar-actions-table">
                  <button type="button" class="btn-mod btn-mod-teal btn-open-filter-guru-nonaktif" title="Filter"><i class="fas fa-filter"></i></button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable-guru-nonaktif" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" style="width:10px;">No</th>
                    <th style="min-width:180px;">ID</th>
                    <th style="min-width:220px;">Nama</th>
                    <th style="min-width:170px;">Jenis PTK</th>
                    <th style="min-width:200px;">Status Kepegawaian</th>
                    <th style="min-width:190px;">Jabatan PTK</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Filter Guru Tidak Aktif -->
    <div class="modal fade modal-filter-guru-nonaktif" tabindex="-1" role="dialog" aria-labelledby="modalFilterGuruNonaktifLabel" aria-hidden="true">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalFilterGuruNonaktifLabel">Filter Data Guru Tidak Aktif</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body pb-2">
            <div class="form-group">
              <label class="form-control-label">Jenis PTK</label>
              <select class="form-control filter-jenis-ptk">
                <option value="">Semua Jenis PTK</option>
                <?php foreach ($jenis_list as $v) { ?>
                  <option value="<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-control-label">Status Kepegawaian</label>
              <select class="form-control filter-status-kepegawaian">
                <option value="">Semua Status Kepegawaian</option>
                <?php foreach ($kepeg_list as $v) { ?>
                  <option value="<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group mb-0">
              <label class="form-control-label">Jabatan PTK</label>
              <select class="form-control filter-jabatan-ptk">
                <option value="">Semua Jabatan PTK</option>
                <?php foreach ($jabatan_list as $v) { ?>
                  <option value="<?php echo htmlspecialchars($v); ?>"><?php echo htmlspecialchars($v); ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-reset-filter-guru-nonaktif">Reset</button>
            <button type="button" class="btn btn-primary btn-apply-filter-guru-nonaktif">Terapkan</button>
          </div>
        </div>
      </div>
    </div>

    <?php
  } else {
    hak_akses();
  }
}
