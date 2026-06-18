<?php
// Gunakan mode standalone hanya jika file diakses langsung.
$is_standalone = !isset($connection) || empty($site_name);

// Include config dan functions jika belum ada
if (!isset($connection)) {
    include_once '../../library/config.php';
    include_once '../../library/function.php';
}
?>
<?php if ($is_standalone): ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Digital | <?php echo isset($site_name) ? $site_name : 'SMK Negeri 1 Pagelaran'; ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Gunakan style global; tamu.css dinonaktifkan sementara -->
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-800: #1f2937;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .guest-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }

        .logo-container {
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            background: white;
            padding: 10px;
        }

        .progress-container {
            background: white;
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 2;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .step-circle.active {
            background: var(--primary-color);
            transform: scale(1.1);
        }

        .step-circle.completed {
            background: var(--success-color);
        }

        .step-circle.pending {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        .step-label {
            font-size: 12px;
            text-align: center;
            color: var(--gray-800);
            font-weight: 500;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            height: 2px;
            background: var(--gray-200);
            z-index: 1;
        }

        .progress-line-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.5s ease;
            width: 0%;
        }

        .card-body {
            padding: 30px 20px;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .scanner-container {
            text-align: center;
            padding: 20px 0;
        }

        .scanner-frame {
            width: 250px;
            height: 250px;
            border: 3px dashed var(--primary-color);
            border-radius: 15px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: var(--gray-100);
        }

        .scanner-icon {
            font-size: 80px;
            color: var(--primary-color);
            opacity: 0.7;
        }

        .scan-instruction {
            color: var(--gray-800);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .camera-container {
            text-align: center;
            padding: 20px 0;
        }

        .camera-preview {
            width: 280px;
            height: 350px;
            border: 3px solid var(--primary-color);
            border-radius: 15px;
            margin: 0 auto 20px;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .camera-placeholder {
            font-size: 60px;
            color: var(--primary-color);
            opacity: 0.7;
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray-200);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: var(--gray-800);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--gray-800);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner-border {
            width: 40px;
            height: 40px;
            color: var(--primary-color);
        }

        @media (max-width: 576px) {
            .guest-card {
                margin: 10px;
                border-radius: 15px;
            }

            .card-header {
                padding: 20px 15px;
            }

            .card-body {
                padding: 20px 15px;
            }

            .scanner-frame,
            .camera-preview {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>

<body>
<?php endif; ?>
    <div class="main-container">
        <div class="guest-card">
            <!-- Header -->
            <div class="card-header">
                <div class="logo-container">
                    <img src="<?php echo isset($site_logo) ? './content/' . $site_logo : './content/logoweb1.png'; ?>"
                        alt="Logo" id="logo">
                </div>
                <h3 class="mb-2">Buku Tamu Digital</h3>
                <p class="mb-0 opacity-75"><?php echo isset($site_name) ? $site_name : 'SMK Negeri 1 Pagelaran'; ?></p>
            </div>

            <!-- Progress Steps -->
            <div class="progress-container">
                <div class="progress-steps">
                    <div class="progress-line">
                        <div class="progress-line-fill" id="progressFill"></div>
                    </div>

                    <div class="progress-step">
                        <div class="step-circle active" id="step1Circle">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="step-label">Scan QR</div>
                    </div>

                    <div class="progress-step">
                        <div class="step-circle pending" id="step2Circle">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="step-label">Isi Data</div>
                    </div>

                    <div class="progress-step">
                        <div class="step-circle pending" id="step3Circle">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="step-label">Foto</div>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <!-- Step 1: QR Scanner -->
                <div class="step-content active" id="step1">
                    <div class="scanner-container">
                        <div class="scanner-frame" id="qrReaderContainer">
                            <i class="fas fa-qrcode scanner-icon"></i>
                        </div>
                        <p class="scan-instruction">
                            <strong>Pindai QR Code Anda</strong><br>
                            Arahkan kamera ke QR code untuk memulai
                        </p>
                        <button type="button" class="btn btn-primary" id="startScanBtn">
                            <i class="fas fa-camera me-2"></i>Mulai Scan
                        </button>
                        <button type="button" class="btn btn-secondary mt-2" id="skipScanBtn">
                            <i class="fas fa-forward me-2"></i>Lewati Scan
                        </button>
                    </div>
                </div>

                <!-- Step 2: Form Input -->
                <div class="step-content" id="step2">
                    <form id="guestForm">
                        <div class="form-group">
                            <label class="form-label" for="nama">Nama Lengkap *</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="instansi">Asal Instansi *</label>
                            <input type="text" class="form-control" id="instansi" name="instansi" list="instansiList" autocomplete="off" placeholder="Ketik atau pilih instansi" required>
                            <datalist id="instansiList">
                                <?php
                                if (isset($connection)) {
                                    $__ri = $connection->query("SELECT nama FROM tamu_instansi WHERE active='Y' ORDER BY nama ASC LIMIT 500");
                                    if ($__ri) {
                                        while ($__i = $__ri->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($__i['nama'], ENT_QUOTES) . '">';
                                        }
                                    }
                                }
                                ?>
                            </datalist>
                            <small class="text-muted">Jika instansi sudah pernah terdaftar, pilih dari daftar.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="telepon">No. Telepon</label>
                            <input type="tel" class="form-control" id="telepon" name="telepon">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="keperluan">Keperluan Kunjungan *</label>
                            <select class="form-control" id="keperluan" name="keperluan" required>
                                <option value="">Pilih keperluan...</option>
                                <?php
                                $__tujuan_rendered = false;
                                if (isset($connection)) {
                                    $__rt = $connection->query("SELECT nama FROM tamu_tujuan WHERE active='Y' ORDER BY nama ASC");
                                    if ($__rt && $__rt->num_rows > 0) {
                                        while ($__t = $__rt->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($__t['nama'], ENT_QUOTES) . '">' . htmlspecialchars($__t['nama']) . '</option>';
                                        }
                                        $__tujuan_rendered = true;
                                    }
                                }
                                if (!$__tujuan_rendered) {
                                    // Fallback bila tabel referensi belum berisi data
                                    foreach (['Rapat/Meeting', 'Konsultasi', 'Kunjungan Kerja', 'Penelitian', 'Magang/PKL', 'Wawancara', 'Lainnya'] as $__o) {
                                        echo '<option value="' . htmlspecialchars($__o, ENT_QUOTES) . '">' . htmlspecialchars($__o) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="keterangan">Keterangan Tambahan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Jelaskan detail keperluan Anda..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary flex-fill" id="backToScanBtn">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </button>
                            <button type="button" class="btn btn-primary flex-fill" id="nextToPhotoBtn">
                                Lanjut <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Photo Capture -->
                <div class="step-content" id="step3">
                    <div class="camera-container">
                        <div class="camera-preview" id="cameraContainer">
                            <i class="fas fa-camera camera-placeholder"></i>
                            <video id="video" style="display: none;" autoplay playsinline></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                        </div>

                        <div id="photoActions">
                            <button type="button" class="btn btn-primary mb-2" id="startCameraBtn">
                                <i class="fas fa-video me-2"></i>Aktifkan Kamera
                            </button>
                            <button type="button" class="btn btn-success mb-2" id="captureBtn" style="display: none;">
                                <i class="fas fa-camera me-2"></i>Ambil Foto
                            </button>
                            <button type="button" class="btn btn-secondary mb-2" id="retakeBtn" style="display: none;">
                                <i class="fas fa-redo me-2"></i>Foto Ulang
                            </button>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-secondary flex-fill" id="backToFormBtn">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </button>
                            <button type="button" class="btn btn-success flex-fill" id="submitBtn" disabled>
                                <i class="fas fa-check me-2"></i>Selesai
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 mb-0">Menyimpan data...</p>
                </div>

                <!-- Alert Messages -->
                <div id="alertContainer"></div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<?php if ($is_standalone): ?>
    <script src="scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php endif; ?>