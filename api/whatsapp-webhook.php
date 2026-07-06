<?php
/**
 * WhatsApp Webhook Handler
 * Untuk menerima pesan masuk dari WhatsApp Gateway
 * 
 * @author System  
 * @version 1.0
 * @date 2026-02-02
 */

// Include konfigurasi dan library
require_once '../library/config.php';
require_once '../library/whatsapp-gateway.php';

// Set content type untuk JSON response
header('Content-Type: application/json');

// Function untuk log webhook
function logWebhook($data, $status = 'received') {
    $log_file = '../api/logs/whatsapp_webhook_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$status] " . json_encode($data) . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

try {
    // Validasi method request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array(
            'success' => false,
            'message' => 'Method not allowed'
        ));
        exit;
    }
    
    // Ambil raw input data
    $raw_input = file_get_contents('php://input');
    $webhook_data = json_decode($raw_input, true);
    
    // Log webhook untuk debugging
    logWebhook($webhook_data);
    
    // Validasi data
    if (empty($webhook_data)) {
        throw new Exception('No data received');
    }
    
    // Validasi required fields
    $required_fields = ['phone', 'message'];
    foreach ($required_fields as $field) {
        if (!isset($webhook_data[$field]) || empty($webhook_data[$field])) {
            throw new Exception("Required field '$field' is missing");
        }
    }
    
    // Initialize WhatsApp Gateway
    $wa_gateway = new WhatsAppGateway($connection);
    
    // Handle webhook
    $result = $wa_gateway->handleWebhook($webhook_data);
    
    if ($result) {
        // Log success
        logWebhook($webhook_data, 'processed');
        
        // Response success
        http_response_code(200);
        echo json_encode(array(
            'success' => true,
            'message' => 'Webhook processed successfully'
        ));
    } else {
        throw new Exception('Failed to process webhook');
    }
    
} catch (Exception $e) {
    // Log error
    logWebhook(array(
        'error' => $e->getMessage(),
        'raw_input' => $raw_input ?? null
    ), 'error');
    
    // Response error
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}

// Function untuk validasi signature (jika diperlukan)
function validateSignature($payload, $signature, $secret) {
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected_signature, $signature);
}

// Function untuk handle different webhook events
function handleWebhookEvent($event_type, $data) {
    switch ($event_type) {
        case 'message':
            // Handle incoming message
            return handleIncomingMessage($data);
            
        case 'status':
            // Handle message status update (delivered, read, etc.)
            return handleStatusUpdate($data);
            
        case 'webhook_test':
            // Handle webhook test
            return array('success' => true, 'message' => 'Webhook test successful');
            
        default:
            return array('success' => false, 'message' => 'Unknown event type');
    }
}

function handleIncomingMessage($data) {
    global $connection;
    
    // Extract message data
    $phone = isset($data['phone']) ? $data['phone'] : '';
    $message = isset($data['message']) ? $data['message'] : '';
    $message_type = isset($data['type']) ? $data['type'] : 'text';
    $timestamp = isset($data['timestamp']) ? $data['timestamp'] : time();
    
    // Format phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Check for commands or auto-responses
    $response_message = null;
    
    // Handle menu commands
    $message_lower = strtolower(trim($message));
    switch ($message_lower) {
        case 'menu':
        case 'help':
            $response_message = "🤖 *Menu Otomatis*\n\n";
            $response_message .= "Ketik:\n";
            $response_message .= "• *info* - Informasi sekolah\n";
            $response_message .= "• *jadwal* - Jadwal pelajaran\n";
            $response_message .= "• *pengumuman* - Pengumuman terbaru\n";
            $response_message .= "• *kontak* - Informasi kontak\n\n";
            $response_message .= "Atau kirim pesan Anda, kami akan segera membalas.";
            break;
            
        case 'info':
            // Get school info from database
            $result = $connection->query("SELECT site_name, site_address, site_phone FROM setting WHERE site_id = 1");
            if ($result && $row = $result->fetch_assoc()) {
                $response_message = "ℹ️ *Informasi Sekolah*\n\n";
                $response_message .= "🏫 " . $row['site_name'] . "\n";
                $response_message .= "📍 " . $row['site_address'] . "\n";  
                $response_message .= "📞 " . $row['site_phone'];
            }
            break;
            
        case 'kontak':
            $result = $connection->query("SELECT site_phone, site_email FROM setting WHERE site_id = 1");
            if ($result && $row = $result->fetch_assoc()) {
                $response_message = "📞 *Kontak Kami*\n\n";
                $response_message .= "Telepon: " . $row['site_phone'] . "\n";
                $response_message .= "Email: " . $row['site_email'] . "\n\n";
                $response_message .= "Jam Operasional:\n";
                $response_message .= "Senin - Jumat: 07:00 - 16:00\n";
                $response_message .= "Sabtu: 07:00 - 12:00";
            }
            break;
            
        case 'pengumuman':
            $response_message = "📢 *Pengumuman Terbaru*\n\n";
            $response_message .= "Untuk melihat pengumuman terbaru, silakan kunjungi website resmi sekolah atau hubungi admin.\n\n";
            $response_message .= "Terima kasih.";
            break;
            
        default:
            // Use default auto reply if configured
            $wa_config_result = $connection->query("SELECT auto_reply_message FROM whatsapp_config WHERE id = 1 AND auto_reply = 'Y'");
            if ($wa_config_result && $wa_config_result->num_rows > 0) {
                $config = $wa_config_result->fetch_assoc();
                $response_message = $config['auto_reply_message'];
            }
            break;
    }
    
    // Send auto response if available
    if ($response_message) {
        $wa_gateway = new WhatsAppGateway($connection);
        $wa_gateway->sendMessage($phone, $response_message);
    }
    
    return array('success' => true, 'message' => 'Message processed');
}

function handleStatusUpdate($data) {
    global $connection;
    
    // Extract status data
    $message_id = isset($data['message_id']) ? $data['message_id'] : '';
    $status = isset($data['status']) ? $data['status'] : '';
    
    if (empty($message_id) || empty($status)) {
        return array('success' => false, 'message' => 'Invalid status update data');
    }
    
    // Update message status in database
    $stmt = $connection->prepare("UPDATE whatsapp_logs SET status = ? WHERE response LIKE ?");
    $message_id_pattern = '%' . $message_id . '%';
    $stmt->bind_param('ss', $status, $message_id_pattern);
    $stmt->execute();
    $stmt->close();
    
    return array('success' => true, 'message' => 'Status updated');
}
?>