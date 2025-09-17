<?php
// pages/surat-keluar/detail.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Detail Surat Keluar';

// Get ID from URL
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlashMessage('danger', 'ID surat tidak valid');
    redirect('index.php');
}

// Get surat data with user info
$surat = $db->fetch("
    SELECT sk.*, u.nama_lengkap as input_by, u.email as input_email
    FROM surat_keluar sk 
    JOIN users u ON sk.user_id = u.id 
    WHERE sk.id = ?
", [$id]);

if (!$surat) {
    setFlashMessage('danger', 'Surat tidak ditemukan');
    redirect('index.php');
}

// Handle quick status update
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['status'])) {
    $status = sanitize($_GET['status']);
    
    if (in_array($status, ['draft', 'terkirim', 'arsip'])) {
        try {
            $db->query("UPDATE surat_keluar SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
            
            // Create notification
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $db->query($notification_sql, [
                $_SESSION['user_id'],
                'Status Surat Diperbarui',
                "Status surat {$surat['nomor_surat']} diubah menjadi " . ucfirst($status),
                'info'
            ]);
            
            setFlashMessage('success', 'Status surat berhasil diperbarui');
            $surat['status'] = $status; // Update local variable
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }
    
    redirect('detail.php?id=' . $id);
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
                    <i class="fas fa-paper-plane"></i>
                    Detail Surat Keluar
                </h1>
                <p class="page-subtitle">
                    <?= htmlspecialchars($surat['nomor_surat']) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline dropdown-toggle" type="button" onclick="toggleActionDropdown()">
                        <i class="fas fa-cogs"></i>
                        Aksi
                    </button>
                    <div class="dropdown-menu" id="actionDropdown">
                        <a href="edit.php?id=<?= $id ?>" class="dropdown-item">
                            <i class="fas fa-edit"></i>
                            Edit Surat
                        </a>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">Update Status</h6>
                        <a href="?action=update_status&status=draft" class="dropdown-item">
                            <span class="badge badge-secondary">Draft</span>
                        </a>
                        <a href="?action=update_status&status=terkirim" class="dropdown-item">
                            <span class="badge badge-success">Terkirim</span>
                        </a>
                        <a href="?action=update_status&status=arsip" class="dropdown-item">
                            <span class="badge badge-info">Arsip</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="javascript:void(0)" onclick="printDetail()" class="dropdown-item">
                            <i class="fas fa-print"></i>
                            Cetak Detail
                        </a>
                    </div>
                </div>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Kembali
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Main Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Informasi Surat Keluar</h5>
                    <span class="badge badge-<?= $surat['status'] === 'draft' ? 'secondary' : ($surat['status'] === 'terkirim' ? 'success' : 'info') ?> badge-lg">
                        <?= ucfirst($surat['status']) ?>
                    </span>
                </div>
                <div class="card-body" id="printableArea">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%" class="fw-bold">Nomor Surat:</td>
                                    <td><?= htmlspecialchars($surat['nomor_surat']) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tanggal Surat:</td>
                                    <td><?= formatTanggal($surat['tanggal_surat']) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tujuan:</td>
                                    <td><?= htmlspecialchars($surat['tujuan']) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Lampiran:</td>
                                    <td><?= htmlspecialchars($surat['lampiran'] ?: '-') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%" class="fw-bold">Status:</td>
                                    <td>
                                        <span class="badge badge-<?= $surat['status'] === 'draft' ? 'secondary' : ($surat['status'] === 'terkirim' ? 'success' : 'info') ?>">
                                            <?= ucfirst($surat['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Dibuat oleh:</td>
                                    <td><?= htmlspecialchars($surat['input_by']) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tanggal Dibuat:</td>
                                    <td><?= formatTanggal($surat['created_at']) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Terakhir Update:</td>
                                    <td><?= formatTanggal($surat['updated_at']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Perihal:</h6>
                        <div class="border-start border-success border-3 ps-3">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($surat['perihal'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Timeline/History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-history"></i>
                        Riwayat Surat
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>Surat Dibuat</h6>
                                <p class="text-muted mb-1">
                                    Surat keluar berhasil dibuat oleh <?= htmlspecialchars($surat['input_by']) ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    <?= formatTanggal($surat['created_at']) ?>
                                </small>
                            </div>
                        </div>
                        
                        <?php if ($surat['updated_at'] !== $surat['created_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Surat Diperbarui</h6>
                                    <p class="text-muted mb-1">
                                        Data surat telah diperbarui
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        <?= formatTanggal($surat['updated_at']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($surat['status'] === 'terkirim'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Surat Terkirim</h6>
                                    <p class="text-muted mb-1">
                                        Surat telah berhasil dikirim ke tujuan
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        Status terakhir diperbarui: <?= formatTanggal($surat['updated_at']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php elseif ($surat['status'] === 'arsip'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info">
                                    <i class="fas fa-archive"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Surat Diarsipkan</h6>
                                    <p class="text-muted mb-1">
                                        Surat telah diarsipkan
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        Status terakhir diperbarui: <?= formatTanggal($surat['updated_at']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Information -->
        <div class="col-lg-4">
            <!-- File Attachment -->
            <?php if ($surat['file_surat']): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-paperclip"></i>
                            File Lampiran
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="file-preview mb-3">
                            <?php
                            $file_ext = strtolower(pathinfo($surat['file_surat'], PATHINFO_EXTENSION));
                            $file_icon = 'fa-file';
                            $file_color = 'text-secondary';
                            
                            switch ($file_ext) {
                                case 'pdf':
                                    $file_icon = 'fa-file-pdf';
                                    $file_color = 'text-danger';
                                    break;
                                case 'doc':
                                case 'docx':
                                    $file_icon = 'fa-file-word';
                                    $file_color = 'text-primary';
                                    break;
                                case 'jpg':
                                case 'jpeg':
                                case 'png':
                                    $file_icon = 'fa-file-image';
                                    $file_color = 'text-success';
                                    break;
                            }
                            ?>
                            <i class="fas <?= $file_icon ?> <?= $file_color ?>" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h6><?= basename($surat['file_surat']) ?></h6>
                        <p class="text-muted small">
                            Format: <?= strtoupper($file_ext) ?>
                        </p>
                        
                        <div class="d-grid gap-2">
                            <a href="<?= UPLOAD_URL . $surat['file_surat'] ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                Lihat File
                            </a>
                            <a href="<?= UPLOAD_URL . $surat['file_surat'] ?>" download class="btn btn-outline">
                                <i class="fas fa-download"></i>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-bolt"></i>
                        Aksi Cepat
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?= $id ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i>
                            Edit Surat
                        </a>
                        
                        <?php if ($surat['status'] === 'draft'): ?>
                            <a href="?action=update_status&status=terkirim" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i>
                                Tandai Terkirim
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($surat['status'] === 'terkirim'): ?>
                            <a href="?action=update_status&status=arsip" class="btn btn-info">
                                <i class="fas fa-archive"></i>
                                Arsipkan
                            </a>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-secondary" onclick="duplicateSurat()">
                            <i class="fas fa-copy"></i>
                            Duplikasi
                        </button>
                        
                        <button type="button" class="btn btn-info" onclick="shareDetail()">
                            <i class="fas fa-share"></i>
                            Bagikan
                        </button>
                        
                        <button type="button" class="btn btn-outline" onclick="printDetail()">
                            <i class="fas fa-print"></i>
                            Cetak
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Informasi Tambahan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-success mb-0">
                                    <?php
                                    $days_diff = (new DateTime())->diff(new DateTime($surat['tanggal_surat']))->days;
                                    echo $days_diff;
                                    ?>
                                </h4>
                                <small class="text-muted">Hari</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-primary mb-0">
                                <?= getTotalSuratKeluar() ?>
                            </h4>
                            <small class="text-muted">Total Surat</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Surat ini dibuat <?= $days_diff ?> hari yang lalu
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Related Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-link"></i>
                        Tindak Lanjut
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="tambah.php?duplicate=<?= $id ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-copy"></i>
                            Buat Surat Serupa
                        </a>
                        <a href="../reports/laporan-bulanan.php?focus=keluar&surat_id=<?= $id ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-file-alt"></i>
                            Lihat dalam Laporan
                        </a>
                        <a href="../surat-masuk/tambah.php?ref_keluar=<?= $id ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-reply"></i>
                            Catat Balasan Masuk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$inline_scripts = '
function toggleActionDropdown() {
    const dropdown = document.getElementById("actionDropdown");
    dropdown.classList.toggle("show");
}

// Close dropdown when clicking outside
document.addEventListener("click", function(e) {
    if (!e.target.closest(".dropdown")) {
        document.querySelectorAll(".dropdown-menu").forEach(menu => {
            menu.classList.remove("show");
        });
    }
});

function duplicateSurat() {
    if (confirm("Apakah Anda ingin membuat surat keluar baru dengan data serupa?")) {
        window.location.href = "tambah.php?duplicate=' . $id . '";
    }
}

function printDetail() {
    const printContent = document.getElementById("printableArea").innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2>PENGADILAN TATA USAHA NEGARA BANJARMASIN</h2>
                <h3>DETAIL SURAT KELUAR</h3>
                <hr>
            </div>
            ${printContent}
            <div style="margin-top: 30px; text-align: right;">
                <p>Dicetak pada: ${new Date().toLocaleDateString("id-ID")}</p>
            </div>
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

function shareDetail() {
    const url = window.location.href;
    const title = "Detail Surat Keluar - ' . htmlspecialchars($surat['nomor_surat']) . '";
    
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            showSuccessToast("Link detail surat berhasil disalin ke clipboard");
        });
    }
}

// Add timeline styles
const timelineStyles = `
<style>
.timeline {
    position: relative;
}

.timeline::before {
    content: "";
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-color);
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    z-index: 1;
}

.timeline-content h6 {
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.badge-lg {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}

.fw-bold {
    font-weight: 600;
}

@media print {
    .card-header,
    .btn,
    .dropdown {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", timelineStyles);
';

include '../../includes/footer.php';
?>
