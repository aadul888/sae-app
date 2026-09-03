<?php
// Output buffering untuk mencegah "Headers already sent" error (ob_start)
if (ob_get_level() === 0) ob_start();
require_once __DIR__ . '/../library/config.php';
require_once __DIR__ . '/core/ApiResponse.php';
require_once __DIR__ . '/core/ApiAuth.php';
require_once __DIR__ . '/core/ApiLogger.php';
require_once __DIR__ . '/core/DataProcessor.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!defined('API_LOG_REQUESTS')) {
    define('API_LOG_REQUESTS', false);
}

$current_key = ApiAuth::getCurrentApiKey();
error_log("DEBUG receive-data: current_key='{$current_key}' (len=" . strlen($current_key) . "), SAE_API_KEY=" . (defined('SAE_API_KEY') ? SAE_API_KEY : 'UNDEFINED'));

if ($current_key === '') {
    ApiResponse::send(ApiResponse::unauthorized('SAE API key belum dikonfigurasi. Silakan set API key di Konfigurasi Server.'));
}

ApiAuth::authorize();

$endpoint = trim((string) ($_GET['endpoint'] ?? $_GET['action'] ?? $_GET['type'] ?? ''));
if ($endpoint === 'receive') {
    $endpoint = trim((string) ($_GET['source_action'] ?? $_GET['data_type'] ?? $_GET['type'] ?? ''));
}

$raw = file_get_contents('php://input');
$payload = [];
if ($raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (!$payload && isset($_GET['payload'])) {
    $decoded = json_decode((string) $_GET['payload'], true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (!$payload && isset($_GET['data'])) {
    $decoded = json_decode((string) $_GET['data'], true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (!$payload) {
    $payload = $_POST ?: $_GET;
}

if ($endpoint === '' && isset($payload['endpoint'])) {
    $endpoint = trim((string) $payload['endpoint']);
}
if ($endpoint === '' && isset($payload['action'])) {
    $endpoint = trim((string) $payload['action']);
}
if ($endpoint === '' && isset($payload['type'])) {
    $endpoint = trim((string) $payload['type']);
}

$data = $payload['data'] ?? $payload['records'] ?? $payload;
if (isset($payload['records']) && !isset($payload['data'])) {
    $data = $payload['records'];
}

if ($endpoint === '') {
    ApiResponse::send(ApiResponse::validationError(['endpoint' => 'Endpoint/action/type wajib diisi.']));
}

try {
    $processor = new DataProcessor();
    $result = $processor->processData($endpoint, $data);

    if (!empty($result['success'])) {
        ApiResponse::send([
            'status' => 'success',
            'success' => true,
            'message' => $result['message'] ?? 'Data diterima.',
            'data' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    ApiResponse::send([
        'status' => 'error',
        'success' => false,
        'message' => $result['message'] ?? 'Data gagal diproses.',
        'data' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Throwable $e) {
    ApiLogger::logError('receive-data', $e->getMessage());
    ApiResponse::send(ApiResponse::error('API gagal memproses data: ' . $e->getMessage(), null, 500));
}

