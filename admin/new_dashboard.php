<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

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
    <title>MTC Admin Dashboard - Modern Cards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: white;
            text-align: center;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--accent-color);
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        
        .card-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            background: var(--accent-color);
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .card-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .card-link {
            display: inline-flex;
            align-items: center;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .card-link:hover {
            color: var(--accent-color);
            transform: translateX(5px);
        }
        
        .card-link i {
            margin-left: 0.5rem;
            transition: transform 0.3s ease;
        }
        
        .card-link:hover i {
            transform: translateX(3px);
        }
        
        /* Card Colors */
        .cms-card { --accent-color: #e74c3c; }
        .sales-card { --accent-color: #27ae60; }
        .orders-card { --accent-color: #3498db; }
        .products-card { --accent-color: #f39c12; }
        .feedback-card { --accent-color: #9b59b6; }
        .inquiries-card { --accent-color: #1abc9c; }
        .users-card { --accent-color: #34495e; }
        
        .logout-section {
            text-align: center;
            margin-top: 3rem;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(238, 90, 36, 0.4);
            color: white;
        }
        
        .success-message {
            background: rgba(46, 204, 113, 0.1);
            border: 2px solid #2ecc71;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .dashboard-card {
                padding: 1.5rem;
            }
            
            .card-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="bi bi-shop me-2"></i>MTC Admin Dashboard - Modern Cards</h1>
    </div>
    
    <div class="dashboard-container">
        <div class="success-message">
            <i class="bi bi-check-circle me-2"></i>
            New Modern Card Design Successfully Loaded!
        </div>
        
        <div class="cards-grid">
            <!-- CMS Card -->
            <div class="dashboard-card cms-card">
                <div class="card-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="card-title">Content Management</div>
                <div class="card-value">CMS</div>
                <a href="cms.php" class="card-link">
                    Manage Content <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <!-- Sales Card -->
            <div class="dashboard-card sales-card">
                <div class="card-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="card-title">Total Sales</div>
                <div class="card-value">₱<?php echo number_format($total_sales, 0); ?></div>
                <a href="sales.php" class="card-link">
                    View Sales <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <!-- Orders Card -->
            <div class="dashboard-card orders-card">
                <div class="card-icon">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="card-title">Orders</div>
                <div class="card-value"><?php echo $total_orders; ?></div>
                <a href="orders.php" class="card-link">
                    View Orders <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <!-- Products Card -->
            <div class="dashboard-card products-card">
                <div class="card-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="card-title">Products</div>
                <div class="card-value"><?php echo $total_products; ?></div>
                <a href="product.php" class="card-link">
                    Manage Products <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <!-- Feedback Card -->
            <div class="dashboard-card feedback-card">
                <div class="card-icon">
                    <i class="bi bi-chat-heart"></i>
                </div>
                <div class="card-title">Feedback</div>
                <div class="card-value"><?php echo $total_feedback; ?></div>
                <a href="feedback.php" class="card-link">
                    View Feedback <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <!-- Inquiries Card -->
            <div class="dashboard-card inquiries-card">
                <div class="card-icon">
                    <i class="bi bi-envelope-heart"></i>
                </div>
                <div class="card-title">Inquiries</div>
                <div class="card-value"><?php echo $total_inquiries; ?></div>
                <a href="inquiries.php" class="card-link">
                    View Inquiries <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <!-- Users Card -->
            <div class="dashboard-card users-card">
                <div class="card-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="card-title">Users</div>
                <div class="card-value"><?php echo $total_users; ?></div>
                <a href="customers.php" class="card-link">
                    Manage Users <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
