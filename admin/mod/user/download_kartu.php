<?php
// Server-side generator: download kartu pelajar sebagai PNG

$has_admin_auth = isset($_COOKIE['ADMIN_KEY']) || isset($_COOKIE['KEY']);
$has_siswa_auth = isset($_COOKIE['siswa']);

if (!$has_admin_auth && !$has_siswa_auth) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

require_once '../../../library/config.php';
include '../../../library/function.php';

$siswa_user_id = '';
if ($has_siswa_auth && !$has_admin_auth) {
    $siswa_user_id = trim((string)convert('decrypt', (string)$_COOKIE['siswa']));
    if ($siswa_user_id === '') {
        header('HTTP/1.1 401 Unauthorized');
        echo 'Unauthorized';
        exit;
    }
}

$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
// Backward compatibility: accept ?nisn=... from existing admin UI
if ($user_id === '') {
    $nisn_param = isset($_GET['nisn']) ? trim($_GET['nisn']) : '';
    if ($nisn_param !== '') {
        $stmt_n = $connection->prepare("SELECT user_id FROM user WHERE nisn = ? LIMIT 1");
        $stmt_n->bind_param('s', $nisn_param);
        $stmt_n->execute();
        $r_n = $stmt_n->get_result();
        if ($r_n && $r_n->num_rows > 0) {
            $row_n = $r_n->fetch_assoc();
            $user_id = $row_n['user_id'];
        } else {
            header('HTTP/1.1 404 Not Found');
            echo 'User not found';
            exit;
        }
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo 'user_id or nisn required';
        exit;
    }
}

// Siswa hanya boleh mengakses kartu milik dirinya sendiri.
if ($siswa_user_id !== '' && (string)$user_id !== (string)$siswa_user_id) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

// Fetch user and jurusan
$stmt = $connection->prepare("SELECT u.*, j.nama_jurusan, j.kode_jurusan FROM user u LEFT JOIN jurusan j ON u.jurusan_id = j.jurusan_id WHERE u.user_id = ? LIMIT 1");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    echo 'User not found';
    exit;
}
$user = $res->fetch_assoc();

$nisn = $user['nisn'] ?? '';
$nama = $user['nama_lengkap'] ?? '';
$jurusan_id = $user['jurusan_id'] ?? '';
$avatar_db = $user['avatar'] ?? '';


$base = '../../../'; // relative from admin/mod/user

// public base URL for assets: prefer $site_url from settings, fallback to $base_url helper
$public_base = '';
if (!empty($site_url)) {
    $public_base = rtrim($site_url, '/');
} elseif (!empty($base_url)) {
    $public_base = rtrim($base_url, '/');
} else {
    // fallback to document root host
    $http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
    $public_base = $http . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

// Filesystem root (absolute) and web root (URL) for content
$fs_root = realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR; // points to saev4/
$http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
// Use the current request host to avoid cross-origin asset loading when possible
if (!empty($_SERVER['HTTP_HOST'])) {
    $web_root = $http . '://' . $_SERVER['HTTP_HOST'];
} else {
    $web_root = $public_base; // fallback
}

// Compute site root URL (same-origin) based on DOCUMENT_ROOT and fs_root
$doc_root = '';
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $doc_root = realpath($_SERVER['DOCUMENT_ROOT']);
}
if ($doc_root) {
    $fs_norm = str_replace('\\', '/', rtrim($fs_root, DIRECTORY_SEPARATOR));
    $doc_norm = str_replace('\\', '/', rtrim($doc_root, DIRECTORY_SEPARATOR));
    $web_folder = trim(str_replace($doc_norm, '', $fs_norm), '/');
    $site_root_url = $http . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($web_folder ? '/' . $web_folder : '');
} else {
    $site_root_url = rtrim($public_base, '/');
}

// Default paths untuk preview - separate filesystem paths for file_exists checks
$paths_fs = [
    'foto' => $fs_root . 'content' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR . 'avatar.jpg',
    'logo_jurusan' => $fs_root . 'content' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'logo-jurusan' . DIRECTORY_SEPARATOR . 'default.png',
    'kartu_depan' => $fs_root . 'content' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'kartu-pelajar' . DIRECTORY_SEPARATOR . 'depan.jpg',
    'kartu_belakang' => $fs_root . 'content' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'kartu-pelajar' . DIRECTORY_SEPARATOR . 'belakang.jpg',
    'qrcode' => ''
];

// Corresponding public URLs to use in HTML src attributes
$paths_web = [
    'foto' => $web_root . '/content/avatar/avatar.jpg',
    'logo_jurusan' => $web_root . '/content/assets/logo-jurusan/default.png',
    'kartu_depan' => $web_root . '/content/assets/kartu-pelajar/depan.jpg',
    'kartu_belakang' => $web_root . '/content/assets/kartu-pelajar/belakang.jpg',
    'qrcode' => ''
];

// Cek alternatif file kartu depan jika tidak ada (filesystem)
if (!file_exists($paths_fs['kartu_depan'])) {
    $alt_candidates = [
        $fs_root . 'content/assets/kartu-pelajar/depan.png',
        $fs_root . 'content/assets/kartu-depan.jpg',
        $fs_root . 'content/assets/kartu-depan.png',
        $fs_root . 'assets/kartu-pelajar/depan.jpg',
        $fs_root . 'assets/kartu-pelajar/depan.png'
    ];
    foreach ($alt_candidates as $alt) {
        if (file_exists($alt)) {
            $paths_fs['kartu_depan'] = $alt;
            $rel = str_replace($fs_root, '', $alt);
            $paths_web['kartu_depan'] = $web_root . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            break;
        }
    }
} else {
    $rel = str_replace($fs_root, '', $paths_fs['kartu_depan']);
    $paths_web['kartu_depan'] = $web_root . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
}

// Cek alternatif file kartu belakang (filesystem)
if (!file_exists($paths_fs['kartu_belakang'])) {
    $alt_candidates = [
        $fs_root . 'content/assets/kartu-pelajar/belakang.png',
        $fs_root . 'content/assets/kartu-belakang.jpg',
        $fs_root . 'content/assets/kartu-belakang.png',
        $fs_root . 'assets/kartu-pelajar/belakang.jpg',
        $fs_root . 'assets/kartu-pelajar/belakang.png'
    ];
    foreach ($alt_candidates as $alt) {
        if (file_exists($alt)) {
            $paths_fs['kartu_belakang'] = $alt;
            $rel = str_replace($fs_root, '', $alt);
            $paths_web['kartu_belakang'] = $web_root . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            break;
        }
    }
} else {
    $rel = str_replace($fs_root, '', $paths_fs['kartu_belakang']);
    $paths_web['kartu_belakang'] = $web_root . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
}

