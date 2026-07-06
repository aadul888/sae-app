<?php
// SSO configuration for SAE -> PKL
// Keep secret strong and identical on both systems
defined('SSO_SECRET') || define('SSO_SECRET', '9f8b7c6d5e4a3b2c1d0e9f8b7c6d5e4a3b2c1d0e9f8b7c6d5e4a3b2c1d0e9f8b');
// PKL receive endpoint (can override via environment variable on hosting)
$pkl_sso_url_env = getenv('PKL_SSO_URL');
if (!$pkl_sso_url_env) {
	$pkl_sso_url_env = 'http://localhost/pklv2/sso/receive_sae.php';
}
defined('PKL_SSO_URL') || define('PKL_SSO_URL', $pkl_sso_url_env);

// Default targets after successful SSO on PKL
defined('PKL_USER_TARGET') || define('PKL_USER_TARGET', '/?mod=home');
defined('PKL_ADMIN_TARGET') || define('PKL_ADMIN_TARGET', '/sw-admin/');
// SAE local SSO redirect endpoint (built from base_url())
if (!defined('SAE_SSO_REDIRECT')) {
	if (isset($base_url) && $base_url) {
		// Remove common subpaths like /dashboard if present so redirect points to app root
		$app_root = preg_replace('#/(dashboard|admin)(/.*)?$#', '', rtrim($base_url, '/'));
		define('SAE_SSO_REDIRECT', $app_root . '/sso/redirect_student.php');
	} else {
		define('SAE_SSO_REDIRECT', '/sso/redirect_student.php');
	}
}
