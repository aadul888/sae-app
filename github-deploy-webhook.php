<?php
/**
 * GitHub Deploy Webhook
 * Terima notifikasi push dari GitHub, lalu jalankan deploy.
 *
 * Cara pakai:
 * 1. Set webhook di repo GitHub → Settings → Webhooks → Add webhook
 *    - Payload URL: https://domain-anda.com/github-deploy-webhook.php
 *    - Content type: application/json
 *    - Secret: isi GITHUB_WEBHOOK_SECRET di library/config.php
 *    - Events: Pushes
 *
 * Notes: File ini sengaja TIDAK require config.php karena webhook
 * tidak butuh koneksi database. Dua konstanta di-bootstrap manual.
 */

// Bootstrap minimal — hanya konstanta yang dibutuhkan webhook
// Token disimpan di file terpisah, dilindungi .htaccess
$githubConfig = __DIR__ . '/library/github-config.php';
if (file_exists($githubConfig)) {
    require_once $githubConfig;
} else {
    // Fallback inline (hanya jika file config belum ada)
    if (!defined('GITHUB_TOKEN')) define('GITHUB_TOKEN', '');
    if (!defined('GITHUB_WEBHOOK_SECRET')) define('GITHUB_WEBHOOK_SECRET', '');
}

header('Content-Type: application/json');

// Cuma terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verifikasi signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (defined('GITHUB_WEBHOOK_SECRET') && GITHUB_WEBHOOK_SECRET !== '') {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, GITHUB_WEBHOOK_SECRET);
    if (!hash_equals($expected, $signature)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid signature']);
        exit;
    }
}

$data = json_decode($payload, true);

// Cuma respon kalau push ke main
$ref = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main') {
    echo json_encode(['success' => true, 'message' => 'Bukan push ke main, diabaikan.']);
    exit;
}

$git_dir = realpath(__DIR__);
$output = [];
$return_var = -1;
$deployed = false;
$err = '';
$git_bin = '/usr/bin/git';
$git_safe = "$git_bin -c safe.directory='*'";

// Backup config files — jangan timpa
$backup_configs = [];
foreach (['library/config.php', 'library/github-config.php'] as $rel) {
    $abs = realpath($git_dir . '/' . $rel);
    if ($abs && file_exists($abs)) {
        $backup_configs[$rel] = file_get_contents($abs);
    }
}

// Priority 1: git pull
if ($git_dir && file_exists($git_bin)) {
    chdir($git_dir);
    putenv('GIT_TERMINAL_PROMPT=0');
    $cmd_fetch = "$git_safe fetch origin main 2>&1";
    exec($cmd_fetch, $output, $return_var);
    if ($return_var === 0) {
        $cmd_reset = "$git_safe reset --hard origin/main 2>&1";
        exec($cmd_reset, $output, $return_var);
        if ($return_var === 0) {
            // Restore config files
            foreach ($backup_configs as $rel => $content) {
                file_put_contents($git_dir . '/' . $rel, $content);
            }
            $deployed = true;
        } else {
            $err = implode("\n", $output);
        }
    } else {
        $err = implode("\n", $output);
    }
} else {
    // git_bin not found, fallback ke ZIP
}

