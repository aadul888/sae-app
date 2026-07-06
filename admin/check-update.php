<?php
/**
 * ONE-TIME diagnostic for update notification.
 * DELETE this file after check.
 */
require_once '../library/config.php';
include_once '../library/function.php';
require_once '../library/version.php';

echo "<pre>\n";
echo "SAE_VERSION: " . (defined('SAE_VERSION') ? SAE_VERSION : 'UNDEFINED') . "\n";
echo "connection: " . (isset($connection) && $connection ? 'OK' : 'NULL') . "\n";

if (isset($connection) && $connection) {
    $q = $connection->query("SELECT version, pembaharuan FROM pembaharuan ORDER BY release_date DESC LIMIT 1");
    if ($q) {
        $r = $q->fetch_assoc();
        if ($r) {
            echo "latest version in DB: " . $r['version'] . "\n";
            echo "pembaharuan: " . substr($r['pembaharuan'] ?? '', 0, 100) . "\n";
            echo "version_compare: " . (version_compare(SAE_VERSION, $r['version'], '<') ? 'UPDATE AVAILABLE' : 'NO UPDATE (local >= db)') . "\n";
        } else {
            echo "ERROR: pembaharuan table is empty\n";
        }
    } else {
        echo "ERROR: query failed - " . $connection->error . "\n";
    }
}

echo "\n--- header.php check ---\n";
$header = file_get_contents(__DIR__ . '/mod/header.php');
echo "sweetalert2 CDN: " . (strpos($header, 'sweetalert2@11') !== false ? 'YES' : 'MISSING') . "\n";
echo "Swal.fire: " . (strpos($header, 'Swal.fire') !== false ? 'YES' : 'MISSING') . "\n";

echo "\n--- file timestamps ---\n";
echo "header.php: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/mod/header.php')) . "\n";
echo "proses.php: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/mod/lisensi_pembaruan/proses.php')) . "\n";
echo "</pre>\n";
