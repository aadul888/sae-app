<?php
/**
 * SAE v4 API - Authentication Class
 * 
 * Kelas untuk menangani autentikasi API
 */

class ApiAuth {
    
    /**
     * Get all HTTP headers with fallback for nginx/CGI/Apache
     * where getallheaders() may not return Authorization header.
     */
    public static function getAllHeadersSafe(): array {
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];

        // Fallback: pull from $_SERVER for keys that getallheaders() missed
        $server_map = [
            'HTTP_AUTHORIZATION'          => 'Authorization',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Authorization',
            'HTTP_X_API_KEY'             => 'X-API-Key',
            'HTTP_X_AUTHORIZATION'       => 'Authorization',
        ];
        foreach ($server_map as $server_key => $header_name) {
            if (empty($headers[$header_name]) && !empty($_SERVER[$server_key])) {
                $headers[$header_name] = $_SERVER[$server_key];
            }
        }

        return $headers;
    }

    /**
     * Ambil api_key terkini langsung dari tabel setting (bukan konstanta SAE_API_KEY
     * yang bisa basi jika config.php di hosting belum ter-update oleh deploy).
     */
    public static function getCurrentApiKey(): string {
        global $connection;
        if (isset($connection) && $connection instanceof mysqli && !$connection->connect_error) {
            $res = $connection->query("SELECT api_key FROM setting WHERE site_id = 1 LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                return (string) ($row['api_key'] ?? '');
            }
        }

        return defined('SAE_API_KEY') ? (string) SAE_API_KEY : '';
    }

    /**
     * Validate API key from request headers
     */
    public static function validateApiKey() {
        $headers = self::getAllHeadersSafe();
        $current_key = self::getCurrentApiKey();

        if ($current_key === '') {
            return false;
        }

        // Check X-API-Key header first
        $api_key = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';

        if (!empty($api_key)) {
            return hash_equals($current_key, $api_key);
        }

        // Fallback to Authorization Bearer token
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (empty($auth_header) || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return false;
        }

        $provided_key = $matches[1];
        return hash_equals($current_key, $provided_key);
    }
    
    /**
     * Get API key from headers
     */
    public static function getApiKey() {
        $headers = self::getAllHeadersSafe();
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        
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
