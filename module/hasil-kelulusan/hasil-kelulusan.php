<?php
if (empty($connection)) {
    echo '<div class="alert alert-danger text-center mt-4">Koneksi database belum tersedia.</div>';
    return;
}

require_once __DIR__ . '/../../library/kelulusan_helper.php';
kelulusan_ensure_tables($connection);
$settings = kelulusan_get_settings($connection);
$showSklToUser = (!isset($settings['show_skl_to_user']) || $settings['show_skl_to_user'] === 'Y');
$allowDownloadSkl = (!isset($settings['allow_download_skl']) || $settings['allow_download_skl'] === 'Y');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$nisn = isset($_GET['nisn']) ? trim($_GET['nisn']) : '';

$invalid = false;
$row = null;
$downloadUrl = '';
$status = 'BELUM_DIPUTUSKAN';
$statusLabel = '-';
$statusBadge = 'secondary';
$message = 'Data hasil kelulusan belum tersedia.';
$avatarUrl = '';
$sklNotice = '';

if ($token === '' || $nisn === '') {
    $invalid = true;
} else {
    $decoded = base64_decode($token, true);
    if (!$decoded || strpos($decoded, '|') === false) {
        $invalid = true;
    } else {
        $parts = explode('|', $decoded);
        if (count($parts) < 3) {
            $invalid = true;
        } else {
            $userId = (int) $parts[0];
            $signature = $parts[2];

            $kelasCol = kelulusan_user_kelas_column($connection);
            $q = "SELECT u.user_id, u.nisn, u.nama_lengkap, u.avatar, u.`" . $kelasCol . "` AS kelas_id, k.nama_kelas, ks.status, ks.catatan, skl.file_path, skl.is_visible_to_user
                  FROM user u
                  LEFT JOIN kelas k ON k.kelas_id=u.`" . $kelasCol . "`
                  LEFT JOIN kelulusan_status ks ON ks.user_id=u.user_id
                  LEFT JOIN kelulusan_skl skl ON skl.user_id=u.user_id
                  WHERE u.user_id='" . intval($userId) . "' AND u.nisn='" . $connection->real_escape_string($nisn) . "' LIMIT 1";
            $r = $connection->query($q);
            if (!$r || $r->num_rows === 0) {
                $invalid = true;
            } else {
                $row = $r->fetch_assoc();
                $validSignature = md5($row['nisn'] . APP_ENC_KEY);
                if (!hash_equals($validSignature, $signature)) {
                    $invalid = true;
                    $row = null;
                }
            }
        }
    }
}

