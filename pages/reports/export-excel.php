<?php
// pages/reports/export-excel.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Get export parameters
$type = $_GET['type'] ?? 'bulanan';
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Function to set proper headers for Excel download
function setExcelHeaders($filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
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
            exportLaporanBulanan($bulan, $tahun, $nama_bulan[$bulan]);
            break;
        case 'tahunan':
        case 'yearly':
            exportLaporanTahunan($tahun);
            break;
        case 'rekapitulasi':
            exportRekapitulasi();
            break;
        case 'disposisi':
            exportLaporanDisposisi($bulan, $tahun, $nama_bulan[$bulan]);
            break;
        default:
            throw new Exception('Tipe export tidak valid');
    }
} catch (Exception $e) {
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.close();</script>";
}

function exportLaporanBulanan($bulan, $tahun, $nama_bulan) {
    global $db;
    
    setExcelHeaders("Laporan_Bulanan_{$nama_bulan}_{$tahun}");
    
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
    
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h2>LAPORAN BULANAN SURAT - ' . strtoupper($nama_bulan) . ' ' . $tahun . '</h2>';
    echo '<p>PENGADILAN TATA USAHA NEGARA BANJARMASIN</p>';
    echo '<p>Digenerate pada: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<hr>';
    
    // Summary Table
    echo '<h3>RINGKASAN</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr><td><b>Kategori</b></td><td><b>Jumlah</b></td></tr>';
    echo '<tr><td>Total Surat Masuk</td><td>' . $summary['total_masuk'] . '</td></tr>';
    echo '<tr><td>Total Surat Keluar</td><td>' . $summary['total_keluar'] . '</td></tr>';
    echo '<tr><td>Surat Pending</td><td>' . $summary['pending'] . '</td></tr>';
    echo '<tr><td>Surat Diproses</td><td>' . $summary['diproses'] . '</td></tr>';
    echo '<tr><td>Surat Selesai</td><td>' . $summary['selesai'] . '</td></tr>';
    echo '<tr><td>Surat Draft</td><td>' . $summary['draft'] . '</td></tr>';
    echo '<tr><td>Surat Terkirim</td><td>' . $summary['terkirim'] . '</td></tr>';
    echo '<tr><td>Surat Arsip</td><td>' . $summary['arsip'] . '</td></tr>';
    echo '</table>';
    echo '<br><br>';
    
    // Surat Masuk Table
    echo '<h3>DETAIL SURAT MASUK</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr>';
    echo '<td><b>No</b></td>';
    echo '<td><b>Nomor Surat</b></td>';
    echo '<td><b>Tanggal Diterima</b></td>';
    echo '<td><b>Pengirim</b></td>';
    echo '<td><b>Perihal</b></td>';
    echo '<td><b>Status</b></td>';
    echo '<td><b>Disposisi</b></td>';
    echo '<td><b>Input By</b></td>';
    echo '</tr>';
    
    $no = 1;
    foreach ($surat_masuk as $surat) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($surat['nomor_surat']) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($surat['tanggal_diterima'])) . '</td>';
        echo '<td>' . htmlspecialchars($surat['pengirim']) . '</td>';
        echo '<td>' . htmlspecialchars($surat['perihal']) . '</td>';
        echo '<td>' . strtoupper($surat['status']) . '</td>';
        echo '<td>' . htmlspecialchars($surat['disposisi'] ?: '-') . '</td>';
        echo '<td>' . htmlspecialchars($surat['input_by']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<br><br>';
    
    // Surat Keluar Table
    echo '<h3>DETAIL SURAT KELUAR</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr>';
    echo '<td><b>No</b></td>';
    echo '<td><b>Nomor Surat</b></td>';
    echo '<td><b>Tanggal Surat</b></td>';
    echo '<td><b>Tujuan</b></td>';
    echo '<td><b>Perihal</b></td>';
    echo '<td><b>Status</b></td>';
    echo '<td><b>Input By</b></td>';
    echo '</tr>';
    
    $no = 1;
    foreach ($surat_keluar as $surat) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($surat['nomor_surat']) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($surat['tanggal_surat'])) . '</td>';
        echo '<td>' . htmlspecialchars($surat['tujuan']) . '</td>';
        echo '<td>' . htmlspecialchars($surat['perihal']) . '</td>';
        echo '<td>' . strtoupper($surat['status']) . '</td>';
        echo '<td>' . htmlspecialchars($surat['input_by']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</body></html>';
}

