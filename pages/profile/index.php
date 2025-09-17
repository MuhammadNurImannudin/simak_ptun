<?php
// pages/profile/index.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Profile';

// Get user data
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

$errors = [];
$success = false;
$active_tab = $_GET['tab'] ?? 'profile';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        if ($_POST['action'] === 'update_profile') {
            // Update profile
            $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            
            // Validation
            if (empty($nama_lengkap)) {
                $errors['nama_lengkap'] = 'Nama lengkap harus diisi';
            }
            
            if (empty($email)) {
                $errors['email'] = 'Email harus diisi';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format email tidak valid';
            } else {
                // Check if email already exists (except current user)
                $existing = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $_SESSION['user_id']]);
                if ($existing) {
                    $errors['email'] = 'Email sudah digunakan';
                }
            }
            
            // Handle photo upload
            $foto_path = $user['foto'];
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                try {
                    $new_foto_path = uploadFile($_FILES['foto'], 'profiles');
                    
                    // Delete old photo if exists
                    if ($user['foto'] && file_exists(UPLOAD_PATH . $user['foto'])) {
                        unlink(UPLOAD_PATH . $user['foto']);
                    }
                    
                    $foto_path = $new_foto_path;
                } catch (Exception $e) {
                    $errors['foto'] = $e->getMessage();
                }
            }
            
            if (empty($errors)) {
                try {
                    $sql = "UPDATE users SET nama_lengkap = ?, email = ?, foto = ?, updated_at = NOW() WHERE id = ?";
                    $db->query($sql, [$nama_lengkap, $email, $foto_path, $_SESSION['user_id']]);
                    
                    // Update session
                    $_SESSION['nama_lengkap'] = $nama_lengkap;
                    $_SESSION['email'] = $email;
                    
                    setFlashMessage('success', 'Profile berhasil diperbarui');
                    redirect('index.php');
                    
                } catch (Exception $e) {
                    $errors['database'] = 'Gagal memperbarui profile: ' . $e->getMessage();
                }
            }
        }
        
        elseif ($_POST['action'] === 'change_password') {
            // Change password
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($current_password)) {
                $errors['current_password'] = 'Password lama harus diisi';
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors['current_password'] = 'Password lama tidak sesuai';
            }
            
            if (empty($new_password)) {
                $errors['new_password'] = 'Password baru harus diisi';
            } elseif (strlen($new_password) < 6) {
                $errors['new_password'] = 'Password baru minimal 6 karakter';
            }
            
            if (empty($confirm_password)) {
                $errors['confirm_password'] = 'Konfirmasi password harus diisi';
            } elseif ($new_password !== $confirm_password) {
                $errors['confirm_password'] = 'Konfirmasi password tidak sesuai';
            }
            
            if (empty($errors)) {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                    $db->query($sql, [$hashed_password, $_SESSION['user_id']]);
                    
                    setFlashMessage('success', 'Password berhasil diubah');
                    redirect('index.php?tab=security');
                    
                } catch (Exception $e) {
                    $errors['database'] = 'Gagal mengubah password: ' . $e->getMessage();
                }
            }
            
            $active_tab = 'security';
        }
    }
}