// Prioritaskan nilai `avatar` dari DB (bisa berisi "file.ext?t=timestamp")
if (!empty($user['avatar'])) {
    $avatar_db_val = $user['avatar'];
    $avatar_file_only = preg_replace('/\?.*/', '', $avatar_db_val);
    $avatar_fs_path = $fs_root . 'content/avatar/' . $avatar_file_only;
    if (!empty($avatar_file_only) && file_exists($avatar_fs_path)) {
        // Gunakan string DB (termasuk query string) sebagai web src sehingga cache busting dipakai
        $paths_fs['foto'] = $avatar_fs_path;
        $rel = str_replace($fs_root, '', $avatar_fs_path);
        $paths_web['foto'] = $web_root . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel) . (strpos($avatar_db_val, '?') !== false ? '?' . substr($avatar_db_val, strpos($avatar_db_val, '?') + 1) : '');
    }
}

// Jika belum ada foto dari DB, coba berdasarkan NISN (cari .png lalu .jpg), tambahkan filemtime sebagai cache-buster
if ($paths_web['foto'] === $web_root . '/content/avatar/avatar.jpg') {
    if (!empty($user['nisn'])) {
        $foto_png_fs = $fs_root . 'content/avatar/' . $user['nisn'] . '.png';
        $foto_jpg_fs = $fs_root . 'content/avatar/' . $user['nisn'] . '.jpg';
        if (file_exists($foto_png_fs)) {
            $paths_fs['foto'] = $foto_png_fs;
            $paths_web['foto'] = $web_root . '/content/avatar/' . $user['nisn'] . '.png?t=' . filemtime($foto_png_fs);
        } elseif (file_exists($foto_jpg_fs)) {
            $paths_fs['foto'] = $foto_jpg_fs;
            $paths_web['foto'] = $web_root . '/content/avatar/' . $user['nisn'] . '.jpg?t=' . filemtime($foto_jpg_fs);
        }
    }
}

$avatar_file_only = preg_replace('/\?.*/', '', trim((string)$avatar_db));
$has_custom_avatar = ($avatar_file_only !== '' && strtolower($avatar_file_only) !== 'avatar.jpg' && file_exists($fs_root . 'content/avatar/' . $avatar_file_only));

if (!$has_custom_avatar) {
    if (isset($_GET['modal']) && $_GET['modal'] == '1') {
        header('Content-Type: text/html; charset=utf-8');
        echo '<div class="modal-body"><div class="alert alert-warning mb-0"><strong>Avatar belum tersedia.</strong><br>Preview dan unduh kartu pelajar hanya tersedia jika siswa sudah upload avatar (bukan <code>avatar.jpg</code>).</div></div>';
        echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-2"></i>Tutup</button></div>';
        exit;
    }
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kartu pelajar tidak tersedia: avatar belum diupload.';
    exit;
}

// Logo jurusan: gunakan file sesuai jurusan jika ada, tambahkan filemtime untuk cache-busting
$logo_jurusan_file = (!empty($jurusan_id) ? $jurusan_id : 'default') . '.png';
$logo_jurusan_fs = $fs_root . 'content/assets/logo-jurusan/' . $logo_jurusan_file;
if (file_exists($logo_jurusan_fs)) {
    $paths_fs['logo_jurusan'] = $logo_jurusan_fs;
    $rel = str_replace($fs_root, '', $logo_jurusan_fs);
    $paths_web['logo_jurusan'] = $web_root . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel) . '?t=' . filemtime($logo_jurusan_fs);
}

// QR code: jika ada file QR di server, gunakan dan tambahkan filemtime
$qrcode_file_fs = $fs_root . "content/qrcode/{$nisn}.jpg";
if (file_exists($qrcode_file_fs)) {
    $paths_fs['qrcode'] = $qrcode_file_fs;
    $rel = str_replace($fs_root, '', $qrcode_file_fs);
    $paths_web['qrcode'] = $web_root . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel) . '?t=' . filemtime($qrcode_file_fs);
} else {
    // Generate QR jika belum ada
    if (!empty($nisn)) {
        require_once '../../../library/phpqrcode/qrlib.php';
        $codeContents = $public_base . '/' . $nisn;
        $tempdir_fs = $fs_root . 'content/qrcode/';
        if (!is_dir($tempdir_fs)) {
            @mkdir($tempdir_fs, 0755, true);
        }
        $namafile = $nisn . '.jpg';
        $quality = 'QR_ECLEVEL_Q';
        $ukuran = 10;
        $padding = 1;
        try {
            QRCode::png($codeContents, $tempdir_fs . $namafile, $quality, $ukuran, $padding);
            if (file_exists($tempdir_fs . $namafile)) {
                $paths_fs['qrcode'] = $tempdir_fs . $namafile;
                $paths_web['qrcode'] = $web_root . '/content/qrcode/' . $namafile . '?t=' . time();
            }
        } catch (Exception $e) {
            // QR code generation failed, leave empty
        }
    }
}

// Tentukan kelas nama berdasarkan panjang
$nama_length = strlen($nama);
$nama_class = 'kartu-nama-row ';
if ($nama_length <= 8) {
    $nama_class .= 'short';
} elseif ($nama_length <= 15) {
    $nama_class .= 'medium';
} else {
    $nama_class .= 'long';
}

