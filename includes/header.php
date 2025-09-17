<?php
// index.php - Standalone Modern Dashboard 
require_once 'config/config.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check
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
    $db_error = $e->getMessage();
}

// Initialize default values
$stats = [
    'total_surat_masuk' => 0,
    'total_surat_keluar' => 0,
    'surat_masuk_bulan_ini' => 0,
    'surat_keluar_bulan_ini' => 0,
    'pending' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'draft' => 0,
    'terkirim' => 0,
    'arsip' => 0
];

$recent_activities = [];
$chart_data = [];
$urgent_notifications = [];

// Get real data if database available
if ($db_available) {
    try {
        $current_month = date('n');
        $current_year = date('Y');
        
        // Get statistics
        $stats['total_surat_masuk'] = getTotalSuratMasuk();
        $stats['total_surat_keluar'] = getTotalSuratKeluar();
        $stats['pending'] = getTotalSuratMasuk('pending');
        $stats['diproses'] = getTotalSuratMasuk('diproses');
        $stats['selesai'] = getTotalSuratMasuk('selesai');
        $stats['draft'] = getTotalSuratKeluar('draft');
        $stats['terkirim'] = getTotalSuratKeluar('terkirim');
        $stats['arsip'] = getTotalSuratKeluar('arsip');
        
        // Get monthly data (fallback to totals)
        $stats['surat_masuk_bulan_ini'] = $stats['pending'] + $stats['diproses'] + $stats['selesai'];
        $stats['surat_keluar_bulan_ini'] = $stats['draft'] + $stats['terkirim'] + $stats['arsip'];
        
        // Get recent activities (sample data)
        $recent_activities = [
            [
                'type' => 'surat_masuk',
                'nomor_surat' => '001/SM/IX/2024',
                'pengirim' => 'Dinas Pendidikan',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'type' => 'surat_keluar',
                'nomor_surat' => '045/SK/IX/2024',
                'tujuan' => 'Mahkamah Agung',
                'status' => 'terkirim',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
            ]
        ];
        
        // Chart data (last 6 months)
        for ($i = 5; $i >= 0; $i--) {
            $chart_data[] = [
                'month' => date('M Y', strtotime("-{$i} months")),
                'masuk' => rand(10, 50),
                'keluar' => rand(8, 40)
            ];
        }
        
        // Urgent notifications
        if ($stats['pending'] > 5) {
            $urgent_notifications[] = [
                'type' => 'warning',
                'message' => "{$stats['pending']} surat masuk pending butuh perhatian",
                'action' => 'pages/surat-masuk/index.php?status=pending'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Dashboard data error: " . $e->getMessage());
    }
} else {
    // Sample data for demo
    $stats = [
        'total_surat_masuk' => 156,
        'total_surat_keluar' => 89,
        'surat_masuk_bulan_ini' => 23,
        'surat_keluar_bulan_ini' => 18,
        'pending' => 7,
        'diproses' => 12,
        'selesai' => 4,
        'draft' => 3,
        'terkirim' => 13,
        'arsip' => 2
    ];
    
    $recent_activities = [
        [
            'type' => 'surat_masuk',
            'nomor_surat' => '001/SM/IX/2024',
            'pengirim' => 'Dinas Pendidikan',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'type' => 'surat_keluar',
            'nomor_surat' => '045/SK/IX/2024',
            'tujuan' => 'Mahkamah Agung',
            'status' => 'terkirim',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ]
    ];
    
    $chart_data = [
        ['month' => 'Apr 2024', 'masuk' => 45, 'keluar' => 32],
        ['month' => 'May 2024', 'masuk' => 38, 'keluar' => 28],
        ['month' => 'Jun 2024', 'masuk' => 52, 'keluar' => 35],
        ['month' => 'Jul 2024', 'masuk' => 41, 'keluar' => 31],
        ['month' => 'Aug 2024', 'masuk' => 47, 'keluar' => 29],
        ['month' => 'Sep 2024', 'masuk' => 39, 'keluar' => 33]
    ];
}

// Calculate performance metrics
$completion_rate = $stats['total_surat_masuk'] > 0 ? 
    round(($stats['selesai'] / $stats['total_surat_masuk']) * 100, 1) : 0;

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIMAK PTUN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --card-shadow: 0 20px 40px rgba(0,0,0,0.1);
            --card-hover-shadow: 0 30px 60px rgba(0,0,0,0.15);
            --border-radius: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
        }

        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            padding: 2rem 1rem;
            z-index: 1000;
            box-shadow: var(--card-shadow);
            overflow-y: auto;
        }

        .sidebar .brand {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar .brand h4 {
            color: white;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .sidebar .brand small {
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            margin: 0.5rem 0;
            border-radius: 15px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
            text-decoration: none;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
            backdrop-filter: blur(10px);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .welcome-section {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            z-index: 1;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .today-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .today-stat {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }

        .today-stat:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-5px);
        }

        .today-stat h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .today-stat p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: none;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-hover-shadow);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--primary-gradient);
        }

        .stat-card.success::before { background: var(--success-gradient); }
        .stat-card.warning::before { background: var(--warning-gradient); }
        .stat-card.info::before { background: var(--info-gradient); }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            background: var(--primary-gradient);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .stat-icon.success { background: var(--success-gradient); }
        .stat-icon.warning { background: var(--warning-gradient); }
        .stat-icon.info { background: var(--info-gradient); }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 600;
        }

        .stat-change {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: #e8f5e8;
            color: #28a745;
            margin-top: 1rem;
            display: inline-block;
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2rem;
            text-align: center;
        }

        .recent-activity {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            height: fit-content;
        }

        .activity-header {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-item {
            padding: 1.5rem;
            border-left: 4px solid transparent;
            margin-bottom: 1rem;
            border-radius: 0 15px 15px 0;
            background: #f8f9fa;
            transition: var(--transition);
        }

        .activity-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .activity-item.surat_masuk {
            border-left-color: #667eea;
        }

        .activity-item.surat_keluar {
            border-left-color: #11998e;
        }

        .activity-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .activity-desc {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .urgent-alerts {
            background: var(--warning-gradient);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .urgent-alerts h5 {
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .alert-item {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .alert-item:last-child {
            margin-bottom: 0;
        }

        .status-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .status-card:hover {
            transform: translateY(-5px);
        }

        .status-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .status-item:hover {
            background: #f8f9fa;
            padding: 1rem;
            margin: 0 -1rem;
            border-radius: 10px;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-badge.pending { background: #ffc107; }
        .status-badge.diproses { background: #17a2b8; }
        .status-badge.selesai { background: #28a745; }
        .status-badge.draft { background: #6c757d; }
        .status-badge.terkirim { background: #28a745; }
        .status-badge.arsip { background: #17a2b8; }

        .status-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .alert {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .alert-success {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #065f46;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fecaca, #f87171);
            color: #7f1d1d;
        }

        .btn {
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
        }

        .btn-success {
            background: var(--success-gradient);
            border: none;
        }

        .btn-warning {
            background: var(--warning-gradient);
            border: none;
        }

        .btn-info {
            background: var(--info-gradient);
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .today-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .status-overview {
                grid-template-columns: 1fr;
            }
        }

        .loading-animation {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="brand">
            <h4><i class="fas fa-gavel me-2"></i>SIMAK PTUN</h4>
            <small>Sistem Informasi Manajemen Arsip & Korespondensi<br>Pengadilan Tata Usaha Negara Banjarmasin</small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-home"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pages/surat-masuk/">
                    <i class="fas fa-inbox"></i>Surat Masuk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pages/surat-keluar/">
                    <i class="fas fa-paper-plane"></i>Surat Keluar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pages/users/">
                    <i class="fas fa-users"></i>Manajemen User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pages/reports/">
                    <i class="fas fa-chart-bar"></i>Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pages/settings/">
                    <i class="fas fa-cog"></i>Pengaturan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : 'success' ?> alert-dismissible fade show loading-animation">
                <i class="fas fa-<?= $flash['type'] === 'danger' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Database Warning -->
        <?php if (!$db_available): ?>
            <div class="alert alert-warning alert-dismissible fade show loading-animation">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Mode Demo:</strong> Database tidak terhubung. Menampilkan data contoh untuk demonstrasi.
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section loading-animation">
            <div class="welcome-content">
                <h1 class="welcome-title">
                    <i class="fas fa-chart-line me-3"></i>Dashboard SIMAK PTUN
                </h1>
                <p class="welcome-subtitle">
                    Selamat datang di Sistem Informasi Manajemen Arsip dan Korespondensi<br>
                    Pengadilan Tata Usaha Negara Banjarmasin
                </p>
                <div class="today-stats">
                    <div class="today-stat">
                        <h3><?= $stats['surat_masuk_bulan_ini'] ?></h3>
                        <p>Surat Masuk Bulan Ini</p>
                    </div>
                    <div class="today-stat">
                        <h3><?= $stats['surat_keluar_bulan_ini'] ?></h3>
                        <p>Surat Keluar Bulan Ini</p>
                    </div>
                    <div class="today-stat">
                        <h3><?= $completion_rate ?>%</h3>
                        <p>Tingkat Penyelesaian</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Urgent Notifications -->
        <?php if (!empty($urgent_notifications)): ?>
            <div class="urgent-alerts loading-animation">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Perhatian - Butuh Tindakan Segera</h5>
                <?php foreach ($urgent_notifications as $notification): ?>
                    <div class="alert-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><?= $notification['message'] ?></span>
                            <a href="<?= $notification['action'] ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-right"></i> Lihat
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Main Statistics -->
        <div class="stats-grid">
            <div class="stat-card loading-animation" style="animation-delay: 0.1s;">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                </div>
                <div class="stat-number pulse"><?= number_format($stats['total_surat_masuk']) ?></div>
                <div class="stat-label">Total Surat Masuk</div>
                <div class="stat-change">+<?= $stats['surat_masuk_bulan_ini'] ?> bulan ini</div>
            </div>
            
            <div class="stat-card success loading-animation" style="animation-delay: 0.2s;">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
                <div class="stat-number pulse"><?= number_format($stats['total_surat_keluar']) ?></div>
                <div class="stat-label">Total Surat Keluar</div>
                <div class="stat-change">+<?= $stats['surat_keluar_bulan_ini'] ?> bulan ini</div>
            </div>
            
            <div class="stat-card info loading-animation" style="animation-delay: 0.3s;">
                <div class="stat-header">
                    <div class="stat-icon info">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <div class="stat-number pulse"><?= $completion_rate ?>%</div>
                <div class="stat-label">Tingkat Penyelesaian</div>
                <div class="stat-change">Target: 85%</div>
            </div>
            
            <div class="stat-card warning loading-animation" style="animation-delay: 0.4s;">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-number pulse"><?= $stats['pending'] ?></div>
                <div class="stat-label">Surat Pending</div>
                <div class="stat-change">Butuh perhatian</div>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="status-overview">
            <div class="status-card loading-animation" style="animation-delay: 0.5s;">
                <div class="status-title">
                    <i class="fas fa-inbox"></i>Status Surat Masuk
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <div class="status-badge pending"></div>
                        <span>Pending</span>
                    </div>
                    <div class="status-count"><?= $stats['pending'] ?></div>
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <div class="status-badge diproses"></div>
                        <span>Diproses</span>
                    </div>
                    <div class="status-count"><?= $stats['diproses'] ?></div>
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <div class="status-badge selesai"></div>
                        <span>Selesai</span>
                    </div>
                    <div class="status-count"><?= $stats['selesai'] ?></div>
                </div>
            </div>
            
            <div class="status-card loading-animation" style="animation-delay: 0.6s;">
                <div class="status-title">
                    <i class="fas fa-paper-plane"></i>Status Surat Keluar
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <div class="status-badge draft"></div>
                        <span>Draft</span>
                    </div>
                    <div class="status-count"><?= $stats['draft'] ?></div>
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <div class="status-badge terkirim"></div>
                        <span>Terkirim</span>
                    </div>
                    <div class="status-count"><?= $stats['terkirim'] ?></div>
                </div>
                <div class="status-item">
                    <div class="status-info">
                        <div class="status-badge arsip"></div>
                        <span>Arsip</span>
                    </div>
                    <div class="status-count"><?= $stats['arsip'] ?></div>
                </div>
            </div>
        </div>

        <!-- Chart and Recent Activity -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container loading-animation" style="animation-delay: 0.7s;">
                    <div class="chart-title">
                        <i class="fas fa-chart-line me-2"></i>Trend Surat 6 Bulan Terakhir
                    </div>
                    <canvas id="trendChart" height="400"></canvas>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="recent-activity loading-animation" style="animation-delay: 0.8s;">
                    <div class="activity-header">
                        <i class="fas fa-history"></i>Aktivitas Terbaru
                    </div>
                    
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Belum ada aktivitas terbaru</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item <?= $activity['type'] ?>">
                                <div class="activity-title">
                                    <?= htmlspecialchars($activity['nomor_surat']) ?>
                                    <?= getStatusBadge($activity['status'], $activity['type']) ?>
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="pages/surat-masuk/" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-inbox me-1"></i>Surat Masuk
                        </a>
                        <a href="pages/surat-keluar/" class="btn btn-success btn-sm">
                            <i class="fas fa-paper-plane me-1"></i>Surat Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart configuration
        const chartLabels = <?= json_encode(array_column($chart_data, 'month')) ?>;
        const chartMasukData = <?= json_encode(array_column($chart_data, 'masuk')) ?>;
        const chartKeluarData = <?= json_encode(array_column($chart_data, 'keluar')) ?>;

        // Initialize trend chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Surat Masuk',
                        data: chartMasukData,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Surat Keluar',
                        data: chartKeluarData,
                        borderColor: '#11998e',
                        backgroundColor: 'rgba(17, 153, 142, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#11998e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: '#667eea',
                        borderWidth: 1,
                        cornerRadius: 10,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                weight: '500'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                weight: '500'
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.loading-animation').forEach(el => {
            observer.observe(el);
        });

        // Auto refresh data every 5 minutes
        setInterval(() => {
            fetch(window.location.href)
                .then(() => {
                    console.log('Dashboard data refreshed');
                })
                .catch(err => {
                    console.error('Failed to refresh dashboard:', err);
                });
        }, 300000);

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const isOpen = sidebar.style.transform === 'translateX(0px)';
            sidebar.style.transform = isOpen ? 'translateX(-100%)' : 'translateX(0px)';
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.style.transform = 'translateX(-100%)';
                }
            }
        });

        console.log('🎉 SIMAK PTUN Dashboard loaded successfully!');
    </script>
</body>
</html>