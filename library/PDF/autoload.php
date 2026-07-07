<?php

/**
 * PDF autoloader (PHP 8.3+ compatible).
 *
 * The bundled mpdf under library/PDF/mpdf is version 8.0.0 and uses the
 * curly-brace string offset syntax ($str{0}) that PHP 8.0 removed, so it
 * fatals on PHP 8.3. The project already ships a maintained mpdf 8.2.6 (plus
 * PhpSpreadsheet, etc.) under admin/assets/vendor, which is fully PHP 8.3
 * compatible. We delegate to that Composer autoloader so every consumer of
 * library/PDF/autoload.php transparently gets the working mpdf 8.2.6.
 */

$modernVendor = __DIR__ . '/../../admin/assets/vendor/autoload.php';
if (is_file($modernVendor)) {
    return require_once $modernVendor;
}

// Fallback to the legacy bundled Composer autoloader (mpdf 8.0.0).
// NOTE: that mpdf version is NOT PHP 8.3 compatible and will fatal when used.
require_once __DIR__ . '/composer/autoload_real.php';
return ComposerAutoloaderInit60a6e8f90d963c6d1629166fe124ab57::getLoader();
