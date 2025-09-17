<?php
// pages/users/index.php
require_once '../../config/config.php';

// Require login and admin role
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    setFlashMessage('danger', 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
    redirect('../../index.php');
}

// Set page variables
$page_title = 'Manajemen User';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            handleAddUser();
            break;
        case 'edit_user':
            handleEditUser();
            break;
        case 'delete_user':
            handleDeleteUser();
            break;
        case 'reset_password':
            handleResetPassword();
            break;
        case 'toggle_status':
            handleToggleStatus();
            break;
    }
}

function handleAddUser() {
    global $db;
    
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $role = sanitize($_POST['role'] ?? 'user');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    } elseif ($db->fetch("SELECT id FROM users WHERE username = ?", [$username])) {
        $errors[] = 'Username sudah digunakan';
    }
    
    if (empty($email)) {
        $errors[] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    } elseif ($db->fetch("SELECT id FROM users WHERE email = ?", [$email])) {
        $errors[] = 'Email sudah digunakan';
    }
    
    if (empty($nama_lengkap)) {
        $errors[] = 'Nama lengkap harus diisi';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = 'Role tidak valid';
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, nama_lengkap, password, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())";
            $db->query($sql, [$username, $email, $nama_lengkap, $hashed_password, $role]);
            
            // Create notification
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $db->query($notification_sql, [
                $_SESSION['user_id'],
                'User Baru Ditambahkan',
                "User {$nama_lengkap} ({$username}) berhasil ditambahkan",
                'success'
            ]);
            
            setFlashMessage('success', 'User berhasil ditambahkan');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal menambahkan user: ' . $e->getMessage());
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
    
    redirect('index.php');
}

function handleEditUser() {
    global $db;
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $role = sanitize($_POST['role'] ?? 'user');
    
    $errors = [];
    
    // Get existing user
    $existing_user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);
    if (!$existing_user) {
        setFlashMessage('danger', 'User tidak ditemukan');
        redirect('index.php');
        return;
    }
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    } elseif ($db->fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $user_id])) {
        $errors[] = 'Username sudah digunakan';
    }
    
    if (empty($email)) {
        $errors[] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    } elseif ($db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id])) {
        $errors[] = 'Email sudah digunakan';
    }
    
    if (empty($nama_lengkap)) {
        $errors[] = 'Nama lengkap harus diisi';
    }
    
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = 'Role tidak valid';
    }
    
    // Prevent admin from changing their own role
    if ($user_id == $_SESSION['user_id'] && $role !== $_SESSION['role']) {
        $errors[] = 'Anda tidak dapat mengubah role Anda sendiri';
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET username = ?, email = ?, nama_lengkap = ?, role = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$username, $email, $nama_lengkap, $role, $user_id]);
            
            setFlashMessage('success', 'User berhasil diperbarui');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Gagal memperbarui user: ' . $e->getMessage());
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
    
    redirect('index.php');
}

