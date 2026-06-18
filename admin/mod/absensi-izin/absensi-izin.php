<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 10;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
        echo '
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>

<div class="container-fluid mt--6 user-module-page">';

        if ($data_role['lihat'] == 'Y') {
          echo '
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid" id="izin-stat-row">
              <div class="user-stat-card module-stat-card user-stat-total">
                <div class="info">
                  <span class="label">Total Pengajuan</span>
                  <span class="value" id="izin-card-total">0</span>
                </div>
                <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-belum">
                <div class="info">
                  <span class="label">Menunggu</span>
                  <span class="value" id="izin-card-menunggu">0</span>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-identitas">
                <div class="info">
                  <span class="label">Disetujui</span>
                  <span class="value" id="izin-card-setuju">0</span>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-belum-sesuai">
                <div class="info">
                  <span class="label">Ditolak</span>
                  <span class="value" id="izin-card-tolak">0</span>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 user-table-header module-table-header">
      <div class="user-table-head-row module-header-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Daftar Usulan Izin Siswa</h4>
          <small class="text-muted">Kelola verifikasi izin absensi siswa secara cepat dan terstruktur.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <button type="button" class="btn-mod btn-mod-teal btn-open-filter-izin" title="Filter"><i class="fas fa-filter"></i></button>
          <button type="button" class="btn-mod btn-mod-info btn-reload-izin" title="Reload"><i class="fas fa-sync-alt"></i></button>
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table align-items-center table-flush table-striped datatable-izin" style="width:100%">
        <thead class="thead-light">
          <tr>
            <th class="text-center" style="width:10px;">No</th>
            <th style="min-width:100px;">NISN</th>
            <th style="min-width:190px;">Nama Lengkap</th>
            <th style="min-width:130px;">Kelas</th>
            <th style="min-width:130px;">Jenis Izin</th>
            <th style="min-width:180px;">Tanggal</th>
            <th style="min-width:110px;">Status</th>
            <th class="text-center" style="min-width:95px;">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>';
        } else {
          hak_akses();
        }

        echo '
</div>

<div class="modal fade modal-filter-izin" tabindex="-1" role="dialog" aria-labelledby="modalFilterIzinLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFilterIzinLabel">Filter Pengajuan Izin</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body pb-2">
        <div class="form-group mb-0">
          <label class="form-control-label">Status Izin</label>
          <select class="form-control filter-status-izin">
            <option value="">Semua Status</option>
            <option value="Menunggu">Menunggu</option>
            <option value="Disetujui">Disetujui</option>
            <option value="Ditolak">Ditolak</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-reset-filter-izin">Reset</button>
        <button type="button" class="btn btn-primary btn-apply-filter-izin">Terapkan</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalLabel">Detail Izin</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="detail-content"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-edit-catatan" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-edit-catatan" action="javascript:void(0);" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Edit Catatan Penolakan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-id">
          <textarea class="form-control" id="edit-catatan" rows="4" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
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
