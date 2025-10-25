<?php
// Add quoted_price column to customization_requests table
include 'db.php';

echo "<h2>Adding quoted_price column to customization_requests table...</h2>";

// Add quoted_price column
$sql = "ALTER TABLE customization_requests 
        ADD COLUMN IF NOT EXISTS quoted_price DECIMAL(10,2) DEFAULT NULL AFTER status";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✅ Column 'quoted_price' added successfully!</p>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "<p style='color: orange;'>⚠️ Column 'quoted_price' already exists.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}

// Add notes column for admin to add notes when setting price
$sql2 = "ALTER TABLE customization_requests 
         ADD COLUMN IF NOT EXISTS admin_notes TEXT DEFAULT NULL AFTER quoted_price";

if ($conn->query($sql2) === TRUE) {
    echo "<p style='color: green;'>✅ Column 'admin_notes' added successfully!</p>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "<p style='color: orange;'>⚠️ Column 'admin_notes' already exists.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}

// Add price_set_at timestamp
$sql3 = "ALTER TABLE customization_requests 
         ADD COLUMN IF NOT EXISTS price_set_at TIMESTAMP NULL DEFAULT NULL AFTER admin_notes";

if ($conn->query($sql3) === TRUE) {
    echo "<p style='color: green;'>✅ Column 'price_set_at' added successfully!</p>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "<p style='color: orange;'>⚠️ Column 'price_set_at' already exists.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}

echo "<h3>✅ Migration Complete!</h3>";
echo "<p><a href='admin/orders.php'>Go to Admin Orders</a></p>";

$conn->close();
?>
