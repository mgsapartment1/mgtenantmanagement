<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$current_theme = $_SESSION['theme'] ?? 'light';

// 1. Kunin ang unit_id at current lease end date
$u_q = mysqli_query($conn, "SELECT assigned_unit_id, contract_end_date FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($u_q);
$unit_id = $user_data['assigned_unit_id'];

// 2. I-check kung may existing PENDING renewal request na para hindi mag-duplicate
$check_q = mysqli_query($conn, "SELECT id FROM lease_requests WHERE user_id = '$user_id' AND request_type = 'Renew' AND status = 'Pending'");
$has_pending = mysqli_num_rows($check_q) > 0;

// Check if may date sa DB, if wala use today's date as starting point
$current_end = (!empty($user_data['contract_end_date'])) ? $user_data['contract_end_date'] : date('Y-m-d');

$success = false;
if (isset($_POST['submit_renew']) && !$has_pending) {
    $duration = $_POST['duration'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] . " (Requested Duration: $duration Months)");
    
    // PHP Computation para sa database insertion
    $pref_date = date('Y-m-d', strtotime("+$duration months", strtotime($current_end)));

    $sql = "INSERT INTO lease_requests (user_id, unit_id, request_type, preferred_date, reason, status) 
            VALUES ('$user_id', '$unit_id', 'Renew', '$pref_date', '$reason', 'Pending')";

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
    <title>Renew Lease | MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --teal-accent: #008080; --bg: #f8fafc; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b; --input-bg: #f1f5f9; }
        
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8; 
            --input-bg: #334155; 
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .request-card { width: 100%; max-width: 450px; background: var(--card-bg); padding: 40px; border-radius: 28px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.05); }
        .icon-circle { width: 60px; height: 60px; background: rgba(0, 128, 128, 0.1); color: var(--teal-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px; }
        
        .text-muted { color: var(--text-muted) !important; opacity: 1 !important; }

        .form-control, .form-select { 
            background-color: var(--input-bg); 
            border: 2px solid transparent; 
            border-radius: 12px; 
            padding: 12px; 
            color: var(--text-main) !important; 
            font-weight: 600; 
        }
        .form-control:focus, .form-select:focus { 
            background: var(--card-bg); 
            border-color: var(--teal-accent); 
            box-shadow: none; 
            color: var(--text-main) !important; 
        }

        .btn-submit { background: var(--teal-accent); color: white; border: none; border-radius: 12px; padding: 14px; font-weight: 800; width: 100%; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover:not(:disabled) { opacity: 0.9; transform: translateY(-2px); color: white; }
        .btn-submit:disabled { background: #475569; cursor: not-allowed; opacity: 0.6; }

        .alert-pending { background: rgba(0, 128, 128, 0.1); color: var(--teal-accent); border-radius: 15px; padding: 15px; font-size: 0.85rem; border: 1px solid rgba(0, 128, 128, 0.2); margin-bottom: 20px; text-align: center; }
        
        .btn-back { color: var(--text-muted); text-decoration: none; display: block; text-align: center; margin-top: 15px; font-weight: 600; font-size: 0.9rem; }
        .btn-back:hover { color: var(--text-main); }
    </style>
</head>
<body>

    <div class="request-card shadow-lg">
        <div class="icon-circle"><i class="fa fa-calendar-check"></i></div>
        <h4 class="fw-800 text-center mb-2">Lease Renewal</h4>
        <p class="text-muted text-center small mb-4">Extend your stay at MG's Apartment.</p>

        <?php if($has_pending): ?>
            <div class="alert-pending fw-600">
                <i class="fa fa-clock d-block mb-2 fs-4"></i>
                You already have a pending renewal request.<br>
                Please wait for admin approval.
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-700">Renewal Duration</label>
                <select name="duration" id="duration" class="form-select" onchange="calculateNewDate()" <?php echo ($has_pending) ? 'disabled' : ''; ?>>
                    <option value="6">6 Months</option>
                    <option value="12" selected>1 Year (12 Months)</option>
                    <option value="24">2 Years (24 Months)</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-700">New End Date (Target)</label>
                <input type="text" id="end_date_preview" class="form-control" readonly style="opacity: 0.8; cursor: not-allowed;">
                <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">Based on your current contract end date.</small>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-700">Notes to Admin (Optional)</label>
                <textarea name="reason" class="form-control" rows="2" placeholder="e.g. I would like to request..." <?php echo ($has_pending) ? 'disabled' : ''; ?>></textarea>
            </div>

            <button type="submit" name="submit_renew" class="btn-submit" <?php echo ($has_pending) ? 'disabled' : ''; ?>>
                SUBMIT RENEWAL
            </button>
            <a href="tenant_dashboard.php" class="btn-back">Cancel and Return</a>
        </form>
    </div>

    <script>
        function calculateNewDate() {
            let baseDateStr = '<?php echo $current_end; ?>';
            let baseDate = baseDateStr ? new Date(baseDateStr) : new Date();

            if (isNaN(baseDate.getTime())) {
                baseDate = new Date(); 
            }

            let durationMonths = parseInt(document.getElementById('duration').value);
            let targetDate = new Date(baseDate);
            targetDate.setMonth(targetDate.getMonth() + durationMonths);

            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('end_date_preview').value = targetDate.toLocaleDateString('en-US', options);
        }

        window.onload = calculateNewDate;
    </script>

    <?php if ($success): ?>
    <script>
        Swal.fire({
            title: 'Request Sent!',
            text: 'Your renewal request is now pending for admin approval.',
            icon: 'success',
            confirmButtonColor: '#008080',
            confirmButtonText: 'Understood'
        }).then(() => {
            window.location.href = 'tenant_dashboard.php';
        });
    </script>
    <?php endif; ?>

</body>
</html>