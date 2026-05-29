<?php
include('db.php');
session_start();

if (isset($_POST['accept_rules']) && isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET rules_accepted = 1 WHERE id = '$uid'");
    header("Location: tenant_dashboard.php");
    exit();
}
?>