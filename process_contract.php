<?php
include('db.php');
session_start();

if(isset($_POST['sign_contract'])) {
    $user_id = $_SESSION['user_id'];
    // Ginamit ko ang contract_signed_at para tugma sa query sa view_contract
    $timestamp = date('Y-m-d H:i:s');
    $sql = "UPDATE users SET 
            rules_accepted = 1, 
            contract_signed_at = '$timestamp' 
            WHERE id = '$user_id'";

    if(mysqli_query($conn, $sql)) {
        header("Location: tenant_dashboard.php?status=success");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>