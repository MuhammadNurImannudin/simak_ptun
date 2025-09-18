<?php
// pages/surat-keluar/index.php - Professional Surat Keluar Page
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

// Handle actions if database available
if ($db_available) {
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        try {
            $surat = fetchSingle("SELECT file_surat FROM surat_keluar WHERE id = ?", [$id]);
            executeQuery("DELETE FROM surat_keluar WHERE id = ?", [$id]);
            
            if ($surat && isset($surat['file_surat']) && $surat['file_surat'] && file_exists(UPLOAD_PATH . $surat['file_surat'])) {
                unlink(UPLOAD_PATH . $surat['file_surat']);
            }
            
            setFlashMessage('success', 'Surat keluar berhasil dihapus');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal menghapus surat keluar: ' . $e->getMessage());
        }
        redirect('index.php');
    }

    // Handle status update
    if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $id = (int)$_GET['id'];
        $status = sanitize($_GET['status']);
        
        if (in_array($status, ['draft', 'terkirim', 'arsip'])) {
            try {
                executeQuery("UPDATE surat_keluar SET status = ? WHERE id = ?", [$status, $id]);
                setFlashMessage('success', 'Status surat berhasil diperbarui');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Gagal memperbarui status: ' . $e->getMessage());
            }
        }
        redirect('index.php');
    }
}

// Initialize variables
$surat_keluar = [];
$total_records = 0;

