<?php
session_start();
include '../db.php';

// Simple test data
$total_sales = 15000;
$total_orders = 25;
$total_products = 12;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Test Dashboard - MTC Clothing</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    
    .card-icon {
      width: 60px;
      height: 60px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
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
    
    .sales-card .card-icon { background: #10ac84; }
    .orders-card .card-icon { background: #3742fa; }
    .products-card .card-icon { background: #ff6348; }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="text-center text-white mb-5">
      <h1>🎉 NEW CARD DESIGN TEST</h1>
      <p>Kung nakikita mo to, working na ang new design!</p>
    </div>

    <div class="row g-4">
      <!-- Sales Card -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card sales-card h-100">
          <div class="card-body p-4">
            <div class="card-icon">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
            <h3 class="card-number">₱<?php echo number_format($total_sales, 0); ?></h3>
            <p class="card-title">Total Sales</p>
          </div>
        </div>
      </div>

      <!-- Orders Card -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card orders-card h-100">
          <div class="card-body p-4">
            <div class="card-icon">
              <i class="bi bi-bag-check"></i>
            </div>
            <h3 class="card-number"><?php echo $total_orders; ?></h3>
            <p class="card-title">Orders</p>
          </div>
        </div>
      </div>

      <!-- Products Card -->
      <div class="col-lg-4 col-md-6">
        <div class="card stats-card products-card h-100">
          <div class="card-body p-4">
            <div class="card-icon">
              <i class="bi bi-box-seam"></i>
            </div>
            <h3 class="card-number"><?php echo $total_products; ?></h3>
            <p class="card-title">Products</p>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="dashboard.php" class="btn btn-light btn-lg">
        <i class="bi bi-arrow-left me-2"></i>Back to Main Dashboard
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
