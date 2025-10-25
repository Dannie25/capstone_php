# Admin Notification System

## Overview
This notification system alerts admin users about new orders and order cancellations in real-time.

## Features
- 🔔 Real-time notification bell icon in header
- 📊 Badge counter showing unread notifications
- 🔄 Auto-refresh every 30 seconds
- 🖱️ Click notifications to view order details
- 🗑️ Clear all notifications option
- 🌐 Browser push notifications (with permission)
- ⏰ Time-ago display for each notification

## Files Created

### Backend Files
1. **`admin/get_notifications.php`** - API endpoint that fetches new orders and cancellations
2. **`admin/clear_notifications.php`** - API endpoint to clear notification history

### Frontend Files
1. **`admin/js/notifications.js`** - JavaScript for notification functionality
2. **`admin/css/notifications.css`** - Styling for notification UI
3. **`admin/includes/notification_bell.php`** - Reusable HTML snippet for notification bell

## How It Works

### Backend Logic
- Tracks last notification check time in PHP session (`$_SESSION['last_notification_check']`)
- Queries database for:
  - New orders with status='pending' created after last check
  - Cancelled orders with cancelled_at timestamp after last check
- Returns JSON with notification count and details

### Frontend Logic
- Polls `get_notifications.php` every 30 seconds
- Updates badge counter and dropdown list
- Shows browser notifications for new items (requires user permission)
- Clicking a notification redirects to orders page with specific order ID

## Installation on Other Admin Pages

To add the notification bell to any admin page:

### Method 1: Include the snippet (Recommended)
```php
<!-- In the header section, add: -->
<link rel="stylesheet" href="css/notifications.css">

<!-- In the header HTML where you want the bell: -->
<?php include 'includes/notification_bell.php'; ?>

<!-- Before closing </body> tag: -->
<script src="js/notifications.js"></script>
```

### Method 2: Copy from dashboard.php
Copy the notification HTML and CSS from `dashboard.php` lines 266-394 (CSS) and 402-418 (HTML).

## Database Requirements

The system uses the existing `orders` table with these columns:
- `id` - Order ID
- `first_name`, `last_name` - Customer name
- `total_amount` - Order total
- `status` - Order status (pending, shipped, completed, cancelled)
- `created_at` - Order creation timestamp
- `cancelled_at` - Cancellation timestamp (nullable)

## Customization

### Change Polling Interval
In `js/notifications.js`, line 9:
```javascript
setInterval(checkNotifications, 30000); // 30000ms = 30 seconds
```

### Change Notification Types
Edit `get_notifications.php` to add more notification types:
- Subcontract requests
- Custom orders
- Low stock alerts
- Customer messages

### Styling
Edit `css/notifications.css` to match your admin theme colors.

## Browser Notification Permission

The system will request browser notification permission on first load. Users can:
- **Allow**: Receive desktop notifications even when browser is in background
- **Deny**: Only see in-app notifications (bell icon)

## Testing

1. Create a new order from the customer side
2. Check admin dashboard - notification badge should appear within 30 seconds
3. Click bell icon to see notification dropdown
4. Click notification to go to order details
5. Cancel an order - should also trigger notification

## Troubleshooting

### Notifications not appearing
- Check browser console for JavaScript errors
- Verify `get_notifications.php` is accessible (test URL directly)
- Check PHP session is working
- Verify database has `orders` table with required columns

### Badge not updating
- Check browser console network tab for polling requests
- Verify 30-second interval is running
- Clear browser cache and reload

### Browser notifications not working
- Check if user granted notification permission
- Verify HTTPS (some browsers require secure connection)
- Check browser notification settings

## Security Notes

- All API endpoints check for admin authentication
- SQL queries use prepared statements to prevent injection
- Session-based tracking prevents unauthorized access
- XSS protection through proper HTML escaping

## Future Enhancements

Possible improvements:
- WebSocket for instant notifications (no polling delay)
- Sound alerts for new notifications
- Mark individual notifications as read
- Filter notifications by type
- Notification history page
- Email notifications for critical events
