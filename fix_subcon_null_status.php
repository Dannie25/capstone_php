<?php
/**
 * Quick Fix: Update NULL/empty subcontract status to 'submitted'
 */

require_once 'db.php';

echo "<h2>Fixing NULL/Empty Subcontract Statuses...</h2>";

// Check current statuses (including NULL)
$result = $conn->query("SELECT id, status, created_at FROM subcontract_requests ORDER BY id");
echo "<h3>Current Statuses:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Current Status</th><th>Created At</th></tr>";
while ($row = $result->fetch_assoc()) {
    $status = $row['status'] ? $row['status'] : '<span style="color:red;">NULL/EMPTY</span>';
    echo "<tr><td>#{$row['id']}</td><td>{$status}</td><td>{$row['created_at']}</td></tr>";
}
echo "</table>";

// Update NULL or empty status to 'submitted'
$updateResult = $conn->query("UPDATE subcontract_requests SET status = 'submitted' WHERE status IS NULL OR status = ''");
$affected = $conn->affected_rows;

echo "<h3>Update Result:</h3>";
echo "<p>✅ Updated <strong>{$affected}</strong> records from NULL/empty to 'submitted'</p>";

// Also update 'pending' to 'submitted' if any
$updateResult2 = $conn->query("UPDATE subcontract_requests SET status = 'submitted' WHERE status = 'pending'");
$affected2 = $conn->affected_rows;
echo "<p>✅ Updated <strong>{$affected2}</strong> records from 'pending' to 'submitted'</p>";

// Show updated statuses
$result = $conn->query("SELECT id, status, created_at FROM subcontract_requests ORDER BY id");
echo "<h3>Updated Statuses:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>New Status</th><th>Created At</th></tr>";
while ($row = $result->fetch_assoc()) {
    $status = $row['status'] ? "<strong style='color:green;'>{$row['status']}</strong>" : '<span style="color:red;">STILL NULL</span>';
    echo "<tr><td>#{$row['id']}</td><td>{$status}</td><td>{$row['created_at']}</td></tr>";
}
echo "</table>";

// Show counts
echo "<h3>Status Counts:</h3>";
$counts = $conn->query("SELECT status, COUNT(*) as count FROM subcontract_requests GROUP BY status");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Status</th><th>Count</th></tr>";
while ($row = $counts->fetch_assoc()) {
    $statusDisplay = $row['status'] ? $row['status'] : 'NULL/EMPTY';
    echo "<tr><td>{$statusDisplay}</td><td>{$row['count']}</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<p style='color:green; font-weight:bold;'>✅ All done! Refresh your admin orders page.</p>";
echo "<p><a href='admin/orders.php#subcontract' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>Go to Admin Orders</a></p>";

$conn->close();
?>
