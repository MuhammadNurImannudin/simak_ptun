<?php
// pages/surat-keluar/index.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Surat Keluar';
$show_search = true;
$search_table = 'surat_keluar';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Get file info before delete
        $surat = $db->fetch("SELECT file_surat FROM surat_keluar WHERE id = ?", [$id]);
        
        // Delete from database
        $db->query("DELETE FROM surat_keluar WHERE id = ?", [$id]);
        
        // Delete file if exists
        if ($surat && $surat['file_surat'] && file_exists(UPLOAD_PATH . $surat['file_surat'])) {
            unlink(UPLOAD_PATH . $surat['file_surat']);
        }
        
        setFlashMessage('success', 'Surat keluar berhasil dihapus');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal menghapus surat keluar: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

// Handle status update
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['status']);
    
    if (in_array($status, ['draft', 'terkirim', 'arsip'])) {
        try {
            $db->query("UPDATE surat_keluar SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
            setFlashMessage('success', 'Status surat berhasil diperbarui');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }
    
    redirect('index.php');
}

// Pagination and filtering
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$where_conditions = [];
$params = [];

// Filter by status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "sk.status = ?";
    $params[] = $_GET['status'];
}

// Filter by date range
if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
    $where_conditions[] = "sk.tanggal_surat >= ?";
    $params[] = $_GET['from_date'];
}

if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $where_conditions[] = "sk.tanggal_surat <= ?";
    $params[] = $_GET['to_date'];
}

// Search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(sk.nomor_surat LIKE ? OR sk.tujuan LIKE ? OR sk.perihal LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM surat_keluar sk $where_clause";
$total_records = $db->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_records / $limit);

// Get data
$sql = "SELECT sk.*, u.nama_lengkap as input_by 
        FROM surat_keluar sk 
        JOIN users u ON sk.user_id = u.id 
        $where_clause 
        ORDER BY sk.created_at DESC 
        LIMIT $limit OFFSET $offset";

