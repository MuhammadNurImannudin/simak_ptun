<?php
// pages/search/index.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Pencarian Lanjutan';

// Get search parameters
$search_term = sanitize($_GET['q'] ?? '');
$search_type = sanitize($_GET['type'] ?? 'all');
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to = sanitize($_GET['date_to'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$user_id = (int)($_GET['user_id'] ?? 0);
$has_file = sanitize($_GET['has_file'] ?? '');

// Pagination
$limit = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

try {
    $results = [];
    $total_results = 0;
    
    if (!empty($search_term) || !empty($date_from) || !empty($date_to) || !empty($status) || $user_id > 0) {
        // Build search conditions
        $conditions = [];
        $params = [];
        
        // Base search query
        $base_query = "";
        
        if ($search_type === 'masuk' || $search_type === 'all') {
            // Surat Masuk search
            $masuk_conditions = [];
            $masuk_params = [];
            
            if (!empty($search_term)) {
                $masuk_conditions[] = "(sm.nomor_surat LIKE ? OR sm.pengirim LIKE ? OR sm.perihal LIKE ? OR sm.disposisi LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $masuk_params = array_merge($masuk_params, [$search_param, $search_param, $search_param, $search_param]);
            }
            
            if (!empty($date_from)) {
                $masuk_conditions[] = "sm.tanggal_diterima >= ?";
                $masuk_params[] = $date_from;
            }
            
            if (!empty($date_to)) {
                $masuk_conditions[] = "sm.tanggal_diterima <= ?";
                $masuk_params[] = $date_to;
            }
            
            if (!empty($status)) {
                $masuk_conditions[] = "sm.status = ?";
                $masuk_params[] = $status;
            }
            
            if ($user_id > 0) {
                $masuk_conditions[] = "sm.user_id = ?";
                $masuk_params[] = $user_id;
            }
            
            if ($has_file === 'yes') {
                $masuk_conditions[] = "sm.file_surat IS NOT NULL AND sm.file_surat != ''";
            } elseif ($has_file === 'no') {
                $masuk_conditions[] = "(sm.file_surat IS NULL OR sm.file_surat = '')";
            }
            
            if (!empty($masuk_conditions)) {
                $masuk_query = "
                    SELECT 
                        sm.*, 
                        u.nama_lengkap as input_by,
                        'masuk' as surat_type,
                        sm.tanggal_diterima as tanggal_surat_sort,
                        sm.pengirim as pengirim_tujuan
                    FROM surat_masuk sm
                    JOIN users u ON sm.user_id = u.id
                    WHERE " . implode(' AND ', $masuk_conditions);
                
                if ($search_type === 'all') {
                    $base_query .= "(" . $masuk_query . ")";
                    $params = array_merge($params, $masuk_params);
                } else {
                    $base_query = $masuk_query;
                    $params = $masuk_params;
                }
            }
        }
        
        if ($search_type === 'keluar' || $search_type === 'all') {
            // Surat Keluar search
            $keluar_conditions = [];
            $keluar_params = [];
            
            if (!empty($search_term)) {
                $keluar_conditions[] = "(sk.nomor_surat LIKE ? OR sk.tujuan LIKE ? OR sk.perihal LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $keluar_params = array_merge($keluar_params, [$search_param, $search_param, $search_param]);
            }
            
            if (!empty($date_from)) {
                $keluar_conditions[] = "sk.tanggal_surat >= ?";
                $keluar_params[] = $date_from;
            }
            
            if (!empty($date_to)) {
                $keluar_conditions[] = "sk.tanggal_surat <= ?";
                $keluar_params[] = $date_to;
            }
            
            if (!empty($status)) {
                $keluar_conditions[] = "sk.status = ?";
                $keluar_params[] = $status;
            }
            
            if ($user_id > 0) {
                $keluar_conditions[] = "sk.user_id = ?";
                $keluar_params[] = $user_id;
            }
            
            if ($has_file === 'yes') {
                $keluar_conditions[] = "sk.file_surat IS NOT NULL AND sk.file_surat != ''";
            } elseif ($has_file === 'no') {
                $keluar_conditions[] = "(sk.file_surat IS NULL OR sk.file_surat = '')";
            }
            
            if (!empty($keluar_conditions)) {
                $keluar_query = "
                    SELECT 
                        sk.*, 
                        u.nama_lengkap as input_by,
                        'keluar' as surat_type,
                        sk.tanggal_surat as tanggal_surat_sort,
                        sk.tujuan as pengirim_tujuan
                    FROM surat_keluar sk
                    JOIN users u ON sk.user_id = u.id
                    WHERE " . implode(' AND ', $keluar_conditions);
                
                if ($search_type === 'all' && !empty($base_query)) {
                    $base_query .= " UNION ALL (" . $keluar_query . ")";
                    $params = array_merge($params, $keluar_params);
                } else {
                    if (empty($base_query)) {
                        $base_query = $keluar_query;
                        $params = $keluar_params;
                    } else {
                        $base_query .= " UNION ALL (" . $keluar_query . ")";
                        $params = array_merge($params, $keluar_params);
                    }
                }
            }
        }
        
        if (!empty($base_query)) {
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM (" . $base_query . ") as search_results";
            $total_results = $db->fetch($count_query, $params)['total'];
            
            // Get paginated results
            $final_query = $base_query . " ORDER BY tanggal_surat_sort DESC LIMIT $limit OFFSET $offset";
            $results = $db->fetchAll($final_query, $params);
        }
    }
    
    // Calculate pagination
    $total_pages = ceil($total_results / $limit);
    
    // Get users for filter
    $users = $db->fetchAll("SELECT id, nama_lengkap, username FROM users ORDER BY nama_lengkap");
    
    // Search suggestions based on recent searches or common terms
    $search_suggestions = [];
    if (!empty($search_term)) {
        // Get similar terms from existing surat
        $similar_terms = $db->fetchAll("
            (SELECT DISTINCT pengirim as suggestion, 'pengirim' as type FROM surat_masuk WHERE pengirim LIKE ? LIMIT 3)
            UNION ALL
            (SELECT DISTINCT tujuan as suggestion, 'tujuan' as type FROM surat_keluar WHERE tujuan LIKE ? LIMIT 3)
            ORDER BY suggestion
        ", ['%' . $search_term . '%', '%' . $search_term . '%']);
        
        $search_suggestions = array_slice($similar_terms, 0, 5);
    }
    
} catch (Exception $e) {
    $error_message = "Gagal melakukan pencarian: " . $e->getMessage();
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
                    <i class="fas fa-search"></i>
                    Pencarian Lanjutan
                </h1>
                <p class="page-subtitle">
                    Cari surat dengan berbagai filter dan kriteria
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-info" onclick="showSearchTips()">
                    <i class="fas fa-question-circle"></i>
                    Tips Pencarian
                </button>
            </div>
        </div>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-filter"></i>
                Filter Pencarian
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" id="searchForm">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label">Kata Kunci</label>
                            <div class="input-group">
                                <input type="text" name="q" class="form-control" 
                                       value="<?= htmlspecialchars($search_term) ?>"
                                       placeholder="Nomor surat, pengirim, tujuan, atau perihal..."
                                       autocomplete="off">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Search suggestions -->
                            <?php if (!empty($search_suggestions)): ?>
                                <div class="search-suggestions mt-2">
                                    <small class="text-muted">Saran pencarian:</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php foreach ($search_suggestions as $suggestion): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="applySuggestion('<?= htmlspecialchars($suggestion['suggestion']) ?>')">
                                                <?= htmlspecialchars($suggestion['suggestion']) ?>
                                                <small class="text-muted">(<?= $suggestion['type'] ?>)</small>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Tipe Surat</label>
                            <select name="type" class="form-control">
                                <option value="all" <?= $search_type === 'all' ? 'selected' : '' ?>>Semua Surat</option>
                                <option value="masuk" <?= $search_type === 'masuk' ? 'selected' : '' ?>>Surat Masuk</option>
                                <option value="keluar" <?= $search_type === 'keluar' ? 'selected' : '' ?>>Surat Keluar</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Tanggal Dari</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Tanggal Sampai</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="diproses" <?= $status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="terkirim" <?= $status === 'terkirim' ? 'selected' : '' ?>>Terkirim</option>
                                <option value="arsip" <?= $status === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Input Oleh</label>
                            <select name="user_id" class="form-control">
                                <option value="">Semua User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $user_id === $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">File Lampiran</label>
                            <select name="has_file" class="form-control">
                                <option value="">Semua (dengan atau tanpa file)</option>
                                <option value="yes" <?= $has_file === 'yes' ? 'selected' : '' ?>>Ada File</option>
                                <option value="no" <?= $has_file === 'no' ? 'selected' : '' ?>>Tidak Ada File</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Cari
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                    <i class="fas fa-undo"></i>
                                    Reset
                                </button>
                                <button type="button" class="btn btn-info" onclick="saveSearch()">
                                    <i class="fas fa-bookmark"></i>
                                    Simpan Pencarian
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Search Results -->
    <?php if (!empty($search_term) || !empty($date_from) || !empty($date_to) || !empty($status) || $user_id > 0): ?>
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">
                        Hasil Pencarian
                        <?php if ($total_results > 0): ?>
                            <span class="badge badge-primary"><?= number_format($total_results) ?></span>
                        <?php endif; ?>
                    </h5>
                    <?php if ($total_results > 0): ?>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="exportResults()">
                                <i class="fas fa-file-excel"></i>
                                Export
                            </button>
                            <button class="btn btn-info btn-sm" onclick="printResults()">
                                <i class="fas fa-print"></i>
                                Cetak
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($total_results === 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ditemukan hasil</h5>
                        <p class="text-muted">Coba gunakan kata kunci yang berbeda atau ubah filter pencarian</p>
                        
                        <!-- Search tips -->
                        <div class="mt-4">
                            <div class="alert alert-info text-start">
                                <h6><i class="fas fa-lightbulb"></i> Tips Pencarian:</h6>
                                <ul class="mb-0">
                                    <li>Gunakan kata kunci yang lebih umum</li>
                                    <li>Periksa ejaan kata kunci</li>
                                    <li>Coba gunakan sebagian kata saja</li>
                                    <li>Gunakan filter tanggal yang lebih luas</li>
                                    <li>Hapus beberapa filter untuk memperluas hasil</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Search summary -->
                    <div class="search-summary mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                Menampilkan <?= number_format(($page - 1) * $limit + 1) ?> - 
                                <?= number_format(min($page * $limit, $total_results)) ?> 
                                dari <?= number_format($total_results) ?> hasil
                                <?php if (!empty($search_term)): ?>
                                    untuk "<strong><?= htmlspecialchars($search_term) ?></strong>"
                                <?php endif; ?>
                            </div>
                            <div class="search-time">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    Pencarian selesai dalam <?= number_format(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? 0), 3) ?>s
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tipe</th>
                                    <th>Nomor Surat</th>
                                    <th>Tanggal</th>
                                    <th>Pengirim/Tujuan</th>
                                    <th>Perihal</th>
                                    <th>Status</th>
                                    <th>File</th>
                                    <th>Input By</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?= $result['surat_type'] === 'masuk' ? 'primary' : 'success' ?>">
                                                <i class="fas fa-<?= $result['surat_type'] === 'masuk' ? 'inbox' : 'paper-plane' ?>"></i>
                                                <?= ucfirst($result['surat_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../surat-<?= $result['surat_type'] ?>/detail.php?id=<?= $result['id'] ?>" 
                                               class="text-decoration-none fw-bold">
                                                <?= htmlspecialchars($result['nomor_surat']) ?>
                                            </a>
                                        </td>
                                        <td><?= formatTanggal($result['tanggal_surat_sort']) ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($result['pengirim_tujuan']) ?>">
                                                <?= htmlspecialchars($result['pengirim_tujuan']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($result['perihal']) ?>">
                                                <?= htmlspecialchars($result['perihal']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'secondary';
                                            if (in_array($result['status'], ['selesai', 'terkirim'])) $status_class = 'success';
                                            elseif (in_array($result['status'], ['diproses'])) $status_class = 'info';
                                            elseif (in_array($result['status'], ['pending'])) $status_class = 'warning';
                                            ?>
                                            <span class="badge badge-<?= $status_class ?>">
                                                <?= ucfirst($result['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($result['file_surat'])): ?>
                                                <a href="../../assets/uploads/surat-<?= $result['surat_type'] ?>/<?= $result['file_surat'] ?>" 
                                                   target="_blank" class="text-success" title="Lihat file">
                                                    <i class="fas fa-file"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-minus"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($result['input_by']) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="../surat-<?= $result['surat_type'] ?>/detail.php?id=<?= $result['id'] ?>" 
                                                   class="btn btn-info btn-sm" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../surat-<?= $result['surat_type'] ?>/edit.php?id=<?= $result['id'] ?>" 
                                                   class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
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
                                <small class="text-muted">
                                    Halaman <?= $page ?> dari <?= $total_pages ?>
                                </small>
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
    <?php endif; ?>
</main>

<!-- Search Tips Modal -->
<div class="modal fade" id="searchTipsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-lightbulb"></i>
                    Tips Pencarian Lanjutan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Pencarian Kata Kunci</h6>
                        <ul>
                            <li>Gunakan kata kunci spesifik untuk hasil lebih akurat</li>
                            <li>Pencarian tidak case-sensitive</li>
                            <li>Sistem akan mencari di nomor surat, pengirim/tujuan, dan perihal</li>
                            <li>Gunakan sebagian kata jika tidak yakin ejaan lengkap</li>
                        </ul>
                        
                        <h6>Filter Tanggal</h6>
                        <ul>
                            <li>Gunakan filter tanggal untuk mempersempit hasil</li>
                            <li>Bisa menggunakan satu tanggal saja (dari atau sampai)</li>
                            <li>Format tanggal: DD/MM/YYYY</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Filter Status</h6>
                        <ul>
                            <li><span class="badge badge-warning">Pending</span> - Surat masuk belum diproses</li>
                            <li><span class="badge badge-info">Diproses</span> - Surat masuk sedang ditangani</li>
                            <li><span class="badge badge-success">Selesai</span> - Surat masuk sudah selesai</li>
                            <li><span class="badge badge-secondary">Draft</span> - Surat keluar masih draft</li>
                            <li><span class="badge badge-success">Terkirim</span> - Surat keluar sudah dikirim</li>
                            <li><span class="badge badge-info">Arsip</span> - Surat keluar sudah diarsip</li>
                        </ul>
                        
                        <h6>Tips Tambahan</h6>
                        <ul>
                            <li>Kombinasikan beberapa filter untuk hasil lebih spesifik</li>
                            <li>Gunakan "Reset" untuk membersihkan semua filter</li>
                            <li>Simpan pencarian yang sering digunakan</li>
                            <li>Export hasil pencarian ke Excel jika diperlukan</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php
$inline_scripts = '
function clearSearch() {
    document.querySelector("[name=\'q\']").value = "";
    document.querySelector("[name=\'q\']").focus();
}

function resetFilters() {
    if (confirm("Reset semua filter pencarian?")) {
        window.location.href = "index.php";
    }
}

function applySuggestion(suggestion) {
    document.querySelector("[name=\'q\']").value = suggestion;
    document.getElementById("searchForm").submit();
}

function showSearchTips() {
    new bootstrap.Modal(document.getElementById("searchTipsModal")).show();
}

function saveSearch() {
    const searchParams = new URLSearchParams(window.location.search);
    const searchData = {
        q: searchParams.get("q") || "",
        type: searchParams.get("type") || "all",
        date_from: searchParams.get("date_from") || "",
        date_to: searchParams.get("date_to") || "",
        status: searchParams.get("status") || "",
        user_id: searchParams.get("user_id") || "",
        has_file: searchParams.get("has_file") || "",
        timestamp: new Date().toISOString(),
        name: prompt("Nama untuk pencarian ini:")
    };
    
    if (searchData.name) {
        let savedSearches = JSON.parse(localStorage.getItem("savedSearches") || "[]");
        savedSearches.unshift(searchData);
        
        // Keep only last 10 searches
        if (savedSearches.length > 10) {
            savedSearches = savedSearches.slice(0, 10);
        }
        
        localStorage.setItem("savedSearches", JSON.stringify(savedSearches));
        showSuccessToast("Pencarian berhasil disimpan");
    }
}

function exportResults() {
    const params = new URLSearchParams(window.location.search);
    params.set("export", "excel");
    window.open("export-search.php?" + params.toString(), "_blank");
}

function printResults() {
    window.print();
}

// Load saved searches
document.addEventListener("DOMContentLoaded", function() {
    const savedSearches = JSON.parse(localStorage.getItem("savedSearches") || "[]");
    if (savedSearches.length > 0) {
        const searchForm = document.getElementById("searchForm");
        const savedSearchesDiv = document.createElement("div");
        savedSearchesDiv.className = "mt-3";
        savedSearchesDiv.innerHTML = `
            <label class="form-label">Pencarian Tersimpan:</label>
            <div class="d-flex flex-wrap gap-1">
                ${savedSearches.map(search => `
                    <button type="button" class="btn btn-sm btn-outline-info" 
                            onclick="loadSavedSearch(${JSON.stringify(search).replace(/"/g, "&quot;")})">
                        ${search.name}
                        <small class="text-muted">(${new Date(search.timestamp).toLocaleDateString()})</small>
                    </button>
                `).join("")}
            </div>
        `;
        searchForm.appendChild(savedSearchesDiv);
    }
});

function loadSavedSearch(searchData) {
    const params = new URLSearchParams();
    Object.keys(searchData).forEach(key => {
        if (searchData[key] && key !== "timestamp" && key !== "name") {
            params.set(key, searchData[key]);
        }
    });
    window.location.href = "index.php?" + params.toString();
}

// Auto-complete functionality
const searchInput = document.querySelector("[name=\'q\']");
if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener("input", function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                // This would fetch suggestions from server in a real implementation
                console.log("Fetching suggestions for:", query);
            }, 300);
        }
    });
}

// Highlight search terms in results
function highlightSearchTerms() {
    const searchTerm = "' . addslashes($search_term) . '";
    if (searchTerm) {
        const regex = new RegExp("(" + searchTerm.replace(/[.*+?^${}()|[\\]\\\\]/g, "\\\\$&") + ")", "gi");
        document.querySelectorAll(".table tbody td").forEach(cell => {
            if (cell.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
                cell.innerHTML = cell.innerHTML.replace(regex, "<mark>$1</mark>");
            }
        });
    }
}

// Highlight terms after page load
document.addEventListener("DOMContentLoaded", highlightSearchTerms);

// Add search styles
const searchStyles = `
<style>
.search-suggestions {
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 0.75rem;
    background: var(--bg-secondary);
}

.search-summary {
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: 6px;
    margin-bottom: 1rem;
}

.search-time {
    font-size: 0.85rem;
}

mark {
    background: yellow;
    padding: 0.125rem 0.25rem;
    border-radius: 3px;
}

.btn-group .btn {
    margin-right: 0.125rem;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .search-summary .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
}

@media print {
    .card-header,
    .page-header,
    .btn,
    .pagination {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    body {
        background: white !important;
    }
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", searchStyles);
';

include '../../includes/footer.php';
?>
