<?php

if (!function_exists('kelulusan_ensure_tables')) {
    function kelulusan_ensure_tables($connection)
    {
        $queries = array();

        $queries[] = "CREATE TABLE IF NOT EXISTS kelulusan_settings (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            is_open ENUM('Y','N') NOT NULL DEFAULT 'N',
            open_at DATETIME NULL,
            close_at DATETIME NULL,
            countdown_to DATETIME NULL,
            announcement_text TEXT NULL,
            updated_by INT NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $queries[] = "CREATE TABLE IF NOT EXISTS kelulusan_status (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            status ENUM('BELUM_DIPUTUSKAN','LULUS','LULUS_BERSYARAT','TIDAK_LULUS') NOT NULL DEFAULT 'BELUM_DIPUTUSKAN',
            catatan TEXT NULL,
            diputuskan_oleh INT NULL,
            diputuskan_pada DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_kelulusan_status_user (user_id),
            KEY idx_kelulusan_status_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $queries[] = "CREATE TABLE IF NOT EXISTS kelulusan_skl (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            is_visible_to_user ENUM('Y','N') NOT NULL DEFAULT 'Y',
            uploaded_by INT NULL,
            uploaded_at DATETIME NOT NULL,
            UNIQUE KEY uniq_kelulusan_skl_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $queries[] = "CREATE TABLE IF NOT EXISTS kelulusan_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            nisn VARCHAR(32) NOT NULL,
            nama_lengkap VARCHAR(255) NOT NULL,
            action ENUM('OPEN_ENVELOPE','DOWNLOAD_SKL') NOT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            meta_json TEXT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_kelulusan_history_user (user_id),
            KEY idx_kelulusan_history_action (action),
            KEY idx_kelulusan_history_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        foreach ($queries as $sql) {
            $connection->query($sql);
        }

        if (!kelulusan_table_has_column($connection, 'kelulusan_settings', 'show_skl_to_user')) {
            $connection->query("ALTER TABLE kelulusan_settings ADD COLUMN show_skl_to_user ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER is_open");
        }

        if (!kelulusan_table_has_column($connection, 'kelulusan_settings', 'allow_download_skl')) {
            $connection->query("ALTER TABLE kelulusan_settings ADD COLUMN allow_download_skl ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER show_skl_to_user");
        }

        if (!kelulusan_table_has_column($connection, 'kelulusan_skl', 'is_visible_to_user')) {
            $connection->query("ALTER TABLE kelulusan_skl ADD COLUMN is_visible_to_user ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER file_path");
        }

        $queries[] = "CREATE TABLE IF NOT EXISTS kelulusan_ijazah (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            nisn VARCHAR(32) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            uploaded_by INT NULL,
            uploaded_at DATETIME NOT NULL,
            konfirmasi ENUM('belum','sesuai','tidak_sesuai') NOT NULL DEFAULT 'belum',
            konfirmasi_at DATETIME NULL,
            catatan_kesalahan TEXT NULL,
            UNIQUE KEY uniq_kelulusan_ijazah_user (user_id),
            KEY idx_kelulusan_ijazah_nisn (nisn),
            KEY idx_kelulusan_ijazah_konfirmasi (konfirmasi)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        foreach ($queries as $sql) {
            $connection->query($sql);
        }

        $now = date('Y-m-d H:i:s');
        $connection->query("INSERT IGNORE INTO kelulusan_settings (id, is_open, updated_at) VALUES (1, 'N', '" . $connection->real_escape_string($now) . "')");
    }
}

if (!function_exists('kelulusan_get_settings')) {
    function kelulusan_get_settings($connection)
    {
        kelulusan_ensure_tables($connection);
        $result = $connection->query("SELECT * FROM kelulusan_settings WHERE id=1 LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!isset($row['show_skl_to_user'])) {
                $row['show_skl_to_user'] = 'Y';
            }
            if (!isset($row['allow_download_skl'])) {
                $row['allow_download_skl'] = 'Y';
            }
            return $row;
        }

        return array(
            'id' => 1,
            'is_open' => 'N',
            'show_skl_to_user' => 'Y',
            'allow_download_skl' => 'Y',
            'open_at' => null,
            'close_at' => null,
            'countdown_to' => null,
            'announcement_text' => ''
        );
    }
}

if (!function_exists('kelulusan_is_open_now')) {
    function kelulusan_is_open_now($settings)
    {
        // Toggle is the single source of truth for opening/closing the system.
        // Scheduled times remain stored for countdown/informational use only.
        return is_array($settings) && isset($settings['is_open']) && $settings['is_open'] === 'Y';
    }
}

