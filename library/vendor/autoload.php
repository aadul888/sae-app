<?php

/**
 * Compatibility shim (PHP 8.3+).
 *
 * Several report/print pages (e.g. admin/mod/laporan-absensi*) include
 * library/vendor/autoload.php, but no Composer vendor tree was ever installed
 * here. The maintained dependencies (mpdf 8.2.6, PhpSpreadsheet, ...) live
 * under admin/assets/vendor, so we delegate to that autoloader. This keeps the
 * legacy include paths working without shipping a second vendor copy.
 */

$modernVendor = __DIR__ . '/../../admin/assets/vendor/autoload.php';
if (!is_file($modernVendor)) {
    throw new \RuntimeException('Composer vendor autoloader not found at ' . $modernVendor);
}

return require_once $modernVendor;
