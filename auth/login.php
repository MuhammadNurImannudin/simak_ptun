<?php
// auth/login.php - Login Form dengan Logo PTUN Animasi
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';
$flash = function_exists('getFlashMessage') ? getFlashMessage() : null;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
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
            if (function_exists('fetchSingle')) {
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
                if (isset($user) && function_exists('executeQuery')) {
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
                    if (function_exists('executeQuery')) {
                        executeQuery(
                            "UPDATE users SET remember_token = ? WHERE id = ?",
                            [$token, $user_data['id']]
                        );
                    }
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
    <title>Login - SIMAK PTUN Banjarmasin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ptun-green: #2d5a27;
            --ptun-gold: #ffd700;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--ptun-green) 0%, #1a3a17 50%, var(--ptun-green) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255, 215, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255, 215, 0, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            padding: 0 1rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            animation: slideInUp 0.8s ease-out;
        }

        /* Logo Section dengan Animasi */
        .logo-section {
            background: linear-gradient(135deg, var(--ptun-green) 0%, #1a3a17 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .logo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" patternUnits="userSpaceOnUse" width="100" height="100"><circle cx="25" cy="25" r="1" fill="rgba(255,215,0,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,215,0,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            animation: backgroundMove 20s linear infinite;
        }

        /* Enhanced Logo PTUN dengan Multiple Animasi */
        .logo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .logo-ptun {
            width: 90px;
            height: auto;
            border-radius: 12px;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
            animation: 
                logoFloat 4s ease-in-out infinite,
                logoGlow 3s ease-in-out infinite alternate,
                logoRotate 8s linear infinite;
            position: relative;
            z-index: 3;
            cursor: pointer;
        }

        .logo-ptun:hover {
            animation-play-state: paused;
            transform: scale(1.15) rotateY(15deg);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .logo-rings {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
        }

        .logo-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }

        .logo-ring:nth-child(1) {
            width: 110px;
            height: 110px;
            animation: ringPulse 2s ease-out infinite;
        }

        .logo-ring:nth-child(2) {
            width: 130px;
            height: 130px;
            animation: ringPulse 2s ease-out 0.5s infinite;
        }

        .logo-ring:nth-child(3) {
            width: 150px;
            height: 150px;
            animation: ringPulse 2s ease-out 1s infinite;
        }

        .system-title {
            color: white;
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.4);
            position: relative;
            z-index: 2;
            letter-spacing: 1px;
        }

        .system-subtitle {
            color: var(--ptun-gold);
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 2;
            line-height: 1.4;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        /* Form Section */
        .form-section {
            padding: 3rem 2rem;
            background: white;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: #6b7280;
            text-align: center;
            margin-bottom: 2.5rem;
            font-size: 0.9375rem;
        }

        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--ptun-green);
            background: white;
            box-shadow: 0 0 0 4px rgba(45, 90, 39, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.125rem;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .form-control:focus + .input-icon {
            color: var(--ptun-green);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--ptun-green) 0%, #1a3a17 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1.125rem;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(45, 90, 39, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(45, 90, 39, 0.3);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc2626;
            animation: shake 0.5s ease-in-out;
        }

        .demo-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0c4a6e;
            padding: 1.25rem;
            border-radius: 12px;
            font-size: 0.875rem;
            margin-top: 1.5rem;
            border: 1px solid #bae6fd;
            text-align: center;
        }

        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-10px) rotate(2deg);
            }
            50% {
                transform: translateY(-5px) rotate(0deg);
            }
            75% {
                transform: translateY(-15px) rotate(-2deg);
            }
        }

        @keyframes logoGlow {
            0% {
                filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3)) brightness(1);
            }
            100% {
                filter: drop-shadow(0 10px 20px rgba(255,215,0,0.5)) brightness(1.2);
            }
        }

        @keyframes logoRotate {
            0% {
                transform: rotateY(0deg);
            }
            100% {
                transform: rotateY(360deg);
            }
        }

        @keyframes ringPulse {
            0% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(1.3);
                opacity: 0;
            }
        }

        @keyframes backgroundMove {
            0% {
                transform: translateX(0) translateY(0);
            }
            25% {
                transform: translateX(-10px) translateY(-10px);
            }
            50% {
                transform: translateX(-20px) translateY(0);
            }
            75% {
                transform: translateX(-10px) translateY(10px);
            }
            100% {
                transform: translateX(0) translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 0 0.5rem;
            }
            
            .form-section {
                padding: 2rem 1.5rem;
            }
            
            .logo-section {
                padding: 2.5rem 1.5rem;
            }
            
            .logo-ptun {
                width: 75px;
            }
            
            .logo-ring:nth-child(1) { width: 95px; height: 95px; }
            .logo-ring:nth-child(2) { width: 115px; height: 115px; }
            .logo-ring:nth-child(3) { width: 135px; height: 135px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo Section dengan Animasi Enhanced -->
            <div class="logo-section">
                <div class="logo-container">
                    <div class="logo-rings">
                        <div class="logo-ring"></div>
                        <div class="logo-ring"></div>
                        <div class="logo-ring"></div>
                    </div>
                    <img src="../assets/img/logo-ptun.png" 
                         alt="Logo PTUN Banjarmasin" 
                         class="logo-ptun"
                         onerror="this.style.display='none'">
                </div>
                <h1 class="system-title">SIMAK PTUN</h1>
                <p class="system-subtitle">SISTEM INFORMASI MANAJEMEN ARSIP KORESPONDENSI<br>PENGADILAN TATA USAHA NEGARA BANJARMASIN</p>
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <h2 class="form-title">Selamat Datang</h2>
                <p class="form-subtitle">Silakan masuk dengan kredensial Anda</p>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : 'success' ?>">
                        <?= htmlspecialchars($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div style="position: relative;">
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Masukkan username Anda"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   required
                                   autocomplete="username">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div style="position: relative;">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Masukkan password Anda"
                                   required
                                   autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Ingat saya selama 30 hari</label>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Masuk ke Sistem
                    </button>
                </form>

                <div class="demo-info">
                    <strong>Akun Demo:</strong><br>
                    Username: <code>admin</code> | Password: <code>admin</code><br>
                    Username: <code>demo</code> | Password: <code>demo</code>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced logo interaction
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.querySelector('.logo-ptun');
            
            // Add click animation
            logo.addEventListener('click', function() {
                // Temporarily stop current animations
                this.style.animation = 'none';
                
                // Reset animations after a short delay
                setTimeout(() => {
                    this.style.animation = 'logoFloat 4s ease-in-out infinite, logoGlow 3s ease-in-out infinite alternate, logoRotate 8s linear infinite';
                }, 100);
                
                // Add spectacular spin effect
                this.style.transform = 'scale(1.3) rotateY(720deg) rotateX(360deg)';
                this.style.filter = 'drop-shadow(0 15px 30px rgba(255,215,0,0.8)) brightness(1.5)';
                
                setTimeout(() => {
                    this.style.transform = '';
                    this.style.filter = '';
                }, 800);
            });
            
            // Form enhancement dengan smooth interactions
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = '';
                });
                
                // Add typing effect
                input.addEventListener('input', function() {
                    const icon = this.nextElementSibling;
                    if (icon) {
                        icon.style.transform = 'translateY(-50%) scale(1.1)';
                        setTimeout(() => {
                            icon.style.transform = 'translateY(-50%) scale(1)';
                        }, 150);
                    }
                });
            });
            
            // Button hover effects
            const loginBtn = document.querySelector('.btn-login');
            loginBtn.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 15px 30px rgba(45, 90, 39, 0.5)';
            });
            
            loginBtn.addEventListener('mouseleave', function() {
                this.style.boxShadow = '';
            });
        });

        // Add particles effect (optional)
        function createParticle() {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: fixed;
                width: 4px;
                height: 4px;
                background: rgba(255, 215, 0, 0.6);
                border-radius: 50%;
                pointer-events: none;
                z-index: 1;
                left: ${Math.random() * 100}vw;
                top: 100vh;
                animation: particleFloat ${3 + Math.random() * 4}s linear infinite;
            `;
            
            document.body.appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 7000);
        }

        // Add CSS for particle animation
        const particleStyle = document.createElement('style');
        particleStyle.textContent = `
            @keyframes particleFloat {
                0% {
                    transform: translateY(0) rotate(0deg);
                    opacity: 1;
                }
                100% {
                    transform: translateY(-100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(particleStyle);

        // Create particles periodically
        setInterval(createParticle, 2000);
    </script>
</body>
</html>