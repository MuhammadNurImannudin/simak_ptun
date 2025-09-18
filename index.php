<?php
// index.php - Professional Dashboard dengan proporsi rapi
require_once 'config/config.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

// Check database availability
$db_available = false;
try {
    $test_result = fetchSingle("SELECT 1 as test");
    $db_available = ($test_result !== null);
} catch (Exception $e) {
    $db_available = false;
}

// Initialize stats
$stats = [
    'total_surat_masuk' => $db_available ? getTotalSuratMasuk() : 156,
    'total_surat_keluar' => $db_available ? getTotalSuratKeluar() : 89,
    'pending' => $db_available ? getTotalSuratMasuk('pending') : 7,
    'diproses' => $db_available ? getTotalSuratMasuk('diproses') : 12,
    'selesai' => $db_available ? getTotalSuratMasuk('selesai') : 4,
    'draft' => $db_available ? getTotalSuratKeluar('draft') : 3,
    'terkirim' => $db_available ? getTotalSuratKeluar('terkirim') : 13,
    'arsip' => $db_available ? getTotalSuratKeluar('arsip') : 2
];

$stats['surat_masuk_bulan_ini'] = $stats['pending'] + $stats['diproses'] + $stats['selesai'];
$stats['surat_keluar_bulan_ini'] = $stats['draft'] + $stats['terkirim'] + $stats['arsip'];
$completion_rate = $stats['total_surat_masuk'] > 0 ? 
    round(($stats['selesai'] / $stats['total_surat_masuk']) * 100, 1) : 0;

// Chart data
$chart_data = [
    ['month' => 'Apr 2024', 'masuk' => 45, 'keluar' => 32],
    ['month' => 'May 2024', 'masuk' => 38, 'keluar' => 28],
    ['month' => 'Jun 2024', 'masuk' => 52, 'keluar' => 35],
    ['month' => 'Jul 2024', 'masuk' => 41, 'keluar' => 31],
    ['month' => 'Aug 2024', 'masuk' => 47, 'keluar' => 29],
    ['month' => 'Sep 2024', 'masuk' => 39, 'keluar' => 33]
];

