<?php
/**
 * proses.php — Pembaruan sistem async.
 * Menghindari request deploy panjang yang mudah terkena 504 di proxy/Cloudflare.
 */

if (PHP_SAPI === 'cli' && isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
  foreach (array_slice($_SERVER['argv'], 1) as $arg) {
    if (strpos($arg, '=') === false) {
      continue;
    }
    [$key, $value] = explode('=', $arg, 2);
    $_GET[$key] = $value;
  }
}

if (PHP_SAPI !== 'cli') {
  session_start();
}
require_once __DIR__ . '/../../../library/config.php';
require_once __DIR__ . '/../../../library/function.php';
require_once __DIR__ . '/../../../library/commit_logger.php';

$action = $_GET['action'] ?? '';
$git_dir = realpath(__DIR__ . '/../../../');
$state_file = $git_dir ? ($git_dir . '/content/cache/update-job-state.json') : '';
$lock_file = $git_dir ? ($git_dir . '/content/cache/update-job.lock') : '';

function update_job_lock_payload($job_key)
{
  return json_encode([
    'job_key' => $job_key,
    'created_at' => date('c'),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function update_job_read_lock($lock_file)
{
  if (!$lock_file || !is_file($lock_file)) {
    return [];
  }

  $raw = @file_get_contents($lock_file);
  if ($raw === false || trim($raw) === '') {
    return [];
  }

  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

function update_job_is_internal_run($action, $lock_file)
{
  if ($action !== 'deploy_run') {
    return false;
  }

  $lock = update_job_read_lock($lock_file);
  $job_key = $_GET['job_key'] ?? '';
  return !empty($job_key) && !empty($lock['job_key']) && hash_equals((string) $lock['job_key'], (string) $job_key);
}

$is_internal_run = update_job_is_internal_run($action, $lock_file);

if (!$is_internal_run) {
  $csrf = $_GET['csrf'] ?? '';
  if (empty($csrf) || $csrf !== ($_SESSION['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token tidak valid']);
    exit;
  }
}

header('Content-Type: application/json');

$modul_id = 40;
$has_access = false;
$data_role = ['modifikasi' => 'N'];
if (!$is_internal_run) {
  include __DIR__ . '/../check_role.php';
  if (!$has_access) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
  }
} else {
  $has_access = true;
  $data_role['modifikasi'] = 'Y';
}

function update_job_default_state()
{
  return [
    'status' => 'idle',
    'started_at' => null,
    'finished_at' => null,
    'message' => '',
    'log' => [],
    'success' => null,
    'local_version' => null,
    'remote_version' => null,
    'deploy_hash' => '',
    'errors' => [],
  ];
}

function update_job_read_state($state_file)
{
  if (!$state_file || !is_file($state_file)) {
    return update_job_default_state();
  }

  $raw = @file_get_contents($state_file);
  if ($raw === false || $raw === '') {
    return update_job_default_state();
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    return update_job_default_state();
  }

  return array_merge(update_job_default_state(), $decoded);
}

function update_job_write_state($state_file, array $state)
{
  if (!$state_file) {
    return false;
  }

  $dir = dirname($state_file);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }

  $encoded = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  return @file_put_contents($state_file, $encoded, LOCK_EX) !== false;
}

function update_job_append_log($state_file, $message)
{
  $state = update_job_read_state($state_file);
  $state['log'][] = '[' . date('H:i:s') . '] ' . trim((string) $message);
  if (count($state['log']) > 400) {
    $state['log'] = array_slice($state['log'], -400);
  }
  update_job_write_state($state_file, $state);
}

function update_job_set_state($state_file, array $patch)
{
  $state = update_job_read_state($state_file);
  foreach ($patch as $key => $value) {
    $state[$key] = $value;
  }
  update_job_write_state($state_file, $state);
}

function update_job_get_local_hash($git_dir, $connection)
{
  $local_hash = '';
  if ($git_dir) {
    $git_head = $git_dir . '/.git/HEAD';
    if (is_file($git_head)) {
      $head_value = trim((string) @file_get_contents($git_head));
      if (preg_match('/^[a-f0-9]{40}$/i', $head_value)) {
        $local_hash = strtolower($head_value);
      } elseif (strpos($head_value, 'ref: ') === 0) {
        $head_ref = trim(substr($head_value, 5));
        $head_ref_file = $git_dir . '/.git/' . ltrim($head_ref, '/');
        if (is_file($head_ref_file)) {
          $local_hash = trim((string) @file_get_contents($head_ref_file));
        }
      }
    }

    foreach (['.git/refs/heads/main', '.git/refs/remotes/origin/main'] as $ref) {
      if ($local_hash !== '' || !is_file($git_dir . '/' . $ref)) {
        continue;
      }
      $candidate = trim((string) @file_get_contents($git_dir . '/' . $ref));
      if (preg_match('/^[a-f0-9]{40}$/i', $candidate)) {
        $local_hash = strtolower($candidate);
      }
    }

    if ($local_hash === '' && is_file($git_dir . '/.git/packed-refs')) {
      $content = (string) @file_get_contents($git_dir . '/.git/packed-refs');
      if (preg_match('/^([a-f0-9]{40}) refs\/(?:heads|remotes\/origin)\/main$/m', $content, $m)) {
        $local_hash = strtolower($m[1]);
      }
    }
  }

  if ((empty($local_hash) || strlen($local_hash) !== 40) && isset($connection) && $connection) {
    $q_lh = $connection->query("SELECT last_deploy_commit FROM setting WHERE site_id=1 LIMIT 1");
    if ($q_lh && $q_lh->num_rows) {
      $db_hash = trim($q_lh->fetch_row()[0] ?? '');
      if (!empty($db_hash) && strlen($db_hash) === 40) {
        $local_hash = strtolower($db_hash);
      }
    }
  }

  return $local_hash;
}

function update_job_launch_background_runner($script_path, $job_key, &$launch_log = [])
{
  $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
  $php_binary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
  $php_binary_escaped = escapeshellarg($php_binary);
  $script_escaped = escapeshellarg($script_path);
  $action_arg = escapeshellarg('action=deploy_run');
  $job_key_arg = escapeshellarg('job_key=' . $job_key);

  if (stripos(PHP_OS, 'WIN') === 0) {
    $command = 'start /B "sae-update" ' . $php_binary_escaped . ' ' . $script_escaped . ' ' . $action_arg . ' ' . $job_key_arg;
    if (function_exists('pclose') && function_exists('popen') && !in_array('popen', $disabled) && !in_array('pclose', $disabled)) {
      $handle = @popen($command, 'r');
      if (is_resource($handle)) {
        @pclose($handle);
        $launch_log[] = 'Background runner: popen(start /B)';
        return true;
      }
    }
  } else {
    $command = 'nohup ' . $php_binary_escaped . ' ' . $script_escaped . ' ' . $action_arg . ' ' . $job_key_arg . ' > /dev/null 2>&1 &';
    if (function_exists('exec') && !in_array('exec', $disabled)) {
      $out = [];
      $code = -1;
      @exec($command, $out, $code);
      if ($code === 0) {
        $launch_log[] = 'Background runner: exec(nohup)';
        return true;
      }
      $launch_log[] = 'Launch exec gagal: exit=' . $code;
    }
    if (function_exists('proc_open') && !in_array('proc_open', $disabled)) {
      $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']], $pipes);
      if (is_resource($process)) {
        if (isset($pipes[0]) && is_resource($pipes[0])) {
          fclose($pipes[0]);
        }
        @proc_close($process);
        $launch_log[] = 'Background runner: proc_open(nohup)';
        return true;
      }
    }
  }

  $launch_log[] = 'Background runner tidak tersedia, fallback ke mode inline.';
  return false;
}

function update_job_launch_http_runner($job_key, &$launch_log = [])
{
  if (PHP_SAPI === 'cli') {
    return false;
  }

  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
  if ($host === '' || $script_name === '') {
    $launch_log[] = 'Loopback HTTP tidak dapat dibuat karena host/script kosong.';
    return false;
  }

  $url = $scheme . '://' . $host . $script_name . '?action=deploy_run&job_key=' . rawurlencode($job_key);

  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => false,
      CURLOPT_HEADER => false,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_TIMEOUT_MS => 800,
      CURLOPT_CONNECTTIMEOUT_MS => 800,
      CURLOPT_NOSIGNAL => true,
      CURLOPT_HTTPHEADER => ['Connection: Close'],
    ]);
    @curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno === 0 || $errno === 28) {
      $launch_log[] = 'Background runner: loopback cURL';
      return true;
    }
    $launch_log[] = 'Loopback cURL gagal: errno=' . $errno;
  }

  if (function_exists('fsockopen')) {
    $transport = $scheme === 'https' ? 'ssl://' : '';
    $port = $scheme === 'https' ? 443 : 80;
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($transport . $host, $port, $errno, $errstr, 1.0);
    if ($socket) {
      $path = $script_name . '?action=deploy_run&job_key=' . rawurlencode($job_key);
      $request = "GET " . $path . " HTTP/1.1\r\n";
      $request .= "Host: " . $host . "\r\n";
      $request .= "Connection: Close\r\n\r\n";
      fwrite($socket, $request);
      fclose($socket);
      $launch_log[] = 'Background runner: loopback fsockopen';
      return true;
    }
    $launch_log[] = 'Loopback fsockopen gagal: ' . $errstr;
  }

  return false;
}