function handleDeleteUser() {
    global $db;
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    // Get user info
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);
    if (!$user) {
        setFlashMessage('danger', 'User tidak ditemukan');
        redirect('index.php');
        return;
    }
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        setFlashMessage('danger', 'Anda tidak dapat menghapus akun Anda sendiri');
        redirect('index.php');
        return;
    }
    
    // Check if user has data
    $has_surat_masuk = $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE user_id = ?", [$user_id])['total'] > 0;
    $has_surat_keluar = $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE user_id = ?", [$user_id])['total'] > 0;
    
    if ($has_surat_masuk || $has_surat_keluar) {
        setFlashMessage('warning', 'User tidak dapat dihapus karena masih memiliki data surat terkait');
        redirect('index.php');
        return;
    }
    
    try {
        $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
        
        // Create notification
        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
        $db->query($notification_sql, [
            $_SESSION['user_id'],
            'User Dihapus',
            "User {$user['nama_lengkap']} ({$user['username']}) berhasil dihapus",
            'info'
        ]);
        
        setFlashMessage('success', 'User berhasil dihapus');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal menghapus user: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

function handleResetPassword() {
    global $db;
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_password = 'password'; // Default password
    
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);
    if (!$user) {
        setFlashMessage('danger', 'User tidak ditemukan');
        redirect('index.php');
        return;
    }
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", [$hashed_password, $user_id]);
        
        setFlashMessage('success', "Password user {$user['nama_lengkap']} berhasil direset ke 'password'");
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal mereset password: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

function handleToggleStatus() {
    global $db;
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);
    if (!$user) {
        setFlashMessage('danger', 'User tidak ditemukan');
        redirect('index.php');
        return;
    }
    
    // Prevent admin from deactivating themselves
    if ($user_id == $_SESSION['user_id']) {
        setFlashMessage('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri');
        redirect('index.php');
        return;
    }
    
    try {
        $new_status = $user['is_active'] ? 0 : 1;
        $db->query("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?", [$new_status, $user_id]);
        
        $status_text = $new_status ? 'diaktifkan' : 'dinonaktifkan';
        setFlashMessage('success', "User {$user['nama_lengkap']} berhasil {$status_text}");
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal mengubah status user: ' . $e->getMessage());
    }
    
    redirect('index.php');
}

// Get filters
$search = sanitize($_GET['search'] ?? '');
$role_filter = sanitize($_GET['role'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

// Build query
$where_conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR nama_lengkap LIKE ?)";
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = (int)$status_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$total_records = $db->fetch("SELECT COUNT(*) as total FROM users $where_clause", $params)['total'];

// Pagination
$limit = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_records / $limit);

