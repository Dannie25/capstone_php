<?php
session_start();
include '../db.php';
include_once '../includes/image_helper.php';
 
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
 
// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = [];
$order_items = [];
 
if ($order_id > 0) {
    // Get order details
    $stmt = $conn->prepare("SELECT o.*, CONCAT(o.first_name, ' ', o.last_name) as customer_name
                           FROM orders o
                           WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
   
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
       
        // Get order items
        $items_sql = "SELECT oi.*, p.name as product_name, p.image as product_image, p.price as product_price, oi.size, oi.color
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // For each item, try to get first image from product_images table if main image is empty
        foreach ($order_items as &$item) {
            if (empty($item['product_image'])) {
                $img_sql = "SELECT image FROM product_images WHERE product_id = ? ORDER BY id LIMIT 1";
                $img_stmt = $conn->prepare($img_sql);
                $img_stmt->bind_param("i", $item['product_id']);
                $img_stmt->execute();
                $img_result = $img_stmt->get_result();
                if ($img_row = $img_result->fetch_assoc()) {
                    $item['product_image'] = $img_row['image'];
                }
                $img_stmt->close();
            }
        }
        unset($item);
    } else {
        header("Location: orders.php");
        exit();
    }
} else {
    header("Location: orders.php");
    exit();
}
 
// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : null;
    if ($new_status === 'cancelled') {
        $cancelled_at = date('Y-m-d H:i:s');
        $update_query = "UPDATE orders SET status = ?, cancel_reason = ?, cancelled_at = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $new_status, $cancel_reason, $cancelled_at, $order_id);
    } else {
        $update_query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $order_id);
    }
    if ($stmt->execute()) {
        $success_message = "Order status updated successfully!";
        $order['status'] = $new_status; // Update local variable
        if ($new_status === 'cancelled') {
            $order['cancel_reason'] = $cancel_reason;
            $order['cancelled_at'] = $cancelled_at;
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?> - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
       
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
       
        .admin-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c8d99a 100%);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
       
        .admin-title {
            color: var(--primary-color);
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }
       
        .back-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
       
        .back-btn:hover {
            background-color: #4a5a36;
            color: white;
            transform: translateY(-2px);
        }
       
        .order-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
       
        .card-header {
            background: var(--secondary-color);
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
       
        .card-title {
            color: var(--primary-color);
            font-weight: 700;
            margin: 0;
            font-size: 1.5rem;
        }
       
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
       
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
       
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
       
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
       
        .product-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.3s;
        }
       
        .product-item:hover {
            background-color: #f8f9fa;
        }
       
        .product-item:last-child {
            border-bottom: none;
        }
       
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
            border: 1px solid #dee2e6;
        }
       
        .product-placeholder {
            width: 80px;
            height: 80px;
            background: var(--light-gray);
            border-radius: 8px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
       
        .product-details {
            flex: 1;
        }
       
        .product-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
       
        .product-info {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
       
        .product-price {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
       
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
        }
       
        .info-label {
            font-weight: 600;
            color: var(--primary-color);
        }
       
        .info-value {
            color: #6c757d;
        }
       
        .total-section {
            background: var(--light-gray);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
       
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
       
        .total-final {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
        }
       
        .status-form {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
       
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
       
        .btn-primary-custom:hover {
            background-color: #4a5a36;
            border-color: #4a5a36;
            transform: translateY(-2px);
        }
       
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 8px;
        }
       
        @media (max-width: 768px) {
            .admin-title {
                font-size: 1.5rem;
            }
           
            .product-item {
                flex-direction: column;
                text-align: center;
            }
           
            .product-image, .product-placeholder {
                margin-right: 0;
                margin-bottom: 15px;
            }
           
            .info-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="admin-title">
                    <i class="fas fa-receipt me-3"></i>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                </h1>
                <a href="orders.php" class="back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
            </div>
        </div>
    </div>
 
    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
 
        <!-- Order Information -->
        <div class="order-card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="card-title">Order Information</h2>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
            <div class="p-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">Customer:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Method:</span>
                            <span class="info-value">
                                <?php
                                switch($order['payment_method']) {
                                    case 'cod': echo 'Cash on Delivery'; break;
                                    case 'gcash': 
                                        echo 'GCash';
                                        if (!empty($order['gcash_reference_number'])) {
                                            echo '<br><small style="color: #5b6b46; font-weight: 600;">Ref: ' . htmlspecialchars($order['gcash_reference_number']) . '</small>';
                                        }
                                        break;
                                    default: echo ucfirst($order['payment_method']);
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Delivery Option:</span>
                            <span class="info-value">
                                <?php
                                if (isset($order['delivery_mode']) && trim($order['delivery_mode']) !== '') {
                                    switch(trim($order['delivery_mode'])) {
                                        case 'pickup': echo 'Pick Up'; break;
                                        case 'lalamove': echo 'Lalamove'; break;
                                        case 'jnt': echo 'J&T Express'; break;
                                        default: echo ucfirst(trim($order['delivery_mode']));
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </span>
                        </div>
                        <?php if ($order['payment_method'] === 'gcash' && !empty($order['gcash_receipt'])): ?>
                        <div class="info-row">
                            <span class="info-label">GCash Receipt:</span>
                            <a href="../<?php echo htmlspecialchars($order['gcash_receipt']); ?>" target="_blank" class="btn btn-primary-custom" style="padding:6px 18px; font-size:0.98rem;">
                                <i class="fas fa-image me-2"></i>View Receipt
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">Order Date:</span>
                            <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Shipping Address:</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($order['address'] . ', ' . $order['city']); ?>
                                <?php if ($order['postal_code']): ?>
                                    <?php echo htmlspecialchars(' ' . $order['postal_code']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
 
        <!-- Order Items -->
        <div class="order-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-box me-2"></i>Order Items (<?php echo count($order_items); ?> item<?php echo count($order_items) > 1 ? 's' : ''; ?>)
                </h2>
            </div>
            <div>
                <?php foreach ($order_items as $item): ?>
                    <div class="product-item d-flex p-3 border-bottom">
                        <?php
                        // Build image path - from admin folder, go up one level
                        $imagePath = '';
                        if (!empty($item['product_image'])) {
                            // Remove any leading slashes
                            $cleanPath = ltrim($item['product_image'], '/\\');
                            
                            // If path doesn't have img/ prefix, add it
                            if (!preg_match('/^img\//i', $cleanPath)) {
                                $cleanPath = 'img/' . basename($cleanPath);
                            }
                            
                            // From admin folder, go up one level
                            $imagePath = '../' . $cleanPath;
                        }
                        
                        // Check if file exists
                        $imageExists = !empty($imagePath) && file_exists($imagePath);
                        ?>
                       
                        <?php if ($imageExists): ?>
                            <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 class="img-thumbnail me-3"
                                 style="width: 100px; height: 100px; object-fit: cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <?php endif; ?>
                        
                        <?php if (!$imageExists): ?>
                            <div class="d-flex align-items-center justify-content-center bg-light me-3"
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-image fa-2x text-muted"></i>
                            </div>
                        <?php endif; ?>
                       
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h5>
                            <div class="text-muted mb-1">Quantity: <?php echo $item['quantity']; ?></div>
                            <div class="fw-bold">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            <?php if (!empty($item['size'])): ?>
                                <div class="text-muted">Size: <?php echo htmlspecialchars($item['size']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['color'])): ?>
                                <div class="text-muted">Color: <?php echo htmlspecialchars($item['color']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
               
                <!-- Order Total -->
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
        </div>
 
        <!-- Update Status -->
        <div class="status-form">
            <h3 class="mb-3">
                <i class="fas fa-edit me-2"></i>Update Order Status
            </h3>
            <form method="POST">
    <div class="row align-items-end">
        <div class="col-md-6">
            <label for="status" class="form-label">Order Status</label>
            <select name="status" id="status" class="form-select" onchange="toggleCancelReason()" <?php if ($order['status'] === 'cancelled' || $order['status'] === 'completed') echo 'disabled'; ?>>
                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-6 d-flex flex-column align-items-end">
            <button type="submit" name="update_status" class="btn btn-primary-custom mb-2" <?php if ($order['status'] === 'cancelled' || $order['status'] === 'completed') echo 'disabled'; ?> >
                <i class="fas fa-save me-2"></i>Update Status
            </button>
                    </div>
    </div>
    <div class="row mt-3" id="cancelReasonRow" style="display: none;">
        <div class="col-12">
            <label for="cancel_reason" class="form-label">Reason for Cancellation</label>
            <textarea name="cancel_reason" id="cancel_reason" class="form-control" rows="3" placeholder="Enter reason..."><?php echo isset($order['cancel_reason']) ? htmlspecialchars($order['cancel_reason']) : ''; ?></textarea>
        </div>
    </div>
</form>
<script>
function toggleCancelReason() {
    var status = document.getElementById('status').value;
    var row = document.getElementById('cancelReasonRow');
    if(status === 'cancelled') {
        row.style.display = '';
    } else {
        row.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    toggleCancelReason();
});
</script>
        </div>
       
        <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
            <div class="alert alert-danger">
                <h6><i class="fas fa-ban me-2"></i>Order Cancelled</h6>
                <p class="mb-0"><strong>Reason:</strong> <?php echo htmlspecialchars($order['cancel_reason']); ?></p>
                <?php if (!empty($order['cancelled_at'])): ?>
                    <p class="mb-0 mt-2"><strong>Cancelled on:</strong> <?php echo date('F j, Y g:i A', strtotime($order['cancelled_at'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 