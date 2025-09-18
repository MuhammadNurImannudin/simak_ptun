<?php
// includes/sidebar.php - Updated dengan Logo PTUN Animasi
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

function isActive($page, $directory = '') {
    global $current_page, $current_dir;
    
    if ($directory) {
        return $current_dir === $directory;
    }
    
    return $current_page === $page || 
           (is_array($page) && in_array($current_page, $page));
}
?>

<!-- Enhanced Sidebar dengan Logo PTUN -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= APP_URL ?>index.php" class="logo">
            <div class="logo-container">
                <img src="<?= APP_URL ?>assets/img/logo-ptun.png" 
                     alt="Logo PTUN Banjarmasin" 
                     class="logo-ptun"
                     onerror="this.style.display='none'">
                <div class="logo-pulse-ring"></div>
            </div>
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
                    if (function_exists('getTotalSuratMasuk')) {
                        $pending_count = getTotalSuratMasuk('pending');
                        if ($pending_count > 0):
                    ?>
                        <span class="badge badge-warning" style="margin-left: auto; font-size: 0.7rem;">
                            <?= $pending_count ?>
                        </span>
                    <?php 
                        endif;
                    }
                    ?>
                </a>
            </li>
            
            <!-- Surat Keluar -->
            <li class="nav-item">
                <a href="<?= APP_URL ?>pages/surat-keluar/" 
                   class="nav-link <?= isActive('', 'surat-keluar') ? 'active' : '' ?>">
                    <i class="fas fa-paper-plane"></i>
                    <span>Surat Keluar</span>
                    <?php
                    if (function_exists('getTotalSuratKeluar')) {
                        $draft_count = getTotalSuratKeluar('draft');
                        if ($draft_count > 0):
                    ?>
                        <span class="badge badge-info" style="margin-left: auto; font-size: 0.7rem;">
                            <?= $draft_count ?>
                        </span>
                    <?php 
                        endif;
                    }
                    ?>
                </a>
            </li>
            
            <!-- Divider -->
            <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            
            <!-- REPORTS DROPDOWN SECTION -->
            <li class="nav-item nav-dropdown">
                <div class="nav-link nav-dropdown-toggle <?= isActive('', 'reports') ? 'active' : '' ?>" onclick="toggleReportsDropdown(this)">
                    <div style="display: flex; align-items: center;">
                        <i class="fas fa-chart-bar"></i>
                        <span>Laporan</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                </div>
                <div class="nav-dropdown-menu" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease; background: rgba(0,0,0,0.2); border-radius: 8px; margin: 0.25rem 0.75rem;">
                    <a href="<?= APP_URL ?>pages/reports/index.php" 
                       class="nav-dropdown-item <?= isActive('index', 'reports') ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard Laporan</span>
                    </a>
                    <a href="<?= APP_URL ?>pages/reports/laporan-bulanan.php" 
                       class="nav-dropdown-item <?= isActive('laporan-bulanan', 'reports') ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Laporan Bulanan</span>
                    </a>
                    <a href="<?= APP_URL ?>pages/reports/laporan-tahunan.php" 
                       class="nav-dropdown-item <?= isActive('laporan-tahunan', 'reports') ? 'active' : '' ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Laporan Tahunan</span>
                    </a>
                    <a href="<?= APP_URL ?>pages/reports/statistik-surat.php" 
                       class="nav-dropdown-item <?= isActive('statistik-surat', 'reports') ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik Surat</span>
                    </a>
                    <a href="<?= APP_URL ?>pages/reports/rekapitulasi.php" 
                       class="nav-dropdown-item <?= isActive('rekapitulasi', 'reports') ? 'active' : '' ?>">
                        <i class="fas fa-file-excel"></i>
                        <span>Rekapitulasi</span>
                    </a>
                    <a href="<?= APP_URL ?>pages/reports/laporan-disposisi.php" 
                       class="nav-dropdown-item <?= isActive('laporan-disposisi', 'reports') ? 'active' : '' ?>">
                        <i class="fas fa-route"></i>
                        <span>Laporan Disposisi</span>
                    </a>
                </div>
            </li>
            
            <!-- Settings Section (Admin Only) -->
            <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <!-- Divider -->
                <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
                
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
            <?php endif; ?>
            
            <!-- Divider -->
            <li class="nav-divider" style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></li>
            
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
                <span class="sidebar-expanded-only"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User') ?></span>
            </div>
            <div>
                <i class="fas fa-clock"></i>
                <span class="sidebar-expanded-only" id="currentTime"></span>
            </div>
        </div>
    </div>
</aside>

<style>
/* Enhanced Logo Styles untuk Sidebar */
.logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
    text-decoration: none;
    padding: 0.5rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.logo:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.logo-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-ptun {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    position: relative;
    z-index: 2;
}

.logo-ptun:hover {
    transform: scale(1.1) rotateY(10deg);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
}

.logo-pulse-ring {
    position: absolute;
    width: 60px;
    height: 60px;
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 50%;
    animation: logoPulse 2s ease-out infinite;
    z-index: 1;
}

.logo-text h4 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.logo-text .subtitle {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.2;
    margin-top: 0.25rem;
}

/* Dropdown Styles */
.nav-dropdown {
    position: relative;
}

.nav-dropdown-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    cursor: pointer;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
}

.nav-dropdown-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.nav-dropdown-toggle.active {
    background: var(--primary-color);
    color: white;
}

.nav-dropdown.show .dropdown-arrow {
    transform: rotate(180deg);
}

.nav-dropdown.show .nav-dropdown-menu {
    max-height: 400px !important;
    padding: 0.5rem 0;
}

.nav-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1.25rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    border-radius: 6px;
    margin: 0.125rem 0.5rem;
}

.nav-dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    transform: translateX(4px);
}

.nav-dropdown-item.active {
    background: var(--primary-color);
    color: white;
}

.nav-dropdown-item i {
    width: 16px;
    text-align: center;
    font-size: 0.875rem;
}

/* Sidebar collapsed state */
.sidebar.collapsed .logo-container {
    justify-content: center;
}

.sidebar.collapsed .logo-text {
    display: none;
}

.sidebar.collapsed .logo-ptun {
    width: 40px;
    height: 40px;
}

.sidebar.collapsed .logo-pulse-ring {
    width: 50px;
    height: 50px;
}

/* Animations */
@keyframes logoPulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.2);
        opacity: 0;
    }
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
}
</style>

<script>
// Toggle Reports Dropdown
function toggleReportsDropdown(element) {
    const dropdown = element.closest('.nav-dropdown');
    dropdown.classList.toggle('show');
    return false;
}

// Auto-open reports dropdown if on reports page
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/reports/')) {
        const reportsDropdown = document.querySelector('.nav-dropdown');
        if (reportsDropdown) {
            reportsDropdown.classList.add('show');
        }
    }
});

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

updateTime();
setInterval(updateTime, 60000);

// Enhanced logo interaction
document.addEventListener('DOMContentLoaded', function() {
    const logo = document.querySelector('.logo-ptun');
    
    if (logo) {
        // Add click animation
        logo.addEventListener('click', function() {
            this.style.animation = 'none';
            setTimeout(() => {
                this.style.animation = '';
            }, 100);
            
            // Add temporary spin effect
            this.style.transform = 'scale(1.2) rotateY(360deg)';
            setTimeout(() => {
                this.style.transform = '';
            }, 600);
        });
    }
});
</script>