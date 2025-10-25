<?php
// Run all migrations for capstone_db
include '../db.php';

echo "<h1>Running All Migrations</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// List of migration files to run
$migrations = [
    'create_subcontract_requests_table.sql',
    'create_product_images_table.sql',
    'create_customization_logs_table.sql',
    'create_wishlist_table.sql',
    'add_delivery_method_to_subcontract_requests.sql',
    'add_delivery_mode_to_orders.sql',
    'add_gcash_reference_to_orders.sql',
    'add_price_to_customization_requests.sql',
    'add_price_notes_to_customization.sql',
    'enhance_customization_request.sql',
    'update_customization_requests_table.sql',
    'update_design_file_column.sql',
    'fix_customization_status_enum.sql',
    'add_columns_to_customization_requests.sql',
    'add_detailed_measurements_to_customization.sql',
    'add_created_at_to_products.sql',
    '20250923_add_discount_to_products.sql',
    '20250923_add_quantity_to_product_colors.sql',
    '20250923_gcash_qr_migration.sql',
    '20250924_add_cancel_reason_to_orders.sql',
    '20251012_add_cancellation_reason.sql',
    '20251012_add_phone_to_users.sql',
    '20250116_add_color_size_inventory.sql'
];

$success_count = 0;
$error_count = 0;

foreach ($migrations as $migration_file) {
    $file_path = __DIR__ . '/' . $migration_file;
    
    if (!file_exists($file_path)) {
        echo "<p class='info'>⏭️ Skipping: <strong>$migration_file</strong> (file not found)</p>";
        continue;
    }
    
    echo "<p class='info'>🔄 Running: <strong>$migration_file</strong></p>";
    
    // Read SQL file
    $sql = file_get_contents($file_path);
    
    if (empty($sql)) {
        echo "<p class='error'>❌ Error: File is empty</p>";
        $error_count++;
        continue;
    }
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $migration_success = true;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            if ($conn->query($statement) === TRUE) {
                // Success - silent
            } else {
                // Check if error is "already exists" - that's OK
                if (strpos($conn->error, 'already exists') !== false || 
                    strpos($conn->error, 'Duplicate column') !== false ||
                    strpos($conn->error, 'Duplicate key') !== false) {
                    echo "<p class='info'>ℹ️ Already exists (skipping)</p>";
                } else {
                    echo "<p class='error'>❌ Error: " . htmlspecialchars($conn->error) . "</p>";
                    $migration_success = false;
                }
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "<p class='info'>ℹ️ Already exists (skipping)</p>";
            } else {
                echo "<p class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
                $migration_success = false;
            }
        }
    }
    
    if ($migration_success) {
        echo "<p class='success'>✅ Success!</p>";
        $success_count++;
    } else {
        $error_count++;
    }
    
    echo "<hr>";
}

echo "<h2>Summary</h2>";
echo "<p class='success'>✅ Successful: $success_count</p>";
echo "<p class='error'>❌ Failed: $error_count</p>";

if ($error_count == 0) {
    echo "<p class='success'><strong>🎉 All migrations completed successfully!</strong></p>";
    echo "<p><a href='../admin/orders.php' style='padding:10px 20px;background:#5b6b46;color:white;text-decoration:none;border-radius:4px;'>Go to Orders Page</a></p>";
} else {
    echo "<p class='error'><strong>⚠️ Some migrations failed. Please check the errors above.</strong></p>";
}

$conn->close();
?>
