<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Redirect to sales.php as default page
header('Location: sales.php');
exit();

// Get real data from database
$total_sales = 0;
$total_orders = 0;
$total_products = 0;
$total_feedback = 0;
$total_inquiries = 0;
$total_users = 0;

// Count products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result) {
    $row = $result->fetch_assoc();
    $total_products = $row['count'];
}

// Count orders (if orders table exists)
$result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_orders = $row['count'];
    }
    
    // Calculate total sales from orders
    $result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_sales = $row['total'] ? $row['total'] : 0;
    }
}

// Count feedback (if feedback table exists)
$result = $conn->query("SHOW TABLES LIKE 'feedback'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM feedback");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_feedback = $row['count'];
    }
}

// Count inquiries (if inquiries table exists)
$result = $conn->query("SHOW TABLES LIKE 'inquiries'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM inquiries");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_inquiries = $row['count'];
    }
}

// Count users (if users table exists)
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_users = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTC Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1>Dashboard</h1>
        </div>
        
        <div class="content-body">
            <div class="dashboard-grid">
                <!-- CMS Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon blue">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <div class="card-title">Content Management</div>
                        </div>
                    </div>
                    <div class="card-value">CMS</div>
                    <a href="cms.php" class="card-link">
                        Manage Content <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Sales Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon green">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <div class="card-title">Total Sales</div>
                        </div>
                    </div>
                    <div class="card-value">₱<?php echo number_format($total_sales, 0); ?></div>
                    <a href="sales.php" class="card-link">
                        View Sales <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Orders Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon purple">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div>
                            <div class="card-title">Orders</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $total_orders; ?></div>
                    <a href="orders.php" class="card-link">
                        View Orders <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Products Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon orange">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>
                            <div class="card-title">Products</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $total_products; ?></div>
                    <a href="product.php" class="card-link">
                        Manage Products <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Feedback Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon red">
                            <i class="bi bi-chat-heart"></i>
                        </div>
                        <div>
                            <div class="card-title">Feedback</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $total_feedback; ?></div>
                    <a href="feedback.php" class="card-link">
                        View Feedback <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Users Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon teal">
                            <i class="bi bi-people"></i>
                        </div>
                        <div>
                            <div class="card-title">Users</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $total_users; ?></div>
                    <a href="customers.php" class="card-link">
                        Manage Users <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Customer Chat Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon blue">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div>
                            <div class="card-title">Customer Chat</div>
                        </div>
                    </div>
                    <div class="card-value">Messages</div>
                    <a href="admin_chat.php" class="card-link">
                        Go to Admin Chat <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