function exportLaporanTahunan($tahun) {
    global $db;
    
    setExcelHeaders("Laporan_Tahunan_{$tahun}");
    
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
    
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h2>LAPORAN TAHUNAN SURAT - ' . $tahun . '</h2>';
    echo '<p>PENGADILAN TATA USAHA NEGARA BANJARMASIN</p>';
    echo '<p>Digenerate pada: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<hr>';
    
    // Summary
    echo '<h3>RINGKASAN TAHUNAN</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr><td><b>Total Surat Masuk</b></td><td>' . $total_masuk . '</td></tr>';
    echo '<tr><td><b>Total Surat Keluar</b></td><td>' . $total_keluar . '</td></tr>';
    echo '<tr><td><b>Total Keseluruhan</b></td><td>' . ($total_masuk + $total_keluar) . '</td></tr>';
    echo '</table>';
    echo '<br><br>';
    
    // Monthly breakdown
    echo '<h3>RINCIAN PER BULAN</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr>';
    echo '<td><b>Bulan</b></td>';
    echo '<td><b>Surat Masuk</b></td>';
    echo '<td><b>Surat Keluar</b></td>';
    echo '<td><b>Total</b></td>';
    echo '<td><b>Persentase</b></td>';
    echo '</tr>';
    
    foreach ($monthly_data as $data) {
        $total_bulan = $data['masuk'] + $data['keluar'];
        $percentage = ($total_masuk + $total_keluar) > 0 ? 
            round(($total_bulan / ($total_masuk + $total_keluar)) * 100, 1) : 0;
        
        echo '<tr>';
        echo '<td>' . $data['nama_bulan'] . '</td>';
        echo '<td>' . $data['masuk'] . '</td>';
        echo '<td>' . $data['keluar'] . '</td>';
        echo '<td>' . $total_bulan . '</td>';
        echo '<td>' . $percentage . '%</td>';
        echo '</tr>';
    }
    
    echo '<tr style="background-color: #f0f0f0;">';
    echo '<td><b>TOTAL</b></td>';
    echo '<td><b>' . $total_masuk . '</b></td>';
    echo '<td><b>' . $total_keluar . '</b></td>';
    echo '<td><b>' . ($total_masuk + $total_keluar) . '</b></td>';
    echo '<td><b>100%</b></td>';
    echo '</tr>';
    echo '</table>';
    
    echo '</body></html>';
}

function exportRekapitulasi() {
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
    
    setExcelHeaders("Rekapitulasi_Surat_{$periode_label}");
    
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
    
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h2>REKAPITULASI SURAT - ' . strtoupper($periode_label) . '</h2>';
    echo '<p>PENGADILAN TATA USAHA NEGARA BANJARMASIN</p>';
    echo '<p>Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</p>';
    echo '<p>Digenerate pada: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<hr>';
    
    // Summary table
    echo '<h3>REKAPITULASI SURAT MASUK</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr><td><b>Status</b></td><td><b>Jumlah</b></td><td><b>Persentase</b></td></tr>';
    echo '<tr><td>Pending</td><td>' . $rekap_masuk['pending'] . '</td><td>' . 
         ($rekap_masuk['total'] > 0 ? round(($rekap_masuk['pending'] / $rekap_masuk['total']) * 100, 1) : 0) . '%</td></tr>';
    echo '<tr><td>Diproses</td><td>' . $rekap_masuk['diproses'] . '</td><td>' . 
         ($rekap_masuk['total'] > 0 ? round(($rekap_masuk['diproses'] / $rekap_masuk['total']) * 100, 1) : 0) . '%</td></tr>';
    echo '<tr><td>Selesai</td><td>' . $rekap_masuk['selesai'] . '</td><td>' . 
         ($rekap_masuk['total'] > 0 ? round(($rekap_masuk['selesai'] / $rekap_masuk['total']) * 100, 1) : 0) . '%</td></tr>';
    echo '<tr style="background-color: #f0f0f0;"><td><b>TOTAL</b></td><td><b>' . $rekap_masuk['total'] . '</b></td><td><b>100%</b></td></tr>';
    echo '</table>';
    echo '<br><br>';
    
    echo '<h3>REKAPITULASI SURAT KELUAR</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr><td><b>Status</b></td><td><b>Jumlah</b></td><td><b>Persentase</b></td></tr>';
    echo '<tr><td>Draft</td><td>' . $rekap_keluar['draft'] . '</td><td>' . 
         ($rekap_keluar['total'] > 0 ? round(($rekap_keluar['draft'] / $rekap_keluar['total']) * 100, 1) : 0) . '%</td></tr>';
    echo '<tr><td>Terkirim</td><td>' . $rekap_keluar['terkirim'] . '</td><td>' . 
         ($rekap_keluar['total'] > 0 ? round(($rekap_keluar['terkirim'] / $rekap_keluar['total']) * 100, 1) : 0) . '%</td></tr>';
    echo '<tr><td>Arsip</td><td>' . $rekap_keluar['arsip'] . '</td><td>' . 
         ($rekap_keluar['total'] > 0 ? round(($rekap_keluar['arsip'] / $rekap_keluar['total']) * 100, 1) : 0) . '%</td></tr>';
    echo '<tr style="background-color: #f0f0f0;"><td><b>TOTAL</b></td><td><b>' . $rekap_keluar['total'] . '</b></td><td><b>100%</b></td></tr>';
    echo '</table>';
    
    echo '</body></html>';
}

