<?php
session_start();
include '../db.php';
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
// --- FEEDBACK STATS ---
$avg_sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM order_feedback";
$avg_res = $conn->query($avg_sql);
$avg_row = $avg_res ? $avg_res->fetch_assoc() : ['avg_rating' => 0, 'total' => 0];
$avg_rating = $avg_row['avg_rating'] ? number_format($avg_row['avg_rating'], 2) : '0.00';
$total_feedbacks = $avg_row['total'];
$star_counts = [1=>0,2=>0,3=>0,4=>0,5=>0];
$star_sql = "SELECT rating, COUNT(*) as cnt FROM order_feedback GROUP BY rating";
$star_res = $conn->query($star_sql);
if ($star_res) {
  while($row = $star_res->fetch_assoc()) {
    $star = intval($row['rating']);
    if ($star >= 1 && $star <= 5) $star_counts[$star] = $row['cnt'];
  }
}
// --- FILTERS & PAGINATION ---
$filter = isset($_GET['star']) ? intval($_GET['star']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$feedbacks_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $feedbacks_per_page;
$where = [];
if ($filter > 0 && $filter <= 5) $where[] = "f.rating = $filter";
if (!empty($search_filter)) {
    $safe = $conn->real_escape_string($search_filter);
    $where[] = "(u.name LIKE '%$safe%' OR o.id LIKE '%$safe%' OR f.feedback_text LIKE '%$safe%' OR EXISTS (SELECT 1 FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = f.order_id AND p.name LIKE '%$safe%'))";
}
$where_clause = '';
if (count($where)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where);
}
$count_sql = "SELECT COUNT(*) as total FROM order_feedback f LEFT JOIN users u ON f.user_id = u.id LEFT JOIN orders o ON f.order_id = o.id $where_clause";
$count_res = $conn->query($count_sql);
$total_rows = $count_res ? $count_res->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $feedbacks_per_page);
$sql = "SELECT f.*, u.name AS user_name, o.id AS order_id, (
    SELECT GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ')
    FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = f.order_id
) AS products
FROM order_feedback f
LEFT JOIN users u ON f.user_id = u.id
LEFT JOIN orders o ON f.order_id = o.id
$where_clause
ORDER BY f.created_at DESC
LIMIT $feedbacks_per_page OFFSET $offset";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f1f3f6;
    }


    .dashboard {
      padding: 32px 8vw 32px 8vw;
      max-width: 1200px;
      margin: 0 auto;
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

    .feedback-stars {
      color: #ffd700;
      font-size: 1.25rem;
      letter-spacing: 1px;
    }
    .table thead th {
      font-weight: 700;
      background: #e6f4ea;
      color: #256029;
      border-bottom: 2px solid #b7e4c7;
      vertical-align: middle;
    }
    .table tbody tr:hover {
      background: #f5f5f5;
      transition: background 0.2s;
    }
    .card {
      box-shadow: 0 8px 24px rgba(80,120,80,0.09);
      border-radius: 14px;
      border: 1px solid #e0e0e0;
      margin-bottom: 32px;
    }
    .card-header {
      background: linear-gradient(90deg,#d9e6a7,#c8d99a);
      font-size: 1.4rem;
      font-weight: 600;
      color: #3d4a2a;
      border-radius: 14px 14px 0 0;
      padding: 18px 28px;
      border-bottom: 1px solid #e0e0e0;
    }
    .card-body {
      padding: 26px 28px 22px 28px;
    }
    .footer {
      background-color: #bdc3c7;
      text-align: center;
      padding: 10px;
      font-size: 14px;
    }
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
      cursor: pointer;
    }
    .stats-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .stats-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }
    .active-card {
      outline: 2px solid var(--primary-color);
    }
    .stats-icon {
      font-size: 1.8rem;
      margin-bottom: 10px;
      color: #ffc107;
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
    .customers-table {
      background: var(--white);
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 32px;
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
    .table-responsive {
      font-size: 0.98rem;
    }
    @media (max-width: 768px) {
      .stats-card { margin-bottom: 20px; }
      .table-responsive { font-size: 0.9rem; }
    }
  </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-chat-heart"></i> Customer Feedback</h1>
        </div>
        
        <div class="content-body">
            <div class="container-fluid">
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col">
                        <a href="?star=0<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($filter === 0) ? 'active-card' : ''; ?>">
                            <div class="stats-card card-total">
                                <i class="fas fa-comments stats-icon"></i>
                                <h3 class="stats-number"><?php echo $total_feedbacks; ?></h3>
                                <p class="stats-label">Total Feedback</p>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <div class="stats-card card-today" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#avgRatingModal">
                            <i class="fas fa-star-half-alt stats-icon"></i>
                            <h3 class="stats-number"><?php echo $avg_rating; ?> / 5</h3>
                            <p class="stats-label">Average Rating</p>
                        </div>
                    </div>
<!-- Modal for Average Rating -->
<div class="modal fade" id="avgRatingModal" tabindex="-1" aria-labelledby="avgRatingModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #5b6b46; color: white;">
        <h5 class="modal-title" id="avgRatingModalLabel"><i class="fas fa-star-half-alt me-2"></i>Average Rating Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <h1 style="font-size:3rem; color:#ffc107;"><i class="fas fa-star"></i> <?php echo $avg_rating; ?> / 5</h1>
        <p class="mt-3 mb-1" style="font-size:1.3rem;">Total Feedback: <b><?php echo $total_feedbacks; ?></b></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>
            <div class="col">
                <a href="?star=5<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($filter === 5) ? 'active-card' : ''; ?>">
                    <div class="stats-card card-week">
                        <i class="fas fa-star stats-icon"></i>
                        <h3 class="stats-number"><?php echo $star_counts[5]; ?></h3>
                        <p class="stats-label">5 Star</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?star=4<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($filter === 4) ? 'active-card' : ''; ?>">
                    <div class="stats-card card-month">
                        <i class="fas fa-star stats-icon"></i>
                        <h3 class="stats-number"><?php echo $star_counts[4]; ?></h3>
                        <p class="stats-label">4 Star</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?star=3<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($filter === 3) ? 'active-card' : ''; ?>">
                    <div class="stats-card card-total">
                        <i class="fas fa-star stats-icon"></i>
                        <h3 class="stats-number"><?php echo $star_counts[3]; ?></h3>
                        <p class="stats-label">3 Star</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?star=2<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($filter === 2) ? 'active-card' : ''; ?>">
                    <div class="stats-card card-today">
                        <i class="fas fa-star stats-icon"></i>
                        <h3 class="stats-number"><?php echo $star_counts[2]; ?></h3>
                        <p class="stats-label">2 Star</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?star=1<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($filter === 1) ? 'active-card' : ''; ?>">
                    <div class="stats-card card-week">
                        <i class="fas fa-star stats-icon"></i>
                        <h3 class="stats-number"><?php echo $star_counts[1]; ?></h3>
                        <p class="stats-label">1 Star</p>
                    </div>
                </a>
            </div>
        </div>
        <!-- Feedback Table -->
        <div class="customers-table">
            <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #5b6b46; border-radius: 10px 10px 0 0; color: white;">
                <h2 class="mb-0" style="font-size: 1.25rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-list me-2"></i>Feedback List
                </h2>
                <form method="GET" class="d-flex align-items-center ms-2" style="margin: 0;">
                    <div class="input-group" style="width: 260px;">
                        <input type="text"
                               name="search"
                               class="form-control form-control-sm search-box"
                               placeholder="Search user, order #, feedback, or product"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn-sm search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="?"                               class="btn btn-sm btn-light ms-1" style="border-radius: 20px; border: none;">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Products</th>
                        <th>Feedback</th>
                        <th>Rating</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && $res->num_rows > 0): ?>
    <?php while ($row = $res->fetch_assoc()): ?>
        <tr>
            <td><?php echo str_pad($row['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
            <td><?php echo htmlspecialchars($row['products']); ?></td>
            <td><?php echo nl2br(htmlspecialchars($row['feedback_text'])); ?></td>
            <td class="feedback-stars"><?php echo str_repeat('&#11088;', (int)$row['rating']); ?><?php if($row['rating']) echo " ({$row['rating']}/5)"; ?></td>
            <td><?php echo date('F j, Y g:i A', strtotime($row['created_at'])); ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="6" class="text-center py-4">
            <i class="fas fa-comments fa-2x text-muted mb-2"></i>
            <p class="text-muted">No feedbacks found<?php echo $filter ? ' for this rating.' : ' yet.'; ?></p>
        </td>
    </tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <nav aria-label="Feedback pagination">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $filter ? '&star=' . urlencode($filter) : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $filter ? '&star=' . urlencode($filter) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $filter ? '&star=' . urlencode($filter) : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer mt-4">
        &copy; <?php echo date("Y"); ?> MTC Clothing
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit search form on Enter key
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.form.submit();
                    }
                });
            }
        });
    </script>
            </div>
        </div>
    </div>
</div>
</body>
</html>
