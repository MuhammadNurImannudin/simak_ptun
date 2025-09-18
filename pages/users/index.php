<?php
// pages/users/index.php - Professional Users Management Page
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit();
}

// Check if user is admin (in real implementation)
$is_admin = true; // For demo purposes

// Check database availability
$db_available = false;
try {
    $test_result = fetchSingle("SELECT 1 as test");
    $db_available = ($test_result !== null);
} catch (Exception $e) {
    $db_available = false;
}

// Handle actions if database available
if ($db_available && $is_admin) {
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            executeQuery("DELETE FROM users WHERE id = ? AND id != ?", [$id, $_SESSION['user_id'] ?? 1]);
            setFlashMessage('success', 'User berhasil dihapus');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal menghapus user: ' . $e->getMessage());
        }
        redirect('index.php');
    }

    // Handle status toggle
    if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            $user = fetchSingle("SELECT status FROM users WHERE id = ?", [$id]);
            $new_status = $user['status'] === 'aktif' ? 'nonaktif' : 'aktif';
            executeQuery("UPDATE users SET status = ? WHERE id = ?", [$new_status, $id]);
            setFlashMessage('success', 'Status user berhasil diperbarui');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal memperbarui status: ' . $e->getMessage());
        }
        redirect('index.php');
    }
}

// Initialize users data
$users = [];
$user_stats = [
    'total_users' => 0,
    'aktif' => 0,
    'nonaktif' => 0,
    'admin' => 0
];

