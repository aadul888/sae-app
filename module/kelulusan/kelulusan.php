<?php
if (empty($connection)) {
    echo '<div class="alert alert-danger text-center mt-4">Koneksi database belum tersedia.</div>';
    return;
}

require_once __DIR__ . '/../../library/kelulusan_helper.php';
kelulusan_ensure_tables($connection);
$settings = kelulusan_get_settings($connection);
$isOpen = kelulusan_is_open_now($settings);
$announcement = !empty($settings['announcement_text']) ? strip_tags($settings['announcement_text']) : 'Masukkan NISN dan tanggal lahir untuk membuka amplop kelulusan.';
$announcement = trim((string) $announcement);
$hasAnnouncement = ($announcement !== '');
$targetCountdown = !empty($settings['countdown_to']) ? $settings['countdown_to'] : (!empty($settings['open_at']) ? $settings['open_at'] : '');

?>

<div class="module-home-container kelulusan-page" data-open="<?php echo $isOpen ? 'Y' : 'N'; ?>" data-countdown="<?php echo htmlspecialchars($targetCountdown); ?>">
    <div class="module-home-content">
        <div class="sae-landing kelulusan-landing">
            <section class="sae-hero kelulusan-hero" aria-label="Pengumuman kelulusan">
                <div class="sae-hero-bg" aria-hidden="true"></div>
                <div class="sae-hero-inner">
                    <div class="sae-hero-copy">
                        <span class="sae-hero-kicker"><i class="fas fa-circle" aria-hidden="true"></i> Pengumuman Resmi</span>
                        <h1 class="sae-hero-title">Informasi <span class="sae-hero-accent">Kelulusan</span></h1>
                        <p class="sae-hero-subtitle">Halaman resmi untuk membuka amplop kelulusan siswa sesuai jadwal rilis yang ditetapkan sekolah.</p>
                        <div class="sae-tech-strip">
                            <span class="sae-tech-badge"><i class="fas fa-user-graduate"></i> Verifikasi Siswa</span>
                            <span class="sae-tech-badge"><i class="fas fa-envelope-open-text"></i> Amplop Digital</span>
                            <span class="sae-tech-badge"><i class="fas fa-clock"></i> Countdown Rilis</span>
                        </div>
                    </div>
                    <div class="sae-hero-right kelulusan-hero-right">
                        <div class="kelulusan-hero-panel card">
                            <div class="kelulusan-hero-panel-head">
                                <div>
                                    <h6 class="mb-1">Status Layanan</h6>
                                    <p class="mb-0"><?php echo $isOpen ? 'Amplop kelulusan sedang dibuka untuk peserta didik.' : 'Amplop kelulusan belum dibuka dan menunggu jadwal resmi.'; ?></p>
                                </div>
                            </div>
                            <div class="kelulusan-hero-panel-body">
                                <div class="kelulusan-status-chip <?php echo $isOpen ? 'is-open' : 'is-closed'; ?>">
                                    <i class="fas <?php echo $isOpen ? 'fa-check-circle' : 'fa-lock'; ?> me-2"></i><?php echo $isOpen ? 'Layanan Dibuka' : 'Menunggu Pembukaan'; ?>
                                </div>
                                <p class="mb-0 announcement-text"><?php echo $hasAnnouncement ? nl2br(htmlspecialchars($announcement)) : 'Belum ada pengumuman tambahan dari admin.'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="glass-card card kelulusan-shell">
                <div class="card-body">
                    <div class="kelulusan-shell-inner">
                    <div class="card border-0 shadow mb-4 kelulusan-announcement-card">
                        <div class="card-header">
                            <h6 class="chart-title mb-0"><i class="fas fa-bullhorn mr-2"></i>Pengumuman</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($hasAnnouncement) { ?>
                                <p class="mb-0 announcement-text"><?php echo nl2br(htmlspecialchars($announcement)); ?></p>
                            <?php } else { ?>
                                <p class="mb-0 text-muted">Belum ada pengumuman.</p>
                            <?php } ?>
                        </div>
                    </div>

                    <?php if (!$isOpen) { ?>
                        <div class="kelulusan-closed-focus card border-0 shadow-lg">
                            <div class="card-body text-center py-5 px-3 px-md-5">
                                <h3 class="closed-title mb-3">Amplop Kelulusan Belum Dibuka</h3>
                                <p class="closed-sub mb-4">Pantau hitung mundur berikut untuk waktu rilis resmi.</p>
                                <div class="timer-mega" id="kelulusan-countdown" data-target="<?php echo htmlspecialchars($targetCountdown); ?>">
                                    <div class="timer-segment">
                                        <span class="timer-value" id="timer-days">00</span>
                                        <span class="timer-label">Hari</span>
                                    </div>
                                    <div class="timer-segment">
                                        <span class="timer-value" id="timer-hours">00</span>
                                        <span class="timer-label">Jam</span>
                                    </div>
                                    <div class="timer-segment">
                                        <span class="timer-value" id="timer-minutes">00</span>
                                        <span class="timer-label">Menit</span>
                                    </div>
                                    <div class="timer-segment">
                                        <span class="timer-value" id="timer-seconds">00</span>
                                        <span class="timer-label">Detik</span>
                                    </div>
                                </div>
                                <div class="timer-note mt-4" id="kelulusan-countdown-note">Menunggu jadwal rilis dari admin.</div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="kelulusan-open-panel card border-0 shadow mb-4">
                            <div class="card-header">
                                <h6 class="chart-title mb-0"><i class="fas fa-user-shield mr-2"></i>Verifikasi Peserta Didik</h6>
                            </div>
                            <div class="card-body p-4">
                                <form id="kelulusan-form" autocomplete="off">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>NISN</label>
                                            <input type="text" class="form-control" name="nisn" id="nisn" maxlength="10" minlength="10" pattern="[0-9]{10}" required placeholder="Contoh: 0123456789" inputmode="numeric" autocomplete="off">
                                            <small class="text-muted">NISN wajib tepat 10 digit angka.</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Tanggal Lahir</label>
                                            <input type="text" class="form-control" name="tanggal_lahir" id="tanggal_lahir" required placeholder="ddmmyyyy" inputmode="numeric" maxlength="8" autocomplete="off">
                                            <small class="text-muted">Gunakan format Indonesia tanpa pemisah: hari-bulan-tahun (contoh: 31012007).</small>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block btn-open-envelope">
                                        <i class="fas fa-envelope-open-text mr-1"></i> Buka Amplop Kelulusan
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<div id="kelulusan-loading" class="kelulusan-loading d-none" aria-hidden="true">
    <div class="kelulusan-loading-backdrop"></div>
    <div class="kelulusan-loading-content">
        <div class="loading-envelope" aria-hidden="true">
            <div class="loading-envelope-back"></div>
            <div class="loading-envelope-flap"></div>
            <div class="loading-envelope-paper"></div>
        </div>
        <h4 class="loading-title">Membuka Amplop Kelulusan</h4>
        <p id="loading-stage" class="loading-stage">Memverifikasi data peserta didik...</p>
        <div class="loading-bar">
            <span></span>
        </div>
    </div>
</div>

<style>
.kelulusan-page {
    min-height: 100vh;
}
.kelulusan-shell-inner {
    display: grid;
    gap: 1.1rem;
}
.kelulusan-announcement-card,
.kelulusan-open-panel,
.kelulusan-result,
.kelulusan-closed-focus {
    border-radius: 18px;
    border: 1px solid var(--sae-auth-shell-border);
    background: var(--sae-auth-surface);
    box-shadow: var(--sae-auth-shell-shadow);
    overflow: hidden;
}
.kelulusan-announcement-card .card-header,
.kelulusan-open-panel .card-header,
.kelulusan-result .card-header {
    background: var(--sae-auth-surface-soft);
    border-bottom: 1px solid var(--sae-auth-shell-border);
}
.kelulusan-announcement-card .chart-title,
.kelulusan-open-panel .chart-title,
.kelulusan-result .chart-title {
    color: var(--c-text);
    font-weight: 800;
}
.kelulusan-announcement-card .chart-title i,
.kelulusan-open-panel .chart-title i,
.kelulusan-result .chart-title i {
    color: var(--c-primary);
}
.kelulusan-announcement-card .card-body,
.kelulusan-open-panel .card-body,
.kelulusan-result .card-body {
    background: var(--sae-auth-surface);
}
.kelulusan-announcement-card .card-body {
    padding: 1.15rem 1.2rem;
}
.announcement-text {
    color: var(--c-body);
    line-height: 1.72;
    font-weight: 500;
}
.btn-open-envelope {
    font-weight: 800;
    border-radius: 14px;
    min-height: 52px;
    box-shadow: var(--sae-auth-accent-shadow);
}
.kelulusan-closed-focus {
    border-radius: 22px;
    background: linear-gradient(180deg, var(--sae-auth-surface) 0%, var(--sae-auth-surface-strong) 100%);
}
.kelulusan-closed-focus .card-body {
    background:
        radial-gradient(circle at top center, rgba(96,165,250,.10), transparent 38%),
        linear-gradient(180deg, rgba(255,255,255,.92) 0%, rgba(248,250,252,.98) 100%);
}
.closed-title {
    font-weight: 800;
    color: var(--c-text);
    font-size: clamp(1.7rem, 4vw, 2.3rem);
    letter-spacing: -.02em;
}
.closed-sub {
    color: var(--c-muted);
    max-width: 46ch;
    margin-left: auto;
    margin-right: auto;
}
.timer-mega {
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: 1rem;
    max-width: 760px;
    margin: 0 auto;
}
.timer-segment {
    background: linear-gradient(135deg, #0f4c81 0%, #14b8a6 100%);
    border-radius: 18px;
    padding: 1rem .7rem;
    color: #fff;
    border: 1px solid rgba(255,255,255,.16);
    box-shadow: 0 18px 32px rgba(15,76,129,.22);
    animation: pulseSegment 2s ease-in-out infinite;
}
.timer-value {
    display: block;
    font-size: clamp(2rem, 4vw, 2.65rem);
    font-weight: 800;
    letter-spacing: 1px;
    line-height: 1.1;
}
.timer-label {
    display: block;
    font-size: .88rem;
    text-transform: uppercase;
    letter-spacing: .8px;
    opacity: .95;
}
.timer-note {
    color: var(--c-body);
    font-weight: 600;
}
@keyframes pulseSegment {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.envelope-wrap {
    display: flex;
    justify-content: center;
}
.envelope {
    width: 150px;
    height: 110px;
    position: relative;
    transform-style: preserve-3d;
}
.envelope-front {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #0f4c81, #0f766e);
    border-radius: 10px;
}
.envelope-front:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    margin: auto;
    width: 0;
    height: 0;
    border-left: 75px solid transparent;
    border-right: 75px solid transparent;
    border-top: 56px solid rgba(255, 255, 255, 0.25);
    transform-origin: top;
    transition: transform 0.8s ease;
}
.envelope-letter {
    position: absolute;
    left: 12px;
    right: 12px;
    bottom: 8px;
    height: 72px;
    background: var(--sae-auth-surface-strong);
    border-radius: 6px;
    transform: translateY(32px);
    transition: transform 0.8s ease;
}
.envelope.open .envelope-front:before {
    transform: rotateX(180deg);
}
.envelope.open .envelope-letter {
    transform: translateY(-6px);
}

.kelulusan-loading {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.kelulusan-loading-backdrop {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(30, 58, 138, 0.8));
    backdrop-filter: blur(5px);
}
.kelulusan-loading-content {
    position: relative;
    width: min(92vw, 520px);
    text-align: center;
    color: #fff;
    padding: 24px 20px;
    border-radius: 16px;
    background: rgba(15, 23, 42, 0.68);
    border: 1px solid rgba(148, 163, 184, 0.35);
    box-shadow: 0 22px 45px rgba(2, 6, 23, 0.45);
}
.loading-envelope {
    width: 170px;
    height: 120px;
    margin: 0 auto 14px;
    position: relative;
}
.loading-envelope-back {
    position: absolute;
    inset: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, #0f4c81, #2563eb);
}
.loading-envelope-flap {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    width: 0;
    height: 0;
    margin: auto;
    border-left: 85px solid transparent;
    border-right: 85px solid transparent;
    border-top: 60px solid #3b82f6;
    transform-origin: top;
    animation: loadingFlap 1.5s ease-in-out infinite;
}
.loading-envelope-paper {
    position: absolute;
    left: 18px;
    right: 18px;
    bottom: 8px;
    height: 78px;
    border-radius: 8px;
    background: #e2e8f0;
    animation: loadingPaper 1.5s ease-in-out infinite;
}
.loading-title {
    font-weight: 800;
    margin-bottom: 8px;
    color: #e2e8f0;
    text-shadow: 0 2px 8px rgba(15, 23, 42, 0.7);
}
.loading-stage {
    min-height: 26px;
    color: #dbeafe;
    margin-bottom: 12px;
    font-weight: 500;
}
.loading-bar {
    height: 7px;
    border-radius: 999px;
    background: rgba(100, 116, 139, 0.35);
    overflow: hidden;
}
.loading-bar span {
    display: block;
    width: 45%;
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #14b8a6);
    border-radius: 999px;
    animation: loadingBar 1.6s linear infinite;
}
.kelulusan-open-panel label {
    color: var(--c-text);
    font-weight: 700;
    font-size: .88rem;
}
.kelulusan-open-panel .text-muted,
.kelulusan-announcement-card .text-muted {
    color: var(--c-muted) !important;
}

[data-theme="dark"] .kelulusan-announcement-card,
[data-theme="dark"] .kelulusan-open-panel,
[data-theme="dark"] .kelulusan-result,
[data-theme="dark"] .kelulusan-closed-focus {
    background: rgba(15,23,42,.90);
}

[data-theme="dark"] .kelulusan-closed-focus .card-body {
    background:
        radial-gradient(circle at top center, rgba(59,130,246,.16), transparent 38%),
        linear-gradient(180deg, rgba(15,23,42,.94) 0%, rgba(30,41,59,.96) 100%);
}

[data-theme="dark"] .timer-segment {
    background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%);
    box-shadow: 0 18px 36px rgba(2,6,23,.34);
}

[data-theme="dark"] .timer-note,
[data-theme="dark"] .announcement-text {
    color: #cbd5e1;
}

[data-theme="dark"] .closed-sub {
    color: #94a3b8;
}

@keyframes loadingFlap {
    0%, 100% { transform: rotateX(0deg); }
    50% { transform: rotateX(165deg); }
}
@keyframes loadingPaper {
    0%, 100% { transform: translateY(20px); }
    50% { transform: translateY(-6px); }
}
@keyframes loadingBar {
    0% { transform: translateX(-130%); }
    100% { transform: translateX(270%); }
}

@media (max-width: 768px) {
    .closed-title {
        font-size: 1.55rem;
    }
    .timer-mega {
        grid-template-columns: repeat(2, minmax(120px, 1fr));
    }
    .timer-value {
        font-size: 1.8rem;
    }
}
</style>
