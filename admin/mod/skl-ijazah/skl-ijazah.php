<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}

$modul_id = 48;
include __DIR__ . '/../check_role.php';

if (!$has_access) {
    theme_404();
    return;
}

require_once __DIR__ . '/../../../library/kelulusan_helper.php';
kelulusan_ensure_tables($connection);

$students = kelulusan_get_final_grade_students($connection);

$kelas_list = [];
foreach ($students as $s) {
    $kn = !empty($s['nama_kelas']) ? $s['nama_kelas'] : '-';
    if (!in_array($kn, $kelas_list)) $kelas_list[] = $kn;
}
sort($kelas_list);

$ijazah_map = [];
if (!empty($students)) {
    $uids = array_map(fn($s) => (int)$s['user_id'], $students);
    $uid_in = implode(',', $uids);
    $q = $connection->query("SELECT * FROM kelulusan_ijazah WHERE user_id IN ($uid_in)");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $ijazah_map[(int)$r['user_id']] = $r;
        }
    }
}

$total = count($students);
$sudah_upload = count($ijazah_map);
$belum_upload = $total - $sudah_upload;
$konfirmasi_sesuai = 0;
$konfirmasi_tidak = 0;
foreach ($ijazah_map as $ij) {
    if ($ij['konfirmasi'] === 'sesuai') $konfirmasi_sesuai++;
    elseif ($ij['konfirmasi'] === 'tidak_sesuai') $konfirmasi_tidak++;
}
?>
<div class="header bg-primary pb-4 user-page-header-compact">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-3"></div>
        </div>
    </div>
</div>

