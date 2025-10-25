# Subcontract 5-Status Workflow Implementation

## Overview
Updated subcontract workflow to match customization with 5 detailed status stages.

## Status Workflow

### 1. **PENDING** (`submitted`)
- **Description:** Kakasend lang ng client ng order
- **Color:** Yellow (#ffc107)
- **Icon:** Clock
- **Admin Action:** Set price → moves to APPROVED

### 2. **AWAITING CONFIRMATION** (`approved`)
- **Description:** Waiting for customer to agree on price then payment
- **Color:** Cyan (#17a2b8)
- **Icon:** Hourglass
- **Customer Action:** Accept price & checkout → moves to VERIFYING

### 3. **VERIFYING** (`verifying`)
- **Description:** Verifying the payment of client
- **Color:** Blue (#007bff)
- **Icon:** Search Dollar
- **Admin Action:** Verify payment → moves to IN PROGRESS

### 4. **IN PROGRESS** (`in_progress`)
- **Description:** Doing of the request
- **Color:** Purple (#6f42c1)
- **Icon:** Cog/Spinner
- **Admin Action:** Complete work → moves to COMPLETED

### 5. **COMPLETED** (`completed`)
- **Description:** Complete order
- **Color:** Green (#28a745)
- **Icon:** Check Circle
- **Final Status**

## Complete Flow

```
SUBMITTED → APPROVED → VERIFYING → IN PROGRESS → COMPLETED
  (yellow)   (cyan)      (blue)      (purple)      (green)
     ↓          ↓           ↓            ↓
                  CANCELLED (red) - anytime
```

## Files Modified

### 1. `admin/orders.php`
**Statistics Queries (Lines 48-95):**
- Added 5 separate status counts
- Each status has its own query

**Statistics Cards (Lines 973-1029):**
- 5 status cards + 1 "All Requests" card
- Each card links to filtered view

**CSS Styles:**
- Added `.card-approved`, `.card-verifying`, `.card-inprogress`
- Added `.status-in_progress` badge style

### 2. `admin/view_subcontract.php`
**Action Buttons (Lines 333-370):**
- `submitted`: Set price button
- `approved`: Waiting message
- `verifying`: Mark as In Progress button
- `in_progress`: Mark as Completed button
- `completed`: Success message

### 3. `admin/update_subcontract_status.php`
- Already supports `in_progress` status
- Valid statuses: submitted, pending, approved, verifying, in_progress, completed, cancelled

## Status Badge Colors

| Status | Background | Text | Usage |
|--------|-----------|------|-------|
| submitted | `#fff3cd` | `#856404` | Yellow - New request |
| approved | `#d1ecf1` | `#0c5460` | Cyan - Price set |
| verifying | `#cfe2ff` | `#084298` | Blue - Payment check |
| in_progress | `#e7d6f5` | `#6f42c1` | Purple - Work ongoing |
| completed | `#d4edda` | `#155724` | Green - Done |
| cancelled | `#f8d7da` | `#721c24` | Red - Cancelled |

## Admin Dashboard View

```
┌─────────────┬──────────────────────┬────────────┬─────────────┬───────────┐
│   Pending   │ Awaiting Confirmation│  Verifying │ In Progress │ Completed │
│      0      │          0           │     0      │      0      │     0     │
└─────────────┴──────────────────────┴────────────┴─────────────┴───────────┘
```

## Comparison with Customization

Both now have identical 5-status workflow:
- ✅ Pending (submitted)
- ✅ Awaiting Confirmation (approved)
- ✅ Verifying (verifying)
- ✅ In Progress (in_progress)
- ✅ Completed (completed)

## Testing Checklist

- [ ] Submit new subcontract request → Status: submitted
- [ ] Admin sets price → Status: approved
- [ ] Customer accepts & pays → Status: verifying
- [ ] Admin verifies payment → Status: in_progress
- [ ] Admin completes work → Status: completed
- [ ] All status cards show correct counts
- [ ] Clicking each card filters correctly
- [ ] Status badges display with correct colors

## Notes

- Ang workflow ay **exactly same** sa customization
- Mas detailed ang status tracking
- Clear ang flow from start to finish
- Each status may specific action na kailangan
