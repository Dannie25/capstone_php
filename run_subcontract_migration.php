<?php
/**
 * Run Subcontract Price Acceptance Migration
 * 
 * This script adds the necessary columns to the subcontract_requests table
 * for the price acceptance feature.
 * 
 * IMPORTANT: Run this only once!
 */

include 'db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Subcontract Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #5b6b46; }
    </style>
</head>
<body>
    <h1>🔧 Subcontract Price Acceptance Migration</h1>
";

// Check if migration already ran
$check_sql = "SHOW COLUMNS FROM subcontract_requests LIKE 'price'";
$result = $conn->query($check_sql);

if ($result && $result->num_rows > 0) {
    echo "<div class='info'>
        <strong>ℹ️ Migration Already Applied</strong><br>
        The 'price' column already exists in the subcontract_requests table.<br>
        This migration has likely been run before.
    </div>";
    
    // Show current columns
    $columns_sql = "SHOW COLUMNS FROM subcontract_requests";
    $columns_result = $conn->query($columns_sql);
    
    echo "<h2>Current Table Structure:</h2>";
    echo "<pre>";
    echo str_pad("Field", 30) . str_pad("Type", 20) . str_pad("Null", 10) . "Default\n";
    echo str_repeat("-", 80) . "\n";
    
    while ($col = $columns_result->fetch_assoc()) {
        echo str_pad($col['Field'], 30) . 
             str_pad($col['Type'], 20) . 
             str_pad($col['Null'], 10) . 
             ($col['Default'] ?? 'NULL') . "\n";
    }
    echo "</pre>";
    
} else {
    echo "<div class='info'>
        <strong>🚀 Running Migration...</strong><br>
        Adding columns: price, admin_notes, accepted_at, rejected_at, rejection_reason
    </div>";
    
    // Run the migration
    $migration_sql = "
        ALTER TABLE `subcontract_requests` 
        ADD COLUMN `price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `delivery_method`,
        ADD COLUMN `admin_notes` TEXT NULL AFTER `price`,
        ADD COLUMN `accepted_at` DATETIME NULL AFTER `updated_at`,
        ADD COLUMN `rejected_at` DATETIME NULL AFTER `accepted_at`,
        ADD COLUMN `rejection_reason` TEXT NULL AFTER `rejected_at`
    ";
    
    if ($conn->multi_query($migration_sql)) {
        // Clear any remaining results
        while ($conn->next_result()) {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
        
        echo "<div class='success'>
            <strong>✅ Migration Successful!</strong><br>
            The following columns have been added to the subcontract_requests table:
            <ul>
                <li><strong>price</strong> - DECIMAL(10,2) - Admin-set price</li>
                <li><strong>admin_notes</strong> - TEXT - Optional admin notes</li>
                <li><strong>accepted_at</strong> - DATETIME - Customer acceptance timestamp</li>
                <li><strong>rejected_at</strong> - DATETIME - Customer rejection timestamp</li>
                <li><strong>rejection_reason</strong> - TEXT - Customer's rejection reason</li>
            </ul>
        </div>";
        
        // Verify the columns were added
        $verify_sql = "SHOW COLUMNS FROM subcontract_requests";
        $verify_result = $conn->query($verify_sql);
        
        echo "<h2>Updated Table Structure:</h2>";
        echo "<pre>";
        echo str_pad("Field", 30) . str_pad("Type", 20) . str_pad("Null", 10) . "Default\n";
        echo str_repeat("-", 80) . "\n";
        
        while ($col = $verify_result->fetch_assoc()) {
            $is_new = in_array($col['Field'], ['price', 'admin_notes', 'accepted_at', 'rejected_at', 'rejection_reason']);
            $prefix = $is_new ? "✨ " : "   ";
            
            echo $prefix . str_pad($col['Field'], 27) . 
                 str_pad($col['Type'], 20) . 
                 str_pad($col['Null'], 10) . 
                 ($col['Default'] ?? 'NULL') . "\n";
        }
        echo "</pre>";
        
    } else {
        echo "<div class='error'>
            <strong>❌ Migration Failed!</strong><br>
            Error: " . htmlspecialchars($conn->error) . "
        </div>";
    }
}

echo "
    <h2>📋 Next Steps:</h2>
    <ol>
        <li>Go to <a href='admin/orders.php#subcontract'>Admin Orders → Subcontract Tab</a></li>
        <li>Click the <strong>Set Price</strong> button (tag icon) on a pending request</li>
        <li>Enter a price and optional notes, then submit</li>
        <li>Go to <a href='my_orders.php#subcontract'>My Orders → Subcontract Tab</a> as the customer</li>
        <li>You should see the green <strong>Quoted Price</strong> panel with Accept/Decline buttons</li>
    </ol>
    
    <h2>📖 Documentation:</h2>
    <p>For detailed information, see <a href='SUBCONTRACT_PRICE_IMPLEMENTATION.md'>SUBCONTRACT_PRICE_IMPLEMENTATION.md</a></p>
    
    <hr>
    <p style='text-align: center; color: #666;'>
        <small>Migration script completed. You can safely delete this file after verification.</small>
    </p>
</body>
</html>
";

$conn->close();
?>
