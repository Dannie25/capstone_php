  <?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Date Range Filtering
$current_month = date('Y-m');
$today = date('Y-m-d');
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Always define $last_month for growth calculation
$last_month = date('Y-m', strtotime('-1 month'));

// Build two date filters: one for plain orders table, one for aliased (o) orders table
if ($from_date && $to_date) {
    // Validate dates
    if ($from_date > $to_date) {
        $from_date = $to_date;
    }
    if ($to_date > $today) {
        $to_date = $today;
    }
    $date_filter_orders = "DATE(created_at) BETWEEN '" . $conn->real_escape_string($from_date) . "' AND '" . $conn->real_escape_string($to_date) . "'";
    $date_filter_o = "DATE(o.created_at) BETWEEN '" . $conn->real_escape_string($from_date) . "' AND '" . $conn->real_escape_string($to_date) . "'";
    $period_label = date('M d, Y', strtotime($from_date)) . ' - ' . date('M d, Y', strtotime($to_date));
} else {
    // Default to current month
    $current_month = date('Y-m');
    $date_filter_orders = "DATE_FORMAT(created_at, '%Y-%m') = '$current_month'";
    $date_filter_o = "DATE_FORMAT(o.created_at, '%Y-%m') = '$current_month'";
    $period_label = date('F Y');
}

// Monthly Sales Data
$monthly_sales_query = "SELECT 
    COALESCE(SUM(total_amount), 0) as total_sales,
    COUNT(*) as total_orders,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as completed_sales
    FROM orders 
    WHERE $date_filter_orders";
$monthly_result = $conn->query($monthly_sales_query);
$monthly_data = $monthly_result->fetch_assoc();

// Last Month Sales for Growth Calculation
$last_month_query = "SELECT COALESCE(SUM(total_amount), 0) as last_month_sales 
    FROM orders 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = '$last_month' AND status = 'completed'";
$last_month_result = $conn->query($last_month_query);
$last_month_data = $last_month_result->fetch_assoc();

// Calculate growth percentage
$growth_percentage = 0;
if ($last_month_data['last_month_sales'] > 0) {
    $growth_percentage = (($monthly_data['completed_sales'] - $last_month_data['last_month_sales']) / $last_month_data['last_month_sales']) * 100;
}

// Top Selling Product
$top_product_query = "SELECT 
    oi.product_name,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.price * oi.quantity) as total_revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE $date_filter_o
    AND o.status IN ('completed', 'shipped', 'pending')
    GROUP BY oi.product_name
    ORDER BY total_quantity DESC
    LIMIT 1";
$top_product_result = $conn->query($top_product_query);
$top_product = $top_product_result->fetch_assoc();

// Total Units Sold (Filtered)
$units_sold_query = "SELECT COALESCE(SUM(oi.quantity), 0) as total_units
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE $date_filter_o
    AND o.status IN ('completed', 'shipped', 'pending')";
$units_result = $conn->query($units_sold_query);
$units_data = $units_result->fetch_assoc();

// Weekly Sales Breakdown (last 4 weeks)
$weekly_sales = [];
for ($i = 3; $i >= 0; $i--) {
    $week_start = date('Y-m-d', strtotime("-$i weeks", strtotime('monday this week')));
    $week_end = date('Y-m-d', strtotime("-$i weeks", strtotime('sunday this week')));
    
    $weekly_query = "SELECT COALESCE(SUM(total_amount), 0) as week_sales
        FROM orders 
        WHERE DATE(created_at) BETWEEN '$week_start' AND '$week_end'
        AND status IN ('completed', 'shipped')";
    $weekly_result = $conn->query($weekly_query);
    $week_data = $weekly_result->fetch_assoc();
    $weekly_sales[] = [
        'start' => $week_start,
        'end' => $week_end,
        'sales' => $week_data['week_sales']
    ];
}

