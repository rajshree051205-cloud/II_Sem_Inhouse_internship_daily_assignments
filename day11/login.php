<?php session_start();

if (isset($_POST['email'])) {
    $_SESSION['email'] = $_POST['email'];
    header("Location: dashboard.php");
}
?>

<form method="post">
    <input name="email">
    <button>Login</button>
</form>