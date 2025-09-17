<?php
// includes/sidebar.php

// Determine current page for active menu
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Function to check if menu is active
function isActive($page, $directory = '') {
    global $current_page, $current_dir;
    
    if ($directory) {
        return $current_dir === $directory;
    }
    
    return $current_page === $page || 
           (is_array($page) && in_array($current_page, $page));
}
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= APP_URL ?>index.php" class="logo">
            <img src="<?= APP_URL ?>assets/img/logo-ptun.png" alt="Logo PTUN" 
                 onerror="this.style.display='none'">
            <div class="logo-text">
                <h4><?= APP_NAME ?></h4>
                <div class="subtitle"><?= APP_SUBTITLE ?></div>
            </div>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list" style="list-style: none; padding: 0; margin: 0;">
            
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>index.php" 
                   class="nav-link <?= isActive('index') ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Divider -->
            <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            
            <!-- Surat Masuk -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/surat-masuk/" 
                   class="nav-link <?= isActive('', 'surat-masuk') ? 'active' : '' ?>">
                    <i class="fas fa-inbox"></i>
                    <span>Surat Masuk</span>
                    <?php
                    $pending_count = getTotalSuratMasuk('pending');
                    if ($pending_count > 0):
                    ?>
                        <span class="badge badge-warning" style="margin-left: auto; font-size: 0.7rem;">
                            <?= $pending_count ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <!-- Surat Keluar -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/surat-keluar/" 
                   class="nav-link <?= isActive('', 'surat-keluar') ? 'active' : '' ?>">
                    <i class="fas fa-paper-plane"></i>
                    <span>Surat Keluar</span>
                    <?php
                    $draft_count = getTotalSuratKeluar('draft');
                    if ($draft_count > 0):
                    ?>
                        <span class="badge badge-info" style="margin-left: auto; font-size: 0.7rem;">
                            <?= $draft_count ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <!-- Divider -->
            <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            
            <!-- Reports Section -->
            <li class="nav-item">
                <div class="nav-section-title" style="padding: 0.5rem 1.5rem; color: rgba(255,255,255,0.6); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                    <span>Laporan</span>
                </div>
            </li>
            
            <!-- Laporan Bulanan -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/reports/laporan-bulanan.php" 
                   class="nav-link <?= isActive('laporan-bulanan') ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Laporan Bulanan</span>
                </a>
            </li>
            
            <!-- Laporan Tahunan -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/reports/laporan-tahunan.php" 
                   class="nav-link <?= isActive('laporan-tahunan') ? 'active' : '' ?>">
                    <i class="fas fa-calendar"></i>
                    <span>Laporan Tahunan</span>
                </a>
            </li>
            
            <!-- Statistik Surat -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/reports/statistik-surat.php" 
                   class="nav-link <?= isActive('statistik-surat') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistik Surat</span>
                </a>
            </li>
            
            <!-- Rekapitulasi -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/reports/rekapitulasi.php" 
                   class="nav-link <?= isActive('rekapitulasi') ? 'active' : '' ?>">
                    <i class="fas fa-file-excel"></i>
                    <span>Rekapitulasi</span>
                </a>
            </li>
            
            <!-- Laporan Disposisi -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/reports/laporan-disposisi.php" 
                   class="nav-link <?= isActive('laporan-disposisi') ? 'active' : '' ?>">
                    <i class="fas fa-route"></i>
                    <span>Laporan Disposisi</span>
                </a>
            </li>
            
            <!-- Additional Reports (for Skripsi - 8 reports total) -->
            <?php if (isAdmin()): ?>
                <!-- Divider -->
                <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
                
                <li class="nav-item">
                    <div class="nav-section-title" style="padding: 0.5rem 1.5rem; color: rgba(255,255,255,0.6); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                        <span>Laporan Lanjutan</span>
                    </div>
                </li>
                
                <!-- Analisis Trend -->
                <li class="nav-item">
                    <a href="<?= APP_URL ?>pages/reports/analisis-trend.php" 
                       class="nav-link <?= isActive('analisis-trend') ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Analisis Trend</span>
                    </a>
                </li>
                
                <!-- Performa Penanganan -->
                <li class="nav-item">
                    <a href="<?= APP_URL ?>pages/reports/performa-penanganan.php" 
                       class="nav-link <?= isActive('performa-penanganan') ? 'active' : '' ?>">
                        <i class="fas fa-stopwatch"></i>
                        <span>Performa Penanganan</span>
                    </a>
                </li>
                
                <!-- Dashboard Eksekutif -->
                <li class="nav-item">
                    <a href="<?= APP_URL ?>pages/reports/dashboard-eksekutif.php" 
                       class="nav-link <?= isActive('dashboard-eksekutif') ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard Eksekutif</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Divider -->
            <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            
            <!-- Settings Section (Admin Only) -->
            <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <div class="nav-section-title" style="padding: 0.5rem 1.5rem; color: rgba(255,255,255,0.6); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                        <span>Pengaturan</span>
                    </div>
                </li>
                
                <!-- User Management -->
                <li class="nav-item">
                    <a href="<?= APP_URL ?>pages/users/" 
                       class="nav-link <?= isActive('', 'users') ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Manajemen User</span>
                    </a>
                </li>
                
                <!-- System Settings -->
                <li class="nav-item">
                    <a href="<?= APP_URL ?>pages/settings/" 
                       class="nav-link <?= isActive('', 'settings') ? 'active' : '' ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Pengaturan Sistem</span>
                    </a>
                </li>
                
                <!-- Divider -->
                <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            <?php endif; ?>
            
            <!-- Profile -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/profile/" 
                   class="nav-link <?= isActive('', 'profile') ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>
            </li>
            
            <!-- Help -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/help/" 
                   class="nav-link <?= isActive('', 'help') ? 'active' : '' ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>Bantuan</span>
                </a>
            </li>
            
            <!-- Quick Actions (when collapsed) -->
            <li class="nav-item sidebar-collapsed-only" style="display: none;">
                <div style="margin-top: 2rem; padding: 0 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="<?= APP_URL ?>pages/surat-masuk/tambah.php" 
                           class="btn btn-primary btn-sm" 
                           title="Tambah Surat Masuk">
                            <i class="fas fa-plus"></i>
                        </a>
                        <a href="<?= APP_URL ?>pages/surat-keluar/tambah.php" 
                           class="btn btn-success btn-sm" 
                           title="Tambah Surat Keluar">
                            <i class="fas fa-paper-plane"></i>
                        </a>
                    </div>
                </div>
            </li>
        </ul>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.1);">
        <div style="text-align: center; color: rgba(255,255,255,0.6); font-size: 0.75rem;">
            <div style="margin-bottom: 0.25rem;">
                <i class="fas fa-user"></i>
                <span class="sidebar-expanded-only"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
            </div>
            <div>
                <i class="fas fa-clock"></i>
                <span class="sidebar-expanded-only" id="currentTime"></span>
            </div>
        </div>
    </div>
