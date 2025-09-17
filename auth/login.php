<?php
// login.php
require_once 'config/config.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Cek user di database
        $sql = "SELECT * FROM users WHERE username = ?";
        $user = $db->fetch($sql, [$username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Update last login (optional)
            $updateSql = "UPDATE users SET updated_at = NOW() WHERE id = ?";
            $db->query($updateSql, [$user['id']]);
            
            redirect('index.php');
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="assets/img/logo-ptun.png">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/img/logo-ptun.png" alt="Logo PTUN" onerror="this.style.display='none'">
                <h2><?= APP_NAME ?></h2>
                <p class="text-secondary"><?= APP_FULL_NAME ?></p>
                <p class="text-secondary"><?= APP_SUBTITLE ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="needs-validation" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($username ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                    <div class="invalid-feedback">
                        Username harus diisi
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            class="btn-show-password" 
                            onclick="togglePassword()"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary);"
                        >
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">
                        Password harus diisi
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="d-flex align-items-center">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label" style="margin-left: 0.5rem;">
                            Ingat saya
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Masuk
                </button>
            </form>
            
            <div style="margin-top: 1.5rem; text-align: center;">
                <p class="text-secondary">
                    <small>
                        Default login:<br>
                        Username: <strong>admin</strong> / Password: <strong>password</strong><br>
                        Username: <strong>user1</strong> / Password: <strong>password</strong>
                    </small>
                </p>
            </div>
            
            <div style="margin-top: 2rem; text-align: center; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <p class="text-secondary">
                    <small>
                        &copy; <?= date('Y') ?> <?= APP_SUBTITLE ?><br>
                        Versi <?= APP_VERSION ?>
                    </small>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.needs-validation');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
            
            // Focus on username field
            document.getElementById('username').focus();
            
            // Enter key navigation
            document.getElementById('username').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('password').focus();
                }
            });
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
