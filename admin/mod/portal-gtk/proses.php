<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';



  // Pastikan kolom is_active ada di tabel portal_apps
  $connection->query("ALTER TABLE portal_apps ADD COLUMN IF NOT EXISTS is_active ENUM('Y','N') DEFAULT 'Y'");

  /** Fungsi untuk enkripsi/dekripsi password sederhana */
  function encrypt_password($password) {
    $key = 'sae_portal_key_2024'; // Ganti dengan key yang lebih aman di production
    return base64_encode(openssl_encrypt($password, 'AES-256-CBC', $key, 0, substr(hash('sha256', $key), 0, 16)));
  }
  
  function decrypt_password($encrypted_password) {
    $key = 'sae_portal_key_2024'; // Harus sama dengan key enkripsi
    return openssl_decrypt(base64_decode($encrypted_password), 'AES-256-CBC', $key, 0, substr(hash('sha256', $key), 0, 16));
  }

  try {
    switch (@$_GET['action']) {

    /** Portal GTK - Akses Aplikasi */
    case 'portal-access':
      $app_name = isset($_POST['app_name']) ? htmlentities(trim($_POST['app_name'])) : '';
      $app_url = isset($_POST['app_url']) ? trim($_POST['app_url']) : '';
      
      if (empty($app_name) || empty($app_url)) {
        echo json_encode(['status' => 'error', 'message' => 'Data aplikasi tidak lengkap']);
        exit;
      }
      
      // Ambil admin_id dari cookie
      $admin_id = 0;
      if (isset($_COOKIE['ADMIN_KEY'])) {
        $admin_id = intval(epm_decode($_COOKIE['ADMIN_KEY']));
      }
      
      if ($admin_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired, silakan login kembali']);
        exit;
      }
      
      // Cek apakah admin memiliki kredensial untuk aplikasi ini
      $stmt = $connection->prepare("SELECT app_username, app_password, notes FROM user_app_credentials WHERE admin_id = ? AND app_id = (SELECT app_id FROM portal_apps WHERE app_name = ? LIMIT 1) AND is_active = 'Y'");
      $stmt->bind_param('is', $admin_id, $app_name);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result && $result->num_rows > 0) {
        $credential = $result->fetch_assoc();
        
        // Dekripsi password
        $decrypted_password = decrypt_password($credential['app_password']);
        
        // Log aktivitas akses portal
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->close();
        
        // Pastikan tabel portal_access_log ada
        $connection->query("CREATE TABLE IF NOT EXISTS portal_access_log (
          id INT AUTO_INCREMENT PRIMARY KEY,
          admin_id INT NOT NULL,
          app_name VARCHAR(255) NOT NULL,
          app_url TEXT NOT NULL,
          ip_address VARCHAR(45),
          user_agent TEXT,
          access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_admin_app (admin_id, app_name),
          INDEX idx_access_time (access_time)
        )");
        
        // Insert log sederhana
        $log_stmt = $connection->prepare("INSERT INTO portal_access_log (admin_id, app_name, app_url, ip_address, user_agent, access_time) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($log_stmt) {
          $log_stmt->bind_param('issss', $admin_id, $app_name, $app_url, $ip_address, $user_agent);
          $log_stmt->execute();
          $log_stmt->close();
        }
        
        echo json_encode([
          'status' => 'success',
          'username' => $credential['app_username'],
          'password' => $decrypted_password,
          'app_name' => $app_name,
          'app_url' => $app_url,
          'notes' => $credential['notes'] ?? null
        ]);
      } else {
        $stmt->close();
        // Berikan response yang lebih user-friendly
        echo json_encode([
          'status' => 'info',
          'message' => 'Kredensial belum disimpan untuk aplikasi ini',
          'app_name' => $app_name,
          'app_url' => $app_url,
          'action' => 'setup_credential'
        ]);
      }
      break;

    /** CRUD Portal Apps */
    case 'app-save':
      $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
      $app_name = isset($_POST['app_name']) ? htmlentities(trim($_POST['app_name'])) : '';
      $app_url = isset($_POST['app_url']) ? trim($_POST['app_url']) : '';
      $app_icon = isset($_POST['app_icon']) ? htmlentities(trim($_POST['app_icon'])) : 'fas fa-globe';
      $app_description = isset($_POST['app_description']) ? htmlentities(trim($_POST['app_description'])) : '';
      $app_category = isset($_POST['app_category']) ? $_POST['app_category'] : 'other';
      $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
      $icon_type = isset($_POST['icon_type']) ? $_POST['icon_type'] : 'font';
      $current_icon_file = isset($_POST['current_icon_file']) ? $_POST['current_icon_file'] : '';
      
      // Validasi
      if (empty($app_name) || empty($app_url)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama aplikasi dan URL harus diisi']);
        exit;
      }
      
      // Validasi URL
      if (!filter_var($app_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format URL tidak valid']);
        exit;
      }
      
      // Handle upload icon - EXACT pattern dari user upload_foto_nisn yang BERHASIL
      $custom_icon = $current_icon_file;
      if ($icon_type === 'upload' && isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK && $_FILES['icon_file']['size'] > 0) {
        
        $allowed_ext = ['png', 'jpg', 'jpeg'];  // User module hanya 3 format ini
        $fileExt = strtolower(pathinfo($_FILES['icon_file']['name'], PATHINFO_EXTENSION));
        
        // Validasi hanya jika ada file yang benar-benar diupload
        if (empty($fileExt) || !in_array($fileExt, $allowed_ext)) {
          echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung. Hanya PNG/JPG/JPEG.']);
          exit;
        }
        
        if ($_FILES['icon_file']['size'] > 1048576) { // 1MB
          echo json_encode(['status' => 'error', 'message' => 'Icon terlalu besar! Maksimal 1MB.']);
          exit;
        }
        
        // EXACT pattern dari user modul - TIDAK pakai mkdir()
        $target_dir = '../../../content/icon-apps/';
        $filename = 'icon_' . time() . '.png';  // Selalu .png seperti user module
        $target_file = $target_dir . $filename;
        
        // User module TIDAK cek is_dir atau buat folder - langsung pakai folder yang ada
        
        // Hapus icon lama jika ada
        if (!empty($current_icon_file)) {
          $old_file_path = $target_dir . $current_icon_file;
          if (file_exists($old_file_path)) {
            @unlink($old_file_path);
          }
        }
        
        // EXACT SAME logic dari user module upload_foto_nisn
        if ($fileExt !== 'png') {
          // Konversi ke PNG jika bukan PNG - EXACT dari user module
          $img = null;
          if ($fileExt === 'jpg' || $fileExt === 'jpeg') {
            $img = imagecreatefromjpeg($_FILES['icon_file']['tmp_name']);
          }
          if ($img) {
            if (imagepng($img, $target_file)) {
              imagedestroy($img);
              $custom_icon = $filename;
            } else {
              imagedestroy($img);
              echo json_encode(['status' => 'error', 'message' => 'Gagal konversi gambar.']);
              exit;
            }
          } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal konversi gambar.']);
            exit;
          }
        } else {
          // PNG langsung move - EXACT dari user module
          if (move_uploaded_file($_FILES['icon_file']['tmp_name'], $target_file)) {
            $custom_icon = $filename;
          } else {
            // Add permission check untuk debug VPS
            $parent_dir = dirname($target_file);
            $perm_msg = '';
            if (!is_writable($parent_dir)) {
              $perm_msg = ' (folder tidak writable)';
            }
            echo json_encode(['status' => 'error', 'message' => 'Gagal upload file icon' . $perm_msg]);
            exit;
          }
        }
      } elseif ($icon_type === 'upload' && !empty($current_icon_file)) {
        // Jika mode upload tapi tidak ada file baru, gunakan icon yang sudah ada
        $custom_icon = $current_icon_file;
      } elseif ($icon_type === 'font') {
        // Jika ganti ke font icon, hapus custom icon lama
        if (!empty($current_icon_file)) {
          $old_file_path = '../../../content/icon-apps/' . basename($current_icon_file);
          if (file_exists($old_file_path)) {
            @unlink($old_file_path);
          }
        }
        $custom_icon = null;
      }
      
      // Auto generate sort_order jika 0
      if ($sort_order == 0) {
        $max_query = $connection->query("SELECT MAX(sort_order) as max_order FROM portal_apps");
        $max_result = $max_query->fetch_assoc();
        $sort_order = ($max_result['max_order'] ?? 0) + 1;
      }
      
      if ($app_id > 0) {
        // Update
        $stmt = $connection->prepare("UPDATE portal_apps SET app_name=?, app_url=?, app_icon=?, custom_icon=?, app_description=?, app_category=?, sort_order=? WHERE app_id=?");
        $stmt->bind_param('ssssssii', $app_name, $app_url, $app_icon, $custom_icon, $app_description, $app_category, $sort_order, $app_id);
        
        if ($stmt->execute()) {
          echo json_encode(['status' => 'success', 'message' => 'Aplikasi berhasil diperbarui']);
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui aplikasi']);
        }
        $stmt->close();
      } else {
        // Insert
        $stmt = $connection->prepare("INSERT INTO portal_apps (app_name, app_url, app_icon, custom_icon, app_description, app_category, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssi', $app_name, $app_url, $app_icon, $custom_icon, $app_description, $app_category, $sort_order);
        
        if ($stmt->execute()) {
          echo json_encode(['status' => 'success', 'message' => 'Aplikasi berhasil ditambahkan']);
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan aplikasi']);
        }
        $stmt->close();
      }
      break;
      
    case 'app-delete':
      $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
      $custom_icon = isset($_POST['custom_icon']) ? trim($_POST['custom_icon']) : '';
      
      if ($app_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID aplikasi tidak valid']);
        exit;
      }
      
      // Jika ada custom icon, hapus file icon nya
      if (!empty($custom_icon)) {
        $icon_path = '../../../content/icon-apps/' . basename($custom_icon);
        if (file_exists($icon_path)) {
          @unlink($icon_path);
        }
      }
      
      // Hapus aplikasi dari database
      $stmt = $connection->prepare("DELETE FROM portal_apps WHERE app_id=?");
      $stmt->bind_param('i', $app_id);
      
      if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Aplikasi berhasil dihapus']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus aplikasi']);
      }
      
      $stmt->close();
      break;
      
    case 'getApp':
      $app_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
      
      if ($app_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID aplikasi tidak valid']);
        exit;
      }
      
      $stmt = $connection->prepare("SELECT app_id as id, app_name as nama, app_url as link, app_icon as icon, custom_icon, app_description as keterangan, app_category as kategori, is_active as status FROM portal_apps WHERE app_id=?");
      $stmt->bind_param('i', $app_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        $app = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $app]);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Aplikasi tidak ditemukan']);
      }
      
      $stmt->close();
      break;
      
    case 'app-toggle':
      $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
      $status = isset($_POST['status']) ? $_POST['status'] : 'N';
      
      if ($app_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID aplikasi tidak valid']);
        exit;
      }
      
      $stmt = $connection->prepare("UPDATE portal_apps SET is_active=? WHERE app_id=?");
      $stmt->bind_param('si', $status, $app_id);
      
      if ($stmt->execute()) {
        $message = $status == 'Y' ? 'Aplikasi berhasil diaktifkan' : 'Aplikasi berhasil dinonaktifkan';
        echo json_encode(['status' => 'success', 'message' => $message]);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status aplikasi']);
      }
      $stmt->close();
      break;
      
    /** CRUD User App Credentials */
    case 'credential-save':
      $admin_id = isset($_COOKIE['ADMIN_KEY']) ? intval(epm_decode($_COOKIE['ADMIN_KEY'])) : 0;
      $credential_id = isset($_POST['credential_id']) ? intval($_POST['credential_id']) : 0;
      $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
      $app_username = isset($_POST['app_username']) ? trim($_POST['app_username']) : '';
      $app_password = isset($_POST['app_password']) ? trim($_POST['app_password']) : '';
      $notes = isset($_POST['notes']) ? htmlentities(trim($_POST['notes'])) : '';
      
      if ($admin_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired, silakan login kembali']);
        exit;
      }
      
      if ($app_id <= 0 || empty($app_username) || empty($app_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Data kredensial tidak lengkap']);
        exit;
      }
      
      $encrypted_password = encrypt_password($app_password);
      
      if ($credential_id > 0) {
        // Update
        $stmt = $connection->prepare("UPDATE user_app_credentials SET app_username=?, app_password=?, notes=? WHERE id=? AND admin_id=?");
        $stmt->bind_param('sssii', $app_username, $encrypted_password, $notes, $credential_id, $admin_id);
        
        if ($stmt->execute()) {
          echo json_encode(['status' => 'success', 'message' => 'Kredensial berhasil diperbarui']);
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui kredensial']);
        }
      } else {
        // Insert
        $stmt = $connection->prepare("INSERT INTO user_app_credentials (admin_id, app_id, app_username, app_password, notes, created_at, is_active) VALUES (?, ?, ?, ?, ?, NOW(), 'Y')");
        $stmt->bind_param('iisss', $admin_id, $app_id, $app_username, $encrypted_password, $notes);
        
        if ($stmt->execute()) {
          echo json_encode(['status' => 'success', 'message' => 'Kredensial berhasil disimpan']);
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan kredensial']);
        }
      }
      
      $stmt->close();
      break;
      
    case 'credential-get':
      $admin_id = isset($_COOKIE['ADMIN_KEY']) ? intval(epm_decode($_COOKIE['ADMIN_KEY'])) : 0;
      $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
      
      if ($admin_id <= 0 || $app_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid']);
        exit;
      }
      
      $stmt = $connection->prepare("SELECT id, app_username, app_password, notes FROM user_app_credentials WHERE admin_id=? AND app_id=? AND is_active='Y'");
      $stmt->bind_param('ii', $admin_id, $app_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        $credential = $result->fetch_assoc();
        $credential['app_password'] = decrypt_password($credential['app_password']);
        echo json_encode(['status' => 'success', 'data' => $credential]);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Kredensial tidak ditemukan']);
      }
      
      $stmt->close();
      break;
      
    case 'credential-delete':
      $admin_id = isset($_COOKIE['ADMIN_KEY']) ? intval(epm_decode($_COOKIE['ADMIN_KEY'])) : 0;
      $credential_id = isset($_POST['credential_id']) ? intval($_POST['credential_id']) : 0;
      
      if ($admin_id <= 0 || $credential_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid']);
        exit;
      }
      
      $stmt = $connection->prepare("DELETE FROM user_app_credentials WHERE id=? AND admin_id=?");
      $stmt->bind_param('ii', $credential_id, $admin_id);
      
      if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Kredensial berhasil dihapus']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus kredensial']);
      }
      
      $stmt->close();
      break;
  }
  } catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem']);
  }
}