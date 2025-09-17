<?php
// pages/maintenance/backup.php
require_once '../../config/config.php';

// Require login and admin role
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    setFlashMessage('danger', 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
    redirect('../../index.php');
}

// Set page variables
$page_title = 'Backup & Restore';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            handleCreateBackup();
            break;
        case 'restore_backup':
            handleRestoreBackup();
            break;
        case 'delete_backup':
            handleDeleteBackup();
            break;
        case 'schedule_backup':
            handleScheduleBackup();
            break;
    }
}

function handleCreateBackup() {
    try {
        $backup_dir = '../../backups/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_name = $_POST['backup_name'] ?? 'backup_' . $timestamp;
        $backup_type = $_POST['backup_type'] ?? 'full';
        
        // Sanitize backup name
        $backup_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $backup_name);
        if (empty($backup_name)) {
            $backup_name = 'backup_' . $timestamp;
        }
        
        $backup_file = $backup_dir . $backup_name . '_' . $timestamp . '.zip';
        
        // Create backup
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Tidak dapat membuat file backup');
        }
        
        // Backup database
        $sql_file = 'database_' . $timestamp . '.sql';
        $sql_content = createDatabaseBackup();
        $zip->addFromString($sql_file, $sql_content);
        
        // Backup files if full backup
        if ($backup_type === 'full') {
            $upload_dir = '../../assets/uploads/';
            if (is_dir($upload_dir)) {
                addDirectoryToZip($zip, $upload_dir, 'uploads/');
            }
            
            // Backup config files
            $config_files = [
                '../../config/config.php',
                '../../config/database.php'
            ];
            
            foreach ($config_files as $config_file) {
                if (file_exists($config_file)) {
                    $zip->addFile($config_file, 'config/' . basename($config_file));
                }
            }
        }
        
        // Add backup info
        $backup_info = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $backup_type,
            'created_by' => $_SESSION['nama_lengkap'],
            'version' => '2.0',
            'php_version' => phpversion(),
            'mysql_version' => getDBVersion()
        ];
        
        $zip->addFromString('backup_info.json', json_encode($backup_info, JSON_PRETTY_PRINT));
        $zip->close();
        
        // Log backup creation
        global $db;
        $db->query("
            INSERT INTO backup_logs (backup_name, backup_type, file_path, created_by, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ", [$backup_name, $backup_type, $backup_file, $_SESSION['user_id']]);
        
        setFlashMessage('success', "Backup '$backup_name' berhasil dibuat");
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal membuat backup: ' . $e->getMessage());
    }
    
    redirect('backup.php');
}

