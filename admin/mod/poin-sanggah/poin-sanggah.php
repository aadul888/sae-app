<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
} else {
  $modul_id = 36;
  include __DIR__ . '/../check_role.php';

  // Stats
  $cnt_menunggu=0; $cnt_disetujui=0; $cnt_ditolak=0; $cnt_selesai=0;
  $q=$connection->query("SELECT status, COUNT(*) c FROM poin_sanggah GROUP BY status");
  if($q) while($r=$q->fetch_assoc()){ switch($r['status']){ case 'Menunggu': $cnt_menunggu=$r['c']; break; case 'Disetujui': $cnt_disetujui=$r['c']; break; case 'Ditolak': $cnt_ditolak=$r['c']; break; case 'Selesai': $cnt_selesai=$r['c']; break; }}

  echo '
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>
<div class="container-fluid mt--6 user-module-page">';;

  if ($data_role['lihat'] == 'Y') {

    echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Menunggu</span><span class="value text-warning">'.$cnt_menunggu.'</span></div><div class="icon"><i class="fas fa-clock"></i></div></div>
      <div class="module-stat-card user-stat-berkas-valid"><div class="info"><span class="label">Disetujui</span><span class="value text-success">'.$cnt_disetujui.'</span></div><div class="icon"><i class="fas fa-check"></i></div></div>
      <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Ditolak</span><span class="value text-danger">'.$cnt_ditolak.'</span></div><div class="icon"><i class="fas fa-times"></i></div></div>
      <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Selesai</span><span class="value">'.$cnt_selesai.'</span></div><div class="icon"><i class="fas fa-check-double"></i></div></div>
    </div></div></div></div></div></div>';

    echo '<div class="card user-table-panel module-table-card shadow">
      <div class="card-header py-3 px-3 module-table-header"><div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Daftar Sanggahan Siswa</h4><small class="text-muted">Kelola dan proses sanggahan poin pelanggaran siswa.</small></div>
      <div class="module-header-actions">
        <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterSanggah" title="Filter"><i class="fas fa-filter"></i></button>
      </div></div></div>
      <div class="table-responsive">
        <table class="table align-items-center table-flush" id="tbl-sanggah" width="100%">
          <thead class="thead-light"><tr>
            <th width="30">No</th><th>Siswa</th><th>Pelanggaran</th><th class="text-center">Jenis</th><th>Alasan</th><th class="text-center">Status</th><th class="text-center">Tanggal</th><th class="text-center" width="100">Aksi</th>
          </tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>';

  } else { hak_akses(); }
  echo '</div>';

  // Modal Proses Sanggah
  echo '
<div class="modal fade" id="modal-proses-sanggah" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form id="form-proses-sanggah">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-gavel mr-2"></i>Proses Sanggahan</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><input type="hidden" id="sanggah-id-proses" name="sanggah_id">
<div id="sanggah-detail-content"><div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
<hr>
<div class="form-group"><label class="font-weight-bold">Keputusan</label><select class="form-control" name="status" id="sanggah-keputusan" required><option value="Disetujui">Disetujui</option><option value="Ditolak">Ditolak</option></select></div>
<div id="sanggah-setuju-fields">
<div class="form-group"><label class="font-weight-bold">Poin Dikurangi</label><input type="number" class="form-control" name="poin_dikurangi" id="sanggah-poin-kurang" min="0" value="0"></div>
<div class="form-group"><label class="font-weight-bold">Kesepakatan/Syarat <small class="text-muted">(Opsional)</small></label><textarea class="form-control" name="kesepakatan" rows="2" placeholder="Misal: Bakti sosial 3 hari, piket tambahan, dll"></textarea></div>
</div>
<div class="form-group"><label class="font-weight-bold">Catatan Admin</label><textarea class="form-control" name="catatan_admin" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan Keputusan</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button></div>
</form></div></div></div>';
}
?>

<!-- Modal Filter Sanggahan -->
<div class="modal fade" id="modalFilterSanggah" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Sanggahan</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body pb-2">
        <div class="form-group">
          <label class="filter-label">Status</label>
          <select class="form-control form-control-sm" id="filter-status-sanggah">
            <option value="">Semua Status</option>
            <option value="Menunggu">Menunggu</option>
            <option value="Disetujui">Disetujui</option>
            <option value="Ditolak">Ditolak</option>
            <option value="Selesai">Selesai</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-sanggah">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-sanggah">Terapkan</button>
      </div>
    </div>
  </div>
</div>