function update_job_run_deploy($state_file, $lock_file, $git_dir, $connection)
{
  ignore_user_abort(true);
  if (function_exists('set_time_limit')) {
    @set_time_limit(0);
  }
  @ini_set('max_execution_time', '0');
  @ini_set('memory_limit', '768M');

  $github_token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : (getenv('GITHUB_TOKEN') ?: '');
  $git_bin = 'git';
  $git_safe = "$git_bin -c safe.directory=*";
  $deployed = false;
  $err = '';
  $orig_origin_url = '';
  $remote_hash = strtolower(sae_fetch_remote_hash($github_token));

  update_job_set_state($state_file, [
    'status' => 'running',
    'started_at' => date('c'),
    'finished_at' => null,
    'message' => 'Menyiapkan update file, kode, commit log, database...',
    'log' => [],
    'success' => null,
    'errors' => [],
    'local_version' => update_job_get_local_hash($git_dir, $connection) ?: '-',
    'remote_version' => $remote_hash ?: '-',
    'deploy_hash' => '',
  ]);

  update_job_append_log($state_file, 'Memulai proses update...');
  update_job_append_log($state_file, 'Menyiapkan update file, kode, commit log, database...');
  update_job_append_log($state_file, 'Direktori aplikasi: ' . $git_dir);
  update_job_append_log($state_file, 'Remote hash: ' . ($remote_hash ? substr($remote_hash, 0, 7) : 'gagal dibaca'));

  $disabled_fns = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));
  update_job_append_log($state_file, 'Diagnostik exec=' . (!in_array('exec', $disabled_fns) ? 'yes' : 'no') . ', proc_open=' . (!in_array('proc_open', $disabled_fns) ? 'yes' : 'no') . ', shell_exec=' . (!in_array('shell_exec', $disabled_fns) ? 'yes' : 'no'));

  $git_version = cl_exec("$git_bin --version 2>&1");
  update_job_append_log($state_file, 'git version: ' . ($git_version['code'] === 0 ? trim($git_version['output']) : 'NOT FOUND'));

  if ($github_token) {
    $git_config_file = $git_dir . '/.git/config';
    if (is_file($git_config_file)) {
      $config_content = file_get_contents($git_config_file);
      if (preg_match('#\[remote "origin"\][^[]*url\s*=\s*(\S+)#i', $config_content, $m)) {
        $orig_origin_url = trim($m[1]);
      }
    }
    if ($orig_origin_url && preg_match('#^https://(([^@]+)@)?github\.com/(.+)$#i', $orig_origin_url, $url_m)) {
      $patched_url = 'https://x-access-token:' . $github_token . '@github.com/' . $url_m[3];
      $remote_patch = cl_exec("$git_safe remote set-url origin " . escapeshellarg($patched_url) . " 2>&1");
      update_job_append_log($state_file, 'Patch remote URL: exit=' . $remote_patch['code']);
    }
  }

  chdir($git_dir);
  putenv('GIT_TERMINAL_PROMPT=0');

  $ref_before = update_job_get_local_hash($git_dir, $connection);
  update_job_append_log($state_file, 'Hash lokal sebelum: ' . ($ref_before ? substr($ref_before, 0, 7) : '(kosong)'));

  $fetch_result = cl_exec("$git_safe fetch origin 2>&1");
  update_job_append_log($state_file, 'git fetch: exit=' . $fetch_result['code'] . ', method=' . ($fetch_result['method'] ?? '?') . ', output=' . substr(trim($fetch_result['output']), 0, 400));

  if ($fetch_result['code'] === 0) {
    $clean_result = cl_exec("$git_safe clean -fd -e content/ -e .env -e google/ 2>&1");
    update_job_append_log($state_file, 'git clean: exit=' . $clean_result['code'] . ', output=' . substr(trim($clean_result['output']), 0, 240));

    $cfg_backup = '';
    $cfg_path = $git_dir . '/library/config.php';
    if (is_file($cfg_path)) {
      $cfg_backup = file_get_contents($cfg_path);
    }

    $reset_result = cl_exec("$git_safe reset --hard origin/main 2>&1");
    update_job_append_log($state_file, 'git reset: exit=' . $reset_result['code'] . ', method=' . ($reset_result['method'] ?? '?') . ', output=' . substr(trim($reset_result['output']), 0, 400));

    if ($cfg_backup !== '' && is_file($cfg_path)) {
      file_put_contents($cfg_path, $cfg_backup);
    }

    if ($reset_result['code'] === 0) {
      $deployed = true;
      $ref_after = update_job_get_local_hash($git_dir, $connection);
      update_job_append_log($state_file, 'Hash setelah reset: ' . ($ref_before ? substr($ref_before, 0, 7) : '(kosong)') . ' -> ' . ($ref_after ? substr($ref_after, 0, 7) : '(kosong)'));
    } else {
      $err = 'git reset gagal: ' . trim($reset_result['output']);
      update_job_append_log($state_file, 'ERROR: ' . $err);
    }
  } else {
    $err = 'git fetch gagal: ' . trim($fetch_result['output']);
    update_job_append_log($state_file, 'ERROR: ' . $err);
  }

  if (!$deployed) {
    if (empty($remote_hash)) {
      $err = 'Git CLI gagal dan hash remote dari API tidak tersedia.';
      update_job_append_log($state_file, 'ERROR: ZIP fallback dibatalkan karena remote hash kosong');
    } else {
      update_job_append_log($state_file, 'Mencoba ZIP fallback...');
      $zip_result = sae_deploy_from_zip($git_dir, $github_token, $remote_hash);
      foreach (($zip_result['log'] ?? []) as $line) {
        update_job_append_log($state_file, $line);
      }
      if (!empty($zip_result['success'])) {
        $deployed = true;
        $err = '';
      } elseif ($err === '') {
        $err = 'ZIP fallback gagal. Update manual diperlukan.';
      }
    }
  }

  unset($_SESSION['check_update_cache']);

  if ($deployed) {
    $deploy_hash = $remote_hash;
    if (empty($deploy_hash)) {
      $deploy_hash = update_job_get_local_hash($git_dir, $connection);
    }

    update_job_append_log($state_file, 'Hash deploy final: ' . ($deploy_hash ? substr($deploy_hash, 0, 7) : '(kosong)'));

    $commit_result = save_commit_log($connection, 5);
    update_job_append_log($state_file, 'Commit log tersimpan: ' . (int) ($commit_result['saved'] ?? 0));

    $deploy_by = '';
    if (isset($_SESSION['admin_id'])) {
      $q_admin = $connection->query("SELECT fullname FROM admin WHERE admin_id='" . (int) $_SESSION['admin_id'] . "' LIMIT 1");
      if ($q_admin && $q_admin->num_rows) {
        $deploy_by = $q_admin->fetch_row()[0];
      }
    }

    require_once __DIR__ . '/../../../library/version.php';
    $current_version = $deploy_hash ? sae_version_from_commit($connection, $deploy_hash) . ' [' . substr($deploy_hash, 0, 7) . ']' : '';

    $sql_update = "UPDATE setting SET
      last_deploy_at=NOW(),
      last_deploy_commit='" . $connection->real_escape_string($deploy_hash) . "',
      deploy_count=deploy_count+1,
      last_deploy_by='" . $connection->real_escape_string($deploy_by) . "',
      last_deploy_status='success'";
    if ($current_version !== '') {
      $sql_update .= ", app_version='" . $connection->real_escape_string($current_version) . "'";
    }
    $sql_update .= " WHERE site_id=1";

    $db_result = $connection->query($sql_update);
    update_job_append_log($state_file, 'Update setting: ' . ($db_result ? 'OK' : 'GAGAL: ' . $connection->error));

    $ref_dir = $git_dir . '/.git/refs/heads';
    if (is_dir($ref_dir) && !empty($deploy_hash)) {
      @file_put_contents($ref_dir . '/main', $deploy_hash . "\n");
    }

    if (file_exists(__DIR__ . '/../../../library/activity.php')) {
      require_once __DIR__ . '/../../../library/activity.php';
      $admin_id = $_SESSION['admin_id'] ?? null;
      $admin_name = $_SESSION['admin_nama'] ?? 'Admin';
      log_activity($connection, $admin_id, $admin_name, 'deploy', 'Deploy pembaruan ke versi ' . ($current_version ?: '?'));
    }

    require_once __DIR__ . '/../../../library/migrate.php';
    update_job_append_log($state_file, 'Menjalankan migrasi database...');
    $mig_result = run_pending_migrations($connection);
    update_job_append_log($state_file, 'Migrasi dijalankan: ' . (int) ($mig_result['ran'] ?? 0));
    if (!empty($mig_result['errors'])) {
      foreach ($mig_result['errors'] as $mig_error) {
        update_job_append_log($state_file, 'ERROR migrasi: ' . $mig_error);
      }
    }

    $msg = 'Pembaruan berhasil diterapkan.';
    if (($commit_result['saved'] ?? 0) > 0) {
      $msg .= ' ' . (int) $commit_result['saved'] . ' commit baru tercatat.';
    }
    if (($mig_result['ran'] ?? 0) > 0) {
      $msg .= ' ' . (int) $mig_result['ran'] . ' migrasi database dijalankan.';
    }
    if (empty($mig_result['success'])) {
      $msg .= ' Ada error migrasi: ' . implode('; ', $mig_result['errors'] ?? []);
    }

    update_job_append_log($state_file, $msg);
    update_job_set_state($state_file, [
      'status' => 'success',
      'finished_at' => date('c'),
      'message' => $msg,
      'success' => true,
      'deploy_hash' => $deploy_hash,
      'local_version' => $deploy_hash ? substr($deploy_hash, 0, 7) : '-',
      'remote_version' => $remote_hash ?: '-',
    ]);
  } else {
    if ($err === '') {
      $err = 'Gagal memperbarui aplikasi.';
    }
    update_job_append_log($state_file, $err);
    update_job_set_state($state_file, [
      'status' => 'failed',
      'finished_at' => date('c'),
      'message' => $err,
      'success' => false,
      'errors' => [$err],
      'remote_version' => $remote_hash ?: '-',
    ]);
  }

  if ($github_token && $orig_origin_url !== '') {
    $remote_restore = cl_exec("$git_safe remote set-url origin " . escapeshellarg($orig_origin_url) . " 2>&1");
    update_job_append_log($state_file, 'Remote URL restored: exit=' . $remote_restore['code']);
  }

  if ($lock_file && is_file($lock_file)) {
    @unlink($lock_file);
  }
}