if (!function_exists('kelulusan_status_label')) {
    function kelulusan_status_label($status)
    {
        switch ((string) $status) {
            case 'LULUS':
                return 'Lulus';
            case 'LULUS_BERSYARAT':
                return 'Lulus Bersyarat';
            case 'TIDAK_LULUS':
                return 'Tidak Lulus';
            default:
                return 'Belum Diputuskan';
        }
    }
}

if (!function_exists('kelulusan_status_badge_class')) {
    function kelulusan_status_badge_class($status)
    {
        switch ((string) $status) {
            case 'LULUS':
                return 'success';
            case 'LULUS_BERSYARAT':
                return 'warning';
            case 'TIDAK_LULUS':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}

if (!function_exists('kelulusan_table_has_column')) {
    function kelulusan_table_has_column($connection, $tableName, $columnName)
    {
        $tableSafe = $connection->real_escape_string($tableName);
        $colSafe = $connection->real_escape_string($columnName);
        $q = $connection->query("SHOW COLUMNS FROM `" . $tableSafe . "` LIKE '" . $colSafe . "'");
        return ($q && $q->num_rows > 0);
    }
}

if (!function_exists('kelulusan_get_grade12_class_ids')) {
    function kelulusan_get_grade12_class_ids($connection)
    {
        $ids = array();

        $tableCheck = $connection->query("SHOW TABLES LIKE 'kelas'");
        if (!($tableCheck && $tableCheck->num_rows > 0)) {
            return $ids;
        }

        $hasTingkat = kelulusan_table_has_column($connection, 'kelas', 'tingkat_pendidikan_id');
        $hasNamaKelas = kelulusan_table_has_column($connection, 'kelas', 'nama_kelas');

        if ($hasTingkat) {
            $q = "SELECT DISTINCT kelas_id FROM kelas WHERE tingkat_pendidikan_id IN (12, 3)";
            if ($hasNamaKelas) {
                $q .= " OR UPPER(nama_kelas) REGEXP '(^|[^A-Z])XII([^A-Z]|$)|(^|[^0-9])12([^0-9]|$)'";
            }
            $r = $connection->query($q);
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $ids[] = (string) $row['kelas_id'];
                }
            }
        } elseif ($hasNamaKelas) {
            $r = $connection->query("SELECT DISTINCT kelas_id FROM kelas WHERE UPPER(nama_kelas) REGEXP '(^|[^A-Z])XII([^A-Z]|$)|(^|[^0-9])12([^0-9]|$)|(^|[^0-9])3([^0-9]|$)'");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $ids[] = (string) $row['kelas_id'];
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids, function ($v) {
            return $v !== '';
        })));

        return $ids;
    }
}

if (!function_exists('kelulusan_user_kelas_column')) {
    function kelulusan_user_kelas_column($connection)
    {
        if (kelulusan_table_has_column($connection, 'user', 'kelas')) {
            return 'kelas';
        }
        if (kelulusan_table_has_column($connection, 'user', 'kelas_id')) {
            return 'kelas_id';
        }
        return 'kelas';
    }
}

