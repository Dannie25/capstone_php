<?php
$file = 'img/68ee2a7913150.png';

echo "<h1>Image File Analysis</h1>";

if (file_exists($file)) {
    echo "<h2>Basic Info:</h2>";
    echo "File: $file<br>";
    echo "Size: " . filesize($file) . " bytes<br>";
    echo "Real path: " . realpath($file) . "<br><br>";
    
    echo "<h2>File Type Detection:</h2>";
    
    // Method 1: getimagesize
    $imageInfo = @getimagesize($file);
    if ($imageInfo) {
        echo "✓ getimagesize() SUCCESS<br>";
        echo "Width: " . $imageInfo[0] . "px<br>";
        echo "Height: " . $imageInfo[1] . "px<br>";
        echo "Type: " . $imageInfo[2] . " (" . image_type_to_mime_type($imageInfo[2]) . ")<br>";
        echo "HTML: " . $imageInfo[3] . "<br><br>";
    } else {
        echo "✗ getimagesize() FAILED - File is NOT a valid image!<br><br>";
    }
    
    // Method 2: finfo
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file);
        finfo_close($finfo);
        echo "MIME type (finfo): $mimeType<br><br>";
    }
    
    // Method 3: Read first bytes
    $handle = fopen($file, 'rb');
    $header = fread($handle, 16);
    fclose($handle);
    
    echo "<h2>File Header (first 16 bytes):</h2>";
    echo "Hex: " . bin2hex($header) . "<br>";
    echo "ASCII: " . htmlspecialchars($header) . "<br><br>";
    
    // Check if it's actually a PNG
    $pngHeader = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
    if (substr($header, 0, 8) === $pngHeader) {
        echo "✓ Valid PNG header detected<br>";
    } else {
        echo "✗ NOT a valid PNG file! Header doesn't match.<br>";
    }
    
    // Try to display it anyway
    echo "<h2>Attempt to Display:</h2>";
    echo "<img src='$file' style='max-width: 300px; border: 2px solid red;'><br>";
    
} else {
    echo "File not found!";
}
?>