if ($action === 'check_remote_commits') {
  $cache_key = 'check_update_cache';
  $cache_ttl = 30;
  if (isset($_SESSION[$cache_key]) && is_array($_SESSION[$cache_key])) {
    $cached = $_SESSION[$cache_key];
    if (time() - $cached['time'] < $cache_ttl) {
      $resp = $cached['data'];
      $resp['cached'] = true;
      echo json_encode($resp);
      exit;
    }
  }

  $github_token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : '';
  $local_hash = update_job_get_local_hash($git_dir, $connection);
  $remote_hash = strtolower(sae_fetch_remote_hash($github_token));
  $update_available = false;
  $message = '';

  if (empty($remote_hash)) {
    $message = 'Gagal menghubungi GitHub API. Periksa token atau koneksi server.';
    $update_available = true;
  } elseif (!empty($local_hash) && $local_hash !== $remote_hash) {
    $update_available = true;
  } elseif (empty($local_hash)) {
    $message = 'Hash lokal tidak dapat dibaca dari server ini.';
    $update_available = true;
  }

  $resp = [
    'update_available' => $update_available,
    'local_version' => $local_hash ? substr($local_hash, 0, 7) : '-',
    'remote_version' => $remote_hash ? substr($remote_hash, 0, 7) : '-',
  ];
  if ($message !== '') {
    $resp['message'] = $message;
  }

  $_SESSION[$cache_key] = ['time' => time(), 'data' => $resp];
  echo json_encode($resp);
  exit;
}

