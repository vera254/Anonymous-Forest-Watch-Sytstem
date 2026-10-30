<?php
// Start session at the VERY beginning
session_start();

// Get the submitted data
$username = $_POST['username'];
$password = $_POST['password'];

// Check credentials
if ($username == "forest_admin" && $password == "KenyaForest2024") {
    // Set session variables
    $_SESSION["admin_logged_in"] = true;
    $_SESSION["admin_username"] = $username;
    $_SESSION["admin_name"] = "Forest Ministry Admin";
    
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Wrong credentials - go back to login
    header("Location: login.php?error=1");
    exit();
}
?>