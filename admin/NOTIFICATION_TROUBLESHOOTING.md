# Notification Troubleshooting Guide

## Problem: Walang lumalabas na notification

### Step 1: Check kung may pending orders
1. Open: `http://localhost/capstone_php/admin/test_notifications.php`
2. Tignan kung may pending orders sa table
3. Kung wala, gumawa ng bagong order from customer side

### Step 2: Check Browser Console
1. Open admin dashboard
2. Press F12 (Developer Tools)
3. Go to Console tab
4. Tignan kung may error messages
5. Dapat makita mo: `[Notifications] Checking for new notifications...`

### Step 3: Check Network Tab
1. Press F12
2. Go to Network tab
3. Refresh page
4. Look for `get_notifications.php` request
5. Click it and check:
   - Status should be 200
   - Response should show JSON with notifications

### Step 4: Test API Directly
1. Open: `http://localhost/capstone_php/admin/get_notifications.php`
2. Should show JSON response like:
```json
{
  "success": true,
  "count": 1,
  "notifications": [...]
}
```

### Step 5: Clear Session
1. Go to: `http://localhost/capstone_php/admin/test_notifications.php`
2. Click "Clear Session (Reset Notifications)"
3. Refresh dashboard

## Common Issues

### Issue 1: "Unauthorized" error
**Cause:** Hindi naka-login as admin
**Fix:** Login muna sa admin account

### Issue 2: Notifications not updating
**Cause:** Session timestamp is set too recent
**Fix:** 
- Click "Clear All" button sa notification dropdown
- Or visit test_notifications.php and click "Clear Session"

### Issue 3: No orders showing
**Cause:** Walang pending orders sa database
**Fix:** 
- Create new order from customer side
- Make sure order status is 'pending'

### Issue 4: JavaScript not loading
**Cause:** File path issue
**Fix:** 
- Check if `admin/js/notifications.js` exists
- Check browser console for 404 errors

### Issue 5: Badge not appearing
**Cause:** CSS not loading or no notifications
**Fix:**
- Check if notification count > 0
- Inspect element and check if badge has `display: none`

## Debug Checklist

✓ XAMPP is running
✓ Logged in as admin
✓ Orders table has pending orders
✓ Browser console shows no errors
✓ get_notifications.php returns valid JSON
✓ notifications.js is loaded
✓ Bell icon is visible in header

## Testing Steps

1. **Create Test Order:**
   - Go to customer checkout
   - Complete an order
   - Status should be 'pending'

2. **Check Database:**
   ```sql
   SELECT * FROM orders WHERE status='pending' ORDER BY created_at DESC LIMIT 5;
   ```

3. **Clear Session:**
   - Visit: `admin/test_notifications.php`
   - Click "Clear Session"

4. **Refresh Dashboard:**
   - Go to: `admin/dashboard.php`
   - Wait 5 seconds
   - Check console logs
   - Check if badge appears

5. **Manual API Test:**
   - Visit: `admin/get_notifications.php`
   - Should see JSON with notifications

## Quick Fixes

### Fix 1: Reset Everything
```
1. Visit: admin/test_notifications.php
2. Click "Clear Session"
3. Close browser completely
4. Open new browser window
5. Login to admin
6. Go to dashboard
```

### Fix 2: Force Check
Open browser console and run:
```javascript
checkNotifications();
```

### Fix 3: Check if function exists
Open browser console and run:
```javascript
console.log(typeof checkNotifications);
// Should output: "function"
```

## Files to Check

1. **Backend:**
   - `admin/get_notifications.php` - API endpoint
   - `admin/clear_notifications.php` - Clear endpoint

2. **Frontend:**
   - `admin/js/notifications.js` - Main logic
   - `admin/css/notifications.css` - Styling

3. **Dashboard:**
   - `admin/dashboard.php` - Should include notification bell

## Contact Points

If still not working, check:
1. PHP error logs
2. Browser console errors
3. Network tab for failed requests
4. Database connection

## Updated Behavior (Latest Changes)

✅ **Changed:** Notifications now show from last 24 hours on first load
✅ **Changed:** Notifications persist until you click "Clear All"
✅ **Added:** Console logging for debugging
✅ **Added:** Test page at `test_notifications.php`

## How to Use Test Page

1. Visit: `http://localhost/capstone_php/admin/test_notifications.php`
2. Check "Recent Pending Orders" section
3. Click "Test get_notifications.php" button
4. See API response
5. Use "Clear Session" to reset

This will help you see exactly what's happening!
