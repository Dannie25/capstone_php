<?php
// gcash.php - GCash payment page with reference number
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_start();

// Handle cancel request
if (isset($_GET['cancel']) && $_GET['cancel'] == '1') {
    // Keep the timer to mark this as a failed attempt
    // Attempts will be incremented when they return from checkout
    
    // Check if this is a subcontract or customization order
    if (isset($_SESSION['pending_subcontract_gcash'])) {
        // For subcontract, redirect back to my_orders.php with the modal open
        header('Location: my_orders.php#subcontract');
    } elseif (isset($_SESSION['pending_gcash_customization_order'])) {
        // For customization, redirect to customization checkout
        header('Location: my_orders.php#customization');
    } else {
        // For regular orders, redirect to checkout
        header('Location: checkout.php');
    }
    exit();
}

// Check if pending order exists (regular, customization, or subcontract)
$is_customization = false;
$is_subcontract = false;
if (isset($_SESSION['pending_gcash_customization_order'])) {
    $is_customization = true;
    $pending_order = $_SESSION['pending_gcash_customization_order'];
} elseif (isset($_SESSION['pending_subcontract_gcash'])) {
    $is_subcontract = true;
    $pending_order = $_SESSION['pending_subcontract_gcash'];
} elseif (isset($_SESSION['pending_gcash_order'])) {
    $pending_order = $_SESSION['pending_gcash_order'];
} else {
    header('Location: checkout.php');
    exit();
}

// Define limits
$MAX_ATTEMPTS = 3;
$TIMEOUT_SECONDS = 300; // 5 minutes
$COOLDOWN_SECONDS = 180; // 3 minutes cooldown after max attempts

// Initialize GCash payment session tracking
// This will only set the start time ONCE per payment session (not on refresh)
if (!isset($_SESSION['gcash_payment_start_time'])) {
    $_SESSION['gcash_payment_start_time'] = time();
}

// Ensure attempt counter exists (should be set in checkout.php)
if (!isset($_SESSION['gcash_payment_attempts'])) {
    $_SESSION['gcash_payment_attempts'] = 0;
}

// Check if attempts exceeded FIRST
if ($_SESSION['gcash_payment_attempts'] >= $MAX_ATTEMPTS) {
    // Check if cooldown is set
    if (!isset($_SESSION['gcash_cooldown_start'])) {
        // Set cooldown start time
        $_SESSION['gcash_cooldown_start'] = time();
    }
    
    $cooldown_elapsed = time() - $_SESSION['gcash_cooldown_start'];
    
    if ($cooldown_elapsed < $COOLDOWN_SECONDS) {
        // Still in cooldown period
        $cooldown_remaining = $COOLDOWN_SECONDS - $cooldown_elapsed;
        $cooldown_minutes = floor($cooldown_remaining / 60);
        $cooldown_seconds = $cooldown_remaining % 60;
        
        // Clear GCash session data but keep cooldown
        unset($_SESSION['gcash_payment_start_time']);
        unset($_SESSION['pending_gcash_order']);
        unset($_SESSION['pending_gcash_customization_order']);
        unset($_SESSION['pending_subcontract_gcash']);
        
        // Set error message with cooldown timer
        $_SESSION['payment_error'] = sprintf(
            'You have exceeded the maximum GCash payment attempts. Please wait %d minute(s) and %d second(s) before trying again, or choose another payment method.',
            $cooldown_minutes,
            $cooldown_seconds
        );
        $_SESSION['gcash_cooldown_remaining'] = $cooldown_remaining;
        
        // Redirect based on order type
        if ($is_subcontract) {
            header('Location: my_orders.php#subcontract');
        } elseif ($is_customization) {
            header('Location: my_orders.php#customization');
        } else {
            header('Location: checkout.php');
        }
        exit();
    } else {
        // Cooldown period has passed, reset attempts
        unset($_SESSION['gcash_payment_attempts']);
        unset($_SESSION['gcash_cooldown_start']);
        unset($_SESSION['gcash_cooldown_remaining']);
        $_SESSION['gcash_payment_attempts'] = 0;
    }
}

