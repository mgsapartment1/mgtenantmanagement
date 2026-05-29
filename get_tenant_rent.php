<?php
include('db.php');

if (isset($_POST['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    
    // Kunin ang monthly_rent mula sa units table base sa approved request ng user
    $query = "SELECT u.monthly_rent 
              FROM units u 
              JOIN rent_requests rr ON u.id = rr.unit_id 
              WHERE rr.user_id = '$user_id' AND rr.status = 'Approved' 
              LIMIT 1";
              
    $result = mysqli_query($conn, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo $row['monthly_rent'];
    } else {
        echo "0.00";
    }
}
?>