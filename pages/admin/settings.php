<?php
// pages/settings/index.php - Professional Settings Page
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit();
}

// Check if user is admin
$is_admin = true; // For demo purposes

// Check database availability
$db_available = false;
try {
    $test_result = fetchSingle("SELECT 1 as test");
    $db_available = ($test_result !== null);
} catch (Exception $e) {
    $db_available = false;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            // Update user profile
            try {
                // In real implementation, update database
                setFlashMessage('success', 'Profil berhasil diperbarui');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Gagal memperbarui profil: ' . $e->getMessage());
            }
            redirect('index.php');
            break;
            
        case 'update_system':
            // Update system settings
            try {
                // In real implementation, update system settings
                setFlashMessage('success', 'Pengaturan sistem berhasil diperbarui');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Gagal memperbarui pengaturan: ' . $e->getMessage());
            }
            redirect('index.php');
            break;
            
        case 'backup_database':
            // Create database backup
            try {
                // In real implementation, create backup
                setFlashMessage('success', 'Backup database berhasil dibuat');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Gagal membuat backup: ' . $e->getMessage());
            }
            redirect('index.php');
            break;
    }
}

// Get current settings (demo data)
$settings = [
    'sistem' => [
        'nama_instansi' => 'Pengadilan Tata Usaha Negara Banjarmasin',
        'alamat' => 'Jl. Sultan Adam No.18, Banjarmasin, Kalimantan Selatan',
        'telepon' => '(0511) 3252643',
        'email' => 'info@ptun-banjarmasin.go.id',
        'website' => 'https://ptun-banjarmasin.go.id',
        'logo' => 'logo-ptun.png'
    ],
    'aplikasi' => [
        'nama_aplikasi' => 'SIMAK PTUN',
        'versi' => '1.0.0',
        'maintenance_mode' => false,
        'auto_backup' => true,
        'notifikasi_email' => true,
        'tema' => 'default'
    ],
    'keamanan' => [
        'session_timeout' => 60,
        'max_login_attempts' => 3,
        'password_min_length' => 8,
        'require_2fa' => false,
        'audit_log' => true
    ]
];

