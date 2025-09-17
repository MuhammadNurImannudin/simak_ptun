<?php
// api/main.php - Main API router
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/config.php';

// API Version
define('API_VERSION', '1.0');

// Get request info
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Remove 'api' from path
array_shift($path_parts);
$endpoint = $path_parts[0] ?? '';
$action = $path_parts[1] ?? '';
$id = $path_parts[2] ?? '';

// Authentication check for protected endpoints
$protected_endpoints = ['users', 'surat', 'reports', 'backup'];
if (in_array($endpoint, $protected_endpoints)) {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(401, 'Unauthorized', ['error' => 'Authentication required']);
    }
}

try {
    switch ($endpoint) {
        case '':
        case 'info':
            handleInfo();
            break;
            
        case 'auth':
            handleAuth($action);
            break;
            
        case 'notifications':
            handleNotifications($action, $id);
            break;
            
        case 'stats':
            handleStats($action);
            break;
            
        case 'search':
            handleSearch();
            break;
            
        case 'export':
            handleExport($action);
            break;
            
        case 'upload':
            handleUpload();
            break;
            
        case 'system':
            handleSystem($action);
            break;
            
        default:
            sendResponse(404, 'Not Found', ['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    sendResponse(500, 'Internal Server Error', ['error' => $e->getMessage()]);
}

function sendResponse($code, $status, $data = []) {
    http_response_code($code);
    echo json_encode([
        'success' => $code < 400,
        'status' => $status,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit;
}

function handleInfo() {
    global $db;
    
    $info = [
        'api_version' => API_VERSION,
        'app_name' => 'SIMAK PTUN API',
        'app_version' => '2.0',
        'server_time' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'GET /api/info' => 'API information',
            'POST /api/auth/login' => 'User login',
            'POST /api/auth/logout' => 'User logout',
            'GET /api/notifications' => 'Get user notifications',
            'GET /api/stats/dashboard' => 'Dashboard statistics',
            'GET /api/search' => 'Search surat',
            'GET /api/export/{type}' => 'Export data',
            'POST /api/upload' => 'Upload file',
            'GET /api/system/health' => 'System health check'
        ]
    ];
    
    // Add database status
    try {
        $db->fetch("SELECT 1");
        $info['database_status'] = 'connected';
    } catch (Exception $e) {
        $info['database_status'] = 'disconnected';
    }
    
    sendResponse(200, 'OK', $info);
}

function handleAuth($action) {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            if ($action === 'login') {
                handleLogin();
            } elseif ($action === 'logout') {
                handleLogout();
            } else {
                sendResponse(404, 'Not Found', ['error' => 'Action not found']);
            }
            break;
        default:
            sendResponse(405, 'Method Not Allowed');
    }
}

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendResponse(400, 'Bad Request', ['error' => 'Username and password required']);
    }
    
    global $db;
    
    try {
        $user = $db->fetch("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1", [$username, $username]);
        
        if (!$user || !password_verify($password, $user['password'])) {
            sendResponse(401, 'Unauthorized', ['error' => 'Invalid credentials']);
        }
        
        // Update last login
        $db->query("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?", [$user['id']]);
        
        // Create session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        
        sendResponse(200, 'Login successful', [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role' => $user['role']
            ],
            'session_id' => session_id()
        ]);
        
    } catch (Exception $e) {
        sendResponse(500, 'Internal Server Error', ['error' => 'Login failed']);
    }
}

function handleLogout() {
    session_start();
    session_destroy();
    sendResponse(200, 'Logout successful');
}

function handleNotifications($action, $id) {
    global $db;
    $user_id = $_SESSION['user_id'];
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'unread') {
                $notifications = $db->fetchAll("
                    SELECT * FROM notifications 
                    WHERE user_id = ? AND is_read = 0 
                    ORDER BY created_at DESC 
                    LIMIT 20
                ", [$user_id]);
                
                sendResponse(200, 'OK', $notifications);
            } else {
                $limit = (int)($_GET['limit'] ?? 20);
                $offset = (int)($_GET['offset'] ?? 0);
                
                $notifications = $db->fetchAll("
                    SELECT * FROM notifications 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT $limit OFFSET $offset
                ", [$user_id]);
                
                $total = $db->fetch("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?", [$user_id])['total'];
                
                sendResponse(200, 'OK', [
                    'notifications' => $notifications,
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ]);
            }
            break;
            
        case 'PUT':
            if ($action === 'read' && $id) {
                $db->query("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?", [$id, $user_id]);
                sendResponse(200, 'Notification marked as read');
            } elseif ($action === 'read-all') {
                $db->query("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0", [$user_id]);
                sendResponse(200, 'All notifications marked as read');
            } else {
                sendResponse(404, 'Not Found');
            }
            break;
            
        default:
            sendResponse(405, 'Method Not Allowed');
    }
}