</aside>

<style>
/* Sidebar responsive styles */
.sidebar-collapsed .sidebar-expanded-only {
    display: none !important;
}

.sidebar:not(.collapsed) .sidebar-collapsed-only {
    display: none !important;
}

.sidebar.collapsed .sidebar-collapsed-only {
    display: block !important;
}

.nav-section-title span {
    transition: opacity 0.3s ease;
}

.sidebar.collapsed .nav-section-title {
    display: none;
}

.sidebar.collapsed .nav-divider {
    margin: 0.25rem 0;
}

/* Badge positioning */
.nav-link .badge {
    transition: all 0.3s ease;
}

.sidebar.collapsed .nav-link .badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    transform: scale(0.8);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar-footer {
        display: none;
    }
}

/* Sidebar overlay for mobile */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
}

.sidebar-overlay.show {
    display: block;
}

@media (max-width: 768px) {
    .sidebar.show + .sidebar-overlay {
        display: block;
    }
}
</style>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// Update time every minute
updateTime();
setInterval(updateTime, 60000);

// Handle sidebar state changes
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    
    // Observer for sidebar class changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                const isCollapsed = sidebar.classList.contains('collapsed');
                
                // Toggle expanded/collapsed specific elements
                const expandedElements = document.querySelectorAll('.sidebar-expanded-only');
                const collapsedElements = document.querySelectorAll('.sidebar-collapsed-only');
                
                expandedElements.forEach(el => {
                    el.style.display = isCollapsed ? 'none' : '';
                });
                
                collapsedElements.forEach(el => {
                    el.style.display = isCollapsed ? 'block' : 'none';
                });
            }
        });
    });
    
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
});

// Mobile sidebar toggle
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('show');
    if (overlay) {
        overlay.classList.toggle('show');
    }
}

// Close mobile sidebar when clicking overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('sidebar-overlay')) {
        toggleMobileSidebar();
    }
});

// Add mobile toggle button for smaller screens
if (window.innerWidth <= 768) {
    const headerLeft = document.querySelector('.header-left');
    if (headerLeft) {
        const mobileToggle = document.createElement('button');
        mobileToggle.className = 'mobile-toggle';
        mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
        mobileToggle.onclick = toggleMobileSidebar;
        headerLeft.insertBefore(mobileToggle, headerLeft.firstChild);
    }
    
    // Add overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
}
</script>
