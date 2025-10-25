<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "capstone_db";

$conn = mysqli_connect('localhost', 'root', '', 'capstone_db');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>