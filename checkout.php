<?php
session_start();
include 'db.php';
include 'cart_functions.php';
include 'includes/address_functions.php';
include_once 'includes/image_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Parse selected cart item IDs from query parameter (if any)
$selected_ids = [];
if (isset($_GET['items']) && trim($_GET['items']) !== '') {
    foreach (explode(',', $_GET['items']) as $id) {
        if ($id > 0) { $selected_ids[$id] = true; }
    }
    $selected_ids = array_keys($selected_ids); // unique ints
}

//# Get user's default address if exists
$default_address = getDefaultAddress($_SESSION['user_id']);

// Get all user addresses for address selector
$all_addresses = getCustomerAddresses($_SESSION['user_id']);

// If no default address, get user info from users table as fallback
if (!$default_address) {
    $stmt = $conn->prepare("SELECT name, email, phone, address, city, postal_code FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user_data = $result->fetch_assoc()) {
        $default_address = [
            'first_name' => $user_data['name'],
            'last_name' => '',
            'email' => $user_data['email'],
            'phone' => $user_data['phone'] ?? '',
            'address' => $user_data['address'] ?? '',
            'city' => $user_data['city'] ?? '',
            'postal_code' => $user_data['postal_code'] ?? ''
        ];
    }
}

// Get cart items
$cart_items = getCartItems();
// If selection provided, filter to only those items
if (!empty($selected_ids)) {
    $cart_items = array_values(array_filter($cart_items, function($it) use ($selected_ids) {
        return in_array((int)$it['id'], $selected_ids, true);
    }));
}

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as &$item) {
    // Fetch discount fields for each product
    $stmt = $conn->prepare("SELECT discount_enabled, discount_type, discount_value FROM products WHERE id = ?");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $stmt->bind_result($discount_enabled, $discount_type, $discount_value);
    $stmt->fetch();
    $stmt->close();
    $item['orig_price'] = $item['price'];
    if ($discount_enabled && $discount_type && $discount_value > 0) {
        if ($discount_type === 'percent') {
            $item['price'] = $item['orig_price'] * (1 - ($discount_value / 100));
            $item['discount_label'] = $discount_value . '% OFF';
        } else {
            $item['price'] = max($item['orig_price'] - $discount_value, 0);
            $item['discount_label'] = '₱' . number_format($discount_value, 2) . ' OFF';
        }
    } else {
        $item['discount_label'] = '';
    }
    $subtotal += $item['price'] * $item['quantity'];
}
unset($item);

// Delivery mode and region logic
$delivery_mode = isset($_POST['delivery_mode']) ? $_POST['delivery_mode'] : 'pickup';
$region_name = isset($_POST['region_select']) ? $_POST['region_select'] : '';

// Luzon/Visayas/Mindanao region mapping
function getIslandGroupPHP($regionName) {
    $luzon = ["NCR", "Ilocos Region", "Cagayan Valley", "Central Luzon", "CALABARZON", "MIMAROPA Region", "Cordillera Administrative Region", "Bicol Region"];
    $visayas = ["Western Visayas", "Central Visayas", "Eastern Visayas"];
    $mindanao = ["Zamboanga Peninsula", "Northern Mindanao", "Davao Region", "SOCCSKSARGEN", "Caraga", "BARMM"];
    if (in_array($regionName, $luzon)) return 'luzon';
    if (in_array($regionName, $visayas)) return 'visayas';
    if (in_array($regionName, $mindanao)) return 'mindanao';
    return '';
}

$island_group = getIslandGroupPHP(trim($region_name));
if (isset($_GET['debug_region']) && $_GET['debug_region'] == '1') {
    echo '<div style="background:#fffbe6;color:#b65c00;padding:10px;">';
    echo 'DEBUG: region_name = <b>' . htmlspecialchars($region_name) . '</b><br>';
    echo 'Mapping result: <b>' . htmlspecialchars($island_group) . '</b><br>';
    // Show ord values for region_name and mapping value
    echo 'region_name ords: [';
    for ($i = 0; $i < strlen($region_name); $i++) {
        echo ord($region_name[$i]) . ' ';
    }
    echo ']<br>';
    $car = "Cordillera Administrative Region";
    echo 'CAR mapping ords: [';
    for ($i = 0; $i < strlen($car); $i++) {
        echo ord($car[$i]) . ' ';
    }
    echo ']';
    echo '</div>';
}

