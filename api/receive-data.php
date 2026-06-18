<?php

/**
 * SAE v5 API - Simplified Main Endpoint
 * 
 * API sederhana untuk menerima dan memproses data tanpa integrasi kompleks
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Include required files
    require_once __DIR__ . '/../library/config.php';
    require_once __DIR__ . '/../library/function.php';
    require_once __DIR__ . '/api_config.php';
    require_once __DIR__ . '/core/ApiResponse.php';
    require_once __DIR__ . '/core/ApiAuth.php';
    require_once __DIR__ . '/core/ApiLogger.php';
    require_once __DIR__ . '/core/ApiValidator.php';
    require_once __DIR__ . '/core/DataProcessor.php';

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed. Use POST.');
    }

    // Get raw input
    $raw_input = file_get_contents('php://input');
    if (empty($raw_input)) {
        throw new Exception('Empty request body');
    }

    // Parse JSON input
    $input = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }

    // Sanitize input
    $input = ApiValidator::sanitizeInput($input);

    $endpoint = $input['endpoint'] ?? '';
    $data = $input['data'] ?? [];

    // Lightweight authenticated check - useful for external health/auth checks
    if ($endpoint === 'auth_check') {
        if (!ApiAuth::validateApiKey()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid API key']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'API Key valid', 'timestamp' => date('Y-m-d H:i:s')]);
        exit;
    }

    // Handle ping request (no authentication required)
    if ($endpoint === 'ping') {
        $response = [
            'success' => true,
            'message' => 'SAE API v5.0 - Simplified',
            'data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '5.0-simplified'
            ]
        ];
        echo json_encode($response);
        exit;
    }

    // Validate API key for other endpoints
    if (!ApiAuth::validateApiKey()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid API key'
        ]);
        exit;
    }

    // Process data
    $processor = new DataProcessor();
    $result = $processor->processData($endpoint, $data);

    // Prepare response
    $response = [
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => [
            'endpoint' => $endpoint,
            'stats' => $result['stats'] ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    // Set appropriate HTTP status code
    http_response_code($result['success'] ? 200 : 400);

    echo json_encode($response);
} catch (Exception $e) {
    // Log error
    ApiLogger::logError('receive-data.php', $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'API Error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
