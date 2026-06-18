<?php
// Server-side generator: download kartu pelajar sebagai PNG
// Usage: download_kartu.php?user_id=123&side=depan
// Preview: download_kartu.php?user_id=123 (tanpa parameter lain untuk preview)

require_once '../../../library/config.php';
require_once '../../../library/function.php';

// Ensure connection charset
if (method_exists($connection, 'set_charset')) $connection->set_charset('utf8');

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Unauthorized';
    exit;
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
    // Prefer relative URLs using `$base` (module-relative) so hosting path resolution matches berkas module
    $paths_out_rel = [];
    foreach ($paths_fs as $k => $fs_path) {
        if (empty($fs_path)) {
            $paths_out_rel[$k] = '';
            continue;
        }
        // compute path relative to fs_root
        $rel = str_replace(str_replace('\\', '/', $fs_root), '', str_replace('\\', '/', $fs_path));
        $rel = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $rel), '/');
        // Use site-root absolute URLs to ensure correct resolution when HTML is injected via AJAX
        $asset_base = rtrim($site_root_url, '/');
        $url = $asset_base . '/' . $rel;
        if (file_exists($fs_path)) $url .= '?t=' . filemtime($fs_path);
        $paths_out_rel[$k] = $url;
    }

    header('Content-Type: text/html; charset=utf-8');
?>
    <div class="modal-body">
        <div class="text-center mb-3">
            <h5 class="modal-title">
                <i class="fas fa-id-card mr-2"></i>
                Preview Kartu Pelajar
            </h5>
            <small class="text-muted"><?= htmlspecialchars($nama) ?> - <?= htmlspecialchars($nisn) ?></small>
        </div>



        <div class="kartu-pelajar-portrait-wrapper w-100" style="max-width:370px; margin: 0 auto;">
            <!-- Kartu Depan -->
            <div class="kartu-pelajar-portrait position-relative mx-auto" id="kartu-depan-modal">
                <?php if (!empty($paths_fs['kartu_depan']) && file_exists($paths_fs['kartu_depan'])): ?>
                    <img src="<?= ($paths_out_rel['kartu_depan'] ?? '') ?>" alt="Kartu Pelajar Depan" class="kartu-bg" loading="lazy" crossorigin="anonymous">
                <?php else: ?>
                    <!-- Fallback jika kartu depan tidak ada -->
                    <div class="kartu-bg-fallback" style="width: 340px; height: 214px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 18px; position: relative;"></div>
                <?php endif; ?>

                <div class="kartu-content">
                    <!-- Logo Jurusan Background -->
                    <?php if (!empty($paths_fs['logo_jurusan']) && file_exists($paths_fs['logo_jurusan'])): ?>
                        <div class="kartu-jurusan-row">
                            <img src="<?= ($paths_out_rel['logo_jurusan'] ?? '') ?>" alt="Logo Jurusan" class="kartu-logo-jurusan" loading="lazy" crossorigin="anonymous">
                        </div>
                    <?php endif; ?>

                    <!-- Foto Siswa -->
                    <?php if (!empty($paths_fs['foto']) && file_exists($paths_fs['foto'])): ?>
                        <div class="kartu-foto-row">
                            <img src="<?= ($paths_out_rel['foto'] ?? '') ?>" alt="Foto Siswa" class="kartu-foto-siswa" loading="lazy" crossorigin="anonymous">
                        </div>
                    <?php else: ?>
                        <div class="kartu-foto-row">
                            <div class="kartu-foto-siswa" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Nama Siswa -->
                    <div class="<?= $nama_class ?>"><?= htmlspecialchars($nama) ?></div>

                    <!-- NISN -->
                    <div class="kartu-nisn-row"><strong><?= htmlspecialchars($nisn) ?></strong></div>

                    <!-- QR Code -->
                    <div class="kartu-bottom-row">
                        <?php if (!empty($paths_fs['qrcode']) && file_exists($paths_fs['qrcode'])): ?>
                            <img src="<?= ($paths_out_rel['qrcode'] ?? '') ?>" alt="QR Code" class="kartu-qrcode" loading="lazy" crossorigin="anonymous">
                        <?php else: ?>
                            <div class="kartu-qrcode" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 10px;">
                                QR Code
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kartu Belakang -->
            <div class="kartu-pelajar-portrait position-relative mx-auto" id="kartu-belakang-modal" style="display: none; margin-top: 20px;">
                <?php if (!empty($paths_fs['kartu_belakang']) && file_exists($paths_fs['kartu_belakang'])): ?>
                    <img src="<?= ($paths_out_rel['kartu_belakang'] ?? '') ?>" alt="Kartu Pelajar Belakang" class="kartu-bg" loading="lazy" crossorigin="anonymous">
                <?php endif; ?>
            </div>
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

    <!-- Load global admin stylesheet -->
    <link rel="stylesheet" href="<?= $public_base ?>/assets/css/style.css">



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

