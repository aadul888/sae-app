<?php
// Catatan: file ini WAJIB di-include dengan require_once. Include ganda (include)
// memicu "Cannot redeclare function" fatal karena fungsi top-level dikompilasi
// sebelum guard runtime sempat berjalan. Semua pemanggil sudah pakai require_once.

$date     = DATE('Y-m-d');
$day      = DATE('d');
$day_en   = DATE('l');
$month_en = DATE('F');
$month    = DATE('m');
$year     = DATE('Y');
$time     = DATE('H:i:s');
$timeNow  = DATE('Y-m-d H:i:s');
$timein   = time();
setlocale(LC_ALL, 'id_ID');

// Centralized debug logging helper. Uses APP_DEBUG from config.php to decide whether to log.
if (!function_exists('debug_log')) {
  function debug_log($message)
  {
    // Disabled during troubleshooting: suppress all debug logging to avoid
    // interfering with JSON endpoints and client polling.
    return;
  }
}


/**
 * Mengembalikan path absolut ke folder content/ project.
 * Aman digunakan di Apache maupun Nginx/PHP-FPM karena
 * tidak bergantung pada getcwd().
 * 
 * @param string $sub Sub-path opsional (e.g. 'avatar/', 'assets/logo-jurusan/')
 * @return string Path absolut ke content/ atau sub-folder-nya
 */
if (!function_exists('content_path')) {
  function content_path(string $sub = ''): string
  {
    static $base = null;
    if ($base === null) {
      // library/function.php -> dirname = library/ -> parent = root project
      $base = realpath(dirname(__DIR__) . '/content');
      if (!$base) {
        $base = dirname(__DIR__) . '/content';
      }
    }
    if ($sub === '') return $base;
    return $base . '/' . ltrim($sub, '/\\');
  }
}

/**
 * Helper upload file ke folder content/ dengan fallback bertingkat.
 * Menangani permission issue di hosting Linux (file owned by root).
 * 
 * @param string $tmp_file Path temporary file ($_FILES[x]['tmp_name'])
 * @param string $filename Nama file tujuan (e.g. 'logoweb1.png')
 * @param string $sub_dir Sub-direktori di content/ (e.g. 'assets/logo-jurusan')
 * @return string|false Path absolut file jika berhasil, false jika gagal
 */
if (!function_exists('content_upload')) {
  function content_upload(string $tmp_file, string $filename, string $sub_dir = ''): string|false
  {
    $base = content_path();
    $dirs = [];
    if ($sub_dir !== '') {
      $dirs[] = $base . '/' . trim($sub_dir, '/\\');
    }
    $dirs[] = $base;

    foreach ($dirs as $dir) {
      if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
      }

      if (is_dir($dir)) {
        $dest = rtrim($dir, '/\\') . '/' . $filename;

        // Hapus file lama jika ada
        if (file_exists($dest)) {
          @chmod($dest, 0666);
          @unlink($dest);
        }

        // Coba 3 metode upload
        if (@move_uploaded_file($tmp_file, $dest)) {
          @chmod($dest, 0644);
          return $dest;
        }
        if (@copy($tmp_file, $dest)) {
          @chmod($dest, 0644);
          return $dest;
        }
        $data = @file_get_contents($tmp_file);
        if ($data !== false && @file_put_contents($dest, $data) !== false) {
          @chmod($dest, 0644);
          return $dest;
        }
      }
    }
    return false;
  }
}


function hari_aja()
{
  $seminggu = array("Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu");
  $hari     = date("w");
  $hari_ini = $seminggu[$hari];
  return $hari_ini;
}

function hari_in()
{
  $today_in = hari() . ", " . tgl_indo(date('Y-m-d'));
  return $today_in;
}

function hari_en()
{
  $today_en = date('l') . ", " . date('F') . " " . date('d') . ", " . date('Y');
  return $today_en;
}

$tgl_sekarang = date("Ymd");
$thn_sekarang = date("Y");
$time_sekarang = date("H:i");

$tanggal = date("Y-m-d");
$tgl_jam = date("Y-m-d H:i:s");

