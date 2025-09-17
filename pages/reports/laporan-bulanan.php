<?php
// pages/reports/laporan-bulanan.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Laporan Bulanan';

// Get filter parameters
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Generate report data
try {
    $report_data = generateMonthlyReport($bulan, $tahun);
    
    // Get detailed data
    $surat_masuk_detail = $db->fetchAll("
        SELECT sm.*, u.nama_lengkap as input_by
        FROM surat_masuk sm 
        JOIN users u ON sm.user_id = u.id
        WHERE MONTH(sm.tanggal_diterima) = ? AND YEAR(sm.tanggal_diterima) = ?
        ORDER BY sm.tanggal_diterima DESC
    ", [$bulan, $tahun]);
    
    $surat_keluar_detail = $db->fetchAll("
        SELECT sk.*, u.nama_lengkap as input_by
        FROM surat_keluar sk 
        JOIN users u ON sk.user_id = u.id
        WHERE MONTH(sk.tanggal_surat) = ? AND YEAR(sk.tanggal_surat) = ?
        ORDER BY sk.tanggal_surat DESC
    ", [$bulan, $tahun]);
    
    // Status breakdown for incoming letters
    $status_masuk = $db->fetchAll("
        SELECT status, COUNT(*) as jumlah
        FROM surat_masuk 
        WHERE MONTH(tanggal_diterima) = ? AND YEAR(tanggal_diterima) = ?
        GROUP BY status
    ", [$bulan, $tahun]);
    
    // Status breakdown for outgoing letters
    $status_keluar = $db->fetchAll("
        SELECT status, COUNT(*) as jumlah
        FROM surat_keluar 
        WHERE MONTH(tanggal_surat) = ? AND YEAR(tanggal_surat) = ?
        GROUP BY status
    ", [$bulan, $tahun]);
    
    // Top senders (incoming)
    $top_pengirim = $db->fetchAll("
        SELECT pengirim, COUNT(*) as jumlah
        FROM surat_masuk 
        WHERE MONTH(tanggal_diterima) = ? AND YEAR(tanggal_diterima) = ?
        GROUP BY pengirim
        ORDER BY jumlah DESC
        LIMIT 5
    ", [$bulan, $tahun]);
    
    // Top destinations (outgoing)
    $top_tujuan = $db->fetchAll("
        SELECT tujuan, COUNT(*) as jumlah
        FROM surat_keluar 
        WHERE MONTH(tanggal_surat) = ? AND YEAR(tanggal_surat) = ?
        GROUP BY tujuan
        ORDER BY jumlah DESC
        LIMIT 5
    ", [$bulan, $tahun]);
    
} catch (Exception $e) {
    $error_message = "Gagal memuat data laporan: " . $e->getMessage();
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
                    <i class="fas fa-calendar-alt"></i>
                    Laporan Bulanan
                </h1>
                <p class="page-subtitle">
                    Laporan surat masuk dan keluar bulan <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
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
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-control">
                        <?php foreach ($nama_bulan as $key => $nama): ?>
                            <option value="<?= $key ?>" <?= $bulan === $key ? 'selected' : '' ?>>
                                <?= $nama ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-control">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
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
            <!-- Summary Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($report_data['surat_masuk']) ?></h3>
                        <p>Surat Masuk</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($report_data['surat_keluar']) ?></h3>
                        <p>Surat Keluar</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($report_data['pending']) ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($report_data['selesai']) ?></h3>
                        <p>Selesai</p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Status Breakdown Charts -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Status Surat Masuk</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartSuratMasuk" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Status Surat Keluar</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartSuratKeluar" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Top Senders -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Top 5 Pengirim Surat</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_pengirim)): ?>
                                <p class="text-center text-muted">Tidak ada data pengirim</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Pengirim</th>
                                                <th class="text-center">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_pengirim as $pengirim): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($pengirim['pengirim']) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge badge-primary"><?= $pengirim['jumlah'] ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Top Destinations -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Top 5 Tujuan Surat</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_tujuan)): ?>
                                <p class="text-center text-muted">Tidak ada data tujuan</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Tujuan</th>
                                                <th class="text-center">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_tujuan as $tujuan): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($tujuan['tujuan']) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge badge-success"><?= $tujuan['jumlah'] ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Tables -->
            <div class="row mt-4">
                <!-- Surat Masuk Detail -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Detail Surat Masuk</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($surat_masuk_detail)): ?>
                                <p class="text-center text-muted">Tidak ada surat masuk pada periode ini</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>No. Surat</th>
                                                <th>Tanggal</th>
                                                <th>Pengirim</th>
                                                <th>Perihal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($surat_masuk_detail as $surat): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($surat['nomor_surat']) ?></td>
                                                    <td><?= formatTanggal($surat['tanggal_diterima']) ?></td>
                                                    <td><?= htmlspecialchars($surat['pengirim']) ?></td>
                                                    <td><?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?>...</td>
                                                    <td>
                                                        <span class="badge badge-<?= $surat['status'] === 'pending' ? 'warning' : ($surat['status'] === 'diproses' ? 'info' : 'success') ?>">
                                                            <?= ucfirst($surat['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Surat Keluar Detail -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Detail Surat Keluar</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($surat_keluar_detail)): ?>
                                <p class="text-center text-muted">Tidak ada surat keluar pada periode ini</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>No. Surat</th>
                                                <th>Tanggal</th>
                                                <th>Tujuan</th>
                                                <th>Perihal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($surat_keluar_detail as $surat): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($surat['nomor_surat']) ?></td>
                                                    <td><?= formatTanggal($surat['tanggal_surat']) ?></td>
                                                    <td><?= htmlspecialchars($surat['tujuan']) ?></td>
                                                    <td><?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?>...</td>
                                                    <td>
                                                        <span class="badge badge-<?= $surat['status'] === 'draft' ? 'secondary' : ($surat['status'] === 'terkirim' ? 'success' : 'info') ?>">
                                                            <?= ucfirst($surat['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary Footer -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h6>Ringkasan Laporan</h6>
                    <p class="mb-0">
                        Pada bulan <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong>, 
                        tercatat <strong><?= number_format($report_data['surat_masuk']) ?> surat masuk</strong> 
                        dan <strong><?= number_format($report_data['surat_keluar']) ?> surat keluar</strong>.
                        <?php if ($report_data['pending'] > 0): ?>
                            Terdapat <strong><?= number_format($report_data['pending']) ?> surat yang masih pending</strong> 
                            dan perlu ditindaklanjuti.
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
// Prepare data for charts
$status_masuk_data = array_column($status_masuk, 'jumlah');
$status_masuk_labels = array_column($status_masuk, 'status');

$status_keluar_data = array_column($status_keluar, 'jumlah');
$status_keluar_labels = array_column($status_keluar, 'status');

$additional_js = [
    'https://cdn.jsdelivr.net/npm/chart.js'
];

$inline_scripts = '
// Chart configurations
const statusMasukData = ' . json_encode($status_masuk_data) . ';
const statusMasukLabels = ' . json_encode($status_masuk_labels) . ';
const statusKeluarData = ' . json_encode($status_keluar_data) . ';
const statusKeluarLabels = ' . json_encode($status_keluar_labels) . ';

// Colors for different statuses
const statusColors = {
    pending: "#f59e0b",
    diproses: "#3b82f6", 
    selesai: "#10b981",
    draft: "#6b7280",
    terkirim: "#10b981",
    arsip: "#3b82f6"
};

// Chart for Surat Masuk
if (statusMasukData.length > 0) {
    const ctxMasuk = document.getElementById("chartSuratMasuk").getContext("2d");
    new Chart(ctxMasuk, {
        type: "doughnut",
        data: {
            labels: statusMasukLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
            datasets: [{
                data: statusMasukData,
                backgroundColor: statusMasukLabels.map(label => statusColors[label] || "#6b7280"),
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

// Chart for Surat Keluar
if (statusKeluarData.length > 0) {
    const ctxKeluar = document.getElementById("chartSuratKeluar").getContext("2d");
    new Chart(ctxKeluar, {
        type: "doughnut",
        data: {
            labels: statusKeluarLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
            datasets: [{
                data: statusKeluarData,
                backgroundColor: statusKeluarLabels.map(label => statusColors[label] || "#6b7280"),
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

// Export functions
function exportToExcel() {
    window.open("export-excel.php?type=monthly&bulan=' . $bulan . '&tahun=' . $tahun . '", "_blank");
}

function exportToPDF() {
    window.open("export-pdf.php?type=monthly&bulan=' . $bulan . '&tahun=' . $tahun . '", "_blank");
}

function printReport() {
    const printContent = document.getElementById("reportContent").innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1>PENGADILAN TATA USAHA NEGARA BANJARMASIN</h1>
                <h2>LAPORAN BULANAN</h2>
                <h3>' . $nama_bulan[$bulan] . ' ' . $tahun . '</h3>
                <hr>
            </div>
            ${printContent}
        </div>
    `;
    
    // Hide charts in print
    const charts = document.querySelectorAll("canvas");
    charts.forEach(chart => {
        chart.style.display = "none";
    });
    
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Add print styles
const printStyles = `
<style>
@media print {
    .btn, .card-header, .page-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    
    .table {
        border-collapse: collapse;
    }
    
    .table th, .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
    }
    
    canvas {
        display: none !important;
    }
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", printStyles);
';

include '../../includes/footer.php';
?>
