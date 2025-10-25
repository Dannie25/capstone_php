<?php
// Direct test of get_all_pending.php
$url = 'http://localhost/capstone_php/admin/get_all_pending.php';
$response = file_get_contents($url);

echo "<h3>Raw Response:</h3>";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

echo "<h3>Response Length:</h3>";
echo strlen($response) . " bytes";

echo "<h3>Decoded JSON:</h3>";
$data = json_decode($response, true);
if ($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "JSON decode failed: " . json_last_error_msg();
}
?>