function jam_id($tgl)
{
  $tanggal = substr($tgl, 11, 5);
  return $tanggal;
}

function jam_indo($timeNow)
{
  $tgl_jam = substr($timeNow, 11, 8);
  return $tgl_jam;
}



function hari_ini()
{
  $hari = date("D");
  switch ($hari) {
    case 'Sun':
      $hari_ini = "Minggu";
      break;

    case 'Mon':
      $hari_ini = "Senin";
      break;

    case 'Tue':
      $hari_ini = "Selasa";
      break;

    case 'Wed':
      $hari_ini = "Rabu";
      break;

    case 'Thu':
      $hari_ini = "Kamis";
      break;

    case 'Fri':
      $hari_ini = "Jumat";
      break;

    case 'Sat':
      $hari_ini = "Sabtu";
      break;

    default:
      $hari_ini = "Tidak di ketahui";
      break;
  }

  return "" . $hari_ini . "";
}
$hari_ini = hari_ini();



function format_hari_tanggal($waktu)
{
  $hari_array = array(
    'Minggu',
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu'
  );
  $hr = date('w', strtotime($waktu));
  $hari = $hari_array[$hr];
  $tanggal = date('j', strtotime($waktu));
  $bulan_array = array(
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
  );
  $bl = date('n', strtotime($waktu));
  $bulan = $bulan_array[$bl];
  $tahun = date('Y', strtotime($waktu));
  $jam = date('H:i:s', strtotime($waktu));

  //untuk menampilkan hari, tanggal bulan tahun jam
  //return "$hari, $tanggal $bulan $tahun $jam";

  //untuk menampilkan hari, tanggal bulan tahun
  return "$hari, $tanggal $bulan $tahun";
}


// Maret 2021
function bulan_indo($tgl)
{
  $tanggal = substr($tgl, 8, 2);
  $bulan   = ambilbulan(substr($tgl, 5, 2));
  $tahun   = substr($tgl, 0, 4);
  return $bulan . ' ' . $tahun;
}



// 14 Maret 2014
function tgl_indo($tgl)
{
  $tanggal = substr($tgl, 8, 2);
  $bulan   = ambilbulan(substr($tgl, 5, 2));
  $tahun   = substr($tgl, 0, 4);
  return $tanggal . ' ' . $bulan . ' ' . $tahun;
}
function tgl_ind($tgl)
{
  $tanggal = substr($tgl, 8, 2);
  $bulan   = ambil_bulan(substr($tgl, 5, 2));
  $tahun   = substr($tgl, 0, 4);
  return $tanggal . ' ' . $bulan . ' ' . $tahun;
}

function tanggal_ind($tanggal)
{
  $pisah   = explode('-', $tanggal);
  $larik   = array($pisah[2], $pisah[1], $pisah[0]);
  $satukan = implode('-', $larik);
  return $satukan;
}

function tanggal_en($tanggal)
{
  $pisah   = explode('-', $tanggal);
  $larik   = array($pisah[2], $pisah[1], $pisah[0]);
  $satukan = implode('-', $larik);
  return $satukan;
}

function ambilbulan($bln)
{
  if ($bln == "01") return "Januari";
  elseif ($bln == "02") return "Februari";
  elseif ($bln == "03") return "Maret";
  elseif ($bln == "04") return "April";
  elseif ($bln == "05") return "Mei";
  elseif ($bln == "06") return "Juni";
  elseif ($bln == "07") return "Juli";
  elseif ($bln == "08") return "Agustus";
  elseif ($bln == "09") return "September";
  elseif ($bln == "10") return "Oktober";
  elseif ($bln == "11") return "November";
  elseif ($bln == "12") return "Desember";
}

function ambil_bulan($bln)
{
  if ($bln == "01") return "Jan";
  elseif ($bln == "02") return "Feb";
  elseif ($bln == "03") return "Mar";
  elseif ($bln == "04") return "Apr";
  elseif ($bln == "05") return "Mei";
  elseif ($bln == "06") return "Jun";
  elseif ($bln == "07") return "Jul";
  elseif ($bln == "08") return "Agu";
  elseif ($bln == "09") return "Sep";
  elseif ($bln == "10") return "Okt";
  elseif ($bln == "11") return "Nov";
  elseif ($bln == "12") return "Des";
}

