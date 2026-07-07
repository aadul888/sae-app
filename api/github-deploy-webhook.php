<?php
/**
 * GitHub Deploy Webhook
 * Terima notifikasi push dari GitHub, lalu jalankan deploy + migrasi DB.
 *
 * Cara pakai:
 * 1. Set webhook di repo GitHub → Settings → Webhooks → Add webhook
 *    - Payload URL: https://domain-anda.com/api/github-deploy-webhook.php
 *    - Content type: application/json
 *    - Secret: (isi string random, lalu set GITHUB_WEBHOOK_SECRET di config)
 *    - Events: Pushes
 * 2. Tambahkan define('GITHUB_WEBHOOK_SECRET', 'your-secret') di library/config.php
 */

require_once __DIR__ . '/../library/config.php';
require_once __DIR__ . '/../library/migrate.php';

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

if (defined('GITHUB_WEBHOOK_SECRET')) {
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

$git_dir = realpath(__DIR__ . '/../');
$output = [];
$return_var = -1;
$deployed = false;
$err = '';

// Priority 1: git pull
if ($git_dir) {
    chdir($git_dir);
    exec('git fetch origin main 2>&1', $output, $return_var);
    if ($return_var === 0) {
        exec('git reset --hard origin/main 2>&1', $output, $return_var);
        if ($return_var === 0) {
            $deployed = true;
        } else {
            $err = implode("\n", $output);
        }
    } else {
        $err = implode("\n", $output);
    }
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
                $target = realpath(__DIR__ . '/../');
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
    // Jalankan migrasi database yang tertunda
    $mig_result = run_pending_migrations($connection);
    $msg = 'Deploy berhasil.';
    if ($mig_result['ran'] > 0) {
        $msg .= ' ' . $mig_result['ran'] . ' migrasi database dijalankan.';
    }
    if (!$mig_result['success']) {
        $msg .= ' Ada error migrasi: ' . implode('; ', $mig_result['errors']);
    }
    echo json_encode(['success' => true, 'message' => $msg]);
} else {
    $msg = empty($err) ? 'Gagal deploy' : $err;
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $msg]);
}
