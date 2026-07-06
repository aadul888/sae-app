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
$settings = kelulusan_get_settings($connection);
$students = kelulusan_get_final_grade_students($connection);

$total = count($students);
$lulus = $bersyarat = $tidak = $belum = $skl_ada = $skl_belum = 0;
foreach ($students as $s) {
    if ($s['status_kelulusan'] === 'LULUS') $lulus++;
    elseif ($s['status_kelulusan'] === 'LULUS_BERSYARAT') $bersyarat++;
    elseif ($s['status_kelulusan'] === 'TIDAK_LULUS') $tidak++;
    else $belum++;
    if (!empty($s['file_name'])) $skl_ada++;
    else $skl_belum++;
}
?>


<!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-3"></div>
        </div>
    </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6 user-module-page">
    <div class="row">
        <div class="col">
            <div class="card shadow">
                <!-- Card header -->
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;">
                        <div>
                            <h4 class="mb-1"><i class="fas fa-cogs text-primary mr-2"></i>Panel Pengaturan Kelulusan</h4>
                            <small class="text-muted">Kelola kontrol akses, jadwal rilis, dan informasi pengumuman kelulusan.</small>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="card-body pb-0">
                    <ul class="nav nav-pills nav-fill flex-column flex-md-row tab-responsive" id="tabs-skl-settings" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0 active" id="tab-kontrol" data-toggle="tab" href="#panel-kontrol" role="tab">
                                <i class="fas fa-toggle-on mr-2"></i>Kontrol Kelulusan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0" id="tab-statistik" data-toggle="tab" href="#panel-statistik" role="tab">
                                <i class="fas fa-chart-bar mr-2"></i>Statistik Cepat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0" id="tab-pengumuman" data-toggle="tab" href="#panel-pengumuman" role="tab">
                                <i class="fas fa-bullhorn mr-2"></i>Pesan Pengumuman
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Tab content -->
                <div class="card-body">
                    <div class="tab-content" id="skl-settings-tab-content">

                        <!-- Tab 1: Kontrol Kelulusan -->
                        <div class="tab-pane fade show active" id="panel-kontrol" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info" role="alert">
                                        <span class="alert-inner--icon"><i class="fas fa-sliders-h"></i></span>
                                        <span class="alert-inner--text">
                                            <strong>Kontrol Amplop Kelulusan</strong><br>
                                            Atur kapan murid bisa membuka amplop, melihat, dan mengunduh SKL mereka.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <form id="form-kelulusan-toggle">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="toggle-setting-row">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <div class="font-weight-bold text-dark mb-1">Buka sistem kelulusan untuk murid</div>
                                                    <small class="text-muted">Aktifkan agar murid dapat membuka amplop di halaman kelulusan.</small>
                                                </div>
                                                <div class="custom-control custom-switch ml-3">
                                                    <input type="checkbox" class="custom-control-input" id="is_open" name="is_open" value="Y" <?php echo ($settings['is_open'] === 'Y') ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="is_open">&nbsp;</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="toggle-setting-row">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <div class="font-weight-bold text-dark mb-1">Tampilkan SKL ke user</div>
                                                    <small class="text-muted">Jika nonaktif, file SKL disembunyikan dari halaman hasil kelulusan.</small>
                                                </div>
                                                <div class="custom-control custom-switch ml-3">
                                                    <input type="checkbox" class="custom-control-input" id="show_skl_to_user" name="show_skl_to_user" value="Y" <?php echo (!isset($settings['show_skl_to_user']) || $settings['show_skl_to_user'] === 'Y') ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="show_skl_to_user">&nbsp;</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="toggle-setting-row">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <div class="font-weight-bold text-dark mb-1">Izinkan unduh SKL oleh user</div>
                                                    <small class="text-muted">Jika nonaktif, tombol unduh tidak muncul dan akses download ditolak.</small>
                                                </div>
                                                <div class="custom-control custom-switch ml-3">
                                                    <input type="checkbox" class="custom-control-input" id="allow_download_skl" name="allow_download_skl" value="Y" <?php echo (!isset($settings['allow_download_skl']) || $settings['allow_download_skl'] === 'Y') ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="allow_download_skl">&nbsp;</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-control-label font-weight-bold">Mulai Dibuka <small class="text-muted font-weight-normal">(opsional)</small></label>
                                                    <input type="datetime-local" class="form-control" name="open_at" value="<?php echo !empty($settings['open_at']) ? date('Y-m-d\TH:i', strtotime($settings['open_at'])) : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-control-label font-weight-bold">Tutup Otomatis <small class="text-muted font-weight-normal">(opsional)</small></label>
                                                    <input type="datetime-local" class="form-control" name="close_at" value="<?php echo !empty($settings['close_at']) ? date('Y-m-d\TH:i', strtotime($settings['close_at'])) : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-control-label font-weight-bold">Timer Mundur Landing Page</label>
                                                    <input type="datetime-local" class="form-control" name="countdown_to" value="<?php echo !empty($settings['countdown_to']) ? date('Y-m-d\TH:i', strtotime($settings['countdown_to'])) : ''; ?>">
                                                    <small class="text-muted">Tanggal hitung mundur jika sistem ditutup.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-1"></i> Simpan Pengaturan
                                            </button>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-secondary" disabled>Tidak ada hak modifikasi</button>
                                        <?php } ?>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tab 2: Statistik Cepat -->
                        <div class="tab-pane fade" id="panel-statistik" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert alert-info" role="alert">
                                        <span class="alert-inner--icon"><i class="fas fa-chart-bar"></i></span>
                                        <span class="alert-inner--text">
                                            <strong>Statistik Cepat</strong><br>
                                            Ringkasan data kelulusan dan ketersediaan SKL.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="skl-stat-card">
                                        <div class="text-muted small mb-1">Total Murid</div>
                                        <h2 class="mb-0"><?php echo $total; ?></h2>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="skl-stat-card">
                                        <div class="text-muted small mb-1">Lulus</div>
                                        <h2 class="text-success mb-0"><?php echo $lulus; ?></h2>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="skl-stat-card">
                                        <div class="text-muted small mb-1">Lulus Bersyarat</div>
                                        <h2 class="text-warning mb-0"><?php echo $bersyarat; ?></h2>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="skl-stat-card">
                                        <div class="text-muted small mb-1">Tidak Lulus</div>
                                        <h2 class="text-danger mb-0"><?php echo $tidak; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light border">
                                Belum diputuskan: <strong><?php echo $belum; ?></strong> siswa.
                            </div>
                            <hr class="my-3">
                            <h5 class="text-muted mb-3"><i class="fas fa-file-pdf mr-1"></i> Ketersediaan SKL</h5>
                            <div class="row mb-2">
                                <div class="col-md-6 mb-3">
                                    <div class="skl-stat-card">
                                        <div class="text-muted small mb-1">SKL Sudah Diupload</div>
                                        <h2 class="text-success mb-1"><?php echo $skl_ada; ?></h2>
                                        <span class="badge badge-success"><?php echo $total > 0 ? round($skl_ada / $total * 100) : 0; ?>%</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="skl-stat-card">
                                        <div class="text-muted small mb-1">SKL Belum Diupload</div>
                                        <h2 class="text-danger mb-1"><?php echo $skl_belum; ?></h2>
                                        <span class="badge badge-danger"><?php echo $total > 0 ? round($skl_belum / $total * 100) : 0; ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($total > 0) { ?>
                            <div class="progress" style="height:10px;" title="SKL tersedia: <?php echo $skl_ada; ?>/<?php echo $total; ?>">
                                <div class="progress-bar bg-success" role="progressbar" style="width:<?php echo round($skl_ada / $total * 100); ?>%" aria-valuenow="<?php echo round($skl_ada / $total * 100); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">Progress upload SKL: <?php echo $skl_ada; ?> dari <?php echo $total; ?> murid</small>
                            <?php } ?>
                        </div>

                        <!-- Tab 3: Pesan Pengumuman -->
                        <div class="tab-pane fade" id="panel-pengumuman" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info" role="alert">
                                        <span class="alert-inner--icon"><i class="fas fa-bullhorn"></i></span>
                                        <span class="alert-inner--text">
                                            <strong>Pesan Pengumuman</strong><br>
                                            Pesan ini akan tampil pada halaman modul kelulusan publik.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <form id="form-skl-settings">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="form-group">
                                            <label class="form-control-label font-weight-bold">Pesan Pengumuman</label>
                                            <textarea class="form-control" name="announcement_text" rows="6" placeholder="Contoh: Selamat datang pada pengumuman kelulusan tahun ajaran 2025/2026."><?php echo htmlspecialchars(!empty($settings['announcement_text']) ? $settings['announcement_text'] : ''); ?></textarea>
                                            <small class="text-muted">Teks ini akan tampil sebagai heading pengumuman di halaman kelulusan.</small>
                                        </div>
                                        <?php if ($data_role['modifikasi'] === 'Y') { ?>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-1"></i> Simpan Pesan
                                            </button>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-secondary" disabled>Tidak ada hak modifikasi</button>
                                        <?php } ?>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
