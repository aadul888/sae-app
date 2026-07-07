<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
} else {
  $modul_id = 33;
  include __DIR__ . '/../check_role.php';

  $cnt_pasal = 0; $cnt_ayat = 0; $cnt_ringan = 0; $cnt_sedang = 0; $cnt_berat = 0; $cnt_sangat_berat = 0;
  $q = $connection->query("SELECT COUNT(*) c FROM poin_pasal WHERE aktif='Y'"); if ($q) $cnt_pasal = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM poin_ayat WHERE aktif='Y'"); if ($q) $cnt_ayat = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM poin_ayat WHERE aktif='Y' AND kategori='Ringan'"); if ($q) $cnt_ringan = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM poin_ayat WHERE aktif='Y' AND kategori='Sedang'"); if ($q) $cnt_sedang = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM poin_ayat WHERE aktif='Y' AND kategori='Berat'"); if ($q) $cnt_berat = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM poin_ayat WHERE aktif='Y' AND kategori='Sangat Berat'"); if ($q) $cnt_sangat_berat = intval($q->fetch_assoc()['c']);

  $top_pelanggaran = [];
  $qt = $connection->query("SELECT pa.jenis_pelanggaran, pa.poin, pp2.nama_pasal, COUNT(pp.pelanggaran_id) AS total FROM poin_pelanggaran pp JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id JOIN poin_pasal pp2 ON pa.pasal_id=pp2.pasal_id WHERE pp.status='Aktif' GROUP BY pp.ayat_id ORDER BY total DESC LIMIT 5");
  if ($qt) while ($rt = $qt->fetch_assoc()) $top_pelanggaran[] = $rt;

  $pasal_list = [];
  $qp = $connection->query("SELECT pasal_id, kode_pasal, nama_pasal FROM poin_pasal ORDER BY urutan ASC, pasal_id ASC");
  if ($qp) while ($rp = $qp->fetch_assoc()) $pasal_list[] = $rp;

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

    // Stats cards
    echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Pasal</span><span class="value">'.$cnt_pasal.'</span></div><div class="icon"><i class="fas fa-book"></i></div></div>
      <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Ayat</span><span class="value">'.$cnt_ayat.'</span></div><div class="icon"><i class="fas fa-list"></i></div></div>
      <div class="module-stat-card user-stat-berkas-valid"><div class="info"><span class="label">Ringan</span><span class="value text-success">'.$cnt_ringan.'</span></div><div class="icon"><i class="fas fa-check"></i></div></div>
      <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Sedang</span><span class="value text-warning">'.$cnt_sedang.'</span></div><div class="icon"><i class="fas fa-exclamation"></i></div></div>
      <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Berat/S.Berat</span><span class="value text-danger">'.($cnt_berat+$cnt_sangat_berat).'</span></div><div class="icon"><i class="fas fa-fire"></i></div></div>
    </div></div></div></div></div></div>';

    // Top pelanggaran
    if (count($top_pelanggaran) > 0) {
      echo '<div class="card shadow module-table-card mb-4"><div class="card-header py-3 px-3 module-table-header"><div class="module-header-row"><div><h4 class="mb-1"><i class="fas fa-chart-bar text-danger mr-2"></i>Pelanggaran Paling Banyak</h4><small class="text-muted">5 pelanggaran yang paling sering terjadi.</small></div></div></div><div class="table-responsive"><table class="table align-items-center table-flush"><thead class="thead-light"><tr><th>Pelanggaran</th><th class="text-center">Pasal</th><th class="text-center">Poin</th><th class="text-center">Kasus</th></tr></thead><tbody>';
      foreach ($top_pelanggaran as $tp) {
        echo '<tr><td>'.htmlspecialchars($tp['jenis_pelanggaran']).'</td><td class="text-center"><small>'.htmlspecialchars($tp['nama_pasal']).'</small></td><td class="text-center"><span class="badge badge-danger">'.$tp['poin'].'</span></td><td class="text-center"><strong>'.$tp['total'].'</strong></td></tr>';
      }
      echo '</tbody></table></div></div>';
    }

    // Main DataTable card
    echo '<div class="card shadow module-table-card">
      <div class="card-header py-3 px-3 module-table-header">
        <div class="module-header-row" style="gap:10px;">
          <div><h4 class="mb-1">Daftar Ayat &amp; Pasal</h4><small class="text-muted">Kelola pasal dan ayat dalam tata tertib sekolah.</small></div>
          <div class="module-header-actions">
            <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterTatib" title="Filter"><i class="fas fa-filter"></i></button>';
    if ($data_role['modifikasi'] == 'Y') echo '<button class="btn-mod btn-mod-add" id="btn-tambah-tatib" title="Tambah"><i class="fas fa-plus"></i></button>';
    echo '</div></div></div>
      <div class="table-responsive">
        <table class="table align-items-center table-flush" id="tbl-tatib" width="100%">
          <thead class="thead-light">
            <tr>
              <th width="30">No</th>
              <th width="70">Kode</th>
              <th>Pasal</th>
              <th>Jenis Pelanggaran</th>
              <th class="text-center" width="110">Kategori</th>
              <th class="text-center" width="60">Poin</th>
              <th class="text-center" width="70">Status</th>
              <th class="text-center" width="110">Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>';

  } else { hak_akses(); }
  echo '</div>';

  // ========== MODALS ==========

  // Filter modal
  echo '<div class="modal fade" id="modalFilterTatib" tabindex="-1"><div class="modal-dialog modal-md modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Data</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
  <div class="modal-body pb-2">
    <div class="form-group"><label class="filter-label">Pasal</label>
      <select class="form-control form-control-sm" id="filter-pasal">
        <option value="">Semua Pasal</option>';
  foreach ($pasal_list as $p) echo '<option value="'.$p['pasal_id'].'">'.htmlspecialchars($p['kode_pasal'].' - '.$p['nama_pasal']).'</option>';
  echo '</select></div>
    <div class="form-group"><label class="filter-label">Kategori</label>
      <select class="form-control form-control-sm" id="filter-kategori">
        <option value="">Semua Kategori</option>
        <option value="Ringan">Ringan</option>
        <option value="Sedang">Sedang</option>
        <option value="Berat">Berat</option>
        <option value="Sangat Berat">Sangat Berat</option>
      </select></div>
    <div class="form-group"><label class="filter-label">Status</label>
      <select class="form-control form-control-sm" id="filter-aktif">
        <option value="">Semua Status</option>
        <option value="Y">Aktif</option>
        <option value="N">Nonaktif</option>
      </select></div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-tatib">Reset</button>
    <button type="button" class="btn btn-primary btn-sm btn-apply-filter-tatib">Terapkan</button>
  </div>
