<?php
/**
 * commit_logger.php
 * Helper untuk catat riwayat commit/deploy ke tabel commit_log.
 */

/**
 * Ambil log commit dari repo git (HEAD~n).
 */
function get_git_commits(int $limit = 5): array {
    $git_dir = realpath(__DIR__ . '/..');
    $git_bin = '/usr/bin/git';
    if (!$git_dir || !file_exists($git_bin)) return [];

    $cmd = sprintf(
        '%s -C %s log --max-count=%d --format="---HASH:%H---AUTHOR:%an <%ae>---DATE:%cI---SUBJECT:%s---BODY:%b" 2>/dev/null',
        escapeshellarg($git_bin),
        escapeshellarg($git_dir),
        $limit
    );
    $out = [];
    exec($cmd, $out, $rv);
    if ($rv !== 0) return [];

    $raw = implode("\n", $out);
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

        if (preg_match('/^(?<hash>[a-f0-9]{40})/', $e, $m)) $hash = $m['hash'];
        if (preg_match('/AUTHOR:(?<author>[^\n]+)/', $e, $m)) $author = trim($m['author']);
        if (preg_match('/DATE:(?<date>[^\n]+)/', $e, $m)) $date_raw = trim($m['date']);
        if (preg_match('/SUBJECT:(?<subject>[^\n]+)/', $e, $m)) $subject = trim($m['subject']);
        if (preg_match('/BODY:(?<body>.*)/s', $e, $m)) $body = trim($m['body']);

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

        $subject = $conn->real_escape_string($c['subject']);
        $body = $conn->real_escape_string($c['body'] ?? '');
        $msg_bahasa = $conn->real_escape_string(translate_commit_message($c['subject'], $c['body'] ?? ''));
        $author = $conn->real_escape_string($c['author']);
        $date = $c['committed_at'] ? "'" . $conn->real_escape_string($c['committed_at']) . "'" : 'NULL';

        $sql = "INSERT INTO commit_log (commit_hash, commit_message, commit_message_bahasa, author, committed_at, created_at)
                VALUES ('$hash', '$subject', '$msg_bahasa', '$author', $date, NOW())";
        if ($conn->query($sql)) $saved++;
    }

    return ['saved' => $saved, 'total' => count($commits)];
}

/**
 * Ambil riwayat commit log dari DB untuk ditampilkan sebagai baris tabel.
 */
function get_commit_log_rows(mysqli $conn): string {
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
    // Gabung commit_log + pembaharuan, urut DESC
    $sql = "SELECT 'commit' AS source, commit_message_bahasa AS keterangan, committed_at AS tanggal, '' AS versi
            FROM commit_log
            UNION ALL
            SELECT 'release' AS source, CONCAT(pembaharuan, IF(perbaikan!='', CONCAT('\nPerbaikan: ', perbaikan), '')) AS keterangan, release_date AS tanggal, version AS versi
            FROM pembaharuan
            ORDER BY tanggal DESC
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
