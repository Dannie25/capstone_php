<?php
session_start();
include 'db.php';
 
// Check if order ID and token are provided
if (!isset($_GET['order_id']) || !isset($_GET['token'])) {
    header("Location: home.php");
    exit();
}
 
$order_id = $_GET['order_id'];
$token = $_GET['token'];
 
// Verify token - must match session token for this order
$session_key = 'order_success_' . $order_id;
if (!isset($_SESSION[$session_key]) || $_SESSION[$session_key] !== $token) {
    // Token invalid or already used
    header("Location: my_orders.php?highlight=" . str_pad($order_id, 6, '0', STR_PAD_LEFT));
    exit();
}
 
// Token is valid - clear it immediately (one-time use)
unset($_SESSION[$session_key]);
 
// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
 
if (!$order) {
    header("Location: home.php");
    exit();
}
 
// Get order items
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - MTC Clothing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
            --success-color: #28a745;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--light-gray); color: var(--text-color); line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .success-header { text-align: center; margin: 40px 0; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .success-icon { font-size: 60px; color: var(--success-color); margin-bottom: 20px; }
        .success-title { font-size: 28px; color: var(--primary-color); margin-bottom: 10px; }
        .success-message { font-size: 16px; color: #6c757d; margin-bottom: 20px; }
        .order-number { font-size: 18px; font-weight: 600; color: var(--primary-color); background: var(--secondary-color); padding: 10px 20px; border-radius: 25px; display: inline-block; }
        .order-details { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 30px; }
        .section-title { font-size: 20px; font-weight: 600; color: var(--primary-color); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; }
        .detail-label { font-weight: 500; color: var(--text-color); }
        .detail-value { color: #6c757d; }
        .order-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); }
        .order-item:last-child { border-bottom: none; }
        .item-info { flex: 1; }
        .item-name { font-weight: 500; margin-bottom: 5px; }
        .item-details { font-size: 14px; color: #6c757d; }
        .total-section { margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--border-color); }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .total-final { font-size: 18px; font-weight: 700; color: var(--primary-color); margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color); }
        .action-buttons { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; padding: 12px 30px; margin: 0 10px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: #4a5a36; color: white; }
        .btn-secondary { background-color: white; color: var(--primary-color); border: 2px solid var(--primary-color); }
        .btn-secondary:hover { background-color: var(--primary-color); color: white; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; background-color: #ffc107; color: #856404; }
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .success-header { padding: 30px 20px; }
            .order-details { padding: 20px; }
            .action-buttons { flex-direction: column; }
            .btn { display: block; margin: 10px 0; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
   
    <div class="container">
        <!-- Success Header -->
        <div class="success-header">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="success-title">Order Placed Successfully!</h1>
            <p class="success-message">Thank you for your purchase. Your order has been received and is being processed.</p>
            <div class="order-number">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>
       
        <!-- Order Details -->
        <div class="order-details">
            <h2 class="section-title">Order Information</h2>
           
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
           
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="status-badge"><?php echo ucfirst($order['status']); ?></span>
            </div>
           
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">
                    <?php
                    switch($order['payment_method']) {
                        case 'cod': echo 'Cash on Delivery'; break;
                        case 'gcash': echo 'GCash'; break;
                        default: echo ucfirst($order['payment_method']);
                    }
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Delivery Option:</span>
                <span class="detail-value">
                    <?php
                    switch($order['delivery_mode']) {
                        case 'pickup': echo 'Pick Up'; break;
                        case 'lalamove': echo 'Lalamove'; break;
                        case 'jnt': echo 'J&T Express'; break;
                        default: echo ucfirst($order['delivery_mode']);
                    }
                    ?>
                </span>
            </div>
<?php if ($order['payment_method'] === 'gcash' && !empty($order['gcash_reference_number'])): ?>
            <div class="detail-row">
                <span class="detail-label">GCash Reference No.:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['gcash_reference_number']); ?></span>
            </div>
<?php endif; ?>
        </div>
       
        <!-- Shipping Information -->
        <div class="order-details">
            <h2 class="section-title">Shipping Information</h2>
           
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
            </div>
           
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
            </div>
           
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
            </div>
           
            <div class="detail-row">
                <span class="detail-label">Address:</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($order['address'] . ', ' . $order['city']); ?>
                    <?php if ($order['postal_code']): ?>
                        <?php echo htmlspecialchars(' ' . $order['postal_code']); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
       
        <!-- Order Items -->
        <div class="order-details">
            <h2 class="section-title">Order Items</h2>
           
            <?php foreach ($order_items as $item): ?>
    <?php
    // Try to show discount info if available
    $stmt = $conn->prepare("SELECT price, discount_enabled, discount_type, discount_value FROM products WHERE id = ?");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $stmt->bind_result($orig_price, $discount_enabled, $discount_type, $discount_value);
    $stmt->fetch();
    $stmt->close();
    $discount_label = '';
    $show_discount = false;
    if ($discount_enabled && $discount_type && $discount_value > 0 && $orig_price > $item['price']) {
        $show_discount = true;
        if ($discount_type === 'percent') {
            $discount_label = $discount_value . '% OFF';
        } else {
            $discount_label = '₱' . number_format($discount_value, 2) . ' OFF';
        }
    }
    ?>
    <div class="order-item">
        <div class="item-info">
            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
            <div class="item-details">
                Quantity: <?php echo $item['quantity']; ?> ×
                <?php if ($show_discount): ?>
                    <span style="color:#888;text-decoration:line-through;font-size:13px;">₱<?php echo number_format($orig_price, 2); ?></span>
                    <span style="color:#e44d26;">₱<?php echo number_format($item['price'], 2); ?></span>
                    <span style="background:#f9e8d2;color:#b65c00;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:8px;">
                        <?php echo $discount_label; ?>
                    </span>
                <?php else: ?>
                    ₱<?php echo number_format($item['price'], 2); ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="item-total">
            <?php if ($show_discount): ?>
                <span style="color:#888;text-decoration:line-through;font-size:13px;">₱<?php echo number_format($orig_price * $item['quantity'], 2); ?></span><br>
                <span style="color:#e44d26;">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
            <?php else: ?>
                ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
           
            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
               
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>₱<?php echo number_format($order['shipping'], 2); ?></span>
                </div>
               
                <div class="total-row">
                    <span>Tax:</span>
                    <span>₱<?php echo number_format($order['tax'], 2); ?></span>
                </div>
               
                <div class="total-row total-final">
                    <span>Total:</span>
                    <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
       
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="home.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Continue Shopping
            </a>
            <a href="my_orders.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> View My Orders
            </a>
        </div>
    </div>
   
    <?php include 'footer.php'; ?>
</body>
</html>