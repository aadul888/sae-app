<?php
/**
 * Diagnostic: update notification check.
 * Hapus file ini setelah debugging.
 */
$reason = '';

if (!isset($connection) || !$connection) {
    $reason = 'connection not available';
} elseif (!defined('SAE_VERSION')) {
    $reason = 'SAE_VERSION not defined';
} else {
    $q = $connection->query("SELECT version, pembaharuan FROM pembaharuan ORDER BY release_date DESC LIMIT 1");
    if (!$q) {
        $reason = 'query failed: ' . $connection->error;
    } elseif (!$r = $q->fetch_assoc()) {
        $reason = 'no rows in pembaharuan table';
    } elseif (!version_compare(SAE_VERSION, $r['version'], '<')) {
        $reason = 'SAE_VERSION (' . SAE_VERSION . ') >= latest version (' . $r['version'] . ')';
    } else {
        $reason = 'OK - update available: ' . $r['version'];
    }
}
echo '<div id="update-debug" style="display:none" data-reason="' . htmlspecialchars($reason) . '"></div>';
