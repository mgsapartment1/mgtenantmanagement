<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Kunin ang 'issue' mula sa dashboard form at i-save sa 'issue_details'
    $issue = isset($_POST['issue']) ? mysqli_real_escape_string($conn, $_POST['issue']) : '';
    $priority = isset($_POST['priority']) ? mysqli_real_escape_string($conn, $_POST['priority']) : 'Low';
    $image_name = NULL;

    // Handle Image Upload
    if (isset($_FILES['issue_image']) && $_FILES['issue_image']['error'] == 0) {
        $target_dir = "uploads/maintenance/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["issue_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "IMG_" . time() . "_" . $user_id . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["issue_image"]["tmp_name"], $target_file)) {
            $image_name = $new_filename; 
        }
    }

    if (!empty($issue)) {
        // Query base sa iyong phpMyAdmin structure
        $query = "INSERT INTO maintenance (user_id, issue_details, issue_image, priority, status, admin_remarks, request_date, created_at) 
                  VALUES ('$user_id', '$issue', '$image_name', '$priority', 'Pending', '', NOW(), NOW())";

        if (mysqli_query($conn, $query)) {
            header("Location: tenant_dashboard.php?msg=" . urlencode("Maintenance request sent successfully!"));
        } else {
            die("Database Error: " . mysqli_error($conn));
        }
    } else {
        header("Location: tenant_dashboard.php?error=" . urlencode("Please describe the issue."));
    }
    exit();
}
?>