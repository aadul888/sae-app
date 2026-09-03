<?php
/**
 * Ambil SHA commit terbaru dari GitHub API.
 */
function sae_fetch_remote_hash(string $token): string {
    $api_url = "https://api.github.com/repos/aadul888/sae-app/commits/main";
    $hash = '';
    if (ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: sae-deploy\r\n" . ($token ? "Authorization: Bearer $token\r\n" : ''),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ];
        $body = @file_get_contents($api_url, false, stream_context_create($opts));
        if ($body) { $data = json_decode($body, true); if (isset($data['sha'])) $hash = $data['sha']; }
    }
    if (empty($hash) && function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'sae-deploy',
            CURLOPT_HTTPHEADER => $token ? ["Authorization: Bearer $token"] : [],
        ]);
        $body = curl_exec($ch);
        if ($body) { $data = json_decode($body, true); if (isset($data['sha'])) $hash = $data['sha']; }
        curl_close($ch);
    }
    return $hash;
}

/**
 * Download, extract, and sync ZIP from GitHub to target dir.
 * Returns ['success'=>bool, 'log'=>string[], 'hash'=>string]
 */
function sae_deploy_from_zip(string $target, string $github_token, string $remote_hash): array {
    $log = [];
    $success = false;

    $zip_url = "https://api.github.com/repos/aadul888/sae-app/zipball/main";
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $zip_url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 120,
      CURLOPT_HTTPHEADER => [
        'Accept: application/vnd.github+json',
        'User-Agent: SAE-Deploy/1.0',
        $github_token ? "Authorization: Bearer $github_token" : '',
      ],
    ]);
    $zip_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);
    $log[] = 'ZIP download: HTTP ' . $http_code . ', size=' . strlen($zip_data ?? '') . ', error=' . ($curl_err ?: 'none');

    if ($http_code >= 400 || $curl_err) {
        $log[] = 'ERROR: ZIP download gagal (HTTP ' . $http_code . '): ' . $curl_err;
        return ['success' => false, 'log' => $log, 'hash' => ''];
    }

    $tmp_zip = sys_get_temp_dir() . '/sae-deploy-' . uniqid() . '.zip';
    file_put_contents($tmp_zip, $zip_data);

    $zip = new ZipArchive;
    if ($zip->open($tmp_zip) !== true) {
        $log[] = 'ERROR: ZipArchive gagal membuka ZIP';
        @unlink($tmp_zip);
        return ['success' => false, 'log' => $log, 'hash' => ''];
    }
    $tmp_extract = sys_get_temp_dir() . '/sae-extract-' . uniqid();
    $zip->extractTo($tmp_extract);
    $num_files = $zip->numFiles;
    $zip->close();
    $log[] = 'ZIP dibuka, ' . $num_files . ' entries, diekstrak ke ' . $tmp_extract;

    $items = scandir($tmp_extract);
    $inner = null;
    foreach ($items as $item) {
      if ($item !== '.' && $item !== '..' && is_dir("$tmp_extract/$item")) {
        $inner = "$tmp_extract/$item";
        break;
      }
    }
    if (!$inner) {
        $log[] = 'ERROR: inner directory tidak ditemukan di ZIP';
        return ['success' => false, 'log' => $log, 'hash' => ''];
    }

    // Backup .env before sync
    $env_backup = '';
    if (file_exists($target . '/.env')) {
        $env_backup = file_get_contents($target . '/.env');
    }
    // Backup library/config.php (DB credentials)
    $cfg_backup = '';
    $cfg_path = $target . '/library/config.php';
    if (is_file($cfg_path)) {
        $cfg_backup = file_get_contents($cfg_path);
    }

    // Sync: copy+overwrite (add/edit) + delete stale (delete)
    $sync_result = sae_deploy_sync_files($inner, $target);
    $log[] = 'Copy: ' . $sync_result['copied'] . ' disalin, ' . $sync_result['skipped'] . ' dilewati, ' . $sync_result['failed'] . ' gagal';
    $log[] = 'Hapus: ' . $sync_result['deleted'] . ' file usang terhapus' . ($sync_result['delete_failed'] ? ", {$sync_result['delete_failed']} gagal" : '');
    foreach ($sync_result['errors'] as $example) {
        $log[] = 'GAGAL ' . $example;
    }

    // Restore .env + config.php
    if ($env_backup) {
        @chmod($target . '/.env', 0644);
        file_put_contents($target . '/.env', $env_backup);
    }
    if ($cfg_backup !== '' && is_file($cfg_path)) {
        file_put_contents($cfg_path, $cfg_backup);
    }

    // Cleanup temp
    $rmdir = function($dir) use (&$rmdir) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = "$dir/$f";
            is_dir($p) ? $rmdir($p) : unlink($p);
        }
        rmdir($dir);
    };
    $rmdir($tmp_extract);
    @unlink($tmp_zip);

    if ($sync_result['failed'] > 0 || $sync_result['copied'] === 0) {
        $log[] = 'ERROR: Gagal menimpa/menyalin file (' . $sync_result['failed'] . ' gagal). Pastikan direktori aplikasi writable (CHMOD 755/644).';
        return ['success' => false, 'log' => $log, 'hash' => ''];
    }

    // Tulis hash ke .git/refs/heads/main
    $ref_dir = $target . '/.git/refs/heads';
    if (is_dir($ref_dir)) {
        @chmod($ref_dir, 0755);
        @chmod($ref_dir . '/main', 0644);
        @file_put_contents($ref_dir . '/main', $remote_hash . "\n");
        $log[] = 'Hash ditulis ke refs/heads/main: ' . substr($remote_hash, 0, 7);
    } else {
        $log[] = 'WARNING: .git/refs/heads bukan direktori';
    }

    return ['success' => true, 'log' => $log, 'hash' => $remote_hash];
}
/**
 * commit_logger.php
 * Helper untuk catat riwayat commit/deploy ke tabel commit_log.
 */