// Place logo di pojok kiri atas (visible, bukan watermark)
if (file_exists($logo_path)) {
    // Logo kiri atas, ukuran sekitar 80x60px di 340px width
    $logo_w = intval($width * 0.18); // ~122px di 680px width
    $logo_h = intval($logo_w * 0.75); // Proporsi logo yang umum
    $logo_x = intval($width * 0.04); // 4% dari kiri
    $logo_y = intval($height * 0.02); // 2% dari atas

    overlay_image($canvas, $logo_path, $logo_x, $logo_y, $logo_w, $logo_h, true);
}

if ($side === 'depan') {
    // Place avatar covering the full area from top 70px (match ekpd.php exactly)
    if (!empty($avatar_path) && file_exists(preg_replace('/\?.*/', '', $avatar_path))) {
        $src_avatar = preg_replace('/\?.*/', '', $avatar_path);

        // Avatar positioning to match ekpd.php: foto di tengah, tidak full width
        $av_w = intval($width * 0.7); // 70% dari lebar kartu  
        $av_h = intval($height * 0.5); // 50% dari tinggi kartu
        $av_x = intval(($width - $av_w) / 2); // Center horizontal
        $av_y = intval($height * 0.12); // Mulai 12% dari atas

        $img_av = load_image_any($src_avatar);
        if ($img_av) {
            // Create avatar with object-fit: cover behavior
            $src_w = imagesx($img_av);
            $src_h = imagesy($img_av);

            // Calculate crop for cover behavior
            $src_ratio = $src_w / $src_h;
            $dst_ratio = $av_w / $av_h;

            if ($src_ratio > $dst_ratio) {
                // Source is wider, crop horizontally
                $new_src_w = intval($src_h * $dst_ratio);
                $new_src_h = $src_h;
                $crop_x = intval(($src_w - $new_src_w) / 2);
                $crop_y = 0;
            } else {
                // Source is taller, crop vertically
                $new_src_w = $src_w;
                $new_src_h = intval($src_w / $dst_ratio);
                $crop_x = 0;
                $crop_y = intval(($src_h - $new_src_h) / 2);
            }

            imagecopyresampled($canvas, $img_av, $av_x, $av_y, $crop_x, $crop_y, $av_w, $av_h, $new_src_w, $new_src_h);
            imagedestroy($img_av);
        }
    }

    // QR code di kanan bawah (sesuai ekpd.php)
    if (file_exists($qrcode_path)) {
        $qr_s = intval($width * 0.18); // 18% dari lebar
        $qr_x = intval($width * 0.77); // Kanan bawah
        $qr_y = intval($height * 0.77); // Bawah

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

    // Draw nama and nisn with exact positioning from ekpd.php
    $black = imagecolorallocate($canvas, 20, 20, 20);
    $white = imagecolorallocate($canvas, 255, 255, 255);

    // Font setup
    $font_bold = '';
    $font_regular = '';
    if (PHP_OS_FAMILY === 'Windows') {
        if (file_exists('C:/Windows/Fonts/arialbd.ttf')) $font_bold = 'C:/Windows/Fonts/arialbd.ttf';
        if (file_exists('C:/Windows/Fonts/arial.ttf')) $font_regular = 'C:/Windows/Fonts/arial.ttf';
    }
    if ($font_bold === '' && file_exists($base . 'assets/fonts/arialbd.ttf')) $font_bold = $base . 'assets/fonts/arialbd.ttf';
    if ($font_regular === '' && file_exists($base . 'assets/fonts/arial.ttf')) $font_regular = $base . 'assets/fonts/arial.ttf';

    $name_text = mb_strtoupper(trim($nama ?: '-'));
    $nisn_text = trim($nisn ?: '-');

    if ($font_bold || $font_regular) {
        $font_for_name = $font_bold ? $font_bold : $font_regular;
        $font_for_nisn = $font_regular ? $font_regular : $font_for_name;


        // Nama di kiri bawah (sesuai gambar ekpd.php)
        $nama_length = strlen($name_text);
        if ($nama_length <= 8) {
            $size_name = intval($width * 0.055);
        } elseif ($nama_length <= 15) {
            $size_name = intval($width * 0.045);
        } else {
            $size_name = intval($width * 0.035);
        }

        $name_x = intval($width * 0.06); // Kiri 
        $name_y = intval($height * 0.87); // Bawah

        // Background putih untuk nama
        $name_bbox = imagettfbbox($size_name, 0, $font_for_name, $name_text);
        $name_w = $name_bbox[2] - $name_bbox[0];
        $name_h = $name_bbox[1] - $name_bbox[7];

        $bg_padding = 8;
        imagefilledrectangle(
            $canvas,
            $name_x - $bg_padding,
            $name_y - $name_h - $bg_padding,
            $name_x + $name_w + $bg_padding,
            $name_y + $bg_padding,
            $white
        );
        imagettftext($canvas, $size_name, 0, $name_x, $name_y, $black, $font_for_name, $name_text);

        // NISN di kanan atas area foto (sesuai gambar ekpd.php)
        $size_nisn = intval($width * 0.035); // Size yang cukup visible
        $nisn_x = intval($width * 0.75); // Kanan dari foto
        $nisn_y = intval($height * 0.43); // Sejajar dengan area tengah foto

        // Background putih untuk NISN
        $nisn_bbox = imagettfbbox($size_nisn, 0, $font_for_nisn, $nisn_text);
        $nisn_w = $nisn_bbox[2] - $nisn_bbox[0];
        $nisn_h = $nisn_bbox[1] - $nisn_bbox[7];

        $bg_padding = 8;
        imagefilledrectangle(
            $canvas,
            $nisn_x - $bg_padding,
            $nisn_y - $nisn_h - $bg_padding,
            $nisn_x + $nisn_w + $bg_padding,
            $nisn_y + $bg_padding,
            $white
        );
        imagettftext($canvas, $size_nisn, 0, $nisn_x, $nisn_y, $black, $font_for_nisn, $nisn_text);
    } else {
        // Fallback: imagestring
        imagestring($canvas, 5, intval($width * 0.06), intval($height * 0.85), $name_text, $black);
        imagestring($canvas, 4, intval($width * 0.75), intval($height * 0.4), $nisn_text, $black);
    }
} else {
    // belakang: just overlay background and maybe QR centered
    if (file_exists($qrcode_path)) {
        $qr_s = intval($width * 0.28);
        $qr_x = intval(($width - $qr_s) / 2);
        $qr_y = intval($height * 0.45);
        overlay_image($canvas, $qrcode_path, $qr_x, $qr_y, $qr_s, $qr_s, true);
    }
}

// Output PNG for download
$filename = 'kartu-pelajar-' . ($nisn ?: $user_id) . '-' . $side . '.png';
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
imagepng($canvas);
imagedestroy($canvas);
exit;