function handleStats($action) {
    global $db;
    
    switch ($action) {
        case 'dashboard':
            $stats = $db->fetch("SELECT * FROM dashboard_stats");
            sendResponse(200, 'OK', $stats);
            break;
            
        case 'monthly':
            $year = (int)($_GET['year'] ?? date('Y'));
            $monthly = $db->fetchAll("
                SELECT * FROM monthly_report 
                WHERE tahun = ? 
                ORDER BY bulan
            ", [$year]);
            sendResponse(200, 'OK', $monthly);
            break;
            
        case 'user-activity':
            $user_id = $_SESSION['user_id'];
            $days = (int)($_GET['days'] ?? 30);
            
            $activity = $db->fetchAll("
                SELECT DATE(created_at) as date, COUNT(*) as count
                FROM activity_logs 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ", [$user_id, $days]);
            
            sendResponse(200, 'OK', $activity);
            break;
            
        default:
            sendResponse(404, 'Not Found', ['error' => 'Stats endpoint not found']);
    }
}

function handleSearch() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(405, 'Method Not Allowed');
    }
    
    global $db;
    
    $q = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all';
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    if (empty($q)) {
        sendResponse(400, 'Bad Request', ['error' => 'Search query required']);
    }
    
    $search_param = '%' . $q . '%';
    $results = [];
    
    if ($type === 'all' || $type === 'masuk') {
        $surat_masuk = $db->fetchAll("
            SELECT sm.*, u.nama_lengkap as input_by, 'masuk' as surat_type
            FROM surat_masuk sm
            JOIN users u ON sm.user_id = u.id
            WHERE sm.nomor_surat LIKE ? OR sm.pengirim LIKE ? OR sm.perihal LIKE ?
            ORDER BY sm.created_at DESC
            LIMIT $limit OFFSET $offset
        ", [$search_param, $search_param, $search_param]);
        
        $results = array_merge($results, $surat_masuk);
    }
    
    if ($type === 'all' || $type === 'keluar') {
        $surat_keluar = $db->fetchAll("
            SELECT sk.*, u.nama_lengkap as input_by, 'keluar' as surat_type
            FROM surat_keluar sk
            JOIN users u ON sk.user_id = u.id
            WHERE sk.nomor_surat LIKE ? OR sk.tujuan LIKE ? OR sk.perihal LIKE ?
            ORDER BY sk.created_at DESC
            LIMIT $limit OFFSET $offset
        ", [$search_param, $search_param, $search_param]);
        
        $results = array_merge($results, $surat_keluar);
    }
    
    sendResponse(200, 'OK', [
        'query' => $q,
        'type' => $type,
        'results' => $results,
        'count' => count($results)
    ]);
}

function handleExport($action) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(405, 'Method Not Allowed');
    }
    
    // Check admin permission for exports
    if ($_SESSION['role'] !== 'admin') {
        sendResponse(403, 'Forbidden', ['error' => 'Admin access required']);
    }
    
    switch ($action) {
        case 'surat-masuk':
            exportSuratMasuk();
            break;
        case 'surat-keluar':
            exportSuratKeluar();
            break;
        case 'users':
            exportUsers();
            break;
        case 'logs':
            exportLogs();
            break;
        default:
            sendResponse(404, 'Not Found', ['error' => 'Export type not found']);
    }
}