// Jika parameter modal=1, tampilkan konten untuk modal
if (isset($_GET['modal']) && $_GET['modal'] == '1') {
    header('Content-Type: text/html; charset=utf-8');
    $front_url = './mod/user/download_kartu.php?user_id=' . rawurlencode($user_id) . '&side=depan&inline=1&t=' . time();
    $back_url = './mod/user/download_kartu.php?user_id=' . rawurlencode($user_id) . '&side=belakang&inline=1&t=' . time();
?>
    <div class="modal-body">
        <div class="text-center" style="max-width:370px; margin: 0 auto;">
            <img id="kartu-depan-modal" src="<?= htmlspecialchars($front_url) ?>" alt="Kartu Pelajar Depan" style="width:100%; border-radius:18px; box-shadow:0 8px 24px rgba(15,23,42,0.16);" loading="lazy">
            <img id="kartu-belakang-modal" src="<?= htmlspecialchars($back_url) ?>" alt="Kartu Pelajar Belakang" style="display:none; width:100%; border-radius:18px; box-shadow:0 8px 24px rgba(15,23,42,0.16);" loading="lazy">
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" id="btn-download-depan-modal">
            <i class="fas fa-download mr-2"></i>Download Depan
        </button>
        <button type="button" class="btn btn-outline-success" id="btn-download-belakang-modal">
            <i class="fas fa-download mr-2"></i>Download Belakang
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times mr-2"></i>Tutup
        </button>
    </div>

<?php
    exit;
}

// Jika tidak ada parameter side dan bukan modal, tampilkan preview seperti ekpd.php
if (!isset($_GET['side']) && !isset($_GET['download'])) {
    // Prefer module-relative preview URLs using `$base` so hosting path resolution matches berkas module
    $paths_preview = [];
    foreach ($paths_fs as $k => $fs_path) {
        if (empty($fs_path)) {
            $paths_preview[$k] = '';
            continue;
        }
        $rel = str_replace(str_replace('\\', '/', $fs_root), '', str_replace('\\', '/', $fs_path));
        $rel = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $rel), '/');
        $asset_base = rtrim($site_root_url, '/');
        $url = $asset_base . '/' . $rel;
        if (file_exists($fs_path)) $url .= '?t=' . filemtime($fs_path);
        $paths_preview[$k] = $url;
    }

    header('Content-Type: text/html; charset=utf-8');
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preview Kartu Pelajar - <?= htmlspecialchars($nama) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?= $public_base ?>/assets/css/style.css">
        <style>
            body {
                background: linear-gradient(135deg, #6c7ae0 0%, #7c3aed 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            }

            .preview-container {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 20px;
                padding: 30px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                max-width: 400px;
                width: 100%;
            }

            .preview-title {
                text-align: center;
                margin-bottom: 20px;
                color: #2d3748;
                font-weight: 600;
                font-size: 1.4rem;
            }

            .download-buttons {
                margin-top: 20px;
                text-align: center;
                display: flex;
                gap: 12px;
                justify-content: center;
            }

            .btn-download {
                padding: 12px 20px;
                border-radius: 12px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                border: none;
                font-size: 0.95rem;
            }

            .btn-depan {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }

            .btn-belakang {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
            }

            .btn-download:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                color: white;
                text-decoration: none;
            }

            .info-text {
                text-align: center;
                color: #6b7280;
                font-size: 0.9rem;
                margin-top: 15px;
            }

            @media (max-width: 400px) {
                .kartu-pelajar-portrait {
                    width: 98vw !important;
                    height: calc(98vw * 1.59) !important;
                    min-width: 220px;
                    min-height: 350px;
                }

                .download-buttons {
                    flex-direction: column;
                }
            }
        </style>
    </head>

    <body>
        <div class="preview-container">
            <h2 class="preview-title">
                <i class="fas fa-id-card me-2"></i>
                Preview Kartu Pelajar
            </h2>

            <div class="kartu-pelajar-portrait-wrapper w-100" style="max-width:370px; margin: 0 auto;">
                <!-- Kartu Depan -->
                <div class="kartu-pelajar-portrait position-relative mx-auto" id="kartu-depan">
                    <?php if (!empty($paths_fs['kartu_depan']) && file_exists($paths_fs['kartu_depan'])): ?>
                        <img src="<?= $paths_preview['kartu_depan'] ?>" alt="Kartu Pelajar Depan" class="kartu-bg" loading="lazy" crossorigin="anonymous">
                    <?php endif; ?>

                    <div class="kartu-content">
                        <!-- Logo Jurusan Background -->
                        <?php if (!empty($paths_fs['logo_jurusan']) && file_exists($paths_fs['logo_jurusan'])): ?>
                            <div class="kartu-jurusan-row">
                                <img src="<?= $paths_preview['logo_jurusan'] ?>" alt="Logo Jurusan" class="kartu-logo-jurusan" loading="lazy" crossorigin="anonymous">
                            </div>
                        <?php endif; ?>

                        <!-- Foto Siswa -->
                        <?php if (!empty($paths_fs['foto']) && file_exists($paths_fs['foto'])): ?>
                            <div class="kartu-foto-row">
                                <img src="<?= $paths_preview['foto'] ?>" alt="Foto Siswa" class="kartu-foto-siswa" loading="lazy" crossorigin="anonymous">
                            </div>
                        <?php endif; ?>

                        <!-- Nama Siswa -->
                        <div class="<?= $nama_class ?>"><?= htmlspecialchars($nama) ?></div>

                        <!-- NISN -->
                        <div class="kartu-nisn-row"><strong><?= htmlspecialchars($nisn) ?></strong></div>

                        <!-- QR Code -->
                        <div class="kartu-bottom-row">
                            <?php if (!empty($paths_fs['qrcode']) && file_exists($paths_fs['qrcode'])): ?>
                                <img src="<?= $paths_preview['qrcode'] ?>" alt="QR Code" class="kartu-qrcode" loading="lazy" crossorigin="anonymous">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Kartu Belakang -->
                <div class="kartu-pelajar-portrait position-relative mx-auto" id="kartu-belakang" style="display: none; margin-top: 20px;">
                    <?php if (!empty($paths_fs['kartu_belakang']) && file_exists($paths_fs['kartu_belakang'])): ?>
                        <img src="<?= $paths_preview['kartu_belakang'] ?>" alt="Kartu Pelajar Belakang" class="kartu-bg" loading="lazy" crossorigin="anonymous">
                    <?php endif; ?>
                </div>
            </div>

            <div class="download-buttons">
                <button type="button" class="btn-download btn-depan" id="btn-download-depan">
                    <i class="fas fa-download me-2"></i>Download Depan
                </button>
                <button type="button" class="btn-download btn-belakang" id="btn-download-belakang">
                    <i class="fas fa-download me-2"></i>Download Belakang
                </button>
            </div>

            <div class="info-text">
                <i class="fas fa-info-circle me-1"></i>
                Klik tombol untuk mengunduh kartu dalam format PNG
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const btnDepan = document.getElementById('btn-download-depan');
                const btnBelakang = document.getElementById('btn-download-belakang');
                const kartuDepan = document.getElementById('kartu-depan');
                const kartuBelakang = document.getElementById('kartu-belakang');

                if (btnDepan) {
                    btnDepan.addEventListener('click', function() {
                        btnDepan.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
                        btnDepan.disabled = true;

                        // Pastikan kartu depan visible
                        kartuDepan.style.display = 'block';
                        kartuBelakang.style.display = 'none';

                        setTimeout(() => {
                            html2canvas(kartuDepan, {
                                backgroundColor: null,
                                useCORS: true,
                                scale: 2,
                                logging: false
                            }).then(function(canvas) {
                                // Create masked canvas to ensure rounded corners
                                const w = canvas.width;
                                const h = canvas.height;
                                const out = document.createElement('canvas');
                                out.width = w;
                                out.height = h;
                                const ctx = out.getContext('2d');

                                // Draw rounded rect path
                                const r = Math.round(18 * (w / 340)); // scale radius proportionally
                                ctx.clearRect(0, 0, w, h);
                                ctx.beginPath();
                                ctx.moveTo(r, 0);
                                ctx.arcTo(w, 0, w, h, r);
                                ctx.arcTo(w, h, 0, h, r);
                                ctx.arcTo(0, h, 0, 0, r);
                                ctx.arcTo(0, 0, w, 0, r);
                                ctx.closePath();
                                ctx.clip();
                                ctx.drawImage(canvas, 0, 0);

                                const link = document.createElement('a');
                                link.download = 'kartu-pelajar-depan-<?= $nisn ?>.png';
                                link.href = out.toDataURL('image/png');
                                link.click();

                                btnDepan.innerHTML = '<i class="fas fa-download me-2"></i>Download Depan';
                                btnDepan.disabled = false;
                            }).catch(function(e) {
                                console.error(e);
                                alert('Gagal generate gambar');
                                btnDepan.innerHTML = '<i class="fas fa-download me-2"></i>Download Depan';
                                btnDepan.disabled = false;
                            });
                        }, 100);
                    });
                }

                if (btnBelakang) {
                    btnBelakang.addEventListener('click', function() {
                        btnBelakang.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
                        btnBelakang.disabled = true;

                        // Show kartu belakang for capture
                        kartuDepan.style.display = 'none';
                        kartuBelakang.style.display = 'block';

                        setTimeout(() => {
                            html2canvas(kartuBelakang, {
                                backgroundColor: null,
                                useCORS: true,
                                scale: 2,
                                logging: false
                            }).then(function(canvas) {
                                // Create masked canvas to ensure rounded corners
                                const w = canvas.width;
                                const h = canvas.height;
                                const out = document.createElement('canvas');
                                out.width = w;
                                out.height = h;
                                const ctx = out.getContext('2d');

                                // Draw rounded rect path
                                const r = Math.round(18 * (w / 340)); // scale radius proportionally
                                ctx.clearRect(0, 0, w, h);
                                ctx.beginPath();
                                ctx.moveTo(r, 0);
                                ctx.arcTo(w, 0, w, h, r);
                                ctx.arcTo(w, h, 0, h, r);
                                ctx.arcTo(0, h, 0, 0, r);
                                ctx.arcTo(0, 0, w, 0, r);
                                ctx.closePath();
                                ctx.clip();
                                ctx.drawImage(canvas, 0, 0);

                                const link = document.createElement('a');
                                link.download = 'kartu-pelajar-belakang-<?= $nisn ?>.png';
                                link.href = out.toDataURL('image/png');
                                link.click();

                                // Hide kartu belakang after capture
                                kartuDepan.style.display = 'block';
                                kartuBelakang.style.display = 'none';

                                btnBelakang.innerHTML = '<i class="fas fa-download me-2"></i>Download Belakang';
                                btnBelakang.disabled = false;
                            }).catch(function(e) {
                                console.error(e);
                                alert('Gagal generate gambar');
                                kartuDepan.style.display = 'block';
                                kartuBelakang.style.display = 'none';
                                btnBelakang.innerHTML = '<i class="fas fa-download me-2"></i>Download Belakang';
                                btnBelakang.disabled = false;
                            });
                        }, 100);
                    });
                }
            });
        </script>
    </body>

    </html>