// Sample data if database not available
if (!$db_available) {
    $users = [
        [
            'id' => 1,
            'nama_lengkap' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@ptun-banjarmasin.go.id',
            'role' => 'Administrator',
            'status' => 'aktif',
            'last_login' => '2024-09-17 10:30:00',
            'created_at' => '2024-01-01 08:00:00'
        ],
        [
            'id' => 2,
            'nama_lengkap' => 'Siti Nurhaliza',
            'username' => 'siti.n',
            'email' => 'siti.n@ptun-banjarmasin.go.id',
            'role' => 'Staff TU',
            'status' => 'aktif',
            'last_login' => '2024-09-17 09:15:00',
            'created_at' => '2024-02-15 10:00:00'
        ],
        [
            'id' => 3,
            'nama_lengkap' => 'Muhammad Rizki',
            'username' => 'rizki.m',
            'email' => 'rizki.m@ptun-banjarmasin.go.id',
            'role' => 'Panitera',
            'status' => 'aktif',
            'last_login' => '2024-09-16 16:45:00',
            'created_at' => '2024-03-10 14:00:00'
        ],
        [
            'id' => 4,
            'nama_lengkap' => 'Putri Wulandari',
            'username' => 'putri.w',
            'email' => 'putri.w@ptun-banjarmasin.go.id',
            'role' => 'Kasubbag',
            'status' => 'nonaktif',
            'last_login' => '2024-09-10 11:20:00',
            'created_at' => '2024-04-05 09:00:00'
        ],
        [
            'id' => 5,
            'nama_lengkap' => 'Ahmad Fauzi',
            'username' => 'ahmad.f',
            'email' => 'ahmad.f@ptun-banjarmasin.go.id',
            'role' => 'Staff TU',
            'status' => 'aktif',
            'last_login' => '2024-09-17 08:00:00',
            'created_at' => '2024-05-20 13:30:00'
        ]
    ];
    
    $user_stats = [
        'total_users' => 5,
        'aktif' => 4,
        'nonaktif' => 1,
        'admin' => 1
    ];
} else {
    // Get data from database
    try {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $users = fetchAll($sql);
        
        $stats = fetchAll("SELECT status, COUNT(*) as count FROM users GROUP BY status");
        foreach ($stats as $stat) {
            $user_stats[$stat['status']] = $stat['count'];
            $user_stats['total_users'] += $stat['count'];
        }
        
        $admin_count = fetchSingle("SELECT COUNT(*) as count FROM users WHERE role IN ('Administrator', 'admin')");
        $user_stats['admin'] = $admin_count['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $users = [];
    }
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - SIMAK PTUN</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card.primary::before { background: var(--primary-color); }
        .stat-card.success::before { background: var(--success-color); }
        .stat-card.warning::before { background: var(--warning-color); }
        .stat-card.info::before { background: var(--info-color); }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.primary { background: var(--primary-color); }
        .stat-icon.success { background: var(--success-color); }
        .stat-icon.warning { background: var(--warning-color); }
        .stat-icon.info { background: var(--info-color); }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            border-top: none;
            padding: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table tbody td {
            padding: 1rem;
            border-color: var(--gray-100);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: var(--gray-50);
        }

        /* User Avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Status & Role Badges */
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge.bg-success {
            background-color: #dcfce7 !important;
            color: #166534 !important;
        }

        .badge.bg-danger {
            background-color: #fecaca !important;
            color: #991b1b !important;
        }

        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }

        .badge.bg-warning {
            background-color: #fef3c7 !important;
            color: #92400e !important;
        }

        .badge.bg-info {
            background-color: #dbeafe !important;
            color: #1e40af !important;
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

        .btn-info {
            background: var(--info-color);
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

        /* Form Styles */
        .form-control, .form-select {
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        .empty-state h4 {
            color: var(--gray-600);
            margin-bottom: 0.5rem;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .online-indicator {
            position: relative;
        }

        .online-indicator::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: var(--success-color);
            border: 2px solid white;
            border-radius: 50%;
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
                <a href="../users/" class="nav-link active">
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
                <a href="../settings/" class="nav-link">
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
                <h1 class="header-title">Manajemen User</h1>
                <p class="header-subtitle">Kelola akses pengguna sistem SIMAK PTUN</p>
            </div>
            <div class="header-actions">
                <?php if ($is_admin): ?>
                    <a href="tambah.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah User
                    </a>
                <?php endif; ?>
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
                    <strong>Mode Demo:</strong> Database tidak terhubung. Menampilkan data contoh.
                </div>
            <?php endif; ?>

            <!-- Access Control Warning -->
            <?php if (!$is_admin): ?>
                <div class="alert alert-warning alert-dismissible fade show fade-in">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Akses Terbatas:</strong> Anda hanya dapat melihat data user. Kontak administrator untuk mengelola user.
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid fade-in">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($user_stats['total_users']) ?></div>
                    <div class="stat-label">Total Users</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($user_stats['aktif']) ?></div>
                    <div class="stat-label">Users Aktif</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($user_stats['nonaktif']) ?></div>
                    <div class="stat-label">Users Nonaktif</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($user_stats['admin']) ?></div>
                    <div class="stat-label">Administrator</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card fade-in">
                <div class="card-header">
                    <i class="fas fa-filter me-2"></i>Filter & Pencarian User
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?= ($_GET['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($_GET['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="">Semua Role</option>
                                <option value="Administrator" <?= ($_GET['role'] ?? '') === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                                <option value="Staff TU" <?= ($_GET['role'] ?? '') === 'Staff TU' ? 'selected' : '' ?>>Staff TU</option>
                                <option value="Panitera" <?= ($_GET['role'] ?? '') === 'Panitera' ? 'selected' : '' ?>>Panitera</option>
                                <option value="Kasubbag" <?= ($_GET['role'] ?? '') === 'Kasubbag' ? 'selected' : '' ?>>Kasubbag</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pencarian</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Nama, username, email..." 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container fade-in">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-users me-2"></i>Daftar Users
                            <span class="badge bg-primary ms-2"><?= count($users) ?> users</span>
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm me-2">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                            <button class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h4>Tidak Ada User</h4>
                        <p class="text-muted">Belum ada user yang terdaftar dalam sistem</p>
                        <?php if ($is_admin): ?>
                            <a href="tambah.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Tambah User Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">User</th>
                                    <th style="width: 20%;">Email</th>
                                    <th style="width: 15%;">Role</th>
                                    <th style="width: 12%;">Status</th>
                                    <th style="width: 18%;">Last Login</th>
                                    <th style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="user-avatar <?= $user['status'] === 'aktif' ? 'online-indicator' : '' ?>">
                                                    <?= strtoupper(substr($user['nama_lengkap'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-primary"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                                                    <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($user['email']) ?></div>
                                            <small class="text-muted">
                                                Bergabung: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $role_class = 'bg-info';
                                            if ($user['role'] === 'Administrator') $role_class = 'bg-danger';
                                            if ($user['role'] === 'Panitera') $role_class = 'bg-warning';
                                            if ($user['role'] === 'Kasubbag') $role_class = 'bg-primary';
                                            ?>
                                            <span class="badge <?= $role_class ?>">
                                                <?= htmlspecialchars($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $user['status'] === 'aktif' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-medium">
                                                <?= $user['last_login'] ? date('d/m/Y', strtotime($user['last_login'])) : '-' ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= $user['last_login'] ? date('H:i', strtotime($user['last_login'])) : 'Belum login' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($is_admin): ?>
                                                <div class="btn-group" role="group">
                                                    <a href="detail.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-info btn-sm" title="Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" 
                                                       onclick="toggleStatus('?action=toggle_status&id=<?= $user['id'] ?>')"
                                                       class="btn btn-<?= $user['status'] === 'aktif' ? 'warning' : 'success' ?> btn-sm" 
                                                       title="<?= $user['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                        <i class="fas fa-<?= $user['status'] === 'aktif' ? 'user-slash' : 'user-check' ?>"></i>
                                                    </a>
                                                    <?php if ($user['id'] != ($_SESSION['user_id'] ?? 1)): ?>
                                                        <a href="javascript:void(0)" 
                                                           onclick="confirmDelete('?action=delete&id=<?= $user['id'] ?>')"
                                                           class="btn btn-danger btn-sm" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <a href="detail.php?id=<?= $user['id'] ?>" 
                                                   class="btn btn-info btn-sm" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(url) {
            if (confirm('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.')) {
                window.location.href = url;
            }
        }

        function toggleStatus(url) {
            if (confirm('Apakah Anda yakin ingin mengubah status user ini?')) {
                window.location.href = url;
            }
        }

        // Auto refresh user status every 30 seconds
        setInterval(function() {
            const onlineIndicators = document.querySelectorAll('.online-indicator');
            // In real implementation, you would check actual user status
            console.log('Checking user status...');
        }, 30000);

        console.log('Users management page loaded successfully');
    </script>
</body>
</html>