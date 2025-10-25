<?php
// Simple test to check what's causing the error
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../db.php';

echo "Testing database queries...<br><br>";

// Test orders
echo "Testing Orders:<br>";
$orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$result = $conn->query($orders_query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "Pending Orders: " . $row['count'] . "<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Test subcontracts
echo "<br>Testing Subcontracts:<br>";
$subcon_query = "SELECT COUNT(*) as count FROM subcontract_requests WHERE status = 'pending'";
$result = $conn->query($subcon_query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "Pending Subcontracts: " . $row['count'] . "<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

// Test customizations
echo "<br>Testing Customizations:<br>";
$custom_query = "SELECT COUNT(*) as count FROM customization_requests WHERE status IN ('pending', 'submitted')";
$result = $conn->query($custom_query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "Pending Customizations: " . $row['count'] . "<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

echo "<br><br>All tests completed!";
?>