<?php
    exit;
}

$side = isset($_GET['side']) && $_GET['side'] === 'belakang' ? 'belakang' : 'depan';

// Use absolute filesystem paths (from $paths_fs) for server-side generation to avoid relative path issues
$bg_path = ($side === 'depan') ? ($paths_fs['kartu_depan'] ?? '') : ($paths_fs['kartu_belakang'] ?? '');
$logo_path = $paths_fs['logo_jurusan'] ?? '';
$qrcode_path = $paths_fs['qrcode'] ?? '';

// Avatar resolution: prefer DB value stored in $paths_fs['foto'], else fallback to NISN files
$avatar_path = '';
$avatar_file_only = preg_replace('/\?.*/', '', $avatar_db);
if (!empty($paths_fs['foto']) && file_exists($paths_fs['foto'])) {
    $avatar_path = $paths_fs['foto'];
} else {
    // fallback by nisn in filesystem
    if (!empty($nisn)) {
        $png_fs = $fs_root . 'content/avatar/' . $nisn . '.png';
        $jpg_fs = $fs_root . 'content/avatar/' . $nisn . '.jpg';
        if (file_exists($png_fs)) $avatar_path = $png_fs;
        elseif (file_exists($jpg_fs)) $avatar_path = $jpg_fs;
    }
}

// Final fallbacks: ensure bg/logo/qrcode have filesystem paths if earlier attempts failed
if (empty($bg_path)) $bg_path = $fs_root . 'content/assets/kartu-pelajar/' . ($side === 'depan' ? 'depan.jpg' : 'belakang.jpg');
if (empty($logo_path)) $logo_path = $fs_root . 'content/assets/logo-jurusan/default.png';
if (empty($qrcode_path)) $qrcode_path = $fs_root . "content/qrcode/{$nisn}.jpg";