function ubah_tgl($tanggal)
{
  $pisah   = explode('/', $tanggal);
  $larik   = array($pisah[2], $pisah[1], $pisah[0]);
  $satukan = implode('-', $larik);
  return $satukan;
}

function current_date()
{
  $tGL = date("Y-m-d");
  $time = date("H:i:s");
  $tgljam = $tGL . " " . $time;
  return "$tgljam";
}

function getformat($tGl)
{
  $pisah   = explode(' ', $tGl);
  $aray = array($pisah[0]);
  $get = format_indo($aray);
  return $get;
}

function jam($time)
{
  $pisah   = explode(':', $time);
  $larik   = array($pisah[0], $pisah[1]);
  $satukan = implode(':', $larik);
  return $satukan;
}


function format_angka($angka)
{
  $hasil =  number_format($angka, 0, ",", ".");
  return $hasil;
}

function format_nomer($angka2)
{
  $hasil2 =  number_format($angka2, 3, ".", ",");
  return $hasil2;
}


function time_since($original)
{
  date_default_timezone_set('Asia/Jakarta');
  $chunks = array(
    array(60 * 60 * 24 * 365, 'tahun'),
    array(60 * 60 * 24 * 30, 'bulan'),
    array(60 * 60 * 24 * 7, 'minggu'),
    array(60 * 60 * 24, 'hari'),
    array(60 * 60, 'jam'),
    array(60, 'menit'),
  );

  $today = time();
  $since = $today - $original;

  if ($since > 604800) {
    $print = date("M jS", $original);
    if ($since > 31536000) {
      $print .= ", " . date("Y", $original);
    }
    return $print;
  }

  for ($i = 0, $j = count($chunks); $i < $j; $i++) {
    $seconds = $chunks[$i][0];
    $name = $chunks[$i][1];

    if (($count = floor($since / $seconds)) != 0)
      break;
  }

  $print = ($count == 1) ? '1 ' . $name : "$count {$name}";
  return $print . ' yang lalu';
}


// Ucapa Selamat Pagi siang sore malam
$time_info = date('H:i');
if ($time_info > '06:30' && $time_info < '10:59') {
  $salam = 'Pagi';
  $time_info_kerja = 'Masuk';
} elseif ($time_info >= '11:00' && $time_info < '14:59') {
  $salam = 'Siang';
  $time_info_kerja = 'Pulang';
} elseif ($time_info >= '15:00' && $time_info < '18:59') {
  $salam = 'Sore';
} else {
  $salam = 'Malam';
}

//fungsi untuk mengkonversi size file
function formatBytes($bytes, $precision = 2)
{
  $units = array('B', 'KB', 'MB', 'GB', 'TB');
  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);
  $bytes /= pow(1024, $pow);
  return round($bytes, $precision) . ' ' . $units[$pow];
}


function timezoneList()
{
  $timezoneIdentifiers = DateTimeZone::listIdentifiers();
  $utcTime = new DateTime('now', new DateTimeZone('UTC'));
  $tempTimezones = array();
  foreach ($timezoneIdentifiers as $timezoneIdentifier) {
    $currentTimezone = new DateTimeZone($timezoneIdentifier);
    $tempTimezones[] = array(
      'offset' => (int)$currentTimezone->getOffset($utcTime),
      'identifier' => $timezoneIdentifier
    );
  }
  function sort_list($a, $b)
  {
    return ($a['offset'] == $b['offset'])
      ? strcmp($a['identifier'], $b['identifier'])
      : $a['offset'] - $b['offset'];
  }
  usort($tempTimezones, "sort_list");
  $timezoneList = array();
  foreach ($tempTimezones as $tz) {
    $sign = ($tz['offset'] > 0) ? '+' : '-';
    $offset = gmdate('H:i', abs($tz['offset']));
    $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' .
      $tz['identifier'];
  }
  return $timezoneList;
}

