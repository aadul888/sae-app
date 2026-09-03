<?php

require_once __DIR__ . '/../library/wilayah_indonesia.php';

header('Content-Type: application/json; charset=utf-8');

$levelMap = [
    'province' => 'provinces',
    'provinces' => 'provinces',
    'regency' => 'regencies',
    'regencies' => 'regencies',
    'district' => 'districts',
    'districts' => 'districts',
    'village' => 'villages',
    'villages' => 'villages',
];

$requestedLevel = strtolower(trim((string)($_GET['level'] ?? 'provinces')));
$parentId = trim((string)($_GET['parent_id'] ?? ''));
$level = $levelMap[$requestedLevel] ?? null;

if ($level === null) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Level wilayah tidak dikenal.',
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = sae_get_wilayah_reference($level, $parentId);
if (!$result['success']) {
    http_response_code(502);
    echo json_encode([
        'status' => 'error',
        'message' => 'Referensi wilayah belum tersedia. Pastikan server dapat mengakses sumber data wilayah Indonesia.',
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status' => 'success',
    'source' => $result['source'],
    'data' => $result['data'],
], JSON_UNESCAPED_UNICODE);