function file_signature_meta($path)
{
    if (empty($path) || !file_exists($path)) {
        return array('path' => (string)$path, 'exists' => false, 'mtime' => 0, 'size' => 0);
    }
    return array(
        'path' => (string)$path,
        'exists' => true,
        'mtime' => (int)@filemtime($path),
        'size' => (int)@filesize($path),
    );
}

function is_valid_png_cache_file($path)
{
    if (empty($path) || !file_exists($path)) return false;
    $size = (int)@filesize($path);
    if ($size < 128) return false;
    $info = @getimagesize($path);
    if (!$info || empty($info['mime']) || $info['mime'] !== 'image/png') return false;
    return true;
}

function atomic_write_file($path, $data)
{
    $dir = dirname($path);
    if (!is_dir($dir)) return false;

    $tmp = $path . '.tmp.' . uniqid('', true);
    $ok = @file_put_contents($tmp, $data, LOCK_EX);
    if ($ok === false) {
        @unlink($tmp);
        return false;
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function cleanup_kartu_cache($cache_dir, $ttl_seconds = 604800, $max_files = 5000)
{
    if (!is_dir($cache_dir)) return;

    $now = time();
    $files = @glob($cache_dir . '*.png');
    if (!is_array($files)) return;

    foreach ($files as $file) {
        $mtime = (int)@filemtime($file);
        if ($mtime > 0 && ($now - $mtime) > $ttl_seconds) {
            @unlink($file);
        }
    }

    $files = @glob($cache_dir . '*.png');
    if (!is_array($files) || count($files) <= $max_files) return;

    usort($files, function ($a, $b) {
        return (int)@filemtime($a) <=> (int)@filemtime($b);
    });

    $to_delete = count($files) - $max_files;
    for ($i = 0; $i < $to_delete; $i++) {
        @unlink($files[$i]);
    }

    // Bersihkan lock file lama agar folder cache tetap rapi.
    $lock_files = @glob($cache_dir . '*.lock');
    if (is_array($lock_files)) {
        foreach ($lock_files as $lf) {
            $mtime = (int)@filemtime($lf);
            if ($mtime > 0 && ($now - $mtime) > $ttl_seconds) {
                @unlink($lf);
            }
        }
    }
}

$is_inline = isset($_GET['inline']) && $_GET['inline'] == '1';
$cache_bypass = isset($_GET['no_cache']) && $_GET['no_cache'] == '1';
$render_version = 'kartu-render-v2';
$cache_enabled = !$cache_bypass;
$cache_file = '';
$cache_lock_file = '';
$cache_lock_handle = null;

if ($cache_enabled) {
    $cache_dir = $fs_root . 'content' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'kartu-pelajar' . DIRECTORY_SEPARATOR;
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }

    if (is_dir($cache_dir)) {
        $cache_payload = array(
            'v' => $render_version,
            'side' => (string)$side,
            'user_id' => (string)$user_id,
            'nisn' => (string)$nisn,
            'nama' => (string)$nama,
            'avatar_db' => (string)$avatar_db,
            'bg' => file_signature_meta($bg_path),
            'logo' => file_signature_meta($logo_path),
            'avatar' => file_signature_meta($avatar_path),
            'qrcode' => file_signature_meta($qrcode_path),
        );
        $cache_key = sha1(json_encode($cache_payload));
        $cache_user = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$user_id);
        $cache_nisn = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$nisn);
        $cache_file = $cache_dir . 'u' . $cache_user . '-n' . $cache_nisn . '-' . $side . '-' . $cache_key . '.png';
        $cache_lock_file = $cache_file . '.lock';

        // Cleanup berkala (2% request) agar cache tidak terus membesar.
        if (mt_rand(1, 100) <= 2) {
            cleanup_kartu_cache($cache_dir, 7 * 24 * 60 * 60, 5000);
        }

        if (file_exists($cache_file)) {
            if (!is_valid_png_cache_file($cache_file)) {
                @unlink($cache_file);
            }
        }

        if (is_valid_png_cache_file($cache_file)) {
            $filename = 'kartu-pelajar-' . ($nisn ?: $user_id) . '-' . $side . '.png';
            header('Content-Type: image/png');
            if ($is_inline) {
                header('Content-Disposition: inline; filename="' . basename($filename) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            }
            clearstatcache(true, $cache_file);
            header('Content-Length: ' . (int)filesize($cache_file));
            readfile($cache_file);
            exit;
        }

        // Lock per cache-key untuk mencegah render ganda saat request paralel.
        $cache_lock_handle = @fopen($cache_lock_file, 'c');
        if ($cache_lock_handle) {
            @flock($cache_lock_handle, LOCK_EX);

            // Setelah lock didapat, cek ulang karena request lain mungkin sudah selesai generate.
            if (is_valid_png_cache_file($cache_file)) {
                $filename = 'kartu-pelajar-' . ($nisn ?: $user_id) . '-' . $side . '.png';
                header('Content-Type: image/png');
                if ($is_inline) {
                    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
                } else {
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                }
                clearstatcache(true, $cache_file);
                header('Content-Length: ' . (int)filesize($cache_file));
                readfile($cache_file);
                @flock($cache_lock_handle, LOCK_UN);
                @fclose($cache_lock_handle);
                exit;
            }
        }
    }
}

// Load background or create blank canvas
function load_image_any($path)
{
    if (!file_exists($path)) return false;
    $info = getimagesize($path);
    if (!$info) return false;
    $mime = $info['mime'];
    if ($mime === 'image/png') return imagecreatefrompng($path);
    if ($mime === 'image/jpeg') return imagecreatefromjpeg($path);
    return false;
}

// Fixed canvas size to match ekpd.php: 340x540px (scaled 2x for quality)
$width = 680;  // 340 * 2
$height = 1080; // 540 * 2

$bg_img = load_image_any($bg_path);
if ($bg_img !== false) {
    $canvas = imagecreatetruecolor($width, $height);
    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);
    imagecopyresampled($canvas, $bg_img, 0, 0, 0, 0, $width, $height, imagesx($bg_img), imagesy($bg_img));
    imagedestroy($bg_img);
} else {
    $canvas = imagecreatetruecolor($width, $height);
    $bg_color = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $bg_color);
}

