<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "ERROR: Not logged in as admin<br>";
    echo '<a href="login.php">Login here</a>';
    exit();
}

echo "<h2>Notification System Debug</h2>";
echo "<hr>";

// Show session info
echo "<h3>Session Info:</h3>";
echo "Admin logged in: " . ($_SESSION['admin_logged_in'] ? 'YES' : 'NO') . "<br>";
echo "Last notification check: " . (isset($_SESSION['last_notification_check']) ? $_SESSION['last_notification_check'] : 'NOT SET') . "<br>";
echo "<br>";

// Check recent pending orders
echo "<h3>Recent Pending Orders (Last 24 hours):</h3>";
$query = "SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, total_amount, status, created_at 
          FROM orders 
          WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
          ORDER BY created_at DESC 
          LIMIT 10";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = $row['status'] == 'pending' ? 'style="background-color: #ffffcc;"' : '';
        echo "<tr $highlight>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['customer_name'] . "</td>";
        echo "<td>₱" . number_format($row['total_amount'], 2) . "</td>";
        echo "<td><strong>" . $row['status'] . "</strong></td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No orders found in last 24 hours</p>";
}
echo "<br>";

// Check recent cancelled orders
echo "<h3>Recent Cancelled Orders (Last 24 hours):</h3>";
$query = "SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, total_amount, 
          COALESCE(cancelled_at, updated_at) as cancel_time 
          FROM orders 
          WHERE status = 'cancelled' AND COALESCE(cancelled_at, updated_at) > DATE_SUB(NOW(), INTERVAL 24 HOUR)
          ORDER BY COALESCE(cancelled_at, updated_at) DESC 
          LIMIT 10";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Customer</th><th>Amount</th><th>Cancelled At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['customer_name'] . "</td>";
        echo "<td>₱" . number_format($row['total_amount'], 2) . "</td>";
        echo "<td>" . $row['cancel_time'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No cancelled orders in last 24 hours</p>";
}
echo "<br>";

// Test API call
echo "<h3>Test API Response:</h3>";
echo "<button onclick='testAPI()'>Test get_notifications.php</button>";
echo "<div id='api-result' style='margin-top: 10px; padding: 10px; background: #f0f0f0; display: none;'></div>";

echo "<br><br>";
echo "<h3>Actions:</h3>";
echo "<button onclick='clearSession()'>Clear Session (Reset Notifications)</button> ";
echo "<a href='dashboard.php'><button>Go to Dashboard</button></a>";

?>

<script>
function testAPI() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('api-result').style.display = 'block';
            document.getElementById('api-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('api-result').style.display = 'block';
            document.getElementById('api-result').innerHTML = '<span style="color: red;">ERROR: ' + error + '</span>';
        });
}

function clearSession() {
    if (confirm('Clear notification session?')) {
        fetch('clear_notifications.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert('Session cleared!');
                location.reload();
            });
    }
}
</script>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}
table {
    border-collapse: collapse;
    width: 100%;
}
button {
    padding: 10px 20px;
    background: #5b6b46;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
button:hover {
    background: #4a5a3a;
}
</style>