$recent_activities = [
    ['type' => 'surat_masuk', 'nomor_surat' => '001/SM/IX/2024', 'pengirim' => 'Dinas Pendidikan', 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['type' => 'surat_keluar', 'nomor_surat' => '045/SK/IX/2024', 'tujuan' => 'Mahkamah Agung', 'status' => 'terkirim', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))],
    ['type' => 'surat_masuk', 'nomor_surat' => '002/SM/IX/2024', 'pengirim' => 'Pemkot Banjarmasin', 'status' => 'diproses', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
];

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIMAK PTUN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stat-change {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .stat-change.positive { color: var(--success-color); }
        .stat-change.negative { color: var(--danger-color); }
        .stat-change.neutral { color: var(--gray-500); }

        /* Quick Stats */
        .quick-stats {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .quick-stats::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .quick-stats-content {
            position: relative;
            z-index: 2;
        }

        .quick-stats-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .quick-stats-subtitle {
            opacity: 0.9;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .quick-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1.5rem;
        }

        .quick-stat {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 1.25rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .quick-stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .quick-stat-label {
            font-size: 0.8125rem;
            opacity: 0.9;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        /* Status Overview */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }

        .status-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.pending { background: var(--warning-color); }
        .status-dot.diproses { background: var(--info-color); }
        .status-dot.selesai { background: var(--success-color); }
        .status-dot.draft { background: var(--gray-400); }
        .status-dot.terkirim { background: var(--success-color); }
        .status-dot.arsip { background: var(--info-color); }

        .status-label {
            font-size: 0.875rem;
            color: var(--gray-700);
            font-weight: 500;
        }

        .status-count {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        /* Activity Feed */
        .activity-feed {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            height: fit-content;
        }

        .activity-header {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-100);
            position: relative;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -1rem;
            top: 1.25rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-color);
        }

        .activity-content {
            margin-left: 0.5rem;
        }

        .activity-title {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .activity-desc {
            color: var(--gray-600);
            font-size: 0.8125rem;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: var(--gray-400);
            font-size: 0.75rem;
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

        .btn-outline {
            background: transparent;
            color: var(--gray-600);
            border-color: var(--gray-300);
        }

        .btn-outline:hover {
            background: var(--gray-50);
            color: var(--gray-700);
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-gavel"></i>
                SIMAK PTUN
            </a>
            <div class="sidebar-subtitle">
                Pengadilan Tata Usaha Negara<br>Banjarmasin
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="pages/surat-masuk/" class="nav-link">
                    <i class="fas fa-inbox"></i>
                    Surat Masuk
                </a>
            </div>
            <div class="nav-item">
                <a href="pages/surat-keluar/" class="nav-link">
                    <i class="fas fa-paper-plane"></i>
                    Surat Keluar
                </a>
            </div>
            <div class="nav-item">
                <a href="pages/users/" class="nav-link">
                    <i class="fas fa-users"></i>
                    Manajemen User
                </a>
            </div>
            <div class="nav-item">
                <a href="pages/reports/" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Laporan
                </a>
            </div>
            <div class="nav-item">
                <a href="pages/settings/" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Pengaturan
                </a>
            </div>
            <div class="nav-item" style="margin-top: 2rem;">
                <a href="auth/logout.php" class="nav-link">
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
                <h1 class="header-title">Dashboard</h1>
                <p class="header-subtitle">Sistem Informasi Manajemen Arsip dan Korespondensi</p>
            </div>
            <div class="header-actions">
                <span class="text-sm text-gray-500">
                    <?= isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Administrator' ?>
                </span>
            </div>
        </header>

        <!-- Content -->
        <div class="content">
            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : 'warning' ?> alert-dismissible fade show">
                    <i class="fas fa-<?= $flash['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle' ?> me-2"></i>
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Database Warning -->
            <?php if (!$db_available): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Mode Demo:</strong> Database tidak terhubung. Menampilkan data contoh.
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="quick-stats fade-in">
                <div class="quick-stats-content">
                    <h2 class="quick-stats-title">Ringkasan Hari Ini</h2>
                    <p class="quick-stats-subtitle">Data terkini sistem manajemen surat</p>
                    <div class="quick-stats-grid">
                        <div class="quick-stat">
                            <div class="quick-stat-number"><?= $stats['surat_masuk_bulan_ini'] ?></div>
                            <div class="quick-stat-label">Surat Masuk Bulan Ini</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-number"><?= $stats['surat_keluar_bulan_ini'] ?></div>
                            <div class="quick-stat-label">Surat Keluar Bulan Ini</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-number"><?= $completion_rate ?>%</div>
                            <div class="quick-stat-label">Tingkat Penyelesaian</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-number"><?= $stats['pending'] ?></div>
                            <div class="quick-stat-label">Pending</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid fade-in">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-inbox"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_surat_masuk']) ?></div>
                    <div class="stat-label">Total Surat Masuk</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?= $stats['surat_masuk_bulan_ini'] ?> bulan ini
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_surat_keluar']) ?></div>
                    <div class="stat-label">Total Surat Keluar</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?= $stats['surat_keluar_bulan_ini'] ?> bulan ini
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= $completion_rate ?>%</div>
                    <div class="stat-label">Tingkat Penyelesaian</div>
                    <div class="stat-change neutral">
                        Target: 85%
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Surat Pending</div>
                    <div class="stat-change <?= $stats['pending'] > 10 ? 'negative' : 'neutral' ?>">
                        <?= $stats['pending'] > 10 ? 'Perlu perhatian' : 'Dalam batas normal' ?>
                    </div>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="status-grid fade-in">
                <div class="status-card">
                    <div class="status-card-title">
                        <i class="fas fa-inbox"></i>
                        Status Surat Masuk
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <div class="status-dot pending"></div>
                            <span class="status-label">Pending</span>
                        </div>
                        <span class="status-count"><?= $stats['pending'] ?></span>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <div class="status-dot diproses"></div>
                            <span class="status-label">Diproses</span>
                        </div>
                        <span class="status-count"><?= $stats['diproses'] ?></span>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <div class="status-dot selesai"></div>
                            <span class="status-label">Selesai</span>
                        </div>
                        <span class="status-count"><?= $stats['selesai'] ?></span>
                    </div>
                </div>

                <div class="status-card">
                    <div class="status-card-title">
                        <i class="fas fa-paper-plane"></i>
                        Status Surat Keluar
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <div class="status-dot draft"></div>
                            <span class="status-label">Draft</span>
                        </div>
                        <span class="status-count"><?= $stats['draft'] ?></span>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <div class="status-dot terkirim"></div>
                            <span class="status-label">Terkirim</span>
                        </div>
                        <span class="status-count"><?= $stats['terkirim'] ?></span>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <div class="status-dot arsip"></div>
                            <span class="status-label">Arsip</span>
                        </div>
                        <span class="status-count"><?= $stats['arsip'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Chart and Activity -->
            <div class="row fade-in">
                <div class="col-lg-8 mb-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">
                                <i class="fas fa-chart-line me-2"></i>
                                Trend Surat 6 Bulan Terakhir
                            </h3>
                        </div>
                        <canvas id="trendChart" height="300"></canvas>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="activity-feed">
                        <div class="activity-header">
                            <i class="fas fa-history"></i>
                            Aktivitas Terbaru
                        </div>
                        
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?= htmlspecialchars($activity['nomor_surat']) ?>
                                    </div>
                                    <div class="activity-desc">
                                        <?php if ($activity['type'] === 'surat_masuk'): ?>
                                            Dari: <?= htmlspecialchars($activity['pengirim']) ?>
                                        <?php else: ?>
                                            Ke: <?= htmlspecialchars($activity['tujuan']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= timeAgo($activity['created_at']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-3 text-center">
                            <a href="pages/surat-masuk/" class="btn btn-outline me-2">
                                <i class="fas fa-inbox"></i>
                                Surat Masuk
                            </a>
                            <a href="pages/surat-keluar/" class="btn btn-outline">
                                <i class="fas fa-paper-plane"></i>
                                Surat Keluar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart configuration
        const chartLabels = <?= json_encode(array_column($chart_data, 'month')) ?>;
        const chartMasukData = <?= json_encode(array_column($chart_data, 'masuk')) ?>;
        const chartKeluarData = <?= json_encode(array_column($chart_data, 'keluar')) ?>;

        // Initialize chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Surat Masuk',
                        data: chartMasukData,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4f46e5',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: 'Surat Keluar',
                        data: chartKeluarData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    }
                }
            }
        });

        console.log('Dashboard loaded successfully');
    </script>
</body>
</html>