/**
 * Helper: jalankan command dengan fallback exec -> proc_open -> shell_exec
 * Return: ['output' => string, 'code' => int, 'method' => string]
 */
function cl_exec(string $cmd): array {
    $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));

    // Method 1: exec (captures stdout, 2>&1 needed for stderr)
    if (function_exists('exec') && !in_array('exec', $disabled)) {
        $out = []; $rv = -1;
        @exec($cmd, $out, $rv);
        return ['output' => implode("\n", $out), 'code' => $rv, 'method' => 'exec'];
    }

    // Method 2: proc_open (captures stdout + stderr)
    if (function_exists('proc_open') && !in_array('proc_open', $disabled)) {
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open($cmd, $desc, $pipes);
        if (is_resource($proc)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
            $rv = proc_close($proc);
            $combined = trim($stdout);
            if (!empty($stderr)) $combined .= ($combined ? "\n" : '') . trim($stderr);
            return ['output' => $combined, 'code' => $rv, 'method' => 'proc_open'];
        }
    }

    // Method 3: shell_exec (no exit code, assume 0 if output received)
    if (function_exists('shell_exec') && !in_array('shell_exec', $disabled)) {
        $result = @shell_exec($cmd . ' 2>&1');
        if ($result !== null) return ['output' => trim($result), 'code' => 0, 'method' => 'shell_exec'];
    }

    return ['output' => 'Semua fungsi exec disabled oleh server', 'code' => -1, 'method' => 'none'];
}

/**
 * Ambil log commit dari repo git (HEAD~n).
 * Utamakan git CLI, fallback baca .git/logs/HEAD langsung (tanpa exec).
 */
