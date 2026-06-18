<?php
/**
 * SAE v4 API - Core Response Class
 * 
 * Kelas untuk standarisasi response API
 */

class ApiResponse {
    
    /**
     * Success response
     */
    public static function success($data = null, $message = 'Success', $meta = []) {
        return self::response(true, $message, $data, $meta);
    }
    
    /**
     * Error response
     */
    public static function error($message = 'Error', $data = null, $code = 500, $meta = []) {
        http_response_code($code);
        return self::response(false, $message, $data, $meta);
    }
    
    /**
     * Validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        return self::error($message, ['errors' => $errors], 400);
    }
    
    /**
     * Unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, null, 401);
    }
    
    /**
     * Not found response
     */
    public static function notFound($message = 'Not found') {
        return self::error($message, null, 404);
    }
    
    /**
     * Base response structure
     */
    private static function response($success, $message, $data = null, $meta = []) {
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        return $response;
    }
    
    /**
     * Send JSON response and exit
     */
    public static function send($response) {
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}