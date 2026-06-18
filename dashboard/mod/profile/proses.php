<?php
session_start();
require_once '../../../library/config.php';
require_once '../../../library/function.php';
require_once '../../../library/whatsapp-gateway.php';

if (!isset($_COOKIE['siswa'])) {
  echo json_encode(['status' => 'error', 'message' => 'Session tidak valid']);
  exit;
}

// Get user ID from cookie
$siswa_cookie = convert("decrypt", $_COOKIE['siswa']);
$siswa_id_int = (int)$siswa_cookie;

if (empty($siswa_id_int)) {
  echo json_encode(['status' => 'error', 'message' => 'User ID tidak valid']);
  exit;
}

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan'];

// Proses berdasarkan action
$action = $_GET['action'] ?? '';

switch ($action) {
  case 'update_profile':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $email = trim($_POST['email'] ?? '');
      $telp = trim($_POST['telp'] ?? '');
      
      // Validasi email
      if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Format email tidak valid';
        break;
      }
      
      // Validasi nomor telepon
      if (!empty($telp) && !preg_match('/^[0-9+\-\s]+$/', $telp)) {
        $response['message'] = 'Format nomor telepon tidak valid';
        break;
      }
      
      // Escape data untuk SQL
      $email_escaped = $connection->real_escape_string($email);
      $telp_escaped = $connection->real_escape_string($telp);
      
      // Cek apakah nomor telepon berubah
      $current_telp_query = $connection->query("SELECT telp FROM user WHERE user_id='$siswa_id_int'");
      $should_reset_whatsapp = false;
      
      if ($current_telp_query && $current_telp_query->num_rows > 0) {
        $current_data = $current_telp_query->fetch_assoc();
        if ($current_data['telp'] !== $telp) {
          $should_reset_whatsapp = true;
        }
      }
      
      // Update data dengan reset verifikasi WhatsApp jika nomor berubah
      if ($should_reset_whatsapp) {
        $sql = "UPDATE user SET email='$email_escaped', telp='$telp_escaped', whatsapp_verified=0, whatsapp_verified_at=NULL WHERE user_id='$siswa_id_int'";
      } else {
        $sql = "UPDATE user SET email='$email_escaped', telp='$telp_escaped' WHERE user_id='$siswa_id_int'";
      }
      
      if ($connection->query($sql) === true) {
        if ($should_reset_whatsapp) {
          $response = [
            'status' => 'success', 
            'message' => 'Profile berhasil diupdate. Verifikasi WhatsApp direset karena nomor telepon berubah.',
            'whatsapp_reset' => true
          ];
        } else {
          $response = ['status' => 'success', 'message' => 'Profile berhasil diupdate'];
        }
      } else {
        $response['message'] = 'Gagal update profile: ' . $connection->error;
      }
    }
    break;
    
  case 'change_password':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $new_password = $_POST['new_password'] ?? '';
      $confirm_password = $_POST['confirm_password'] ?? '';
      
      // Validasi input
      if (empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'Password baru dan konfirmasi password harus diisi';
        break;
      }
      
      if ($new_password !== $confirm_password) {
        $response['message'] = 'Konfirmasi password tidak sama';
        break;
      }
      
      // Validasi kekuatan password
      $policyRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/';
      if (!preg_match($policyRegex, $new_password)) {
        $response['message'] = 'Password harus minimal 6 karakter dan mengandung huruf besar, huruf kecil, angka, dan simbol';
        break;
      }
      
      // Hash password baru langsung tanpa cek password lama
      $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
      $new_password_escaped = $connection->real_escape_string($new_password_hash);
      
      $sql = "UPDATE user SET password='$new_password_escaped' WHERE user_id='$siswa_id_int'";
      
      if ($connection->query($sql) === true) {
        $response = ['status' => 'success', 'message' => 'Password berhasil diubah'];
      } else {
        $response['message'] = 'Gagal mengubah password: ' . $connection->error;
      }
    }
    break;
    
  case 'upload_avatar':
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
      $file = $_FILES['avatar'];
      
      // Validasi file
      $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
      $max_size = 5 * 1024 * 1024; // 5MB
      
      if (!in_array($file['type'], $allowed_types)) {
        $response['message'] = 'Format file tidak didukung. Gunakan JPEG, JPG, atau PNG';
        break;
      }
      
      if ($file['size'] > $max_size) {
        $response['message'] = 'Ukuran file terlalu besar. Maksimal 5MB';
        break;
      }
      
      if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Terjadi error saat upload file';
        break;
      }
      
      // Generate nama file
      $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = $siswa_id . '_' . time() . '.' . $extension;
      $upload_path = '../../../content/avatar/' . $filename;
      
      // Buat direktori jika belum ada
      $avatar_dir = '../../../content/avatar/';
      if (!file_exists($avatar_dir)) {
        mkdir($avatar_dir, 0755, true);
      }
      
      // Upload file
      if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Hapus avatar lama jika ada
        $siswa_id_int = intval($siswa_id);
        $q = $connection->query("SELECT avatar FROM user WHERE user_id='$siswa_id_int'");
        if ($q && $q->num_rows > 0) {
          $old_data = $q->fetch_assoc();
          if (!empty($old_data['avatar'])) {
            $old_file = preg_replace('/\?.*/', '', $old_data['avatar']);
            $old_path = '../../../content/avatar/' . $old_file;
            if (file_exists($old_path) && $old_file !== 'avatar.jpg') {
              unlink($old_path);
            }
          }
        }
        
        // Update database
        $avatar_with_timestamp = $filename . '?t=' . time();
        $avatar_escaped = $connection->real_escape_string($avatar_with_timestamp);
        $sql = "UPDATE user SET avatar='$avatar_escaped' WHERE user_id='$siswa_id_int'";
        
        if ($connection->query($sql) === true) {
          $response = [
            'status' => 'success', 
            'message' => 'Avatar berhasil diupload',
            'avatar_url' => '../content/avatar/' . $avatar_with_timestamp
          ];
        } else {
          $response['message'] = 'File terupload tapi gagal update database';
        }
      } else {
        $response['message'] = 'Gagal upload file';
      }
    }
    break;
    
  case 'send_whatsapp_verification':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Ambil data user
      $q = $connection->query("SELECT telp, nama_lengkap FROM user WHERE user_id='$siswa_id_int'");
      
      if (!$q || $q->num_rows === 0) {
        $response['message'] = 'User tidak ditemukan';
        break;
      }
      
      $user_data = $q->fetch_assoc();
      $phone = trim($user_data['telp'] ?? '');
      $nama = $user_data['nama_lengkap'] ?? '';
      
      if (empty($phone)) {
        $response['message'] = 'Nomor telepon belum diisi. Silakan isi nomor telepon terlebih dahulu.';
        break;
      }
      
      // Generate kode verifikasi
      $verification_code = rand(100000, 999999);
      
      // Simpan kode ke session dengan expire 5 menit
      $_SESSION['whatsapp_verification'] = [
        'code' => $verification_code,
        'phone' => $phone,
        'user_id' => $siswa_id_int,
        'expires' => time() + 300, // 5 menit
        'attempts' => 0
      ];
      
      // Siapkan pesan WhatsApp
      $message = getWhatsAppTemplate('verifikasi_hp', [
        'kode' => $verification_code,
        'nama' => $nama
      ]);
      
      if (!$message) {
        $message = "Kode verifikasi WhatsApp Anda: $verification_code\n\nJangan berikan kode ini kepada siapa pun.\n\nTerima kasih.";
      }
      
      // Kirim via WhatsApp
      $wa_result = sendWhatsAppNotification($phone, $message, 'verifikasi_hp', $connection, $siswa_id_int);
      
      // Debug log untuk troubleshooting
      error_log("WhatsApp Verification Debug - Phone: $phone, Result: " . json_encode($wa_result));
      
      if ($wa_result['success']) {
        $response = [
          'status' => 'success', 
          'message' => 'Kode verifikasi telah dikirim ke WhatsApp Anda',
          'phone' => $phone,
          'debug' => $wa_result // Sementara untuk debugging
        ];
      } else {
        $response['message'] = 'Gagal mengirim kode verifikasi: ' . $wa_result['message'];
      }
    }
    break;
    
  case 'verify_whatsapp_code':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $input_code = trim($_POST['code'] ?? '');
      
      if (empty($input_code)) {
        $response['message'] = 'Kode verifikasi harus diisi';
        break;
      }
      
      // Cek session verifikasi
      if (!isset($_SESSION['whatsapp_verification'])) {
        $response['message'] = 'Session verifikasi tidak ditemukan. Silakan minta kode baru.';
        break;
      }
      
      $verification = $_SESSION['whatsapp_verification'];
      
      // Cek apakah expired
      if (time() > $verification['expires']) {
        unset($_SESSION['whatsapp_verification']);
        $response['message'] = 'Kode verifikasi telah expired. Silakan minta kode baru.';
        break;
      }
      
      // Cek attempts
      if ($verification['attempts'] >= 3) {
        unset($_SESSION['whatsapp_verification']);
        $response['message'] = 'Terlalu banyak percobaan. Silakan minta kode baru.';
        break;
      }
      
      // Verifikasi kode
      if ($input_code == $verification['code']) {
        // Update database - set whatsapp_verified = 1
        $sql = "UPDATE user SET whatsapp_verified=1, whatsapp_verified_at=NOW() WHERE user_id='$siswa_id_int'";
        
        if ($connection->query($sql) === true) {
          // Clear session
          unset($_SESSION['whatsapp_verification']);
          
          // Log aktivitas
          if (function_exists('logWhatsAppActivity')) {
            logWhatsAppActivity($connection, 'verification_success', $verification['phone'], 'WhatsApp verification successful', json_encode([
              'user_id' => $siswa_id_int,
              'verified_at' => date('Y-m-d H:i:s')
            ]));
          }
          
          $response = [
            'status' => 'success', 
            'message' => 'Nomor WhatsApp berhasil diverifikasi!'
          ];
        } else {
          $response['message'] = 'Gagal menyimpan status verifikasi';
        }
      } else {
        // Increment attempts
        $_SESSION['whatsapp_verification']['attempts']++;
        $remaining = 3 - $_SESSION['whatsapp_verification']['attempts'];
        
        if ($remaining > 0) {
          $response['message'] = "Kode verifikasi salah. Sisa percobaan: $remaining";
        } else {
          unset($_SESSION['whatsapp_verification']);
          $response['message'] = 'Kode verifikasi salah. Batas percobaan tercapai. Silakan minta kode baru.';
        }
      }
    }
    break;
    
  default:
    $response['message'] = 'Action tidak valid';
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>