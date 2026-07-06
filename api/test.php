<?php
/**
 * Test API endpoint availability
 */

header('Content-Type: application/json');

// Basic test response
$test_data = [
    'status' => 'success',
    'message' => 'SAE API endpoint is accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
    ],
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown', 
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'query_string' => $_SERVER['QUERY_STRING'] ?? ''
    ]
];

echo json_encode($test_data, JSON_PRETTY_PRINT);
?>