function exportLaporanDisposisi($bulan, $tahun, $nama_bulan) {
    global $db;
    
    setExcelHeaders("Laporan_Disposisi_{$nama_bulan}_{$tahun}");
    
    // Get disposisi data
    $disposisi_data = $db->fetchAll("
        SELECT sm.*, u.nama_lengkap as input_by
        FROM surat_masuk sm
        JOIN users u ON sm.user_id = u.id
        WHERE MONTH(sm.tanggal_diterima) = ? AND YEAR(sm.tanggal_diterima) = ?
        ORDER BY sm.tanggal_diterima DESC
    ", [$bulan, $tahun]);
    
    // Summary
    $total_surat = count($disposisi_data);
    $ada_disposisi = count(array_filter($disposisi_data, fn($s) => !empty($s['disposisi'])));
    $tidak_ada_disposisi = $total_surat - $ada_disposisi;
    
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h2>LAPORAN DISPOSISI - ' . strtoupper($nama_bulan) . ' ' . $tahun . '</h2>';
    echo '<p>PENGADILAN TATA USAHA NEGARA BANJARMASIN</p>';
    echo '<p>Digenerate pada: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<hr>';
    
    // Summary
    echo '<h3>RINGKASAN DISPOSISI</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr><td><b>Total Surat</b></td><td>' . $total_surat . '</td></tr>';
    echo '<tr><td><b>Ada Disposisi</b></td><td>' . $ada_disposisi . ' (' . 
         ($total_surat > 0 ? round(($ada_disposisi / $total_surat) * 100, 1) : 0) . '%)</td></tr>';
    echo '<tr><td><b>Belum Ada Disposisi</b></td><td>' . $tidak_ada_disposisi . ' (' . 
         ($total_surat > 0 ? round(($tidak_ada_disposisi / $total_surat) * 100, 1) : 0) . '%)</td></tr>';
    echo '</table>';
    echo '<br><br>';
    
    // Detail table
    echo '<h3>DETAIL SURAT DAN DISPOSISI</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
    echo '<tr>';
    echo '<td><b>No</b></td>';
    echo '<td><b>Nomor Surat</b></td>';
    echo '<td><b>Tanggal Diterima</b></td>';
    echo '<td><b>Pengirim</b></td>';
    echo '<td><b>Perihal</b></td>';
    echo '<td><b>Disposisi</b></td>';
    echo '<td><b>Status</b></td>';
    echo '<td><b>Input By</b></td>';
    echo '</tr>';
    
    $no = 1;
    foreach ($disposisi_data as $surat) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($surat['nomor_surat']) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($surat['tanggal_diterima'])) . '</td>';
        echo '<td>' . htmlspecialchars($surat['pengirim']) . '</td>';
        echo '<td>' . htmlspecialchars($surat['perihal']) . '</td>';
        echo '<td>' . htmlspecialchars($surat['disposisi'] ?: 'Belum ada disposisi') . '</td>';
        echo '<td>' . strtoupper($surat['status']) . '</td>';
        echo '<td>' . htmlspecialchars($surat['input_by']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</body></html>';
}
?>