function exportSuratMasuk() {
    global $db;
    
    $format = $_GET['format'] ?? 'json';
    $from_date = $_GET['from'] ?? '';
    $to_date = $_GET['to'] ?? '';
    
    $where_conditions = ['1=1'];
    $params = [];
    
    if ($from_date) {
        $where_conditions[] = 'tanggal_diterima >= ?';
        $params[] = $from_date;
    }
    
    if ($to_date) {
        $where_conditions[] = 'tanggal_diterima <= ?';
        $params[] = $to_date;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $data = $db->fetchAll("
        SELECT sm.*, u.nama_lengkap as input_by
        FROM surat_masuk sm
        JOIN users u ON sm.user_id = u.id
        WHERE $where_clause
        ORDER BY sm.tanggal_diterima DESC
    ", $params);
    
    if ($format === 'csv') {
        exportAsCSV($data, 'surat_masuk_' . date('Y-m-d'));
    } else {
        sendResponse(200, 'OK', $data);
    }
}

function exportSuratKeluar() {
    global $db;
    
    $format = $_GET['format'] ?? 'json';
    $from_date = $_GET['from'] ?? '';
    $to_date = $_GET['to'] ?? '';
    
    $where_conditions = ['1=1'];
    $params = [];
    
    if ($from_date) {
        $where_conditions[] = 'tanggal_surat >= ?';
        $params[] = $from_date;
    }
    
    if ($to_date) {
        $where_conditions[] = 'tanggal_surat <= ?';
        $params[] = $to_date;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $data = $db->fetchAll("
        SELECT sk.*, u.nama_lengkap as input_by
        FROM surat_keluar sk
        JOIN users u ON sk.user_id = u.id
        WHERE $where_clause
        ORDER BY sk.tanggal_surat DESC
    ", $params);
    
    if ($format === 'csv') {
        exportAsCSV($data, 'surat_keluar_' . date('Y-m-d'));
    } else {
        sendResponse(200, 'OK', $data);
    }
}

function exportUsers() {
    global $db;
    
    $format = $_GET['format'] ?? 'json';
    
    $data = $db->fetchAll("
        SELECT id, username, email, nama_lengkap, role, is_active, last_login, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    
    if ($format === 'csv') {
        exportAsCSV($data, 'users_' . date('Y-m-d'));
    } else {
        sendResponse(200, 'OK', $data);
    }
}

function exportLogs() {
    global $db;
    
    $format = $_GET['format'] ?? 'json';
    $days = min((int)($_GET['days'] ?? 30), 90);
    
    $data = $db->fetchAll("
        SELECT al.*, u.nama_lengkap
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY al.created_at DESC
        LIMIT 1000
    ", [$days]);
    
    if ($format === 'csv') {
        exportAsCSV($data, 'activity_logs_' . date('Y-m-d'));
    } else {
        sendResponse(200, 'OK', $data);
    }
}

function exportAsCSV($data, $filename) {
    if (empty($data)) {
        sendResponse(200, 'OK', []);
        return;
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    fputcsv($output, array_keys($data[0]));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function handleUpload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, 'Method Not Allowed');
    }
    
    if (!isset($_FILES['file'])) {
        sendResponse(400, 'Bad Request', ['error' => 'No file uploaded']);
    }
    
    $file = $_FILES['file'];
    $table_name = $_POST['table_name'] ?? '';
    $record_id = (int)($_POST['record_id'] ?? 0);
    
    if (empty($table_name) || $record_id <= 0) {
        sendResponse(400, 'Bad Request', ['error' => 'table_name and record_id required']);
    }
    
    // Validate file
    $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        sendResponse(400, 'Bad Request', ['error' => 'File type not allowed']);
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        sendResponse(400, 'Bad Request', ['error' => 'File too large']);
    }
    
    // Upload file
    $upload_dir = '../assets/uploads/' . $table_name . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = time() . '_' . uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Save to database
        global $db;
        $db->query("
            INSERT INTO file_attachments (table_name, record_id, file_name, original_name, file_type, file_size, file_path, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $table_name,
            $record_id,
            $filename,
            $file['name'],
            $file['type'],
            $file['size'],
            $filepath,
            $_SESSION['user_id']
        ]);
        
        sendResponse(200, 'File uploaded successfully', [
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'path' => $filepath
        ]);
    } else {
        sendResponse(500, 'Upload failed', ['error' => 'Could not save file']);
    }
}

function handleSystem($action) {
    // Check admin permission
    if ($_SESSION['role'] !== 'admin') {
        sendResponse(403, 'Forbidden', ['error' => 'Admin access required']);
    }
    
    switch ($action) {
        case 'health':
            systemHealthCheck();
            break;
        case 'info':
            systemInfo();
            break;
        case 'logs':
            systemLogs();
            break;
        case 'cleanup':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                systemCleanup();
            } else {
                sendResponse(405, 'Method Not Allowed');
            }
            break;
        default:
            sendResponse(404, 'Not Found', ['error' => 'System endpoint not found']);
    }
}

function systemHealthCheck() {
    global $db;
    
    $health = [
        'status' => 'healthy',
        'checks' => [],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Database check
    try {
        $db->fetch("SELECT 1");
        $health['checks']['database'] = ['status' => 'ok', 'message' => 'Connected'];
    } catch (Exception $e) {
        $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        $health['status'] = 'unhealthy';
    }
    
    // Disk space check
    $free_space = disk_free_space('../');
    $total_space = disk_total_space('../');
    $used_percentage = (($total_space - $free_space) / $total_space) * 100;
    
    if ($used_percentage > 90) {
        $health['checks']['disk_space'] = ['status' => 'critical', 'message' => 'Disk space > 90% used'];
        $health['status'] = 'unhealthy';
    } elseif ($used_percentage > 80) {
        $health['checks']['disk_space'] = ['status' => 'warning', 'message' => 'Disk space > 80% used'];
    } else {
        $health['checks']['disk_space'] = ['status' => 'ok', 'message' => 'Sufficient disk space'];
    }
    
    // Upload directory check
    $upload_dirs = ['../assets/uploads/surat-masuk/', '../assets/uploads/surat-keluar/'];
    $all_writable = true;
    
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir) || !is_writable($dir)) {
            $all_writable = false;
            break;
        }
    }
    
    $health['checks']['upload_dirs'] = $all_writable ? 
        ['status' => 'ok', 'message' => 'Upload directories writable'] :
        ['status' => 'error', 'message' => 'Some upload directories not writable'];
    
    if (!$all_writable) {
        $health['status'] = 'unhealthy';
    }
    
    sendResponse(200, 'OK', $health);
}

