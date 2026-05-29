<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit_bill'])) {
    $user_id   = mysqli_real_escape_string($conn, $_POST['user_id']);
    $bill_type = mysqli_real_escape_string($conn, $_POST['bill_type']);
    $amount    = mysqli_real_escape_string($conn, $_POST['amount']);
    $due_date  = mysqli_real_escape_string($conn, $_POST['due_date']);
    $status    = 'Unpaid'; 
    $prev_reading = !empty($_POST['previous_reading']) ? mysqli_real_escape_string($conn, $_POST['previous_reading']) : 0.00;
    $curr_reading = !empty($_POST['current_reading']) ? mysqli_real_escape_string($conn, $_POST['current_reading']) : 0.00;
    $rate         = !empty($_POST['rate']) ? mysqli_real_escape_string($conn, $_POST['rate']) : 0.00;
    
    $prev_reading = floatval($prev_reading);
    $curr_reading = floatval($curr_reading);
    $rate         = floatval($rate);
    $amount       = floatval($amount);

    $sql = "INSERT INTO bills (
                user_id, 
                bill_type, 
                previous_reading, 
                current_reading, 
                rate, 
                amount, 
                due_date, 
                status, 
                penalty
            ) VALUES (
                '$user_id', 
                '$bill_type', 
                '$prev_reading', 
                '$curr_reading', 
                '$rate', 
                '$amount', 
                '$due_date', 
                '$status', 
                0.00 
            )";

    if (mysqli_query($conn, $sql)) {
        
        // --- 🔔 1. NOTIFICATION LOGIC (Para sa Bell ni Tenant) ---
        $notif_msg = "New Bill: Mayroon kang bagong billing para sa $bill_type (₱" . number_format($amount, 2) . "). Due date: " . date('M d, Y', strtotime($due_date));
        
        $notif_sql = "INSERT INTO notifications (user_id, message, status, created_at) 
                      VALUES ('$user_id', '$notif_msg', 'unread', NOW())";
        mysqli_query($conn, $notif_sql);
        // --- END NG NOTIFICATION LOGIC ---

        // 2. Audit Log Entry
        $admin_name = $_SESSION['full_name'] ?? 'Admin'; // Inayos ko ito para kumuha sa session mo
        $action = "Added $bill_type bill for User ID $user_id (Amount: $amount)";
        mysqli_query($conn, "INSERT INTO audit_logs (admin_name, action, affected_table) 
                             VALUES ('$admin_name', '$action', 'bills')");

        $_SESSION['success_msg'] = "Billing record saved successfully!";
        header("Location: billing.php?msg=success");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: billing.php");
    exit();
}
?>