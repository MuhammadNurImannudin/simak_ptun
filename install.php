<?php
/**
 * SIMAK PTUN Installer
 * Automatic Installation Script
 * 
 * This script will help you install SIMAK PTUN automatically
 * Run this file via web browser: http://yourdomain.com/install.php
 */

// Prevent running after installation
if (file_exists('config/installed.lock')) {
    die('SIMAK PTUN already installed. Delete config/installed.lock to reinstall.');
}

// Start session
session_start();

// Installation steps
$steps = [
    1 => 'System Check',
    2 => 'Database Configuration', 
    3 => 'Admin Account Setup',
    4 => 'Finalization'
];

$current_step = (int)($_GET['step'] ?? $_SESSION['install_step'] ?? 1);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($current_step) {
        case 1:
            handleSystemCheck();
            break;
        case 2:
            handleDatabaseSetup();
            break;
        case 3:
            handleAdminSetup();
            break;
        case 4:
            handleFinalization();
            break;
    }
}

function handleSystemCheck() {
    $_SESSION['install_step'] = 2;
    header('Location: install.php?step=2');
    exit;
}

function handleDatabaseSetup() {
    $host = trim($_POST['db_host']);
    $port = trim($_POST['db_port']) ?: '3306';
    $name = trim($_POST['db_name']);
    $user = trim($_POST['db_user']);
    $pass = trim($_POST['db_pass']);
    
    $errors = [];
    
    if (empty($host)) $errors[] = 'Database host is required';
    if (empty($name)) $errors[] = 'Database name is required';
    if (empty($user)) $errors[] = 'Database user is required';
    
    if (empty($errors)) {
        try {
            // Test database connection
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if database exists, create if not
            $stmt = $pdo->prepare("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $stmt->execute();
            
            // Connect to the database
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            
            // Save database config
            $_SESSION['db_config'] = [
                'host' => $host,
                'port' => $port,
                'name' => $name,
                'user' => $user,
                'pass' => $pass
            ];
            
            $_SESSION['db_connection'] = $pdo;
            $_SESSION['install_step'] = 3;
            
            header('Location: install.php?step=3');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['install_errors'] = ['Database connection failed: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['install_errors'] = $errors;
    }
}

function handleAdminSetup() {
    $username = trim($_POST['admin_username']);
    $email = trim($_POST['admin_email']);
    $name = trim($_POST['admin_name']);
    $password = $_POST['admin_password'];
    $confirm_password = $_POST['admin_password_confirm'];
    
    $errors = [];
    
    if (empty($username)) $errors[] = 'Admin username is required';
    if (empty($email)) $errors[] = 'Admin email is required';
    if (empty($name)) $errors[] = 'Admin name is required';
    if (empty($password)) $errors[] = 'Admin password is required';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    
    if (empty($errors)) {
        $_SESSION['admin_config'] = [
            'username' => $username,
            'email' => $email,
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        
        $_SESSION['install_step'] = 4;
        
        header('Location: install.php?step=4');
        exit;
    } else {
        $_SESSION['install_errors'] = $errors;
    }
}

function handleFinalization() {
    try {
        // Create database schema
        installDatabase();
        
        // Create config files
        createConfigFiles();
        
        // Create directories
        createDirectories();
        
        // Insert initial data
        insertInitialData();
        
        // Create lock file
        file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
        
        $_SESSION['installation_complete'] = true;
        $_SESSION['install_step'] = 'complete';
        
        header('Location: install.php?step=complete');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['install_errors'] = ['Installation failed: ' . $e->getMessage()];
    }
}

function installDatabase() {
    $pdo = $_SESSION['db_connection'];
    $sql = file_get_contents('database_schema.sql');
    
    if (!$sql) {
        throw new Exception('Could not read database schema file');
    }
    
    // Execute SQL statements
    $statements = explode(';', $sql);
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt) && !preg_match('/^(--|#)/', $stmt)) {
            $pdo->exec($stmt);
        }
    }
}

function createConfigFiles() {
    $db_config = $_SESSION['db_config'];
    
    // Create database config
    $db_config_content = "<?php
// Database Configuration
// Generated by SIMAK PTUN Installer on " . date('Y-m-d H:i:s') . "

define('DB_HOST', '{$db_config['host']}');
define('DB_PORT', '{$db_config['port']}');
define('DB_USER', '{$db_config['user']}');
define('DB_PASS', '{$db_config['pass']}');
define('DB_NAME', '{$db_config['name']}');
define('DB_CHARSET', 'utf8mb4');
?>";
    
    if (!is_dir('config')) mkdir('config', 0755, true);
    file_put_contents('config/database.php', $db_config_content);
    
    // Create main config if not exists
    if (!file_exists('config/config.php')) {
        $base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
        
        $config_content = "<?php
// Main Configuration
// Generated by SIMAK PTUN Installer on " . date('Y-m-d H:i:s') . "

// Application Settings
define('APP_NAME', 'SIMAK PTUN');
define('APP_VERSION', '2.0');
define('BASE_URL', '{$base_url}');
define('TIMEZONE', 'Asia/Jakarta');

// Security Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);

// File Upload Settings
define('ALLOWED_FILE_TYPES', 'pdf,doc,docx,jpg,jpeg,png');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB

// Include database config
require_once 'database.php';

// Include functions
require_once dirname(__DIR__) . '/includes/functions.php';
?>";
        
        file_put_contents('config/config.php', $config_content);
    }
}

function createDirectories() {
    $dirs = [
        'assets/uploads/surat-masuk',
        'assets/uploads/surat-keluar',
        'assets/uploads/profiles',
        'backups',
        'logs',
        'temp'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function insertInitialData() {
    $pdo = $_SESSION['db_connection'];
    $admin_config = $_SESSION['admin_config'];
    
    // Clear existing admin user
    $pdo->exec("DELETE FROM users WHERE username = 'admin' OR id = 1");
    
    // Insert admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, nama_lengkap, password, role, is_active, created_at) 
        VALUES (?, ?, ?, ?, 'admin', 1, NOW())
    ");
    $stmt->execute([
        $admin_config['username'],
        $admin_config['email'], 
        $admin_config['name'],
        $admin_config['password']
    ]);
    
    // Insert default settings
    $settings = [
        ['app_name', 'SIMAK PTUN'],
        ['institution_name', 'Pengadilan Tata Usaha Negara Banjarmasin'],
        ['timezone', 'Asia/Jakarta'],
        ['session_timeout', '1800'],
        ['max_login_attempts', '5'],
        ['password_min_length', '6']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value, created_at, updated_at) 
        VALUES (?, ?, NOW(), NOW()) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
}

function checkSystemRequirements() {
    $requirements = [
        'PHP Version' => [
            'required' => '7.4.0',
            'current' => phpversion(),
            'status' => version_compare(phpversion(), '7.4.0', '>=')
        ],
        'PDO Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pdo')
        ],
        'PDO MySQL' => [
            'required' => 'Enabled', 
            'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pdo_mysql')
        ],
        'mbstring Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled', 
            'status' => extension_loaded('mbstring')
        ],
        'JSON Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('json')
        ],
        'Config Directory' => [
            'required' => 'Writable',
            'current' => is_writable(dirname(__FILE__)) ? 'Writable' : 'Not Writable',
            'status' => is_writable(dirname(__FILE__))
        ]
    ];
    
    return $requirements;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMAK PTUN Installer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .installer-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 800px;
            margin: 20px;
            overflow: hidden;
        }
        
        .installer-header {
            background: #4f46e5;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .installer-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .installer-header p {
            opacity: 0.9;
        }
        
        .steps-nav {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .step-item {
            flex: 1;
            padding: 1rem;
            text-align: center;
            background: #f8fafc;
            color: #64748b;
            position: relative;
        }
        
        .step-item.active {
            background: #4f46e5;
            color: white;
        }
        
        .step-item.completed {
            background: #10b981;
            color: white;
        }
        
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: currentColor;
            color: white;
            line-height: 30px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .step-item:not(.active):not(.completed) .step-number {
            background: #cbd5e1;
        }
        
        .installer-content {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.15s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        
        .btn:hover {
            background: #4338ca;
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-success {
            background: #10b981;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        .requirements-table th,
        .requirements-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .requirements-table th {
            background: #f8fafc;
            font-weight: 600;
        }
        
        .status-ok {
            color: #10b981;
            font-weight: 600;
        }
        
        .status-error {
            color: #ef4444;
            font-weight: 600;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .installer-footer {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-success {
            color: #10b981;
        }
        
        .text-muted {
            color: #6b7280;
        }
        
        .mb-0 {
            margin-bottom: 0;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 640px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .steps-nav {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>SIMAK PTUN Installer</h1>
            <p>Sistem Informasi Manajemen Arsip dan Korespondensi</p>
        </div>
        
        <div class="steps-nav">
            <?php foreach ($steps as $step_num => $step_name): ?>
                <div class="step-item <?= $current_step == $step_num ? 'active' : ($current_step > $step_num ? 'completed' : '') ?>">
                    <div class="step-number"><?= $step_num ?></div>
                    <div><?= $step_name ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="installer-content">
            <?php if (isset($_SESSION['install_errors'])): ?>
                <div class="alert alert-error">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['install_errors'] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['install_errors']); ?>
            <?php endif; ?>
            
            <?php if ($current_step == 1): ?>
                <!-- System Check -->
                <h2>System Requirements Check</h2>
                <p>Checking if your server meets the requirements for SIMAK PTUN...</p>
                
                <?php $requirements = checkSystemRequirements(); ?>
                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th>Requirement</th>
                            <th>Required</th>
                            <th>Current</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requirements as $name => $req): ?>
                            <tr>
                                <td><?= $name ?></td>
                                <td><?= $req['required'] ?></td>
                                <td><?= $req['current'] ?></td>
                                <td class="<?= $req['status'] ? 'status-ok' : 'status-error' ?>">
                                    <?= $req['status'] ? '✓ OK' : '✗ FAIL' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php $all_ok = array_reduce($requirements, function($carry, $req) { return $carry && $req['status']; }, true); ?>
                
                <?php if ($all_ok): ?>
                    <div class="alert alert-success">
                        <strong>Great!</strong> Your server meets all requirements for SIMAK PTUN.
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>Warning!</strong> Some requirements are not met. Please fix the issues above before proceeding.
                    </div>
                <?php endif; ?>
                
            <?php elseif ($current_step == 2): ?>
                <!-- Database Setup -->
                <h2>Database Configuration</h2>
                <p>Please provide your database connection details:</p>
                
                <form method="POST">
                    <div class="grid">
                        <div class="form-group">
                            <label class="form-label">Database Host</label>
                            <input type="text" name="db_host" class="form-input" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Port</label>
                            <input type="text" name="db_port" class="form-input" value="3306">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" class="form-input" value="simak_ptun" required>
                    </div>
                    
                    <div class="grid">
                        <div class="form-group">
                            <label class="form-label">Database Username</label>
                            <input type="text" name="db_user" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Password</label>
                            <input type="password" name="db_pass" class="form-input">
                        </div>
                    </div>
                    
                    <div class="alert alert-success">
                        <strong>Note:</strong> The installer will create the database if it doesn't exist.
                    </div>
                    
                    <button type="submit" class="btn">Test Connection & Continue</button>
                </form>
                
            <?php elseif ($current_step == 3): ?>
                <!-- Admin Setup -->
                <h2>Administrator Account Setup</h2>
                <p>Create the main administrator account:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Admin Username</label>
                        <input type="text" name="admin_username" class="form-input" value="admin" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Admin Email</label>
                        <input type="email" name="admin_email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Admin Full Name</label>
                        <input type="text" name="admin_name" class="form-input" required>
                    </div>
                    
                    <div class="grid">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="admin_password" class="form-input" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="admin_password_confirm" class="form-input" required minlength="6">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Create Admin Account</button>
                </form>
                
            <?php elseif ($current_step == 4): ?>
                <!-- Finalization -->
                <h2>Final Installation</h2>
                <p>Ready to complete the installation. This will:</p>
                
                <ul>
                    <li>Create database tables and initial data</li>
                    <li>Generate configuration files</li>
                    <li>Set up directory structure</li>
                    <li>Create administrator account</li>
                </ul>
                
                <div class="alert alert-success">
                    <strong>Database:</strong> <?= $_SESSION['db_config']['name'] ?> on <?= $_SESSION['db_config']['host'] ?><br>
                    <strong>Admin:</strong> <?= $_SESSION['admin_config']['username'] ?> (<?= $_SESSION['admin_config']['email'] ?>)
                </div>
                
                <form method="POST">
                    <button type="submit" class="btn btn-success">Complete Installation</button>
                </form>
                
            <?php elseif ($current_step === 'complete' || isset($_SESSION['installation_complete'])): ?>
                <!-- Completion -->
                <div class="text-center">
                    <h2 class="text-success">Installation Complete!</h2>
                    <p>SIMAK PTUN has been successfully installed on your server.</p>
                    
                    <div class="alert alert-success">
                        <strong>Login Details:</strong><br>
                        Username: <?= $_SESSION['admin_config']['username'] ?? 'admin' ?><br>
                        Password: [as you configured]<br>
                        URL: <a href="index.php">Open SIMAK PTUN</a>
                    </div>
                    
                    <div class="mt-2">
                        <a href="index.php" class="btn btn-success">Go to SIMAK PTUN</a>
                        <a href="<?= basename(__FILE__) ?>?cleanup=1" class="btn btn-secondary">Delete Installer</a>
                    </div>
                    
                    <p class="text-muted mt-2">
                        For security reasons, please delete this installer file after use.
                    </p>
                </div>
                
                <?php 
                // Clear session
                session_destroy();
                ?>
                
            <?php endif; ?>
        </div>
        
        <?php if ($current_step < 4 && $current_step !== 'complete'): ?>
            <div class="installer-footer">
                <div class="text-muted">
                    Step <?= $current_step ?> of <?= count($steps) ?>
                </div>
                <div>
                    © 2024 PTUN Banjarmasin
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Handle cleanup request
if (isset($_GET['cleanup']) && $_GET['cleanup'] == '1' && isset($_SESSION['installation_complete'])) {
    unlink(__FILE__);
    header('Location: index.php');
    exit;
}
?>
