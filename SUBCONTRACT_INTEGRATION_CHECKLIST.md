# Subcontract Integration Checklist

## ✅ Database Schema

### Required Migration
**File:** `migrations/update_subcontract_status_enum.sql`
**Run:** `run_subcontract_status_migration.php`

**Status Flow:**
1. `pending` - Initial submission by customer
2. `awaiting_confirmation` - Admin sets price, waiting for customer acceptance
3. `in_progress` - Customer accepted & paid, work in progress
4. `to_deliver` - Ready for delivery
5. `completed` - Delivered and completed
6. `cancelled` - Cancelled by customer or admin

### Required Columns (from `20251017_add_price_acceptance_to_subcontract.sql`)
- `price` DECIMAL(10,2) - Admin-set quoted price
- `admin_notes` TEXT - Admin notes
- `payment_method` VARCHAR(50) - 'cod' or 'gcash'
- `final_delivery_mode` VARCHAR(50) - Delivery method
- `accepted_at` DATETIME - When customer accepted price
- `rejected_at` DATETIME - When customer rejected price
- `rejection_reason` TEXT - Reason for rejection
- `cancelled_at` DATETIME - When cancelled
- `cancel_reason` TEXT - Cancellation reason

---

## ✅ Frontend Files

### 1. **subcon.php** - Subcontract Request Form
- Allows customers to submit subcontract requests
- Uploads design files (max 5)
- Stores with status = 'pending'
- Redirects to `my_orders.php` after submission

### 2. **my_orders.php** - Customer Order Dashboard
**Key Features:**
- Displays subcontract requests in dedicated tab
- Shows status-specific alerts and actions
- Handles price acceptance/rejection
- Opens checkout modal for accepted prices

**JavaScript Functions:**
- `openSubcontractCheckout(requestId, price, whatFor, quantity)` - Opens checkout modal
- `rejectSubcontractPrice(requestId)` - Declines price quote
- `cancelSubcontractRequest(requestId)` - Cancels pending request

**Status Display Logic:**
- **pending**: Show "Cancel Request" button
- **awaiting_confirmation**: Show quoted price + Accept/Decline buttons
- **in_progress**: Show "Being processed" message
- **to_deliver**: Show "Ready for delivery" message
- **completed**: Show "Completed" message
- **cancelled**: Show cancellation reason

### 3. **js/orders_polling.js** - Real-time Updates
- Polls `get_orders_data.php` every 10 seconds
- Updates subcontract cards dynamically
- Renders status-specific UI elements
- Function: `renderSubcontractCard(subcon)`

---

## ✅ Backend Processing Files

### 1. **process_subcontract_order.php** - Checkout Handler
**Purpose:** Processes customer acceptance and payment

**Flow:**
1. Validates request is in `awaiting_confirmation` status
2. Collects billing/shipping info
3. Calculates shipping fee (JNT: ₱100-150, Lalamove: ₱0, Pickup: ₱0)
4. **If GCash:** Stores in `$_SESSION['pending_subcontract_gcash']` → Redirects to `gcash.php`
5. **If COD:** Updates status to `in_progress` immediately

**Session Structure (GCash):**
```php
$_SESSION['pending_subcontract_gcash'] = [
    'request_id' => $subcontract_id,
    'user_id' => $user_id,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
    'city' => $city,
    'region' => $region,
    'payment_method' => 'gcash',
    'delivery_mode' => $delivery_mode,
    'price' => $subcontract['price'],
    'shipping_fee' => $shipping_fee,
    'total_amount' => $total_amount
];
```

### 2. **gcash.php** - GCash Payment Handler
**Subcontract Integration:**
- Detects `$_SESSION['pending_subcontract_gcash']`
- Shows 5-minute timer (3 attempts max)
- On successful payment:
  - Updates status to `in_progress`
  - Sets `accepted_at = NOW()`
  - Stores payment method
  - Redirects to `my_orders.php#subcontract`
  - Sets `$_SESSION['subcontract_success']` message

**Key Code Sections:**
- Lines 15-29: Session detection
- Lines 47-69: Timeout/attempt cleanup
- Lines 457-465: Subcontract order update
- Lines 528-531: Success redirect
- Lines 553-561: Amount display

### 3. **confirm_subcontract_price.php** - Price Rejection Handler
**Purpose:** Handles customer declining the price quote

**Flow:**
1. Validates request is in `awaiting_confirmation` status
2. Updates status to `cancelled`
3. Returns JSON success response

### 4. **cancel_subcontract.php** - Request Cancellation
**Purpose:** Allows customer to cancel pending requests

**Requirements:**
- Request must be in `pending` status
- Requires cancellation reason (min 10 chars)
- Sets `cancelled_at` timestamp

### 5. **get_orders_data.php** - Data Fetcher for Polling
**Returns JSON:**
```json
{
    "success": true,
    "orders": [...],
    "subcontracts": [...],
    "customizations": [...],
    "timestamp": 1234567890
}
```

---

## ✅ Admin Files

### 1. **admin/update_subcontract_status.php** - Admin Status Manager
**Purpose:** Admin updates subcontract status and sets price

**Capabilities:**
- Set price and admin notes
- Change status to `awaiting_confirmation` (with price)
- Change status to `in_progress`, `to_deliver`, `completed`, `cancelled`
- Transaction-safe updates