// Sample data if database not available
if (!$db_available) {
    $surat_keluar = [
        [
            'id' => 1,
            'nomor_surat' => '001/SK/2024',
            'tanggal_surat' => '2024-09-17',
            'tujuan' => 'Mahkamah Agung Republik Indonesia',
            'perihal' => 'Laporan Kinerja Triwulan III Tahun 2024',
            'status' => 'terkirim',
            'file_surat' => 'sk001.pdf',
            'input_by' => 'Administrator'
        ],
        [
            'id' => 2,
            'nomor_surat' => '002/SK/2024',
            'tanggal_surat' => '2024-09-16',
            'tujuan' => 'Kementerian Dalam Negeri',
            'perihal' => 'Permintaan Data Pegawai dan Infrastruktur',
            'status' => 'draft',
            'file_surat' => '',
            'input_by' => 'Staff TU'
        ],
        [
            'id' => 3,
            'nomor_surat' => '003/SK/2024',
            'tanggal_surat' => '2024-09-15',
            'tujuan' => 'Dinas Pendidikan Kalsel',
            'perihal' => 'Konfirmasi Data Sekolah dan Lembaga Pendidikan',
            'status' => 'terkirim',
            'file_surat' => 'sk003.pdf',
            'input_by' => 'Kasubbag'
        ],
        [
            'id' => 4,
            'nomor_surat' => '004/SK/2024',
            'tanggal_surat' => '2024-09-14',
            'tujuan' => 'Pemkot Banjarmasin',
            'perihal' => 'Undangan Rapat Koordinasi Program Kerja',
            'status' => 'arsip',
            'file_surat' => 'sk004.pdf',
            'input_by' => 'Panitera'
        ],
        [
            'id' => 5,
            'nomor_surat' => '005/SK/2024',
            'tanggal_surat' => '2024-09-13',
            'tujuan' => 'Pengadilan Tinggi TUN Makassar',
            'perihal' => 'Laporan Penyelesaian Perkara Bulan September',
            'status' => 'draft',
            'file_surat' => '',
            'input_by' => 'Staff TU'
        ]
    ];
    $total_records = 5;
} else {
    // Get data from database
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where_conditions = [];
    $params = [];

    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where_conditions[] = "sk.status = ?";
        $params[] = $_GET['status'];
    }

    // Filter by date range
    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
        $where_conditions[] = "sk.tanggal_surat >= ?";
        $params[] = $_GET['from_date'];
    }

    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
        $where_conditions[] = "sk.tanggal_surat <= ?";
        $params[] = $_GET['to_date'];
    }

    // Search
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $where_conditions[] = "(sk.nomor_surat LIKE ? OR sk.tujuan LIKE ? OR sk.perihal LIKE ?)";
        $params = array_merge($params, [$search, $search, $search]);
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    try {
        $count_result = fetchSingle("SELECT COUNT(*) as total FROM surat_keluar sk $where_clause", $params);
        $total_records = $count_result ? $count_result['total'] : 0;

        $sql = "SELECT sk.*, 'Administrator' as input_by 
                FROM surat_keluar sk 
                $where_clause 
                ORDER BY sk.id DESC 
                LIMIT $limit OFFSET $offset";

        $surat_keluar = fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $surat_keluar = [];
        $total_records = 0;
    }
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keluar - SIMAK PTUN</title>
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

        /* Badges */
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge.bg-warning {
            background-color: #fef3c7 !important;
            color: #92400e !important;
        }

        .badge.bg-info {
            background-color: #dbeafe !important;
            color: #1e40af !important;
        }

        .badge.bg-success {
            background-color: #dcfce7 !important;
            color: #166534 !important;
        }

        .badge.bg-secondary {
            background-color: #f3f4f6 !important;
            color: #4b5563 !important;
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
                <a href="../surat-keluar/" class="nav-link active">
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
                <h1 class="header-title">Surat Keluar</h1>
                <p class="header-subtitle">Kelola semua surat keluar dari Pengadilan Tata Usaha Negara</p>
            </div>
            <div class="header-actions">
                <a href="tambah.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Surat Keluar
                </a>
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

            <!-- Statistics -->
            <div class="stats-grid fade-in">
                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-edit"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= getTotalSuratKeluar('draft') ?></div>
                    <div class="stat-label">Draft</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= getTotalSuratKeluar('terkirim') ?></div>
                    <div class="stat-label">Terkirim</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="fas fa-archive"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= getTotalSuratKeluar('arsip') ?></div>
                    <div class="stat-label">Arsip</div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= getTotalSuratKeluar() ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card fade-in">
                <div class="card-header">
                    <i class="fas fa-filter me-2"></i>Filter & Pencarian
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="terkirim" <?= ($_GET['status'] ?? '') === 'terkirim' ? 'selected' : '' ?>>Terkirim</option>
                                <option value="arsip" <?= ($_GET['status'] ?? '') === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="from_date" class="form-control" value="<?= $_GET['from_date'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="to_date" class="form-control" value="<?= $_GET['to_date'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pencarian</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Nomor surat, tujuan..." 
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

            <!-- Data Table -->
            <div class="table-container fade-in">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-list me-2"></i>Daftar Surat Keluar
                            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?> surat</span>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary btn-sm me-2">
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
                
                <?php if (empty($surat_keluar)): ?>
                    <div class="empty-state">
                        <i class="fas fa-paper-plane"></i>
                        <h4>Tidak Ada Surat Keluar</h4>
                        <p class="text-muted">Belum ada surat keluar yang tercatat dalam sistem</p>
                        <a href="tambah.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Tambah Surat Keluar Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">No. Surat</th>
                                    <th style="width: 15%;">Tanggal</th>
                                    <th style="width: 20%;">Tujuan</th>
                                    <th style="width: 25%;">Perihal</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 8%;">File</th>
                                    <th style="width: 7%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($surat_keluar as $surat): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= htmlspecialchars($surat['nomor_surat']) ?></div>
                                            <small class="text-muted">
                                                <?= formatTanggal($surat['tanggal_surat']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= formatTanggal($surat['tanggal_surat']) ?></div>
                                            <small class="text-muted">
                                                oleh <?= htmlspecialchars($surat['input_by']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($surat['tujuan']) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-wrap">
                                                <?= htmlspecialchars(substr($surat['perihal'], 0, 60)) ?>
                                                <?= strlen($surat['perihal']) > 60 ? '...' : '' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-secondary';
                                            if ($surat['status'] === 'draft') $badge_class = 'bg-warning';
                                            if ($surat['status'] === 'terkirim') $badge_class = 'bg-success';
                                            if ($surat['status'] === 'arsip') $badge_class = 'bg-info';
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= ucfirst($surat['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($surat['file_surat'])): ?>
                                                <a href="<?= UPLOAD_URL . $surat['file_surat'] ?>" target="_blank" 
                                                   class="btn btn-outline-primary btn-sm" title="Lihat File">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="detail.php?id=<?= $surat['id'] ?>" 
                                                   class="btn btn-info btn-sm" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $surat['id'] ?>" 
                                                   class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($db_available): ?>
                                                    <a href="javascript:void(0)" 
                                                       onclick="confirmDelete('?action=delete&id=<?= $surat['id'] ?>')"
                                                       class="btn btn-danger btn-sm" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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
            if (confirm('Apakah Anda yakin ingin menghapus surat ini?')) {
                window.location.href = url;
            }
        }

        function updateStatus(id, status) {
            if (confirm('Apakah Anda yakin ingin mengubah status surat ini?')) {
                window.location.href = `?action=update_status&id=${id}&status=${status}`;
            }
        }

        console.log('Surat Keluar page loaded successfully');
    </script>
</body>
</html>