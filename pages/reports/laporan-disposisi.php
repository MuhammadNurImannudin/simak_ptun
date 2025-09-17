<?php
// pages/reports/laporan-disposisi.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Laporan Disposisi';

// Get filter parameters
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$status_filter = $_GET['status'] ?? '';
$surat_id = (int)($_GET['surat_id'] ?? 0);

// Generate report data
try {
    // Base query conditions
    $where_conditions = ["MONTH(sm.tanggal_diterima) = ? AND YEAR(sm.tanggal_diterima) = ?"];
    $params = [$bulan, $tahun];
    
    // Add status filter if specified
    if (!empty($status_filter)) {
        $where_conditions[] = "sm.status = ?";
        $params[] = $status_filter;
    }
    
    // Add specific surat filter if specified
    if ($surat_id > 0) {
        $where_conditions[] = "sm.id = ?";
        $params[] = $surat_id;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get detailed disposisi data
    $disposisi_data = $db->fetchAll("
        SELECT 
            sm.*,
            u.nama_lengkap as input_by,
            u.email as input_email,
            DATEDIFF(COALESCE(sm.updated_at, NOW()), sm.created_at) as processing_days,
            CASE 
                WHEN sm.status = 'selesai' THEN DATEDIFF(sm.updated_at, sm.created_at)
                ELSE DATEDIFF(NOW(), sm.created_at)
            END as days_since_received
        FROM surat_masuk sm
        JOIN users u ON sm.user_id = u.id
        $where_clause
        ORDER BY sm.tanggal_diterima DESC, sm.created_at DESC
    ", $params);
    
    // Summary statistics
    $summary_stats = $db->fetch("
        SELECT 
            COUNT(*) as total_surat,
            SUM(CASE WHEN sm.disposisi IS NOT NULL AND sm.disposisi != '' THEN 1 ELSE 0 END) as ada_disposisi,
            SUM(CASE WHEN sm.disposisi IS NULL OR sm.disposisi = '' THEN 1 ELSE 0 END) as tidak_ada_disposisi,
            SUM(CASE WHEN sm.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN sm.status = 'diproses' THEN 1 ELSE 0 END) as diproses,
            SUM(CASE WHEN sm.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            AVG(CASE WHEN sm.status = 'selesai' THEN DATEDIFF(sm.updated_at, sm.created_at) END) as avg_completion_days
        FROM surat_masuk sm
        $where_clause
    ", $params);
    
    // Disposisi categories analysis
    $disposisi_categories = [];
    foreach ($disposisi_data as $surat) {
        if (!empty($surat['disposisi'])) {
            $kategori = analyzeDisposisiCategory($surat['disposisi']);
            if (!isset($disposisi_categories[$kategori])) {
                $disposisi_categories[$kategori] = 0;
            }
            $disposisi_categories[$kategori]++;
        }
    }
    
    // Top disposisi keywords
    $all_disposisi_text = implode(' ', array_filter(array_column($disposisi_data, 'disposisi')));
    $disposisi_keywords = extractKeywords($all_disposisi_text);
    
    // Processing time analysis
    $processing_time_groups = [
        'sangat_cepat' => 0, // 0-3 hari
        'cepat' => 0,        // 4-7 hari
        'normal' => 0,       // 8-14 hari
        'lambat' => 0,       // 15-30 hari
        'sangat_lambat' => 0 // >30 hari
    ];
    
    foreach ($disposisi_data as $surat) {
        $days = $surat['days_since_received'];
        if ($days <= 3) {
            $processing_time_groups['sangat_cepat']++;
        } elseif ($days <= 7) {
            $processing_time_groups['cepat']++;
        } elseif ($days <= 14) {
            $processing_time_groups['normal']++;
        } elseif ($days <= 30) {
            $processing_time_groups['lambat']++;
        } else {
            $processing_time_groups['sangat_lambat']++;
        }
    }
    
    // Calculate percentages
    $total_surat = $summary_stats['total_surat'];
    $disposisi_rate = $total_surat > 0 ? round(($summary_stats['ada_disposisi'] / $total_surat) * 100, 1) : 0;
    $completion_rate = $total_surat > 0 ? round(($summary_stats['selesai'] / $total_surat) * 100, 1) : 0;
    
} catch (Exception $e) {
    $error_message = "Gagal memuat data laporan: " . $e->getMessage();
}

// Helper functions
function analyzeDisposisiCategory($disposisi) {
    $disposisi_lower = strtolower($disposisi);
    
    if (strpos($disposisi_lower, 'tindak lanjut') !== false || strpos($disposisi_lower, 'tindaklanjut') !== false) {
        return 'Tindak Lanjut';
    } elseif (strpos($disposisi_lower, 'proses') !== false || strpos($disposisi_lower, 'kerjakan') !== false) {
        return 'Proses Lebih Lanjut';
    } elseif (strpos($disposisi_lower, 'arsip') !== false || strpos($disposisi_lower, 'simpan') !== false) {
        return 'Arsip';
    } elseif (strpos($disposisi_lower, 'balas') !== false || strpos($disposisi_lower, 'jawab') !== false) {
        return 'Perlu Balasan';
    } elseif (strpos($disposisi_lower, 'koordinasi') !== false || strpos($disposisi_lower, 'koordinir') !== false) {
        return 'Koordinasi';
    } elseif (strpos($disposisi_lower, 'tolak') !== false || strpos($disposisi_lower, 'ditolak') !== false) {
        return 'Ditolak';
    } else {
        return 'Lainnya';
    }
}

function extractKeywords($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^\w\s]/', ' ', $text);
    $words = explode(' ', $text);
    $words = array_filter($words, function($word) {
        return strlen($word) > 3 && !in_array($word, ['yang', 'untuk', 'dengan', 'dari', 'pada', 'dalam', 'akan', 'telah', 'sudah', 'agar', 'supaya']);
    });
    $word_count = array_count_values($words);
    arsort($word_count);
    return array_slice($word_count, 0, 10, true);
}

// Month names in Indonesian
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

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
                    <i class="fas fa-clipboard-check"></i>
                    Laporan Disposisi
                </h1>
                <p class="page-subtitle">
                    Analisis disposisi dan tindak lanjut surat masuk bulan <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i>
                    Export Excel
                </button>
                <button class="btn btn-danger" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i>
                    Export PDF
                </button>
                <button class="btn btn-info" onclick="printReport()">
                    <i class="fas fa-print"></i>
                    Cetak
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-control">
                        <?php foreach ($nama_bulan as $key => $nama): ?>
                            <option value="<?= $key ?>" <?= $bulan === $key ? 'selected' : '' ?>>
                                <?= $nama ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-control">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="diproses" <?= $status_filter === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Tampilkan Laporan
                    </button>
                    <?php if (!empty($status_filter) || $surat_id > 0): ?>
                        <a href="laporan-disposisi.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-outline">
                            <i class="fas fa-times"></i>
                            Reset Filter
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error_message ?>
        </div>
    <?php else: ?>
        
        <div id="reportContent">
            <!-- Summary Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($summary_stats['total_surat']) ?></h3>
                        <p>Total Surat</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($summary_stats['ada_disposisi']) ?></h3>
                        <p>Ada Disposisi</p>
                        <small class="text-muted"><?= $disposisi_rate ?>% dari total</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-comment-slash"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($summary_stats['tidak_ada_disposisi']) ?></h3>
                        <p>Tanpa Disposisi</p>
                        <small class="text-muted"><?= round(100 - $disposisi_rate, 1) ?>% dari total</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= round($summary_stats['avg_completion_days'], 1) ?></h3>
                        <p>Rata-rata Hari Selesai</p>
                        <small class="text-muted">Untuk surat selesai</small>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="row mb-4">
                <!-- Disposisi Categories -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Kategori Disposisi</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($disposisi_categories)): ?>
                                <p class="text-center text-muted">Tidak ada data disposisi</p>
                            <?php else: ?>
                                <canvas id="disposisiCategoryChart" height="300"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Processing Time Distribution -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Distribusi Waktu Pemrosesan</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="processingTimeChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Disposisi Analysis -->
            <div class="row mb-4">
                <!-- Category Breakdown -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-tags"></i>
                                Analisis Kategori Disposisi
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($disposisi_categories)): ?>
                                <p class="text-center text-muted">Tidak ada kategorisasi disposisi</p>
                            <?php else: ?>
                                <div class="category-list">
                                    <?php 
                                    arsort($disposisi_categories);
                                    foreach ($disposisi_categories as $kategori => $jumlah): 
                                        $percentage = round(($jumlah / array_sum($disposisi_categories)) * 100, 1);
                                    ?>
                                        <div class="category-item">
                                            <div class="category-info">
                                                <span class="category-name"><?= htmlspecialchars($kategori) ?></span>
                                                <span class="category-count"><?= $jumlah ?> surat</span>
                                            </div>
                                            <div class="category-bar">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= $percentage ?>%</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Keywords Analysis -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-search"></i>
                                Kata Kunci Disposisi Terpopuler
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($disposisi_keywords)): ?>
                                <p class="text-center text-muted">Tidak ada kata kunci ditemukan</p>
                            <?php else: ?>
                                <div class="keywords-cloud">
                                    <?php foreach ($disposisi_keywords as $keyword => $count): ?>
                                        <span class="keyword-tag" style="font-size: <?= min(2, 0.8 + ($count / max($disposisi_keywords)) * 1.2) ?>rem;">
                                            <?= htmlspecialchars($keyword) ?>
                                            <small>(<?= $count ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Processing Time Analysis -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-stopwatch"></i>
                        Analisis Waktu Pemrosesan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $time_labels = [
                            'sangat_cepat' => ['label' => 'Sangat Cepat (0-3 hari)', 'color' => 'success', 'icon' => 'fa-rocket'],
                            'cepat' => ['label' => 'Cepat (4-7 hari)', 'color' => 'info', 'icon' => 'fa-tachometer-alt'],
                            'normal' => ['label' => 'Normal (8-14 hari)', 'color' => 'warning', 'icon' => 'fa-clock'],
                            'lambat' => ['label' => 'Lambat (15-30 hari)', 'color' => 'danger', 'icon' => 'fa-hourglass-half'],
                            'sangat_lambat' => ['label' => 'Sangat Lambat (>30 hari)', 'color' => 'dark', 'icon' => 'fa-hourglass-end']
                        ];
                        
                        foreach ($processing_time_groups as $key => $count):
                            $config = $time_labels[$key];
                            $percentage = $total_surat > 0 ? round(($count / $total_surat) * 100, 1) : 0;
                        ?>
                            <div class="col-md-2">
                                <div class="time-metric text-center">
                                    <i class="fas <?= $config['icon'] ?> text-<?= $config['color'] ?>" style="font-size: 2rem;"></i>
                                    <h4 class="text-<?= $config['color'] ?> mt-2"><?= $count ?></h4>
                                    <p class="text-muted small"><?= $config['label'] ?></p>
                                    <span class="badge badge-<?= $config['color'] ?>"><?= $percentage ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Detail Surat dan Disposisi</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($disposisi_data)): ?>
                        <p class="text-center text-muted">Tidak ada data surat pada periode ini</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No. Surat</th>
                                        <th>Tanggal Diterima</th>
                                        <th>Pengirim</th>
                                        <th>Perihal</th>
                                        <th>Disposisi</th>
                                        <th>Status</th>
                                        <th>Hari Proses</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($disposisi_data as $surat): ?>
                                        <tr>
                                            <td>
                                                <a href="../surat-masuk/detail.php?id=<?= $surat['id'] ?>" class="text-decoration-none fw-bold">
                                                    <?= htmlspecialchars($surat['nomor_surat']) ?>
                                                </a>
                                            </td>
                                            <td><?= formatTanggal($surat['tanggal_diterima']) ?></td>
                                            <td><?= htmlspecialchars($surat['pengirim']) ?></td>
                                            <td>
                                                <?= htmlspecialchars(substr($surat['perihal'], 0, 40)) ?>
                                                <?= strlen($surat['perihal']) > 40 ? '...' : '' ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($surat['disposisi'])): ?>
                                                    <div class="disposisi-text">
                                                        <?= htmlspecialchars(substr($surat['disposisi'], 0, 50)) ?>
                                                        <?= strlen($surat['disposisi']) > 50 ? '...' : '' ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        Kategori: <?= analyzeDisposisiCategory($surat['disposisi']) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">Belum ada disposisi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $surat['status'] === 'pending' ? 'warning' : ($surat['status'] === 'diproses' ? 'info' : 'success') ?>">
                                                    <?= ucfirst($surat['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $surat['days_since_received'] <= 7 ? 'success' : ($surat['days_since_received'] <= 14 ? 'warning' : 'danger') ?>">
                                                    <?= $surat['days_since_received'] ?> hari
                                                </span>
                                            </td>
                                            <td>
                                                <a href="../surat-masuk/detail.php?id=<?= $surat['id'] ?>" class="btn btn-info btn-sm" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../surat-masuk/edit.php?id=<?= $surat['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Summary and Recommendations -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-lightbulb"></i>
                        Ringkasan dan Rekomendasi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6>Ringkasan Laporan:</h6>
                            <ul>
                                <li>
                                    Dari <strong><?= number_format($summary_stats['total_surat']) ?> surat masuk</strong>, 
                                    <strong><?= number_format($summary_stats['ada_disposisi']) ?> surat (<?= $disposisi_rate ?>%)</strong> 
                                    telah memiliki disposisi.
                                </li>
                                <li>
                                    Tingkat penyelesaian surat mencapai <strong><?= $completion_rate ?>%</strong>.
                                </li>
                                <li>
                                    Rata-rata waktu penyelesaian adalah <strong><?= round($summary_stats['avg_completion_days'], 1) ?> hari</strong>.
                                </li>
                                <?php if (!empty($disposisi_categories)): ?>
                                    <li>
                                        Kategori disposisi terbanyak adalah 
                                        <strong><?= array_keys($disposisi_categories)[0] ?></strong> 
                                        dengan <?= array_values($disposisi_categories)[0] ?> surat.
                                    </li>
                                <?php endif; ?>
                            </ul>
                            
                            <h6 class="mt-4">Rekomendasi:</h6>
                            <ul>
                                <?php if ($disposisi_rate < 80): ?>
                                    <li class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Tingkatkan pemberian disposisi pada surat masuk (saat ini <?= $disposisi_rate ?>%).
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($completion_rate < 85): ?>
                                    <li class="text-warning">
                                        <i class="fas fa-clock"></i>
                                        Tingkatkan tingkat penyelesaian surat (target minimal 85%).
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($summary_stats['avg_completion_days'] > 14): ?>
                                    <li class="text-danger">
                                        <i class="fas fa-tachometer-alt"></i>
                                        Percepat proses penyelesaian surat (rata-rata saat ini <?= round($summary_stats['avg_completion_days'], 1) ?> hari).
                                    </li>
                                <?php endif; ?>
                                
                                <li class="text-info">
                                    <i class="fas fa-chart-line"></i>
                                    Standardisasi format disposisi untuk mempermudah kategorisasi dan analisis.
                                </li>
                                
                                <li class="text-success">
                                    <i class="fas fa-bell"></i>
                                    Implementasikan sistem reminder untuk surat yang belum ada disposisi.
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <div class="performance-summary">
                                <h6>Indikator Kinerja:</h6>
                                <div class="performance-item">
                                    <span>Tingkat Disposisi</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-<?= $disposisi_rate >= 80 ? 'success' : 'warning' ?>" 
                                             style="width: <?= $disposisi_rate ?>%"></div>
                                    </div>
                                    <small><?= $disposisi_rate ?>% (Target: 80%)</small>
                                </div>
                                
                                <div class="performance-item">
                                    <span>Tingkat Penyelesaian</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-<?= $completion_rate >= 85 ? 'success' : 'warning' ?>" 
                                             style="width: <?= $completion_rate ?>%"></div>
                                    </div>
                                    <small><?= $completion_rate ?>% (Target: 85%)</small>
                                </div>
                                
                                <div class="performance-item">
                                    <span>Kecepatan Proses</span>
                                    <?php 
                                    $speed_score = $summary_stats['avg_completion_days'] <= 7 ? 100 : 
                                                  ($summary_stats['avg_completion_days'] <= 14 ? 75 : 50);
                                    ?>
                                    <div class="progress">
                                        <div class="progress-bar bg-<?= $speed_score >= 75 ? 'success' : 'warning' ?>" 
                                             style="width: <?= $speed_score ?>%"></div>
                                    </div>
                                    <small><?= round($summary_stats['avg_completion_days'], 1) ?> hari (Target: ≤14 hari)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            Laporan disposisi periode <?= $nama_bulan[$bulan] ?> <?= $tahun ?> 
                            | Digenerate pada <?= formatTanggal(date('Y-m-d')) ?> 
                            | oleh <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</main>

<?php
$additional_js = [
    'https://cdn.jsdelivr.net/npm/chart.js'
];

$inline_scripts = '
// Chart data
const disposisiCategories = ' . json_encode(array_keys($disposisi_categories)) . ';
const disposisiCounts = ' . json_encode(array_values($disposisi_categories)) . ';
const processingTimeLabels = ["Sangat Cepat", "Cepat", "Normal", "Lambat", "Sangat Lambat"];
const processingTimeCounts = ' . json_encode(array_values($processing_time_groups)) . ';

// Disposisi Category Chart
if (disposisiCategories.length > 0) {
    const categoryCtx = document.getElementById("disposisiCategoryChart").getContext("2d");
    new Chart(categoryCtx, {
        type: "doughnut",
        data: {
            labels: disposisiCategories,
            datasets: [{
                data: disposisiCounts,
                backgroundColor: [
                    "#3b82f6", "#10b981", "#f59e0b", "#ef4444", 
                    "#8b5cf6", "#06b6d4", "#84cc16", "#f97316"
                ],
                borderWidth: 2,
                borderColor: "#ffffff"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom"
                }
            }
        }
    });
}

// Processing Time Chart
const timeCtx = document.getElementById("processingTimeChart").getContext("2d");
new Chart(timeCtx, {
    type: "bar",
    data: {
        labels: processingTimeLabels,
        datasets: [{
            label: "Jumlah Surat",
            data: processingTimeCounts,
            backgroundColor: ["#10b981", "#3b82f6", "#f59e0b", "#ef4444", "#6b7280"],
            borderWidth: 1,
            borderColor: "#ffffff"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Export functions
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    window.open("export-excel.php?type=disposisi&" + params.toString(), "_blank");
}

function exportToPDF() {
    const params = new URLSearchParams(window.location.search);
    window.open("export-pdf.php?type=disposisi&" + params.toString(), "_blank");
}

function printReport() {
    window.print();
}

// Add custom styles
const customStyles = `
<style>
.category-list {
    max-height: 350px;
    overflow-y: auto;
}

.category-item {
    margin-bottom: 1rem;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-color);
}

.category-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.category-name {
    font-weight: 600;
}

.category-count {
    color: var(--primary-color);
    font-weight: 600;
}

.category-bar .progress {
    height: 6px;
    margin-bottom: 0.25rem;
}

.keywords-cloud {
    line-height: 2;
}

.keyword-tag {
    display: inline-block;
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    margin: 0.25rem;
    border-radius: 20px;
    font-weight: 500;
}

.keyword-tag small {
    opacity: 0.8;
}

.time-metric {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-color);
    margin-bottom: 1rem;
}

.disposisi-text {
    font-size: 0.9rem;
    line-height: 1.3;
}

.performance-summary {
    background: var(--bg-color);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.performance-item {
    margin-bottom: 1rem;
}

.performance-item span {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.performance-item .progress {
    height: 8px;
    margin-bottom: 0.25rem;
}

@media print {
    .btn, .card-header, .page-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    
    canvas {
        display: none !important;
    }
    
    .table {
        font-size: 0.8rem;
    }
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", customStyles);
';

include '../../includes/footer.php';
?>
