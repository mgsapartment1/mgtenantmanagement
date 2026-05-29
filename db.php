<?php
$conn = mysqli_connect("localhost", "root", "", "onlinetenantmanagement");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Manatili ang iyong original na logActivity
function logActivity($conn, $admin_id, $admin_name, $action, $table) {
    $action = mysqli_real_escape_string($conn, $action);
    $query = "INSERT INTO audit_logs (admin_id, admin_name, action, affected_table) 
              VALUES ('$admin_id', '$admin_name', '$action', '$table')";
    return mysqli_query($conn, $query);
}

/**
 * HELPER FUNCTIONS PARA SA BILLING
 * Makakatulong ito para hindi ka paulit-ulit ng code sa ibang files.
 */

// Kunin ang huling reading ng tenant
function getLastReading($conn, $user_id, $type) {
    $type = mysqli_real_escape_string($conn, $type);
    $query = "SELECT current_reading FROM bills 
              WHERE user_id = '$user_id' AND bill_type = '$type' 
              ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['current_reading'];
    }
    return 0.00;
}

// I-format ang pera (₱) para laging maganda tignan sa UI
function formatMoney($amount) {
    return "₱" . number_format($amount, 2);
}
?>