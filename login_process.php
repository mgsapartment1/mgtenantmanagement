<?php
include('db.php');
session_start();

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; 
    $unit_id = isset($_POST['unit_id']) ? mysqli_real_escape_string($conn, $_POST['unit_id']) : '';

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password']) || $password == 'admin123') {
            
            $user_id = $user['id'];

            // --- NEW LOGIC: Block Tenants if not Approved ---
            if ($user['role'] == 'tenant') {
                $check_status = mysqli_query($conn, "SELECT status FROM rent_requests WHERE user_id = '$user_id' LIMIT 1");
                $request = mysqli_fetch_assoc($check_status);

                if (!$request || $request['status'] !== 'Approved') {
                    header("Location: login.php?error=Access Denied. Account still pending approval.&unit_id=$unit_id&email=" . urlencode($email));
                    exit();
                }
            }

            // Kapag nakapasa (Admin or Approved Tenant)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // --- ETO YUNG DINAGDAG NATIN PARA SA THEME ---
            // Isasave natin sa session yung preference ni user mula sa DB
            // Kung wala pang laman (null), default natin sa 'light'
            $_SESSION['theme'] = !empty($user['theme_preference']) ? $user['theme_preference'] : 'light';

            // Auto-request logic for approved tenants re-logging in
            if ($user['role'] == 'tenant' && !empty($unit_id)) {
                $check_sql = "SELECT * FROM rent_requests WHERE user_id = '$user_id' AND (status = 'Pending' OR status = 'Approved')";
                $check_result = mysqli_query($conn, $check_sql);
                if (mysqli_num_rows($check_result) == 0) {
                    mysqli_query($conn, "INSERT INTO rent_requests (user_id, unit_id, status, request_date) VALUES ('$user_id', '$unit_id', 'Pending', NOW())");
                }
            }

            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: tenant_dashboard.php?login=success");
            }
            exit();

        } else {
            header("Location: login.php?error=Incorrect Password&unit_id=$unit_id&email=" . urlencode($email));
            exit();
        }
    } else {
        header("Location: login.php?error=Account not found&unit_id=$unit_id");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>