function anti_injection($string)
{
  $string = str_replace('"', ' ', $string);
  $string = str_replace('=', ' ', $string);
  $string = str_replace('}', ' ', $string);
  $string = str_replace('{', ' ', $string);
  $string = str_replace('[', ' ', $string);
  $string = str_replace(']', ' ', $string);
  $string = str_replace('.php', ' ', $string);
  $string = str_replace('.txt', ' ', $string);
  $string = str_replace('.jpg', ' ', $string);
  $string = str_replace('.png', ' ', $string);
  $string = str_replace('.jpeg', ' ', $string);
  $string = stripslashes($string);
  $string = strip_tags($string);
  $string = htmlspecialchars($string);
  return $string;
}


function seo_title($s)
{
  $c = array(' ');
  $d = array('-', '/', '\\', ',', '.', '#', ':', ';', '\'', '"', '[', ']', '{', '}', ')', '(', '|', '`', '~', '!', '@', '%', '$', '^', '&', '*', '=', '?', '+');
  $s = str_replace($d, '', $s);
  $s = strtolower(str_replace($c, '-', $s));
  return $s;
}


function minify_html($string)
{
  $string = preg_replace('/<!--(?!\[if|\<\!\[endif)(.|\s)*?-->/', '', $string);
  $string = preg_replace('/\t+/', '', $string);
  $string = preg_replace('/\n+/', '', $string);
  $string = preg_replace('/>\r+/', '>', $string);
  $string = preg_replace('/\r+</', '<', $string);
  $string = preg_replace('/>\s+</', '><', $string);
  return $string;
}
function minify_js($buffer)
{
  $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
  $buffer = str_replace(array("\r\n", "\r", "\t", "\n", '  ', '    ', '     '), '', $buffer);
  $buffer = preg_replace(array('(( )+\))', '(\)( )+)'), ')', $buffer);
  return $buffer;
}


function getExtension($str)
{
  $i = strrpos($str, ".");
  if (!$i) {
    return "";
  }
  $l = strlen($str) - $i;
  $ext = substr($str, $i + 1, $l);
  return $ext;
}



/* 
 * Custom function to compress image size and 
 * upload to the server using PHP 
 */
function compressImage($source, $destination, $quality)
{
  // Get image info 
  $imgInfo = getimagesize($source);
  $mime = $imgInfo['mime'];

  // Create a new image from file 
  switch ($mime) {
    case 'image/jpeg':
      $image = imagecreatefromjpeg($source);
      break;
    case 'image/png':
      $image = imagecreatefrompng($source);
      break;
    case 'image/gif':
      $image = imagecreatefromgif($source);
      break;
    default:
      $image = imagecreatefromjpeg($source);
  }

  // Save image 
  imagejpeg($image, $destination, $quality);

  // Return compressed image 
  return $destination;
}



function getBrowser()
{
  $u_agent  = $_SERVER['HTTP_USER_AGENT'];
  $bname    = 'Unknown';
  $platform = 'Unknown';
  $version  = "";

  //First get the platform?
  if (preg_match('/linux/i', $u_agent)) {
    $platform = 'linux';
  } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
    $platform = 'mac';
  } elseif (preg_match('/windows|win32/i', $u_agent)) {
    $platform = 'windows';
  }

  if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
    $bname = 'Internet Explorer';
    $ub    = "MSIE";
  } elseif (preg_match('/Firefox/i', $u_agent)) {
    $bname = 'Mozilla Firefox';
    $ub    = "Firefox";
  } elseif (preg_match('/Chrome/i', $u_agent)) {
    $bname = 'Google Chrome';
    $ub    = "Chrome";
  } elseif (preg_match('/Safari/i', $u_agent)) {
    $bname = 'Apple Safari';
    $ub    = "Safari";
  } elseif (preg_match('/Opera/i', $u_agent)) {
    $bname = 'Opera';
    $ub    = "Opera";
  } elseif (preg_match('/Netscape/i', $u_agent)) {
    $bname = 'Netscape';
    $ub    = "Netscape";
  }

  // finally get the correct version number
  $known   = array('Version', $ub, 'other');
  $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
  if (!preg_match_all($pattern, $u_agent, $matches)) {
    // we have no matching number just continue
  }

  // see how many we have
  $i = count($matches['browser']);
  if ($i != 1) {
    //we will have two since we are not using 'other' argument yet
    //see if version is before or after the name
    if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
      $version = $matches['version'][0];
    } else {
      $version = $matches['version'][1];
    }
  } else {
    $version = $matches['version'][0];
  }

  // check if we have a number
  if ($version == null || $version == "") {
    $version = "?";
  }

  return array(
    'userAgent' => $u_agent,
    'name'      => $bname,
    'version'   => $version,
    'platform'  => $platform,
    'pattern'   => $pattern,
    'browser'   => $ub
  );
}


