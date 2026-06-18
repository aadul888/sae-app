<?php ob_start();
session_start();
include_once 'library/config.php';
include_once 'library/function.php';

if (!function_exists('sae_table_has_rows')) {
  function sae_table_has_rows($connection, $table_name)
  {
    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table_name);
    if ($table_name === '') {
      return false;
    }

    $table_check = $connection->query("SHOW TABLES LIKE '" . $connection->real_escape_string($table_name) . "'");
    if (!$table_check || !$table_check->num_rows) {
      return false;
    }

    $count_result = $connection->query("SELECT 1 FROM `{$table_name}` LIMIT 1");
    return $count_result && $count_result->num_rows > 0;
  }
}

if (!function_exists('sae_sync_bootstrap_tables')) {
  function sae_sync_bootstrap_tables()
  {
    return [
      'getSekolah' => 'sync_sekolah',
      'getGtk' => 'sync_gtk',
      'getRombonganBelajar' => 'sync_rombongan_belajar',
      'getPesertaDidik' => 'sync_peserta_didik',
      'getPengguna' => 'sync_pengguna'
    ];
  }
}

if (!function_exists('sae_sync_bootstrap_required')) {
  function sae_sync_bootstrap_required($connection)
  {
    foreach (sae_sync_bootstrap_tables() as $endpoint => $table_name) {
      if (!sae_table_has_rows($connection, $table_name)) {
        return true;
      }

      $status_query = "SELECT status FROM sync_log WHERE endpoint='" . $connection->real_escape_string($endpoint) . "' ORDER BY created_at DESC, id DESC LIMIT 1";
      $status_result = $connection->query($status_query);
      $status_row = $status_result ? $status_result->fetch_assoc() : null;
      if (!$status_row || ($status_row['status'] ?? '') !== 'success') {
        return true;
      }
    }

    return false;
  }
}

if (!function_exists('sae_sync_bootstrap_completed')) {
  function sae_sync_bootstrap_completed($connection)
  {
    foreach (sae_sync_bootstrap_tables() as $endpoint => $table_name) {
      if (!sae_table_has_rows($connection, $table_name)) {
        return false;
      }

      $status_query = "SELECT status FROM sync_log WHERE endpoint='" . $connection->real_escape_string($endpoint) . "' ORDER BY created_at DESC, id DESC LIMIT 1";
      $status_result = $connection->query($status_query);
      $status_row = $status_result ? $status_result->fetch_assoc() : null;
      if (!$status_row || ($status_row['status'] ?? '') !== 'success') {
        return false;
      }
    }

    return true;
  }
}

// Cek status sistem untuk maintenance
checkSystemMaintenance();

//ob_start("minify_html");
$website_url        = $row_site['site_url'];
$website_name       = $row_site['site_name'];
$website_phone      = $row_site['site_phone'];
$website_addres     = $row_site['site_address'];
$website_logo       = $row_site['site_logo'];
$website_email      = $row_site['site_email'];

if (!empty($_GET['id'])) {
  $nisn = convert("encrypt", strip_tags($_GET['id']));
  header('location:./nisn/' . $nisn . '');
}

// Determine requested module and normalize it (remove leading/trailing slashes)
$mod = "home";
if (!empty($_GET['mod'])) {
  // Normalize route segments without stripping valid leading characters.
  $requested = trim((string)$_GET['mod']);
  $requested = trim($requested, "/\\");
  $mod = $requested !== '' ? htmlentities($requested) : 'home';
} else {
  $mod = 'home';
}

$bootstrap_required = sae_sync_bootstrap_required($connection);
$bootstrap_completed = sae_sync_bootstrap_completed($connection);
if (!$bootstrap_completed && $mod !== 'registrasi') {
  sae_redirect_to_registrasi();
}

if ($bootstrap_completed && $mod === 'registrasi') {
  header('location:./home/');
  exit;
}

require_once 'module/header.php';
// Support module subpages, e.g. /pra-spmb/form -> module/pra-spmb/form.php
$included = false;
if (strpos($mod, '/') !== false) {
  $parts = explode('/', $mod);
  $a = preg_replace('/[^a-zA-Z0-9_\-]/', '', $parts[0]);
  $b = preg_replace('/[^a-zA-Z0-9_\-]/', '', $parts[1] ?? '');
  if ($a && $b && file_exists("module/$a/$b.php")) {
    require_once("module/$a/$b.php");
    $included = true;
  }
}

if (!$included) {
  if (file_exists("module/$mod/$mod.php")) {
    require_once("module/$mod/$mod.php");
  } else {
    require_once("module/home/home.php"); // Fallback ke home.php
  }
}
require_once 'module/footer.php';
