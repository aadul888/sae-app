<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 21;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    $sub = isset($_GET['sub']) ? $_GET['sub'] : 'jadwal';

    // Pre-load kelas for filter modal
    $kelas_filter = [];
    $q_kf = $connection->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
    while ($k = $q_kf->fetch_assoc()) $kelas_filter[] = $k;

    // Stat cards per sub-tab
    $stat_jadwal = ['total' => 0, 'kelas' => 0, 'mapel' => 0, 'guru' => 0];
    $stat_agenda = ['total' => 0, 'hadir' => 0, 'tidak_hadir' => 0, 'tugas' => 0];
    $stat_edit   = ['pending' => 0, 'disetujui' => 0, 'ditolak' => 0];
    if ($sub === 'jadwal') {
      $q_sj = $connection->query("SELECT COUNT(*) AS total, COUNT(DISTINCT j.kelas_id) AS kelas, COUNT(DISTINCT j.mapel_id) AS mapel, COUNT(DISTINCT m.guru_id) AS guru FROM agenda_jadwal j LEFT JOIN agenda_mapel m ON j.mapel_id = m.mapel_id");
      if ($q_sj) $stat_jadwal = array_merge($stat_jadwal, $q_sj->fetch_assoc());
    } elseif ($sub === 'agenda') {
      $q_sa = $connection->query("SELECT COUNT(*) AS total, SUM(kehadiran_guru='Hadir') AS hadir, SUM(kehadiran_guru='Tidak Hadir') AS tidak_hadir, SUM(kehadiran_guru='Tidak Hadir + Tugas') AS tugas FROM agenda_kelas WHERE status != 'dihapus' AND MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW())");
      if ($q_sa) $stat_agenda = array_merge($stat_agenda, $q_sa->fetch_assoc());
    } elseif ($sub === 'edit-request') {
      $q_se = $connection->query("SELECT SUM(status='pending') AS pending, SUM(status='disetujui') AS disetujui, SUM(status='ditolak') AS ditolak FROM agenda_edit_request");
      if ($q_se) $stat_edit = array_merge($stat_edit, $q_se->fetch_assoc());
    }

    echo '
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
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid">';

    if ($sub === 'jadwal') {
      echo '
              <div class="module-stat-card user-stat-total">
                <div class="info"><span class="label">Total Jadwal</span><span class="value">' . intval($stat_jadwal['total']) . '</span></div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
              </div>
              <div class="module-stat-card user-stat-identitas">
                <div class="info"><span class="label">Kelas</span><span class="value">' . intval($stat_jadwal['kelas']) . '</span></div>
                <div class="icon"><i class="fas fa-chalkboard"></i></div>
              </div>
              <div class="module-stat-card user-stat-berkas-valid">
                <div class="info"><span class="label">Mata Pelajaran</span><span class="value">' . intval($stat_jadwal['mapel']) . '</span></div>
                <div class="icon"><i class="fas fa-book"></i></div>
              </div>
              <div class="module-stat-card user-stat-belum">
                <div class="info"><span class="label">Guru</span><span class="value">' . intval($stat_jadwal['guru']) . '</span></div>
                <div class="icon"><i class="fas fa-user-tie"></i></div>
              </div>';
    } elseif ($sub === 'agenda') {
      echo '
              <div class="module-stat-card user-stat-total">
                <div class="info"><span class="label">Agenda Bulan Ini</span><span class="value">' . intval($stat_agenda['total']) . '</span></div>
                <div class="icon"><i class="fas fa-book-open"></i></div>
              </div>
              <div class="module-stat-card user-stat-berkas-valid">
                <div class="info"><span class="label">Hadir</span><span class="value text-success">' . intval($stat_agenda['hadir']) . '</span></div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
              </div>
              <div class="module-stat-card user-stat-belum-sesuai">
                <div class="info"><span class="label">Tidak Hadir</span><span class="value text-danger">' . intval($stat_agenda['tidak_hadir']) . '</span></div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
              </div>
              <div class="module-stat-card user-stat-identitas">
                <div class="info"><span class="label">Tidak Hadir + Tugas</span><span class="value text-warning">' . intval($stat_agenda['tugas']) . '</span></div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
              </div>';
    } elseif ($sub === 'edit-request') {
      echo '
              <div class="module-stat-card user-stat-belum">
                <div class="info"><span class="label">Menunggu</span><span class="value text-warning">' . intval($stat_edit['pending']) . '</span></div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
              </div>
              <div class="module-stat-card user-stat-berkas-valid">
                <div class="info"><span class="label">Disetujui</span><span class="value text-success">' . intval($stat_edit['disetujui']) . '</span></div>
                <div class="icon"><i class="fas fa-check"></i></div>
              </div>
              <div class="module-stat-card user-stat-belum-sesuai">
                <div class="info"><span class="label">Ditolak</span><span class="value text-danger">' . intval($stat_edit['ditolak']) . '</span></div>
                <div class="icon"><i class="fas fa-ban"></i></div>
              </div>';
    }

    echo '
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <div class="card shadow module-table-card">
        <div class="card-header border-0 pb-0">
          <div style="width:100%;">
          <div class="module-header-row mb-2" style="gap:10px;">
            <div><h4 class="mb-1">Jadwal &amp; Agenda Kelas</h4><small class="text-muted">Kelola jadwal pelajaran dan agenda harian kelas.</small></div>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
          <ul class="nav nav-pills nav-fill flex-column flex-md-row tab-responsive" role="tablist" style="flex:1;">
            <li class="nav-item">
              <a class="nav-link mb-sm-3 mb-md-0 ' . ($sub === 'jadwal' ? 'active' : '') . '" href="?mod=agenda-jadwal&sub=jadwal">
                <i class="fas fa-calendar-alt mr-1"></i>Jadwal Mapel
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link mb-sm-3 mb-md-0 ' . ($sub === 'agenda' ? 'active' : '') . '" href="?mod=agenda-jadwal&sub=agenda">
                <i class="fas fa-book-open mr-1"></i>Agenda Harian
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link mb-sm-3 mb-md-0 ' . ($sub === 'edit-request' ? 'active' : '') . '" href="?mod=agenda-jadwal&sub=edit-request">
                <i class="fas fa-edit mr-1"></i>Permintaan Edit
              </a>
            </li>
          </ul>
          </div>
          ' . ($sub !== 'edit-request' ? '<div class="d-flex justify-content-center mt-2"><button type="button" class="btn-mod btn-mod-teal btn-open-filter-jadwal" title="Filter"><i class="fas fa-filter"></i></button></div>' : '') . '
          </div>
        </div>
        <div class="card-body">';

    switch ($sub) {
      case 'jadwal':
        echo '
          <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
          <table class="table align-items-center table-flush table-striped datatable-jadwal" width="auto">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="4">No</th>
                <th class="text-center">Kelas</th>
                <th class="text-center">Hari</th>
                <th class="text-center">Jam Ke</th>
                <th class="text-center">Mata Pelajaran</th>
                <th class="text-center">Guru</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>';
        } else { hak_akses(); }
        echo '</div>';
        break;

      case 'agenda':
        echo '
          <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
          <table class="table align-items-center table-flush table-striped datatable-agenda" width="auto">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="4">No</th>
                <th class="text-center">Kelas</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Jam Ke</th>
                <th class="text-center">Mapel</th>
                <th class="text-center">Guru</th>
                <th class="text-center">Kehadiran Guru</th>
                <th class="text-center">Materi</th>
                <th class="text-center">Foto</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>';
        } else { hak_akses(); }
        echo '</div>';
        break;

      case 'edit-request':
        echo '
          <h3 class="mb-3">Permintaan Edit Agenda</h3>
          <div class="table-responsive">';
        if ($data_role['lihat'] == 'Y') {
          echo '
          <table class="table align-items-center table-flush table-striped datatable-edit" width="auto">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="4">No</th>
                <th class="text-center">Kelas</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Koordinator</th>
                <th class="text-center">Alasan</th>
                <th class="text-center">Status</th>
                <th class="text-center">Waktu</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>';
        } else { hak_akses(); }
        echo '</div>';
        break;
    }

    echo '
        </div>
      </div>
    </div>
  </div>
</div>';

    if ($sub !== 'edit-request') {
      $filter_title = $sub === 'jadwal' ? 'Jadwal Mapel' : 'Agenda Harian';
      $kelas_select_id = $sub === 'jadwal' ? 'filter-kelas-jadwal' : 'filter-kelas-agenda';
      echo '<div class="modal fade modal-filter-jadwal" id="modalFilterJadwal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter ' . $filter_title . '</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body pb-2">';
      if ($sub === 'agenda') {
        echo '<div class="form-group">
          <label class="form-control-label">Tanggal</label>
          <input type="date" id="filter-tanggal" class="form-control form-control-sm" value="' . date('Y-m-d') . '">
        </div>';
      }
      echo '<div class="form-group mb-0">
        <label class="form-control-label">Kelas</label>
        <select id="' . $kelas_select_id . '" class="form-control form-control-sm">
          <option value="">Semua Kelas</option>';
      foreach ($kelas_filter as $k) {
        echo '<option value="' . $k['kelas_id'] . '">' . htmlspecialchars($k['nama_kelas']) . '</option>';
      }
      echo '</select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-jadwal">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-jadwal">Terapkan</button>
      </div>
    </div>
  </div>
</div>';
    }

  } else {
    hak_akses();
  }
}
?>
