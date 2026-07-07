<?php
// One-off script to rebuild kelas.wali_kelas_nama from admin table
// Usage: php sync_wali_kelas_fix.php

// Use __DIR__ so script works from CLI regardless of current working directory
require_once __DIR__ . '/../../../library/config.php';
require_once __DIR__ . '/../../../library/function.php';

$totalUpdated = 0;
$errors = [];

$sql = "SELECT admin_id, ptk_id, fullname, gelar_depan, gelar_belakang FROM admin WHERE ptk_id IS NOT NULL AND ptk_id != ''";
$res = $connection->query($sql);
if (!$res) {
    echo "Error querying admin: " . $connection->error . PHP_EOL;
    exit(1);
}

while ($row = $res->fetch_assoc()) {
    $ptk = $connection->real_escape_string($row['ptk_id']);
    $fullname = trim($row['fullname']);
    $gelar_depan = trim($row['gelar_depan']);
    $gelar_belakang = trim($row['gelar_belakang']);

    $display = trim(($gelar_depan !== '' ? $gelar_depan . ' ' : '') . $fullname);
    if ($gelar_belakang !== '') {
        $display .= ', ' . $gelar_belakang;
    }

    $display_safe = $connection->real_escape_string($display);

    $u = "UPDATE kelas SET wali_kelas_nama = '$display_safe' WHERE wali_kelas_ptk_id = '$ptk'";
    if ($connection->query($u) === false) {
        $errors[] = "Failed to update ptk_id=$ptk: " . $connection->error;
        continue;
    }

    $affected = $connection->affected_rows;
    if ($affected > 0) {
        echo "Updated $affected class(es) for ptk_id=$ptk -> $display" . PHP_EOL;
        $totalUpdated += $affected;
    }
}

echo "\nDone. Total kelas rows updated: $totalUpdated" . PHP_EOL;
if (!empty($errors)) {
    echo "\nErrors:\n" . implode("\n", $errors) . PHP_EOL;
}

return 0;
