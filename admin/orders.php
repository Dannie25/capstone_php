<?php
session_start();
include '../db.php';

// Force cache clear - VERSION 2.0
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: -1");
$_cache_buster = time(); // Force refresh

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get order statistics
$pending_orders = 0;
$shipped_orders = 0;
$completed_orders = 0;
$cancelled_orders = 0;
$total_orders = 0;

// Get pending orders count
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
if ($result && $row = $result->fetch_assoc()) {
    $pending_orders = $row['count'];
}

// Get shipped orders count
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'shipped'");
if ($result && $row = $result->fetch_assoc()) {
    $shipped_orders = $row['count'];
}

// Get completed orders count
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'");
if ($result && $row = $result->fetch_assoc()) {
    $completed_orders = $row['count'];
}

// Get cancelled orders count
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'");
if ($result && $row = $result->fetch_assoc()) {
    $cancelled_orders = $row['count'];
}

// Get total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result && $row = $result->fetch_assoc()) {
    $total_orders = $row['count'];
}

// Get subcontract statistics - FIXED QUERIES
$pending_subcontracts = 0;
$approved_subcontracts = 0;
$verifying_subcontracts = 0;
$inprogress_subcontracts = 0;
$completed_subcontracts = 0;
$cancelled_subcontracts = 0;
$total_subcontracts = 0;

// Get counts with proper error handling
$result = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'pending'");
$pending_subcontracts = $result ? $result->fetch_assoc()['c'] : 0;

$result = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'awaiting_confirmation'");
$approved_subcontracts = $result ? $result->fetch_assoc()['c'] : 0;

$result = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'in_progress'");
$inprogress_subcontracts = $result ? $result->fetch_assoc()['c'] : 0;

$result = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'to_deliver'");
$verifying_subcontracts = $result ? $result->fetch_assoc()['c'] : 0;

$result = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'completed'");
$completed_subcontracts = $result ? $result->fetch_assoc()['c'] : 0;

$result = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'cancelled'");
$cancelled_subcontracts = $result ? $result->fetch_assoc()['c'] : 0;

$result = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests");
$total_subcontracts = $result ? $result->fetch_assoc()['c'] : 0;

// Get recent orders
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$recent_orders = [];
$query = "SELECT id, first_name, last_name, email, total_amount, status, created_at, payment_method, delivery_mode, gcash_receipt, gcash_reference_number, CONCAT(first_name, ' ', last_name) as customer_name FROM orders";

$where_added = false;

// Add status filter if a status is selected
if (!empty($status_filter) && in_array($status_filter, ['pending', 'shipped', 'completed', 'cancelled'])) {
    $query .= " WHERE status = '" . $conn->real_escape_string($status_filter) . "'";
    $where_added = true;
}

// Add search filter if a search query is provided
if (!empty($search_filter)) {
    $query .= $where_added ? " AND " : " WHERE ";
    $query .= "id = '" . $conn->real_escape_string($search_filter) . "'";
}

$query .= " ORDER BY created_at DESC LIMIT 10";

$result = $conn->query($query);
if ($result) {
    $recent_orders = $result->fetch_all(MYSQLI_ASSOC);
}

// Get recent subcontract requests
$subcontract_status_filter = isset($_GET['subcontract_status']) ? $_GET['subcontract_status'] : '';
$recent_subcontracts = [];
$subcontract_query = "SELECT sr.*, u.name as customer_name 
                      FROM subcontract_requests sr 
                      LEFT JOIN users u ON sr.user_id = u.id";

$where_added_sub = false;

// Add status filter if a status is selected
if (!empty($subcontract_status_filter)) {
    $valid_statuses = ['pending', 'awaiting_confirmation', 'in_progress', 'to_deliver', 'completed', 'cancelled'];
    if (in_array($subcontract_status_filter, $valid_statuses)) {
        $subcontract_query .= " WHERE sr.status = '" . $conn->real_escape_string($subcontract_status_filter) . "'";
        $where_added_sub = true;
    }
}

$subcontract_query .= " ORDER BY sr.created_at DESC LIMIT 50";

$result = $conn->query($subcontract_query);
if ($result) {
    $recent_subcontracts = $result->fetch_all(MYSQLI_ASSOC);
}

