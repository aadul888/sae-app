<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 49;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    $can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
    $can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

    // ---- Statistik ringkas ----
    $stat = ['hari' => 0, 'minggu' => 0, 'bulan' => 0, 'aktif' => 0];
    if ($r = $connection->query("SELECT COUNT(*) c FROM buku_tamu WHERE tanggal_kunjungan = CURDATE()")) $stat['hari'] = intval($r->fetch_row()[0]);
    if ($r = $connection->query("SELECT COUNT(*) c FROM buku_tamu WHERE YEARWEEK(tanggal_kunjungan, 1) = YEARWEEK(CURDATE(), 1)")) $stat['minggu'] = intval($r->fetch_row()[0]);
    if ($r = $connection->query("SELECT COUNT(*) c FROM buku_tamu WHERE YEAR(tanggal_kunjungan)=YEAR(CURDATE()) AND MONTH(tanggal_kunjungan)=MONTH(CURDATE())")) $stat['bulan'] = intval($r->fetch_row()[0]);
    if ($r = $connection->query("SELECT COUNT(*) c FROM buku_tamu WHERE status='Aktif'")) $stat['aktif'] = intval($r->fetch_row()[0]);

    // ---- Rekap survey ----
    $sv = ['n' => 0, 'rating' => 0, 'pelayanan' => 0, 'kecepatan' => 0, 'kenyamanan' => 0];
    if ($r = $connection->query("SELECT COUNT(*) n, ROUND(AVG(rating),2) a, ROUND(AVG(pelayanan),2) b, ROUND(AVG(kecepatan),2) c, ROUND(AVG(kenyamanan),2) d FROM buku_tamu_survey")) {
      $x = $r->fetch_assoc();
      $sv = ['n' => intval($x['n']), 'rating' => floatval($x['a']), 'pelayanan' => floatval($x['b']), 'kecepatan' => floatval($x['c']), 'kenyamanan' => floatval($x['d'])];
    }

    $public_form_url = '../tamu/form';

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
    <div class="col-xl-3 col-md-6">
      <div class="card card-stats mb-4"><div class="card-body"><div class="row"><div class="col"><h5 class="card-title text-uppercase text-muted mb-0">Tamu Hari Ini</h5><span class="h2 font-weight-bold mb-0">' . $stat['hari'] . '</span></div><div class="col-auto"><div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-calendar-day"></i></div></div></div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-stats mb-4"><div class="card-body"><div class="row"><div class="col"><h5 class="card-title text-uppercase text-muted mb-0">Minggu Ini</h5><span class="h2 font-weight-bold mb-0">' . $stat['minggu'] . '</span></div><div class="col-auto"><div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-calendar-week"></i></div></div></div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-stats mb-4"><div class="card-body"><div class="row"><div class="col"><h5 class="card-title text-uppercase text-muted mb-0">Bulan Ini</h5><span class="h2 font-weight-bold mb-0">' . $stat['bulan'] . '</span></div><div class="col-auto"><div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-calendar-alt"></i></div></div></div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
      <div class="card card-stats mb-4"><div class="card-body"><div class="row"><div class="col"><h5 class="card-title text-uppercase text-muted mb-0">Masih Aktif</h5><span class="h2 font-weight-bold mb-0">' . $stat['aktif'] . '</span></div><div class="col-auto"><div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-door-open"></i></div></div></div></div></div>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <div class="card shadow">
        <div class="card-header module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Buku Tamu</h4>
              <small class="text-muted">Manajemen data kunjungan tamu, laporan, dan survey kepuasan.</small>
            </div>
            <div class="module-header-actions">
              <a href="' . $public_form_url . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-qrcode mr-1"></i>Form Registrasi Publik</a>
            </div>
          </div>
        </div>
        <div class="card-body pb-0">
          <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab-data" role="tab"><i class="fas fa-list mr-1"></i> Data Tamu</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-laporan" role="tab"><i class="fas fa-file-export mr-1"></i> Laporan</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-survey" role="tab"><i class="fas fa-star mr-1"></i> Survey</a></li>
          </ul>
        </div>

        <div class="tab-content">
          <!-- ===== DATA TAMU ===== -->
          <div class="tab-pane fade show active" id="tab-data" role="tabpanel">
            <div class="px-4 pb-3">
              <form id="filterData" class="form-row align-items-end">
                <div class="form-group col-auto mb-2"><label class="small mb-1">Dari</label><input type="date" id="f_dari" class="form-control form-control-sm"></div>
                <div class="form-group col-auto mb-2"><label class="small mb-1">Sampai</label><input type="date" id="f_sampai" class="form-control form-control-sm"></div>
                <div class="form-group col-auto mb-2"><label class="small mb-1">Status</label>
                  <select id="f_status" class="form-control form-control-sm">
                    <option value="">Semua</option><option value="Aktif">Aktif</option><option value="Selesai">Selesai</option><option value="Batal">Batal</option>
                  </select>
                </div>
                <div class="form-group col-auto mb-2"><label class="small mb-1 d-block">&nbsp;</label>
                  <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
                  <button type="button" id="resetFilter" class="btn btn-sm btn-secondary">Reset</button>
                </div>
              </form>
            </div>
            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped" id="tableTamu" width="100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="4">No</th>
                    <th class="text-center">Foto</th>
                    <th>Nama / Instansi</th>
                    <th>Keperluan</th>
                    <th class="text-center">Tanggal</th>
                    <th class="text-center">Masuk</th>
                    <th class="text-center">Keluar</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- ===== LAPORAN ===== -->
          <div class="tab-pane fade" id="tab-laporan" role="tabpanel">
            <div class="p-4">
              <p class="text-muted">Pilih rentang tanggal lalu unduh laporan kunjungan tamu.</p>
              <form id="formLaporan" class="form-row align-items-end" target="_blank" method="get" action="./mod/buku-tamu/laporan.php">
                <div class="form-group col-auto mb-2"><label class="small mb-1">Dari</label><input type="date" name="dari" class="form-control form-control-sm" required></div>
                <div class="form-group col-auto mb-2"><label class="small mb-1">Sampai</label><input type="date" name="sampai" class="form-control form-control-sm" required></div>
                <div class="form-group col-auto mb-2"><label class="small mb-1">Status</label>
                  <select name="status" class="form-control form-control-sm"><option value="">Semua</option><option value="Aktif">Aktif</option><option value="Selesai">Selesai</option><option value="Batal">Batal</option></select>
                </div>
                <div class="form-group col-auto mb-2"><label class="small mb-1 d-block">&nbsp;</label>
                  <button type="submit" name="format" value="excel" class="btn btn-sm btn-success"><i class="fas fa-file-excel mr-1"></i>Excel</button>
                  <button type="submit" name="format" value="print" class="btn btn-sm btn-info"><i class="fas fa-print mr-1"></i>Cetak/PDF</button>
                </div>
              </form>
            </div>
          </div>

          <!-- ===== SURVEY ===== -->
          <div class="tab-pane fade" id="tab-survey" role="tabpanel">
            <div class="p-4">
              <div class="row">
                <div class="col-md-3 col-6 mb-3"><div class="card border-left-primary"><div class="card-body py-3"><div class="small text-muted">Total Survey</div><div class="h3 mb-0">' . $sv['n'] . '</div></div></div></div>
                <div class="col-md-3 col-6 mb-3"><div class="card border-left-warning"><div class="card-body py-3"><div class="small text-muted">Rating Keseluruhan</div><div class="h3 mb-0">' . number_format($sv['rating'], 2) . ' <small class="text-muted">/5</small></div></div></div></div>
                <div class="col-md-2 col-6 mb-3"><div class="card"><div class="card-body py-3"><div class="small text-muted">Pelayanan</div><div class="h4 mb-0">' . number_format($sv['pelayanan'], 2) . '</div></div></div></div>
                <div class="col-md-2 col-6 mb-3"><div class="card"><div class="card-body py-3"><div class="small text-muted">Kecepatan</div><div class="h4 mb-0">' . number_format($sv['kecepatan'], 2) . '</div></div></div></div>
                <div class="col-md-2 col-6 mb-3"><div class="card"><div class="card-body py-3"><div class="small text-muted">Kenyamanan</div><div class="h4 mb-0">' . number_format($sv['kenyamanan'], 2) . '</div></div></div></div>
              </div>
              <div class="table-responsive">
                <table class="table align-items-center table-flush table-striped" id="tableSurvey" width="100%">
                  <thead class="thead-light">
                    <tr>
                      <th class="text-center" width="4">No</th>
                      <th>Tamu</th>
                      <th class="text-center">Rating</th>
                      <th class="text-center">Pelayanan</th>
                      <th class="text-center">Kecepatan</th>
                      <th class="text-center">Kenyamanan</th>
                      <th>Komentar</th>
                      <th class="text-center">Waktu</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>';

    // ===== Modal Detail =====
    echo '