function epm_encode($id)
{
  $rawKey = defined('APP_ENC_KEY') ? APP_ENC_KEY : getenv('APP_ENC_KEY');
  if (empty($rawKey)) {
    trigger_error('APP_ENC_KEY not set — encryption is insecure! Set APP_ENC_KEY in .env.', E_USER_WARNING);
    $rawKey = 'changeme_legacy_fallback_key_2025';
  }
  $key = hash('sha256', $rawKey, true);
  $iv  = random_bytes(16);
  $ct  = openssl_encrypt((string)$id, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
  $hmac = hash_hmac('sha256', $iv . $ct, $key, true);
  return rtrim(strtr(base64_encode($hmac . $iv . $ct), '+/', '-_'), '=');
}
function epm_decode($enc)
{
  $rawKey = defined('APP_ENC_KEY') ? APP_ENC_KEY : getenv('APP_ENC_KEY');
  if (empty($rawKey)) {
    $rawKey = 'changeme_legacy_fallback_key_2025';
  }
  $key = hash('sha256', $rawKey, true);
  $raw = base64_decode(strtr((string)$enc, '-_', '+/'));
  if ($raw === false || strlen($raw) < 48) return '0';
  $hmac    = substr($raw, 0, 32);
  $iv      = substr($raw, 32, 16);
  $ct      = substr($raw, 48);
  if (!hash_equals(hash_hmac('sha256', $iv . $ct, $key, true), $hmac)) return '0';
  $dec = openssl_decrypt($ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
  if ($dec === false || !preg_match('/^[0-9]+$/', $dec)) return '0';
  return $dec;
}


function convert($action, $string)
{
  $output = false;
  $encrypt_method = "AES-256-CBC";
  $rawKey = defined('APP_ENC_KEY') ? APP_ENC_KEY : getenv('APP_ENC_KEY');
  if (empty($rawKey)) {
    trigger_error('APP_ENC_KEY not set — encryption is insecure! Set APP_ENC_KEY in .env.', E_USER_WARNING);
    $rawKey = 'changeme_legacy_fallback_key_2025';
  }
  $secret_key = $rawKey;
  $secret_iv  = substr(hash('sha256', $rawKey . ':iv'), 0, 16);
  // hash
  $key = hash('sha256', $secret_key);
  // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
  $iv = substr(hash('sha256', $secret_iv), 0, 16);
  if ($action == 'encrypt') {
    $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
    $output = base64_encode($output);
  } else if ($action == 'decrypt') {
    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
  }
  return $output;
}

$expired_cookie = time() + 60 * 60 * 24 * 90;

function randomPassword()
{
  $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  $pass = array(); //remember to declare $pass as an array
  $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
  for ($i = 0; $i < 8; $i++) {
    $n = rand(0, $alphaLength);
    $pass[] = $alphabet[$n];
  }
  return implode($pass); //turn the array into a string
}

// Function untuk mengecek status sistem
function isSystemActive()
{
  global $connection;

  if (!$connection) {
    return false; // Jika tidak ada koneksi, anggap sistem tidak aktif
  }

  try {
    $query = "SELECT aktif FROM tahun_pelajaran ORDER BY id DESC LIMIT 1";
    $result = $connection->query($query);

    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      return ($row['aktif'] == 'Y');
    }

    return false; // Default tidak aktif jika tidak ada data
  } catch (Exception $e) {
    return false; // Jika ada error, anggap sistem tidak aktif
  }
}

if (!function_exists('getMaintenanceModeStatus')) {
  function getMaintenanceModeStatus($db = null)
  {
    global $connection;

    $db = $db ?: $connection;
    if (!$db) {
      return 'open';
    }

    try {
      $column_check = $db->query("SHOW COLUMNS FROM setting LIKE 'maintenance_status'");
      if (!$column_check || !$column_check->num_rows) {
        return 'open';
      }

      $result = $db->query("SELECT maintenance_status FROM setting ORDER BY site_id ASC LIMIT 1");
      if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $status = strtolower(trim((string) ($row['maintenance_status'] ?? 'open')));
        return $status === 'closed' ? 'closed' : 'open';
      }

      return 'open';
    } catch (Exception $e) {
      return 'open';
    }
  }
}

