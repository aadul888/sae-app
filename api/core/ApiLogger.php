<?php
/**
 * SAE v4 API - Logger Class
 * 
 * Kelas untuk logging aktivitas API
 */

class ApiLogger {
    
    const LOG_FILE = __DIR__ . '/../../api/API_LOG_FILE';
    
    /**
     * Log API request
     */
    public static function logRequest($endpoint, $status, $message = '', $additional_data = []) {
        if (!defined('API_LOG_REQUESTS') || !API_LOG_REQUESTS) {
            return;
        }
        
        $client_info = ApiAuth::getClientInfo();
        
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $client_info['ip'],
            'method' => $client_info['method'],
            'endpoint' => $endpoint,
            'status' => $status,
            'message' => $message,
            'user_agent' => $client_info['user_agent']
        ];
        
        // Add additional data if provided
        if (!empty($additional_data)) {
            $log_entry = array_merge($log_entry, $additional_data);
        }
        
        self::writeLog($log_entry);
    }
    
    /**
     * Log sync activity
     */
    public static function logSyncActivity($endpoint, $status, $total_records, $message, $stats = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'sync_activity',
            'endpoint' => $endpoint,
            'status' => $status,
            'total_records' => $total_records,
            'message' => $message
        ];
        
        if (!empty($stats)) {
            $log_entry['stats'] = $stats;
        }
        
        self::writeLog($log_entry);
    }
    
    /**
     * Log integration activity
     */
    public static function logIntegration($integrator_name, $status, $stats, $message = '') {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'integration',
            'integrator' => $integrator_name,
            'status' => $status,
            'message' => $message,
            'stats' => $stats
        ];
        
        self::writeLog($log_entry);
    }
    
    /**
     * Log error
     */
    public static function logError($context, $error_message, $additional_data = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'error',
            'context' => $context,
            'error' => $error_message
        ];
        
        if (!empty($additional_data)) {
            $log_entry = array_merge($log_entry, $additional_data);
        }
        
        self::writeLog($log_entry);
        
        // Also log to PHP error log for critical errors
        error_log("SAE API Error [$context]: $error_message");
    }
    
    /**
     * Write log entry to file
     */
    private static function writeLog($log_entry) {
        $log_line = json_encode($log_entry) . PHP_EOL;
        
        // Buat direktori jika belum ada
        $log_dir = dirname(self::LOG_FILE);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents(self::LOG_FILE, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get recent log entries
     */
    public static function getRecentLogs($limit = 50, $type = null) {
        if (!file_exists(self::LOG_FILE)) {
            return [];
        }
        
        $lines = file(self::LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        
        // Get last N lines
        $recent_lines = array_slice($lines, -$limit);
        
        foreach ($recent_lines as $line) {
            $log_entry = json_decode($line, true);
            if ($log_entry && ($type === null || ($log_entry['type'] ?? 'request') === $type)) {
                $logs[] = $log_entry;
            }
        }
        
        return array_reverse($logs); // Most recent first
    }
}