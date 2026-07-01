<?php
// Basic error reporting and output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type for proper response
header('Content-Type: text/plain; charset=utf-8');

// Start output buffering to catch any unwanted output
ob_start();

// Catch fatal errors and return readable message
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        echo 'Server error: ' . $error['message'];
    }
});

session_start();

// Debug log with request info
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'action' => $_GET['action'] ?? 'none',
    'files_count' => count($_FILES),
    'session_exists' => session_id() ? 'yes' : 'no',
    'cookie_exists' => isset($_COOKIE['siswa']) ? 'yes' : 'no'
];
if (function_exists('debug_log')) debug_log(["Berkas request" => $debug_info]);

// Check session/cookie
if (!isset($_COOKIE['siswa'])) {
    ob_clean();
    echo 'Session tidak valid - silakan login ulang';
    exit;
}

try {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    // Manual user authentication (instead of requiring user.php)
    $siswa = convert("decrypt", $_COOKIE['siswa']);
    if (empty($siswa)) {
        ob_clean();
        echo 'Session tidak valid - data tidak dapat didekripsi';
        exit;
    }

    // Get user data from database
    $query_user = "SELECT * FROM user WHERE status='Aktif' AND user_id='" . htmlentities($siswa, ENT_QUOTES, 'UTF-8') . "' LIMIT 1";
    $result_user = $connection->query($query_user);
    if (!$result_user || $result_user->num_rows == 0) {
        ob_clean();
        echo 'User tidak ditemukan atau tidak aktif';
        exit;
    }

    $data_user = $result_user->fetch_assoc();

    // Verify user data
    if (empty($data_user)) {
        ob_clean();
        echo 'Data user tidak ditemukan - silakan login ulang';
        exit;
    }

    if (function_exists('debug_log')) debug_log("User verified: " . ($data_user['user_id'] ?? 'unknown'));
} catch (Exception $e) {
    ob_clean();
    echo 'Error sistem: ' . $e->getMessage();
    if (function_exists('debug_log')) debug_log("Include error: " . $e->getMessage());
    exit;
}

// Setup folder dengan error handling
$folder = '../../../content/berkas/';

// Ensure the path resolves correctly to content/berkas
if (!file_exists('../../../content/')) {
    if (!mkdir('../../../content/', 0755, true)) {
        ob_clean();
        echo 'Gagal membuat folder content';
        if (function_exists('debug_log')) debug_log("Cannot create content folder");
        exit;
    }
}

if (!is_dir($folder)) {
    if (!mkdir($folder, 0755, true)) {
        ob_clean();
        echo 'Gagal membuat folder berkas';
        if (function_exists('debug_log')) debug_log("Cannot create folder: $folder");
        exit;
    }
    if (function_exists('debug_log')) debug_log("Created folder: $folder");
}

// Verify folder permissions
if (!is_writable($folder)) {
    ob_clean();
    echo 'Folder berkas tidak dapat ditulis - periksa permission';
    if (function_exists('debug_log')) debug_log("Folder not writable: $folder");
    exit;
}

// Get absolute path for logging
$folder_absolute = realpath($folder);
if (function_exists('debug_log')) debug_log("Folder ready: $folder (absolute: $folder_absolute)");