// Helper to overlay image with resizing
function overlay_image(&$dst, $src_path, $dst_x, $dst_y, $dst_w, $dst_h, $keep_alpha = true)
{
    $img = load_image_any($src_path);
    if (!$img) return false;
    $src_w = imagesx($img);
    $src_h = imagesy($img);
    if ($keep_alpha) {
        imagealphablending($dst, true);
        imagesavealpha($dst, true);
        $tmp = imagecreatetruecolor($dst_w, $dst_h);
        imagealphablending($tmp, false);
        $col = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefill($tmp, 0, 0, $col);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        imagecopy($dst, $tmp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h);
        imagedestroy($tmp);
    } else {
        imagecopyresampled($dst, $img, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    }
    imagedestroy($img);
    return true;
}

function overlay_image_with_opacity(&$dst, $src_path, $dst_x, $dst_y, $dst_w, $dst_h, $opacityPercent = 25)
{
    $img = load_image_any($src_path);
    if (!$img) return false;

    $opacityPercent = max(0, min(100, (int)$opacityPercent));
    $src_w = imagesx($img);
    $src_h = imagesy($img);

    $tmp = imagecreatetruecolor($dst_w, $dst_h);
    imagealphablending($tmp, false);
    $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    imagefill($tmp, 0, 0, $transparent);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

    imagealphablending($dst, true);
    imagecopymerge($dst, $tmp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $opacityPercent);

    imagedestroy($tmp);
    imagedestroy($img);
    return true;
}

function overlay_image_with_opacity_contain(&$dst, $src_path, $box_x, $box_y, $box_w, $box_h, $opacityPercent = 25)
{
    $img = load_image_any($src_path);
    if (!$img) return false;

    $opacityPercent = max(0, min(100, (int)$opacityPercent));
    $src_w = imagesx($img);
    $src_h = imagesy($img);
    if ($src_w <= 0 || $src_h <= 0 || $box_w <= 0 || $box_h <= 0) {
        imagedestroy($img);
        return false;
    }

    $src_ratio = $src_w / $src_h;
    $box_ratio = $box_w / $box_h;

    if ($src_ratio > $box_ratio) {
        $draw_w = $box_w;
        $draw_h = (int)round($box_w / $src_ratio);
    } else {
        $draw_h = $box_h;
        $draw_w = (int)round($box_h * $src_ratio);
    }

    $draw_x = $box_x + (int)floor(($box_w - $draw_w) / 2);
    $draw_y = $box_y + (int)floor(($box_h - $draw_h) / 2);

    $tmp = imagecreatetruecolor($draw_w, $draw_h);
    imagealphablending($tmp, false);
    $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    imagefill($tmp, 0, 0, $transparent);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $draw_w, $draw_h, $src_w, $src_h);

    imagealphablending($dst, true);
    imagecopymerge($dst, $tmp, $draw_x, $draw_y, 0, 0, $draw_w, $draw_h, $opacityPercent);

    imagedestroy($tmp);
    imagedestroy($img);
    return true;
}

function overlay_png_with_opacity_contain(&$dst, $src_path, $box_x, $box_y, $box_w, $box_h, $opacityPercent = 25)
{
    if (!file_exists($src_path)) return false;
    $img = @imagecreatefrompng($src_path);
    if (!$img) return false;

    $opacityPercent = max(0, min(100, (int)$opacityPercent));
    $src_w = imagesx($img);
    $src_h = imagesy($img);
    if ($src_w <= 0 || $src_h <= 0 || $box_w <= 0 || $box_h <= 0) {
        imagedestroy($img);
        return false;
    }

    $src_ratio = $src_w / $src_h;
    $box_ratio = $box_w / $box_h;
    if ($src_ratio > $box_ratio) {
        $draw_w = $box_w;
        $draw_h = (int)round($box_w / $src_ratio);
    } else {
        $draw_h = $box_h;
        $draw_w = (int)round($box_h * $src_ratio);
    }

    $draw_x = $box_x + (int)floor(($box_w - $draw_w) / 2);
    $draw_y = $box_y + (int)floor(($box_h - $draw_h) / 2);

    $tmp = imagecreatetruecolor($draw_w, $draw_h);
    imagealphablending($tmp, false);
    imagesavealpha($tmp, true);
    $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    imagefill($tmp, 0, 0, $transparent);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $draw_w, $draw_h, $src_w, $src_h);

    // Preserve alpha PNG and clean near-white solid background if present.
    $extraAlpha = (int)round((100 - $opacityPercent) * 1.27); // 0..127
    for ($yy = 0; $yy < $draw_h; $yy++) {
        for ($xx = 0; $xx < $draw_w; $xx++) {
            $rgba = imagecolorat($tmp, $xx, $yy);
            $a = ($rgba >> 24) & 0x7F;
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // Remove flat white-ish background from non-transparent logo assets.
            if ($r > 242 && $g > 242 && $b > 242 && $a < 120) {
                $newA = 127;
            } else {
                $newA = min(127, $a + $extraAlpha);
            }

            $col = imagecolorallocatealpha($tmp, $r, $g, $b, $newA);
            imagesetpixel($tmp, $xx, $yy, $col);
        }
    }

    imagealphablending($dst, true);
    imagesavealpha($dst, true);
    imagecopy($dst, $tmp, $draw_x, $draw_y, 0, 0, $draw_w, $draw_h);

    imagedestroy($tmp);
    imagedestroy($img);
    return true;
}

function apply_vertical_white_gradient(&$dst, $x, $y, $w, $h, $startAlpha = 127, $endAlpha = 0)
{
    $h = (int)$h;
    if ($h <= 0 || $w <= 0) return;
    imagealphablending($dst, true);

    for ($i = 0; $i < $h; $i++) {
        $t = $h > 1 ? ($i / ($h - 1)) : 1;
        $a = (int)round($startAlpha + ($endAlpha - $startAlpha) * $t);
        $a = max(0, min(127, $a));
        $col = imagecolorallocatealpha($dst, 255, 255, 255, $a);
        imageline($dst, $x, $y + $i, $x + $w, $y + $i, $col);
    }
}

