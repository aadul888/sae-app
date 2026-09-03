<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}

$modul_id = 38;
include __DIR__ . '/../check_role.php';

if (!$has_access) {
    theme_404();
    return;
}

require_once __DIR__ . '/../../../library/kelulusan_helper.php';
kelulusan_ensure_tables($connection);

$students = kelulusan_get_final_grade_students($connection);
?>

<div class="header bg-primary pb-6">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-8 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">Murid Kelas Akhir</h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                            <li class="breadcrumb-item">Kelulusan</li>
                            <li class="breadcrumb-item active" aria-current="page">Murid Kelas Akhir</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--6">
    <div class="row">
        <div class="col">
            <div class="card shadow border-0">
                <div class="card-header bg-transparent">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4 class="mb-2 mb-md-0">Daftar Murid Tingkat/Kelas Akhir</h4>
                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                            <button type="button" class="btn btn-sm btn-success" id="btn-lulus-semua">
                                <i class="fas fa-check-double mr-1"></i> Keputusan Masal: Lulus Semua
                            </button>
                        <?php } ?>
                    </div>
                </div>
                <div>
                    <table class="table align-items-center table-flush table-striped w-100" id="table-kelulusan-user">
                        <thead class="thead-light">
                            <tr>
                                <th>No</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status Kelulusan</th>
                                <th>Catatan</th>
                                <th class="text-center">SKL</th>
                                <th class="text-center">Status Berkas</th>
                                <th class="text-center">Tampil ke User</th>
                                <th class="text-center">Toggle Lulus / Tidak</th>
                                <th class="text-center">Toggle Lulus Bersyarat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($students as $row) {
                                $badge = kelulusan_status_badge_class($row['status_kelulusan']);
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nisn']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars(!empty($row['nama_kelas']) ? $row['nama_kelas'] : '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $badge; ?>">
                                            <?php echo htmlspecialchars(kelulusan_status_label($row['status_kelulusan'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(!empty($row['catatan']) ? $row['catatan'] : '-'); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['file_name'])) { ?>
                                            <span class="badge badge-success"><i class="fas fa-file-pdf mr-1"></i>Ada</span>
                                        <?php } else { ?>
                                            <span class="badge badge-secondary">Belum Ada</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $validasi = strtolower(trim((string)($row['validasi_berkas'] ?? '')));
                                        if ($validasi === 'valid') {
                                        ?>
                                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Valid</span>
                                        <?php } elseif ($validasi === 'tidak valid' || $validasi === 'ditolak') { ?>
                                            <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Ditolak</span>
                                        <?php } elseif ($validasi !== '') { ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars(ucfirst($validasi)); ?></span>
                                        <?php } else { ?>
                                            <span class="badge badge-light text-muted">Belum Upload</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['file_name'])) {
                                            $berkasValid = (strtolower(trim((string)($row['validasi_berkas'] ?? ''))) === 'valid');
                                        ?>
                                            <?php if ($berkasValid) { ?>
                                                <span class="badge badge-success"><i class="fas fa-eye mr-1"></i>Tampil</span>
                                                <div><small class="text-muted">Berkas valid</small></div>
                                            <?php } else { ?>
                                                <span class="badge badge-secondary"><i class="fas fa-eye-slash mr-1"></i>Tersembunyi</span>
                                                <div><small class="text-danger">Berkas belum valid</small></div>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="text-muted">-</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input status-toggle-main"
                                                    id="status-main-<?php echo (int) $row['user_id']; ?>"
                                                    data-id="<?php echo (int) $row['user_id']; ?>"
                                                    data-current="<?php echo htmlspecialchars($row['status_kelulusan']); ?>"
                                                    data-note="<?php echo htmlspecialchars(!empty($row['catatan']) ? $row['catatan'] : ''); ?>"
                                                    <?php echo ($row['status_kelulusan'] === 'LULUS') ? 'checked' : ''; ?>
                                                >
                                                <label class="custom-control-label" for="status-main-<?php echo (int) $row['user_id']; ?>">&nbsp;</label>
                                            </div>
                                            <div><small class="text-muted">ON: Lulus | OFF: Tidak Lulus</small></div>
                                        <?php } else { ?>
                                            <span class="text-muted">Terkunci</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input status-toggle-syarat"
                                                    id="status-syarat-<?php echo (int) $row['user_id']; ?>"
                                                    data-id="<?php echo (int) $row['user_id']; ?>"
                                                    data-current="<?php echo htmlspecialchars($row['status_kelulusan']); ?>"
                                                    data-note="<?php echo htmlspecialchars(!empty($row['catatan']) ? $row['catatan'] : ''); ?>"
                                                    <?php echo ($row['status_kelulusan'] === 'LULUS_BERSYARAT') ? 'checked' : ''; ?>
                                                >
                                                <label class="custom-control-label" for="status-syarat-<?php echo (int) $row['user_id']; ?>">&nbsp;</label>
                                            </div>
                                        <?php } else { ?>
                                            <span class="text-muted">Terkunci</span>
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

<div class="modal fade" id="modalStatusKelulusan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="form-status-kelulusan">
                <div class="modal-header">
                    <h5 class="modal-title">Alasan Keputusan Kelulusan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="status_user_id">
                    <input type="hidden" name="status" id="status_value">
                    <div class="alert alert-info mb-3">
                        Status keputusan: <strong id="status_value_label">-</strong>
                    </div>
                    <div class="form-group mb-0">
                        <label>Alasan / Catatan</label>
                        <textarea class="form-control" name="catatan" id="status_catatan" rows="4" placeholder="Opsional: tuliskan alasan keputusan ini."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.toggle-open-wrap .custom-control-input:checked ~ .custom-control-label::before {
    border-color: #2dce89;
    background-color: #2dce89;
}
</style>
