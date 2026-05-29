<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$current_theme = $_SESSION['theme'] ?? 'light';

// 1. Check for Unpaid Bills (Blocking Logic)
$bill_q = mysqli_query($conn, "SELECT COUNT(*) as unpaid FROM bills WHERE user_id = '$user_id' AND status = 'Unpaid'");
$unpaid_data = mysqli_fetch_assoc($bill_q);
$unpaid_count = $unpaid_data['unpaid'] ?? 0;

// 2. Kunin ang unit_id
$u_q = mysqli_query($conn, "SELECT assigned_unit_id FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($u_q);
$unit_id = $user_data['assigned_unit_id'];

$success = false;
if (isset($_POST['submit_moveout']) && $unpaid_count == 0) {
    $pref_date = $_POST['preferred_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    $sql = "INSERT INTO lease_requests (user_id, unit_id, request_type, preferred_date, reason, status) 
            VALUES ('$user_id', '$unit_id', 'Move-out', '$pref_date', '$reason', 'Pending')";

    if (mysqli_query($conn, $sql)) { 
        $success = true; 
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Move-out Request | MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --danger-accent: #dc3545; --bg: #f8fafc; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b; --input-bg: #f1f5f9; }
        
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8; /* Matingkad na gray para sa dark mode */
            --input-bg: #334155; 
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .request-card { width: 100%; max-width: 450px; background: var(--card-bg); padding: 40px; border-radius: 28px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.05); }
        .icon-circle { width: 60px; height: 60px; background: rgba(220, 53, 69, 0.1); color: var(--danger-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px; }
        
        /* Fixed visibility para sa subtitles */
        .text-muted { color: var(--text-muted) !important; opacity: 1 !important; }

        .form-control { 
            background-color: var(--input-bg); 
            border: 2px solid transparent; 
            border-radius: 12px; 
            padding: 12px; 
            color: var(--text-main) !important; 
            font-weight: 600; 
        }
        .form-control:focus { 
            background: var(--card-bg); 
            border-color: var(--danger-accent); 
            box-shadow: none; 
            color: var(--text-main) !important; 
        }

        /* Calendar Icon visibility fix */
        [data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        .btn-submit { background: var(--danger-accent); color: white; border: none; border-radius: 12px; padding: 14px; font-weight: 800; width: 100%; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover:not(:disabled) { opacity: 0.9; transform: translateY(-2px); color: white; }
        .btn-submit:disabled { background: #475569; cursor: not-allowed; color: #94a3b8; }

        .alert-lock { background: rgba(220, 53, 69, 0.1); color: #ff8787; border-radius: 15px; padding: 15px; font-size: 0.85rem; border: 1px solid rgba(220, 53, 69, 0.2); margin-bottom: 20px; text-align: center; }
        
        .btn-back { color: var(--text-muted); text-decoration: none; display: block; text-align: center; margin-top: 15px; font-weight: 600; font-size: 0.9rem; }
        .btn-back:hover { color: var(--text-main); }
    </style>
</head>
<body>

    <div class="request-card shadow-lg">
        <div class="icon-circle"><i class="fa fa-door-open"></i></div>
        <h4 class="fw-800 text-center mb-2">Move-out Request</h4>
        <p class="text-muted text-center small mb-4">Submit your intent to vacate the unit.</p>

        <?php if($unpaid_count > 0): ?>
            <div class="alert-lock fw-600">
                <i class="fa fa-exclamation-circle d-block mb-2 fs-4"></i>
                Request Blocked: You have <b><?php echo $unpaid_count; ?></b> unpaid bill(s).<br>
                Please settle your balance first.
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-700">Intended Move-out Date</label>
                <input type="date" name="preferred_date" class="form-control" required 
                       min="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                       <?php echo ($unpaid_count > 0) ? 'disabled' : ''; ?>>
                <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">Minimum 30-day notice period required.</small>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-700">Reason for Leaving</label>
                <textarea name="reason" class="form-control" rows="3" required 
                          placeholder="Please state your reason..."
                          <?php echo ($unpaid_count > 0) ? 'disabled' : ''; ?>></textarea>
            </div>

            <button type="submit" name="submit_moveout" class="btn-submit" <?php echo ($unpaid_count > 0) ? 'disabled' : ''; ?>>
                SUBMIT MOVE-OUT NOTICE
            </button>
            <a href="tenant_dashboard.php" class="btn-back">Cancel and Return</a>
        </form>
    </div>

    <?php if ($success): ?>
    <script>
        Swal.fire({
            title: 'Notice Submitted',
            text: 'Your move-out request has been sent for admin review.',
            icon: 'success',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Understood'
        }).then(() => {
            window.location.href = 'tenant_dashboard.php';
        });
    </script>
    <?php endif; ?>

</body>
</html>