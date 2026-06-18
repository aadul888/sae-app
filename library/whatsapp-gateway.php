<?php
/**
 * WhatsApp Gateway Helper - Simple Version
 * Untuk mengirim notifikasi aktivitas sistem SAEV4
 * 
 * @author System
 * @version 1.0
 * @date 2026-02-02
 */

class WhatsAppGateway {
    
    private $connection;
    private $config;
    
    public function __construct($database_connection) {
        $this->connection = $database_connection;
        $this->loadConfig();
    }
    
    /**
     * Load konfigurasi WhatsApp Gateway dari database
     */
    private function loadConfig() {
        $result = $this->connection->query("SELECT * FROM whatsapp_config WHERE id = 1");
        if ($result && $result->num_rows > 0) {
            $this->config = $result->fetch_assoc();
        } else {
            $this->config = array(
                'api_url' => '',
                'api_key' => '',
                'status' => 'N'
            );
        }
    }
    
    /**
     * Cek apakah WhatsApp Gateway aktif
     * @return boolean
     */
    public function isActive() {
        return $this->config['status'] == 'Y';
    }
    
    /**
     * Kirim pesan WhatsApp untuk aktivitas sistem
     * @param string $phone Nomor telepon tujuan
     * @param string $message Isi pesan
     * @param string $activity_type Jenis aktivitas (verifikasi_hp, reset_password, login_alert, dll)
     * @param int $user_id ID user terkait
     * @return array Response dengan status dan pesan
     */
    public function sendMessage($phone, $message, $activity_type = 'notification', $user_id = null) {
        
        // Cek apakah WhatsApp Gateway aktif
        if (!$this->isActive()) {
            return array(
                'success' => false,
                'message' => 'WhatsApp Gateway tidak aktif'
            );
        }
        
        // Validasi input
        if (empty($phone) || empty($message)) {
            return array(
                'success' => false,
                'message' => 'Nomor telepon dan pesan harus diisi'
            );
        }
        
        // Format nomor telepon
        $phone = $this->formatPhoneNumber($phone);
        
        // Log pesan ke database
        $log_id = $this->logMessage($phone, $message, $activity_type, $user_id);
        
        // Kirim request ke API WhatsApp Gateway
        $response = $this->sendApiRequest($phone, $message);
        
        if ($response['success']) {
            // Update status log menjadi sent
            $this->updateLogStatus($log_id, 'sent', $response['data']);
            
            return array(
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'data' => $response['data']
            );
        } else {
            // Update status log menjadi failed
            $this->updateLogStatus($log_id, 'failed', null, $response['message']);
            
            return array(
                'success' => false,
                'message' => $response['message']
            );
        }
    }
    