function draw_ttf_with_white_shadow(&$dst, $size, $x, $y, $font, $text, $mainColor)
{
    $shadow1 = imagecolorallocatealpha($dst, 255, 255, 255, 70);
    $shadow2 = imagecolorallocatealpha($dst, 255, 255, 255, 95);

    imagettftext($dst, $size, 0, $x - 2, $y, $shadow1, $font, $text);
    imagettftext($dst, $size, 0, $x + 2, $y, $shadow1, $font, $text);
    imagettftext($dst, $size, 0, $x, $y - 2, $shadow1, $font, $text);
    imagettftext($dst, $size, 0, $x, $y + 2, $shadow1, $font, $text);
    imagettftext($dst, $size, 0, $x - 1, $y - 1, $shadow2, $font, $text);
    imagettftext($dst, $size, 0, $x + 1, $y + 1, $shadow2, $font, $text);
    imagettftext($dst, $size, 0, $x, $y, $mainColor, $font, $text);
}

function wrap_text_by_width($text, $font, $size, $maxWidth)
{
    $text = trim((string)$text);
    if ($text === '') return [''];

    $words = preg_split('/\s+/', $text);
    $lines = [];
    $current = '';

    foreach ($words as $w) {
        $candidate = ($current === '') ? $w : ($current . ' ' . $w);
        $bbox = imagettfbbox($size, 0, $font, $candidate);
        $wpx = $bbox[2] - $bbox[0];

        if ($wpx <= $maxWidth || $current === '') {
            $current = $candidate;
            if ($wpx > $maxWidth) {
                // Satu kata sangat panjang: pecah per karakter.
                $current = '';
                $chars = preg_split('//u', $candidate, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($chars as $ch) {
                    $candChar = $current . $ch;
                    $bboxChar = imagettfbbox($size, 0, $font, $candChar);
                    $charW = $bboxChar[2] - $bboxChar[0];
                    if ($charW > $maxWidth && $current !== '') {
                        $lines[] = $current;
                        $current = $ch;
                    } else {
                        $current = $candChar;
                    }
                }
            }
        } else {
            $lines[] = $current;
            $current = $w;
        }
    }

    if ($current !== '') $lines[] = $current;
    return $lines;
}

function fit_text_into_box($text, $font, $maxWidth, $maxHeight, $minSize = 18, $maxSize = 52)
{
    $text = trim((string)$text);
    if ($text === '') {
        return [
            'size' => $minSize,
            'lines' => [''],
            'lineHeight' => $minSize,
            'totalHeight' => $minSize,
            'maxLineWidth' => 0
        ];
    }

    for ($size = $maxSize; $size >= $minSize; $size--) {
        $lines = wrap_text_by_width($text, $font, $size, $maxWidth);
        $lineHeight = (int)round($size * 1.18);
        $totalHeight = $lineHeight * count($lines);

        $maxLineWidth = 0;
        foreach ($lines as $line) {
            $bbox = imagettfbbox($size, 0, $font, $line);
            $lineW = $bbox[2] - $bbox[0];
            if ($lineW > $maxLineWidth) $maxLineWidth = $lineW;
        }

        if ($totalHeight <= $maxHeight && $maxLineWidth <= $maxWidth) {
            return [
                'size' => $size,
                'lines' => $lines,
                'lineHeight' => $lineHeight,
                'totalHeight' => $totalHeight,
                'maxLineWidth' => $maxLineWidth
            ];
        }
    }

    // Fallback minimum size jika teks sangat panjang.
    $lines = wrap_text_by_width($text, $font, $minSize, $maxWidth);
    $lineHeight = (int)round($minSize * 1.18);
    return [
        'size' => $minSize,
        'lines' => $lines,
        'lineHeight' => $lineHeight,
        'totalHeight' => $lineHeight * count($lines),
        'maxLineWidth' => $maxWidth
    ];
}

if ($side === 'depan') {
    // Watermark jurusan transparan di belakang avatar (sesuai preview awal).
    if (file_exists($logo_path)) {
        $logo_ext = strtolower((string)pathinfo($logo_path, PATHINFO_EXTENSION));
        // Kembalikan ukuran watermark seperti komposisi awal, tetap rasio asli PNG.
        if ($logo_ext === 'png') {
            $wm_x = -50;
            $wm_y = 320;
            $wm_w = 360;
            $wm_h = 360;
            overlay_png_with_opacity_contain($canvas, $logo_path, $wm_x, $wm_y, $wm_w, $wm_h, 20);
        }
    }

    // Place avatar to match CSS template .kartu-foto-row (top:70, w:340, h:470) at 2x scale.
    if (!empty($avatar_path) && file_exists(preg_replace('/\?.*/', '', $avatar_path))) {
        $src_avatar = preg_replace('/\?.*/', '', $avatar_path);

        // Foto full kiri-kanan dan mentok ke batas bawah kartu.
        $av_x = 0;
        $av_y = 180;
        $av_w = $width;
        $av_h = $height - $av_y;

        $img_av = load_image_any($src_avatar);
        if ($img_av) {
            // Avatar cover agar benar-benar penuh kiri-kanan.
            $src_w = imagesx($img_av);
            $src_h = imagesy($img_av);

            $src_ratio = $src_w / $src_h;
            $dst_ratio = $av_w / $av_h;
            if ($src_ratio > $dst_ratio) {
                // Source lebih lebar: samakan tinggi area lalu crop kiri-kanan.
                $draw_h = $av_h;
                $draw_w = (int)round($av_h * $src_ratio);
            } else {
                // Source lebih sempit/tinggi: samakan lebar area lalu crop bawah.
                $draw_w = $av_w;
                $draw_h = (int)round($av_w / $src_ratio);
            }
            $draw_x = $av_x + (int)floor(($av_w - $draw_w) / 2);
            $draw_y = $av_y;

            imagecopyresampled($canvas, $img_av, $draw_x, $draw_y, 0, 0, $draw_w, $draw_h, $src_w, $src_h);
            imagedestroy($img_av);

            // Tutup setengah badan dengan gradasi putih: atas 0% -> bawah 100%.
            $grad_x = $draw_x;
            $grad_y = $draw_y + (int)floor($draw_h * 0.50);
            $grad_w = $draw_w;
            $grad_h = $draw_h - (int)floor($draw_h * 0.50);
            apply_vertical_white_gradient($canvas, $grad_x, $grad_y, $grad_w, $grad_h, 127, 0);
        }
    }

    // QR placement baseline (dipakai juga untuk batas lebar teks nama).
    $qr_s = 240;
    $qr_x = 392;
    $qr_y = 804;

    // QR code di kanan bawah sesuai CSS (.kartu-bottom-row + .kartu-qrcode) di 2x scale.
    if (file_exists($qrcode_path)) {

        // QR with white background and border
        $qr_img = load_image_any($qrcode_path);
        if ($qr_img) {
            // QR with white background and border
            $qr_with_bg = imagecreatetruecolor($qr_s, $qr_s);
            $white_qr = imagecolorallocate($qr_with_bg, 255, 255, 255);
            imagefill($qr_with_bg, 0, 0, $white_qr);
            imagecopyresampled($qr_with_bg, $qr_img, 0, 0, 0, 0, $qr_s, $qr_s, imagesx($qr_img), imagesy($qr_img));

            // Draw border
            $border = imagecolorallocate($qr_with_bg, 221, 221, 221);
            imagerectangle($qr_with_bg, 0, 0, $qr_s - 1, $qr_s - 1, $border);

            imagecopy($canvas, $qr_with_bg, $qr_x, $qr_y, 0, 0, $qr_s, $qr_s);
            imagedestroy($qr_with_bg);
            imagedestroy($qr_img);
        }
    }

    // Draw nama and nisn to match CSS positions.
    $black = imagecolorallocate($canvas, 20, 20, 20);
    $white = imagecolorallocate($canvas, 255, 255, 255);

    // Font setup: gunakan font sistem yang umum dan rapi, tanpa dependensi font custom.
    $font_bold_candidates = array(
        'C:/Windows/Fonts/segoeuib.ttf', // Segoe UI Bold
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/tahomabd.ttf',
        $base . 'assets/fonts/arialbd.ttf',
        $base . 'admin/assets/fonts/arialbd.ttf',
    );
    $font_regular_candidates = array(
        'C:/Windows/Fonts/segoeui.ttf', // Segoe UI Regular
        'C:/Windows/Fonts/arial.ttf',
        'C:/Windows/Fonts/tahoma.ttf',
        $base . 'assets/fonts/arial.ttf',
        $base . 'admin/assets/fonts/arial.ttf',
    );

    $font_bold = '';
    $font_regular = '';

    foreach ($font_bold_candidates as $f) {
        if ($font_bold === '' && file_exists($f)) $font_bold = $f;
    }
    foreach ($font_regular_candidates as $f) {
        if ($font_regular === '' && file_exists($f)) $font_regular = $f;
    }

    $name_text = mb_strtoupper(trim($nama ?: '-'));
    $nisn_text = trim($nisn ?: '-');

    if ($font_bold || $font_regular) {
        $font_for_name = $font_bold ? $font_bold : $font_regular;
        $font_for_nisn = $font_bold ? $font_bold : $font_for_name;


        // Nama full tanpa disembunyikan, fit otomatis dalam box kiri QR.
        // Batas atas disamakan dengan QR (top = $qr_y) dan dibatasi kiri/kanan/bawah.
        $name_box_left = 50;
        $name_box_top = $qr_y;
        $name_box_right = $qr_x - 20;
        $name_box_bottom = $qr_y + $qr_s;
        $name_box_width = max(120, $name_box_right - $name_box_left);
        $name_box_height = max(80, $name_box_bottom - $name_box_top);

        $fit = fit_text_into_box($name_text, $font_for_name, $name_box_width, $name_box_height, 18, 52);
        $name_size = $fit['size'];
        $name_lines = $fit['lines'];
        $name_line_h = $fit['lineHeight'];
        $name_total_h = $fit['totalHeight'];

        // Vertikal center di dalam box agar rapi dan tetap dalam batas.
        $line_y = $name_box_top + (int)floor(($name_box_height - $name_total_h) / 2) + $name_line_h;
        foreach ($name_lines as $line) {
            draw_ttf_with_white_shadow($canvas, $name_size, $name_box_left, $line_y, $font_for_name, $line, $black);
            $line_y += $name_line_h;
        }

        // NISN: right 25, bottom 140 at 2x scale.
        $size_nisn = 44;
        $nisn_y = 770;

        // Batasi panjang visual NISN agar tidak melebihi lebar QR (240 px).
        $max_nisn_width = 240;
        $nisn_bbox = imagettfbbox($size_nisn, 0, $font_for_nisn, $nisn_text);
        while ($size_nisn > 24 && ($nisn_bbox[2] - $nisn_bbox[0]) > $max_nisn_width) {
            $size_nisn -= 2;
            $nisn_bbox = imagettfbbox($size_nisn, 0, $font_for_nisn, $nisn_text);
        }

        // Hitung lebar untuk align kanan, lalu render dengan white-shadow.
        $nisn_w = $nisn_bbox[2] - $nisn_bbox[0];
        $nisn_x = $width - 50 - $nisn_w;
        draw_ttf_with_white_shadow($canvas, $size_nisn, $nisn_x, $nisn_y, $font_for_nisn, $nisn_text, $black);
    } else {
        // Fallback: imagestring
        imagestring($canvas, 5, intval($width * 0.06), intval($height * 0.85), $name_text, $black);
        imagestring($canvas, 4, intval($width * 0.75), intval($height * 0.4), $nisn_text, $black);
    }
} else {
    // belakang: gunakan template background saja agar hasil konsisten.
}

// Output PNG for download
$filename = 'kartu-pelajar-' . ($nisn ?: $user_id) . '-' . $side . '.png';
$png_data = '';
ob_start();
imagepng($canvas);
$png_data = ob_get_clean();

if ($cache_enabled && $cache_file !== '' && is_string($png_data) && $png_data !== '') {
    if (!is_valid_png_cache_file($cache_file)) {
        atomic_write_file($cache_file, $png_data);
    }
}

if (is_resource($cache_lock_handle)) {
    @flock($cache_lock_handle, LOCK_UN);
    @fclose($cache_lock_handle);
    $cache_lock_handle = null;
}

imagedestroy($canvas);

header('Content-Type: image/png');
if ($is_inline) {
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
} else {
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
}
if (is_string($png_data) && $png_data !== '') {
    header('Content-Length: ' . strlen($png_data));
    echo $png_data;
}
exit;
