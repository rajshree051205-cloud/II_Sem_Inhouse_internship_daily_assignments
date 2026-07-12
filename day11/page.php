<?php session_start();

if (!isset($_SESSION['count'])) {
    $_SESSION['count'] = 1;
} else {
    $_SESSION['count']++;
}

echo "<h2>Page Visited : " . $_SESSION['count'] . " Times</h2>";
?>
<a href="page.php">Refresh</a>