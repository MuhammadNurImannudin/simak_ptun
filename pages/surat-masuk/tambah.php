<?php
// pages/surat-masuk/tambah.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Tambah Surat Masuk';

$errors = [];
$success = false;

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
    
    // Check if nomor surat already exists
    if (!isset($errors['nomor_surat'])) {
        $existing = $db->fetch("SELECT id FROM surat_masuk WHERE nomor_surat = ?", [$nomor_surat]);
        if ($existing) {
            $errors['nomor_surat'] = 'Nomor surat sudah ada';
        }
    }
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === UPLOAD_ERR_OK) {
        try {
            $file_path = uploadFile($_FILES['file_surat'], 'surat-masuk');
        } catch (Exception $e) {
            $errors['file_surat'] = $e->getMessage();
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO surat_masuk (nomor_surat, tanggal_surat, tanggal_diterima, pengirim, perihal, lampiran, file_surat, disposisi, status, user_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
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
                $_SESSION['user_id']
            ];
            
            $db->query($sql, $params);
            
            // Create notification
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $db->query($notification_sql, [
                $_SESSION['user_id'],
                'Surat Masuk Baru',
                "Surat masuk {$nomor_surat} berhasil ditambahkan",
                'success'
            ]);
            
            setFlashMessage('success', 'Surat masuk berhasil ditambahkan');
            redirect('index.php');
            
        } catch (Exception $e) {
            $errors['database'] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

// Generate nomor surat otomatis jika kosong
$nomor_surat_default = generateNomorSurat('masuk');

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
                    <i class="fas fa-plus"></i>
                    Tambah Surat Masuk
                </h1>
                <p class="page-subtitle">
                    Tambahkan surat masuk baru ke dalam sistem
                </p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Kembali
                </a>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Form Tambah Surat Masuk</h5>
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
                            <div class="d-flex gap-2">
                                <input type="text" 
                                       id="nomor_surat" 
                                       name="nomor_surat" 
                                       class="form-control <?= isset($errors['nomor_surat']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['nomor_surat'] ?? $nomor_surat_default) ?>"
                                       placeholder="Contoh: 001/SM/2024"
                                       required>
                                <button type="button" class="btn btn-outline" onclick="generateNomorSurat()" title="Generate Otomatis">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['nomor_surat'])): ?>
                                <div class="invalid-feedback d-block"><?= $errors['nomor_surat'] ?></div>
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
                                   value="<?= htmlspecialchars($_POST['tanggal_surat'] ?? '') ?>"
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
                                   value="<?= htmlspecialchars($_POST['tanggal_diterima'] ?? date('Y-m-d')) ?>"
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
                                   value="<?= htmlspecialchars($_POST['pengirim'] ?? '') ?>"
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
                                   value="<?= htmlspecialchars($_POST['lampiran'] ?? '') ?>"
                                   placeholder="Contoh: 1 berkas">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="pending" <?= ($_POST['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="diproses" <?= ($_POST['status'] ?? '') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="selesai" <?= ($_POST['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
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
                              required><?= htmlspecialchars($_POST['perihal'] ?? '') ?></textarea>
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
                              placeholder="Disposisi atau catatan tambahan"><?= htmlspecialchars($_POST['disposisi'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="file_surat" class="form-label">File Surat</label>
                    <input type="file" 
                           id="file_surat" 
                           name="file_surat" 
                           class="form-control <?= isset($errors['file_surat']) ? 'is-invalid' : '' ?>"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           data-preview="#filePreview">
                    <small class="form-text text-muted">
                        Format yang didukung: PDF, DOC, DOCX, JPG, PNG. Maksimal 5MB.
                    </small>
                    <?php if (isset($errors['file_surat'])): ?>
                        <div class="invalid-feedback d-block"><?= $errors['file_surat'] ?></div>
                    <?php endif; ?>
                    <div id="filePreview" class="mt-2"></div>
                </div>
                
                <div class="form-group">
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Surat Masuk
                        </button>
                        <button type="reset" class="btn btn-outline" onclick="resetForm()">
                            <i class="fas fa-undo"></i>
                            Reset Form
                        </button>
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
$inline_scripts = '
function generateNomorSurat() {
    fetch("../../api/generate-nomor-surat.php?type=masuk")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("nomor_surat").value = data.nomor_surat;
            }
        })
        .catch(error => console.error("Error:", error));
}

function resetForm() {
    if (confirm("Apakah Anda yakin ingin mereset form? Semua data yang telah diisi akan hilang.")) {
        document.querySelector("form").reset();
        document.getElementById("filePreview").innerHTML = "";
        document.getElementById("tanggal_diterima").value = "' . date('Y-m-d') . '";
        document.getElementById("nomor_surat").value = "' . $nomor_surat_default . '";
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
    });
    
    // Set today as default for tanggal_diterima if empty
    const tanggalDiterima = document.getElementById("tanggal_diterima");
    if (!tanggalDiterima.value) {
        tanggalDiterima.value = "' . date('Y-m-d') . '";
    }
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
';

include '../../includes/footer.php';
?>
