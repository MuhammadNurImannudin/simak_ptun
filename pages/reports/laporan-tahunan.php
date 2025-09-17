<?php
// pages/reports/laporan-tahunan.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Laporan Tahunan';

// Get filter parameters
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Generate report data
try {
    // Monthly data for the year
    $monthly_data = [];
    for ($bulan = 1; $bulan <= 12; $bulan++) {
        $stats = generateMonthlyReport($bulan, $tahun);
        $monthly_data[] = [
            'bulan' => $bulan,
            'nama_bulan' => ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$bulan],
            'surat_masuk' => $stats['surat_masuk'],
            'surat_keluar' => $stats['surat_keluar'],
            'pending' => $stats['pending'],
            'selesai' => $stats['selesai']
        ];
    }
    
    // Year totals
    $year_totals = [
        'surat_masuk' => array_sum(array_column($monthly_data, 'surat_masuk')),
        'surat_keluar' => array_sum(array_column($monthly_data, 'surat_keluar')),
        'pending' => array_sum(array_column($monthly_data, 'pending')),
        'selesai' => array_sum(array_column($monthly_data, 'selesai'))
    ];
    
    // Comparison with previous year
    $prev_year = $tahun - 1;
    $prev_year_totals = [
        'surat_masuk' => $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal_diterima) = ?", [$prev_year])['total'],
        'surat_keluar' => $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE YEAR(tanggal_surat) = ?", [$prev_year])['total']
    ];
    
    // Calculate growth percentages
    $growth = [
        'surat_masuk' => $prev_year_totals['surat_masuk'] > 0 ? 
            round((($year_totals['surat_masuk'] - $prev_year_totals['surat_masuk']) / $prev_year_totals['surat_masuk']) * 100, 1) : 0,
        'surat_keluar' => $prev_year_totals['surat_keluar'] > 0 ? 
            round((($year_totals['surat_keluar'] - $prev_year_totals['surat_keluar']) / $prev_year_totals['surat_keluar']) * 100, 1) : 0
    ];
    
    // Monthly trends analysis
    $peak_month_masuk = array_reduce($monthly_data, function($carry, $item) {
        return ($carry === null || $item['surat_masuk'] > $carry['surat_masuk']) ? $item : $carry;
    });
    
    $peak_month_keluar = array_reduce($monthly_data, function($carry, $item) {
        return ($carry === null || $item['surat_keluar'] > $carry['surat_keluar']) ? $item : $carry;
    });
    
    // Top senders and recipients for the year
    $top_pengirim_year = $db->fetchAll("
        SELECT pengirim, COUNT(*) as jumlah
        FROM surat_masuk 
        WHERE YEAR(tanggal_diterima) = ?
        GROUP BY pengirim
        ORDER BY jumlah DESC
        LIMIT 10
    ", [$tahun]);
    
    $top_tujuan_year = $db->fetchAll("
        SELECT tujuan, COUNT(*) as jumlah
        FROM surat_keluar 
        WHERE YEAR(tanggal_surat) = ?
        GROUP BY tujuan
        ORDER BY jumlah DESC
        LIMIT 10
    ", [$tahun]);
    
    // Performance metrics
    $completion_rate = $year_totals['surat_masuk'] > 0 ? 
        round(($year_totals['selesai'] / $year_totals['surat_masuk']) * 100, 1) : 0;
    
    $avg_processing_time = $db->fetch("
        SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days
        FROM surat_masuk 
        WHERE YEAR(tanggal_diterima) = ? AND status = 'selesai'
    ", [$tahun])['avg_days'] ?: 0;
    
    // Quarter comparison
    $quarterly_data = [];
    for ($quarter = 1; $quarter <= 4; $quarter++) {
        $start_month = ($quarter - 1) * 3 + 1;
        $end_month = $quarter * 3;
        
        $masuk_q = $db->fetch("
            SELECT COUNT(*) as total FROM surat_masuk 
            WHERE YEAR(tanggal_diterima) = ? AND MONTH(tanggal_diterima) BETWEEN ? AND ?
        ", [$tahun, $start_month, $end_month])['total'];
        
        $keluar_q = $db->fetch("
            SELECT COUNT(*) as total FROM surat_keluar 
            WHERE YEAR(tanggal_surat) = ? AND MONTH(tanggal_surat) BETWEEN ? AND ?
        ", [$tahun, $start_month, $end_month])['total'];
        
        $quarterly_data[] = [
            'quarter' => $quarter,
            'nama' => 'Q' . $quarter,
            'surat_masuk' => $masuk_q,
            'surat_keluar' => $keluar_q
        ];
    }
    
} catch (Exception $e) {
    $error_message = "Gagal memuat data laporan: " . $e->getMessage();
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
                    <i class="fas fa-calendar-check"></i>
                    Laporan Tahunan
                </h1>
                <p class="page-subtitle">
                    Laporan komprehensif surat masuk dan keluar tahun <?= $tahun ?>
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
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tahun Laporan</label>
                    <select name="tahun" class="form-control">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-chart-bar"></i>
                        Tampilkan Laporan
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
        
        <div id="reportContent">
            <!-- Executive Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Ringkasan Eksekutif Tahun <?= $tahun ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="lead">
                                Pada tahun <?= $tahun ?>, PTUN Banjarmasin telah memproses total 
                                <strong><?= number_format($year_totals['surat_masuk'] + $year_totals['surat_keluar']) ?> surat</strong>
                                dengan tingkat penyelesaian <strong><?= $completion_rate ?>%</strong>.
                            </p>
                            
                            <div class="row">
                                <div class="col-sm-6">
                                    <h6>Perbandingan dengan Tahun <?= $prev_year ?>:</h6>
                                    <ul class="list-unstyled">
                                        <li>
                                            <i class="fas fa-inbox text-primary"></i>
                                            Surat Masuk: 
                                            <span class="badge badge-<?= $growth['surat_masuk'] >= 0 ? 'success' : 'danger' ?>">
                                                <?= $growth['surat_masuk'] >= 0 ? '+' : '' ?><?= $growth['surat_masuk'] ?>%
                                            </span>
                                        </li>
                                        <li>
                                            <i class="fas fa-paper-plane text-success"></i>
                                            Surat Keluar: 
                                            <span class="badge badge-<?= $growth['surat_keluar'] >= 0 ? 'success' : 'danger' ?>">
                                                <?= $growth['surat_keluar'] >= 0 ? '+' : '' ?><?= $growth['surat_keluar'] ?>%
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-sm-6">
                                    <h6>Puncak Aktivitas:</h6>
                                    <ul class="list-unstyled">
                                        <li>
                                            <i class="fas fa-calendar-day text-warning"></i>
                                            Surat Masuk Tertinggi: <?= $peak_month_masuk['nama_bulan'] ?> (<?= $peak_month_masuk['surat_masuk'] ?> surat)
                                        </li>
                                        <li>
                                            <i class="fas fa-calendar-day text-info"></i>
                                            Surat Keluar Tertinggi: <?= $peak_month_keluar['nama_bulan'] ?> (<?= $peak_month_keluar['surat_keluar'] ?> surat)
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-summary">
                                <div class="stat-item text-center mb-3">
                                    <h3 class="text-primary"><?= number_format($year_totals['surat_masuk']) ?></h3>
                                    <p class="text-muted">Total Surat Masuk</p>
                                </div>
                                <div class="stat-item text-center mb-3">
                                    <h3 class="text-success"><?= number_format($year_totals['surat_keluar']) ?></h3>
                                    <p class="text-muted">Total Surat Keluar</p>
                                </div>
                                <div class="stat-item text-center">
                                    <h3 class="text-info"><?= $completion_rate ?>%</h3>
                                    <p class="text-muted">Tingkat Penyelesaian</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="row">
                <!-- Monthly Trend -->
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Trend Bulanan Tahun <?= $tahun ?></h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyTrendChart" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Quarterly Comparison -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Perbandingan Per Kuartal</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="quarterlyChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Year Comparison -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Perbandingan Tahunan</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="yearComparisonChart" height="300"></canvas>
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
                            <div class="performance-grid">
                                <div class="performance-item">
                                    <div class="performance-value">
                                        <h3 class="text-success"><?= $completion_rate ?>%</h3>
                                        <p>Tingkat Penyelesaian</p>
                                    </div>
                                    <div class="performance-indicator">
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= $completion_rate ?>%"></div>
                                        </div>
                                        <small class="text-muted">Target: 85%</small>
                                    </div>
                                </div>
                                
                                <div class="performance-item">
                                    <div class="performance-value">
                                        <h3 class="text-info"><?= round($avg_processing_time, 1) ?></h3>
                                        <p>Rata-rata Hari Proses</p>
                                    </div>
                                    <div class="performance-indicator">
                                        <small class="text-muted">
                                            <?= $avg_processing_time < 7 ? 'Sangat Baik' : ($avg_processing_time < 14 ? 'Baik' : 'Perlu Perbaikan') ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="performance-item">
                                    <div class="performance-value">
                                        <h3 class="text-warning"><?= number_format($year_totals['pending']) ?></h3>
                                        <p>Surat Pending</p>
                                    </div>
                                    <div class="performance-indicator">
                                        <small class="text-muted">
                                            <?= round(($year_totals['pending'] / max($year_totals['surat_masuk'], 1)) * 100, 1) ?>% dari total masuk
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Analisis Trend</h5>
                        </div>
                        <div class="card-body">
                            <div class="trend-analysis">
                                <h6>Kesimpulan Trend <?= $tahun ?>:</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-chart-line text-<?= $growth['surat_masuk'] >= 0 ? 'success' : 'danger' ?>"></i>
                                        Volume surat masuk <?= $growth['surat_masuk'] >= 0 ? 'meningkat' : 'menurun' ?> 
                                        <?= abs($growth['surat_masuk']) ?>% dibanding tahun sebelumnya
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-chart-line text-<?= $growth['surat_keluar'] >= 0 ? 'success' : 'danger' ?>"></i>
                                        Volume surat keluar <?= $growth['surat_keluar'] >= 0 ? 'meningkat' : 'menurun' ?> 
                                        <?= abs($growth['surat_keluar']) ?>% dibanding tahun sebelumnya
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-calendar text-warning"></i>
                                        Puncak aktivitas pada bulan <?= $peak_month_masuk['nama_bulan'] ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle text-<?= $completion_rate >= 85 ? 'success' : 'warning' ?>"></i>
                                        Tingkat penyelesaian <?= $completion_rate >= 85 ? 'memenuhi' : 'belum memenuhi' ?> target (85%)
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Detail Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Detail Bulanan Tahun <?= $tahun ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th class="text-center">Surat Masuk</th>
                                    <th class="text-center">Surat Keluar</th>
                                    <th class="text-center">Pending</th>
                                    <th class="text-center">Selesai</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_data as $data): ?>
                                    <tr>
                                        <td><strong><?= $data['nama_bulan'] ?></strong></td>
                                        <td class="text-center"><?= number_format($data['surat_masuk']) ?></td>
                                        <td class="text-center"><?= number_format($data['surat_keluar']) ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-warning"><?= number_format($data['pending']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-success"><?= number_format($data['selesai']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <strong><?= number_format($data['surat_masuk'] + $data['surat_keluar']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-center"><strong><?= number_format($year_totals['surat_masuk']) ?></strong></td>
                                    <td class="text-center"><strong><?= number_format($year_totals['surat_keluar']) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-warning"><strong><?= number_format($year_totals['pending']) ?></strong></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><strong><?= number_format($year_totals['selesai']) ?></strong></span>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= number_format($year_totals['surat_masuk'] + $year_totals['surat_keluar']) ?></strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Top Lists -->
            <div class="row mt-4">
                <!-- Top Senders -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Top 10 Pengirim Surat Tahun <?= $tahun ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_pengirim_year)): ?>
                                <p class="text-center text-muted">Tidak ada data pengirim</p>
                            <?php else: ?>
                                <div class="top-list">
                                    <?php foreach ($top_pengirim_year as $index => $pengirim): ?>
                                        <div class="top-item">
                                            <span class="rank"><?= $index + 1 ?></span>
                                            <span class="name"><?= htmlspecialchars($pengirim['pengirim']) ?></span>
                                            <span class="value"><?= $pengirim['jumlah'] ?> surat</span>
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
                            <h5 class="card-title">Top 10 Tujuan Surat Tahun <?= $tahun ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_tujuan_year)): ?>
                                <p class="text-center text-muted">Tidak ada data tujuan</p>
                            <?php else: ?>
                                <div class="top-list">
                                    <?php foreach ($top_tujuan_year as $index => $tujuan): ?>
                                        <div class="top-item">
                                            <span class="rank"><?= $index + 1 ?></span>
                                            <span class="name"><?= htmlspecialchars($tujuan['tujuan']) ?></span>
                                            <span class="value"><?= $tujuan['jumlah'] ?> surat</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Summary -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h6>Kesimpulan Laporan Tahunan <?= $tahun ?></h6>
                    <p class="mb-2">
                        PTUN Banjarmasin berhasil mengelola <strong><?= number_format($year_totals['surat_masuk'] + $year_totals['surat_keluar']) ?> surat</strong> 
                        sepanjang tahun <?= $tahun ?> dengan tingkat penyelesaian <strong><?= $completion_rate ?>%</strong>.
                        <?php if ($growth['surat_masuk'] > 0 || $growth['surat_keluar'] > 0): ?>
                            Terjadi peningkatan volume surat yang menunjukkan tingginya kepercayaan masyarakat terhadap layanan PTUN.
                        <?php endif; ?>
                    </p>
                    <small class="text-muted">
                        Laporan digenerate pada <?= formatTanggal(date('Y-m-d')) ?> oleh <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
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
const monthlyLabels = ' . json_encode(array_column($monthly_data, 'nama_bulan')) . ';
const monthlyMasukData = ' . json_encode(array_column($monthly_data, 'surat_masuk')) . ';
const monthlyKeluarData = ' . json_encode(array_column($monthly_data, 'surat_keluar')) . ';
const quarterlyData = ' . json_encode($quarterly_data) . ';

// Monthly Trend Chart
const monthlyCtx = document.getElementById("monthlyTrendChart").getContext("2d");
new Chart(monthlyCtx, {
    type: "line",
    data: {
        labels: monthlyLabels,
        datasets: [
            {
                label: "Surat Masuk",
                data: monthlyMasukData,
                borderColor: "#3b82f6",
                backgroundColor: "rgba(59, 130, 246, 0.1)",
                tension: 0.4,
                fill: true
            },
            {
                label: "Surat Keluar",
                data: monthlyKeluarData,
                borderColor: "#10b981",
                backgroundColor: "rgba(16, 185, 129, 0.1)",
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "top"
            },
            title: {
                display: true,
                text: "Trend Surat Bulanan ' . $tahun . '"
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        },
        interaction: {
            intersect: false,
            mode: "index"
        }
    }
});

// Quarterly Chart
const quarterlyCtx = document.getElementById("quarterlyChart").getContext("2d");
new Chart(quarterlyCtx, {
    type: "bar",
    data: {
        labels: quarterlyData.map(q => q.nama),
        datasets: [
            {
                label: "Surat Masuk",
                data: quarterlyData.map(q => q.surat_masuk),
                backgroundColor: "#3b82f6"
            },
            {
                label: "Surat Keluar",
                data: quarterlyData.map(q => q.surat_keluar),
                backgroundColor: "#10b981"
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

// Year Comparison Chart
const yearComparisonCtx = document.getElementById("yearComparisonChart").getContext("2d");
new Chart(yearComparisonCtx, {
    type: "bar",
    data: {
        labels: ["' . $prev_year . '", "' . $tahun . '"],
        datasets: [
            {
                label: "Surat Masuk",
                data: [' . $prev_year_totals['surat_masuk'] . ', ' . $year_totals['surat_masuk'] . '],
                backgroundColor: "#3b82f6"
            },
            {
                label: "Surat Keluar",
                data: [' . $prev_year_totals['surat_keluar'] . ', ' . $year_totals['surat_keluar'] . '],
                backgroundColor: "#10b981"
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

// Export functions
function exportToExcel() {
    window.open("export-excel.php?type=yearly&tahun=' . $tahun . '", "_blank");
}

function exportToPDF() {
    window.open("export-pdf.php?type=yearly&tahun=' . $tahun . '", "_blank");
}

function printReport() {
    window.print();
}

// Add custom styles
const customStyles = `
<style>
.performance-grid {
    display: grid;
    gap: 1.5rem;
}

.performance-item {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-color);
}

.performance-value h3 {
    margin-bottom: 0.25rem;
}

.performance-value p {
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.progress {
    height: 8px;
    background-color: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.progress-bar {
    height: 100%;
    transition: width 0.3s ease;
}

.top-list {
    max-height: 400px;
    overflow-y: auto;
}

.top-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.top-item:last-child {
    border-bottom: none;
}

.top-item .rank {
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
    margin-right: 0.75rem;
}

.top-item .name {
    flex: 1;
    font-weight: 500;
}

.top-item .value {
    font-weight: 600;
    color: var(--primary-color);
}

.stats-summary .stat-item {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-color);
}

.trend-analysis ul li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.trend-analysis ul li:last-child {
    border-bottom: none;
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
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", customStyles);
';

include '../../includes/footer.php';
?>
