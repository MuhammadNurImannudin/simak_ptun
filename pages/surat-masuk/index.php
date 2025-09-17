<?php
// pages/surat-masuk/index.php - CLEAN VERSION
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit();
}

// Initialize variables
$surat_masuk = [];
$total_records = 0;
$total_pages = 1;

// Check database availability
$db_available = false;
try {
    $test_result = fetchSingle("SELECT 1 as test");
    $db_available = ($test_result !== null);
} catch (Exception $e) {
    $db_available = false;
    $db_error = $e->getMessage();
}

// Handle actions only if database is available
if ($db_available) {
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        try {
            // Get file info before delete
            $surat = fetchSingle("SELECT file_surat FROM surat_masuk WHERE id = ?", [$id]);
            
            // Delete from database
            executeQuery("DELETE FROM surat_masuk WHERE id = ?", [$id]);
            
            // Delete file if exists
            if ($surat && isset($surat['file_surat']) && $surat['file_surat'] && file_exists(UPLOAD_PATH . $surat['file_surat'])) {
                unlink(UPLOAD_PATH . $surat['file_surat']);
            }
            
            setFlashMessage('success', 'Surat masuk berhasil dihapus');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal menghapus surat masuk: ' . $e->getMessage());
        }
        
        redirect('index.php');
    }

    // Handle status update
    if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $id = (int)$_GET['id'];
        $status = sanitize($_GET['status']);
        
        if (in_array($status, ['pending', 'diproses', 'selesai'])) {
            try {
                executeQuery("UPDATE surat_masuk SET status = ? WHERE id = ?", [$status, $id]);
                setFlashMessage('success', 'Status surat berhasil diperbarui');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Gagal memperbarui status: ' . $e->getMessage());
            }
        }
        
        redirect('index.php');
    }
}

// Sample data if database not available
if (!$db_available) {
    $surat_masuk = [
        [
            'id' => 1,
            'nomor_surat' => '001/SM/2024',
            'tanggal_surat' => '2024-09-15',
            'tanggal_diterima' => '2024-09-15',
            'pengirim' => 'Dinas Pendidikan',
            'perihal' => 'Permohonan Data Sekolah',
            'status' => 'pending',
            'file_surat' => '',
            'input_by' => 'Administrator'
        ],
        [
            'id' => 2,
            'nomor_surat' => '002/SM/2024',
            'tanggal_surat' => '2024-09-14',
            'tanggal_diterima' => '2024-09-14',
            'pengirim' => 'Kementerian Dalam Negeri',
            'perihal' => 'Laporan Kinerja Triwulan',
            'status' => 'diproses',
            'file_surat' => '',
            'input_by' => 'Administrator'
        ],
        [
            'id' => 3,
            'nomor_surat' => '003/SM/2024',
            'tanggal_surat' => '2024-09-13',
            'tanggal_diterima' => '2024-09-13',
            'pengirim' => 'Pemkot Banjarmasin',
            'perihal' => 'Koordinasi Pelaksanaan Program',
            'status' => 'selesai',
            'file_surat' => '',
            'input_by' => 'Administrator'
        ]
    ];
    $total_records = 3;
} else {
    // Get data from database
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where_conditions = [];
    $params = [];

    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where_conditions[] = "sm.status = ?";
        $params[] = $_GET['status'];
    }

    // Filter by date range
    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
        $where_conditions[] = "sm.tanggal_diterima >= ?";
        $params[] = $_GET['from_date'];
    }

    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
        $where_conditions[] = "sm.tanggal_diterima <= ?";
        $params[] = $_GET['to_date'];
    }

    // Search
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $where_conditions[] = "(sm.nomor_surat LIKE ? OR sm.pengirim LIKE ? OR sm.perihal LIKE ?)";
        $params = array_merge($params, [$search, $search, $search]);
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    try {
        // Get total count
        $count_result = fetchSingle("SELECT COUNT(*) as total FROM surat_masuk sm $where_clause", $params);
        $total_records = $count_result ? $count_result['total'] : 0;
        $total_pages = ceil($total_records / $limit);

        // Get data - simplified query for compatibility
        $sql = "SELECT sm.*, 'Administrator' as input_by 
                FROM surat_masuk sm 
                $where_clause 
                ORDER BY sm.id DESC 
                LIMIT $limit OFFSET $offset";

        $surat_masuk = fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $surat_masuk = [];
        $total_records = 0;
    }
}

