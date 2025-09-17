<?php
// config/config.php
session_start();

// Konfigurasi Aplikasi
define('APP_NAME', 'SIMAK PTUN');
define('APP_FULL_NAME', 'Sistem Informasi Manajemen Arsip dan Korespondensi');
define('APP_SUBTITLE', 'Pengadilan Tata Usaha Negara Banjarmasin');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/simak-ptun/');

// Konfigurasi Path
define('BASE_PATH', dirname(dirname(__FILE__)) . '/');
define('UPLOAD_PATH', BASE_PATH . 'assets/uploads/');
define('UPLOAD_URL', APP_URL . 'assets/uploads/');

// Konfigurasi Upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Timezone
date_default_timezone_set('Asia/Makassar');

// Include database
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'includes/functions.php';

// Inisialisasi database
$db = new Database();

// Fungsi untuk redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Fungsi untuk mengecek login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fungsi untuk mengecek role admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fungsi untuk logout
function logout() {
    session_destroy();
    redirect('login.php');
}

// Middleware untuk halaman yang memerlukan login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Middleware untuk halaman admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php');
    }
}
?>
