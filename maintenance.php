<?php
// File untuk mengecek status sistem secara real-time
session_start();
include_once 'library/config.php';
include_once 'library/function.php';

if (!function_exists('maintenance_sync_bootstrap_completed')) {
    function maintenance_sync_bootstrap_completed($connection)
    {
        $tables = [
            'getSekolah' => 'sync_sekolah',
            'getGtk' => 'sync_gtk',
            'getRombonganBelajar' => 'sync_rombongan_belajar',
            'getPesertaDidik' => 'sync_peserta_didik',
            'getPengguna' => 'sync_pengguna'
        ];
        foreach ($tables as $endpoint => $table_name) {
            $table_check = $connection->query("SHOW TABLES LIKE '" . $connection->real_escape_string($table_name) . "'");
            if (!$table_check || !$table_check->num_rows) {
                return false;
            }

            $row_check = $connection->query("SELECT 1 FROM `{$table_name}` LIMIT 1");
            if (!$row_check || !$row_check->num_rows) {
                return false;
            }

            $status_query = "SELECT 1 FROM sync_log WHERE endpoint='" . $connection->real_escape_string($endpoint) . "' AND status='success' LIMIT 1";
            $status_result = $connection->query($status_query);
            if (!$status_result || !$status_result->num_rows) {
                return false;
            }
        }

        return true;
    }
}

// Force no cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Debug: Get current system status from database
$debug_status = 'Unknown';
$debug_count = 0;
if($connection) {
    $debug_query = $connection->query("SELECT COUNT(*) as total, SUM(CASE WHEN aktif='Y' THEN 1 ELSE 0 END) as active FROM tahun_pelajaran");
    if($debug_query && $row_debug = $debug_query->fetch_assoc()) {
        $debug_count = intval($row_debug['total']);
        $debug_active = intval($row_debug['active']);
        $debug_status = "DB: {$debug_active} aktif dari {$debug_count} tahun pelajaran";
    }
}

// Jika sistem sudah aktif, redirect ke halaman utama
if ($connection && !maintenance_sync_bootstrap_completed($connection)) {
    sae_redirect_to_registrasi();
}

if (!isMaintenanceModeClosed($connection)) {
    header('Location: ./');
    exit;
}

if(isSystemActive()) {
    header('Location: ./');
    exit;
}

// Ambil informasi website
$website_name = 'Sistem Aplikasi';
$website_phone = '';
$website_email = '';
$website_address = '';
$microsite_url = 'https://s.id/smakpalapik';

