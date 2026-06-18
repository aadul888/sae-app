<?php

/**
 * SAE v4 API - Simplified Data Processor
 * 
 * Processor sederhana untuk menangani data tanpa integrasi kompleks
 */

require_once __DIR__ . '/../handlers/SekolahHandler.php';
require_once __DIR__ . '/../handlers/PesertaDidikHandler.php';
require_once __DIR__ . '/../handlers/GtkHandler.php';
require_once __DIR__ . '/../handlers/PenggunaHandler.php';
require_once __DIR__ . '/../handlers/RombelHandler.php';

class DataProcessor
{

    private $handlers = [];

    public function __construct()
    {
        $this->initializeHandlers();
    }

    /**
     * Initialize all handlers
     */
    private function initializeHandlers()
    {
        $this->handlers = [
            'sekolah' => new SekolahHandler(),
            'peserta_didik' => new PesertaDidikHandler(),
            'gtk' => new GtkHandler(),
            'pengguna' => new PenggunaHandler(),
            'rombel' => new RombelHandler()
        ];
    }

    /**
     * Process data berdasarkan endpoint - Simplified version
     */
    public function processData($endpoint, $data)
    {
        try {
            switch ($endpoint) {
                case 'ping':
                    return $this->handlePing();

                case 'getSekolah':
                case 'sekolah':
                    $result = $this->processSekolah($data);
                    $this->writeSyncLog($endpoint, $result);
                    return $result;

                case 'getPesertaDidik':
                case 'peserta_didik':
                    $result = $this->processPesertaDidik($data);
                    $this->writeSyncLog($endpoint, $result);
                    return $result;

                case 'getPTK':
                case 'getPtk':
                case 'ptk':
                case 'getGtk':
                case 'gtk':
                    $result = $this->processGtk($data);
                    $this->writeSyncLog($endpoint, $result);
                    return $result;

                case 'getPengguna':
                case 'pengguna':
                    $result = $this->processPengguna($data);
                    $this->writeSyncLog($endpoint, $result);
                    return $result;

                case 'getRombonganBelajar':
                case 'rombongan_belajar':
                    $result = $this->processRombel($data);
                    $this->writeSyncLog($endpoint, $result);
                    return $result;

                case 'syncAll':
                    $result = $this->processSyncAll($data);
                    $this->writeSyncLog('syncAll', $result);
                    return $result;

                default:
                    throw new Exception("Unknown endpoint: $endpoint");
            }
        } catch (Exception $e) {
            ApiLogger::logError('DataProcessor::processData', $e->getMessage(), [
                'endpoint' => $endpoint
            ]);

            $failResult = [
                'success' => false,
                'message' => 'Data processing failed: ' . $e->getMessage()
            ];
            $this->writeSyncLog($endpoint, $failResult);
            return $failResult;
        }
    }

    /**
     * Write sync status to sync_log table (last sync per endpoint)
     */
    private function writeSyncLog($endpoint, $result)
    {
        try {
            global $connection;
            if (!$connection) return;

            $status = ($result['success'] ?? false) ? 'success' : 'failed';
            $total_records = (int)($result['stats']['processed'] ?? $result['stats']['total'] ?? 0);
            $message = substr($result['message'] ?? '', 0, 65535);

            $stmt = $connection->prepare("
                INSERT INTO sync_log (endpoint, status, total_records, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    total_records = VALUES(total_records),
                    message = VALUES(message),
                    created_at = NOW()
            ");
            if ($stmt) {
                $stmt->bind_param('ssis', $endpoint, $status, $total_records, $message);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Silent fail - log writing must never break main sync
            error_log("SAE API: writeSyncLog failed for $endpoint: " . $e->getMessage());
        }
    }

    /**
     * Handle ping request
     */
    private function handlePing()
    {
        return [
            'success' => true,
            'message' => 'API is running',
            'stats' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '4.0-simplified'
            ]
        ];
    }

    /**
     * Process sekolah data
     */
    private function processSekolah($data)
    {
        if (empty($data)) {
            throw new Exception('No sekolah data provided');
        }

        // Handle data format from Dapodik (could be array or object with 'rows')
        $items = $this->normalizeDapodikData($data);

        $handler = $this->handlers['sekolah'];
        $processed = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                $handler->save($item);
                $processed++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                ApiLogger::logError('DataProcessor::processSekolah', $e->getMessage(), [
                    'item_data' => $item
                ]);
            }
        }

        return [
            'success' => true,
            'message' => "Processed $processed sekolah records",
            'stats' => [
                'processed' => $processed,
                'errors' => count($errors)
            ]
        ];
    }

