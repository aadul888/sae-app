<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
} else {
  $modul_id = 35;
  include __DIR__ . '/../check_role.php';

  // Siswa dengan poin >= 100 yang belum dipanggil
  $belum_panggil = [];
  $qb = $connection->query("SELECT u.user_id, u.nama_lengkap, u.nisn, u.telp_ortu, u.nama_ayah, k.nama_kelas,
    SUM(pp.poin_diberikan) AS total_poin, COUNT(pp.pelanggaran_id) AS jml_kasus
    FROM poin_pelanggaran pp
    JOIN user u ON pp.user_id=u.user_id
    LEFT JOIN kelas k ON pp.kelas_id=k.kelas_id
    WHERE pp.status='Aktif'
    GROUP BY pp.user_id HAVING total_poin >= 100
    ORDER BY total_poin DESC");
  if ($qb) while ($r = $qb->fetch_assoc()) {
    // Check if already have pending panggilan
    $qp = $connection->query("SELECT panggil_id FROM poin_panggil WHERE user_id=".$r['user_id']." AND status IN ('Menunggu') LIMIT 1");
    $r['sudah_panggil'] = ($qp && $qp->num_rows > 0) ? 'Y' : 'N';
    $belum_panggil[] = $r;
  }

  // Stats panggil
  $stat_panggil_total = 0; $stat_panggil_menunggu = 0; $stat_panggil_hadir = 0; $stat_panggil_tidak_hadir = 0;
  $qsp = $connection->query("SELECT SUM(status='Menunggu') AS menunggu, SUM(status='Hadir') AS hadir, SUM(status='Tidak Hadir') AS tidak_hadir, COUNT(*) AS total FROM poin_panggil");
  if ($qsp && $rsp = $qsp->fetch_assoc()) {
    $stat_panggil_total = intval($rsp['total']); $stat_panggil_menunggu = intval($rsp['menunggu']);
    $stat_panggil_hadir = intval($rsp['hadir']); $stat_panggil_tidak_hadir = intval($rsp['tidak_hadir']);
  }

  // Kelas for filter
  $kelas_list = [];
  $qk = $connection->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas");
  if ($qk) while ($rk = $qk->fetch_assoc()) $kelas_list[] = $rk;

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

    // Stats cards above main section
    echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Panggilan</span><span class="value">'.$stat_panggil_total.'</span></div><div class="icon"><i class="fas fa-phone-alt"></i></div></div>
      <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Menunggu</span><span class="value text-warning">'.$stat_panggil_menunggu.'</span></div><div class="icon"><i class="fas fa-clock"></i></div></div>
      <div class="module-stat-card user-stat-identitas"><div class="info"><span class="label">Hadir</span><span class="value text-success">'.$stat_panggil_hadir.'</span></div><div class="icon"><i class="fas fa-user-check"></i></div></div>
      <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Tidak Hadir</span><span class="value text-danger">'.$stat_panggil_tidak_hadir.'</span></div><div class="icon"><i class="fas fa-user-times"></i></div></div>
    </div></div></div></div></div></div>';

    // Alert siswa 70+ poin
    if (count($belum_panggil) > 0) {
      $need_action = array_filter($belum_panggil, function($s){ return $s['sudah_panggil'] === 'N'; });
      if (count($need_action) > 0) {
        echo '<div class="alert alert-danger shadow-sm mb-4"><div class="d-flex align-items-center"><i class="fas fa-exclamation-triangle fa-2x mr-3"></i><div><h5 class="mb-0 text-white">Perhatian!</h5><p class="mb-0">Ada <strong>'.count($need_action).'</strong> siswa dengan total poin ≥100 yang belum dipanggil orang tuanya.</p></div></div></div>';
      }

      echo '<div class="card shadow module-table-card mb-4"><div class="card-header py-3 px-3 module-table-header"><div class="module-header-row"><div><h4 class="mb-1"><i class="fas fa-users text-danger mr-2"></i>Siswa Poin &ge; 100</h4><small class="text-muted">Siswa yang memerlukan pemanggilan orang tua.</small></div></div></div>
        <div class="table-responsive"><table class="table align-items-center table-flush"><thead class="thead-light"><tr><th>Siswa</th><th class="text-center">Kelas</th><th class="text-center">Total Poin</th><th class="text-center">Kasus</th><th class="text-center">Ortu</th><th class="text-center">Status</th><th class="text-center" width="100">Aksi</th></tr></thead><tbody>';
      foreach ($belum_panggil as $s) {
        $poin_cls = $s['total_poin'] >= 100 ? 'dark' : 'danger';
        echo '<tr><td><strong>'.htmlspecialchars($s['nama_lengkap']).'</strong><br><small class="text-muted">'.$s['nisn'].'</small></td>
          <td class="text-center">'.htmlspecialchars($s['nama_kelas']??'-').'</td>
          <td class="text-center"><span class="badge badge-'.$poin_cls.'" style="font-size:16px">'.$s['total_poin'].'</span></td>
          <td class="text-center">'.$s['jml_kasus'].'</td>
          <td class="text-center"><small>'.htmlspecialchars($s['nama_ayah']??'-').'<br>'.$s['telp_ortu'].'</small></td>
          <td class="text-center">';
        if ($s['sudah_panggil'] == 'Y') echo '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Sudah Dijadwalkan</span>';
        else echo '<span class="badge badge-danger"><i class="fas fa-exclamation mr-1"></i>Belum Dipanggil</span>';
        echo '</td><td class="text-center">';
        if ($data_role['modifikasi'] == 'Y' && $s['sudah_panggil'] == 'N') {
          echo '<button class="btn btn-sm btn-danger btn-buat-panggilan" data-userid="'.$s['user_id'].'" data-nama="'.htmlspecialchars($s['nama_lengkap']).'" data-poin="'.$s['total_poin'].'"><i class="fas fa-phone mr-1"></i>Panggil</button>';
        }
        echo '</td></tr>';
      }
      echo '</tbody></table></div></div>';
    }

    // Riwayat panggilan
    echo '<div class="card shadow module-table-card">
      <div class="card-header module-table-header">
        <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Riwayat Pemanggilan</h4><small class="text-muted">Kelola dan pantau riwayat pemanggilan orang tua siswa bermasalah.</small></div>
        <div class="module-header-actions">
          <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterPanggil" title="Filter"><i class="fas fa-filter"></i></button>
        </div></div>
      </div>
      <div class="table-responsive">
        <table class="table align-items-center table-flush" id="tbl-panggil" width="100%">
          <thead class="thead-light"><tr>
            <th width="30">No</th><th>Siswa</th><th>Kelas</th><th class="text-center">Poin</th><th class="text-center">Jenis</th><th class="text-center">Tgl Panggil</th><th class="text-center">Status</th><th class="text-center" width="100">Aksi</th>
          </tr></thead><tbody></tbody>
        </table>
      </div>
    </div>';

  } else { hak_akses(); }
  echo '</div>';

  // Modal Buat Panggilan
  echo '
<div class="modal fade" id="modal-panggilan" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form id="form-panggilan">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-phone-alt mr-2"></i>Buat Pemanggilan</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><input type="hidden" name="user_id" id="panggil-userid"><input type="hidden" name="total_poin" id="panggil-total-poin">
<div class="alert alert-light"><strong id="panggil-nama"></strong> - Total Poin: <span class="badge badge-danger" id="panggil-poin-badge"></span></div>
<div class="form-group"><label class="font-weight-bold">Jenis Pemanggilan</label><select class="form-control" name="jenis_panggilan" required>
  <option value="Pemanggilan Orang Tua">Pemanggilan Orang Tua</option>
  <option value="Surat Peringatan">Surat Peringatan</option>
  <option value="Skorsing">Skorsing</option>
  <option value="Dikeluarkan">Dikeluarkan</option>
</select></div>
<div class="form-group"><label class="font-weight-bold">Alasan</label><textarea class="form-control" name="alasan" rows="3" required placeholder="Alasan pemanggilan orang tua..."></textarea></div>
<div class="form-group"><label class="font-weight-bold">Tanggal Panggil</label><input type="date" class="form-control" name="tanggal_panggil" required></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-danger"><i class="fas fa-phone mr-1"></i>Buat Panggilan</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button></div>
</form></div></div></div>

<div class="modal fade" id="modal-hasil-panggilan" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form id="form-hasil">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-clipboard-check mr-2"></i>Hasil Pertemuan</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><input type="hidden" name="panggil_id" id="hasil-panggil-id">
<div class="form-group"><label class="font-weight-bold">Status Kehadiran</label><select class="form-control" name="status" required><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></div>
<div class="form-group"><label class="font-weight-bold">Tanggal Hadir</label><input type="date" class="form-control" name="tanggal_hadir"></div>
<div class="form-group"><label class="font-weight-bold">Hasil Pertemuan</label><textarea class="form-control" name="hasil_pertemuan" rows="3"></textarea></div>
<div class="form-group"><label class="font-weight-bold">Tindakan yang Disepakati</label><textarea class="form-control" name="tindakan" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button></div>
</form></div></div></div>';
}
?>

<!-- Modal Filter Panggilan -->
<div class="modal fade" id="modalFilterPanggil" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Pemanggilan</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body pb-2">
        <div class="form-group">
          <label class="filter-label">Status</label>
          <select class="form-control form-control-sm" id="filter-status-panggil">
            <option value="">Semua Status</option>
            <option value="Menunggu">Menunggu</option>
            <option value="Hadir">Hadir</option>
            <option value="Tidak Hadir">Tidak Hadir</option>
            <option value="Selesai">Selesai</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-panggil">Reset</button>
        <button type="button" class="btn btn-primary btn-sm btn-apply-filter-panggil">Terapkan</button>
      </div>
    </div>
  </div>
</div>