// Get user statistics
$user_stats = [
    'surat_masuk_input' => $db->fetch("SELECT COUNT(*) as total FROM surat_masuk WHERE user_id = ?", [$_SESSION['user_id']])['total'],
    'surat_keluar_input' => $db->fetch("SELECT COUNT(*) as total FROM surat_keluar WHERE user_id = ?", [$_SESSION['user_id']])['total'],
    'last_login' => $user['updated_at']
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
                    <i class="fas fa-user-circle"></i>
                    Profile
                </h1>
                <p class="page-subtitle">
                    Kelola informasi akun dan pengaturan Anda
                </p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Profile Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <?php if ($user['foto']): ?>
                            <img src="<?= UPLOAD_URL . $user['foto'] ?>" alt="Avatar" 
                                 class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-placeholder rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                 style="width: 120px; height: 120px; background: var(--primary-color); color: white; font-size: 3rem; font-weight: 600;">
                                <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4><?= htmlspecialchars($user['nama_lengkap']) ?></h4>
                    <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?> badge-lg">
                        <?= ucfirst($user['role']) ?>
                    </span>
                    
                    <hr>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-primary"><?= $user_stats['surat_masuk_input'] ?></h5>
                            <small class="text-muted">Surat Masuk</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-success"><?= $user_stats['surat_keluar_input'] ?></h5>
                            <small class="text-muted">Surat Keluar</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            Bergabung sejak <?= formatTanggal($user['created_at']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="col-lg-8">
            <!-- Tabs Navigation -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?= $active_tab === 'profile' ? 'active' : '' ?>" 
                               href="?tab=profile">
                                <i class="fas fa-user"></i>
                                Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_tab === 'security' ? 'active' : '' ?>" 
                               href="?tab=security">
                                <i class="fas fa-shield-alt"></i>
                                Keamanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_tab === 'activity' ? 'active' : '' ?>" 
                               href="?tab=activity">
                                <i class="fas fa-history"></i>
                                Aktivitas
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= $errors['database'] ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Profile Tab -->
                    <?php if ($active_tab === 'profile'): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" class="form-control" 
                                       value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                <small class="form-text text-muted">Username tidak dapat diubah</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" 
                                       class="form-control <?= isset($errors['nama_lengkap']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? $user['nama_lengkap']) ?>"
                                       required>
                                <?php if (isset($errors['nama_lengkap'])): ?>
                                    <div class="invalid-feedback"><?= $errors['nama_lengkap'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                                       required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= $errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="foto" class="form-label">Foto Profile</label>
                                <input type="file" id="foto" name="foto" 
                                       class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                                       accept=".jpg,.jpeg,.png"
                                       data-preview="#fotoPreview">
                                <small class="form-text text-muted">
                                    Format: JPG, PNG. Maksimal 2MB. Ukuran rekomendasi: 300x300px
                                </small>
                                <?php if (isset($errors['foto'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['foto'] ?></div>
                                <?php endif; ?>
                                <div id="fotoPreview" class="mt-2"></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" 
                                       value="<?= ucfirst($user['role']) ?>" readonly>
                                <small class="form-text text-muted">Role ditentukan oleh administrator</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <!-- Security Tab -->
                    <?php if ($active_tab === 'security'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password" class="form-label">Password Lama *</label>
                                <input type="password" id="current_password" name="current_password" 
                                       class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                                       required>
                                <?php if (isset($errors['current_password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['current_password'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password" class="form-label">Password Baru *</label>
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                                       minlength="6" required>
                                <small class="form-text text-muted">Minimal 6 karakter</small>
                                <?php if (isset($errors['new_password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['new_password'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru *</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                       required>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i>
                                    Ubah Password
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <!-- Security Info -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-shield-alt"></i> Tips Keamanan</h6>
                            <ul class="mb-0">
                                <li>Gunakan password yang kuat dan unik</li>
                                <li>Jangan bagikan informasi login Anda</li>
                                <li>Selalu logout setelah selesai menggunakan sistem</li>
                                <li>Laporkan aktivitas mencurigakan kepada administrator</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Activity Tab -->
                    <?php if ($active_tab === 'activity'): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Aktivitas Surat Masuk</h6>
                                <div class="list-group list-group-flush">
                                    <?php
                                    $recent_surat_masuk = $db->fetchAll(
                                        "SELECT * FROM surat_masuk WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
                                        [$_SESSION['user_id']]
                                    );
                                    ?>
                                    <?php if (empty($recent_surat_masuk)): ?>
                                        <div class="list-group-item">
                                            <small class="text-muted">Belum ada aktivitas</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_surat_masuk as $surat): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($surat['nomor_surat']) ?></h6>
                                                        <small><?= htmlspecialchars($surat['pengirim']) ?></small>
                                                    </div>
                                                    <small class="text-muted"><?= formatTanggal($surat['created_at']) ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Aktivitas Surat Keluar</h6>
                                <div class="list-group list-group-flush">
                                    <?php
                                    $recent_surat_keluar = $db->fetchAll(
                                        "SELECT * FROM surat_keluar WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
                                        [$_SESSION['user_id']]
                                    );
                                    ?>
                                    <?php if (empty($recent_surat_keluar)): ?>
                                        <div class="list-group-item">
                                            <small class="text-muted">Belum ada aktivitas</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_surat_keluar as $surat): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($surat['nomor_surat']) ?></h6>
                                                        <small><?= htmlspecialchars($surat['tujuan']) ?></small>
                                                    </div>
                                                    <small class="text-muted"><?= formatTanggal($surat['created_at']) ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary"><?= $user_stats['surat_masuk_input'] ?></h4>
                                <small class="text-muted">Total Surat Masuk Diinput</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success"><?= $user_stats['surat_keluar_input'] ?></h4>
                                <small class="text-muted">Total Surat Keluar Diinput</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-info"><?= formatTanggal($user['created_at'], 'd-m-Y') ?></h4>
                                <small class="text-muted">Tanggal Bergabung</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning"><?= formatTanggal($user_stats['last_login'], 'd-m-Y') ?></h4>
                                <small class="text-muted">Login Terakhir</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$inline_scripts = '
// Tab navigation
document.querySelectorAll(".nav-link").forEach(link => {
    link.addEventListener("click", function(e) {
        // Remove active class from all tabs
        document.querySelectorAll(".nav-link").forEach(l => l.classList.remove("active"));
        // Add active class to clicked tab
        this.classList.add("active");
    });
});

// Password confirmation validation
document.getElementById("confirm_password")?.addEventListener("input", function() {
    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity("Password tidak sesuai");
        this.classList.add("is-invalid");
    } else {
        this.setCustomValidity("");
        this.classList.remove("is-invalid");
    }
});

// Password strength indicator
document.getElementById("new_password")?.addEventListener("input", function() {
    const password = this.value;
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    // Remove existing strength indicator
    const existingIndicator = this.parentElement.querySelector(".password-strength");
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    if (password.length > 0) {
        const indicator = document.createElement("div");
        indicator.className = "password-strength mt-1";
        
        let strengthText = "";
        let strengthClass = "";
        
        switch (strength) {
            case 0:
            case 1:
                strengthText = "Lemah";
                strengthClass = "text-danger";
                break;
            case 2:
            case 3:
                strengthText = "Sedang";
                strengthClass = "text-warning";
                break;
            case 4:
            case 5:
                strengthText = "Kuat";
                strengthClass = "text-success";
                break;
        }
        
        indicator.innerHTML = `<small class="${strengthClass}">Kekuatan password: ${strengthText}</small>`;
        this.parentElement.appendChild(indicator);
    }
});

// File preview for photo
document.getElementById("foto")?.addEventListener("change", function() {
    const file = this.files[0];
    const preview = document.getElementById("fotoPreview");
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="mt-2">
                    <img src="${e.target.result}" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                    <p class="small text-muted mt-1">Preview foto baru</p>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = "";
    }
});

// Add styles for profile
const profileStyles = `
<style>
.nav-tabs .nav-link {
    border: none;
    color: var(--text-secondary);
    transition: all 0.2s ease;
}

.nav-tabs .nav-link.active {
    background-color: var(--primary-color);
    color: white;
    border-radius: 6px 6px 0 0;
}

.nav-tabs .nav-link:hover {
    background-color: var(--bg-color);
    border: none;
}

.badge-lg {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.list-group-flush .list-group-item {
    border-left: none;
    border-right: none;
    padding: 0.75rem 0;
}

.list-group-flush .list-group-item:first-child {
    border-top: none;
}

.list-group-flush .list-group-item:last-child {
    border-bottom: none;
}
</style>
`;
document.head.insertAdjacentHTML("beforeend", profileStyles);
';

include '../../includes/footer.php';
?>