// Top Cities by Sales
$city_sales_query = "SELECT 
    city,
    COALESCE(SUM(total_amount), 0) as city_sales,
    COUNT(*) as orders_count
    FROM orders 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'
    AND status IN ('completed', 'shipped')
    GROUP BY city
    ORDER BY city_sales DESC
    LIMIT 4";
$city_result = $conn->query($city_sales_query);
$city_sales = [];
while ($row = $city_result->fetch_assoc()) {
    $city_sales[] = $row;
}

// Daily Sales Trend (7-day filter)
$daily_sales = [];
if (isset($_GET['daily_from']) && isset($_GET['daily_to']) && $_GET['daily_from'] && $_GET['daily_to']) {
    $from = $_GET['daily_from'];
    $to = $_GET['daily_to'];
    $from_ts = strtotime($from);
    $to_ts = strtotime($to);
    if ($from_ts <= $to_ts && ($to_ts - $from_ts) / (60*60*24) == 6) {
        for ($d = $from_ts; $d <= $to_ts; $d += 86400) {
            $date = date('Y-m-d', $d);
            $daily_query = "SELECT COALESCE(SUM(total_amount), 0) as daily_sales
                FROM orders 
                WHERE DATE(created_at) = '$date'
                AND status IN ('completed', 'shipped')";
            $daily_result = $conn->query($daily_query);
            $day_data = $daily_result->fetch_assoc();
            $daily_sales[] = [
                'date' => date('M j', strtotime($date)),
                'sales' => $day_data['daily_sales']
            ];
        }
    }
}
if (empty($daily_sales)) {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daily_query = "SELECT COALESCE(SUM(total_amount), 0) as daily_sales
            FROM orders 
            WHERE DATE(created_at) = '$date'
            AND status IN ('completed', 'shipped')";
        $daily_result = $conn->query($daily_query);
        $day_data = $daily_result->fetch_assoc();
        $daily_sales[] = [
            'date' => date('M j', strtotime($date)),
            'sales' => $day_data['daily_sales']
        ];
    }
}

// Compute helpers for UI visuals
$max_city_sales = 0;
foreach ($city_sales as $c) {
    if ($c['city_sales'] > $max_city_sales) {
        $max_city_sales = $c['city_sales'];
    }
}

// Prepare labels/values for charts
$weekly_labels = array_map(function($w) { return date('M j', strtotime($w['start'])) . ' - ' . date('M j', strtotime($w['end'])); }, $weekly_sales);
$weekly_values = array_map(function($w) { return (float)$w['sales']; }, $weekly_sales);
$daily_labels = array_map(function($d){ return $d['date']; }, $daily_sales);
$daily_values = array_map(function($d){ return (float)$d['sales']; }, $daily_sales);
?>

<?php
// Extended datasets for modals
// Only include data from June (current year) onwards
$start_cutoff = date('Y-06-01');
// All-time city sales history (completed + shipped)
$all_city_sales = [];
$all_cities_q = "SELECT city, COALESCE(SUM(total_amount),0) AS city_sales, COUNT(*) AS orders_count
                 FROM orders
                 WHERE status IN ('completed','shipped') AND city IS NOT NULL AND city <> ''
                 AND DATE(created_at) >= '$start_cutoff'
                 GROUP BY city
                 ORDER BY city_sales DESC";
$res_all_cities = $conn->query($all_cities_q);
if ($res_all_cities) {
    while ($row = $res_all_cities->fetch_assoc()) { $all_city_sales[] = $row; }
}