switch (@$_GET['action']) {
    /* ---------- ADD BERKAS ---------- */
    case 'add':
        try {
            // Clear any previous output
            ob_clean();

            if (function_exists('debug_log')) debug_log("Processing upload for user: " . $data_user['user_id']);

            $user_id = $data_user['user_id'] ?? '';
            $nisn = $data_user['nisn'] ?? '';

            if (empty($user_id) || empty($nisn)) {
                echo 'Data user tidak lengkap';
                exit;
            }

            // Get uploaded files
            $files = [];
            foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                $files[$field] = $_FILES[$field] ?? null;
            }

            if (function_exists('debug_log')) debug_log(["Files received" => array_map(function ($f) {
                return $f ? ['name' => $f['name'], 'size' => $f['size'], 'error' => $f['error']] : null;
            }, $files)]);

            // Helper function untuk upload
            function uploadFile($file, $jenis, $nisn, $folder)
            {
                if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                    return '';
                }

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Error upload $jenis: " . $file['error']);
                }

                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
                    if ($finfo) finfo_close($finfo);
                } else {
                    $mime_type = false;
                }
                if (!$mime_type && function_exists('mime_content_type')) {
                    $mime_type = mime_content_type($file['tmp_name']);
                }
                if (!$mime_type) {
                    // Fallback: detect by file extension
                    $ext_map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
                    $tmp_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $mime_type = $ext_map[$tmp_ext] ?? '';
                }

                if (!in_array($mime_type, $allowed_types)) {
                    throw new Exception("Tipe file $jenis tidak didukung. Hanya JPG, PNG, atau PDF yang diizinkan.");
                }

                // Validate file size (10MB for image, 2MB for PDF)
                if (in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png'])) {
                    if ($file['size'] > 10 * 1024 * 1024) {
                        throw new Exception("File $jenis terlalu besar (max 10MB)");
                    }
                } else {
                    if ($file['size'] > 2 * 1024 * 1024) {
                        throw new Exception("File $jenis terlalu besar (max 2MB)");
                    }
                }

                // Validate file name
                $original_name = basename($file['name']);
                if (empty($original_name)) {
                    throw new Exception("Nama file $jenis tidak valid");
                }

                // Generate secure filename
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                if (empty($ext)) {
                    $ext = $mime_type === 'application/pdf' ? 'pdf' : 'jpg';
                }

                $filename = preg_replace('/[^a-zA-Z0-9]/', '', $nisn) . '_' . $jenis . '_' . date('YmdHis') . '.' . $ext;
                $destination = $folder . $filename;

                // Ensure unique filename
                $counter = 1;
                while (file_exists($destination)) {
                    $filename = preg_replace('/[^a-zA-Z0-9]/', '', $nisn) . '_' . $jenis . '_' . date('YmdHis') . '_' . $counter . '.' . $ext;
                    $destination = $folder . $filename;
                    $counter++;
                }

                // Auto resize for image files (JPG/PNG)
                if (in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png'])) {
                    // Resize only if width or height > 1200px
                    list($width, $height) = getimagesize($file['tmp_name']);
                    $max_dim = 1200;
                    if ($width > $max_dim || $height > $max_dim) {
                        $ratio = min($max_dim / $width, $max_dim / $height);
                        $new_width = (int)($width * $ratio);
                        $new_height = (int)($height * $ratio);
                        if ($mime_type === 'image/png') {
                            $src_img = imagecreatefrompng($file['tmp_name']);
                            $dst_img = imagecreatetruecolor($new_width, $new_height);
                            // Preserve transparency
                            imagealphablending($dst_img, false);
                            imagesavealpha($dst_img, true);
                        } else {
                            $src_img = imagecreatefromjpeg($file['tmp_name']);
                            $dst_img = imagecreatetruecolor($new_width, $new_height);
                        }
                        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                        if ($mime_type === 'image/png') {
                            imagepng($dst_img, $destination, 6);
                        } else {
                            imagejpeg($dst_img, $destination, 85);
                        }
                        imagedestroy($src_img);
                        imagedestroy($dst_img);
                    } else {
                        // No resize needed, just move
                        if (!move_uploaded_file($file['tmp_name'], $destination)) {
                            throw new Exception("Gagal menyimpan file $jenis ke server");
                        }
                    }
                } else {
                    // PDF, just move
                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        throw new Exception("Gagal menyimpan file $jenis ke server");
                    }
                }

                // Set proper file permissions
                chmod($destination, 0644);

                if (function_exists('debug_log')) debug_log("File uploaded: $filename");
                return $filename;
            }

            // Check existing data
            $existing_data = null;
            $check_query = "SELECT * FROM berkas WHERE user_id = '" . mysqli_real_escape_string($connection, $user_id) . "'";
            $check_result = $connection->query($check_query);
            if ($check_result && $check_result->num_rows > 0) {
                $existing_data = $check_result->fetch_assoc();
            }

            // Validate required fields for new users - allow either KK or Ijazah
            if (!$existing_data) {
                $has_kk = $files['kk'] && $files['kk']['error'] === UPLOAD_ERR_OK;
                $has_ijazah = $files['ijazah'] && $files['ijazah']['error'] === UPLOAD_ERR_OK;

                if (!$has_kk && !$has_ijazah) {
                    echo 'Minimal upload salah satu: Kartu Keluarga atau Ijazah (untuk pendaftaran pertama)';
                    exit;
                }
            }

            // Check if any files uploaded
            $has_upload = false;
            foreach ($files as $file) {
                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $has_upload = true;
                    break;
                }
            }

            if (!$has_upload) {
                echo 'Pilih minimal satu file untuk diupload';
                exit;
            }

            // Process uploads
            $uploaded_files = [];
            foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                try {
                    $uploaded_files[$field] = uploadFile($files[$field], $field, $nisn, $folder);
                } catch (Exception $e) {
                    // If one file fails, clean up already uploaded files
                    foreach ($uploaded_files as $uploaded_file) {
                        if (!empty($uploaded_file) && file_exists($folder . $uploaded_file)) {
                            @unlink($folder . $uploaded_file);
                        }
                    }
                    throw $e;
                }
            }

            // Database operations with transaction
            $connection->autocommit(FALSE);

            try {
                if ($existing_data) {
                    // Update existing record
                    // Delete old files if new ones uploaded
                    foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                        if ($uploaded_files[$field] && !empty($existing_data[$field])) {
                            $old_file = $folder . $existing_data[$field];
                            if (file_exists($old_file)) {
                                @unlink($old_file);
                            }
                        }
                    }

                    // Build update query
                    $update_parts = [];
                    foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                        if ($uploaded_files[$field]) {
                            $update_parts[] = "$field = '" . mysqli_real_escape_string($connection, $uploaded_files[$field]) . "'";
                        }
                    }

                    if (!empty($update_parts)) {
                        // Jika ada perubahan file, reset validasi_berkas agar admin memvalidasi ulang
                        $update_parts[] = "validasi_berkas = ''";
                        $update_parts[] = "validasi_by = NULL";
                        $update_parts[] = "keterangan = ''";
                        // Also reset per-document validation for the changed fields
                        foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $f) {
                            if ($uploaded_files[$f]) {
                                $update_parts[] = "{$f}_valid = ''";
                                $update_parts[] = "{$f}_keterangan = ''";
                            }
                        }
                        $update_parts[] = "updated_at = NOW()";
                        $update_sql = "UPDATE berkas SET " . implode(', ', $update_parts) . " WHERE user_id = '" . mysqli_real_escape_string($connection, $user_id) . "'";

                        if (!$connection->query($update_sql)) {
                            throw new Exception('Gagal update database: ' . $connection->error);
                        }
                        if (function_exists('debug_log')) debug_log("berkas: reset validasi_berkas for user: $user_id");
                    }
                } else {
                    // Insert new record
                    $insert_sql = "INSERT INTO berkas (user_id, kk, akte, ijazah, kip, kks, kis, validasi_berkas, keterangan, validasi_by, created_at, updated_at) VALUES (
                    '" . mysqli_real_escape_string($connection, $user_id) . "', 
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kk']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['akte']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['ijazah']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kip']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kks']) . "',
                    '" . mysqli_real_escape_string($connection, $uploaded_files['kis']) . "',
                    '',
                    '',
                    NULL,
                    NOW(), 
                    NOW()
                )";

                    if (!$connection->query($insert_sql)) {
                        throw new Exception('Gagal simpan ke database: ' . $connection->error);
                    }
                }

                $connection->commit();
                $connection->autocommit(TRUE);

                if (function_exists('debug_log')) debug_log("Upload completed successfully for user: $user_id");
                echo 'success';
            } catch (Exception $e) {
                $connection->rollback();
                $connection->autocommit(TRUE);
                throw $e;
            }
        } catch (Exception $e) {
            if (function_exists('debug_log')) debug_log("Upload error: " . $e->getMessage());
            echo $e->getMessage();
        }
        break;

    /* ---------- GET DATA BERKAS ---------- */
    case 'get-data':
        try {
            ob_clean();

            $user_id = $data_user['user_id'] ?? '';
            if (empty($user_id)) {
                echo json_encode(['error' => 'User tidak valid']);
                exit;
            }

            $query = "SELECT * FROM berkas WHERE user_id = '$user_id'";
            $result = $connection->query($query);

            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                // Convert empty strings to null
                foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                    if (empty($data[$field])) {
                        $data[$field] = null;
                    }
                }
                echo json_encode($data);
            } else {
                echo json_encode([
                    'kk' => null,
                    'akte' => null,
                    'ijazah' => null,
                    'kip' => null,
                    'kks' => null,
                    'kis' => null
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    /* ---------- DELETE BERKAS ---------- */
    case 'delete':
        try {
            ob_clean();

            $user_id = $data_user['user_id'] ?? '';
            if (empty($user_id)) {
                echo 'User tidak valid';
                exit;
            }

            // Get existing files
            $query = "SELECT * FROM berkas WHERE user_id = '$user_id'";
            $result = $connection->query($query);

            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                // Delete physical files
                foreach (['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'] as $field) {
                    if (!empty($data[$field])) {
                        $file_path = $folder . $data[$field];
                        if (file_exists($file_path)) {
                            @unlink($file_path);
                        }
                    }
                }
            }

            // Delete database record
            $delete_sql = "DELETE FROM berkas WHERE user_id = '$user_id'";
            if ($connection->query($delete_sql)) {
                echo 'success';
            } else {
                echo 'Gagal menghapus data: ' . $connection->error;
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
        break;

    /* ---------- DELETE SINGLE FILE ---------- */
    case 'delete_one':
        try {
            ob_clean();

            $user_id = $data_user['user_id'] ?? '';
            $field = isset($_POST['field']) ? $_POST['field'] : '';
            $allowed = ['kk', 'akte', 'ijazah', 'kip', 'kks', 'kis'];

            if (empty($user_id) || empty($field) || !in_array($field, $allowed)) {
                echo 'Field tidak valid';
                exit;
            }

            $query = "SELECT $field FROM berkas WHERE user_id = '" . mysqli_real_escape_string($connection, $user_id) . "' LIMIT 1";
            $result = $connection->query($query);
            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                $filename = $data[$field];
                if (!empty($filename)) {
                    $file_path = $folder . $filename;
                    if (file_exists($file_path)) {
                        @unlink($file_path);
                    }
                }

                // Update DB: clear field and reset per-document validasi, overall validasi, validasi_by and keterangan
                $sql = "UPDATE berkas SET $field = '', {$field}_valid = '', {$field}_keterangan = '', validasi_berkas = '', validasi_by = NULL, keterangan = '' WHERE user_id = '" . mysqli_real_escape_string($connection, $user_id) . "'";
                if ($connection->query($sql)) {
                    echo 'success';
                } else {
                    echo 'Gagal mengupdate database: ' . $connection->error;
                }
            } else {
                echo 'Data berkas tidak ditemukan';
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
        break;

    default:
        ob_clean();
        echo 'Action tidak valid';
        break;
}