    /**
     * Normalize data format from Dapodik
     * Handles both array format and Dapodik response format with 'rows'
     */
    private function normalizeDapodikData($data)
    {
        // If data has 'rows' key (Dapodik format)
        if (isset($data['rows'])) {
            // If rows is an array, return it
            if (is_array($data['rows']) && isset($data['rows'][0])) {
                return $data['rows'];
            }
            // If rows is a single object, wrap it in array
            elseif (is_array($data['rows'])) {
                return [$data['rows']];
            }
        }

        // If data is already an array of items
        if (is_array($data) && isset($data[0])) {
            return $data;
        }

        // If data is a single object, wrap it in array
        if (is_array($data)) {
            return [$data];
        }

        return [];
    }

    /**
     * Process peserta didik data
     */
    private function processPesertaDidik($data)
    {
        if (empty($data)) {
            throw new Exception('No peserta didik data provided');
        }

        $items = $this->normalizeDapodikData($data);

        $handler = $this->handlers['peserta_didik'];

        // Use smart sync instead of individual saves to handle deleted records
        $stats = $handler->smartSync($items);

        ApiLogger::logSyncActivity(
            'peserta_didik',
            'success',
            $stats['total'],
            "Smart sync completed",
            $stats
        );

        return [
            'success' => true,
            'message' => "Smart sync completed: {$stats['total']} total, {$stats['inserted']} inserted, {$stats['updated']} updated",
            'stats' => $stats
        ];
    }

    /**
     * Process GTK data
     */
    private function processGtk($data)
    {
        if (empty($data)) {
            throw new Exception('No GTK data provided');
        }

        $items = $this->normalizeDapodikData($data);

        $handler = $this->handlers['gtk'];
        $stats = $handler->smartSync($items);

        return [
            'success' => true,
            'message' => "Smart sync GTK selesai: {$stats['total']} total, {$stats['inserted']} inserted, {$stats['updated']} updated, {$stats['deleted']} deleted, {$stats['deactivated_admin']} admin dinonaktifkan",
            'stats' => $stats
        ];
    }