if ($action === 'status') {
  echo json_encode(['success' => true, 'job' => update_job_read_state($state_file)]);
  exit;
}

if ($is_internal_run) {
  update_job_run_deploy($state_file, $lock_file, $git_dir, $connection);
  echo json_encode(['success' => true, 'message' => 'Runner selesai']);
  exit;
}

if ($data_role['modifikasi'] != 'Y') {
  echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
  exit;
}

if ($action !== 'deploy_start') {
  echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
  exit;
}

if (!$git_dir) {
  echo json_encode(['success' => false, 'message' => 'Direktori root tidak ditemukan']);
  exit;
}

$current_state = update_job_read_state($state_file);
if (($current_state['status'] ?? 'idle') === 'running' && $lock_file && is_file($lock_file)) {
  // Deteksi lock basi (>5 menit) — tandai gagal agar tidak memblokir selamanya
  $lock_data = update_job_read_lock($lock_file);
  $lock_age = !empty($lock_data['created_at']) ? (time() - strtotime($lock_data['created_at'])) : 0;
  if ($lock_age > 300) {
    @unlink($lock_file);
    update_job_set_state($state_file, [
      'status' => 'failed',
      'finished_at' => date('c'),
      'message' => 'Proses update terlalu lama (>5 menit) dan dianggap gagal. Silakan coba lagi.',
      'success' => false,
    ]);
  } else {
    echo json_encode(['success' => true, 'started' => false, 'message' => 'Proses update masih berjalan.', 'job' => $current_state]);
    exit;
  }
}

