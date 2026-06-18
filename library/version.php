<?php
/**
 * SAE Version Configuration
 * -------------------------
 * Update SAE_VERSION_PATCH to increment:  0 → a → b → c → ...
 * Update SAE_VERSION_MAJOR for new major: 5 → 6 → 7 → ...
 *
 * Example progression:
 *   v5.0  → v5.a → v5.b → v5.c → v6.0 → ...
 */

if (!defined('SAE_VERSION_MAJOR')) define('SAE_VERSION_MAJOR', '5');
if (!defined('SAE_VERSION_PATCH')) define('SAE_VERSION_PATCH', '0');
if (!defined('SAE_VERSION'))       define('SAE_VERSION', 'v' . SAE_VERSION_MAJOR . '.' . SAE_VERSION_PATCH);
if (!defined('SAE_APP_YEAR'))      define('SAE_APP_YEAR', '2025');
if (!defined('SAE_APP_NAME'))      define('SAE_APP_NAME', 'Smart Apps Education');
