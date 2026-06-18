<?php
/**
 * SAE API - Data Synchronization Endpoints
 * Endpoints untuk menarik data admin, kelas, dan user dari SAE ke sistem lain (PKL)
 */

// Include required files
require_once __DIR__ . '/../core/ApiAuth.php';
require_once __DIR__ . '/../core/ApiResponse.php';
require_once __DIR__ . '/../core/ApiValidator.php';
require_once __DIR__ . '/../core/ApiLogger.php';
require_once __DIR__ . '/../api_config.php';  // Load API key
require_once __DIR__ . '/../../library/config.php';

// Set response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Initialize components
    $auth = new ApiAuth();
    $response = new ApiResponse();
    $validator = new ApiValidator();
    $logger = new ApiLogger();
    
    // Get request method and endpoint
    $method = $_SERVER['REQUEST_METHOD'];
    $path = isset($_GET['path']) ? $_GET['path'] : '';
    
    // Log request
    $logger->logRequest($method, $path, $_GET);
    
    // Authenticate request
    if (!ApiAuth::validateApiKey()) {
        ApiResponse::send(ApiResponse::unauthorized('Invalid or missing API key'));
        exit();
    }
    
    // Route based on path
    switch ($path) {
        case 'admin':
            handleAdminSync($connection, $response, $validator, $_GET);
            break;
            
        case 'kelas':
            handleKelasSync($connection, $response, $validator, $_GET);
            break;
            
        case 'user':
        case 'siswa':
            handleUserSync($connection, $response, $validator, $_GET);
            break;
            
        case 'all':
            handleAllDataSync($connection, $response, $validator, $_GET);
            break;
            
        case 'api-info':
            handleApiInfo($connection, $response, $validator, $_GET);
            break;
            
        default:
            ApiResponse::send(ApiResponse::error('Invalid endpoint. Available endpoints: admin, kelas, user, all, api-info', null, 400));
            break;
    }
    
} catch (Exception $e) {
    $logger->logError('API Error: ' . $e->getMessage());
    ApiResponse::send(ApiResponse::error('Internal server error', null, 500));
}

/**
 * Handle admin data synchronization
 */
function handleAdminSync($connection, $response, $validator, $params) {
    try {
        // Build query
        $query = "SELECT 
            admin_id,
            fullname,
            gelar_depan,
            gelar_belakang,
            username,
            password,
            phone,
            email,
            avatar,
            registrasi_date,
            tanggal_login,
            status,
            level_id,
            ptk_id,
            pengguna_id,
            nuptk,
            nik,
            jenis_kelamin,
            tempat_lahir,
            tanggal_lahir,
            agama_id_str as agama,
            jenis_ptk_id_str as jenis_ptk,
            jabatan_ptk_id_str as jabatan,
            status_kepegawaian_id_str as status_kepegawaian,
            nip,
            pendidikan_terakhir,
            bidang_studi_terakhir,
            pangkat_golongan_terakhir,
            sync_status,
            last_sync_at,
            active,
            tugas_tambahan
        FROM admin WHERE 1=1";
        
        $countQuery = "SELECT COUNT(*) as total FROM admin WHERE 1=1";
        
        // Add filters
        $whereClause = "";
        $bindParams = [];
        $bindTypes = "";
        
        // Filter by status
        if (isset($params['status']) && !empty($params['status'])) {
            $whereClause .= " AND status = ?";
            $bindParams[] = $params['status'];
            $bindTypes .= "s";
        }
        
        // Filter by level
        if (isset($params['level_id']) && !empty($params['level_id'])) {
            $whereClause .= " AND level_id = ?";
            $bindParams[] = intval($params['level_id']);
            $bindTypes .= "i";
        }
        
        // Filter by active status
        if (isset($params['active']) && !empty($params['active'])) {
            $whereClause .= " AND active = ?";
            $bindParams[] = $params['active'];
            $bindTypes .= "s";
        }
        
        // Filter by sync_status
        if (isset($params['sync_status']) && !empty($params['sync_status'])) {
            $whereClause .= " AND sync_status = ?";
            $bindParams[] = $params['sync_status'];
            $bindTypes .= "s";
        }
        
        // Add where clause to queries
        $query .= $whereClause;
        $countQuery .= $whereClause;
        
        // Add ordering and pagination
        $query .= " ORDER BY admin_id ASC";
        
        // Pagination
        $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
        $limit = isset($params['limit']) ? min(1000, max(1, intval($params['limit']))) : 100;
        $offset = ($page - 1) * $limit;
        
        $query .= " LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        $bindTypes .= "ii";
        
        // Get total count
        $countStmt = $connection->prepare($countQuery);
        if (!empty($bindParams) && count($bindParams) > 2) {
            $countBindParams = array_slice($bindParams, 0, -2);
            $countBindTypes = substr($bindTypes, 0, -2);
            if (!empty($countBindParams)) {
                $countStmt->bind_param($countBindTypes, ...$countBindParams);
            }
        }
        $countStmt->execute();
        $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Execute main query
        $stmt = $connection->prepare($query);
        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            // Include password for sync purposes but ensure it's handled securely
            // Keep password for API sync, let receiving system handle it
            $admins[] = $row;
        }
        
        ApiResponse::send(ApiResponse::success([
            'data' => $admins,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($totalCount),
                'total_pages' => ceil($totalCount / $limit)
            ],
            'filters_applied' => array_intersect_key($params, array_flip(['status', 'level_id', 'active', 'sync_status']))
        ], 'Admin data retrieved successfully'));
        
    } catch (Exception $e) {
        ApiResponse::send(ApiResponse::error('Failed to retrieve admin data: ' . $e->getMessage(), null, 500));
    }
}