if (!$invalid && $row) {
    $status = !empty($row['status']) ? $row['status'] : 'BELUM_DIPUTUSKAN';
    $statusLabel = kelulusan_status_label($status);
    $statusBadge = kelulusan_status_badge_class($status);

    $catatan = trim((string) ($row['catatan'] ?? ''));
    $massText = 'Keputusan masal: Lulus semua oleh admin.';
    if (strcasecmp($catatan, $massText) === 0) {
        $catatan = '';
    }

    if ($status === 'LULUS') {
        $message = $catatan !== '' ? $catatan : 'Selamat! Anda dinyatakan lulus. Semoga sukses di langkah pendidikan berikutnya.';
    } elseif ($status === 'LULUS_BERSYARAT') {
        $message = $catatan !== '' ? $catatan : 'Anda dinyatakan lulus bersyarat. Silakan penuhi persyaratan yang ditetapkan sekolah.';
    } elseif ($status === 'TIDAK_LULUS') {
        $message = $catatan !== '' ? $catatan : 'Tetap semangat. Silakan konsultasi dengan pihak sekolah untuk arahan selanjutnya.';
    } else {
        $message = $catatan !== '' ? $catatan : 'Hasil kelulusan Anda belum diputuskan oleh admin.';
    }

    if ($status === 'LULUS' && !empty($row['file_path'])) {
        $visiblePerUser = (!isset($row['is_visible_to_user']) || $row['is_visible_to_user'] === 'Y');
        $berkasValid = kelulusan_is_user_berkas_valid($connection, (int) $row['user_id']);
        if (!$showSklToUser || !$visiblePerUser) {
            $sklNotice = 'File SKL saat ini disembunyikan oleh admin.';
        } elseif (!$berkasValid) {
            $sklNotice = 'File SKL disembunyikan karena berkas administrasi Anda belum valid.';
        } elseif (!$allowDownloadSkl) {
            $sklNotice = 'Unduh SKL saat ini dinonaktifkan oleh admin.';
        } else {
            $downloadUrl = './module/kelulusan/proses.php?action=download&token=' . rawurlencode($token) . '&nisn=' . rawurlencode($row['nisn']);
        }
    }

    // Avatar resolution order: DB avatar -> avatar by NISN -> default avatar
    $basePrefix = isset($base_url) ? rtrim((string) $base_url, '/') . '/' : './';
    $avatarDefaultRel = 'content/avatar/avatar.jpg';
    $avatarDir = realpath(__DIR__ . '/../../content/avatar');
    $avatarUrl = $basePrefix . $avatarDefaultRel;

    $dbAvatar = isset($row['avatar']) ? trim((string) $row['avatar']) : '';
    if ($dbAvatar !== '' && strtolower($dbAvatar) !== 'avatar.jpg' && $avatarDir) {
        $dbAvatarClean = preg_replace('/\?.*/', '', $dbAvatar);
        $dbAvatarFile = $avatarDir . DIRECTORY_SEPARATOR . basename($dbAvatarClean);
        if (is_file($dbAvatarFile)) {
            $avatarUrl = $basePrefix . 'content/avatar/' . basename($dbAvatarClean) . '?v=' . @filemtime($dbAvatarFile);
        }
    }

    if ($avatarDir && strpos($avatarUrl, $avatarDefaultRel) !== false) {
        $nisnPng = $avatarDir . DIRECTORY_SEPARATOR . $row['nisn'] . '.png';
        $nisnJpg = $avatarDir . DIRECTORY_SEPARATOR . $row['nisn'] . '.jpg';
        if (is_file($nisnPng)) {
            $avatarUrl = $basePrefix . 'content/avatar/' . $row['nisn'] . '.png?v=' . @filemtime($nisnPng);
        } elseif (is_file($nisnJpg)) {
            $avatarUrl = $basePrefix . 'content/avatar/' . $row['nisn'] . '.jpg?v=' . @filemtime($nisnJpg);
        }
    }
}
?>

