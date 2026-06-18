<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 22;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'guru';

    // Load filter options
    $guru_opts = $connection->query("SELECT admin_id, fullname, gelar_depan, gelar_belakang FROM admin WHERE level_id=2 AND active='Y' ORDER BY fullname ASC");
    $kelas_opts = $connection->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
    $mapel_opts = $connection->query("SELECT mapel_id, nama_mapel FROM agenda_mapel WHERE aktif='Y' ORDER BY nama_mapel ASC");

    echo '
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>

<div class="container-fluid mt--6 user-module-page">
  <div class="row" id="summary-cards"></div>
  <div class="row">
    <div class="col">
      <div class="card shadow module-table-card">
        <div class="card-header border-0 pb-0">
          <div style="width:100%;">
          <div class="module-header-row mb-2" style="gap:10px;">
            <div><h4 class="mb-1">Laporan Agenda Kelas</h4><small class="text-muted">Rekap dan laporan agenda mengajar per guru, kelas, atau mata pelajaran.</small></div>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
          <ul class="nav nav-pills nav-fill flex-column flex-md-row tab-responsive" role="tablist" style="flex:1;">
            <li class="nav-item"><a class="nav-link mb-sm-3 mb-md-0 ' . ($tab === 'guru' ? 'active' : '') . '" href="?mod=agenda-laporan&tab=guru"><i class="fas fa-chalkboard-teacher mr-1"></i>Per Guru</a></li>
            <li class="nav-item"><a class="nav-link mb-sm-3 mb-md-0 ' . ($tab === 'kelas' ? 'active' : '') . '" href="?mod=agenda-laporan&tab=kelas"><i class="fas fa-chalkboard mr-1"></i>Per Kelas</a></li>
            <li class="nav-item"><a class="nav-link mb-sm-3 mb-md-0 ' . ($tab === 'mapel' ? 'active' : '') . '" href="?mod=agenda-laporan&tab=mapel"><i class="fas fa-book mr-1"></i>Per Mapel</a></li>
          </ul>
          </div>
          <div class="d-flex justify-content-center mt-2"><button type="button" class="btn-mod btn-mod-teal btn-open-filter-laporan" title="Filter"><i class="fas fa-filter"></i></button></div>
          </div>
        </div>
        <div class="card-body pt-2">
          <!-- Data Table -->
          <div class="table-responsive">
            <table class="table align-items-center table-flush table-striped datatable-laporan" width="auto">
              <thead class="thead-light">
                <tr id="table-header"></tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>';

    // Modal Filter Laporan
    echo '<div class="modal fade modal-filter-laporan" id="modalFilterLaporan" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Laporan Agenda</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body pb-2">
        <div class="form-group">
          <label class="form-control-label">Tanggal Mulai</label>
          <input type="date" id="filter-dari" class="form-control form-control-sm" value="' . date('Y-m-01') . '">
        </div>
        <div class="form-group">
          <label class="form-control-label">Tanggal Akhir</label>
          <input type="date" id="filter-sampai" class="form-control form-control-sm" value="' . date('Y-m-d') . '">
        </div>';
    if ($tab === 'guru') {
      echo '<div class="form-group mb-0"><label class="form-control-label">Guru</label><select id="filter-guru" class="form-control form-control-sm"><option value="">Semua Guru</option>';
      while ($g = $guru_opts->fetch_assoc()) {
        $n = trim(($g['gelar_depan'] ? $g['gelar_depan'] . ' ' : '') . $g['fullname'] . ($g['gelar_belakang'] ? ', ' . $g['gelar_belakang'] : ''));
        echo '<option value="' . $g['admin_id'] . '">' . htmlspecialchars($n) . '</option>';
      }
      echo '</select></div>';
    } elseif ($tab === 'kelas') {
      echo '<div class="form-group mb-0"><label class="form-control-label">Kelas</label><select id="filter-kelas" class="form-control form-control-sm"><option value="">Semua Kelas</option>';
      while ($k = $kelas_opts->fetch_assoc()) {
        echo '<option value="' . $k['kelas_id'] . '">' . htmlspecialchars($k['nama_kelas']) . '</option>';
      }
      echo '</select></div>';
    } elseif ($tab === 'mapel') {
      echo '<div class="form-group mb-0"><label class="form-control-label">Mapel</label><select id="filter-mapel" class="form-control form-control-sm"><option value="">Semua Mapel</option>';
      while ($m = $mapel_opts->fetch_assoc()) {
        echo '<option value="' . $m['mapel_id'] . '">' . htmlspecialchars($m['nama_mapel']) . '</option>';
      }
      echo '</select></div>';
    }
    echo '      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-laporan">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-laporan">Terapkan</button>
      </div>
    </div>
  </div>
</div>';

  } else {
    hak_akses();
  }
}
?>