// Get users data
$users = $db->fetchAll("
    SELECT u.*, 
           (SELECT COUNT(*) FROM surat_masuk WHERE user_id = u.id) as total_surat_masuk,
           (SELECT COUNT(*) FROM surat_keluar WHERE user_id = u.id) as total_surat_keluar
    FROM users u 
    $where_clause
    ORDER BY u.created_at DESC 
    LIMIT $limit OFFSET $offset
", $params);

// Get statistics
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as total FROM users")['total'],
    'active_users' => $db->fetch("SELECT COUNT(*) as total FROM users WHERE is_active = 1")['total'],
    'admin_users' => $db->fetch("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")['total'],
    'regular_users' => $db->fetch("SELECT COUNT(*) as total FROM users WHERE role = 'user'")['total']
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
                    <i class="fas fa-users"></i>
                    Manajemen User
                </h1>
                <p class="page-subtitle">
                    Kelola user dan hak akses sistem
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i>
                    Tambah User
                </button>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid mb-4">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['total_users']) ?></h3>
                <p>Total User</p>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['active_users']) ?></h3>
                <p>User Aktif</p>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['admin_users']) ?></h3>
                <p>Admin</p>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['regular_users']) ?></h3>
                <p>User Biasa</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Cari User</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Username, email, atau nama lengkap">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control">
                        <option value="">Semua Role</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>User</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Cari
                    </button>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-times"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                Daftar User
                <?php if ($total_records > 0): ?>
                    <span class="badge badge-primary"><?= number_format($total_records) ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada user ditemukan</h5>
                    <p class="text-muted">Coba ubah filter pencarian atau tambah user baru</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User Info</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aktivitas</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php if ($user['foto_profile']): ?>
                                                    <img src="../../assets/uploads/profiles/<?= $user['foto_profile'] ?>" alt="Avatar">
                                                <?php else: ?>
                                                    <div class="avatar-initials">
                                                        <?= strtoupper(substr($user['nama_lengkap'], 0, 2)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-details">
                                                <div class="user-name">
                                                    <?= htmlspecialchars($user['nama_lengkap']) ?>
                                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge badge-info">You</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="user-username">@<?= htmlspecialchars($user['username']) ?></div>
                                                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                            <i class="fas fa-<?= $user['role'] === 'admin' ? 'user-shield' : 'user' ?>"></i>
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                            <i class="fas fa-<?= $user['is_active'] ? 'check-circle' : 'times-circle' ?>"></i>
                                            <?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="activity-stats">
                                            <small class="d-block">
                                                <i class="fas fa-inbox text-primary"></i>
                                                <?= $user['total_surat_masuk'] ?> surat masuk
                                            </small>
                                            <small class="d-block">
                                                <i class="fas fa-paper-plane text-success"></i>
                                                <?= $user['total_surat_keluar'] ?> surat keluar
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <small class="d-block"><?= formatTanggal($user['created_at']) ?></small>
                                            <small class="text-muted"><?= timeAgo($user['created_at']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)"
                                                    title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-info btn-sm" 
                                                        onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>')"
                                                        title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-<?= $user['is_active'] ? 'secondary' : 'success' ?> btn-sm" 
                                                        onclick="toggleStatus(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>', <?= $user['is_active'] ?>)"
                                                        title="<?= $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                    <i class="fas fa-<?= $user['is_active'] ? 'user-times' : 'user-check' ?>"></i>
                                                </button>
                                                
                                                <?php if ($user['total_surat_masuk'] == 0 && $user['total_surat_keluar'] == 0): ?>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>')"
                                                            title="Hapus User">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
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
                            Menampilkan <?= number_format(($page - 1) * $limit + 1) ?> - 
                            <?= number_format(min($page * $limit, $total_records)) ?> 
                            dari <?= number_format($total_records) ?> user
                        </div>
                        <nav>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($_GET) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($_GET) ?>">
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Tambah User Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Role *</label>
                                <select name="role" class="form-control" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Konfirmasi Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editUserForm">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" id="edit_username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Role *</label>
                                <select name="role" id="edit_role" class="form-control" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Forms for Actions -->
<form id="deleteUserForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="delete_user_id">
</form>

<form id="resetPasswordForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="reset_password">
    <input type="hidden" name="user_id" id="reset_user_id">
</form>

<form id="toggleStatusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="user_id" id="toggle_user_id">
</form>

<?php
$inline_scripts = '
function editUser(user) {
    document.getElementById("edit_user_id").value = user.id;
    document.getElementById("edit_username").value = user.username;
    document.getElementById("edit_email").value = user.email;
    document.getElementById("edit_nama_lengkap").value = user.nama_lengkap;
    document.getElementById("edit_role").value = user.role;
    
    new bootstrap.Modal(document.getElementById("editUserModal")).show();
}

function deleteUser(userId, userName) {
    if (confirm("Apakah Anda yakin ingin menghapus user: " + userName + "?\\n\\nPerhatian: Aksi ini tidak dapat dibatalkan!")) {
        document.getElementById("delete_user_id").value = userId;
        document.getElementById("deleteUserForm").submit();
    }
}

function resetPassword(userId, userName) {
    if (confirm("Apakah Anda yakin ingin mereset password user: " + userName + "?\\n\\nPassword akan direset menjadi \'password\'")) {
        document.getElementById("reset_user_id").value = userId;
        document.getElementById("resetPasswordForm").submit();
    }
}

function toggleStatus(userId, userName, currentStatus) {
    const action = currentStatus ? "menonaktifkan" : "mengaktifkan";
    if (confirm("Apakah Anda yakin ingin " + action + " user: " + userName + "?")) {
        document.getElementById("toggle_user_id").value = userId;
        document.getElementById("toggleStatusForm").submit();
    }
}

// Add user management styles
const userManagementStyles = `
<style>
.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initials {
    width: 100%;
    height: 100%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-username {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 0.125rem;
}

.user-email {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.activity-stats small {
    margin-bottom: 0.25rem;
}

.date-info small {
    margin-bottom: 0.125rem;
}

.btn-group .btn {
    margin-right: 0.25rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

@media (max-width: 768px) {
    .user-info {
        flex-direction: column;
        text-align: center;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-right: 0;
        margin-bottom: 0.25rem;
    }
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", userManagementStyles);
';

include '../../includes/footer.php';
?>