/**
 * Handle kelas data synchronization
 */
function handleKelasSync($connection, $response, $validator, $params) {
    try {
        // Build query
        $query = "SELECT 
            k.kelas_id,
            k.nama_kelas,
            k.jurusan_id,
            j.nama_jurusan,
            j.kode_jurusan,
            k.rombongan_belajar_id,
            k.wali_kelas_ptk_id,
            k.wali_kelas_nama,
            k.tingkat_pendidikan_id,
            k.tingkat_pendidikan_str,
            k.semester_id,
            k.jenis_rombel,
            k.jenis_rombel_str,
            k.kurikulum_id,
            k.kurikulum_str,
            k.id_ruang,
            k.nama_ruang,
            k.moving_class,
            k.sync_jurusan_id,
            k.sync_jurusan_str,
            k.total_anggota,
            k.total_pembelajaran,
            k.sync_status,
            k.last_sync_at,
            k.created_from_sync
        FROM kelas k
        LEFT JOIN jurusan j ON k.jurusan_id = j.jurusan_id
        WHERE 1=1";
        
        $countQuery = "SELECT COUNT(*) as total FROM kelas k WHERE 1=1";
        
        // Add filters
        $whereClause = "";
        $bindParams = [];
        $bindTypes = "";
        
        // Filter by jurusan
        if (isset($params['jurusan_id']) && !empty($params['jurusan_id'])) {
            $whereClause .= " AND k.jurusan_id = ?";
            $bindParams[] = intval($params['jurusan_id']);
            $bindTypes .= "i";
        }
        
        // Filter by sync_status
        if (isset($params['sync_status']) && !empty($params['sync_status'])) {
            $whereClause .= " AND k.sync_status = ?";
            $bindParams[] = $params['sync_status'];
            $bindTypes .= "s";
        }
        
        // Filter by tingkat
        if (isset($params['tingkat']) && !empty($params['tingkat'])) {
            $whereClause .= " AND k.tingkat_pendidikan_id = ?";
            $bindParams[] = $params['tingkat'];
            $bindTypes .= "s";
        }
        
        // Add where clause to queries
        $query .= $whereClause;
        $countQuery .= $whereClause;
        
        // Add ordering and pagination
        $query .= " ORDER BY k.kelas_id ASC";
        
        // Pagination
        $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
        $limit = isset($params['limit']) ? min(1000, max(1, intval($params['limit']))) : 100;
        $offset = ($page - 1) * $limit;
        
        $query .= " LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        $bindTypes .= "ii";
        
        // Get total count
        $countStmt = $connection->prepare($countQuery);
        if (!empty($bindParams) && count($bindParams) > 2) {
            $countBindParams = array_slice($bindParams, 0, -2);
            $countBindTypes = substr($bindTypes, 0, -2);
            if (!empty($countBindParams)) {
                $countStmt->bind_param($countBindTypes, ...$countBindParams);
            }
        }
        $countStmt->execute();
        $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Execute main query
        $stmt = $connection->prepare($query);
        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $kelas = [];
        while ($row = $result->fetch_assoc()) {
            $kelas[] = $row;
        }
        
        ApiResponse::send(ApiResponse::success([
            'data' => $kelas,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($totalCount),
                'total_pages' => ceil($totalCount / $limit)
            ],
            'filters_applied' => array_intersect_key($params, array_flip(['jurusan_id', 'sync_status', 'tingkat']))
        ], 'Kelas data retrieved successfully'));
        
    } catch (Exception $e) {
        ApiResponse::send(ApiResponse::error('Failed to retrieve kelas data: ' . $e->getMessage(), null, 500));
    }
}

/**
 * Handle user/siswa data synchronization
 */