// Check if time expired
$elapsed_time = time() - $_SESSION['gcash_payment_start_time'];
if ($elapsed_time >= $TIMEOUT_SECONDS) {
    // Clear GCash session data
    unset($_SESSION['gcash_payment_start_time']);
    unset($_SESSION['gcash_payment_attempts']);
    unset($_SESSION['pending_gcash_order']);
    unset($_SESSION['pending_gcash_customization_order']);
    unset($_SESSION['pending_subcontract_gcash']);
    
    // Redirect back to checkout with timeout message
    $_SESSION['payment_error'] = 'GCash payment time expired (5 minutes). Please try again or choose another payment method.';
    
    // Redirect based on order type
    if ($is_subcontract) {
        header('Location: my_orders.php#subcontract');
    } elseif ($is_customization) {
        header('Location: my_orders.php#customization');
    } else {
        header('Location: checkout.php');
    }
    exit();
}

// Calculate remaining time (will be consistent across refreshes)
$remaining_time = max(0, $TIMEOUT_SECONDS - $elapsed_time);
$remaining_attempts = $MAX_ATTEMPTS - $_SESSION['gcash_payment_attempts'];
 
// Get QR code
$qr = null;
$qr_result = $conn->query("SELECT image_path FROM gcash_qr ORDER BY id DESC LIMIT 1");
if ($row = $qr_result->fetch_assoc()) {
    $qr = $row['image_path'];
}
 