update_job_write_state($state_file, [
  'status' => 'queued',
  'started_at' => date('c'),
  'finished_at' => null,
  'message' => 'Job update dimasukkan ke antrean.',
  'log' => ['[' . date('H:i:s') . '] Job update dimasukkan ke antrean.'],
  'success' => null,
  'local_version' => update_job_get_local_hash($git_dir, $connection) ?: '-',
  'remote_version' => '-',
  'deploy_hash' => '',
  'errors' => [],
]);

 $job_key = bin2hex(random_bytes(16));
if ($lock_file) {
  @file_put_contents($lock_file, update_job_lock_payload($job_key), LOCK_EX);
}

$launch_log = [];
$started_in_background = update_job_launch_background_runner(__FILE__, $job_key, $launch_log);
if (!$started_in_background) {
  $started_in_background = update_job_launch_http_runner($job_key, $launch_log);
}
foreach ($launch_log as $line) {
  update_job_append_log($state_file, $line);
}

if ($started_in_background) {
  echo json_encode(['success' => true, 'started' => true, 'message' => 'Proses update dimulai di background.', 'job' => update_job_read_state($state_file)]);
  exit;
}

if (function_exists('fastcgi_finish_request')) {
  echo json_encode(['success' => true, 'started' => true, 'message' => 'Proses update dimulai di background.', 'job' => update_job_read_state($state_file)]);
  fastcgi_finish_request();
  update_job_run_deploy($state_file, $lock_file, $git_dir, $connection);
  exit;
}

register_shutdown_function(function () use ($state_file, $lock_file, $git_dir, $connection) {
  update_job_run_deploy($state_file, $lock_file, $git_dir, $connection);
});

echo json_encode(['success' => true, 'started' => true, 'message' => 'Proses update dimulai di background.', 'job' => update_job_read_state($state_file)]);
exit;
