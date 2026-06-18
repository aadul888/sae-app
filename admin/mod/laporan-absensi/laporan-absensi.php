<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
$modul_id = 17;
include __DIR__ . '/../check_role.php';

if ($has_access) {
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
  <div class="row">
    <div class="col">
      <div class="card user-table-panel module-table-card pb-2">
        <div class="card-header py-3 px-3 user-table-header module-table-header">
          <div class="module-header-row">
            <div>
              <h4 class="mb-1">Laporan hari ini</h4>
              <small class="text-muted">Rekap kehadiran siswa hari ini secara real-time.</small>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-1">Filter :</div>
            <div class="col-md-4">
              <div class="form-group">
                <div class="input-group input-group-merge">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                  </div>
                  <input class="form-control datepicker tanggal" value="' . date('d-m-Y') . '" placeholder="Tanggal" type="text">
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <button type="button" class="btn-mod btn-mod-danger btn-print" data-tipe="pdf" title="PDF"><i class="far fa-file-pdf"></i></button>
              <button type="button" class="btn-mod btn-mod-info btn-print" data-tipe="print" title="Print"><i class="fas fa-print"></i></button>
              <button type="button" class="btn-mod btn-mod-success btn-print" data-tipe="excel" title="Excel"><i class="far fa-file-excel"></i></button>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <div class="load-data"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Modal Koreksi Waktu Absensi -->
<div class="modal fade" id="modalKoreksiAbsen" tabindex="-1" role="dialog" aria-labelledby="modalKoreksiAbsenLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalKoreksiAbsenLabel">Koreksi Waktu Absensi</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Form akan di-load via AJAX -->
      </div>
    </div>
  </div>
</div>';
} else {
  // show 404 theme when user doesn't have view permission
  if (function_exists('theme_404')) {
    theme_404();
  } else {
    // fallback: simple 404 header and exit
    header("HTTP/1.0 404 Not Found");
    echo '<div class="container mt-5"><h3>404 Not Found</h3><p>Anda tidak memiliki hak akses untuk melihat halaman ini.</p></div>';
    exit;
  }
}
