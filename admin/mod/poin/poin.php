<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
} else {
  $modul_id = 34;
  include __DIR__ . '/../check_role.php';

  // Stats
  $stat_total = 0; $stat_bulan = 0; $stat_aktif = 0; $stat_70plus = 0;
  $q = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran"); if ($q) $stat_total = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE MONTH(tanggal_kejadian)=MONTH(CURDATE()) AND YEAR(tanggal_kejadian)=YEAR(CURDATE())"); if ($q) $stat_bulan = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE status='Aktif'"); if ($q) $stat_aktif = intval($q->fetch_assoc()['c']);
  $q = $connection->query("SELECT COUNT(DISTINCT user_id) c FROM (SELECT user_id, SUM(poin_diberikan) total FROM poin_pelanggaran WHERE status='Aktif' GROUP BY user_id HAVING total >= 100) t"); if ($q) $stat_70plus = intval($q->fetch_assoc()['c']);

  // Kelas list (for filter only)
  $kelas_list = [];
  $qk = $connection->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
  if ($qk) while ($rk = $qk->fetch_assoc()) $kelas_list[] = $rk;

  // Pasal + Ayat for select
  $pasal_ayat = [];
  $qpa = $connection->query("SELECT pa.ayat_id, pa.jenis_pelanggaran, pa.poin, pa.kategori, pp.kode_pasal, pp.nama_pasal FROM poin_ayat pa JOIN poin_pasal pp ON pa.pasal_id=pp.pasal_id WHERE pa.aktif='Y' AND pp.aktif='Y' ORDER BY pp.urutan, pa.urutan");
  if ($qpa) while ($r = $qpa->fetch_assoc()) $pasal_ayat[] = $r;

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

    // Stats
    echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Record</span><span class="value">'.$stat_total.'</span></div><div class="icon"><i class="fas fa-database"></i></div></div>
      <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Bulan Ini</span><span class="value">'.$stat_bulan.'</span></div><div class="icon"><i class="fas fa-calendar-day"></i></div></div>
      <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Aktif</span><span class="value text-warning">'.$stat_aktif.'</span></div><div class="icon"><i class="fas fa-exclamation-circle"></i></div></div>
      <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Murid ≥100 Poin</span><span class="value text-danger">'.$stat_70plus.'</span></div><div class="icon"><i class="fas fa-user-slash"></i></div></div>
    </div></div></div></div></div></div>';

    // Filter replaced with modal - see modalFilterPelanggaran at bottom
    echo '<div class="card user-table-panel module-table-card shadow">
      <div class="card-header py-3 px-3 module-table-header">
        <div class="module-header-row" style="gap:10px;">
          <div><h4 class="mb-1">Data Pencatatan Pelanggaran</h4><small class="text-muted">Kelola dan pantau catatan pelanggaran siswa.</small></div>
          <div class="module-header-actions">
            <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterPelanggaran" title="Filter"><i class="fas fa-filter"></i></button>';
    if ($data_role['modifikasi'] == 'Y') echo '<button class="btn-mod btn-mod-add" id="btn-tambah-pelanggaran" title="Catat Pelanggaran"><i class="fas fa-plus"></i></button>';
    echo '</div></div></div>
      <div class="table-responsive">
        <table class="table align-items-center table-flush" id="tbl-pelanggaran" width="100%">
          <thead class="thead-light">
            <tr>
              <th width="30">No</th>
              <th>Murid</th>
              <th>Kelas</th>
              <th>Pelanggaran</th>
              <th class="text-center">Poin</th>
              <th class="text-center">Tanggal</th>
              <th class="text-center">Status</th>
              <th class="text-center" width="80">Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>';

  } else { hak_akses(); }
  echo '</div>';

  // Modal Tambah/Edit Pelanggaran
  echo '
<div class="modal fade" id="modal-pelanggaran" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form id="form-pelanggaran" enctype="multipart/form-data">
<div class="modal-header"><h5 class="modal-title" id="modal-pelanggaran-title"><i class="fas fa-exclamation-triangle mr-2"></i>Catat Pelanggaran</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><input type="hidden" id="pelanggaran-id" name="pelanggaran_id" value="0"><input type="hidden" name="user_id" id="pel-user-id" value="0">
<div class="row">
<div class="col-md-6">
  <div class="form-group"><label class="font-weight-bold">Cari Murid</label>
    <input type="text" class="form-control" id="pel-cari-murid" placeholder="Ketik NISN atau nama (min 3 karakter)" autocomplete="off">
    <div id="pel-murid-results" class="list-group" style="position:absolute;z-index:1050;width:calc(100% - 30px);max-height:200px;overflow-y:auto;display:none;"></div>
  </div>
  <div id="pel-murid-selected" class="d-none alert alert-light py-2 mb-2"><div class="d-flex justify-content-between align-items-center"><div id="pel-murid-nama"></div><button type="button" class="btn btn-sm btn-outline-danger btn-clear-murid"><i class="fas fa-times"></i></button></div></div>
  <div id="pel-ortu-info" class="d-none">
    <label class="font-weight-bold"><i class="fas fa-users mr-1"></i>Data Orang Tua / Wali</label>
    <table class="table table-sm table-borderless mb-0"><tbody id="pel-ortu-table"></tbody></table>
  </div>
