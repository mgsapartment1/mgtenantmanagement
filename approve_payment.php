<?php
// 1. IMPORT PHPMAILER CLASSES AT THE VERY TOP
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include('db.php');
session_start();

// I-set ang Timezone sa Pilipinas
date_default_timezone_set('Asia/Manila');

/**
 * SECURITY CHECK: Admin lang ang pwedeng mag-approve
 */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bill_id'])) {
    $bill_id    = mysqli_real_escape_string($conn, $_POST['bill_id']);
    $action     = mysqli_real_escape_string($conn, $_POST['action']);
    $admin_name = $_SESSION['full_name'] ?? 'Admin';
    $admin_id   = $_SESSION['user_id']; 
    $date_now   = date('Y-m-d H:i:s');

    // KUNIN ANG DATA NG BILL PARA SA RESIBO
    $info_query = "SELECT b.*, u.id as tenant_id, u.full_name, u.email 
                   FROM bills b 
                   JOIN users u ON b.user_id = u.id 
                   WHERE b.id = '$bill_id' LIMIT 1";
    $info_res = mysqli_query($conn, $info_query);
    $bill_data = mysqli_fetch_assoc($info_res);

    if (!$bill_data) { 
        die("Billing record not found."); 
    }

    $tenant_id    = $bill_data['tenant_id'];
    $tenant_email = $bill_data['email'];
    $tenant_name  = $bill_data['full_name'];
    $bill_type    = $bill_data['bill_type'];
    $amount       = $bill_data['amount'];
    $method       = $bill_data['payment_method'];
    
    $ref_no       = !empty($bill_data['reference_no']) ? $bill_data['reference_no'] : 'N/A';
    $receipt_img  = !empty($bill_data['proof_of_payment']) ? $bill_data['proof_of_payment'] : ''; 
    $amount_fmt   = number_format($amount, 2);

    if ($action === 'approve') {
        // A. UPDATE STATUS NG BILL SA 'PAID'
        $update_bill = "UPDATE bills SET status = 'Paid', date_paid = '$date_now' WHERE id = '$bill_id'";
        
        if (mysqli_query($conn, $update_bill)) {
            
            // B. INSERT SA RECEIPTS TABLE (History para sa resibo)
            $insert_receipt = "INSERT INTO receipts (user_id, bill_id, amount, method, ref_no, receipt_image, date_paid) 
                               VALUES ('$tenant_id', '$bill_id', '$amount', '$method', '$ref_no', '$receipt_img', '$date_now')";
            mysqli_query($conn, $insert_receipt);

            // C. NOTIFICATION PARA KAY TENANT
            $notif_title = "Payment Approved ₱$amount_fmt";
            $notif_msg = "Your payment for $bill_type (₱$amount_fmt) via $method is now APPROVED. You can now view and download your Official Receipt.";
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, status, created_at) 
                                 VALUES ('$tenant_id', '$notif_title', '$notif_msg', 'unread', '$date_now')");

            // D. AUTOMATED EMAIL RECEIPT USING PHPMAILER ENGINE
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'mgsapartment1@gmail.com';       
                $mail->Password   = 'uicarhkqncphsitx'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Clean header formation to avoid spam folder routing
                $mail->setFrom('mgsapartment1@gmail.com', 'MGs Apartment Notification');
                $mail->addAddress($tenant_email, $tenant_name);

                $mail->isHTML(true);
                $mail->Subject = "Official Payment Receipt - MGs Apartment";
                
                $mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif; color: #333; background-color: #f8fafc; padding: 20px;'>
                    <div style='max-width: 500px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                        <div style='text-align: center; background-color: #16a34a; color: white; padding: 20px;'>
                            <h2 style='margin: 0; font-size: 22px; letter-spacing: 0.5px;'>MGs Apartment</h2>
                            <p style='margin: 5px 0 0 0; opacity: 0.9; font-size: 13px;'>Official Electronic Receipt</p>
                        </div>
                        <div style='padding: 25px;'>
                            <p style='font-size: 15px;'>Dear <b>$tenant_name</b>,</p>
                            <p>We are pleased to inform you that your payment validation is successful. Please find your remittance breakdown details below:</p>
                            
                            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                                <tr style='background-color: #f8fafc;'><td style='padding: 10px; border-bottom: 1px solid #edf2f7; color: #4a5568;'><b>Payment Category:</b></td><td style='padding: 10px; border-bottom: 1px solid #edf2f7; text-align: right; font-weight: 600;'>$bill_type Allocation</td></tr>
                                <tr><td style='padding: 10px; border-bottom: 1px solid #edf2f7; color: #4a5568;'><b>Total Paid Amount:</b></td><td style='padding: 10px; border-bottom: 1px solid #edf2f7; text-align: right; color: #16a34a; font-weight: bold; font-size: 16px;'>₱$amount_fmt</td></tr>
                                <tr style='background-color: #f8fafc;'><td style='padding: 10px; border-bottom: 1px solid #edf2f7; color: #4a5568;'><b>Method:</b></td><td style='padding: 10px; border-bottom: 1px solid #edf2f7; text-align: right;'>$method</td></tr>
                                <tr><td style='padding: 10px; border-bottom: 1px solid #edf2f7; color: #4a5568;'><b>Reference ID:</b></td><td style='padding: 10px; border-bottom: 1px solid #edf2f7; text-align: right; font-family: monospace; font-size: 13px;'>$ref_no</td></tr>
                                <tr style='background-color: #f8fafc;'><td style='padding: 10px; border-bottom: 1px solid #edf2f7; color: #4a5568;'><b>Date Settled:</b></td><td style='padding: 10px; border-bottom: 1px solid #edf2f7; text-align: right;'>".date('M d, Y h:i A')."</td></tr>
                            </table>
                            
                            <p style='color: #4a5568; font-size: 13px; text-align: center; margin-top: 25px;'>You can now also save or print an official copy of your receipt by logging in to your Tenant Portal dashboard.</p>
                            
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-top: 30px;'>
                            <p style='font-size: 11px; color: #a0aec0; text-align: center; margin: 0;'>Thank you for your prompt settlement! This is a system-automated acknowledgment record.</p>
                        </div>
                    </div>
                </body>
                </html>";

                $mail->send();
            } catch (Exception $e) {
                // Tahimik na magpapatuloy ang system kahit magka-network error ang email sending
            }

            // Tawagin ang logActivity() mula sa db.php
            logActivity($conn, $admin_id, $admin_name, "Approved payment for $bill_type (ID: $bill_id)", "bills");
            $_SESSION['success_msg'] = "Payment Approved! Receipt has been generated.";
        }
    } else {
        // REJECT LOGIC: Ibalik sa Unpaid at linisin ang fields para makapag-upload ulit si Tenant
        $reject_sql = "UPDATE bills SET status = 'Unpaid', proof_of_payment = NULL, reference_no = NULL WHERE id = '$bill_id'";
        
        if (mysqli_query($conn, $reject_sql)) {
            $notif_title = "Payment Rejected ❌";
            $notif_msg = "Your payment submission for $bill_type (₱$amount_fmt) was rejected due to invalid reference code or proof image. Please review and re-upload.";
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, status, created_at) 
                                 VALUES ('$tenant_id', '$notif_title', '$notif_msg', 'unread', '$date_now')");
            
            logActivity($conn, $admin_id, $admin_name, "Rejected payment for $bill_type (ID: $bill_id)", "bills");
            $_SESSION['error_msg'] = "Payment rejected. Tenant notified.";
        }
    }

    header("Location: billing.php?status=success");
    exit();
} else {
    header("Location: billing.php");
    exit();
}
?>