if (!function_exists('isMaintenanceModeClosed')) {
  function isMaintenanceModeClosed($db = null)
  {
    return getMaintenanceModeStatus($db) === 'closed';
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

if (!function_exists('sae_sync_table_has_rows')) {
  function sae_sync_table_has_rows($db, $table_name)
  {
    if (!$db) {
      return false;
    }

    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table_name);
    if ($table_name === '') {
      return false;
    }

    $table_check = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table_name) . "'");
    if (!$table_check || !$table_check->num_rows) {
      return false;
    }

    $row_check = $db->query("SELECT 1 FROM `{$table_name}` LIMIT 1");
    return $row_check && $row_check->num_rows > 0;
  }
}

if (!function_exists('sae_sync_bootstrap_completed')) {
  function sae_sync_bootstrap_completed($db = null)
  {
    global $connection;

    $db = $db ?: $connection;
    if (!$db) {
      return false;
    }

    foreach (sae_sync_bootstrap_tables() as $endpoint => $table_name) {
      if (!sae_sync_table_has_rows($db, $table_name)) {
        return false;
      }

      $status_query = "SELECT 1 FROM sync_log WHERE endpoint='" . $db->real_escape_string($endpoint) . "' AND status='success' LIMIT 1";
      $status_result = $db->query($status_query);
      if (!$status_result || !$status_result->num_rows) {
        return false;
      }
    }

    return true;
  }
}

if (!function_exists('sae_sync_has_logged_failure')) {
  function sae_sync_has_logged_failure($db = null)
  {
    global $connection;

    $db = $db ?: $connection;
    if (!$db) {
      return true;
    }

    if (sae_sync_bootstrap_completed($db)) {
      return false;
    }

    foreach (sae_sync_bootstrap_tables() as $endpoint => $table_name) {
      $status_query = "SELECT status FROM sync_log WHERE endpoint='" . $db->real_escape_string($endpoint) . "' ORDER BY created_at DESC, id DESC LIMIT 1";
      $status_result = $db->query($status_query);
      $status_row = $status_result ? $status_result->fetch_assoc() : null;
      $status = strtolower((string) ($status_row['status'] ?? ''));
      if ($status === 'failed') {
        return true;
      }
    }

    return false;
  }
}

if (!function_exists('sae_registration_sync_required')) {
  function sae_registration_sync_required($db = null)
  {
    return !sae_sync_bootstrap_completed($db);
  }
}

if (!function_exists('sae_home_url')) {
  function sae_home_url(): string
  {
    $script_name = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = rtrim((string) dirname($script_name), '/\\');
    if ($base === '.' || $base === '/' || $base === '\\') {
      $base = '';
    }
    return $base . '/';
  }
}

if (!function_exists('sae_konfigurasi_url')) {
  function sae_konfigurasi_url()
  {
    // Path relatif — browser resolve sendiri ke protocol yang sama.
    // Hindari absolute URL agar tidak loop di balik proxy/Cloudflare.
    return rtrim(sae_home_url(), '/\\') . '/konfigurasi/';
  }
}

if (!function_exists('sae_registrasi_url')) {
  function sae_registrasi_url() { return sae_konfigurasi_url(); }
}

if (!function_exists('sae_redirect_to_konfigurasi')) {
  function sae_redirect_to_konfigurasi()
  {
    header('Location: ' . sae_konfigurasi_url());
    exit;
  }
}

