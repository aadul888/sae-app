<?php
/**
 * GitHub Deploy Webhook
 * Terima notifikasi push dari GitHub, lalu jalankan deploy + migrasi DB.
 *
 * Cara pakai:
 * 1. Set webhook di repo GitHub → Settings → Webhooks → Add webhook
 *    - Payload URL: https://domain-anda.com/api/webhook.php
 *    - Content type: application/json
 *    - Secret: lihat library/github-config.php
 *    - Events: Pushes
 * 2. Secret sudah dikonfigurasi di library/github-config.php
 */

require_once __DIR__ . '/../library/config.php';
require_once __DIR__ . '/../library/migrate.php';
require_once __DIR__ . '/../library/commit_logger.php';
require_once __DIR__ . '/../library/version.php';

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

// Hash dari payload GitHub (source of truth — lebih akurat daripada baca .git/refs)
$payload_hash = $data['after'] ?? '';

$git_dir = realpath(__DIR__ . '/../');
$deployed = false;
$err = '';
$git_bin = 'git';
$git_safe = "$git_bin -c safe.directory='*'";

// Priority 1: git fetch + reset --hard via cl_exec
if ($git_dir) {
    chdir($git_dir);
    putenv('GIT_TERMINAL_PROMPT=0');
    // Backup library/config.php (DB credentials) sebelum reset
    $cfg_path = $git_dir . '/library/config.php';
    $cfg_backup = '';
    if (is_file($cfg_path)) {
        $cfg_backup = file_get_contents($cfg_path);
    }
    $fetch_result = cl_exec("$git_safe fetch origin main 2>&1");
    if ($fetch_result['code'] === 0) {
        $reset_result = cl_exec("$git_safe reset --hard origin/main 2>&1");
        if ($reset_result['code'] === 0) {
            $deployed = true;
        } else {
            $err = $reset_result['output'];
        }
    } else {
        $err = $fetch_result['output'];
    }
    // Restore config.php (DB credentials)
    if ($cfg_backup !== '' && is_file($cfg_path)) {
        file_put_contents($cfg_path, $cfg_backup);
    }
}

// Priority 2: ZIP download dari GitHub (sama seperti proses.php)
if (!$deployed) {
    // Fallback hash dari API jika payload kosong
    if (empty($payload_hash)) {
        $token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : '';
        $payload_hash = sae_fetch_remote_hash($token);
    }
    if (empty($payload_hash)) {
        $err = 'Tidak dapat menentukan hash remote (payload & API kosong)';
    } else {
        $token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : (defined('SAE_API_KEY') ? SAE_API_KEY : '');
        $target = $git_dir ?: realpath(__DIR__ . '/../');
        $zip_result = sae_deploy_from_zip($target, $token, $payload_hash);
        if ($zip_result['success']) {
            $deployed = true;
        } else {
            $err = implode('; ', array_filter($zip_result['log']));
        }
    }
}

// ============================================================
//  Deploy sukses — update DB & jalankan migrasi
// ============================================================
if ($deployed) {
    // Hash: payload (source of truth) → .git/refs fallback
    $deploy_hash = $payload_hash;
    if (empty($deploy_hash)) {
        $refs_file = $git_dir . '/.git/refs/heads/main';
        if (file_exists($refs_file)) {
            $deploy_hash = trim(file_get_contents($refs_file));
        }
    }

    // Update deploy stats + version in one query (sama seperti proses.php)
    $new_version = $deploy_hash ? sae_version_from_commit($connection, $deploy_hash) . ' [' . substr($deploy_hash, 0, 7) . ']' : '';
    $sql_update = "UPDATE setting SET
        last_deploy_at=NOW(),
        last_deploy_commit='" . $connection->real_escape_string($deploy_hash) . "',
        deploy_count=deploy_count+1,
        last_deploy_by='Webhook (auto)',
        last_deploy_status='success'";
    if ($new_version) {
        $sql_update .= ", app_version='" . $connection->real_escape_string($new_version) . "'";
    }
    $sql_update .= " WHERE site_id=1";
    $connection->query($sql_update);

    // Sinkronkan .git/refs/heads/main dengan payload hash
    $ref_dir_wh = $git_dir . '/.git/refs/heads';
    if (is_dir($ref_dir_wh) && !empty($deploy_hash)) {
        @file_put_contents($ref_dir_wh . '/main', $deploy_hash . "\n");
    }

    // Catat commit log
    save_commit_log($connection, 10);

    // Catat aktivitas deploy
    if (file_exists(__DIR__ . '/../library/activity.php')) {
        require_once __DIR__ . '/../library/activity.php';
        log_activity($connection, null, 'Webhook', 'deploy', 'Auto-deploy via webhook ke versi ' . ($new_version ?: '?'));
    }

    // Jalankan migrasi database yang tertunda
    $mig_result = run_pending_migrations($connection);
    $msg = 'Deploy berhasil. Versi: ' . ($new_version ?: '?');
    if ($mig_result['ran'] > 0) {
        $msg .= ' ' . $mig_result['ran'] . ' migrasi database dijalankan.';
    }
    if (!$mig_result['success']) {
        $msg .= ' Ada error migrasi: ' . implode('; ', $mig_result['errors'] ?? []);
    }
    echo json_encode(['success' => true, 'message' => $msg]);
} else {
    $msg = empty($err) ? 'Gagal deploy' : $err;
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $msg]);
}
