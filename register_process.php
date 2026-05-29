<?php
include('db.php');
session_start();

if (isset($_POST['register'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $contact   = mysqli_real_escape_string($conn, $_POST['contact']);
    $emergency = mysqli_real_escape_string($conn, $_POST['emergency_contact']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $unit_id   = mysqli_real_escape_string($conn, $_POST['unit_id']);

    // 1. Final check if unit is still available
    if (!empty($unit_id)) {
        $final_check = mysqli_query($conn, "SELECT id FROM rent_requests 
                                            WHERE unit_id = '$unit_id' 
                                            AND (status = 'Pending' OR status = 'Approved')");
        if (mysqli_num_rows($final_check) > 0) {
            header("Location: index.php?error=Someone just reserved this unit seconds ago. Please pick another one.");
            exit();
        }
    }

    // 2. Passwords matching validation
    if ($password !== $confirm) {
        header("Location: register.php?error=Passwords do not match!&unit_id=$unit_id");
        exit();
    }

    // 3. Email uniqueness check
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        header("Location: register.php?error=Email is already taken!&unit_id=$unit_id");
        exit();
    }

    // 4. File Upload Configuration
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    // Helper function para malinis ang pag-upload ng multiple files
    function processUpload($file_array, $prefix, $dir) {
        if (!empty($file_array['name'])) {
            $new_name = time() . "_" . $prefix . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_array['name']);
            if (move_uploaded_file($file_array['tmp_name'], $dir . $new_name)) {
                return $new_name;
            }
        }
        return "";
    }

    // I-process ang lahat ng documents
    $profile_pic = processUpload($_FILES['profile_pic'], "prof", $upload_dir) ?: "default_profile.png";
    $valid_id    = processUpload($_FILES['valid_id'], "id", $upload_dir);
    $police      = processUpload($_FILES['police_clearance'], "police", $upload_dir);
    $brgy        = processUpload($_FILES['brgy_clearance'], "brgy", $upload_dir);
    $nbi         = processUpload($_FILES['nbi_clearance'], "nbi", $upload_dir);

    // 5. Database Insertion
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Query kasama ang mga bagong columns
    $query_user = "INSERT INTO users (
                        full_name, email, contact, emergency_contact, password, 
                        role, assigned_unit_id, profile_pic, valid_id, 
                        police_clearance, brgy_clearance, nbi_clearance, 
                        rules_accepted, is_archived
                    ) VALUES (
                        '$full_name', '$email', '$contact', '$emergency', '$hashed_password', 
                        'tenant', '$unit_id', '$profile_pic', '$valid_id', 
                        '$police', '$brgy', '$nbi', 
                        0, 0
                    )";

    if (mysqli_query($conn, $query_user)) {
        $new_user_id = mysqli_insert_id($conn);
        
        // Mag-create ng entry sa rent_requests para ma-review ni Admin
        if (!empty($unit_id)) {
            $query_request = "INSERT INTO rent_requests (user_id, unit_id, status, request_date) 
                              VALUES ('$new_user_id', '$unit_id', 'Pending', NOW())";
            mysqli_query($conn, $query_request);
        }

        header("Location: login.php?email=$email&unit_id=$unit_id&msg=Application submitted! Please wait for admin approval of your requirements.");
        exit();
    } else {
        header("Location: register.php?error=DB Error: " . mysqli_error($conn) . "&unit_id=$unit_id");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>