// Last 12 weeks breakdown (filtered by cutoff)
$weekly_sales_ext = [];
for ($i = 11; $i >= 0; $i--) {
    $w_start = date('Y-m-d', strtotime("-$i weeks", strtotime('monday this week')));
    $w_end   = date('Y-m-d', strtotime("-$i weeks", strtotime('sunday this week')));
    if ($w_end < $start_cutoff) { continue; }
    $q = "SELECT COALESCE(SUM(total_amount),0) AS sales, COUNT(*) AS orders
          FROM orders
          WHERE DATE(created_at) BETWEEN '$w_start' AND '$w_end' AND status IN ('completed','shipped')
          AND DATE(created_at) >= '$start_cutoff'";
    $r = $conn->query($q);
    $d = $r ? $r->fetch_assoc() : ['sales'=>0,'orders'=>0];
    $weekly_sales_ext[] = [
        'label' => date('M j', strtotime($w_start)) . ' - ' . date('M j', strtotime($w_end)),
        'start' => $w_start,
        'end'   => $w_end,
        'sales' => (float)$d['sales'],
        'orders'=> (int)$d['orders']
    ];
}

// Last 30 days breakdown (filtered by cutoff)
$daily_sales_ext = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    if ($d < $start_cutoff) { continue; }
    $q = "SELECT COALESCE(SUM(total_amount),0) AS sales, COUNT(*) AS orders
          FROM orders
          WHERE DATE(created_at) = '$d' AND status IN ('completed','shipped')
          AND DATE(created_at) >= '$start_cutoff'";
    $r = $conn->query($q);
    $row = $r ? $r->fetch_assoc() : ['sales'=>0,'orders'=>0];
    $daily_sales_ext[] = [
        'date' => $d,
        'label'=> date('M j, Y', strtotime($d)),
        'sales'=> (float)$row['sales'],
        'orders'=> (int)$row['orders']
    ];
}