if ($delivery_mode === 'pickup') {
    $shipping = 0;
} else if ($delivery_mode === 'lalamove') {
    $shipping = null; // Tentative
} else if ($delivery_mode === 'jnt') {
    if ($island_group === 'luzon') $shipping = 100;
    else if ($island_group === 'visayas') $shipping = 130;
    else if ($island_group === 'mindanao') $shipping = 150;
    else $shipping = 0;
} else {
    $shipping = 0;
}
$tax_rate = 0.12;
$tax = $subtotal * $tax_rate;
$total = $subtotal + ($shipping ?? 0) + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_mode = isset($_POST['delivery_mode']) ? $_POST['delivery_mode'] : 'pickup';
    $region_name = isset($_POST['region_select']) ? $_POST['region_select'] : '';

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $payment_method = $_POST['payment_method'];
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address) || empty($city)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            
            // If payment method is GCash, store order data in session and redirect to gcash.php
            if ($payment_method === 'gcash') {
                // Initialize attempt counter if not exists (don't increment yet)
                if (!isset($_SESSION['gcash_payment_attempts'])) {
                    $_SESSION['gcash_payment_attempts'] = 0;
                }
                
                // Only increment if there was a previous failed attempt
                // (timer exists means they had a previous session that timed out/cancelled)
                if (isset($_SESSION['gcash_payment_start_time'])) {
                    $_SESSION['gcash_payment_attempts']++;
                }
                
                // Reset timer for this new attempt
                unset($_SESSION['gcash_payment_start_time']);
                
                $_SESSION['pending_gcash_order'] = [
                    'user_id' => $_SESSION['user_id'],
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'city' => $city,
                    'postal_code' => $postal_code,
                    'payment_method' => $payment_method,
                    'delivery_mode' => $delivery_mode,
                    'subtotal' => $subtotal,
                    'shipping' => $shipping,
                    'tax' => $tax,
                    'total' => $total,
                    'cart_items' => $cart_items,
                    'selected_ids' => $selected_ids,
                                    ];
                $conn->commit();
                header("Location: gcash.php");
                exit();
            }

            // Otherwise (e.g., COD), proceed with normal order placement
            $stmt = $conn->prepare("INSERT INTO orders (user_id, first_name, last_name, email, phone, address, city, postal_code, payment_method, delivery_mode, subtotal, shipping, tax, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $shipping_val = (float)($shipping ?? 0);
            $status = 'pending';
            $stmt->bind_param("issssssssssddds", $_SESSION['user_id'], $first_name, $last_name, $email, $phone, $address, $city, $postal_code, $payment_method, $delivery_mode, $subtotal, $shipping_val, $tax, $total, $status);
            $stmt->execute();
            $order_id = $conn->insert_id;
            foreach ($cart_items as $item) {
                $size = isset($item['size']) ? $item['size'] : null;
                $color = isset($item['color']) ? $item['color'] : null;
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, size, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisddss", $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $size, $color);
                $stmt->execute();
            }
            if (!empty($selected_ids)) {
                foreach ($selected_ids as $cid) {
                    $del = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                    $del->bind_param("ii", $cid, $_SESSION['user_id']);
                    $del->execute();
                }
            } else {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
            }
            $conn->commit();
            
            // Generate one-time token for order success page
            $token = bin2hex(random_bytes(32));
            $_SESSION['order_success_' . $order_id] = $token;
            
            header("Location: order_success.php?order_id=" . $order_id . "&token=" . $token);
            exit();
            
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Order error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MTC Clothing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .checkout-header {
            text-align: center;
            margin: 20px 0 40px;
            color: var(--primary-color);
        }
        
        .checkout-container {
            display: flex;
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .checkout-form {
            flex: 2;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        textarea#address {
            width: 100%;
            min-height: 100px;  
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 15px;
            resize: vertical;  
            margin-bottom: 15px;
        }
        
        textarea#address:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(91, 107, 70, 0.2);
        }
        
        .payment-options {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .payment-option {
            flex: 1;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover {
            border-color: var(--primary-color);
        }
        
        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(91, 107, 70, 0.1);
        }
        .payment-option.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
        
        .payment-option input {
            display: none;
        }
        
        .payment-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: var(--primary-color);
        }
        
        .order-summary {
            flex: 1;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .summary-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-color);
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .item-details {
            font-size: 12px;
            color: #6c757d;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 18px;
            margin: 20px 0;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .place-order-btn {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 30px;
        }
        
        .place-order-btn:hover:not(:disabled) {
            background-color: #4a5a36;
        }
        
        .place-order-btn {
            display: none;
        }
        
        .place-order-btn.show {
            display: block !important;
        }
        
        .validation-status {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .validation-status.complete {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .validation-status h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
        }
        
        .validation-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .validation-list li {
            padding: 5px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .validation-list li i {
            font-size: 16px;
        }
        
        .validation-list li.complete {
            color: #28a745;
        }
        
        .validation-list li.incomplete {
            color: #856404;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .saved-addresses {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .address-option {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .address-option:hover {
            border-color: #5b6b46;
            background: #f0f0f0;
        }
        .address-option.selected {
            border-color: #5b6b46;
            background: #e8f5e9;
        }
        .address-actions {
            margin-top: 10px;
            font-size: 0.9em;
        }
        .use-this-address {
            color: #5b6b46;
            text-decoration: underline;
            cursor: pointer;
        }
        
        .save-address-checkbox {
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .save-address-checkbox input {
            margin-right: 10px;
        }
        
        .address-selector {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .change-address-btn {
            width: 100%;
            padding: 12px;
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            color: var(--primary-color);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .change-address-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .current-address-display {
            background: #f8f9fa;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 13px;
        }
        
        .current-address-display strong {
            color: var(--primary-color);
            display: block;
            margin-bottom: 5px;
        }
        
        .address-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .address-modal.show {
            display: flex;
        }
        
        .address-modal-content {
            background: white;
            border-radius: 10px;
            padding: 25px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .address-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .address-modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .address-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .address-modal-close:hover {
            background: #f8f9fa;
            color: var(--text-color);
        }
        
        .address-card {
            border: 1.5px solid var(--border-color);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .address-card:last-child {
            margin-bottom: 0;
        }
        
        .address-card:hover {
            border-color: var(--primary-color);
            background-color: rgba(91, 107, 70, 0.05);
        }
        
        .address-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(91, 107, 70, 0.1);
        }
        
        .address-card-header {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
            font-size: 14px;
        }
        
        .address-card-body {
            font-size: 13px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .address-default-badge {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 6px;
        }
        
        .product-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .product-meta {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 3px;
        }
        
        .product-price-info {
            font-size: 14px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .payment-options {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1 class="checkout-header">Checkout</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['payment_error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_SESSION['payment_error']); ?>
            </div>
            <?php unset($_SESSION['payment_error']); ?>
        <?php endif; ?>
        
        <div class="checkout-container">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <form method="POST" id="checkout_form">
                    <input type="hidden" name="region_select" id="region_select_hidden">

                    <!-- Billing Information -->
                    <h2 class="section-title">Billing Information</h2>
                    
                    <?php if (!empty($default_address)): ?>
                    <div style="background: #e8f5e9; border: 1px solid #4caf50; border-radius: 6px; padding: 12px; margin-bottom: 15px; font-size: 14px; color: #2e7d32;">
                        <i class="fas fa-check-circle"></i> Naka-load na ang iyong saved address mula sa profile.
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required 
                                   value="<?php echo isset($default_address['first_name']) ? htmlspecialchars($default_address['first_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?php echo isset($default_address['last_name']) ? htmlspecialchars($default_address['last_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo isset($default_address['email']) ? htmlspecialchars($default_address['email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?php echo isset($default_address['phone']) ? htmlspecialchars($default_address['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" rows="3" required><?php echo isset($default_address['address']) ? htmlspecialchars($default_address['address']) : ''; ?></textarea>
                        <div class="muted" style="font-size:12px; margin-top:6px; color:#6b7280;">House/Unit, Street, Subdivision (huwag isama ang Barangay/City dito — pipiliin sa ibaba)</div>
                    </div>

                    <h2 class="section-title">Location</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="region_select">Region *</label>
                            <select id="region_select" required></select>
                        </div>
                        <div class="form-group">
                            <label for="province_select">Province *</label>
                            <select id="province_select" required></select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="citymun_select">City/Municipality *</label>
                            <select id="citymun_select" required></select>
                        </div>
                        <div class="form-group">
                            <label for="barangay_select">Barangay *</label>
                            <select id="barangay_select" required></select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <input type="hidden" id="city" name="city" value="<?php echo isset($default_address['city']) ? htmlspecialchars($default_address['city']) : ''; ?>">
                        <div class="form-group">
                            <label for="postal_code">Postal Code *</label>
                            <input type="text" id="postal_code" name="postal_code" required
                                   value="<?php echo isset($default_address['postal_code']) ? htmlspecialchars($default_address['postal_code']) : ''; ?>">
                        </div>
                    </div>


                    <!-- Payment Method -->
<h2 class="section-title">Payment Method</h2>
<div class="payment-options">
    <label class="payment-option" for="cod">
        <input type="radio" id="cod" name="payment_method" value="cod">
        <i class="fas fa-money-bill-wave"></i>
        <div>Cash on Delivery</div>
    </label>
    <label class="payment-option" for="gcash">
        <input type="radio" id="gcash" name="payment_method" value="gcash">
        <i class="fas fa-mobile-alt"></i>
        <div>GCash</div>
    </label>
</div>

<!-- Delivery Mode -->
<h2 class="section-title">Delivery Mode</h2>
<div class="payment-options" id="delivery-modes">
    <label class="payment-option delivery-mode-option" for="pickup">
        <input type="radio" id="pickup" name="delivery_mode" value="pickup">
        <i class="fas fa-store"></i>
        <div>Pick Up</div>
    </label>
    <label class="payment-option delivery-mode-option" for="lalamove">
        <input type="radio" id="lalamove" name="delivery_mode" value="lalamove">
        <i class="fas fa-motorcycle"></i>
        <div>Lalamove</div>
    </label>
    <label class="payment-option delivery-mode-option" for="jnt">
        <input type="radio" id="jnt" name="delivery_mode" value="jnt">
        <i class="fas fa-truck"></i>
        <div>J&T Express</div>
    </label>
</div>
<div id="shipping-fee-info" style="margin-top:10px;font-size:14px;color:#5b6b46;"></div>


                    
                    <!-- Validation Status -->
                    <div class="validation-status" id="validationStatus">
                        <h3><i class="fas fa-info-circle"></i> Please complete the following:</h3>
                        <ul class="validation-list" id="validationList">
                            <li class="incomplete" data-field="billing"><i class="fas fa-circle"></i> Complete billing information</li>
                            <li class="incomplete" data-field="location"><i class="fas fa-circle"></i> Select complete location (Region, Province, City, Barangay)</li>
                            <li class="incomplete" data-field="payment"><i class="fas fa-circle"></i> Select payment method</li>
                            <li class="incomplete" data-field="delivery"><i class="fas fa-circle"></i> Select delivery mode</li>
                        </ul>
                    </div>
                    
                    <button type="submit" name="place_order" class="place-order-btn" id="placeOrderBtn" disabled>
                        <i class="fas fa-check-circle"></i> Place Order
                    </button>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <!-- Address Selector -->
                <?php if (!empty($all_addresses)): ?>
                <div class="address-selector">
                    <div class="current-address-display" id="currentAddressDisplay">
                        <strong><i class="fas fa-map-marker-alt"></i> Delivery Address</strong>
                        <div id="currentAddressText">
                            <?php 
                            $display_addr = $default_address ?? $all_addresses[0];
                            echo htmlspecialchars($display_addr['first_name'] . ' ' . $display_addr['last_name']);
                            ?><br>
                            <?php echo htmlspecialchars($display_addr['phone']); ?><br>
                            <?php echo htmlspecialchars($display_addr['address']); ?>, 
                            <?php echo htmlspecialchars($display_addr['city']); ?>
                        </div>
                    </div>
                    <button type="button" class="change-address-btn" id="changeAddressBtn">
                        <i class="fas fa-edit"></i>
                        Change Address
                    </button>
                </div>
                
                <!-- Address Modal -->
                <div class="address-modal" id="addressModal">
                    <div class="address-modal-content">
                        <div class="address-modal-header">
                            <div class="address-modal-title">
                                <i class="fas fa-map-marker-alt"></i> Select Delivery Address
                            </div>
                            <button type="button" class="address-modal-close" id="closeAddressModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="address-list">
                            <?php foreach ($all_addresses as $addr): ?>
                            <div class="address-card" data-address-id="<?php echo $addr['id']; ?>" 
                                 data-first-name="<?php echo htmlspecialchars($addr['first_name']); ?>"
                                 data-last-name="<?php echo htmlspecialchars($addr['last_name']); ?>"
                                 data-email="<?php echo htmlspecialchars($addr['email']); ?>"
                                 data-phone="<?php echo htmlspecialchars($addr['phone']); ?>"
                                 data-address="<?php echo htmlspecialchars($addr['address']); ?>"
                                 data-city="<?php echo htmlspecialchars($addr['city']); ?>"
                                 data-postal-code="<?php echo htmlspecialchars($addr['postal_code']); ?>"
                                 data-region-code="<?php echo htmlspecialchars($addr['region_code'] ?? ''); ?>"
                                 data-region-name="<?php echo htmlspecialchars($addr['region_name'] ?? ''); ?>"
                                 data-province-code="<?php echo htmlspecialchars($addr['province_code'] ?? ''); ?>"
                                 data-city-code="<?php echo htmlspecialchars($addr['city_code'] ?? ''); ?>"
                                 data-barangay-code="<?php echo htmlspecialchars($addr['barangay_code'] ?? ''); ?>">
                                <div class="address-card-header">
                                    <?php echo htmlspecialchars($addr['first_name'] . ' ' . $addr['last_name']); ?>
                                    <?php if ($addr['is_default']): ?>
                                        <span class="address-default-badge">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div class="address-card-body">
                                    <?php echo htmlspecialchars($addr['phone']); ?><br>
                                    <?php echo htmlspecialchars($addr['address']); ?>, 
                                    <?php echo htmlspecialchars($addr['city']); ?>, 
                                    <?php echo htmlspecialchars($addr['postal_code']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <h2 class="summary-title">Order Summary</h2>
                
                <!-- Product Items with Images -->
                <div style="margin-bottom: 20px;">
                <?php foreach ($cart_items as $item): 
                    // Get Image 1 from product_images table
                    $display_image = getCatalogImage($conn, $item['product_id'], $item['image']);
                ?>
                    <div class="product-item">
                        <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" 
                           title="View product details"
                           style="display: block; flex-shrink: 0;">
                            <img src="<?php echo htmlspecialchars($display_image); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="product-image"
                                 style="cursor: pointer; transition: transform 0.2s;"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'"
                                 onerror="this.onerror=null; this.src='<?php echo getPlaceholderImage(); ?>';">
                        </a>
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <?php if (!empty($item['size'])): ?>
                            <div class="product-meta">Size: <?php echo htmlspecialchars($item['size']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['color'])): ?>
                            <div class="product-meta">Color: <?php echo htmlspecialchars($item['color']); ?></div>
                            <?php endif; ?>
                            <div class="product-meta">Quantity: <?php echo $item['quantity']; ?></div>
                            <div class="product-price-info">
                                <?php if (!empty($item['discount_label'])): ?>
                                    <span style="color:#888;text-decoration:line-through;font-size:13px;">₱<?php echo number_format($item['orig_price'], 2); ?></span>
                                    <span style="color:#e44d26;font-weight:600;">₱<?php echo number_format($item['price'], 2); ?></span>
                                    <span style="background:#f9e8d2;color:#b65c00;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:8px;">
                                        <?php echo $item['discount_label']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="font-weight:600;">₱<?php echo number_format($item['price'], 2); ?></span>
                                <?php endif; ?>
                                <span style="margin-left:8px;color:#6c757d;">× <?php echo $item['quantity']; ?></span>
                                <span style="margin-left:8px;font-weight:600;color:var(--primary-color);">= ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="order-summary-subtotal" data-value="<?php echo $subtotal; ?>">₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row shipping-row">
                        <span>Shipping</span>
                        <span><?php echo ($shipping === null) ? 'Tentative' : '₱' . number_format($shipping, 2); ?></span>
                    </div>
                    
                    <div class="summary-row tax-row">
                        <span>Tax (12%)</span>
                        <span>₱<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            // Only add event for payment methods (not delivery modes)
            if (!option.classList.contains('delivery-mode-option')) {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.payment-option:not(.delivery-mode-option)').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input').checked = true;
                    updateDeliveryOptions();
                    updateShippingFeeInfo();
                    validateForm();
                });
            }
        });

        // Delivery mode selection
        document.querySelectorAll('.delivery-mode-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.delivery-mode-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input').checked = true;
                updateShippingFeeInfo();
                validateForm();
            });
        });

        // Enable/disable J&T based on payment method
        function updateDeliveryOptions() {
            const payment = document.querySelector('input[name="payment_method"]:checked').value;
            const jntOption = document.getElementById('jnt');
            const jntLabel = jntOption.closest('label');
            if (payment === 'cod') {
                jntOption.disabled = true;
                if (!jntLabel.classList.contains('disabled')) jntLabel.classList.add('disabled');
                // If J&T was selected, just uncheck it and remove .selected from all delivery modes
                if (jntOption.checked) {
                    jntOption.checked = false;
                    document.querySelectorAll('.delivery-mode-option').forEach(opt => opt.classList.remove('selected'));
                }
            } else {
                jntOption.disabled = false;
                if (jntLabel.classList.contains('disabled')) jntLabel.classList.remove('disabled');
            }
        }

        // Luzon/Visayas/Mindanao region mapping (stub, update for full mapping)
        function getIslandGroup(regionName) {
            const luzon = ["NCR", "Ilocos Region", "Cagayan Valley", "Central Luzon", "CALABARZON", "MIMAROPA Region", "Cordillera Administrative Region", "Bicol Region"];
            const visayas = ["Western Visayas", "Central Visayas", "Eastern Visayas"];
            const mindanao = ["Zamboanga Peninsula", "Northern Mindanao", "Davao Region", "SOCCSKSARGEN", "Caraga", "BARMM"];
            if (luzon.includes(regionName)) return 'luzon';
            if (visayas.includes(regionName)) return 'visayas';
            if (mindanao.includes(regionName)) return 'mindanao';
            return '';
        }

        // Update shipping fee info
        function updateShippingFeeInfo() {
            const delivery = document.querySelector('input[name="delivery_mode"]:checked').value;
            const regionSel = document.getElementById('region_select');
            const regionName = regionSel.options[regionSel.selectedIndex]?.text || '';
            const group = getIslandGroup(regionName);
            let fee = '';
            if (delivery === 'pickup') fee = '₱0 (Pick up at store)';
            else if (delivery === 'lalamove') fee = 'Tentative (Lalamove rates apply)';
            else if (delivery === 'jnt') {
                if (group === 'luzon') fee = '₱100 (Luzon)';
                else if (group === 'visayas') fee = '₱130 (Visayas)';
                else if (group === 'mindanao') fee = '₱150 (Mindanao)';
                else fee = 'Select region to see shipping fee';
            }
            document.getElementById('shipping-fee-info').textContent = 'Shipping Fee: ' + fee;

            // Update order summary
            const shippingRow = document.querySelector('.summary-row.shipping-row span:last-child');
            const taxRow = document.querySelector('.summary-row.tax-row span:last-child');
            const totalRow = document.querySelector('.summary-row.summary-total span:last-child');
            const subtotalVal = parseFloat(document.getElementById('order-summary-subtotal').dataset.value);
            let shippingVal = 0;
            if (delivery === 'pickup') shippingVal = 0;
            else if (delivery === 'lalamove') shippingVal = null;
            else if (delivery === 'jnt') {
                if (group === 'luzon') shippingVal = 100;
                else if (group === 'visayas') shippingVal = 130;
                else if (group === 'mindanao') shippingVal = 150;
                else shippingVal = 0;
            }
            // Tax always 12% of subtotal
            const taxVal = subtotalVal * 0.12;
            let totalVal = subtotalVal + (shippingVal ?? 0) + taxVal;
            // Update UI
            if (shippingRow) shippingRow.textContent = (shippingVal === null) ? 'Tentative' : '₱' + shippingVal.toFixed(2);
            if (taxRow) taxRow.textContent = '₱' + taxVal.toFixed(2);
            if (totalRow) totalRow.textContent = '₱' + totalVal.toFixed(2);
        }

        // Update on region change
        document.getElementById('region_select').addEventListener('change', function() {
            updateShippingFeeInfo();
        });
        // Update on page load
        window.addEventListener('DOMContentLoaded', function() {
            updateDeliveryOptions();
            updateShippingFeeInfo();
            validateForm();
        });
        
        // Form validation function
        function validateForm() {
            const validationStatus = document.getElementById('validationStatus');
            const validationList = document.getElementById('validationList');
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            
            // Check billing information
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            const postalCode = document.getElementById('postal_code').value.trim();
            
            const billingComplete = firstName && lastName && email && phone && address && postalCode;
            
            // Check location selection
            const regionSel = document.getElementById('region_select');
            const citySel = document.getElementById('citymun_select');
            const brgySel = document.getElementById('barangay_select');
            
            const locationComplete = regionSel.value && citySel.value && brgySel.value;
            
            // Check payment method
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const paymentComplete = paymentMethod !== null;
            
            // Check delivery mode
            const deliveryMode = document.querySelector('input[name="delivery_mode"]:checked');
            const deliveryComplete = deliveryMode !== null;
            
            // Update validation list
            const billingItem = validationList.querySelector('[data-field="billing"]');
            const locationItem = validationList.querySelector('[data-field="location"]');
            const paymentItem = validationList.querySelector('[data-field="payment"]');
            const deliveryItem = validationList.querySelector('[data-field="delivery"]');
            
            if (billingComplete) {
                billingItem.classList.remove('incomplete');
                billingItem.classList.add('complete');
                billingItem.innerHTML = '<i class="fas fa-check-circle"></i> Billing information complete';
            } else {
                billingItem.classList.remove('complete');
                billingItem.classList.add('incomplete');
                billingItem.innerHTML = '<i class="fas fa-circle"></i> Complete billing information';
            }
            
            if (locationComplete) {
                locationItem.classList.remove('incomplete');
                locationItem.classList.add('complete');
                locationItem.innerHTML = '<i class="fas fa-check-circle"></i> Location complete';
            } else {
                locationItem.classList.remove('complete');
                locationItem.classList.add('incomplete');
                locationItem.innerHTML = '<i class="fas fa-circle"></i> Select complete location (Region, Province, City, Barangay)';
            }
            
            if (paymentComplete) {
                paymentItem.classList.remove('incomplete');
                paymentItem.classList.add('complete');
                paymentItem.innerHTML = '<i class="fas fa-check-circle"></i> Payment method selected';
            } else {
                paymentItem.classList.remove('complete');
                paymentItem.classList.add('incomplete');
                paymentItem.innerHTML = '<i class="fas fa-circle"></i> Select payment method';
            }
            
            if (deliveryComplete) {
                deliveryItem.classList.remove('incomplete');
                deliveryItem.classList.add('complete');
                deliveryItem.innerHTML = '<i class="fas fa-check-circle"></i> Delivery mode selected';
            } else {
                deliveryItem.classList.remove('complete');
                deliveryItem.classList.add('incomplete');
                deliveryItem.innerHTML = '<i class="fas fa-circle"></i> Select delivery mode';
            }
            
            // Enable/disable Place Order button
            const allComplete = billingComplete && locationComplete && paymentComplete && deliveryComplete;
            
            if (allComplete) {
                placeOrderBtn.disabled = false;
                placeOrderBtn.classList.add('show');
                validationStatus.classList.add('complete');
                validationStatus.querySelector('h3').innerHTML = '<i class="fas fa-check-circle"></i> Ready to place order!';
            } else {
                placeOrderBtn.disabled = true;
                placeOrderBtn.classList.remove('show');
                validationStatus.classList.remove('complete');
                validationStatus.querySelector('h3').innerHTML = '<i class="fas fa-info-circle"></i> Please complete the following:';
            }
        }
        
        // Add event listeners to all form fields
        document.getElementById('first_name').addEventListener('input', validateForm);
        document.getElementById('last_name').addEventListener('input', validateForm);
        document.getElementById('email').addEventListener('input', validateForm);
        document.getElementById('phone').addEventListener('input', validateForm);
        document.getElementById('address').addEventListener('input', validateForm);
        document.getElementById('postal_code').addEventListener('input', validateForm);
        document.getElementById('region_select').addEventListener('change', validateForm);
        document.getElementById('citymun_select').addEventListener('change', validateForm);
        document.getElementById('barangay_select').addEventListener('change', validateForm);
        
        // Add event listeners to payment method options
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', validateForm);
        });
        
        // Add event listeners to delivery mode options
        document.querySelectorAll('input[name="delivery_mode"]').forEach(radio => {
            radio.addEventListener('change', validateForm);
        });

        // PSGC API cascading selects for Region/Province/City/Barangay
        const PSGC = {
            regions: 'https://psgc.gitlab.io/api/regions/',
            regionProvinces: (code) => `https://psgc.gitlab.io/api/regions/${code}/provinces/`,
            regionCitiesMuns: (code) => `https://psgc.gitlab.io/api/regions/${code}/cities-municipalities/`,
            provinceCitiesMuns: (code) => `https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`,
            cityMunBarangays: (code) => `https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`
        };

        const regionSel = document.getElementById('region_select');
        const provSel = document.getElementById('province_select');
        const citySel = document.getElementById('citymun_select');
        const brgySel = document.getElementById('barangay_select');
        const cityInput = document.getElementById('city');
        const addressText = document.getElementById('address');
        const form = document.getElementById('checkout_form');

        function setLoading(selectEl, isLoading) {
            if (isLoading) {
                selectEl.innerHTML = '<option>Loading...</option>';
                selectEl.disabled = true;
            } else {
                selectEl.disabled = false;
            }
        }

        function fillOptions(selectEl, items, placeholder = 'Select...') {
            selectEl.innerHTML = '';
            const ph = document.createElement('option');
            ph.value = '';
            ph.textContent = placeholder;
            ph.disabled = true;
            ph.selected = true;
            selectEl.appendChild(ph);
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.code;
                opt.textContent = item.name;
                selectEl.appendChild(opt);
            });
        }

        async function fetchJSON(url) {
            const res = await fetch(url);
            if (!res.ok) throw new Error('Network error');
            return res.json();
        }

        async function loadRegions() {
            setLoading(regionSel, true);
            try {
                const data = await fetchJSON(PSGC.regions);
                // Sort alphabetically by name for nicer UX
                data.sort((a,b)=>a.name.localeCompare(b.name));
                fillOptions(regionSel, data, 'Select Region');
            } finally {
                setLoading(regionSel, false);
            }
        }

        async function loadProvincesAndCities(regionCode) {
            // Reset dependent selects
            fillOptions(provSel, [], 'Select Province');
            fillOptions(citySel, [], 'Select City/Municipality');
            fillOptions(brgySel, [], 'Select Barangay');

            // Try provinces under region (some regions like NCR have none)
            setLoading(provSel, true);
            let provinces = [];
            try {
                provinces = await fetchJSON(PSGC.regionProvinces(regionCode));
            } catch (e) { /* ignore */ }
            setLoading(provSel, false);

            if (Array.isArray(provinces) && provinces.length) {
                provinces.sort((a,b)=>a.name.localeCompare(b.name));
                fillOptions(provSel, provinces, 'Select Province');
                provSel.parentElement.style.display = '';
                // Cities/Muns will load on province change
            } else {
                // No provinces (e.g., NCR). Hide province field and load cities directly from region
                provSel.parentElement.style.display = 'none';
                setLoading(citySel, true);
                try {
                    let cities = await fetchJSON(PSGC.regionCitiesMuns(regionCode));
                    cities.sort((a,b)=>a.name.localeCompare(b.name));
                    fillOptions(citySel, cities, 'Select City/Municipality');
                } finally {
                    setLoading(citySel, false);
                }
            }
        }

        async function loadCities(provinceCode) {
            fillOptions(citySel, [], 'Select City/Municipality');
            fillOptions(brgySel, [], 'Select Barangay');
            setLoading(citySel, true);
            try {
                let cities = await fetchJSON(PSGC.provinceCitiesMuns(provinceCode));
                cities.sort((a,b)=>a.name.localeCompare(b.name));
                fillOptions(citySel, cities, 'Select City/Municipality');
            } finally {
                setLoading(citySel, false);
            }
        }

        async function loadBarangays(cityMunCode) {
            fillOptions(brgySel, [], 'Select Barangay');
            setLoading(brgySel, true);
            try {
                let brgys = await fetchJSON(PSGC.cityMunBarangays(cityMunCode));
                brgys.sort((a,b)=>a.name.localeCompare(b.name));
                fillOptions(brgySel, brgys, 'Select Barangay');
            } finally {
                setLoading(brgySel, false);
            }
        }

        // Event listeners
        regionSel.addEventListener('change', (e) => {
            const code = e.target.value;
            if (!code) return;
            loadProvincesAndCities(code);
        });
        provSel.addEventListener('change', (e) => {
            const code = e.target.value;
            if (!code) return;
            loadCities(code);
        });
        citySel.addEventListener('change', (e) => {
            const selectedText = citySel.options[citySel.selectedIndex]?.text || '';
            cityInput.value = selectedText; // keep DB field compatibility
            const code = e.target.value;
            if (!code) return;
            loadBarangays(code);
        });

        // On submit: append selected location to address and ensure city is set
        form.addEventListener('submit', (e) => {
            // Copy region_select value to hidden input for backend
            document.getElementById('region_select_hidden').value = regionSel.options[regionSel.selectedIndex]?.text || '';

            const regionName = regionSel.options[regionSel.selectedIndex]?.text || '';
            const provinceName = provSel.parentElement.style.display === 'none' ? '' : (provSel.options[provSel.selectedIndex]?.text || '');
            const cityName = citySel.options[citySel.selectedIndex]?.text || '';
            const brgyName = brgySel.options[brgySel.selectedIndex]?.text || '';

            if (!regionName || !cityName || !brgyName) {
                e.preventDefault();
                alert('Please select Region, City/Municipality and Barangay.');
                return false;
            }

            // Auto-fill city input
            cityInput.value = cityName;

            // Append structured location into address field for better fulfillment
            const base = addressText.value.trim();
            const parts = [base, brgyName, cityName, provinceName, regionName].filter(Boolean);
            addressText.value = parts.join(', ');
        });

        // Address modal functionality
        const addressModal = document.getElementById('addressModal');
        const changeAddressBtn = document.getElementById('changeAddressBtn');
        const closeAddressModal = document.getElementById('closeAddressModal');
        const currentAddressText = document.getElementById('currentAddressText');
        
        // Open modal
        if (changeAddressBtn) {
            changeAddressBtn.addEventListener('click', function() {
                addressModal.classList.add('show');
            });
        }
        
        // Close modal
        if (closeAddressModal) {
            closeAddressModal.addEventListener('click', function() {
                addressModal.classList.remove('show');
            });
        }
        
        // Close modal when clicking outside
        addressModal.addEventListener('click', function(e) {
            if (e.target === addressModal) {
                addressModal.classList.remove('show');
            }
        });
        
        // Address selector functionality
        document.querySelectorAll('.address-card').forEach(card => {
            card.addEventListener('click', async function() {
                // Remove selected class from all cards
                document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Get address data from data attributes
                const addressData = {
                    firstName: this.dataset.firstName,
                    lastName: this.dataset.lastName,
                    email: this.dataset.email,
                    phone: this.dataset.phone,
                    address: this.dataset.address,
                    city: this.dataset.city,
                    postalCode: this.dataset.postalCode,
                    regionCode: this.dataset.regionCode,
                    regionName: this.dataset.regionName,
                    provinceCode: this.dataset.provinceCode,
                    cityCode: this.dataset.cityCode,
                    barangayCode: this.dataset.barangayCode
                };
                
                // Update current address display
                currentAddressText.innerHTML = `
                    ${addressData.firstName} ${addressData.lastName}<br>
                    ${addressData.phone}<br>
                    ${addressData.address}, ${addressData.city}
                `;
                
                // Fill form fields
                document.getElementById('first_name').value = addressData.firstName;
                document.getElementById('last_name').value = addressData.lastName;
                document.getElementById('email').value = addressData.email;
                document.getElementById('phone').value = addressData.phone;
                document.getElementById('address').value = addressData.address;
                document.getElementById('postal_code').value = addressData.postalCode;
                
                // Load location data
                if (addressData.regionCode) {
                    regionSel.value = addressData.regionCode;
                    await loadProvincesAndCities(addressData.regionCode);
                    
                    if (addressData.provinceCode && provSel.parentElement.style.display !== 'none') {
                        provSel.value = addressData.provinceCode;
                        await loadCities(addressData.provinceCode);
                    }
                    
                    if (addressData.cityCode) {
                        citySel.value = addressData.cityCode;
                        cityInput.value = citySel.options[citySel.selectedIndex]?.text || '';
                        await loadBarangays(addressData.cityCode);
                        
                        if (addressData.barangayCode) {
                            brgySel.value = addressData.barangayCode;
                        }
                    }
                }
                
                // Update shipping fee based on region (for J&T)
                updateShippingFeeInfo();
                
                // Trigger validation
                validateForm();
                
                // Close modal
                addressModal.classList.remove('show');
            });
        });

        // Initialize
        async function initializeForm() {
            await loadRegions();
            
            // Pre-populate saved address data if available
            <?php if (!empty($default_address)): ?>
                const savedData = {
                    regionCode: '<?php echo addslashes($default_address['region_code'] ?? ''); ?>',
                    provinceCode: '<?php echo addslashes($default_address['province_code'] ?? ''); ?>',
                    cityCode: '<?php echo addslashes($default_address['city_code'] ?? ''); ?>',
                    barangayCode: '<?php echo addslashes($default_address['barangay_code'] ?? ''); ?>'
                };
                
                // Set region
                if (savedData.regionCode) {
                    regionSel.value = savedData.regionCode;
                    await loadProvincesAndCities(savedData.regionCode);
                    
                    // Set province
                    if (savedData.provinceCode && provSel.parentElement.style.display !== 'none') {
                        provSel.value = savedData.provinceCode;
                        await loadCities(savedData.provinceCode);
                    }
                    
                    // Set city
                    if (savedData.cityCode) {
                        citySel.value = savedData.cityCode;
                        cityInput.value = citySel.options[citySel.selectedIndex]?.text || '';
                        await loadBarangays(savedData.cityCode);
                        
                        // Set barangay
                        if (savedData.barangayCode) {
                            brgySel.value = savedData.barangayCode;
                        }
                    }
                    
                    // Update shipping fee after location is loaded
                    updateShippingFeeInfo();
                }
                
                // Mark default address as selected
                const defaultCard = document.querySelector('.address-card[data-address-id]');
                if (defaultCard) {
                    defaultCard.classList.add('selected');
                }
                
                // Trigger validation after loading saved data
                validateForm();
            <?php endif; ?>
        }
        
        initializeForm();
    </script>
</body>
</html>
