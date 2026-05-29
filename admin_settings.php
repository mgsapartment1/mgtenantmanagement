<?php
include('db.php');
session_start();

// Security: Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); 
    exit(); 
}

// Kunin ang kasalukuyang rules mula sa database
$res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'house_rules'");
$rules_data = mysqli_fetch_assoc($res);
$rules = $rules_data['setting_value'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
    
    <style>
        :root { --teal-primary: #008080; }
        body { background: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; }
        .ck-editor__editable { min-height: 450px; } /* Mas mataas na editor box */
        .card { border-radius: 20px; border: none; }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <a href="admin_dashboard.php" class="text-decoration-none text-muted mb-3 d-inline-block fw-bold">
        <i class="fa fa-arrow-left me-1"></i> Back to Dashboard
    </a>

    <div class="card p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa fa-file-contract me-2 text-primary"></i>Lease Contract Template</h4>
                <p class="text-muted small mb-0">I-edit dito ang terms, conditions, at house rules na pipirmahan ng mga tenants.</p>
            </div>
            
            <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="alert alert-success py-2 px-3 mb-0 small rounded-pill shadow-sm">
                    <i class="fa fa-check-circle me-1"></i> Rules updated and published!
                </div>
            <?php endif; ?>
        </div>

        <form action="save_rules_action.php" method="POST">
            <div class="mb-3">
                <textarea name="house_rules" id="editor"><?php echo $rules; ?></textarea>
            </div>
            
            <div class="d-flex align-items-center justify-content-between mt-4">
                <div class="text-muted small">
                    <i class="fa fa-info-circle me-1"></i> Lahat ng pagbabago ay automatic na mag-a-update sa <b>Tenant Dashboard</b> at <b>PDF Contract</b>.
                </div>
                <button type="submit" name="update_rules" class="btn btn-success px-5 fw-bold rounded-pill py-3 shadow">
                    <i class="fa fa-save me-2"></i> UPDATE AND PUBLISH RULES
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    ClassicEditor
        .create(document.querySelector('#editor'), {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo' ]
        })
        .catch(error => {
            console.error(error);
        });
</script>

</body>
</html>