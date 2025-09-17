<?php
// pages/surat-masuk/edit.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Edit Surat Masuk';

// Get ID from URL
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlashMessage('danger', 'ID surat tidak valid');
    redirect('index.php');
}

// Get existing data
$surat = $db->fetch("SELECT * FROM surat_masuk WHERE id = ?", [$id]);
if (!$surat) {
    setFlashMessage('danger', 'Surat tidak ditemukan');
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $nomor_surat = sanitize($_POST['nomor_surat'] ?? '');
    $tanggal_surat = sanitize($_POST['tanggal_surat'] ?? '');
    $tanggal_diterima = sanitize($_POST['tanggal_diterima'] ?? '');
    $pengirim = sanitize($_POST['pengirim'] ?? '');
    $perihal = sanitize($_POST['perihal'] ?? '');
    $lampiran = sanitize($_POST['lampiran'] ?? '');
    $disposisi = sanitize($_POST['disposisi'] ?? '');
    $status = sanitize($_POST['status'] ?? 'pending');
    
    // Validation
    if (empty($nomor_surat)) {
        $errors['nomor_surat'] = 'Nomor surat harus diisi';
    }
    
    if (empty($tanggal_surat)) {
        $errors['tanggal_surat'] = 'Tanggal surat harus diisi';
    }
    
    if (empty($tanggal_diterima)) {
        $errors['tanggal_diterima'] = 'Tanggal diterima harus diisi';
    }
    
    if (empty($pengirim)) {
        $errors['pengirim'] = 'Pengirim harus diisi';
    }
    
    if (empty($perihal)) {
        $errors['perihal'] = 'Perihal harus diisi';
    }
    
    // Check if nomor surat already exists (except current record)
    if (!isset($errors['nomor_surat'])) {
        $existing = $db->fetch("SELECT id FROM surat_masuk WHERE nomor_surat = ? AND id != ?", [$nomor_surat, $id]);
        if ($existing) {
            $errors['nomor_surat'] = 'Nomor surat sudah ada';
        }
    }
    
    // Handle file upload
    $file_path = $surat['file_surat']; // Keep existing file by default
    
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === UPLOAD_ERR_OK) {
        try {
            $new_file_path = uploadFile($_FILES['file_surat'], 'surat-masuk');
            
            // Delete old file if exists
            if ($surat['file_surat'] && file_exists(UPLOAD_PATH . $surat['file_surat'])) {
                unlink(UPLOAD_PATH . $surat['file_surat']);
            }
            
            $file_path = $new_file_path;
        } catch (Exception $e) {
            $errors['file_surat'] = $e->getMessage();
        }
    }
    
    // Handle delete file
    if (isset($_POST['delete_file']) && $_POST['delete_file'] === '1') {
        if ($surat['file_surat'] && file_exists(UPLOAD_PATH . $surat['file_surat'])) {
            unlink(UPLOAD_PATH . $surat['file_surat']);
        }
        $file_path = null;
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $sql = "UPDATE surat_masuk SET 
                        nomor_surat = ?, 
                        tanggal_surat = ?, 
                        tanggal_diterima = ?, 
                        pengirim = ?, 
                        perihal = ?, 
                        lampiran = ?, 
                        file_surat = ?, 
                        disposisi = ?, 
                        status = ?, 
                        updated_at = NOW() 
                    WHERE id = ?";
            
            $params = [
                $nomor_surat,
                $tanggal_surat,
                $tanggal_diterima,
                $pengirim,
                $perihal,
                $lampiran,
                $file_path,
                $disposisi,
                $status,
                $id
            ];
            
            $db->query($sql, $params);
            
            // Create notification
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $db->query($notification_sql, [
                $_SESSION['user_id'],
                'Surat Masuk Diperbarui',
                "Surat masuk {$nomor_surat} berhasil diperbarui",
                'info'
            ]);
            
            setFlashMessage('success', 'Surat masuk berhasil diperbarui');
            redirect('detail.php?id=' . $id);
            
        } catch (Exception $e) {
            $errors['database'] = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
    
    // Update surat array with new values for form display
    $surat = array_merge($surat, [
        'nomor_surat' => $nomor_surat,
        'tanggal_surat' => $tanggal_surat,
        'tanggal_diterima' => $tanggal_diterima,
        'pengirim' => $pengirim,
        'perihal' => $perihal,
        'lampiran' => $lampiran,
        'disposisi' => $disposisi,
        'status' => $status
    ]);
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
                    <i class="fas fa-edit"></i>
                    Edit Surat Masuk
                </h1>
                <p class="page-subtitle">
                    Edit data surat masuk: <?= htmlspecialchars($surat['nomor_surat']) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="detail.php?id=<?= $id ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i>
                    Lihat Detail
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Kembali
                </a>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Form Edit Surat Masuk</h5>
        </div>
        <div class="card-body">
            <?php if (isset($errors['database'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $errors['database'] ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nomor_surat" class="form-label">Nomor Surat *</label>
                            <input type="text" 
                                   id="nomor_surat" 
                                   name="nomor_surat" 
                                   class="form-control <?= isset($errors['nomor_surat']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($surat['nomor_surat']) ?>"
                                   placeholder="Contoh: 001/SM/2024"
                                   required>
                            <?php if (isset($errors['nomor_surat'])): ?>
                                <div class="invalid-feedback"><?= $errors['nomor_surat'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tanggal_surat" class="form-label">Tanggal Surat *</label>
                            <input type="date" 
                                   id="tanggal_surat" 
                                   name="tanggal_surat" 
                                   class="form-control <?= isset($errors['tanggal_surat']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($surat['tanggal_surat']) ?>"
                                   required>
                            <?php if (isset($errors['tanggal_surat'])): ?>
                                <div class="invalid-feedback"><?= $errors['tanggal_surat'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tanggal_diterima" class="form-label">Tanggal Diterima *</label>
                            <input type="date" 
                                   id="tanggal_diterima" 
                                   name="tanggal_diterima" 
                                   class="form-control <?= isset($errors['tanggal_diterima']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($surat['tanggal_diterima']) ?>"
                                   required>
                            <?php if (isset($errors['tanggal_diterima'])): ?>
                                <div class="invalid-feedback"><?= $errors['tanggal_diterima'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pengirim" class="form-label">Pengirim *</label>
                            <input type="text" 
                                   id="pengirim" 
                                   name="pengirim" 
                                   class="form-control <?= isset($errors['pengirim']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($surat['pengirim']) ?>"
                                   placeholder="Nama instansi/lembaga pengirim"
                                   required>
                            <?php if (isset($errors['pengirim'])): ?>
                                <div class="invalid-feedback"><?= $errors['pengirim'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="lampiran" class="form-label">Lampiran</label>
                            <input type="text" 
                                   id="lampiran" 
                                   name="lampiran" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($surat['lampiran']) ?>"
                                   placeholder="Contoh: 1 berkas">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="pending" <?= $surat['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="diproses" <?= $surat['status'] === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="selesai" <?= $surat['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="perihal" class="form-label">Perihal *</label>
                    <textarea id="perihal" 
                              name="perihal" 
                              class="form-control <?= isset($errors['perihal']) ? 'is-invalid' : '' ?>" 
                              rows="3"
                              placeholder="Isi perihal/subjek surat"
                              required><?= htmlspecialchars($surat['perihal']) ?></textarea>
                    <?php if (isset($errors['perihal'])): ?>
                        <div class="invalid-feedback"><?= $errors['perihal'] ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="disposisi" class="form-label">Disposisi/Catatan</label>
                    <textarea id="disposisi" 
                              name="disposisi" 
                              class="form-control" 
                              rows="3"
                              placeholder="Disposisi atau catatan tambahan"><?= htmlspecialchars($surat['disposisi']) ?></textarea>
                </div>
                
                <!-- Current File Display -->
                <?php if ($surat['file_surat']): ?>
                    <div class="form-group">
                        <label class="form-label">File Saat Ini</label>
                        <div class="d-flex align-items-center gap-3 p-3 border rounded">
                            <div class="flex-grow-1">
                                <i class="fas fa-file-pdf text-danger"></i>
                                <span class="ms-2"><?= basename($surat['file_surat']) ?></span>
                                <small class="d-block text-muted">
                                    Diupload: <?= formatTanggal($surat['updated_at']) ?>
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= UPLOAD_URL . $surat['file_surat'] ?>" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                    Lihat
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteCurrentFile()">
                                    <i class="fas fa-trash"></i>
                                    Hapus
                                </button>
                            </div>
                        </div>
                        <input type="hidden" id="delete_file" name="delete_file" value="0">
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="file_surat" class="form-label">
                        <?= $surat['file_surat'] ? 'Ganti File Surat' : 'File Surat' ?>
                    </label>
                    <input type="file" 
                           id="file_surat" 
                           name="file_surat" 
                           class="form-control <?= isset($errors['file_surat']) ? 'is-invalid' : '' ?>"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           data-preview="#filePreview">
                    <small class="form-text text-muted">
                        Format yang didukung: PDF, DOC, DOCX, JPG, PNG. Maksimal 5MB.
                        <?= $surat['file_surat'] ? 'Kosongkan jika tidak ingin mengganti file.' : '' ?>
                    </small>
                    <?php if (isset($errors['file_surat'])): ?>
                        <div class="invalid-feedback d-block"><?= $errors['file_surat'] ?></div>
                    <?php endif; ?>
                    <div id="filePreview" class="mt-2"></div>
                </div>
                
                <!-- Audit Information -->
                <div class="form-group">
                    <label class="form-label">Informasi Audit</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <small>
                                    <strong>Dibuat:</strong> <?= formatTanggal($surat['created_at']) ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <small>
                                    <strong>Diperbarui:</strong> <?= formatTanggal($surat['updated_at']) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Perubahan
                        </button>
                        <button type="button" class="btn btn-outline" onclick="resetToOriginal()">
                            <i class="fas fa-undo"></i>
                            Reset ke Asli
                        </button>
                        <a href="detail.php?id=<?= $id ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i>
                            Lihat Detail
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Batal
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
$original_data = json_encode([
    'nomor_surat' => $surat['nomor_surat'],
    'tanggal_surat' => $surat['tanggal_surat'],
    'tanggal_diterima' => $surat['tanggal_diterima'],
    'pengirim' => $surat['pengirim'],
    'perihal' => $surat['perihal'],
    'lampiran' => $surat['lampiran'],
    'disposisi' => $surat['disposisi'],
    'status' => $surat['status']
]);

$inline_scripts = '
const originalData = ' . $original_data . ';

function deleteCurrentFile() {
    if (confirm("Apakah Anda yakin ingin menghapus file ini?")) {
        document.getElementById("delete_file").value = "1";
        const currentFileDiv = document.querySelector(".form-group:has([name=\'delete_file\'])");
        if (currentFileDiv) {
            currentFileDiv.style.display = "none";
        }
        showWarningToast("File akan dihapus setelah menyimpan perubahan");
    }
}

function resetToOriginal() {
    if (confirm("Apakah Anda yakin ingin mereset form ke data asli?")) {
        Object.keys(originalData).forEach(key => {
            const field = document.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = originalData[key] || "";
            }
        });
        
        document.getElementById("delete_file").value = "0";
        const currentFileDiv = document.querySelector(".form-group:has([name=\'delete_file\'])");
        if (currentFileDiv) {
            currentFileDiv.style.display = "block";
        }
        
        document.getElementById("filePreview").innerHTML = "";
        document.getElementById("file_surat").value = "";
        
        showInfoToast("Form telah direset ke data asli");
    }
}

// Auto-resize textarea
document.addEventListener("DOMContentLoaded", function() {
    const textareas = document.querySelectorAll("textarea");
    textareas.forEach(textarea => {
        textarea.addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = this.scrollHeight + "px";
        });
        
        // Initial resize
        textarea.style.height = "auto";
        textarea.style.height = textarea.scrollHeight + "px";
    });
});

// Form validation
document.querySelector("form").addEventListener("submit", function(e) {
    const requiredFields = ["nomor_surat", "tanggal_surat", "tanggal_diterima", "pengirim", "perihal"];
    let hasError = false;
    
    requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field.value.trim()) {
            field.classList.add("is-invalid");
            hasError = true;
        } else {
            field.classList.remove("is-invalid");
        }
    });
    
    if (hasError) {
        e.preventDefault();
        showErrorToast("Mohon lengkapi semua field yang wajib diisi");
    }
});

// Real-time validation
document.querySelectorAll("input[required], textarea[required]").forEach(field => {
    field.addEventListener("blur", function() {
        if (!this.value.trim()) {
            this.classList.add("is-invalid");
        } else {
            this.classList.remove("is-invalid");
        }
    });
    
    field.addEventListener("input", function() {
        if (this.classList.contains("is-invalid") && this.value.trim()) {
            this.classList.remove("is-invalid");
        }
    });
});

// Track changes
let hasChanges = false;
document.querySelectorAll("input, textarea, select").forEach(field => {
    const originalValue = field.value;
    field.addEventListener("input", function() {
        hasChanges = this.value !== originalValue;
    });
});

// Warn before leaving if there are unsaved changes
window.addEventListener("beforeunload", function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = "Ada perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?";
    }
});

// Reset hasChanges when form is submitted
document.querySelector("form").addEventListener("submit", function() {
    hasChanges = false;
});
';

include '../../includes/footer.php';
?>
