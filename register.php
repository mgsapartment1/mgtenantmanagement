<?php 
include('db.php'); 
session_start();
$unit_id = isset($_GET['unit_id']) ? mysqli_real_escape_string($conn, $_GET['unit_id']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

if (!empty($unit_id)) {
    $check_availability = mysqli_query($conn, "SELECT id FROM rent_requests 
                                              WHERE unit_id = '$unit_id' 
                                              AND (status = 'Pending' OR status = 'Approved')");
    
    if (mysqli_num_rows($check_availability) > 0) {
        header("Location: index.php?error=Sorry, this unit was just reserved by someone else.");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - MG'S RENTAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --teal-color: #008080; --dark-teal: #006666; }
        body { background-color: #f4f7f6; min-height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', sans-serif; }
        
        .register-card { 
            max-width: 700px; 
            width: 95%; 
            margin: 40px auto; 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            overflow: hidden; 
        }
        
        .card-header-teal { background-color: var(--teal-color); color: white; padding: 30px; text-align: center; }
        
        .btn-teal { 
            background-color: var(--teal-color); 
            color: white; 
            border: none; 
            padding: 14px; 
            font-weight: bold; 
            border-radius: 12px; 
            transition: 0.3s; 
            width: 100%;
        }
        .btn-teal:hover { background-color: var(--dark-teal); transform: translateY(-2px); }
        
        .form-label { font-weight: 600; color: #444; font-size: 0.85rem; }
        .form-control { border-radius: 10px; padding: 10px; border: 1px solid #ddd; font-size: 0.9rem; }
        
        /* Fix for input group borders */
        .input-group .form-control { border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
        .input-group .btn { border-top-right-radius: 10px !important; border-bottom-right-radius: 10px !important; border: 1px solid #ddd; border-left: none; }

        .section-title { 
            font-size: 0.85rem; 
            text-transform: uppercase; 
            color: var(--teal-color); 
            letter-spacing: 1px; 
            font-weight: 800; 
            margin-top: 25px;
            margin-bottom: 15px; 
            display: block; 
            border-bottom: 2px solid #e0f2f1; 
            padding-bottom: 5px; 
        }

        .input-group-text { border-radius: 10px 0 0 10px; border: 1px solid #ddd; }
        .rounded-end-input { border-radius: 0 10px 10px 0 !important; }
        
        .requirement-note { font-size: 0.75rem; color: #666; font-style: italic; display: block; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="register-card card shadow-lg">
    <div class="card-header-teal">
        <h3 class="fw-bold mb-0">Join MG's Tenant</h3>
        <p class="small mb-0 opacity-75">Submit your details and requirements for approval.</p>
    </div>

    <div class="card-body p-4 p-md-5">
        <?php if($error): ?>
            <div class="alert alert-danger py-2 small text-center rounded-pill">
                <i class="fa fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="register_process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="unit_id" value="<?php echo $unit_id; ?>">

            <span class="section-title">Personal Information</span>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa fa-user text-muted"></i></span>
                    <input type="text" name="full_name" class="form-control rounded-end-input" placeholder="Juan Dela Cruz" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="juan@example.com" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" placeholder="09123456789" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('password', 'eye1')">
                            <i class="fa fa-eye" id="eye1"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('confirm_password', 'eye2')">
                            <i class="fa fa-eye" id="eye2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-danger">Emergency Contact (Name & No.)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-danger"><i class="fa fa-phone-alt"></i></span>
                    <input type="text" name="emergency_contact" class="form-control rounded-end-input" placeholder="Maria Dela Cruz - 0912..." required>
                </div>
            </div>

            <span class="section-title">Required Documents</span>
            <small class="requirement-note text-muted">Please upload clear photos or PDF files (Max 5MB each).</small>
            
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Profile Picture</label>
                    <input type="file" name="profile_pic" class="form-control" accept="image/*" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valid ID Card</label>
                    <input type="file" name="valid_id" class="form-control" accept="image/*,.pdf" required>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Police Clearance</label>
                    <input type="file" name="police_clearance" class="form-control" accept="image/*,.pdf" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brgy. Clearance</label>
                    <input type="file" name="brgy_clearance" class="form-control" accept="image/*,.pdf" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NBI Clearance</label>
                    <input type="file" name="nbi_clearance" class="form-control" accept="image/*,.pdf" required>
                </div>
            </div>
            
            <button type="submit" name="register" class="btn btn-teal shadow-sm mb-3">
                SUBMIT APPLICATION
            </button>
        </form>

        <div class="text-center mt-3">
            <p class="small text-muted">Already have an account? 
                <a href="login.php?unit_id=<?php echo $unit_id; ?>" class="fw-bold text-decoration-none" style="color: var(--teal-color);">Login here</a>
            </p>
            <hr>
            <a href="index.php" class="small text-muted text-decoration-none"><i class="fa fa-arrow-left me-1"></i> Back to Home</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

</body>
</html>