// Last 12 months breakdown (from June current year to current month)
$monthly_sales_ext = [];
$startMonth = new DateTime($start_cutoff); // June 1 current year
$currMonth = new DateTime(date('Y-m-01'));
while ($startMonth <= $currMonth) {
    $month_start = $startMonth->format('Y-m-01');
    $month_end = date('Y-m-t', strtotime($month_start));
    $q = "SELECT COALESCE(SUM(total_amount),0) AS sales, COUNT(*) AS orders
          FROM orders
          WHERE DATE(created_at) BETWEEN '$month_start' AND '$month_end'
          AND status IN ('completed','shipped')
          AND DATE(created_at) >= '$start_cutoff'";
    $r = $conn->query($q);
    $row = $r ? $r->fetch_assoc() : ['sales'=>0,'orders'=>0];
    $monthly_sales_ext[] = [
        'month'  => date('Y-m', strtotime($month_start)),
        'label'  => date('F Y', strtotime($month_start)),
        'sales'  => (float)$row['sales'],
        'orders' => (int)$row['orders']
    ];
    $startMonth->modify('+1 month');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Dashboard - MTC Clothing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        :root{
            /* Light theme matching earlier palette */
            --bg: linear-gradient(to right, #eef2f3, #8e9eab);
            --panel: #ffffff;
            --muted: #5c6b7a;
            --text: #2c3e50;
            --accent: #27ae60;   /* green used previously */
            --accent-2: #2980b9; /* blue used in headings */
            --border: #e5e7eb;
            --card: #ffffff;
            --shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f5f5;
            color: var(--text);
        }


        .sales-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 18px 18px 16px;
        }
        .card h2 {
            color: var(--accent-2);
            margin: 0 0 12px;
            font-size: 18px;
            letter-spacing: .2px;
            display:flex; align-items:center; gap:8px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 8px;
        }
        .kpi{ display:grid; grid-template-columns: 1fr auto; gap:8px; align-items:center; padding:10px 0; border-bottom:1px dashed #e5e7eb }
        .kpi:last-child{ border-bottom: none }
        /* Backward compatibility for existing markup */
        .sales-metric{ display:grid; grid-template-columns: 1fr auto; gap:8px; align-items:center; padding:10px 0; border-bottom:1px dashed #e5e7eb }
        .sales-metric:last-child{ border-bottom: none }
        .metric-label { color: var(--muted); font-weight:500 }
        .metric-value { font-weight: 700; color: var(--accent); }
        .muted { color: var(--muted); font-size:12px }
        .growth-positive { color: #34d399 }
        .growth-negative { color: #f87171 }
        .no-data { color: var(--muted); font-style: italic; }
        .footer {
            border-top: 1px solid var(--border);
            text-align: center;
            padding: 16px;
            color: var(--muted);
            background: #f8fafc80;
        }

        .progress { height: 8px; background:#f1f5f9; border:1px solid var(--border); border-radius:999px; overflow:hidden }
        .progress > span { display:block; height:100%; background: linear-gradient(90deg, var(--accent), #66bb6a); }

        .charts{ grid-column: 1 / -1; display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:20px }
        @media (max-width: 900px){ .charts{ grid-template-columns: 1fr } }
        canvas{ width:100% !important; height:280px !important }

        /* Buttons */
        .card-actions{ margin-left:auto; display:inline-flex; gap:8px }
        .btn{ appearance:none; border:1px solid var(--border); background:#ffffff; color:#1f2d3a; padding:6px 10px; border-radius:8px; font-size:12px; cursor:pointer }
        .btn:hover{ background:#f8fafc }
        .btn-primary{ background: var(--accent-2); color:#fff; border-color: #1f76c3 }
        .btn-primary:hover{ background:#2779bd }

        /* Modal */
        .modal{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.35); z-index:1000 }
        .modal.open{ display:flex }
        .modal-card{ width:min(900px, 92vw); max-height:85vh; overflow:auto; background:#fff; color:#1f2d3a; border-radius:12px; box-shadow: var(--shadow); border:1px solid #e5e7eb }
        .modal-header{ padding:14px 16px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between }
        .modal-title{ font-weight:700; color:#1f2d3a }
        .modal-body{ padding:16px; background:#ffffff }
        .close-btn{ background:transparent; border:1px solid #e5e7eb; padding:6px 10px; border-radius:8px; cursor:pointer }
        .close-btn:hover{ background:#f8fafc }
        table{ width:100%; border-collapse:collapse }
        th, td{ padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px }
        th{ background:#f8fafc; color:#334155; position:sticky; top:0 }
        .calendar-filter {
            margin: 32px auto 20px auto;
            padding: 18px 24px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(60,120,200,0.10), 0 1.5px 5px rgba(30,30,60,0.04);
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 520px;
            gap: 18px;
            position: relative;
        }
        .calendar-filter form {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .calendar-filter label {
            font-weight: 500;
            color: #1f2d3a;
            margin-right: 4px;
        }
        .calendar-filter input[type="date"] {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 14px;
            color: #1f2d3a;
            background: #f8fafc;
            margin-right: 8px;
        }
        .calendar-filter .calendar-icon {
            font-size: 20px;
            color: #60a5fa;
            margin-right: 6px;
        }
        @media (max-width: 600px) {
            .calendar-filter {
                flex-direction: column;
                padding: 12px;
                gap: 8px;
            }
            .calendar-filter form {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-graph-up-arrow"></i> Sales Dashboard</h1>
        </div>
        
        <div class="content-body">
            <div class="sales-container">

    <div class="card">
        <h2>💰 Sales (<?php echo htmlspecialchars($period_label); ?>)
    <span class="card-actions">
        <button class="btn" onclick="openModal('modalMonthly')">View Details</button>
        <button class="btn btn-primary" onclick="openModal('calendarModal'); return false;">Filter by Date</button>
    </span>
</h2>
        <div class="sales-metric">
            <span class="metric-label">Total Revenue:</span>
            <span class="metric-value">₱<?php echo number_format($monthly_data['total_sales'], 2); ?></span>
        </div>
        <div class="sales-metric">
            <span class="metric-label">Completed Sales:</span>
            <span class="metric-value">₱<?php echo number_format($monthly_data['completed_sales'], 2); ?></span>
        </div>
        <div class="sales-metric">
            <span class="metric-label">Total Orders:</span>
            <span class="metric-value"><?php echo $monthly_data['total_orders']; ?></span>
        </div>
        <div class="sales-metric">
            <span class="metric-label">Units Sold:</span>
            <span class="metric-value"><?php echo number_format($units_data['total_units']); ?></span>
        </div>
        <div class="sales-metric">
            <span class="metric-label">Growth vs Last Month:</span>
            <span class="metric-value <?php echo $growth_percentage >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                <?php echo ($growth_percentage >= 0 ? '+' : '') . number_format($growth_percentage, 1); ?>%
            </span>
        </div>
        <?php if ($top_product): ?>
        <div class="sales-metric">
            <span class="metric-label">Top Product:</span>
            <span class="metric-value"><?php echo htmlspecialchars($top_product['product_name']); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📅 Weekly Sales Breakdown <span class="card-actions"><button class="btn" onclick="openModal('modalWeekly')">View Details</button></span></h2>
        <?php if (!empty($weekly_sales) && array_sum(array_column($weekly_sales, 'sales')) > 0): ?>
            <?php foreach ($weekly_sales as $i => $week): ?>
                <div class="sales-metric">
                    <span class="metric-label">
                        Week <?php echo $i + 1; ?> (<?php echo date('M j', strtotime($week['start'])); ?> - <?php echo date('M j', strtotime($week['end'])); ?>):
                    </span>
                    <span class="metric-value">₱<?php echo number_format($week['sales'], 2); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-data">No sales data available for recent weeks</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>🌍 Top Cities by Sales <span class="card-actions"><button class="btn" onclick="openModal('modalCities')">View All</button></span></h2>
        <?php if (!empty($city_sales)): ?>
            <?php foreach ($city_sales as $city): ?>
            <?php 
              $pct = $max_city_sales > 0 ? ($city['city_sales'] / $max_city_sales) * 100 : 0;
            ?>
            <div class="kpi">
                <span class="metric-label"><?php echo htmlspecialchars($city['city']); ?></span>
                <span class="metric-value">₱<?php echo number_format($city['city_sales'], 2); ?></span>
                <div class="progress" style="grid-column:1 / -1;">
                    <span style="width: <?php echo number_format($pct,2); ?>%"></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-data">No city sales data available for this month</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📊 Sales Summary</h2>
        <?php
        // Calculate additional metrics
        $avg_order_value = $monthly_data['total_orders'] > 0 ? $monthly_data['completed_sales'] / $monthly_data['total_orders'] : 0;
        
        // Get order status breakdown
        $status_query = "SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as amount
            FROM orders 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'
            GROUP BY status";
        $status_result = $conn->query($status_query);
        $status_breakdown = [];
        while ($row = $status_result->fetch_assoc()) {
            $status_breakdown[$row['status']] = $row;
        }
        ?>
        <div class="sales-metric">
            <span class="metric-label">Average Order Value:</span>
            <span class="metric-value">₱<?php echo number_format($avg_order_value, 2); ?></span>
        </div>
        <?php foreach (['pending', 'shipped', 'completed', 'cancelled'] as $status): ?>
            <?php if (isset($status_breakdown[$status])): ?>
            <div class="sales-metric">
                <span class="metric-label"><?php echo ucfirst($status); ?> Orders:</span>
                <span class="metric-value"><?php echo $status_breakdown[$status]['count']; ?></span>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="charts">
      <div class="card">
        <h2>📅 Weekly Sales Chart <span class="card-actions"><button class="btn" onclick="openModal('modalWeekly')">View Details</button></span></h2>
        <canvas id="weeklyChart"></canvas>
      </div>
      <div class="card">
        <h2>📈 Daily Sales (7 days) <span class="card-actions"><button class="btn" onclick="openModal('modalDaily')">View Details</button></span></h2>
        <form id="dailyFilterForm" method="get" style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <label for="daily_from" style="font-weight:500;">From</label>
          <input type="date" id="daily_from" name="daily_from" max="<?php echo $today; ?>" value="<?php echo isset($_GET['daily_from']) ? htmlspecialchars($_GET['daily_from']) : '' ?>" required>
          <label for="daily_to" style="font-weight:500;">To</label>
          <input type="date" id="daily_to" name="daily_to" max="<?php echo $today; ?>" value="<?php echo isset($_GET['daily_to']) ? htmlspecialchars($_GET['daily_to']) : '' ?>" required>
          <button type="submit" class="btn btn-primary">Filter</button>
          <?php if (isset($_GET['daily_from']) || isset($_GET['daily_to'])): ?>
            <a href="sales.php" class="btn" style="background: #eee; color: #333;">Reset</a>
          <?php endif; ?>
        </form>
        <script>
        document.getElementById('dailyFilterForm').onsubmit = function(e) {
          var from = document.getElementById('daily_from').value;
          var to = document.getElementById('daily_to').value;
          if(from && to) {
            var diff = (new Date(to) - new Date(from)) / (1000*60*60*24);
            if(diff !== 6) {
              alert('Please select exactly 7 days (start and end inclusive).');
              e.preventDefault();
              return false;
            }
          }
        };
        </script>
        <canvas id="dailyChart"></canvas>
      </div>
    </div>
</div>

<!-- Monthly Details Modal -->
<div id="modalMonthly" class="modal" aria-hidden="true" role="dialog">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">Monthly Sales - Last 12 Months</div>
      <button class="close-btn" onclick="closeModal('modalMonthly')">Close</button>
    </div>
    <div class="modal-body">
      <table>
        <thead>
          <tr><th>Month</th><th style=\"text-align:right\">Orders</th><th style=\"text-align:right\">Revenue (₱)</th></tr>
        </thead>
        <tbody>
          <?php foreach ($monthly_sales_ext as $m): ?>
          <tr>
            <td><?php echo htmlspecialchars($m['label']); ?></td>
            <td style=\"text-align:right\"><?php echo (int)$m['orders']; ?></td>
            <td style=\"text-align:right\">₱<?php echo number_format($m['sales'], 2); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  </div>
<!-- Modals -->
<div id="modalCities" class="modal" aria-hidden="true" role="dialog">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">All Cities - Order History</div>
      <button class="close-btn" onclick="closeModal('modalCities')">Close</button>
    </div>
    <div class="modal-body">
      <?php if (!empty($all_city_sales)): ?>
      <table>
        <thead>
          <tr><th>City/Municipality</th><th style="text-align:right">Orders</th><th style="text-align:right">Revenue (₱)</th></tr>
        </thead>
        <tbody>
          <?php foreach ($all_city_sales as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['city']); ?></td>
            <td style="text-align:right"><?php echo (int)$row['orders_count']; ?></td>
            <td style="text-align:right">₱<?php echo number_format($row['city_sales'], 2); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p>No city history found.</p>
      <?php endif; ?>
    </div>
  </div>
  </div>

<div id="modalWeekly" class="modal" aria-hidden="true" role="dialog">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">Weekly Sales - Last 12 Weeks</div>
      <button class="close-btn" onclick="closeModal('modalWeekly')">Close</button>
    </div>
    <div class="modal-body">
      <table>
        <thead>
          <tr><th>Week Range</th><th style="text-align:right">Orders</th><th style="text-align:right">Revenue (₱)</th></tr>
        </thead>
        <tbody>
          <?php foreach ($weekly_sales_ext as $w): ?>
          <tr>
            <td><?php echo htmlspecialchars($w['label']); ?></td>
            <td style="text-align:right"><?php echo (int)$w['orders']; ?></td>
            <td style="text-align:right">₱<?php echo number_format($w['sales'], 2); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  </div>

<div id="modalDaily" class="modal" aria-hidden="true" role="dialog">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">Daily Sales - Last 30 Days</div>
      <button class="close-btn" onclick="closeModal('modalDaily')">Close</button>
    </div>
    <div class="modal-body">
      <table>
        <thead>
          <tr><th>Date</th><th style="text-align:right">Orders</th><th style="text-align:right">Revenue (₱)</th></tr>
        </thead>
        <tbody>
          <?php foreach ($daily_sales_ext as $d): ?>
          <tr>
            <td><?php echo htmlspecialchars($d['label']); ?></td>
            <td style="text-align:right"><?php echo (int)$d['orders']; ?></td>
            <td style="text-align:right">₱<?php echo number_format($d['sales'], 2); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  function openModal(id){ document.getElementById(id).classList.add('open'); }
  function closeModal(id){ document.getElementById(id).classList.remove('open'); }
  const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
  const weeklyData = <?php echo json_encode($weekly_values); ?>;
  const weeklyLabels = <?php echo json_encode($weekly_labels); ?>;
  new Chart(weeklyCtx, {
    type: 'bar',
    data: {
      labels: weeklyLabels,
      datasets: [{
        label: 'Revenue (₱)',
        data: weeklyData,
        backgroundColor: 'rgba(96,165,250,0.5)',
        borderColor: 'rgba(96,165,250,1)',
        borderWidth: 1,
        borderRadius: 6,
      }]
    },
    options: {
      plugins: { legend: { labels: { color: '#5c6b7a' } } },
      scales: {
        x: { ticks: { color: '#5c6b7a' }, grid: { color: '#e5e7eb' } },
        y: { ticks: { color: '#5c6b7a' }, grid: { color: '#e5e7eb' } }
      }
    }
  });

  const dailyCtx = document.getElementById('dailyChart').getContext('2d');
  const dailyLabels = <?php echo json_encode($daily_labels); ?>;
  const dailyValues = <?php echo json_encode($daily_values); ?>;
  new Chart(dailyCtx, {
    type: 'line',
    data: {
      labels: dailyLabels,
      datasets: [{
        label: 'Revenue (₱)',
        data: dailyValues,
        fill: true,
        tension: 0.35,
        backgroundColor: 'rgba(34,197,94,0.15)',
        borderColor: 'rgba(34,197,94,1)',
        pointBackgroundColor: '#22c55e',
        pointRadius: 3
      }]
    },
    options: {
      plugins: { legend: { labels: { color: '#5c6b7a' } } },
      scales: {
        x: { ticks: { color: '#5c6b7a' }, grid: { color: '#e5e7eb' } },
        y: { ticks: { color: '#5c6b7a' }, grid: { color: '#e5e7eb' } }
      }
    }
  });
</script>

<!-- Calendar Filter Modal -->
<div id="calendarModal" class="modal">
  <div class="modal-card">
    <div class="modal-header">
      <span class="modal-title">Filter Sales by Date</span>
      <button class="close-btn" onclick="closeModal('calendarModal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="get" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:center;">
        <span class="calendar-icon" style="font-size:20px;color:#60a5fa;margin-right:6px;">📅</span>
        <label for="from_date" style="font-weight:500;margin-right:4px;">From</label>
        <input type="date" id="from_date" name="from_date" max="2025-09-23" value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>" required>
        <label for="to_date" style="font-weight:500;margin-right:4px;">To</label>
        <input type="date" id="to_date" name="to_date" max="2025-09-23" value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>" required>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if (isset($_GET['from_date']) || isset($_GET['to_date'])): ?>
          <a href="sales.php" class="btn" style="background: #eee; color: #333;">Reset</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
<script>
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
// Optional: Close modal on ESC
window.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.open').forEach(function(modal) {
      modal.classList.remove('open');
    });
  }
});
// Optional: Close modal when clicking outside
window.addEventListener('click', function(e) {
  document.querySelectorAll('.modal.open').forEach(function(modal) {
    if (e.target === modal) modal.classList.remove('open');
  });
});
</script>
</body>
</html>
