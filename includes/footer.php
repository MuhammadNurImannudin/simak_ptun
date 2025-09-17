<?php
// includes/footer.php
?>

        </div> <!-- End main-content -->
    </div> <!-- End container-fluid -->

    <!-- JavaScript -->
    <script src="<?= APP_URL ?>assets/js/main.js"></script>
    <script src="<?= APP_URL ?>assets/js/notifications.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?= $inline_scripts ?>
        </script>
    <?php endif; ?>
    
    <!-- Global JavaScript Variables -->
    <script>
        // Set global variables for JavaScript
        window.APP_URL = '<?= APP_URL ?>';
        window.CURRENT_USER = {
            id: <?= $_SESSION['user_id'] ?>,
            username: '<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES) ?>',
            nama_lengkap: '<?= htmlspecialchars($_SESSION['nama_lengkap'], ENT_QUOTES) ?>',
            role: '<?= htmlspecialchars($_SESSION['role'], ENT_QUOTES) ?>',
            email: '<?= htmlspecialchars($_SESSION['email'], ENT_QUOTES) ?>'
        };
    </script>
    
    <!-- Page specific JavaScript -->
    <script>
        // Show flash messages as toasts
        <?php
        $flash_types = ['success', 'warning', 'danger', 'info'];
        foreach ($flash_types as $type) {
            $message = getFlashMessage($type);
            if ($message) {
                $toast_type = $type === 'danger' ? 'error' : $type;
                echo "if (typeof showToast === 'function') {";
                echo "showToast('{$toast_type}', '" . ($toast_type === 'error' ? 'Error' : ucfirst($toast_type)) . "', '" . htmlspecialchars($message, ENT_QUOTES) . "');";
                echo "}";
            }
        }
        ?>
        
        // Auto-refresh data every 5 minutes (only on dashboard)
        <?php if ($current_page === 'index'): ?>
        setInterval(function() {
            // Refresh notifications
            if (typeof notificationSystem !== 'undefined') {
                notificationSystem.loadNotifications();
            }
            
            // Refresh statistics (implement AJAX call if needed)
            // refreshStatistics();
        }, 300000); // 5 minutes
        <?php endif; ?>
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Shift shortcuts
            if ((e.ctrlKey || e.metaKey) && e.shiftKey) {
                switch(e.key) {
                    case 'D': // Dashboard
                        e.preventDefault();
                        window.location.href = '<?= APP_URL ?>index.php';
                        break;
                    case 'I': // Surat Masuk (Incoming)
                        e.preventDefault();
                        window.location.href = '<?= APP_URL ?>pages/surat-masuk/';
                        break;
                    case 'O': // Surat Keluar (Outgoing)
                        e.preventDefault();
                        window.location.href = '<?= APP_URL ?>pages/surat-keluar/';
                        break;
                    case 'R': // Reports
                        e.preventDefault();
                        window.location.href = '<?= APP_URL ?>pages/reports/';
                        break;
                    case 'P': // Profile
                        e.preventDefault();
                        window.location.href = '<?= APP_URL ?>pages/profile/';
                        break;
                    case 'L': // Logout
                        e.preventDefault();
                        if (confirm('Apakah Anda yakin ingin keluar?')) {
                            window.location.href = '<?= APP_URL ?>logout.php';
                        }
                        break;
                }
            }
            
            // Escape key to close modals/dropdowns
            if (e.key === 'Escape') {
                // Close notifications dropdown
                const notificationDropdown = document.querySelector('.notifications-dropdown .dropdown-menu');
                if (notificationDropdown) {
                    notificationDropdown.classList.remove('show');
                }
                
                // Close user dropdown
                const userDropdown = document.getElementById('userDropdownMenu');
                if (userDropdown) {
                    userDropdown.classList.remove('show');
                }
                
                // Close any open modals
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                });
            }
        });
        
        // Initialize tooltips for elements with title attribute
        document.addEventListener('DOMContentLoaded', function() {
            const elementsWithTitle = document.querySelectorAll('[title]');
            elementsWithTitle.forEach(element => {
                // Simple tooltip implementation
                element.addEventListener('mouseenter', function(e) {
                    if (this.title) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'simple-tooltip';
                        tooltip.textContent = this.title;
                        tooltip.style.cssText = `
                            position: absolute;
                            background: rgba(0,0,0,0.8);
                            color: white;
                            padding: 0.5rem;
                            border-radius: 4px;
                            font-size: 0.75rem;
                            z-index: 10000;
                            pointer-events: none;
                            white-space: nowrap;
                        `;
                        
                        document.body.appendChild(tooltip);
                        
                        const rect = this.getBoundingClientRect();
                        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                        
                        this.tooltipElement = tooltip;
                        this.removeAttribute('title');
                        this.originalTitle = this.title || tooltip.textContent;
                    }
                });
                
                element.addEventListener('mouseleave', function() {
                    if (this.tooltipElement) {
                        this.tooltipElement.remove();
                        this.tooltipElement = null;
                        if (this.originalTitle) {
                            this.title = this.originalTitle;
                        }
                    }
                });
            });
        });
        
        // Page visibility handling (pause auto-refresh when tab is not visible)
        let isPageVisible = true;
        document.addEventListener('visibilitychange', function() {
            isPageVisible = !document.hidden;
            
            if (isPageVisible) {
                // Refresh notifications when page becomes visible again
                if (typeof notificationSystem !== 'undefined') {
                    notificationSystem.loadNotifications();
                }
            }
        });
        
        // Performance optimization: lazy load images
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        });
    </script>
    
    <!-- Analytics or tracking scripts can be added here -->
    <?php if (defined('ANALYTICS_CODE') && ANALYTICS_CODE): ?>
        <!-- Google Analytics or other tracking code -->
        <script>
            // Analytics implementation
        </script>
    <?php endif; ?>

</body>
</html>
