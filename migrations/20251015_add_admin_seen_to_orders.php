<?php
// Migration to add admin_seen column to orders table
include __DIR__ . '/../db.php';

try {
    $result = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'admin_seen'");
    if ($result && $result->num_rows == 0) {
        $sql = "ALTER TABLE `orders` ADD COLUMN `admin_seen` TINYINT(1) DEFAULT 0 AFTER `cancelled_at`";
        if ($conn->query($sql) === TRUE) {
            echo "✓ Successfully added admin_seen column to orders table<br>";
        } else {
            echo "✗ Error adding admin_seen column: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ admin_seen column already exists in orders table<br>";
    }
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage();
}

$conn->close();

?>
