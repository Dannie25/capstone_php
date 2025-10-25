<?php
include 'db.php';

// Create chat_messages table
$sql_chat = "CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    admin_id INT DEFAULT NULL,
    sender_type ENUM('customer', 'admin') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
)";

// Execute query
if ($conn->query($sql_chat) === TRUE) {
    echo "Chat messages table created successfully<br>";
} else {
    echo "Error creating chat_messages table: " . $conn->error . "<br>";
}

echo "<br><strong>Chat system database setup complete!</strong><br>";
echo "<a href='customer-inq.php'>Go to Customer Inquiry</a>";

$conn->close();
?>
