<?php if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('location:../');
  exit();
} else {
  if (isset($_COOKIE['siswa'])) {
    $user_id = $data_user['user_id'] ?? '';
    $kelas_id = $data_user['kelas'] ?? '';
    $nama_kelas = '';

    // Ambil nama kelas
    if (!empty($kelas_id)) {
      $qk = $connection->prepare("SELECT nama_kelas FROM kelas WHERE kelas_id = ? LIMIT 1");
      if ($qk) {
        $qk->bind_param('i', $kelas_id);
        $qk->execute();
        $rk = $qk->get_result();
        if ($rk && $rk->num_rows > 0) {
          $dk = $rk->fetch_assoc();
          $nama_kelas = $dk['nama_kelas'];
        }
        $qk->close();
      }
    }

    // Ambil tahun ajaran otomatis
    $bulan = intval(date('m'));
    $tahun = intval(date('Y'));
    if ($bulan >= 7) {
      $tahun_ajaran = $tahun . '/' . ($tahun + 1);
    } else {
      $tahun_ajaran = ($tahun - 1) . '/' . $tahun;
    }

    // Ambil semua kategori & barang untuk form
    $kategori_list = [];
    $qkat = $connection->query("SELECT * FROM inv_kategori ORDER BY nama_kategori ASC");
    if ($qkat) {
      while ($rkat = $qkat->fetch_assoc()) {
        $kategori_list[] = $rkat;
      }
    }

    $barang_list = [];
    $qbrg = $connection->query("SELECT b.*, k.nama_kategori FROM inv_barang b LEFT JOIN inv_kategori k ON b.kategori_id = k.kategori_id ORDER BY k.nama_kategori, b.nama_barang ASC");
    if ($qbrg) {
      while ($rbrg = $qbrg->fetch_assoc()) {
        $barang_list[] = $rbrg;
      }
    }

    // Hitung statistik inventaris kelas
    $cnt_total = 0;
    $cnt_baik = 0;
    $cnt_rusak = 0;
    $cnt_laporan = 0;
    if (!empty($kelas_id)) {
      $q = $connection->prepare("SELECT COUNT(*) AS cnt FROM inv_kelas WHERE kelas_id = ?");
      if ($q) { $q->bind_param('i', $kelas_id); $q->execute(); $r = $q->get_result()->fetch_assoc(); $cnt_total = intval($r['cnt']); $q->close(); }

      $q = $connection->prepare("SELECT COUNT(*) AS cnt FROM inv_kelas WHERE kelas_id = ? AND kondisi = 'Baik'");
      if ($q) { $q->bind_param('i', $kelas_id); $q->execute(); $r = $q->get_result()->fetch_assoc(); $cnt_baik = intval($r['cnt']); $q->close(); }

      $q = $connection->prepare("SELECT COUNT(*) AS cnt FROM inv_kelas WHERE kelas_id = ? AND kondisi IN ('Rusak Ringan','Rusak Berat')");
      if ($q) { $q->bind_param('i', $kelas_id); $q->execute(); $r = $q->get_result()->fetch_assoc(); $cnt_rusak = intval($r['cnt']); $q->close(); }

      $q = $connection->prepare("SELECT COUNT(*) AS cnt FROM inv_laporan WHERE kelas_id = ? AND user_id = ?");
      if ($q) { $q->bind_param('ii', $kelas_id, $user_id); $q->execute(); $r = $q->get_result()->fetch_assoc(); $cnt_laporan = intval($r['cnt']); $q->close(); }
    }

    // Message handling
    $message = '';
    if (!empty($_GET['msg'])) {
      if ($_GET['msg'] === 'success') $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i>Data berhasil disimpan.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
      if ($_GET['msg'] === 'deleted') $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i>Data berhasil dihapus.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
      if ($_GET['msg'] === 'report_success') $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i>Laporan berhasil dikirim.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    }
    if (!empty($_GET['error'])) {
      $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i>' . htmlspecialchars($_GET['error']) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    }

    echo '
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <h6 class="h2 text-white d-inline-block mb-0"><i class="fas fa-clipboard-list mr-2"></i>Inventaris Kelas</h6>
          <p class="text-white-50 mb-0 mt-1">Kelas: <strong>' . htmlspecialchars($nama_kelas) . '</strong> | TA: ' . htmlspecialchars($tahun_ajaran) . '</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6">
  ' . $message . '

  <!-- Statistik -->
  <div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
      <div class="card card-stats shadow-sm border-0">
        <div class="card-body py-3 px-3">
          <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow mr-3" style="width:45px;height:45px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-boxes"></i>
            </div>
            <div>
              <p class="text-muted mb-0 small">Total Item</p>
              <h4 class="font-weight-bold mb-0">' . $cnt_total . '</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card card-stats shadow-sm border-0">
        <div class="card-body py-3 px-3">
          <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow mr-3" style="width:45px;height:45px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-check-circle"></i>
            </div>
            <div>
              <p class="text-muted mb-0 small">Kondisi Baik</p>
              <h4 class="font-weight-bold mb-0 text-success">' . $cnt_baik . '</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card card-stats shadow-sm border-0">
        <div class="card-body py-3 px-3">
          <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow mr-3" style="width:45px;height:45px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
              <p class="text-muted mb-0 small">Rusak</p>
              <h4 class="font-weight-bold mb-0 text-warning">' . $cnt_rusak . '</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="card card-stats shadow-sm border-0">
        <div class="card-body py-3 px-3">
          <div class="d-flex align-items-center">
            <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow mr-3" style="width:45px;height:45px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-flag"></i>
            </div>
            <div>
              <p class="text-muted mb-0 small">Laporan Saya</p>
              <h4 class="font-weight-bold mb-0 text-info">' . $cnt_laporan . '</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab Navigation -->
  <div class="card shadow-sm">
    <div class="card-header border-0 pb-0">
      <ul class="nav nav-tabs" id="invTab" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="tab-inventaris" data-toggle="tab" href="#panel-inventaris" role="tab">
            <i class="fas fa-clipboard-list mr-1"></i> Data Inventaris
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="tab-tambah" data-toggle="tab" href="#panel-tambah" role="tab">
            <i class="fas fa-plus-circle mr-1"></i> Input Data
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="tab-laporan" data-toggle="tab" href="#panel-laporan" role="tab">
            <i class="fas fa-flag mr-1"></i> Laporan
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="tab-riwayat" data-toggle="tab" href="#panel-riwayat" role="tab">
            <i class="fas fa-history mr-1"></i> Riwayat Laporan
          </a>
        </li>
      </ul>
    </div>
    <div class="card-body pt-3">
      <div class="tab-content" id="invTabContent">

        <!-- TAB 1: Data Inventaris Kelas -->
        <div class="tab-pane fade show active" id="panel-inventaris" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover table-striped align-items-center" id="tbl-inventaris">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" width="40">No</th>
                  <th>Nama Barang</th>
                  <th class="text-center">Kategori</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-center">Kondisi</th>
                  <th>Keterangan</th>
                  <th class="text-center">Tanggal</th>
                  <th class="text-center" width="80">Aksi</th>
                </tr>
              </thead>
              <tbody>';

    // Ambil data inventaris kelas ini
    if (!empty($kelas_id)) {
      $qi = $connection->prepare("SELECT ik.*, ib.nama_barang, ib.kode_barang, ic.nama_kategori 
        FROM inv_kelas ik 
        LEFT JOIN inv_barang ib ON ik.barang_id = ib.barang_id 
        LEFT JOIN inv_kategori ic ON ib.kategori_id = ic.kategori_id 
        WHERE ik.kelas_id = ? 
        ORDER BY ik.created_at DESC");
      if ($qi) {
        $qi->bind_param('i', $kelas_id);
        $qi->execute();
        $ri = $qi->get_result();
        $no = 1;
        while ($di = $ri->fetch_assoc()) {
          $kondisi_badge = 'success';
          if ($di['kondisi'] === 'Rusak Ringan') $kondisi_badge = 'warning';
          elseif ($di['kondisi'] === 'Rusak Berat') $kondisi_badge = 'danger';
          elseif ($di['kondisi'] === 'Hilang') $kondisi_badge = 'dark';

          $can_edit = (intval($di['user_id']) === intval($user_id));

          echo '<tr>
            <td class="text-center">' . $no++ . '</td>
            <td><strong>' . htmlspecialchars($di['nama_barang']) . '</strong><br><small class="text-muted">' . htmlspecialchars($di['kode_barang'] ?? '') . '</small></td>
            <td class="text-center"><span class="badge badge-light">' . htmlspecialchars($di['nama_kategori']) . '</span></td>
            <td class="text-center">' . intval($di['jumlah']) . '</td>
            <td class="text-center"><span class="badge badge-' . $kondisi_badge . '">' . htmlspecialchars($di['kondisi']) . '</span></td>
            <td>' . htmlspecialchars($di['keterangan'] ?? '-') . '</td>
            <td class="text-center"><small>' . date('d/m/Y', strtotime($di['tanggal_input'])) . '</small></td>
            <td class="text-center">';
          if ($can_edit) {
            echo '<a href="javascript:void(0)" class="btn-edit-inv text-info mx-1" data-id="' . $di['inv_id'] . '" data-barang="' . $di['barang_id'] . '" data-jumlah="' . $di['jumlah'] . '" data-kondisi="' . htmlspecialchars($di['kondisi']) . '" data-keterangan="' . htmlspecialchars($di['keterangan'] ?? '') . '" title="Edit"><i class="fas fa-edit"></i></a>';
            echo '<a href="javascript:void(0)" class="btn-hapus-inv text-danger mx-1" data-id="' . $di['inv_id'] . '" title="Hapus"><i class="fas fa-trash"></i></a>';
          }
          echo '</td></tr>';
        }
        $qi->close();
      }
    }

    echo '
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 2: Input Data Inventaris -->
        <div class="tab-pane fade" id="panel-tambah" role="tabpanel">
          <form id="form-inventaris" method="post">
            <input type="hidden" name="action" value="tambah_inventaris">
            <input type="hidden" name="kelas_id" value="' . intval($kelas_id) . '">
            <input type="hidden" name="tahun_ajaran" value="' . htmlspecialchars($tahun_ajaran) . '">
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Kategori <span class="text-danger">*</span></label>
                <select class="form-control" id="sel-kategori" required>
                  <option value="">-- Pilih Kategori --</option>';
    foreach ($kategori_list as $kat) {
      echo '<option value="' . $kat['kategori_id'] . '">' . htmlspecialchars($kat['nama_kategori']) . '</option>';
    }
    echo '
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Barang/Sarana <span class="text-danger">*</span></label>
                <select class="form-control" id="sel-barang" name="barang_id" required>
                  <option value="">-- Pilih Kategori Dulu --</option>';
    // Group by kategori for JS filtering
    foreach ($barang_list as $brg) {
      echo '<option value="' . $brg['barang_id'] . '" data-kategori="' . $brg['kategori_id'] . '">' . htmlspecialchars($brg['nama_barang']) . ' (' . htmlspecialchars($brg['satuan']) . ')</option>';
    }
    echo '
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Jumlah <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="jumlah" min="0" value="1" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Kondisi <span class="text-danger">*</span></label>
                <select class="form-control" name="kondisi" required>
                  <option value="Baik">Baik</option>
                  <option value="Rusak Ringan">Rusak Ringan</option>
                  <option value="Rusak Berat">Rusak Berat</option>
                  <option value="Hilang">Hilang</option>
                </select>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label font-weight-bold">Foto (opsional)</label>
                <input type="file" class="form-control-file" name="foto" accept="image/*">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label font-weight-bold">Keterangan</label>
              <textarea class="form-control" name="keterangan" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Data</button>
          </form>
        </div>

        <!-- TAB 3: Form Laporan Kerusakan/Kehilangan/Kebutuhan -->
        <div class="tab-pane fade" id="panel-laporan" role="tabpanel">
          <form id="form-laporan" method="post">
            <input type="hidden" name="action" value="tambah_laporan">
            <input type="hidden" name="kelas_id" value="' . intval($kelas_id) . '">
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Jenis Laporan <span class="text-danger">*</span></label>
                <select class="form-control" name="jenis_laporan" required>
                  <option value="">-- Pilih --</option>
                  <option value="Kerusakan">Kerusakan</option>
                  <option value="Kehilangan">Kehilangan</option>
                  <option value="Kebutuhan">Kebutuhan</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Prioritas</label>
                <select class="form-control" name="prioritas">
                  <option value="Rendah">Rendah</option>
                  <option value="Sedang" selected>Sedang</option>
                  <option value="Tinggi">Tinggi</option>
                  <option value="Urgent">Urgent</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Barang Terkait (opsional)</label>
                <select class="form-control" name="barang_id">
                  <option value="">-- Tidak ada / Umum --</option>';
    foreach ($barang_list as $brg) {
      echo '<option value="' . $brg['barang_id'] . '">' . htmlspecialchars($brg['nama_barang']) . '</option>';
    }
    echo '
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label font-weight-bold">Foto Bukti (opsional)</label>
                <input type="file" class="form-control-file" name="foto_laporan" accept="image/*">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label font-weight-bold">Deskripsi Laporan <span class="text-danger">*</span></label>
              <textarea class="form-control" name="deskripsi" rows="4" placeholder="Jelaskan detail kerusakan/kehilangan/kebutuhan..." required></textarea>
            </div>
            <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane mr-1"></i> Kirim Laporan</button>
          </form>
        </div>

        <!-- TAB 4: Riwayat Laporan Saya -->
        <div class="tab-pane fade" id="panel-riwayat" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover table-striped" id="tbl-riwayat">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" width="40">No</th>
                  <th>Jenis</th>
                  <th>Deskripsi</th>
                  <th class="text-center">Prioritas</th>
                  <th class="text-center">Status</th>
                  <th>Catatan Admin</th>
                  <th class="text-center">Tanggal</th>
                </tr>
              </thead>
              <tbody>';

    if (!empty($user_id)) {
      $ql = $connection->prepare("SELECT il.*, ib.nama_barang FROM inv_laporan il LEFT JOIN inv_barang ib ON il.barang_id = ib.barang_id WHERE il.user_id = ? ORDER BY il.created_at DESC");
      if ($ql) {
        $ql->bind_param('i', $user_id);
        $ql->execute();
        $rl = $ql->get_result();
        $no = 1;
        while ($dl = $rl->fetch_assoc()) {
          $status_badge = 'warning';
          if ($dl['status'] === 'Diproses') $status_badge = 'info';
          elseif ($dl['status'] === 'Selesai') $status_badge = 'success';
          elseif ($dl['status'] === 'Ditolak') $status_badge = 'danger';

          $prioritas_badge = 'secondary';
          if ($dl['prioritas'] === 'Sedang') $prioritas_badge = 'info';
          elseif ($dl['prioritas'] === 'Tinggi') $prioritas_badge = 'warning';
          elseif ($dl['prioritas'] === 'Urgent') $prioritas_badge = 'danger';

          echo '<tr>
            <td class="text-center">' . $no++ . '</td>
            <td><span class="badge badge-' . ($dl['jenis_laporan'] === 'Kerusakan' ? 'danger' : ($dl['jenis_laporan'] === 'Kehilangan' ? 'dark' : 'info')) . '">' . htmlspecialchars($dl['jenis_laporan']) . '</span>
              ' . (!empty($dl['nama_barang']) ? '<br><small class="text-muted">' . htmlspecialchars($dl['nama_barang']) . '</small>' : '') . '
            </td>
            <td>' . htmlspecialchars(mb_strimwidth($dl['deskripsi'], 0, 80, '...')) . '</td>
            <td class="text-center"><span class="badge badge-' . $prioritas_badge . '">' . htmlspecialchars($dl['prioritas']) . '</span></td>
            <td class="text-center"><span class="badge badge-' . $status_badge . '">' . htmlspecialchars($dl['status']) . '</span></td>
            <td>' . htmlspecialchars($dl['catatan_admin'] ?? '-') . '</td>
            <td class="text-center"><small>' . date('d/m/Y', strtotime($dl['tanggal_laporan'])) . '</small></td>
          </tr>';
        }
        $ql->close();
      }
    }

    echo '
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Inventaris -->
<div class="modal fade" id="modal-edit-inv" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-edit-inv">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Inventaris</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-inv-id" name="inv_id">
          <div class="form-group">
            <label class="font-weight-bold">Jumlah</label>
            <input type="number" class="form-control" id="edit-jumlah" name="jumlah" min="0" required>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Kondisi</label>
            <select class="form-control" id="edit-kondisi" name="kondisi" required>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Berat">Rusak Berat</option>
              <option value="Hilang">Hilang</option>
            </select>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Keterangan</label>
            <textarea class="form-control" id="edit-keterangan" name="keterangan" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>';

  } else {
    header('location:../login');
    exit;
  }
}