<div class="module-home-container kelulusan-hasil-page" data-status="<?php echo htmlspecialchars($status); ?>">
    <div id="celebration-layer" class="celebration-layer<?php echo ($status === 'LULUS' && !$invalid && $row) ? '' : ' d-none'; ?>" aria-hidden="true"></div>

    <div class="module-home-content">
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8 col-xl-6">
                    <div class="card border-0 shadow hasil-card">
                        <div class="card-body text-center p-4 p-md-5">
                            <?php if ($invalid || !$row) { ?>
                                <h4 class="mb-3 text-danger">Akses Hasil Tidak Valid</h4>
                                <p class="text-muted mb-4">Token hasil kelulusan tidak valid atau sudah kedaluwarsa.</p>
                                <a href="./kelulusan" class="btn btn-primary">Kembali ke Halaman Kelulusan</a>
                            <?php } else { ?>
                                <div class="hasil-envelope-wrap mb-4">
                                    <div class="hasil-envelope" id="hasil-envelope">
                                        <div class="hasil-envelope-front"></div>
                                        <div class="hasil-envelope-letter"></div>
                                    </div>
                                </div>

                                <div class="hasil-avatar-wrap mb-3">
                                    <img src="<?php echo htmlspecialchars($avatarUrl !== '' ? $avatarUrl : './content/avatar/avatar.jpg'); ?>" alt="Avatar <?php echo htmlspecialchars($row['nama_lengkap']); ?>" class="hasil-avatar" onerror="this.src='./content/avatar/avatar.jpg'">
                                </div>

                                <h3 class="mb-1"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h3>
                                <div class="text-muted mb-3">Kelas: <?php echo htmlspecialchars(!empty($row['nama_kelas']) ? $row['nama_kelas'] : '-'); ?></div>
                                <span class="badge badge-pill badge-<?php echo htmlspecialchars($statusBadge); ?> px-4 py-2 mb-3"><?php echo htmlspecialchars($statusLabel); ?></span>

                                <p class="hasil-message mb-4"><?php echo htmlspecialchars($message); ?></p>

                                <?php if ($downloadUrl !== '') { ?>
                                    <a href="<?php echo htmlspecialchars($downloadUrl); ?>" class="btn btn-success mb-2">
                                        <i class="fas fa-file-download mr-1"></i> Unduh Surat Keterangan Lulus
                                    </a>
                                <?php } ?>
                                <?php if ($sklNotice !== '') { ?>
                                    <div class="text-muted small mb-2"><?php echo htmlspecialchars($sklNotice); ?></div>
                                <?php } ?>

                                <div>
                                    <a href="./kelulusan" class="btn btn-outline-primary btn-sm mt-2">Kembali</a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.kelulusan-hasil-page {
    min-height: 100vh;
    position: relative;
}
.hasil-card {
    border-radius: 16px;
    position: relative;
    z-index: 3;
}
.hasil-message {
    color: #334155;
    line-height: 1.8;
    font-weight: 500;
}
.hasil-avatar-wrap {
    display: flex;
    justify-content: center;
}
.hasil-avatar {
    width: 92px;
    height: 92px;
    border-radius: 999px;
    object-fit: cover;
    border: 3px solid #dbeafe;
    box-shadow: 0 8px 22px rgba(30, 64, 175, 0.22);
}
.hasil-envelope-wrap {
    display: flex;
    justify-content: center;
}
.hasil-envelope {
    width: 170px;
    height: 122px;
    position: relative;
}
.hasil-envelope-front {
    position: absolute;
    inset: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, #0f4c81, #0f766e);
}
.hasil-envelope-front:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    margin: auto;
    width: 0;
    height: 0;
    border-left: 85px solid transparent;
    border-right: 85px solid transparent;
    border-top: 62px solid rgba(255, 255, 255, 0.24);
    transform-origin: top;
    transition: transform 0.8s ease;
}
.hasil-envelope-letter {
    position: absolute;
    left: 16px;
    right: 16px;
    bottom: 8px;
    height: 82px;
    border-radius: 8px;
    background: #f8fafc;
    transform: translateY(28px);
    transition: transform 0.8s ease;
}
.hasil-envelope.open .hasil-envelope-front:before {
    transform: rotateX(180deg);
}
.hasil-envelope.open .hasil-envelope-letter {
    transform: translateY(-6px);
}

.celebration-layer {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 2;
    overflow: hidden;
}
.confetti-piece {
    position: absolute;
    top: -16px;
    width: 10px;
    height: 16px;
    opacity: 0.9;
    animation: confettiFall linear forwards;
}
.firework {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    box-shadow: 0 0 0 0 rgba(255,255,255,0.6);
    animation: fireworkBurst 1s ease-out forwards;
}
@keyframes confettiFall {
    0% { transform: translateY(-10px) rotate(0deg); }
    100% { transform: translateY(105vh) rotate(720deg); }
}
@keyframes fireworkBurst {
    0% { transform: scale(0.2); opacity: 1; box-shadow: 0 0 0 0 currentColor; }
    100% { transform: scale(1.4); opacity: 0; box-shadow: 0 0 0 26px transparent; }
}
</style>
