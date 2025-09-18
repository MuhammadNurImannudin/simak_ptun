<?php
// pages/reports/disposisi.php - Laporan Disposisi dengan Workflow Tracking
require_once '../../config/config.php';
require_once '../../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit();
}

$workflow_stages = [
    ['stage' => 'Diterima', 'jumlah' => 156, 'color' => '#3b82f6', 'icon' => 'fas fa-inbox'],
    ['stage' => 'Didisposisi', 'jumlah' => 142, 'color' => '#f59e0b', 'icon' => 'fas fa-share'],
    ['stage' => 'Diproses', 'jumlah' => 128, 'color' => '#8b5cf6', 'icon' => 'fas fa-cogs'],
    ['stage' => 'Selesai', 'jumlah' => 112, 'color' => '#10b981', 'icon' => 'fas fa-check-circle']
];

$pic_performance = [
    ['nama' => 'Dr. Budi Santoso, S.H., M.H.', 'jabatan' => 'Hakim Ketua', 'total' => 28, 'selesai' => 25, 'avg_time' => '1.2 hari'],
    ['nama' => 'Siti Nurhaliza, S.H.', 'jabatan' => 'Panitera', 'total' => 24, 'selesai' => 22, 'avg_time' => '1.5 hari'],
    ['nama' => 'Ahmad Fauzi, S.H., M.H.', 'jabatan' => 'Hakim Anggota', 'total' => 22, 'selesai' => 20, 'avg_time' => '1.8 hari'],
    ['nama' => 'Rina Kartika, S.H.', 'jabatan' => 'Sekretaris', 'total' => 18, 'selesai' => 16, 'avg_time' => '2.1 hari'],
    ['nama' => 'Hendra Wijaya, S.Kom.', 'jabatan' => 'Staf IT', 'total' => 15, 'selesai' => 14, 'avg_time' => '1.0 hari']
];