// Get customization statistics
$pending_customizations = 0;
$approved_customizations = 0;
$verifying_customizations = 0;
$inprogress_customizations = 0;
$completed_customizations = 0;
$cancelled_customizations = 0;
$total_customizations = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status IN ('pending', 'submitted')");
if ($result && $row = $result->fetch_assoc()) {
    $pending_customizations = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'approved'");
if ($result && $row = $result->fetch_assoc()) {
    $approved_customizations = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'verifying'");
if ($result && $row = $result->fetch_assoc()) {
    $verifying_customizations = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'in_progress'");
if ($result && $row = $result->fetch_assoc()) {
    $inprogress_customizations = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'completed'");
if ($result && $row = $result->fetch_assoc()) {
    $completed_customizations = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'cancelled'");
if ($result && $row = $result->fetch_assoc()) {
    $cancelled_customizations = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests");
if ($result && $row = $result->fetch_assoc()) {
    $total_customizations = $row['count'];
}

// Get recent customization requests
$customization_status_filter = isset($_GET['customization_status']) ? $_GET['customization_status'] : '';
$recent_customizations = [];

// Select specific fields and join with users table to get the customer name
$customization_query = "SELECT 
    cr.*,
    cr.quoted_price as price,
    u.name as customer_name,
    u.email as contact_email
FROM customization_requests cr
LEFT JOIN users u ON cr.user_id = u.id";

$where_added_cust = false;

// Add status filter if a status is selected
if (!empty($customization_status_filter) && in_array($customization_status_filter, ['pending', 'submitted', 'approved', 'in_progress', 'completed', 'cancelled'])) {
    if ($customization_status_filter === 'pending') {
        $customization_query .= " WHERE cr.status IN ('pending', 'submitted')";
    } else {
        $customization_query .= " WHERE cr.status = '" . $conn->real_escape_string($customization_status_filter) . "'";
    }
    $where_added_cust = true;
}

$customization_query .= " ORDER BY cr.created_at DESC LIMIT 10";

$result = $conn->query($customization_query);
if ($result) {
    $recent_customizations = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Dashboard - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <audio id="notificationSound" style="display: none;"></audio>
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        
        /* Override container to work with sidebar */
        .container {
            max-width: 100%;
            padding: 0;
        }
        
        /* Recent Orders Notification */
        .recent-orders-notification {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 3px 10px rgba(255, 193, 7, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 3px 10px rgba(255, 193, 7, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 5px 15px rgba(255, 193, 7, 0.5); }
        }
        
        /* Pending Orders Dropdown */
        .notification-wrapper {
            position: relative;
        }
        
        .pending-orders-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            width: 350px;
            max-height: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            overflow: hidden;
        }
        
        .pending-orders-dropdown .dropdown-header {
            background: var(--primary-color);
            color: white;
            padding: 12px 15px;
            font-size: 0.95rem;
        }
        
        .pending-orders-list {
            max-height: 350px;
            overflow-y: auto;
        }
        
        .pending-order-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .pending-order-item:hover {
            background: #f8f9fa;
        }
        
        .pending-order-item:last-child {
            border-bottom: none;
        }
        
        .pending-order-id {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .pending-order-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
            text-transform: uppercase;
        }
        
        .type-order {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .type-subcontract {
            background-color: #cfe2ff;
            color: #084298;
        }
        
        .type-customization {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .pending-order-customer {
            font-size: 0.85rem;
            color: #666;
            margin-top: 2px;
        }
        
        .pending-order-amount {
            font-size: 0.9rem;
            font-weight: 600;
            color: #28a745;
            margin-top: 4px;
        }
        
        .pending-orders-empty {
            padding: 30px 15px;
            text-align: center;
            color: #999;
        }
        
        /* Popup Message */
        .popup-message {
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-right: 15px;
            background: linear-gradient(135deg, #5b6b46 0%, #4a5a36 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(91, 107, 70, 0.4);
            white-space: nowrap;
            animation: slideInLeft 0.5s ease-out, popupPulse 2s ease-in-out infinite;
            z-index: 999;
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateY(-50%) translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateY(-50%) translateX(0);
            }
        }
        
        @keyframes popupPulse {
            0%, 100% { 
                box-shadow: 0 4px 15px rgba(91, 107, 70, 0.4);
            }
            50% { 
                box-shadow: 0 6px 20px rgba(91, 107, 70, 0.6);
            }
        }
        
        .popup-message::after {
            content: '';
            position: absolute;
            right: -10px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid #4a5a36;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }
        
        /* Highlight for clicked order */
        .order-row-highlight {
            background-color: rgba(255, 193, 7, 0.7) !important;
            border-left: 5px solid #ff9800 !important;
            border-right: 5px solid #ff9800 !important;
            box-shadow: 0 0 20px rgba(255, 152, 0, 0.6) !important;
        }
        
        .order-row-highlight td {
            animation: highlightPulse 2s ease-in-out infinite !important;
        }
        
        @keyframes highlightPulse {
            0% { 
                background-color: rgba(255, 193, 7, 0.5);
            }
            50% { 
                background-color: rgba(255, 193, 7, 0.9);
            }
            100% { 
                background-color: rgba(255, 193, 7, 0.5);
            }
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
            font-size: 2rem;
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
        
        .stats-card {
            background: var(--white);
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 5px 0 0;
            font-weight: 500;
        }
        
        /* Status-specific colors */
        .card-pending {
            border-left-color: #ffc107;
        }
        .card-pending .stats-icon,
        .card-pending .stats-number {
            color: #ffc107;
        }
        
        .card-approved {
            border-left-color: #17a2b8;
        }
        .card-approved .stats-icon,
        .card-approved .stats-number {
            color: #17a2b8;
        }
        
        .card-verifying {
            border-left-color: #007bff;
        }
        .card-verifying .stats-icon,
        .card-verifying .stats-number {
            color: #007bff;
        }
        
        .card-inprogress {
            border-left-color: #6f42c1;
        }
        .card-inprogress .stats-icon,
        .card-inprogress .stats-number {
            color: #6f42c1;
        }
        
        .card-shipped {
            border-left-color: #17a2b8;
        }
        .card-shipped .stats-icon,
        .card-shipped .stats-number {
            color: #17a2b8;
        }
        
        .card-completed {
            border-left-color: #28a745;
        }
        .card-completed .stats-icon,
        .card-completed .stats-number {
            color: #28a745;
        }
        
        .card-cancelled {
            border-left-color: #dc3545;
        }
        .card-cancelled .stats-icon,
        .card-cancelled .stats-number {
            color: #dc3545;
        }
        
        .card-all {
            border-left-color: #6c757d;
        }
        .card-all .stats-icon,
        .card-all .stats-number {
            color: #6c757d;
        }
        
        .orders-table {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .table-header {
            background: var(--secondary-color);
            color: var(--primary-color);
            font-weight: 600;
            padding: 20px;
            margin: 0;
            font-size: 1.3rem;
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-submitted {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-verifying {
            background-color: #cfe2ff;
            color: #084298;
        }
        
        .status-in_progress {
            background-color: #e7d6f5;
            color: #6f42c1;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .order-row-cancelled {
            background-color: #fff5f5;
        }
        
        .order-row-cancelled:hover {
            background-color: #ffecec !important;
        }
        
        .action-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background-color: #4a5a36;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .admin-title {
                font-size: 1.5rem;
            }
            
            .stats-card {
                margin-bottom: 20px;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
        }
        
        /* Tab Styles */
        .nav-tabs {
            border-bottom: 2px solid var(--secondary-color);
            margin-bottom: 2rem;
        }
        
        .nav-tabs .nav-link {
            color: var(--primary-color);
            font-weight: 600;
            border: none;
            padding: 12px 24px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #4a5a36;
        }
        
        .nav-tabs .nav-link.active {
            color: white;
            background-color: var(--primary-color);
            border-radius: 8px 8px 0 0;
            border: none;
        }
        
        .tab-content {
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card-inprogress {
            border-left-color: #007bff;
        }
        .card-inprogress .stats-icon,
        .card-inprogress .stats-number {
            color: #007bff;
        }
        
        .status-in_progress {
            background-color: #cfe2ff;
            color: #084298;
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h1><i class="bi bi-bag-check"></i> Orders Dashboard</h1>
            <div class="d-flex align-items-center gap-3">
                <div class="notification-wrapper" style="position: relative;">
                    <div id="popup-message" class="popup-message" style="display: none;">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <span id="popup-message-text">You have new pending orders!</span>
                    </div>
                    <div id="recent-orders-notification" class="recent-orders-notification" style="display: flex; cursor: pointer;" onclick="togglePendingOrdersDropdown()">
                        <i class="fas fa-bell me-2"></i>
                        <span id="notification-text">New Order</span>
                        <span id="pending-count-badge" class="badge bg-light text-dark ms-2">0</span>
                    </div>
                    <div id="pending-orders-dropdown" class="pending-orders-dropdown" style="display: none;">
                        <div class="dropdown-header">
                            <strong>All Pending Items</strong>
                        </div>
                        <div id="pending-orders-list" class="pending-orders-list">
                            <!-- Pending items will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-body">
            <div class="container-fluid">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="orderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="true">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="subcontract-tab" data-bs-toggle="tab" data-bs-target="#subcontract" type="button" role="tab" aria-controls="subcontract" aria-selected="false">
                    <i class="fas fa-handshake me-2"></i>Sub-Contract
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="customization-tab" data-bs-toggle="tab" data-bs-target="#customization" type="button" role="tab" aria-controls="customization" aria-selected="false">
                    <i class="fas fa-paint-brush me-2"></i>Customization
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="orderTabsContent">
            <!-- Orders Tab -->
            <div class="tab-pane fade show active" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
            <div class="col">
                <a href="?status=pending" class="text-decoration-none">
                    <div class="stats-card card-pending">
                        <i class="fas fa-clock stats-icon"></i>
                        <h3 class="stats-number"><?php echo $pending_orders; ?></h3>
                        <p class="stats-label">Pending Orders</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?status=shipped" class="text-decoration-none">
                    <div class="stats-card card-shipped">
                        <i class="fas fa-truck stats-icon"></i>
                        <h3 class="stats-number"><?php echo $shipped_orders; ?></h3>
                        <p class="stats-label">Shipped Orders</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?status=completed" class="text-decoration-none">
                    <div class="stats-card card-completed">
                        <i class="fas fa-check-circle stats-icon"></i>
                        <h3 class="stats-number"><?php echo $completed_orders; ?></h3>
                        <p class="stats-label">Completed Orders</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?status=cancelled" class="text-decoration-none">
                    <div class="stats-card card-cancelled">
                        <i class="fas fa-times-circle stats-icon"></i>
                        <h3 class="stats-number"><?php echo $cancelled_orders; ?></h3>
                        <p class="stats-label">Cancelled Orders</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?" class="text-decoration-none">
                    <div class="stats-card card-all">
                        <i class="fas fa-shopping-cart stats-icon"></i>
                        <h3 class="stats-number"><?php echo $total_orders; ?></h3>
                        <p class="stats-label">All Orders</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="orders-table">
            <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #5b6b46; border-radius: 10px 10px 0 0; color: white;">
                <h2 class="mb-0" style="font-size: 1.25rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-list me-2"></i>Recent Orders
                </h2>
                <form method="GET" class="d-flex align-items-center" style="margin: 0;">
                    <?php if (!empty($status_filter)): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <div class="input-group" style="width: 250px;">
                        <input type="text" 
                               name="search" 
                               class="form-control form-control-sm" 
                               placeholder="Search Order ID" 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               style="border-radius: 20px 0 0 20px; border: none; box-shadow: none;">
                        <button type="submit" class="btn btn-sm btn-light" style="border-radius: 0 20px 20px 0; border: none;">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="?<?php echo !empty($status_filter) ? 'status=' . urlencode($status_filter) : ''; ?>" class="btn btn-sm btn-light ms-1" style="border-radius: 20px; border: none;">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Delivery Option</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No orders found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr class="<?php echo $order['status'] === 'cancelled' ? 'order-row-cancelled' : ''; ?>">
                                    <td><strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                                    <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            switch($order['payment_method']) {
                                                case 'cod': echo 'Cash on Delivery'; break;
                                                case 'gcash': echo 'GCash'; break;
                                                default: echo ucfirst($order['payment_method']);
                                            }
                                        ?>
                                        <?php if ($order['payment_method'] === 'gcash' && !empty($order['gcash_receipt'])): ?>
                                            <a href="../<?php echo htmlspecialchars($order['gcash_receipt']); ?>" target="_blank" class="btn btn-sm btn-primary ms-2" title="View GCash Receipt"><i class="fas fa-receipt"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
    <?php
    // DEBUG: show raw value for delivery_mode
    echo '<!-- delivery_mode raw: ' . htmlspecialchars(var_export($order['delivery_mode'], true)) . ' -->';
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
</td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm" style="background-color: white; border: 1px solid #5b6b46; color: #5b6b46;" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        title="Mark as Shipped"
                                                        onclick="showConfirmation(
                                                            '<?php echo $order['id']; ?>', 
                                                            '<?php echo addslashes($order['customer_name']); ?>', 
                                                            '<?php echo addslashes($order['email']); ?>', 
                                                            <?php echo $order['total_amount']; ?>, 
                                                            '<?php echo $order['status']; ?>', 
                                                            'shipped',
                                                            '<?php echo $order['payment_method']; ?>',
                                                            '<?php echo isset($order['gcash_receipt']) ? addslashes($order['gcash_receipt']) : ''; ?>',
                                                            '<?php echo isset($order['delivery_mode']) ? addslashes($order['delivery_mode']) : ''; ?>',
                                                            '<?php echo isset($order['gcash_reference_number']) ? addslashes($order['gcash_reference_number']) : ''; ?>'
                                                        )">
                                                    <i class="fas fa-truck"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        title="Cancel Order"
                                                        onclick="showCancelOrderModal(
                                                            '<?php echo $order['id']; ?>',
                                                            '<?php echo addslashes($order['customer_name']); ?>'
                                                        )">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($order['status'] === 'shipped'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        title="Mark as Completed"
                                                        onclick="showConfirmation(
                                                            '<?php echo $order['id']; ?>', 
                                                            '<?php echo addslashes($order['customer_name']); ?>', 
                                                            '<?php echo addslashes($order['email']); ?>', 
                                                            <?php echo $order['total_amount']; ?>, 
                                                            '<?php echo $order['status']; ?>', 
                                                            'completed',
                                                            '<?php echo $order['payment_method']; ?>',
                                                            '<?php echo isset($order['gcash_receipt']) ? addslashes($order['gcash_receipt']) : ''; ?>',
                                                            '<?php echo isset($order['delivery_mode']) ? addslashes($order['delivery_mode']) : ''; ?>',
                                                            '<?php echo isset($order['gcash_reference_number']) ? addslashes($order['gcash_reference_number']) : ''; ?>'
                                                        )">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
            </div>
            <!-- End Orders Tab -->

            <!-- Sub-Contract Tab -->
            <div class="tab-pane fade" id="subcontract" role="tabpanel" aria-labelledby="subcontract-tab">
                <!-- Subcontract Statistics Cards -->
                <?php
                // FORCE RECOUNT - DIRECT QUERIES
                $pending_subcontracts = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'pending'")->fetch_assoc()['c'];
                $approved_subcontracts = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'awaiting_confirmation'")->fetch_assoc()['c'];
                $inprogress_subcontracts = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'in_progress'")->fetch_assoc()['c'];
                $verifying_subcontracts = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'to_deliver'")->fetch_assoc()['c'];
                $completed_subcontracts = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'completed'")->fetch_assoc()['c'];
                $cancelled_subcontracts = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'cancelled'")->fetch_assoc()['c'];
                $total_subcontracts = $conn->query("SELECT COUNT(*) as c FROM subcontract_requests")->fetch_assoc()['c'];
                ?>
                <div class="row g-3 mb-4">
                    <div class="col">
                        <a href="?subcontract_status=pending#subcontract" class="text-decoration-none">
                            <div class="stats-card card-pending">
                                <i class="fas fa-clock stats-icon"></i>
                                <h3 class="stats-number"><?php echo $pending_subcontracts; ?></h3>
                                <p class="stats-label">Pending</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?subcontract_status=awaiting_confirmation#subcontract" class="text-decoration-none">
                            <div class="stats-card card-approved">
                                <i class="fas fa-hourglass-half stats-icon"></i>
                                <h3 class="stats-number"><?php echo $approved_subcontracts; ?></h3>
                                <p class="stats-label">Awaiting Confirmation</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?subcontract_status=in_progress#subcontract" class="text-decoration-none">
                            <div class="stats-card card-inprogress">
                                <i class="fas fa-spinner stats-icon"></i>
                                <h3 class="stats-number"><?php echo $inprogress_subcontracts; ?></h3>
                                <p class="stats-label">In Progress</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?subcontract_status=to_deliver#subcontract" class="text-decoration-none">
                            <div class="stats-card card-verifying">
                                <i class="fas fa-truck stats-icon"></i>
                                <h3 class="stats-number"><?php echo $verifying_subcontracts; ?></h3>
                                <p class="stats-label">To Deliver</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?subcontract_status=completed#subcontract" class="text-decoration-none">
                            <div class="stats-card card-completed">
                                <i class="fas fa-check-circle stats-icon"></i>
                                <h3 class="stats-number"><?php echo $completed_subcontracts; ?></h3>
                                <p class="stats-label">Completed</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?subcontract_status=cancelled#subcontract" class="text-decoration-none">
                            <div class="stats-card card-cancelled">
                                <i class="fas fa-times-circle stats-icon"></i>
                                <h3 class="stats-number"><?php echo $cancelled_subcontracts; ?></h3>
                                <p class="stats-label">Cancelled</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="orders.php#subcontract" class="text-decoration-none">
                            <div class="stats-card card-all">
                                <i class="fas fa-handshake stats-icon"></i>
                                <h3 class="stats-number"><?php echo $total_subcontracts; ?></h3>
                                <p class="stats-label">All Requests</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Subcontract Requests Table -->
                <div class="orders-table">
                    <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #5b6b46; border-radius: 10px 10px 0 0; color: white;">
                        <h2 class="mb-0" style="font-size: 1.25rem; font-weight: 600; margin: 0;">
                            <i class="fas fa-handshake me-2"></i>Recent Sub-Contract Requests
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Customer</th>
                                    <th>What For</th>
                                    <th>Quantity</th>
                                    <th>Date Needed</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_subcontracts)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No subcontract requests found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_subcontracts as $subcontract): ?>
                                        <tr class="<?php echo $subcontract['status'] === 'cancelled' ? 'order-row-cancelled' : ''; ?>">
                                            <td><strong>#<?php echo str_pad($subcontract['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                            <td><?php echo htmlspecialchars($subcontract['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subcontract['what_for']); ?></td>
                                            <td><?php echo $subcontract['quantity']; ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($subcontract['date_needed'] . ' ' . $subcontract['time_needed'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $subcontract['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $subcontract['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($subcontract['created_at'])); ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_subcontract.php?id=<?php echo $subcontract['id']; ?>" class="btn btn-sm" style="background-color: white; border: 1px solid #5b6b46; color: #5b6b46;" title="View Request">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($subcontract['status'] === 'pending'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary" 
                                                                title="Set Price & Send Quote"
                                                                onclick="showSubcontractPriceModal(<?php echo $subcontract['id']; ?>, '<?php echo addslashes(htmlspecialchars($subcontract['customer_name'] ?? 'Customer')); ?>')">
                                                            <i class="fas fa-tag"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                title="Cancel Request"
                                                                onclick="updateSubcontractStatus(<?php echo $subcontract['id']; ?>, 'cancelled')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($subcontract['status'] === 'awaiting_confirmation'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-info" 
                                                                title="Waiting for customer confirmation"
                                                                disabled>
                                                            <i class="fas fa-clock"></i>
                                                        </button>
                                                    <?php elseif ($subcontract['status'] === 'in_progress'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-warning" 
                                                                title="Mark as To Deliver"
                                                                onclick="updateSubcontractStatus(<?php echo $subcontract['id']; ?>, 'to_deliver')">
                                                            <i class="fas fa-truck"></i>
                                                        </button>
                                                    <?php elseif ($subcontract['status'] === 'to_deliver'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-success" 
                                                                title="Mark as Completed"
                                                                onclick="updateSubcontractStatus(<?php echo $subcontract['id']; ?>, 'completed')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Sub-Contract Tab -->

            <!-- Customization Tab -->
            <div class="tab-pane fade" id="customization" role="tabpanel" aria-labelledby="customization-tab">
                <!-- Customization Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col">
                        <a href="?customization_status=pending#customization" class="text-decoration-none">
                            <div class="stats-card card-pending">
                                <i class="fas fa-clock stats-icon"></i>
                                <h3 class="stats-number"><?php echo $pending_customizations; ?></h3>
                                <p class="stats-label">Pending</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?customization_status=approved#customization" class="text-decoration-none">
                            <div class="stats-card card-shipped">
                                <i class="fas fa-clipboard-check stats-icon"></i>
                                <h3 class="stats-number"><?php echo $approved_customizations; ?></h3>
                                <p class="stats-label">Awaiting Confirmation</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?customization_status=verifying#customization" class="text-decoration-none">
                            <div class="stats-card card-processing">
                                <i class="fas fa-check-double stats-icon"></i>
                                <h3 class="stats-number"><?php echo $verifying_customizations; ?></h3>
                                <p class="stats-label">Verifying</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?customization_status=in_progress#customization" class="text-decoration-none">
                            <div class="stats-card card-inprogress">
                                <i class="fas fa-spinner stats-icon"></i>
                                <h3 class="stats-number"><?php echo $inprogress_customizations; ?></h3>
                                <p class="stats-label">In Progress</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?customization_status=completed#customization" class="text-decoration-none">
                            <div class="stats-card card-completed">
                                <i class="fas fa-check-circle stats-icon"></i>
                                <h3 class="stats-number"><?php echo $completed_customizations; ?></h3>
                                <p class="stats-label">Completed</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="?customization_status=cancelled#customization" class="text-decoration-none">
                            <div class="stats-card card-cancelled">
                                <i class="fas fa-times-circle stats-icon"></i>
                                <h3 class="stats-number"><?php echo $cancelled_customizations; ?></h3>
                                <p class="stats-label">Cancelled</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="orders.php#customization" class="text-decoration-none">
                            <div class="stats-card card-all">
                                <i class="fas fa-paint-brush stats-icon"></i>
                                <h3 class="stats-number"><?php echo $total_customizations; ?></h3>
                                <p class="stats-label">All Requests</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Customization Requests Table -->
                <div class="orders-table">
                    <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #5b6b46; border-radius: 10px 10px 0 0; color: white;">
                        <h2 class="mb-0" style="font-size: 1.25rem; font-weight: 600; margin: 0;">
                            <i class="fas fa-paint-brush me-2"></i>Recent Customization Requests
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Delivery Option</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_customizations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No customization requests found</p>
                                            <small class="text-danger">DEBUG: Query = <?php echo htmlspecialchars($customization_query); ?></small>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <!-- DEBUG: Found <?php echo count($recent_customizations); ?> customizations -->
                                    <?php foreach ($recent_customizations as $customization): ?>
                                        <tr class="<?php echo $customization['status'] === 'cancelled' ? 'order-row-cancelled' : ''; ?>">
                                            <td><strong>#<?php echo str_pad($customization['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                            <td><?php echo !empty($customization['customer_name']) ? htmlspecialchars($customization['customer_name']) : 'N/A'; ?></td>
                                            <td><?php echo !empty($customization['contact_email']) ? htmlspecialchars($customization['contact_email']) : 'N/A'; ?></td>
                                            <td>₱<?php echo !empty($customization['price']) ? number_format($customization['price'], 2) : '0.00'; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $customization['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $customization['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($customization['payment_method']) ? ucfirst($customization['payment_method']) : 'N/A'; ?></td>
                                            <td><?php echo !empty($customization['delivery_mode']) ? ucfirst($customization['delivery_mode']) : 'N/A'; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($customization['created_at'])); ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_customization.php?id=<?php echo $customization['id']; ?>" class="btn btn-sm" style="background-color: white; border: 1px solid #5b6b46; color: #5b6b46;" title="View Request">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php 
                                                    $status = trim($customization['status']);
                                                    // DEBUG: Show status
                                                    echo "<!-- DEBUG: Status='" . htmlspecialchars($status) . "' IsInArray=" . (in_array($status, ['pending', 'submitted']) ? 'YES' : 'NO') . " -->";
                                                    if (in_array($status, ['pending', 'submitted'])): 
                                                    ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary" 
                                                                title="Set Price & Approve"
                                                                onclick="showPriceModal(<?php echo $customization['id']; ?>, '<?php echo addslashes(htmlspecialchars($customization['customer_name'] ?? 'Customer')); ?>')">
                                                            <i class="fas fa-tag"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                title="Cancel Request"
                                                                onclick="updateCustomizationStatus(event, <?php echo $customization['id']; ?>, 'cancelled')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($status === 'approved'): ?>
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-primary"
                                                                title="Mark as In Progress"
                                                                onclick="updateCustomizationStatus(event, <?php echo $customization['id']; ?>, 'in_progress')">
                                                            <i class="fas fa-spinner"></i>
                                                        </button>
                                                    <?php elseif ($status === 'in_progress'): ?>
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                title="Mark as Completed"
                                                                onclick="updateCustomizationStatus(event, <?php echo $customization['id']; ?>, 'completed')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Customization Tab -->
        </div>
    </div>

    <!-- Price Setting Modal (Customization) -->
    <div class="modal fade" id="priceModal" tabindex="-1" aria-labelledby="priceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="priceModalLabel">Set Customization Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="priceForm">
                        <input type="hidden" id="customizationId" name="id">
                        <div class="mb-3">
                            <label for="customerNameDisplay" class="form-label">Customer</label>
                            <input type="text" class="form-control" id="customerNameDisplay" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitPrice()">Set Price & Approve</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Subcontract Price Setting Modal -->
    <div class="modal fade" id="subcontractPriceModal" tabindex="-1" aria-labelledby="subcontractPriceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subcontractPriceModalLabel">Set Subcontract Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="subcontractPriceForm">
                        <input type="hidden" id="subcontractId" name="request_id">
                        <div class="mb-3">
                            <label for="subcontractCustomerName" class="form-label">Customer</label>
                            <input type="text" class="form-control" id="subcontractCustomerName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="subcontractPrice" class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" id="subcontractPrice" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="subcontractNotes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="subcontractNotes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitSubcontractPrice()">Set Price & Send Quote</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmStatusModal" tabindex="-1" aria-labelledby="confirmStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #5b6b46; color: white;">
                    <h5 class="modal-title" id="confirmStatusModalLabel">
                        <i class="fas fa-clipboard-check me-2"></i>Confirm Order Status Change
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Order #</strong> <span id="orderId" class="fw-bold"></span></p>
                            <p><strong>Customer:</strong> <span id="customerName"></span></p>
                            <p><strong>Email:</strong> <span id="customerEmail"></span></p>
                            <p class="mt-2" id="modalPaymentRow"></p>
                            <p id="modalGcashRefRow" style="display: none;"><strong>GCash Ref No.:</strong> <span id="modalGcashRef"></span></p>
<p><strong>Delivery Mode:</strong> <span id="modalDeliveryMode"></span></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong>Current Status:</strong> <span id="currentStatus" class="badge"></span></p>
                            <p><strong>New Status:</strong> <span id="newStatus" class="badge bg-success"></span></p>
                            <p class="mb-0"><strong>Total Amount:</strong> <span class="fw-bold">₱<span id="orderTotal"></span></span></p>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="orderItems">
                                <!-- Order items will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <form id="statusUpdateForm" method="POST" action="update_order_status.php" class="d-inline">
                        <input type="hidden" name="order_id" id="formOrderId">
                        <input type="hidden" name="status" id="formStatus">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Confirm Status Update
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showConfirmation(orderId, customerName, email, total, currentStatus, newStatus, paymentMethod = null, gcashReceipt = null, deliveryMode = null, gcashRefNumber = null) {
            // Update basic order info
            document.getElementById('orderId').textContent = orderId.toString().padStart(6, '0');
            document.getElementById('customerName').textContent = customerName;
            document.getElementById('customerEmail').textContent = email;
            document.getElementById('orderTotal').textContent = parseFloat(total).toFixed(2);

            // Show payment method
            let paymentHtml = '';
            if(paymentMethod === 'gcash') {
                paymentHtml = 'GCash';
                if(gcashReceipt) {
                    paymentHtml += ` <a href="../${gcashReceipt}" target="_blank" class="btn btn-sm btn-primary ms-2"><i class="fas fa-receipt"></i> View Receipt</a>`;
                }
            } else if(paymentMethod === 'cod') {
                paymentHtml = 'Cash on Delivery';
            } else {
                paymentHtml = paymentMethod ? paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1) : '';
            }
            let paymentRow = document.getElementById('modalPaymentRow');
            if(paymentRow) {
                paymentRow.innerHTML = `<strong>Payment Method:</strong> ${paymentHtml}`;
            }

            // Show GCash reference number if available
            let gcashRefRow = document.getElementById('modalGcashRefRow');
            let gcashRefSpan = document.getElementById('modalGcashRef');
            if(paymentMethod === 'gcash' && gcashRefNumber && gcashRefNumber.trim() !== '') {
                gcashRefSpan.textContent = gcashRefNumber;
                gcashRefRow.style.display = 'block';
            } else {
                gcashRefRow.style.display = 'none';
            }

            // Show delivery mode
            let deliveryLabel = '-';
            if (deliveryMode && deliveryMode.trim() !== '') {
                switch(deliveryMode.trim()) {
                    case 'pickup': deliveryLabel = 'Pick Up'; break;
                    case 'lalamove': deliveryLabel = 'Lalamove'; break;
                    case 'jnt': deliveryLabel = 'J&T Express'; break;
                    default: deliveryLabel = deliveryMode.charAt(0).toUpperCase() + deliveryMode.slice(1);
                }
            }
            let deliveryRow = document.getElementById('modalDeliveryMode');
            if(deliveryRow) {
                deliveryRow.textContent = deliveryLabel;
            }
            
            // Update status badges
            const currentStatusBadge = document.getElementById('currentStatus');
            currentStatusBadge.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
            currentStatusBadge.className = 'badge ' + getStatusBadgeClass(currentStatus);
            
            const newStatusBadge = document.getElementById('newStatus');
            newStatusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            newStatusBadge.className = 'badge ' + getStatusBadgeClass(newStatus);
            
            // Set form values
            document.getElementById('formOrderId').value = orderId;
            document.getElementById('formStatus').value = newStatus;

            
            // Load order items via AJAX
            loadOrderItems(orderId);
            
            // Show the modal
            var modal = new bootstrap.Modal(document.getElementById('confirmStatusModal'));
            modal.show();
        }
        
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'pending': return 'bg-warning text-dark';
                case 'shipped': return 'bg-info text-dark';
                case 'completed': return 'bg-success';
                case 'cancelled': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }
        
        function loadOrderItems(orderId) {
            const orderItemsContainer = document.getElementById('orderItems');
            orderItemsContainer.innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
            
            fetch(`get_order_items.php?order_id=${orderId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Check if we have a valid response
                    if (!data || typeof data !== 'object') {
                        throw new Error('Invalid response from server');
                    }
                    
                    // Check for error in response
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Get items from response
                    const items = data.items || [];
                    
                    if (items.length === 0) {
                        orderItemsContainer.innerHTML = '<tr><td colspan="4" class="text-center py-4">No items found in this order</td></tr>';
                        return;
                    }
                    
                    let html = '';
                    items.forEach(item => {
                        // Handle image path
                        let imageSrc = '';
                        if (item.image) {
                            // If image path is already a URL or starts with http
                            if (item.image.startsWith('http') || item.image.startsWith('//')) {
                                imageSrc = item.image;
                            } 
                            // If image path is relative
                            else {
                                // Remove any leading slashes or dots
                                let cleanPath = item.image.replace(/^[\/\.]+/, '');
                                // Add leading slash if not present
                                if (!cleanPath.startsWith('/')) {
                                    cleanPath = '/' + cleanPath;
                                }
                                // Add base URL if needed (adjust if your site uses a subdirectory)
                                imageSrc = cleanPath.startsWith('/capstone_php/') ? cleanPath : '/capstone_php' + cleanPath;
                            }
                        } else {
                            // Fallback to a placeholder image
                            imageSrc = 'https://via.placeholder.com/50x50?text=No+Image';
                        }
                        
                        const price = parseFloat(item.price || 0).toFixed(2);
                        const subtotal = (parseFloat(item.price || 0) * parseInt(item.quantity || 1)).toFixed(2);
                        
                        html += `
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="${imageSrc}" 
                                             alt="${item.product_name || 'Product'}" 
                                             class="img-thumbnail me-3" 
                                             style="width: 50px; height: 50px; object-fit: cover;"
                                             onerror="this.onerror=null; this.src='https://via.placeholder.com/50x50?text=No+Image';">
                                        <div>
                                            <h6 class="mb-0">${item.product_name || 'Unknown Product'}</h6>
                                            ${item.size ? `<small class="text-muted">Size: ${item.size}</small>` : ''}
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">₱${price}</td>
                                <td class="align-middle">${item.quantity || 1}</td>
                                <td class="text-end align-middle">₱${subtotal}</td>
                            </tr>
                        `;
                    });
                    
                    orderItemsContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading order items:', error);
                    orderItemsContainer.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Error loading order items. Please refresh the page and try again.
                                ${error.message ? `<div class="small mt-2">${error.message}</div>` : ''}
                            </td>
                        </tr>`;
                });
        }
    </script>
    <script>
    // Play notification sound with fallback if audio file is missing
    async function playNotificationSound() {
        try {
            const audioEl = document.getElementById('notificationSound');
            if (audioEl) {
                try {
                    await audioEl.play();
                    return;
                } catch (e) {
                    // fall through to beep
                }
            }
            // Fallback beep using Web Audio API
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.type = 'sine';
            o.frequency.value = 880; // A5
            g.gain.setValueAtTime(0.0001, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.01);
            g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
            o.connect(g);
            g.connect(ctx.destination);
            o.start();
            o.stop(ctx.currentTime + 0.22);
        } catch (err) {
            // As a last resort, do nothing silently
            console.warn('Notification sound unavailable.', err);
        }
    }

    // Real-time update for order statistics cards
    function updateOrderStats() {
        fetch('get_order_stats.php')
            .then(res => res.json())
            .then(stats => {
                if (!stats) return;
                document.querySelector('.card-pending .stats-number').textContent = stats.pending;
                document.querySelector('.card-shipped .stats-number').textContent = stats.shipped;
                document.querySelector('.card-completed .stats-number').textContent = stats.completed;
                document.querySelector('.card-cancelled .stats-number').textContent = stats.cancelled;
                document.querySelector('.card-all .stats-number').textContent = stats.total;
            });
    }
    setInterval(updateOrderStats, 2000);
    updateOrderStats();
    </script>
    <script>
    // AJAX polling for real-time orders table update
    function renderOrdersTable(orders, currentOrders, previousOrders) {
        const tbody = document.querySelector('.orders-table tbody');
        if (!tbody) return;
        if (!orders || orders.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4">
                <i class='fas fa-inbox fa-2x text-muted mb-2'></i>
                <p class='text-muted'>No orders found</p>
            </td></tr>`;
            return;
        }
        tbody.innerHTML = orders.map(order => {
            const statusClass = `status-badge status-${order.status}`;
            const rowClass = order.status === 'cancelled' ? 'order-row-cancelled' : '';
            let paymentLabel = '';
            switch(order.payment_method) {
                case 'cod': paymentLabel = 'Cash on Delivery'; break;
                case 'gcash': paymentLabel = 'GCash'; break;
                default: paymentLabel = order.payment_method ? order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1) : '';
            }
            let gcashReceiptBtn = '';
            if (order.payment_method === 'gcash' && order.gcash_receipt) {
                gcashReceiptBtn = `<a href="../${order.gcash_receipt}" target="_blank" class="btn btn-sm btn-primary ms-2" title="View GCash Receipt"><i class="fas fa-receipt"></i></a>`;
            }
            
            // Track new orders (no highlighting)
            const isNewOrder = false;
            let actions = `<div class='d-flex gap-2'>
                <a href='view_order.php?id=${order.id}' class='btn btn-sm' style='background-color: white; border: 1px solid #5b6b46; color: #5b6b46;' title='View Order'><i class='fas fa-eye'></i></a>`;
            if (order.status === 'pending') {
                actions += `<button type='button' class='btn btn-sm btn-outline-success' title='Mark as Shipped' onclick="showConfirmation('${order.id}','${order.customer_name.replace(/'/g, "&#39;")}', '${order.email.replace(/'/g, "&#39;")}', ${order.total_amount}, '${order.status}', 'shipped', '${order.payment_method}', '${order.gcash_receipt || ''}', '${order.delivery_mode || ''}', '${order.gcash_reference_number || ''}')"><i class='fas fa-truck'></i></button>`;
                actions += `<button type='button' class='btn btn-sm btn-outline-danger' title='Cancel Order' onclick="showCancelOrderModal('${order.id}','${order.customer_name.replace(/'/g, "&#39;")}')"><i class='fas fa-times'></i></button>`;
            } else if (order.status === 'shipped') {
                actions += `<button type='button' class='btn btn-sm btn-outline-success' title='Mark as Completed' onclick="showConfirmation('${order.id}','${order.customer_name.replace(/'/g, "&#39;")}', '${order.email.replace(/'/g, "&#39;")}', ${order.total_amount}, '${order.status}', 'completed', '${order.payment_method}', '${order.gcash_receipt || ''}', '${order.delivery_mode || ''}', '${order.gcash_reference_number || ''}')"><i class='fas fa-check'></i></button>`;
            }
            actions += '</div>';
            // DELIVERY MODE LOGIC
let deliveryLabel = '-';
if (order.delivery_mode && order.delivery_mode.trim() !== '') {
    switch(order.delivery_mode.trim()) {
        case 'lalamove': 
            deliveryLabel = 'Lalamove'; 
            break;
        case 'jnt': 
            deliveryLabel = 'J&T Express'; 
            break;
        default: 
            deliveryLabel = order.delivery_mode.charAt(0).toUpperCase() + order.delivery_mode.slice(1);
    }
}
return `<tr class='${rowClass}'>
    <td><strong>#${String(order.id).padStart(6, '0')}</strong></td>
    <td>${order.customer_name}</td>
    <td>${order.email}</td>
    <td><strong>₱${parseFloat(order.total_amount).toFixed(2)}</strong></td>
    <td><span class='${statusClass}'>${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></td>
    <td>${paymentLabel} ${gcashReceiptBtn}</td>
    <td>${deliveryLabel}</td>
    <td>${new Date(order.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
    <td>${actions}</td>
</tr>`;
        }).join('');
    }

    // Keep track of previously seen orders
    let previousOrders = new Set();
    let notificationSound = document.getElementById('notificationSound');

    function pollOrdersTable() {
        // Compose params for status/search if present
        let url = 'get_new_orders.php';
        const params = [];
        const status = new URLSearchParams(window.location.search).get('status');
        if (status) params.push('status=' + encodeURIComponent(status));
        if (params.length) url += '?' + params.join('&');
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data && data.orders) {
                    // Check for new orders
                    const currentOrders = new Set(data.orders.map(order => order.id));
                    let hasNewOrders = false;
                    let newOrderCount = 0;
                    
                    // Find new orders that weren't in the previous set
                    currentOrders.forEach(orderId => {
                        if (!previousOrders.has(orderId)) {
                            hasNewOrders = true;
                            newOrderCount++;
                        }
                    });

                    // Update notification - will be handled by separate polling
                    // Keep this for backward compatibility
                    if (data.stats && data.stats.pending !== undefined) {
                        // Stats will be updated by pollAllPending
                    }

                    // Update the orders table with highlight
                    renderOrdersTable(data.orders, currentOrders, previousOrders);
                    
                    // Update previous orders
                    previousOrders = currentOrders;
                }
            })
            .catch(err => { console.error('Error polling orders:', err); });
    }
    
    let lastPendingCount = 0;
    
    function showRecentOrdersNotification(pendingCount) {
        const notification = document.getElementById('recent-orders-notification');
        const badge = document.getElementById('pending-count-badge');
        const popupMessage = document.getElementById('popup-message');
        const popupText = document.getElementById('popup-message-text');
        
        if (notification && badge) {
            if (pendingCount > 0) {
                badge.textContent = pendingCount;
                notification.style.display = 'flex';
                
                // Show popup message if there are new orders
                if (pendingCount > lastPendingCount && lastPendingCount > 0) {
                    if (popupMessage && popupText) {
                        const newOrdersCount = pendingCount - lastPendingCount;
                        popupText.textContent = newOrdersCount === 1 
                            ? 'You have a new pending order!' 
                            : `You have ${newOrdersCount} new pending orders!`;
                        popupMessage.style.display = 'flex';
                        
                        // Hide popup after 8 seconds
                        setTimeout(() => {
                            popupMessage.style.display = 'none';
                        }, 8000);
                    }
                }
                
                lastPendingCount = pendingCount;
            } else {
                notification.style.display = 'none';
                if (popupMessage) {
                    popupMessage.style.display = 'none';
                }
                lastPendingCount = 0;
            }
        }
    }
    
    function togglePendingOrdersDropdown() {
        const dropdown = document.getElementById('pending-orders-dropdown');
        if (dropdown) {
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                loadPendingOrders();
            }
        }
    }
    
    function loadPendingOrders() {
        fetch('get_all_pending_v2.php')
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('pending-orders-list');
                if (!list) return;
                
                if (data && data.pending_items && data.pending_items.length > 0) {
                    list.innerHTML = data.pending_items.map(item => {
                        const typeLabel = item.type === 'order' ? 'Order' : 
                                        item.type === 'subcontract' ? 'Subcon' : 'Custom';
                        const typeClass = `type-${item.type}`;
                        const amount = item.total_amount ? parseFloat(item.total_amount).toFixed(2) : 'N/A';
                        
                        return `
                            <div class="pending-order-item" onclick="viewItem(${item.id}, '${item.type}')">
                                <div class="pending-order-id">
                                    #${String(item.id).padStart(6, '0')}
                                    <span class="pending-order-type ${typeClass}">${typeLabel}</span>
                                </div>
                                <div class="pending-order-customer">${item.customer_name || 'N/A'}</div>
                                <div class="pending-order-amount">₱${amount}</div>
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<div class="pending-orders-empty"><i class="fas fa-inbox fa-2x mb-2"></i><br>No pending items</div>';
                }
            })
            .catch(err => {
                console.error('Error loading pending items:', err);
            });
    }
    
    function viewItem(itemId, itemType) {
        // Close the dropdown
        const dropdown = document.getElementById('pending-orders-dropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
        
        // Redirect based on type
        if (itemType === 'order') {
            window.location.href = 'orders.php?status=pending&highlight=' + itemId;
        } else if (itemType === 'subcontract') {
            window.location.href = 'orders.php?subcontract_status=pending&highlight=' + itemId + '#subcontract';
        } else if (itemType === 'customization') {
            window.location.href = 'orders.php?customization_status=pending&highlight=' + itemId + '#customization';
        }
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('pending-orders-dropdown');
        const notification = document.getElementById('recent-orders-notification');
        
        if (dropdown && notification && 
            !dropdown.contains(e.target) && 
            !notification.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            <strong><i class="fas fa-bell me-2"></i>${message}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(notification);
        
        // Remove notification after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Poll for all pending items notification
    function pollAllPending() {
        fetch('get_all_pending_v2.php')
            .then(res => res.json())
            .then(data => {
                console.log('All pending data:', data);
                if (data && data.stats) {
                    console.log('Total pending:', data.stats.total);
                    showRecentOrdersNotification(data.stats.total);
                }
            })
            .catch(err => { console.error('Error polling all pending:', err); });
    }
    
    setInterval(pollOrdersTable, 2000); // Poll every 2 seconds
    setInterval(pollAllPending, 2000); // Poll for notification
    // Initial load
    pollOrdersTable();
    pollAllPending();
    </script>
    <script>
    function showPriceModal(id, customerName) {
        try {
            const idInput = document.getElementById('customizationId');
            const nameInput = document.getElementById('customerNameDisplay');
            if (idInput) idInput.value = id;
            if (nameInput) nameInput.value = customerName || 'Customer';
            const modalEl = document.getElementById('priceModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        } catch (e) {
            console.error('Error opening price modal:', e);
        }
    }

    async function submitPrice() {
        const form = document.getElementById('priceForm');
        if (!form) return;
        const formData = new FormData(form);
        const submitBtn = Array.from(document.querySelectorAll('#priceModal .btn.btn-primary'))[0];
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            }
            const resp = await fetch('update_customization_price.php', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (!data || !data.success) {
                throw new Error(data && data.message ? data.message : 'Failed to update price');
            }
            // Close modal then reload customization tab
            const modalEl = document.getElementById('priceModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
            }
            // Keep user on customization tab
            window.location.href = 'orders.php#customization';
            window.location.reload();
        } catch (e) {
            console.error(e);
            alert('Error: ' + e.message);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Set Price & Approve';
            }
        }
    }

    // Subcontract Price Modal Functions
    function showSubcontractPriceModal(id, customerName) {
        try {
            const idInput = document.getElementById('subcontractId');
            const nameInput = document.getElementById('subcontractCustomerName');
            const priceInput = document.getElementById('subcontractPrice');
            const notesInput = document.getElementById('subcontractNotes');
            
            if (idInput) idInput.value = id;
            if (nameInput) nameInput.value = customerName || 'Customer';
            if (priceInput) priceInput.value = '';
            if (notesInput) notesInput.value = '';
            
            const modalEl = document.getElementById('subcontractPriceModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        } catch (e) {
            console.error('Error opening subcontract price modal:', e);
        }
    }

    async function submitSubcontractPrice() {
        const form = document.getElementById('subcontractPriceForm');
        if (!form) return;
        
        const formData = new FormData(form);
        formData.append('status', 'awaiting_confirmation'); // Set status to awaiting_confirmation
        
        const submitBtn = Array.from(document.querySelectorAll('#subcontractPriceModal .btn.btn-primary'))[0];
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            }
            
            const resp = await fetch('update_subcontract_status.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await resp.json();
            
            if (!data || !data.success) {
                throw new Error(data && data.message ? data.message : 'Failed to set price');
            }
            
            // Close modal
            const modalEl = document.getElementById('subcontractPriceModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
            }
            
            // Reload to show updated data
            window.location.href = 'orders.php#subcontract';
            window.location.reload();
            
        } catch (e) {
            console.error(e);
            alert('Error: ' + e.message);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Set Price & Send Quote';
            }
        }
    }

    async function updateCustomizationStatus(event, id, status) {
        if (event) event.preventDefault();
        const confirmMap = {
            approved: 'Approve this customization request? The customer will need to confirm the price if already set.',
            cancelled: 'Cancel this customization request?',
            in_progress: 'Mark this customization as in progress?',
            completed: 'Mark this customization as completed?'
        };
        const message = confirmMap[status] || `Change status to ${status}?`;
        if (!confirm(message)) return;

        try {
            const params = new URLSearchParams();
            params.append('request_id', String(id));
            params.append('status', status);
            const resp = await fetch('update_customization_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            });
            const data = await resp.json();
            if (!data || !data.success) {
                throw new Error(data && data.message ? data.message : 'Failed to update status');
            }
            // Refresh current view on customization tab
            window.location.href = 'orders.php#customization';
            window.location.reload();
        } catch (e) {
            console.error(e);
            alert('Error: ' + e.message);
        }
    }
    </script>
    <!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="cancelOrderForm" method="POST" action="cancel_order.php">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="cancelOrderModalLabel"><i class="fas fa-times me-2"></i>Cancel Order</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="order_id" id="cancelOrderId">
          <div class="mb-3">
            <label for="cancelOrderCustomer" class="form-label">Customer</label>
            <input type="text" id="cancelOrderCustomer" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label for="cancelReason" class="form-label">Reason for Cancellation</label>
            <textarea name="cancel_reason" id="cancelReason" class="form-control" required rows="3" placeholder="Enter reason..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-danger"><i class="fas fa-times me-1"></i>Confirm Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function showCancelOrderModal(orderId, customerName) {
  document.getElementById('cancelOrderId').value = orderId;
  document.getElementById('cancelOrderCustomer').value = customerName;
  document.getElementById('cancelReason').value = '';
  var modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
  modal.show();
}

// Function to update subcontract status
function updateSubcontractStatus(requestId, newStatus) {
    const statusText = newStatus.replace('_', ' ');
    if (confirm(`Are you sure you want to mark this request as ${statusText}?`)) {
        fetch('update_subcontract_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `request_id=${requestId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status updated successfully!');
                window.location.reload();
            } else {
                alert('Error updating status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the status.');
        });
    }
}


// AJAX polling for subcontract table
function renderSubcontractsTable(subcontracts) {
    const tbody = document.querySelector('#subcontract .orders-table tbody');
    if (!tbody) return;
    
    if (!subcontracts || subcontracts.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4">
            <i class='fas fa-inbox fa-2x text-muted mb-2'></i>
            <p class='text-muted'>No subcontract requests found</p>
        </td></tr>`;
        return;
    }
    
    tbody.innerHTML = subcontracts.map(subcontract => {
        const statusClass = `status-badge status-${subcontract.status}`;
        const rowClass = subcontract.status === 'cancelled' ? 'order-row-cancelled' : '';
        const dateNeeded = new Date(subcontract.date_needed + ' ' + subcontract.time_needed);
        
        const customerNameEscaped = (subcontract.customer_name || 'Customer').replace(/'/g, "\\'");
        
        let actions = `<div class='d-flex gap-2'>
            <a href='view_subcontract.php?id=${subcontract.id}' class='btn btn-sm' style='background-color: white; border: 1px solid #5b6b46; color: #5b6b46;' title='View Request'><i class='fas fa-eye'></i></a>`;
        
        if (subcontract.status === 'pending') {
            actions += `<button type='button' class='btn btn-sm btn-outline-primary' title='Set Price & Send Quote' onclick="showSubcontractPriceModal(${subcontract.id}, '${customerNameEscaped}')"><i class='fas fa-tag'></i></button>`;
            actions += `<button type='button' class='btn btn-sm btn-outline-danger' title='Cancel Request' onclick="updateSubcontractStatus(${subcontract.id}, 'cancelled')"><i class='fas fa-times'></i></button>`;
        } else if (subcontract.status === 'awaiting_confirmation') {
            actions += `<button type='button' class='btn btn-sm btn-outline-info' title='Waiting for customer confirmation' disabled><i class='fas fa-clock'></i></button>`;
        } else if (subcontract.status === 'in_progress') {
            actions += `<button type='button' class='btn btn-sm btn-outline-warning' title='Mark as To Deliver' onclick="updateSubcontractStatus(${subcontract.id}, 'to_deliver')"><i class='fas fa-truck'></i></button>`;
        } else if (subcontract.status === 'to_deliver') {
            actions += `<button type='button' class='btn btn-sm btn-outline-success' title='Mark as Completed' onclick="updateSubcontractStatus(${subcontract.id}, 'completed')"><i class='fas fa-check'></i></button>`;
        }
        actions += '</div>';
        
        return `<tr class='${rowClass}'>
            <td><strong>#${String(subcontract.id).padStart(6, '0')}</strong></td>
            <td>${subcontract.customer_name || 'N/A'}</td>
            <td>${subcontract.what_for || 'N/A'}</td>
            <td>${subcontract.quantity || 0}</td>
            <td>${dateNeeded.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} ${dateNeeded.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</td>
            <td><span class='${statusClass}'>${subcontract.status.replace('_', ' ').charAt(0).toUpperCase() + subcontract.status.replace('_', ' ').slice(1)}</span></td>
            <td>${new Date(subcontract.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
            <td>${actions}</td>
        </tr>`;
    }).join('');
}

function updateSubcontractStats(stats) {
    if (!stats) return;
    const subcontractTab = document.querySelector('#subcontract');
    if (!subcontractTab) return;
    
    const statCards = subcontractTab.querySelectorAll('.stats-number');
    if (statCards.length >= 7) {
        statCards[0].textContent = stats.pending || 0;
        statCards[1].textContent = stats.awaiting_confirmation || 0;
        statCards[2].textContent = stats.in_progress || 0;
        statCards[3].textContent = stats.to_deliver || 0;
        statCards[4].textContent = stats.completed || 0;
        statCards[5].textContent = stats.cancelled || 0;
        statCards[6].textContent = stats.total || 0;
    }
}

function pollSubcontractsTable() {
    let url = 'get_new_subcontracts.php';
    const params = [];
    const status = new URLSearchParams(window.location.search).get('subcontract_status');
    if (status) params.push('subcontract_status=' + encodeURIComponent(status));
    if (params.length) url += '?' + params.join('&');
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data) {
                if (data.subcontracts) {
                    renderSubcontractsTable(data.subcontracts);
                }
                if (data.stats) {
                    updateSubcontractStats(data.stats);
                }
            }
        })
        .catch(err => { console.error('Error polling subcontracts:', err); });
}

// AJAX polling for customization table
function renderCustomizationsTable(customizations) {
    const tbody = document.querySelector('#customization .orders-table tbody');
    if (!tbody) return;
    
    if (!customizations || customizations.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4">
            <i class='fas fa-inbox fa-2x text-muted mb-2'></i>
            <p class='text-muted'>No customization requests found</p>
        </td></tr>`;
        return;
    }
    
    tbody.innerHTML = customizations.map(customization => {
        const statusClass = `status-badge status-${customization.status}`;
        const rowClass = customization.status === 'cancelled' ? 'order-row-cancelled' : '';
        const customerName = customization.customer_name || 'N/A';
        const customerNameEscaped = customerName.replace(/'/g, "&#39;");
        
        let actions = `<div class='d-flex gap-2'>
            <a href='view_customization.php?id=${customization.id}' class='btn btn-sm' style='background-color: white; border: 1px solid #5b6b46; color: #5b6b46;' title='View Request'><i class='fas fa-eye'></i></a>`;
        
        // Only show cancel button until verifying status
        if (customization.status === 'pending' || customization.status === 'submitted') {
            actions += `<button type='button' class='btn btn-sm btn-outline-primary' title='Set Price & Approve' onclick="showPriceModal(${customization.id}, '${customerNameEscaped}')"><i class='fas fa-tag'></i></button>`;
            actions += `<button type='button' class='btn btn-sm btn-outline-danger' title='Cancel Request' onclick="updateCustomizationStatus(event, ${customization.id}, 'cancelled')"><i class='fas fa-times'></i></button>`;
        } else if (customization.status === 'approved') {
            actions += `<button type='button' class='btn btn-sm btn-outline-danger' title='Cancel Request' onclick="updateCustomizationStatus(event, ${customization.id}, 'cancelled')"><i class='fas fa-times'></i></button>`;
        } else if (customization.status === 'verifying') {
            actions += `<button type='button' class='btn btn-sm btn-outline-success' title='Approve Order' onclick="updateCustomizationStatus(event, ${customization.id}, 'approved')"><i class='fas fa-check'></i></button>`;
            actions += `<button type='button' class='btn btn-sm btn-outline-danger' title='Cancel Order' onclick="updateCustomizationStatus(event, ${customization.id}, 'cancelled')"><i class='fas fa-times'></i></button>`;
        } else if (customization.status === 'in_progress') {
            // No cancel button - only complete button
            actions += `<button type='button' class='btn btn-sm btn-outline-success' title='Mark as Completed' onclick="updateCustomizationStatus(event, ${customization.id}, 'completed')"><i class='fas fa-check'></i></button>`;
        }
        actions += '</div>';
        
        const email = customization.contact_email || 'N/A';
        const total = customization.price ? `₱${parseFloat(customization.price).toFixed(2)}` : '₱0.00';
        const payment = customization.payment_method ? customization.payment_method.charAt(0).toUpperCase() + customization.payment_method.slice(1) : 'N/A';
        const delivery = customization.delivery_mode ? customization.delivery_mode.charAt(0).toUpperCase() + customization.delivery_mode.slice(1) : 'N/A';
        
        return `<tr class='${rowClass}'>
            <td><strong>#${String(customization.id).padStart(6, '0')}</strong></td>
            <td>${customerName}</td>
            <td>${email}</td>
            <td>${total}</td>
            <td><span class='${statusClass}'>${customization.status.replace('_', ' ').charAt(0).toUpperCase() + customization.status.replace('_', ' ').slice(1)}</span></td>
            <td>${payment}</td>
            <td>${delivery}</td>
            <td>${new Date(customization.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
            <td>${actions}</td>
        </tr>`;
    }).join('');
}

function updateCustomizationStats(stats) {
    if (!stats) return;
    const customizationTab = document.querySelector('#customization');
    if (!customizationTab) return;
    
    const statCards = customizationTab.querySelectorAll('.stats-number');
    if (statCards.length >= 7) {
        statCards[0].textContent = stats.pending || 0;
        statCards[1].textContent = stats.approved || 0;
        statCards[2].textContent = stats.verifying || 0;
        statCards[3].textContent = stats.in_progress || 0;
        statCards[4].textContent = stats.completed || 0;
        statCards[5].textContent = stats.cancelled || 0;
        statCards[6].textContent = stats.total || 0;
    }
}

function pollCustomizationsTable() {
    let url = 'get_new_customizations.php';
    const params = [];
    const status = new URLSearchParams(window.location.search).get('customization_status');
    if (status) params.push('customization_status=' + encodeURIComponent(status));
    if (params.length) url += '?' + params.join('&');
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data) {
                if (data.customizations) {
                    renderCustomizationsTable(data.customizations);
                }
                if (data.stats) {
                    updateCustomizationStats(data.stats);
                }
            }
        })
        .catch(err => { console.error('Error polling customizations:', err); });
}

// Start polling for all tables
setInterval(pollSubcontractsTable, 3000); // Poll every 3 seconds
setInterval(pollCustomizationsTable, 3000); // Poll every 3 seconds

// Initial load
pollSubcontractsTable();
pollCustomizationsTable();

// Handle tab switching based on URL hash
document.addEventListener('DOMContentLoaded', function() {
    // Check if URL has hash and switch to appropriate tab
    if (window.location.hash === '#subcontract') {
        const subcontractTab = new bootstrap.Tab(document.getElementById('subcontract-tab'));
        subcontractTab.show();
    } else if (window.location.hash === '#customization') {
        const customizationTab = new bootstrap.Tab(document.getElementById('customization-tab'));
        customizationTab.show();
    }
    
    // Check if there's a highlight parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (highlightId) {
        // Wait for table to load, then highlight the item
        setTimeout(() => {
            highlightItemInTable(highlightId);
        }, 1000);
    }
    
    // Function to highlight item in any table
    function highlightItemInTable(itemId) {
        const idFormatted = String(itemId).padStart(6, '0');
        
        // Try all tables (orders, subcontract, customization)
        const allTables = document.querySelectorAll('.orders-table tbody');
        
        allTables.forEach(tbody => {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const idCell = row.querySelector('td:first-child');
                if (idCell && idCell.textContent.includes('#' + idFormatted)) {
                    // Remove previous highlights from all tables
                    document.querySelectorAll('.order-row-highlight').forEach(r => {
                        r.classList.remove('order-row-highlight');
                    });
                    
                    // Highlight the target row
                    row.classList.add('order-row-highlight');
                    
                    // Scroll to the row smoothly
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Remove highlight after 20 seconds
                    setTimeout(() => {
                        row.classList.remove('order-row-highlight');
                    }, 20000);
                }
            });
        });
    }
    
    // Update URL hash when tab is clicked
    const tabButtons = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function (event) {
            const targetId = event.target.getAttribute('data-bs-target');
            if (targetId === '#subcontract') {
                window.location.hash = 'subcontract';
            } else if (targetId === '#customization') {
                window.location.hash = 'customization';
            } else {
                history.pushState("", document.title, window.location.pathname + window.location.search);
            }
        });
    });
});
</script>
            </div>
        </div>
    </div>
</div>
</body>
</html>
