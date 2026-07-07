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

// Kumpulkan daftar kelas unik untuk filter
$kelas_list = array();
foreach ($students as $s) {
    $kn = !empty($s['nama_kelas']) ? $s['nama_kelas'] : '-';
    if (!in_array($kn, $kelas_list)) {
        $kelas_list[] = $kn;
    }
}
sort($kelas_list);

// Stats
$total_skl = count($students);
$sudah_skl = 0;
foreach ($students as $s) { if (!empty($s['file_name'])) $sudah_skl++; }
$belum_skl = $total_skl - $sudah_skl;
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
                                <div class="info"><span class="label">Total Murid</span><span class="value"><?= $total_skl ?></span></div>
                                <div class="icon"><i class="fas fa-users"></i></div>
                            </div>
                            <div class="module-stat-card user-stat-berkas-valid">
                                <div class="info"><span class="label">Sudah Upload SKL</span><span class="value text-success"><?= $sudah_skl ?></span></div>
                                <div class="icon"><i class="fas fa-file-pdf"></i></div>
                            </div>
                            <div class="module-stat-card user-stat-belum-sesuai">
                                <div class="info"><span class="label">Belum Upload SKL</span><span class="value text-danger"><?= $belum_skl ?></span></div>
                                <div class="icon"><i class="fas fa-times-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow border-0">
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Upload SKL Per Murid</h4><small class="text-muted">Unggah file SKL PDF per murid satu per satu.</small></div></div>
                </div>
                <div class="card-body">
                    <form id="form-import-single" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Pilih Murid (status harus Lulus)</label>
                            <select class="form-control" id="select-murid-skl" name="user_id" required>
                                <option value="">-- pilih murid --</option>
                                <?php foreach ($students as $s) {
                                    if ($s['status_kelulusan'] !== 'LULUS') continue;
                                ?>
                                    <option value="<?php echo (int) $s['user_id']; ?>">
                                        <?php echo htmlspecialchars($s['nisn'] . ' - ' . $s['nama_lengkap'] . ' (' . (!empty($s['nama_kelas']) ? $s['nama_kelas'] : '-') . ')'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>File SKL (PDF)</label>
                                <div id="dropzone-single" class="skl-dropzone" data-input="skl_file_input">
                                    <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                                    <div class="skl-dropzone-text">Drag &amp; drop file PDF di sini, atau <span class="text-primary" style="cursor:pointer;">pilih file</span></div>
                                    <div class="skl-dropzone-hint text-muted small mt-1">Format: PDF</div>
                                    <div class="skl-dropzone-filename text-success small mt-1 font-weight-bold"></div>
                                </div>
                                <input type="file" id="skl_file_input" name="skl_file" accept="application/pdf" required style="display:none;">
                        </div>
                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-upload mr-1"></i> Upload SKL</button>
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
                    <div class="module-header-row" style="gap:10px;"><div><h4 class="mb-1">Import Masal via ZIP PDF</h4><small class="text-muted">Impor SKL massal dalam satu file ZIP berisi PDF bernama NISN.</small></div></div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Unggah file <strong>.zip</strong> berisi PDF SKL dengan format nama file <strong>NISN.pdf</strong>. Contoh: <strong>0123456789.pdf</strong>.</p>
                    <p class="text-muted small">Sistem otomatis menyimpan file ke <code>content/skl</code> dan memetakan ke murid dengan NISN sesuai nama file.</p>
                    <form id="form-import-bulk" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>File ZIP</label>
                                <div id="dropzone-bulk" class="skl-dropzone" data-input="zip_file_input">
                                    <i class="fas fa-file-archive fa-2x text-warning mb-2"></i>
                                    <div class="skl-dropzone-text">Drag &amp; drop file ZIP di sini, atau <span class="text-primary" style="cursor:pointer;">pilih file</span></div>
                                    <div class="skl-dropzone-hint text-muted small mt-1">Format: ZIP</div>
                                    <div class="skl-dropzone-filename text-success small mt-1 font-weight-bold"></div>
                                </div>
                                <input type="file" id="zip_file_input" name="zip_file" accept=".zip,application/zip,application/x-zip-compressed" required style="display:none;">
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

    <div class="row">
        <div class="col">
            <div class="card shadow border-0 module-table-card">
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;">
                        <div>
                            <h4 class="mb-1">Monitoring Ketersediaan SKL</h4>
                            <small class="text-muted">Pantau status ketersediaan SKL untuk setiap murid.</small>
                        </div>
                        <div class="module-header-actions">
                            <button type="button" class="btn-mod btn-mod-teal btn-open-filter-skl-monitor" title="Filter"><i class="fas fa-filter"></i></button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-items-center" id="table-skl-monitor">
                        <thead class="thead-light">
                            <tr>
                                <th>No</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>SKL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($students as $s) {
                                $kn = htmlspecialchars(!empty($s['nama_kelas']) ? $s['nama_kelas'] : '-');
                                $skl_status = !empty($s['file_name']) ? 'ada' : 'belum';
                            ?>
                                <tr data-skl-status="<?php echo $skl_status; ?>">
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($s['nisn']); ?></td>
                                    <td><?php echo htmlspecialchars($s['nama_lengkap']); ?></td>
                                    <td><?php echo $kn; ?></td>
                                    <td><span class="badge badge-<?php echo kelulusan_status_badge_class($s['status_kelulusan']); ?>"><?php echo htmlspecialchars(kelulusan_status_label($s['status_kelulusan'])); ?></span></td>
                                    <td>
                                        <?php if (!empty($s['file_name'])) { ?>
                                            <button type="button"
                                                class="btn btn-sm btn-success btn-lihat-skl"
                                                data-filename="<?php echo htmlspecialchars($s['file_name']); ?>"
                                                title="<?php echo htmlspecialchars($s['file_name']); ?>">
                                                <i class="fas fa-file-pdf mr-1"></i> Lihat SKL
                                            </button>
                                        <?php } else { ?>
                                            <span class="badge badge-secondary">Belum tersedia</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Filter SKL Monitor -->
<div class="modal fade modal-filter-skl-monitor" id="modalFilterSKLMonitor" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Monitoring SKL</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body pb-2">
                <div class="form-group">
                    <label class="form-control-label">Kelas</label>
                    <select id="filter-kelas-skl" class="form-control form-control-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kn) { ?>
                            <option value="<?php echo htmlspecialchars($kn); ?>"><?php echo htmlspecialchars($kn); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-control-label">Status SKL</label>
                    <select id="filter-status-skl" class="form-control form-control-sm">
                        <option value="">Semua Status</option>
                        <option value="ada">Sudah Upload</option>
                        <option value="belum">Belum Upload</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-filter-skl-monitor">Reset</button>
                <button type="button" class="btn btn-primary btn-sm btn-apply-filter-skl-monitor">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview SKL PDF -->
<div class="modal fade" id="modalPreviewSKL" tabindex="-1" role="dialog" aria-labelledby="modalPreviewSKLLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPreviewSKLLabel">Preview SKL</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" id="modalPreviewSKLBody" style="min-height:500px;max-height:82vh;overflow:auto;"></div>
        </div>
    </div>
</div>
