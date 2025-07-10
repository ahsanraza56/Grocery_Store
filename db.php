<?php
$host = "localhost";
$user = "root";
$pass = ""; // your MySQL password
$db = "grocery_store";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