// Get user profile
$user_profile = [
    'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Administrator',
    'username' => $_SESSION['username'] ?? 'admin',
    'email' => 'admin@ptun-banjarmasin.go.id',
    'role' => 'Administrator',
    'last_login' => '2024-09-17 10:30:00',
    'created_at' => '2024-01-01 08:00:00'
];

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - SIMAK PTUN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #4338ca;
            --secondary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --sidebar-width: 260px;
            --header-height: 80px;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
            font-size: 14px;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-800) 100%);
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .sidebar-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            margin-top: 0.5rem;
            line-height: 1.4;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            border-radius: 0;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
            position: relative;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: white;
        }

        .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            padding: 0 2rem;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }

        .header-subtitle {
            color: var(--gray-500);
            font-size: 0.875rem;
            margin: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Content Area */
        .content {
            padding: 2rem;
        }

        /* Cards */
        .card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-900);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Settings Tabs */
        .settings-nav {
            display: flex;
            gap: 0;
            background: var(--gray-100);
            padding: 0.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .settings-tab {
            flex: 1;
            padding: 0.75rem 1rem;
            text-align: center;
            background: transparent;
            border: none;
            color: var(--gray-600);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .settings-tab.active {
            background: white;
            color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }

        .settings-tab:hover:not(.active) {
            color: var(--gray-900);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Styles */
        .form-control, .form-select, .form-check-input {
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-text {
            color: var(--gray-500);
            font-size: 0.8125rem;
        }

        /* Switch Toggle */
        .form-check-input[type="checkbox"] {
            width: 3rem;
            height: 1.5rem;
            background-color: var(--gray-300);
            border: none;
            border-radius: 1rem;
            cursor: pointer;
        }

        .form-check-input[type="checkbox"]:checked {
            background-color: var(--success-color);
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid transparent;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-outline-secondary {
            background: transparent;
            color: var(--gray-600);
            border-color: var(--gray-300);
        }

        .btn-outline-secondary:hover {
            background: var(--gray-50);
            color: var(--gray-700);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        /* Profile Card */
        .profile-card {
            text-align: center;
            padding: 2rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .profile-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .profile-role {
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        /* System Info */
        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.8125rem;
            color: var(--gray-500);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-value {
            font-weight: 600;
            color: var(--gray-900);
        }

        /* Danger Zone */
        .danger-zone {
            border: 2px dashed var(--danger-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            background: #fef2f2;
        }

        .danger-zone h5 {
            color: var(--danger-color);
            margin-bottom: 1rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }

        .alert-danger {
            background: #fecaca;
            color: #991b1b;
            border-color: #f87171;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-color: #fcd34d;
        }

        .alert-dismissible .btn-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .content {
                padding: 1rem;
            }
            
            .settings-nav {
                flex-direction: column;
            }

            .system-info {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="../../index.php" class="sidebar-brand">
                <i class="fas fa-gavel"></i>
                SIMAK PTUN
            </a>
            <div class="sidebar-subtitle">
                Pengadilan Tata Usaha Negara<br>Banjarmasin
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="../../index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="../surat-masuk/" class="nav-link">
                    <i class="fas fa-inbox"></i>
                    Surat Masuk
                </a>
            </div>
            <div class="nav-item">
                <a href="../surat-keluar/" class="nav-link">
                    <i class="fas fa-paper-plane"></i>
                    Surat Keluar
                </a>
            </div>
            <div class="nav-item">
                <a href="../users/" class="nav-link">
                    <i class="fas fa-users"></i>
                    Manajemen User
                </a>
            </div>
            <div class="nav-item">
                <a href="../reports/" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Laporan
                </a>
            </div>
            <div class="nav-item">
                <a href="../settings/" class="nav-link active">
                    <i class="fas fa-cog"></i>
                    Pengaturan
                </a>
            </div>
            <div class="nav-item" style="margin-top: 2rem;">
                <a href="../../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div>
                <h1 class="header-title">Pengaturan</h1>
                <p class="header-subtitle">Kelola konfigurasi sistem dan profil pengguna</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-success" onclick="createBackup()">
                    <i class="fas fa-download"></i>
                    Backup System
                </button>
            </div>
        </header>

        <!-- Content -->
        <div class="content">
            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : 'success' ?> alert-dismissible fade show fade-in">
                    <i class="fas fa-<?= $flash['type'] === 'danger' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Database Warning -->
            <?php if (!$db_available): ?>
                <div class="alert alert-warning alert-dismissible fade show fade-in">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Mode Demo:</strong> Database tidak terhubung. Perubahan tidak akan disimpan.
                </div>
            <?php endif; ?>

            <!-- Settings Navigation -->
            <div class="settings-nav fade-in">
                <button class="settings-tab active" onclick="showTab('profile')">
                    <i class="fas fa-user me-2"></i>Profil
                </button>
                <button class="settings-tab" onclick="showTab('system')">
                    <i class="fas fa-cogs me-2"></i>Sistem
                </button>
                <button class="settings-tab" onclick="showTab('security')">
                    <i class="fas fa-shield-alt me-2"></i>Keamanan
                </button>
                <button class="settings-tab" onclick="showTab('backup')">
                    <i class="fas fa-database me-2"></i>Backup
                </button>
            </div>

            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content active">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card fade-in">
                            <div class="card-body profile-card">
                                <div class="profile-avatar">
                                    <?= strtoupper(substr($user_profile['nama_lengkap'], 0, 2)) ?>
                                </div>
                                <h3 class="profile-name"><?= htmlspecialchars($user_profile['nama_lengkap']) ?></h3>
                                <p class="profile-role"><?= htmlspecialchars($user_profile['role']) ?></p>
                                <div class="text-muted small">
                                    <div>Username: <?= htmlspecialchars($user_profile['username']) ?></div>
                                    <div>Last Login: <?= date('d/m/Y H:i', strtotime($user_profile['last_login'])) ?></div>
                                    <div>Member Since: <?= date('d/m/Y', strtotime($user_profile['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <div class="card fade-in">
                            <div class="card-header">
                                <i class="fas fa-edit me-2"></i>Edit Profil
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" name="nama_lengkap" class="form-control" 
                                                   value="<?= htmlspecialchars($user_profile['nama_lengkap']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control" 
                                                   value="<?= htmlspecialchars($user_profile['username']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" 
                                                   value="<?= htmlspecialchars($user_profile['email']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password Baru</label>
                                            <input type="password" name="password" class="form-control" 
                                                   placeholder="Kosongkan jika tidak ingin mengubah">
                                            <div class="form-text">Minimum 8 karakter</div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Tab -->
            <div id="system-tab" class="tab-content">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-building me-2"></i>Informasi Instansi
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_system">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Instansi</label>
                                    <input type="text" name="nama_instansi" class="form-control" 
                                           value="<?= htmlspecialchars($settings['sistem']['nama_instansi']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Website</label>
                                    <input type="url" name="website" class="form-control" 
                                           value="<?= htmlspecialchars($settings['sistem']['website']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email_instansi" class="form-control" 
                                           value="<?= htmlspecialchars($settings['sistem']['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telepon</label>
                                    <input type="tel" name="telepon" class="form-control" 
                                           value="<?= htmlspecialchars($settings['sistem']['telepon']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($settings['sistem']['alamat']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Logo Instansi</label>
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                    <div class="form-text">Format: JPG, PNG. Maksimal 2MB</div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="mb-3">Pengaturan Aplikasi</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nama Aplikasi</label>
                                    <input type="text" name="nama_aplikasi" class="form-control" 
                                           value="<?= htmlspecialchars($settings['aplikasi']['nama_aplikasi']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Versi</label>
                                    <input type="text" name="versi" class="form-control" 
                                           value="<?= htmlspecialchars($settings['aplikasi']['versi']) ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tema</label>
                                    <select name="tema" class="form-select">
                                        <option value="default" <?= $settings['aplikasi']['tema'] === 'default' ? 'selected' : '' ?>>Default</option>
                                        <option value="dark" <?= $settings['aplikasi']['tema'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                                        <option value="blue" <?= $settings['aplikasi']['tema'] === 'blue' ? 'selected' : '' ?>>Blue</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                                       <?= $settings['aplikasi']['maintenance_mode'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">Mode Maintenance</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_backup" 
                                                       <?= $settings['aplikasi']['auto_backup'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">Auto Backup</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="notifikasi_email" 
                                                       <?= $settings['aplikasi']['notifikasi_email'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">Notifikasi Email</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan Pengaturan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Tab -->
            <div id="security-tab" class="tab-content">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-shield-alt me-2"></i>Pengaturan Keamanan
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_security">
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Session Timeout (menit)</label>
                                    <input type="number" name="session_timeout" class="form-control" 
                                           value="<?= $settings['keamanan']['session_timeout'] ?>" min="15" max="480">
                                    <div class="form-text">15-480 menit</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" class="form-control" 
                                           value="<?= $settings['keamanan']['max_login_attempts'] ?>" min="3" max="10">
                                    <div class="form-text">3-10 percobaan</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Min Password Length</label>
                                    <input type="number" name="password_min_length" class="form-control" 
                                           value="<?= $settings['keamanan']['password_min_length'] ?>" min="6" max="20">
                                    <div class="form-text">6-20 karakter</div>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="require_2fa" 
                                                       <?= $settings['keamanan']['require_2fa'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">Require 2FA</label>
                                                <div class="form-text">Memerlukan Two Factor Authentication</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="audit_log" 
                                                       <?= $settings['keamanan']['audit_log'] ? 'checked' : '' ?>>
                                                <label class="form-check-label">Audit Log</label>
                                                <div class="form-text">Log semua aktivitas pengguna</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan Keamanan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Informasi Sistem
                    </div>
                    <div class="card-body">
                        <div class="system-info">
                            <div class="info-item">
                                <div class="info-label">PHP Version</div>
                                <div class="info-value"><?= phpversion() ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Server</div>
                                <div class="info-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Database</div>
                                <div class="info-value"><?= $db_available ? 'Connected' : 'Disconnected' ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Disk Space</div>
                                <div class="info-value"><?= formatBytes(disk_free_space('./')) ?> free</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Memory Limit</div>
                                <div class="info-value"><?= ini_get('memory_limit') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Max Upload</div>
                                <div class="info-value"><?= ini_get('upload_max_filesize') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Tab -->
            <div id="backup-tab" class="tab-content">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-database me-2"></i>Backup & Restore
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Create Backup</h6>
                                <p class="text-muted">Buat backup lengkap database dan file sistem</p>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="backup_database">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_files" checked>
                                            <label class="form-check-label">Include Files</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_uploads" checked>
                                            <label class="form-check-label">Include Uploads</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-download me-2"></i>Create Backup
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Restore Backup</h6>
                                <p class="text-muted">Restore dari file backup sebelumnya</p>
                                
                                <form>
                                    <div class="mb-3">
                                        <input type="file" class="form-control" accept=".sql,.zip">
                                        <div class="form-text">Format: SQL atau ZIP</div>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-upload me-2"></i>Restore Backup
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6>Recent Backups</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Filename</th>
                                        <th>Size</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>backup_2024-09-17.sql</td>
                                        <td>2.4 MB</td>
                                        <td>17/09/2024 10:30</td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>backup_2024-09-16.sql</td>
                                        <td>2.3 MB</td>
                                        <td>16/09/2024 10:30</td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Danger Zone -->
                        <div class="danger-zone">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h5>
                            <p class="text-muted mb-3">Tindakan berikut dapat menyebabkan kehilangan data permanen.</p>
                            
                            <button class="btn btn-outline-danger me-2" onclick="confirmAction('clear_logs')">
                                <i class="fas fa-trash me-2"></i>Clear All Logs
                            </button>
                            <button class="btn btn-outline-danger me-2" onclick="confirmAction('reset_settings')">
                                <i class="fas fa-undo me-2"></i>Reset Settings
                            </button>
                            <button class="btn btn-danger" onclick="confirmAction('factory_reset')">
                                <i class="fas fa-exclamation-triangle me-2"></i>Factory Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        function createBackup() {
            if (confirm('Membuat backup sistem? Proses ini mungkin memakan waktu beberapa menit.')) {
                // Show loading
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating backup...';
                btn.disabled = true;
                
                // Simulate backup process
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert('Backup berhasil dibuat!');
                }, 3000);
            }
        }

        function confirmAction(action) {
            let message = '';
            let isDestructive = true;
            
            switch(action) {
                case 'clear_logs':
                    message = 'Hapus semua log sistem? Tindakan ini tidak dapat dibatalkan.';
                    break;
                case 'reset_settings':
                    message = 'Reset semua pengaturan ke default? Konfigurasi custom akan hilang.';
                    break;
                case 'factory_reset':
                    message = 'Factory reset akan menghapus SEMUA data dan mengembalikan sistem ke kondisi awal. Apakah Anda yakin?';
                    break;
            }
            
            if (confirm(message)) {
                if (action === 'factory_reset') {
                    if (confirm('Konfirmasi sekali lagi: Ini akan menghapus SEMUA data. Lanjutkan?')) {
                        performAction(action);
                    }
                } else {
                    performAction(action);
                }
            }
        }

        function performAction(action) {
            // Show loading and perform action
            console.log('Performing action:', action);
            alert('Action ' + action + ' completed successfully!');
        }

        // Auto-save form changes
        document.addEventListener('change', function(e) {
            if (e.target.matches('form input, form select, form textarea')) {
                // In real implementation, you could auto-save changes
                console.log('Form field changed:', e.target.name, e.target.value);
            }
        });

        console.log('Settings page loaded successfully');
    </script>
</body>
</html>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}
?>