</div>
<div class="col-md-6">
  <div class="form-group"><label class="font-weight-bold">Jenis Pelanggaran</label><select class="form-control" name="ayat_id" id="pel-ayat" required><option value="">Pilih pelanggaran</option>';
  $prev_pasal = '';
  foreach ($pasal_ayat as $pa) {
    if ($pa['kode_pasal'] !== $prev_pasal) {
      if ($prev_pasal !== '') echo '</optgroup>';
      echo '<optgroup label="'.htmlspecialchars($pa['kode_pasal'].' - '.$pa['nama_pasal']).'">';
      $prev_pasal = $pa['kode_pasal'];
    }
    echo '<option value="'.$pa['ayat_id'].'" data-poin="'.$pa['poin'].'" data-kategori="'.$pa['kategori'].'">'.htmlspecialchars($pa['jenis_pelanggaran']).' ('.$pa['poin'].' poin)</option>';
  }
  if ($prev_pasal !== '') echo '</optgroup>';
  echo '</select></div>
  <div class="form-group"><label class="font-weight-bold">Poin Diberikan</label><input type="number" class="form-control" name="poin_diberikan" id="pel-poin" min="0" value="0" required><small class="text-muted">Otomatis dari master, bisa di-override</small></div>
  <div class="form-group"><label class="font-weight-bold">Tanggal Kejadian</label><input type="date" class="form-control" name="tanggal_kejadian" id="pel-tanggal" required></div>
</div>
</div>
<div class="form-group"><label class="font-weight-bold">Keterangan</label><textarea class="form-control" name="keterangan" id="pel-keterangan" rows="2" placeholder="Catatan tambahan"></textarea></div>
<div class="row">
<div class="col-md-6"><div class="form-group"><label class="font-weight-bold">Bukti Foto <small class="text-muted">(opsional, maks 2MB)</small></label><input type="file" class="form-control-file" name="bukti_foto" id="pel-bukti-foto" accept="image/jpeg,image/png,image/jpg"><small class="text-muted">Format: JPG, JPEG, PNG</small></div></div>
<div class="col-md-6"><div class="form-group"><label class="font-weight-bold">Bukti Video <small class="text-muted">(opsional, maks 5MB)</small></label><input type="file" class="form-control-file" name="bukti_video" id="pel-bukti-video" accept="video/mp4,video/mpeg,video/quicktime"><small class="text-muted">Format: MP4, MPEG, MOV</small></div></div>
</div>
<div class="row"><div class="col-md-6"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="pel-tindak" name="perlu_tindak_lanjut" value="Y"><label class="custom-control-label" for="pel-tindak">Perlu tindak lanjut</label></div></div></div>
<div id="pel-repeat-warning" class="alert alert-warning mt-3 d-none"><i class="fas fa-redo mr-2"></i><span></span></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button></div>
</form></div></div></div>

<div class="modal fade" id="modal-detail" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-info-circle mr-2"></i>Detail Pelanggaran</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body" id="detail-content"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
</div></div></div>';
}
?>

<!-- Modal Filter Pelanggaran -->
<div class="modal fade" id="modalFilterPelanggaran" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Pelanggaran</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body pb-2">
        <div class="form-group">
          <label class="filter-label">Kelas</label>
          <select class="form-control form-control-sm" id="filter-kelas">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelas_list as $k) echo '<option value="'.$k['kelas_id'].'">'.htmlspecialchars($k['nama_kelas']).'</option>'; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="filter-label">Status</label>
          <select class="form-control form-control-sm" id="filter-status">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Disanggah">Disanggah</option>
            <option value="Dikurangi">Dikurangi</option>
            <option value="Dihapus">Dihapus</option>
          </select>
        </div>
        <div class="form-group">
          <label class="filter-label">Dari Tanggal</label>
          <input type="date" class="form-control form-control-sm" id="filter-dari">
        </div>
        <div class="form-group">
          <label class="filter-label">Sampai Tanggal</label>
          <input type="date" class="form-control form-control-sm" id="filter-sampai">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-pelanggaran">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-pelanggaran">Terapkan</button>
      </div>
    </div>
  </div>
</div>
