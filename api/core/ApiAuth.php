<?php
/**
 * SAE v4 API - Authentication Class
 * 
 * Kelas untuk menangani autentikasi API
 */

class ApiAuth {
    
    /**
     * Validate API key from request headers
     */
    public static function validateApiKey() {
        $headers = getallheaders();
        
        // Check X-API-Key header first
        $api_key = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
        
        if (!empty($api_key)) {
            return $api_key === SAE_API_KEY;
        }
        
        // Fallback to Authorization Bearer token
        $auth_header = $headers['Authorization'] ?? '';
        
        if (empty($auth_header) || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return false;
        }
        
        $provided_key = $matches[1];
        return $provided_key === SAE_API_KEY;
    }
    
    /**
     * Get API key from headers
     */
    public static function getApiKey() {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Check if request is authorized
     */
    public static function authorize() {
        if (!self::validateApiKey()) {
            ApiResponse::send(ApiResponse::unauthorized('Invalid API key'));
        }
        return true;
    }
    
    /**
     * Get client info for logging
     */
    public static function getClientInfo() {
        return [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ];
    }
}