<?php
include('db.php');

// 1. Hanapin ang lahat ng bills na 5 days from now ang due
$query = "SELECT b.*, u.full_name, u.email, u.phone 
          FROM bills b 
          JOIN users u ON b.user_id = u.id 
          WHERE b.status = 'Unpaid' 
          AND b.bill_type = 'Rent' 
          AND b.due_date = DATE_ADD(CURDATE(), INTERVAL 5 DAY)";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $email = $row['email'];
    $phone = $row['phone'];
    $name = $row['full_name'];
    $amount = $row['amount'];
    
    // I-send ang EMAIL (PHPMailer)
    // I-send ang SMS (Semaphore API code mo)
    echo "Reminder sent to $name for due date on " . $row['due_date'] . "<br>";
}
?>