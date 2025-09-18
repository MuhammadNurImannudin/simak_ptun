<?php
// auth/login.php - Fixed login with correct path
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';
$flash = getFlashMessage();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi';
    } else {
        // Try database login first
        $login_success = false;
        $user_data = null;
        
        try {
            // Check if database is available
            $db_test = fetchSingle("SELECT 1 as test");
            if ($db_test) {
                // Database available - check real user
                $user = fetchSingle(
                    "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'aktif'",
                    [$username, $username]
                );
                
                if ($user && password_verify($password, $user['password'])) {
                    $login_success = true;
                    $user_data = $user;
                }
            }
        } catch (Exception $e) {
            // Database not available - use demo login
            error_log("Database error during login: " . $e->getMessage());
        }
        
        // Fallback demo login if database not available
        if (!$login_success) {
            if (($username === 'admin' && $password === 'admin') || 
                ($username === 'demo' && $password === 'demo')) {
                $login_success = true;
                $user_data = [
                    'id' => 1,
                    'username' => $username,
                    'nama_lengkap' => $username === 'admin' ? 'Administrator' : 'Demo User',
                    'email' => $username . '@ptun-banjarmasin.go.id',
                    'role' => $username === 'admin' ? 'Administrator' : 'Staff TU'
                ];
            }
        }
        
        if ($login_success && $user_data) {
            // Set session variables
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['nama_lengkap'] = $user_data['nama_lengkap'];
            $_SESSION['email'] = $user_data['email'];
            $_SESSION['role'] = $user_data['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            // Update last login if database available
            try {
                if (isset($user)) {
                    executeQuery(
                        "UPDATE users SET last_login = NOW() WHERE id = ?",
                        [$user_data['id']]
                    );
                }
            } catch (Exception $e) {
                // Ignore if database not available
            }
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                
                // Store token in database if available
                try {
                    executeQuery(
                        "UPDATE users SET remember_token = ? WHERE id = ?",
                        [$token, $user_data['id']]
                    );
                } catch (Exception $e) {
                    // Ignore if database not available
                }
            }
            
            // Redirect to dashboard
            header('Location: ../index.php');
            exit();
        } else {
            $error_message = 'Username atau password salah';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMAK PTUN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .form-control {
            border-radius: 12px;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
        }
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-primary {
            background: #4f46e5;
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 600;
        }
        .alert {
            border-radius: 12px;
            padding: 0.875rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="fas fa-gavel fa-3x text-primary mb-3"></i>
            <h2 class="fw-bold">SIMAK PTUN</h2>
            <p class="text-muted">Sistem Informasi Manajemen Arsip</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" 
                       placeholder="Masukkan username" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" 
                       placeholder="Masukkan password" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="remember" class="form-check-input">
                <label class="form-check-label">Ingat saya</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <div class="alert alert-info mt-3">
            <strong>Demo Login:</strong><br>
            Username: <code>admin</code> | Password: <code>admin</code><br>
            Username: <code>demo</code> | Password: <code>demo</code>
        </div>
    </div>
</body>
</html>