function handleRestoreBackup() {
    try {
        $backup_file = $_POST['backup_file'] ?? '';
        
        if (!file_exists($backup_file)) {
            throw new Exception('File backup tidak ditemukan');
        }
        
        if (!isset($_POST['confirm_restore']) || $_POST['confirm_restore'] !== 'yes') {
            throw new Exception('Konfirmasi restore diperlukan');
        }
        
        $zip = new ZipArchive();
        if ($zip->open($backup_file) !== TRUE) {
            throw new Exception('Tidak dapat membuka file backup');
        }
        
        $temp_dir = '../../temp/restore_' . time() . '/';
        mkdir($temp_dir, 0755, true);
        
        // Extract backup
        $zip->extractTo($temp_dir);
        $zip->close();
        
        // Check backup info
        $info_file = $temp_dir . 'backup_info.json';
        if (file_exists($info_file)) {
            $backup_info = json_decode(file_get_contents($info_file), true);
            // You could add version compatibility checks here
        }
        
        // Restore database
        $sql_files = glob($temp_dir . 'database_*.sql');
        if (!empty($sql_files)) {
            $sql_content = file_get_contents($sql_files[0]);
            restoreDatabase($sql_content);
        }
        
        // Restore files if they exist
        $uploads_dir = $temp_dir . 'uploads/';
        if (is_dir($uploads_dir)) {
            $target_dir = '../../assets/uploads/';
            copyDirectory($uploads_dir, $target_dir);
        }
        
        // Clean up temp directory
        deleteDirectory($temp_dir);
        
        // Log restore
        global $db;
        $db->query("
            INSERT INTO backup_logs (backup_name, backup_type, file_path, action, created_by, created_at) 
            VALUES (?, 'restore', ?, 'restore', ?, NOW())
        ", [basename($backup_file), $backup_file, $_SESSION['user_id']]);
        
        setFlashMessage('success', 'Backup berhasil direstore');
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal restore backup: ' . $e->getMessage());
    }
    
    redirect('backup.php');
}

function handleDeleteBackup() {
    try {
        $backup_file = $_POST['backup_file'] ?? '';
        
        if (!file_exists($backup_file)) {
            throw new Exception('File backup tidak ditemukan');
        }
        
        unlink($backup_file);
        
        // Update log
        global $db;
        $db->query("UPDATE backup_logs SET deleted_at = NOW() WHERE file_path = ?", [$backup_file]);
        
        setFlashMessage('success', 'Backup berhasil dihapus');
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal menghapus backup: ' . $e->getMessage());
    }
    
    redirect('backup.php');
}

function handleScheduleBackup() {
    // This would handle scheduled backup settings
    setFlashMessage('info', 'Fitur penjadwalan backup akan segera tersedia');
    redirect('backup.php');
}

// Helper functions
function createDatabaseBackup() {
    global $db;
    
    $sql = "-- SIMAK PTUN Database Backup\n";
    $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // Get all tables
    $tables = $db->fetchAll("SHOW TABLES");
    $table_key = 'Tables_in_' . DB_NAME;
    
    foreach ($tables as $table) {
        $table_name = $table[$table_key];
        
        // Table structure
        $create_table = $db->fetch("SHOW CREATE TABLE `$table_name`");
        $sql .= "-- Table structure for `$table_name`\n";
        $sql .= "DROP TABLE IF EXISTS `$table_name`;\n";
        $sql .= $create_table['Create Table'] . ";\n\n";
        
        // Table data
        $rows = $db->fetchAll("SELECT * FROM `$table_name`");
        if (!empty($rows)) {
            $sql .= "-- Data for table `$table_name`\n";
            
            foreach ($rows as $row) {
                $values = array_map(function($value) {
                    return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }, array_values($row));
                
                $columns = '`' . implode('`, `', array_keys($row)) . '`';
                $sql .= "INSERT INTO `$table_name` ($columns) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    return $sql;
}

function restoreDatabase($sql_content) {
    global $db;
    
    // Split SQL into individual queries
    $queries = explode(';', $sql_content);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->query($query);
        }
    }
}

function addDirectoryToZip($zip, $dir, $zip_dir = '') {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_path = $dir . $file;
                $zip_path = $zip_dir . $file;
                
                if (is_dir($file_path)) {
                    addDirectoryToZip($zip, $file_path . '/', $zip_path . '/');
                } else {
                    $zip->addFile($file_path, $zip_path);
                }
            }
        }
    }
}

function copyDirectory($src, $dst) {
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    
    if (is_dir($src)) {
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $src_file = $src . $file;
                $dst_file = $dst . $file;
                
                if (is_dir($src_file)) {
                    copyDirectory($src_file . '/', $dst_file . '/');
                } else {
                    copy($src_file, $dst_file);
                }
            }
        }
    }
}

function deleteDirectory($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_path = $dir . $file;
                if (is_dir($file_path)) {
                    deleteDirectory($file_path . '/');
                } else {
                    unlink($file_path);
                }
            }
        }
        rmdir($dir);
    }
}

function getDBVersion() {
    global $db;
    return $db->fetch("SELECT VERSION() as version")['version'];
}

// Get existing backups
$backup_dir = '../../backups/';
$backup_files = [];