if(isset($row_site)) {
    $website_name = htmlspecialchars($row_site['site_name']);
    $website_phone = htmlspecialchars($row_site['site_phone']);
    $website_email = htmlspecialchars($row_site['site_email']);
    $website_address = htmlspecialchars($row_site['site_address']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Sedang Maintenance - <?php echo $website_name; ?></title>
    <link rel="icon" type="image/png" href="content/favicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --c-primary: #2563eb;
            --c-primary-dark: #0f4c81;
            --c-primary-deep: #1e3a5f;
            --c-accent: #0ea5e9;
            --c-text: #0f172a;
            --c-body: #475569;
            --c-muted: #64748b;
            --c-border: rgba(148, 163, 184, 0.22);
            --shell-bg: rgba(255, 255, 255, 0.9);
            --surface: rgba(255, 255, 255, 0.94);
            --surface-strong: rgba(255, 255, 255, 0.98);
            --shadow-shell: 0 24px 48px rgba(15, 23, 42, 0.16);
            --gradient-hero: linear-gradient(135deg, #1e3a5f 0%, #2563eb 55%, #0ea5e9 100%);
            --gradient-soft: linear-gradient(180deg, rgba(248, 250, 252, 0.98) 0%, rgba(241, 245, 249, 0.92) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--c-body);
            background:
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.18), transparent 32%),
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 26%),
                linear-gradient(135deg, #dbeafe 0%, #c7d2fe 35%, #e9d5ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }

        .maintenance-scene {
            width: min(1120px, 100%);
        }

        .maintenance-shell {
            background: var(--shell-bg);
            border: 1px solid var(--c-border);
            border-radius: 32px;
            box-shadow: var(--shadow-shell);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            overflow: hidden;
        }

        .maintenance-grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            min-height: 680px;
        }

        .maintenance-hero {
            position: relative;
            padding: 3rem;
            background: var(--gradient-hero);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .maintenance-hero::before,
        .maintenance-hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            filter: blur(2px);
        }

        .maintenance-hero::before {
            width: 220px;
            height: 220px;
            top: -70px;
            right: -50px;
        }

        .maintenance-hero::after {
            width: 180px;
            height: 180px;
            bottom: 40px;
            left: -60px;
        }

        .maintenance-kicker {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            width: fit-content;
            margin-bottom: 1rem;
            padding: .5rem .95rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.16);
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .maintenance-brand {
            position: relative;
            z-index: 1;
        }

        .maintenance-brand h1 {
            margin-bottom: .9rem;
            font-size: clamp(2.3rem, 4vw, 3.5rem);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.03em;
        }

        .maintenance-brand p {
            max-width: 34rem;
            font-size: 1rem;
            line-height: 1.85;
            color: rgba(255,255,255,0.84);
        }

        .maintenance-highlights {
            position: relative;
            z-index: 1;
            display: grid;
            gap: .85rem;
            margin-top: 2rem;
        }

        .maintenance-highlight {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            padding: 1rem 1.1rem;
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.14);
        }

        .maintenance-highlight i {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.14);
            font-size: 1rem;
        }

        .maintenance-highlight strong {
            display: block;
            margin-bottom: .2rem;
            font-size: .95rem;
            font-weight: 700;
        }

        .maintenance-highlight span {
            display: block;
            color: rgba(255,255,255,0.82);
            line-height: 1.65;
            font-size: .92rem;
        }

        .maintenance-panel {
            padding: 2.1rem;
            background: var(--gradient-soft);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .maintenance-card {
            padding: 2rem;
            border-radius: 28px;
            background: var(--surface);
            border: 1px solid var(--c-border);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }

        .maintenance-panel-head {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.4rem;
        }

        .maintenance-icon {
            width: 74px;
            height: 74px;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37,99,235,0.14), rgba(14,165,233,0.18));
            color: var(--c-primary);
            font-size: 2rem;
            flex-shrink: 0;
        }

        .maintenance-title {
            margin: 0 0 .35rem;
            color: var(--c-text);
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .maintenance-subtitle {
            color: var(--c-muted);
            line-height: 1.75;
            margin: 0;
        }

        .status-card {
            margin: 1.5rem 0 1.2rem;
            padding: 1.35rem;
            border-radius: 24px;
            background: var(--gradient-hero);
            color: #fff;
            box-shadow: 0 18px 34px rgba(37, 99, 235, 0.22);
        }

        .status-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .spinner {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.24);
            border-top-color: #fff;
            animation: spin 1.1s linear infinite;
        }

        .status-card h5 {
            margin-bottom: .25rem;
            font-size: 1.12rem;
            font-weight: 800;
        }

        .status-card p {
            margin: 0;
            color: rgba(255,255,255,0.84);
        }

        .countdown {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.16);
            color: rgba(255,255,255,0.88);
            font-size: .96rem;
        }

        .countdown span {
            font-weight: 800;
            font-size: 1.15rem;
        }

        .maintenance-meta {
            display: grid;
            gap: .85rem;
            margin-bottom: 1.25rem;
        }

        .maintenance-actions {
            display: grid;
            gap: .85rem;
            margin-bottom: 1.25rem;
        }

        .maintenance-action-card {
            padding: 1.05rem 1.1rem;
            border-radius: 20px;
            background: rgba(37,99,235,0.08);
            border: 1px solid rgba(37,99,235,0.14);
        }

        .maintenance-action-card strong {
            display: block;
            margin-bottom: .2rem;
            color: var(--c-text);
            font-size: .95rem;
        }

        .maintenance-action-card span {
            display: block;
            color: var(--c-muted);
            line-height: 1.65;
            font-size: .9rem;
        }

        .maintenance-action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            width: 100%;
            min-height: 52px;
            margin-top: .9rem;
            padding: .9rem 1.2rem;
            border-radius: 16px;
            background: var(--gradient-hero);
            color: #fff;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 16px 30px rgba(37, 99, 235, 0.18);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .maintenance-action-link:hover {
            color: #fff;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 20px 36px rgba(37, 99, 235, 0.24);
        }

        .maintenance-meta-item {
            padding: 1rem 1.05rem;
            border-radius: 18px;
            background: var(--surface-strong);
            border: 1px solid rgba(148,163,184,0.18);
        }

        .maintenance-meta-item strong {
            display: block;
            margin-bottom: .2rem;
            color: var(--c-text);
            font-size: .93rem;
        }

        .maintenance-meta-item span {
            display: block;
            color: var(--c-muted);
            line-height: 1.65;
            font-size: .9rem;
        }

        .alert-custom {
            padding: 1rem 1.1rem;
            border-radius: 18px;
            background: rgba(37,99,235,0.08);
            border: 1px solid rgba(37,99,235,0.14);
            color: var(--c-text);
            line-height: 1.7;
        }

        .alert-custom i {
            color: var(--c-primary);
            margin-right: .45rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 991.98px) {
            .maintenance-grid {
                grid-template-columns: 1fr;
            }

            .maintenance-hero,
            .maintenance-panel {
                padding: 1.5rem;
            }
        }

        @media (max-width: 575.98px) {
            body {
                padding: .75rem;
            }

            .maintenance-shell,
            .maintenance-card {
                border-radius: 24px;
            }

            .maintenance-hero,
            .maintenance-panel,
            .maintenance-card {
                padding: 1.15rem;
            }

            .maintenance-panel-head {
                align-items: flex-start;
            }

            .maintenance-icon {
                width: 62px;
                height: 62px;
                border-radius: 20px;
                font-size: 1.7rem;
            }

            .maintenance-title {
                font-size: 1.7rem;
            }

            .status-row {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-scene">
        <div class="maintenance-shell">
            <div class="maintenance-grid">
                <section class="maintenance-hero">
                    <div class="maintenance-brand">
                        <span class="maintenance-kicker"><i class="fas fa-shield-alt"></i> Sistem Dipelihara</span>
                        <h1>Layanan publik sementara dijeda agar sistem kembali stabil.</h1>
                        <p><?php echo $website_name; ?> sedang menjalankan pemeliharaan untuk pengguna non-admin seperti guru, siswa, orang tua, dan masyarakat umum agar akses informasi sekolah kembali berjalan lebih rapi, aman, dan konsisten.</p>
                    </div>

                    <div class="maintenance-highlights">
                        <div class="maintenance-highlight">
                            <i class="fas fa-users"></i>
                            <div>
                                <strong>Ditujukan untuk pengguna publik</strong>
                                <span>Halaman ini muncul saat layanan utama untuk guru, siswa, dan pengunjung umum sedang dibatasi sementara selama proses pemeliharaan.</span>
                            </div>
                        </div>
                        <div class="maintenance-highlight">
                            <i class="fas fa-sync-alt"></i>
                            <div>
                                <strong>Pemeriksaan berkala</strong>
                                <span>Halaman ini akan mengecek status otomatis dan kembali aktif tanpa perlu Anda refresh manual.</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="maintenance-panel">
                    <div class="maintenance-card">
                        <div class="maintenance-panel-head">
                            <div class="maintenance-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div>
                                <h2 class="maintenance-title">Mode Maintenance</h2>
                                <p class="maintenance-subtitle">Akses halaman utama sedang dibatasi sementara sampai layanan publik aktif kembali.</p>
                            </div>
                        </div>

                        <div class="status-card">
                            <div class="status-row">
                                <div class="spinner"></div>
                                <div>
                                    <h5><i class="fas fa-exclamation-circle"></i> Layanan Publik Belum Aktif</h5>
                                    <p>Portal untuk guru, siswa, dan pengunjung umum masih dalam proses pemeliharaan.</p>
                                </div>
                            </div>
                            <div class="countdown">
                                Pemeriksaan status berikutnya dalam <span id="countdown">30</span> detik
                            </div>
                        </div>

                        <div class="maintenance-actions">
                            <div class="maintenance-action-card">
                                <strong>Microsite sekolah tetap tersedia</strong>
                                <span>Buka microsite sekolah untuk melihat media sosial, kontak, dan kanal informasi lain selama layanan utama masih maintenance.</span>
                                <a class="maintenance-action-link" href="<?php echo htmlspecialchars($microsite_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-globe-asia"></i>
                                    Buka Microsite Sekolah
                                </a>
                            </div>
                        </div>

                        <div class="alert-custom">
                            <i class="fas fa-info-circle"></i>
                            <strong>Informasi:</strong> Jika Anda membutuhkan informasi sekolah, kontak, atau media sosial selama maintenance, gunakan microsite di <strong>s.id/smakpalapik</strong> sambil menunggu layanan utama aktif kembali.
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <script>
        // Simple auto-refresh counter setiap 30 detik
        let countdownValue = 30;
        const countdownEl = document.getElementById('countdown');
        
        setInterval(function() {
            countdownValue--;
            if(countdownValue <= 0) {
                location.reload();
                countdownValue = 30;
            }
            if(countdownEl) {
                countdownEl.innerText = countdownValue;
            }
        }, 1000);
    </script>
</body>
</html>