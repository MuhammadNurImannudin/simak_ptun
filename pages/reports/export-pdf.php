<?php
// pages/reports/export-pdf.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Get export parameters
$type = $_GET['type'] ?? 'bulanan';
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Function to set proper headers for PDF download
function setPDFHeaders($filename) {
    header('Content-Type: text/html; charset=utf-8');
    // Note: In production, you would use a proper PDF library like TCPDF or DOMPDF
    // For simplicity, we're creating an HTML that can be printed as PDF
}

// Month names
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

try {
    switch ($type) {
        case 'bulanan':
            exportLaporanBulananPDF($bulan, $tahun, $nama_bulan[$bulan]);
            break;
        case 'tahunan':
        case 'yearly':
            exportLaporanTahunanPDF($tahun);
            break;
        case 'rekapitulasi':
            exportRekapitulasiPDF();
            break;
        case 'disposisi':
            exportLaporanDisposisiPDF($bulan, $tahun, $nama_bulan[$bulan]);
            break;
        default:
            throw new Exception('Tipe export tidak valid');
    }
} catch (Exception $e) {
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.close();</script>";
}

function exportLaporanBulananPDF($bulan, $tahun, $nama_bulan) {
    global $db;
    
    setPDFHeaders("Laporan_Bulanan_{$nama_bulan}_{$tahun}");
    
    // Get data
    $surat_masuk = $db->fetchAll("
        SELECT sm.*, u.nama_lengkap as input_by
        FROM surat_masuk sm
        JOIN users u ON sm.user_id = u.id
        WHERE MONTH(sm.tanggal_diterima) = ? AND YEAR(sm.tanggal_diterima) = ?
        ORDER BY sm.tanggal_diterima DESC
    ", [$bulan, $tahun]);
    
    $surat_keluar = $db->fetchAll("
        SELECT sk.*, u.nama_lengkap as input_by
        FROM surat_keluar sk
        JOIN users u ON sk.user_id = u.id
        WHERE MONTH(sk.tanggal_surat) = ? AND YEAR(sk.tanggal_surat) = ?
        ORDER BY sk.tanggal_surat DESC
    ", [$bulan, $tahun]);
    
    // Summary statistics
    $summary = [
        'total_masuk' => count($surat_masuk),
        'total_keluar' => count($surat_keluar),
        'pending' => count(array_filter($surat_masuk, fn($s) => $s['status'] === 'pending')),
        'diproses' => count(array_filter($surat_masuk, fn($s) => $s['status'] === 'diproses')),
        'selesai' => count(array_filter($surat_masuk, fn($s) => $s['status'] === 'selesai')),
        'draft' => count(array_filter($surat_keluar, fn($s) => $s['status'] === 'draft')),
        'terkirim' => count(array_filter($surat_keluar, fn($s) => $s['status'] === 'terkirim')),
        'arsip' => count(array_filter($surat_keluar, fn($s) => $s['status'] === 'arsip'))
    ];
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Bulanan <?= $nama_bulan ?> <?= $tahun ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #333;
                padding-bottom: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 18px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 16px;
                color: #666;
            }
            .header p {
                margin: 5px 0;
                color: #888;
            }
            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .summary-table th,
            .summary-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            .summary-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .detail-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                font-size: 10px;
            }
            .detail-table th,
            .detail-table td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
            }
            .detail-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                margin: 20px 0 10px 0;
                color: #333;
                border-bottom: 2px solid #333;
                padding-bottom: 5px;
            }
            .footer {
                margin-top: 50px;
                text-align: right;
                font-size: 10px;
                color: #666;
            }
            @media print {
                body { margin: 0; padding: 10px; }
                .no-print { display: none; }
            }
        </style>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Pengadilan Tata Usaha Negara Banjarmasin</h1>
            <h2>Laporan Bulanan Surat</h2>
            <p>Periode: <?= $nama_bulan ?> <?= $tahun ?></p>
            <p>Digenerate pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <div class="section-title">RINGKASAN STATISTIK</div>
        <table class="summary-table">
            <tr>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Persentase</th>
            </tr>
            <tr>
                <td>Total Surat Masuk</td>
                <td><?= $summary['total_masuk'] ?></td>
                <td><?= ($summary['total_masuk'] + $summary['total_keluar']) > 0 ? round(($summary['total_masuk'] / ($summary['total_masuk'] + $summary['total_keluar'])) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr>
                <td>Total Surat Keluar</td>
                <td><?= $summary['total_keluar'] ?></td>
                <td><?= ($summary['total_masuk'] + $summary['total_keluar']) > 0 ? round(($summary['total_keluar'] / ($summary['total_masuk'] + $summary['total_keluar'])) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td><strong>Total Keseluruhan</strong></td>
                <td><strong><?= $summary['total_masuk'] + $summary['total_keluar'] ?></strong></td>
                <td><strong>100%</strong></td>
            </tr>
        </table>
        
        <table class="summary-table">
            <tr>
                <th colspan="2">Status Surat Masuk</th>
            </tr>
            <tr>
                <td>Pending</td>
                <td><?= $summary['pending'] ?> (<?= $summary['total_masuk'] > 0 ? round(($summary['pending'] / $summary['total_masuk']) * 100, 1) : 0 ?>%)</td>
            </tr>
            <tr>
                <td>Diproses</td>
                <td><?= $summary['diproses'] ?> (<?= $summary['total_masuk'] > 0 ? round(($summary['diproses'] / $summary['total_masuk']) * 100, 1) : 0 ?>%)</td>
            </tr>
            <tr>
                <td>Selesai</td>
                <td><?= $summary['selesai'] ?> (<?= $summary['total_masuk'] > 0 ? round(($summary['selesai'] / $summary['total_masuk']) * 100, 1) : 0 ?>%)</td>
            </tr>
        </table>
        
        <table class="summary-table">
            <tr>
                <th colspan="2">Status Surat Keluar</th>
            </tr>
            <tr>
                <td>Draft</td>
                <td><?= $summary['draft'] ?> (<?= $summary['total_keluar'] > 0 ? round(($summary['draft'] / $summary['total_keluar']) * 100, 1) : 0 ?>%)</td>
            </tr>
            <tr>
                <td>Terkirim</td>
                <td><?= $summary['terkirim'] ?> (<?= $summary['total_keluar'] > 0 ? round(($summary['terkirim'] / $summary['total_keluar']) * 100, 1) : 0 ?>%)</td>
            </tr>
            <tr>
                <td>Arsip</td>
                <td><?= $summary['arsip'] ?> (<?= $summary['total_keluar'] > 0 ? round(($summary['arsip'] / $summary['total_keluar']) * 100, 1) : 0 ?>%)</td>
            </tr>
        </table>
        
        <?php if (!empty($surat_masuk)): ?>
        <div class="section-title">DETAIL SURAT MASUK</div>
        <table class="detail-table">
            <tr>
                <th>No</th>
                <th>Nomor Surat</th>
                <th>Tanggal</th>
                <th>Pengirim</th>
                <th>Perihal</th>
                <th>Status</th>
                <th>Disposisi</th>
                <th>Input By</th>
            </tr>
            <?php $no = 1; foreach ($surat_masuk as $surat): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($surat['nomor_surat']) ?></td>
                <td><?= date('d/m/Y', strtotime($surat['tanggal_diterima'])) ?></td>
                <td><?= htmlspecialchars($surat['pengirim']) ?></td>
                <td><?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?><?= strlen($surat['perihal']) > 50 ? '...' : '' ?></td>
                <td><?= strtoupper($surat['status']) ?></td>
                <td><?= htmlspecialchars(substr($surat['disposisi'] ?: '-', 0, 30)) ?><?= strlen($surat['disposisi'] ?: '') > 30 ? '...' : '' ?></td>
                <td><?= htmlspecialchars($surat['input_by']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <?php if (!empty($surat_keluar)): ?>
        <div class="section-title">DETAIL SURAT KELUAR</div>
        <table class="detail-table">
            <tr>
                <th>No</th>
                <th>Nomor Surat</th>
                <th>Tanggal</th>
                <th>Tujuan</th>
                <th>Perihal</th>
                <th>Status</th>
                <th>Input By</th>
            </tr>
            <?php $no = 1; foreach ($surat_keluar as $surat): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($surat['nomor_surat']) ?></td>
                <td><?= date('d/m/Y', strtotime($surat['tanggal_surat'])) ?></td>
                <td><?= htmlspecialchars($surat['tujuan']) ?></td>
                <td><?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?><?= strlen($surat['perihal']) > 50 ? '...' : '' ?></td>
                <td><?= strtoupper($surat['status']) ?></td>
                <td><?= htmlspecialchars($surat['input_by']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <div class="footer">
            <p>Laporan ini digenerate secara otomatis oleh Sistem SIMAK PTUN</p>
            <p>Tanggal cetak: <?= date('d/m/Y H:i:s') ?> | User: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
        </div>
    </body>
    </html>
    <?php
}

function exportLaporanTahunanPDF($tahun) {
    global $db;
    
    setPDFHeaders("Laporan_Tahunan_{$tahun}");
    
    // Monthly data for the year
    $monthly_data = [];
    for ($bulan = 1; $bulan <= 12; $bulan++) {
        $masuk = $db->fetch("
            SELECT COUNT(*) as total FROM surat_masuk 
            WHERE MONTH(tanggal_diterima) = ? AND YEAR(tanggal_diterima) = ?
        ", [$bulan, $tahun])['total'];
        
        $keluar = $db->fetch("
            SELECT COUNT(*) as total FROM surat_keluar 
            WHERE MONTH(tanggal_surat) = ? AND YEAR(tanggal_surat) = ?
        ", [$bulan, $tahun])['total'];
        
        $monthly_data[] = [
            'bulan' => $bulan,
            'nama_bulan' => ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$bulan],
            'masuk' => $masuk,
            'keluar' => $keluar
        ];
    }
    
    // Year totals
    $total_masuk = array_sum(array_column($monthly_data, 'masuk'));
    $total_keluar = array_sum(array_column($monthly_data, 'keluar'));
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Tahunan <?= $tahun ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #333;
                padding-bottom: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 18px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 16px;
                color: #666;
            }
            .header p {
                margin: 5px 0;
                color: #888;
            }
            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .summary-table th,
            .summary-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: center;
            }
            .summary-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                margin: 20px 0 10px 0;
                color: #333;
                border-bottom: 2px solid #333;
                padding-bottom: 5px;
            }
            .footer {
                margin-top: 50px;
                text-align: right;
                font-size: 10px;
                color: #666;
            }
            @media print {
                body { margin: 0; padding: 10px; }
                .no-print { display: none; }
            }
        </style>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Pengadilan Tata Usaha Negara Banjarmasin</h1>
            <h2>Laporan Tahunan Surat</h2>
            <p>Tahun: <?= $tahun ?></p>
            <p>Digenerate pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <div class="section-title">RINGKASAN TAHUNAN</div>
        <table class="summary-table">
            <tr>
                <th>Kategori</th>
                <th>Jumlah</th>
            </tr>
            <tr>
                <td>Total Surat Masuk</td>
                <td><?= number_format($total_masuk) ?></td>
            </tr>
            <tr>
                <td>Total Surat Keluar</td>
                <td><?= number_format($total_keluar) ?></td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td><strong>Total Keseluruhan</strong></td>
                <td><strong><?= number_format($total_masuk + $total_keluar) ?></strong></td>
            </tr>
        </table>
        
        <div class="section-title">RINCIAN PER BULAN</div>
        <table class="summary-table">
            <tr>
                <th>Bulan</th>
                <th>Surat Masuk</th>
                <th>Surat Keluar</th>
                <th>Total</th>
                <th>Persentase</th>
            </tr>
            <?php foreach ($monthly_data as $data): 
                $total_bulan = $data['masuk'] + $data['keluar'];
                $percentage = ($total_masuk + $total_keluar) > 0 ? 
                    round(($total_bulan / ($total_masuk + $total_keluar)) * 100, 1) : 0;
            ?>
            <tr>
                <td><?= $data['nama_bulan'] ?></td>
                <td><?= number_format($data['masuk']) ?></td>
                <td><?= number_format($data['keluar']) ?></td>
                <td><?= number_format($total_bulan) ?></td>
                <td><?= $percentage ?>%</td>
            </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f0f0f0;">
                <td><strong>TOTAL</strong></td>
                <td><strong><?= number_format($total_masuk) ?></strong></td>
                <td><strong><?= number_format($total_keluar) ?></strong></td>
                <td><strong><?= number_format($total_masuk + $total_keluar) ?></strong></td>
                <td><strong>100%</strong></td>
            </tr>
        </table>
        
        <div class="footer">
            <p>Laporan ini digenerate secara otomatis oleh Sistem SIMAK PTUN</p>
            <p>Tanggal cetak: <?= date('d/m/Y H:i:s') ?> | User: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
        </div>
    </body>
    </html>
    <?php
}

function exportRekapitulasiPDF() {
    global $db;
    
    $periode = $_GET['periode'] ?? 'tahun_ini';
    $tahun = (int)($_GET['tahun_custom'] ?? date('Y'));
    $bulan = (int)($_GET['bulan_custom'] ?? date('n'));
    
    // Determine date range
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
            $start_date = date('Y-m-01', mktime(0, 0, 0, $bulan, 1, $tahun));
            $end_date = date('Y-m-t', mktime(0, 0, 0, $bulan, 1, $tahun));
            $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $periode_label = $nama_bulan[$bulan] . ' ' . $tahun;
            break;
        case 'custom_tahun':
            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';
            $periode_label = 'Tahun ' . $tahun;
            break;
        default:
            $start_date = date('Y-01-01');
            $end_date = date('Y-12-31');
            $periode_label = 'Tahun ' . date('Y');
    }
    
    setPDFHeaders("Rekapitulasi_Surat_{$periode_label}");
    
    // Get rekapitulasi data
    $rekap_masuk = $db->fetch("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
        FROM surat_masuk 
        WHERE tanggal_diterima BETWEEN ? AND ?
    ", [$start_date, $end_date]);
    
    $rekap_keluar = $db->fetch("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'terkirim' THEN 1 ELSE 0 END) as terkirim,
            SUM(CASE WHEN status = 'arsip' THEN 1 ELSE 0 END) as arsip
        FROM surat_keluar 
        WHERE tanggal_surat BETWEEN ? AND ?
    ", [$start_date, $end_date]);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Rekapitulasi Surat <?= $periode_label ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #333;
                padding-bottom: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 18px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 16px;
                color: #666;
            }
            .header p {
                margin: 5px 0;
                color: #888;
            }
            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .summary-table th,
            .summary-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: center;
            }
            .summary-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                margin: 20px 0 10px 0;
                color: #333;
                border-bottom: 2px solid #333;
                padding-bottom: 5px;
            }
            .footer {
                margin-top: 50px;
                text-align: right;
                font-size: 10px;
                color: #666;
            }
            @media print {
                body { margin: 0; padding: 10px; }
                .no-print { display: none; }
            }
        </style>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Pengadilan Tata Usaha Negara Banjarmasin</h1>
            <h2>Rekapitulasi Surat</h2>
            <p>Periode: <?= $periode_label ?></p>
            <p>Rentang Tanggal: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
            <p>Digenerate pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <div class="section-title">REKAPITULASI SURAT MASUK</div>
        <table class="summary-table">
            <tr>
                <th>Status</th>
                <th>Jumlah</th>
                <th>Persentase</th>
            </tr>
            <tr>
                <td>Pending</td>
                <td><?= number_format($rekap_masuk['pending']) ?></td>
                <td><?= $rekap_masuk['total'] > 0 ? round(($rekap_masuk['pending'] / $rekap_masuk['total']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr>
                <td>Diproses</td>
                <td><?= number_format($rekap_masuk['diproses']) ?></td>
                <td><?= $rekap_masuk['total'] > 0 ? round(($rekap_masuk['diproses'] / $rekap_masuk['total']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr>
                <td>Selesai</td>
                <td><?= number_format($rekap_masuk['selesai']) ?></td>
                <td><?= $rekap_masuk['total'] > 0 ? round(($rekap_masuk['selesai'] / $rekap_masuk['total']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td><strong>TOTAL</strong></td>
                <td><strong><?= number_format($rekap_masuk['total']) ?></strong></td>
                <td><strong>100%</strong></td>
            </tr>
        </table>
        
        <div class="section-title">REKAPITULASI SURAT KELUAR</div>
        <table class="summary-table">
            <tr>
                <th>Status</th>
                <th>Jumlah</th>
                <th>Persentase</th>
            </tr>
            <tr>
                <td>Draft</td>
                <td><?= number_format($rekap_keluar['draft']) ?></td>
                <td><?= $rekap_keluar['total'] > 0 ? round(($rekap_keluar['draft'] / $rekap_keluar['total']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr>
                <td>Terkirim</td>
                <td><?= number_format($rekap_keluar['terkirim']) ?></td>
                <td><?= $rekap_keluar['total'] > 0 ? round(($rekap_keluar['terkirim'] / $rekap_keluar['total']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr>
                <td>Arsip</td>
                <td><?= number_format($rekap_keluar['arsip']) ?></td>
                <td><?= $rekap_keluar['total'] > 0 ? round(($rekap_keluar['arsip'] / $rekap_keluar['total']) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td><strong>TOTAL</strong></td>
                <td><strong><?= number_format($rekap_keluar['total']) ?></strong></td>
                <td><strong>100%</strong></td>
            </tr>
        </table>
        
        <div class="footer">
            <p>Laporan ini digenerate secara otomatis oleh Sistem SIMAK PTUN</p>
            <p>Tanggal cetak: <?= date('d/m/Y H:i:s') ?> | User: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
        </div>
    </body>
    </html>
    <?php
}

function exportLaporanDisposisiPDF($bulan, $tahun, $nama_bulan) {
    global $db;
    
    setPDFHeaders("Laporan_Disposisi_{$nama_bulan}_{$tahun}");
    
    // Get disposisi data
    $disposisi_data = $db->fetchAll("
        SELECT sm.*, u.nama_lengkap as input_by,
               DATEDIFF(COALESCE(sm.updated_at, NOW()), sm.created_at) as processing_days
        FROM surat_masuk sm
        JOIN users u ON sm.user_id = u.id
        WHERE MONTH(sm.tanggal_diterima) = ? AND YEAR(sm.tanggal_diterima) = ?
        ORDER BY sm.tanggal_diterima DESC
    ", [$bulan, $tahun]);
    
    // Summary
    $total_surat = count($disposisi_data);
    $ada_disposisi = count(array_filter($disposisi_data, fn($s) => !empty($s['disposisi'])));
    $tidak_ada_disposisi = $total_surat - $ada_disposisi;
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Disposisi <?= $nama_bulan ?> <?= $tahun ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #333;
                padding-bottom: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 18px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 16px;
                color: #666;
            }
            .header p {
                margin: 5px 0;
                color: #888;
            }
            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .summary-table th,
            .summary-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            .summary-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .detail-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                font-size: 10px;
            }
            .detail-table th,
            .detail-table td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
            }
            .detail-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                margin: 20px 0 10px 0;
                color: #333;
                border-bottom: 2px solid #333;
                padding-bottom: 5px;
            }
            .footer {
                margin-top: 50px;
                text-align: right;
                font-size: 10px;
                color: #666;
            }
            @media print {
                body { margin: 0; padding: 10px; }
                .no-print { display: none; }
            }
        </style>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>Pengadilan Tata Usaha Negara Banjarmasin</h1>
            <h2>Laporan Analisis Disposisi</h2>
            <p>Periode: <?= $nama_bulan ?> <?= $tahun ?></p>
            <p>Digenerate pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <div class="section-title">RINGKASAN DISPOSISI</div>
        <table class="summary-table">
            <tr>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Persentase</th>
            </tr>
            <tr>
                <td>Total Surat</td>
                <td><?= number_format($total_surat) ?></td>
                <td>100%</td>
            </tr>
            <tr>
                <td>Ada Disposisi</td>
                <td><?= number_format($ada_disposisi) ?></td>
                <td><?= $total_surat > 0 ? round(($ada_disposisi / $total_surat) * 100, 1) : 0 ?>%</td>
            </tr>
            <tr>
                <td>Belum Ada Disposisi</td>
                <td><?= number_format($tidak_ada_disposisi) ?></td>
                <td><?= $total_surat > 0 ? round(($tidak_ada_disposisi / $total_surat) * 100, 1) : 0 ?>%</td>
            </tr>
        </table>
        
        <?php if (!empty($disposisi_data)): ?>
        <div class="section-title">DETAIL SURAT DAN DISPOSISI</div>
        <table class="detail-table">
            <tr>
                <th>No</th>
                <th>Nomor Surat</th>
                <th>Tanggal</th>
                <th>Pengirim</th>
                <th>Perihal</th>
                <th>Disposisi</th>
                <th>Status</th>
                <th>Hari Proses</th>
            </tr>
            <?php $no = 1; foreach ($disposisi_data as $surat): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($surat['nomor_surat']) ?></td>
                <td><?= date('d/m/Y', strtotime($surat['tanggal_diterima'])) ?></td>
                <td><?= htmlspecialchars($surat['pengirim']) ?></td>
                <td><?= htmlspecialchars(substr($surat['perihal'], 0, 40)) ?><?= strlen($surat['perihal']) > 40 ? '...' : '' ?></td>
                <td><?= htmlspecialchars(substr($surat['disposisi'] ?: 'Belum ada disposisi', 0, 40)) ?><?= strlen($surat['disposisi'] ?: '') > 40 ? '...' : '' ?></td>
                <td><?= strtoupper($surat['status']) ?></td>
                <td><?= $surat['processing_days'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <div class="footer">
            <p>Laporan ini digenerate secara otomatis oleh Sistem SIMAK PTUN</p>
            <p>Tanggal cetak: <?= date('d/m/Y H:i:s') ?> | User: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
        </div>
    </body>
    </html>
    <?php
}
?>
