# Subcontract Status Fix - Admin Orders Page

## Problem
Ang status badges sa admin orders page ay nag-show ng 0 dahil gumagamit pa ng old status names (`pending`, `in_progress`).

## Solution
Updated ang `admin/orders.php` para gumamit ng bagong workflow statuses.

## Status Mapping

### Display Categories
1. **Pending Requests** = `submitted` + `approved`
   - `submitted` - Customer just submitted, waiting for admin to set price
   - `approved` - Admin set price, waiting for customer to accept

2. **In Progress** = `verifying`
   - Customer accepted price and paid, admin verifying payment

3. **Completed** = `completed`
   - Order completed

4. **Cancelled** = `cancelled`
   - Order cancelled

## Changes Made

### 1. Statistics Queries (Lines 55-65)
```php
// OLD
WHERE status = 'pending'
WHERE status = 'in_progress'

// NEW
WHERE status IN ('submitted', 'approved')  // Pending
WHERE status = 'verifying'                 // In Progress
```

### 2. Filter Logic (Lines 117-130)
```php
if ($subcontract_status_filter === 'pending') {
    // Show both submitted and approved
    $subcontract_query .= " WHERE status IN ('submitted', 'approved')";
} elseif ($subcontract_status_filter === 'in_progress') {
    // Show verifying
    $subcontract_query .= " WHERE status = 'verifying'";
}
```

### 3. CSS Status Badges (Lines 583-596)
Added new status colors:
- `.status-submitted` - Yellow (same as pending)
- `.status-approved` - Light blue
- `.status-verifying` - Blue

## Status Colors

| Status | Color | Background | Text |
|--------|-------|------------|------|
| Submitted | Yellow | `#fff3cd` | `#856404` |
| Approved | Light Blue | `#d1ecf1` | `#0c5460` |
| Verifying | Blue | `#cfe2ff` | `#084298` |
| Completed | Green | `#d4edda` | `#155724` |
| Cancelled | Red | `#f8d7da` | `#721c24` |

## Testing

1. ✅ Pending count shows `submitted` + `approved` requests
2. ✅ In Progress count shows `verifying` requests
3. ✅ Clicking "Pending Requests" filters correctly
4. ✅ Clicking "In Progress" filters correctly
5. ✅ Status badges display with correct colors
6. ✅ Individual status badges show actual status (submitted/approved/verifying)

## Notes

- Ang display sa table ay nag-show pa rin ng actual status (submitted, approved, verifying)
- Ang grouping lang sa statistics cards ang nag-combine ng statuses
- This matches the customization workflow pattern
