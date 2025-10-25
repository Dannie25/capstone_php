<?php
/**
 * Force Fix: Update subcontract status using direct ID targeting
 */

require_once 'db.php';

echo "<h2>Force Fixing Subcontract Statuses...</h2>";

// First, let's see the table structure
echo "<h3>Table Structure:</h3>";
$structure = $conn->query("DESCRIBE subcontract_requests");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $structure->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>" . ($row['Default'] ?? 'NULL') . "</td></tr>";
}
echo "</table>";

// Check current statuses with actual values
echo "<h3>Current Raw Data:</h3>";
$result = $conn->query("SELECT id, status, ISNULL(status) as is_null, status = '' as is_empty FROM subcontract_requests ORDER BY id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Status Value</th><th>Is NULL?</th><th>Is Empty?</th></tr>";
while ($row = $result->fetch_assoc()) {
    $statusDisplay = $row['status'] === null ? 'NULL' : ($row['status'] === '' ? 'EMPTY STRING' : $row['status']);
    echo "<tr><td>#{$row['id']}</td><td>{$statusDisplay}</td><td>{$row['is_null']}</td><td>{$row['is_empty']}</td></tr>";
}
echo "</table>";

// Try multiple update approaches
echo "<h3>Attempting Updates...</h3>";

// Approach 1: Update where status IS NULL
$sql1 = "UPDATE subcontract_requests SET status = 'submitted' WHERE status IS NULL";
$conn->query($sql1);
echo "<p>Approach 1 (IS NULL): Affected {$conn->affected_rows} rows</p>";

// Approach 2: Update where status = ''
$sql2 = "UPDATE subcontract_requests SET status = 'submitted' WHERE status = ''";
$conn->query($sql2);
echo "<p>Approach 2 (= ''): Affected {$conn->affected_rows} rows</p>";

// Approach 3: Update where status NOT IN completed list
$sql3 = "UPDATE subcontract_requests SET status = 'submitted' WHERE status NOT IN ('submitted', 'approved', 'verifying', 'in_progress', 'completed', 'cancelled')";
$conn->query($sql3);
echo "<p>Approach 3 (NOT IN valid statuses): Affected {$conn->affected_rows} rows</p>";

// Approach 4: Direct update by ID for records 2 and 3
$sql4a = "UPDATE subcontract_requests SET status = 'submitted' WHERE id = 2";
$conn->query($sql4a);
echo "<p>Approach 4a (ID=2): Affected {$conn->affected_rows} rows</p>";

$sql4b = "UPDATE subcontract_requests SET status = 'submitted' WHERE id = 3";
$conn->query($sql4b);
echo "<p>Approach 4b (ID=3): Affected {$conn->affected_rows} rows</p>";

// Show final statuses
echo "<h3>Final Statuses:</h3>";
$result = $conn->query("SELECT id, status, created_at FROM subcontract_requests ORDER BY id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Status</th><th>Created At</th></tr>";
while ($row = $result->fetch_assoc()) {
    $statusDisplay = $row['status'] ? "<strong style='color:green;'>{$row['status']}</strong>" : '<span style="color:red;">STILL NULL/EMPTY</span>';
    echo "<tr><td>#{$row['id']}</td><td>{$statusDisplay}</td><td>{$row['created_at']}</td></tr>";
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
echo "<p style='color:green; font-weight:bold; font-size:18px;'>✅ Force update complete!</p>";
echo "<p><a href='admin/orders.php#subcontract' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px; font-size:16px;'>Go to Admin Orders</a></p>";

$conn->close();
?>