</div></div></div>';

  // Unified add/edit modal with tabs
  echo '<div class="modal fade" id="modal-tatib" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title" id="modal-tatib-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Data</h5>
    <button type="button" class="close" data-dismiss="modal">&times;</button>
  </div>
  <div class="modal-body">
    <ul class="nav nav-pills nav-fill mb-3" id="tatib-tabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-pasal-content" id="tab-pasal-link" role="tab">Pasal</a></li>
      <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-ayat-content" id="tab-ayat-link" role="tab">Ayat</a></li>
    </ul>
    <div class="tab-content">

      <div class="tab-pane fade show active" id="tab-pasal-content" role="tabpanel">
        <form id="form-pasal">
          <input type="hidden" id="pasal-id" name="pasal_id" value="0">
          <div class="form-group"><label class="font-weight-bold">Kode Pasal</label><input type="text" class="form-control" name="kode_pasal" id="pasal-kode" placeholder="Misal: Pasal 8" required></div>
          <div class="form-group"><label class="font-weight-bold">Nama Pasal</label><input type="text" class="form-control" name="nama_pasal" id="pasal-nama" required></div>
          <div class="form-group"><label class="font-weight-bold">Deskripsi <small class="text-muted">(opsional)</small></label><textarea class="form-control" name="deskripsi" id="pasal-deskripsi" rows="2"></textarea></div>
          <div class="row">
            <div class="col-6"><div class="form-group"><label class="font-weight-bold">Urutan</label><input type="number" class="form-control" name="urutan" id="pasal-urutan" value="0" min="0"></div></div>
            <div class="col-6"><div class="form-group"><label class="font-weight-bold">Status</label><select class="form-control" name="aktif" id="pasal-aktif"><option value="Y">Aktif</option><option value="N">Nonaktif</option></select></div></div>
          </div>
          <div class="text-right pt-2">
            <button type="button" class="btn btn-outline-danger btn-sm mr-auto" id="btn-hapus-pasal-modal" style="display:none"><i class="fas fa-trash mr-1"></i>Hapus Pasal</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-save mr-1"></i>Simpan Pasal</button>
          </div>
        </form>
      </div>

      <div class="tab-pane fade" id="tab-ayat-content" role="tabpanel">
        <form id="form-ayat">
          <input type="hidden" id="ayat-id" name="ayat_id" value="0">
          <div class="form-group"><label class="font-weight-bold">Pasal</label>
            <select class="form-control" name="pasal_id" id="ayat-pasal-id" required>
              <option value="">Pilih Pasal</option>';
  foreach ($pasal_list as $p) echo '<option value="'.$p['pasal_id'].'">'.htmlspecialchars($p['kode_pasal'].' - '.$p['nama_pasal']).'</option>';
  echo '</select></div>
          <div class="form-group"><label class="font-weight-bold">Kode Ayat</label><input type="text" class="form-control" name="kode_ayat" id="ayat-kode" required></div>
          <div class="form-group"><label class="font-weight-bold">Jenis Pelanggaran</label><input type="text" class="form-control" name="jenis_pelanggaran" id="ayat-jenis" required></div>
          <div class="form-group"><label class="font-weight-bold">Deskripsi <small class="text-muted">(opsional)</small></label><textarea class="form-control" name="deskripsi" id="ayat-deskripsi" rows="2"></textarea></div>
          <div class="row">
            <div class="col-4"><div class="form-group"><label class="font-weight-bold">Kategori</label><select class="form-control" name="kategori" id="ayat-kategori"><option value="Ringan">Ringan</option><option value="Sedang">Sedang</option><option value="Berat">Berat</option><option value="Sangat Berat">Sangat Berat</option></select></div></div>
            <div class="col-4"><div class="form-group"><label class="font-weight-bold">Poin</label><input type="number" class="form-control" name="poin" id="ayat-poin" min="0" value="10" required></div></div>
            <div class="col-4"><div class="form-group"><label class="font-weight-bold">Status</label><select class="form-control" name="aktif" id="ayat-aktif"><option value="Y">Aktif</option><option value="N">Nonaktif</option></select></div></div>
          </div>
          <input type="hidden" name="urutan" id="ayat-urutan" value="0">
          <div class="text-right pt-2">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-save mr-1"></i>Simpan Ayat</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div></div></div>';
}
