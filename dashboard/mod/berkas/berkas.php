<?php if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('location:../');
  exit();
} else {
  if (isset($_COOKIE['siswa'])) {
    // Ambil data berkas dari database
    $user_id = $data_user['user_id'] ?? '';
    $berkas = [];
    $waktu_upload = [];
    if (!empty($user_id)) {
      $q = $connection->query("SELECT * FROM berkas WHERE user_id='$user_id'");
      if ($q && $q->num_rows > 0) {
        $berkas = $q->fetch_assoc();
        $waktu_upload['kk'] = !empty($berkas['created_at']) ? $berkas['created_at'] : '';
        $waktu_upload['akte'] = !empty($berkas['created_at']) ? $berkas['created_at'] : '';
        $waktu_upload['ijazah'] = !empty($berkas['created_at']) ? $berkas['created_at'] : '';
        $waktu_upload['kip'] = !empty($berkas['created_at']) ? $berkas['created_at'] : '';
        $waktu_upload['kks'] = !empty($berkas['created_at']) ? $berkas['created_at'] : '';
        $waktu_upload['kis'] = !empty($berkas['created_at']) ? $berkas['created_at'] : '';
      }
    }
    $status_kk = !empty($berkas['kk']) ? 'Sudah upload' : 'Belum upload';
    $status_akte = !empty($berkas['akte']) ? 'Sudah upload' : 'Belum upload';
    $status_ijazah = !empty($berkas['ijazah']) ? 'Sudah upload' : 'Belum upload';
    $status_kip = !empty($berkas['kip']) ? 'Sudah upload' : 'Belum upload';
    $status_kks = !empty($berkas['kks']) ? 'Sudah upload' : 'Belum upload';
    $status_kis = !empty($berkas['kis']) ? 'Sudah upload' : 'Belum upload';

    // ---- Per-document validation status ----
    $doc_fields = ['kk', 'ijazah', 'akte', 'kip', 'kks', 'kis'];
    $doc_valid = [];
    $doc_valid_label = [];
    $doc_valid_class = [];
    foreach ($doc_fields as $f) {
      $v = $berkas[$f . '_valid'] ?? '';
      $doc_valid[$f] = $v;
      switch ($v) {
        case 'valid':
          $doc_valid_label[$f] = 'Valid';
          $doc_valid_class[$f] = 'success';
          break;
        case 'tidak_valid':
          $doc_valid_label[$f] = 'Tidak Valid';
          $doc_valid_class[$f] = 'danger';
          break;
        default:
          $doc_valid_label[$f] = '';
          $doc_valid_class[$f] = '';
          break;
      }
    }

    // Status overall (legacy)
    $validasi_label = 'Belum Divalidasi';
    $validasi_class = 'secondary';
    if (!empty($berkas['validasi_berkas'])) {
      switch ($berkas['validasi_berkas']) {
        case 'valid':
          $validasi_label = 'Valid';
          $validasi_class = 'success';
          break;
        case 'tidak_valid':
          $validasi_label = 'Tidak Valid';
          $validasi_class = 'danger';
          break;
      }
    }

    // Prepare display values for validator admin, note (keterangan) and last action time
    $validasi_admin_name = '';
    $keterangan_note = '';
    $last_action = '';
    if (!empty($berkas)) {
      if (!empty($berkas['validasi_by'])) {
        $admin_q = $connection->query("SELECT fullname FROM admin WHERE admin_id='" . intval($berkas['validasi_by']) . "' LIMIT 1");
        if ($admin_q && $admin_q->num_rows) {
          $admin_row = $admin_q->fetch_assoc();
          $validasi_admin_name = $admin_row['fullname'];
        }
      }
      // Collect per-document keterangan
      $all_keterangan = [];
      foreach ($doc_fields as $f) {
        if (!empty($berkas[$f . '_keterangan'])) {
          $all_keterangan[] = '<strong>' . ucfirst($f) . ':</strong> ' . nl2br(htmlspecialchars($berkas[$f . '_keterangan']));
        }
      }
      $keterangan_note = !empty($berkas['keterangan']) ? $berkas['keterangan'] : '';
      $last_action = !empty($berkas['updated_at']) ? date('d M Y H:i', strtotime($berkas['updated_at'])) : '';
    }

    echo '
<!-- Header -->
<div class="header pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Upload Berkas</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Card Validasi Berkas Responsive -->
<div class="container-fluid mt--6">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6">
      <div class="card border-' . $validasi_class . ' mb-4 shadow-sm">
        <div class="card-body d-flex flex-column flex-sm-row align-items-center justify-content-center gap-2 p-3">
            <span class="badge badge-' . $validasi_class . ' px-3 py-2 w-100 w-sm-auto text-wrap" style="font-size:1.05em;white-space:normal;">Status Validasi Berkas: <b>' . $validasi_label . '</b></span>
            ' . (
      (!empty($validasi_admin_name) || !empty($keterangan_note) || !empty($all_keterangan))
      ? ('<div class="w-100 text-center mt-2">' .
        (!empty($validasi_admin_name) ? '<small class="text-muted">oleh <strong>' . htmlspecialchars($validasi_admin_name) . '</strong></small>' : '') .
        (!empty($last_action) ? ' <small class="text-muted"> - ' . htmlspecialchars($last_action) . '</small>' : '') .
        '</div>')
      : ''
    ) . '
            ' . (
      // Show per-document keterangan (always visible if any)
      (!empty($all_keterangan) ? '<div class="w-100 mt-2 berkas-alasan-container"><div class="bg-danger text-white mb-0 p-2 text-center rounded" style="background-color:#dc3545 !important;"><strong class="d-block mb-1">Alasan Validasi</strong>' . implode('<br>', $all_keterangan) . '</div></div>' : '')
    ) . '
          </div>
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid berkas-upload-container">
  <div class="row">
    <div class="col">
      <div class="card pb-3 berkas-upload-card">
        <div class="card-header mb-2 berkas-card-header">
          <h3 class="berkas-card-title">Upload Berkas Murid</h3>
        </div>
        <div class="card-body berkas-card-body">
          <form method="post" enctype="multipart/form-data" action="#" class="form-upload">
            <div class="berkas-form-group">
              <label for="kk" class="berkas-form-label"><strong>Kartu Keluarga</strong><span class="berkas-required">(Wajib - salah satu)</span></label>
              ' . ($doc_valid['kk'] === 'valid' ? '
              <input type="file" class="form-control berkas-file-input" id="kk" name="kk" accept=".jpg,.jpeg,.png,.pdf" disabled tabindex="-1" style="opacity:0.5;">
              <div id="preview-kk" class="berkas-preview"></div>
              ' : '
              <input type="file" class="form-control berkas-file-input" id="kk" name="kk" accept=".jpg,.jpeg,.png,.pdf">
              <div id="preview-kk" class="berkas-preview"></div>
              ') . '
              <div class="berkas-status-info">
                <span class="berkas-status-badge ' . ($status_kk == 'Sudah upload' ? 'success' : 'danger') . '">' . $status_kk . '</span>
                ' . (!empty($doc_valid_label['kk']) ? '<span class="badge badge-' . $doc_valid_class['kk'] . ' ml-1">' . $doc_valid_label['kk'] . '</span>' : '') . '
                ' . (!empty($berkas['kk']) ? '<a href="#" data-file-type="kk" data-file-url="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kk'] . '" class="berkas-thumb-link"><span class="berkas-thumb">' . (preg_match('/\.(jpg|jpeg|png)$/i', $berkas['kk']) ? '<img src="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kk'] . '" alt="KK" class="berkas-thumb-img">' : '<i class="fas fa-file-pdf fa-2x text-danger"></i>') . '</span><span class="berkas-thumb-text">Lihat KK</span></a>' : '') . '
                ' . (!empty($waktu_upload['kk']) ? '<small class="berkas-upload-time">Upload: ' . $waktu_upload['kk'] . '</small>' : '') . '
              
                <div class="berkas-actions mt-2">
                  ' . ($doc_valid['kk'] === 'valid' ? '' : '<button type="button" class="btn btn-sm btn-primary berkas-upload-one" data-field="kk"><i class="fas fa-upload"></i> Upload</button>') . '
                  <button type="button" class="btn btn-sm btn-danger berkas-delete-one" data-field="kk"><i class="fas fa-trash"></i> Hapus</button>
                </div>
              </div>
            </div>
            <div class="berkas-form-group">
              <label for="ijazah" class="berkas-form-label"><strong>Ijazah Terakhir</strong><span class="berkas-required">(Wajib - salah satu)</span></label>
              ' . ($doc_valid['ijazah'] === 'valid' ? '
              <input type="file" class="form-control berkas-file-input" id="ijazah" name="ijazah" accept=".jpg,.jpeg,.png,.pdf" disabled tabindex="-1" style="opacity:0.5;">
              <div id="preview-ijazah" class="berkas-preview"></div>
              ' : '
              <input type="file" class="form-control berkas-file-input" id="ijazah" name="ijazah" accept=".jpg,.jpeg,.png,.pdf">
              <div id="preview-ijazah" class="berkas-preview"></div>
              ') . '
              <div class="berkas-status-info">
                <span class="berkas-status-badge ' . ($status_ijazah == 'Sudah upload' ? 'success' : 'danger') . '">' . $status_ijazah . '</span>
                ' . (!empty($doc_valid_label['ijazah']) ? '<span class="badge badge-' . $doc_valid_class['ijazah'] . ' ml-1">' . $doc_valid_label['ijazah'] . '</span>' : '') . '
                ' . (!empty($berkas['ijazah']) ? '<a href="#" data-file-type="ijazah" data-file-url="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['ijazah'] . '" class="berkas-thumb-link"><span class="berkas-thumb">' . (preg_match('/\.(jpg|jpeg|png)$/i', $berkas['ijazah']) ? '<img src="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['ijazah'] . '" alt="Ijazah" class="berkas-thumb-img">' : '<i class="fas fa-file-pdf fa-2x text-danger"></i>') . '</span><span class="berkas-thumb-text">Lihat Ijazah</span></a>' : '') . '
                ' . (!empty($waktu_upload['ijazah']) ? '<small class="berkas-upload-time">Upload: ' . $waktu_upload['ijazah'] . '</small>' : '') . '
                <div class="berkas-actions mt-2">
                  ' . ($doc_valid['ijazah'] === 'valid' ? '' : '<button type="button" class="btn btn-sm btn-primary berkas-upload-one" data-field="ijazah"><i class="fas fa-upload"></i> Upload</button>') . '
                  <button type="button" class="btn btn-sm btn-danger berkas-delete-one" data-field="ijazah"><i class="fas fa-trash"></i> Hapus</button>
                </div>
              </div>
            </div>
            <div class="berkas-form-group">
              <label for="akte" class="berkas-form-label">Akte Lahir</label>
              ' . ($doc_valid['akte'] === 'valid' ? '
              <input type="file" class="form-control berkas-file-input" id="akte" name="akte" accept=".jpg,.jpeg,.png,.pdf" disabled tabindex="-1" style="opacity:0.5;">
              <div id="preview-akte" class="berkas-preview"></div>
              ' : '
              <input type="file" class="form-control berkas-file-input" id="akte" name="akte" accept=".jpg,.jpeg,.png,.pdf">
              <div id="preview-akte" class="berkas-preview"></div>
              ') . '
              <div class="berkas-status-info">
                <span class="berkas-status-badge ' . ($status_akte == 'Sudah upload' ? 'success' : 'danger') . '">' . $status_akte . '</span>
                ' . (!empty($doc_valid_label['akte']) ? '<span class="badge badge-' . $doc_valid_class['akte'] . ' ml-1">' . $doc_valid_label['akte'] . '</span>' : '') . '
                ' . (!empty($berkas['akte']) ? '<a href="#" data-file-type="akte" data-file-url="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['akte'] . '" class="berkas-thumb-link"><span class="berkas-thumb">' . (preg_match('/\.(jpg|jpeg|png)$/i', $berkas['akte']) ? '<img src="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['akte'] . '" alt="Akte" class="berkas-thumb-img">' : '<i class="fas fa-file-pdf fa-2x text-danger"></i>') . '</span><span class="berkas-thumb-text">Lihat Akte</span></a>' : '') . '
                ' . (!empty($waktu_upload['akte']) ? '<small class="berkas-upload-time">Upload: ' . $waktu_upload['akte'] . '</small>' : '') . '
                <div class="berkas-actions mt-2">
                  ' . ($doc_valid['akte'] === 'valid' ? '' : '<button type="button" class="btn btn-sm btn-primary berkas-upload-one" data-field="akte"><i class="fas fa-upload"></i> Upload</button>') . '
                  <button type="button" class="btn btn-sm btn-danger berkas-delete-one" data-field="akte"><i class="fas fa-trash"></i> Hapus</button>
                </div>
              </div>
            </div>
            <div class="berkas-form-group">
              <label for="kip" class="berkas-form-label">Kartu Indonesia Pintar (KIP)</label>
              ' . ($doc_valid['kip'] === 'valid' ? '
              <input type="file" class="form-control berkas-file-input" id="kip" name="kip" accept=".jpg,.jpeg,.png,.pdf" disabled tabindex="-1" style="opacity:0.5;">
              <div id="preview-kip" class="berkas-preview"></div>
              ' : '
              <input type="file" class="form-control berkas-file-input" id="kip" name="kip" accept=".jpg,.jpeg,.png,.pdf">
              <div id="preview-kip" class="berkas-preview"></div>
              ') . '
              <div class="berkas-status-info">
                <span class="berkas-status-badge ' . ($status_kip == 'Sudah upload' ? 'success' : 'danger') . '">' . $status_kip . '</span>
                ' . (!empty($doc_valid_label['kip']) ? '<span class="badge badge-' . $doc_valid_class['kip'] . ' ml-1">' . $doc_valid_label['kip'] . '</span>' : '') . '
                ' . (!empty($berkas['kip']) ? '<a href="#" data-file-type="kip" data-file-url="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kip'] . '" class="berkas-thumb-link"><span class="berkas-thumb">' . (preg_match('/\.(jpg|jpeg|png)$/i', $berkas['kip']) ? '<img src="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kip'] . '" alt="KIP" class="berkas-thumb-img">' : '<i class="fas fa-file-pdf fa-2x text-danger"></i>') . '</span><span class="berkas-thumb-text">Lihat KIP</span></a>' : '') . '
                ' . (!empty($waktu_upload['kip']) ? '<small class="berkas-upload-time">Upload: ' . $waktu_upload['kip'] . '</small>' : '') . '
                <div class="berkas-actions mt-2">
                  ' . ($doc_valid['kip'] === 'valid' ? '' : '<button type="button" class="btn btn-sm btn-primary berkas-upload-one" data-field="kip"><i class="fas fa-upload"></i> Upload</button>') . '
                  <button type="button" class="btn btn-sm btn-danger berkas-delete-one" data-field="kip"><i class="fas fa-trash"></i> Hapus</button>
                </div>
              </div>
            </div>
            <div class="berkas-form-group">
              <label for="kks" class="berkas-form-label">Kartu Merah Putih (KKS/PKH/BPNT)</label>
              ' . ($doc_valid['kks'] === 'valid' ? '
              <input type="file" class="form-control berkas-file-input" id="kks" name="kks" accept=".jpg,.jpeg,.png,.pdf" disabled tabindex="-1" style="opacity:0.5;">
              <div id="preview-kks" class="berkas-preview"></div>
              ' : '
              <input type="file" class="form-control berkas-file-input" id="kks" name="kks" accept=".jpg,.jpeg,.png,.pdf">
              <div id="preview-kks" class="berkas-preview"></div>
              ') . '
              <div class="berkas-status-info">
                <span class="berkas-status-badge ' . ($status_kks == 'Sudah upload' ? 'success' : 'danger') . '">' . $status_kks . '</span>
                ' . (!empty($doc_valid_label['kks']) ? '<span class="badge badge-' . $doc_valid_class['kks'] . ' ml-1">' . $doc_valid_label['kks'] . '</span>' : '') . '
                ' . (!empty($berkas['kks']) ? '<a href="#" data-file-type="kks" data-file-url="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kks'] . '" class="berkas-thumb-link"><span class="berkas-thumb">' . (preg_match('/\.(jpg|jpeg|png)$/i', $berkas['kks']) ? '<img src="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kks'] . '" alt="KKS" class="berkas-thumb-img">' : '<i class="fas fa-file-pdf fa-2x text-danger"></i>') . '</span><span class="berkas-thumb-text">Lihat KKS</span></a>' : '') . '
                ' . (!empty($waktu_upload['kks']) ? '<small class="berkas-upload-time">Upload: ' . $waktu_upload['kks'] . '</small>' : '') . '
                <div class="berkas-actions mt-2">
                  ' . ($doc_valid['kks'] === 'valid' ? '' : '<button type="button" class="btn btn-sm btn-primary berkas-upload-one" data-field="kks"><i class="fas fa-upload"></i> Upload</button>') . '
                  <button type="button" class="btn btn-sm btn-danger berkas-delete-one" data-field="kks"><i class="fas fa-trash"></i> Hapus</button>
                </div>
              </div>
            </div>

            <div class="berkas-form-group">
              <label for="kis" class="berkas-form-label">Kartu Indonesia Sehat (KIS)</label>
              ' . ($doc_valid['kis'] === 'valid' ? '
              <input type="file" class="form-control berkas-file-input" id="kis" name="kis" accept=".jpg,.jpeg,.png,.pdf" disabled tabindex="-1" style="opacity:0.5;">
              <div id="preview-kis" class="berkas-preview"></div>
              ' : '
              <input type="file" class="form-control berkas-file-input" id="kis" name="kis" accept=".jpg,.jpeg,.png,.pdf">
              <div id="preview-kis" class="berkas-preview"></div>
              ') . '
              <div class="berkas-status-info">
                <span class="berkas-status-badge ' . ($status_kis == 'Sudah upload' ? 'success' : 'danger') . '">' . $status_kis . '</span>
                ' . (!empty($doc_valid_label['kis']) ? '<span class="badge badge-' . $doc_valid_class['kis'] . ' ml-1">' . $doc_valid_label['kis'] . '</span>' : '') . '
                ' . (!empty($berkas['kis']) ? '<a href="#" data-file-type="kis" data-file-url="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kis'] . '" class="berkas-thumb-link"><span class="berkas-thumb">' . (preg_match('/\.(jpg|jpeg|png)$/i', $berkas['kis']) ? '<img src="' . rtrim(str_replace('/dashboard', '', $base_url), '/') . '/content/berkas/' . $berkas['kis'] . '" alt="KIS" class="berkas-thumb-img">' : '<i class="fas fa-file-pdf fa-2x text-danger"></i>') . '</span><span class="berkas-thumb-text">Lihat KIS</span></a>' : '') . '
                ' . (!empty($waktu_upload['kis']) ? '<small class="berkas-upload-time">Upload: ' . $waktu_upload['kis'] . '</small>' : '') . '
                <div class="berkas-actions mt-2">
                  ' . ($doc_valid['kis'] === 'valid' ? '' : '<button type="button" class="btn btn-sm btn-primary berkas-upload-one" data-field="kis"><i class="fas fa-upload"></i> Upload</button>') . '
                  <button type="button" class="btn btn-sm btn-danger berkas-delete-one" data-field="kis"><i class="fas fa-trash"></i> Hapus</button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>';
    // Add preview modal markup and lightweight styles
    echo '
<!-- Preview Modal -->
<div class="modal fade" id="modalPreviewBerkas" tabindex="-1" aria-labelledby="modalPreviewBerkasLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPreviewBerkasLabel">Preview Berkas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div id="previewContainer" style="max-height:75vh; overflow:auto;">
          <!-- dynamic content -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>
.berkas-thumb { display:inline-flex; align-items:center; gap:8px; }
.berkas-thumb-img { max-height:48px; border-radius:4px; object-fit:cover; }
#previewContainer img { max-width:100%; height:auto; cursor:zoom-in; transition: transform .2s ease; }
#previewContainer .pdf-iframe { width:100%; height:75vh; border:0; }
.berkas-thumb-link { display:inline-flex; align-items:center; gap:10px; text-decoration:none; color:inherit; }
.berkas-thumb-link .berkas-thumb-text { color: #1e73be; margin-left:6px; font-weight:600; }
.berkas-thumb-link .fa-file-pdf { vertical-align:middle; }
/* Prevent global alert auto-dismiss from affecting per-doc alasan */
.berkas-alasan-container { animation:none !important; transition:none !important; opacity:1 !important; visibility:visible !important; }
</style>
';
  }
}
