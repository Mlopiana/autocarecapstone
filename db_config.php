<?php
$host = "sql100.infinityfree.com";
$user = "if0_40281034";
$pass = "capstonekuno1"; // your DB password
$dbname = "if0_40281034_autocare"; // change to your database name

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
