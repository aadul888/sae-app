<?php
/**
 * Auto Database Migration Runner
 * Panggil setelah deploy untuk menjalankan migrasi SQL yang belum dijalankan.
 *
 * Cara kerja:
 * - Cek tabel `migrations` (batch tracking)
 * - Scan folder __DIR__ . '/../database/migrations/' untuk file *.sql
 * - Jalankan yang belum tercatat di tabel migrations
 *
 * Usage: require_once __DIR__ . '/migrate.php';
 * Return: array ['success' => bool, 'ran' => int, 'errors' => array]
 */

function run_pending_migrations($connection) {
    $result = ['success' => true, 'ran' => 0, 'errors' => []];

    // Buat tabel migrations jika belum ada
    $connection->query("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `batch` INT UNSIGNED NOT NULL DEFAULT 1,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Cari batch terakhir
    $q_batch = $connection->query("SELECT COALESCE(MAX(batch), 0) AS last_batch FROM `migrations`");
    $batch = $q_batch ? (int)$q_batch->fetch_assoc()['last_batch'] + 1 : 1;

    $migrations_dir = __DIR__ . '/../database/migrations';
    if (!is_dir($migrations_dir)) {
        return $result; // No migrations folder yet
    }

    $files = glob($migrations_dir . '/*.sql');
    sort($files);

    foreach ($files as $filepath) {
        $filename = basename($filepath);

        // Cek sudah dijalankan
        $q_check = $connection->query("SELECT id FROM `migrations` WHERE migration = '" . $connection->real_escape_string($filename) . "' LIMIT 1");
        if ($q_check && $q_check->num_rows > 0) {
            continue; // Already ran
        }

        $sql = file_get_contents($filepath);
        if (trim($sql) === '') continue;

        // Jalankan multi-query
        if ($connection->multi_query($sql)) {
            $all_ok = true;
            do {
                $rs = $connection->store_result();
                if ($rs) $rs->free();
                // Cek error pada setiap result set
                if ($connection->error && $connection->errno != 0) {
                    $result['errors'][] = $filename . ' (sub-query): ' . $connection->error;
                    $all_ok = false;
                }
            } while ($connection->more_results() && $connection->next_result());

            if ($all_ok) {
                // Catat sukses
                $connection->query("INSERT INTO `migrations` (`migration`, `batch`) VALUES ('" . $connection->real_escape_string($filename) . "', $batch)");
                $result['ran']++;
            } else {
                $result['success'] = false;
            }
        } else {
            $result['success'] = false;
            $result['errors'][] = $filename . ': ' . $connection->error;
            // Skip ke result berikutnya karena multi_query mungkin masih pending
            while ($connection->more_results() && $connection->next_result()) {
                $rs = $connection->store_result();
                if ($rs) $rs->free();
            }
        }
    }

    return $result;
}
