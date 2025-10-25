<?php
/**
 * Migration: Update subcontract_requests table for new workflow
 * Adds columns for price approval and payment workflow
 */

require_once __DIR__ . '/../db.php';

echo "Starting migration: Update subcontract_requests table...\n";

try {
    // Check if quoted_price column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'quoted_price'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD quoted_price DECIMAL(10,2) DEFAULT NULL AFTER quantity");
        echo "✓ Added quoted_price column\n";
    } else {
        echo "- quoted_price column already exists\n";
    }
    
    // Check if admin_notes column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'admin_notes'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD admin_notes TEXT DEFAULT NULL AFTER quoted_price");
        echo "✓ Added admin_notes column\n";
    } else {
        echo "- admin_notes column already exists\n";
    }
    
    // Check if price_set_at column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'price_set_at'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD price_set_at DATETIME DEFAULT NULL AFTER admin_notes");
        echo "✓ Added price_set_at column\n";
    } else {
        echo "- price_set_at column already exists\n";
    }
    
    // Check if payment_method column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'payment_method'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD payment_method VARCHAR(50) DEFAULT NULL AFTER price_set_at");
        echo "✓ Added payment_method column\n";
    } else {
        echo "- payment_method column already exists\n";
    }
    
    // Check if delivery_mode column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'delivery_mode'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD delivery_mode VARCHAR(50) DEFAULT NULL AFTER payment_method");
        echo "✓ Added delivery_mode column\n";
    } else {
        echo "- delivery_mode column already exists\n";
    }
    
    // Check if delivery_address column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'delivery_address'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD delivery_address TEXT DEFAULT NULL AFTER delivery_mode");
        echo "✓ Added delivery_address column\n";
    } else {
        echo "- delivery_address column already exists\n";
    }
    
    // Check if email column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'email'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD email VARCHAR(255) DEFAULT NULL AFTER customer_name");
        echo "✓ Added email column\n";
    } else {
        echo "- email column already exists\n";
    }
    
    // Check if updated_at column exists
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'updated_at'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "✓ Added updated_at column\n";
    } else {
        echo "- updated_at column already exists\n";
    }
    
    // Update existing 'pending' status to 'submitted' for consistency
    $conn->query("UPDATE subcontract_requests SET status = 'submitted' WHERE status = 'pending'");
    echo "✓ Updated existing 'pending' statuses to 'submitted'\n";
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nNew workflow statuses:\n";
    echo "  - submitted: Customer submitted request\n";
    echo "  - approved: Admin set price, waiting for customer\n";
    echo "  - verifying: Customer accepted, verifying payment\n";
    echo "  - completed: Order completed\n";
    echo "  - cancelled: Order cancelled\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
