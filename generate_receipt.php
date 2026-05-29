<?php
include('db.php');
session_start();

// Check kung may ID na pinasa
if (isset($_GET['id'])) {
    $bill_id = $_GET['id'];
    
    // Redirect sa view_receipt.php para makita ang design at makapag-print
    header("Location: view_receipt.php?id=" . $bill_id);
    exit();
} else {
    echo "Invalid Request.";
}
?>