function get_git_commits(int $limit = 5): array {
    $git_dir = realpath(__DIR__ . '/..');
    if (!$git_dir) return [];

    $commits = [];
    $git_bin = 'git';
    $git_safe = "$git_bin -c safe.directory='*'";

    // Method 1: git log via exec/proc_open/shell_exec
    $cmd = sprintf(
        '%s -C %s log --max-count=%d --format="---HASH:%%H---AUTHOR:%%an <%%ae>---DATE:%%cI---SUBJECT:%%s---BODY:%%b" 2>/dev/null',
        $git_safe,
        escapeshellarg($git_dir),
        $limit
    );
    $result = cl_exec($cmd);
    if ($result['code'] === 0 && !empty($result['output'])) {
        return parse_git_log_output($result['output']);
    }

    // Method 2: baca .git/logs/HEAD langsung (pure PHP, tanpa git CLI)
    $reflog_file = $git_dir . '/.git/logs/HEAD';
    if (file_exists($reflog_file)) {
        $lines = file($reflog_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($lines)) {
            $lines = array_reverse($lines);
            foreach ($lines as $line) {
                if (preg_match('/^[a-f0-9]+ ([a-f0-9]{40}) (.+?) (\d+) ([+-]\d{4})\tcommit: (.+)$/', $line, $m)) {
                    $hash = $m[1];
                    $author = $m[2];
                    $timestamp = (int)$m[3];
                    $tz = $m[4];
                    $subject = $m[5];
                    try {
                        $dt = new DateTime('@' . $timestamp);
                        $dt->setTimezone(new DateTimeZone($tz));
                        $committed_at = $dt->format('Y-m-d H:i:s');
                    } catch (Throwable $th) {
                        $committed_at = null;
                    }
                    $commits[] = [
                        'hash' => $hash,
                        'author' => $author,
                        'committed_at' => $committed_at,
                        'subject' => $subject,
                        'body' => '',
                        'message_raw' => $subject,
                    ];
                }
            }
        }
    }

    // Method 3: fallback ke GitHub API (pure PHP, tanpa git CLI)
    if (empty($commits)) {
        $commits = fetch_github_commits($limit);
    }

    return $commits;
}

/**
 * Ambil commit terbaru via GitHub API (fallback jika git CLI & reflog tidak tersedia).
 */
function fetch_github_commits(int $limit = 5): array {
    // Load token seperti di proses.php
    $env_file = __DIR__ . '/env_loader.php';
    if (file_exists($env_file)) require_once $env_file;
    $github_legacy = __DIR__ . '/github-config.php';
    if (file_exists($github_legacy)) require_once $github_legacy;
    if (!defined('GITHUB_TOKEN')) {
        define('GITHUB_TOKEN', getenv('GITHUB_TOKEN') ?: '');
    }
    $github_token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : (getenv('GITHUB_TOKEN') ?: '');

    $owner = 'aadul888';
    $repo = 'sae-app';
    $api_url = "https://api.github.com/repos/$owner/$repo/commits?per_page=$limit";
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: sae-commit-logger\r\n" . ($github_token ? "Authorization: Bearer $github_token\r\n" : ''),
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($api_url, false, $ctx);

    // cURL fallback
    if (!$body && function_exists('curl_init')) {
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'sae-commit-logger',
            CURLOPT_HTTPHEADER => $github_token ? ["Authorization: Bearer $github_token"] : [],
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
    }

    if (!$body) return [];

    $data = json_decode($body, true);
    if (!is_array($data)) return [];

    $commits = [];
    foreach ($data as $item) {
        $hash = $item['sha'] ?? '';
        if (empty($hash)) continue;
        $subject = $item['commit']['message'] ?? '';
        // Ambil hanya baris pertama (subject)
        $subject = strtok($subject, "\n") ?: $subject;
        $author = $item['commit']['author']['name'] ?? '';
        $author_email = $item['commit']['author']['email'] ?? '';
        $date_raw = $item['commit']['author']['date'] ?? '';
        $author_full = $author . ($author_email ? " <$author_email>" : '');

        try {
            $dt = new DateTime($date_raw);
            $committed_at = $dt->format('Y-m-d H:i:s');
        } catch (Throwable $th) {
            $committed_at = null;
        }

        $commits[] = [
            'hash' => $hash,
            'author' => $author_full,
            'committed_at' => $committed_at,
            'subject' => $subject,
            'body' => '',
            'message_raw' => $subject,
        ];
        if (count($commits) >= $limit) break;
    }
    return $commits;
}

/**
 * Parse output dari git log --format.
 */
