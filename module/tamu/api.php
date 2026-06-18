<?php

/**
 * API Endpoint untuk Buku Tamu
 * Menangani request AJAX dari dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include config dan functions
require_once '../../library/config.php';
require_once '../../library/function.php';

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_guest':
            handleGetGuest();
            break;

        case 'checkout':
            handleCheckout();
            break;

        case 'update_guest':
            handleUpdateGuest();
            break;

        case 'delete_guest':
            handleDeleteGuest();
            break;

        case 'get_stats':
            handleGetStats();
            break;

        case 'search_guests':
            handleSearchGuests();
            break;

        default:
            throw new Exception('Action not found');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'API_ERROR'
    ]);
}

/**
 * Get guest detail by ID
 */
function handleGetGuest()
{
    global $connection;

    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID guest tidak valid');
    }

    $query = "SELECT * FROM buku_tamu WHERE id = ?";
    $stmt = $connection->prepare($query);

    if (!$stmt) {
        throw new Exception('Prepare statement failed');
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Data tamu tidak ditemukan');
    }

    $guest = $result->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'guest' => $guest
    ]);
}

/**
 * Checkout guest (set waktu_keluar and status)
 */
function handleCheckout()
{
    global $connection;

    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID guest tidak valid');
    }

    // Check if guest exists and is active
    $check_query = "SELECT id, guest_id, nama, status FROM buku_tamu WHERE id = ? AND status = 'Aktif'";
    $stmt = $connection->prepare($check_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Tamu tidak ditemukan atau sudah checkout');
    }

    $guest = $result->fetch_assoc();
    $stmt->close();

    // Update checkout time and status
    $current_time = date('H:i:s');
    $update_query = "UPDATE buku_tamu SET waktu_keluar = ?, status = 'Selesai', updated_at = NOW() WHERE id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param('si', $current_time, $id);

    if (!$stmt->execute()) {
        throw new Exception('Gagal melakukan checkout');
    }

    $stmt->close();

    // Log activity
    logGuestActivity($connection, $id, 'CHECKOUT', $guest['guest_id']);

    echo json_encode([
        'success' => true,
        'message' => 'Checkout berhasil',
        'data' => [
            'guest_id' => $guest['guest_id'],
            'nama' => $guest['nama'],
            'waktu_keluar' => $current_time
        ]
    ]);
}

/**
 * Update guest information
 */
function handleUpdateGuest()
{
    global $connection;

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID guest tidak valid');
    }

    // Validate required fields
    $required_fields = ['nama', 'instansi', 'keperluan'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field wajib diisi");
        }
    }

    // Sanitize input
    $nama = mysqli_real_escape_string($connection, trim($_POST['nama']));
    $instansi = mysqli_real_escape_string($connection, trim($_POST['instansi']));
    $telepon = mysqli_real_escape_string($connection, trim($_POST['telepon'] ?? ''));
    $keperluan = mysqli_real_escape_string($connection, trim($_POST['keperluan']));
    $keterangan = mysqli_real_escape_string($connection, trim($_POST['keterangan'] ?? ''));

    // Update query
    $update_query = "UPDATE buku_tamu SET nama = ?, instansi = ?, telepon = ?, keperluan = ?, keterangan = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param('sssssi', $nama, $instansi, $telepon, $keperluan, $keterangan, $id);

    if (!$stmt->execute()) {
        throw new Exception('Gagal mengupdate data');
    }

    $stmt->close();

    // Get guest_id for logging
    $guest_query = "SELECT guest_id FROM buku_tamu WHERE id = ?";
    $stmt = $connection->prepare($guest_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $guest = $result->fetch_assoc();
    $stmt->close();

    // Log activity
    if ($guest) {
        logGuestActivity($connection, $id, 'UPDATE', $guest['guest_id']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil diupdate'
    ]);
}

/**
 * Delete guest (soft delete by setting status to Batal)
 */
function handleDeleteGuest()
{
    global $connection;

    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID guest tidak valid');
    }

    // Get guest info before delete
    $guest_query = "SELECT guest_id, nama FROM buku_tamu WHERE id = ?";
    $stmt = $connection->prepare($guest_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Data tamu tidak ditemukan');
    }

    $guest = $result->fetch_assoc();
    $stmt->close();

    // Soft delete
    $delete_query = "UPDATE buku_tamu SET status = 'Batal', updated_at = NOW() WHERE id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        throw new Exception('Gagal menghapus data');
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil dihapus',
        'data' => [
            'guest_id' => $guest['guest_id'],
            'nama' => $guest['nama']
        ]
    ]);
}

/**
 * Get statistics
 */
