<?php
include('db.php');
session_start();

// SECURITY: Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $unit_id = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. Kunin muna ang Unit No para sa Audit Log
    $info_sql = "SELECT unit_no FROM units WHERE id = '$unit_id'";
    $info_res = mysqli_query($conn, $info_sql);
    $unit_data = mysqli_fetch_assoc($info_res);
    $unit_no = $unit_data['unit_no'];

    // 2. ARCHIVE LOGIC: Imbes na DELETE, i-set lang ang status sa 'Archived'
    // Siguraduhin na ang status column mo ay tumatanggap ng 'Archived'
    $archive_sql = "UPDATE units SET status = 'Archived' WHERE id = '$unit_id'";

    if (mysqli_query($conn, $archive_sql)) {
        // 3. INSERT TO AUDIT LOG
        $admin_id = $_SESSION['user_id'];
        $admin_name = $_SESSION['full_name'];
        $action = "Archived Unit $unit_no";
        
        mysqli_query($conn, "INSERT INTO audit_logs (admin_id, admin_name, action, affected_table) 
                             VALUES ('$admin_id', '$admin_name', '$action', 'units')");

        header("Location: manage_units.php?msg=Unit $unit_no has been archived.");
        exit();
    } else {
        header("Location: manage_units.php?msg=Error archiving unit.");
        exit();
    }
} else {
    header("Location: manage_units.php");
    exit();
}
?>