function parse_git_log_output(string $raw): array {
    // Normalize line endings (Windows CRLF -> LF)
    $raw = str_replace("\r\n", "\n", $raw);
    $raw = str_replace("\r", "\n", $raw);

    $entries = explode("\n---HASH:", "\n" . $raw);
    $commits = [];
    foreach ($entries as $e) {
        $e = trim($e);
        if (empty($e)) continue;
        $hash = '';
        $author = '';
        $date_raw = '';
        $subject = '';
        $body = '';

        // Hash bisa di awal (setelah explode) atau dengan prefix HASH:
        if (preg_match('/^(?<hash>[a-f0-9]{40})/', $e, $m)) {
            $hash = $m['hash'];
        } elseif (preg_match('/HASH:(?<hash>[a-f0-9]{40})/', $e, $m)) {
            $hash = $m['hash'];
        }
        if (preg_match('/AUTHOR:(?<author>[^\n]+)/', $e, $m)) $author = trim($m['author']);
        if (preg_match('/DATE:(?<date>[^\n]+)/', $e, $m)) $date_raw = trim($m['date']);
        if (preg_match('/SUBJECT:(?<subject>[^\n]+)/', $e, $m)) $subject = trim($m['subject']);
        // Body: ambil apapun setelah BODY: sampai akhir string, lalu hapus trailing whitespace
        if (preg_match('/BODY:(?<body>.*)$/s', $e, $m)) {
            $body = trim($m['body']);
            // Hapus marker "---HASH:" jika body mengandung sisa format dari entry berikutnya (parsing glitch)
            $body = preg_replace('/\n---HASH:.*$/s', '', $body);
            $body = trim($body);
        }

        if (empty($hash)) continue;

        try {
            $dt = new DateTime($date_raw);
            $committed_at = $dt->format('Y-m-d H:i:s');
        } catch (Throwable $th) {
            $committed_at = null;
        }

        $commits[] = [
            'hash' => $hash,
            'author' => $author,
            'committed_at' => $committed_at,
            'subject' => $subject,
            'body' => $body,
            'message_raw' => $subject . ($body ? "\n\n" . $body : ''),
        ];
    }
    return $commits;
}

/**
 * Terjemahkan pesan commit Inggris ke bahasa Indonesia sederhana.
 */
function translate_commit_message(string $subject, string $body = ''): string {
    $full = $subject;
    if (!empty($body)) $full .= "\n" . $body;

    $rules = [
        '/\bfix(?:ed|es)?\b/i' => 'Perbaikan',
        '/\bupdate(?:d)?\b/i' => 'Pembaruan',
        '/\badd(?:ed)?\b/i' => 'Penambahan',
        '/\bremove(?:d)?\b/i' => 'Penghapusan',
        '/\bchange(?:d)?\b/i' => 'Perubahan',
        '/\bimprove(?:d|ment)?\b/i' => 'Peningkatan',
        '/\brefactor(?:ed|ing)?\b/i' => 'Perbaikan kode',
        '/\brewrite?\b/i' => 'Penulisan ulang',
        '/\bclean(?:ed|up)?\b/i' => 'Pembersihan kode',
        '/\bmerge\b/i' => 'Penggabungan',
        '/\bconfig\b/i' => 'Konfigurasi',
        '/\bdeploy\b/i' => 'Proses rilis',
        '/\bmigrate?(?:ion)?\b/i' => 'Migrasi database',
        '/\bdatabase\b/i' => 'Database',
        '/\bfeature\b/i' => 'Fitur baru',
        '/\bbug\b/i' => 'Kesalahan',
        '/\btest(?:s|ing)?\b/i' => 'Pengujian',
        '/\bdocs?\b/i' => 'Dokumentasi',
        '/\bcommit\b/i' => 'Penyimpanan perubahan',
        '/\bpush\b/i' => 'Pengiriman ke server',
        '/\bpull\b/i' => 'Penarikan dari server',
        '/\brevert\b/i' => 'Pengembalian',
        '/\breset\b/i' => 'Pengaturan ulang',
        '/\binit(?:ial)?\b/i' => 'Inisialisasi',
        '/\brelease\b/i' => 'Rilis',
        '/\bhotfix\b/i' => 'Perbaikan darurat',
        '/\b(?:ui|ux)\b/i' => 'Tampilan',
        '/\bpermission\b/i' => 'Hak akses',
        '/\bsecurity\b/i' => 'Keamanan',
        '/\bvalidate?(?:ion)?\b/i' => 'Validasi',
        '/\bnotif(?:y|ication)?\b/i' => 'Notifikasi',
        '/\berror\b/i' => 'Kesalahan',
        '/\bwarning\b/i' => 'Peringatan',
    ];

    $result = preg_replace(array_keys($rules), array_values($rules), $full);

    // Capitalize first letter
    $result = ucfirst(trim($result));

    return $result;
}