function systemInfo() {
    $info = [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'disk_free_space' => formatBytes(disk_free_space('../')),
        'disk_total_space' => formatBytes(disk_total_space('../')),
        'loaded_extensions' => get_loaded_extensions()
    ];
    
    sendResponse(200, 'OK', $info);
}

function systemLogs() {
    $log_files = [];
    $log_dir = '../logs/';
    
    if (is_dir($log_dir)) {
        $files = glob($log_dir . '*.log');
        foreach ($files as $file) {
            $log_files[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'lines' => countFileLines($file)
            ];
        }
    }
    
    sendResponse(200, 'OK', $log_files);
}

function systemCleanup() {
    $cleaned = [];
    
    // Clean old sessions
    $session_path = session_save_path();
    if ($session_path && is_dir($session_path)) {
        $old_sessions = glob($session_path . '/sess_*');
        $count = 0;
        foreach ($old_sessions as $session_file) {
            if (filemtime($session_file) < time() - 3600) { // 1 hour old
                unlink($session_file);
                $count++;
            }
        }
        $cleaned['old_sessions'] = $count;
    }
    
    // Clean old temporary files
    $temp_dir = '../temp/';
    if (is_dir($temp_dir)) {
        $temp_files = glob($temp_dir . '*');
        $count = 0;
        foreach ($temp_files as $temp_file) {
            if (filemtime($temp_file) < time() - 86400) { // 1 day old
                if (is_file($temp_file)) {
                    unlink($temp_file);
                } elseif (is_dir($temp_file)) {
                    rmdir($temp_file);
                }
                $count++;
            }
        }
        $cleaned['temp_files'] = $count;
    }
    
    // Clean old logs (keep last 30 days)
    global $db;
    $result = $db->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $cleaned['old_activity_logs'] = $result;
    
    sendResponse(200, 'Cleanup completed', $cleaned);
}

// Helper functions
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function countFileLines($file) {
    $count = 0;
    $handle = fopen($file, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $count++;
        }
        fclose($handle);
    }
    return $count;
}
?>