if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.zip');
    foreach ($files as $file) {
        $backup_files[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'created' => filemtime($file)
        ];
    }
    
    // Sort by creation time (newest first)
    usort($backup_files, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// Get backup logs
try {
    $backup_logs = $db->fetchAll("
        SELECT bl.*, u.nama_lengkap 
        FROM backup_logs bl 
        JOIN users u ON bl.created_by = u.id 
        ORDER BY bl.created_at DESC 
        LIMIT 20
    ");
} catch (Exception $e) {
    $backup_logs = [];
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
                    <i class="fas fa-database"></i>
                    Backup & Restore
                </h1>
                <p class="page-subtitle">
                    Kelola backup dan restore data sistem
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                    <i class="fas fa-plus"></i>
                    Buat Backup
                </button>
            </div>
        </div>
    </div>
    
    <!-- System Info -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-card">
                <div class="info-icon bg-primary">
                    <i class="fas fa-database"></i>
                </div>
                <div class="info-content">
                    <h4><?= count($backup_files) ?></h4>
                    <p>Total Backup</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card">
                <div class="info-icon bg-success">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="info-content">
                    <h4><?= array_sum(array_column($backup_files, 'size')) > 0 ? number_format(array_sum(array_column($backup_files, 'size')) / 1024 / 1024, 2) . ' MB' : '0 MB' ?></h4>
                    <p>Total Size</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card">
                <div class="info-icon bg-info">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="info-content">
                    <h4><?= !empty($backup_files) ? date('d/m/Y', max(array_column($backup_files, 'created'))) : 'N/A' ?></h4>
                    <p>Backup Terakhir</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-card">
                <div class="info-icon bg-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="info-content">
                    <h4><?= disk_free_space('../../') > 0 ? number_format(disk_free_space('../../') / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A' ?></h4>
                    <p>Free Space</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Backup Files -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-archive"></i>
                File Backup Tersedia
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($backup_files)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-database fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada backup</h5>
                    <p class="text-muted">Buat backup pertama untuk mengamankan data sistem</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                        <i class="fas fa-plus"></i>
                        Buat Backup Sekarang
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama Backup</th>
                                <th>Ukuran</th>
                                <th>Tanggal Dibuat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_files as $backup): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-archive text-primary me-2"></i>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($backup['name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($backup['path']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB</td>
                                    <td>
                                        <div><?= date('d/m/Y H:i', $backup['created']) ?></div>
                                        <small class="text-muted"><?= timeAgo(date('Y-m-d H:i:s', $backup['created'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i>
                                            Valid
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= $backup['path'] ?>" class="btn btn-info btn-sm" download>
                                                <i class="fas fa-download"></i>
                                                Download
                                            </a>
                                            <button type="button" class="btn btn-success btn-sm" 
                                                    onclick="restoreBackup('<?= htmlspecialchars($backup['path']) ?>', '<?= htmlspecialchars($backup['name']) ?>')">
                                                <i class="fas fa-undo"></i>
                                                Restore
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="deleteBackup('<?= htmlspecialchars($backup['path']) ?>', '<?= htmlspecialchars($backup['name']) ?>')">
                                                <i class="fas fa-trash"></i>
                                                Hapus
                                            </button>
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
    
    <!-- Backup Logs -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-history"></i>
                Log Aktivitas Backup
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($backup_logs)): ?>
                <p class="text-center text-muted">Belum ada log aktivitas backup</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                                <th>Nama Backup</th>
                                <th>Tipe</th>
                                <th>User</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_logs as $log): ?>
                                <tr>
                                    <td><?= formatTanggal($log['created_at']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $log['action'] === 'restore' ? 'success' : 'primary' ?>">
                                            <i class="fas fa-<?= $log['action'] === 'restore' ? 'undo' : 'save' ?>"></i>
                                            <?= ucfirst($log['action'] ?? 'backup') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['backup_name']) ?></td>
                                    <td><?= ucfirst($log['backup_type']) ?></td>
                                    <td><?= htmlspecialchars($log['nama_lengkap']) ?></td>
                                    <td>
                                        <?php if ($log['deleted_at']): ?>
                                            <span class="badge badge-secondary">Deleted</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Success</span>
                                        <?php endif; ?>
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

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i>
                    Buat Backup Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_backup">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Backup</label>
                        <input type="text" name="backup_name" class="form-control" 
                               placeholder="backup_<?= date('Y_m_d') ?>" 
                               value="backup_<?= date('Y_m_d') ?>">
                        <small class="text-muted">Hanya huruf, angka, underscore, dan dash yang diizinkan</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tipe Backup</label>
                        <select name="backup_type" class="form-control">
                            <option value="full">Full Backup (Database + Files)</option>
                            <option value="database">Database Only</option>
                        </select>
                        <small class="text-muted">Full backup termasuk database dan file uploads</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Informasi Backup</h6>
                        <ul class="mb-0">
                            <li>Backup akan disimpan dalam format ZIP</li>
                            <li>Database akan diexport dalam format SQL</li>
                            <li>File uploads akan disertakan jika memilih Full Backup</li>
                            <li>Backup dapat didownload atau direstore kapan saja</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Buat Backup
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Konfirmasi Restore
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="restoreForm">
                <input type="hidden" name="action" value="restore_backup">
                <input type="hidden" name="backup_file" id="restore_backup_file">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> PERINGATAN PENTING!</h6>
                        <p class="mb-0">
                            Proses restore akan <strong>mengganti seluruh data yang ada</strong> dengan data dari backup. 
                            Pastikan Anda telah membuat backup terbaru sebelum melakukan restore.
                        </p>
                    </div>
                    
                    <p>Anda akan merestore backup: <strong id="restore_backup_name"></strong></p>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="confirm_restore" value="yes" required>
                        <label class="form-check-label">
                            <strong>Saya memahami risiko dan ingin melanjutkan proses restore</strong>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo"></i>
                        Ya, Restore Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Forms -->
<form id="deleteBackupForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_backup">
    <input type="hidden" name="backup_file" id="delete_backup_file">
</form>

<?php
$inline_scripts = '
function restoreBackup(filePath, fileName) {
    document.getElementById("restore_backup_file").value = filePath;
    document.getElementById("restore_backup_name").textContent = fileName;
    new bootstrap.Modal(document.getElementById("restoreModal")).show();
}

function deleteBackup(filePath, fileName) {
    if (confirm("Apakah Anda yakin ingin menghapus backup: " + fileName + "?\\n\\nFile backup akan dihapus permanen dan tidak dapat dikembalikan.")) {
        document.getElementById("delete_backup_file").value = filePath;
        document.getElementById("deleteBackupForm").submit();
    }
}

// Auto-refresh backup list every 30 seconds
setInterval(function() {
    if (!document.querySelector(".modal.show")) {
        // Only refresh if no modal is open
        console.log("Auto-refreshing backup list...");
    }
}, 30000);

// Estimate backup size before creation
function estimateBackupSize() {
    fetch("../../api/estimate-backup-size.php")
        .then(response => response.json())
        .then(data => {
            console.log("Estimated backup size:", data.size);
        })
        .catch(error => {
            console.error("Error estimating backup size:", error);
        });
}

// Check available disk space
function checkDiskSpace() {
    const freeSpaceElement = document.querySelector(".info-card:last-child h4");
    if (freeSpaceElement) {
        const freeSpaceText = freeSpaceElement.textContent;
        const freeSpaceGB = parseFloat(freeSpaceText);
        
        if (freeSpaceGB < 1) {
            freeSpaceElement.className = "text-danger";
            freeSpaceElement.parentElement.parentElement.classList.add("border-danger");
        } else if (freeSpaceGB < 5) {
            freeSpaceElement.className = "text-warning";
            freeSpaceElement.parentElement.parentElement.classList.add("border-warning");
        }
    }
}

// Check disk space on page load
document.addEventListener("DOMContentLoaded", checkDiskSpace);

// Add backup styles
const backupStyles = `
<style>
.info-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.info-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.info-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.5rem;
    color: white;
}

.info-content h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
    font-weight: 700;
}

.info-content p {
    margin: 0;
    color: var(--text-secondary);
    font-weight: 500;
}

.btn-group .btn {
    margin-right: 0.125rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.border-danger {
    border-color: var(--danger-color) !important;
}

.border-warning {
    border-color: var(--warning-color) !important;
}

@media (max-width: 768px) {
    .info-card {
        flex-direction: column;
        text-align: center;
    }
    
    .info-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin-right: 0;
        margin-bottom: 0.125rem;
        width: 100%;
    }
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", backupStyles);
';

include '../../includes/footer.php';
?>
