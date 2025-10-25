<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get user statistics
$total_users = 0;
$today_users = 0;
$this_week_users = 0;
$this_month_users = 0;

// Get total users count
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result && $row = $result->fetch_assoc()) {
    $total_users = $row['count'];
}

// Get today's users count
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
if ($result && $row = $result->fetch_assoc()) {
    $today_users = $row['count'];
}

// Get this week's users count
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
if ($result && $row = $result->fetch_assoc()) {
    $this_week_users = $row['count'];
}

// Get this month's users count
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
if ($result && $row = $result->fetch_assoc()) {
    $this_month_users = $row['count'];
}

// Get users with search and pagination
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$users_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $users_per_page;

$query = "SELECT id, name, email, created_at FROM users";
$count_query = "SELECT COUNT(*) as total FROM users";

// Build dynamic conditions for search and period filters
$conditions = [];

// Add search filter if provided
if (!empty($search_filter)) {
    $search_term = $conn->real_escape_string($search_filter);
    $conditions[] = "(name LIKE '%$search_term%' OR email LIKE '%$search_term%')";
}

// Add period filter if provided
switch ($period) {
    case 'today':
        $conditions[] = "DATE(created_at) = CURDATE()";
        break;
    case 'week':
        $conditions[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $conditions[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    default:
        // 'all' or any other value means no additional date filter
        break;
}

if (count($conditions) > 0) {
    $whereClause = " WHERE " . implode(" AND ", $conditions);
    $query .= $whereClause;
    $count_query .= $whereClause;
}

$query .= " ORDER BY created_at DESC LIMIT $users_per_page OFFSET $offset";

// Get total count for pagination
$result = $conn->query($count_query);
$total_rows = $result ? $result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $users_per_page);

// Get users
$users = [];
$result = $conn->query($query);
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
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

        /* User-specific colors */
        .card-total {
            border-left-color: #6c757d;
        }
        .card-total .stats-icon,
        .card-total .stats-number {
            color: #6c757d;
        }

        .card-today {
            border-left-color: #28a745;
        }
        .card-today .stats-icon,
        .card-today .stats-number {
            color: #28a745;
        }

        .card-week {
            border-left-color: #17a2b8;
        }
        .card-week .stats-icon,
        .card-week .stats-number {
            color: #17a2b8;
        }

        .card-month {
            border-left-color: #ffc107;
        }
        .card-month .stats-icon,
        .card-month .stats-number {
            color: #ffc107;
        }

        .customers-table {
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

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--primary-color);
        }

        .search-box {
            border-radius: 20px;
            border: none;
            box-shadow: none;
            padding: 8px 15px;
        }

        .search-btn {
            border-radius: 20px;
            border: none;
            background-color: var(--primary-color);
            color: white;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link:hover {
            background-color: var(--secondary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-people"></i> Customer Management</h1>
        </div>
        
        <div class="content-body">
            <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col">
                <a href="?period=all<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($period === 'all') ? 'active-card' : ''; ?>">
                    <div class="stats-card card-total">
                        <i class="fas fa-users stats-icon"></i>
                        <h3 class="stats-number"><?php echo $total_users; ?></h3>
                        <p class="stats-label">Total Customers</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?period=today<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($period === 'today') ? 'active-card' : ''; ?>">
                    <div class="stats-card card-today">
                        <i class="fas fa-calendar-day stats-icon"></i>
                        <h3 class="stats-number"><?php echo $today_users; ?></h3>
                        <p class="stats-label">Today</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?period=week<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($period === 'week') ? 'active-card' : ''; ?>">
                    <div class="stats-card card-week">
                        <i class="fas fa-calendar-week stats-icon"></i>
                        <h3 class="stats-number"><?php echo $this_week_users; ?></h3>
                        <p class="stats-label">This Week</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="?period=month<?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?>" class="stats-link <?php echo ($period === 'month') ? 'active-card' : ''; ?>">
                    <div class="stats-card card-month">
                        <i class="fas fa-calendar-alt stats-icon"></i>
                        <h3 class="stats-number"><?php echo $this_month_users; ?></h3>
                        <p class="stats-label">This Month</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="customers-table">
            <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #5b6b46; border-radius: 10px 10px 0 0; color: white;">
                <h2 class="mb-0" style="font-size: 1.25rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-list me-2"></i>Customer List
                </h2>
                <form method="GET" class="d-flex align-items-center" style="margin: 0;">
                    <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                    <div class="input-group" style="width: 300px;">
                        <input type="text"
                               name="search"
                               class="form-control form-control-sm search-box"
                               placeholder="Search by name or email"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn-sm search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="?" class="btn btn-sm btn-light ms-1" style="border-radius: 20px; border: none;">
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
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No customers found</p>
                                    <?php if (!empty($search_filter)): ?>
                                        <a href="?" class="btn btn-sm" style="background-color: var(--primary-color); color: white;">
                                            <i class="fas fa-arrow-left me-1"></i>Clear Search
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                <small class="text-muted">ID: #<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button"
                                                    class="btn btn-sm"
                                                    style="background-color: white; border: 1px solid var(--primary-color); color: var(--primary-color);"
                                                    title="View Details"
                                                    onclick="viewCustomer(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['created_at']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <nav aria-label="Customer pagination">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?><?php echo isset($period) && $period !== 'all' ? '&period=' . urlencode($period) : ''; ?>">
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
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?><?php echo isset($period) && $period !== 'all' ? '&period=' . urlencode($period) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_filter) ? '&search=' . urlencode($search_filter) : ''; ?><?php echo isset($period) && $period !== 'all' ? '&period=' . urlencode($period) : ''; ?>">
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

    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #5b6b46; color: white;">
                    <h5 class="modal-title" id="customerModalLabel">
                        <i class="fas fa-user me-2"></i>Customer Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="user-avatar me-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <span id="modalAvatar">U</span>
                                </div>
                                <div>
                                    <h4 id="modalName" class="mb-0"></h4>
                                    <p class="text-muted mb-0">Customer ID: <span id="modalId"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Member Since</h5>
                                    <h3 id="modalJoinDate" class="text-primary"></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Information</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Email:</strong></p>
                                    <p id="modalEmail" class="text-muted"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCustomer(id, name, email, joinDate) {
            // Update modal content
            document.getElementById('modalId').textContent = id.toString().padStart(6, '0');
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalEmail').textContent = email;
            document.getElementById('modalJoinDate').textContent = new Date(joinDate).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('modalAvatar').textContent = name.charAt(0).toUpperCase();

            // Show the modal
            var modal = new bootstrap.Modal(document.getElementById('customerModal'));
            modal.show();
        }

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