if (!function_exists('kelulusan_get_final_grade_students')) {
    function kelulusan_get_final_grade_students($connection)
    {
        $kelasCol = kelulusan_user_kelas_column($connection);
        $classIds = kelulusan_get_grade12_class_ids($connection);

        $where = "WHERE LOWER(COALESCE(u.status,''))='aktif'";
        if (!empty($classIds)) {
            $safe = array();
            foreach ($classIds as $id) {
                $safe[] = "'" . $connection->real_escape_string($id) . "'";
            }
            $where .= " AND u.`" . $kelasCol . "` IN (" . implode(',', $safe) . ")";
        }

        // Check if berkas table exists before joining
        $berkasJoin = '';
        $berkasSelect = ', NULL AS validasi_berkas';
        $bCheck = $connection->query("SHOW TABLES LIKE 'berkas'");
        if ($bCheck && $bCheck->num_rows > 0) {
            $berkasJoin = "LEFT JOIN berkas b ON b.user_id = u.user_id";
            $berkasSelect = ', b.validasi_berkas';
        }

        $sql = "SELECT
                    u.user_id,
                    u.nisn,
                    u.nama_lengkap,
                    u.tanggal_lahir,
                    u.`" . $kelasCol . "` AS kelas_id,
                    k.nama_kelas,
                    ks.status AS status_kelulusan,
                    ks.catatan,
                    ks.diputuskan_pada,
                    a.fullname AS diputuskan_oleh,
                    skl.file_name,
                    skl.file_path,
                    skl.is_visible_to_user
                    " . $berkasSelect . "
                FROM user u
                LEFT JOIN kelas k ON u.`" . $kelasCol . "` = k.kelas_id
                LEFT JOIN kelulusan_status ks ON ks.user_id = u.user_id
                LEFT JOIN admin a ON a.admin_id = ks.diputuskan_oleh
                LEFT JOIN kelulusan_skl skl ON skl.user_id = u.user_id
                " . $berkasJoin . "
                " . $where . "
                ORDER BY k.nama_kelas ASC, u.nama_lengkap ASC";

        $rows = array();
        $r = $connection->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                if (empty($row['status_kelulusan'])) {
                    $row['status_kelulusan'] = 'BELUM_DIPUTUSKAN';
                }
                if (empty($row['is_visible_to_user'])) {
                    $row['is_visible_to_user'] = 'Y';
                }
                if (!isset($row['validasi_berkas'])) {
                    $row['validasi_berkas'] = null;
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('kelulusan_find_student_by_identity')) {
    function kelulusan_find_student_by_identity($connection, $nisn, $birthDate)
    {
        $kelasCol = kelulusan_user_kelas_column($connection);
        $classIds = kelulusan_get_grade12_class_ids($connection);

        $whereClass = '';
        if (!empty($classIds)) {
            $safe = array();
            foreach ($classIds as $id) {
                $safe[] = "'" . $connection->real_escape_string($id) . "'";
            }
            $whereClass = " AND u.`" . $kelasCol . "` IN (" . implode(',', $safe) . ")";
        }

        $sql = "SELECT
                    u.user_id,
                    u.nisn,
                    u.nama_lengkap,
                    u.tanggal_lahir,
                    u.`" . $kelasCol . "` AS kelas_id,
                    k.nama_kelas,
                    COALESCE(ks.status, 'BELUM_DIPUTUSKAN') AS status_kelulusan,
                    ks.catatan,
                    ks.diputuskan_pada,
                    skl.file_name,
                    skl.file_path,
                    skl.is_visible_to_user
                FROM user u
                LEFT JOIN kelas k ON u.`" . $kelasCol . "` = k.kelas_id
                LEFT JOIN kelulusan_status ks ON ks.user_id = u.user_id
                LEFT JOIN kelulusan_skl skl ON skl.user_id = u.user_id
                WHERE u.nisn='" . $connection->real_escape_string($nisn) . "'
                  AND LOWER(COALESCE(u.status,''))='aktif'"
                . $whereClass .
                " LIMIT 1";

        $r = $connection->query($sql);
        if ($r && $r->num_rows > 0) {
            $row = $r->fetch_assoc();
            $inputDate = kelulusan_normalize_date($birthDate);
            $dbDate = kelulusan_normalize_date(isset($row['tanggal_lahir']) ? $row['tanggal_lahir'] : '');

            if ($inputDate !== '' && $dbDate !== '' && $inputDate === $dbDate) {
                if (empty($row['is_visible_to_user'])) {
                    $row['is_visible_to_user'] = 'Y';
                }
                return $row;
            }
        }

        return null;
    }
}

if (!function_exists('kelulusan_normalize_date')) {
    function kelulusan_normalize_date($value)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $normalized = str_replace(array('/', '.'), '-', $raw);
        $normalized = preg_replace('/\s+/', '', $normalized);

        $formats = array('Y-m-d', 'd-m-Y', 'd-m-y', 'Ymd', 'dmY', 'dmy');
        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $normalized);
            if ($dt && $dt->format($fmt) === $normalized) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($normalized);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return '';
    }
}