    /**
     * Process pengguna data
     */
    private function processPengguna($data)
    {
        if (empty($data)) {
            throw new Exception('No pengguna data provided');
        }

        $items = $this->normalizeDapodikData($data);

        $handler = $this->handlers['pengguna'];
        $processed = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                $handler->save($item);
                $processed++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                ApiLogger::logError('DataProcessor::processPengguna', $e->getMessage(), [
                    'item_data' => $item
                ]);
            }
        }

        return [
            'success' => true,
            'message' => "Processed $processed pengguna records",
            'stats' => [
                'processed' => $processed,
                'errors' => count($errors)
            ]
        ];
    }

    /**
     * Process rombel data
     */
    private function processRombel($data)
    {
        if (empty($data)) {
            throw new Exception('No rombongan belajar data provided');
        }

        $items = $this->normalizeDapodikData($data);

        $handler = $this->handlers['rombel'];
        $processed = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                $handler->save($item);
                $processed++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                ApiLogger::logError('DataProcessor::processRombel', $e->getMessage(), [
                    'item_data' => $item
                ]);
            }
        }

        return [
            'success' => true,
            'message' => "Processed $processed rombongan belajar records",
            'stats' => [
                'processed' => $processed,
                'errors' => count($errors)
            ]
        ];
    }

    /**
     * Process all endpoints in one bundled request
     * Data format: ['getSekolah' => [...], 'getGtk' => [...], ...]
     */
    private function processSyncAll($allData)
    {
        if (empty($allData) || !is_array($allData)) {
            throw new Exception('syncAll: data bundle kosong atau tidak valid');
        }

        $map = [
            'getSekolah'          => 'processSekolah',
            'getPengguna'         => 'processPengguna',
            'getGtk'              => 'processGtk',
            'getPesertaDidik'     => 'processPesertaDidik',
            'getRombonganBelajar' => 'processRombel',
        ];

        $stats   = [];
        $success = 0;
        $failed  = 0;

        foreach ($map as $key => $method) {
            if (!isset($allData[$key]) || empty($allData[$key])) continue;
            try {
                $result        = $this->$method($allData[$key]);
                $stats[$key]   = $result;
                $this->writeSyncLog($key, $result);
                if ($result['success'] ?? false) $success++;
                else $failed++;
            } catch (Exception $e) {
                $err         = ['success' => false, 'message' => $e->getMessage(), 'stats' => []];
                $stats[$key] = $err;
                $this->writeSyncLog($key, $err);
                $failed++;
                ApiLogger::logError('DataProcessor::processSyncAll', $e->getMessage(), ['endpoint' => $key]);
            }
        }

        $total = $success + $failed;

        // Otomatis isi tabel admin dari sync_pengguna + sync_gtk setelah sync selesai
        $adminResult = ['success' => false, 'message' => 'skipped'];
        if ($success > 0) {
            try {
                $adminResult = $this->syncAdminFromSync();
                $this->writeSyncLog('syncAdmin', $adminResult);
            } catch (Exception $e) {
                $adminResult = ['success' => false, 'message' => $e->getMessage()];
                ApiLogger::logError('DataProcessor::syncAdminFromSync', $e->getMessage());
            }
        }

        return [
            'success' => $failed === 0 && $total > 0,
            'message' => "syncAll selesai: $success/$total endpoint berhasil",
            'stats'   => ['processed' => $success, 'failed' => $failed],
            'details' => $stats,
            'admin_sync' => $adminResult,
        ];
    }

    /**
     * Isi tabel admin secara otomatis dari sync_pengguna dan sync_gtk.
     * Dipanggil setiap kali syncAll berhasil (minimal 1 endpoint sukses).
     */
    private function syncAdminFromSync()
    {
        global $connection;
        $db = $connection;

        if (!$db) {
            throw new Exception('syncAdminFromSync: koneksi DB tidak tersedia');
        }

        // ---------- 1. Preload jenis_ptk dari sync_gtk ----------
        $gtk_jenis = [];
        $gq = $db->query(
            "SELECT ptk_id, jenis_ptk_id_str FROM sync_gtk WHERE ptk_id IS NOT NULL AND ptk_id != ''"
        );
        if ($gq) {
            while ($gr = $gq->fetch_assoc()) {
                $gtk_jenis[$gr['ptk_id']] = $gr['jenis_ptk_id_str'];
            }
        }

        // ---------- 2. Upsert admin dari sync_pengguna ----------
        $rq = $db->query(
            "SELECT pengguna_id, username, nama, peran_id_str, password, no_hp, ptk_id
             FROM sync_pengguna
             WHERE username IS NOT NULL AND username != ''
               AND peran_id_str IN ('Operator Sekolah', 'Kepala Sekolah', 'PTK')
             ORDER BY nama"
        );
        if (!$rq) {
            throw new Exception('Query sync_pengguna gagal: ' . $db->error);
        }

        $proc = $upd = $ins = $fail = $deactivated = 0;
        $errs = [];
        $now  = date('Y-m-d H:i:s');

        while ($p = $rq->fetch_assoc()) {
            $proc++;
            $pengguna_id    = $p['pengguna_id'];
            $username       = $p['username'];
            $fullname       = $p['nama'] ?? '';
            $peran_id_str   = $p['peran_id_str'] ?? '';
            $password       = !empty($p['password']) ? $p['password'] : password_hash('12345', PASSWORD_DEFAULT);
            $phone          = $p['no_hp'] ?? '';
            $ptk_id         = $p['ptk_id'] ?? '';
            $email          = $username;
            $is_ptk_role    = (strcasecmp(trim((string)$peran_id_str), 'PTK') === 0);
            $has_sync_gtk   = (!empty($ptk_id) && isset($gtk_jenis[$ptk_id]));
            $force_inactive = ($is_ptk_role && !$has_sync_gtk);
            $active_flag    = $force_inactive ? 'N' : 'Y';
            $status_flag    = 'Offline';
            $sync_flag      = $force_inactive ? 'manual' : 'synced';

            $jenis_ptk_id_str = '';
            if ($peran_id_str === 'Operator Sekolah') {
                $level_id = 1;
            } elseif ($peran_id_str === 'Kepala Sekolah') {
                $level_id = 13;
            } elseif ($peran_id_str === 'PTK') {
                $jenis_ptk_id_str = !empty($ptk_id) ? ($gtk_jenis[$ptk_id] ?? '') : '';
                $level_id = (stripos($jenis_ptk_id_str, 'Guru') !== false) ? 2 : 3;
            } else {
                $level_id = 3;
            }

            // Cek sudah ada?
            $cek = $db->prepare(
                "SELECT admin_id, avatar, gelar_depan, gelar_belakang, tugas_tambahan FROM admin WHERE pengguna_id = ? LIMIT 1"
            );
            $cek->bind_param('s', $pengguna_id);
            $cek->execute();
            $row = $cek->get_result()->fetch_assoc();
            $cek->close();

            if ($row) {
                $avatar  = !empty($row['avatar']) ? $row['avatar'] : 'avatar.jpg';
                $gelar_d = $row['gelar_depan'] ?? '';
                $gelar_b = $row['gelar_belakang'] ?? '';
                $tugas   = $row['tugas_tambahan'] ?? '';
                $aid     = intval($row['admin_id']);
                $s = $db->prepare(
                    "UPDATE admin SET
                       username=?, email=?, password=?, fullname=?, phone=?,
                       avatar=?, gelar_depan=?, gelar_belakang=?, level_id=?,
                       peran_id_str=?, ptk_id=?, jenis_ptk_id_str=?, tugas_tambahan=?,
                       active=?, status=?,
                       sync_status=?, last_sync_at=NOW(), updated_at=NOW()
                     WHERE admin_id=?"
                );
                $s->bind_param('ssssssssssssssssi',
                    $username, $email, $password, $fullname, $phone,
                    $avatar, $gelar_d, $gelar_b, $level_id,
                    $peran_id_str, $ptk_id, $jenis_ptk_id_str, $tugas,
                    $active_flag, $status_flag, $sync_flag,
                    $aid
                );
                if ($s->execute()) {
                    $upd++;
                    if ($force_inactive) {
                        $deactivated++;
                    }
                }
                else { $fail++; $errs[] = "upd $username: " . $s->error; }
                $s->close();
            } else {
                $avatar  = 'avatar.jpg';
                $tugas   = ''; $gelar_d = ''; $gelar_b = '';
                $ip_val  = '127.0.0.1'; $br_val = 'System Sync';
                $s = $db->prepare(
                    "INSERT INTO admin
                       (pengguna_id, username, email, password, fullname, phone, avatar,
                        gelar_depan, gelar_belakang, level_id, peran_id_str, ptk_id,
                        jenis_ptk_id_str, active, status, tugas_tambahan, time, ip, browser,
                        sync_status, last_sync_at, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                             ?, NOW(), NOW(), NOW())"
                );
                $s->bind_param('ssssssssssssssssssss',
                    $pengguna_id, $username, $email, $password, $fullname, $phone, $avatar,
                    $gelar_d, $gelar_b, $level_id, $peran_id_str, $ptk_id,
                    $jenis_ptk_id_str, $active_flag, $status_flag, $tugas, $now, $ip_val, $br_val,
                    $sync_flag
                );
                if ($s->execute()) {
                    $ins++;
                    if ($force_inactive) {
                        $deactivated++;
                    }
                }
                else { $fail++; $errs[] = "ins $username: " . $s->error; }
                $s->close();
            }
        }

        // Hard safeguard untuk akun PTK: jika ptk_id tidak ada di sync_gtk maka wajib nonaktif
        $deactivate_sql = "
            UPDATE admin a
            LEFT JOIN sync_gtk g ON TRIM(COALESCE(g.ptk_id, '')) = TRIM(COALESCE(a.ptk_id, ''))
            SET
                a.active = 'N',
                a.status = 'Offline',
                a.sync_status = 'manual',
                a.updated_at = NOW()
            WHERE
                a.ptk_id IS NOT NULL
                AND TRIM(a.ptk_id) <> ''
                AND g.ptk_id IS NULL
                AND (COALESCE(a.peran_id_str, '') = 'PTK' OR a.level_id IN (2,3))
                AND UPPER(TRIM(COALESCE(a.active, 'N'))) = 'Y'
        ";
        if ($db->query($deactivate_sql)) {
            $deactivated += (int)$db->affected_rows;
        } else {
            $fail++;
            $errs[] = 'deactivate ptk missing sync_gtk: ' . $db->error;
        }

        // ---------- 3. Update jenis_ptk_id_str + level pada admin yang sudah ada via ptk_id ----------
        foreach ($gtk_jenis as $ptk_id => $jenis_ptk) {
            if (empty($ptk_id)) continue;
            $level_id = (stripos($jenis_ptk, 'Guru') !== false) ? 2 : 3;
            $s = $db->prepare(
                "UPDATE admin SET jenis_ptk_id_str=?, level_id=?
                 WHERE ptk_id=? AND level_id NOT IN (1, 13)"
            );
            $s->bind_param('sss', $jenis_ptk, $level_id, $ptk_id);
            $s->execute();
            $s->close();
        }

        // ---------- 4. Sinkron tugas tambahan Wali Kelas (berdasarkan PTK di sync_rombongan_belajar) ----------
        $waliSync = $this->syncWaliKelasTugasTambahan($db);

        $msg = "admin sync: processed=$proc, updated=$upd, inserted=$ins, deactivated=$deactivated, failed=$fail";
        $msg .= ", wali_synced=" . $waliSync['updated'] . ", wali_level_id=" . $waliSync['level_id'];
        if ($fail > 0) $msg .= ' | ' . implode('; ', $errs);
        return ['success' => $fail === 0, 'message' => $msg];
    }

    /**
     * Sinkronkan tugas tambahan Wali Kelas berdasarkan ptk_id di sync_rombongan_belajar.
     * Tugas lain tetap dipertahankan, hanya token level wali yang disesuaikan otomatis.
     */
    private function syncWaliKelasTugasTambahan($db)
    {
        $waliLevelId = '9';
        $lvl = $db->query("SELECT level_id FROM level WHERE level_nama = 'Wali Kelas' LIMIT 1");
        if ($lvl && ($row = $lvl->fetch_assoc()) && !empty($row['level_id'])) {
            $waliLevelId = (string)$row['level_id'];
        }

        $waliPtk = [];
        $rombel = $db->query(
            "SELECT DISTINCT ptk_id
             FROM sync_rombongan_belajar
             WHERE ptk_id IS NOT NULL AND TRIM(ptk_id) <> ''"
        );
        if ($rombel) {
            while ($r = $rombel->fetch_assoc()) {
                $waliPtk[(string)$r['ptk_id']] = true;
            }
        }

        $updated = 0;
        $admins = $db->query("SELECT admin_id, ptk_id, tugas_tambahan FROM admin");
        if (!$admins) {
            throw new Exception('Query admin untuk sinkron wali kelas gagal: ' . $db->error);
        }

        while ($a = $admins->fetch_assoc()) {
            $adminId = intval($a['admin_id']);
            $ptkId = (string)($a['ptk_id'] ?? '');
            $tokens = $this->parseTugasTambahan((string)($a['tugas_tambahan'] ?? ''));

            $newTokens = [];
            foreach ($tokens as $token) {
                // Hapus token wali lama (contoh: 9 atau 9:xxx) agar bisa dibentuk ulang otomatis
                if ($token === $waliLevelId || strpos($token, $waliLevelId . ':') === 0) {
                    continue;
                }
                $newTokens[] = $token;
            }

            if (!empty($ptkId) && isset($waliPtk[$ptkId])) {
                $newTokens[] = $waliLevelId;
            }

            $newTokens = array_values(array_unique($newTokens));
            $newValue = implode(',', $newTokens);
            $oldValue = implode(',', $tokens);

            if ($newValue !== $oldValue) {
                $u = $db->prepare("UPDATE admin SET tugas_tambahan=?, updated_at=NOW() WHERE admin_id=?");
                $u->bind_param('si', $newValue, $adminId);
                if ($u->execute()) {
                    $updated++;
                }
                $u->close();
            }
        }

        return ['updated' => $updated, 'level_id' => $waliLevelId];
    }

    private function parseTugasTambahan($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') return [];

        $items = explode(',', $raw);
        $clean = [];
        foreach ($items as $it) {
            $it = trim($it);
            if ($it === '') continue;
            $clean[] = $it;
        }

        return array_values(array_unique($clean));
    }
}
