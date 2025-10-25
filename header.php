<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTC Clothing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Header Styles */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 0;
            background: #fafafa;
        }
        
        header { 
            background: #d9e6a7; 
            padding: 15px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky;
            top: 0;
            z-index: 9999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo { 
            font-size: 22px; 
            font-weight: bold; 
            color: #333;
        }
        
        .logo a {
            text-decoration: none;
            color: inherit;
        }
        
        nav a { 
            margin: 0 15px; 
            text-decoration: none; 
            color: #222; 
            font-weight: 600;
            transition: color 0.3s;
        }
        
        nav a:hover {
            color: #5b6b46;
        }
        
        nav a.active { 
            color: #5b6b46;
            text-decoration: underline;
        }
        
        .nav-right { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        }
        
        .btn-style { 
            background-color: #fff; 
            border: 1px solid #555; 
            padding: 8px 16px; 
            border-radius: 20px; 
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-style:hover {
            background-color: #f0f0f0;
        }
        
        .icon { 
            cursor: pointer; 
            font-size: 18px; 
            position: relative;
            color: #333;
            transition: color 0.3s;
            pointer-events: auto;
        }
        
        .icon:hover {
            color: #5b6b46;
        }
        
        .badge { 
            position: absolute; 
            top: -8px; 
            right: -10px; 
            background: #e74c3c; 
            color: white; 
            border-radius: 50%; 
            font-size: 12px; 
            padding: 2px 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
        }
        
        /* Menu Panel Styles */
        .menu-panel {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            width: 250px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow: hidden;
        }
        
        .menu-item {
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .menu-item:last-child {
            border-bottom: none;
        }
        
        .menu-item:hover {
            background-color: #f8f8f8;
        }
        
        .menu-item a {
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Notification Panel */
        .notification-panel {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            width: 320px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 10000;
            max-height: 240px; /* 4 notifications (each ~60px) */
            overflow-y: auto;
        }
        
        .notification-header {
            padding: 12px 16px;
            background: #fff;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 2;
            background: #fff;
        }
        
        .notification-header a {
            font-size: 12px;
            color: #5b6b46;
            text-decoration: none;
            font-weight: 500;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s, border-left 0.3s;
        }
        .notification-item.bg-light {
            background: #fffbe6 !important;
            border-left: 4px solid #ffd700;
            font-weight: 600;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item strong {
            display: block;
            margin-bottom: 4px;
            color: #333;
        }
        
        .notification-item p {
            margin: 0;
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 12px 20px;
                flex-wrap: wrap;
            }
            
            nav {
                order: 3;
                width: 100%;
                margin-top: 15px;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            nav a {
                margin: 0 8px;
                font-size: 14px;
            }
            
            .btn-style {
                padding: 6px 12px;
                font-size: 14px;
            }
            
            .notification-panel {
                width: 280px;
                right: 10px;
            }
        }

        /* Floating Chatbot Styles */
        .chatbot-toggle {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #5b6b46;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 10000;
        }
        .chatbot-toggle i { font-size: 22px; }

        .chatbot-window {
            position: fixed;
            right: 20px;
            bottom: 88px;
            width: 320px;
            max-height: 420px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 10000;
        }
        .cb-header {
            padding: 12px 14px;
            background: #d9e6a7;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cb-close { background: transparent; border: none; cursor: pointer; color: #333; font-size: 18px; padding: 4px 8px; transition: color 0.2s; }
        .cb-close:hover { color: #e74c3c; }
        .cb-messages { padding: 12px; gap: 8px; display: flex; flex-direction: column; overflow-y: auto; height: 230px; }
        .cb-msg { padding: 8px 12px; border-radius: 10px; max-width: 85%; line-height: 1.35; font-size: 14px; }
        .cb-bot { background: #f2f5ea; color: #333; align-self: flex-start; }
        .cb-user { background: #5b6b46; color: #fff; align-self: flex-end; }
        /* Suggestions dropdown */
        .cb-chips { position: absolute; left: 12px; right: 12px; bottom: 64px; padding: 10px 0; border-radius: 10px; background: #1f2430; color: #fff; border: 1px solid #2a2f3b; box-shadow: 0 10px 24px rgba(0,0,0,0.35); display: none; max-height: 260px; overflow-y: auto; z-index: 10001; }
        .cb-chips::after { content: ''; position: absolute; left: 16px; bottom: -8px; width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid #1f2430; }
        .cb-suggestions { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
        .cb-suggestion { padding: 12px 14px; cursor: pointer; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .cb-suggestion:last-child { border-bottom: none; }
        .cb-suggestion:hover, .cb-suggestion:focus { background: #2a3140; outline: none; }
        .cb-input {
            display: flex;
            gap: 8px;
            border-top: 1px solid #eee;
            padding: 8px;
            position: relative;
        }
        .cb-input input { flex: 1; padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .cb-input .cb-send { background: #5b6b46; color: #fff; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; }
        .cb-input .cb-toggle { background: #fff; border: 1px solid #ddd; color: #333; border-radius: 8px; padding: 8px 10px; cursor: pointer; }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="home.php">MTC Clothing</a>
        </div>
        <nav>
            <a href="home.php" <?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'class="active"' : ''; ?>>HOME</a>
            <a href="women.php" <?php echo basename($_SERVER['PHP_SELF']) == 'women.php' ? 'class="active"' : ''; ?>>WOMEN</a>
            <a href="men.php" <?php echo basename($_SERVER['PHP_SELF']) == 'men.php' ? 'class="active"' : ''; ?>>MEN</a>
            <a href="subcon.php" <?php echo basename($_SERVER['PHP_SELF']) == 'subcon.php' ? 'class="active"' : ''; ?>>SUB-CON</a>
        </nav>
        <div class="nav-right">
            <a href="customization.php" class="btn-style" style="text-decoration: none; color: inherit; display: inline-block;">Create Your Style</a>
            
            <!-- Notification Button -->
            <div class="icon" id="notifBtn">
                <i class="fas fa-bell"></i>
                
                <!-- Notification Dropdown -->
                <div class="notification-panel" id="notifPanel">
                    <div class="notification-header">
                        <span>Notifications</span>
                        <a href="#">Mark all as Read</a>
                    </div>
                    <div class="notification-item">
                        <strong>Your order is on its way</strong>
                        <p>Your order is currently on its way and will be delivered within the expected timeframe.</p>
                    </div>
                    <div class="notification-item">
                        <strong>New Arrival Alert!</strong>
                        <p>A new item just arrived in our collection! Feel free to check it out.</p>
                    </div>
                    <div class="notification-item">
                        <strong>Back in Stock</strong>
                        <p>Your favorite item just got restocked.</p>
                    </div>
                </div>
            </div>
            
            <!-- Profile Button -->
            <div class="icon">
                <a href="profile.php" title="My Profile" style="color: #333;">
                    <i class="fas fa-user"></i>
                </a>
            </div>
            
            <!-- Cart Button -->
            <div class="icon" id="cartIcon" title="Shopping Cart">
                <a href="cart.php" style="color: #333;">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cartCount" class="badge" style="display: none;">0</span>
                </a>
            </div>
            
            <!-- Mobile Menu Button -->
            <div class="icon" id="menuBtn">
                <i class="fas fa-bars"></i>
            </div>
            
            <!-- Mobile Menu Panel -->
            <div class="menu-panel" id="menuPanel">
                <div class="menu-item">
                    <a href="about.php">
                        <i class="fas fa-info-circle"></i> About Us
                    </a>
                </div>
                
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                    <!-- Show these menu items only when user is logged in -->
                    <div class="menu-item" style="border-top: 1px solid #eee;">
                        <a href="profile.php">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="password.php">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="my_orders.php">
                            <i class="fas fa-box"></i> My Orders
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="wishlist.php">
                            <i class="fas fa-heart"></i> Wishlist
                        </a>
                    </div>
                    <div class="menu-item" style="border-top: 1px solid #eee;">
                        <a href="logout.php" style="color: #e74c3c;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Show these menu items only when user is NOT logged in -->
                    <div class="menu-item" style="border-top: 1px solid #eee;">
                        <a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Log In
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="signup.php">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <!-- Floating Chatbot Markup -->
    <button id="chatbot-toggle" class="chatbot-toggle" aria-expanded="false" aria-controls="chatbot-window" title="Chat with us">
        <i class="fas fa-comments"></i>
    </button>
    <div id="chatbot-window" class="chatbot-window" role="dialog" aria-labelledby="chatbot-title" aria-modal="false">
        <div class="cb-header">
            <span id="chatbot-title">Help & FAQ</span>
            <button id="chatbot-close" class="cb-close" aria-label="Close" onclick="document.getElementById('chatbot-window').style.display='none';">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="chatbot-messages" class="cb-messages"></div>
        <div id="chatbot-chips" class="cb-chips" aria-hidden="true"></div>
        <div class="cb-input">
            <input id="chatbot-input" type="text" placeholder="Type your question..." />
            <button id="chatbot-toggle-suggestions" class="cb-toggle" type="button" title="Suggestions"><i class="fas fa-caret-down"></i></button>
            <button id="chatbot-send" class="cb-send" type="button" title="Send"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Include our cart JavaScript -->
    <script src="js/cart.js"></script>
    <!-- Include Chatbot JavaScript -->
    <script src="js/chatbot.js"></script>
    
    <script>
    // Toggle notification panel
    document.getElementById('notifBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        const panel = document.getElementById('notifPanel');
        const menuPanel = document.getElementById('menuPanel');
        
        // Close menu panel if open
        if (menuPanel.style.display === 'block') {
            menuPanel.style.display = 'none';
        }
        
        // Toggle notification panel
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
    });
    
    // Toggle menu panel
    document.getElementById('menuBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        const panel = document.getElementById('menuPanel');
        const notifPanel = document.getElementById('notifPanel');
        
        // Close notification panel if open
        if (notifPanel.style.display === 'block') {
            notifPanel.style.display = 'none';
        }
        
        // Toggle menu panel
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
    });
    
    // Close panels when clicking outside
    document.addEventListener('click', function() {
        document.getElementById('notifPanel').style.display = 'none';
        document.getElementById('menuPanel').style.display = 'none';
    });
    
    // Prevent panel from closing when clicking inside it
    document.querySelectorAll('.notification-panel, .menu-panel').forEach(panel => {
        panel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Open chatbot window when admin_reply notif is clicked
    function openChatbotFromNotif() {
        var toggle = document.getElementById('chatbot-toggle');
        var wnd = document.getElementById('chatbot-window');
        if (wnd && wnd.style.display !== 'flex') {
            toggle && toggle.click();
        }
        // Optionally, scroll to bottom of chatbot messages
        setTimeout(function() {
            var msgs = document.getElementById('chatbot-messages');
            if (msgs) msgs.scrollTop = msgs.scrollHeight;
        }, 400);
    }
    // --- USER NOTIFICATION POLLING ---
    function renderNotifications(notifs) {
        const notifPanel = document.getElementById('notifPanel');
        let notifHeader = notifPanel.querySelector('.notification-header');
        // Rebuild header to ensure event is always attached
        notifPanel.innerHTML = `<div class="notification-header">
            <span>Notifications</span>
            <a href="#" id="markAllReadLink">Mark all as Read</a>
        </div>`;
        notifHeader = notifPanel.querySelector('.notification-header');
        let notifHtml = '';
        if (!notifs.length) {
            notifHtml = `<div class='notification-item text-center text-muted'>No notifications yet.</div>`;
        } else {
            notifHtml = notifs.map(n => {
                // Try to extract order number from message
                let orderMatch = n.message.match(/#(\d{6})/);
                let orderId = orderMatch ? orderMatch[1] : null;
                let linkStart = orderId ? `<a href='my_orders.php?highlight=${orderId}' class='notif-link' data-order='${orderId}' style='text-decoration:none;color:inherit;'>` : '';
                let linkEnd = orderId ? '</a>' : '';
                let clickAttr = n.type === 'admin_reply' ? "onclick=\"openChatbotFromNotif()\" style='cursor:pointer;'" : '';
                return `
                    ${linkStart}
                    <div class=\"notification-item${n.is_read == 0 ? ' bg-light' : ''}\" ${clickAttr} style='position:relative;'>\n                        <strong>${n.type === 'order_status' ? 'Order Update' : n.type === 'admin_reply' ? 'Admin Reply' : 'Notification'}</strong>\n                        <p>${n.message}</p>\n                        <small style='color:#999;'>${new Date(n.created_at).toLocaleString()}</small>\n                        <button class='notif-delete-btn' data-id='${n.id}' title='Delete' style='position:absolute;top:8px;right:8px;background:none;border:none;color:#d9534f;cursor:pointer;font-size:15px;'><i class=\"fas fa-trash\"></i></button>\n                    </div>\n                    ${linkEnd}
                `;
            }).join('');
        }
        notifPanel.innerHTML += notifHtml;
        // Add event listener for delete notification
        const deleteBtns = document.querySelectorAll('.notif-delete-btn');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const notifId = btn.getAttribute('data-id');
                if (window.confirm('Are you sure you want to delete this notification?')) {
                    fetch('delete_notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + encodeURIComponent(notifId)
                    })
                    .then(res => res.json())
                    .then(data => { if (data.success) pollUserNotifications(); });
                }
            });
        });
        // Add event listener for mark all as read
        const markAll = document.getElementById('markAllReadLink');
        if (markAll) {
            markAll.onclick = function(e) {
                e.preventDefault();
                fetch('mark_all_notifications_read.php', { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            pollUserNotifications();
                            // Close notification panel if open
                            const panel = document.getElementById('notifPanel');
                            if (panel.style.display === 'block') {
                                panel.style.display = 'none';
                            }
                        }
                    });
            };
        }
    }
    function pollUserNotifications() {
        fetch('get_user_notifications.php')
            .then(res => res.json())
            .then(data => {
                if (!data || !data.notifications) return;
                renderNotifications(data.notifications);
                // Update badge
                const unreadCount = data.notifications.filter(n => n.is_read == 0).length;
                let badge = document.querySelector('#notifBtn .badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge';
                    document.getElementById('notifBtn').appendChild(badge);
                }
                badge.textContent = unreadCount > 0 ? unreadCount : '';
                badge.style.display = unreadCount > 0 ? 'flex' : 'none';
            });
    }
    setInterval(pollUserNotifications, 5000);
    pollUserNotifications();
    // --- END USER NOTIFICATION POLLING ---

    // Update cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        // This will be handled by cart.js
    });
    </script>