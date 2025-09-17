<?php
// includes/functions.php - Enhanced functions library with redeclare protection
// =====================================================

// Prevent direct access
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

// Database connection helper
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        global $db;
        
        if (isset($db) && $db) {
            return $db;
        }
        
        // Try to establish connection
        if (defined('DB_HOST')) {
            try {
                $db = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                return $db;
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                return null;
            }
        }
        
        return null;
    }
}

// Execute query with fallback
if (!function_exists('executeQuery')) {
    function executeQuery($query, $params = []) {
        $db = getDatabaseConnection();
        
        if (!$db) return false;
        
        try {
            if ($db instanceof PDO) {
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                return $stmt;
            }
            
            if (method_exists($db, 'query')) {
                return $db->query($query, $params);
            }
            
            if (method_exists($db, 'fetch') && empty($params)) {
                return $db;
            }
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
        
        return false;
    }
}

// Fetch single result
if (!function_exists('fetchSingle')) {
    function fetchSingle($query, $params = []) {
        $db = getDatabaseConnection();
        
        if (!$db) return null;
        
        try {
            if (method_exists($db, 'fetch')) {
                return $db->fetch($query, $params);
            }
            
            $result = executeQuery($query, $params);
            if (!$result) return null;
            
            if ($result instanceof PDOStatement) {
                return $result->fetch();
            }
        } catch (Exception $e) {
            error_log("Database fetch error: " . $e->getMessage());
        }
        
        return null;
    }
}

// Fetch all results
if (!function_exists('fetchAll')) {
    function fetchAll($query, $params = []) {
        $db = getDatabaseConnection();
        
        if (!$db) return [];
        
        try {
            if (method_exists($db, 'fetchAll')) {
                return $db->fetchAll($query, $params);
            }
            
            $result = executeQuery($query, $params);
            if (!$result) return [];
            
            if ($result instanceof PDOStatement) {
                return $result->fetchAll();
            }
        } catch (Exception $e) {
            error_log("Database fetchAll error: " . $e->getMessage());
        }
        
        return [];
    }
}

// Get total surat masuk by status
if (!function_exists('getTotalSuratMasuk')) {
    function getTotalSuratMasuk($status = '') {
        try {
            if ($status) {
                $result = fetchSingle("SELECT COUNT(*) as total FROM surat_masuk WHERE status = ?", [$status]);
            } else {
                $result = fetchSingle("SELECT COUNT(*) as total FROM surat_masuk");
            }
            
            return $result ? ($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            error_log("getTotalSuratMasuk error: " . $e->getMessage());
            return 0;
        }
    }
}

// Get total surat keluar by status
if (!function_exists('getTotalSuratKeluar')) {
    function getTotalSuratKeluar($status = '') {
        try {
            if ($status) {
                $result = fetchSingle("SELECT COUNT(*) as total FROM surat_keluar WHERE status = ?", [$status]);
            } else {
                $result = fetchSingle("SELECT COUNT(*) as total FROM surat_keluar");
            }
            
            return $result ? ($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            error_log("getTotalSuratKeluar error: " . $e->getMessage());
            return 0;
        }
    }
}

// Sanitize input
if (!function_exists('sanitize')) {
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Flash message functions
if (!function_exists('setFlashMessage')) {
    function setFlashMessage($type, $message) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

if (!function_exists('hasFlashMessage')) {
    function hasFlashMessage() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['flash_message']);
    }
}

// Redirect function - only define if not already defined
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// Login check functions
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: auth/login.php');
            exit();
        }
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
}

// Format tanggal Indonesia
if (!function_exists('formatTanggal')) {
    function formatTanggal($date, $format = 'full') {
        if (empty($date)) return '-';
        
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        $timestamp = strtotime($date);
        
        if ($format === 'short') {
            return date('j M Y', $timestamp);
        }
        
        return date('j', $timestamp) . ' ' . $bulan[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }
}

// Time ago function
if (!function_exists('timeAgo')) {
    function timeAgo($datetime, $full = false) {
        if (empty($datetime)) return 'Tidak diketahui';
        
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'tahun',
            'm' => 'bulan', 
            'w' => 'minggu',
            'd' => 'hari',
            'h' => 'jam',
            'i' => 'menit',
            's' => 'detik',
        );
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v;
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
    }
}

// Format file size
if (!function_exists('formatFileSize')) {
    function formatFileSize($size) {
        if ($size === 0) return '0 B';
        
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $base = log($size, 1024);
        return round(pow(1024, $base - floor($base)), 2) . ' ' . $units[floor($base)];
    }
}

// Generate random string
if (!function_exists('generateRandomString')) {
    function generateRandomString($length = 10) {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }
}

// Generate nomor surat otomatis
if (!function_exists('generateNomorSurat')) {
    function generateNomorSurat($jenis = 'masuk') {
        $tahun = date('Y');
        $bulan = date('m');
        
        if ($jenis === 'masuk') {
            $prefix = 'SM';
            $table = 'surat_masuk';
        } else {
            $prefix = 'SK';
            $table = 'surat_keluar';
        }
        
        // Get last number for this month/year
        $result = fetchSingle("
            SELECT nomor_surat FROM {$table} 
            WHERE nomor_surat LIKE ? 
            ORDER BY id DESC LIMIT 1
        ", ["%/{$prefix}/{$bulan}/{$tahun}"]);
        
        $nomor_urut = 1;
        if ($result) {
            // Extract number from format: 001/SM/09/2024
            $parts = explode('/', $result['nomor_surat']);
            if (count($parts) >= 1) {
                $nomor_urut = intval($parts[0]) + 1;
            }
        }
        
        return sprintf('%03d/%s/%02d/%s', $nomor_urut, $prefix, $bulan, $tahun);
    }
}

// Status badge helper
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status, $type = 'surat_masuk') {
        $badges = [
            'surat_masuk' => [
                'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
                'diproses' => '<span class="badge bg-info">Diproses</span>',
                'selesai' => '<span class="badge bg-success">Selesai</span>'
            ],
            'surat_keluar' => [
                'draft' => '<span class="badge bg-secondary">Draft</span>',
                'terkirim' => '<span class="badge bg-success">Terkirim</span>',
                'arsip' => '<span class="badge bg-info">Arsip</span>'
            ]
        ];
        
        return $badges[$type][$status] ?? '<span class="badge bg-light text-dark">' . ucfirst($status) . '</span>';
    }
}