// Priority 2: download zip dari GitHub
if (!$deployed) {
    $repo = 'aadul888/sae-app';
    $token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : (defined('SAE_API_KEY') ? SAE_API_KEY : '');
    $branch = 'main';
    $zip_url = "https://api.github.com/repos/$repo/zipball/$branch";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $zip_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'User-Agent: SAE-Deploy/1.0',
            $token ? "Authorization: Bearer $token" : '',
        ],
    ]);
    $zip_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if (!$curl_err && $http_code < 400) {
        $tmp_zip = sys_get_temp_dir() . '/sae-deploy-' . uniqid() . '.zip';
        file_put_contents($tmp_zip, $zip_data);

        $zip = new ZipArchive;
        if ($zip->open($tmp_zip) === true) {
            $tmp_extract = sys_get_temp_dir() . '/sae-extract-' . uniqid();
            $zip->extractTo($tmp_extract);
            $zip->close();

            $items = scandir($tmp_extract);
            $inner = null;
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir("$tmp_extract/$item")) {
                    $inner = "$tmp_extract/$item";
                    break;
                }
            }

            if ($inner) {
                $target = realpath(__DIR__);
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($inner, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $item) {
                    $dest = $target . '/' . $iterator->getSubPathname();
                    if ($item->isDir()) {
                        if (!is_dir($dest)) mkdir($dest, 0755, true);
                    } else {
                        copy($item, $dest);
                    }
                }
                $deployed = true;

                // Cleanup
                $rmdir = function($dir) use (&$rmdir) {
                    foreach (scandir($dir) as $f) {
                        if ($f === '.' || $f === '..') continue;
                        $p = "$dir/$f";
                        is_dir($p) ? $rmdir($p) : unlink($p);
                    }
                    rmdir($dir);
                };
                $rmdir($tmp_extract);
            }
            unlink($tmp_zip);
        }
    }
}

if ($deployed) {
    // Auto-run pending DB migrations — koneksi manual, tidak pakai config.php
    $mig_file = __DIR__ . '/library/migrate.php';
    if (file_exists($mig_file)) {
        try {
            // Baca kredensial DB dari config.php tanpa require penuh
            $db_host = 'localhost';
            $db_name = 'saev5';
            $db_user = 'root';
            $db_pass = '';
            // Override dari define jika sudah ada
            if (defined('DB_HOST')) $db_host = DB_HOST;
            if (defined('DB_NAME')) $db_name = DB_NAME;
            if (defined('DB_USER')) $db_user = DB_USER;
            if (defined('DB_PASSWD')) $db_pass = DB_PASSWD;
            // Atau dari file config kalau ada
            $cfg = __DIR__ . '/library/config.php';
            if (file_exists($cfg) && !defined('DB_HOST')) {
                $cfg_content = file_get_contents($cfg);
                $patterns = [
                    '/\$DB_HOST\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
                    '/\$DB_NAME\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
                    '/\$DB_USER\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
                    '/\$DB_PASSWD\s*=\s*[\'"]([^\'"]*)[\'"]\s*;/',
                ];
                $vars = ['db_host', 'db_name', 'db_user', 'db_pass'];
                foreach ($patterns as $i => $pat) {
                    if (preg_match($pat, $cfg_content, $m)) ${$vars[$i]} = $m[1];
                }
            }

            mysqli_report(MYSQLI_REPORT_OFF);
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if (!$conn->connect_error) {
                require_once $mig_file;
                $mig_result = run_pending_migrations($conn);

                // Catat commit log
                $log_file = __DIR__ . '/library/commit_logger.php';
                if (file_exists($log_file)) {
                    require_once $log_file;
                    save_commit_log($conn, 3);
                }

                // Simpan notifikasi deploy untuk alert dashboard
                $last_hash = '';
                $q_hash = $conn->query("SELECT commit_hash FROM commit_log ORDER BY created_at DESC LIMIT 1");
                if ($q_hash && $r_hash = $q_hash->fetch_assoc()) {
                    $last_hash = $conn->real_escape_string($r_hash['commit_hash']);
                }
                $conn->query("UPDATE setting SET last_deploy_at=NOW(), last_deploy_commit='$last_hash' WHERE site_id=1");

                $conn->close();
                $mig_msg = $mig_result['ran'] > 0 ? ' (' . $mig_result['ran'] . ' migrasi)' : '';
                $mig_err = !$mig_result['success'] ? ' Error: ' . implode('; ', $mig_result['errors']) : '';
                echo json_encode(['success' => true, 'message' => 'Deploy berhasil.' . $mig_msg . $mig_err]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Deploy berhasil. (migrasi DB dilewati: koneksi gagal)']);
            }
        } catch (Throwable $e) {
            echo json_encode(['success' => true, 'message' => 'Deploy berhasil. Migrasi DB gagal: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Deploy berhasil.']);
    }
} else {
    $msg = empty($err) ? 'Gagal deploy' : $err;
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $msg]);
}
