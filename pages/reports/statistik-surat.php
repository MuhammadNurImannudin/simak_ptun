<?php
// pages/reports/statistik-surat.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Statistik Surat';

// Get filter parameters
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$periode = $_GET['periode'] ?? 'bulanan';

// Generate statistics data
try {
    if ($periode === 'bulanan') {
        // Monthly statistics for the year
        $monthly_stats = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $stats = generateMonthlyReport($bulan, $tahun);
            $monthly_stats[] = [
                'bulan' => $bulan,
                'nama_bulan' => ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'][$bulan],
                'surat_masuk' => $stats['surat_masuk'],
                'surat_keluar' => $stats['surat_keluar'],
                'pending' => $stats['pending'],
                'selesai' => $stats['selesai']
            ];
        }
        $chart_data = $monthly_stats;
        $chart_labels = array_column($monthly_stats, 'nama_bulan');
    } else {
        // Yearly statistics for last 5 years
        $yearly_stats = [];
        for ($y = $tahun - 4; $y <= $tahun; $y++) {
            $masuk_total = $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ?", [$y])['total'];
            $keluar_total = $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE YEAR(tanggal_surat) = ?", [$y])['total'];
            $pending_total = $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ? AND status = 'pending'", [$y])['total'];
            $selesai_total = $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ? AND status = 'selesai'", [$y])['total'];
            
            $yearly_stats[] = [
                'tahun' => $y,
                'surat_masuk' => $masuk_total,
                'surat_keluar' => $keluar_total,
                'pending' => $pending_total,
                'selesai' => $selesai_total
            ];
        }
        $chart_data = $yearly_stats;
        $chart_labels = array_column($yearly_stats, 'tahun');
    }
    
    // Current year totals
    $total_stats = [
        'surat_masuk' => $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ?", [$tahun])['total'],
        'surat_keluar' => $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE YEAR(tanggal_surat) = ?", [$tahun])['total'],
        'pending' => $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ? AND status = 'pending'", [$tahun])['total'],
        'diproses' => $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ? AND status = 'diproses'", [$tahun])['total'],
        'selesai' => $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ? AND status = 'selesai'", [$tahun])['total']
    ];
    
    // Status distribution
    $status_distribution = [
        'masuk' => [
            'pending' => $total_stats['pending'],
            'diproses' => $total_stats['diproses'],
            'selesai' => $total_stats['selesai']
        ],
        'keluar' => [
            'draft' => $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE YEAR(tanggal_surat) = ? AND status = 'draft'", [$tahun])['total'],
            'terkirim' => $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE YEAR(tanggal_surat) = ? AND status = 'terkirim'", [$tahun])['total'],
            'arsip' => $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE YEAR(tanggal_surat) = ? AND status = 'arsip'", [$tahun])['total']
        ]
    ];
    
    // Top senders and recipients
    $top_pengirim = $db->fetchAll("
        SELECT pengirim, COUNT(*) as jumlah
        FROM surat_masuk 
        WHERE YEAR(tanggal_diterima) = ?
        GROUP BY pengirim
        ORDER BY jumlah DESC
        LIMIT 10
    ", [$tahun]);
    
    $top_tujuan = $db->fetchAll("
        SELECT tujuan, COUNT(*) as jumlah
        FROM surat_keluar 
        WHERE YEAR(tanggal_surat) = ?
        GROUP BY tujuan
        ORDER BY jumlah DESC
        LIMIT 10
    ", [$tahun]);
    
    // Performance metrics
    $avg_processing_time = $db->fetch("
        SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days
        FROM surat_masuk 
        WHERE YEAR(tanggal_diterima) = ? AND status = 'selesai'
    ", [$tahun])['avg_days'] ?: 0;
    
    $completion_rate = $total_stats['surat_masuk'] > 0 ? 
        round(($total_stats['selesai'] / $total_stats['surat_masuk']) * 100, 1) : 0;
    
} catch (Exception $e) {
    $error_message = "Gagal memuat data statistik: " . $e->getMessage();
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
                    <i class="fas fa-chart-bar"></i>
                    Statistik Surat
                </h1>
                <p class="page-subtitle">
                    Analisis dan visualisasi data surat masuk dan keluar tahun <?= $tahun ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" onclick="exportChart()">
                    <i class="fas fa-download"></i>
                    Export Chart
                </button>
                <button class="btn btn-info" onclick="printStatistics()">
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
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-control">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select name="periode" class="form-control">
                        <option value="bulanan" <?= $periode === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                        <option value="tahunan" <?= $periode === 'tahunan' ? 'selected' : '' ?>>5 Tahun Terakhir</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i>
                        Tampilkan Statistik
                    </button>
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
        
        <div id="statisticsContent">
            <!-- Key Performance Indicators -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($total_stats['surat_masuk']) ?></h3>
                        <p>Total Surat Masuk</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($total_stats['surat_keluar']) ?></h3>
                        <p>Total Surat Keluar</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $completion_rate ?>%</h3>
                        <p>Tingkat Penyelesaian</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= round($avg_processing_time, 1) ?></h3>
                        <p>Rata-rata Hari Proses</p>
                    </div>
                </div>
            </div>
            
            <!-- Main Charts -->
            <div class="row">
                <!-- Trend Chart -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                Trend Surat <?= ucfirst($periode) ?> <?= $periode === 'bulanan' ? $tahun : '' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="trendChart" height="400"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Status Distribution -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Distribusi Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Metrik Kinerja</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="metric-item">
                                        <h3 class="text-success"><?= $completion_rate ?>%</h3>
                                        <p class="text-muted">Tingkat Penyelesaian</p>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= $completion_rate ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-item">
                                        <h3 class="text-info"><?= round($avg_processing_time, 1) ?></h3>
                                        <p class="text-muted">Rata-rata Hari Proses</p>
                                        <small class="text-muted">
                                            <?= $avg_processing_time < 7 ? 'Sangat Baik' : ($avg_processing_time < 14 ? 'Baik' : 'Perlu Perbaikan') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="performance-details">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Surat Selesai:</span>
                                    <strong class="text-success"><?= number_format($total_stats['selesai']) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Surat Diproses:</span>
                                    <strong class="text-info"><?= number_format($total_stats['diproses']) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Surat Pending:</span>
                                    <strong class="text-warning"><?= number_format($total_stats['pending']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Rasio Surat</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="ratioChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Lists -->
            <div class="row mt-4">
                <!-- Top Senders -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Top 10 Pengirim Surat</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_pengirim)): ?>
                                <p class="text-center text-muted">Tidak ada data pengirim</p>
                            <?php else: ?>
                                <div class="top-list">
                                    <?php foreach ($top_pengirim as $index => $pengirim): ?>
                                        <div class="top-item d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <span class="rank-badge"><?= $index + 1 ?></span>
                                                <span class="ms-2"><?= htmlspecialchars($pengirim['pengirim']) ?></span>
                                            </div>
                                            <span class="badge badge-primary"><?= $pengirim['jumlah'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Top Recipients -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Top 10 Tujuan Surat</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_tujuan)): ?>
                                <p class="text-center text-muted">Tidak ada data tujuan</p>
                            <?php else: ?>
                                <div class="top-list">
                                    <?php foreach ($top_tujuan as $index => $tujuan): ?>
                                        <div class="top-item d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <span class="rank-badge"><?= $index + 1 ?></span>
                                                <span class="ms-2"><?= htmlspecialchars($tujuan['tujuan']) ?></span>
                                            </div>
                                            <span class="badge badge-success"><?= $tujuan['jumlah'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h6>Ringkasan Statistik</h6>
                    <p class="mb-0">
                        Pada tahun <strong><?= $tahun ?></strong>, sistem telah memproses 
                        <strong><?= number_format($total_stats['surat_masuk'] + $total_stats['surat_keluar']) ?> surat</strong> 
                        dengan tingkat penyelesaian <strong><?= $completion_rate ?>%</strong>. 
                        Rata-rata waktu pemrosesan adalah <strong><?= round($avg_processing_time, 1) ?> hari</strong>.
                    </p>
                    <small class="text-muted">
                        Statistik digenerate pada <?= formatTanggal(date('Y-m-d')) ?> oleh <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                    </small>
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
const chartLabels = ' . json_encode($chart_labels) . ';
const suratMasukData = ' . json_encode(array_column($chart_data, 'surat_masuk')) . ';
const suratKeluarData = ' . json_encode(array_column($chart_data, 'surat_keluar')) . ';
const statusMasukData = ' . json_encode(array_values($status_distribution['masuk'])) . ';
const statusKeluarData = ' . json_encode(array_values($status_distribution['keluar'])) . ';

// Trend Chart
const trendCtx = document.getElementById("trendChart").getContext("2d");
new Chart(trendCtx, {
    type: "line",
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: "Surat Masuk",
                data: suratMasukData,
                borderColor: "#3b82f6",
                backgroundColor: "rgba(59, 130, 246, 0.1)",
                tension: 0.4
            },
            {
                label: "Surat Keluar", 
                data: suratKeluarData,
                borderColor: "#10b981",
                backgroundColor: "rgba(16, 185, 129, 0.1)",
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "top"
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Distribution Chart
const statusCtx = document.getElementById("statusChart").getContext("2d");
new Chart(statusCtx, {
    type: "doughnut",
    data: {
        labels: ["Pending", "Diproses", "Selesai"],
        datasets: [{
            data: statusMasukData,
            backgroundColor: ["#f59e0b", "#3b82f6", "#10b981"]
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

// Ratio Chart
const ratioCtx = document.getElementById("ratioChart").getContext("2d");
new Chart(ratioCtx, {
    type: "pie",
    data: {
        labels: ["Surat Masuk", "Surat Keluar"],
        datasets: [{
            data: [' . $total_stats['surat_masuk'] . ', ' . $total_stats['surat_keluar'] . '],
            backgroundColor: ["#3b82f6", "#10b981"]
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

// Export functions
function exportChart() {
    const canvas = document.getElementById("trendChart");
    const url = canvas.toDataURL("image/png");
    const link = document.createElement("a");
    link.download = "statistik-surat-' . $tahun . '.png";
    link.href = url;
    link.click();
}

function printStatistics() {
    window.print();
}

// Add custom styles
const customStyles = `
<style>
.progress {
    height: 8px;
    background-color: var(--bg-color);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    transition: width 0.3s ease;
}

.metric-item {
    padding: 1rem 0;
}

.top-list {
    max-height: 400px;
    overflow-y: auto;
}

.top-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.top-item:last-child {
    border-bottom: none;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 600;
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
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", customStyles);
';

include '../../includes/footer.php';
?>
