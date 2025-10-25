<?php
include 'db.php';

// Read and execute the SQL migration file
$sql = file_get_contents(__DIR__ . '/migrations/create_subcontract_requests_table.sql');

if ($conn->multi_query($sql)) {
    echo "Subcontract requests table created successfully!<br>";
    
    // Clear any remaining results
    while ($conn->more_results()) {
        $conn->next_result();
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$conn->close();
?>