// Get flash message
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Masuk - SIMAK PTUN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --bg-color: #f8fafc;
            --text-primary: #1e293b;
            --text-light: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
        }

        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            padding: 1rem;
            z-index: 1000;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .card {
            background: var(--card-bg);
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 10px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.primary::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
        .stat-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
        .stat-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .stat-card.info::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            position: relative;
        }

        .stat-icon.primary { 
            background: linear-gradient(135deg, #ddd6fe, #c4b5fd);
            color: #6366f1;
        }
        .stat-icon.success { 
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #10b981;
        }
        .stat-icon.warning { 
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #f59e0b;
        }
        .stat-icon.info { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #3b82f6;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #1e293b, #475569);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            color: var(--text-primary);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            color: white;
            transform: translateX(5px);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: none;
            font-weight: 600;
            color: var(--text-primary);
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .badge.bg-warning { 
            background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
            color: #92400e !important;
        }
        .badge.bg-info { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe) !important;
            color: #1e40af !important;
        }
        .badge.bg-success { 
            background: linear-gradient(135deg, #dcfce7, #bbf7d0) !important;
            color: #065f46 !important;
        }

        .btn-group .btn {
            border-radius: 8px;
            margin: 0 2px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
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

        @media (max-width: 768px) {
            .sidebar { 
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .main-content { 
                margin-left: 0;
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="text-center mb-4">
            <h5><i class="fas fa-gavel me-2"></i>SIMAK PTUN</h5>
            <small class="text-muted">Banjarmasin</small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="../../index.php">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="../surat-masuk/">
                    <i class="fas fa-inbox me-2"></i>Surat Masuk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../surat-keluar/">
                    <i class="fas fa-paper-plane me-2"></i>Surat Keluar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../users/">
                    <i class="fas fa-users me-2"></i>Manajemen User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : 'success' ?> alert-dismissible fade show">
                <i class="fas fa-<?= $flash['type'] === 'danger' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Database Warning -->
        <?php if (!$db_available): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Mode Demo:</strong> Database tidak terhubung. Menampilkan data contoh.
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-inbox me-3"></i>Surat Masuk
                    </h1>
                    <p class="mb-0 opacity-90">
                        Kelola semua surat masuk di Pengadilan Tata Usaha Negara Banjarmasin
                    </p>
                </div>
                <div>
                    <a href="tambah.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Tambah Surat Masuk
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Summary -->
        <div class="stats-grid">
            <div class="stat-card warning">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= getTotalSuratMasuk('pending') ?></h3>
                    <p class="mb-0 text-muted">Pending</p>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon info">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-content">
                    <h3><?= getTotalSuratMasuk('diproses') ?></h3>
                    <p class="mb-0 text-muted">Diproses</p>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= getTotalSuratMasuk('selesai') ?></h3>
                    <p class="mb-0 text-muted">Selesai</p>
                </div>
            </div>
            
            <div class="stat-card primary">
                <div class="stat-icon primary">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="stat-content">
                    <h3><?= getTotalSuratMasuk() ?></h3>
                    <p class="mb-0 text-muted">Total</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="diproses" <?= ($_GET['status'] ?? '') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                            <option value="selesai" <?= ($_GET['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Dari Tanggal</label>
                        <input type="date" name="from_date" class="form-control" value="<?= $_GET['from_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sampai Tanggal</label>
                        <input type="date" name="to_date" class="form-control" value="<?= $_GET['to_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Pencarian</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Nomor surat, pengirim..." 
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
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Daftar Surat Masuk
                    <span class="badge bg-primary ms-2"><?= number_format($total_records) ?> surat</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($surat_masuk)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4 class="mt-3">Tidak Ada Surat Masuk</h4>
                        <p class="text-muted">Belum ada surat masuk yang tercatat dalam sistem</p>
                        <a href="tambah.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Surat Masuk Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>No. Surat</th>
                                    <th>Tanggal Diterima</th>
                                    <th>Pengirim</th>
                                    <th>Perihal</th>
                                    <th>Status</th>
                                    <th>File</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($surat_masuk as $surat): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($surat['nomor_surat']) ?></strong>
                                            <?php if (!empty($surat['tanggal_surat'])): ?>
                                                <small class="d-block text-muted">
                                                    Tgl Surat: <?= formatTanggal($surat['tanggal_surat']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= formatTanggal($surat['tanggal_diterima']) ?>
                                            <small class="d-block text-muted">
                                                oleh <?= htmlspecialchars($surat['input_by']) ?>
                                            </small>
                                        </td>
                                        <td><?= htmlspecialchars($surat['pengirim']) ?></td>
                                        <td>
                                            <?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?>
                                            <?= strlen($surat['perihal']) > 50 ? '...' : '' ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($surat['status'], 'surat_masuk') ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($surat['file_surat'])): ?>
                                                <a href="<?= UPLOAD_URL . $surat['file_surat'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-pdf"></i> Lihat
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="detail.php?id=<?= $surat['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $surat['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($db_available): ?>
                                                    <a href="javascript:void(0)" 
                                                       onclick="confirmDelete('?action=delete&id=<?= $surat['id'] ?>', 'Apakah Anda yakin ingin menghapus surat ini?')"
                                                       class="btn btn-sm btn-danger" title="Hapus">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(url, message) {
            if (confirm(message)) {
                window.location.href = url;
            }
        }

        // Add smooth loading animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
        }
    </script>
</body>
</html>