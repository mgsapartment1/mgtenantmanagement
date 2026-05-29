<?php
// 1. Database Connection (Replace with your actual connection file if you have conn.php)
$conn = mysqli_connect("localhost", "root", "", "mgs_apartment_db");

// 2. Include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3. Check if an ID was passed in the URL
if (isset($_GET['id'])) {
    $tenant_id = $_GET['id'];

    // 4. Fetch the specific tenant's data dynamically
    $query = "SELECT * FROM tenants WHERE id = '$tenant_id'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $tenant = mysqli_fetch_assoc($result);

        // PHP AUTOMATICALLY ASSIGNS THESE VALUES BASED ON THE CLICKED ID:
        $tenant_email = $tenant['email'];  
        $tenant_name = $tenant['name'];   
        $tenant_unit = $tenant['unit'];     

        // 5. Initialize PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server Settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mgsapartment1@gmail.com'; // MG's / Admin Email
            $mail->Password   = 'mavn qrte klek lscv';           // Your 16-digit Google App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('mgsapartment1@gmail.com', "MG's Apartment");
            $mail->addAddress($tenant_email, $tenant_name); // Dynamic recipient!

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Monthly Rent Notice - MG's Apartment";
            $mail->Body    = "
                <h2>Good day, $tenant_name!</h2>
                <p>This is a friendly reminder that your monthly rent invoice for <b>Unit $tenant_unit</b> has been posted.</p>
                <p>Please log in to your <a href='http://localhost/onlinetenantmanagement/'>Tenant Portal</a> to review your balance. Thank you!</p>
            ";

            $mail->send();
            echo "Notification successfully sent to $tenant_name ($tenant_email)!";
            
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        echo "No tenant found with this ID.";
    }
} else {
    echo "No tenant selected.";
}
?>