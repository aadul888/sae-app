<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 26;
  include __DIR__ . '/../check_role.php';

  // Statistik
  $cnt_total = 0; $cnt_dipinjam = 0; $cnt_kembali = 0; $cnt_terlambat = 0; $cnt_hilang = 0;
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_pinjam"); if ($q) $cnt_total = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_pinjam WHERE status='Dipinjam'"); if ($q) $cnt_dipinjam = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_pinjam WHERE status='Dikembalikan'"); if ($q) $cnt_kembali = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_pinjam WHERE status='Terlambat'"); if ($q) $cnt_terlambat = intval($q->fetch_assoc()['cnt']);
  $q = $connection->query("SELECT COUNT(*) AS cnt FROM inv_pinjam WHERE status='Hilang'"); if ($q) $cnt_hilang = intval($q->fetch_assoc()['cnt']);

  // Daftar kelas, barang
  $kelas_list = [];
  $qk = $connection->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
  if ($qk) while ($rk = $qk->fetch_assoc()) $kelas_list[] = $rk;

  $barang_list = [];
  $qb = $connection->query("SELECT barang_id, nama_barang FROM inv_barang ORDER BY nama_barang ASC");
  if ($qb) while ($rb = $qb->fetch_assoc()) $barang_list[] = $rb;

  switch (@$_GET['op']) {
    default:
      echo '
<!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>

<div class="container-fluid mt--6 user-module-page">';;

      if ($data_role['lihat'] == 'Y') {
        // Stats
        echo '<div class="row"><div class="col-12"><div class="card user-stats-panel module-stats-shell mb-3"><div class="card-body py-2 px-2 px-md-3"><div class="user-stats-wrap"><div class="user-stats module-stats-grid">
      <div class="module-stat-card user-stat-total"><div class="info"><span class="label">Total Peminjaman</span><span class="value">' . $cnt_total . '</span></div><div class="icon"><i class="fas fa-exchange-alt"></i></div></div>
      <div class="module-stat-card user-stat-belum"><div class="info"><span class="label">Sedang Dipinjam</span><span class="value text-warning">' . $cnt_dipinjam . '</span></div><div class="icon"><i class="fas fa-hand-holding"></i></div></div>
      <div class="module-stat-card user-stat-berkas-valid"><div class="info"><span class="label">Dikembalikan</span><span class="value text-success">' . $cnt_kembali . '</span></div><div class="icon"><i class="fas fa-check-circle"></i></div></div>
      <div class="module-stat-card user-stat-belum-sesuai"><div class="info"><span class="label">Terlambat</span><span class="value text-danger">' . $cnt_terlambat . '</span></div><div class="icon"><i class="fas fa-exclamation-triangle"></i></div></div>
      <div class="module-stat-card user-stat-wali"><div class="info"><span class="label">Hilang</span><span class="value">' . $cnt_hilang . '</span></div><div class="icon"><i class="fas fa-ban"></i></div></div>
    </div></div></div></div></div></div>';

        // Status filter hidden (used by JS)
        echo '<div style="display:none"><select id="filter-status-pinjam-hidden"><option value=""></option><option value="Dipinjam">Dipinjam</option><option value="Dikembalikan">Dikembalikan</option><option value="Terlambat">Terlambat</option><option value="Hilang">Hilang</option></select></div>';

        echo '
  <div class="card user-table-panel module-table-card shadow">
    <div class="card-header py-3 px-3 module-table-header">
      <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Data Peminjaman Barang</h4><small class="text-muted">Kelola pencatatan peminjaman dan pengembalian inventaris sekolah.</small></div>
      <div class="module-header-actions">
        <button class="btn-mod btn-mod-teal" data-toggle="modal" data-target="#modalFilterInvPinjam" title="Filter"><i class="fas fa-filter"></i></button>';
        if ($data_role['modifikasi'] == 'Y') echo '<button class="btn-mod btn-mod-add" id="btn-tambah-pinjam" title="Tambah Peminjaman"><i class="fas fa-plus"></i></button>';
        echo '</div></div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-striped datatable align-items-center table-flush" width="auto">
        <thead class="thead-light">
          <tr>
            <th class="text-center" width="40">No</th>
            <th>Peminjam</th>
            <th>Kelas</th>
            <th>Barang</th>
            <th class="text-center">Jumlah</th>
            <th class="text-center">Tgl Pinjam</th>
            <th class="text-center">Tgl Kembali</th>
            <th class="text-center">Status</th>
            <th class="text-center" width="120">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>';
      } else {
        hak_akses();
      }

      echo '</div>';

      // Modal Filter
      echo '
<!-- Modal Filter Peminjaman -->
<div class="modal fade" id="modalFilterInvPinjam" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter mr-2"></i>Filter Peminjaman</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="font-weight-bold">Status</label>
          <select class="form-control" id="filter-status-pinjam">
            <option value="">Semua Status</option>
            <option value="Dipinjam">Dipinjam</option>
            <option value="Dikembalikan">Dikembalikan</option>
            <option value="Terlambat">Terlambat</option>
            <option value="Hilang">Hilang</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm btn-reset-filter-pinjam"><i class="fas fa-times mr-1"></i>Reset</button>
        <button class="btn btn-primary btn-sm btn-apply-filter-pinjam"><i class="fas fa-check mr-1"></i>Terapkan</button>
      </div>
    </div>
  </div>
</div>';

      // Modal Tambah / Edit Peminjaman
      echo '
<div class="modal fade" id="modal-pinjam" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-pinjam">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-pinjam-title"><i class="fas fa-plus mr-2"></i>Tambah Peminjaman</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="pinjam-id" name="pinjam_id" value="0">
          <div class="form-group">
            <label class="font-weight-bold">Peminjam (Siswa)</label>
            <select class="form-control" id="pinjam-user" name="user_id" required>
              <option value="">-- Pilih Siswa --</option>
            </select>
            <small class="text-muted">Ketik nama atau NISN untuk mencari</small>
          </div>
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label class="font-weight-bold">Barang</label>
                <select class="form-control" id="pinjam-barang" name="barang_id" required>
                  <option value="">-- Pilih Barang --</option>';
      foreach ($barang_list as $b) {
        echo '<option value="' . $b['barang_id'] . '">' . htmlspecialchars($b['nama_barang']) . '</option>';
      }
      echo '
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="font-weight-bold">Jumlah</label>
                <input type="number" class="form-control" name="jumlah_pinjam" id="pinjam-jumlah" min="1" value="1" required>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Kelas</label>
            <select class="form-control" id="pinjam-kelas" name="kelas_id" required>
              <option value="">-- Pilih Kelas --</option>';
      foreach ($kelas_list as $kls) {
        echo '<option value="' . $kls['kelas_id'] . '">' . htmlspecialchars($kls['nama_kelas']) . '</option>';
      }
      echo '
            </select>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Tanggal Pinjam</label>
                <input type="date" class="form-control" name="tanggal_pinjam" id="pinjam-tgl-pinjam" value="' . date('Y-m-d') . '" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Rencana Kembali</label>
                <input type="date" class="form-control" name="tanggal_kembali" id="pinjam-tgl-rencana">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Keterangan</label>
            <textarea class="form-control" name="keterangan" id="pinjam-keterangan" rows="2" placeholder="Keperluan peminjaman..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Kembalikan -->
<div class="modal fade" id="modal-kembalikan" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-kembalikan">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-undo mr-2"></i>Proses Pengembalian</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="kembali-id" name="pinjam_id">
          <div class="form-group">
            <label class="font-weight-bold">Status Pengembalian</label>
            <select class="form-control" name="status" id="kembali-status" required>
              <option value="Dikembalikan">Dikembalikan (Normal)</option>
              <option value="Terlambat">Terlambat</option>
              <option value="Hilang">Hilang</option>
            </select>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Tanggal Kembali Aktual</label>
                <input type="date" class="form-control" name="tanggal_dikembalikan" id="kembali-tgl" value="' . date('Y-m-d') . '" required>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Catatan</label>
            <textarea class="form-control" name="keterangan" id="kembali-keterangan" rows="2" placeholder="Catatan pengembalian..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Proses</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>';
      break;
  }
}
