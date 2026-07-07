<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
  $modul_id = 12;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

        switch (@$_GET['op']) {
            default:
                $total_jadwal = 0;
                $jadwal_aktif = 0;
                $jadwal_nonaktif = 0;

                $q_total = $connection->query("SELECT COUNT(*) AS jumlah FROM jadwal");
                if ($q_total && $r = $q_total->fetch_assoc()) $total_jadwal = intval($r['jumlah']);
                $q_aktif = $connection->query("SELECT COUNT(*) AS jumlah FROM jadwal WHERE status='Y'");
                if ($q_aktif && $r = $q_aktif->fetch_assoc()) $jadwal_aktif = intval($r['jumlah']);
                $q_nonaktif = $connection->query("SELECT COUNT(*) AS jumlah FROM jadwal WHERE status='N'");
                if ($q_nonaktif && $r = $q_nonaktif->fetch_assoc()) $jadwal_nonaktif = intval($r['jumlah']);

                echo '  
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
                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card user-stats-panel module-stats-shell mb-3">
                              <div class="card-body py-2 px-2 px-md-3">
                                <div class="user-stats-wrap">
                                <div class="user-stats module-stats-grid" id="jadwal-stat-row">
                                    <div class="user-stat-card module-stat-card user-stat-total">
                                        <div class="info">
                                            <span class="label">Total Jadwal</span>
                                            <span class="value" id="jadwal-stat-total">' . $total_jadwal . '</span>
                                        </div>
                                        <div class="icon"><i class="fas fa-calendar-days"></i></div>
                                    </div>
                                    <div class="user-stat-card module-stat-card user-stat-identitas">
                                        <div class="info">
                                            <span class="label">Jadwal Aktif</span>
                                            <span class="value" id="jadwal-stat-aktif">' . $jadwal_aktif . '</span>
                                        </div>
                                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                                    </div>
                                    <div class="user-stat-card module-stat-card user-stat-belum">
                                        <div class="info">
                                            <span class="label">Jadwal Nonaktif</span>
                                            <span class="value" id="jadwal-stat-nonaktif">' . $jadwal_nonaktif . '</span>
                                        </div>
                                        <div class="icon"><i class="fas fa-times-circle"></i></div>
                                    </div>
                                </div>
                              </div>
                            </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="row">
                        <div class="col">
                            <div class="card user-table-panel module-table-card pb-2">
                                <!-- Card header -->
                                                                <div class="card-header py-3 px-3 user-table-header module-table-header">
                                                                    <div class="user-table-head-row module-header-row" style="gap:10px;">
                                                                        <div>
                                                                            <h4 class="mb-1">Daftar Jadwal</h4>
                                                                            <small class="text-muted">Atur jam masuk, jam pulang, dan status aktif jadwal harian.</small>
                                                                        </div>
                                                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="text-center" width="5">No</th>
                                                <th class="text-center" width="6">Hari</th>
                                                <th class="text-center" width="10">Jam Masuk</th>
                                                <th class="text-center" width="10">Jam Pulang</th>
                                                <th class="text-center" width="10">Aksi</th>
                                                <th class="text-center" width="10">Aktif</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<!-- Modal Edit Waktu -->
<div id="editModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Waktu Jadwal</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form class="form-edit">
                <div class="modal-body">
                    <input type="hidden" id="edit-id" name="id">
                    <div class="form-group">
                        <label>Waktu Mulai</label>
                        <input type="text" id="edit-waktu-mulai" name="waktu_mulai" class="form-control timepicker" required>
                    </div>
                    <div class="form-group">
                        <label>Waktu Selesai</label>
                        <input type="text" id="edit-waktu-selesai" name="waktu_selesai" class="form-control timepicker" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-save">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>';

                break;
        }
    } else {
        theme_404();
    }
}
?>
