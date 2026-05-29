<?php
include('db.php');

if (isset($_POST['user_id']) && isset($_POST['type'])) {
    $uID = mysqli_real_escape_string($conn, $_POST['user_id']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);

    // Ginagamit ang 'current_reading' mula sa DB
    $query = "SELECT current_reading FROM bills 
              WHERE user_id = '$uID' AND bill_type = '$type' 
              ORDER BY id DESC LIMIT 1";
    
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        echo number_format($row['current_reading'], 2, '.', '');
    } else {
        echo "0.00"; 
    }
}
?>