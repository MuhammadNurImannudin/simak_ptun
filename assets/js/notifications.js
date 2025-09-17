// assets/js/notifications.js

class NotificationSystem {
    constructor() {
        this.container = null;
        this.notifications = [];
        this.init();
    }

    init() {
        this.createContainer();
        this.bindEvents();
        this.loadNotifications();
    }

    createContainer() {
        // Create notification container if it doesn't exist
        if (!document.querySelector('.notification-container')) {
            const container = document.createElement('div');
            container.className = 'notification-container';
            container.innerHTML = `
                <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
                </div>
            `;
            document.body.appendChild(container);
        }
        this.container = document.querySelector('.toast-container');
    }

    bindEvents() {
        // Notification dropdown toggle
        const notificationBtn = document.querySelector('.notification-btn');
        const dropdownMenu = document.querySelector('.notifications-dropdown .dropdown-menu');

        if (notificationBtn && dropdownMenu) {
            notificationBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown(dropdownMenu);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.notifications-dropdown')) {
                    dropdownMenu.classList.remove('show');
                }
            });

            // Mark notification as read when clicked
            dropdownMenu.addEventListener('click', (e) => {
                const notificationItem = e.target.closest('.notification-item');
                if (notificationItem && notificationItem.classList.contains('unread')) {
                    this.markAsRead(notificationItem.dataset.id);
                }
            });
        }
    }

    toggleDropdown(dropdown) {
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            this.loadNotifications();
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('api/get-notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateDropdown(data.notifications);
                this.updateBadge(data.unread_count);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    updateDropdown(notifications) {
        const dropdown = document.querySelector('.notifications-dropdown .dropdown-menu');
        if (!dropdown) return;

        const notificationsHtml = notifications.map(notification => `
            <div class="notification-item ${notification.is_read == 0 ? 'unread' : ''}" 
                 data-id="${notification.id}">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${this.formatTime(notification.created_at)}</div>
            </div>
        `).join('');

        dropdown.innerHTML = `
            <div class="dropdown-header d-flex justify-content-between align-items-center">
                <span>Notifikasi</span>
                <button class="btn btn-sm btn-outline" onclick="notificationSystem.markAllAsRead()">
                    Tandai Semua Dibaca
                </button>
            </div>
            ${notificationsHtml || '<div class="notification-item">Tidak ada notifikasi</div>'}
        `;
    }

    updateBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: notificationId })
            });

            const data = await response.json();
            if (data.success) {
                // Update UI
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                this.loadNotifications(); // Refresh to update badge
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('api/mark-all-notifications-read.php', {
                method: 'POST'
            });

            const data = await response.json();
            if (data.success) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    // Toast notifications
    showToast(type, title, message, duration = 5000) {
        const toastId = 'toast_' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast toast-${type}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-${this.getToastIcon(type)} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close" onclick="notificationSystem.hideToast('${toastId}')"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        this.container.insertAdjacentHTML('beforeend', toastHtml);
        
        const toast = document.getElementById(toastId);
        toast.classList.add('show');

        // Auto-hide after duration
        setTimeout(() => {
            this.hideToast(toastId);
        }, duration);
    }

    hideToast(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    }

    getToastIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) {
            return 'Baru saja';
        } else if (diffMins < 60) {
            return `${diffMins} menit yang lalu`;
        } else if (diffHours < 24) {
            return `${diffHours} jam yang lalu`;
        } else if (diffDays < 30) {
            return `${diffDays} hari yang lalu`;
        } else {
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }
    }

    // Real-time notifications (for future implementation with WebSockets)
    initRealTime() {
        // This could be implemented with WebSockets or Server-Sent Events
        // For now, we'll use polling
        setInterval(() => {
            this.checkNewNotifications();
        }, 30000); // Check every 30 seconds
    }

    async checkNewNotifications() {
        try {
            const response = await fetch('api/check-new-notifications.php');
            const data = await response.json();
            
            if (data.new_notifications && data.new_notifications.length > 0) {
                data.new_notifications.forEach(notification => {
                    this.showToast(
                        notification.type || 'info',
                        notification.title,
                        notification.message
                    );
                });
                
                this.loadNotifications(); // Refresh dropdown
            }
        } catch (error) {
            console.error('Error checking new notifications:', error);
        }
    }
}

// Toast notification styles (add to CSS)
const toastStyles = `
<style>
.toast-container {
    z-index: 9999;
}

.toast {
    background-color: white;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    margin-bottom: 0.5rem;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    max-width: 350px;
}

.toast.show {
    opacity: 1;
    transform: translateX(0);
}

.toast-header {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    background-color: var(--bg-color);
    border-bottom: 1px solid var(--border-color);
    border-radius: 8px 8px 0 0;
}

.toast-body {
    padding: 0.75rem 1rem;
    color: var(--text-primary);
}

.toast-success .toast-header {
    background-color: #dcfce7;
    color: #166534;
}

.toast-error .toast-header {
    background-color: #fee2e2;
    color: #991b1b;
}

.toast-warning .toast-header {
    background-color: #fef3c7;
    color: #92400e;
}

.toast-info .toast-header {
    background-color: #f0f9ff;
    color: #0c4a6e;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0.5;
    padding: 0;
    margin-left: auto;
}

.btn-close:hover {
    opacity: 1;
}
</style>
`;

// Add styles to head
document.head.insertAdjacentHTML('beforeend', toastStyles);

// Initialize notification system
let notificationSystem;
document.addEventListener('DOMContentLoaded', function() {
    notificationSystem = new NotificationSystem();
    
    // Initialize real-time notifications
    notificationSystem.initRealTime();
});

// Global functions for easy access
window.showToast = function(type, title, message, duration) {
    if (notificationSystem) {
        notificationSystem.showToast(type, title, message, duration);
    }
};

window.showSuccessToast = function(message) {
    showToast('success', 'Berhasil', message);
};

window.showErrorToast = function(message) {
    showToast('error', 'Error', message);
};

window.showWarningToast = function(message) {
    showToast('warning', 'Peringatan', message);
};

window.showInfoToast = function(message) {
    showToast('info', 'Informasi', message);
};