function handleUserSync($connection, $response, $validator, $params) {
    try {
        // Build query
        $query = "SELECT 
            u.user_id,
            u.no_kk,
            u.nik,
            u.nipd,
            u.nisn,
            u.rfid,
            u.jurusan_id,
            j.nama_jurusan,
            j.kode_jurusan,
            u.nama_lengkap,
            u.sekolah_asal,
            u.tempat_lahir,
            u.tanggal_lahir,
            u.agama,
            u.jenis_kelamin,
            u.status_keluarga,
            u.tingkat,
            u.kelas,
            u.wali_kelas,
            u.diterima_dikelas,
            u.diterima_tanggal,
            u.nik_ayah,
            u.nama_ayah,
            u.pekerjaan_ayah,
            u.nik_ibu,
            u.nama_ibu,
            u.nama_wali,
            u.alamat_wali,
            u.telp_wali,
            u.pekerjaan_wali,
            u.pekerjaan_ibu,
            u.alamat,
            u.rt,
            u.rw,
            u.desa,
            u.kecamatan,
            u.kodepos,
            u.email,
            u.telp,
            u.telp_ortu,
            u.anak_ke,
            u.avatar,
            u.date as tanggal_daftar,
            u.status,
            u.konfirmasi,
            u.koordinator,
            u.rombongan_belajar_id,
            u.kelas_nama,
            u.rombel_sync_status,
            u.rombel_last_sync,
            u.whatsapp_verified,
            u.whatsapp_verified_at
        FROM user u
        LEFT JOIN jurusan j ON u.jurusan_id = j.jurusan_id
        WHERE 1=1";
        
        $countQuery = "SELECT COUNT(*) as total FROM user u WHERE 1=1";
        
        // Add filters
        $whereClause = "";
        $bindParams = [];
        $bindTypes = "";
        
        // Filter by status
        if (isset($params['status']) && !empty($params['status'])) {
            $whereClause .= " AND u.status = ?";
            $bindParams[] = $params['status'];
            $bindTypes .= "s";
        }
        
        // Filter by jurusan
        if (isset($params['jurusan_id']) && !empty($params['jurusan_id'])) {
            $whereClause .= " AND u.jurusan_id = ?";
            $bindParams[] = intval($params['jurusan_id']);
            $bindTypes .= "i";
        }
        
        // Filter by kelas
        if (isset($params['kelas']) && !empty($params['kelas'])) {
            $whereClause .= " AND u.kelas = ?";
            $bindParams[] = $params['kelas'];
            $bindTypes .= "s";
        }

        // Filter by tingkat
        if (isset($params['tingkat']) && $params['tingkat'] !== '') {
            $whereClause .= " AND u.tingkat = ?";
            $bindParams[] = $params['tingkat'];
            $bindTypes .= "s";
        }
        
        // Filter by rombel sync status
        if (isset($params['rombel_sync_status']) && !empty($params['rombel_sync_status'])) {
            $whereClause .= " AND u.rombel_sync_status = ?";
            $bindParams[] = $params['rombel_sync_status'];
            $bindTypes .= "s";
        }
        
        // Filter by konfirmasi
        if (isset($params['konfirmasi']) && !empty($params['konfirmasi'])) {
            $whereClause .= " AND u.konfirmasi = ?";
            $bindParams[] = $params['konfirmasi'];
            $bindTypes .= "s";
        }
        
        // Add where clause to queries
        $query .= $whereClause;
        $countQuery .= $whereClause;
        
        // Add ordering and pagination
        $query .= " ORDER BY u.user_id ASC";
        
        // Pagination
        $page = isset($params['page']) ? max(1, intval($params['page'])) : 1;
        $limit = isset($params['limit']) ? min(1000, max(1, intval($params['limit']))) : 100;
        $offset = ($page - 1) * $limit;
        
        $query .= " LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        $bindTypes .= "ii";
        
        // Get total count
        $countStmt = $connection->prepare($countQuery);
        if (!empty($bindParams) && count($bindParams) > 2) {
            $countBindParams = array_slice($bindParams, 0, -2);
            $countBindTypes = substr($bindTypes, 0, -2);
            if (!empty($countBindParams)) {
                $countStmt->bind_param($countBindTypes, ...$countBindParams);
            }
        }
        $countStmt->execute();
        $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Execute main query
        $stmt = $connection->prepare($query);
        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            // Don't expose password in API
            unset($row['password']);
            $users[] = $row;
        }
        
        ApiResponse::send(ApiResponse::success([
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($totalCount),
                'total_pages' => ceil($totalCount / $limit)
            ],
            'filters_applied' => array_intersect_key($params, array_flip(['status', 'jurusan_id', 'kelas', 'tingkat', 'rombel_sync_status', 'konfirmasi']))
        ], 'User data retrieved successfully'));
        
    } catch (Exception $e) {
        ApiResponse::send(ApiResponse::error('Failed to retrieve user data: ' . $e->getMessage(), null, 500));
    }
}

/**
 * Handle all data synchronization (combined)
 */
