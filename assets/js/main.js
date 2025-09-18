// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
        
        // Restore sidebar state from localStorage
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
    
    // Mobile sidebar functionality
    const mobileToggle = document.querySelector('.mobile-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    // Active menu highlighting
    setActiveMenu();
    
    // Form validation
    initFormValidation();
    
    // Auto-hide alerts
    autoHideAlerts();
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize modals
    initModals();
    
    // File upload preview
    initFileUpload();
    
    // Search functionality
    initSearch();
    
    // Auto refresh for real-time updates
    initAutoRefresh();
});

// Set active menu based on current page
function setActiveMenu() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href)) {
            link.classList.add('active');
        }
    });
}

// Form validation
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
    
    // Real-time validation
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    const isValid = field.checkValidity();
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
    }
}

// Auto-hide alerts after 5 seconds
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Initialize tooltips
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    // Tooltip implementation would go here if using Bootstrap
}

// Initialize modals
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const targetModal = document.querySelector(this.getAttribute('data-target'));
            if (targetModal) {
                showModal(targetModal);
            }
        });
    });
    
    // Close modal functionality
    const closeButtons = document.querySelectorAll('.modal-close, .modal-backdrop');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
}

function showModal(modal) {
    modal.style.display = 'block';
    modal.classList.add('show');
    document.body.classList.add('modal-open');
}

function hideModal(modal) {
    modal.style.display = 'none';
    modal.classList.remove('show');
    document.body.classList.remove('modal-open');
}

// File upload preview
function initFileUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.querySelector(this.getAttribute('data-preview'));
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
                    } else {
                        preview.innerHTML = `<p>File terpilih: ${file.name}</p>`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

// Search functionality
function initSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        let timeout;
        
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                timeout = setTimeout(() => {
                    performSearch(query, this);
                }, 300);
            } else if (query.length === 0) {
                clearSearchResults(this);
            }
        });
    });
}

function performSearch(query, input) {
    const table = input.getAttribute('data-table');
    if (!table) return;
    
    // Show loading state
    const tbody = document.querySelector(`#${table} tbody`);
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="100%" class="text-center">Mencari...</td></tr>';
    }
    
    // Perform AJAX search
    fetch(`search.php?table=${table}&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            updateSearchResults(tbody, data);
        })
        .catch(error => {
            console.error('Search error:', error);
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center text-danger">Terjadi kesalahan saat mencari</td></tr>';
        });
}

function updateSearchResults(tbody, results) {
    if (!tbody) return;
    
    if (results.length === 0) {
        tbody.innerHTML = '<tr><td colspan="100%" class="text-center">Tidak ada data ditemukan</td></tr>';
        return;
    }
    
    // This would be customized based on the table structure
    tbody.innerHTML = results.map(row => generateTableRow(row)).join('');
}

function clearSearchResults(input) {
    // Reload original data
    window.location.reload();
}

// Auto refresh for real-time updates
function initAutoRefresh() {
    // Only enable on dashboard
    if (window.location.pathname.includes('dashboard')) {
        setInterval(() => {
            updateNotificationCount();
            updateStatistics();
        }, 30000); // Refresh every 30 seconds
    }
}

function updateNotificationCount() {
    fetch('api/notifications-count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.style.display = data.count > 0 ? 'block' : 'none';
            }
        })
        .catch(error => console.error('Notification update error:', error));
}

function updateStatistics() {
    fetch('api/statistics.php')
        .then(response => response.json())
        .then(data => {
            // Update stat cards
            Object.keys(data).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    element.textContent = data[key];
                }
            });
        })
        .catch(error => console.error('Statistics update error:', error));
}

// Utility functions
function showAlert(type, message) {
    const alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function confirmDelete(url, message = 'Apakah Anda yakin ingin menghapus data ini?') {
    if (confirm(message)) {
        window.location.href = url;
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

// Export functions for global use
window.SimakPTUN = {
    showAlert,
    confirmDelete,
    formatNumber,
    formatDate,
    showModal,
    hideModal
};
// Tambahkan di bagian akhir JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Prevent form auto-scroll
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Let form submit normally but prevent auto-scroll
            setTimeout(() => {
                window.scrollTo(0, 0);
            }, 100);
        });
    });

    // Fix anchor links
    const anchorLinks = document.querySelectorAll('a[href="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            return false;
        });
    });

    // Fix button clicks
    const buttons = document.querySelectorAll('button[onclick]');
    buttons.forEach(button => {
        const originalOnclick = button.getAttribute('onclick');
        button.setAttribute('onclick', originalOnclick + '; return false;');
    });
});