if (!function_exists('kelulusan_log_history')) {
    function kelulusan_log_history($connection, $studentRow, $action, $meta = array())
    {
        $userId = isset($studentRow['user_id']) ? (int) $studentRow['user_id'] : 0;
        if ($userId <= 0) {
            return;
        }

        $nisn = isset($studentRow['nisn']) ? $studentRow['nisn'] : '';
        $nama = isset($studentRow['nama_lengkap']) ? $studentRow['nama_lengkap'] : '';
        $action = ($action === 'DOWNLOAD_SKL') ? 'DOWNLOAD_SKL' : 'OPEN_ENVELOPE';

        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 250) : '';
        $created = date('Y-m-d H:i:s');
        $metaJson = !empty($meta) ? json_encode($meta) : null;

        // Keep one history row per user+action by updating existing records.
        $checkStmt = $connection->prepare("SELECT id FROM kelulusan_history WHERE user_id=? AND action=? ORDER BY id DESC LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param('is', $userId, $action);
            $checkStmt->execute();
            $res = $checkStmt->get_result();
            if ($res && $res->num_rows > 0) {
                $existing = $res->fetch_assoc();
                $hid = (int) $existing['id'];
                $checkStmt->close();

                $updStmt = $connection->prepare("UPDATE kelulusan_history SET nisn=?, nama_lengkap=?, ip_address=?, user_agent=?, meta_json=?, created_at=? WHERE id=?");
                if ($updStmt) {
                    $updStmt->bind_param('ssssssi', $nisn, $nama, $ip, $ua, $metaJson, $created, $hid);
                    $updStmt->execute();
                    $updStmt->close();
                }
                return;
            }
            $checkStmt->close();
        }

        $insStmt = $connection->prepare("INSERT INTO kelulusan_history (user_id, nisn, nama_lengkap, action, ip_address, user_agent, meta_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insStmt) {
            $insStmt->bind_param('isssssss', $userId, $nisn, $nama, $action, $ip, $ua, $metaJson, $created);
            $insStmt->execute();
            $insStmt->close();
        }
    }
}

if (!function_exists('kelulusan_upsert_status')) {
    function kelulusan_upsert_status($connection, $userId, $status, $catatan, $adminId)
    {
        $allowed = array('BELUM_DIPUTUSKAN', 'LULUS', 'LULUS_BERSYARAT', 'TIDAK_LULUS');
        if (!in_array($status, $allowed, true)) {
            $status = 'BELUM_DIPUTUSKAN';
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $connection->prepare("INSERT INTO kelulusan_status (user_id, status, catatan, diputuskan_oleh, diputuskan_pada, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status), catatan=VALUES(catatan), diputuskan_oleh=VALUES(diputuskan_oleh), diputuskan_pada=VALUES(diputuskan_pada), updated_at=VALUES(updated_at)");
        if ($stmt) {
            $stmt->bind_param('ississs', $userId, $status, $catatan, $adminId, $now, $now, $now);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }

        return false;
    }
}

if (!function_exists('kelulusan_upsert_skl')) {
    function kelulusan_upsert_skl($connection, $userId, $fileName, $filePath, $adminId)
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $connection->prepare("INSERT INTO kelulusan_skl (user_id, file_name, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE file_name=VALUES(file_name), file_path=VALUES(file_path), uploaded_by=VALUES(uploaded_by), uploaded_at=VALUES(uploaded_at)");
        if ($stmt) {
            $stmt->bind_param('issis', $userId, $fileName, $filePath, $adminId, $now);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }

        return false;
    }
}

if (!function_exists('kelulusan_set_skl_visibility')) {
    function kelulusan_set_skl_visibility($connection, $userId, $isVisible)
    {
        $visible = ($isVisible === 'N') ? 'N' : 'Y';
        $stmt = $connection->prepare("UPDATE kelulusan_skl SET is_visible_to_user=? WHERE user_id=?");
        if ($stmt) {
            $stmt->bind_param('si', $visible, $userId);
            $stmt->execute();
            $ok = ($stmt->affected_rows >= 0);
            $stmt->close();
            return $ok;
        }

        return false;
    }
}

if (!function_exists('kelulusan_is_user_berkas_valid')) {
    function kelulusan_is_user_berkas_valid($connection, $userId)
    {
        $uid = (int) $userId;
        if ($uid <= 0) {
            return false;
        }

        $q = $connection->query("SELECT validasi_berkas FROM berkas WHERE user_id='" . intval($uid) . "' LIMIT 1");
        if (!$q || $q->num_rows === 0) {
            return false;
        }

        $row = $q->fetch_assoc();
        $status = strtolower(trim((string) ($row['validasi_berkas'] ?? '')));
        return ($status === 'valid');
    }
}

if (!function_exists('kelulusan_sync_skl_visibility_by_berkas')) {
    function kelulusan_sync_skl_visibility_by_berkas($connection, $userId)
    {
        $uid = (int) $userId;
        if ($uid <= 0) {
            return false;
        }

        $qSkl = $connection->query("SELECT user_id FROM kelulusan_skl WHERE user_id='" . intval($uid) . "' LIMIT 1");
        if (!$qSkl || $qSkl->num_rows === 0) {
            return false;
        }

        $isValid = kelulusan_is_user_berkas_valid($connection, $uid);
        return kelulusan_set_skl_visibility($connection, $uid, $isValid ? 'Y' : 'N');
    }
}
