<?php
/**
 * Activity Log Library
 * Mencatat aktivitas admin ke tabel activity_log.
 *
 * Usage:
 *   require_once __DIR__ . '/activity.php';
 *   log_activity($connection, $admin_id, $admin_name, $action, $description);
 */

function log_activity($connection, $admin_id, $admin_name, $action, $description = '') {
    // Cek apakah tabel activity_log sudah ada
    static $table_exists = null;
    if ($table_exists === null) {
        $q = $connection->query("SHOW TABLES LIKE 'activity_log'");
        $table_exists = $q && $q->num_rows > 0;
    }
    if (!$table_exists) return false;

    $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? (PHP_SAPI === 'cli' ? 'CLI' : '');
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (strlen($ua) > 500) $ua = substr($ua, 0, 500);

    $sql = "INSERT INTO activity_log (admin_id, admin_name, action, description, ip_address, request_method, user_agent, created_at)
            VALUES (
                " . ($admin_id ? intval($admin_id) : 'NULL') . ",
                '" . $connection->real_escape_string($admin_name ?? '') . "',
                '" . $connection->real_escape_string($action) . "',
                '" . $connection->real_escape_string($description) . "',
                '" . $connection->real_escape_string($ip) . "',
                '" . $connection->real_escape_string($method) . "',
                '" . $connection->real_escape_string($ua) . "',
                NOW()
            )";
    return $connection->query($sql);
}

function get_recent_activities($connection, $limit = 20) {
    $limit = intval($limit);
    $q = $connection->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT $limit");
    $rows = [];
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    return $rows;
}
