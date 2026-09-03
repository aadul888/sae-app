<?php
/**
 * MODUL: SURAT — Dashboard Persuratan
 * Data dummy sampai struktur database final ditentukan.
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}

$modul_id = 52;
include __DIR__ . '/../check_role.php';
if (!$has_access) {
  hak_akses();
  return;
}

$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
$can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

$stats = [
  'masuk' => 48,
  'keluar' => 32,
  'pending' => 3,
  'arsip' => 124,
  'bulan' => 18,
  'draft' => 5,
];

$recent = [
  ['id'=>1, 'jenis'=>'Masuk', 'nomor'=>'SM/008/VI/2026', 'tanggal'=>'2026-06-21', 'asal_tujuan'=>'Pusat Prestasi Nasional', 'perihal'=>'Undangan Olimpiade Sains Nasional 2026', 'status'=>'Diterima'],
  ['id'=>2, 'jenis'=>'Keluar', 'nomor'=>'SK/006/VI/2026', 'tanggal'=>'2026-06-20', 'asal_tujuan'=>'Dinas Pendidikan Provinsi', 'perihal'=>'Penyerahan Laporan Bulanan Sekolah', 'status'=>'Terkirim'],
  ['id'=>3, 'jenis'=>'Masuk', 'nomor'=>'SM/007/VI/2026', 'tanggal'=>'2026-06-18', 'asal_tujuan'=>'Kementerian Pendidikan', 'perihal'=>'Jadwal Pelaksanaan ANBK 2026', 'status'=>'Diproses'],
  ['id'=>4, 'jenis'=>'Keluar', 'nomor'=>'SK/005/VI/2026', 'tanggal'=>'2026-06-15', 'asal_tujuan'=>'Puskesmas Kecamatan', 'perihal'=>'Permohonan Kegiatan Imunisasi Siswa', 'status'=>'Draf'],
  ['id'=>5, 'jenis'=>'Masuk', 'nomor'=>'SM/006/VI/2026', 'tanggal'=>'2026-06-13', 'asal_tujuan'=>'Komite Sekolah', 'perihal'=>'Usulan Kegiatan Peringatan HUT RI ke-81', 'status'=>'Diterima'],
];
?>

<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>

<div class="container-fluid mt--6 user-module-page surat-dashboard-page">
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid">
              <div class="user-stat-card module-stat-card user-stat-total">
                <div class="info"><span class="label">Surat Masuk</span><span class="value"><?php echo number_format($stats['masuk']); ?></span><div class="sub-info"><small><i class="fas fa-arrow-down"></i> 5 hari ini</small></div></div>
                <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-identitas">
                <div class="info"><span class="label">Surat Keluar</span><span class="value"><?php echo number_format($stats['keluar']); ?></span><div class="sub-info"><small><i class="fas fa-paper-plane"></i> <?php echo $stats['bulan']; ?> bulan ini</small></div></div>
                <div class="icon"><i class="fas fa-paper-plane"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-belum-sesuai">
                <div class="info"><span class="label">Tindak Lanjut</span><span class="value"><?php echo number_format($stats['pending']); ?></span><div class="sub-info"><small><i class="fas fa-clock"></i> perlu diproses</small></div></div>
                <div class="icon"><i class="fas fa-clock"></i></div>
              </div>
              <div class="user-stat-card module-stat-card user-stat-belum">
                <div class="info"><span class="label">Arsip Surat</span><span class="value"><?php echo number_format($stats['arsip']); ?></span><div class="sub-info"><small><i class="fas fa-archive"></i> terdokumentasi</small></div></div>
                <div class="icon"><i class="fas fa-archive"></i></div>
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
          <h4 class="mb-1">Dashboard Persuratan</h4>
          <small class="text-muted">Ringkasan layanan surat, referensi indeks, surat masuk, surat keluar, dan arsip.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table module-header-actions">
          <a href="./surat-index" class="btn-mod btn-mod-info" title="Referensi Surat"><i class="fas fa-list"></i></a>
          <a href="./surat-masuk" class="btn-mod btn-mod-teal" title="Surat Masuk"><i class="fas fa-envelope-open-text"></i></a>
          <a href="./surat-keluar" class="btn-mod btn-mod-warn" title="Surat Keluar"><i class="fas fa-paper-plane"></i></a>
          <a href="./surat-arsip" class="btn-mod btn-mod-secondary" title="Arsip Surat"><i class="fas fa-archive"></i></a>
          <?php if ($can_edit): ?>
            <button type="button" class="btn-mod btn-mod-add" data-toggle="modal" data-target="#modalSuratBaru" title="Tambah Surat"><i class="fas fa-plus"></i></button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card-body pt-3">
      <div class="row">
        <div class="col-xl-4 col-lg-5 mb-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h5 class="mb-2"><i class="fas fa-hashtag text-primary mr-2"></i>Nomor Surat Terakhir</h5>
              <div class="h2 mb-1 font-weight-bold">0029/KPG.11.01-SMKN1PGL</div>
              <p class="text-muted text-sm mb-3">Format nomor contoh dari referensi indeks surat.</p>
              <a href="./surat-index" class="btn btn-sm btn-outline-primary"><i class="fas fa-list mr-1"></i>Kelola Referensi</a>
            </div>
          </div>
        </div>
        <div class="col-xl-8 col-lg-7 mb-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h5 class="mb-3"><i class="fas fa-chart-line text-success mr-2"></i>Alur Kerja Persuratan</h5>
              <div class="row text-center">
                <div class="col-6 col-md-3 mb-2"><span class="badge badge-primary p-2 w-100">1. Input</span></div>
                <div class="col-6 col-md-3 mb-2"><span class="badge badge-warning p-2 w-100">2. Disposisi</span></div>
                <div class="col-6 col-md-3 mb-2"><span class="badge badge-info p-2 w-100">3. Tindak Lanjut</span></div>
                <div class="col-6 col-md-3 mb-2"><span class="badge badge-success p-2 w-100">4. Arsip</span></div>
              </div>
              <p class="text-muted text-sm mb-0 mt-2">Dashboard ini masih memakai data dummy dan siap disambungkan ke tabel database persuratan.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table align-items-center table-flush table-striped surat-dashboard-table" width="100%">
          <thead class="thead-light">
            <tr>
              <th class="text-center" style="width:10px">No</th>
              <th>Jenis</th>
              <th>Nomor</th>
              <th>Tanggal</th>
              <th>Asal/Tujuan</th>
              <th>Perihal</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($recent as $row):
              $jenisBadge = $row['jenis'] === 'Masuk' ? 'primary' : 'success';
              $statusBadge = $row['status'] === 'Diterima' || $row['status'] === 'Terkirim' ? 'success' : ($row['status'] === 'Diproses' ? 'warning' : 'secondary');
            ?>
              <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><span class="badge badge-<?php echo $jenisBadge; ?>"><?php echo htmlspecialchars($row['jenis']); ?></span></td>
                <td><code><?php echo htmlspecialchars($row['nomor']); ?></code></td>
                <td><small><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></small></td>
                <td><?php echo htmlspecialchars($row['asal_tujuan']); ?></td>
                <td><strong><?php echo htmlspecialchars($row['perihal']); ?></strong></td>
                <td><span class="badge badge-<?php echo $statusBadge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td class="text-center"><button class="btn btn-sm btn-outline-primary btn-detail-surat" data-id="<?php echo $row['id']; ?>"><i class="fas fa-eye"></i></button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalSuratBaru" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Tambah Surat</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form class="form-surat-dummy">
        <div class="modal-body">
          <div class="form-group"><label>Jenis Surat</label><select class="form-control"><option>Surat Masuk</option><option>Surat Keluar</option></select></div>
          <div class="form-group"><label>Nomor Surat</label><input class="form-control" placeholder="0029/KPG.11.01-SMKN1PGL"></div>
          <div class="form-group"><label>Perihal</label><textarea class="form-control" rows="2" placeholder="Perihal surat"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button></div>
      </form>
    </div>
  </div>
</div>