if (!function_exists('sae_redirect_to_registrasi')) {
  function sae_redirect_to_registrasi() { sae_redirect_to_konfigurasi(); }
}

// Function untuk redirect ke maintenance jika sistem tidak aktif
function checkSystemMaintenance()
{
  global $connection;

  // Skip pengecekan jika sedang di halaman maintenance atau admin
  $current_uri = $_SERVER['REQUEST_URI'];
  $script_name = $_SERVER['SCRIPT_NAME'];

  // Skip jika sudah di maintenance atau di area admin
  if (
    strpos($current_uri, 'maintenance') !== false ||
    strpos($current_uri, '/admin') !== false ||
    strpos($script_name, 'maintenance.php') !== false
  ) {
    return;
  }

  // Selama bootstrap data awal belum lengkap, izinkan alur publik menuju registrasi.
  $bootstrap_completed = sae_sync_bootstrap_completed($connection);

  if (!$bootstrap_completed) {
    // Bootstrap belum selesai — jangan redirect ke maintenance.
    // Biarkan index.php dan dashboard/index.php yang menangani routing ke registrasi.
    return;
  }

  if (!isMaintenanceModeClosed($connection)) {
    return;
  }

  // Cek status sistem
  if (!isSystemActive()) {
    // Redirect ke halaman maintenance tanpa ekstensi .php
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    if ($base_path == '/') $base_path = '';

    header('Location: ' . $base_path . '/maintenance');
    exit;
  }
}

/**
 * Call SAE Induk API
 * Defined here so form.php can call it real-time on each tab load.
 */
if (!function_exists('indukApiCall')) {
  function indukApiCall(string $endpoint, string $method = 'GET', ?array $body = null): array
  {
    $url = rtrim(SAE_INDUK_URL, '/') . '/api/' . ltrim($endpoint, '/');
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-API-Key: ' . SAE_API_KEY,
      ],
    ]);

    if ($method === 'POST') {
      curl_setopt($ch, CURLOPT_POST, true);
      if ($body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
      }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
      return ['success' => false, 'message' => 'Koneksi ke server induk gagal: ' . $error];
    }

    $data = json_decode($response, true);
    if (!$data || $httpCode >= 400) {
      return ['success' => false, 'message' => 'Server induk merespon dengan kode ' . $httpCode . ' - ' . ($data['message'] ?? 'No message')];
    }

    return array_merge(['success' => true], $data);
  }
}

// ============================================================
//  SECURITY HELPERS
// ============================================================

/**
 * Set a secure cookie with HttpOnly, Secure, SameSite flags.
 * Wrapper around setcookie() with security-hardened defaults.
 */
if (!function_exists('secure_setcookie')) {
  function secure_setcookie(string $name, string $value, int $expire = 0, string $path = '/'): bool
  {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443');
    // SameSite=Lax by default — use 'Strict' for session cookies
    $samesite = 'Lax';
    if ($path === '/' && $expire === 0) {
      $samesite = 'Strict'; // session cookies
    }
    // PHP 7.3+ native SameSite support
    return setcookie($name, $value, [
      'expires' => $expire,
      'path' => $path,
      'domain' => '',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => $samesite,
    ]);
  }
}

/**
 * Validate CSRF token from POST/header against session token.
 * Returns true if valid, false otherwise.
 */
if (!function_exists('validate_csrf_token')) {
  function validate_csrf_token(?string $token = null): bool
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '') return false;
    $provided = $token ?? ($_POST['csrf_token'] ?? '');
    // Also check header (for AJAX requests)
    if ($provided === '' && function_exists('getallheaders')) {
      $headers = getallheaders();
      $provided = $headers['Csrf-Token'] ?? $headers['X-CSRF-TOKEN'] ?? '';
    }
    return hash_equals($expected, $provided);
  }
}

/**
 * Require a valid CSRF token for write operations (POST).
 * Exits with 403 JSON if invalid. Call at top of proses.php handlers.
 */
if (!function_exists('sae_require_csrf')) {
  function sae_require_csrf(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    if (!validate_csrf_token()) {
      http_response_code(403);
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid.']);
      exit;
    }
  }
}

