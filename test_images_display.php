<?php
include 'db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Test</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Product Images Debug Test</h1>
    
    <?php
    $result = $conn->query("SELECT id, name, image, category FROM products LIMIT 10");
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>DB Value</th><th>Processed Path</th><th>File Exists?</th><th>Image Preview</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        // Apply the same logic as in the fixed files
        $imgSrc = $row['image'];
        $originalPath = $imgSrc;
        
        if (!empty($imgSrc) && !preg_match('/^(img\/|admin\/|https?:\/\/)/i', $imgSrc)) {
            $imgSrc = 'img/' . $imgSrc;
        }
        
        $fileExists = !empty($imgSrc) && file_exists($imgSrc);
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td><code>" . htmlspecialchars($originalPath) . "</code></td>";
        echo "<td><code>" . htmlspecialchars($imgSrc) . "</code></td>";
        echo "<td class='" . ($fileExists ? "success" : "error") . "'>" . ($fileExists ? "✓ YES" : "✗ NO") . "</td>";
        echo "<td>";
        if ($fileExists) {
            echo "<img src='" . htmlspecialchars($imgSrc) . "' width='100' height='100' style='object-fit: contain; border: 1px solid #ccc;'>";
        } else {
            echo "<span class='error'>No image</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Also check what's in the img directory
    echo "<h2>Files in img/ directory (first 20):</h2>";
    echo "<ul>";
    $files = array_slice(scandir('img'), 2, 20); // Skip . and ..
    foreach($files as $file) {
        if (is_file('img/' . $file)) {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
    ?>
    
    <h2>Browser Console Check</h2>
    <p>Open browser console (F12) and check for any 404 errors on images.</p>
    
    <script>
        console.log('=== IMAGE LOAD TEST ===');
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                console.error('Failed to load:', this.src);
            });
            img.addEventListener('load', function() {
                console.log('Successfully loaded:', this.src);
            });
        });
    </script>
</body>
</html>