$surat_keluar = $db->fetchAll($sql, $params);

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
                    Surat Keluar
                </h1>
                <p class="page-subtitle">
                    Kelola semua surat keluar dari Pengadilan Tata Usaha Negara Banjarmasin
                </p>
            </div>
            <div>
                <a href="tambah.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Surat Keluar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="terkirim" <?= ($_GET['status'] ?? '') === 'terkirim' ? 'selected' : '' ?>>Terkirim</option>
                        <option value="arsip" <?= ($_GET['status'] ?? '') === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from_date" class="form-control" value="<?= $_GET['from_date'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to_date" class="form-control" value="<?= $_GET['to_date'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <div class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nomor surat, tujuan, perihal..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="btn btn-outline">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="index.php" class="btn btn-outline">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Stats Summary -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon secondary">
                <i class="fas fa-edit"></i>
            </div>
            <div class="stat-content">
                <h3><?= getTotalSuratKeluar('draft') ?></h3>
                <p>Draft</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="stat-content">
                <h3><?= getTotalSuratKeluar('terkirim') ?></h3>
                <p>Terkirim</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-archive"></i>
            </div>
            <div class="stat-content">
                <h3><?= getTotalSuratKeluar('arsip') ?></h3>
                <p>Arsip</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="stat-content">
                <h3><?= getTotalSuratKeluar() ?></h3>
                <p>Total</p>
            </div>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                Daftar Surat Keluar
                <span class="badge badge-primary"><?= number_format($total_records) ?> surat</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($surat_keluar)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-paper-plane" style="font-size: 4rem; color: var(--text-light);"></i>
                    <h4 class="mt-3 text-secondary">Tidak Ada Surat Keluar</h4>
                    <p class="text-secondary">Belum ada surat keluar yang tercatat dalam sistem</p>
                    <a href="tambah.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah Surat Keluar Pertama
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="suratKeluarTable">
                        <thead>
                            <tr>
                                <th>No. Surat</th>
                                <th>Tanggal Surat</th>
                                <th>Tujuan</th>
                                <th>Perihal</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($surat_keluar as $surat): ?>
                                <tr>
                                    <td>
                                        <a href="detail.php?id=<?= $surat['id'] ?>" class="text-decoration-none fw-bold">
                                            <?= htmlspecialchars($surat['nomor_surat']) ?>
                                        </a>
                                        <small class="d-block text-muted">
                                            oleh <?= htmlspecialchars($surat['input_by']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?= formatTanggal($surat['tanggal_surat']) ?>
                                        <small class="d-block text-muted">
                                            Input: <?= formatTanggal($surat['created_at']) ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($surat['tujuan']) ?></td>
                                    <td>
                                        <?= htmlspecialchars(substr($surat['perihal'], 0, 50)) ?>
                                        <?= strlen($surat['perihal']) > 50 ? '...' : '' ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <span class="badge badge-<?= $surat['status'] === 'draft' ? 'secondary' : ($surat['status'] === 'terkirim' ? 'success' : 'info') ?> dropdown-toggle" 
                                                  style="cursor: pointer;" 
                                                  onclick="toggleStatusDropdown(<?= $surat['id'] ?>)">
                                                <?= ucfirst($surat['status']) ?>
                                            </span>
                                            <div class="dropdown-menu" id="statusDropdown<?= $surat['id'] ?>">
                                                <a class="dropdown-item" href="?action=update_status&id=<?= $surat['id'] ?>&status=draft">
                                                    <span class="badge badge-secondary">Draft</span>
                                                </a>
                                                <a class="dropdown-item" href="?action=update_status&id=<?= $surat['id'] ?>&status=terkirim">
                                                    <span class="badge badge-success">Terkirim</span>
                                                </a>
                                                <a class="dropdown-item" href="?action=update_status&id=<?= $surat['id'] ?>&status=arsip">
                                                    <span class="badge badge-info">Arsip</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($surat['file_surat']): ?>
                                            <a href="<?= UPLOAD_URL . $surat['file_surat'] ?>" target="_blank" class="btn btn-outline btn-sm">
                                                <i class="fas fa-file-pdf"></i>
                                                Lihat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="detail.php?id=<?= $surat['id'] ?>" 
                                               class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $surat['id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($surat['status'] === 'draft'): ?>
                                                <a href="?action=update_status&id=<?= $surat['id'] ?>&status=terkirim" 
                                                   class="btn btn-success btn-sm" title="Kirim">
                                                    <i class="fas fa-paper-plane"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="javascript:void(0)" 
                                               onclick="confirmDelete('?action=delete&id=<?= $surat['id'] ?>', 'Apakah Anda yakin ingin menghapus surat ini?')"
                                               class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_records) ?> dari <?= number_format($total_records) ?> data
                        </div>
                        <nav>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
$inline_scripts = '
function toggleStatusDropdown(id) {
    // Close all other dropdowns first
    document.querySelectorAll(".dropdown-menu").forEach(menu => {
        if (menu.id !== "statusDropdown" + id) {
            menu.classList.remove("show");
        }
    });
    
    // Toggle current dropdown
    const dropdown = document.getElementById("statusDropdown" + id);
    dropdown.classList.toggle("show");
}

// Close dropdowns when clicking outside
document.addEventListener("click", function(e) {
    if (!e.target.closest(".dropdown")) {
        document.querySelectorAll(".dropdown-menu").forEach(menu => {
            menu.classList.remove("show");
        });
    }
});

// Add pagination styles
const paginationStyles = `
<style>
.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 0.25rem;
}

.page-item .page-link {
    display: block;
    padding: 0.5rem 0.75rem;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.page-item.active .page-link {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.page-item .page-link:hover {
    background: var(--bg-color);
    border-color: var(--primary-color);
}

.dropdown-menu {
    position: absolute;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    box-shadow: var(--shadow-lg);
    z-index: 1000;
    display: none;
    min-width: 120px;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-menu .dropdown-item {
    display: block;
    padding: 0.5rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: background-color 0.2s ease;
}

.dropdown-menu .dropdown-item:hover {
    background-color: var(--bg-color);
}

.btn-group {
    display: flex;
    gap: 0.25rem;
}

.btn-group .btn {
    border-radius: 6px;
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", paginationStyles);
';

include '../../includes/footer.php';
?>