/**
 * Generate CSRF token hidden field for forms.
 */
if (!function_exists('csrf_field')) {
  function csrf_field(): string
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
  }
}

/**
 * Securely hash a cookie value with session binding to prevent reuse.
 * Returns hash_hmac('sha256', $value, session_id() . APP_ENC_KEY)
 */
if (!function_exists('cookie_bind_session')) {
  function cookie_bind_session(string $value): string
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $key = session_id() . (defined('APP_ENC_KEY') ? APP_ENC_KEY : 'sae-fallback-key');
    return hash_hmac('sha256', $value, $key);
  }
}

/**
 * Helper: run a query using prepared statement for simple SELECT/UPDATE/DELETE/INSERT.
 * Uses mysqli prepared statements internally.
 * 
 * Usage:
 *   $rows = query_prepare($connection, "SELECT * FROM admin WHERE admin_id=? AND active=?", ['s', 's'], [$id, 'Y']);
 *   $row = $rows[0] ?? null;
 * 
 * Returns: array of associative rows, or null on failure.
 */
if (!function_exists('query_prepare')) {
  function query_prepare(mysqli $db, string $sql, array $types = [], array $params = []): ?array
  {
    if (empty($types) || empty($params)) {
      // No params — execute directly (safe, no user input)
      $result = $db->query($sql);
      if (!$result) return null;
      if ($result === true) return []; // INSERT/UPDATE/DELETE success
      $rows = [];
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
      return $rows;
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) return null;

    if (!empty($types) && !empty($params)) {
      $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
      $stmt->close();
      return null;
    }

    $result = $stmt->get_result();
    $stmt->close();

    if (!$result) return null;

    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
    return $rows;
  }
}

/**
 * Rate limiter: check if action from IP exceeds limit in window.
 * Returns false if rate limited, true if allowed.
 * Uses DB table `rate_limits` (auto-created if not exists).
 */
if (!function_exists('rate_limit_check')) {
  function rate_limit_check(mysqli $db, string $action, int $max_attempts = 5, int $window_seconds = 300): bool
  {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $now = time();

    // Auto-create rate_limits table if needed
    $db->query("CREATE TABLE IF NOT EXISTS rate_limits (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      action_ip_hash VARCHAR(64) NOT NULL,
      action VARCHAR(64) NOT NULL,
      expires_at INT UNSIGNED NOT NULL,
      INDEX idx_action_ip (action_ip_hash, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $hash = hash('sha256', $action . '|' . $ip);

    // Clean expired
    $db->query("DELETE FROM rate_limits WHERE expires_at < $now");

    // Count recent attempts
    $cutoff = $now - $window_seconds;
    $result = $db->query("SELECT COUNT(*) as cnt FROM rate_limits WHERE action_ip_hash='$hash' AND expires_at > $cutoff");
    $row = $result ? $result->fetch_assoc() : null;
    $count = $row ? (int)$row['cnt'] : 0;

    if ($count >= $max_attempts) {
      return false; // rate limited
    }

    // Record this attempt
    $db->query("INSERT INTO rate_limits (action_ip_hash, action, expires_at) VALUES ('$hash', '$action', " . ($now + $window_seconds) . ")");
    return true;
  }
}

/**
 * Sanitize filename for uploads — removes path traversal, special chars.
 */
if (!function_exists('sanitize_filename')) {
  function sanitize_filename(string $filename): string
  {
    // Remove any path info
    $filename = basename($filename);
    // Replace non-alphanumeric chars (except . - _)
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Limit length
    $filename = substr($filename, 0, 200);
    if ($filename === '' || $filename === '.') {
      $filename = 'unnamed_' . bin2hex(random_bytes(4));
    }
    return $filename;
  }
}

/**
 * Validate uploaded file MIME type against allowed list.
 */
if (!function_exists('validate_upload_mime')) {
  function validate_upload_mime(string $tmpPath, array $allowedMimes = []): bool
  {
    if (empty($allowedMimes)) {
      $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    return in_array($mime, $allowedMimes, true);
  }
}
