<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
        // Cek status konfirmasi user, hanya izinkan jika 'Belum Sesuai'
        if (!isset($data_user['konfirmasi']) || $data_user['konfirmasi'] !== 'Belum Sesuai') {
            echo '<div class="container mt-5"><div class="alert alert-danger text-center"><h4><i class="fas fa-ban"></i> Akses Ditolak</h4><p>Anda tidak diizinkan mengakses halaman ini.</p><a href="../dashboard/home" class="btn btn-primary"><i class="fas fa-home"></i> Kembali ke Dashboard</a></div></div>';
            exit();
        }
        $user_id = $data_user['user_id'] ?? '';

        // Ambil data usulan perubahan dari tabel perubahan menggunakan prepared statement
        $usulan = [];
        if (!empty($user_id)) {
            $stmt = $connection->prepare("SELECT * FROM perubahan WHERE user_id = ? ORDER BY id DESC");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $usulan[] = $row;
            }
            $stmt->close();
        }

        // Ambil data user dari tabel user untuk mengisi form
        $user_data = [];
        if (!empty($user_id)) {
            $stmt = $connection->prepare("SELECT * FROM user WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $user_data = $row;
            }
            $stmt->close();
        }

        // Tentukan status terakhir dan progress
        $last_status = 'Tidak ada progres Usulan perubahan Data';
        if (!empty($usulan)) {
            $last_status = $usulan[0]['status_pengajuan'];
        }
        $progress = 0;
        $progress_class = 'bg-secondary';
        $progress_icon = 'fas fa-hourglass-start';

        // Cek status validasi berkas terakhir
        $validasi_berkas = '';
        if (!empty($user_id)) {
            $stmt = $connection->prepare("SELECT validasi_berkas FROM berkas WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $stmt->bind_result($validasi_berkas);
            $stmt->fetch();
            $stmt->close();
        }

        if ($last_status == 'Berhasil Dikirim') {
            $progress = 25;
            $progress_class = 'bg-success';
            $progress_icon = 'fas fa-paper-plane';
        } elseif ($last_status == 'Dalam Proses') {
            if ($validasi_berkas === 'valid') {
                $progress = 50;
                $progress_class = 'bg-primary';
                $progress_icon = 'fas fa-cog fa-spin';
            } else {
                $progress = 25;
                $progress_class = 'bg-success';
                $progress_icon = 'fas fa-paper-plane';
            }
        } elseif ($last_status == 'Ditolak') {
            $progress = 75;
            $progress_class = 'bg-danger';
            $progress_icon = 'fas fa-times-circle';
        } elseif ($last_status == 'Disetujui') {
            $progress = 100;
            $progress_class = 'bg-info';
            $progress_icon = 'fas fa-check-circle';
        }

        // Fungsi untuk mendapatkan badge status
        function getStatusBadge($status)
        {
            switch ($status) {
                case 'Berhasil Dikirim':
                    return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>' . $status . '</span>';
                case 'Dalam Proses':
                    return '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>' . $status . '</span>';
                case 'Disetujui':
                    return '<span class="badge bg-info"><i class="fas fa-thumbs-up me-1"></i>' . $status . '</span>';
                case 'Ditolak':
                    return '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>' . $status . '</span>';
                default:
                    return '<span class="badge bg-secondary">' . $status . '</span>';
            }
        }
?>
        <!-- Header -->
        <div class="header bg-primary pb-6">
            <div class="container-fluid">
                <div class="header-body">
                    <div class="row align-items-center py-4">
                        <div class="col-lg-6 col-12">
                            <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                                    <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Usulan Perbaikan Identitas</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page content -->
        <div class="container-fluid mt--6 edit-identitas-container">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="card shadow border-0">
                        <div class="card-header bg-white border-0 pb-0">
                            <?php
                            // Cek apakah admin telah menutup usulan melalui file status
                            $status_file = __DIR__ . '/../../../admin/mod/edit-identitas/status.json';
                            $usulan_closed = false;
                            if (file_exists($status_file)) {
                                $c = @file_get_contents($status_file);
                                $j = json_decode($c, true);
                                if (is_array($j) && !empty($j['closed'])) $usulan_closed = true;
                            }
                            ?>
                            <div class="text-center py-3">
                                <?php if ($usulan_closed): ?>
                                    <button class="btn btn-lg btn-danger" disabled><i class="fas fa-lock me-2"></i> Usulan Ditutup</button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalFormIdentitas">
                                        <i class="fas fa-edit me-2"></i> Tambah Usulan Perubahan Data
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body px-4 py-4">
                            <!-- Status dan Progress Bar -->
                            <div class="text-center mb-4">
                                <h5 class="mb-3 text-dark fw-bold">Status Pengajuan</h5>
                                <div class="mb-3">
                                    <i class="<?php echo $progress_icon; ?> me-2"></i>
                                    <span class="badge <?php echo str_replace('bg-', 'bg-', $progress_class); ?> fs-6 px-3 py-2">
                                        <?php echo htmlspecialchars($last_status); ?>
                                    </span>
                                </div>
                                <div class="progress mx-auto position-relative" style="height: 12px; max-width: 500px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated <?php echo $progress_class; ?>"
                                        role="progressbar"
                                        style="width: <?php echo $progress; ?>%;"
                                        aria-valuenow="<?php echo $progress; ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted fw-bold"><?php echo $progress; ?>% Complete</small>
                                </div>

                                <!-- Progress Steps -->
                                <div class="row mt-4 text-center">
                                    <div class="col-3">
                                        <div class="<?php echo $progress >= 25 ? 'text-success' : 'text-muted'; ?>">
                                            <i class="fas fa-paper-plane fa-lg"></i>
                                            <div><small>Dikirim</small></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="<?php echo $progress >= 50 ? 'text-primary' : 'text-muted'; ?>">
                                            <i class="fas fa-cog fa-lg"></i>
                                            <div><small>Proses (Validasi Berkas)</small></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="<?php echo $progress >= 75 ? 'text-danger' : 'text-muted'; ?>">
                                            <i class="fas fa-times-circle fa-lg"></i>
                                            <div><small>Ditolak</small></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="<?php echo $progress == 100 && $last_status == 'Disetujui' ? 'text-info' : 'text-muted'; ?>">
                                            <i class="fas fa-check-circle fa-lg"></i>
                                            <div><small>Disetujui</small></div>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // Tampilkan catatan (alasan_penolakan) dari usulan terakhir di bawah status
                                $last_note = '';
                                $last_note = (!empty($usulan) && !empty($usulan[0]['alasan_penolakan'])) ? trim($usulan[0]['alasan_penolakan']) : '';
                                $note_status = !empty($usulan) && isset($usulan[0]['status_pengajuan']) ? $usulan[0]['status_pengajuan'] : $last_status;

                                // Pilih gaya card berdasarkan status
                                $card_border = 'border-info';
                                $text_class = 'text-info';
                                $icon_class = 'fas fa-info-circle';
                                if ($note_status === 'Ditolak') {
                                    $card_border = 'border-danger';
                                    $text_class = 'text-danger';
                                    $icon_class = 'fas fa-times-circle';
                                } elseif ($note_status === 'Disetujui') {
                                    $card_border = 'border-success';
                                    $text_class = 'text-success';
                                    $icon_class = 'fas fa-check-circle';
                                } elseif ($note_status === 'Dalam Proses' || $note_status === 'Berhasil Dikirim') {
                                    $card_border = 'border-primary';
                                    $text_class = 'text-primary';
                                    $icon_class = 'fas fa-hourglass-half';
                                }
                                ?>

                                <?php
                                // Modern focused note card styling
                                $note_color = '#e9ecef';
                                if ($note_status === 'Ditolak') $note_color = '#f8d7da';
                                elseif ($note_status === 'Disetujui') $note_color = '#d1ecf1';
                                elseif ($note_status === 'Dalam Proses') $note_color = '#fff3cd';
                                elseif ($note_status === 'Berhasil Dikirim') $note_color = '#d4edda';
                                $accent = ($note_status === 'Ditolak') ? '#e55353' : (($note_status === 'Disetujui') ? '#17a2b8' : (($note_status === 'Dalam Proses') ? '#f0ad4e' : '#28a745'));
                                ?>
                                <style>
                                    .focus-note-card {
                                        border-radius: .6rem;
                                        box-shadow: 0 6px 18px rgba(22, 28, 36, 0.06);
                                        overflow: hidden;
                                    }

                                    .focus-note-accent {
                                        width: 8px;
                                        background: <?php echo $accent; ?>;
                                    }

                                    .focus-note-body {
                                        padding: 18px;
                                    }

                                    .focus-note-icon {
                                        font-size: 22px;
                                        width: 48px;
                                        height: 48px;
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        border-radius: 50%;
                                        color: #fff;
                                    }

                                    .focus-note-pulse {
                                        animation: pulse 2s infinite;
                                    }

                                    @keyframes pulse {
                                        0% {
                                            transform: scale(1);
                                        }

                                        50% {
                                            transform: scale(1.06);
                                        }

                                        100% {
                                            transform: scale(1);
                                        }
                                    }
                                </style>

                                <div class="mt-3 d-flex justify-content-center">
                                    <div class="d-flex w-100 focus-note-card" style="max-width:920px;">
                                        <div class="focus-note-accent"></div>
                                        <div class="d-flex flex-grow-1 align-items-center focus-note-body" style="background: <?php echo $note_color; ?>;">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold" style="margin:0;">Catatan</h6>
                                                    </div>
                                                    <div></div>
                                                </div>
                                                <div class="mt-2">
                                                    <?php if (!empty($last_note)): ?>
                                                        <div class="text-dark" style="white-space:pre-line"><?php echo nl2br(htmlspecialchars($last_note)); ?></div>
                                                    <?php else: ?>
                                                        <?php if (in_array($last_status, ['Berhasil Dikirim', 'Dalam Proses'])): ?>
                                                            <div class="text-muted">Belum ada Catatan / Silahkan Tunggu</div>
                                                        <?php else: ?>
                                                            <div class="text-muted">-</div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Riwayat -->
                            <div class="mt-5">
                                <h5 class="mb-3 text-center text-dark">Riwayat Usulan Perbaikan Data</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-borderless align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="text-center fw-bold" style="width: 60px;">#</th>
                                                <th class="fw-bold text-center" style="width:120px">Aksi</th>
                                                <th class="fw-bold">Perubahan</th>
                                                <th class="fw-bold">Tanggal Usulan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($usulan) == 0): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">
                                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                        Belum ada usulan perubahan data
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php $no = 1; ?>
                                                <?php foreach ($usulan as $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $no++; ?></td>
                                                        <td class="text-center">
                                                            <?php if ($row['status_pengajuan'] === 'Disetujui'): ?>
                                                                <button type="button" class="btn btn-sm btn-primary btn-view-usulan" data-id="<?php echo $row['id']; ?>" data-keterangan="<?php echo htmlspecialchars($row['keterangan'], ENT_QUOTES); ?>" title="Lihat data yang disetujui"><i class="fas fa-eye"></i></button>
                                                            <?php elseif (in_array($row['status_pengajuan'], ['Berhasil Dikirim', 'Dalam Proses', 'Ditolak'])): ?>
                                                                <button type="button" class="btn btn-sm btn-warning btn-edit-usulan" data-id="<?php echo $row['id']; ?>" data-keterangan="<?php echo htmlspecialchars($row['keterangan'], ENT_QUOTES); ?>" title="Edit usulan"><i class="fas fa-edit"></i></button>
                                                                <button type="button" class="btn btn-sm btn-danger btn-delete-usulan ms-1" data-id="<?php echo $row['id']; ?>" title="Hapus usulan"><i class="fas fa-trash"></i></button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-sm btn-secondary" disabled title="Tidak tersedia"><i class="fas fa-eye"></i></button>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            // Prefer ringkasan (denormalized) if available, otherwise compute from keterangan
                                                            $summary = '-';
                                                            if (!empty($row['ringkasan'])) {
                                                                $summary = htmlspecialchars($row['ringkasan'], ENT_QUOTES);
                                                            } elseif (!empty($row['keterangan'])) {
                                                                $keterangan = json_decode($row['keterangan'], true);
                                                                if (is_array($keterangan)) {
                                                                    // Mapping nama field ke label yang lebih ramah
                                                                    $label_map = [
                                                                        'nama_lengkap' => 'Nama Lengkap',
                                                                        'no_kk' => 'Nomor KK',
                                                                        'nik' => 'NIK',
                                                                        'jenis_kelamin' => 'Jenis Kelamin',
                                                                        'tempat_lahir' => 'Tempat Lahir',
                                                                        'tanggal_lahir' => 'Tanggal Lahir',
                                                                        'agama' => 'Agama',
                                                                        'nik_ayah' => 'NIK Ayah',
                                                                        'nama_ayah' => 'Nama Ayah',
                                                                        'nik_ibu' => 'NIK Ibu',
                                                                        'nama_ibu' => 'Nama Ibu',
                                                                        'nama_wali' => 'Nama Wali',
                                                                        'telp_wali' => 'Telp Wali',
                                                                    ];
                                                                    $labels = [];
                                                                    // Normalizer for rendering comparisons (keep in sync with server normalize)
                                                                    $normalize = function ($field, $val) {
                                                                        $v = trim((string)$val);
                                                                        $numeric_fields = ['nik', 'no_kk', 'nik_ayah', 'nik_ibu', 'telp', 'telp_wali', 'kodepos'];
                                                                        if (in_array($field, $numeric_fields)) {
                                                                            return preg_replace('/\D+/', '', $v);
                                                                        }
                                                                        if (in_array($field, ['tanggal_lahir', 'diterima_tanggal'])) {
                                                                            $t = strtotime($v);
                                                                            if ($t !== false) return date('Y-m-d', $t);
                                                                            return $v;
                                                                        }
                                                                        $v = preg_replace('/\s+/', ' ', $v);
                                                                        return mb_strtolower($v);
                                                                    };

                                                                    foreach ($keterangan as $k => $v) {
                                                                        // Lewati metadata internal yang dimulai dengan underscore
                                                                        if (strpos($k, '_') === 0) continue;

                                                                        // Determine new value (support both string-format and {old,new})
                                                                        $new_val = '';
                                                                        $old_val = '';
                                                                        if (is_array($v)) {
                                                                            $old_val = isset($v['old']) ? $v['old'] : '';
                                                                            $new_val = isset($v['new']) ? $v['new'] : '';
                                                                        } else {
                                                                            // legacy: store as simple value -> treat as new
                                                                            $new_val = $v;
                                                                        }

                                                                        // Prefer stored old->new comparison when available
                                                                        $is_change = false;
                                                                        if ($old_val !== '' || $new_val !== '') {
                                                                            if ($old_val !== '' && $new_val !== '') {
                                                                                $n_old = $normalize($k, $old_val);
                                                                                $n_new = $normalize($k, $new_val);
                                                                                if ($n_old !== $n_new) $is_change = true;
                                                                            } else {
                                                                                // If we only have one side (legacy or partial), consider it a change
                                                                                $is_change = true;
                                                                            }
                                                                        }

                                                                        if ($is_change) {
                                                                            $label = $label_map[$k] ?? ucwords(str_replace('_', ' ', $k));
                                                                            $labels[] = $label;
                                                                        }
                                                                    }
                                                                    if (!empty($labels)) {
                                                                        // Tampilkan sampai 3 label lalu tambahkan ellipsis jika lebih
                                                                        $display = array_slice($labels, 0, 3);
                                                                        // Escape each label but allow HTML line breaks between them
                                                                        $safeDisplay = array_map(function ($s) {
                                                                            return htmlspecialchars($s, ENT_QUOTES);
                                                                        }, $display);
                                                                        $summary = implode('<br>', $safeDisplay);
                                                                        if (count($labels) > 3) {
                                                                            $summary .= '<br><small class="text-muted">... lainnya</small>';
                                                                        }
                                                                    } else {
                                                                        // If keterangan exists but no labels determined (edge cases), show fallback
                                                                        if (!empty($row['keterangan'])) {
                                                                            $summary = '<small class="text-muted">Detail perubahan tersedia</small>';
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            echo $summary;
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('d M Y', strtotime($row['date_submitted'])); ?><br>
                                                                <?php echo date('H:i', strtotime($row['date_submitted'])); ?>
                                                            </small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Form Perubahan Identitas (termasuk Orang Tua & Wali) -->
            <div class="modal fade" id="modalFormIdentitas" tabindex="-1" aria-labelledby="modalFormIdentitasLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="formIdentitas" method="post" action="" novalidate>
                            <input type="hidden" name="perubahan_id" id="perubahan_id" value="" />
                            <!-- Header -->
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalFormIdentitasLabel">
                                    <i class="fas fa-user-edit me-2"></i>Edit Identitas Peserta Didik
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <!-- Body -->
                            <div class="modal-body">
                                <!-- Section 1: Data Pribadi -->
                                <div class="form-section">
                                    <h6 class="section-title" style="color: var(--section-title-color, #212529);">
                                        <i class="fas fa-user me-2"></i> Data Pribadi
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="nama_lengkap" class="form-label required-field">Nama Lengkap</label>
                                                <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap"
                                                    placeholder="Masukkan nama lengkap"
                                                    value="<?php echo htmlspecialchars($user_data['nama_lengkap'] ?? ''); ?>" required>
                                                <div class="invalid-feedback">Nama lengkap wajib diisi</div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="no_kk" class="form-label required-field">Nomor KK</label>
                                                <input type="text" class="form-control" name="no_kk" id="no_kk"
                                                    placeholder="16 digit Nomor KK" maxlength="16"
                                                    value="<?php echo htmlspecialchars($user_data['no_kk'] ?? ''); ?>" required>
                                                <div class="invalid-feedback">Nomor KK wajib diisi dengan 16 digit angka</div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="nik" class="form-label required-field">Nomor NIK</label>
                                                <input type="text" class="form-control" name="nik" id="nik"
                                                    placeholder="16 digit Nomor NIK" maxlength="16"
                                                    value="<?php echo htmlspecialchars($user_data['nik'] ?? ''); ?>" required>
                                                <div class="invalid-feedback">Nomor NIK wajib diisi dengan 16 digit angka</div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="jenis_kelamin" class="form-label required-field">Jenis Kelamin</label>
                                                <select class="form-control" name="jenis_kelamin" id="jenis_kelamin" required>
                                                    <option value="" disabled <?php echo empty($user_data['jenis_kelamin']) ? 'selected' : ''; ?>>Pilih Jenis Kelamin</option>
                                                    <option value="Laki-laki" <?php echo (isset($user_data['jenis_kelamin']) && $user_data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                                    <option value="Perempuan" <?php echo (isset($user_data['jenis_kelamin']) && $user_data['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                                </select>
                                                <div class="invalid-feedback">Jenis kelamin wajib dipilih</div>
                                            </div>
                                        </div>

                                        <!-- Field Agama -->
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="agama" class="form-label required-field">Agama</label>
                                                <select class="form-control" name="agama" id="agama" required>
                                                    <option value="" disabled <?php echo empty($user_data['agama']) ? 'selected' : ''; ?>>Pilih Agama</option>
                                                    <option value="Islam" <?php echo (isset($user_data['agama']) && $user_data['agama'] == 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                                    <option value="Kristen" <?php echo (isset($user_data['agama']) && $user_data['agama'] == 'Kristen') ? 'selected' : ''; ?>>Kristen</option>
                                                    <option value="Katolik" <?php echo (isset($user_data['agama']) && $user_data['agama'] == 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                                                    <option value="Hindu" <?php echo (isset($user_data['agama']) && $user_data['agama'] == 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                                                    <option value="Buddha" <?php echo (isset($user_data['agama']) && $user_data['agama'] == 'Buddha') ? 'selected' : ''; ?>>Buddha</option>
                                                    <option value="Konghucu" <?php echo (isset($user_data['agama']) && $user_data['agama'] == 'Konghucu') ? 'selected' : ''; ?>>Konghucu</option>
                                                    <option value="Lainnya" <?php echo (isset($user_data['agama']) && $user_data['agama'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                                </select>
                                                <div class="invalid-feedback">Agama wajib dipilih</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 2: Tempat & Tanggal Lahir -->
                                <div class="form-section">
                                    <h6 class="section-title" style="color: var(--section-title-color, #212529);">
                                        <i class="fas fa-calendar-alt me-2"></i>Tempat & Tanggal Lahir
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="tempat_lahir" class="form-label required-field">Tempat Lahir</label>
                                                <input type="text" class="form-control" name="tempat_lahir" id="tempat_lahir"
                                                    placeholder="Masukkan tempat lahir"
                                                    value="<?php echo htmlspecialchars($user_data['tempat_lahir'] ?? ''); ?>" required>
                                                <div class="invalid-feedback">Tempat lahir wajib diisi</div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="tanggal_lahir" class="form-label required-field">Tanggal Lahir</label>
                                                <input type="date" class="form-control" name="tanggal_lahir" id="tanggal_lahir"
                                                    value="<?php echo !empty($user_data['tanggal_lahir']) ? date('Y-m-d', strtotime($user_data['tanggal_lahir'])) : ''; ?>" required>
                                                <div class="invalid-feedback">Tanggal lahir wajib diisi</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 3: Data Keluarga -->
                                <div class="form-section">
                                    <h6 class="section-title" style="color: var(--section-title-color, #212529);">
                                        <i class="fas fa-home me-2"></i> Data Keluarga
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="status_keluarga" class="form-label">Status dalam Keluarga</label>
                                                <select class="form-control" name="status_keluarga" id="status_keluarga" required>
                                                    <option value="" disabled <?php echo empty($user_data['status_keluarga']) ? 'selected' : ''; ?>>Pilih Status</option>
                                                    <option value="Anak Kandung" <?php echo (isset($user_data['status_keluarga']) && $user_data['status_keluarga'] == 'Anak Kandung') ? 'selected' : ''; ?>>Anak Kandung</option>
                                                    <option value="Anak Tiri" <?php echo (isset($user_data['status_keluarga']) && $user_data['status_keluarga'] == 'Anak Tiri') ? 'selected' : ''; ?>>Anak Tiri</option>
                                                    <option value="Anak Angkat" <?php echo (isset($user_data['status_keluarga']) && $user_data['status_keluarga'] == 'Anak Angkat') ? 'selected' : ''; ?>>Anak Angkat</option>
                                                    <option value="Lainnya" <?php echo (isset($user_data['status_keluarga']) && $user_data['status_keluarga'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="anak_ke" class="form-label">Anak ke-berapa (berdasarkan KK)</label>
                                                <input type="number" class="form-control" name="anak_ke" id="anak_ke"
                                                    placeholder="Urutan anak" min="1" max="20"
                                                    value="<?php echo htmlspecialchars($user_data['anak_ke'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 4: Alamat Lengkap -->
                                <div class="form-section">
                                    <h6 class="section-title" style="color: var(--section-title-color, #212529);">
                                        <i class="fas fa-map-marker-alt me-2"></i> Alamat Lengkap
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="mb-2">
                                                <label for="alamat" class="form-label required-field">Alamat (Jl/Kp)</label>
                                                <textarea class="form-control" name="alamat" id="alamat" rows="3"
                                                    placeholder="Masukkan alamat lengkap (jalan, kampung, nomor rumah)" required><?php echo htmlspecialchars($user_data['alamat'] ?? ''); ?></textarea>
                                                <div class="invalid-feedback">Alamat wajib diisi</div>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="mb-2">
                                                <label for="rt" class="form-label">RT</label>
                                                <input type="text" class="form-control" name="rt" id="rt"
                                                    placeholder="001" maxlength="3"
                                                    value="<?php echo htmlspecialchars($user_data['rt'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="mb-2">
                                                <label for="rw" class="form-label">RW</label>
                                                <input type="text" class="form-control" name="rw" id="rw"
                                                    placeholder="001" maxlength="3"
                                                    value="<?php echo htmlspecialchars($user_data['rw'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="desa" class="form-label">Desa/Kelurahan</label>
                                                <input type="text" class="form-control" name="desa" id="desa"
                                                    placeholder="Masukkan desa/kelurahan"
                                                    value="<?php echo htmlspecialchars($user_data['desa'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="kecamatan" class="form-label">Kecamatan</label>
                                                <input type="text" class="form-control" name="kecamatan" id="kecamatan"
                                                    placeholder="Masukkan kecamatan"
                                                    value="<?php echo htmlspecialchars($user_data['kecamatan'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="kodepos" class="form-label">Kode Pos</label>
                                                <input type="text" class="form-control" name="kodepos" id="kodepos"
                                                    placeholder="12345" maxlength="5"
                                                    value="<?php echo htmlspecialchars($user_data['kodepos'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 5: Kontak & Data Sekolah -->
                                <div class="form-section">
                                    <h6 class="section-title" style="color: var(--section-title-color, #212529);">
                                        <i class="fas fa-school me-2"></i> Kontak & Data Sekolah
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="telp" class="form-label">Telp/HP</label>
                                                <input type="tel" class="form-control" name="telp" id="telp"
                                                    placeholder="08123456789"
                                                    value="<?php echo htmlspecialchars($user_data['telp'] ?? ''); ?>" required>
                                                <small class="form-text text-muted">Format: 08xxxxxxxxxx</small>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" id="email"
                                                    placeholder="nama@smk.belajar.id"
                                                    value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                                                <div class="invalid-feedback">Format email tidak valid</div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="sekolah_asal" class="form-label">Asal Sekolah</label>
                                                <input type="text" class="form-control" name="sekolah_asal" id="sekolah_asal"
                                                    placeholder="Nama sekolah asal"
                                                    value="<?php echo htmlspecialchars($user_data['sekolah_asal'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="diterima_dikelas" class="form-label">Diterima di Kelas</label>
                                                <input type="text" class="form-control" name="diterima_dikelas" id="diterima_dikelas"
                                                    placeholder="Nama kelas diterima"
                                                    value="<?php echo htmlspecialchars($user_data['diterima_dikelas'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="diterima_tanggal" class="form-label">Diterima pada Tanggal</label>
                                                <input type="date" class="form-control" name="diterima_tanggal" id="diterima_tanggal"
                                                    value="<?php echo !empty($user_data['diterima_tanggal']) ? date('Y-m-d', strtotime($user_data['diterima_tanggal'])) : ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Section 6: Data Orang Tua (digabung) -->
                                <hr class="my-4">
                                <div class="form-section">
                                    <h6 class="section-title" style="color: var(--section-title-color, #212529);">
                                        <i class="fas fa-users me-2"></i> Data Orang Tua
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="card p-3 h-100">
                                                <h6 class="mb-3 fw-bold">Ayah</h6>
                                                <div class="mb-2">
                                                    <label for="nik_ayah" class="form-label">NIK Ayah</label>
                                                    <input type="text" class="form-control" name="nik_ayah" id="nik_ayah" placeholder="16 digit NIK Ayah" maxlength="16" value="<?php echo htmlspecialchars($user_data['nik_ayah'] ?? ''); ?>">
                                                </div>
                                                <div class="mb-2">
                                                    <label for="nama_ayah" class="form-label">Nama Ayah Kandung</label>
                                                    <input type="text" class="form-control" name="nama_ayah" id="nama_ayah" placeholder="Nama Ayah Kandung" value="<?php echo htmlspecialchars($user_data['nama_ayah'] ?? ''); ?>" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label for="pekerjaan_ayah" class="form-label">Pekerjaan Ayah</label>
                                                    <select class="form-control" name="pekerjaan_ayah" id="pekerjaan_ayah" required>
                                                        <option value="" disabled <?php echo empty($user_data['pekerjaan_ayah']) ? 'selected' : ''; ?>>Pilih Pekerjaan</option>
                                                        <option value="Tidak bekerja" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Tidak bekerja') ? 'selected' : ''; ?>>Tidak bekerja</option>
                                                        <option value="Nelayan" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Nelayan') ? 'selected' : ''; ?>>Nelayan</option>
                                                        <option value="Petani" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Petani') ? 'selected' : ''; ?>>Petani</option>
                                                        <option value="Peternak" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Peternak') ? 'selected' : ''; ?>>Peternak</option>
                                                        <option value="PNS/TNI/Polri" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'PNS/TNI/Polri') ? 'selected' : ''; ?>>PNS/TNI/Polri</option>
                                                        <option value="Karyawan Swasta" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Karyawan Swasta') ? 'selected' : ''; ?>>Karyawan Swasta</option>
                                                        <option value="Pedagang Kecil" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Pedagang Kecil') ? 'selected' : ''; ?>>Pedagang Kecil</option>
                                                        <option value="Pedagang Besar" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Pedagang Besar') ? 'selected' : ''; ?>>Pedagang Besar</option>
                                                        <option value="Wiraswasta" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Wiraswasta') ? 'selected' : ''; ?>>Wiraswasta</option>
                                                        <option value="Wirausaha" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Wirausaha') ? 'selected' : ''; ?>>Wirausaha</option>
                                                        <option value="Buruh" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Buruh') ? 'selected' : ''; ?>>Buruh</option>
                                                        <option value="Pensiunan" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Pensiunan') ? 'selected' : ''; ?>>Pensiunan</option>
                                                        <option value="Tenaga Kerja Indonesia" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Tenaga Kerja Indonesia') ? 'selected' : ''; ?>>Tenaga Kerja Indonesia</option>
                                                        <option value="Karyawan BUMN" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Karyawan BUMN') ? 'selected' : ''; ?>>Karyawan BUMN</option>
                                                        <option value="Tidak dapat diterapkan" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Tidak dapat diterapkan') ? 'selected' : ''; ?>>Tidak dapat diterapkan</option>
                                                        <option value="Sudah Meninggal" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Sudah Meninggal') ? 'selected' : ''; ?>>Sudah Meninggal</option>
                                                        <option value="Lainnya" <?php echo (isset($user_data['pekerjaan_ayah']) && $user_data['pekerjaan_ayah'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="card p-3 h-100">
                                                <h6 class="mb-3 fw-bold">Ibu</h6>
                                                <div class="mb-2">
                                                    <label for="nik_ibu" class="form-label">NIK Ibu</label>
                                                    <input type="text" class="form-control" name="nik_ibu" id="nik_ibu" placeholder="16 digit NIK Ibu" maxlength="16" value="<?php echo htmlspecialchars($user_data['nik_ibu'] ?? ''); ?>">
                                                </div>
                                                <div class="mb-2">
                                                    <label for="nama_ibu" class="form-label">Nama Ibu Kandung</label>
                                                    <input type="text" class="form-control" name="nama_ibu" id="nama_ibu" placeholder="Nama Ibu Kandung" value="<?php echo htmlspecialchars($user_data['nama_ibu'] ?? ''); ?>" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label for="pekerjaan_ibu" class="form-label">Pekerjaan Ibu</label>
                                                    <select class="form-control" name="pekerjaan_ibu" id="pekerjaan_ibu" required>
                                                        <option value="" disabled <?php echo empty($user_data['pekerjaan_ibu']) ? 'selected' : ''; ?>>Pilih Pekerjaan</option>
                                                        <option value="Tidak bekerja" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Tidak bekerja') ? 'selected' : ''; ?>>Tidak bekerja</option>
                                                        <option value="Nelayan" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Nelayan') ? 'selected' : ''; ?>>Nelayan</option>
                                                        <option value="Petani" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Petani') ? 'selected' : ''; ?>>Petani</option>
                                                        <option value="Peternak" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Peternak') ? 'selected' : ''; ?>>Peternak</option>
                                                        <option value="PNS/TNI/Polri" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'PNS/TNI/Polri') ? 'selected' : ''; ?>>PNS/TNI/Polri</option>
                                                        <option value="Karyawan Swasta" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Karyawan Swasta') ? 'selected' : ''; ?>>Karyawan Swasta</option>
                                                        <option value="Pedagang Kecil" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Pedagang Kecil') ? 'selected' : ''; ?>>Pedagang Kecil</option>
                                                        <option value="Pedagang Besar" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Pedagang Besar') ? 'selected' : ''; ?>>Pedagang Besar</option>
                                                        <option value="Wiraswasta" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Wiraswasta') ? 'selected' : ''; ?>>Wiraswasta</option>
                                                        <option value="Wirausaha" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Wirausaha') ? 'selected' : ''; ?>>Wirausaha</option>
                                                        <option value="Buruh" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Buruh') ? 'selected' : ''; ?>>Buruh</option>
                                                        <option value="Pensiunan" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Pensiunan') ? 'selected' : ''; ?>>Pensiunan</option>
                                                        <option value="Tenaga Kerja Indonesia" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Tenaga Kerja Indonesia') ? 'selected' : ''; ?>>Tenaga Kerja Indonesia</option>
                                                        <option value="Karyawan BUMN" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Karyawan BUMN') ? 'selected' : ''; ?>>Karyawan BUMN</option>
                                                        <option value="Tidak dapat diterapkan" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Tidak dapat diterapkan') ? 'selected' : ''; ?>>Tidak dapat diterapkan</option>
                                                        <option value="Sudah Meninggal" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Sudah Meninggal') ? 'selected' : ''; ?>>Sudah Meninggal</option>
                                                        <option value="Lainnya" <?php echo (isset($user_data['pekerjaan_ibu']) && $user_data['pekerjaan_ibu'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 7: Data Wali (digabung) -->
                                <hr class="my-4">
                                <div class="form-section">
                                    <h6 class="section-title" style="color: var(--section-title-color, #212529);">
                                        <i class="fas fa-user-friends me-2"></i> Data Wali
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="nama_wali" class="form-label">Nama Wali</label>
                                                <input type="text" class="form-control" name="nama_wali" id="nama_wali" placeholder="Nama Wali" value="<?php echo htmlspecialchars($user_data['nama_wali'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="telp_wali" class="form-label">Telp/HP Wali</label>
                                                <input type="text" class="form-control" name="telp_wali" id="telp_wali" placeholder="No HP Wali" value="<?php echo htmlspecialchars($user_data['telp_wali'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="alamat_wali" class="form-label">Alamat Wali</label>
                                                <input type="text" class="form-control" name="alamat_wali" id="alamat_wali" placeholder="Alamat Wali" value="<?php echo htmlspecialchars($user_data['alamat_wali'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="mb-2">
                                                <label for="pekerjaan_wali" class="form-label">Pekerjaan Wali</label>
                                                <select class="form-control" name="pekerjaan_wali" id="pekerjaan_wali">
                                                    <option value="" disabled <?php echo empty($user_data['pekerjaan_wali']) ? 'selected' : ''; ?>>Pilih Pekerjaan</option>
                                                    <option value="Tidak bekerja" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Tidak bekerja') ? 'selected' : ''; ?>>Tidak bekerja</option>
                                                    <option value="Nelayan" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Nelayan') ? 'selected' : ''; ?>>Nelayan</option>
                                                    <option value="Petani" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Petani') ? 'selected' : ''; ?>>Petani</option>
                                                    <option value="Peternak" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Peternak') ? 'selected' : ''; ?>>Peternak</option>
                                                    <option value="PNS/TNI/Polri" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'PNS/TNI/Polri') ? 'selected' : ''; ?>>PNS/TNI/Polri</option>
                                                    <option value="Karyawan Swasta" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Karyawan Swasta') ? 'selected' : ''; ?>>Karyawan Swasta</option>
                                                    <option value="Pedagang Kecil" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Pedagang Kecil') ? 'selected' : ''; ?>>Pedagang Kecil</option>
                                                    <option value="Pedagang Besar" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Pedagang Besar') ? 'selected' : ''; ?>>Pedagang Besar</option>
                                                    <option value="Wiraswasta" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Wiraswasta') ? 'selected' : ''; ?>>Wiraswasta</option>
                                                    <option value="Wirausaha" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Wirausaha') ? 'selected' : ''; ?>>Wirausaha</option>
                                                    <option value="Buruh" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Buruh') ? 'selected' : ''; ?>>Buruh</option>
                                                    <option value="Pensiunan" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Pensiunan') ? 'selected' : ''; ?>>Pensiunan</option>
                                                    <option value="Tenaga Kerja Indonesia" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Tenaga Kerja Indonesia') ? 'selected' : ''; ?>>Tenaga Kerja Indonesia</option>
                                                    <option value="Karyawan BUMN" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Karyawan BUMN') ? 'selected' : ''; ?>>Karyawan BUMN</option>
                                                    <option value="Tidak dapat diterapkan" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Tidak dapat diterapkan') ? 'selected' : ''; ?>>Tidak dapat diterapkan</option>
                                                    <option value="Sudah Meninggal" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Sudah Meninggal') ? 'selected' : ''; ?>>Sudah Meninggal</option>
                                                    <option value="Lainnya" <?php echo (isset($user_data['pekerjaan_wali']) && $user_data['pekerjaan_wali'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-primary btn-save">
                                    <i class="fas fa-save me-1"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Modal View Usulan (read-only for approved data) -->
            <div class="modal fade" id="modalViewUsulan" tabindex="-1" aria-labelledby="modalViewUsulanLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalViewUsulanLabel"><i class="fas fa-eye me-2"></i> Detail Usulan (Disetujui)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="viewUsulanRingkasan" class="mb-2"></div>
                            <div id="viewUsulanDetails" style="white-space:pre-wrap; font-family:inherit;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php
    }
}
?>