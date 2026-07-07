<?php
/**
 * SAE Version Configuration
 * -------------------------
 * Format versi aplikasi mengikuti semester aktif dari data sinkronisasi:
 *   v20252.1, v20252.2, ... v20252.9
 *   v20251.1, v20251.2, ... v20251.9
 *
 * semester_id diambil otomatis dari tabel sync_rombongan_belajar.
 * Revisi rilis kecil dalam semester aktif diatur lewat SAE_VERSION_REVISION.
 */

if (!function_exists('sae_normalize_version_revision')) {
	function sae_normalize_version_revision($revision)
	{
		$revision = (int)$revision;

		if ($revision < 1) {
			return 1;
		}

		if ($revision > 9) {
			return 9;
		}

		return $revision;
	}
}

if (!function_exists('sae_get_default_semester_id')) {
	function sae_get_default_semester_id()
	{
		$currentYear = (int)date('Y');
		$currentMonth = (int)date('n');

		if ($currentMonth >= 7) {
			return (string)$currentYear . '1';
		}

		return (string)($currentYear - 1) . '2';
	}
}

if (!function_exists('sae_get_version_semester_id')) {
	function sae_get_version_semester_id($connection = null)
	{
		$fallbackSemesterId = sae_get_default_semester_id();

		if (!($connection instanceof mysqli)) {
			return $fallbackSemesterId;
		}

		$query = "
			SELECT MAX(CASE
				WHEN semester_id REGEXP '^[0-9]{5}$' THEN semester_id
				ELSE NULL
			END) AS semester_id
			FROM sync_rombongan_belajar
		";

		$result = $connection->query($query);
		if (!$result instanceof mysqli_result) {
			return $fallbackSemesterId;
		}

		$row = $result->fetch_assoc();
		$result->free();

		$semesterId = trim((string)($row['semester_id'] ?? ''));
		if (!preg_match('/^[0-9]{5}$/', $semesterId)) {
			return $fallbackSemesterId;
		}

		return $semesterId;
	}
}

$saeVersionRevision = sae_normalize_version_revision(defined('SAE_VERSION_REVISION') ? SAE_VERSION_REVISION : 1);
$saeSemesterId = sae_get_version_semester_id(isset($connection) ? $connection : null);
$saeAppYear = substr($saeSemesterId, 0, 4);

if (!defined('SAE_SEMESTER_ID')) define('SAE_SEMESTER_ID', $saeSemesterId);
if (!defined('SAE_VERSION_REVISION')) define('SAE_VERSION_REVISION', (string)$saeVersionRevision);
if (!defined('SAE_VERSION')) define('SAE_VERSION', 'v' . SAE_SEMESTER_ID . '.' . SAE_VERSION_REVISION);
if (!defined('SAE_APP_YEAR')) define('SAE_APP_YEAR', $saeAppYear);
if (!defined('SAE_APP_NAME')) define('SAE_APP_NAME', 'Smart Apps Education');
