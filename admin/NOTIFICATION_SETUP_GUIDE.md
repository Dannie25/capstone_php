# Quick Setup Guide - Admin Notification System

## ✅ What's Been Implemented

Nag-add na ako ng notification system sa admin dashboard. Makikita mo yung bell icon sa upper right corner ng header.

## 🎯 Features

1. **Real-time Notifications** - Every 30 seconds, automatic check for:
   - New orders (status = 'pending')
   - Cancelled orders (status = 'cancelled')

2. **Visual Indicators**:
   - 🔔 Bell icon sa header
   - 🔴 Red badge with count ng notifications
   - 📋 Dropdown list ng lahat ng notifications

3. **Interactive**:
   - Click sa bell = open dropdown
   - Click sa notification = redirect to orders page
   - "Clear All" button = reset notifications

4. **Browser Notifications** (optional):
   - Desktop notifications kahit naka-minimize ang browser
   - Need lang i-allow ng user

## 📁 Files Created

### Backend (PHP):
- `admin/get_notifications.php` - API for fetching notifications
- `admin/clear_notifications.php` - API for clearing notifications

### Frontend (JS/CSS):
- `admin/js/notifications.js` - Main notification logic
- `admin/css/notifications.css` - Notification styling
- `admin/includes/notification_bell.php` - Reusable HTML component

### Documentation:
- `admin/NOTIFICATION_SYSTEM.md` - Complete technical documentation
- `admin/NOTIFICATION_SETUP_GUIDE.md` - This file

## 🚀 How to Use

### For Admin Users:
1. Login sa admin dashboard
2. Tingnan ang bell icon sa upper right
3. Pag may new order or cancellation, lalabas ang red badge
4. Click sa bell para makita ang notifications
5. Click sa notification para pumunta sa order details

### Testing:
1. Open admin dashboard: `http://localhost/capstone_php/admin/dashboard.php`
2. Create a new order from customer side
3. Wait 30 seconds or refresh page
4. Bell icon should show notification badge
5. Click bell to see notification details

## 🔧 Adding to Other Admin Pages

Para i-add ang notification bell sa ibang admin pages:

```php
<!-- Add sa <head> section: -->
<link rel="stylesheet" href="css/notifications.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- Add sa header kung saan mo gusto ilagay ang bell: -->
<?php include 'includes/notification_bell.php'; ?>

<!-- Add before closing </body> tag: -->
<script src="js/notifications.js"></script>
```

## ⚙️ Configuration

### Change Polling Interval
Sa `js/notifications.js`, line 9:
```javascript
setInterval(checkNotifications, 30000); // 30000ms = 30 seconds
```

### Customize Notification Types
Edit `get_notifications.php` to add more types:
- Subcontract requests
- Custom orders
- Low stock alerts
- etc.

## 🐛 Troubleshooting

### Notifications hindi lumalabas:
1. Check if XAMPP is running
2. Check browser console for errors (F12)
3. Test API directly: `http://localhost/capstone_php/admin/get_notifications.php`
4. Check if logged in as admin

### Badge hindi nag-uupdate:
1. Wait 30 seconds for auto-refresh
2. Check network tab (F12) if polling is working
3. Clear browser cache

### Browser notifications hindi gumagana:
1. Check if browser notification permission is allowed
2. Some browsers need HTTPS for notifications
3. Check browser notification settings

## 📊 How It Works

1. **Session Tracking**: 
   - System tracks last check time sa PHP session
   - `$_SESSION['last_notification_check']`

2. **Database Query**:
   - New orders: `WHERE status='pending' AND created_at > last_check`
   - Cancelled: `WHERE status='cancelled' AND cancelled_at > last_check`

3. **Frontend Polling**:
   - Every 30 seconds, call `get_notifications.php`
   - Update badge count and dropdown list
   - Show browser notification if new items

## 🎨 Customization

### Colors:
Edit `css/notifications.css`:
- Bell color: `.notification-bell { color: #5b6b46; }`
- Badge color: `.notification-badge { background-color: #dc3545; }`
- Header color: `.notification-header { background: #5b6b46; }`

### Icons:
Using Bootstrap Icons. Change sa HTML:
- Bell: `<i class="bi bi-bell-fill"></i>`
- New order: `<i class="bi bi-bag-check-fill"></i>`
- Cancelled: `<i class="bi bi-x-circle-fill"></i>`

## 📝 Next Steps (Optional Enhancements)

1. Add sound notification
2. Add notification for subcontract requests
3. Add notification for custom orders
4. Email notifications
5. SMS notifications
6. Mark individual notifications as read
7. Notification history page

## 🔒 Security

- All API endpoints check admin authentication
- SQL injection protection (prepared statements)
- XSS protection (proper HTML escaping)
- Session-based access control

## ✨ Summary

Tapos na ang notification system! Pwede mo na i-test sa dashboard. Pag may new order or cancellation, automatic lalabas sa bell icon. Click lang para makita ang details.

Para sa technical details, basahin ang `NOTIFICATION_SYSTEM.md`.