$pending_disposisi = [
    ['nomor' => '125/SM/IX/2024', 'pengirim' => 'Mahkamah Agung RI', 'pic' => 'Dr. Budi Santoso', 'durasi' => '3 hari', 'priority' => 'High'],
    ['nomor' => '126/SM/IX/2024', 'pengirim' => 'Kemenkumham', 'pic' => 'Siti Nurhaliza', 'durasi' => '2 hari', 'priority' => 'Medium'],
    ['nomor' => '127/SM/IX/2024', 'pengirim' => 'Pemkot Bjm', 'pic' => 'Ahmad Fauzi', 'durasi' => '1 hari', 'priority' => 'Low'],
    ['nomor' => '128/SM/IX/2024', 'pengirim' => 'Dinas Pendidikan', 'pic' => 'Rina Kartika', 'durasi' => '4 hari', 'priority' => 'High']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Disposisi - SIMAK PTUN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Workflow Visualization */
        .workflow-stage {
            position: relative;
            padding: 1.5rem;
            border-radius: 12px;
            background: white;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            text-align: center;
        }

        .workflow-stage:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .workflow-stage.active {
            border-color: #4f46e5;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
        }

        .workflow-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .workflow-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .workflow-connector {
            position: absolute;
            top: 50%;
            right: -20px;
            width: 40px;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }

        .workflow-connector::after {
            content: '';
            position: absolute;
            right: -6px;
            top: -4px;
            width: 0;
            height: 0;
            border-left: 8px solid #e5e7eb;
            border-top: 4px solid transparent;
            border-bottom: 4px solid transparent;
        }

        .pic-card {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .pic-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .pic-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.25rem;
        }

        .performance-meter {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .performance-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .priority-high { 
            background: linear-gradient(45deg, #ef4444, #f87171);
            color: white;
        }
        .priority-medium { 
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
            color: white;
        }
        .priority-low { 
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
        }

        .kanban-column {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            min-height: 400px;
        }

        .kanban-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #4f46e5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        .kanban-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar dengan "Laporan Disposisi" active -->
    
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="header-title">
                    <i class="fas fa-route me-3"></i>
                    Laporan Disposisi
                </h1>
                <p class="header-subtitle">Monitoring workflow disposisi dan analisis performa PIC</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-warning me-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    Alert Pending
                </button>
                <button class="btn btn-success">
                    <i class="fas fa-download"></i>
                    Export Report
                </button>
            </div>
        </header>

        <div class="content">
            <!-- Workflow Visualization -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-project-diagram me-2"></i>
                    Workflow Disposisi
                    <span class="badge bg-info ms-2">Real-time</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 position-relative">
                        <?php foreach ($workflow_stages as $index => $stage): ?>
                        <div class="col-lg-3 col-md-6">
                            <div class="workflow-stage <?= $index === 2 ? 'active' : '' ?>">
                                <?php if ($index < count($workflow_stages) - 1): ?>
                                <div class="workflow-connector d-none d-lg-block"></div>
                                <?php endif; ?>
                                <div class="workflow-icon" style="background: <?= $stage['color'] ?>;">
                                    <i class="<?= $stage['icon'] ?>"></i>
                                </div>
                                <div class="workflow-number"><?= $stage['jumlah'] ?></div>
                                <div class="fw-medium"><?= $stage['stage'] ?></div>
                                <small class="opacity-75">Total surat</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Efficiency Metrics -->
                    <div class="row mt-4">
                        <div class="col-lg-3 col-md-6 text-center">
                            <div class="h3 text-success mb-1">89.7%</div>
                            <small class="text-muted">Completion Rate</small>
                        </div>
                        <div class="col-lg-3 col-md-6 text-center">
                            <div class="h3 text-info mb-1">1.6</div>
                            <small class="text-muted">Avg Days</small>
                        </div>
                        <div class="col-lg-3 col-md-6 text-center">
                            <div class="h3 text-warning mb-1">8</div>
                            <small class="text-muted">Pending Items</small>
                        </div>
                        <div class="col-lg-3 col-md-6 text-center">
                            <div class="h3 text-primary mb-1">156</div>
                            <small class="text-muted">Total Process</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PIC Performance & Pending Items -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users me-2"></i>
                            Performance PIC (Person in Charge)
                        </div>
                        <div class="card-body">
                            <?php foreach ($pic_performance as $index => $pic): ?>
                            <div class="pic-card p-3 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="pic-avatar">
                                            <?= substr($pic['nama'], 0, 2) ?>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="fw-bold"><?= $pic['nama'] ?></div>
                                        <small class="text-muted"><?= $pic['jabatan'] ?></small>
                                    </div>
                                    <div class="col-auto text-center">
                                        <div class="fw-bold"><?= $pic['total'] ?></div>
                                        <small class="text-muted">Total</small>
                                    </div>
                                    <div class="col-auto text-center">
                                        <div class="fw-bold text-success"><?= $pic['selesai'] ?></div>
                                        <small class="text-muted">Selesai</small>
                                    </div>
                                    <div class="col-auto text-center">
                                        <div class="fw-bold"><?= $pic['avg_time'] ?></div>
                                        <small class="text-muted">Avg Time</small>
                                    </div>
                                    <div class="col-3">
                                        <?php $performance = ($pic['selesai'] / $pic['total']) * 100; ?>
                                        <div class="performance-meter">
                                            <div class="performance-fill" 
                                                 style="width: <?= $performance ?>%; background: <?= $performance >= 90 ? '#10b981' : ($performance >= 80 ? '#f59e0b' : '#ef4444') ?>;"></div>
                                        </div>
                                        <small class="text-muted"><?= round($performance, 1) ?>% completed</small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clock me-2"></i>
                            Pending Disposisi
                            <span class="badge bg-danger ms-2"><?= count($pending_disposisi) ?></span>
                        </div>
                        <div class="card-body">
                            <?php foreach ($pending_disposisi as $item): ?>
                            <div class="kanban-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <small class="text-muted"><?= $item['nomor'] ?></small>
                                    <span class="badge priority-<?= strtolower($item['priority']) ?>"><?= $item['priority'] ?></span>
                                </div>
                                <div class="fw-medium mb-1"><?= $item['pengirim'] ?></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">PIC: <?= explode(' ', $item['pic'])[0] ?></small>
                                    <small class="text-danger fw-medium">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $item['durasi'] ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanban-style Status Board -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-columns me-2"></i>
                    Disposisi Board
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <div class="kanban-column">
                                <h6 class="text-center mb-3">
                                    <i class="fas fa-inbox text-primary me-2"></i>
                                    Masuk (14)
                                </h6>
                                <!-- Kanban cards for "Masuk" status -->
                                <div class="kanban-card">
                                    <small class="text-muted">129/SM/IX/2024</small>
                                    <div class="fw-medium">Surat dari MA RI</div>
                                    <small class="text-muted">2 jam lalu</small>
                                </div>
                                <div class="kanban-card">
                                    <small class="text-muted">130/SM/IX/2024</small>
                                    <div class="fw-medium">Permohonan Info</div>
                                    <small class="text-muted">5 jam lalu</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="kanban-column">
                                <h6 class="text-center mb-3">
                                    <i class="fas fa-share text-warning me-2"></i>
                                    Disposisi (8)
                                </h6>
                                <!-- Kanban cards for "Disposisi" status -->
                                <div class="kanban-card">
                                    <small class="text-muted">125/SM/IX/2024</small>
                                    <div class="fw-medium">Surat Edaran</div>
                                    <small class="text-muted">Dr. Budi S.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="kanban-column">
                                <h6 class="text-center mb-3">
                                    <i class="fas fa-cogs text-info me-2"></i>
                                    Proses (12)
                                </h6>
                                <!-- Kanban cards for "Proses" status -->
                                <div class="kanban-card">
                                    <small class="text-muted">120/SM/IX/2024</small>
                                    <div class="fw-medium">Review Dokumen</div>
                                    <small class="text-muted">Siti N.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="kanban-column">
                                <h6 class="text-center mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Selesai (45)
                                </h6>
                                <!-- Kanban cards for "Selesai" status -->
                                <div class="kanban-card">
                                    <small class="text-muted">115/SM/IX/2024</small>
                                    <div class="fw-medium">Surat Balasan</div>
                                    <small class="text-success">Completed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Add drag and drop functionality for kanban cards
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.draggable = true;
            
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/html', e.target.outerHTML);
                e.target.style.opacity = '0.5';
            });
            
            card.addEventListener('dragend', (e) => {
                e.target.style.opacity = '1';
            });
        });

        document.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', (e) => {
                e.preventDefault();
                column.style.background = '#e0e7ff';
            });
            
            column.addEventListener('dragleave', (e) => {
                column.style.background = '#f8fafc';
            });
            
            column.addEventListener('drop', (e) => {
                e.preventDefault();
                column.style.background = '#f8fafc';
                // Handle drop logic here
            });
        });

        // Animate performance meters on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.querySelectorAll('.performance-fill').forEach(fill => {
                    fill.style.width = fill.style.width;
                });
            }, 500);
        });
    </script>
</body>
</html>