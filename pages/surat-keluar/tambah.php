<?php
// pages/surat-keluar/tambah.php
require_once '../../config/config.php';

// Require login
requireLogin();

// Set page variables
$page_title = 'Tambah Surat Keluar';

$errors = [];
$success = false;

// Check if this is a reply to incoming letter
$ref_surat_masuk = null;
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $ref_id = (int)$_GET['ref'];
    $ref_surat_masuk = $db->fetch("SELECT * FROM surat_masuk WHERE id = ?", [$ref_id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $nomor_surat = sanitize($_POST['nomor_surat'] ?? '');
    $tanggal_surat = sanitize($_POST['tanggal_surat'] ?? '');
    $tujuan = sanitize($_POST['tujuan'] ?? '');
    $perihal = sanitize($_POST['perihal'] ?? '');
    $lampiran = sanitize($_POST['lampiran'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');
    
    // Validation
    if (empty($nomor_surat)) {
        $errors['nomor_surat'] = 'Nomor surat harus diisi';
    }
    
    if (empty($tanggal_surat)) {
        $errors['tanggal_surat'] = 'Tanggal surat harus diisi';
    }
    
    if (empty($tujuan)) {
        $errors['tujuan'] = 'Tujuan harus diisi';
    }
    
    if (empty($perihal)) {
        $errors['perihal'] = 'Perihal harus diisi';
    }
    
    // Check if nomor surat already exists
    if (!isset($errors['nomor_surat'])) {
        $existing = $db->fetch("SELECT id FROM surat_keluar WHERE nomor_surat = ?", [$nomor_surat]);
        if ($existing) {
            $errors['nomor_surat'] = 'Nomor surat sudah ada';
        }
    }
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === UPLOAD_ERR_OK) {
        try {
            $file_path = uploadFile($_FILES['file_surat'], 'surat-keluar');
        } catch (Exception $e) {
            $errors['file_surat'] = $e->getMessage();
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO surat_keluar (nomor_surat, tanggal_surat, tujuan, perihal, lampiran, file_surat, status, user_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $nomor_surat,
                $tanggal_surat,
                $tujuan,
                $perihal,
                $lampiran,
                $file_path,
                $status,
                $_SESSION['user_id']
            ];
            
            $db->query($sql, $params);
            
            // Create notification
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $db->query($notification_sql, [
                $_SESSION['user_id'],
                'Surat Keluar Baru',
                "Surat keluar {$nomor_surat} berhasil ditambahkan",
                'success'
            ]);
            
            setFlashMessage('success', 'Surat keluar berhasil ditambahkan');
            redirect('index.php');
            
        } catch (Exception $e) {
            $errors['database'] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

// Generate nomor surat otomatis jika kosong
$nomor_surat_default = generateNomorSurat('keluar');

// Pre-fill data if replying to incoming letter
$default_values = [];
if ($ref_surat_masuk) {
    $default_values = [
        'tujuan' => $ref_surat_masuk['pengirim'],
        'perihal' => 'Re: ' . $ref_surat_masuk['perihal']
    ];
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
                    <i class="fas fa-plus"></i>
                    Tambah Surat Keluar
                </h1>
                <p class="page-subtitle">
                    <?php if ($ref_surat_masuk): ?>
                        Balasan untuk surat masuk: <?= htmlspecialchars($ref_surat_masuk['nomor_surat']) ?>
                    <?php else: ?>
                        Tambahkan surat keluar baru ke dalam sistem
                    <?php endif; ?>
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
    
    <?php if ($ref_surat_masuk): ?>
        <!-- Reference Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-reply"></i>
                    Referensi Surat Masuk
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Nomor Surat:</strong> <?= htmlspecialchars($ref_surat_masuk['nomor_surat']) ?><br>
                        <strong>Pengirim:</strong> <?= htmlspecialchars($ref_surat_masuk['pengirim']) ?><br>
                        <strong>Tanggal Diterima:</strong> <?= formatTanggal($ref_surat_masuk['tanggal_diterima']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Perihal:</strong><br>
                        <?= htmlspecialchars($ref_surat_masuk['perihal']) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Form Tambah Surat Keluar</h5>
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
                                       placeholder="Contoh: 001/SK/PTUN-BJM/2024"
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
                                   value="<?= htmlspecialchars($_POST['tanggal_surat'] ?? date('Y-m-d')) ?>"
                                   required>
                            <?php if (isset($errors['tanggal_surat'])): ?>
                                <div class="invalid-feedback"><?= $errors['tanggal_surat'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="terkirim" <?= ($_POST['status'] ?? '') === 'terkirim' ? 'selected' : '' ?>>Terkirim</option>
                                <option value="arsip" <?= ($_POST['status'] ?? '') === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="tujuan" class="form-label">Tujuan *</label>
                            <input type="text" 
                                   id="tujuan" 
                                   name="tujuan" 
                                   class="form-control <?= isset($errors['tujuan']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($_POST['tujuan'] ?? $default_values['tujuan'] ?? '') ?>"
                                   placeholder="Nama instansi/lembaga tujuan"
                                   required>
                            <?php if (isset($errors['tujuan'])): ?>
                                <div class="invalid-feedback"><?= $errors['tujuan'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
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
                </div>
                
                <div class="form-group">
                    <label for="perihal" class="form-label">Perihal *</label>
                    <textarea id="perihal" 
                              name="perihal" 
                              class="form-control <?= isset($errors['perihal']) ? 'is-invalid' : '' ?>" 
                              rows="3"
                              placeholder="Isi perihal/subjek surat"
                              required><?= htmlspecialchars($_POST['perihal'] ?? $default_values['perihal'] ?? '') ?></textarea>
                    <?php if (isset($errors['perihal'])): ?>
                        <div class="invalid-feedback"><?= $errors['perihal'] ?></div>
                    <?php endif; ?>
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
                
                <!-- Quick Templates -->
                <div class="form-group">
                    <label class="form-label">Template Cepat</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline btn-sm" onclick="useTemplate('undangan')">
                            <i class="fas fa-calendar"></i>
                            Undangan
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="useTemplate('pemberitahuan')">
                            <i class="fas fa-bullhorn"></i>
                            Pemberitahuan
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="useTemplate('permohonan')">
                            <i class="fas fa-hand-paper"></i>
                            Permohonan
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="useTemplate('laporan')">
                            <i class="fas fa-file-alt"></i>
                            Laporan
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Surat Keluar
                        </button>
                        <button type="button" class="btn btn-success" onclick="saveAndSend()">
                            <i class="fas fa-paper-plane"></i>
                            Simpan & Kirim
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
$templates = [
    'undangan' => [
        'tujuan' => '',
        'perihal' => 'Undangan Rapat/Acara'
    ],
    'pemberitahuan' => [
        'tujuan' => '',
        'perihal' => 'Pemberitahuan'
    ],
    'permohonan' => [
        'tujuan' => '',
        'perihal' => 'Permohonan'
    ],
    'laporan' => [
        'tujuan' => 'Mahkamah Agung RI',
        'perihal' => 'Laporan Bulanan'
    ]
];

$inline_scripts = '
const templates = ' . json_encode($templates) . ';

function generateNomorSurat() {
    fetch("../../api/generate-nomor-surat.php?type=keluar")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("nomor_surat").value = data.nomor_surat;
            }
        })
        .catch(error => console.error("Error:", error));
}

function useTemplate(type) {
    if (templates[type]) {
        if (templates[type].tujuan) {
            document.getElementById("tujuan").value = templates[type].tujuan;
        }
        if (templates[type].perihal) {
            document.getElementById("perihal").value = templates[type].perihal;
        }
        showInfoToast("Template " + type + " berhasil diterapkan");
    }
}

function saveAndSend() {
    // Set status to terkirim
    document.getElementById("status").value = "terkirim";
    
    // Submit form
    document.querySelector("form").submit();
}

function resetForm() {
    if (confirm("Apakah Anda yakin ingin mereset form? Semua data yang telah diisi akan hilang.")) {
        document.querySelector("form").reset();
        document.getElementById("filePreview").innerHTML = "";
        document.getElementById("tanggal_surat").value = "' . date('Y-m-d') . '";
        document.getElementById("nomor_surat").value = "' . $nomor_surat_default . '";
        document.getElementById("status").value = "draft";
        
        // Restore default values if replying
        ' . ($ref_surat_masuk ? '
        document.getElementById("tujuan").value = "' . htmlspecialchars($default_values['tujuan']) . '";
        document.getElementById("perihal").value = "' . htmlspecialchars($default_values['perihal']) . '";
        ' : '') . '
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
    
    // Set today as default for tanggal_surat if empty
    const tanggalSurat = document.getElementById("tanggal_surat");
    if (!tanggalSurat.value) {
        tanggalSurat.value = "' . date('Y-m-d') . '";
    }
});

// Form validation
document.querySelector("form").addEventListener("submit", function(e) {
    const requiredFields = ["nomor_surat", "tanggal_surat", "tujuan", "perihal"];
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

// Auto-complete for tujuan (common institutions)
const commonInstitutions = [
    "Mahkamah Agung RI",
    "Pengadilan Negeri Banjarmasin", 
    "Kejaksaan Negeri Banjarmasin",
    "BPN Kota Banjarmasin",
    "Pemkot Banjarmasin",
    "DPRD Kota Banjarmasin",
    "Kepolisian Resort Kota Banjarmasin"
];

// Simple autocomplete implementation
const tujuanInput = document.getElementById("tujuan");
let autocompleteList = null;

tujuanInput.addEventListener("input", function() {
    const value = this.value.toLowerCase();
    
    // Remove existing autocomplete
    if (autocompleteList) {
        autocompleteList.remove();
    }
    
    if (value.length >= 2) {
        const matches = commonInstitutions.filter(inst => 
            inst.toLowerCase().includes(value)
        );
        
        if (matches.length > 0) {
            autocompleteList = document.createElement("div");
            autocompleteList.className = "autocomplete-list";
            autocompleteList.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                box-shadow: var(--shadow-lg);
                z-index: 1000;
                max-height: 200px;
                overflow-y: auto;
            `;
            
            matches.forEach(match => {
                const item = document.createElement("div");
                item.textContent = match;
                item.style.cssText = `
                    padding: 0.5rem 1rem;
                    cursor: pointer;
                    border-bottom: 1px solid var(--border-color);
                `;
                item.addEventListener("click", function() {
                    tujuanInput.value = match;
                    autocompleteList.remove();
                });
                item.addEventListener("mouseenter", function() {
                    this.style.backgroundColor = "var(--bg-color)";
                });
                item.addEventListener("mouseleave", function() {
                    this.style.backgroundColor = "transparent";
                });
                autocompleteList.appendChild(item);
            });
            
            this.parentElement.style.position = "relative";
            this.parentElement.appendChild(autocompleteList);
        }
    }
});

// Close autocomplete when clicking outside
document.addEventListener("click", function(e) {
    if (autocompleteList && !tujuanInput.contains(e.target) && !autocompleteList.contains(e.target)) {
        autocompleteList.remove();
    }
});
';

include '../../includes/footer.php';
?>