function handleGetStats()
{
    global $connection;

    $stats = [
        'today' => getGuestCount($connection, 'TODAY'),
        'week' => getGuestCount($connection, 'WEEK'),
        'month' => getGuestCount($connection, 'MONTH'),
        'year' => getGuestCount($connection, 'YEAR'),
        'active' => getGuestCount($connection, 'ACTIVE'),
        'total' => getGuestCount($connection, 'TOTAL')
    ];

    // Get top keperluan
    $keperluan_query = "SELECT keperluan, COUNT(*) as count FROM buku_tamu WHERE status != 'Batal' GROUP BY keperluan ORDER BY count DESC LIMIT 5";
    $result = $connection->query($keperluan_query);
    $top_keperluan = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $top_keperluan[] = $row;
        }
    }

    // Get recent activity
    $activity_query = "SELECT g.*, l.activity, l.created_at as log_time 
                      FROM buku_tamu g 
                      LEFT JOIN buku_tamu_log l ON g.id = l.guest_table_id 
                      ORDER BY l.created_at DESC LIMIT 10";
    $result = $connection->query($activity_query);
    $recent_activity = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_activity[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'top_keperluan' => $top_keperluan,
        'recent_activity' => $recent_activity
    ]);
}

/**
 * Search guests with filters
 */
function handleSearchGuests()
{
    global $connection;

    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $keperluan = $_GET['keperluan'] ?? '';
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);

    // Build query
    $where_conditions = [];
    $params = [];
    $param_types = '';

    if (!empty($search)) {
        $where_conditions[] = "(nama LIKE ? OR instansi LIKE ? OR guest_id LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= 'sss';
    }

    if (!empty($status)) {
        $where_conditions[] = "status = ?";
        $params[] = $status;
        $param_types .= 's';
    }

    if (!empty($date_from)) {
        $where_conditions[] = "tanggal_kunjungan >= ?";
        $params[] = $date_from;
        $param_types .= 's';
    }

    if (!empty($date_to)) {
        $where_conditions[] = "tanggal_kunjungan <= ?";
        $params[] = $date_to;
        $param_types .= 's';
    }

    if (!empty($keperluan)) {
        $where_conditions[] = "keperluan = ?";
        $params[] = $keperluan;
        $param_types .= 's';
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Count total
    $count_query = "SELECT COUNT(*) as total FROM buku_tamu $where_clause";
    $stmt = $connection->prepare($count_query);

    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }

    $stmt->execute();
    $count_result = $stmt->get_result();
    $total = $count_result->fetch_assoc()['total'];
    $stmt->close();

    // Get data with pagination
    $data_query = "SELECT * FROM buku_tamu $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';

    $stmt = $connection->prepare($data_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $guests = [];
    while ($row = $result->fetch_assoc()) {
        $guests[] = $row;
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'total' => $total,
        'data' => $guests,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total_pages' => ceil($total / $limit),
            'current_page' => floor($offset / $limit) + 1
        ]
    ]);
}

/**
 * Helper function to get guest count
 */
function getGuestCount($connection, $period)
{
    $query = '';

    switch ($period) {
        case 'TODAY':
            $query = "SELECT COUNT(*) as count FROM buku_tamu WHERE DATE(tanggal_kunjungan) = CURDATE() AND status != 'Batal'";
            break;
        case 'WEEK':
            $query = "SELECT COUNT(*) as count FROM buku_tamu WHERE YEARWEEK(tanggal_kunjungan) = YEARWEEK(NOW()) AND status != 'Batal'";
            break;
        case 'MONTH':
            $query = "SELECT COUNT(*) as count FROM buku_tamu WHERE YEAR(tanggal_kunjungan) = YEAR(NOW()) AND MONTH(tanggal_kunjungan) = MONTH(NOW()) AND status != 'Batal'";
            break;
        case 'YEAR':
            $query = "SELECT COUNT(*) as count FROM buku_tamu WHERE YEAR(tanggal_kunjungan) = YEAR(NOW()) AND status != 'Batal'";
            break;
        case 'ACTIVE':
            $query = "SELECT COUNT(*) as count FROM buku_tamu WHERE status = 'Aktif'";
            break;
        case 'TOTAL':
            $query = "SELECT COUNT(*) as count FROM buku_tamu WHERE status != 'Batal'";
            break;
        default:
            return 0;
    }

    $result = $connection->query($query);
    if ($result) {
        return $result->fetch_assoc()['count'];
    }

    return 0;
}

/**
 * Log guest activity
 */
function logGuestActivity($connection, $guest_table_id, $activity, $guest_id)
{
    // Create log table if not exists
    $check_log_table = "SHOW TABLES LIKE 'buku_tamu_log'";
    $result = $connection->query($check_log_table);

    if ($result->num_rows == 0) {
        $create_log_table = "
            CREATE TABLE `buku_tamu_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `guest_table_id` int(11) NOT NULL,
                `guest_id` varchar(50) NOT NULL,
                `activity` enum('CHECKIN','CHECKOUT','UPDATE','DELETE') NOT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_guest_id` (`guest_id`),
                KEY `idx_activity` (`activity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $connection->query($create_log_table);
    }

    // Insert log
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $log_query = "INSERT INTO buku_tamu_log (guest_table_id, guest_id, activity, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($log_query);

    if ($stmt) {
        $stmt->bind_param('issss', $guest_table_id, $guest_id, $activity, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}
