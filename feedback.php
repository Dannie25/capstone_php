<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Feedback Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f1f3f6;
    }

    header {
      background-color: #d9e6a7;
      padding: 25px 50px;
    }

    header div {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    header span {
      font-size: 35px;
      font-weight: bold;
      color: #222;
    }

    header img {
      height: 24px;
      vertical-align: middle;
    }

    .dashboard {
      padding: 30px 50px;
      display: grid;
      gap: 20px;
    }

    .feedback-card {
      background-color: #ffffff;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }

    .feedback-card h2 {
      font-size: 22px;
      color: #2c3e50;
      margin-bottom: 10px;
    }

    .feedback-card p {
      margin: 6px 0;
      font-size: 16px;
    }

    .feedback-card .product {
      font-weight: bold;
      color: #2980b9;
    }

    .footer {
      background-color: #bdc3c7;
      text-align: center;
      padding: 10px;
      font-size: 14px;
    }
  </style>
</head>
<body>

<header>
  <div>
    <span>Customer Feedback</span>
    <a href="dashboard.php">
      <img src="img/left.png" alt="Back" />
    </a>
  </div>
</header>

<div class="dashboard">
<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-warning">Please <a href="login.php">login</a> to view your feedbacks.</div>';
    exit();
}
$user_id = $_SESSION['user_id'];
$sql = "SELECT f.*, o.id AS order_id, (
    SELECT GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ')
    FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = f.order_id
) AS products
FROM order_feedback f
LEFT JOIN orders o ON f.order_id = o.id
WHERE f.user_id = ?
ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<h2 style="margin-top:0;">My Feedbacks</h2>
<?php if ($res && $res->num_rows > 0): ?>
    <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-success">
            <tr>
                <th>Order #</th>
                <th>Products</th>
                <th>Feedback</th>
                <th>Rating</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?php echo str_pad($row['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($row['products']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['feedback_text'])); ?></td>
                <td><?php echo str_repeat('⭐', (int)$row['rating']); ?><?php if($row['rating']) echo " ({$row['rating']}/5)"; ?></td>
                <td><?php echo date('F j, Y g:i A', strtotime($row['created_at'])); ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">You have not submitted any feedback yet.</div>
<?php endif; ?>
</div>

<div class="footer">
  &copy; <?php echo date("Y"); ?> MTC Clothing
</div>

</body>
</html>
