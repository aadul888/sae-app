<?php
/**
 * GitHub Configuration
 * Dipisah dari config.php agar webhook tidak perlu koneksi DB.
 * File ini tidak boleh diakses langsung via web (dilindungi .htaccess).
 */
if (!defined('GITHUB_TOKEN')) define('GITHUB_TOKEN', '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');
if (!defined('GITHUB_WEBHOOK_SECRET')) define('GITHUB_WEBHOOK_SECRET', 'cd3c2eac42000a6bf8c4f2a1f3c60dd0');
