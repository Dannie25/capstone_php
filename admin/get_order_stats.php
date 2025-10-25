<?php
require_once '../db.php';
header('Content-Type: application/json');

// Get order statistics
$pending = 0;
$shipped = 0;
$completed = 0;
$cancelled = 0;
$total = 0;

// Pending
$res = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
if ($res && $row = $res->fetch_assoc()) $pending = (int)$row['count'];
// Shipped
$res = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'shipped'");
if ($res && $row = $res->fetch_assoc()) $shipped = (int)$row['count'];
// Completed
$res = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'");
if ($res && $row = $res->fetch_assoc()) $completed = (int)$row['count'];
// Cancelled
$res = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'");
if ($res && $row = $res->fetch_assoc()) $cancelled = (int)$row['count'];
// Total
$res = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($res && $row = $res->fetch_assoc()) $total = (int)$row['count'];

echo json_encode([
    'pending' => $pending,
    'shipped' => $shipped,
    'completed' => $completed,
    'cancelled' => $cancelled,
    'total' => $total
]);
