<?php
$is_standalone = !isset($connection) || empty($site_name);
if ($is_standalone) {
    require_once __DIR__ . '/../../library/config.php';
    require_once __DIR__ . '/../../library/function.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda | Smart Apps Education</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php } ?>

<div class="module-home-container module-agenda">
    <div class="module-home-content">
        <div class="sae-landing agenda-landing">
            <section class="sae-hero agenda-hero" aria-label="Hero monitoring agenda">
                <div class="sae-hero-bg" aria-hidden="true"></div>
                <div class="sae-hero-inner">
                    <div class="sae-hero-copy">
                        <span class="sae-hero-kicker"><i class="fas fa-circle" aria-hidden="true"></i> Monitor Agenda Kelas</span>
                        <h1 class="sae-hero-title">Agenda <span class="sae-hero-accent">Guru</span></h1>
                        <p class="sae-hero-subtitle">Pantau kehadiran guru di setiap jam pelajaran secara realtime, lengkap dengan rekap per guru, kelas, dan mata pelajaran.</p>
                        <div class="sae-tech-strip agenda-tech-strip">
                            <span class="sae-tech-badge"><i class="fas fa-broadcast-tower"></i> Realtime</span>
                            <span class="sae-tech-badge"><i class="fas fa-user-check"></i> Kehadiran Guru</span>
                            <span class="sae-tech-badge"><i class="fas fa-school"></i> Per Kelas</span>
                            <span class="sae-tech-badge"><i class="fas fa-book"></i> Per Mapel</span>
                        </div>
                    </div>

                    <div class="sae-hero-right agenda-hero-right">
                        <div class="agenda-side-panel card">
                            <div class="agenda-side-panel-head">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-calendar-day me-2"></i>Tanggal Monitoring</h6>
                                    <p class="mb-0">Pilih tanggal untuk melihat kondisi agenda kelas pada hari tersebut.</p>
                                </div>
                            </div>
                            <div class="agenda-side-panel-body">
                                <div class="agenda-filter-bar agenda-filter-bar--hero">
                                    <label class="agenda-filter-label" for="ag-tanggal">Tanggal realtime</label>
                                    <input type="date" id="ag-tanggal" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="legend-row agenda-legend-grid">
                                    <div class="legend-item"><div class="legend-dot legend-hadir"></div> Hadir</div>
                                    <div class="legend-item"><div class="legend-dot legend-tidak-hadir"></div> Tidak Hadir</div>
                                    <div class="legend-item"><div class="legend-dot legend-tugas"></div> Tidak Hadir + Tugas</div>
                                    <div class="legend-item"><div class="legend-dot legend-belum"></div> Belum Diisi</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="agenda-shell glass-card card">
                <div class="card-body agenda-shell-body">
                    <div class="agenda-tabs" role="tablist" aria-label="Kategori laporan agenda">
                        <button class="tab-btn active" data-tab="realtime" type="button">Realtime</button>
                        <button class="tab-btn" data-tab="guru" type="button">Per Guru</button>
                        <button class="tab-btn" data-tab="kelas" type="button">Per Kelas</button>
                        <button class="tab-btn" data-tab="mapel" type="button">Per Mapel</button>
                    </div>

                    <div class="agenda-summary-strip" id="ag-summary"></div>

                    <div class="tab-panel active" id="tab-realtime">
                        <div class="agenda-panel-head">
                            <div class="home-insight-head mb-0">
                                <h5><i class="fas fa-satellite-dish me-2"></i>Realtime Agenda Kelas</h5>
                                <p>Daftar seluruh kelas dengan status pengisian agenda di setiap jam pelajaran.</p>
                            </div>
                        </div>
                        <div class="kelas-grid" id="kelas-grid"></div>
                    </div>

                    <div class="tab-panel" id="tab-guru">
                        <div class="agenda-report-head">
                            <div class="home-insight-head mb-0">
                                <h5><i class="fas fa-chalkboard-teacher me-2"></i>Rekap Per Guru</h5>
                                <p>Ringkasan agenda guru berdasarkan rentang tanggal.</p>
                            </div>
                            <div class="agenda-filter-bar">
                                <input type="date" id="ag-dari-guru" value="<?php echo date('Y-m-01'); ?>">
                                <input type="date" id="ag-sampai-guru" value="<?php echo date('Y-m-d'); ?>">
                                <button class="btn btn-sm btn-primary" id="btn-filter-guru" type="button"><i class="fas fa-filter me-1"></i>Filter</button>
                            </div>
                        </div>
                        <div class="agenda-table-wrap"><table class="report-table" id="tbl-guru"></table></div>
                    </div>

                    <div class="tab-panel" id="tab-kelas">
                        <div class="agenda-report-head">
                            <div class="home-insight-head mb-0">
                                <h5><i class="fas fa-school me-2"></i>Rekap Per Kelas</h5>
                                <p>Ringkasan agenda per kelas pada rentang tanggal terpilih.</p>
                            </div>
                            <div class="agenda-filter-bar">
                                <input type="date" id="ag-dari-kelas" value="<?php echo date('Y-m-01'); ?>">
                                <input type="date" id="ag-sampai-kelas" value="<?php echo date('Y-m-d'); ?>">
                                <button class="btn btn-sm btn-primary" id="btn-filter-kelas" type="button"><i class="fas fa-filter me-1"></i>Filter</button>
                            </div>
                        </div>
                        <div class="agenda-table-wrap"><table class="report-table" id="tbl-kelas"></table></div>
                    </div>

                    <div class="tab-panel" id="tab-mapel">
                        <div class="agenda-report-head">
                            <div class="home-insight-head mb-0">
                                <h5><i class="fas fa-book-open me-2"></i>Rekap Per Mapel</h5>
                                <p>Ringkasan agenda setiap mata pelajaran pada rentang tanggal terpilih.</p>
                            </div>
                            <div class="agenda-filter-bar">
                                <input type="date" id="ag-dari-mapel" value="<?php echo date('Y-m-01'); ?>">
                                <input type="date" id="ag-sampai-mapel" value="<?php echo date('Y-m-d'); ?>">
                                <button class="btn btn-sm btn-primary" id="btn-filter-mapel" type="button"><i class="fas fa-filter me-1"></i>Filter</button>
                            </div>
                        </div>
                        <div class="agenda-table-wrap"><table class="report-table" id="tbl-mapel"></table></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php if ($is_standalone) { ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="scripts.js"></script>
</body></html>
<?php } ?>
