<?php
include('db.php');
session_start();

if (isset($_GET['unit_id'])) {
    $unit_id = $_GET['unit_id'];
    $_SESSION['pending_unit_id'] = $unit_id;

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?msg=Please login to reserve this unit.");
    } else {
        if($_SESSION['role'] == 'tenant') {
            header("Location: tenant_dashboard.php");
        } else {
            header("Location: admin_requests.php");
        }
    }
    exit();
}
?>