<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Clear All Orders</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d32f2f; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .warning h2 { color: #856404; margin-top: 0; }
        .btn { padding: 12px 24px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin: 10px 5px; text-decoration: none; display: inline-block; }
        .btn-danger { background: #d32f2f; color: white; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .success { background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 8px; color: #155724; }
        .error { background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 8px; color: #721c24; }
        ul { line-height: 2; }
    </style>
</head>
<body>
    <div class='container'>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'DELETE_ALL_ORDERS') {
    
    echo "<h1>🗑️ Clearing All Orders...</h1>";
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get counts before deletion
        $order_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
        $order_items_count = $conn->query("SELECT COUNT(*) as count FROM order_items")->fetch_assoc()['count'];
        $subcontract_count = $conn->query("SELECT COUNT(*) as count FROM subcontract_requests")->fetch_assoc()['count'];
        $customization_count = $conn->query("SELECT COUNT(*) as count FROM customization_requests")->fetch_assoc()['count'];
        
        // Delete in correct order (child tables first)
        $tables = [
            'order_feedback' => 'Order feedback/reviews',
            'order_items' => 'Order items',
            'orders' => 'Orders',
            'subcontract_requests' => 'Subcontract requests',
            'customization_requests' => 'Customization requests'
        ];
        
        $results = [];
        foreach ($tables as $table => $description) {
            // Check if table exists
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check->num_rows > 0) {
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $count_result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $conn->query("DELETE FROM $table");
                    $results[] = "✓ Deleted $count records from <strong>$table</strong> ($description)";
                } else {
                    $results[] = "ℹ️ Table <strong>$table</strong> was already empty";
                }
                
                // Reset auto-increment
                $conn->query("ALTER TABLE $table AUTO_INCREMENT = 1");
            } else {
                $results[] = "⚠️ Table <strong>$table</strong> does not exist (skipped)";
            }
        }
        
        // Also clear cart items (optional)
        $cart_check = $conn->query("SHOW TABLES LIKE 'cart'");
        if ($cart_check->num_rows > 0) {
            $cart_count = $conn->query("SELECT COUNT(*) as count FROM cart")->fetch_assoc()['count'];
            if ($cart_count > 0) {
                $conn->query("DELETE FROM cart");
                $conn->query("ALTER TABLE cart AUTO_INCREMENT = 1");
                $results[] = "✓ Deleted $cart_count items from <strong>cart</strong> (Shopping carts)";
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        echo "<div class='success'>";
        echo "<h2>✅ Successfully Cleared All Orders!</h2>";
        echo "<ul>";
        foreach ($results as $result) {
            echo "<li>$result</li>";
        }
        echo "</ul>";
        echo "<p><strong>Summary:</strong></p>";
        echo "<ul>";
        echo "<li>Total orders deleted: <strong>$order_count</strong></li>";
        echo "<li>Total order items deleted: <strong>$order_items_count</strong></li>";
        echo "<li>Total subcontract requests deleted: <strong>$subcontract_count</strong></li>";
        echo "<li>Total customization requests deleted: <strong>$customization_count</strong></li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<p><a href='orders.php' class='btn btn-secondary'>← Back to Orders</a></p>";
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        echo "<div class='error'>";
        echo "<h2>❌ Error!</h2>";
        echo "<p>Failed to clear orders: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        
        echo "<p><a href='clear_all_orders.php' class='btn btn-secondary'>← Try Again</a></p>";
    }
    
} else {
    // Show confirmation form
    
    // Get current counts
    $order_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    $order_items_count = $conn->query("SELECT COUNT(*) as count FROM order_items")->fetch_assoc()['count'];
    $subcontract_count = $conn->query("SELECT COUNT(*) as count FROM subcontract_requests")->fetch_assoc()['count'];
    $customization_count = $conn->query("SELECT COUNT(*) as count FROM customization_requests")->fetch_assoc()['count'];
    
    echo "<h1>⚠️ Clear All Orders, Subcontracts & Customizations</h1>";
    
    echo "<div class='warning'>";
    echo "<h2>🚨 WARNING: This action cannot be undone!</h2>";
    echo "<p><strong>This will permanently delete:</strong></p>";
    echo "<ul>";
    echo "<li><strong>$order_count</strong> orders</li>";
    echo "<li><strong>$order_items_count</strong> order items</li>";
    echo "<li><strong>$subcontract_count</strong> subcontract requests</li>";
    echo "<li><strong>$customization_count</strong> customization requests</li>";
    echo "<li>All order feedback/reviews</li>";
    echo "<li>All shopping cart items</li>";
    echo "</ul>";
    echo "<p style='color: #d32f2f; font-weight: bold;'>⚠️ This will reset ALL order data to zero!</p>";
    echo "</div>";
    
    echo "<form method='POST' onsubmit='return confirm(\"Are you ABSOLUTELY SURE you want to delete ALL orders? This cannot be undone!\");'>";
    echo "<p><strong>Type exactly:</strong> <code>DELETE_ALL_ORDERS</code> to confirm</p>";
    echo "<input type='text' name='confirm' required style='padding: 10px; font-size: 16px; width: 300px; border: 2px solid #ddd; border-radius: 4px;' placeholder='Type here...'>";
    echo "<br><br>";
    echo "<button type='submit' class='btn btn-danger'>🗑️ Yes, Delete All Orders</button>";
    echo "<a href='orders.php' class='btn btn-secondary'>Cancel</a>";
    echo "</form>";
}

echo "</div></body></html>";
?>
