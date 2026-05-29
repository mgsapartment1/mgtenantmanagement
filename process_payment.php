<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit_payment'])) {
    $bill_id = mysqli_real_escape_string($conn, $_POST['bill_id']);
    $method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // Kunin ang ref_no mula sa form
    $reference_no = isset($_POST['reference_no']) ? mysqli_real_escape_string($conn, $_POST['reference_no']) : '';
    
    $query = "";

    if ($method === 'Cash') {
        // TAMA NA FLOW: 'Pending Approval' muna kahit cash para ma-verify ni admin kung natanggap na ang pera physically
        $query = "UPDATE bills SET 
                  payment_method = 'Cash', 
                  status = 'Pending Approval', 
                  reference_no = 'CASH-" . strtoupper(uniqid()) . "',
                  date_paid = NOW()
                  WHERE id = '$bill_id'";
    } else {
        // Ang inyong uploads folder na tugma sa text logic
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        if (!empty($_FILES['proof_img']['name'])) {
            $file_extension = pathinfo($_FILES['proof_img']['name'], PATHINFO_EXTENSION);
            $file_name = time() . '_' . $bill_id . '.' . $file_extension;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['proof_img']['tmp_name'], $target_file)) {
                // FIXED WORKFLOW ROUTER: Ginawa nating 'Pending Approval' ang status at inalis ang direct view_receipt redirection
                $query = "UPDATE bills SET 
                          payment_method = '$method', 
                          reference_no = '$reference_no', 
                          proof_of_payment = '$file_name', 
                          status = 'Pending Approval',
                          date_paid = NOW() 
                          WHERE id = '$bill_id'";
            } else {
                die("Error uploading receipt.");
            }
        } else {
            die("Error: Please upload proof of payment.");
        }
    }

    if ($query !== "" && mysqli_query($conn, $query)) {
        // TAMA NA REDIRECTION: Ibalik sa listahan ng bills niya, kung saan makikita niya na 'Pending Verification' ang status
        echo "<script>
                alert('Payment submitted successfully! Waiting for admin verification and approval before receipt generation.'); 
                window.location.href = 'tenant_bills.php';
              </script>";
        exit();
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
} else {
    header("Location: tenant_bills.php");
    exit();
}
?>