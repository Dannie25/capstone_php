<!DOCTYPE html>
<html>
<head>
    <title>Direct Image Test</title>
</head>
<body>
    <h1>Direct Image Access Test</h1>
    
    <h2>Test 1: Relative path - img/68ee2a7913150.png</h2>
    <img src="img/68ee2a7913150.png" width="200" style="border: 2px solid red;">
    
    <h2>Test 2: Absolute path - /capstone_php/img/68ee2a7913150.png</h2>
    <img src="/capstone_php/img/68ee2a7913150.png" width="200" style="border: 2px solid blue;">
    
    <h2>Test 3: Full URL</h2>
    <img src="http://localhost/capstone_php/img/68ee2a7913150.png" width="200" style="border: 2px solid green;">
    
    <h2>File System Check</h2>
    <?php
    $file = 'img/68ee2a7913150.png';
    echo "<p>File exists: " . (file_exists($file) ? "YES" : "NO") . "</p>";
    echo "<p>File path: " . realpath($file) . "</p>";
    echo "<p>File size: " . (file_exists($file) ? filesize($file) . " bytes" : "N/A") . "</p>";
    echo "<p>Is readable: " . (is_readable($file) ? "YES" : "NO") . "</p>";
    ?>
    
    <script>
        document.querySelectorAll('img').forEach((img, index) => {
            img.addEventListener('load', function() {
                console.log('Test ' + (index + 1) + ' loaded successfully:', this.src);
            });
            img.addEventListener('error', function() {
                console.error('Test ' + (index + 1) + ' FAILED:', this.src);
            });
        });
    </script>
</body>
</html>