// Get CMS content with defaults
function getCMSContent($conn, $key, $default) {
    $stmt = $conn->prepare("SELECT setting_value FROM cms_settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}
 
$cms = [
    'page_title' => getCMSContent($conn, 'gcash_page_title', 'GCash Payment'),
    'qr_heading' => getCMSContent($conn, 'gcash_qr_heading', 'Scan QR Code'),
    'qr_description' => getCMSContent($conn, 'gcash_qr_description', 'Use your GCash app to scan this QR code'),
    'instructions_title' => getCMSContent($conn, 'gcash_instructions_title', 'Payment Instructions'),
    'step1' => getCMSContent($conn, 'gcash_instruction_step1', 'Open your GCash app and scan the QR code above'),
    'step2' => getCMSContent($conn, 'gcash_instruction_step2', 'Complete the payment of the total amount shown'),
    'step3' => getCMSContent($conn, 'gcash_instruction_step3', 'Copy the Reference Number from your GCash receipt'),
    'step4' => getCMSContent($conn, 'gcash_instruction_step4', 'Enter the reference number below to complete your order'),
    'reference_label' => getCMSContent($conn, 'gcash_reference_label', 'GCash Reference Number'),
    'reference_placeholder' => getCMSContent($conn, 'gcash_reference_placeholder', 'Enter your 13-digit reference number'),
    'reference_help' => getCMSContent($conn, 'gcash_reference_help', 'The reference number can be found in your GCash transaction receipt'),
    'button_text' => getCMSContent($conn, 'gcash_button_text', 'Confirm Payment'),
    'amount_label' => getCMSContent($conn, 'gcash_amount_label', 'Total Amount to Pay')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cms['page_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .payment-container {
            max-width: 480px;
            margin: 20px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(91,107,70,0.1);
            overflow: hidden;
        }
        .payment-header {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            padding: 20px 24px;
            text-align: center;
            position: relative;
        }
        .payment-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .payment-header .gcash-logo {
            font-size: 36px;
            margin-bottom: 6px;
        }
        .close-btn {
            position: absolute;
            top: 18px;
            right: 20px;
            font-size: 20px;
            color: rgba(255,255,255,0.8);
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .close-btn:hover {
            color: white;
            transform: scale(1.1);
        }
        .payment-body {
            padding: 24px;
        }
        .qr-section {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px solid #d9e6a7;
        }
        .qr-img {
            max-width: 180px;
            max-height: 180px;
            border-radius: 8px;
            border: 2px solid #fff;
            background: #fff;
            box-shadow: 0 2px 12px rgba(91,107,70,0.12);
            margin: 12px auto;
        }
        .amount-display {
            background: linear-gradient(135deg, #d9e6a7 0%, #c8d99a 100%);
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .amount-label {
            font-size: 13px;
            color: #5b6b46;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .amount-value {
            font-size: 28px;
            font-weight: 700;
            color: #5b6b46;
        }
        .instruction-box {
            background: #fff8e1;
            border-left: 3px solid #ffc107;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .instruction-box h5 {
            color: #5b6b46;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .instruction-step {
            display: flex;
            align-items: start;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        .instruction-step .step-number {
            background: #5b6b46;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .reference-form {
            background: #f8f9fa;
            padding: 18px;
            border-radius: 10px;
            border: 2px dashed #d9e6a7;
        }
        .form-label {
            font-weight: 600;
            color: #5b6b46;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .form-control {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #5b6b46;
            box-shadow: 0 0 0 0.2rem rgba(91,107,70,0.15);
        }
        .btn-submit {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            margin-top: 12px;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(91,107,70,0.2);
            cursor: pointer;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(91,107,70,0.3);
            background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
        }
        .alert-custom {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .view-qr-link {
            color: #5b6b46;
            font-size: 12px;
            text-decoration: none;
            font-weight: 500;
        }
        .view-qr-link:hover {
            text-decoration: underline;
        }
        .timer-warning-bar {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            padding: 12px 20px;
            text-align: center;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 14px;
        }
        .timer-section, .attempts-section {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .timer-display {
            font-size: 18px;
            font-family: 'Courier New', monospace;
            background: rgba(255,255,255,0.25);
            padding: 4px 10px;
            border-radius: 5px;
            min-width: 70px;
            font-weight: 700;
        }
        .attempts-display {
            background: rgba(255,255,255,0.25);
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
        }
        .timer-warning-bar.warning {
            background: linear-gradient(135deg, #ffa500 0%, #ff8c00 100%);
            animation: pulse 2s ease-in-out infinite;
        }
        .timer-warning-bar.critical {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            animation: pulse 1s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }
        
        /* Mobile Responsive */
        @media (max-width: 576px) {
            body {
                padding: 10px 0;
            }
            .payment-container {
                margin: 10px;
                border-radius: 12px;
            }
            .payment-body {
                padding: 20px;
            }
            .payment-header {
                padding: 16px 20px;
            }
            .payment-header h3 {
                font-size: 18px;
            }
            .payment-header .gcash-logo {
                font-size: 32px;
            }
            .timer-warning-bar {
                padding: 10px 16px;
                font-size: 13px;
            }
            .timer-display {
                font-size: 16px;
                min-width: 60px;
            }
            .amount-value {
                font-size: 24px;
            }
            .qr-img {
                max-width: 160px;
                max-height: 160px;
            }
            .instruction-step {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <button type="button" class="close-btn" onclick="goBackToCheckout()">
                <i class="fas fa-times"></i>
            </button>
            <div class="gcash-logo">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h3><?php echo htmlspecialchars($cms['page_title']); ?></h3>
        </div>
        
        <!-- Timer and Attempts Warning Bar -->
        <div class="timer-warning-bar <?php echo ($remaining_attempts == 1) ? 'critical' : ''; ?>" id="timerBar">
            <div class="timer-section">
                <i class="fas fa-clock"></i>
                <span>Time Remaining:</span>
                <span class="timer-display" id="countdown">5:00</span>
            </div>
            <div class="attempts-section">
                <i class="fas fa-exclamation-circle"></i>
                <span>Attempts Left:</span>
                <span class="attempts-display"><?php echo $remaining_attempts; ?> of <?php echo $MAX_ATTEMPTS; ?></span>
            </div>
        </div>
       
        <div class="payment-body">
            <?php if ($qr): ?>
                <?php
                $payment_completed = false;
                $order_id = 0;
               
                if ($pending_order) {
                    // Handle reference number submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reference_number'])) {
                        $reference_number = trim($_POST['reference_number']);
                       
                        if (!empty($reference_number)) {
                            // Insert order and items
                            $conn->begin_transaction();
                            try {
                                // Add columns if they don't exist
                                $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'gcash_reference_number'");
                                if ($result->num_rows == 0) {
                                    $conn->query("ALTER TABLE orders ADD gcash_reference_number VARCHAR(50) DEFAULT NULL");
                                }
                                
                                // Add columns to customization_requests if they don't exist
                                $result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'payment_method'");
                                if ($result->num_rows == 0) {
                                    $conn->query("ALTER TABLE customization_requests ADD payment_method VARCHAR(50) DEFAULT NULL");
                                }
                                
                                $result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'delivery_mode'");
                                if ($result->num_rows == 0) {
                                    $conn->query("ALTER TABLE customization_requests ADD delivery_mode VARCHAR(50) DEFAULT NULL");
                                }
                                
                                if ($is_customization) {
                                    // Handle customization order - NO order creation, only update customization_requests
                                    // Save payment and delivery info along with status
                                    $update_stmt = $conn->prepare("UPDATE customization_requests SET status = 'verifying', payment_method = ?, delivery_mode = ?, updated_at = NOW() WHERE id = ?");
                                    $update_stmt->bind_param("ssi", $pending_order['payment_method'], $pending_order['delivery_mode'], $pending_order['customization_id']);
                                    $update_stmt->execute();
                                    
                                    // Set order_id for success redirect (use customization_id)
                                    $order_id = $pending_order['customization_id'];
                                    
                                } elseif ($is_subcontract) {
                                    // Handle subcontract order - update subcontract_requests
                                    $update_stmt = $conn->prepare("UPDATE subcontract_requests SET status = 'in_progress', payment_method = ?, delivery_mode = ?, delivery_address = ?, email = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ?");
                                    $update_stmt->bind_param("ssssi", $pending_order['payment_method'], $pending_order['delivery_mode'], $pending_order['delivery_address'], $pending_order['email'], $pending_order['request_id']);
                                    $update_stmt->execute();
                                    
                                    // Set order_id for success redirect (use request_id)
                                    $order_id = $pending_order['request_id'];
                                    
                                } else {
                                    // Handle regular order
                                    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, first_name, last_name, email, phone, address, city, postal_code, payment_method, delivery_mode, subtotal, shipping, tax, total_amount, status, created_at, gcash_reference_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)");
                                    $delivery_mode = isset($pending_order['delivery_mode']) ? $pending_order['delivery_mode'] : 'pickup';
                                    $order_stmt->bind_param(
                                        "isssssssssdddds",
                                        $pending_order['user_id'],
                                        $pending_order['first_name'],
                                        $pending_order['last_name'],
                                        $pending_order['email'],
                                        $pending_order['phone'],
                                        $pending_order['address'],
                                        $pending_order['city'],
                                        $pending_order['postal_code'],
                                        $pending_order['payment_method'],
                                        $delivery_mode,
                                        $pending_order['subtotal'],
                                        $pending_order['shipping'],
                                        $pending_order['tax'],
                                        $pending_order['total'],
                                        $reference_number
                                    );
                                    $order_stmt->execute();
                                    $order_id = $conn->insert_id;
                                   
                                    foreach ($pending_order['cart_items'] as $item) {
                                        $size = isset($item['size']) ? $item['size'] : null;
                                        $color = isset($item['color']) ? $item['color'] : null;
                                        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, size, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                        $item_stmt->bind_param("iisddss", $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $size, $color);
                                        $item_stmt->execute();
                                    }
                                   
                                    // Clear cart
                                    if (!empty($pending_order['selected_ids'])) {
                                        foreach ($pending_order['selected_ids'] as $cid) {
                                            $del = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                                            $del->bind_param("ii", $cid, $pending_order['user_id']);
                                            $del->execute();
                                        }
                                    } else {
                                        $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                                        $del->bind_param("i", $pending_order['user_id']);
                                        $del->execute();
                                    }
                                }
                               
                                $conn->commit();
                               
                                // Remove pending order and timer from session
                                unset($_SESSION['pending_gcash_order']);
                                unset($_SESSION['pending_gcash_customization_order']);
                                unset($_SESSION['pending_subcontract_gcash']);
                                unset($_SESSION['gcash_payment_start_time']);
                                unset($_SESSION['gcash_payment_attempts']);
                                
                                    // If this was a subcontract, insert a notification for the user
                                    try {
                                        if ($is_subcontract && isset($order_id) && $order_id) {
                                            $notifMsg = "Your subcontract request #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " payment was received and the request is now in progress.";
                                            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subcontract', ?)");
                                            if ($notifStmt && isset($pending_order['user_id'])) {
                                                $notifStmt->bind_param('is', $pending_order['user_id'], $notifMsg);
                                                $notifStmt->execute();
                                                $notifStmt->close();
                                            }
                                        }
                                    } catch (Exception $nex) {
                                        error_log('Notification insert error (gcash): ' . $nex->getMessage());
                                    }
                               
                                // Redirect based on order type
                                ob_end_clean();
                                if ($is_customization) {
                                    // For customization orders, redirect to my_orders.php customization tab with success message
                                    $_SESSION['customization_success'] = 'Payment submitted successfully! Your customization order is now being verified.';
                                    header('Location: my_orders.php#customization');
                                } elseif ($is_subcontract) {
                                    // For subcontract orders, redirect to my_orders.php subcontract tab with success message
                                    $_SESSION['subcontract_success'] = 'Payment submitted successfully! Your subcontract order is now in progress.';
                                    header('Location: my_orders.php#subcontract');
                                } else {
                                    // For regular orders, redirect to order success page
                                    $token = bin2hex(random_bytes(32));
                                    $_SESSION['order_success_' . $order_id] = $token;
                                    header('Location: order_success.php?order_id=' . $order_id . '&token=' . $token);
                                }
                                exit();
                               
                            } catch (Exception $e) {
                                $conn->rollback();
                                echo '<div class="alert alert-danger alert-custom"><i class="fas fa-exclamation-triangle me-2"></i>Failed to process order: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning alert-custom"><i class="fas fa-exclamation-circle me-2"></i>Please enter a valid reference number.</div>';
                        }
                    }
                ?>
               
                <!-- Amount Display -->
                <?php if ($is_customization || $is_subcontract): ?>
                    <!-- Order Summary Breakdown for Customization/Subcontract -->
                    <?php
                    // DEBUG: Show what's in the session
                    if ($is_subcontract) {
                        echo '<div class="alert alert-info alert-custom" style="font-size: 12px; font-family: monospace;">';
                        echo '<strong>DEBUG - Session Data:</strong><br>';
                        echo 'price: ' . (isset($pending_order['price']) ? $pending_order['price'] : 'NOT SET') . '<br>';
                        echo 'shipping_fee: ' . (isset($pending_order['shipping_fee']) ? $pending_order['shipping_fee'] : 'NOT SET') . '<br>';
                        echo 'total_amount: ' . (isset($pending_order['total_amount']) ? $pending_order['total_amount'] : 'NOT SET') . '<br>';
                        echo '<hr>';
                        echo 'All keys: ' . implode(', ', array_keys($pending_order));
                        echo '</div>';
                    }
                    
                    // Get price and shipping fee with fallback
                    // For subcontract: 'price', for customization: 'customization_price'
                    $subtotal = 0;
                    if (isset($pending_order['price'])) {
                        $subtotal = $pending_order['price'];
                    } elseif (isset($pending_order['customization_price'])) {
                        $subtotal = $pending_order['customization_price'];
                    }
                    $shipping = isset($pending_order['shipping_fee']) ? $pending_order['shipping_fee'] : 0;
                    $total = isset($pending_order['total_amount']) ? $pending_order['total_amount'] : ($subtotal + $shipping);
                    ?>
                    <div class="amount-display">
                        <div class="amount-label" style="margin-bottom: 15px; font-size: 16px;">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </div>
                        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #666;">
                                <span>Subtotal:</span>
                                <span style="font-weight: 600; color: #5b6b46;">₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #dee2e6; font-size: 14px; color: #666;">
                                <span>Shipping Fee:</span>
                                <span style="font-weight: 600; color: #5b6b46;">₱<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 16px; font-weight: 700; color: #5b6b46;">Total:</span>
                                <span style="font-size: 24px; font-weight: 800; color: #5b6b46;">₱<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Simple Amount Display for Regular Orders -->
                    <div class="amount-display">
                        <div class="amount-label"><?php echo htmlspecialchars($cms['amount_label']); ?></div>
                        <div class="amount-value">₱<?php echo number_format($pending_order['total'], 2); ?></div>
                    </div>
                <?php endif; ?>
               
                <!-- QR Code Section -->
                <div class="qr-section">
                    <h5 style="color: #5b6b46; font-weight: 600; margin-bottom: 8px; font-size: 15px;">
                        <i class="fas fa-qrcode me-2"></i><?php echo htmlspecialchars($cms['qr_heading']); ?>
                    </h5>
                    <p style="font-size: 13px; color: #666; margin-bottom: 12px;">
                        <?php echo htmlspecialchars($cms['qr_description']); ?>
                    </p>
                    <img src="img/<?php echo htmlspecialchars($qr); ?>" alt="GCash QR Code" class="qr-img">
                    <div style="margin-top: 10px;">
                        <a href="img/<?php echo htmlspecialchars($qr); ?>" target="_blank" class="view-qr-link">
                            <i class="fas fa-expand-alt me-1"></i>View Full Size
                        </a>
                    </div>
                </div>
               
                <!-- Instructions -->
               
                <div class="instruction-box">
                    <h5><i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($cms['instructions_title']); ?></h5>
                    <div class="instruction-step">
                        <div class="step-number">1</div>
                        <div><?php echo htmlspecialchars($cms['step1']); ?></div>
                    </div>
                    <div class="instruction-step">
                        <div class="step-number">2</div>
                        <div><?php echo htmlspecialchars($cms['step2']); ?></div>
                    </div>
                    <div class="instruction-step">
                        <div class="step-number">3</div>
                        <div><?php echo htmlspecialchars($cms['step3']); ?></div>
                    </div>
                    <div class="instruction-step">
                        <div class="step-number">4</div>
                        <div><?php echo htmlspecialchars($cms['step4']); ?></div>
                    </div>
                </div>
               
                <!-- Reference Number Form -->
                <div class="reference-form">
                    <form method="POST" id="gcashReferenceForm">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-hashtag me-2"></i><?php echo htmlspecialchars($cms['reference_label']); ?>
                            </label>
                            <input
                                type="text"
                                name="reference_number"
                                id="reference_number_input"
                                class="form-control"
                                placeholder="<?php echo htmlspecialchars($cms['reference_placeholder']); ?>"
                                required
                                pattern="[0-9]{13}"
                                maxlength="13"
                                title="Please enter a valid 13-digit reference number"
                            >
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-lightbulb me-1"></i>
                                <?php echo htmlspecialchars($cms['reference_help']); ?>
                            </small>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($cms['button_text']); ?>
                        </button>
                    </form>
                </div>
               
           
               
                <?php } else { ?>
                    <div class="alert alert-warning alert-custom">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No pending order found. Please go back to checkout.
                    </div>
                <?php } ?>
               
            <?php else: ?>
                <div class="alert alert-danger alert-custom">
                    <i class="fas fa-times-circle me-2"></i>
                    No GCash QR code available. Please contact support.
                </div>
            <?php endif; ?>
        </div>
    </div>
   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to go back to checkout (cancel payment)
        function goBackToCheckout() {
            if (confirm('Are you sure you want to cancel this payment and return to checkout?')) {
                window.location.href = 'gcash.php?cancel=1';
            }
        }
        
        // Confirmation for GCash reference number submission
        document.getElementById('gcashReferenceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const referenceInput = document.getElementById('reference_number_input');
            const referenceNumber = referenceInput.value.trim();
            
            // Validate format
            if (!/^[0-9]{13}$/.test(referenceNumber)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Reference Number',
                    text: 'Please enter a valid 13-digit reference number.',
                    confirmButtonColor: '#5b6b46',
                    confirmButtonText: 'OK'
                }).then(() => {
                    referenceInput.focus();
                });
                return false;
            }
            
            // Format reference number for display (e.g., 1234-5678-90123)
            const formattedRef = referenceNumber.substring(0, 4) + '-' + 
                                referenceNumber.substring(4, 8) + '-' + 
                                referenceNumber.substring(8, 13);
            
            // Show confirmation dialog with SweetAlert2
            Swal.fire({
                title: 'Confirm Reference Number',
                html: `
                    <div style="text-align: center; padding: 10px;">
                        <p style="color: #6c757d; margin-bottom: 20px; font-size: 0.95rem;">
                            Please verify your GCash reference number
                        </p>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #dee2e6;">
                            <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">
                                Reference Number
                            </div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #5b6b46; font-family: 'Courier New', monospace; letter-spacing: 1px;">
                                ${formattedRef}
                            </div>
                        </div>
                        
                        <div style="background: #fff3cd; padding: 12px; border-radius: 6px; border-left: 3px solid #ffc107;">
                            <p style="margin: 0; color: #856404; font-size: 0.85rem;">
                                <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>
                                This cannot be changed after submission
                            </p>
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#5b6b46',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Confirm & Submit',
                cancelButtonText: 'Edit',
                width: '500px'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Processing Payment',
                        html: 'Please wait...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    // User confirmed, submit the form
                    form.submit();
                } else {
                    // User cancelled, focus back to input
                    referenceInput.focus();
                    referenceInput.select();
                }
            });
        });
        
        // Countdown timer - synced with server time
        let remainingSeconds = <?php echo $remaining_time; ?>;
        const countdownElement = document.getElementById('countdown');
        const timerBar = document.getElementById('timerBar');
        
        // Store the initial server time for verification
        const serverTimeOnLoad = <?php echo time(); ?>;
        const clientTimeOnLoad = Math.floor(Date.now() / 1000);
        const timeDrift = clientTimeOnLoad - serverTimeOnLoad; // Calculate drift
        
        function updateCountdown() {
            // Ensure we never go negative
            if (remainingSeconds < 0) {
                remainingSeconds = 0;
            }
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color based on time remaining
            if (remainingSeconds <= 60) {
                timerBar.classList.add('critical');
                timerBar.classList.remove('warning');
            } else if (remainingSeconds <= 120) {
                timerBar.classList.add('warning');
                timerBar.classList.remove('critical');
            }
            
            if (remainingSeconds <= 0) {
                // Time expired - redirect to checkout
                clearInterval(countdownInterval);
                window.location.href = 'checkout.php';
                return;
            }
            
            remainingSeconds--;
        }
        
        // Update countdown every second
        updateCountdown(); // Initial call to display immediately
        const countdownInterval = setInterval(updateCountdown, 1000);
        
        // Clear interval when page unloads
        window.addEventListener('beforeunload', function() {
            clearInterval(countdownInterval);
        });
        
        // Sync with server on visibility change (when user returns to tab)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // When page becomes visible again, verify time hasn't expired
                const currentClientTime = Math.floor(Date.now() / 1000);
                const elapsedClientTime = currentClientTime - clientTimeOnLoad;
                const estimatedServerTime = serverTimeOnLoad + elapsedClientTime;
                const startTime = <?php echo $_SESSION['gcash_payment_start_time']; ?>;
                const timeoutSeconds = <?php echo $TIMEOUT_SECONDS; ?>;
                const estimatedRemaining = timeoutSeconds - (estimatedServerTime - startTime);
                
                // If there's significant drift, reload page to sync with server
                if (Math.abs(estimatedRemaining - remainingSeconds) > 5) {
                    window.location.reload();
                }
            }
        });
    </script>
</body>
</html>