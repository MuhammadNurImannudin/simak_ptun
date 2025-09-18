<?php
// auth/logout.php - Fixed logout with correct path
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Store user info for logging (optional)
$logout_user = $_SESSION['username'] ?? 'unknown';
$logout_time = date('Y-m-d H:i:s');

// Destroy session and clear all session data
$_SESSION = array();

// Delete session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remember me cookies (if implemented)
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Optional: Log logout activity
error_log("User logout: $logout_user at $logout_time");

// Set logout message for login page
session_start(); // Start new session for flash message
setFlashMessage('success', 'Anda telah berhasil logout dari sistem');

// Redirect to login page
header('Location: login.php');
exit();
?>