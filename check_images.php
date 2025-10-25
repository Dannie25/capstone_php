<?php
include 'db.php';

echo "<h2>Product Images Check</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Image Path in DB</th><th>File Exists?</th><th>Preview</th></tr>";

$result = $conn->query("SELECT id, name, image FROM products LIMIT 10");
while($row = $result->fetch_assoc()) {
    $imagePath = $row['image'];
    
    // Check different possible paths
    $paths = [
        $imagePath,
        'img/' . $imagePath,
        'admin/' . $imagePath,
    ];
    
    $exists = false;
    $actualPath = '';
    foreach($paths as $path) {
        if(file_exists($path)) {
            $exists = true;
            $actualPath = $path;
            break;
        }
    }
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($imagePath) . "</td>";
    echo "<td>" . ($exists ? "✓ YES ($actualPath)" : "✗ NO") . "</td>";
    echo "<td>";
    if($exists) {
        echo "<img src='$actualPath' width='50' height='50' style='object-fit: contain;'>";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
?>
