<?php
// pages/reports/index.php - Fixed Reports Page
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
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

// Initialize statistics
$stats = [
    'total_surat_masuk' => 0,
    'total_surat_keluar' => 0,
    'surat_masuk_bulan_ini' => 0,
    'surat_keluar_bulan_ini' => 0
];

// Sample data if database not available
if (!$db_available) {
    $stats = [
        'total_surat_masuk' => 156,
        'total_surat_keluar' => 89,
        'surat_masuk_bulan_ini' => 23,
        'surat_keluar_bulan_ini' => 12
    ];
} else {
    try {
        // Get real statistics from database
        $surat_masuk_total = fetchSingle("SELECT COUNT(*) as total FROM surat_masuk");
        $surat_keluar_total = fetchSingle("SELECT COUNT(*) as total FROM surat_keluar");
        $surat_masuk_bulan_ini = fetchSingle("SELECT COUNT(*) as total FROM surat_masuk WHERE MONTH(tanggal_terima) = MONTH(CURDATE())");
        $surat_keluar_bulan_ini = fetchSingle("SELECT COUNT(*) as total FROM surat_keluar WHERE MONTH(tanggal_surat) = MONTH(CURDATE())");
        
        $stats = [
            'total_surat_masuk' => $surat_masuk_total['total'] ?? 0,
            'total_surat_keluar' => $surat_keluar_total['total'] ?? 0,
            'surat_masuk_bulan_ini' => $surat_masuk_bulan_ini['total'] ?? 0,
            'surat_keluar_bulan_ini' => $surat_keluar_bulan_ini['total'] ?? 0
        ];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - SIMAK PTUN</title>
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

        .btn-outline-primary {
            background: transparent;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
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

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-color: #fcd34d;
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
                <a href="../reports/" class="nav-link active">
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
                <h1 class="header-title">Laporan</h1>
                <p class="header-subtitle">Generate dan lihat laporan statistik sistem</p>
            </div>
        </header>

        <!-- Content -->
        <div class="content">
            <!-- Database Warning -->
            <?php if (!$db_available): ?>
                <div class="alert alert-warning fade-in">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Mode Demo:</strong> Database tidak terhubung. Menampilkan data contoh.
                </div>
            <?php endif; ?>

            <!-- Statistics Overview -->
            <div class="stats-grid fade-in">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-inbox"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_surat_masuk']) ?></div>
                    <div class="stat-label">Total Surat Masuk</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_surat_keluar']) ?></div>
                    <div class="stat-label">Total Surat Keluar</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($stats['surat_masuk_bulan_ini']) ?></div>
                    <div class="stat-label">Surat Masuk Bulan Ini</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($stats['surat_keluar_bulan_ini']) ?></div>
                    <div class="stat-label">Surat Keluar Bulan Ini</div>
                </div>
            </div>

            <!-- Report Generation -->
            <div class="card fade-in">
                <div class="card-header">
                    <i class="fas fa-file-alt me-2"></i>Generate Laporan
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Jenis Laporan</label>
                            <select name="report_type" class="form-select" required>
                                <option value="">Pilih Jenis Laporan</option>
                                <option value="surat_masuk">Laporan Surat Masuk</option>
                                <option value="surat_keluar">Laporan Surat Keluar</option>
                                <option value="rekap_bulanan">Rekap Bulanan</option>
                                <option value="rekap_tahunan">Rekap Tahunan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-chart-bar"></i>
                                    Generate
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Export Options -->
            <div class="card fade-in">
                <div class="card-header">
                    <i class="fas fa-download me-2"></i>Export Cepat
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="d-grid">
                                <button class="btn btn-success" onclick="exportExcel('surat_masuk')">
                                    <i class="fas fa-file-excel me-2"></i>
                                    Export Surat Masuk
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-grid">
                                <button class="btn btn-info" onclick="exportExcel('surat_keluar')">
                                    <i class="fas fa-file-excel me-2"></i>
                                    Export Surat Keluar
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-grid">
                                <button class="btn btn-warning" onclick="exportPDF('rekap')">
                                    <i class="fas fa-file-pdf me-2"></i>
                                    Export PDF Rekap
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-grid">
                                <button class="btn btn-danger" onclick="printReport()">
                                    <i class="fas fa-print me-2"></i>
                                    Print Laporan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportExcel(type) {
            alert('Export ' + type + ' ke Excel (fitur akan diimplementasikan)');
        }

        function exportPDF(type) {
            alert('Export ' + type + ' ke PDF (fitur akan diimplementasikan)');
        }

        function printReport() {
            window.print();
        }

        console.log('Reports page loaded successfully');
    </script>
</body>
</html>