/**
 * Simpan commit log ke database.
 */
function save_commit_log(mysqli $conn, int $limit = 3): array {
    $commits = get_git_commits($limit);
    $saved = 0;

    foreach ($commits as $c) {
        $hash = $conn->real_escape_string($c['hash']);
        $exists = $conn->query("SELECT id FROM commit_log WHERE commit_hash='$hash' LIMIT 1");
        if ($exists && $exists->num_rows > 0) continue;

        // Bersihkan subject dari artefak BODY: dan garis miring
        $subject_clean = preg_replace('/---BODY:.*$/s', '', $c['subject']);
        $subject_clean = trim($subject_clean);
        $body_clean = trim($c['body'] ?? '');

        $subject_safe = $conn->real_escape_string($subject_clean);
        $body_safe = $conn->real_escape_string($body_clean);
        $msg_bahasa = $conn->real_escape_string(translate_commit_message($subject_clean, $body_clean));
        $author = $conn->real_escape_string($c['author']);
        $date = $c['committed_at'] ? "'" . $conn->real_escape_string($c['committed_at']) . "'" : 'NOW()';

        $sql = "INSERT INTO commit_log (commit_hash, commit_message, commit_message_bahasa, author, committed_at, created_at)
                VALUES ('$hash', '$subject_safe', '$msg_bahasa', '$author', $date, NOW())";
        if ($conn->query($sql)) $saved++;
    }

    return ['saved' => $saved, 'total' => count($commits)];
}

/**
 * Ambil riwayat commit log dari DB untuk ditampilkan sebagai baris tabel.
 */
