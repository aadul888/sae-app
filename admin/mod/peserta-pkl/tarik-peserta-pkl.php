<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 51;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    $can_send = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');

    // Load PKL endpoint config (shared with the Sync module)
    $cfg_file = __DIR__ . '/../sync/pkl_sync_config.json';
    $cfg = ['pkl_base_url' => '', 'api_token' => ''];
    if (is_file($cfg_file)) {
      $tmp = json_decode((string)file_get_contents($cfg_file), true);
      if (is_array($tmp)) $cfg = array_merge($cfg, $tmp);
    }

    // Filter dropdowns
    $kelas_opt = '';
    $rk = $connection->query("SELECT kelas_id, nama_kelas FROM kelas WHERE (tingkat_pendidikan_id='12' OR UPPER(nama_kelas) LIKE 'XII%') ORDER BY nama_kelas ASC");
    if ($rk) while ($k = $rk->fetch_assoc()) $kelas_opt .= '<option value="' . htmlspecialchars($k['kelas_id'], ENT_QUOTES) . '">' . htmlspecialchars($k['nama_kelas']) . '</option>';
    $jur_opt = '';
    $rj = $connection->query("SELECT jurusan_id, nama_jurusan FROM jurusan ORDER BY nama_jurusan ASC");
    if ($rj) while ($j = $rj->fetch_assoc()) $jur_opt .= '<option value="' . htmlspecialchars($j['jurusan_id'], ENT_QUOTES) . '">' . htmlspecialchars($j['nama_jurusan']) . '</option>';

    echo '
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid"><div class="header-body"><div class="row align-items-center py-3"></div></div></div>
</div>

<div class="container-fluid mt--6 user-module-page">
  <div class="row">
    <div class="col">
      <div class="card shadow">
        <div class="card-header module-table-header">
          <div class="module-header-row" style="gap:10px;">
            <div>
              <h4 class="mb-1">Tarik Peserta PKL</h4>
              <small class="text-muted">Pilih siswa kelas XII aktif lalu kirim ke aplikasi e-PKL.</small>
            </div>
            <div class="module-header-actions">
              <button class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#modalKonfigPkl"><i class="fas fa-cog mr-1"></i>Konfigurasi Endpoint</button>
            </div>
          </div>
        </div>

        <div class="card-body pb-0">';
    if (empty($cfg['pkl_base_url']) || empty($cfg['api_token'])) {
      echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Endpoint e-PKL belum dikonfigurasi. Klik <b>Konfigurasi Endpoint</b> untuk mengatur URL dan token.</div>';
    } else {
      echo '<div class="alert alert-info py-2"><small><i class="fas fa-link mr-1"></i>Target: <code>' . htmlspecialchars($cfg['pkl_base_url']) . '</code></small></div>';
    }
    echo '
          <form id="filterPkl" class="form-row align-items-end">
            <div class="form-group col-auto mb-2"><label class="small mb-1">Kelas</label><select id="f_kelas" class="form-control form-control-sm"><option value="">Semua Kelas XII</option>' . $kelas_opt . '</select></div>
            <div class="form-group col-auto mb-2"><label class="small mb-1">Jurusan</label><select id="f_jurusan" class="form-control form-control-sm"><option value="">Semua Jurusan</option>' . $jur_opt . '</select></div>
            <div class="form-group col-auto mb-2"><label class="small mb-1">Status Kirim</label><select id="f_kirim" class="form-control form-control-sm"><option value="">Semua</option><option value="belum">Belum Terkirim</option><option value="Terkirim">Terkirim</option><option value="Gagal">Gagal</option></select></div>
            <div class="form-group col-auto mb-2"><label class="small mb-1 d-block">&nbsp;</label><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Terapkan</button></div>
          </form>';
    if ($can_send) {
      echo '
          <div class="d-flex align-items-center mb-2" style="gap:10px;">
            <button id="btnKirim" class="btn btn-sm btn-success"><i class="fas fa-paper-plane mr-1"></i>Kirim Terpilih (<span id="selCount">0</span>)</button>
            <small class="text-muted">Centang siswa pada tabel lalu klik Kirim.</small>
          </div>';
    }
    echo '
        </div>

        <div class="table-responsive">
          <table class="table align-items-center table-flush table-striped" id="tablePkl" width="100%">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="4"><input type="checkbox" id="checkAll"></th>
                <th class="text-center" width="4">No</th>
                <th>NISN</th>
                <th>Nama</th>
                <th class="text-center">Kelas</th>
                <th>Jurusan</th>
                <th class="text-center">Status Kirim</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>';

    // Config modal
    echo '
<div class="modal fade" id="modalKonfigPkl" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-secondary">
        <h5 class="modal-title text-white"><i class="fas fa-cog mr-2"></i>Konfigurasi Endpoint e-PKL</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formKonfigPkl">
        <div class="modal-body">
          <div class="form-group">
            <label>URL Endpoint <span class="text-danger">*</span></label>
            <input type="url" name="pkl_base_url" class="form-control" value="' . htmlspecialchars($cfg['pkl_base_url'], ENT_QUOTES) . '" placeholder="http://localhost/pklv1/api/kirim-data" ' . ($can_send ? '' : 'readonly') . '>
            <small class="text-muted">Endpoint penerima di aplikasi e-PKL (mis. pklv1). Data dikirim via POST JSON.</small>
          </div>
          <div class="form-group">
            <label>API Token (Bearer) <span class="text-danger">*</span></label>
            <input type="text" name="api_token" class="form-control" value="' . htmlspecialchars($cfg['api_token'], ENT_QUOTES) . '" placeholder="Token rahasia" ' . ($can_send ? '' : 'readonly') . '>
          </div>
        </div>';
    if ($can_send) {
      echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button></div>';
    } else {
      echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>';
    }
    echo '
      </form>
    </div>
  </div>
</div>';

    echo '<script>window.PKL_PERM = { send: ' . ($can_send ? 'true' : 'false') . ' };</script>';
  } else {
    hak_akses();
  }
}
?>
