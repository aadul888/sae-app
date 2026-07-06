<?php
session_start();
require_once '../../../library/config.php';
include('../../../library/function.php');

if (!function_exists('sync_table_has_rows')) {
  function sync_table_has_rows($connection, $table_name)
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

if (!function_exists('sync_bootstrap_tables')) {
  function sync_bootstrap_tables()
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

if (!function_exists('sync_bootstrap_required')) {
  function sync_bootstrap_required($connection)
  {
    return !sync_bootstrap_completed($connection);
  }
}

if (!function_exists('sync_bootstrap_completed')) {
  function sync_bootstrap_completed($connection)
  {
    foreach (sync_bootstrap_tables() as $endpoint => $table_name) {
      if (!sync_table_has_rows($connection, $table_name)) {
        return false;
      }

      $status_query = "SELECT 1 FROM sync_log WHERE endpoint='" . $connection->real_escape_string($endpoint) . "' AND status='success' LIMIT 1";
      $status_result = $connection->query($status_query);
      if (!$status_result || !$status_result->num_rows) {
        return false;
      }
    }

    return true;
  }
}

$requested_action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$public_bootstrap_actions = ['get-status', 'save-dapodik-config', 'test-dapodik-connection', 'getSekolah', 'getGtk', 'getRombonganBelajar', 'getPesertaDidik', 'getPengguna', 'clear-sync-tables'];
$bootstrap_required = sync_bootstrap_required($connection);
$bootstrap_completed = sync_bootstrap_completed($connection);
$allow_public_bootstrap = !$bootstrap_completed && in_array($requested_action, $public_bootstrap_actions, true);

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY']) && !$allow_public_bootstrap) {
  header('location:./login');
  exit;
} else {
  if (!$allow_public_bootstrap) {
    require_once '../../login/user.php';
  }

  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }

  // Fungsi untuk menyimpan log sinkronisasi
  function save_sync_log($endpoint, $status, $total_records, $message)
  {
    global $connection;
    $message = substr((string) $message, 0, 65535);
    $stmt = $connection->prepare("\n      INSERT INTO sync_log (endpoint, status, total_records, message, created_at)\n      VALUES (?, ?, ?, ?, NOW())\n      ON DUPLICATE KEY UPDATE\n        status = VALUES(status),\n        total_records = VALUES(total_records),\n        message = VALUES(message),\n        created_at = NOW()\n    ");
    if (!$stmt) {
      return false;
    }

    $stmt->bind_param("ssis", $endpoint, $status, $total_records, $message);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  $sync_pull_actions = ['getSekolah', 'getGtk', 'getPengguna', 'getRombonganBelajar', 'getPesertaDidik'];
  if (in_array($requested_action, $sync_pull_actions, true)) {
    register_shutdown_function(function () use ($requested_action) {
      $last_error = error_get_last();
      if (!$last_error) {
        return;
      }

      $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
      if (!in_array((int) $last_error['type'], $fatal_types, true)) {
        return;
      }

      $fatal_message = 'Fatal error: ' . ($last_error['message'] ?? 'unknown error');
      save_sync_log($requested_action, 'failed', 0, $fatal_message);
      push_sync_progress($requested_action, 'failed', $fatal_message, [
        'scope' => 'table',
        'processed' => 0,
        'total' => 0,
        'failed' => 1
      ]);
    });
  }

  function get_sync_progress_path()
  {
    return __DIR__ . '/current_progress.json';
  }

  function load_sync_progress_state()
  {
    $defaults = [
      'updated_at' => '',
      'current' => null,
      'items' => []
    ];

    $file = get_sync_progress_path();
    if (!file_exists($file)) {
      return $defaults;
    }

    $raw = @file_get_contents($file);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return $defaults;
    }

    return array_merge($defaults, $decoded);
  }

  function save_sync_progress_state($state)
  {
    @file_put_contents(
      get_sync_progress_path(),
      json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
      LOCK_EX
    );
  }

  function reset_sync_progress($message = 'Menunggu proses tarik data dimulai.')
  {
    save_sync_progress_state([
      'updated_at' => date('c'),
      'current' => [
        'endpoint' => 'bootstrap',
        'endpoint_label' => 'Bootstrap',
        'status' => 'idle',
        'scope' => 'table',
        'message' => $message,
        'processed' => 0,
        'total' => 0,
        'updated' => 0,
        'inserted' => 0,
        'failed' => 0,
        'skipped' => 0,
        'timestamp' => date('c')
      ],
      'items' => []
    ]);
  }

  function push_sync_progress($endpoint, $status, $message, $context = [])
  {
    static $last_write_time = 0;
    static $history = null;

    $scope = $context['scope'] ?? 'table';
    $now = microtime(true);

    // Throttle row-level writes: maks 3 kali per detik agar tidak membebani I/O pada data besar
    if ($scope === 'row' && ($now - $last_write_time) < 0.33) {
      return;
    }
    $last_write_time = $now;

    $labels = [
      'getSekolah' => 'Sekolah',
      'getGtk' => 'GTK',
      'getRombonganBelajar' => 'Rombongan Belajar',
      'getPesertaDidik' => 'Peserta Didik',
      'getPengguna' => 'Pengguna',
      'bootstrap' => 'Bootstrap'
    ];

    $entry = [
      'endpoint' => $endpoint,
      'endpoint_label' => $labels[$endpoint] ?? $endpoint,
      'status' => $status,
      'scope' => $scope,
      'row_label' => $context['row_label'] ?? '',
      'message' => $message,
      'processed' => intval($context['processed'] ?? 0),
      'total' => intval($context['total'] ?? 0),
      'updated' => intval($context['updated'] ?? 0),
      'inserted' => intval($context['inserted'] ?? 0),
      'failed' => intval($context['failed'] ?? 0),
      'skipped' => intval($context['skipped'] ?? 0),
      'timestamp' => date('c')
    ];

    if ($history === null) {
      $history = load_sync_progress_state();
      if (!is_array($history)) {
        $history = [
          'updated_at' => '',
          'current' => null,
          'items' => []
        ];
      }
    }

    $items = isset($history['items']) && is_array($history['items']) ? $history['items'] : [];
    array_unshift($items, $entry);
    $items = array_slice($items, 0, 12);

    $history['updated_at'] = $entry['timestamp'];
    $history['current'] = $entry;
    $history['items'] = $items;

    // Tulis langsung tanpa baca-ubah-tulis: lebih cepat untuk dataset besar
    @file_put_contents(
      get_sync_progress_path(),
      json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
      LOCK_EX
    );
  }

  function ensure_setting_sekolah_id_capacity($connection)
  {
    $column_result = $connection->query("SHOW COLUMNS FROM setting LIKE 'sekolah_id'");
    if (!$column_result || !$column_result->num_rows) {
      return;
    }

    $column = $column_result->fetch_assoc();
    $type = strtolower((string) ($column['Type'] ?? ''));
    if (preg_match('/varchar\((\d+)\)/', $type, $matches) && intval($matches[1]) < 50) {
      $connection->query("ALTER TABLE setting MODIFY sekolah_id varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL");
    }
  }

  // Helper: cek apakah kolom ada di tabel (runtime safe)
  function column_exists($connection, $table, $column)
  {
    $sql = "SHOW COLUMNS FROM `" . $connection->real_escape_string($table) . "` LIKE '" . $connection->real_escape_string($column) . "'";
    $res = $connection->query($sql);
    return ($res && $res->num_rows > 0);
  }

  function ensure_admin_gtk_reference_columns($connection)
  {
    $ddl_map = [
      'gtk_status_kepegawaian' => "ALTER TABLE admin ADD COLUMN gtk_status_kepegawaian varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER jenis_ptk_id_str",
      'gtk_jabatan_ptk' => "ALTER TABLE admin ADD COLUMN gtk_jabatan_ptk varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER gtk_status_kepegawaian",
      'gtk_nip' => "ALTER TABLE admin ADD COLUMN gtk_nip varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER gtk_jabatan_ptk",
      'gtk_nuptk' => "ALTER TABLE admin ADD COLUMN gtk_nuptk varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER gtk_nip",
      'gtk_nik' => "ALTER TABLE admin ADD COLUMN gtk_nik varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER gtk_nuptk"
    ];

    foreach ($ddl_map as $column => $ddl) {
      if (!column_exists($connection, 'admin', $column)) {
        $connection->query($ddl);
      }
    }
  }

  function get_pkl_sync_config_path()
  {
    return __DIR__ . '/pkl_sync_config.json';
  }

  function load_pkl_sync_config()
  {
    $path = get_pkl_sync_config_path();
    $defaults = [
      'pkl_base_url' => '',
      'api_token' => '',
      'updated_at' => ''
    ];
    if (!file_exists($path)) {
      return $defaults;
    }
    $raw = @file_get_contents($path);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return $defaults;
    }
    return array_merge($defaults, $decoded);
  }

  function save_pkl_sync_config($base_url, $api_token)
  {
    $path = get_pkl_sync_config_path();
    $payload = [
      'pkl_base_url' => trim((string) $base_url),
      'api_token' => trim((string) $api_token),
      'updated_at' => date('Y-m-d H:i:s')
    ];
    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $payload;
  }

  function normalize_pkl_endpoint($base_url)
  {
    $url = trim((string) $base_url);
    if ($url === '') {
      return '';
    }
    // Tambahkan scheme jika tidak ada
    if (stripos($url, 'http://') !== 0 && stripos($url, 'https://') !== 0) {
      $url = 'https://' . $url;
    }
    $parsed = parse_url($url);
    $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
    $host = isset($parsed['host']) ? $parsed['host'] : '';
    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    $path = isset($parsed['path']) ? $parsed['path'] : '/';

    // Jika user menempel URL dari dalam sw-admin, paksa kembali ke root app + /api/kirim-data
    if (stripos($path, '/sw-admin/') !== false || preg_match('#/sw-admin$#i', $path)) {
      $base_path = preg_replace('#/sw-admin.*$#i', '', $path);
      $base_path = rtrim((string) $base_path, '/');
      $path = ($base_path === '' ? '' : $base_path) . '/api/kirim-data';
      if ($host !== '') {
        return $scheme . '://' . $host . $port . $path;
      }
      return $url;
    }

    // Jika URL hanya domain atau root app, tambahkan endpoint default.
    // Jika URL sudah path API, gunakan apa adanya.
    $segments = array_filter(explode('/', trim($path, '/')));
    if (count($segments) <= 1) {
      if ($host !== '') {
        $base_path = '/' . trim($path, '/');
        if ($base_path === '/' || $base_path === '') {
          $base_path = '';
        }
        $url = $scheme . '://' . $host . $port . rtrim($base_path, '/') . '/api/kirim-data';
      } else {
        $url = rtrim($url, '/') . '/api/kirim-data';
      }
    }
    return $url;
  }

  function post_json_with_bearer($url, $token, $payload, $timeout = 60)
  {
    $ch = curl_init();
    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $token
    ];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $body = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    return [
      'body' => $body,
      'http_code' => $http_code,
      'curl_error' => $curl_error
    ];
  }

  function build_sae_public_avatar_url($avatar)
  {
    $avatar = trim((string) $avatar);
    if ($avatar === '' || $avatar === 'avatar.jpg' || $avatar === 'default.jpg') {
      return '';
    }

    $base = rtrim((string) base_url(), '/');
    if ($base === '') {
      return '';
    }

    return $base . '/content/avatar/' . rawurlencode(basename($avatar));
  }

  function get_dapodik_sync_config_path()
  {
    return __DIR__ . '/dapodik_sync_config.json';
  }

  function load_dapodik_sync_config()
  {
    $defaults = [
      'base_url' => 'http://localhost:5774',
      'npsn' => '20252031',
      'token' => '',
      'timeout' => 30,
      'updated_at' => ''
    ];

    $path = get_dapodik_sync_config_path();
    if (!file_exists($path)) {
      return $defaults;
    }

    $raw = @file_get_contents($path);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return $defaults;
    }

    return array_merge($defaults, $decoded);
  }

  function save_dapodik_sync_config($base_url, $token, $npsn, $timeout = 30)
  {
    $path = get_dapodik_sync_config_path();
    $payload = [
      'base_url' => normalize_dapodik_base_url($base_url),
      'token' => trim((string) $token),
      'npsn' => trim((string) $npsn),
      'timeout' => max(5, intval($timeout)),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $payload;
  }

  function normalize_dapodik_base_url($url)
  {
    $url = trim((string) $url);
    if ($url === '') {
      return '';
    }

    if (stripos($url, 'http://') !== 0 && stripos($url, 'https://') !== 0) {
      $url = 'http://' . $url;
    }

    return rtrim($url, '/');
  }

  function dapodik_get_raw($url, $token = null, $timeout = 30)
  {
    $headers = [
      'Accept: application/json',
      'User-Agent: SAE-Sync/1.0'
    ];

    if (!empty($token)) {
      $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => max(5, intval($timeout)),
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTPGET => true,
      CURLOPT_POST => false,
      CURLOPT_CUSTOMREQUEST => 'GET'
    ]);

    $response = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
      'response' => $response,
      'http_code' => $http_code,
      'error' => $error
    ];
  }

  function parse_dapodik_response_rows($endpoint, $raw_response)
  {
    $data = json_decode($raw_response, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
      if (isset($data['rows'])) {
        $rows = $data['rows'];
        if (is_array($rows) && isset($rows[0])) {
          return $rows;
        }
        if (is_array($rows)) {
          return [$rows];
        }
      }

      $top_keys = array_keys($data);
      $likely_keys = ['npsn', 'nama', 'sekolah_id', 'ptk_id', 'peserta_didik_id', 'rombongan_belajar_id', 'pengguna_id'];
      if (count(array_intersect($top_keys, $likely_keys)) > 0) {
        return [$data];
      }
    }

    return [];
  }

  function fetch_dapodik_rows($endpoint, $config = null)
  {
    $endpoint_map = [
      'getSekolah' => ['path' => 'rest/Sekolah', 'use_npsn' => true],
      'getGtk' => ['path' => 'rest/Ptk', 'use_npsn' => true],
      'getPesertaDidik' => ['path' => 'rest/PesertaDidik', 'use_npsn' => true],
      'getRombonganBelajar' => ['path' => 'rest/RombonganBelajar', 'use_npsn' => true],
      'getPengguna' => ['path' => 'rest/Pengguna', 'use_npsn' => true]
    ];

    if (!isset($endpoint_map[$endpoint])) {
      throw new Exception('Endpoint Dapodik tidak dikenal: ' . $endpoint);
    }

    if (!is_array($config)) {
      $config = load_dapodik_sync_config();
    }

    $base_url = normalize_dapodik_base_url($config['base_url'] ?? '');
    $npsn = trim((string) ($config['npsn'] ?? ''));
    $token = trim((string) ($config['token'] ?? ''));
    $timeout = intval($config['timeout'] ?? 30);

    if ($base_url === '') {
      throw new Exception('URL Dapodik belum diisi');
    }

    $primary_url = rtrim($base_url, '/') . '/' . ltrim($endpoint_map[$endpoint]['path'], '/');
    if (!empty($endpoint_map[$endpoint]['use_npsn']) && $npsn !== '') {
      $primary_url .= (strpos($primary_url, '?') === false ? '?' : '&') . 'npsn=' . urlencode($npsn);
    }

    $resp = dapodik_get_raw($primary_url, $token, $timeout);
    $raw = is_string($resp['response']) ? $resp['response'] : '';

    if (!empty($resp['error'])) {
      throw new Exception('cURL Error: ' . $resp['error']);
    }

    if ($resp['http_code'] === 200) {
      $rows = parse_dapodik_response_rows($endpoint, $raw);
      if (!empty($rows)) {
        return $rows;
      }
    }

    $legacy_candidates = [
      rtrim($base_url, '/') . '/WebService/' . $endpoint,
      rtrim($base_url, '/') . '/WebService/' . $endpoint . ($npsn !== '' ? '?npsn=' . urlencode($npsn) : ''),
      rtrim($base_url, '/') . '/WebService/' . $endpoint . (!empty($token) ? (strpos($endpoint, '?') === false ? '?token=' : '&token=') . urlencode($token) : '')
    ];

    foreach ($legacy_candidates as $candidate) {
      $legacy_resp = dapodik_get_raw($candidate, $token, $timeout);
      if (!empty($legacy_resp['error'])) {
        continue;
      }
      if ((int) $legacy_resp['http_code'] !== 200) {
        continue;
      }

      $legacy_raw = is_string($legacy_resp['response']) ? $legacy_resp['response'] : '';
      $rows = parse_dapodik_response_rows($endpoint, $legacy_raw);
      if (!empty($rows)) {
        return $rows;
      }
    }

    throw new Exception('Data Dapodik tidak valid atau kosong untuk endpoint ' . $endpoint);
  }

  function sync_dapodik_sources_for_action($action)
  {
    $action_map = [
      'getSekolah' => ['getSekolah'],
      'getGtk' => ['getGtk', 'getPengguna'],
      'getPengguna' => ['getGtk', 'getPengguna'],
      'getRombonganBelajar' => ['getRombonganBelajar'],
      'getPesertaDidik' => ['getRombonganBelajar', 'getPesertaDidik']
    ];

    if (is_array($action)) {
      $endpoint_list = array_values(array_unique(array_filter($action)));
    } elseif (isset($action_map[$action])) {
      $endpoint_list = $action_map[$action];
    } else {
      return ['status' => 'success', 'message' => 'Tidak ada prefetch Dapodik yang diperlukan'];
    }

    if (!class_exists('ApiLogger')) {
      require_once '../../../api/core/ApiLogger.php';
    }
    if (!class_exists('DataProcessor')) {
      require_once '../../../api/core/DataProcessor.php';
    }

    $config = load_dapodik_sync_config();
    $processor = new DataProcessor();
    $messages = [];

    foreach ($endpoint_list as $endpoint) {
      try {
        $rows = fetch_dapodik_rows($endpoint, $config);
        if (empty($rows)) {
          $message = 'Data Dapodik kosong untuk endpoint ' . $endpoint;
          save_sync_log($endpoint, 'failed', 0, $message);
          return ['status' => 'error', 'message' => $message];
        }

        $result = $processor->processData($endpoint, $rows);
        if (!($result['success'] ?? false)) {
          $message = $result['message'] ?? ('Gagal memproses endpoint ' . $endpoint);
          save_sync_log($endpoint, 'failed', count($rows), $message);
          return ['status' => 'error', 'message' => $message];
        }

        $messages[] = $endpoint . ' (' . count($rows) . ' data)';
      } catch (Exception $e) {
        $message = $e->getMessage();
        save_sync_log($endpoint, 'failed', 0, $message);
        push_sync_progress($endpoint, 'failed', $message, [
          'scope' => 'table',
          'processed' => 0,
          'total' => 0
        ]);
        return ['status' => 'error', 'message' => $message];
      }
    }

    return [
      'status' => 'success',
      'message' => 'Data Dapodik berhasil diambil: ' . implode(', ', $messages)
    ];
  }

  function save_dapodik_connection_status($status, $message, $details = [])
  {
    $file = __DIR__ . '/connection_status.json';
    $payload = [];
    if (file_exists($file)) {
      $raw = @file_get_contents($file);
      $payload = json_decode($raw, true) ?: [];
    }

    $payload['connection_status'] = $status;
    $payload['updated_at'] = date('c');
    $payload['message'] = $message;
    if (!empty($details)) {
      $payload['details'] = $details;
    }

    @file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $payload;
  }

  switch (@$_GET['action']) {

    /** Set connection status (saved to file) */
    case 'set-connection-status':
      // Expect POST { status: 'berhasil'|'gagal', type: 'dapodik'|'api' }
      $new_status = isset($_POST['status']) ? $_POST['status'] : '';
      $type = isset($_POST['type']) ? $_POST['type'] : 'dapodik';
      $allowed = ['berhasil', 'gagal'];
      if (!in_array($new_status, $allowed)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
      }

      $file = __DIR__ . '/connection_status.json';
      $data = [];
      if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $data = json_decode($raw, true) ?: [];
      }

      if ($type === 'api') {
        $data['api_connection_status'] = $new_status;
        $data['api_updated_at'] = date('c');
      } else {
        $data['connection_status'] = $new_status;
        $data['updated_at'] = date('c');
      }

      @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

      header('Content-Type: application/json');
      echo json_encode(['status' => 'success', 'data' => $data]);
      exit;

    /** Hapus semua tabel sync agar bisa tarik ulang dari awal */
    case 'clear-sync-tables':
      header('Content-Type: application/json');
      // Disable FK checks agar TRUNCATE tidak terhambat constraint fk_pembelajaran_rombel
      $connection->query("SET FOREIGN_KEY_CHECKS=0");
      $sync_tables_to_clear = [
        'sync_pembelajaran', 'sync_anggota_rombel',
        'sync_sekolah', 'sync_gtk', 'sync_pengguna',
        'sync_rombongan_belajar', 'sync_peserta_didik'
      ];
      foreach ($sync_tables_to_clear as $_tbl) {
        $_tbl_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $_tbl);
        if ($_tbl_safe !== '') {
          $connection->query("TRUNCATE TABLE `{$_tbl_safe}`");
        }
      }
      $connection->query("SET FOREIGN_KEY_CHECKS=1");
      $connection->query("DELETE FROM sync_log");
      reset_sync_progress('Tabel sync telah dihapus. Siap untuk tarik ulang.');
      echo json_encode(['status' => 'success', 'message' => 'Semua tabel sync berhasil dihapus. Silakan tarik ulang data dari awal.']);
      exit;

    /** Get status (reads saved file and database) */
    case 'get-status':
      // Lepas lock session agar endpoint ini bisa diakses paralel saat sync sedang berjalan
      session_write_close();
      $response = [
        'status' => 'success',
        'data' => []
      ];

      // Ambil status dari file connection_status.json
      $file = __DIR__ . '/connection_status.json';
      $file_data = [];
      if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $file_data = json_decode($raw, true) ?: [];
      }

      $response['data']['connection_status'] = $file_data['connection_status'] ?? null;
      $response['data']['api_connection_status'] = $file_data['api_connection_status'] ?? null;

      // Ambil status koneksi dari web_service_dapodik
      $ws_query = "SELECT status FROM web_service_dapodik ORDER BY id DESC LIMIT 1";
      $ws_result = $connection->query($ws_query);
      if ($ws_result && $ws_result->num_rows > 0) {
        $ws_data = $ws_result->fetch_assoc();
        $response['data']['dapodik_status'] = $ws_data['status'];
      } else {
        $response['data']['dapodik_status'] = 'never';
      }

      // Ambil status sync untuk setiap endpoint dari sync_log
      $endpoints = ['getSekolah', 'getGtk', 'getRombonganBelajar', 'getPesertaDidik', 'getPengguna'];
      $sync_status = [];

      foreach ($endpoints as $endpoint) {
        $log_query = "SELECT status FROM sync_log WHERE endpoint='$endpoint' ORDER BY created_at DESC LIMIT 1";
        $log_result = $connection->query($log_query);
        if ($log_result && $log_result->num_rows > 0) {
          $log_data = $log_result->fetch_assoc();
          $sync_status[$endpoint] = $log_data['status'];
        } else {
          $sync_status[$endpoint] = 'never';
        }
      }

      $response['data']['sync_status'] = $sync_status;

      $recent_logs = [];
      $log_query = "SELECT endpoint, status, total_records, message, created_at FROM sync_log ORDER BY created_at DESC, id DESC LIMIT 8";
      $log_result = $connection->query($log_query);
      if ($log_result) {
        while ($log_row = $log_result->fetch_assoc()) {
          $recent_logs[] = $log_row;
        }
      }

      $sync_counts = [];
      foreach (sync_bootstrap_tables() as $endpoint => $table_name) {
        $sync_counts[$endpoint] = 0;
        $table_check = $connection->query("SHOW TABLES LIKE '" . $connection->real_escape_string($table_name) . "'");
        if ($table_check && $table_check->num_rows > 0) {
          $count_result = $connection->query("SELECT COUNT(*) AS total FROM `{$table_name}`");
          if ($count_result && ($count_row = $count_result->fetch_assoc())) {
            $sync_counts[$endpoint] = intval($count_row['total']);
          }
        }
      }

      $response['data']['recent_logs'] = $recent_logs;
      $response['data']['sync_counts'] = $sync_counts;
      $response['data']['bootstrap_completed'] = $bootstrap_completed;
      $response['data']['current_progress'] = load_sync_progress_state();

      header('Content-Type: application/json');
      echo json_encode($response);
      exit;

    case 'save-dapodik-config':
      header('Content-Type: application/json');
      $base_url = $_POST['base_url'] ?? '';
      $token = $_POST['token'] ?? '';
      $npsn = $_POST['npsn'] ?? '';
      $timeout = $_POST['timeout'] ?? 30;

      if (trim((string) $base_url) === '') {
        echo json_encode(['status' => 'error', 'message' => 'URL Dapodik wajib diisi']);
        exit;
      }
      if (trim((string) $token) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Token Dapodik wajib diisi']);
        exit;
      }
      if (trim((string) $npsn) === '') {
        echo json_encode(['status' => 'error', 'message' => 'NPSN wajib diisi']);
        exit;
      }

      $saved = save_dapodik_sync_config($base_url, $token, $npsn, $timeout);
      reset_sync_progress('Konfigurasi Dapodik tersimpan. Proses tarik data akan dimulai.');
      push_sync_progress('bootstrap', 'running', 'Konfigurasi Dapodik berhasil disimpan.', ['scope' => 'table']);
      echo json_encode(['status' => 'success', 'message' => 'Konfigurasi Dapodik berhasil disimpan', 'data' => $saved]);
      exit;

    case 'test-dapodik-connection':
      header('Content-Type: application/json');
      $test_config = load_dapodik_sync_config();
      if (!empty($_POST['base_url'])) {
        $test_config['base_url'] = $_POST['base_url'];
      }
      if (!empty($_POST['token'])) {
        $test_config['token'] = $_POST['token'];
      }
      if (!empty($_POST['npsn'])) {
        $test_config['npsn'] = $_POST['npsn'];
      }
      if (!empty($_POST['timeout'])) {
        $test_config['timeout'] = $_POST['timeout'];
      }

      try {
        $rows = fetch_dapodik_rows('getSekolah', $test_config);
        $first = $rows[0] ?? [];
        $message = 'Koneksi ke Dapodik berhasil';
        if (!empty($first['nama'])) {
          $message .= ' - ' . $first['nama'];
        }
        save_dapodik_connection_status('berhasil', $message, ['endpoint' => 'getSekolah', 'rows' => count($rows)]);
        echo json_encode(['status' => 'success', 'message' => $message, 'rows' => count($rows)]);
      } catch (Exception $e) {
        save_dapodik_connection_status('gagal', $e->getMessage(), ['endpoint' => 'getSekolah']);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
      }
      exit;

    // Handler sinkronisasi Sekolah dari sync_sekolah ke setting
    case 'getSekolah':
      header('Content-Type: application/json');
      ob_clean();
      // Lepas lock session sebelum proses panjang agar polling get-status bisa berjalan paralel
      session_write_close();

      // Buat proses lebih tahan terhadap server lambat
      if (function_exists('set_time_limit')) {
        @set_time_limit(0);
      }
      @ini_set('max_execution_time', '0');

      $prefetch = sync_dapodik_sources_for_action(['getSekolah']);
      if (($prefetch['status'] ?? '') !== 'success') {
        echo json_encode(['status' => 'error', 'message' => $prefetch['message'] ?? 'Gagal mengambil data Dapodik']);
        exit;
      }

      $processed = 0;
      $updated = 0;
      $failed = 0;
      $failed_msgs = [];
      push_sync_progress('getSekolah', 'running', 'Memulai sinkronisasi tabel Sekolah.', ['scope' => 'table', 'total' => 1]);

      // Cek tabel sync_sekolah
      $table_check = $connection->query("SHOW TABLES LIKE 'sync_sekolah'");
      if (!$table_check || $table_check->num_rows == 0) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Tabel sync_sekolah tidak ditemukan.'
        ]);
        exit;
      }

      // Ambil 1 data sekolah terbaru
      $sync_query = "SELECT sekolah_id, nama, nss, npsn, bentuk_pendidikan_id, bentuk_pendidikan_id_str,
                            status_sekolah, status_sekolah_str, alamat_jalan, rt, rw, dusun,
                            desa_kelurahan, kode_wilayah, kode_pos, lintang, bujur,
                            nomor_telepon, nomor_fax, email, website, is_sks,
                            kecamatan, kabupaten_kota, provinsi
                     FROM sync_sekolah
                     ORDER BY updated_at DESC, created_at DESC
                     LIMIT 1";
      $sync_result = $connection->query($sync_query);
      if (!$sync_result) {
        $msg = 'Query sync_sekolah gagal: ' . $connection->error;
        save_sync_log('getSekolah', 'failed', 0, $msg);
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit;
      }
      if ($sync_result->num_rows == 0) {
        $msg = 'Tidak ada data di sync_sekolah.';
        save_sync_log('getSekolah', 'success', 0, $msg);
        echo json_encode(['status' => 'info', 'message' => $msg]);
        exit;
      }

      $s = $sync_result->fetch_assoc();
      $processed = 1;
      push_sync_progress('getSekolah', 'running', 'Baris sekolah sedang diproses: ' . ($s['nama'] ?? 'Sekolah'), [
        'scope' => 'row',
        'row_label' => $s['nama'] ?? 'Sekolah',
        'processed' => $processed,
        'total' => 1,
        'updated' => $updated,
        'failed' => $failed
      ]);

      // Ambil site_id aktif
      ensure_setting_sekolah_id_capacity($connection);
      $setting_res = $connection->query("SELECT site_id FROM setting ORDER BY site_id ASC LIMIT 1");
      if (!$setting_res || $setting_res->num_rows == 0) {
        // Jika setting kosong, buat 1 baris default agar sinkronisasi sekolah tetap bisa berjalan.
        $ins_setting = $connection->prepare(
          "INSERT INTO setting
            (site_name, site_phone, site_address, site_owner, site_logo, site_logo2, site_favicon,
             site_kop, site_url, site_email,
             gmail_host, gmail_username, gmail_password, gmail_port, gmail_active,
             google_client_id, google_client_secret, google_client_active)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$ins_setting) {
          $msg = 'Prepare insert setting default gagal: ' . $connection->error;
          save_sync_log('getSekolah', 'failed', 0, $msg);
          echo json_encode(['status' => 'error', 'message' => $msg]);
          exit;
        }

        $def_site_name = 'SAE';
        $def_site_phone = '-';
        $def_site_address = '-';
        $def_site_owner = '-';
        $def_site_logo = 'logoweb1.png';
        $def_site_logo2 = null;
        $def_site_favicon = 'favicon.png';
        $def_site_kop = null;
        $def_site_url = '-';
        $def_site_email = '-';
        $def_gmail_host = '-';
        $def_gmail_username = '-';
        $def_gmail_password = '-';
        $def_gmail_port = '-';
        $def_gmail_active = 'N';
        $def_google_client_id = '-';
        $def_google_client_secret = '-';
        $def_google_client_active = 'N';

        $ins_setting->bind_param(
          'ssssssssssssssssss',
          $def_site_name,
          $def_site_phone,
          $def_site_address,
          $def_site_owner,
          $def_site_logo,
          $def_site_logo2,
          $def_site_favicon,
          $def_site_kop,
          $def_site_url,
          $def_site_email,
          $def_gmail_host,
          $def_gmail_username,
          $def_gmail_password,
          $def_gmail_port,
          $def_gmail_active,
          $def_google_client_id,
          $def_google_client_secret,
          $def_google_client_active
        );

        if (!$ins_setting->execute()) {
          $msg = 'Insert setting default gagal: ' . $ins_setting->error;
          $ins_setting->close();
          save_sync_log('getSekolah', 'failed', 0, $msg);
          echo json_encode(['status' => 'error', 'message' => $msg]);
          exit;
        }
        $ins_setting->close();

        $site_id = intval($connection->insert_id);
      } else {
        $site = $setting_res->fetch_assoc();
        $site_id = intval($site['site_id']);
      }

      // Mapping utama ke field existing setting
      $site_name = (string)($s['nama'] ?? '');
      $site_phone = substr((string)($s['nomor_telepon'] ?? ''), 0, 12);
      $site_address = (string)($s['alamat_jalan'] ?? '');
      $site_email = substr((string)($s['email'] ?? ''), 0, 30);
      $site_url = (string)($s['website'] ?? '');

      $upd = $connection->prepare(
        "UPDATE setting SET
            site_name = ?,
            site_phone = ?,
            site_address = ?,
            site_email = ?,
            site_url = ?,
            sekolah_id = ?,
            nama = ?,
            nss = ?,
            npsn = ?,
            bentuk_pendidikan_id = ?,
            bentuk_pendidikan_id_str = ?,
            status_sekolah = ?,
            status_sekolah_str = ?,
            alamat_jalan = ?,
            rt = ?,
            rw = ?,
            dusun = ?,
            desa_kelurahan = ?,
            kode_wilayah = ?,
            kode_pos = ?,
            lintang = ?,
            bujur = ?,
            nomor_telepon = ?,
            nomor_fax = ?,
            email = ?,
            website = ?,
            is_sks = ?,
            kecamatan = ?,
            kabupaten_kota = ?,
            provinsi = ?,
            updated_at = NOW()
         WHERE site_id = ?"
      );

      if (!$upd) {
        $msg = 'Prepare update setting gagal: ' . $connection->error;
        save_sync_log('getSekolah', 'failed', 0, $msg);
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit;
      }

      $upd->bind_param(
        'sssssssssssssssssssssississsssi',
        $site_name,
        $site_phone,
        $site_address,
        $site_email,
        $site_url,
        $s['sekolah_id'],
        $s['nama'],
        $s['nss'],
        $s['npsn'],
        $s['bentuk_pendidikan_id'],
        $s['bentuk_pendidikan_id_str'],
        $s['status_sekolah'],
        $s['status_sekolah_str'],
        $s['alamat_jalan'],
        $s['rt'],
        $s['rw'],
        $s['dusun'],
        $s['desa_kelurahan'],
        $s['kode_wilayah'],
        $s['kode_pos'],
        $s['lintang'],
        $s['bujur'],
        $s['nomor_telepon'],
        $s['nomor_fax'],
        $s['email'],
        $s['website'],
        $s['is_sks'],
        $s['kecamatan'],
        $s['kabupaten_kota'],
        $s['provinsi'],
        $site_id
      );

      if ($upd->execute()) {
        $updated = 1;
      } else {
        $failed++;
        $failed_msgs[] = $upd->error;
      }
      $upd->close();

      $msg = "Processed: $processed, Updated: $updated, Failed: $failed";
      if ($failed > 0) $msg .= "\nDetails: " . implode('; ', $failed_msgs);
      push_sync_progress('getSekolah', $failed > 0 ? 'failed' : 'success', $msg, [
        'scope' => 'table',
        'processed' => $processed,
        'total' => 1,
        'updated' => $updated,
        'failed' => $failed
      ]);
      save_sync_log('getSekolah', $failed > 0 ? 'failed' : 'success', $processed, $msg);
      echo json_encode(['status' => $failed > 0 ? 'error' : 'success', 'message' => $msg]);
      exit;

      // Handler untuk melengkapi tabel admin dari sync_pengguna berdasarkan peran

    case 'getPengguna':
      header('Content-Type: application/json');
      ob_clean();
      // Lepas lock session sebelum proses panjang agar polling get-status bisa berjalan paralel
      session_write_close();
      ensure_admin_gtk_reference_columns($connection);

      $prefetch = sync_dapodik_sources_for_action(['getGtk', 'getPengguna']);
      if (($prefetch['status'] ?? '') !== 'success') {
        echo json_encode(['status' => 'error', 'message' => $prefetch['message'] ?? 'Gagal mengambil data Dapodik']);
        exit;
      }

      $processed = 0; $updated = 0; $inserted = 0; $skipped = 0; $failed = 0; $deactivated = 0;
      $failed_msgs = [];

      // Preload referensi GTK dari sync_gtk (ptk_id => detail)
      $gtk_ref = [];
      $gq = $connection->query("SELECT ptk_id, jenis_ptk_id_str, status_kepegawaian_id_str, jabatan_ptk_id_str, nip, nuptk, nik FROM sync_gtk WHERE ptk_id IS NOT NULL AND ptk_id != ''");
      if ($gq) {
        while ($gr = $gq->fetch_assoc()) {
          $gtk_ref[$gr['ptk_id']] = $gr;
        }
      }

      $result = $connection->query(
        "SELECT pengguna_id, username, nama, peran_id_str, password, no_hp, ptk_id
         FROM sync_pengguna WHERE username IS NOT NULL AND username != '' ORDER BY nama"
      );
      if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Query sync_pengguna gagal: ' . $connection->error]);
        exit;
      }
      if ($result->num_rows == 0) {
        echo json_encode(['status' => 'info', 'message' => 'Tidak ada data di sync_pengguna.']);
        exit;
      }

      $now = date('Y-m-d H:i:s');
      $total_rows = intval($result->num_rows);
      push_sync_progress('getPengguna', 'running', 'Memulai sinkronisasi tabel Pengguna.', ['scope' => 'table', 'total' => $total_rows]);

      while ($p = $result->fetch_assoc()) {
        $processed++;
        $pengguna_id    = $p['pengguna_id'];
        $username       = $p['username'];
        $fullname       = $p['nama'] ?? '';
        $peran_id_str   = $p['peran_id_str'] ?? '';
        $password       = !empty($p['password']) ? $p['password'] : password_hash('12345', PASSWORD_DEFAULT);
        $phone          = $p['no_hp'] ?? '';
        $ptk_id         = $p['ptk_id'] ?? '';
        $email          = $username;
        $is_ptk_role    = (strcasecmp(trim((string) $peran_id_str), 'PTK') === 0);
        $has_sync_gtk   = (!empty($ptk_id) && isset($gtk_ref[$ptk_id]));
        $force_inactive = ($is_ptk_role && !$has_sync_gtk);
        $active_flag    = $force_inactive ? 'N' : 'Y';
        $status_flag    = 'Offline';
        $sync_flag      = $force_inactive ? 'manual' : 'synced';
        push_sync_progress('getPengguna', 'running', 'Memproses baris pengguna: ' . ($username ?: ($fullname ?: ('Baris ' . $processed))), [
          'scope' => 'row',
          'row_label' => $username ?: ($fullname ?: ('Baris ' . $processed)),
          'processed' => $processed,
          'total' => $total_rows,
          'updated' => $updated,
          'inserted' => $inserted,
          'failed' => $failed,
          'skipped' => $skipped
        ]);

        // Jangan sinkronkan akun Peserta Didik ke tabel admin.
        if (strcasecmp(trim($peran_id_str), 'Peserta Didik') === 0) {
          $skipped++;
          continue;
        }

        // Tentukan level_id dan jenis_ptk_id_str
        $jenis_ptk_id_str = '';
        if ($peran_id_str === 'Operator Sekolah') {
          $level_id = 1;
        } elseif ($peran_id_str === 'Kepala Sekolah') {
          $level_id = 13;
        } elseif ($peran_id_str === 'PTK') {
          $jenis_ptk_id_str = !empty($ptk_id) ? (($gtk_ref[$ptk_id]['jenis_ptk_id_str'] ?? '')) : '';
          $level_id = (stripos($jenis_ptk_id_str, 'Guru') !== false) ? 2 : 3;
        } else {
          $level_id = 3;
        }

        $gtk_status_kepegawaian = !empty($ptk_id) ? (($gtk_ref[$ptk_id]['status_kepegawaian_id_str'] ?? '')) : '';
        $gtk_jabatan_ptk = !empty($ptk_id) ? (($gtk_ref[$ptk_id]['jabatan_ptk_id_str'] ?? '')) : '';
        $gtk_nip = !empty($ptk_id) ? (($gtk_ref[$ptk_id]['nip'] ?? '')) : '';
        $gtk_nuptk = !empty($ptk_id) ? (($gtk_ref[$ptk_id]['nuptk'] ?? '')) : '';
        $gtk_nik = !empty($ptk_id) ? (($gtk_ref[$ptk_id]['nik'] ?? '')) : '';

        // Cek apakah sudah ada di admin berdasarkan pengguna_id
        $cek = $connection->prepare(
          "SELECT admin_id, avatar, gelar_depan, gelar_belakang, tugas_tambahan, jenis_ptk_id_str, gtk_status_kepegawaian, gtk_jabatan_ptk, gtk_nip, gtk_nuptk, gtk_nik FROM admin WHERE pengguna_id = ? LIMIT 1"
        );
        $cek->bind_param('s', $pengguna_id);
        $cek->execute();
        $row = $cek->get_result()->fetch_assoc();
        $cek->close();

        if ($row) {
          // UPDATE — jaga avatar, gelar, tugas_tambahan yang diisi manual
          $avatar  = !empty($row['avatar']) ? $row['avatar'] : 'avatar.jpg';
          $gelar_d = $row['gelar_depan'] ?? '';
          $gelar_b = $row['gelar_belakang'] ?? '';
          $tugas   = $row['tugas_tambahan'] ?? '';
          if ($jenis_ptk_id_str === '') $jenis_ptk_id_str = (string)($row['jenis_ptk_id_str'] ?? '');
          if ($gtk_status_kepegawaian === '') $gtk_status_kepegawaian = (string)($row['gtk_status_kepegawaian'] ?? '');
          if ($gtk_jabatan_ptk === '') $gtk_jabatan_ptk = (string)($row['gtk_jabatan_ptk'] ?? '');
          if ($gtk_nip === '') $gtk_nip = (string)($row['gtk_nip'] ?? '');
          if ($gtk_nuptk === '') $gtk_nuptk = (string)($row['gtk_nuptk'] ?? '');
          if ($gtk_nik === '') $gtk_nik = (string)($row['gtk_nik'] ?? '');
          $aid     = intval($row['admin_id']);
          $aid_str = (string) $aid;
          $upd = $connection->prepare(
            "UPDATE admin SET
               username=?, email=?, password=?, fullname=?, phone=?,
               avatar=?, gelar_depan=?, gelar_belakang=?, level_id=?,
               peran_id_str=?, ptk_id=?, jenis_ptk_id_str=?, gtk_status_kepegawaian=?, gtk_jabatan_ptk=?, gtk_nip=?, gtk_nuptk=?, gtk_nik=?, tugas_tambahan=?,
               active=?, status=?,
               sync_status=?, last_sync_at=NOW(), updated_at=NOW()
             WHERE admin_id=?"
          );
          // 22 params, gunakan string untuk menjaga fleksibilitas casting MySQL
          $upd->bind_param(str_repeat('s', 22),
            $username, $email, $password, $fullname, $phone,
            $avatar, $gelar_d, $gelar_b, $level_id,
            $peran_id_str, $ptk_id, $jenis_ptk_id_str, $gtk_status_kepegawaian, $gtk_jabatan_ptk, $gtk_nip, $gtk_nuptk, $gtk_nik, $tugas,
            $active_flag, $status_flag, $sync_flag,
            $aid_str
          );
          if ($upd->execute()) {
            $updated++;
            if ($force_inactive) {
              $deactivated++;
            }
          }
          else { $failed++; $failed_msgs[] = "Update $username: " . $upd->error; }
          $upd->close();
        } else {
          // INSERT
          $avatar  = 'avatar.jpg';
          $tugas   = '';
          $gelar_d = '';
          $gelar_b = '';
          $ip_val  = '127.0.0.1';
          $br_val  = 'System Sync';
          $ins = $connection->prepare(
            "INSERT INTO admin
               (pengguna_id, username, email, password, fullname, phone, avatar,
                gelar_depan, gelar_belakang, level_id, peran_id_str, ptk_id,
               jenis_ptk_id_str, gtk_status_kepegawaian, gtk_jabatan_ptk, gtk_nip, gtk_nuptk, gtk_nik,
               active, status, tugas_tambahan, time, ip, browser,
                sync_status, last_sync_at, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                     ?, NOW(), NOW(), NOW())"
          );
           // 25 params, gunakan string untuk menjaga fleksibilitas casting MySQL
           $ins->bind_param(str_repeat('s', 25),
            $pengguna_id, $username, $email, $password, $fullname, $phone, $avatar,
            $gelar_d, $gelar_b, $level_id, $peran_id_str, $ptk_id,
            $jenis_ptk_id_str, $gtk_status_kepegawaian, $gtk_jabatan_ptk, $gtk_nip, $gtk_nuptk, $gtk_nik,
            $active_flag, $status_flag, $tugas, $now, $ip_val, $br_val,
            $sync_flag
          );
          if ($ins->execute()) {
            $inserted++;
            if ($force_inactive) {
              $deactivated++;
            }
          }
          else { $failed++; $failed_msgs[] = "Insert $username: " . $ins->error; }
          $ins->close();
        }
      }

      // Hard safeguard: jika PTK tidak ditemukan lagi di sync_gtk, paksa nonaktif walau ada di sync_pengguna
      $deactivate_sql = "
        UPDATE admin a
        LEFT JOIN sync_gtk g ON TRIM(COALESCE(g.ptk_id, '')) = TRIM(COALESCE(a.ptk_id, ''))
        SET
          a.active = 'N',
          a.status = 'Offline',
          a.sync_status = 'manual',
          a.updated_at = NOW()
        WHERE
          a.ptk_id IS NOT NULL
          AND TRIM(a.ptk_id) <> ''
          AND g.ptk_id IS NULL
          AND (COALESCE(a.peran_id_str, '') = 'PTK' OR a.level_id IN (2,3))
          AND UPPER(TRIM(COALESCE(a.active, 'N'))) = 'Y'
      ";
      if ($connection->query($deactivate_sql)) {
        $deactivated += (int) $connection->affected_rows;
      } else {
        $failed++;
        $failed_msgs[] = 'Nonaktifkan admin PTK tanpa sync_gtk gagal: ' . $connection->error;
      }

      $msg = "Processed: $processed, Updated: $updated, Inserted: $inserted, Skipped: $skipped, Deactivated: $deactivated, Failed: $failed";
      if ($failed > 0) $msg .= "\nDetails: " . implode('; ', $failed_msgs);
      push_sync_progress('getPengguna', $failed > 0 ? 'failed' : 'success', $msg, [
        'scope' => 'table',
        'processed' => $processed,
        'total' => $total_rows,
        'updated' => $updated,
        'inserted' => $inserted,
        'failed' => $failed,
        'skipped' => $skipped
      ]);
      save_sync_log('getPengguna', $failed > 0 ? 'failed' : 'success', $processed, $msg);
      echo json_encode(['status' => $failed > 0 ? 'error' : 'success', 'message' => $msg]);
      exit;

      // Handler untuk melengkapi tabel admin dari sync_gtk
    case 'getGtk':
      header('Content-Type: application/json');
      ob_clean();
      // Lepas lock session sebelum proses panjang agar polling get-status bisa berjalan paralel
      session_write_close();
      ensure_admin_gtk_reference_columns($connection);

      $prefetch = sync_dapodik_sources_for_action(['getGtk', 'getPengguna']);
      if (($prefetch['status'] ?? '') !== 'success') {
        echo json_encode(['status' => 'error', 'message' => $prefetch['message'] ?? 'Gagal mengambil data Dapodik']);
        exit;
      }

      if (!$connection->query("SHOW TABLES LIKE 'sync_gtk'")->num_rows) {
        echo json_encode(['status' => 'info', 'message' => 'Tabel sync_gtk tidak ditemukan.']);
        exit;
      }

      // Preload data pengguna keyed by ptk_id
      $pengguna_by_ptk = [];
      $pq = $connection->query(
        "SELECT pengguna_id, ptk_id, username, password, no_hp, peran_id_str
         FROM sync_pengguna WHERE ptk_id IS NOT NULL AND ptk_id != ''"
      );
      if ($pq) { while ($pr = $pq->fetch_assoc()) { $pengguna_by_ptk[$pr['ptk_id']] = $pr; } }

      $result = $connection->query(
        "SELECT ptk_id, nama, jenis_ptk_id_str, status_kepegawaian_id_str, jabatan_ptk_id_str, nip, nuptk, nik
         FROM sync_gtk WHERE nama IS NOT NULL AND nama != '' ORDER BY nama"
      );
      if (!$result || $result->num_rows == 0) {
        echo json_encode(['status' => 'info', 'message' => 'Tidak ada data GTK di sync_gtk.']);
        exit;
      }

      $processed = 0; $updated = 0; $inserted = 0; $skipped = 0; $failed = 0; $deactivated = 0;
      $failed_msgs = [];
      $now = date('Y-m-d H:i:s');
      $total_rows = intval($result->num_rows);
      push_sync_progress('getGtk', 'running', 'Memulai sinkronisasi tabel GTK.', ['scope' => 'table', 'total' => $total_rows]);

      while ($gtk = $result->fetch_assoc()) {
        $processed++;
        $ptk_id   = $gtk['ptk_id'] ?? '';
        if (empty($ptk_id)) { $skipped++; continue; }
        $fullname  = $gtk['nama'] ?? '';
        $jenis_ptk = $gtk['jenis_ptk_id_str'] ?? '';
        $gtk_status_kepegawaian = $gtk['status_kepegawaian_id_str'] ?? '';
        $gtk_jabatan_ptk = $gtk['jabatan_ptk_id_str'] ?? '';
        $gtk_nip = $gtk['nip'] ?? '';
        $gtk_nuptk = $gtk['nuptk'] ?? '';
        $gtk_nik = $gtk['nik'] ?? '';
        $level_id  = (stripos($jenis_ptk, 'Guru') !== false) ? 2 : 3;

        $pengguna    = $pengguna_by_ptk[$ptk_id] ?? null;
        $pengguna_id = $pengguna['pengguna_id'] ?? '';
        $username    = $pengguna['username'] ?? '';
        $password    = $pengguna
          ? (!empty($pengguna['password']) ? $pengguna['password'] : password_hash('12345', PASSWORD_DEFAULT))
          : password_hash('12345', PASSWORD_DEFAULT);
        $phone       = $pengguna['no_hp'] ?? '';
        $email       = $username;
        $peran       = $pengguna['peran_id_str'] ?? 'PTK';
        push_sync_progress('getGtk', 'running', 'Memproses baris GTK: ' . ($fullname ?: ($ptk_id ?: ('Baris ' . $processed))), [
          'scope' => 'row',
          'row_label' => $fullname ?: ($ptk_id ?: ('Baris ' . $processed)),
          'processed' => $processed,
          'total' => $total_rows,
          'updated' => $updated,
          'inserted' => $inserted,
          'failed' => $failed,
          'skipped' => $skipped
        ]);

        $cek = $connection->prepare(
          "SELECT admin_id, level_id, avatar, gelar_depan, gelar_belakang, gtk_status_kepegawaian, gtk_jabatan_ptk, gtk_nip, gtk_nuptk, gtk_nik FROM admin WHERE ptk_id = ? LIMIT 1"
        );
        $cek->bind_param('s', $ptk_id);
        $cek->execute();
        $row = $cek->get_result()->fetch_assoc();
        $cek->close();

        if ($row) {
          $aid       = intval($row['admin_id']);
          $cur_level = intval($row['level_id']);
          $avatar    = !empty($row['avatar']) ? $row['avatar'] : 'avatar.jpg';
          $gelar_d   = $row['gelar_depan'] ?? '';
          $gelar_b   = $row['gelar_belakang'] ?? '';
          if ($gtk_status_kepegawaian === '') $gtk_status_kepegawaian = (string)($row['gtk_status_kepegawaian'] ?? '');
          if ($gtk_jabatan_ptk === '') $gtk_jabatan_ptk = (string)($row['gtk_jabatan_ptk'] ?? '');
          if ($gtk_nip === '') $gtk_nip = (string)($row['gtk_nip'] ?? '');
          if ($gtk_nuptk === '') $gtk_nuptk = (string)($row['gtk_nuptk'] ?? '');
          if ($gtk_nik === '') $gtk_nik = (string)($row['gtk_nik'] ?? '');
          // Tidak override level Operator Sekolah (1) dan Kepala Sekolah (13)
          if ($cur_level == 1 || $cur_level == 13) {
            $upd = $connection->prepare(
              "UPDATE admin SET fullname=?, jenis_ptk_id_str=?, gtk_status_kepegawaian=?, gtk_jabatan_ptk=?, gtk_nip=?, gtk_nuptk=?, gtk_nik=?, avatar=?, gelar_depan=?, gelar_belakang=?,
               active='Y', status='Offline',
               sync_status='synced', last_sync_at=NOW(), updated_at=NOW()
               WHERE admin_id=?"
            );
            $aid_str = (string) $aid;
            $upd->bind_param(str_repeat('s', 11), $fullname, $jenis_ptk, $gtk_status_kepegawaian, $gtk_jabatan_ptk, $gtk_nip, $gtk_nuptk, $gtk_nik, $avatar, $gelar_d, $gelar_b, $aid_str);
          } else {
            $upd = $connection->prepare(
              "UPDATE admin SET fullname=?, jenis_ptk_id_str=?, level_id=?, gtk_status_kepegawaian=?, gtk_jabatan_ptk=?, gtk_nip=?, gtk_nuptk=?, gtk_nik=?, avatar=?, gelar_depan=?, gelar_belakang=?,
               active='Y', status='Offline',
               sync_status='synced', last_sync_at=NOW(), updated_at=NOW()
               WHERE admin_id=?"
            );
            $aid_str = (string) $aid;
            $upd->bind_param(str_repeat('s', 12), $fullname, $jenis_ptk, $level_id, $gtk_status_kepegawaian, $gtk_jabatan_ptk, $gtk_nip, $gtk_nuptk, $gtk_nik, $avatar, $gelar_d, $gelar_b, $aid_str);
          }
          if ($upd->execute()) { $updated++; }
          else { $failed++; $failed_msgs[] = "Update ptk $ptk_id: " . $upd->error; }
          $upd->close();
        } else {
          // INSERT: GTK tanpa akun pengguna (atau belum di-sync dari getPengguna)
          $avatar  = 'avatar.jpg';
          $active  = 'Y';
          $status  = 'Offline';
          $tugas   = '';
          $gelar_d = '';
          $gelar_b = '';
          $ip_val  = '127.0.0.1';
          $br_val  = 'System Sync';
          $ins = $connection->prepare(
            "INSERT INTO admin
               (ptk_id, pengguna_id, fullname, jenis_ptk_id_str, level_id,
                username, email, password, phone, avatar, gelar_depan, gelar_belakang,
                peran_id_str, gtk_status_kepegawaian, gtk_jabatan_ptk, gtk_nip, gtk_nuptk, gtk_nik,
                active, status, tugas_tambahan, time, ip, browser,
                sync_status, last_sync_at, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                     'synced', NOW(), NOW(), NOW())"
          );
          // 25 params, gunakan string untuk menjaga fleksibilitas casting MySQL
          $ins->bind_param(str_repeat('s', 25),
            $ptk_id, $pengguna_id, $fullname, $jenis_ptk, $level_id,
            $username, $email, $password, $phone, $avatar, $gelar_d, $gelar_b,
            $peran, $gtk_status_kepegawaian, $gtk_jabatan_ptk, $gtk_nip, $gtk_nuptk, $gtk_nik,
            $active, $status, $tugas, $now, $ip_val, $br_val
          );
          if ($ins->execute()) { $inserted++; }
          else { $failed++; $failed_msgs[] = "Insert ptk $ptk_id: " . $ins->error; }
          $ins->close();
        }
      }

      // Nonaktifkan admin berbasis GTK yang sudah tidak ada lagi di tabel sync_gtk
      $deactivate_sql = "
        UPDATE admin a
        LEFT JOIN sync_gtk g ON TRIM(COALESCE(g.ptk_id, '')) = TRIM(COALESCE(a.ptk_id, ''))
        SET
          a.active = 'N',
          a.status = 'Offline',
          a.sync_status = 'manual',
          a.updated_at = NOW()
        WHERE
          a.ptk_id IS NOT NULL
          AND TRIM(a.ptk_id) <> ''
          AND g.ptk_id IS NULL
          AND UPPER(TRIM(COALESCE(a.active, 'N'))) = 'Y'
      ";
      if ($connection->query($deactivate_sql)) {
        $deactivated = (int) $connection->affected_rows;
      } else {
        $failed++;
        $failed_msgs[] = 'Nonaktifkan admin GTK tidak sinkron gagal: ' . $connection->error;
      }

      $msg = "Processed: $processed, Updated: $updated, Inserted: $inserted, Skipped: $skipped, Deactivated: $deactivated, Failed: $failed";
      if ($failed > 0) $msg .= "\nDetails: " . implode('; ', $failed_msgs);
      push_sync_progress('getGtk', $failed > 0 ? 'failed' : 'success', $msg, [
        'scope' => 'table',
        'processed' => $processed,
        'total' => $total_rows,
        'updated' => $updated,
        'inserted' => $inserted,
        'failed' => $failed,
        'skipped' => $skipped
      ]);
      save_sync_log('getGtk', $failed > 0 ? 'failed' : 'success', $processed, $msg);
      echo json_encode(['status' => $failed > 0 ? 'error' : 'success', 'message' => $msg]);
      break;

    // Handler untuk sinkronisasi Rombongan Belajar dari sync_rombongan_belajar ke kelas
    case 'getRombonganBelajar':
      header('Content-Type: application/json');
      ob_clean();
      // Lepas lock session sebelum proses panjang agar polling get-status bisa berjalan paralel
      session_write_close();

      $prefetch = sync_dapodik_sources_for_action(['getRombonganBelajar']);
      if (($prefetch['status'] ?? '') !== 'success') {
        echo json_encode(['status' => 'error', 'message' => $prefetch['message'] ?? 'Gagal mengambil data Dapodik']);
        exit;
      }

      // Hanya tarik rombel dengan jenis_rombel = 1
      $query = "SELECT rombongan_belajar_id, nama, tingkat_pendidikan_id, tingkat_pendidikan_id_str,
                semester_id, jenis_rombel, jenis_rombel_str, kurikulum_id, kurikulum_id_str,
                id_ruang, id_ruang_str, moving_class, ptk_id, ptk_id_str, 
                jurusan_id, jurusan_id_str
             FROM sync_rombongan_belajar 
             WHERE nama IS NOT NULL AND nama != '' AND jenis_rombel = '1'
             ORDER BY nama";
      $result = $connection->query($query);

      if (!$result || $result->num_rows == 0) {
        $msg = "Data Rombongan Belajar berhasil diproses. Updated: 0, Inserted: 0, Skipped: 0";
        $processed = 0;
        $updated = 0;
        $inserted = 0;
        $skipped = 0;
        $failed = 0;
        $failed_msgs = [];
      }

      $processed = 0;
      $updated = 0;
      $inserted = 0;
      $skipped = 0;
      $failed = 0;
      $failed_msgs = [];
      $total_rows = ($result && $result->num_rows > 0) ? intval($result->num_rows) : 0;
      push_sync_progress('getRombonganBelajar', 'running', 'Memulai sinkronisasi tabel Rombongan Belajar.', ['scope' => 'table', 'total' => $total_rows]);

      if ($result && $result->num_rows > 0) {
        while ($rombel_data = $result->fetch_assoc()) {
          $processed++;
          push_sync_progress('getRombonganBelajar', 'running', 'Memproses baris rombel: ' . (($rombel_data['nama'] ?? '') !== '' ? $rombel_data['nama'] : ('Baris ' . $processed)), [
            'scope' => 'row',
            'row_label' => ($rombel_data['nama'] ?? '') !== '' ? $rombel_data['nama'] : ('Baris ' . $processed),
            'processed' => $processed,
            'total' => $total_rows,
            'updated' => $updated,
            'inserted' => $inserted,
            'failed' => $failed,
            'skipped' => $skipped
          ]);
          // Validasi minimal
          if (empty($rombel_data['rombongan_belajar_id']) || empty($rombel_data['nama'])) {
            $skipped++;
            continue;
          }
          // Siapkan data (escape untuk aman)
          $avatar = 'avatar.jpg';
          $rb_id = $connection->real_escape_string($rombel_data['rombongan_belajar_id']);
          $nama_kelas = $connection->real_escape_string($rombel_data['nama']);
          $wali_ptk = $connection->real_escape_string($rombel_data['ptk_id'] ?? '');
          $wali_nama = $connection->real_escape_string($rombel_data['ptk_id_str'] ?? '');
          $tingkat_id = $connection->real_escape_string($rombel_data['tingkat_pendidikan_id'] ?? '');
          $tingkat_str = $connection->real_escape_string($rombel_data['tingkat_pendidikan_id_str'] ?? '');
          $semester_id = $connection->real_escape_string($rombel_data['semester_id'] ?? '');
          $jenis = $connection->real_escape_string($rombel_data['jenis_rombel'] ?? '');
          $jenis_str = $connection->real_escape_string($rombel_data['jenis_rombel_str'] ?? '');
          $kurikulum_id = $connection->real_escape_string($rombel_data['kurikulum_id'] ?? '');
          $kurikulum_str = $connection->real_escape_string($rombel_data['kurikulum_id_str'] ?? '');
          $id_ruang = $connection->real_escape_string($rombel_data['id_ruang'] ?? '');
          $nama_ruang = $connection->real_escape_string($rombel_data['id_ruang_str'] ?? '');
          $moving_class = $connection->real_escape_string($rombel_data['moving_class'] ?? '');
          // jurusan diisi dari sync_rombongan_belajar
          $jurusan_sync_id = is_numeric($rombel_data['jurusan_id']) ? intval($rombel_data['jurusan_id']) : 0;
          $jurusan_sync_str = $connection->real_escape_string($rombel_data['jurusan_id_str'] ?? '');

          // Cek apakah ada kelas dengan rombongan_belajar_id
          $cek_sql = "SELECT kelas_id FROM kelas WHERE rombongan_belajar_id = '$rb_id' LIMIT 1";
          $cek_res = $connection->query($cek_sql);
          if ($cek_res === false) {
            $failed++;
            $failed_msgs[] = "$rb_id: cek error - " . $connection->error;
            continue;
          }
          if ($cek_res->num_rows > 0) {
            // Update (gunakan escaped values)
            $row_kelas = $cek_res->fetch_assoc();
            $kelas_id = intval($row_kelas['kelas_id']);
            // Cek avatar di kelas (jika kolom tersedia)
            if ($has_avatar) {
              $cek_avatar_sql = "SELECT avatar FROM kelas WHERE kelas_id = {$kelas_id} LIMIT 1";
              $cek_avatar_res = $connection->query($cek_avatar_sql);
              if ($cek_avatar_res && $cek_avatar_res->num_rows > 0) {
                $row_avatar = $cek_avatar_res->fetch_assoc();
                if (!empty($row_avatar['avatar'])) {
                  $avatar = $connection->real_escape_string($row_avatar['avatar']);
                }
              }
            }

            // Build update parts dynamically so we only include `avatar` when available
            $update_parts = [
              "nama_kelas = '{$nama_kelas}'",
              "jurusan_id = {$jurusan_sync_id}",
              "rombongan_belajar_id = '{$rb_id}'",
              "wali_kelas_ptk_id = '{$wali_ptk}'",
              "wali_kelas_nama = '{$wali_nama}'",
              "tingkat_pendidikan_id = '{$tingkat_id}'",
              "tingkat_pendidikan_str = '{$tingkat_str}'",
              "semester_id = '{$semester_id}'",
              "jenis_rombel = '{$jenis}'",
              "jenis_rombel_str = '{$jenis_str}'",
              "kurikulum_id = '{$kurikulum_id}'",
              "kurikulum_str = '{$kurikulum_str}'",
              "id_ruang = '{$id_ruang}'",
              "nama_ruang = '{$nama_ruang}'",
              "moving_class = '{$moving_class}'",
              "sync_jurusan_id = '{$connection->real_escape_string($jurusan_sync_id)}'",
              "sync_jurusan_str = '{$connection->real_escape_string($jurusan_sync_str)}'"
            ];
            if ($has_avatar) {
              $update_parts[] = "avatar = '{$avatar}'";
            }
            push_sync_progress('getRombonganBelajar', $failed > 0 ? 'failed' : 'success', $msg, [
              'scope' => 'table',
              'processed' => $processed,
              'total' => $total_rows,
              'updated' => $updated,
              'inserted' => $inserted,
              'failed' => $failed,
              'skipped' => $skipped
            ]);
            $update_parts[] = "sync_status = 'active'";
            $update_parts[] = "last_sync_at = CURRENT_TIMESTAMP";

            $update_sql = "UPDATE kelas SET " . implode(",\n", $update_parts) . " WHERE kelas_id = {$kelas_id}";
            if ($connection->query($update_sql)) {
              $updated++;
            } else {
              $failed++;
              $failed_msgs[] = "$rb_id: update error - " . $connection->error;
            }
          } else {
            // Insert (gunakan escaped values). Jangan sertakan kolom `avatar` jika tidak ada di DB
            if ($has_avatar) {
              $insert_sql = "
                INSERT INTO kelas (
                  nama_kelas, jurusan_id, rombongan_belajar_id, wali_kelas_ptk_id, wali_kelas_nama,
                  tingkat_pendidikan_id, tingkat_pendidikan_str, semester_id, jenis_rombel, jenis_rombel_str,
                  kurikulum_id, kurikulum_str, id_ruang, nama_ruang, moving_class, sync_jurusan_id, sync_jurusan_str,
                  avatar, sync_status, last_sync_at, created_from_sync
                ) VALUES (
                  '{$nama_kelas}', {$jurusan_sync_id}, '{$rb_id}', '{$wali_ptk}', '{$wali_nama}',
                  '{$tingkat_id}', '{$tingkat_str}', '{$semester_id}', '{$jenis}', '{$jenis_str}',
                  '{$kurikulum_id}', '{$kurikulum_str}', '{$id_ruang}', '{$nama_ruang}', '{$moving_class}', '{$connection->real_escape_string($jurusan_sync_id)}', '{$connection->real_escape_string($jurusan_sync_str)}',
                  '{$avatar}', 'active', CURRENT_TIMESTAMP, 1
                )
              ";
            } else {
              $insert_sql = "
                INSERT INTO kelas (
                  nama_kelas, jurusan_id, rombongan_belajar_id, wali_kelas_ptk_id, wali_kelas_nama,
                  tingkat_pendidikan_id, tingkat_pendidikan_str, semester_id, jenis_rombel, jenis_rombel_str,
                  kurikulum_id, kurikulum_str, id_ruang, nama_ruang, moving_class, sync_jurusan_id, sync_jurusan_str,
                  sync_status, last_sync_at, created_from_sync
                ) VALUES (
                  '{$nama_kelas}', {$jurusan_sync_id}, '{$rb_id}', '{$wali_ptk}', '{$wali_nama}',
                  '{$tingkat_id}', '{$tingkat_str}', '{$semester_id}', '{$jenis}', '{$jenis_str}',
                  '{$kurikulum_id}', '{$kurikulum_str}', '{$id_ruang}', '{$nama_ruang}', '{$moving_class}', '{$connection->real_escape_string($jurusan_sync_id)}', '{$connection->real_escape_string($jurusan_sync_str)}',
                  'active', CURRENT_TIMESTAMP, 1
                )
              ";
            }
            if ($connection->query($insert_sql)) {
              $inserted++;
            } else {
              $failed++;
              $failed_msgs[] = "$rb_id: insert error - " . $connection->error;
            }
          }
        }
      }

      // Buat pesan ringkas dan simpan log (hanya satu kali di akhir)
      $msg = "Processed: $processed, Updated: $updated, Inserted: $inserted, Skipped: $skipped, Failed: $failed";
      if ($failed > 0) $msg .= "\nDetails: " . implode("; ", $failed_msgs);
      // Capture buffered output (if any) and append to message for debugging JSON parser errors
      $buffer = trim((string)ob_get_clean());
      if (!empty($buffer)) {
        $msg .= "\nDebugOutput: " . $buffer;
      }
      save_sync_log('getRombonganBelajar', $failed > 0 ? 'failed' : 'success', $processed, $msg);
      echo json_encode([
        'status' => $failed > 0 ? 'error' : 'success',
        'message' => $msg
      ]);
      exit;

      // Handler untuk sinkronisasi Peserta Didik dari sync_peserta_didik ke user
    case 'getPesertaDidik':
      header('Content-Type: application/json');
      ob_clean();
      // Lepas lock session sebelum proses panjang agar polling get-status bisa berjalan paralel
      session_write_close();

      // Buat proses lebih tahan terhadap server lambat
      if (function_exists('set_time_limit')) {
        @set_time_limit(0);
      }
      @ini_set('max_execution_time', '0');
      @ini_set('memory_limit', '512M');
      
      // Capture any unexpected output so AJAX JSON parsing won't fail
      ob_start();

      $prefetch = sync_dapodik_sources_for_action(['getRombonganBelajar', 'getPesertaDidik']);
      if (($prefetch['status'] ?? '') !== 'success') {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => $prefetch['message'] ?? 'Gagal mengambil data Dapodik']);
        exit;
      }
      
      // Proses sinkronisasi Peserta Didik dari sync_peserta_didik ke user
      $processed = 0;
      $updated = 0;
      $inserted = 0;
      $skipped = 0;
      $failed = 0;
      $failed_msgs = [];
      
      try {
        // Cek tabel sync_peserta_didik ada atau tidak
        $table_check = $connection->query("SHOW TABLES LIKE 'sync_peserta_didik'");
        if (!$table_check || $table_check->num_rows == 0) {
          $buffer = trim((string)ob_get_clean());
          echo json_encode([
            'status' => 'error',
            'message' => 'Tabel sync_peserta_didik tidak ditemukan. Pastikan sinkronisasi data Dapodik sudah berhasil.'
          ]);
          exit;
        }

        // Ambil data dari sync_peserta_didik dengan error handling
          $query = "SELECT registrasi_id, peserta_didik_id, jenis_pendaftaran_id, jenis_pendaftaran_id_str,
                nipd, tanggal_masuk_sekolah, sekolah_asal, nama, nisn, jenis_kelamin, nik,
                tempat_lahir, tanggal_lahir, agama_id, agama_id_str,
                nomor_telepon_rumah, nomor_telepon_seluler,
                nama_ayah, pekerjaan_ayah_id, pekerjaan_ayah_id_str,
                nama_ibu, pekerjaan_ibu_id, pekerjaan_ibu_id_str,
                nama_wali, pekerjaan_wali_id, pekerjaan_wali_id_str,
                anak_keberapa, tinggi_badan, berat_badan, email,
                semester_id, anggota_rombel_id, rombongan_belajar_id,
                tingkat_pendidikan_id, nama_rombel,
                kurikulum_id, kurikulum_id_str, kebutuhan_khusus
              FROM sync_peserta_didik
               WHERE nama IS NOT NULL AND nama != ''
               ORDER BY nama";

        $result = $connection->query($query);
        if (!$result) {
          $buffer = trim((string)ob_get_clean());
          save_sync_log('getPesertaDidik', 'failed', 0, 'Error query sync_peserta_didik: ' . $connection->error);
          echo json_encode([
            'status' => 'error', 
            'message' => 'Error saat mengambil data dari sync_peserta_didik: ' . $connection->error
          ]);
          exit;
        }

        if ($result->num_rows == 0) {
          $buffer = trim((string)ob_get_clean());
          save_sync_log('getPesertaDidik', 'success', 0, 'Tidak ada data peserta didik untuk diproses');
          echo json_encode([
            'status' => 'info',
            'message' => "Tidak ada data Peserta Didik di tabel sync_peserta_didik untuk diproses."
          ]);
          exit;
        }

        $total_rows = intval($result->num_rows);
        push_sync_progress('getPesertaDidik', 'running', 'Memulai sinkronisasi tabel Peserta Didik.', ['scope' => 'table', 'total' => $total_rows]);

        // Ambil semua NISN, NIPD, NIK dari sync_peserta_didik untuk validasi user yang tidak ada
        $sync_nisn = [];
        $sync_nipd = [];
        $sync_nik = [];
        $result_sync_ids = $connection->query("SELECT nisn, nipd, nik FROM sync_peserta_didik WHERE nama IS NOT NULL AND nama != ''");
        if ($result_sync_ids && $result_sync_ids->num_rows > 0) {
          while ($row = $result_sync_ids->fetch_assoc()) {
            if (!empty($row['nisn'])) $sync_nisn[] = $connection->real_escape_string($row['nisn']);
            if (!empty($row['nipd'])) $sync_nipd[] = $connection->real_escape_string($row['nipd']);
            if (!empty($row['nik'])) $sync_nik[] = $connection->real_escape_string($row['nik']);
          }
        }

        // Update user yang tidak ada di sync_peserta_didik menjadi Tidak Aktif
        try {
          $where_not_in = [];
          if (count($sync_nisn) > 0) $where_not_in[] = "(nisn NOT IN ('" . implode("','", $sync_nisn) . "') OR nisn IS NULL OR nisn = '')";
          if (count($sync_nipd) > 0) $where_not_in[] = "(nipd NOT IN ('" . implode("','", $sync_nipd) . "') OR nipd IS NULL OR nipd = '')";
          if (count($sync_nik) > 0) $where_not_in[] = "(nik NOT IN ('" . implode("','", $sync_nik) . "') OR nik IS NULL OR nik = '')";
          if (count($where_not_in) > 0) {
            $where_sql = implode(' AND ', $where_not_in);
            // Hanya update user yang pernah di-sync sebelumnya (memiliki rombel_sync_status = 'active')
            $update_non_sync = "UPDATE user SET status='Tidak Aktif', rombel_sync_status='inactive' WHERE $where_sql AND status!='Tidak Aktif' AND rombel_sync_status='active'";
            $update_result = $connection->query($update_non_sync);
            if (!$update_result) {
              $failed_msgs[] = 'Error updating inactive users: ' . $connection->error;
            }
          }
        } catch (Exception $e) {
          $failed_msgs[] = 'Exception during inactive user update: ' . $e->getMessage();
        }

        // Update status menjadi 'Aktif' untuk semua user yang ada di sync_peserta_didik
        try {
          if (count($sync_nisn) > 0) {
            $active_nisn_sql = "UPDATE user SET status='Aktif', rombel_sync_status='active' WHERE nisn IN ('" . implode("','", $sync_nisn) . "') AND status != 'Aktif'";
            $connection->query($active_nisn_sql);
          }
          if (count($sync_nipd) > 0) {
            $active_nipd_sql = "UPDATE user SET status='Aktif', rombel_sync_status='active' WHERE nipd IN ('" . implode("','", $sync_nipd) . "') AND nisn IS NULL AND status != 'Aktif'";
            $connection->query($active_nipd_sql);
          }
          if (count($sync_nik) > 0) {
            $active_nik_sql = "UPDATE user SET status='Aktif', rombel_sync_status='active' WHERE nik IN ('" . implode("','", $sync_nik) . "') AND nisn IS NULL AND nipd IS NULL AND status != 'Aktif'";
            $connection->query($active_nik_sql);
          }
        } catch (Exception $e) {
          $failed_msgs[] = 'Exception during active user update: ' . $e->getMessage();
        }

        // Prepare mapping lookup for kelas by rombongan_belajar_id and detect user columns
        $has_kelas_id = column_exists($connection, 'user', 'kelas_id');
        $has_jurusan_id = column_exists($connection, 'user', 'jurusan_id');
        $has_wali_kelas = column_exists($connection, 'user', 'wali_kelas');
        $has_kelas_col = column_exists($connection, 'user', 'kelas');
        $has_tingkat = column_exists($connection, 'user', 'tingkat');
        // Detect avatar column in `user` to avoid undefined variable notices
        $has_user_avatar = column_exists($connection, 'user', 'avatar');
        
        // Prepare statements with error handling
        $kelas_stmt = $connection->prepare("SELECT kelas_id, jurusan_id, wali_kelas_ptk_id FROM kelas WHERE rombongan_belajar_id = ? LIMIT 1");
        if (!$kelas_stmt) {
          $failed_msgs[] = 'Error preparing kelas statement: ' . $connection->error;
        }
        
        // prepare admin lookup to convert ptk_id -> admin_id for wali_kelas
        $admin_stmt = $connection->prepare("SELECT admin_id FROM admin WHERE ptk_id = ? LIMIT 1");
        if (!$admin_stmt) {
          $failed_msgs[] = 'Error preparing admin statement: ' . $connection->error;
        }

        while ($siswa_data = $result->fetch_assoc()) {
          $processed++;
          push_sync_progress('getPesertaDidik', 'running', 'Memproses baris peserta didik: ' . (($siswa_data['nama'] ?? '') !== '' ? $siswa_data['nama'] : ('Baris ' . $processed)), [
            'scope' => 'row',
            'row_label' => ($siswa_data['nama'] ?? '') !== '' ? $siswa_data['nama'] : ('Baris ' . $processed),
            'processed' => $processed,
            'total' => $total_rows,
            'updated' => $updated,
            'inserted' => $inserted,
            'failed' => $failed,
            'skipped' => $skipped
          ]);
          
          try {
            // Skip jika tidak ada identifier yang valid
            if (empty($siswa_data['nisn']) && empty($siswa_data['nipd']) && empty($siswa_data['nik'])) {
              $skipped++;
              continue;
            }
            
            // Validasi data wajib
            if (empty($siswa_data['nama'])) {
              $skipped++;
              $failed_msgs[] = "Siswa dengan ID {$siswa_data['peserta_didik_id']} tidak memiliki nama";
              continue;
            }
        // Map rombongan_belajar_id -> kelas/jurusan/wali_kelas (wali_kelas stored as admin_id = wali_kelas_ptk_id)
        $mapped_kelas_id = null;
        $mapped_jurusan_id = null;
        // this will hold actual admin.admin_id (not ptk id)
        $mapped_wali_admin_id = null;
        $rbid = isset($siswa_data['rombongan_belajar_id']) ? $siswa_data['rombongan_belajar_id'] : '';
        if ($rbid !== '' && $kelas_stmt) {
          $kelas_stmt->bind_param('s', $rbid);
          $kelas_stmt->execute();
          $kelas_res = $kelas_stmt->get_result();
          if ($kelas_res && $kelas_res->num_rows > 0) {
            $kelas_row = $kelas_res->fetch_assoc();
            $mapped_kelas_id = $kelas_row['kelas_id'];
            $mapped_jurusan_id = $kelas_row['jurusan_id'];
            $mapped_wali_ptk = $kelas_row['wali_kelas_ptk_id'];
            // resolve wali_kelas_ptk_id -> admin_id if possible
            if (!empty($mapped_wali_ptk) && $admin_stmt) {
              $admin_stmt->bind_param('s', $mapped_wali_ptk);
              $admin_stmt->execute();
              $admin_res = $admin_stmt->get_result();
              if ($admin_res && $admin_res->num_rows > 0) {
                $admin_row = $admin_res->fetch_assoc();
                $mapped_wali_admin_id = $admin_row['admin_id'];
              }
            }
          }
        }
            // Cek apakah data sudah ada di tabel user dengan prioritas NISN > NIPD > NIK
            $check_conditions = [];
            $check_params = [];
            $check_types = '';
            
            if (!empty($siswa_data['nisn'])) {
              $check_conditions[] = "nisn = ?";
              $check_params[] = $siswa_data['nisn'];
              $check_types .= 's';
            }
            if (!empty($siswa_data['nipd']) && empty($siswa_data['nisn'])) {
              $check_conditions[] = "nipd = ?";
              $check_params[] = $siswa_data['nipd'];
              $check_types .= 's';
            }
            if (!empty($siswa_data['nik']) && empty($siswa_data['nisn']) && empty($siswa_data['nipd'])) {
              $check_conditions[] = "nik = ?";
              $check_params[] = $siswa_data['nik'];
              $check_types .= 's';
            }
            
            if (empty($check_conditions)) {
              $skipped++;
              $failed_msgs[] = "Student {$siswa_data['nama']} has no valid identifier (NISN/NIPD/NIK)";
              continue;
            }
            
            $check_query = "SELECT user_id FROM user WHERE (" . implode(' OR ', $check_conditions) . ") LIMIT 1";
            $check_stmt = $connection->prepare($check_query);
            if (!$check_stmt) {
              $failed++;
              $failed_msgs[] = "Error preparing check statement for {$siswa_data['nama']}: " . $connection->error;
              continue;
            }
            
            $check_stmt->bind_param($check_types, ...$check_params);
            if (!$check_stmt->execute()) {
              $failed++;
              $failed_msgs[] = "Error executing check query for {$siswa_data['nama']}: " . $check_stmt->error;
              $check_stmt->close();
              continue;
            }
            
            $check_result = $check_stmt->get_result();
            $avatar_default = 'avatar.jpg';
            if ($check_result->num_rows > 0) {
              // Update data yang sudah ada — hanya update bila ada perbedaan
              $user = $check_result->fetch_assoc();

              // Ambil nilai saat ini dari tabel user untuk dibandingkan
              // Build select to include optional columns if present
              $extra_select = '';
              if ($has_jurusan_id) $extra_select .= ', jurusan_id';
              if ($has_kelas_id) $extra_select .= ', kelas_id';
              if ($has_wali_kelas) $extra_select .= ', wali_kelas';
              if ($has_tingkat) $extra_select .= ', tingkat';
              $cur_sql = "SELECT registrasi_id, peserta_didik_id, jenis_pendaftaran_id, jenis_pendaftaran_id_str,
                          tanggal_masuk_sekolah, agama_id, nomor_telepon_rumah, nomor_telepon_seluler,
                          pekerjaan_ayah_id, pekerjaan_ibu_id, pekerjaan_wali_id,
                          anak_keberapa, tinggi_badan, berat_badan, semester_id, anggota_rombel_id,
                          tingkat_pendidikan_id, nama_rombel, kurikulum_id, kurikulum_id_str, kebutuhan_khusus,
                          nama_lengkap, nisn, nipd, nik, tempat_lahir, tanggal_lahir, jenis_kelamin, agama,
                          nama_ayah, nama_ibu, nama_wali, sekolah_asal, rombongan_belajar_id, kelas_nama,
                          email, telp, anak_ke, pekerjaan_ayah, pekerjaan_ibu, pekerjaan_wali, konfirmasi" . $extra_select . " FROM user WHERE user_id = ? LIMIT 1";
              $cur_stmt = $connection->prepare($cur_sql);
              if (!$cur_stmt) {
                $failed++;
                $failed_msgs[] = "Error preparing current user statement for {$siswa_data['nama']}: " . $connection->error;
                continue;
              }
              
              $cur_stmt->bind_param('i', $user['user_id']);
              if (!$cur_stmt->execute()) {
                $failed++;
                $failed_msgs[] = "Error executing current user query for {$siswa_data['nama']}: " . $cur_stmt->error;
                $cur_stmt->close();
                continue;
              }
              
              $cur_res = $cur_stmt->get_result();
              if (!$cur_res) {
                $failed++;
                $failed_msgs[] = "Error getting current user result for {$siswa_data['nama']}";
                $cur_stmt->close();
                continue;
              }
              
              $current = $cur_res->fetch_assoc();
              $cur_stmt->close();

              $update_fields = [];
              $update_values = [];
              $diff_found = false;

              // Map field sync -> db dengan null checking
              $field_map = [
                'registrasi_id' => 'registrasi_id',
                'peserta_didik_id' => 'peserta_didik_id',
                'jenis_pendaftaran_id' => 'jenis_pendaftaran_id',
                'jenis_pendaftaran_id_str' => 'jenis_pendaftaran_id_str',
                'tanggal_masuk_sekolah' => 'tanggal_masuk_sekolah',
                'nama_lengkap' => 'nama',
                'nisn' => 'nisn',
                'nipd' => 'nipd',
                'nik' => 'nik',
                'tempat_lahir' => 'tempat_lahir',
                'tanggal_lahir' => 'tanggal_lahir',
                'agama_id' => 'agama_id',
                'jenis_kelamin' => 'jenis_kelamin',
                'agama' => 'agama_id_str',
                'nomor_telepon_rumah' => 'nomor_telepon_rumah',
                'nomor_telepon_seluler' => 'nomor_telepon_seluler',
                'nama_ayah' => 'nama_ayah',
                'pekerjaan_ayah_id' => 'pekerjaan_ayah_id',
                'nama_ibu' => 'nama_ibu',
                'pekerjaan_ibu_id' => 'pekerjaan_ibu_id',
                'nama_wali' => 'nama_wali',
                'pekerjaan_wali_id' => 'pekerjaan_wali_id',
                'sekolah_asal' => 'sekolah_asal',
                'rombongan_belajar_id' => 'rombongan_belajar_id',
                'semester_id' => 'semester_id',
                'anggota_rombel_id' => 'anggota_rombel_id',
                'tingkat_pendidikan_id' => 'tingkat_pendidikan_id',
                'nama_rombel' => 'nama_rombel',
                'kurikulum_id' => 'kurikulum_id',
                'kurikulum_id_str' => 'kurikulum_id_str',
                'kebutuhan_khusus' => 'kebutuhan_khusus',
                'kelas_nama' => 'nama_rombel',
                'email' => 'email',
                'telp' => ['nomor_telepon_seluler', 'nomor_telepon_rumah'],
                'anak_ke' => 'anak_keberapa',
                'anak_keberapa' => 'anak_keberapa',
                'tinggi_badan' => 'tinggi_badan',
                'berat_badan' => 'berat_badan',
                'pekerjaan_ayah' => 'pekerjaan_ayah_id_str',
                'pekerjaan_ibu' => 'pekerjaan_ibu_id_str',
                'pekerjaan_wali' => 'pekerjaan_wali_id_str',
                'diterima_dikelas' => 'nama_rombel',
                'diterima_tanggal' => 'tanggal_masuk_sekolah'
              ];

                // Map tingkat pendidikan
                $field_map['tingkat'] = 'tingkat_pendidikan_id';

          foreach ($field_map as $db_field => $sync_field) {
            if (is_array($sync_field)) {
              // only update phone if sync provided at least one non-empty phone
              $hasPhone = !empty($siswa_data['nomor_telepon_seluler']) || !empty($siswa_data['nomor_telepon_rumah']);
              if ($hasPhone) {
                $raw_phone = $siswa_data['nomor_telepon_seluler'] ?: $siswa_data['nomor_telepon_rumah'] ?: '-';
                // Sanitasi nomor telepon: hapus karakter non-digit kecuali +, -, (, ), spasi
                $clean_phone = preg_replace('/[^0-9+\-\(\)\s]/', '', trim($raw_phone));
                // Batasi maksimal 15 karakter untuk safety (standar internasional max 15 digit)
                $newval = substr($clean_phone, 0, 15);
                if (empty($newval) || strlen($newval) == 0) $newval = '-';
                $curval = isset($current['telp']) ? $current['telp'] : '';
                if (trim((string)$newval) !== trim((string)$curval)) {
                  $update_fields[] = "telp=?";
                  $update_values[] = $newval;
                  $diff_found = true;
                }
              }
            } elseif ($db_field === 'jenis_kelamin') {
              // only update gender if sync provided a non-empty gender
              if (isset($siswa_data['jenis_kelamin']) && trim($siswa_data['jenis_kelamin']) !== '') {
                $newjk = ($siswa_data['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';
                $curjk = isset($current['jenis_kelamin']) ? $current['jenis_kelamin'] : '';
                if (trim((string)$newjk) !== trim((string)$curjk)) {
                  $update_fields[] = "jenis_kelamin=?";
                  $update_values[] = $newjk;
                  $diff_found = true;
                }
              }
            } else {
              // only consider sync value when it is provided and non-empty
              if (isset($siswa_data[$sync_field]) && trim((string)$siswa_data[$sync_field]) !== '') {
                $new = $siswa_data[$sync_field];
                
                // Sanitasi panjang untuk field yang berpotensi terlalu panjang
                if (in_array($db_field, ['nama_lengkap', 'tempat_lahir', 'nama_ayah', 'nama_ibu', 'nama_wali', 'sekolah_asal', 'email'])) {
                  $max_lengths = [
                    'nama_lengkap' => 70,   // varchar(70)
                    'tempat_lahir' => 30,   // varchar(30)
                    'nama_ayah'   => 40,    // varchar(40)
                    'nama_ibu'    => 40,    // varchar(40)
                    'nama_wali'   => 100,
                    'sekolah_asal' => 255,
                    'pekerjaan_ayah' => 40, // varchar(40)
                    'pekerjaan_ibu'  => 40, // varchar(40)
                    'pekerjaan_wali' => 100,
                    'kelas_nama'  => 100,
                    'email'       => 50,    // varchar(50)
                    'anak_ke'     => 5,     // varchar(5)
                    'anak_keberapa' => 10
                  ];
                  $max_len = isset($max_lengths[$db_field]) ? $max_lengths[$db_field] : 255;
                  $new = substr(trim($new), 0, $max_len);
                }
                
                $cur = isset($current[$db_field]) ? $current[$db_field] : '';
                if (trim((string)$new) !== trim((string)$cur)) {
                  $update_fields[] = "$db_field=?";
                  $update_values[] = $new;
                  $diff_found = true;
                }
              }
            }
          }

          // Always update rombel sync status, timestamp, dan status menjadi Aktif
          $update_fields[] = "rombel_sync_status=?";
          $update_values[] = 'active';
          $update_fields[] = "rombel_last_sync=CURRENT_TIMESTAMP";
          
          // Set status menjadi Aktif untuk semua siswa yang ada di sync
          $cur_status = isset($current['status']) ? $current['status'] : '';
          if ($cur_status !== 'Aktif') {
            $update_fields[] = "status=?";
            $update_values[] = 'Aktif';
            $diff_found = true;
          }

          // Jangan ubah field non-sync seperti 'konfirmasi' di update; biarkan hanya
          // field yang berasal dari sync_peserta_didik dan mapping yang diupdate.

          // Add mapping fields (jurusan_id, kelas_id, wali_kelas) when available
          if ($mapped_jurusan_id !== null && $has_jurusan_id) {
            $cur_jur = isset($current['jurusan_id']) ? $current['jurusan_id'] : '';
            if (trim((string)$cur_jur) !== trim((string)$mapped_jurusan_id)) {
              $update_fields[] = "jurusan_id=?";
              $update_values[] = $mapped_jurusan_id;
              $diff_found = true;
            }
          }
          if ($mapped_kelas_id !== null && $has_kelas_id) {
            $cur_kelas_id = isset($current['kelas_id']) ? $current['kelas_id'] : '';
            if (trim((string)$cur_kelas_id) !== trim((string)$mapped_kelas_id)) {
              $update_fields[] = "kelas_id=?";
              $update_values[] = $mapped_kelas_id;
              $diff_found = true;
            }
          }
          // also update textual/numeric `kelas` column if present (use mapped kelas_id)
          if ($mapped_kelas_id !== null && $has_kelas_col) {
            $cur_kelas_col = isset($current['kelas']) ? $current['kelas'] : '';
            if (trim((string)$cur_kelas_col) !== trim((string)$mapped_kelas_id)) {
              $update_fields[] = "kelas=?";
              $update_values[] = $mapped_kelas_id;
              $diff_found = true;
            }
          }
          if ($mapped_wali_admin_id !== null && $has_wali_kelas) {
            $cur_wali = isset($current['wali_kelas']) ? $current['wali_kelas'] : '';
            if (trim((string)$cur_wali) !== trim((string)$mapped_wali_admin_id)) {
              $update_fields[] = "wali_kelas=?";
              $update_values[] = $mapped_wali_admin_id;
              $diff_found = true;
            }
          }

              if (count($update_fields) > 0) {
                try {
                  $update_sql = "UPDATE user SET " . implode(',', $update_fields) . " WHERE user_id=?";
                  $update_stmt = $connection->prepare($update_sql);
                  if (!$update_stmt) {
                    $failed++;
                    $failed_msgs[] = "Error preparing update statement for {$siswa_data['nama']}: " . $connection->error;
                    continue;
                  }
                  
                  $update_values[] = $user['user_id'];
                  $bind_types = str_repeat('s', count($update_values) - 1) . 'i';
                  $update_stmt->bind_param($bind_types, ...$update_values);
                  
                  if ($update_stmt->execute()) {
                    if ($diff_found) {
                      $updated++; // count as updated when changes applied
                    } else {
                      // if only rombel fields updated we also consider as updated
                      $updated++;
                    }
                  } else {
                    $failed++;
                    $failed_msgs[] = "Error updating user {$siswa_data['nama']}: " . $update_stmt->error;
                  }
                  $update_stmt->close();
                } catch (Exception $e) {
                  $failed++;
                  $failed_msgs[] = "Exception updating user {$siswa_data['nama']}: " . $e->getMessage();
                }
              }
            } else {
              // Insert data baru jika belum ada
              try {
                // Validasi dan sanitasi nomor telepon (max 15 karakter)
                $raw_telp = $siswa_data['nomor_telepon_seluler'] ?: $siswa_data['nomor_telepon_rumah'] ?: '-';
                // Sanitasi nomor telepon: hapus karakter non-digit kecuali +, -, (, ), spasi
                $clean_telp = preg_replace('/[^0-9+\-\(\)\s]/', '', trim($raw_telp));
                // Batasi maksimal 15 karakter untuk safety (standar internasional max 15 digit)
                $telp = substr($clean_telp, 0, 15);
                if (empty($telp) || strlen($telp) == 0) {
                  $telp = '-';
                }
                
                $password = password_hash('12345', PASSWORD_DEFAULT);
                $jenis_kelamin_formatted = ($siswa_data['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';
                
                // Validasi data wajib untuk insert
                // Validasi duplicate NISN sebelum insert jika NISN tidak kosong
                if (!empty($siswa_data['nisn'])) {
                  $dup_check = $connection->prepare("SELECT user_id FROM user WHERE nisn = ? LIMIT 1");
                  if ($dup_check) {
                    $dup_check->bind_param('s', $siswa_data['nisn']);
                    $dup_check->execute();
                    $dup_result = $dup_check->get_result();
                    if ($dup_result && $dup_result->num_rows > 0) {
                      $failed++;
                      $failed_msgs[] = "Cannot insert {$siswa_data['nama']}: NISN {$siswa_data['nisn']} already exists";
                      $dup_check->close();
                      continue;
                    }
                    $dup_check->close();
                  }
                }
                
                if (!empty($siswa_data['nama'])) {
                  // Helper: return null for empty/null INT or DATE fields (MySQL strict mode compatibility)
                  $nv = function($v) { return ($v !== null && $v !== '') ? $v : null; };

                  $insert_fields = [
                    'registrasi_id',
                    'peserta_didik_id',
                    'jenis_pendaftaran_id',
                    'jenis_pendaftaran_id_str',
                    'tanggal_masuk_sekolah',
                    'nama_lengkap',
                    'nisn',
                    'nipd', 
                    'nik',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'agama_id',
                    'jenis_kelamin',
                    'agama',
                    'nomor_telepon_rumah',
                    'nomor_telepon_seluler',
                    'nama_ayah',
                    'pekerjaan_ayah_id',
                    'nama_ibu',
                    'pekerjaan_ibu_id',
                    'nama_wali',
                    'pekerjaan_wali_id',
                    'sekolah_asal',
                    'rombongan_belajar_id',
                    'semester_id',
                    'anggota_rombel_id',
                    'tingkat_pendidikan_id',
                    'nama_rombel',
                    'kurikulum_id',
                    'kurikulum_id_str',
                    'kebutuhan_khusus',
                    'kelas_nama',
                    'tingkat',
                    'email',
                    'telp',
                    'anak_ke',
                    'anak_keberapa',
                    'tinggi_badan',
                    'berat_badan',
                    'pekerjaan_ayah',
                    'pekerjaan_ibu',
                    'pekerjaan_wali'
                  ];
                
                if ($has_user_avatar) {
                  $insert_fields[] = 'avatar';
                }
                
                $insert_fields = array_merge($insert_fields, [
                  'time',
                  'date',
                  'status',
                  'konfirmasi',
                  'password',
                  'alamat',
                  'rt',
                  'rw',
                  'desa',
                  'kecamatan',
                  'diterima_dikelas',
                  'diterima_tanggal',
                  'rombel_sync_status',
                  'rombel_last_sync'
                ]);
                
                $insert_values = [
                  $siswa_data['registrasi_id'] ?? '',
                  $siswa_data['peserta_didik_id'] ?? '',
                  $siswa_data['jenis_pendaftaran_id'] ?? '',
                  $siswa_data['jenis_pendaftaran_id_str'] ?? '',
                  $nv($siswa_data['tanggal_masuk_sekolah'] ?? null),       // date NULL
                  substr(trim($siswa_data['nama'] ?? ''), 0, 70),
                  $siswa_data['nisn'] ?? '',
                  $siswa_data['nipd'] ?? '',
                  $siswa_data['nik'] ?? '',
                  substr(trim($siswa_data['tempat_lahir'] ?? ''), 0, 30),
                  !empty($siswa_data['tanggal_lahir']) ? $siswa_data['tanggal_lahir'] : '1970-01-01',
                  $nv($siswa_data['agama_id'] ?? null),                    // int NULL
                  $jenis_kelamin_formatted,
                  substr(trim($siswa_data['agama_id_str'] ?? ''), 0, 50),
                  $siswa_data['nomor_telepon_rumah'] ?? '',
                  $siswa_data['nomor_telepon_seluler'] ?? '',
                  substr(trim($siswa_data['nama_ayah'] ?? ''), 0, 40),
                  $nv($siswa_data['pekerjaan_ayah_id'] ?? null),           // int NULL
                  substr(trim($siswa_data['nama_ibu'] ?? ''), 0, 40),
                  $nv($siswa_data['pekerjaan_ibu_id'] ?? null),            // int NULL
                  substr(trim($siswa_data['nama_wali'] ?? ''), 0, 100),
                  $nv($siswa_data['pekerjaan_wali_id'] ?? null),           // int NULL
                  substr(trim($siswa_data['sekolah_asal'] ?? ''), 0, 255),
                  $siswa_data['rombongan_belajar_id'] ?? '',
                  $siswa_data['semester_id'] ?? '',
                  $siswa_data['anggota_rombel_id'] ?? '',
                  $siswa_data['tingkat_pendidikan_id'] ?? '',
                  substr(trim($siswa_data['nama_rombel'] ?? ''), 0, 100),
                  $nv($siswa_data['kurikulum_id'] ?? null),                // int NULL
                  substr(trim($siswa_data['kurikulum_id_str'] ?? ''), 0, 200),
                  substr(trim($siswa_data['kebutuhan_khusus'] ?? ''), 0, 50),
                  substr(trim($siswa_data['kelas_nama'] ?? $siswa_data['nama_rombel'] ?? ''), 0, 100),
                  $siswa_data['tingkat_pendidikan_id'] ?? '',
                  substr(trim($siswa_data['email'] ?? ''), 0, 50),
                  $telp,
                  substr(trim($siswa_data['anak_keberapa'] ?? ''), 0, 5),
                  substr(trim($siswa_data['anak_keberapa'] ?? ''), 0, 10),
                  $siswa_data['tinggi_badan'] ?? '',
                  $siswa_data['berat_badan'] ?? '',
                  substr(trim($siswa_data['pekerjaan_ayah_id_str'] ?? ''), 0, 40),
                  substr(trim($siswa_data['pekerjaan_ibu_id_str'] ?? ''), 0, 40),
                  substr(trim($siswa_data['pekerjaan_wali_id_str'] ?? ''), 0, 100)
                ];
                
                if ($has_user_avatar) {
                  $insert_values[] = $avatar_default;
                }
                
                $insert_values = array_merge($insert_values, [
                  time(),
                  date('Y-m-d'),
                  'Aktif',
                  'Belum Konfirmasi',
                  $password,
                  '',  // alamat
                  '',  // rt
                  '',  // rw
                  '',  // desa
                  '',  // kecamatan
                  substr(trim($siswa_data['nama_rombel'] ?? ''), 0, 50),
                  $nv($siswa_data['tanggal_masuk_sekolah'] ?? null),       // diterima_tanggal date NULL
                  'active',
                  date('Y-m-d'),
                  0    // koordinator NOT NULL
                ]);
                $insert_fields[] = 'koordinator';
                
                // Append mapping fields to insert if columns exist (after basic fields)
                if ($has_jurusan_id) {
                  $insert_fields[] = 'jurusan_id';
                  $insert_values[] = $mapped_jurusan_id !== null ? $mapped_jurusan_id : '';
                }
                if ($has_kelas_id) {
                  $insert_fields[] = 'kelas_id';
                  $insert_values[] = $mapped_kelas_id !== null ? $mapped_kelas_id : '';
                }
                if ($has_kelas_col) {
                  $insert_fields[] = 'kelas';
                  $insert_values[] = $mapped_kelas_id !== null ? $mapped_kelas_id : '10';
                }
                if ($has_wali_kelas) {
                  $insert_fields[] = 'wali_kelas';
                  $insert_values[] = $mapped_wali_admin_id !== null ? $mapped_wali_admin_id : '';
                }
                
                $insert_sql = "INSERT INTO user (" . implode(',', $insert_fields) . ") VALUES (" . rtrim(str_repeat('?,', count($insert_fields)), ',') . ")";
                $insert_stmt = $connection->prepare($insert_sql);
                
                if (!$insert_stmt) {
                  $failed++;
                  $failed_msgs[] = "Error preparing insert statement for {$siswa_data['nama']}: " . $connection->error;
                  continue;
                }
                
                $bind_types = str_repeat('s', count($insert_values));
                $insert_stmt->bind_param($bind_types, ...$insert_values);
                
                if ($insert_stmt->execute()) {
                  $inserted++;
                } else {
                  $failed++;
                  $failed_msgs[] = "Error inserting user {$siswa_data['nama']}: " . $insert_stmt->error;
                }
                $insert_stmt->close();
                
                } else {
                  $failed++;
                  $failed_msgs[] = "Cannot insert student with empty name";
                  continue;
                }
                
              } catch (Exception $e) {
                $failed++;
                $failed_msgs[] = "Exception inserting user {$siswa_data['nama']}: " . $e->getMessage();
              }
            }
            
            if (isset($check_stmt)) $check_stmt->close();
            
          } catch (Exception $e) {
            $failed++;
            $failed_msgs[] = "Error processing student {$siswa_data['nama']}: " . $e->getMessage();
            continue;
          }
        }

        // Close mapping statement
        if (isset($kelas_stmt) && $kelas_stmt) $kelas_stmt->close();
        if (isset($admin_stmt) && $admin_stmt) $admin_stmt->close();

        $msg = "Data Peserta Didik berhasil diproses dari tabel sync. Processed: $processed, Updated: $updated, Inserted: $inserted, Skipped: $skipped, Failed: $failed";
        if ($failed > 0) {
          $preview = array_slice($failed_msgs, 0, 8);
          $preview = array_map(function ($line) {
            return substr((string) $line, 0, 180);
          }, $preview);
          $msg .= "\nDetails: " . implode('; ', $preview);
          if (count($failed_msgs) > count($preview)) {
            $msg .= '; ... +' . (count($failed_msgs) - count($preview)) . ' error lainnya';
          }
        }
        
        // Capture any buffered output (warnings/notices) and append for debugging
        $buffer = trim((string)ob_get_clean());
        if (!empty($buffer)) {
          $msg .= "\nDebugOutput: " . $buffer;
        }
        
        push_sync_progress('getPesertaDidik', $failed > 0 ? 'failed' : 'success', $msg, [
          'scope' => 'table',
          'processed' => $processed,
          'total' => $total_rows,
          'updated' => $updated,
          'inserted' => $inserted,
          'failed' => $failed,
          'skipped' => $skipped
        ]);
        save_sync_log('getPesertaDidik', $failed > 0 ? 'failed' : 'success', $processed, $msg);
        echo json_encode([
          'status' => $failed > 0 ? 'error' : 'success',
          'message' => $msg
        ]);
        
      } catch (Exception $e) {
        $buffer = trim((string)ob_get_clean());
        $error_msg = 'Fatal error in getPesertaDidik: ' . $e->getMessage();
        push_sync_progress('getPesertaDidik', 'failed', $error_msg, [
          'scope' => 'table',
          'processed' => $processed,
          'failed' => $failed
        ]);
        save_sync_log('getPesertaDidik', 'failed', $processed, $error_msg);
        echo json_encode([
          'status' => 'error',
          'message' => $error_msg
        ]);
      }
      exit;

      // Simpan konfigurasi endpoint PKL
    case 'save-pkl-config':
      header('Content-Type: application/json');
      $pkl_base_url = isset($_POST['pkl_base_url']) ? trim($_POST['pkl_base_url']) : '';
      $api_token = isset($_POST['api_token']) ? trim($_POST['api_token']) : '';

      if ($pkl_base_url === '' || $api_token === '') {
        echo json_encode([
          'status' => 'error',
          'message' => 'URL API PKL dan Token API wajib diisi.'
        ]);
        exit;
      }

      $normalized_url = normalize_pkl_endpoint($pkl_base_url);
      $saved = save_pkl_sync_config($normalized_url, $api_token);
      echo json_encode([
        'status' => 'success',
        'message' => 'Konfigurasi PKL berhasil disimpan.',
        'data' => $saved
      ]);
      exit;

      // Test koneksi ke endpoint PKL
    case 'test-pkl-config':
      header('Content-Type: application/json');
      $pkl_base_url = isset($_POST['pkl_base_url']) ? trim($_POST['pkl_base_url']) : '';
      $api_token = isset($_POST['api_token']) ? trim($_POST['api_token']) : '';

      if ($pkl_base_url === '' || $api_token === '') {
        echo json_encode([
          'status' => 'error',
          'message' => 'URL API PKL dan Token API wajib diisi.'
        ]);
        exit;
      }

      $endpoint = normalize_pkl_endpoint($pkl_base_url);
      $result = post_json_with_bearer($endpoint . '?action=ping', $api_token, ['source' => 'sae'], 30);

      if (!empty($result['curl_error'])) {
        echo json_encode([
          'status' => 'error',
          'message' => 'cURL error: ' . $result['curl_error']
        ]);
        exit;
      }

      $decoded = json_decode((string) $result['body'], true);
      if ($result['http_code'] >= 200 && $result['http_code'] < 300 && is_array($decoded) && ($decoded['status'] ?? '') === 'success') {
        echo json_encode([
          'status' => 'success',
          'message' => 'Koneksi ke PKL berhasil. Endpoint valid dan token diterima.'
        ]);
      } else {
        echo json_encode([
          'status' => 'error',
          'message' => 'Koneksi gagal. HTTP ' . $result['http_code'] . ' - ' . substr((string) $result['body'], 0, 300)
        ]);
      }
      exit;

      // Kirim data admin SAE ke PKL
    case 'send-pkl-admin':
      header('Content-Type: application/json');
      $cfg = load_pkl_sync_config();
      $endpoint = normalize_pkl_endpoint($cfg['pkl_base_url']);
      $token = trim((string) $cfg['api_token']);

      if ($endpoint === '' || $token === '') {
        echo json_encode([
          'status' => 'error',
          'message' => 'Konfigurasi PKL belum lengkap. Simpan URL dan token terlebih dahulu.'
        ]);
        exit;
      }

      $records = [];
      // Tabel admin SAE tidak memiliki kolom nip; gunakan ptk_id sebagai fallback identitas NIP/NUPTK
      $q_admin = "SELECT admin_id, fullname, username, email, phone, password, avatar, active, level_id,
                         COALESCE(NULLIF(ptk_id, ''), username) AS nip
                  FROM admin
                  WHERE active='Y'";
      $r_admin = $connection->query($q_admin);
      if (!$r_admin) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Query data admin SAE gagal: ' . $connection->error
        ]);
        exit;
      }
      while ($row = $r_admin->fetch_assoc()) {
        $records[] = [
          'admin_id' => (int) $row['admin_id'],
          'fullname' => (string) ($row['fullname'] ?? ''),
          'username' => (string) ($row['username'] ?? ''),
          'email' => (string) ($row['email'] ?? ''),
          'phone' => (string) ($row['phone'] ?? ''),
          'password' => (string) ($row['password'] ?? ''),
          'avatar' => (string) ($row['avatar'] ?? ''),
          'avatar_url' => build_sae_public_avatar_url($row['avatar'] ?? ''),
          'active' => (string) ($row['active'] ?? 'Y'),
          'level_id' => (int) ($row['level_id'] ?? 3),
          'nip' => (string) ($row['nip'] ?? '')
        ];
      }

      if (empty($records)) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Data admin aktif di SAE tidak ditemukan untuk dikirim.'
        ]);
        exit;
      }

      $payload = [
        'source' => 'sae',
        'type' => 'admin',
        'sent_at' => date('c'),
        'records' => $records
      ];
      $result = post_json_with_bearer($endpoint . '?action=receive', $token, $payload, 120);

      if (!empty($result['curl_error'])) {
        echo json_encode(['status' => 'error', 'message' => 'cURL error: ' . $result['curl_error']]);
        exit;
      }

      $decoded = json_decode((string) $result['body'], true);
      if ($result['http_code'] >= 200 && $result['http_code'] < 300 && is_array($decoded) && ($decoded['status'] ?? '') === 'success') {
        echo json_encode([
          'status' => 'success',
          'message' => 'Data admin berhasil dikirim. ' . ($decoded['message'] ?? ''),
          'summary' => $decoded['summary'] ?? null
        ]);
      } else {
        echo json_encode([
          'status' => 'error',
          'message' => 'Gagal kirim data admin. HTTP ' . $result['http_code'] . ' - ' . substr((string) $result['body'], 0, 300)
        ]);
      }
      exit;

      // Kirim data user SAE tingkat 12 ke PKL
    case 'send-pkl-user12':
      header('Content-Type: application/json');
      $cfg = load_pkl_sync_config();
      $endpoint = normalize_pkl_endpoint($cfg['pkl_base_url']);
      $token = trim((string) $cfg['api_token']);

      if ($endpoint === '' || $token === '') {
        echo json_encode([
          'status' => 'error',
          'message' => 'Konfigurasi PKL belum lengkap. Simpan URL dan token terlebih dahulu.'
        ]);
        exit;
      }

      $records = [];
      $q_user = "SELECT u.user_id, u.nisn, u.email, u.password, u.nama_lengkap, u.tempat_lahir, u.tanggal_lahir, u.jenis_kelamin,
                        u.tingkat, u.kelas, u.kelas_nama, u.telp, u.avatar, u.alamat, u.status, u.wali_kelas, u.koordinator,
                        k.nama_kelas, k.tingkat_pendidikan_id, k.tingkat_pendidikan_str
                 FROM user u
                 LEFT JOIN kelas k ON u.kelas = k.kelas_id
                 WHERE LOWER(TRIM(u.status)) = 'aktif'
                   AND (
                     u.tingkat = '12'
                     OR k.tingkat_pendidikan_id = '12'
                     OR UPPER(COALESCE(u.kelas_nama, k.nama_kelas, '')) LIKE 'XII%'
                   )";
      $r_user = $connection->query($q_user);
      if (!$r_user) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Query data user tingkat 12 SAE gagal: ' . $connection->error
        ]);
        exit;
      }
      while ($row = $r_user->fetch_assoc()) {
        $records[] = [
          'user_id' => (int) ($row['user_id'] ?? 0),
          'nisn' => (string) ($row['nisn'] ?? ''),
          'email' => (string) ($row['email'] ?? ''),
          'password' => (string) ($row['password'] ?? ''),
          'nama_lengkap' => (string) ($row['nama_lengkap'] ?? ''),
          'tempat_lahir' => (string) ($row['tempat_lahir'] ?? ''),
          'tanggal_lahir' => (string) ($row['tanggal_lahir'] ?? ''),
          'jenis_kelamin' => (string) ($row['jenis_kelamin'] ?? ''),
          'tingkat' => (string) ($row['tingkat'] ?? ''),
          'kelas' => (string) ($row['kelas'] ?? ''),
          'kelas_nama' => (string) (($row['kelas_nama'] ?? '') ?: ($row['nama_kelas'] ?? '')),
          'tingkat_pendidikan_id' => (string) ($row['tingkat_pendidikan_id'] ?? ''),
          'tingkat_pendidikan_str' => (string) ($row['tingkat_pendidikan_str'] ?? ''),
          'telp' => (string) ($row['telp'] ?? ''),
          'avatar' => (string) ($row['avatar'] ?? ''),
          'avatar_url' => build_sae_public_avatar_url($row['avatar'] ?? ''),
          'alamat' => (string) ($row['alamat'] ?? ''),
          'status' => (string) ($row['status'] ?? ''),
          'wali_kelas' => (int) ($row['wali_kelas'] ?? 0),
          'koordinator' => (int) ($row['koordinator'] ?? 0)
        ];
      }

      if (empty($records)) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Data user tingkat 12 aktif di SAE tidak ditemukan untuk dikirim.'
        ]);
        exit;
      }

      $payload = [
        'source' => 'sae',
        'type' => 'user12',
        'sent_at' => date('c'),
        'records' => $records
      ];
      $result = post_json_with_bearer($endpoint . '?action=receive', $token, $payload, 180);

      if (!empty($result['curl_error'])) {
        echo json_encode(['status' => 'error', 'message' => 'cURL error: ' . $result['curl_error']]);
        exit;
      }

      $decoded = json_decode((string) $result['body'], true);
      if ($result['http_code'] >= 200 && $result['http_code'] < 300 && is_array($decoded) && ($decoded['status'] ?? '') === 'success') {
        echo json_encode([
          'status' => 'success',
          'message' => 'Data user tingkat 12 berhasil dikirim. ' . ($decoded['message'] ?? ''),
          'summary' => $decoded['summary'] ?? null
        ]);
      } else {
        echo json_encode([
          'status' => 'error',
          'message' => 'Gagal kirim data user tingkat 12. HTTP ' . $result['http_code'] . ' - ' . substr((string) $result['body'], 0, 300)
        ]);
      }
      exit;

      // Handler untuk generate API key baru
    case 'generate-api-key':
      // Generate server-side untuk keamanan maksimal
      $timestamp = date('YmdHis');
      $random_bytes = random_bytes(16);
      $random_hex = bin2hex($random_bytes);
      $new_api_key = "SAE_{$timestamp}_{$random_hex}";

      // Validasi panjang dan format
      if (strlen($new_api_key) < 32) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Failed to generate secure API key'
        ]);
        break;
      }

      // Simpan ke file konfigurasi dengan permission yang aman
      $config_file = '../../../api/api_config.php';
      $config_content = "<?php\n// SAE API Configuration - Generated: " . date('Y-m-d H:i:s') . "\n";
      $config_content .= "// WARNING: Keep this file secure and never commit to version control\n";
      $config_content .= "define('SAE_API_KEY', '$new_api_key');\n";
      $config_content .= "?>";

      // Attempt to write with secure permissions
      if (file_put_contents($config_file, $config_content, LOCK_EX)) {
        // Set secure file permissions (read only for owner)
        if (function_exists('chmod')) {
          chmod($config_file, 0600);
        }

        // Log API key generation for security audit
        if ($connection) {
          $stmt = $connection->prepare("INSERT INTO security_log (event_type, ip_address, user_agent, details, created_at) VALUES ('api_key_generated', ?, ?, ?, NOW())");
          $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
          $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
          $details = json_encode(['key_prefix' => substr($new_api_key, 0, 15) . '...']);
          $stmt->bind_param("sss", $ip, $user_agent, $details);
          $stmt->execute();
          $stmt->close();
        }

        echo json_encode([
          'status' => 'success',
          'message' => 'API Key baru berhasil dibuat dan disimpan dengan aman',
          'api_key' => $new_api_key,
          'security_note' => 'Key telah disimpan dengan permission aman'
        ]);
      } else {
        echo json_encode([
          'status' => 'error',
          'message' => 'Gagal menyimpan API Key. Periksa permission direktori.',
          'api_key' => $new_api_key,
          'manual_save' => 'Simpan key ini secara manual jika diperlukan'
        ]);
      }
      break;
  }
}