function get_commit_log_rows(mysqli $conn): string {
    // ponytail: table auto-created here; full schema in database/db_sae.sql
    // Pastikan tabel commit_log ada (migration mungkin belum jalan)
    $conn->query("CREATE TABLE IF NOT EXISTS `commit_log` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `commit_hash` varchar(40) NOT NULL DEFAULT '',
      `commit_message` text,
      `commit_message_bahasa` text,
      `author` varchar(100) NOT NULL DEFAULT '',
      `committed_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `commit_hash` (`commit_hash`),
      KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $rows = '';
    // Ambil dari commit_log saja (tabel pembaharuan sudah tidak dipakai)
    $sql = "SELECT commit_message AS keterangan, committed_at AS tanggal, SUBSTRING(commit_hash,1,7) AS versi
            FROM commit_log
            ORDER BY IFNULL(committed_at, created_at) DESC
            LIMIT 50";
    $q = $conn->query($sql);
    if ($q && $q->num_rows > 0) {
        while ($r = $q->fetch_assoc()) {
            $versi = !empty($r['versi']) ? htmlspecialchars($r['versi']) : '-';
            $keterangan = nl2br(htmlspecialchars($r['keterangan'] ?? ''));
            $tanggal = !empty($r['tanggal']) ? tgl_indo($r['tanggal']) . ' ' . jam_indo($r['tanggal']) : '-';
            $rows .= "<tr><td class=\"text-nowrap\">$versi</td><td>$keterangan</td><td class=\"text-nowrap text-muted small\">$tanggal</td></tr>";
        }
    }
    if (empty($rows)) {
        $rows = '<tr><td colspan="3" class="text-center text-muted py-3">Belum ada riwayat pembaruan.</td></tr>';
    }
    return $rows;
}

/**
 * Path yang TIDAK boleh disentuh updater (konfigurasi lokal & data user).
 */
function sae_is_protected_path(string $rel): bool {
    if ($rel === '') return true;
    $exact = [
        '.env', '.env.example', '.sae-installed', '.well-known',
        'library/config.php', 'library/github-config.php', 'library/.sae_db.php',
        'api/api_config.php', 'google/google-config.php', 'library/sso_config.php',
    ];
    if (in_array($rel, $exact, true)) return true;
    if ($rel === 'content' || strpos($rel, 'content/') === 0) return true;
    if ($rel === '.git' || strpos($rel, '.git/') === 0) return true;
    return false;
}

/**
 * Tulis file dengan fallback bertingkat agar tahan permission ketat:
 * 1) copy()  2) tulis temp di folder tujuan + rename() atomik  3) cp -f via shell
 */
function sae_deploy_write_file(string $src, string $dest): bool {
    $dir = dirname($dest);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }

    if (file_exists($dest) && !is_writable($dest)) {
        @chmod($dest, 0664);
        if (function_exists('cl_exec')) {
            cl_exec('chmod 664 ' . escapeshellarg($dest) . ' 2>/dev/null');
        }
    }

    if (@copy($src, $dest)) {
        @chmod($dest, 0664);
        return true;
    }

    $content = @file_get_contents($src);
    if ($content !== false) {
        $tmp = $dir . '/.' . uniqid('saeup_') . '.tmp';
        if (@file_put_contents($tmp, $content) !== false) {
            if (@rename($tmp, $dest)) {
                @chmod($dest, 0664);
                return true;
            }
            @unlink($tmp);
        }
    }

    if (function_exists('cl_exec')) {
        $r = cl_exec('cp -f ' . escapeshellarg($src) . ' ' . escapeshellarg($dest) . ' 2>&1');
        if (($r['code'] ?? 1) === 0 && file_exists($dest)) {
            @chmod($dest, 0664);
            return true;
        }
    }
    return false;
}

/**
 * Hapus file secara aman: chmod lalu unlink, fallback rm -f via shell.
 */
function sae_deploy_delete(string $path): bool {
    if (!file_exists($path)) return true;
    @chmod($path, 0664);
    if (@unlink($path)) return true;
    if (function_exists('cl_exec')) {
        cl_exec('rm -f ' . escapeshellarg($path) . ' 2>/dev/null');
        return !file_exists($path);
    }
    return false;
}

/**
 * Sync files dari extracted ZIP ke target: copy (add+edit), delete stale (delete).
 * Returns ['copied'=>int, 'skipped'=>int, 'failed'=>int, 'deleted'=>int, 'delete_failed'=>int, 'errors'=>array]
 */
function sae_deploy_sync_files(string $inner, string $target): array {
    $result = ['copied' => 0, 'skipped' => 0, 'failed' => 0, 'deleted' => 0, 'delete_failed' => 0, 'errors' => []];

    // ---- FASE 1: manifest ZIP (rel => path sumber) untuk copy & deteksi hapus ----
    $zip_manifest = [];
    $mIt = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($inner, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($mIt as $item) {
        $subPath = str_replace('\\', '/', $mIt->getSubPathname());
        $zip_manifest[$subPath] = $item->getPathname();
    }

    // ---- FASE 2: copy/timpa semua file repo (add + edit) ----
    $failed_examples = [];
    foreach ($zip_manifest as $subPath => $srcPath) {
        if (sae_is_protected_path($subPath)) { $result['skipped']++; continue; }
        $dest = $target . '/' . $subPath;
        if (!sae_deploy_write_file($srcPath, $dest)) {
            $result['failed']++;
            if (count($failed_examples) < 50) $failed_examples[] = 'copy: ' . $subPath;
        } else {
            $result['copied']++;
        }
    }
    $result['errors'] = $failed_examples;

    // ---- FASE 3: hapus file lokal yang sudah tidak ada di repo (delete) ----
    $lIt = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $target_norm = rtrim(str_replace('\\', '/', $target), '/');
    $stale = [];
    foreach ($lIt as $f) {
        $rel = str_replace('\\', '/', substr(str_replace('\\', '/', $f->getPathname()), strlen($target_norm) + 1));
        if (sae_is_protected_path($rel)) continue;
        if (strpos($rel, 'node_modules/') === 0 || strpos($rel, 'vendor/') === 0) continue;
        $stale[$rel] = $f->getPathname();
    }
    foreach ($stale as $rel => $abs) {
        if (!array_key_exists($rel, $zip_manifest)) {
            sae_deploy_delete($abs) ? $result['deleted']++ : $result['delete_failed']++;
        }
    }

    return $result;
}
