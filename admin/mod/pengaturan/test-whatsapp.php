<?php
/**
 * WhatsApp Gateway Test & Debug Tool
 * Untuk testing konfigurasi WhatsApp Gateway di localhost
 */

// Include konfigurasi
require_once '../../../library/config.php';
require_once '../../../library/whatsapp-gateway.php';

// Set content type
header('Content-Type: application/json');

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Ambil input
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? 'Test pesan dari sistem WhatsApp Gateway SAEV4 - OneSender';
    
    if (empty($phone)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Nomor telepon harus diisi'
        ]);
        exit;
    }
    
    // Format nomor telepon Indonesia
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    } elseif (substr($phone, 0, 2) != '62') {
        $phone = '62' . $phone;
    }
    
    // Inisialisasi WhatsApp Gateway
    $wa = new WhatsAppGateway($connection);
    
    // Cek apakah gateway aktif
    if (!$wa->isActive()) {
        echo json_encode([
            'status' => 'warning',
            'message' => 'WhatsApp Gateway tidak aktif. Menggunakan mode simulasi.',
            'details' => [
                'phone' => $phone,
                'message' => $message,
                'mode' => 'simulation',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        exit;
    }
    
    // Test dengan format OneAPI WhatsApp Business API yang sudah terbukti berhasil
    $testFormats = [
        // Format 1: WhatsApp Business API Style (BERHASIL!)
        [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ],
        // Format 2: Simple text (fallback)
        [
            'to' => $phone,
            'text' => $message
        ],
        // Format 3: Body parameter (fallback)
        [
            'to' => $phone,
            'body' => $message
        ]
    ];
    
    // Ambil konfigurasi
    $config_result = $connection->query("SELECT * FROM whatsapp_config WHERE id = 1");
    $config = $config_result->fetch_assoc();
    
    $success_result = null;
    $last_error = '';
    
    foreach ($testFormats as $index => $postData) {
        // Test setiap format dengan header authorization yang tepat
        $curl = curl_init();
        
        // Deteksi format authorization header
        $authHeader = 'Authorization: ' . $config['api_key'];
        if (strpos($config['api_url'], 'oneapi.my.id') !== false) {
            $authHeader = 'Authorization: Bearer ' . $config['api_key'];
        }
        
        // Semua format menggunakan JSON untuk OneAPI WhatsApp Business API
        $postFields = json_encode($postData);
        $contentType = 'Content-Type: application/json';
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $config['api_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => array(
                $authHeader,
                $contentType
            ),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        // Log untuk debug
        error_log("Format $index - HTTP: $http_code, Response: $response");
        
        if ($http_code == 200 && !$error) {
            $responseData = json_decode($response, true);
            // Cek response OneAPI WhatsApp Business API
            if ($responseData && isset($responseData['code']) && $responseData['code'] == 200) {
                $success_result = [
                    'format_used' => $index + 1,
                    'format_data' => $postData,
                    'response' => $responseData,
                    'http_code' => $http_code
                ];
                break;
            }
        }
        
        $last_error = $error ?: "HTTP $http_code: $response";
    }
    
    if ($success_result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Pesan test berhasil dikirim!',
            'details' => [
                'phone' => $phone,
                'message' => $message,
                'format_used' => $success_result['format_used'],
                'api_response' => $success_result['response'],
                'mode' => 'live',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal mengirim pesan: ' . $last_error,
            'details' => [
                'phone' => $phone,
                'message' => $message,
                'last_error' => $last_error,
                'tested_formats' => count($testFormats),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage(),
        'details' => [
            'error_type' => get_class($e),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>