<?php
$host = "localhost";
$user = "root";
$pass = ""; // your DB password
$dbname = "autocare_system"; // change to your database name

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