// File upload helper
if (!function_exists('handleFileUpload')) {
    function handleFileUpload($file, $allowed_types = ['pdf', 'doc', 'docx'], $max_size = 10485760) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Tidak ada file yang diupload'];
        }
        
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);
        
        // Check file type
        if (!in_array($extension, $allowed_types)) {
            return ['success' => false, 'message' => 'Tipe file tidak diizinkan. Gunakan: ' . implode(', ', $allowed_types)];
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal ' . formatFileSize($max_size)];
        }
        
        // Generate unique filename
        $new_filename = date('YmdHis') . '_' . generateRandomString(8) . '.' . $extension;
        $upload_path = 'assets/uploads/' . $new_filename;
        
        // Create upload directory if not exists
        if (!file_exists('assets/uploads/')) {
            mkdir('assets/uploads/', 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return [
                'success' => true, 
                'filename' => $new_filename,
                'path' => $upload_path,
                'size' => $file['size']
            ];
        }
        
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// Log activity
if (!function_exists('logActivity')) {
    function logActivity($action, $description, $user_id = null) {
        if (!$user_id && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        
        if (!$user_id) return;
        
        try {
            executeQuery("
                INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ", [
                $user_id, 
                $action, 
                $description, 
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}

// Check user permission
if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        if (!isset($_SESSION['user_role'])) return false;
        
        $permissions = [
            'admin' => ['*'], // All permissions
            'user' => ['read', 'create'],
            'guest' => ['read']
        ];
        
        $user_permissions = $permissions[$_SESSION['user_role']] ?? [];
        
        return in_array('*', $user_permissions) || in_array($permission, $user_permissions);
    }
}

// Pagination helper
if (!function_exists('getPaginationData')) {
    function getPaginationData($total_records, $current_page, $records_per_page = 10) {
        $total_pages = ceil($total_records / $records_per_page);
        $offset = ($current_page - 1) * $records_per_page;
        
        return [
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'records_per_page' => $records_per_page,
            'offset' => $offset,
            'has_prev' => $current_page > 1,
            'has_next' => $current_page < $total_pages,
            'prev_page' => max(1, $current_page - 1),
            'next_page' => min($total_pages, $current_page + 1)
        ];
    }
}

// Search helper
if (!function_exists('buildSearchQuery')) {
    function buildSearchQuery($base_query, $search_fields, $search_term) {
        if (empty($search_term)) {
            return [$base_query, []];
        }
        
        $search_conditions = [];
        $params = [];
        $search_term = '%' . $search_term . '%';
        
        foreach ($search_fields as $field) {
            $search_conditions[] = "$field LIKE ?";
            $params[] = $search_term;
        }
        
        $search_clause = '(' . implode(' OR ', $search_conditions) . ')';
        
        if (strpos(strtolower($base_query), 'where') !== false) {
            $query = $base_query . ' AND ' . $search_clause;
        } else {
            $query = $base_query . ' WHERE ' . $search_clause;
        }
        
        return [$query, $params];
    }
}

// Export helpers
if (!function_exists('exportToCSV')) {
    function exportToCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Header
            fputcsv($output, array_keys($data[0]));
            
            // Data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
}

// Validation helpers
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validatePhone')) {
    function validatePhone($phone) {
        return preg_match('/^[0-9\-\+\(\)\s]{10,20}$/', $phone);
    }
}

if (!function_exists('validateRequired')) {
    function validateRequired($fields, $data) {
        $errors = [];
        
        foreach ($fields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "$label wajib diisi";
            }
        }
        
        return $errors;
    }
}

// Constants for file paths if not defined
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
}

if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', 'assets/uploads/');
}

?>