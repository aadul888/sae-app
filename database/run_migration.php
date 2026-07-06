<?php
echo "Starting migration...\n";
// Bypass session_start in config for CLI
if (PHP_SAPI === 'cli') {
    define('SESSION_NO_START', true);
}
// Create connection manually to avoid session issues
$DB_HOST = 'localhost';
$DB_NAME = 'saev5';
$DB_USER = 'root';
$DB_PASSWD = '';
$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASSWD, $DB_NAME);
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error . "\n");
}
echo "Config loaded.\n";

$migration = file_get_contents(__DIR__ . '/migration_per_dokumen_validasi.sql');
echo "Migration SQL loaded, length: " . strlen($migration) . "\n";

// Check if columns already exist
$check = $connection->query("SHOW COLUMNS FROM `berkas` LIKE 'kk_valid'");
if ($check && $check->num_rows > 0) {
    echo "Columns already exist. Skipping migration.\n";
} else {
    // Run migration using multi_query for multiple ALTER statements
    if ($connection->multi_query($migration)) {
        echo "Migration OK\n";
        // flush results
        while ($connection->next_result()) {;}
    } else {
        echo "Migration Error: " . $connection->error . "\n";
    }
}
$connection->close();
echo "Done.\n";
