<?php
// pages/reports/rekapitulasi.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Rekapitulasi Surat';

// Get filter parameters
$periode = $_GET['periode'] ?? 'tahun_ini';
$tahun_custom = (int)($_GET['tahun_custom'] ?? date('Y'));
$bulan_custom = (int)($_GET['bulan_custom'] ?? date('n'));

// Define date ranges based on periode
$date_filters = [];
switch ($periode) {
    case 'bulan_ini':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        $periode_label = 'Bulan ' . date('F Y');
        break;
    case 'tahun_ini':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        $periode_label = 'Tahun ' . date('Y');
        break;
    case 'custom_bulan':
        $start_date = date('Y-m-01', mktime(0, 0, 0, $bulan_custom, 1, $tahun_custom));
        $end_date = date('Y-m-t', mktime(0, 0, 0, $bulan_custom, 1, $tahun_custom));
        $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $periode_label = $nama_bulan[$bulan_custom] . ' ' . $tahun_custom;
        break;
    case 'custom_tahun':
        $start_date = $tahun_custom . '-01-01';
        $end_date = $tahun_custom . '-12-31';
        $periode_label = 'Tahun ' . $tahun_custom;
        break;
    case 'semua':
        $start_date = '2020-01-01';
        $end_date = date('Y-12-31');
        $periode_label = 'Semua Periode';
        break;
    default:
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        $periode_label = 'Tahun ' . date('Y');
}

