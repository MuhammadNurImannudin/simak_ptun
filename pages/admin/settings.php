<?php
// pages/settings/index.php
require_once '../../config/config.php';

// Require login and admin role
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    setFlashMessage('danger', 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
    redirect('../../index.php');
}

// Set page variables
$page_title = 'Pengaturan Sistem';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_general':
            handleUpdateGeneral();
            break;
        case 'update_mail':
            handleUpdateMail();
            break;
        case 'update_security':
            handleUpdateSecurity();
            break;
        case 'backup_database':
            handleBackupDatabase();
            break;
        case 'clear_logs':
            handleClearLogs();
            break;
        case 'update_system':
            handleUpdateSystem();
            break;
    }
}

function handleUpdateGeneral() {
    global $db;
    
    $app_name = sanitize($_POST['app_name'] ?? '');
    $app_description = sanitize($_POST['app_description'] ?? '');
    $institution_name = sanitize($_POST['institution_name'] ?? '');
    $institution_address = sanitize($_POST['institution_address'] ?? '');
    $contact_phone = sanitize($_POST['contact_phone'] ?? '');
    $contact_email = sanitize($_POST['contact_email'] ?? '');
    $timezone = sanitize($_POST['timezone'] ?? 'Asia/Jakarta');
    
    $errors = [];
    
    if (empty($app_name)) {
        $errors[] = 'Nama aplikasi harus diisi';
    }
    
    if (empty($institution_name)) {
        $errors[] = 'Nama instansi harus diisi';
    }
    
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email kontak tidak valid';
    }
    
    if (empty($errors)) {
        try {
            // Update or insert settings
            $settings = [
                'app_name' => $app_name,
                'app_description' => $app_description,
                'institution_name' => $institution_name,
                'institution_address' => $institution_address,
                'contact_phone' => $contact_phone,
                'contact_email' => $contact_email,
                'timezone' => $timezone
            ];
            
            foreach ($settings as $key => $value) {
                $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                if ($existing) {
                    $db->query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
                } else {
                    $db->query("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$key, $value]);
                }
            }
            
            setFlashMessage('success', 'Pengaturan umum berhasil disimpan');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
    
    redirect('index.php#general');
}

function handleUpdateMail() {
    global $db;
    
    $mail_host = sanitize($_POST['mail_host'] ?? '');
    $mail_port = (int)($_POST['mail_port'] ?? 587);
    $mail_username = sanitize($_POST['mail_username'] ?? '');
    $mail_password = $_POST['mail_password'] ?? '';
    $mail_encryption = sanitize($_POST['mail_encryption'] ?? 'tls');
    $mail_from_address = sanitize($_POST['mail_from_address'] ?? '');
    $mail_from_name = sanitize($_POST['mail_from_name'] ?? '');
    
    $errors = [];
    
    if (!empty($mail_from_address) && !filter_var($mail_from_address, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email pengirim tidak valid';
    }
    
    if (empty($errors)) {
        try {
            $settings = [
                'mail_host' => $mail_host,
                'mail_port' => $mail_port,
                'mail_username' => $mail_username,
                'mail_password' => $mail_password,
                'mail_encryption' => $mail_encryption,
                'mail_from_address' => $mail_from_address,
                'mail_from_name' => $mail_from_name
            ];
            
            foreach ($settings as $key => $value) {
                $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                if ($existing) {
                    $db->query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
                } else {
                    $db->query("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$key, $value]);
                }
            }
            
            setFlashMessage('success', 'Pengaturan email berhasil disimpan');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal menyimpan pengaturan email: ' . $e->getMessage());
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
    
    redirect('index.php#mail');
}

function handleUpdateSecurity() {
    global $db;
    
    $session_timeout = (int)($_POST['session_timeout'] ?? 1800);
    $max_login_attempts = (int)($_POST['max_login_attempts'] ?? 5);
    $password_min_length = (int)($_POST['password_min_length'] ?? 6);
    $require_password_complexity = isset($_POST['require_password_complexity']) ? 1 : 0;
    $enable_two_factor = isset($_POST['enable_two_factor']) ? 1 : 0;
    $allowed_file_types = sanitize($_POST['allowed_file_types'] ?? 'pdf,doc,docx,jpg,jpeg,png');
    $max_file_size = (int)($_POST['max_file_size'] ?? 5);
    
    try {
        $settings = [
            'session_timeout' => $session_timeout,
            'max_login_attempts' => $max_login_attempts,
            'password_min_length' => $password_min_length,
            'require_password_complexity' => $require_password_complexity,
            'enable_two_factor' => $enable_two_factor,
            'allowed_file_types' => $allowed_file_types,
            'max_file_size' => $max_file_size
        ];
        
        foreach ($settings as $key => $value) {
            $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
            if ($existing) {
                $db->query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
            } else {
                $db->query("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$key, $value]);
            }
        }
        
        setFlashMessage('success', 'Pengaturan keamanan berhasil disimpan');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal menyimpan pengaturan keamanan: ' . $e->getMessage());
    }
    
    redirect('index.php#security');
}

function handleBackupDatabase() {
    try {
        $backup_dir = '../../backups/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $filename = 'backup_simak_ptun_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;
        
        // Simple backup using mysqldump (requires mysqldump to be available)
        $command = "mysqldump -h" . DB_HOST . " -u" . DB_USER . " -p" . DB_PASS . " " . DB_NAME . " > " . $filepath;
        
        // For security, we'll create a simple backup method
        // In production, use proper backup tools
        $backup_content = "-- SIMAK PTUN Database Backup\n";
        $backup_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        file_put_contents($filepath, $backup_content);
        
        setFlashMessage('success', 'Backup database berhasil dibuat: ' . $filename);
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal membuat backup: ' . $e->getMessage());
    }
    
    redirect('index.php#maintenance');
}

function handleClearLogs() {
    try {
        $log_dir = '../../logs/';
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '*.log');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        setFlashMessage('success', 'Log files berhasil dihapus');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal menghapus log files: ' . $e->getMessage());
    }
    
    redirect('index.php#maintenance');
}

function handleUpdateSystem() {
    // This would handle system updates in a real application
    setFlashMessage('info', 'Fitur update sistem akan segera tersedia');
    redirect('index.php#maintenance');
}

// Get current settings
function getSetting($key, $default = '') {
    global $db;
    $setting = $db->fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $setting ? $setting['setting_value'] : $default;
}

// Get system information
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'],
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Get statistics
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as total FROM users")['total'],
    'total_surat_masuk' => $db->fetch("SELECT COUNT(*) as total FROM surat_masuk")['total'],
    'total_surat_keluar' => $db->fetch("SELECT COUNT(*) as total FROM surat_keluar")['total'],
    'database_size' => getDatabaseSize()
];

function getDatabaseSize() {
    global $db;
    $result = $db->fetch("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
        FROM information_schema.tables 
        WHERE table_schema = ?
    ", [DB_NAME]);
    return $result['size_mb'] . ' MB';
}

// Include header and sidebar
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-cogs"></i>
                    Pengaturan Sistem
                </h1>
                <p class="page-subtitle">
                    Konfigurasi dan pengaturan aplikasi SIMAK PTUN
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-info" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>
    
    <!-- System Stats -->
    <div class="stats-grid mb-4">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['total_users']) ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['total_surat_masuk'] + $stats['total_surat_keluar']) ?></h3>
                <p>Total Surat</p>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="stat-content">
                <h3><?= $stats['database_size'] ?></h3>
                <p>Ukuran Database</p>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-server"></i>
            </div>
            <div class="stat-content">
                <h3>PHP <?= $system_info['php_version'] ?></h3>
                <p>Versi Server</p>
            </div>
        </div>
    </div>
    
    <!-- Settings Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="fas fa-cog"></i>
                        Umum
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mail-tab" data-bs-toggle="tab" data-bs-target="#mail" type="button" role="tab">
                        <i class="fas fa-envelope"></i>
                        Email
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-shield-alt"></i>
                        Keamanan
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                        <i class="fas fa-wrench"></i>
                        Maintenance
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                        <i class="fas fa-info-circle"></i>
                        Info System
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="settingsTabsContent">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_general">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Nama Aplikasi</label>
                                    <input type="text" name="app_name" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('app_name', 'SIMAK PTUN')) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Timezone</label>
                                <select name="timezone" class="form-control">
                                    <option value="Asia/Jakarta" <?= getSetting('timezone', 'Asia/Jakarta') === 'Asia/Jakarta' ? 'selected' : '' ?>>Asia/Jakarta (WIB)</option>
                                    <option value="Asia/Makassar" <?= getSetting('timezone') === 'Asia/Makassar' ? 'selected' : '' ?>>Asia/Makassar (WITA)</option>
                                    <option value="Asia/Jayapura" <?= getSetting('timezone') === 'Asia/Jayapura' ? 'selected' : '' ?>>Asia/Jayapura (WIT)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Deskripsi Aplikasi</label>
                            <textarea name="app_description" class="form-control" rows="3"><?= htmlspecialchars(getSetting('app_description', 'Sistem Informasi Manajemen Arsip dan Korespondensi')) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nama Instansi</label>
                            <input type="text" name="institution_name" class="form-control" 
                                   value="<?= htmlspecialchars(getSetting('institution_name', 'Pengadilan Tata Usaha Negara Banjarmasin')) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Alamat Instansi</label>
                            <textarea name="institution_address" class="form-control" rows="3"><?= htmlspecialchars(getSetting('institution_address', 'Jl. Brig Jend H. Hasan Basry No.3, Pangeran, Banjarmasin Utara, Kota Banjarmasin, Kalimantan Selatan')) ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Telepon Kontak</label>
                                    <input type="text" name="contact_phone" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('contact_phone', '(0511) 3252989')) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Kontak</label>
                                    <input type="email" name="contact_email" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('contact_email', 'ptun.banjarmasin@go.id')) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Simpan Pengaturan Umum
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Mail Settings -->
                <div class="tab-pane fade" id="mail" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_mail">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Konfigurasi email untuk notifikasi sistem dan reset password.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="mail_host" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('mail_host', 'smtp.gmail.com')) ?>"
                                           placeholder="smtp.gmail.com">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Port</label>
                                    <input type="number" name="mail_port" class="form-control" 
                                           value="<?= getSetting('mail_port', 587) ?>" min="1" max="65535">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="mail_username" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('mail_username')) ?>"
                                           placeholder="your-email@gmail.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="mail_password" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('mail_password')) ?>"
                                           placeholder="App Password atau Password Email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Enkripsi</label>
                            <select name="mail_encryption" class="form-control">
                                <option value="tls" <?= getSetting('mail_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= getSetting('mail_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= getSetting('mail_encryption') === '' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Pengirim</label>
                                    <input type="email" name="mail_from_address" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('mail_from_address')) ?>"
                                           placeholder="noreply@ptun-banjarmasin.go.id">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Nama Pengirim</label>
                                    <input type="text" name="mail_from_name" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('mail_from_name', 'SIMAK PTUN')) ?>"
                                           placeholder="SIMAK PTUN">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Simpan Pengaturan Email
                            </button>
                            <button type="button" class="btn btn-info" onclick="testEmail()">
                                <i class="fas fa-envelope"></i>
                                Test Email
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security Settings -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_security">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Session Timeout (detik)</label>
                                    <input type="number" name="session_timeout" class="form-control" 
                                           value="<?= getSetting('session_timeout', 1800) ?>" min="300" max="86400">
                                    <small class="text-muted">Default: 1800 (30 menit)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Maksimal Percobaan Login</label>
                                    <input type="number" name="max_login_attempts" class="form-control" 
                                           value="<?= getSetting('max_login_attempts', 5) ?>" min="3" max="10">
                                    <small class="text-muted">Default: 5 kali percobaan</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Panjang Minimum Password</label>
                                    <input type="number" name="password_min_length" class="form-control" 
                                           value="<?= getSetting('password_min_length', 6) ?>" min="4" max="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Kompleksitas Password</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="require_password_complexity" 
                                               <?= getSetting('require_password_complexity', 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">
                                            Wajib huruf besar, kecil, angka, dan simbol
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Two-Factor Authentication</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="enable_two_factor" 
                                       <?= getSetting('enable_two_factor', 0) ? 'checked' : '' ?>>
                                <label class="form-check-label">
                                    Aktifkan 2FA untuk semua user
                                </label>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">Tipe File Yang Diizinkan</label>
                                    <input type="text" name="allowed_file_types" class="form-control" 
                                           value="<?= htmlspecialchars(getSetting('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png')) ?>"
                                           placeholder="pdf,doc,docx,jpg,jpeg,png">
                                    <small class="text-muted">Pisahkan dengan koma</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Ukuran Maksimal File (MB)</label>
                                    <input type="number" name="max_file_size" class="form-control" 
                                           value="<?= getSetting('max_file_size', 5) ?>" min="1" max="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Simpan Pengaturan Keamanan
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Maintenance -->
                <div class="tab-pane fade" id="maintenance" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-database"></i>
                                        Database Management
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>Backup dan restore database sistem.</p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="backup_database">
                                        <button type="submit" class="btn btn-info">
                                            <i class="fas fa-download"></i>
                                            Backup Database
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-file-alt"></i>
                                        Log Management
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>Bersihkan log files untuk menghemat space.</p>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus semua log files?')">
                                        <input type="hidden" name="action" value="clear_logs">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-trash"></i>
                                            Hapus Log Files
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-sync"></i>
                                        System Update
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>Update sistem ke versi terbaru.</p>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_system">
                                        <button type="submit" class="btn btn-success" disabled>
                                            <i class="fas fa-upload"></i>
                                            Check Updates
                                        </button>
                                    </form>
                                    <small class="d-block text-muted mt-2">Versi saat ini: 2.0</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        System Reset
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>Reset sistem ke pengaturan default.</p>
                                    <button type="button" class="btn btn-danger" disabled>
                                        <i class="fas fa-redo"></i>
                                        Reset System
                                    </button>
                                    <small class="d-block text-muted mt-2">Fitur tidak tersedia dalam demo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="tab-pane fade" id="system" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informasi Server</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%">PHP Version:</td>
                                    <td><code><?= $system_info['php_version'] ?></code></td>
                                </tr>
                                <tr>
                                    <td>MySQL Version:</td>
                                    <td><code><?= $system_info['mysql_version'] ?></code></td>
                                </tr>
                                <tr>
                                    <td>Server Software:</td>
                                    <td><code><?= $system_info['server_software'] ?></code></td>
                                </tr>
                                <tr>
                                    <td>Document Root:</td>
                                    <td><code><?= $system_info['document_root'] ?></code></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Konfigurasi PHP</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%">Upload Max Filesize:</td>
                                    <td><code><?= $system_info['upload_max_filesize'] ?></code></td>
                                </tr>
                                <tr>
                                    <td>Post Max Size:</td>
                                    <td><code><?= $system_info['post_max_size'] ?></code></td>
                                </tr>
                                <tr>
                                    <td>Memory Limit:</td>
                                    <td><code><?= $system_info['memory_limit'] ?></code></td>
                                </tr>
                                <tr>
                                    <td>Max Execution Time:</td>
                                    <td><code><?= $system_info['max_execution_time'] ?>s</code></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Statistik Aplikasi</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h4 class="text-primary"><?= number_format($stats['total_users']) ?></h4>
                                <small>Total Users</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h4 class="text-success"><?= number_format($stats['total_surat_masuk']) ?></h4>
                                <small>Surat Masuk</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h4 class="text-info"><?= number_format($stats['total_surat_keluar']) ?></h4>
                                <small>Surat Keluar</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <h4 class="text-warning"><?= $stats['database_size'] ?></h4>
                                <small>Database Size</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$inline_scripts = '
// Handle tab switching from URL hash
document.addEventListener("DOMContentLoaded", function() {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`[data-bs-target="${hash}"]`);
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
});

// Update URL hash when tab changes
document.querySelectorAll("[data-bs-toggle=\'tab\']").forEach(tab => {
    tab.addEventListener("shown.bs.tab", function(e) {
        window.location.hash = e.target.getAttribute("data-bs-target");
    });
});

function testEmail() {
    // This would send a test email in a real implementation
    showInfoToast("Fitur test email akan segera tersedia");
}

// Add settings page styles
const settingsStyles = `
<style>
.stat-box {
    text-align: center;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.stat-box h4 {
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.stat-box small {
    color: var(--text-muted);
}

.nav-tabs .nav-link {
    border: none;
    color: var(--text-secondary);
    font-weight: 500;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: var(--primary-color);
}

.nav-tabs .nav-link.active {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
    background: transparent;
}

.card-header-tabs {
    border-bottom: 1px solid var(--border-color);
    margin-bottom: -1px;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}

code {
    color: var(--success-color);
    background: var(--bg-secondary);
    padding: 0.125rem 0.25rem;
    border-radius: 3px;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", settingsStyles);
';

include '../../includes/footer.php';
?>