    /**
     * Format nomor telepon ke format internasional
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber($phone) {
        // Hapus semua karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($phone, 0, 1) == '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // Jika belum dimulai dengan 62, tambahkan 62
        if (substr($phone, 0, 2) != '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Log pesan ke database
     * @param string $phone
     * @param string $message
     * @param string $activity_type
     * @param int $user_id
     * @return int Log ID
     */
    private function logMessage($phone, $message, $activity_type, $user_id) {
        $stmt = $this->connection->prepare("INSERT INTO whatsapp_logs (phone_number, message, activity_type, status, user_id) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->bind_param('sssi', $phone, $message, $activity_type, $user_id);
        $stmt->execute();
        
        $log_id = $this->connection->insert_id;
        $stmt->close();
        
        return $log_id;
    }
    
    /**
     * Update status log pesan
     * @param int $log_id
     * @param string $status
     * @param string $response
     * @param string $error_message
     */
    private function updateLogStatus($log_id, $status, $response = null, $error_message = null) {
        $stmt = $this->connection->prepare("UPDATE whatsapp_logs SET status = ?, response = ?, error_message = ? WHERE id = ?");
        $stmt->bind_param('sssi', $status, $response, $error_message, $log_id);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Kirim request ke API WhatsApp Gateway
     * @param string $phone
     * @param string $message
     * @return array
     */
    private function sendApiRequest($phone, $message) {
        
        // Debug mode untuk testing lokal
        $is_localhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
                       strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
        
        // Jika API URL kosong atau dalam mode localhost testing
        if (empty($this->config['api_url']) || empty($this->config['api_key'])) {
            // Log untuk debug
            error_log("WhatsApp Gateway: Mode simulasi - API belum dikonfigurasi");
            
            return array(
                'success' => true,
                'data' => json_encode([
                    'status' => 'success',
                    'message' => 'Pesan simulasi berhasil dikirim',
                    'target' => $phone,
                    'text' => $message
                ]),
                'message' => 'Mode simulasi: Pesan berhasil dikirim (localhost testing)'
            );
        }
        
        // Log request untuk debug
        error_log("WhatsApp Gateway (OneAPI): Mengirim ke " . $phone . " via " . $this->config['api_url']);
        
        // Coba multiple format untuk OneAPI - prioritas format yang sudah terbukti berhasil
        $formats = [
            // Format 1: WhatsApp Business API Style (TERBUKTI BERHASIL!)
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
        
        $lastError = '';
        
        foreach ($formats as $index => $postData) {
            error_log("WhatsApp Gateway (OneAPI): Mencoba format " . ($index + 1) . " - " . json_encode($postData));
            
            $curl = curl_init();
            
            // Deteksi format authorization header berdasarkan provider
            $authHeader = 'Authorization: ' . $this->config['api_key'];
            if (strpos($this->config['api_url'], 'oneapi.my.id') !== false) {
                // OneAPI menggunakan Bearer token
                $authHeader = 'Authorization: Bearer ' . $this->config['api_key'];
            }
            
            // Semua format menggunakan JSON untuk OneAPI WhatsApp Business API
            $postFields = json_encode($postData);
            $contentType = 'Content-Type: application/json';
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->config['api_url'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
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
            
            // Log response
            error_log("WhatsApp Gateway Format " . ($index + 1) . ": HTTP $http_code - " . ($error ?: $response));
            
            if ($error) {
                $lastError = 'cURL Error: ' . $error;
                continue;
            }
            
            if ($http_code == 200) {
                // Decode response untuk validasi OneAPI
                $responseData = json_decode($response, true);
                
                // Cek apakah response sukses dari OneAPI WhatsApp Business API
                if ($responseData && isset($responseData['code']) && $responseData['code'] == 200) {
                    return array(
                        'success' => true,
                        'data' => $response,
                        'message' => 'Pesan berhasil dikirim via OneAPI (Format ' . ($index + 1) . ')'
                    );
                }
                
                // Jika bukan format yang tepat, lanjut ke format berikutnya
                $lastError = 'OneAPI Error: ' . ($responseData['message'] ?? 'Unknown error');
                continue;
            }
            
            $lastError = 'HTTP Error ' . $http_code . ': ' . $response;
        }
        
        // Jika semua format gagal
        return array(
            'success' => false,
            'message' => 'Semua format gagal. Last error: ' . $lastError
        );
    }
    
    /**
     * Get statistik pengiriman pesan
     * @param int $days Jumlah hari terakhir
     * @return array
     */
    public function getStats($days = 7) {
        $stats = array();
        
        // Total pesan hari ini
        $result = $this->connection->query("SELECT COUNT(*) as total FROM whatsapp_logs WHERE DATE(created_at) = CURDATE()");
        $stats['today'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Total pesan dalam periode
        $result = $this->connection->query("SELECT COUNT(*) as total FROM whatsapp_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)");
        $stats['period'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Status pengiriman
        $result = $this->connection->query("SELECT status, COUNT(*) as total FROM whatsapp_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY status");
        $stats['by_status'] = array();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['by_status'][$row['status']] = $row['total'];
            }
        }
        
        return $stats;
    }
}

/**
 * Fungsi helper untuk mengirim notifikasi WhatsApp
 * @param string $phone Nomor telepon
 * @param string $message Pesan
 * @param string $activity_type Jenis aktivitas
 * @param mysqli $connection Database connection
 * @param int $user_id ID user terkait
 * @return array
 */
function sendWhatsAppNotification($phone, $message, $activity_type, $connection, $user_id = null) {
    $wa = new WhatsAppGateway($connection);
    return $wa->sendMessage($phone, $message, $activity_type, $user_id);
}

/**
 * Template pesan untuk aktivitas umum sistem
 */
function getWhatsAppTemplate($type, $variables = array()) {
    $templates = array(
        'verifikasi_hp' => 'Kode verifikasi WhatsApp Anda: {kode}. Jangan berikan kode ini kepada siapa pun.',
        'reset_password' => 'Kode reset password: {kode}. Gunakan kode ini untuk mereset password akun {username}.',
        'login_alert' => 'Akun {username} berhasil login pada {tanggal} pukul {waktu}. Jika bukan Anda, segera hubungi admin.',
        'register_success' => 'Selamat! Akun Anda berhasil didaftarkan dengan username: {username}. Selamat bergabung di {nama_sekolah}.',
        'account_activated' => 'Akun {username} telah diaktifkan. Anda sekarang dapat menggunakan semua fitur sistem.',
        'payment_reminder' => 'Reminder: Pembayaran {jenis_pembayaran} sebesar Rp {jumlah} akan jatuh tempo pada {tanggal_jatuh_tempo}.'
    );
    
    if (!isset($templates[$type])) {
        return false;
    }
    
    $message = $templates[$type];
    
    // Replace variables
    foreach ($variables as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
    }
    
    return $message;
}