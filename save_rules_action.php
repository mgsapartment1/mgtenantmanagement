<?php
include('db.php');
session_start();

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Linisin ang input mula sa CKEditor
    $new_rules = mysqli_real_escape_string($conn, $_POST['house_rules']);
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['full_name'] ?? 'Admin';

    // 2. I-update ang house_rules sa settings table
    $update_query = "UPDATE settings SET setting_value = '$new_rules' WHERE setting_key = 'house_rules'";
    
    if (mysqli_query($conn, $update_query)) {
        $action = "Updated the Apartment House Rules/Contract Template";
        
        /* FIX: Inalis ang 'timestamp' column dahil ito ang nag-e-error. 
           Kadalasan, ang database (MySQL) ay may DEFAULT CURRENT_TIMESTAMP 
           sa logs table kaya hindi na kailangang i-specify.
        */
        $log_query = "INSERT INTO audit_logs (admin_id, admin_name, action, affected_table) 
                      VALUES ('$admin_id', '$admin_name', '$action', 'settings')";
        
        mysqli_query($conn, $log_query);

        // Redirect back sa settings page ng admin
        header("Location: manage_rules.php?success=1");
        exit();
    } else {
        // Kung may error sa update
        header("Location: manage_rules.php?status=error");
        exit();
    }
} else {
    header("Location: manage_rules.php");
    exit();
}
?>