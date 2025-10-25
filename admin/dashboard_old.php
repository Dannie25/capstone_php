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
  <meta charset="UTF-8" />
  <title>Admin Dashboard - MTC Clothing</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .navbar {
      background: rgba(255, 255, 255, 0.95) !important;
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    }
    
    .navbar-brand {
      font-weight: 700;
      color: #333 !important;
      font-size: 1.5rem;
    }
    
    .dashboard-container {
      padding: 2rem 0;
    }
    
    .welcome-card {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      color: white;
      border: none;
      margin-bottom: 2rem;
    }
    
    .stats-card {
      background: white;
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      overflow: hidden;
      position: relative;
    }
    
    .stats-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .stats-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--card-color);
    }
    
    .card-icon {
      width: 60px;
      height: 60px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
      background: var(--card-color);
      margin-bottom: 1rem;
    }
    
    .card-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: #333;
      margin: 0;
    }
    
    .card-title {
      color: #666;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 0;
    }
    
    .card-link {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 35px;
      height: 35px;
      border-radius: 50%;
      background: rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      color: #666;
      transition: all 0.3s ease;
    }
    
    .card-link:hover {
      background: var(--card-color);
      color: white;
      transform: scale(1.1);
    }
    
    .sales-card { --card-color: #10ac84; }
    .orders-card { --card-color: #3742fa; }
    .products-card { --card-color: #ff6348; }
    .feedback-card { --card-color: #ffa502; }
    .inquiries-card { --card-color: #5f27cd; }
    .users-card { --card-color: #00d2d3; }
    .cms-card { --card-color: #ff3838; }
    
    .logout-btn {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      border: none;
      border-radius: 25px;
      padding: 0.5rem 1.5rem;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(238, 90, 36, 0.4);
      color: white;
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="#">
        <i class="bi bi-shop me-2"></i>MTC Admin Dashboard
      </a>
      <div class="ms-auto">
        <a href="logout.php" class="logout-btn">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
      </div>
    </div>
  </nav>

  <div class="container dashboard-container">
    <!-- Welcome Card -->
    <div class="card welcome-card">
      <div class="card-body text-center py-4">
        <h2 class="mb-2">Welcome back, Admin!</h2>
        <p class="mb-0">Here's what's happening with your store today.</p>
      </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="row g-4">
      <!-- Content Management -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card cms-card h-100">
          <div class="card-body p-4">
            <a href="cms.php" class="card-link">
              <i class="bi bi-arrow-right"></i>
            </a>
            <div class="card-icon">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <h3 class="card-number">CMS</h3>
            <p class="card-title">Content Management</p>
          </div>
        </div>
      </div>

      <!-- Sales -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card sales-card h-100">
          <div class="card-body p-4">
            <a href="sales.php" class="card-link">
              <i class="bi bi-arrow-right"></i>
            </a>
            <div class="card-icon">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
            <h3 class="card-number">₱<?php echo number_format($total_sales, 0); ?></h3>
            <p class="card-title">Total Sales</p>
          </div>
        </div>
      </div>

      <!-- Orders -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card orders-card h-100">
          <div class="card-body p-4">
            <a href="orders.php" class="card-link">
              <i class="bi bi-arrow-right"></i>
            </a>
            <div class="card-icon">
              <i class="bi bi-bag-check"></i>
            </div>
            <h3 class="card-number"><?php echo $total_orders; ?></h3>
            <p class="card-title">Orders</p>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card products-card h-100">
          <div class="card-body p-4">
            <a href="product.php" class="card-link">
              <i class="bi bi-arrow-right"></i>
            </a>
            <div class="card-icon">
              <i class="bi bi-box-seam"></i>
            </div>
            <h3 class="card-number"><?php echo $total_products; ?></h3>
            <p class="card-title">Products</p>
          </div>
        </div>
      </div>

      <!-- Feedback -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card feedback-card h-100">
          <div class="card-body p-4">
            <a href="feedback.php" class="card-link">
              <i class="bi bi-arrow-right"></i>
            </a>
            <div class="card-icon">
              <i class="bi bi-chat-heart"></i>
            </div>
            <h3 class="card-number"><?php echo $total_feedback; ?></h3>
            <p class="card-title">Feedback</p>
          </div>
        </div>
      </div>

      <!-- Inquiries -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card inquiries-card h-100">
          <div class="card-body p-4">
            <a href="inquiries.php" class="card-link">
              <i class="bi bi-arrow-right"></i>
            </a>
            <div class="card-icon">
              <i class="bi bi-envelope-heart"></i>
            </div>
            <h3 class="card-number"><?php echo $total_inquiries; ?></h3>
            <p class="card-title">Inquiries</p>
          </div>
        </div>
      </div>

      <!-- Users -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card users-card h-100">
          <div class="card-body p-4">
            <a href="customers.php" class="card-link">
              <i class="bi bi-arrow-right"></i>
            </a>
            <div class="card-icon">
              <i class="bi bi-people"></i>
            </div>
            <h3 class="card-number"><?php echo $total_users; ?></h3>
            <p class="card-title">Users</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
