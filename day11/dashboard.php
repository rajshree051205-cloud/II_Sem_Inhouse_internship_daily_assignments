<?php session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}
echo "<h2>Dashboard</h2>";
echo "Welcome " . $_SESSION['email'];