<div class="modal fade" id="modalDetail" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="fas fa-id-card mr-2"></i>Detail Tamu</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="detailBody"><div class="text-center text-muted py-4">Memuat...</div></div>
    </div>
  </div>
</div>';

    // ===== Modal Edit =====
    if ($can_edit) {
      echo '
<div class="modal fade" id="modalEditTamu" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Data Tamu</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formEditTamu">
        <input type="hidden" name="id" id="t_id">
        <div class="modal-body">
          <div class="form-group"><label>Nama <span class="text-danger">*</span></label><input type="text" name="nama" id="t_nama" class="form-control" required></div>
          <div class="form-group"><label>Instansi <span class="text-danger">*</span></label><input type="text" name="instansi" id="t_instansi" class="form-control" required></div>
          <div class="form-group"><label>Telepon</label><input type="text" name="telepon" id="t_telepon" class="form-control"></div>
          <div class="form-group"><label>Keperluan <span class="text-danger">*</span></label><input type="text" name="keperluan" id="t_keperluan" class="form-control" required></div>
          <div class="form-group"><label>Keterangan</label><textarea name="keterangan" id="t_keterangan" class="form-control" rows="2"></textarea></div>
          <div class="form-group"><label>Status</label>
            <select name="status" id="t_status" class="form-control"><option value="Aktif">Aktif</option><option value="Selesai">Selesai</option><option value="Batal">Batal</option></select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Update</button>
        </div>
      </form>
    </div>
  </div>
</div>';
    }

    // Pass permission flags to JS
    echo '<script>window.BUKU_TAMU_PERM = { edit: ' . ($can_edit ? 'true' : 'false') . ', del: ' . ($can_del ? 'true' : 'false') . ' };</script>';
  } else {
    hak_akses();
  }
}
?>
