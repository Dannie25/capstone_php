<?php
session_start();
include 'db.php';
include_once 'includes/image_helper.php';
include_once 'includes/address_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's default address for auto-fill
$default_address = getDefaultAddress($user_id);

// Define all possible order statuses (static list)
$all_statuses = ['pending', 'shipped', 'completed', 'cancelled'];

// Get user's orders with items (sorted by latest first - DESC means newest on top)
$orders_query = "SELECT o.*, 
                        COUNT(oi.id) as item_count,
                        GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_summary
                 FROM orders o 
                 LEFT JOIN order_items oi ON o.id = oi.order_id 
                 WHERE o.user_id = ? 
                 GROUP BY o.id 
                 ORDER BY o.created_at DESC";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's subcontract requests
$subcontract_query = "SELECT * FROM subcontract_requests WHERE user_id = ? ORDER BY created_at DESC";
$stmt_sub = $conn->prepare($subcontract_query);
$stmt_sub->bind_param("i", $user_id);
$stmt_sub->execute();
$subcontracts = $stmt_sub->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's customization requests
$customization_query = "SELECT *, quoted_price as price FROM customization_requests WHERE user_id = ? ORDER BY created_at DESC";
$stmt_custom = $conn->prepare($customization_query);
$stmt_custom->bind_param("i", $user_id);
$stmt_custom->execute();
$customizations = $stmt_custom->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - MTC Clothing</title>
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
        
        .page-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c8d99a 100%);
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin: 0;
            font-size: 2.5rem;
        }
        
        .order-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .order-header {
            background: var(--secondary-color);
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-number {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        
        .order-date {
            color: #6c757d;
            font-size: 0.95rem;
            margin-top: 5px;
        }
        
        .order-body {
            padding: 25px;
        }
        
        .order-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-verifying {
            background-color: #e7f3ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .status-processing, .status-in_progress {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .order-total {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .items-section {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .items-title {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .order-items-container {
            overflow-x: auto;
            padding: 10px 5px;
            margin: 0 -5px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .order-items-container::-webkit-scrollbar {
            display: none;
        }
        
        .order-items {
            display: flex;
            flex-wrap: nowrap;
            gap: 15px;
        }
        
        .order-item {
            flex: 0 0 auto;
            width: 220px;
            padding: 12px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .item-image {
            width: 100%;
            height: 120px;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .item-details {
            padding: 15px;
        }
        
        .item-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .item-size {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .item-quantity {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--primary-color);
            text-align: right;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e9ecef;
        }
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        .order-items-container::-webkit-scrollbar {
            display: none;
        }
        
        /* Hide scrollbar for IE, Edge and Firefox */
        .order-items-container {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .empty-text {
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            background-color: #4a5a36;
            border-color: #4a5a36;
            transform: translateY(-2px);
        }
        
        .view-details-btn {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .view-details-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Order Status Filter */
        .status-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .status-filter {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #dee2e6;
            background: white;
            white-space: nowrap;
        }
        
        .status-filter:hover, .status-filter.active {
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-filter[data-status="pending"] {
            border-color: #ffc107;
            color: #856404;
        }
        
        .status-filter[data-status="pending"]:hover,
        .status-filter[data-status="pending"].active {
            background-color: #ffc107;
            border-color: #ffc107;
        }
        
        .status-filter[data-status="processing"] {
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .status-filter[data-status="processing"]:hover,
        .status-filter[data-status="processing"].active {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        
        .status-filter[data-status="shipped"] {
            border-color: #007bff;
            color: #004085;
        }
        
        .status-filter[data-status="shipped"]:hover,
        .status-filter[data-status="shipped"].active {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .status-filter[data-status="completed"] {
            border-color: #28a745;
            color: #155724;
        }
        
        .status-filter[data-status="completed"]:hover,
        .status-filter[data-status="completed"].active {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .status-filter[data-status="cancelled"] {
            border-color: #dc3545;
            color: #721c24;
        }
        
        .status-filter[data-status="cancelled"]:hover,
        .status-filter[data-status="cancelled"].active {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .status-filter[data-status="all"] {
            border-color: #6c757d;
            color: #343a40;
        }
        
        .status-filter[data-status="all"]:hover,
        .status-filter[data-status="all"].active {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .status-filter[data-status="submitted"] {
            border-color: #ffc107;
            color: #856404;
        }
        
        .status-filter[data-status="submitted"]:hover,
        .status-filter[data-status="submitted"].active {
            background-color: #ffc107;
            border-color: #ffc107;
            color: white;
        }
        
        .status-filter[data-status="approved"] {
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .status-filter[data-status="approved"]:hover,
        .status-filter[data-status="approved"].active {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        .status-filter[data-status="in_progress"] {
            border-color: #007bff;
            color: #004085;
        }
        
        .status-filter[data-status="in_progress"]:hover,
        .status-filter[data-status="in_progress"].active {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .status-filter[data-status="awaiting_confirmation"] {
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .status-filter[data-status="awaiting_confirmation"]:hover,
        .status-filter[data-status="awaiting_confirmation"].active {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        .status-filter[data-status="to_deliver"] {
            border-color: #007bff;
            color: #004085;
        }
        
        .status-filter[data-status="to_deliver"]:hover,
        .status-filter[data-status="to_deliver"].active {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .order-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-body {
                padding: 20px;
            }
            
            .order-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
        /* Feedback Modal Star Rating Styles */
        #starRating .star {
            font-size: 2rem;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s, transform 0.1s;
            user-select: none;
            margin-right: 2px;
        }
        #starRating .star.selected,
        #starRating .star:hover {
            color: #ffd700;
            transform: scale(1.15);
        }
        
        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid var(--secondary-color);
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link {
            color: var(--primary-color);
            font-weight: 600;
            border: none;
            padding: 12px 24px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(217, 230, 167, 0.3);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: var(--secondary-color);
            border: none;
            border-bottom: 3px solid var(--primary-color);
        }
        
        /* Subcontract specific styles */
        .subcontract-images {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .subcontract-images img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .subcontract-images img:hover {
            transform: scale(1.1);
        }
        
        .status-in_progress {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .status-awaiting_confirmation {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-to_deliver {
            background-color: #e7f3ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        /* Success Modal for submitted customization */
        .success-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 20000;
            padding: 16px;
        }
        .success-card {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(0,0,0,.25);
            overflow: hidden;
            border: 1px solid #e8f3ce;
            animation: modalIn .18s ease;
        }
        @keyframes modalIn { from { transform: translateY(10px); opacity:0 } to { transform: translateY(0); opacity:1 } }
        .success-header {
            background: #e6f4d7;
            color: #2d5a27;
            font-weight: 800;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #d5e8c0;
        }
        .success-body { padding: 18px; }
        .success-title { font-size: 20px; font-weight: 800; color: #2d3748; margin: 10px 0 6px; text-align: center; }
        .success-text { color: #4a5568; font-size: 14px; text-align: center; }
        .success-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: #10b981; color: #fff; display:flex; align-items:center; justify-content:center;
            font-size: 34px; margin: 14px auto;
            box-shadow: 0 10px 24px rgba(16,185,129,.35);
        }
        .success-note {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #0c4a6e;
            border-radius: 10px;
            padding: 12px;
            margin: 12px 0;
            font-size: 13px;
        }
        .success-actions { display:flex; justify-content:center; padding: 0 18px 18px; }
        .success-btn {
            background: #5b6b46; color:#fff; border:0; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <?php if (isset($_SESSION['customization_success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> <?php echo htmlspecialchars($_SESSION['customization_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['customization_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['subcontract_success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> <?php echo htmlspecialchars($_SESSION['subcontract_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['subcontract_success']); ?>
    <?php endif; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="text-center">
                <h1 class="page-title">
                    <i class="fas fa-shopping-bag me-3"></i>My Orders
                </h1>
                <p class="lead text-muted">Track and view your order history</p>
            </div>
        </div>
    </div>
    
    <div class="container pb-5">
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="orderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                    <i class="fas fa-shopping-cart me-2"></i>Product Orders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="subcontract-tab" data-bs-toggle="tab" data-bs-target="#subcontract" type="button" role="tab">
                    <i class="fas fa-file-contract me-2"></i>Subcontract Requests
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="customization-tab" data-bs-toggle="tab" data-bs-target="#customization" type="button" role="tab">
                    <i class="fas fa-tshirt me-2"></i>Customization Requests
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="orderTabsContent">
            <!-- Product Orders Tab -->
            <div class="tab-pane fade show active" id="orders" role="tabpanel">
                <?php if (!empty($orders)): ?>
                <div class="status-filters mb-4">
                    <div class="status-filter active" data-status="all">All Orders</div>
                    <?php 
                    // Display all possible status filter buttons
                    foreach ($all_statuses as $status): 
                        $status_label = ucfirst($status);
                    ?>
                    <div class="status-filter" data-status="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status_label); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-shopping-bag empty-icon"></i>
                <h2 class="empty-title">No Orders Yet</h2>
                <p class="empty-text">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <a href="home.php" class="btn btn-primary-custom">
                    <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                </a>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <?php foreach ($orders as $order): ?>
                <div class="order-card" data-status="<?php echo $order['status']; ?>">
                    <div class="order-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="order-number">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                <p class="order-date">Placed on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-info">
                            <div>
                                <strong>Payment Method:</strong> 
                                <?php 
                                switch($order['payment_method']) {
                                    case 'cod': echo 'Cash on Delivery'; break;
                                    case 'gcash': echo 'GCash'; break;
                                    default: echo ucfirst($order['payment_method']);
                                }
                                ?>
                            </div>
                            <div>
                                <strong>Delivery Option:</strong> 
                                <?php
                                switch($order['delivery_mode']) {
                                    case 'pickup': echo 'Pick Up'; break;
                                    case 'lalamove': echo 'Lalamove'; break;
                                    case 'jnt': echo 'J&T Express'; break;
                                    default: echo ucfirst($order['delivery_mode']);
                                }
                                ?>
                            </div>
                            <div class="order-total">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="items-section">
                            <h4 class="items-title">
                                <i class="fas fa-box"></i>
                                Order Items (<?php echo $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?>)
                            </h4>
                            
                            <?php
                            // Get detailed items for this order
                            $items_query = "SELECT oi.*, p.name as product_name, p.image as product_image, p.price as product_price 
                                         FROM order_items oi 
                                         LEFT JOIN products p ON oi.product_id = p.id 
                                         WHERE oi.order_id = ?";
                            $items_stmt = $conn->prepare($items_query);
                            $items_stmt->bind_param("i", $order['id']);
                            $items_stmt->execute();
                            $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                            
                            <div class="order-items-container">
                                <div class="order-items">
                                    <?php foreach ($items as $item): ?>
                                        <div class="order-item">
                                            <div class="item-image">
                                                <?php 
                                                // Get Image 1 from product_images table, fallback to product.image
                                                $display_image = getCatalogImage($conn, $item['product_id'], $item['product_image']);
                                                ?>
                                                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" 
                                                   title="View product details"
                                                   style="display: block; width: 100%; height: 100%; text-decoration: none;">
                                                    <img src="<?php echo htmlspecialchars($display_image); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                         style="width: 100%; height: 100%; object-fit: contain; cursor: pointer; transition: transform 0.2s;"
                                                         onmouseover="this.style.transform='scale(1.05)'"
                                                         onmouseout="this.style.transform='scale(1)'"
                                                         onerror="this.onerror=null; this.src='<?php echo getPlaceholderImage(); ?>';">
                                                </a>
                                            </div>
                                            <div class="item-details">
                                                <div class="item-name" title="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </div>
                                                <?php if (!empty($item['size'])): ?>
                                                    <div class="item-size">Size: <?php echo htmlspecialchars($item['size']); ?></div>
                                                <?php endif; ?>
                                                <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                                <div class="item-price">₱<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong>Shipping Address:</strong><br>
                                <?php echo htmlspecialchars($order['address'] . ', ' . $order['city']); ?>
                                <?php if ($order['postal_code']): ?>
                                    <?php echo htmlspecialchars(' ' . $order['postal_code']); ?>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                    <div class="mt-3 d-flex gap-2">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#reviewOrderModal" 
                                                data-order-id="<?php echo $order['id']; ?>">
                                            <i class="fas fa-eye me-1"></i> Review Order
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#cancelOrderModal" 
                                                data-order-id="<?php echo $order['id']; ?>">
                                            <i class="fas fa-times me-1"></i> Cancel Order
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-3">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#reviewOrderModal" 
                                                data-order-id="<?php echo $order['id']; ?>">
                                            <i class="fas fa-eye me-1"></i> Review Order
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <div><strong>Subtotal:</strong> ₱<?php echo number_format($order['subtotal'], 2); ?></div>
                                <div><strong>Shipping:</strong> ₱<?php echo number_format($order['shipping'], 2); ?></div>
                                <div><strong>Tax:</strong> ₱<?php echo number_format($order['tax'], 2); ?></div>
                                <div class="mt-2 fs-5"><strong>Total: ₱<?php echo number_format($order['total_amount'], 2); ?></strong></div>
                            </div>
                        </div>
                        
                        <!-- Cancellation Reason -->
                        <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
                        <div class="alert alert-danger mt-3">
                            <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Cancellation Reason:</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['cancel_reason'])); ?></p>
                            <?php if (!empty($order['cancelled_at'])): ?>
                                <small class="text-muted d-block mt-2">
                                    <i class="far fa-clock me-1"></i>Cancelled on: <?php echo date('F j, Y g:i A', strtotime($order['cancelled_at'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                    <!-- Feedback Button -->
<div class="mt-3">
    <?php
    // Only allow feedback for completed orders
    if ($order['status'] === 'completed') {
        // Check if feedback already exists for this order
        $feedback_check = $conn->prepare("SELECT id FROM order_feedback WHERE order_id = ? AND user_id = ?");
        $feedback_check->bind_param("ii", $order['id'], $user_id);
        $feedback_check->execute();
        $feedback_result = $feedback_check->get_result();
        if ($feedback_result->num_rows === 0): ?>
            <button class="btn btn-success btn-sm open-feedback-modal" data-order-id="<?php echo $order['id']; ?>">Submit Feedback</button>
        <?php else: ?>
            <div class="alert alert-info p-2">Feedback already submitted for this order.</div>
        <?php endif;
        $feedback_check->close();
    } ?>
</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
            </div>
            
            <!-- Subcontract Requests Tab -->
            <div class="tab-pane fade" id="subcontract" role="tabpanel">
                <!-- Status Filters for Subcontract -->
                <?php
                // Count subcontracts by status
                $subcon_counts = [
                    'pending' => 0,
                    'awaiting_confirmation' => 0,
                    'in_progress' => 0,
                    'to_deliver' => 0,
                    'completed' => 0,
                    'cancelled' => 0
                ];
                foreach ($subcontracts as $subcon) {
                    $status = $subcon['status'];
                    if (isset($subcon_counts[$status])) {
                        $subcon_counts[$status]++;
                    }
                }
                $total_subcontracts = count($subcontracts);
                ?>
                <div class="status-filters mb-4" id="subcontractFilters">
                    <div class="status-filter active" data-status="all" onclick="filterSubcontract('all')">
                        All Requests
                        <?php if ($total_subcontracts > 0): ?>
                            <span class="badge bg-secondary ms-1"><?php echo $total_subcontracts; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status-filter" data-status="pending" onclick="filterSubcontract('pending')">
                        Pending
                        <?php if ($subcon_counts['pending'] > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?php echo $subcon_counts['pending']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status-filter" data-status="awaiting_confirmation" onclick="filterSubcontract('awaiting_confirmation')">
                        Awaiting Confirmation
                        <?php if ($subcon_counts['awaiting_confirmation'] > 0): ?>
                            <span class="badge bg-info text-dark ms-1"><?php echo $subcon_counts['awaiting_confirmation']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status-filter" data-status="in_progress" onclick="filterSubcontract('in_progress')">
                        In Progress
                        <?php if ($subcon_counts['in_progress'] > 0): ?>
                            <span class="badge bg-primary ms-1"><?php echo $subcon_counts['in_progress']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status-filter" data-status="to_deliver" onclick="filterSubcontract('to_deliver')">
                        To Deliver
                        <?php if ($subcon_counts['to_deliver'] > 0): ?>
                            <span class="badge bg-info ms-1"><?php echo $subcon_counts['to_deliver']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status-filter" data-status="completed" onclick="filterSubcontract('completed')">
                        Completed
                        <?php if ($subcon_counts['completed'] > 0): ?>
                            <span class="badge bg-success ms-1"><?php echo $subcon_counts['completed']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status-filter" data-status="cancelled" onclick="filterSubcontract('cancelled')">
                        Cancelled
                        <?php if ($subcon_counts['cancelled'] > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo $subcon_counts['cancelled']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (empty($subcontracts)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-file-contract empty-icon"></i>
                        <h2 class="empty-title">No Subcontract Requests Yet</h2>
                        <p class="empty-text">You haven't submitted any subcontract requests yet.</p>
                        <a href="subcon.php" class="btn btn-primary-custom">
                            <i class="fas fa-plus me-2"></i>Create Request
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Subcontract Requests List -->
                    <?php foreach ($subcontracts as $subcon): ?>
                        <div class="order-card" data-status="<?php echo $subcon['status']; ?>">
                            <div class="order-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="order-number">Request #<?php echo str_pad($subcon['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                        <p class="order-date">Submitted on <?php echo date('F j, Y g:i A', strtotime($subcon['created_at'])); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $subcon['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $subcon['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3" style="color: var(--primary-color);">Request Details</h5>
                                        <p><strong>What for:</strong> <?php echo htmlspecialchars($subcon['what_for']); ?></p>
                                        <p><strong>Quantity:</strong> <?php echo $subcon['quantity']; ?></p>
                                        <p><strong>Target Date:</strong> <?php echo date('F j, Y', strtotime($subcon['date_needed'])); ?> at <?php echo date('g:i A', strtotime($subcon['time_needed'])); ?></p>
                                        <p><strong>Delivery Method:</strong> <?php echo htmlspecialchars($subcon['delivery_method']); ?></p>
                                        <?php if (!empty($subcon['note'])): ?>
                                            <p><strong>Note:</strong> <?php echo nl2br(htmlspecialchars($subcon['note'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3" style="color: var(--primary-color);">Customer Details</h5>
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($subcon['customer_name']); ?></p>
                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($subcon['address']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($subcon['email']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($subcon['design_file'])): ?>
                                    <div class="mt-3">
                                        <h5 class="mb-3" style="color: var(--primary-color);">Design Files</h5>
                                        <div class="subcontract-images">
                                            <?php 
                                            $design_files = json_decode($subcon['design_file'], true);
                                            if (is_array($design_files)) {
                                                foreach ($design_files as $file) {
                                                    if (file_exists($file)) {
                                                        echo '<img src="' . htmlspecialchars($file) . '" alt="Design" onclick="window.open(this.src, \'_blank\')">';
                                                    }
                                                }
                                            } else if (file_exists($subcon['design_file'])) {
                                                echo '<img src="' . htmlspecialchars($subcon['design_file']) . '" alt="Design" onclick="window.open(this.src, \'_blank\')">';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($subcon['status'] === 'pending'): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Your request is pending admin approval. You will be notified about the <?php echo htmlspecialchars($subcon['delivery_method']); ?> details once approved.
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-danger" onclick="cancelSubcontractRequest(<?php echo $subcon['id']; ?>)">
                                            <i class="fas fa-times me-2"></i>Cancel Request
                                        </button>
                                    </div>
                                <?php elseif ($subcon['status'] === 'awaiting_confirmation'): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Awaiting Your Confirmation</strong><br>
                                        <small>Please review the details and confirm your subcontract request.</small>
                                    </div>
                                <?php elseif ($subcon['status'] === 'in_progress'): ?>
                                    <div class="alert alert-primary mt-3">
                                        <i class="fas fa-spinner me-2"></i>
                                        Your request is currently being processed.
                                    </div>
                                <?php elseif ($subcon['status'] === 'to_deliver'): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-truck me-2"></i>
                                        <strong>Ready for Delivery</strong><br>
                                        <small>Your order is ready and will be delivered soon.</small>
                                    </div>
                                <?php elseif ($subcon['status'] === 'completed'): ?>
                                    <div class="alert alert-success mt-3">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Your request has been completed!
                                    </div>
                                <?php elseif ($subcon['status'] === 'cancelled'): ?>
                                    <div class="alert alert-danger mt-3">
                                        <h6 class="mb-2"><i class="fas fa-times-circle me-2"></i>Cancelled</h6>
                                        <?php if (!empty($subcon['cancel_reason'])): ?>
                                            <p class="mb-0"><strong>Reason:</strong> <?php echo htmlspecialchars($subcon['cancel_reason']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($subcon['cancelled_at'])): ?>
                                            <small class="text-muted d-block mt-2">
                                                <i class="far fa-clock me-1"></i>Cancelled on: <?php echo date('F j, Y g:i A', strtotime($subcon['cancelled_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Customization Requests Tab -->
            <div class="tab-pane fade" id="customization" role="tabpanel">
                <!-- Status Filters for Customization -->
                <div class="status-filters mb-4" id="customizationFilters">
                    <div class="status-filter active" data-status="all" onclick="filterCustomization('all')">All Requests</div>
                    <div class="status-filter" data-status="submitted" onclick="filterCustomization('submitted')">Pending</div>
                    <div class="status-filter" data-status="approved" onclick="filterCustomization('approved')">Awaiting Confirmation</div>
                    <div class="status-filter" data-status="verifying" onclick="filterCustomization('verifying')">Verifying</div>
                    <div class="status-filter" data-status="in_progress" onclick="filterCustomization('in_progress')">In Progress</div>
                    <div class="status-filter" data-status="completed" onclick="filterCustomization('completed')">Completed</div>
                    <div class="status-filter" data-status="cancelled" onclick="filterCustomization('cancelled')">Cancelled</div>
                </div>
                
                <?php if (empty($customizations)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-tshirt empty-icon"></i>
                        <h4>No Customization Requests</h4>
                        <p>You haven't made any customization requests yet.</p>
                        <a href="customization.php" class="btn btn-primary">Create Custom Design</a>
                    </div>
                <?php else: ?>
                    <!-- Customization Requests List -->
                    <?php foreach ($customizations as $custom): ?>
                        <div class="order-card" data-status="<?php echo $custom['status']; ?>">
                            <div class="order-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="order-number">Custom #<?php echo str_pad($custom['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                        <div class="order-date">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?php echo date('F j, Y', strtotime($custom['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-badge status-<?php echo $custom['status']; ?>">
                                            <?php 
                                                $status = str_replace('_', ' ', $custom['status']);
                                                echo ucwords($status);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="order-body">
                                <div class="row align-items-start">
                                    <!-- Canvas Preview Image - Left Side -->
                                    <?php if ($custom['reference_image_path']): ?>
                                    <div class="col-md-5">
                                        <div style="background: #f8f9fa; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center;">
                                            <h6 style="color: var(--primary-color); font-weight: 700; margin-bottom: 12px;">
                                                <i class="fas fa-tshirt me-2"></i>Design Preview
                                            </h6>
                                            <img src="<?php echo htmlspecialchars($custom['reference_image_path']); ?>" 
                                                 alt="Design Preview" 
                                                 class="img-fluid rounded" 
                                                 style="max-height: 280px; width: auto; object-fit: contain; background: white; padding: 10px; border: 1px solid #ddd;">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Design Details - Right Side -->
                                    <div class="<?php echo $custom['reference_image_path'] ? 'col-md-7' : 'col-md-12'; ?>">
                                        <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px; border-bottom: 2px solid var(--secondary-color); padding-bottom: 8px;">
                                            <i class="fas fa-info-circle me-2"></i>Design Details
                                        </h5>
                                        
                                        <div style="background: #fff; padding: 15px; border-radius: 8px;">
                                            <?php if ($custom['neckline_type']): ?>
                                                <div class="mb-2">
                                                    <strong style="color: #555; display: inline-block; width: 120px;">Neck Style:</strong>
                                                    <span style="color: #2d3748;"><?php echo ucfirst(htmlspecialchars($custom['neckline_type'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($custom['sleeve_type']): ?>
                                                <div class="mb-2">
                                                    <strong style="color: #555; display: inline-block; width: 120px;">Sleeve Length:</strong>
                                                    <span style="color: #2d3748;"><?php echo ucfirst(htmlspecialchars($custom['sleeve_type'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($custom['fit_type']): ?>
                                                <div class="mb-2">
                                                    <strong style="color: #555; display: inline-block; width: 120px;">Fit Style:</strong>
                                                    <span style="color: #2d3748;"><?php echo ucfirst(htmlspecialchars($custom['fit_type'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($custom['color_preference_1']): ?>
                                                <div class="mb-2">
                                                    <strong style="color: #555; display: inline-block; width: 120px;">Color:</strong>
                                                    <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo htmlspecialchars($custom['color_preference_1']); ?>; border: 1px solid #ddd; border-radius: 4px; vertical-align: middle; margin-right: 8px;"></span>
                                                    <span style="color: #2d3748;"><?php echo htmlspecialchars($custom['color_preference_1']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            // Get size info from description or other fields
                                            $sizeInfo = '';
                                            if (!empty($custom['description']) && strpos($custom['description'], 'Size:') !== false) {
                                                preg_match('/Size:\s*([A-Z0-9]+)/i', $custom['description'], $matches);
                                                if (!empty($matches[1])) {
                                                    $sizeInfo = $matches[1];
                                                }
                                            }
                                            if ($sizeInfo): ?>
                                                <div class="mb-2">
                                                    <strong style="color: #555; display: inline-block; width: 120px;">Size:</strong>
                                                    <span style="color: #2d3748; font-weight: 600;"><?php echo htmlspecialchars($sizeInfo); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Section -->
                                <?php if (!empty($custom['price']) && $custom['price'] > 0): ?>
                                    <div class="mt-3 p-3" style="background: linear-gradient(135deg, #f0f7ed 0%, #e8f5e9 100%); border-radius: 12px; border: 2px solid #8bc34a;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1" style="color: #5b6b46; font-weight: 700;">
                                                    <i class="fas fa-tag me-2"></i>Quoted Price
                                                </h5>
                                                <?php if (!empty($custom['notes'])): ?>
                                                    <p class="mb-0 text-muted" style="font-size: 13px;">
                                                        <i class="fas fa-comment-dots me-1"></i><?php echo htmlspecialchars($custom['notes']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <div style="font-size: 28px; font-weight: 800; color: #5b6b46;">
                                                    ₱<?php echo number_format($custom['price'], 2); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($custom['status'] === 'submitted'): ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>Request Submitted</strong><br>
                                        <small>Your customization request has been submitted and is waiting for admin review.</small>
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-danger" onclick="cancelCustomizationRequest(<?php echo $custom['id']; ?>)">
                                            <i class="fas fa-times me-2"></i>Cancel Request
                                        </button>
                                    </div>
                                <?php elseif ($custom['status'] === 'pending'): ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>Waiting for Price Quote</strong><br>
                                        <small>Your customization request is pending admin review. Once approved, you'll receive a price quote for your custom design.</small>
                                    </div>
                                <?php elseif ($custom['status'] === 'approved'): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Price Quote Received - Awaiting Your Confirmation</strong><br>
                                        <small>Please review the quoted price above. Click "Accept" to proceed with the customization or "Decline" to cancel the request.</small>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="button" class="btn btn-success flex-fill" onclick="openCustomizationCheckout(<?php echo $custom['id']; ?>, <?php echo $custom['price']; ?>, '<?php echo addslashes($custom['reference_image_path'] ?? ''); ?>', '<?php echo addslashes($custom['neckline_type'] ?? ''); ?>', '<?php echo addslashes($custom['sleeve_type'] ?? ''); ?>', '<?php echo addslashes($custom['fit_type'] ?? ''); ?>', '<?php echo addslashes($custom['color_preference_1'] ?? ''); ?>')">
                                            <i class="fas fa-check me-2"></i>Accept Price & Proceed
                                        </button>
                                        <button type="button" class="btn btn-outline-danger flex-fill" onclick="confirmCustomizationPrice(<?php echo $custom['id']; ?>, 'decline')">
                                            <i class="fas fa-times me-2"></i>Decline
                                        </button>
                                    </div>
                                <?php elseif ($custom['status'] === 'verifying'): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-check-double me-2"></i>
                                        <strong>Order Verification</strong><br>
                                        <small>Your order is being verified by our team. We'll start production once verification is complete.</small>
                                    </div>
                                <?php elseif ($custom['status'] === 'in_progress'): ?>
                                    <div class="alert alert-primary mt-3">
                                        <i class="fas fa-spinner me-2"></i>
                                        <strong>In Production</strong><br>
                                        <small>Your customization is currently being processed.</small>
                                    </div>
                                <?php elseif ($custom['status'] === 'completed'): ?>
                                    <div class="alert alert-success mt-3">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Completed</strong><br>
                                        <small>Your customization has been completed and is ready for pickup/delivery!</small>
                                    </div>
                                <?php elseif ($custom['status'] === 'cancelled'): ?>
                                    <div class="alert alert-danger mt-3">
                                        <h6 class="mb-2"><i class="fas fa-times-circle me-2"></i>Cancelled</h6>
                                        <?php if (!empty($custom['cancel_reason'])): ?>
                                            <p class="mb-0"><strong>Reason:</strong> <?php echo htmlspecialchars($custom['cancel_reason']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($custom['cancelled_at'])): ?>
                                            <small class="text-muted d-block mt-2">
                                                <i class="far fa-clock me-1"></i>Cancelled on: <?php echo date('F j, Y g:i A', strtotime($custom['cancelled_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <!-- Customization Checkout Modal -->
    <div class="modal fade" id="customizationCheckoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header" style="background: var(--primary-color); color: white; border: none;">
                    <h5 class="modal-title"><i class="fas fa-shopping-bag me-2"></i>Complete Your Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 30px;">
                    <!-- Payment Error Alert -->
                    <?php if (isset($_SESSION['payment_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="paymentErrorAlert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Payment Error:</strong> <?php echo htmlspecialchars($_SESSION['payment_error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php 
                        // Store cooldown info in JavaScript if present
                        $cooldown_remaining = isset($_SESSION['gcash_cooldown_remaining']) ? $_SESSION['gcash_cooldown_remaining'] : 0;
                        unset($_SESSION['payment_error']); 
                        ?>
                    <?php else: ?>
                        <?php $cooldown_remaining = 0; ?>
                    <?php endif; ?>
                    
                    <form id="customizationCheckoutForm">
                        <input type="hidden" id="checkout_customization_id" name="customization_id">
                        
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-lg-7">
                                <!-- Customization Preview -->
                                <div class="mb-4">
                                    <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">
                                        <i class="fas fa-tshirt me-2"></i>Your Customization
                                    </h5>
                                    <div id="customizationPreview" style="background: #f8f9fa; border-radius: 10px; padding: 15px;">
                                        <!-- Will be populated by JS -->
                                    </div>
                                </div>
                                
                                <!-- Billing Information -->
                                <div class="mb-4">
                                    <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">
                                        <i class="fas fa-file-invoice me-2"></i>Billing Information
                                    </h5>
                                    <?php if (!empty($default_address)): ?>
                                    <div style="background: #e8f5e9; border: 1px solid #4caf50; border-radius: 6px; padding: 12px; margin-bottom: 15px; font-size: 14px; color: #2e7d32;">
                                        <i class="fas fa-check-circle"></i> Naka-load na ang iyong saved address mula sa profile.
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">First Name *</label>
                                            <input type="text" class="form-control" name="first_name" id="modal_first_name" 
                                                   value="<?php echo isset($default_address['first_name']) ? htmlspecialchars($default_address['first_name']) : ''; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" name="last_name" id="modal_last_name" 
                                                   value="<?php echo isset($default_address['last_name']) ? htmlspecialchars($default_address['last_name']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" name="email" id="modal_email" 
                                               value="<?php echo isset($default_address['email']) ? htmlspecialchars($default_address['email']) : ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" name="phone" id="modal_phone" 
                                               value="<?php echo isset($default_address['phone']) ? htmlspecialchars($default_address['phone']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <!-- Location -->
                                <div class="mb-4">
                                    <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">
                                        <i class="fas fa-map-marker-alt me-2"></i>Location
                                    </h5>
                                    <div class="mb-3">
                                        <label class="form-label">Region *</label>
                                        <select class="form-select" name="region" id="modal_region" required>
                                            <option value="">Select Region</option>
                                            <option value="NCR">NCR</option>
                                            <option value="Ilocos Region">Ilocos Region</option>
                                            <option value="Cagayan Valley">Cagayan Valley</option>
                                            <option value="Central Luzon">Central Luzon</option>
                                            <option value="CALABARZON">CALABARZON</option>
                                            <option value="MIMAROPA Region">MIMAROPA Region</option>
                                            <option value="Bicol Region">Bicol Region</option>
                                            <option value="Western Visayas">Western Visayas</option>
                                            <option value="Central Visayas">Central Visayas</option>
                                            <option value="Eastern Visayas">Eastern Visayas</option>
                                            <option value="Zamboanga Peninsula">Zamboanga Peninsula</option>
                                            <option value="Northern Mindanao">Northern Mindanao</option>
                                            <option value="Davao Region">Davao Region</option>
                                            <option value="SOCCSKSARGEN">SOCCSKSARGEN</option>
                                            <option value="Caraga">Caraga</option>
                                            <option value="BARMM">BARMM</option>
                                            <option value="CAR">Cordillera Administrative Region</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Municipality *</label>
                                        <select class="form-select" name="municipality" id="modal_municipality" required disabled>
                                            <option value="">Select Municipality</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">City *</label>
                                        <select class="form-select" name="city" id="modal_city" required disabled>
                                            <option value="">Select City</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Barangay *</label>
                                        <select class="form-select" name="barangay" id="modal_barangay" required disabled>
                                            <option value="">Select Barangay</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address *</label>
                                        <input type="text" class="form-control" name="address" id="modal_address" placeholder="House/Unit, Street, Subdivision" 
                                               value="<?php echo isset($default_address['address']) ? htmlspecialchars($default_address['address']) : ''; ?>" required>
                                        <small class="text-muted">Huwag isama ang Barangay/City dito — pipiliin sa itaas</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Postal Code *</label>
                                        <input type="text" class="form-control" name="postal_code" id="modal_postal" 
                                               value="<?php echo isset($default_address['postal_code']) ? htmlspecialchars($default_address['postal_code']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <!-- Delivery Method -->
                                <div class="mb-4">
                                    <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">
                                        <i class="fas fa-truck me-2"></i>Delivery Method
                                    </h5>
                                    <div class="form-check mb-2 p-3" style="border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer;" onclick="document.getElementById('modal_pickup').click()">
                                        <input class="form-check-input" type="radio" name="delivery_mode" value="pickup" id="modal_pickup" checked>
                                        <label class="form-check-label w-100" for="modal_pickup" style="cursor: pointer;">
                                            <strong><i class="fas fa-store me-2"></i>Store Pickup</strong>
                                            <p class="text-muted mb-0 small">Pick up at MTC Clothing Store - FREE</p>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2 p-3" style="border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer;" onclick="document.getElementById('modal_jnt').click()">
                                        <input class="form-check-input" type="radio" name="delivery_mode" value="jnt" id="modal_jnt">
                                        <label class="form-check-label w-100" for="modal_jnt" style="cursor: pointer;">
                                            <strong><i class="fas fa-box me-2"></i>J&T Express</strong>
                                            <p class="text-muted mb-0 small">Standard courier delivery (3-5 days)</p>
                                        </label>
                                    </div>
                                    <div class="form-check p-3" style="border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer;" onclick="document.getElementById('modal_lalamove').click()">
                                        <input class="form-check-input" type="radio" name="delivery_mode" value="lalamove" id="modal_lalamove">
                                        <label class="form-check-label w-100" for="modal_lalamove" style="cursor: pointer;">
                                            <strong><i class="fas fa-motorcycle me-2"></i>Lalamove</strong>
                                            <p class="text-muted mb-0 small">Same-day delivery within Metro Manila</p>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Payment Method -->
                                <div class="mb-4">
                                    <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">
                                        <i class="fas fa-credit-card me-2"></i>Payment Method
                                    </h5>
                                    <div class="form-check mb-2 p-3" style="border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer;" onclick="document.getElementById('modal_cod').click()">
                                        <input class="form-check-input" type="radio" name="payment_method" value="cod" id="modal_cod" checked>
                                        <label class="form-check-label w-100" for="modal_cod" style="cursor: pointer;">
                                            <strong><i class="fas fa-money-bill-wave me-2"></i>Cash on Delivery</strong>
                                        </label>
                                    </div>
                                    <div class="form-check p-3" style="border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer;" onclick="document.getElementById('modal_gcash').click()">
                                        <input class="form-check-input" type="radio" name="payment_method" value="gcash" id="modal_gcash">
                                        <label class="form-check-label w-100" for="modal_gcash" style="cursor: pointer;">
                                            <strong><i class="fas fa-mobile-alt me-2"></i>GCash</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Order Summary -->
                            <div class="col-lg-5">
                                <div class="sticky-top" style="top: 20px;">
                                    <!-- Validation Checklist -->
                                    <div class="mb-3 p-3" style="background: white; border-radius: 10px; border: 2px solid #e2e8f0;">
                                        <h6 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">
                                            <i class="fas fa-clipboard-check me-2"></i>Please complete the following:
                                        </h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check_billing" disabled>
                                            <label class="form-check-label small" for="check_billing">
                                                Billing information complete
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check_location" disabled>
                                            <label class="form-check-label small" for="check_location">
                                                Location complete
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check_payment" disabled checked>
                                            <label class="form-check-label small" for="check_payment">
                                                Select payment method
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="check_delivery" disabled checked>
                                            <label class="form-check-label small" for="check_delivery">
                                                Select delivery mode
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div style="background: linear-gradient(135deg, #f0f7ed 0%, #e8f5e9 100%); border-radius: 15px; padding: 25px;">
                                        <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 20px;">
                                            <i class="fas fa-receipt me-2"></i>Order Summary
                                        </h5>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Subtotal:</span>
                                                <span class="fw-bold">₱<span id="modal_subtotal">0.00</span></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Shipping Fee:</span>
                                                <span class="fw-bold">₱<span id="modal_shipping">0.00</span></span>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between mb-3">
                                                <h5 class="mb-0">Total:</h5>
                                                <h5 class="mb-0 text-success">₱<span id="modal_total">0.00</span></h5>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn w-100" style="background: var(--primary-color); color: white; padding: 15px; border-radius: 10px; font-weight: 700;">
                                            <i class="fas fa-check-circle me-2"></i>Place Order
                                        </button>
                                        <div class="text-center mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-lock me-1"></i>Secure Checkout
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Request Modal -->
    <div class="modal fade" id="cancelRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header" style="background: #dc3545; color: white; border: none;">
                    <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Cancel Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 30px;">
                    <p style="color: #6c757d; margin-bottom: 20px;">
                        Please provide a reason for cancelling this request:
                    </p>
                    <textarea id="cancelReason" class="form-control" rows="4" 
                              placeholder="Enter your reason here..." 
                              style="border-radius: 8px; border: 2px solid #e2e8f0;"></textarea>
                    <div id="cancelError" class="alert alert-danger mt-3" style="display: none;"></div>
                </div>
                <div class="modal-footer" style="border: none; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                        <i class="fas fa-check me-2"></i>Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <?php if (isset($_SESSION['subcon_success']) && $_SESSION['subcon_success']): ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header" style="background: #28a745; color: white; border: none;">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Success!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" style="padding: 40px 30px;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #28a745; margin-bottom: 20px;"></i>
                    <h4 style="color: #5b6b46; margin-bottom: 15px;">Subcontract Request Submitted!</h4>
                    <p style="color: #6c757d; margin-bottom: 20px;">
                        Your request is now pending approval.<br>
                        Once approved by admin, you will be notified about the 
                        <strong><?php echo htmlspecialchars($_SESSION['subcon_delivery_method']); ?></strong> details.
                    </p>
                    <div class="alert alert-info" style="border-left: 4px solid #5b6b46; text-align: left;">
                        <i class="fas fa-info-circle me-2"></i>
                        You can track your request status in the <strong>Subcontract Requests</strong> tab.
                    </div>
                </div>
                <div class="modal-footer" style="border: none; justify-content: center;">
                    <button type="button" class="btn" style="background: #5b6b46; color: white; padding: 10px 30px; border-radius: 8px;" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Got it!
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Show success modal and switch to subcontract tab
        document.addEventListener('DOMContentLoaded', function() {
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            
            // Switch to subcontract tab
            const subcontractTab = new bootstrap.Tab(document.getElementById('subcontract-tab'));
            subcontractTab.show();
        });
    </script>
    <?php 
        unset($_SESSION['subcon_success']);
        unset($_SESSION['subcon_delivery_method']);
    ?>
    <?php endif; ?>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="feedbackModalForm">
            <div class="modal-header" style="background:#5b6b46;color:#fff;">
              <h5 class="modal-title" id="feedbackModalLabel"><i class="fas fa-star me-2"></i>Submit Feedback</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="order_id" id="feedbackModalOrderId">
              <div class="mb-3">
                <label class="form-label">How many stars?</label>
                <div id="starRating" class="mb-2">
                  <!-- Stars will be rendered here -->
                  <span class="star" data-value="1">&#9733;</span>
                  <span class="star" data-value="2">&#9733;</span>
                  <span class="star" data-value="3">&#9733;</span>
                  <span class="star" data-value="4">&#9733;</span>
                  <span class="star" data-value="5">&#9733;</span>
                </div>
                <input type="hidden" name="rating" id="feedbackModalRating" required>
              </div>
              <div class="mb-3">
                <label for="feedbackModalText" class="form-label">Share your experience</label>
                <textarea class="form-control" id="feedbackModalText" name="feedback_text" rows="3" maxlength="500" required placeholder="Share your experience..."></textarea>
              </div>
              <span id="feedbackModalMsg" class="ms-2"></span>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-success">Submit Feedback</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Review Order Modal -->
    <div class="modal fade" id="reviewOrderModal" tabindex="-1" aria-labelledby="reviewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #4a5a36;">
                    <h5 class="modal-title" id="reviewOrderModalLabel">
                        <i class="fas fa-shopping-bag me-2"></i>Order Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="reviewOrderBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelOrderModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Cancel Order
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="cancelOrderForm" action="cancel_order.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="cancelOrderId">
                        
                        <p>Please select the reason for cancellation:</p>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cancel_reason" id="reason1" value="Changed my mind" required>
                                <label class="form-check-label" for="reason1">Changed my mind</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cancel_reason" id="reason2" value="Found a better price">
                                <label class="form-check-label" for="reason2">Found a better price</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cancel_reason" id="reason3" value="Ordered by mistake">
                                <label class="form-check-label" for="reason3">Ordered by mistake</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cancel_reason" id="reason4" value="Taking too long to ship">
                                <label class="form-check-label" for="reason4">Taking too long to ship</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cancel_reason" id="reason5" value="Found a better alternative">
                                <label class="form-check-label" for="reason5">Found a better alternative</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cancel_reason" id="reasonOther" value="Others">
                                <label class="form-check-label" for="reasonOther">Others (please specify)</label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="otherReasonContainer" style="display: none;">
                            <label for="otherReason" class="form-label">Please specify reason:</label>
                            <textarea class="form-control" id="otherReason" name="other_reason" rows="3" maxlength="500"></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Note: Once cancelled, this action cannot be undone. Your payment will be refunded according to our refund policy.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i> Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/orders_polling.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Feedback Modal logic
            var feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
            var stars = document.querySelectorAll('#starRating .star');
            var ratingInput = document.getElementById('feedbackModalRating');
            var msgSpan = document.getElementById('feedbackModalMsg');
            var feedbackForm = document.getElementById('feedbackModalForm');
            var feedbackText = document.getElementById('feedbackModalText');
            var orderIdInput = document.getElementById('feedbackModalOrderId');

            // Open modal on button click
            document.querySelectorAll('.open-feedback-modal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var orderId = btn.getAttribute('data-order-id');
                    orderIdInput.value = orderId;
                    ratingInput.value = '';
                    feedbackText.value = '';
                    msgSpan.textContent = '';
                    stars.forEach(function(star) { star.classList.remove('selected'); });
                    feedbackModal.show();
                });
            });

            // Handle star rating UI
            stars.forEach(function(star) {
                star.addEventListener('mouseenter', function() {
                    var val = parseInt(star.getAttribute('data-value'));
                    stars.forEach(function(s, i) {
                        s.classList.toggle('selected', i < val);
                    });
                });
                star.addEventListener('mouseleave', function() {
                    var curr = parseInt(ratingInput.value) || 0;
                    stars.forEach(function(s, i) {
                        s.classList.toggle('selected', i < curr);
                    });
                });
                star.addEventListener('click', function() {
                    var val = parseInt(star.getAttribute('data-value'));
                    ratingInput.value = val;
                    stars.forEach(function(s, i) {
                        s.classList.toggle('selected', i < val);
                    });
                });
            });

            // Reset stars on modal close
            document.getElementById('feedbackModal').addEventListener('hidden.bs.modal', function() {
                stars.forEach(function(star) { star.classList.remove('selected'); });
                ratingInput.value = '';
                feedbackText.value = '';
                msgSpan.textContent = '';
            });

            // Submit feedback via AJAX
            feedbackForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var orderId = orderIdInput.value;
                var feedback = feedbackText.value.trim();
                var rating = ratingInput.value;
                msgSpan.textContent = '';
                msgSpan.style.color = '';
                if (!rating || !feedback) {
                    msgSpan.textContent = 'Please select a star rating and enter your feedback.';
                    msgSpan.style.color = 'red';
                    return;
                }
                var formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('feedback_text', feedback);
                formData.append('rating', rating);
                fetch('feedback_submit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        msgSpan.textContent = 'Thank you for your feedback!';
                        msgSpan.style.color = 'green';
                        setTimeout(function(){ window.location.reload(); }, 1200);
                    } else {
                        msgSpan.textContent = data.message || 'Submission failed.';
                        msgSpan.style.color = 'red';
                    }
                })
                .catch(() => {
                    msgSpan.textContent = 'Error submitting feedback.';
                    msgSpan.style.color = 'red';
                });
            });
            // Note: Status filter functionality is handled by orders_polling.js
            
            // Highlight order card if ?highlight=XXXXXX is in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const highlightId = urlParams.get('highlight');
            if (highlightId) {
                const card = document.querySelector('.order-card h3.order-number');
                const cards = document.querySelectorAll('.order-card');
                let found = false;
                cards.forEach(cardEl => {
                    const numEl = cardEl.querySelector('h3.order-number');
                    if (numEl && numEl.textContent.includes(highlightId)) {
                        cardEl.style.transition = 'box-shadow 0.5s, background 0.5s';
                        cardEl.style.boxShadow = '0 0 0 4px #ffe066';
                        cardEl.style.background = '#fffbe6';
                        cardEl.scrollIntoView({behavior: 'smooth', block: 'center'});
                        found = true;
                        setTimeout(() => {
                            cardEl.style.boxShadow = '';
                            cardEl.style.background = '';
                        }, 3500);
                    }
                });
            }

            // Existing cancel order modal code...
            const cancelModal = document.getElementById('cancelOrderModal');
            if (cancelModal) {
                cancelModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const orderId = button.getAttribute('data-order-id');
                    const modalBodyInput = cancelModal.querySelector('.modal-body input');
                    if (modalBodyInput) {
                        modalBodyInput.value = orderId;
                    }
                    
                    // Reset form
                    const form = document.getElementById('cancelOrderForm');
                    form.reset();
                    document.getElementById('otherReasonContainer').style.display = 'none';
                });
            }
            
            // Toggle other reason textarea
            document.querySelectorAll('input[name="cancel_reason"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const otherReasonContainer = document.getElementById('otherReasonContainer');
                    otherReasonContainer.style.display = (this.value === 'Others') ? 'block' : 'none';
                    if (this.value !== 'Others') {
                        document.getElementById('otherReason').value = '';
                    }
                });
            });
            
            // Form submission
            const form = document.getElementById('cancelOrderForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const selectedReason = document.querySelector('input[name="cancel_reason"]:checked');
                    const otherReason = document.getElementById('otherReason');
                    
                    if (!selectedReason) {
                        e.preventDefault();
                        alert('Please select a reason for cancellation.');
                        return false;
                    }
                    
                    if (selectedReason.value === 'Others' && !otherReason.value.trim()) {
                        e.preventDefault();
                        alert('Please specify your reason for cancellation.');
                        otherReason.focus();
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Always re-enable the submit button
                        const submitBtn = form.querySelector('button[type="submit"]');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Confirm Cancellation';

                        if (data.success) {
                            // Close the modal
                            const modal = bootstrap.Modal.getInstance(cancelModal);
                            modal.hide();

                            // Redirect to order list with success parameter
                            window.location.href = 'my_orders.php?cancelled=' + data.order_id;
                        } else {
                            // Show error message
                            Swal.fire({
                                icon: 'error',
                                title: 'Cannot Cancel Order',
                                text: data.error || 'An error occurred while processing your request.',
                                confirmButtonColor: '#5b6b46',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Re-enable the submit button
                        const submitBtn = form.querySelector('button[type="submit"]');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Confirm Cancellation';
                        
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to process your request. Please try again.',
                            confirmButtonColor: '#5b6b46',
                            confirmButtonText: 'OK'
                        });
                    });
                });
            }
            
            // Review Order Modal
            const reviewOrderModal = document.getElementById('reviewOrderModal');
            if (reviewOrderModal) {
                reviewOrderModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const orderId = button.getAttribute('data-order-id');
                    const modalBody = reviewOrderModal.querySelector('.modal-body');
                    
                    // Show loading state
                    modalBody.innerHTML = `
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Loading order details...</p>
                        </div>
                    `;
                    
                    // Load order details via AJAX
                    fetch(`get_order_details.php?order_id=${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                modalBody.innerHTML = `
                                    <div class="order-details">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h5 class="mb-0">Order #${data.order.id.toString().padStart(6, '0')}</h5>
                                            <span class="status-badge status-${data.order.status}">
                                                ${data.order.status.charAt(0).toUpperCase() + data.order.status.slice(1)}
                                            </span>
                                        </div>
                                        
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-header bg-light text-dark">
                                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Information</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="mb-2"><strong>Order Date:</strong> ${new Date(data.order.created_at).toLocaleString()}</p>
                                                        <p class="mb-2"><strong>Payment Method:</strong> ${formatPaymentMethod(data.order.payment_method)}</p>
                                                        <p class="mb-2"><strong>Delivery Option:</strong> ${formatDeliveryMode(data.order.delivery_mode)}</p>
                                                        <p class="mb-0"><strong>Status:</strong> 
                                                            <span class="status-badge status-${data.order.status}">
                                                                ${data.order.status.charAt(0).toUpperCase() + data.order.status.slice(1)}
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mt-3 mt-md-0">
                                                <div class="card h-100">
                                                    <div class="card-header bg-light text-dark">
                                                        <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping Information</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="mb-2"><strong>Shipping Address:</strong></p>
                                                        <p class="mb-0">
                                                            ${data.order.address}<br>
                                                            ${data.order.city} ${data.order.postal_code || ''}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card mb-4">
                                            <div class="card-header bg-light text-dark">
                                                <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Order Items (${data.items.length})</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="ps-3">Product</th>
                                                                <th class="text-end">Price</th>
                                                                <th class="text-center">Qty</th>
                                                                <th class="text-end pe-3">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${data.items.map(item => `
                                                                <tr>
                                                                    <td class="ps-3">
                                                                        <div class="d-flex align-items-center">
                                                                            ${item.product_image ? 
                                                                                `<img src="${item.product_image}" 
                                                                                     alt="${item.product_name}" 
                                                                                     class="me-3" 
                                                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;" 
                                                                                     onerror="this.onerror=null; this.src='img/no-image.jpg';">` : 
                                                                                `<div class="bg-light d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px; border-radius: 4px;">
                                                                                    <i class="fas fa-box fa-lg text-muted"></i>
                                                                                </div>`
                                                                            }
                                                                            <div>
                                                                                <div class="fw-medium">${item.product_name}</div>
                                                                                ${item.size ? `<small class="text-muted">Size: ${item.size}</small>` : ''}
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-end">₱${parseFloat(item.price).toFixed(2)}</td>
                                                                    <td class="text-center">${item.quantity}</td>
                                                                    <td class="text-end pe-3">₱${(item.price * item.quantity).toFixed(2)}</td>
                                                                </tr>
                                                            `).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row justify-content-end">
                                            <div class="col-md-5">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span>Subtotal:</span>
                                                            <span>₱${parseFloat(data.order.subtotal).toFixed(2)}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span>Shipping:</span>
                                                            <span>₱${parseFloat(data.order.shipping).toFixed(2)}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span>Tax:</span>
                                                            <span>₱${parseFloat(data.order.tax).toFixed(2)}</span>
                                                        </div>
                                                        <hr>
                                                        <div class="d-flex justify-content-between fw-bold fs-5">
                                                            <span>Total:</span>
                                                            <span>₱${parseFloat(data.order.total_amount).toFixed(2)}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        ${data.order.status === 'cancelled' && data.order.cancel_reason ? `
                                        <div class="alert alert-danger mt-3">
                                            <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Cancellation Reason:</h6>
                                            <p class="mb-0">${data.order.cancel_reason}</p>
                                            ${data.order.cancelled_at ? `
                                                <small class="text-muted d-block mt-2">
                                                    <i class="far fa-clock me-1"></i>Cancelled on: ${new Date(data.order.cancelled_at).toLocaleString()}
                                                </small>
                                            ` : ''}
                                        </div>
                                        ` : ''}
                                    </div>
                                `;
                            } else {
                                modalBody.innerHTML = `
                                    <div class="alert alert-danger mb-0">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        ${data.message || 'Failed to load order details. Please try again.'}
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modalBody.innerHTML = `
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    An error occurred while loading order details. Please try again.
                                </div>
                            `;
                        });
                });
            }
            
            function getStatusBadgeClass(status) {
                switch(status) {
                    case 'pending': return 'warning';
                    case 'processing': return 'info';
                    case 'shipped': return 'primary';
                    case 'completed': return 'success';
                    case 'cancelled': return 'danger';
                    default: return 'secondary';
                }
            }
            
            function formatPaymentMethod(method) {
                switch(method) {
                    case 'cod': return 'Cash on Delivery';
                    case 'gcash': return 'GCash';
                    default: return method.charAt(0).toUpperCase() + method.slice(1);
                }
            }
            function formatDeliveryMode(mode) {
                switch(mode) {
                    case 'pickup': return 'Pick Up';
                    case 'lalamove': return 'Lalamove';
                    case 'jnt': return 'J&T Express';
                    default: return mode ? mode.charAt(0).toUpperCase() + mode.slice(1) : '';
                }
            }
        });
        
        // Function to confirm or decline customization price
        function confirmCustomizationPrice(requestId, action) {
            const actionText = action === 'accept' ? 'accept' : 'decline';
            const confirmMsg = action === 'accept' 
                ? 'Are you sure you want to accept this price quote and proceed with the customization?' 
                : 'Are you sure you want to decline this price quote? The request will be cancelled.';
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            // Show loading state
            const buttons = document.querySelectorAll(`button[onclick*="${requestId}"]`);
            buttons.forEach(btn => {
                btn.disabled = true;
                if (btn.onclick.toString().includes(actionText)) {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                }
            });
            
            // Send request to server
            fetch('confirm_customization_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `request_id=${requestId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to process your response'));
                    // Re-enable buttons
                    buttons.forEach(btn => btn.disabled = false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                // Re-enable buttons
                buttons.forEach(btn => btn.disabled = false);
            });
        }
    </script>
    
    <!-- Success Modal Markup -->
    <div id="successOverlay" class="success-overlay" style="display:none;">
      <div class="success-card">
        <div class="success-header">
          <span>Success!</span>
          <button id="successClose" class="btn btn-sm" style="background:#d1e7dd;color:#0f5132;border:1px solid #badbcc; padding:6px 10px; border-radius:8px;">×</button>
        </div>
        <div class="success-body">
          <div class="success-icon">✓</div>
          <div class="success-title">Customization Request Submitted!</div>
          <div class="success-text">Your request is now pending approval. Once approved by admin, you will be notified about the next steps.</div>
          <div id="successNote" class="success-note" style="display:none;"></div>
        </div>
        <div class="success-actions">
          <button id="successGotIt" class="success-btn">Got it!</button>
        </div>
      </div>
    </div>

    <script>
    (function(){
      function getParam(name){
        const url = new URL(window.location.href);
        return url.searchParams.get(name);
      }
      function showSuccess(requestId){
        const overlay = document.getElementById('successOverlay');
        if (!overlay) return;
        const note = document.getElementById('successNote');
        if (note) {
          if (requestId) {
            note.style.display = 'block';
            note.innerHTML = '<strong>Reference:</strong> Request #' + String(requestId).padStart(6,'0');
          } else {
            note.style.display = 'none';
          }
        }
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }
      function hideSuccess(){
        const overlay = document.getElementById('successOverlay');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
        const url = new URL(window.location.href);
        url.searchParams.delete('submitted');
        url.searchParams.delete('request_id');
        window.history.replaceState({}, '', url.toString());
      }
      const submitted = getParam('submitted');
      const requestId = getParam('request_id');
      if (submitted === '1') {
        showSuccess(requestId);
      }
      const closeBtn = document.getElementById('successClose');
      const gotItBtn = document.getElementById('successGotIt');
      const overlay = document.getElementById('successOverlay');
      if (closeBtn) closeBtn.addEventListener('click', hideSuccess);
      if (gotItBtn) gotItBtn.addEventListener('click', hideSuccess);
      if (overlay) overlay.addEventListener('click', function(e){ if (e.target === overlay) hideSuccess(); });
    })();
    
    // Check for hash and switch to appropriate tab
    (function() {
      const hash = window.location.hash;
      if (hash === '#customization') {
        // Activate customization tab
        const customizationTab = document.getElementById('customization-tab');
        if (customizationTab) {
          const tab = new bootstrap.Tab(customizationTab);
          tab.show();
        }
      } else if (hash === '#subcontract') {
        // Activate subcontract tab
        const subcontractTab = document.getElementById('subcontract-tab');
        if (subcontractTab) {
          const tab = new bootstrap.Tab(subcontractTab);
          tab.show();
        }
      }
    })();
    
    // Global variables for cancel modal
    let cancelRequestId = null;
    let cancelRequestType = null; // 'subcontract' or 'customization'
    
    // Cancel Subcontract Request
    function cancelSubcontractRequest(requestId) {
      cancelRequestId = requestId;
      cancelRequestType = 'subcontract';
      document.getElementById('cancelReason').value = '';
      document.getElementById('cancelError').style.display = 'none';
      const modal = new bootstrap.Modal(document.getElementById('cancelRequestModal'));
      modal.show();
    }
    
    // Cancel Customization Request
    function cancelCustomizationRequest(requestId) {
      cancelRequestId = requestId;
      cancelRequestType = 'customization';
      document.getElementById('cancelReason').value = '';
      document.getElementById('cancelError').style.display = 'none';
      const modal = new bootstrap.Modal(document.getElementById('cancelRequestModal'));
      modal.show();
    }
    
    // Confirm cancellation with reason
    document.getElementById('confirmCancelBtn').addEventListener('click', function() {
      const reason = document.getElementById('cancelReason').value.trim();
      const errorDiv = document.getElementById('cancelError');
      
      if (!reason) {
        errorDiv.textContent = 'Please provide a reason for cancellation';
        errorDiv.style.display = 'block';
        return;
      }
      
      if (reason.length < 10) {
        errorDiv.textContent = 'Please provide a more detailed reason (at least 10 characters)';
        errorDiv.style.display = 'block';
        return;
      }
      
      errorDiv.style.display = 'none';
      this.disabled = true;
      this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelling...';
      
      const endpoint = cancelRequestType === 'subcontract' ? 'cancel_subcontract.php' : 'cancel_customization.php';
      
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'request_id=' + cancelRequestId + '&reason=' + encodeURIComponent(reason)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Request cancelled successfully');
          location.reload();
        } else {
          errorDiv.textContent = data.error || 'Failed to cancel request';
          errorDiv.style.display = 'block';
          this.disabled = false;
          this.innerHTML = '<i class="fas fa-check me-2"></i>Confirm Cancellation';
        }
      })
      .catch(error => {
        errorDiv.textContent = 'Network error. Please try again.';
        errorDiv.style.display = 'block';
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check me-2"></i>Confirm Cancellation';
        console.error(error);
      });
    });
    
    // Filter Subcontract Requests by Status
    function filterSubcontract(status) {
      const filters = document.querySelectorAll('#subcontractFilters .status-filter');
      const cards = document.querySelectorAll('#subcontract .order-card');
      
      // Update active filter
      filters.forEach(f => f.classList.remove('active'));
      event.target.classList.add('active');
      
      // Filter cards
      cards.forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        if (status === 'all' || cardStatus === status) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    }
    
    // Filter Customization Requests by Status
    function filterCustomization(status) {
      const filters = document.querySelectorAll('#customizationFilters .status-filter');
      const cards = document.querySelectorAll('#customization .order-card');
      
      // Update active filter
      filters.forEach(f => f.classList.remove('active'));
      event.target.classList.add('active');
      
      // Filter cards
      cards.forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        if (status === 'all' || cardStatus === status) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    }
    
    // Open Subcontract Checkout Modal
    let modalSubtotal = 0;
    let modalShippingFee = 0;
    
    function openSubcontractCheckout(requestId, price, whatFor, quantity) {
      console.log('Opening subcontract checkout:', {requestId, price, whatFor, quantity});
      
      try {
        modalSubtotal = parseFloat(price) || 0;
        modalShippingFee = 0;
        
        // Set subcontract request ID
        document.getElementById('checkout_customization_id').value = requestId;
        document.getElementById('checkout_customization_id').setAttribute('data-type', 'subcontract');
        
        // Build preview HTML for subcontract
        let previewHTML = `
          <div class="row">
            <div class="col-md-12">
              <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">Subcontract Request #${String(requestId).padStart(6, '0')}</h5>
              <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <p class="mb-2"><strong style="color: #555;">What for:</strong> <span style="color: #2d3748;">${whatFor}</span></p>
                <p class="mb-2"><strong style="color: #555;">Quantity:</strong> <span style="color: #2d3748;">${quantity}</span></p>
              </div>
              <div class="mt-3 p-3" style="background: linear-gradient(135deg, #f0f7ed 0%, #e8f5e9 100%); border-radius: 10px; border: 2px solid #8bc34a;">
                <div class="d-flex justify-content-between align-items-center">
                  <div><strong style="color: #5b6b46;">Quoted Price:</strong></div>
                  <div style="font-size: 1.5rem; font-weight: 800; color: #5b6b46;">₱${modalSubtotal.toFixed(2)}</div>
                </div>
              </div>
            </div>
          </div>`;
        
        document.getElementById('customizationPreview').innerHTML = previewHTML;
        
        // Reset delivery mode to pickup
        document.getElementById('modal_pickup').checked = true;
        modalShippingFee = 0;
        
        // Update totals
        updateModalTotals();
        
        // Load regions
        loadRegions();
        
        // Show modal
        const modalElement = document.getElementById('customizationCheckoutModal');
        if (!modalElement) {
          console.error('Modal element not found!');
          alert('Error: Checkout modal not found. Please refresh the page.');
          return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Subcontract checkout modal opened successfully');
      } catch (error) {
        console.error('Error opening subcontract checkout modal:', error);
        alert('Error opening checkout form: ' + error.message);
      }
    }
    
    // Reject Subcontract Price
    function rejectSubcontractPrice(requestId) {
      if (!confirm('Are you sure you want to decline this price quote? The request will be cancelled.')) {
        return;
      }
      
      fetch('confirm_subcontract_price.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'request_id=' + requestId + '&action=decline'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Price quote declined. The request has been cancelled.');
          location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to decline price'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
      });
    }
    
    // Open Customization Checkout Modal
    function openCustomizationCheckout(id, price, imagePath, neckline, sleeve, fit, color) {
      console.log('Opening checkout modal:', {id, price, imagePath, neckline, sleeve, fit, color});
      
      try {
        modalSubtotal = parseFloat(price) || 0;
        modalShippingFee = 0;
        
        // Set customization ID
        document.getElementById('checkout_customization_id').value = id;
      
      // Build preview HTML
      let previewHTML = '<div class="row">';
      if (imagePath) {
        previewHTML += `
          <div class="col-md-4">
            <img src="${imagePath}" alt="Design Preview" class="img-fluid rounded" style="max-height: 200px; width: auto; border: 2px solid #e2e8f0;">
          </div>`;
      }
      previewHTML += `
        <div class="col-md-${imagePath ? '8' : '12'}">
          <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">Custom #${String(id).padStart(6, '0')}</h5>
          <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
            ${neckline ? `<p class="mb-2"><strong style="color: #555;">Neck Style:</strong> <span style="color: #2d3748;">${neckline}</span></p>` : ''}
            ${sleeve ? `<p class="mb-2"><strong style="color: #555;">Sleeve:</strong> <span style="color: #2d3748;">${sleeve}</span></p>` : ''}
            ${fit ? `<p class="mb-2"><strong style="color: #555;">Fit:</strong> <span style="color: #2d3748;">${fit}</span></p>` : ''}
            ${color ? `<p class="mb-2"><strong style="color: #555;">Color:</strong> <span style="display: inline-block; width: 20px; height: 20px; background-color: ${color}; border: 1px solid #ddd; border-radius: 4px; vertical-align: middle; margin-right: 8px;"></span><span style="color: #2d3748;">${color}</span></p>` : ''}
          </div>
          <div class="mt-3 p-3" style="background: linear-gradient(135deg, #f0f7ed 0%, #e8f5e9 100%); border-radius: 10px; border: 2px solid #8bc34a;">
            <div class="d-flex justify-content-between align-items-center">
              <div><strong style="color: #5b6b46;">Quoted Price:</strong></div>
              <div style="font-size: 1.5rem; font-weight: 800; color: #5b6b46;">₱${modalSubtotal.toFixed(2)}</div>
            </div>
          </div>
        </div>
      </div>`;
      
      document.getElementById('customizationPreview').innerHTML = previewHTML;
      
      // Reset delivery mode to pickup
      document.getElementById('modal_pickup').checked = true;
      modalShippingFee = 0;
      
      // Update totals
      updateModalTotals();
      
      // Load regions from PSGC API
        loadRegions();
      
      // Show modal
        const modalElement = document.getElementById('customizationCheckoutModal');
        if (!modalElement) {
          console.error('Modal element not found!');
          alert('Error: Checkout modal not found. Please refresh the page.');
          return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Modal opened successfully');
      } catch (error) {
        console.error('Error opening checkout modal:', error);
        alert('Error opening checkout form: ' + error.message);
      }
    }
    
    // PSGC API for Philippine Address Data
    const PSGC = {
      regions: 'https://psgc.gitlab.io/api/regions/',
      regionProvinces: (code) => `https://psgc.gitlab.io/api/regions/${code}/provinces/`,
      regionCitiesMuns: (code) => `https://psgc.gitlab.io/api/regions/${code}/cities-municipalities/`,
      provinceCitiesMuns: (code) => `https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`,
      cityMunBarangays: (code) => `https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`
    };
    
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
      const regionSel = document.getElementById('modal_region');
      setLoading(regionSel, true);
      try {
        const data = await fetchJSON(PSGC.regions);
        data.sort((a,b)=>a.name.localeCompare(b.name));
        fillOptions(regionSel, data, 'Select Region');
        
        // Auto-fill saved address if available
        <?php if (!empty($default_address)): ?>
        await autoFillSavedAddress();
        <?php endif; ?>
      } finally {
        setLoading(regionSel, false);
      }
    }
    
    // Auto-fill saved address function
    async function autoFillSavedAddress() {
      <?php if (!empty($default_address)): ?>
      const savedData = {
        regionCode: '<?php echo addslashes($default_address['region_code'] ?? ''); ?>',
        provinceCode: '<?php echo addslashes($default_address['province_code'] ?? ''); ?>',
        cityCode: '<?php echo addslashes($default_address['city_code'] ?? ''); ?>',
        barangayCode: '<?php echo addslashes($default_address['barangay_code'] ?? ''); ?>'
      };
      
      const regionSel = document.getElementById('modal_region');
      const municipalitySel = document.getElementById('modal_municipality');
      const citySel = document.getElementById('modal_city');
      const barangaySel = document.getElementById('modal_barangay');
      
      // Set region
      if (savedData.regionCode) {
        regionSel.value = savedData.regionCode;
        await loadMunicipalitiesAndCities(savedData.regionCode);
        
        // Set province/municipality
        if (savedData.provinceCode && municipalitySel.parentElement.style.display !== 'none') {
          municipalitySel.value = savedData.provinceCode;
          await loadCities(savedData.provinceCode);
        }
        
        // Set city
        if (savedData.cityCode) {
          citySel.value = savedData.cityCode;
          await loadBarangays(savedData.cityCode);
          
          // Set barangay
          if (savedData.barangayCode) {
            barangaySel.value = savedData.barangayCode;
          }
        }
        
        // Update validation and shipping
        updateValidationChecklist();
        calculateShipping();
      }
      <?php endif; ?>
    }
    
    async function loadMunicipalitiesAndCities(regionCode) {
      const municipalitySelect = document.getElementById('modal_municipality');
      const citySelect = document.getElementById('modal_city');
      const barangaySelect = document.getElementById('modal_barangay');
      
      fillOptions(municipalitySelect, [], 'Select Municipality');
      fillOptions(citySelect, [], 'Select City');
      fillOptions(barangaySelect, [], 'Select Barangay');
      
      setLoading(municipalitySelect, true);
      let provinces = [];
      try {
        provinces = await fetchJSON(PSGC.regionProvinces(regionCode));
      } catch (e) { /* ignore */ }
      setLoading(municipalitySelect, false);
      
      if (Array.isArray(provinces) && provinces.length) {
        provinces.sort((a,b)=>a.name.localeCompare(b.name));
        fillOptions(municipalitySelect, provinces, 'Select Municipality');
        municipalitySelect.disabled = false;
      } else {
        // No provinces (e.g., NCR). Load cities directly
        municipalitySelect.parentElement.style.display = 'none';
        setLoading(citySelect, true);
        try {
          let cities = await fetchJSON(PSGC.regionCitiesMuns(regionCode));
          cities.sort((a,b)=>a.name.localeCompare(b.name));
          fillOptions(citySelect, cities, 'Select City');
          citySelect.disabled = false;
        } finally {
          setLoading(citySelect, false);
        }
      }
    }
    
    async function loadCities(provinceCode) {
      const citySelect = document.getElementById('modal_city');
      const barangaySelect = document.getElementById('modal_barangay');
      
      fillOptions(citySelect, [], 'Select City');
      fillOptions(barangaySelect, [], 'Select Barangay');
      setLoading(citySelect, true);
      try {
        let cities = await fetchJSON(PSGC.provinceCitiesMuns(provinceCode));
        cities.sort((a,b)=>a.name.localeCompare(b.name));
        fillOptions(citySelect, cities, 'Select City');
        citySelect.disabled = false;
      } finally {
        setLoading(citySelect, false);
      }
    }
    
    async function loadBarangays(cityMunCode) {
      const barangaySelect = document.getElementById('modal_barangay');
      fillOptions(barangaySelect, [], 'Select Barangay');
      setLoading(barangaySelect, true);
      try {
        let brgys = await fetchJSON(PSGC.cityMunBarangays(cityMunCode));
        brgys.sort((a,b)=>a.name.localeCompare(b.name));
        fillOptions(barangaySelect, brgys, 'Select Barangay');
        barangaySelect.disabled = false;
      } finally {
        setLoading(barangaySelect, false);
      }
    }
    
    // Region change handler
    document.getElementById('modal_region').addEventListener('change', function() {
      const code = this.value;
      if (!code) return;
      loadMunicipalitiesAndCities(code);
      updateValidationChecklist();
      calculateShipping();
    });
    
    // Municipality change handler
    document.getElementById('modal_municipality').addEventListener('change', function() {
      const code = this.value;
      if (!code) return;
      loadCities(code);
      updateValidationChecklist();
    });
    
    // City change handler
    document.getElementById('modal_city').addEventListener('change', function() {
      const code = this.value;
      if (!code) return;
      loadBarangays(code);
      updateValidationChecklist();
    });
    
    // Barangay change handler
    document.getElementById('modal_barangay').addEventListener('change', function() {
      updateValidationChecklist();
    });
    
    // Billing info change handlers
    ['modal_first_name', 'modal_last_name', 'modal_email', 'modal_phone', 'modal_address'].forEach(id => {
      document.getElementById(id).addEventListener('input', updateValidationChecklist);
    });
    
    // Postal code change
    document.getElementById('modal_postal').addEventListener('input', updateValidationChecklist);
    
    // Delivery mode change handlers
    document.getElementById('modal_pickup').addEventListener('change', function() {
      if (this.checked) {
        modalShippingFee = 0;
        // Enable COD for pickup
        document.getElementById('modal_cod').disabled = false;
        document.getElementById('modal_cod').parentElement.style.opacity = '1';
        document.getElementById('modal_cod').parentElement.style.cursor = 'pointer';
        updateModalTotals();
      }
    });
    
    document.getElementById('modal_jnt').addEventListener('change', function() {
      if (this.checked) {
        // Disable COD for JNT
        document.getElementById('modal_cod').disabled = true;
        document.getElementById('modal_cod').parentElement.style.opacity = '0.5';
        document.getElementById('modal_cod').parentElement.style.cursor = 'not-allowed';
        // Auto-select GCash
        if (document.getElementById('modal_cod').checked) {
          document.getElementById('modal_gcash').checked = true;
        }
        calculateShipping();
      }
    });
    
    document.getElementById('modal_lalamove').addEventListener('change', function() {
      if (this.checked) {
        // Enable COD for Lalamove (tentative)
        document.getElementById('modal_cod').disabled = false;
        document.getElementById('modal_cod').parentElement.style.opacity = '1';
        document.getElementById('modal_cod').parentElement.style.cursor = 'pointer';
        modalShippingFee = 0; // Tentative - no fee yet
        updateModalTotals();
      }
    });
    
    // Island group mapping function (same as checkout.php)
    function getIslandGroup(regionName) {
      const luzon = ["NCR", "Ilocos Region", "Cagayan Valley", "Central Luzon", "CALABARZON", "MIMAROPA Region", "Cordillera Administrative Region", "Bicol Region", "CAR"];
      const visayas = ["Western Visayas", "Central Visayas", "Eastern Visayas"];
      const mindanao = ["Zamboanga Peninsula", "Northern Mindanao", "Davao Region", "SOCCSKSARGEN", "Caraga", "BARMM"];
      
      if (luzon.includes(regionName)) return 'luzon';
      if (visayas.includes(regionName)) return 'visayas';
      if (mindanao.includes(regionName)) return 'mindanao';
      return '';
    }
    
    // Calculate shipping based on delivery mode and region
    function calculateShipping() {
      const deliveryMode = document.querySelector('input[name="delivery_mode"]:checked').value;
      const regionSel = document.getElementById('modal_region');
      const regionName = regionSel.options[regionSel.selectedIndex]?.text || '';
      const group = getIslandGroup(regionName);
      
      if (deliveryMode === 'pickup') {
        modalShippingFee = 0;
      } else if (deliveryMode === 'lalamove') {
        modalShippingFee = 0; // Tentative - no fee yet
      } else if (deliveryMode === 'jnt') {
        if (group === 'luzon') modalShippingFee = 100;
        else if (group === 'visayas') modalShippingFee = 130;
        else if (group === 'mindanao') modalShippingFee = 150;
        else modalShippingFee = 0;
      }
      
      updateModalTotals();
    }
    
    // Update validation checklist
    function updateValidationChecklist() {
      // Check billing info
      const billingComplete = 
        document.getElementById('modal_first_name').value.trim() !== '' &&
        document.getElementById('modal_last_name').value.trim() !== '' &&
        document.getElementById('modal_email').value.trim() !== '' &&
        document.getElementById('modal_phone').value.trim() !== '' &&
        document.getElementById('modal_address').value.trim() !== '';
      
      document.getElementById('check_billing').checked = billingComplete;
      
      // Check location
      const locationComplete = 
        document.getElementById('modal_region').value !== '' &&
        document.getElementById('modal_municipality').value !== '' &&
        document.getElementById('modal_city').value !== '' &&
        document.getElementById('modal_barangay').value !== '' &&
        document.getElementById('modal_postal').value.trim() !== '';
      
      document.getElementById('check_location').checked = locationComplete;
    }
    
    function updateModalTotals() {
      const total = modalSubtotal + modalShippingFee;
      document.getElementById('modal_subtotal').textContent = modalSubtotal.toFixed(2);
      document.getElementById('modal_shipping').textContent = modalShippingFee.toFixed(2);
      document.getElementById('modal_total').textContent = total.toFixed(2);
    }
    
    // GCash Cooldown Handler
    let gcashCooldownRemaining = <?php echo $cooldown_remaining; ?>;
    
    function updateGCashCooldown() {
      const gcashRadio = document.getElementById('modal_gcash');
      const gcashContainer = gcashRadio ? gcashRadio.closest('.form-check') : null;
      
      if (!gcashContainer) return;
      
      if (gcashCooldownRemaining > 0) {
        // Disable GCash option
        gcashRadio.disabled = true;
        gcashContainer.style.opacity = '0.5';
        gcashContainer.style.cursor = 'not-allowed';
        gcashContainer.onclick = null;
        
        // Update label with countdown
        const minutes = Math.floor(gcashCooldownRemaining / 60);
        const seconds = gcashCooldownRemaining % 60;
        const label = gcashContainer.querySelector('label');
        const originalHTML = '<strong><i class="fas fa-mobile-alt me-2"></i>GCash</strong>';
        label.innerHTML = originalHTML + `<br><small class="text-danger">Cooldown: ${minutes}:${seconds.toString().padStart(2, '0')}</small>`;
        
        gcashCooldownRemaining--;
        
        // Continue countdown
        setTimeout(updateGCashCooldown, 1000);
      } else {
        // Re-enable GCash option
        gcashRadio.disabled = false;
        gcashContainer.style.opacity = '1';
        gcashContainer.style.cursor = 'pointer';
        gcashContainer.onclick = function() { gcashRadio.click(); };
        
        const label = gcashContainer.querySelector('label');
        label.innerHTML = '<strong><i class="fas fa-mobile-alt me-2"></i>GCash</strong>';
      }
    }
    
    // Start cooldown timer if needed
    if (gcashCooldownRemaining > 0) {
      updateGCashCooldown();
    }
    
    // Form submission
    document.getElementById('customizationCheckoutForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
      
      try {
        // Check if this is a subcontract or customization order
        const customizationIdInput = document.getElementById('checkout_customization_id');
        const isSubcontract = customizationIdInput.getAttribute('data-type') === 'subcontract';
        const endpoint = isSubcontract ? 'process_subcontract_order.php' : 'process_customization_order.php';
        
        // For subcontract, rename the field
        if (isSubcontract) {
          formData.delete('customization_id');
          formData.append('subcontract_id', customizationIdInput.value);
        }
        
        console.log('Submitting form to', endpoint);
        
        const response = await fetch(endpoint, {
          method: 'POST',
          body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        let data;
        try {
          data = JSON.parse(responseText);
        } catch (parseError) {
          console.error('JSON parse error:', parseError);
          console.error('Response was:', responseText);
          throw new Error('Invalid JSON response from server');
        }
        
        console.log('Parsed data:', data);
        
        if (data.success) {
          // Check if redirect to GCash
          if (data.redirect) {
            console.log('Redirecting to:', data.redirect);
            window.location.href = data.redirect;
            return;
          }
          
          // Close modal
          bootstrap.Modal.getInstance(document.getElementById('customizationCheckoutModal')).hide();
          
          // Show success message
          alert('Order placed successfully!');
          
          // Reload page
          window.location.reload();
        } else {
          alert('Error: ' + (data.error || 'Failed to place order'));
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
        }
      } catch (error) {
        console.error('Fetch error:', error);
        alert('Network error: ' + error.message + '\nPlease check console for details.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
      }
    });
    </script>
</body>
</html>