**Valid Status Transitions:**
- `pending` → `awaiting_confirmation` (admin sets price)
- `awaiting_confirmation` → `in_progress` (admin confirms payment)
- `in_progress` → `to_deliver` (ready for delivery)
- `to_deliver` → `completed` (delivered)
- Any → `cancelled` (admin cancels)

### 2. **admin/get_new_subcontracts.php** - Admin Data Fetcher
**Features:**
- Filters by status: `pending`, `awaiting_confirmation`, `in_progress`, `to_deliver`, `completed`, `cancelled`
- Returns statistics for each status
- Used by admin dashboard for real-time updates

### 3. **admin/orders.php** - Admin Dashboard
- Displays all subcontract requests
- Allows setting price and status
- Shows customer details and design files

---

## ✅ Complete Workflow

### Customer Journey:

1. **Submit Request** (`subcon.php`)
   - Fill form with details
   - Upload design files
   - Status: `pending`

2. **Wait for Admin** (`my_orders.php`)
   - View request in "Subcontract" tab
   - Can cancel if still pending

3. **Admin Sets Price** (Admin Dashboard)
   - Admin reviews request
   - Sets price and notes
   - Status: `awaiting_confirmation`

4. **Customer Receives Quote** (`my_orders.php` + polling)
   - Page auto-updates with quoted price
   - Two options: Accept or Decline

5. **Accept Price** (`openSubcontractCheckout()`)
   - Opens checkout modal
   - Enter billing/shipping info
   - Choose payment method (COD/GCash)

6. **Payment Processing**
   - **COD:** `process_subcontract_order.php` → Status: `in_progress`
   - **GCash:** `gcash.php` → Payment → Status: `in_progress`

7. **Production** (Admin updates)
   - Admin changes status to `to_deliver` when ready

8. **Completion**
   - Admin marks as `completed` after delivery

### Admin Journey:

1. View new requests (status: `pending`)
2. Review details and design files
3. Set price and change status to `awaiting_confirmation`
4. Wait for customer acceptance
5. After payment confirmed, status becomes `in_progress`
6. Update to `to_deliver` when ready
7. Mark as `completed` after delivery

---

## ✅ Session Variables Used

1. `$_SESSION['pending_subcontract_gcash']` - GCash payment data
2. `$_SESSION['subcontract_success']` - Success message after payment
3. `$_SESSION['gcash_payment_attempts']` - Payment attempt counter
4. `$_SESSION['gcash_payment_start_time']` - Payment timer

---

## ✅ Key Integration Points

### Database Connections:
- `subcontract_requests` table
- Status enum with 6 values
- Price acceptance columns

### File Dependencies:
```
subcon.php
    ↓
my_orders.php ← get_orders_data.php ← js/orders_polling.js
    ↓
process_subcontract_order.php
    ↓
gcash.php (if GCash) OR direct to in_progress (if COD)
    ↓
my_orders.php#subcontract (success)
```

### Admin Flow:
```
admin/orders.php
    ↓
admin/update_subcontract_status.php (set price)
    ↓
Status: awaiting_confirmation
    ↓
Customer accepts via my_orders.php
    ↓
process_subcontract_order.php
    ↓
Status: in_progress
```

---

## ⚠️ Important Notes

1. **Run Migration First:** Execute `run_subcontract_status_migration.php` to update status enum
2. **Session Management:** GCash sessions cleared after 5 minutes or 3 failed attempts
3. **Price Required:** Admin must set price before status can be `awaiting_confirmation`
4. **Payment Methods:** 
   - COD: Available for Pickup and Lalamove
   - GCash: Available for all delivery methods
   - JNT: GCash only (COD disabled)
5. **Shipping Fees:**
   - Pickup: ₱0
   - Lalamove: ₱0 (tentative)
   - JNT: ₱100 (Luzon), ₱130 (Visayas), ₱150 (Mindanao)

---

## ✅ Testing Checklist

- [ ] Run `run_subcontract_status_migration.php`
- [ ] Submit new subcontract request
- [ ] Admin sets price and changes to `awaiting_confirmation`
- [ ] Customer sees price quote in my_orders.php
- [ ] Test Accept with COD payment
- [ ] Test Accept with GCash payment
- [ ] Test Decline price quote
- [ ] Test Cancel pending request
- [ ] Verify polling updates work
- [ ] Check admin dashboard displays correctly
- [ ] Test all status transitions

---

## 🎯 All Files Modified/Created

### Created:
1. `migrations/update_subcontract_status_enum.sql`
2. `run_subcontract_status_migration.php`
3. `SUBCONTRACT_INTEGRATION_CHECKLIST.md`

### Modified:
1. `my_orders.php` - Added success message, JS functions
2. `js/orders_polling.js` - Updated renderSubcontractCard()
3. `gcash.php` - Added subcontract payment handling
4. `process_subcontract_order.php` - Fixed status and session
5. `confirm_subcontract_price.php` - Fixed status check

### Existing (No changes needed):
1. `subcon.php` - Working correctly
2. `cancel_subcontract.php` - Working correctly
3. `get_orders_data.php` - Working correctly
4. `admin/update_subcontract_status.php` - Working correctly
5. `admin/get_new_subcontracts.php` - Working correctly
