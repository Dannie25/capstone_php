<?php
/**
 * Quick Fix: Update subcontract status from 'pending' to 'submitted'
 * Run this once to fix existing records
 */

require_once 'db.php';

echo "<h2>Fixing Subcontract Statuses...</h2>";

// Check current statuses
$result = $conn->query("SELECT id, status FROM subcontract_requests ORDER BY id");
echo "<h3>Current Statuses:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Current Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>#{$row['id']}</td><td>{$row['status']}</td></tr>";
}
echo "</table>";

// Update 'pending' to 'submitted'
$updateResult = $conn->query("UPDATE subcontract_requests SET status = 'submitted' WHERE status = 'pending'");
$affected = $conn->affected_rows;

echo "<h3>Update Result:</h3>";
echo "<p>✅ Updated <strong>{$affected}</strong> records from 'pending' to 'submitted'</p>";

// Show updated statuses
$result = $conn->query("SELECT id, status FROM subcontract_requests ORDER BY id");
echo "<h3>Updated Statuses:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>New Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>#{$row['id']}</td><td><strong>{$row['status']}</strong></td></tr>";
}
echo "</table>";

// Show counts
echo "<h3>Status Counts:</h3>";
$counts = $conn->query("SELECT status, COUNT(*) as count FROM subcontract_requests GROUP BY status");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Status</th><th>Count</th></tr>";
while ($row = $counts->fetch_assoc()) {
    echo "<tr><td>{$row['status']}</td><td>{$row['count']}</td></tr>";
}
echo "</table>";

echo "<p><a href='admin/orders.php#subcontract'>Go to Admin Orders</a></p>";

$conn->close();
?>