<div class="container-fluid mt--6 user-module-page">
    <!-- Statistik -->
    <div class="row">
        <div class="col-12">
            <div class="card user-stats-panel module-stats-shell mb-3">
                <div class="card-body py-2 px-2 px-md-3">
                    <div class="user-stats-wrap">
                        <div class="user-stats module-stats-grid">
                            <div class="module-stat-card user-stat-total">
                                <div class="info"><span class="label">Total Siswa</span><span class="value"><?= $total ?></span></div>
                                <div class="icon"><i class="fas fa-users"></i></div>
                            </div>
                            <div class="module-stat-card user-stat-berkas-valid">
                                <div class="info"><span class="label">Sudah Upload</span><span class="value text-success"><?= $sudah_upload ?></span></div>
                                <div class="icon"><i class="fas fa-file-pdf"></i></div>
                            </div>
                            <div class="module-stat-card user-stat-belum-sesuai">
                                <div class="info"><span class="label">Belum Upload</span><span class="value text-danger"><?= $belum_upload ?></span></div>
                                <div class="icon"><i class="fas fa-times-circle"></i></div>
                            </div>
                            <div class="module-stat-card user-stat-identitas">
                                <div class="info"><span class="label">Konfirmasi Sesuai</span><span class="value text-success"><?= $konfirmasi_sesuai ?></span></div>
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                            <div class="module-stat-card user-stat-belum">
                                <div class="info"><span class="label">Tidak Sesuai</span><span class="value text-danger"><?= $konfirmasi_tidak ?></span></div>
                                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow border-0">
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Upload Ijazah Per Murid</h4><small class="text-muted">Unggah file ijazah PDF per murid satu per satu.</small></div></div>
                </div>
                <div class="card-body">
                    <form id="form-upload-single-ijazah" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Pilih Murid</label>
                            <select class="form-control" id="select-murid-ijazah" name="user_id" required>
                                <option value="">-- pilih murid --</option>
                                <?php foreach ($students as $s) { ?>
                                    <option value="<?= (int)$s['user_id'] ?>">
                                        <?= htmlspecialchars($s['nisn'] . ' - ' . $s['nama_lengkap'] . ' (' . (!empty($s['nama_kelas']) ? $s['nama_kelas'] : '-') . ')') ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>File Ijazah (PDF)</label>
                            <div id="dropzone-single-ijazah" class="skl-dropzone" data-input="ijazah_file_input">
                                <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                                <div class="skl-dropzone-text">Drag &amp; drop file PDF di sini, atau <span class="text-primary" style="cursor:pointer;">pilih file</span></div>
                                <div class="skl-dropzone-hint text-muted small mt-1">Format: PDF</div>
                                <div class="skl-dropzone-filename text-success small mt-1 font-weight-bold"></div>
                            </div>
                            <input type="file" id="ijazah_file_input" name="file_ijazah" accept="application/pdf" required style="display:none;">
                        </div>
                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-upload mr-1"></i> Upload Ijazah</button>
                        <?php } else { ?>
                            <button type="button" class="btn btn-secondary btn-block" disabled>Tidak ada hak modifikasi</button>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow border-0">
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Import Masal via ZIP</h4><small class="text-muted">Impor ijazah massal dalam satu file ZIP berisi PDF bernama NISN.</small></div></div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Unggah satu file <strong>.zip</strong> berisi PDF ijazah dengan format nama file <strong>NISN.pdf</strong>. Contoh: <strong>0123456789.pdf</strong>.</p>
                    <p class="text-muted small">Sistem otomatis mengekstrak ZIP, lalu menyimpan dan memetakan ke murid berdasarkan NISN sesuai nama file.</p>
                    <form id="form-upload-batch-ijazah" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>File ZIP</label>
                            <div id="dropzone-bulk-ijazah" class="skl-dropzone" data-input="zip_ijazah_input">
                                <i class="fas fa-file-archive fa-2x text-warning mb-2"></i>
                                <div class="skl-dropzone-text">Drag &amp; drop file ZIP di sini, atau <span class="text-primary" style="cursor:pointer;">pilih file</span></div>
                                <div class="skl-dropzone-hint text-muted small mt-1">Format: ZIP</div>
                                <div class="skl-dropzone-filename text-success small mt-1 font-weight-bold"></div>
                            </div>
                            <input type="file" id="zip_ijazah_input" name="zip_file" accept=".zip,application/zip,application/x-zip-compressed" required style="display:none;">
                        </div>
                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                            <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-file-archive mr-1"></i> Import ZIP</button>
                        <?php } else { ?>
                            <button type="button" class="btn btn-secondary btn-block" disabled>Tidak ada hak modifikasi</button>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Monitoring -->
    <div class="row">
        <div class="col">
            <div class="card shadow border-0 module-table-card">
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;">
                        <div>
                            <h4 class="mb-1">Monitoring Ketersediaan Ijazah</h4>
                            <small class="text-muted">Pantau status ketersediaan dan konfirmasi ijazah murid.</small>
                        </div>
                        <div class="module-header-actions">
                            <button type="button" class="btn-mod btn-mod-teal btn-open-filter-ijazah" title="Filter"><i class="fas fa-filter"></i></button>
                        </div>
                    </div>
                </div>
                <table class="table table-striped align-items-center w-100" id="table-ijazah-monitor">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Status Kelulusan</th>
                            <th>Ijazah</th>
                            <th>Konfirmasi Murid</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($students as $row):
                            $uid = (int)$row['user_id'];
                            $ij = $ijazah_map[$uid] ?? null;
                            $file_exists = $ij && !empty($ij['file_name']);
                            $konfirmasi = $ij ? $ij['konfirmasi'] : null;
                            $catatan = $ij ? ($ij['catatan_kesalahan'] ?? '') : '';
                            $ijazah_status = $file_exists ? 'ada' : 'belum';
                            $kn = htmlspecialchars(!empty($row['nama_kelas']) ? $row['nama_kelas'] : '-');
                            if (!$file_exists) {
                                $konfirmasi_status_attr = 'belum';
                            } elseif ($konfirmasi === 'sesuai') {
                                $konfirmasi_status_attr = 'sesuai';
                            } elseif ($konfirmasi === 'tidak_sesuai') {
                                $konfirmasi_status_attr = 'tidak_sesuai';
                            } else {
                                $konfirmasi_status_attr = 'menunggu';
                            }
                        ?>
                        <tr data-ijazah-status="<?= $ijazah_status ?>" data-konfirmasi-status="<?= $konfirmasi_status_attr ?>">
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nisn']) ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td><?= $kn ?></td>
                            <td><span class="badge badge-<?= kelulusan_status_badge_class($row['status_kelulusan']) ?>"><?= htmlspecialchars(kelulusan_status_label($row['status_kelulusan'])) ?></span></td>
                            <td>
                                <?php if ($file_exists) { ?>
                                    <button type="button" class="btn btn-sm btn-success btn-preview-ijazah"
                                        data-uid="<?= $uid ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_lengkap']) ?>"
                                        title="<?= htmlspecialchars($ij['file_name']) ?>">
                                        <i class="fas fa-file-pdf mr-1"></i> Lihat Ijazah
                                    </button>
                                <?php } else { ?>
                                    <span class="badge badge-secondary">Belum tersedia</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (!$file_exists) { ?>
                                    <span class="text-muted">-</span>
                                <?php } elseif ($konfirmasi === 'sesuai') { ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Sesuai</span>
                                <?php } elseif ($konfirmasi === 'tidak_sesuai') { ?>
                                    <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Tidak Sesuai</span>
                                <?php } else { ?>
                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Menunggu</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($konfirmasi === 'tidak_sesuai' && !empty($catatan)) { ?>
                                    <button class="btn btn-sm btn-outline-danger btn-lihat-catatan"
                                        data-nama="<?= htmlspecialchars($row['nama_lengkap']) ?>"
                                        data-catatan="<?= htmlspecialchars($catatan) ?>">
                                        <i class="fas fa-eye mr-1"></i>Lihat
                                    </button>
                                <?php } else { ?>
                                    <span class="text-muted">-</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($file_exists && $data_role['hapus'] === 'Y') { ?>
                                    <button class="btn btn-sm btn-danger btn-hapus-ijazah"
                                        data-id="<?= $uid ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_lengkap']) ?>"
                                        title="Hapus Ijazah">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php } else { ?>
                                    <span class="text-muted">-</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Preview PDF Ijazah -->
<div class="modal fade" id="modalPreviewIjazah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-pdf text-danger mr-2"></i>Preview E-Ijazah &mdash; <span id="preview-ijazah-nama"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" id="modalPreviewIjazahBody" style="min-height:500px;max-height:82vh;overflow:auto;"></div>
        </div>
    </div>
</div>

<!-- Modal Catatan Kesalahan -->
<div class="modal fade" id="modalCatatanKesalahan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:520px;">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Catatan Kesalahan Ijazah</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <h6 class="font-weight-bold" id="catatan-nama"></h6>
                <hr>
                <div id="catatan-isi" class="text-dark" style="white-space:pre-wrap;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload Progress -->
<div class="modal fade" id="modalUploadProgress" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fas fa-file-archive mr-2"></i>Upload ZIP Progress</h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h3 class="mb-0" id="upload-percent">0%</h3>
                    <small class="text-muted">Mengupload file ZIP...</small>
                </div>
                <div class="progress" style="height: 25px;">
                    <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div class="mt-3 text-center">
                    <small id="upload-status" class="text-muted">Memulai upload...</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Filter Ijazah -->
<div class="modal fade modal-filter-ijazah" id="modalFilterIjazah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Monitoring Ijazah</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body pb-2">
                <div class="form-group">
                    <label class="form-control-label">Kelas</label>
                    <select id="filter-kelas-ijazah" class="form-control form-control-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kn) { ?>
                            <option value="<?= htmlspecialchars($kn) ?>"><?= htmlspecialchars($kn) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-control-label">Status Ijazah</label>
                    <select id="filter-status-ijazah" class="form-control form-control-sm">
                        <option value="">Semua Status</option>
                        <option value="ada">Sudah Upload</option>
                        <option value="belum">Belum Upload</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-control-label">Status Konfirmasi</label>
                    <select id="filter-konfirmasi-ijazah" class="form-control form-control-sm">
                        <option value="">Semua Konfirmasi</option>
                        <option value="sesuai">Sesuai</option>
                        <option value="tidak_sesuai">Tidak Sesuai</option>
                        <option value="menunggu">Menunggu</option>
                        <option value="belum">Belum Ada Ijazah</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-ijazah">Reset</button>
                <button type="button" class="btn btn-primary btn-sm btn-apply-filter-ijazah">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<script>
var IJAZAH_BASE = "./mod/skl-ijazah/";
</script>