function handleAllDataSync($connection, $response, $validator, $params) {
    try {
        $data = [];
        
        // Get admin data
        ob_start();
        handleAdminSync($connection, new ApiResponse(), $validator, array_merge($params, ['limit' => 50]));
        $adminOutput = ob_get_clean();
        $adminData = json_decode($adminOutput, true);
        if ($adminData && $adminData['success']) {
            $data['admin'] = $adminData['data'];
        }
        
        // Get kelas data
        ob_start();
        handleKelasSync($connection, new ApiResponse(), $validator, array_merge($params, ['limit' => 50]));
        $kelasOutput = ob_get_clean();
        $kelasData = json_decode($kelasOutput, true);
        if ($kelasData && $kelasData['success']) {
            $data['kelas'] = $kelasData['data'];
        }
        
        // Get user data (limited for performance)
        ob_start();
        handleUserSync($connection, new ApiResponse(), $validator, array_merge($params, ['limit' => 100]));
        $userOutput = ob_get_clean();
        $userData = json_decode($userOutput, true);
        if ($userData && $userData['success']) {
            $data['users'] = $userData['data'];
        }
        
        ApiResponse::send(ApiResponse::success($data, 'All data retrieved successfully'));
        
    } catch (Exception $e) {
        ApiResponse::send(ApiResponse::error('Failed to retrieve all data: ' . $e->getMessage(), null, 500));
    }
}
/**
 * Handle API info and configuration retrieval
 */
function handleApiInfo($connection, $response, $validator, $params) {
    try {
        $info_type = isset($params['type']) ? $params['type'] : 'basic';
        
        switch ($info_type) {
            case 'status':
                // Return API status information
                ApiResponse::send(ApiResponse::success([
                    'api_version' => '2.0',
                    'server_time' => date('Y-m-d H:i:s'),
                    'timezone' => date_default_timezone_get(),
                    'status' => 'active',
                    'endpoints' => ['admin', 'kelas', 'user', 'all', 'api-info'],
                    'max_limit' => 1000,
                    'default_limit' => 100
                ], 'API status information'));
                break;
                
            case 'key-info':
                // Return API key information (not the actual key, just metadata)
                if (!defined('SAE_API_KEY')) {
                    ApiResponse::send(ApiResponse::error('API key not configured', null, 500));
                    break;
                }
                
                $api_key = SAE_API_KEY;
                
                // Extract timestamp from API key if it follows SAE_YYYYMMDDHHMMSS_hex format
                $key_date = null;
                if (preg_match('/^SAE_(\d{14})_/', $api_key, $matches)) {
                    $timestamp = $matches[1];
                    $key_date = DateTime::createFromFormat('YmdHis', $timestamp);
                    $key_date = $key_date ? $key_date->format('Y-m-d H:i:s') : null;
                }
                
                ApiResponse::send(ApiResponse::success([
                    'key_length' => strlen($api_key),
                    'key_prefix' => substr($api_key, 0, 10) . '...',
                    'key_suffix' => '...' . substr($api_key, -10),
                    'key_format' => preg_match('/^SAE_\d{14}_[a-f0-9]{32}$/', $api_key) ? 'standard' : 'legacy',
                    'generated_date' => $key_date,
                    'is_valid' => true
                ], 'API key information'));
                break;
                
            case 'current-key':
                // Return current API key for authorized systems only
                // This requires special authorization parameter
                $auth_token = isset($params['auth_token']) ? $params['auth_token'] : '';
                
                // Simple authorization check - in production, use more secure method
                $expected_token = hash('sha256', 'PKL_AUTH_' . date('Y-m-d') . '_' . SAE_API_KEY);
                
                if (empty($auth_token) || $auth_token !== $expected_token) {
                    ApiResponse::send(ApiResponse::error('Insufficient privileges for key retrieval', null, 403));
                    break;
                }
                
                if (!defined('SAE_API_KEY')) {
                    ApiResponse::send(ApiResponse::error('API key not configured', null, 500));
                    break;
                }
                
                ApiResponse::send(ApiResponse::success([
                    'api_key' => SAE_API_KEY,
                    'retrieved_at' => date('Y-m-d H:i:s'),
                    'warning' => 'Keep this key secure and do not log it'
                ], 'Current API key retrieved successfully'));
                break;
                
            default:
                // Basic API information
                ApiResponse::send(ApiResponse::success([
                    'system' => 'SAE v5 API',
                    'version' => '2.0',
                    'description' => 'School Administration & Attendance System API',
                    'available_info_types' => ['basic', 'status', 'key-info', 'current-key'],
                    'server_time' => date('Y-m-d H:i:s')
                ], 'API information'));
                break;
        }
        
    } catch (Exception $e) {
        ApiResponse::send(ApiResponse::error('Failed to retrieve API info: ' . $e->getMessage(), null, 500));
    }
}
?>