try {
    // Rekapitulasi surat masuk
    $rekap_masuk = $db->fetch("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
        FROM surat_masuk 
        WHERE tanggal_diterima BETWEEN ? AND ?
    ", [$start_date, $end_date]);
    
    // Rekapitulasi surat keluar
    $rekap_keluar = $db->fetch("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'terkirim' THEN 1 ELSE 0 END) as terkirim,
            SUM(CASE WHEN status = 'arsip' THEN 1 ELSE 0 END) as arsip
        FROM surat_keluar 
        WHERE tanggal_surat BETWEEN ? AND ?
    ", [$start_date, $end_date]);
    
    // Rekapitulasi per bulan (jika periode tahunan)
    $rekap_bulanan = [];
    if (in_array($periode, ['tahun_ini', 'custom_tahun'])) {
        $tahun_rekap = ($periode === 'tahun_ini') ? date('Y') : $tahun_custom;
        
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $masuk = $db->fetch("
                SELECT COUNT(*) as total FROM surat_masuk 
                WHERE YEAR(tanggal_diterima) = ? AND MONTH(tanggal_diterima) = ?
            ", [$tahun_rekap, $bulan])['total'];
            
            $keluar = $db->fetch("
                SELECT COUNT(*) as total FROM surat_keluar 
                WHERE YEAR(tanggal_surat) = ? AND MONTH(tanggal_surat) = ?
            ", [$tahun_rekap, $bulan])['total'];
            
            $rekap_bulanan[] = [
                'bulan' => $bulan,
                'nama_bulan' => ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'][$bulan],
                'masuk' => $masuk,
                'keluar' => $keluar,
                'total' => $masuk + $keluar
            ];
        }
    }
    
    // Rekapitulasi per pengirim (top 15)
    $rekap_pengirim = $db->fetchAll("
        SELECT pengirim, COUNT(*) as jumlah
        FROM surat_masuk 
        WHERE tanggal_diterima BETWEEN ? AND ?
        GROUP BY pengirim
        ORDER BY jumlah DESC
        LIMIT 15
    ", [$start_date, $end_date]);
    
    // Rekapitulasi per tujuan (top 15)
    $rekap_tujuan = $db->fetchAll("
        SELECT tujuan, COUNT(*) as jumlah
        FROM surat_keluar 
        WHERE tanggal_surat BETWEEN ? AND ?
        GROUP BY tujuan
        ORDER BY jumlah DESC
        LIMIT 15
    ", [$start_date, $end_date]);
    
    // Rekapitulasi per user
    $rekap_user = $db->fetchAll("
        SELECT 
            u.nama_lengkap,
            u.username,
            (SELECT COUNT(*) FROM surat_masuk WHERE user_id = u.id AND tanggal_diterima BETWEEN ? AND ?) as surat_masuk,
            (SELECT COUNT(*) FROM surat_keluar WHERE user_id = u.id AND tanggal_surat BETWEEN ? AND ?) as surat_keluar
        FROM users u
        ORDER BY (
            (SELECT COUNT(*) FROM surat_masuk WHERE user_id = u.id AND tanggal_diterima BETWEEN ? AND ?) +
            (SELECT COUNT(*) FROM surat_keluar WHERE user_id = u.id AND tanggal_surat BETWEEN ? AND ?)
        ) DESC
    ", [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    
    // Calculate percentages
    $completion_rate = $rekap_masuk['total'] > 0 ? round(($rekap_masuk['selesai'] / $rekap_masuk['total']) * 100, 1) : 0;
    $terkirim_rate = $rekap_keluar['total'] > 0 ? round(($rekap_keluar['terkirim'] / $rekap_keluar['total']) * 100, 1) : 0;
    
    // Performance metrics
    $avg_processing_days = $db->fetch("
        SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days
        FROM surat_masuk 
        WHERE status = 'selesai' AND tanggal_diterima BETWEEN ? AND ?
    ", [$start_date, $end_date])['avg_days'] ?: 0;
    
} catch (Exception $e) {
    $error_message = "Gagal memuat data rekapitulasi: " . $e->getMessage();
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
                    <i class="fas fa-clipboard-list"></i>
                    Rekapitulasi Surat
                </h1>
                <p class="page-subtitle">
                    Rekapitulasi lengkap surat masuk dan keluar - <?= $periode_label ?>
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
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select name="periode" class="form-control" onchange="toggleCustomFields(this.value)">
                        <option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                        <option value="tahun_ini" <?= $periode === 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
                        <option value="custom_bulan" <?= $periode === 'custom_bulan' ? 'selected' : '' ?>>Bulan Tertentu</option>
                        <option value="custom_tahun" <?= $periode === 'custom_tahun' ? 'selected' : '' ?>>Tahun Tertentu</option>
                        <option value="semua" <?= $periode === 'semua' ? 'selected' : '' ?>>Semua Data</option>
                    </select>
                </div>
                <div class="col-md-2" id="bulanField" style="display: <?= $periode === 'custom_bulan' ? 'block' : 'none' ?>">
                    <label class="form-label">Bulan</label>
                    <select name="bulan_custom" class="form-control">
                        <?php 
                        $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        for ($i = 1; $i <= 12; $i++): 
                        ?>
                            <option value="<?= $i ?>" <?= $bulan_custom === $i ? 'selected' : '' ?>>
                                <?= $nama_bulan[$i] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2" id="tahunField" style="display: <?= in_array($periode, ['custom_bulan', 'custom_tahun']) ? 'block' : 'none' ?>">
                    <label class="form-label">Tahun</label>
                    <select name="tahun_custom" class="form-control">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun_custom === $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Tampilkan Rekapitulasi
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
            <!-- Summary Cards -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($rekap_masuk['total']) ?></h3>
                        <p>Total Surat Masuk</p>
                        <small class="text-muted"><?= $periode_label ?></small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($rekap_keluar['total']) ?></h3>
                        <p>Total Surat Keluar</p>
                        <small class="text-muted"><?= $periode_label ?></small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $completion_rate ?>%</h3>
                        <p>Tingkat Penyelesaian</p>
                        <small class="text-muted">Surat masuk selesai</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($rekap_masuk['total'] + $rekap_keluar['total']) ?></h3>
                        <p>Total Seluruh Surat</p>
                        <small class="text-muted">Masuk + Keluar</small>
                    </div>
                </div>
            </div>
            
            <!-- Status Breakdown -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-inbox"></i>
                                Detail Surat Masuk
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="status-breakdown">
                                <div class="status-item">
                                    <div class="status-info">
                                        <span class="status-label">Pending</span>
                                        <span class="status-badge badge-warning"><?= number_format($rekap_masuk['pending']) ?></span>
                                    </div>
                                    <div class="status-bar">
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" 
                                                 style="width: <?= $rekap_masuk['total'] > 0 ? ($rekap_masuk['pending'] / $rekap_masuk['total']) * 100 : 0 ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $rekap_masuk['total'] > 0 ? round(($rekap_masuk['pending'] / $rekap_masuk['total']) * 100, 1) : 0 ?>%
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="status-item">
                                    <div class="status-info">
                                        <span class="status-label">Diproses</span>
                                        <span class="status-badge badge-info"><?= number_format($rekap_masuk['diproses']) ?></span>
                                    </div>
                                    <div class="status-bar">
                                        <div class="progress">
                                            <div class="progress-bar bg-info" 
                                                 style="width: <?= $rekap_masuk['total'] > 0 ? ($rekap_masuk['diproses'] / $rekap_masuk['total']) * 100 : 0 ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $rekap_masuk['total'] > 0 ? round(($rekap_masuk['diproses'] / $rekap_masuk['total']) * 100, 1) : 0 ?>%
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="status-item">
                                    <div class="status-info">
                                        <span class="status-label">Selesai</span>
                                        <span class="status-badge badge-success"><?= number_format($rekap_masuk['selesai']) ?></span>
                                    </div>
                                    <div class="status-bar">
                                        <div class="progress">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?= $rekap_masuk['total'] > 0 ? ($rekap_masuk['selesai'] / $rekap_masuk['total']) * 100 : 0 ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $rekap_masuk['total'] > 0 ? round(($rekap_masuk['selesai'] / $rekap_masuk['total']) * 100, 1) : 0 ?>%
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
                            <h5 class="card-title">
                                <i class="fas fa-paper-plane"></i>
                                Detail Surat Keluar
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="status-breakdown">
                                <div class="status-item">
                                    <div class="status-info">
                                        <span class="status-label">Draft</span>
                                        <span class="status-badge badge-secondary"><?= number_format($rekap_keluar['draft']) ?></span>
                                    </div>
                                    <div class="status-bar">
                                        <div class="progress">
                                            <div class="progress-bar bg-secondary" 
                                                 style="width: <?= $rekap_keluar['total'] > 0 ? ($rekap_keluar['draft'] / $rekap_keluar['total']) * 100 : 0 ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $rekap_keluar['total'] > 0 ? round(($rekap_keluar['draft'] / $rekap_keluar['total']) * 100, 1) : 0 ?>%
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="status-item">
                                    <div class="status-info">
                                        <span class="status-label">Terkirim</span>
                                        <span class="status-badge badge-success"><?= number_format($rekap_keluar['terkirim']) ?></span>
                                    </div>
                                    <div class="status-bar">
                                        <div class="progress">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?= $rekap_keluar['total'] > 0 ? ($rekap_keluar['terkirim'] / $rekap_keluar['total']) * 100 : 0 ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $rekap_keluar['total'] > 0 ? round(($rekap_keluar['terkirim'] / $rekap_keluar['total']) * 100, 1) : 0 ?>%
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="status-item">
                                    <div class="status-info">
                                        <span class="status-label">Arsip</span>
                                        <span class="status-badge badge-info"><?= number_format($rekap_keluar['arsip']) ?></span>
                                    </div>
                                    <div class="status-bar">
                                        <div class="progress">
                                            <div class="progress-bar bg-info" 
                                                 style="width: <?= $rekap_keluar['total'] > 0 ? ($rekap_keluar['arsip'] / $rekap_keluar['total']) * 100 : 0 ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $rekap_keluar['total'] > 0 ? round(($rekap_keluar['arsip'] / $rekap_keluar['total']) * 100, 1) : 0 ?>%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Breakdown (if yearly period) -->
            <?php if (!empty($rekap_bulanan)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calendar"></i>
                            Rekapitulasi Per Bulan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th class="text-center">Surat Masuk</th>
                                        <th class="text-center">Surat Keluar</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_tahunan = array_sum(array_column($rekap_bulanan, 'total'));
                                    foreach ($rekap_bulanan as $data): 
                                    ?>
                                        <tr>
                                            <td><strong><?= $data['nama_bulan'] ?></strong></td>
                                            <td class="text-center"><?= number_format($data['masuk']) ?></td>
                                            <td class="text-center"><?= number_format($data['keluar']) ?></td>
                                            <td class="text-center"><strong><?= number_format($data['total']) ?></strong></td>
                                            <td class="text-center">
                                                <?= $total_tahunan > 0 ? round(($data['total'] / $total_tahunan) * 100, 1) : 0 ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-active">
                                        <td><strong>TOTAL</strong></td>
                                        <td class="text-center"><strong><?= number_format(array_sum(array_column($rekap_bulanan, 'masuk'))) ?></strong></td>
                                        <td class="text-center"><strong><?= number_format(array_sum(array_column($rekap_bulanan, 'keluar'))) ?></strong></td>
                                        <td class="text-center"><strong><?= number_format($total_tahunan) ?></strong></td>
                                        <td class="text-center"><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Top Senders and Recipients -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-users"></i>
                                Top 15 Pengirim Surat
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($rekap_pengirim)): ?>
                                <p class="text-center text-muted">Tidak ada data pengirim</p>
                            <?php else: ?>
                                <div class="top-list">
                                    <?php foreach ($rekap_pengirim as $index => $pengirim): ?>
                                        <div class="top-item">
                                            <span class="rank"><?= $index + 1 ?></span>
                                            <span class="name"><?= htmlspecialchars($pengirim['pengirim']) ?></span>
                                            <span class="value"><?= number_format($pengirim['jumlah']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-building"></i>
                                Top 15 Tujuan Surat
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($rekap_tujuan)): ?>
                                <p class="text-center text-muted">Tidak ada data tujuan</p>
                            <?php else: ?>
                                <div class="top-list">
                                    <?php foreach ($rekap_tujuan as $index => $tujuan): ?>
                                        <div class="top-item">
                                            <span class="rank"><?= $index + 1 ?></span>
                                            <span class="name"><?= htmlspecialchars($tujuan['tujuan']) ?></span>
                                            <span class="value"><?= number_format($tujuan['jumlah']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Performance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-user-tie"></i>
                        Rekapitulasi Per User
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama User</th>
                                    <th>Username</th>
                                    <th class="text-center">Surat Masuk Input</th>
                                    <th class="text-center">Surat Keluar Input</th>
                                    <th class="text-center">Total Input</th>
                                    <th class="text-center">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_semua_input = array_sum(array_map(function($user) { 
                                    return $user['surat_masuk'] + $user['surat_keluar']; 
                                }, $rekap_user));
                                
                                foreach ($rekap_user as $index => $user): 
                                    $total_user = $user['surat_masuk'] + $user['surat_keluar'];
                                ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td class="text-center"><?= number_format($user['surat_masuk']) ?></td>
                                        <td class="text-center"><?= number_format($user['surat_keluar']) ?></td>
                                        <td class="text-center"><strong><?= number_format($total_user) ?></strong></td>
                                        <td class="text-center">
                                            <?= $total_semua_input > 0 ? round(($total_user / $total_semua_input) * 100, 1) : 0 ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-tachometer-alt"></i>
                                Metrik Kinerja
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="metric-grid">
                                <div class="metric-item">
                                    <h4 class="text-success"><?= $completion_rate ?>%</h4>
                                    <p class="text-muted">Tingkat Penyelesaian Surat Masuk</p>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" style="width: <?= $completion_rate ?>%"></div>
                                    </div>
                                    <small class="text-muted">Target: 85%</small>
                                </div>
                                
                                <div class="metric-item">
                                    <h4 class="text-primary"><?= $terkirim_rate ?>%</h4>
                                    <p class="text-muted">Tingkat Pengiriman Surat Keluar</p>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-primary" style="width: <?= $terkirim_rate ?>%"></div>
                                    </div>
                                    <small class="text-muted">Target: 90%</small>
                                </div>
                                
                                <div class="metric-item">
                                    <h4 class="text-info"><?= round($avg_processing_days, 1) ?></h4>
                                    <p class="text-muted">Rata-rata Hari Pemrosesan</p>
                                    <small class="text-muted">
                                        <?= $avg_processing_days < 7 ? 'Sangat Baik' : ($avg_processing_days < 14 ? 'Baik' : 'Perlu Perbaikan') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-pie"></i>
                                Visualisasi Data
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <canvas id="overviewChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="card">
                <div class="card-body text-center">
                    <h6>Ringkasan Rekapitulasi - <?= $periode_label ?></h6>
                    <p class="mb-0">
                        Total <strong><?= number_format($rekap_masuk['total'] + $rekap_keluar['total']) ?> surat</strong> 
                        telah diproses dalam periode ini, terdiri dari 
                        <strong><?= number_format($rekap_masuk['total']) ?> surat masuk</strong> dan 
                        <strong><?= number_format($rekap_keluar['total']) ?> surat keluar</strong>.
                        Tingkat penyelesaian mencapai <strong><?= $completion_rate ?>%</strong>.
                    </p>
                    <small class="text-muted">
                        Rekapitulasi digenerate pada <?= formatTanggal(date('Y-m-d')) ?> oleh <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
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
// Toggle custom fields based on periode selection
function toggleCustomFields(periode) {
    const bulanField = document.getElementById("bulanField");
    const tahunField = document.getElementById("tahunField");
    
    bulanField.style.display = periode === "custom_bulan" ? "block" : "none";
    tahunField.style.display = (periode === "custom_bulan" || periode === "custom_tahun") ? "block" : "none";
}

// Overview Chart
const overviewCtx = document.getElementById("overviewChart").getContext("2d");
new Chart(overviewCtx, {
    type: "doughnut",
    data: {
        labels: ["Surat Masuk", "Surat Keluar"],
        datasets: [{
            data: [' . $rekap_masuk['total'] . ', ' . $rekap_keluar['total'] . '],
            backgroundColor: ["#3b82f6", "#10b981"],
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

// Export functions
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    window.open("export-excel.php?type=rekapitulasi&" + params.toString(), "_blank");
}

function exportToPDF() {
    const params = new URLSearchParams(window.location.search);
    window.open("export-pdf.php?type=rekapitulasi&" + params.toString(), "_blank");
}

function printReport() {
    window.print();
}

// Add custom styles
const customStyles = `
<style>
.status-breakdown {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-color);
}

.status-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-label {
    font-weight: 500;
    min-width: 80px;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-weight: 600;
}

.status-bar {
    flex: 1;
    margin-left: 1rem;
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

.metric-grid {
    display: grid;
    gap: 1.5rem;
}

.metric-item {
    text-align: center;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-color);
}

.metric-item h4 {
    margin-bottom: 0.5rem;
}

.metric-item p {
    margin-bottom: 0.75rem;
    color: var(--text-secondary);
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
