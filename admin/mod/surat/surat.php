<?php
/**
 * MODUL: SURAT — Administrasi Surat Masuk & Keluar
 * Dashboard dengan data dummy untuk tahap awal pengembangan.
 */
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    $modul_id = 52;
    include __DIR__ . '/../check_role.php';
    if ($has_access) {

        $can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
        $can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

        // ---- Statistik dummy ----
        $total_surat_masuk  = 48;
        $total_surat_keluar = 32;
        $surat_hari_ini     = 5;
        $surat_bulan_ini    = 18;
        $surat_tunda        = 3;
        $surat_arsip        = 124;

        // ---- Data dummy surat masuk ----
        $dummy_masuk = [
            ['id'=>1, 'no_surat'=>'SM/001/VI/2026', 'tgl_surat'=>'2026-06-20', 'tgl_terima'=>'2026-06-21', 'pengirim'=>'Dinas Pendidikan Provinsi', 'perihal'=>'Undangan Sosialisasi Kurikulum Merdeka', 'lampiran'=>'2', 'status'=>'Diterima'],
            ['id'=>2, 'no_surat'=>'SM/002/VI/2026', 'tgl_surat'=>'2026-06-18', 'tgl_terima'=>'2026-06-19', 'pengirim'=>'Balai Penjaminan Mutu Pendidikan', 'perihal'=>'Pemberitahuan Akreditasi Sekolah', 'lampiran'=>'1', 'status'=>'Diterima'],
            ['id'=>3, 'no_surat'=>'SM/003/VI/2026', 'tgl_surat'=>'2026-06-17', 'tgl_terima'=>'2026-06-18', 'pengirim'=>'Kementerian Pendidikan dan Kebudayaan', 'perihal'=>'Jadwal Pelaksanaan ANBK 2026', 'lampiran'=>'3', 'status'=>'Diproses'],
            ['id'=>4, 'no_surat'=>'SM/004/VI/2026', 'tgl_surat'=>'2026-06-15', 'tgl_terima'=>'2026-06-16', 'pengirim'=>'Cabang Dinas Pendidikan Wilayah', 'perihal'=>'Permohonan Data Pokok Pendidikan', 'lampiran'=>'-', 'status'=>'Diterima'],
            ['id'=>5, 'no_surat'=>'SM/005/VI/2026', 'tgl_surat'=>'2026-06-14', 'tgl_terima'=>'2026-06-14', 'pengirim'=>'Polres setempat', 'perihal'=>'Sosialisasi Kenakalan Remaja dan Narkoba', 'lampiran'=>'1', 'status'=>'Diterima'],
            ['id'=>6, 'no_surat'=>'SM/006/VI/2026', 'tgl_surat'=>'2026-06-13', 'tgl_terima'=>'2026-06-13', 'pengirim'=>'Komite Sekolah', 'perihal'=>'Usulan Kegiatan Peringatan HUT RI ke-81', 'lampiran'=>'-', 'status'=>'Diproses'],
            ['id'=>7, 'no_surat'=>'SM/007/VI/2026', 'tgl_surat'=>'2026-06-10', 'tgl_terima'=>'2026-06-11', 'pengirim'=>'Dinas Pendidikan Provinsi', 'perihal'=>'Pendaftaran Program Guru Penggerak Angkatan 15', 'lampiran'=>'4', 'status'=>'Diterima'],
            ['id'=>8, 'no_surat'=>'SM/008/VI/2026', 'tgl_surat'=>'2026-06-08', 'tgl_terima'=>'2026-06-09', 'pengirim'=>'Pusat Prestasi Nasional', 'perihal'=>'Undangan Olimpiade Sains Nasional 2026', 'lampiran'=>'2', 'status'=>'Diterima'],
        ];

        // ---- Data dummy surat keluar ----
        $dummy_keluar = [
            ['id'=>1, 'no_surat'=>'SK/001/VI/2026', 'tgl_surat'=>'2026-06-22', 'tujuan'=>'Dinas Pendidikan Provinsi', 'perihal'=>'Penyerahan Laporan Bulanan Sekolah', 'lampiran'=>'1', 'status'=>'Terkirim'],
            ['id'=>2, 'no_surat'=>'SK/002/VI/2026', 'tgl_surat'=>'2026-06-20', 'tujuan'=>'Orang Tua/Wali Murid', 'perihal'=>'Pemberitahuan Pembagian Raport Semester Genap', 'lampiran'=>'-', 'status'=>'Terkirim'],
            ['id'=>3, 'no_surat'=>'SK/003/VI/2026', 'tgl_surat'=>'2026-06-18', 'tujuan'=>'Cabang Dinas Pendidikan', 'perihal'=>'Permohonan Bantuan Operasional Sekolah', 'lampiran'=>'3', 'status'=>'Terkirim'],
            ['id'=>4, 'no_surat'=>'SK/004/VI/2026', 'tgl_surat'=>'2026-06-17', 'tujuan'=>'Bank XYZ Cabang', 'perihal'=>'Konfirmasi Rekening Sekolah', 'lampiran'=>'1', 'status'=>'Terkirim'],
            ['id'=>5, 'no_surat'=>'SK/005/VI/2026', 'tgl_surat'=>'2026-06-15', 'tujuan'=>'Puskesmas Kecamatan', 'perihal'=>'Permohonan Kegiatan Imunisasi Siswa', 'lampiran'=>'-', 'status'=>'Draf'],
            ['id'=>6, 'no_surat'=>'SK/006/VI/2026', 'tgl_surat'=>'2026-06-14', 'tujuan'=>'Dinas Pendidikan Provinsi', 'perihal'=>'Pengajuan Mutasi Guru PNS', 'lampiran'=>'2', 'status'=>'Terkirim'],
        ];

        // ---- Data dummy arsip ----
        $dummy_arsip = [
            ['id'=>1, 'no_surat'=>'SM/020/V/2026', 'jenis'=>'Masuk', 'tgl'=>'2026-05-10', 'asal_tujuan'=>'Dinas Pendidikan', 'perihal'=>'Undangan Workshop Implementasi Kurikulum', 'rak'=>'A1', 'status'=>'Diarsipkan'],
            ['id'=>2, 'no_surat'=>'SK/015/V/2026', 'jenis'=>'Keluar', 'tgl'=>'2026-05-08', 'asal_tujuan'=>'Kecamatan', 'perihal'=>'Permohonan Izin Kegiatan Outing Class', 'rak'=>'B2', 'status'=>'Diarsipkan'],
            ['id'=>3, 'no_surat'=>'SM/018/V/2026', 'jenis'=>'Masuk', 'tgl'=>'2026-05-05', 'asal_tujuan'=>'Kemenag', 'perihal'=>'Koordinasi Kegiatan Keagamaan Sekolah', 'rak'=>'A1', 'status'=>'Diarsipkan'],
            ['id'=>4, 'no_surat'=>'SK/012/V/2026', 'jenis'=>'Keluar', 'tgl'=>'2026-05-03', 'asal_tujuan'=>'BKKBN', 'perihal'=>'Undangan Sosialisasi Program Generasi Berencana', 'rak'=>'B1', 'status'=>'Diarsipkan'],
            ['id'=>5, 'no_surat'=>'SM/015/IV/2026', 'jenis'=>'Masuk', 'tgl'=>'2026-04-28', 'asal_tujuan'=>'Disdik Provinsi', 'perihal'=>'Petunjuk Teknis BOS Reguler 2026', 'rak'=>'A2', 'status'=>'Diarsipkan'],
        ];

        $now = date('d/m/Y');
        $bulan_ini = date('F Y');

        echo '
<div class="header bg-gradient-info pb-4 user-page-header-compact">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-3">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">Administrasi Surat</h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Surat</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-6 col-5 text-right">
                    <span class="text-white text-sm opacity-8"><i class="fas fa-calendar-alt mr-1"></i>' . $now . '</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--6 user-module-page">
    <!-- KPI STATS -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Surat Masuk</h5>
                            <span class="h2 font-weight-bold mb-0">' . $total_surat_masuk . '</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-muted text-sm">
                        <span class="text-success mr-2"><i class="fas fa-arrow-down"></i> ' . $surat_hari_ini . '</span>
                        <span class="text-nowrap">Hari ini</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Surat Keluar</h5>
                            <span class="h2 font-weight-bold mb-0">' . $total_surat_keluar . '</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-muted text-sm">
                        <span class="text-success mr-2"><i class="fas fa-chart-bar"></i> ' . $surat_bulan_ini . '</span>
                        <span class="text-nowrap">Bulan ' . date('F') . '</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Perlu Tindak Lanjut</h5>
                            <span class="h2 font-weight-bold mb-0">' . $surat_tunda . '</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-muted text-sm">
                        <span class="text-warning mr-2"><i class="fas fa-exclamation-triangle"></i></span>
                        <span class="text-nowrap">Menunggu diproses</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Arsip Surat</h5>
                            <span class="h2 font-weight-bold mb-0">' . $surat_arsip . '</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-gradient-secondary text-white rounded-circle shadow">
                                <i class="fas fa-archive"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-muted text-sm">
                        <span class="text-info mr-2"><i class="fas fa-folder"></i></span>
                        <span class="text-nowrap">Terdokumentasi</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;">
                        <div>
                            <h4 class="mb-1">Manajemen Surat</h4>
                            <small class="text-muted">Kelola surat masuk dan keluar, cetak disposisi, serta dokumentasi arsip surat.</small>
                        </div>
                        <div class="module-header-actions">
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalSuratBaru"><i class="fas fa-plus mr-1"></i>Surat Baru</button>
                        </div>
                    </div>
                </div>
                <div class="card-body pb-0">
                    <ul class="nav nav-pills mb-3" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab-masuk" role="tab"><i class="fas fa-envelope-open-text mr-1"></i> Surat Masuk</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-keluar" role="tab"><i class="fas fa-paper-plane mr-1"></i> Surat Keluar</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-arsip" role="tab"><i class="fas fa-archive mr-1"></i> Arsip</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-laporan" role="tab"><i class="fas fa-file-export mr-1"></i> Laporan</a></li>
                    </ul>
                </div>

                <div class="tab-content">
                    <!-- ===== SURAT MASUK ===== -->
                    <div class="tab-pane fade show active" id="tab-masuk" role="tabpanel">
                        <div class="px-4 pb-3">
                            <form id="filterMasuk" class="form-row align-items-end">
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Dari</label><input type="date" class="form-control form-control-sm"></div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Sampai</label><input type="date" class="form-control form-control-sm"></div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Status</label>
                                    <select class="form-control form-control-sm">
                                        <option value="">Semua</option><option>Diterima</option><option>Diproses</option><option>Selesai</option>
                                    </select>
                                </div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1 d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
                                    <button type="button" class="btn btn-sm btn-secondary">Reset</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush table-striped" id="tableMasuk" width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center" width="4">No</th>
                                        <th>No. Surat</th>
                                        <th>Tanggal</th>
                                        <th>Pengirim</th>
                                        <th>Perihal</th>
                                        <th>Lamp.</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>';
        $no = 1;
        foreach ($dummy_masuk as $s) {
            $badge = $s['status'] === 'Diterima' ? 'success' : ($s['status'] === 'Diproses' ? 'warning' : 'secondary');
            echo '<tr>
                <td class="text-center">' . $no++ . '</td>
                <td><code>' . $s['no_surat'] . '</code></td>
                <td><small>' . date('d/m/Y', strtotime($s['tgl_terima'])) . '</small></td>
                <td>' . htmlspecialchars($s['pengirim']) . '</td>
                <td><span class="font-weight-bold">' . htmlspecialchars($s['perihal']) . '</span></td>
                <td class="text-center">' . $s['lampiran'] . '</td>
                <td><span class="badge badge-' . $badge . '">' . $s['status'] . '</span></td>
                <td class="text-center">
                    <a href="javascript:void(0)" class="btn-detail-surat mr-1" data-id="' . $s['id'] . '" data-jenis="masuk"><i class="fas fa-eye text-primary"></i></a>
                    ' . ($can_edit ? '<a href="javascript:void(0)" class="mr-1"><i class="fas fa-edit text-warning"></i></a>' : '') . '
                    ' . ($can_del ? '<a href="javascript:void(0)"><i class="fas fa-trash text-danger"></i></a>' : '') . '
                </td>
            </tr>';
        }
        echo '          </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== SURAT KELUAR ===== -->
                    <div class="tab-pane fade" id="tab-keluar" role="tabpanel">
                        <div class="px-4 pb-3">
                            <form class="form-row align-items-end">
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Dari</label><input type="date" class="form-control form-control-sm"></div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Sampai</label><input type="date" class="form-control form-control-sm"></div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Status</label>
                                    <select class="form-control form-control-sm">
                                        <option value="">Semua</option><option>Terkirim</option><option>Draf</option>
                                    </select>
                                </div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1 d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
                                    <button type="button" class="btn btn-sm btn-secondary">Reset</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush table-striped" id="tableKeluar" width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center" width="4">No</th>
                                        <th>No. Surat</th>
                                        <th>Tanggal</th>
                                        <th>Tujuan</th>
                                        <th>Perihal</th>
                                        <th>Lamp.</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>';
        $no = 1;
        foreach ($dummy_keluar as $s) {
            $badge = $s['status'] === 'Terkirim' ? 'success' : 'secondary';
            echo '<tr>
                <td class="text-center">' . $no++ . '</td>
                <td><code>' . $s['no_surat'] . '</code></td>
                <td><small>' . date('d/m/Y', strtotime($s['tgl_surat'])) . '</small></td>
                <td>' . htmlspecialchars($s['tujuan']) . '</td>
                <td><span class="font-weight-bold">' . htmlspecialchars($s['perihal']) . '</span></td>
                <td class="text-center">' . $s['lampiran'] . '</td>
                <td><span class="badge badge-' . $badge . '">' . $s['status'] . '</span></td>
                <td class="text-center">
                    <a href="javascript:void(0)" class="btn-detail-surat mr-1" data-id="' . $s['id'] . '" data-jenis="keluar"><i class="fas fa-eye text-primary"></i></a>
                    ' . ($can_edit ? '<a href="javascript:void(0)" class="mr-1"><i class="fas fa-edit text-warning"></i></a>' : '') . '
                    ' . ($can_del ? '<a href="javascript:void(0)"><i class="fas fa-trash text-danger"></i></a>' : '') . '
                </td>
            </tr>';
        }
        echo '          </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== ARSIP ===== -->
                    <div class="tab-pane fade" id="tab-arsip" role="tabpanel">
                        <div class="px-4 pb-3">
                            <form class="form-row align-items-end">
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Jenis</label>
                                    <select class="form-control form-control-sm">
                                        <option value="">Semua</option><option>Masuk</option><option>Keluar</option>
                                    </select>
                                </div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Rak</label>
                                    <select class="form-control form-control-sm">
                                        <option value="">Semua</option><option>A1</option><option>A2</option><option>B1</option><option>B2</option>
                                    </select>
                                </div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1 d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush table-striped" id="tableArsip" width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center" width="4">No</th>
                                        <th>No. Surat</th>
                                        <th>Jenis</th>
                                        <th>Tanggal</th>
                                        <th>Asal/Tujuan</th>
                                        <th>Perihal</th>
                                        <th>Rak</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>';
        $no = 1;
        foreach ($dummy_arsip as $s) {
            $badge_jenis = $s['jenis'] === 'Masuk' ? 'primary' : 'success';
            echo '<tr>
                <td class="text-center">' . $no++ . '</td>
                <td><code>' . $s['no_surat'] . '</code></td>
                <td><span class="badge badge-' . $badge_jenis . '">' . $s['jenis'] . '</span></td>
                <td><small>' . date('d/m/Y', strtotime($s['tgl'])) . '</small></td>
                <td>' . htmlspecialchars($s['asal_tujuan']) . '</td>
                <td>' . htmlspecialchars($s['perihal']) . '</td>
                <td><code>' . $s['rak'] . '</code></td>
                <td><span class="badge badge-secondary">' . $s['status'] . '</span></td>
                <td class="text-center">
                    <a href="javascript:void(0)" class="btn-detail-surat" data-id="' . $s['id'] . '" data-jenis="arsip"><i class="fas fa-eye text-primary"></i></a>
                </td>
            </tr>';
        }
        echo '          </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== LAPORAN ===== -->
                    <div class="tab-pane fade" id="tab-laporan" role="tabpanel">
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="card border-left-primary"><div class="card-body py-3">
                                        <div class="small text-muted">Total Surat Masuk</div>
                                        <div class="h3 mb-0">' . $total_surat_masuk . '</div>
                                    </div></div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="card border-left-success"><div class="card-body py-3">
                                        <div class="small text-muted">Total Surat Keluar</div>
                                        <div class="h3 mb-0">' . $total_surat_keluar . '</div>
                                    </div></div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="card border-left-warning"><div class="card-body py-3">
                                        <div class="small text-muted">Surat ' . $bulan_ini . '</div>
                                        <div class="h3 mb-0">' . $surat_bulan_ini . '</div>
                                    </div></div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="card border-left-info"><div class="card-body py-3">
                                        <div class="small text-muted">Terdokumentasi</div>
                                        <div class="h3 mb-0">' . $surat_arsip . '</div>
                                    </div></div>
                                </div>
                            </div>
                            <p class="text-muted">Pilih rentang tanggal lalu unduh laporan surat.</p>
                            <form class="form-row align-items-end" target="_blank" method="get">
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Dari</label><input type="date" name="dari" class="form-control form-control-sm" required></div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Sampai</label><input type="date" name="sampai" class="form-control form-control-sm" required></div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1">Jenis</label>
                                    <select name="jenis" class="form-control form-control-sm">
                                        <option value="">Semua</option><option value="masuk">Surat Masuk</option><option value="keluar">Surat Keluar</option>
                                    </select>
                                </div>
                                <div class="form-group col-auto mb-2"><label class="small mb-1 d-block">&nbsp;</label>
                                    <button type="submit" name="format" value="excel" class="btn btn-sm btn-success"><i class="fas fa-file-excel mr-1"></i>Excel</button>
                                    <button type="submit" name="format" value="print" class="btn btn-sm btn-info"><i class="fas fa-print mr-1"></i>Cetak/PDF</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

        // ===== Modal Detail Surat =====
        echo '
<div class="modal fade" id="modalDetailSurat" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-file-alt mr-2"></i>Detail Surat</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="detailBodySurat">
                <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</div>
            </div>
        </div>
    </div>
</div>';

        // ===== Modal Surat Baru =====
        echo '
<div class="modal fade" id="modalSuratBaru" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Buat Surat Baru</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Jenis Surat <span class="text-danger">*</span></label>
                        <select class="form-control" required>
                            <option value="">Pilih...</option>
                            <option value="masuk">Surat Masuk</option>
                            <option value="keluar">Surat Keluar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>No. Surat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" placeholder="Contoh: SM/001/VI/2026" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Pengirim / Tujuan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" placeholder="Nama instansi/perusahaan" required>
                    </div>
                    <div class="form-group">
                        <label>Perihal <span class="text-danger">*</span></label>
                        <textarea class="form-control" rows="2" placeholder="Isi perihal surat..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Lampiran</label>
                        <input type="number" class="form-control" placeholder="Jumlah lampiran" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>';

        // ===== JS: Detail handler =====
        echo '
<script>
(function(){
    // Data dummy detail
    var detailData = {
        masuk: ' . json_encode($dummy_masuk) . ',
        keluar: ' . json_encode($dummy_keluar) . ',
        arsip: ' . json_encode($dummy_arsip) . '
    };

    document.addEventListener("click", function(e){
        var btn = e.target.closest(".btn-detail-surat");
        if (!btn) return;
        e.preventDefault();
        var id = parseInt(btn.getAttribute("data-id"));
        var jenis = btn.getAttribute("data-jenis");
        var data = detailData[jenis] || [];
        var item = data.find(function(d){ return d.id === id; });
        if (!item) return;

        var html = "";
        if (jenis === "masuk") {
            html = "<table class=\"table table-bordered table-sm\">" +
                "<tr><th width=\"30%\">No. Surat</th><td><code>" + item.no_surat + "</code></td></tr>" +
                "<tr><th>Tanggal Surat</th><td>" + item.tgl_surat + "</td></tr>" +
                "<tr><th>Tanggal Diterima</th><td>" + item.tgl_terima + "</td></tr>" +
                "<tr><th>Pengirim</th><td>" + htmlEscape(item.pengirim) + "</td></tr>" +
                "<tr><th>Perihal</th><td><strong>" + htmlEscape(item.perihal) + "</strong></td></tr>" +
                "<tr><th>Lampiran</th><td>" + item.lampiran + " lembar</td></tr>" +
                "<tr><th>Status</th><td><span class=\"badge badge-" + (item.status==="Diterima"?"success":"warning") + "\">" + item.status + "</span></td></tr>" +
                "</table>";
        } else if (jenis === "keluar") {
            html = "<table class=\"table table-bordered table-sm\">" +
                "<tr><th width=\"30%\">No. Surat</th><td><code>" + item.no_surat + "</code></td></tr>" +
                "<tr><th>Tanggal Surat</th><td>" + item.tgl_surat + "</td></tr>" +
                "<tr><th>Tujuan</th><td>" + htmlEscape(item.tujuan) + "</td></tr>" +
                "<tr><th>Perihal</th><td><strong>" + htmlEscape(item.perihal) + "</strong></td></tr>" +
                "<tr><th>Lampiran</th><td>" + item.lampiran + " lembar</td></tr>" +
                "<tr><th>Status</th><td><span class=\"badge badge-" + (item.status==="Terkirim"?"success":"secondary") + "\">" + item.status + "</span></td></tr>" +
                "</table>";
        } else {
            html = "<table class=\"table table-bordered table-sm\">" +
                "<tr><th width=\"30%\">No. Surat</th><td><code>" + item.no_surat + "</code></td></tr>" +
                "<tr><th>Jenis</th><td><span class=\"badge badge-" + (item.jenis==="Masuk"?"primary":"success") + "\">" + item.jenis + "</span></td></tr>" +
                "<tr><th>Tanggal</th><td>" + item.tgl + "</td></tr>" +
                "<tr><th>Asal/Tujuan</th><td>" + htmlEscape(item.asal_tujuan) + "</td></tr>" +
                "<tr><th>Perihal</th><td><strong>" + htmlEscape(item.perihal) + "</strong></td></tr>" +
                "<tr><th>Rak</th><td><code>" + item.rak + "</code></td></tr>" +
                "<tr><th>Status</th><td><span class=\"badge badge-secondary\">" + item.status + "</span></td></tr>" +
                "</table>";
        }
        document.getElementById("detailBodySurat").innerHTML = html;
        $("#modalDetailSurat").modal("show");
    });

    function htmlEscape(s) {
        var d = document.createElement("div");
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }
})();
</script>';

    } else {
        hak_akses();
    }
}
?>
