<?php
session_start();
$admin_user = "admin";
$admin_pass = "admin123"; // you can encrypt it if needed

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === $admin_user && $password === $admin_pass) {
    $_SESSION['admin'] = true;
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: index.php?error=Invalid+credentials");
    exit;
}
