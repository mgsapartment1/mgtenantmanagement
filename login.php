<?php
include('db.php'); 
session_start();

$error = "";

// 1. Kunin ang values mula sa URL (Auto-fill support)
$email_value = isset($_GET['email']) ? $_GET['email'] : '';
$unit_id_value = isset($_GET['unit_id']) ? $_GET['unit_id'] : '';
if(isset($_GET['error'])) { $error = $_GET['error']; }

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 2. Hanapin ang user
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // 3. Verify Password (Hashed or Master Password)
        if (password_verify($password, $user['password']) || $password == 'admin123') {
            
            $user_id = $user['id'];

            // --- APPROVAL LOGIC ---
            if ($user['role'] == 'tenant') {
                $status_check = mysqli_query($conn, "SELECT status FROM rent_requests WHERE user_id = '$user_id' LIMIT 1");
                $req_data = mysqli_fetch_assoc($status_check);

                if (!$req_data) {
                    $error = "Account found, but no rent request submitted.";
                } elseif ($req_data['status'] !== 'Approved') {
                    $error = "Access Denied: Your account is pending for admin approval.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: tenant_dashboard.php");
                    exit();
                }
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                header("Location: admin_dashboard.php");
                exit();
            }

        } else {
            $error = "Invalid password! Please try again.";
        }
    } else {
        $error = "No account found with that email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MG's Rental</title>

    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="uploads/logo.jpg">
    <meta name="theme-color" content="#008080">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background-color: #f4f7f6; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }
        .login-card { 
            max-width: 380px; 
            width: 90%; 
            margin: auto; 
            border-radius: 20px; 
            border: none; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
        }
        .card-body { padding: 2rem !important; }
        .btn-teal { 
            background-color: #008080; 
            border: none; 
            color: white; 
            font-weight: bold;
            padding: 10px;
            transition: 0.3s;
        }
        .btn-teal:hover { background-color: #006666; color: white; transform: translateY(-2px); }
        .form-control { border-radius: 8px; padding: 10px; }
        
        /* Input Group Adjustments */
        .input-group .form-control { border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
        .input-group .btn { border-top-right-radius: 8px !important; border-bottom-right-radius: 8px !important; border: 1px solid #dee2e6; border-left: none; background: white; color: #6c757d; }
        
        .form-label { font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 5px; }
        hr { margin: 1.5rem 0 !important; opacity: 0.1; }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card shadow-lg">
        <div class="card-body">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-1">Sign In</h2>
                <p class="text-muted small">Access your MG Tenant account</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-warning py-2 small text-center border-0 mb-3" style="border-radius: 8px;">
                    <i class="fa fa-exclamation-triangle me-1"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unit_id_value); ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary text-uppercase">Email Address</label>
                    <input type="email" name="email" class="form-control shadow-sm" 
                           value="<?php echo htmlspecialchars($email_value); ?>" 
                           placeholder="yourname@email.com" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary text-uppercase">Password</label>
                    <div class="input-group shadow-sm">
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter password" required>
                        <button class="btn" type="button" onclick="toggleLoginPass()">
                            <i class="fa fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-teal w-100 shadow-sm">
                    LOGIN <i class="fa fa-sign-in-alt ms-2"></i>
                </button>
            </form>

            <div class="text-center mt-3">
                <p class="small text-muted mb-0">
                    Don't have an account? 
                    <a href="register.php?unit_id=<?php echo urlencode($unit_id_value); ?>" class="text-decoration-none fw-bold" style="color: #008080;">
                        Register Now
                    </a>
                </p>
                <hr>
                <a href="index.php" class="small text-secondary text-decoration-none">
                    <i class="fa fa-arrow-left me-1"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLoginPass() {
    const passInput = document.getElementById('loginPassword');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passInput.type === "password") {
        passInput.type = "text";
        eyeIcon.classList.remove("fa-eye");
        eyeIcon.classList.add("fa-eye-slash");
    } else {
        passInput.type = "password";
        eyeIcon.classList.remove("fa-eye-slash");
        eyeIcon.classList.add("fa-eye");
    }
}
</script>

</body>
</html>