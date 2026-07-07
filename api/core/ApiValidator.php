<?php
/**
 * SAE v4 API - Validator Class
 * 
 * Kelas untuk validasi data API
 */

class ApiValidator {
    
    /**
     * Validate required fields
     */
    public static function validateRequired($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "Field '$field' is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate endpoint format
     */
    public static function validateEndpoint($endpoint) {
        $valid_endpoints = [
            'ping',
            'getSekolah', 'sekolah',
            'getPtk', 'ptk',
            'getPesertaDidik', 'peserta_didik',
            'getGtk', 'gtk',
            'getPengguna', 'pengguna',
            'getRombonganBelajar', 'rombongan_belajar',
            'runIntegration'
        ];
        
        return in_array($endpoint, $valid_endpoints);
    }
    
    /**
     * Validate data structure for specific endpoint
     */
    public static function validateEndpointData($endpoint, $data) {
        $errors = [];
        
        switch ($endpoint) {
            case 'getSekolah':
            case 'sekolah':
                $required = ['sekolah_id', 'nama', 'npsn'];
                $errors = self::validateRequired($data, $required);
                break;
                
            case 'getPesertaDidik':
            case 'peserta_didik':
                if (is_array($data) && !empty($data)) {
                    foreach ($data as $index => $siswa) {
                        $required = ['peserta_didik_id', 'nama', 'nisn'];
                        $item_errors = self::validateRequired($siswa, $required);
                        if (!empty($item_errors)) {
                            $errors["item_$index"] = $item_errors;
                        }
                    }
                }
                break;
                
            case 'getPtk':
            case 'ptk':
                if (is_array($data) && !empty($data)) {
                    foreach ($data as $index => $ptk) {
                        $required = ['ptk_id', 'nama'];
                        $item_errors = self::validateRequired($ptk, $required);
                        if (!empty($item_errors)) {
                            $errors["item_$index"] = $item_errors;
                        }
                    }
                }
                break;
                
            case 'getGtk':
            case 'gtk':
                if (is_array($data) && !empty($data)) {
                    foreach ($data as $index => $gtk) {
                        $required = ['ptk_id', 'nama'];
                        $item_errors = self::validateRequired($gtk, $required);
                        if (!empty($item_errors)) {
                            $errors["item_$index"] = $item_errors;
                        }
                    }
                }
                break;
                
            case 'getPengguna':
            case 'pengguna':
                if (is_array($data) && !empty($data)) {
                    foreach ($data as $index => $pengguna) {
                        $required = ['pengguna_id', 'username', 'nama'];
                        $item_errors = self::validateRequired($pengguna, $required);
                        if (!empty($item_errors)) {
                            $errors["item_$index"] = $item_errors;
                        }
                    }
                }
                break;
                
            case 'getRombonganBelajar':
            case 'rombongan_belajar':
                if (is_array($data) && !empty($data)) {
                    foreach ($data as $index => $rombel) {
                        $required = ['rombongan_belajar_id', 'nama'];
                        $item_errors = self::validateRequired($rombel, $required);
                        if (!empty($item_errors)) {
                            $errors["item_$index"] = $item_errors;
                        }
                    }
                }
                break;
                
            case 'runIntegration':
                // Validasi minimal untuk runIntegration
                if (!isset($data['integration'])) {
                    $errors['integration'] = "Field 'integration' is required";
                } else {
                    $valid_integrations = ['all', 'admin', 'rombel_kelas', 'wali_kelas', 'anggota_rombel', 'user_rombel'];
                    if (!in_array($data['integration'], $valid_integrations)) {
                        $errors['integration'] = "Invalid integration type. Valid: " . implode(', ', $valid_integrations);
                    }
                }
                break;
        }
        
        return $errors;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        if (is_string($data)) {
            return trim($data);
        }
        
        return $data;
    }
    
    /**
     * Validate request structure
     */
    public static function validateRequest($request_data) {
        $required_fields = ['endpoint', 'data'];
        $errors = self::validateRequired($request_data, $required_fields);
        
        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors
            ];
        }
        
        // Validate endpoint
        if (!self::validateEndpoint($request_data['endpoint'])) {
            return [
                'valid' => false,
                'errors' => ['endpoint' => 'Invalid endpoint']
            ];
        }
        
        // Validate data structure for endpoint
        $data_errors = self::validateEndpointData($request_data['endpoint'], $request_data['data']);
        if (!empty($data_errors)) {
            return [
                'valid' => false,
                'errors' => ['data' => $data_errors]
            ];
        }
        
        return [
            'valid' => true,
            'errors' => []
        ];
    }
}