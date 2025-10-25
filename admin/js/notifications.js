// Admin Notifications System
let notificationCount = 0;
let notifications = [];
let isDropdownOpen = false;

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check for notifications immediately
    checkNotifications();
    
    // Then check every 30 seconds
    setInterval(checkNotifications, 30000);
    
    // Setup click handlers
    setupNotificationHandlers();
});

function setupNotificationHandlers() {
    const bellIcon = document.getElementById('notification-bell');
    const dropdown = document.getElementById('notification-dropdown');
    
    if (bellIcon) {
        bellIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleNotificationDropdown();
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (isDropdownOpen && dropdown && !dropdown.contains(e.target)) {
            closeNotificationDropdown();
        }
    });
}

function checkNotifications() {
    console.log('[Notifications] Checking for new notifications...');
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            console.log('[Notifications] Response:', data);
            if (data.success) {
                console.log('[Notifications] Found ' + data.count + ' notifications');
                updateNotificationUI(data.notifications, data.count);
                
                // Show browser notification for new orders/cancellations
                if (data.count > 0 && notificationCount < data.count) {
                    console.log('[Notifications] Showing browser notification');
                    showBrowserNotification(data.notifications[0]);
                }
                
                notificationCount = data.count;
                notifications = data.notifications;
            }
        })
        .catch(error => {
            console.error('[Notifications] Error fetching notifications:', error);
        });
}

function updateNotificationUI(notifs, count) {
    const badge = document.getElementById('notification-badge');
    const list = document.getElementById('notification-list');
    const emptyState = document.getElementById('notification-empty');
    
    // Update badge
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
    
    // Update dropdown list
    if (list && emptyState) {
        if (notifs.length > 0) {
            list.style.display = 'block';
            emptyState.style.display = 'none';
            list.innerHTML = notifs.map(notif => createNotificationItem(notif)).join('');
        } else {
            list.style.display = 'none';
            emptyState.style.display = 'block';
        }
    }
}

function createNotificationItem(notif) {
    const iconClass = notif.type === 'new_order' ? 'bi-bag-check-fill' : 'bi-x-circle-fill';
    const iconColor = notif.type === 'new_order' ? '#28a745' : '#dc3545';
    const timeAgo = getTimeAgo(notif.time);
    
    return `
        <div class="notification-item" onclick="handleNotificationClick(${notif.id}, '${notif.type}')">
            <div class="notification-icon" style="color: ${iconColor}">
                <i class="bi ${iconClass}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-message">${notif.message}</div>
                <div class="notification-meta">
                    <span class="notification-amount">₱${notif.amount}</span>
                    <span class="notification-time">${timeAgo}</span>
                </div>
            </div>
        </div>
    `;
}

function handleNotificationClick(orderId, type) {
    // Redirect to orders page with the specific order
    window.location.href = 'orders.php?order_id=' + orderId;
}

function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notification-dropdown');
    if (dropdown) {
        isDropdownOpen = !isDropdownOpen;
        dropdown.style.display = isDropdownOpen ? 'block' : 'none';
    }
}

function closeNotificationDropdown() {
    const dropdown = document.getElementById('notification-dropdown');
    if (dropdown) {
        isDropdownOpen = false;
        dropdown.style.display = 'none';
    }
}

function showBrowserNotification(notif) {
    // Check if browser supports notifications
    if (!("Notification" in window)) {
        return;
    }
    
    // Check permission
    if (Notification.permission === "granted") {
        createNotification(notif);
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(function(permission) {
            if (permission === "granted") {
                createNotification(notif);
            }
        });
    }
}

function createNotification(notif) {
    const title = notif.type === 'new_order' ? '🛍️ New Order!' : '❌ Order Cancelled';
    const options = {
        body: notif.message + ' - ₱' + notif.amount,
        icon: '../img/logo.png', // Add your logo path
        badge: '../img/logo.png',
        tag: 'order-notification-' + notif.id,
        requireInteraction: false
    };
    
    const notification = new Notification(title, options);
    
    notification.onclick = function() {
        window.focus();
        handleNotificationClick(notif.id, notif.type);
        notification.close();
    };
    
    // Auto close after 5 seconds
    setTimeout(() => notification.close(), 5000);
}

function getTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diff = Math.floor((now - time) / 1000); // difference in seconds
    
    if (diff < 60) {
        return 'Just now';
    } else if (diff < 3600) {
        const mins = Math.floor(diff / 60);
        return mins + ' min' + (mins > 1 ? 's' : '') + ' ago';
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
    } else {
        const days = Math.floor(diff / 86400);
        return days + ' day' + (days > 1 ? 's' : '') + ' ago';
    }
}

function clearAllNotifications() {
    // Reset notification count in session
    fetch('clear_notifications.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notificationCount = 0;
                notifications = [];
                updateNotificationUI([], 0);
                closeNotificationDropdown();
            }
        })
        .catch(error => {
            console.error('Error clearing notifications:', error);
        });
}
