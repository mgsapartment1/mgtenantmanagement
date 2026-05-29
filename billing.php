<?php
// 1. IMPORT PHPMAILER CLASSES AT THE VERY TOP
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include('db.php');
$target_date = date('Y-m-d', strtotime('+5 days'));

// Kinukuha ang mga unpaid bills na 5 days away na ang due date
// at hindi pa na-notify (notified_5days = 0)
$auto_query = "SELECT u.full_name, u.email, SUM(b.amount) as total_amount, b.due_date, 
                      GROUP_CONCAT(b.bill_type SEPARATOR ', ') as types, GROUP_CONCAT(b.id) as ids 
               FROM bills b 
               JOIN users u ON b.user_id = u.id 
               WHERE b.status = 'Unpaid' 
               AND b.due_date = '$target_date'
               AND (b.notified_5days = 0 OR b.notified_5days IS NULL)
               GROUP BY u.id";

$auto_res = mysqli_query($conn, $auto_query);

if ($auto_res && mysqli_num_rows($auto_res) > 0) {
    while ($row = mysqli_fetch_assoc($auto_res)) {
        $mail = new PHPMailer(true);
        try {
            // SMTP Settings (gamit ang existing configuration mo)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mgsapartment1@gmail.com';
            $mail->Password   = 'uicarhkqncphsitx'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('mgsapartment1@gmail.com', 'MGs Apartment Reminder');
            $mail->addAddress($row['email'], $row['full_name']);
            $mail->isHTML(true);
            $mail->Subject = "Reminder: Payment Due in 5 Days";
            $mail->Body    = "
            <div style='font-family: Arial; padding: 20px;'>
                <p>Dear <b>" . $row['full_name'] . "</b>,</p>
                <p>This is a friendly reminder from MGs Apartment. Your payment for <b>" . $row['types'] . "</b> 
                amounting to <b>₱" . number_format($row['total_amount'], 2) . "</b> 
                is due on <b>" . $row['due_date'] . "</b>.</p>
                <p>Please settle your account at the earliest convenience.</p>
                <p>Thank you!</p>
            </div>";
            
            if($mail->send()) {
                // I-mark bilang notified para hindi na mag-email ulit
                $bill_ids = $row['ids'];
                mysqli_query($conn, "UPDATE bills SET notified_5days = 1 WHERE id IN ($bill_ids)");
            }
        } catch (Exception $e) {
            // Error handling (optional)
        }
    }
}
session_start();

// 2. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/** * --- CORRECT AUTO-PENALTY TRIGGER --- */
$current_date = date('Y-m-d');
$penalty_val = 100.00; 

mysqli_query($conn, "UPDATE bills SET penalty = '$penalty_val' 
                    WHERE status = 'Unpaid' 
                    AND due_date < '$current_date' 
                    AND (penalty IS NULL OR penalty = 0)");


// --- PHPMAILER BILLING EMAIL REMINDER LOGIC ---
if (isset($_GET['remind_id'])) {
    $bill_ids = mysqli_real_escape_string($conn, $_GET['remind_id']);
    $t_name = mysqli_real_escape_string($conn, $_GET['name']);
    $t_email = mysqli_real_escape_string($conn, $_GET['email']);
    $b_amount = mysqli_real_escape_string($conn, $_GET['amount']);
    $b_due = mysqli_real_escape_string($conn, $_GET['due']);
    $b_types = mysqli_real_escape_string($conn, $_GET['types']);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mgsapartment1@gmail.com';       
        $mail->Password   = 'uicarhkqncphsitx'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('mgsapartment1@gmail.com', 'MGs Apartment Notification');
        $mail->addAddress($t_email, $t_name);

        $mail->isHTML(true);
        $mail->Subject = "Statement of Account - MGs Apartment";

        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; background-color: #f8fafc; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                <div style='background: #008080; color: white; padding: 25px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 20px; letter-spacing: 0.5px;'>MGs Apartment</h2>
                    <p style='margin: 5px 0 0 0; opacity: 0.8; font-size: 14px;'>Official Billing Statement</p>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 16px;'>Dear <b>$t_name</b>,</p>
                    <p>Please find the summary of your outstanding account balance below:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin: 25px 0;'>
                        <tr style='background: #f8fafc;'>
                            <td style='padding: 12px; border: 1px solid #edf2f7; color: #4a5568;'><b>Particulars:</b></td>
                            <td style='padding: 12px; border: 1px solid #edf2f7; font-weight: 600;'>$b_types</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px; border: 1px solid #edf2f7; color: #4a5568;'><b>Total Amount Due:</b></td>
                            <td style='padding: 12px; border: 1px solid #edf2f7; color: #e53e3e; font-weight: bold; font-size: 16px;'>₱" . number_format($b_amount, 2) . "</td>
                        </tr>
                        <tr style='background: #f8fafc;'>
                            <td style='padding: 12px; border: 1px solid #edf2f7; color: #4a5568;'><b>Due Date:</b></td>
                            <td style='padding: 12px; border: 1px solid #edf2f7; font-weight: 600; color: #4a5568;'>$b_due</td>
                        </tr>
                    </table>
                    
                    <p style='color: #4a5568; font-size: 14px;'>To view the full details and settle your dues, kindly access the online portal using your account credentials.</p>
                    
                    <div style='text-align: center; margin: 35px 0 20px 0;'>
                        <a href='http://localhost/onlinetenantmanagement/login.php' style='background: #008080; color: white; padding: 14px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;'>Access Account Portal</a>
                    </div>
                    
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-top: 40px;'>
                    <p style='font-size: 11px; color: #a0aec0; text-align: center; margin: 0;'>This is a system-generated notification. Please do not reply directly to this message.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        echo "<script>alert('Payment reminder email successfully sent to $t_name!'); window.location.href='billing.php';</script>";
        exit();
    } catch (Exception $e) {
        echo "<script>alert('Email sending failed. Error: " . addslashes($mail->ErrorInfo) . "'); window.location.href='billing.php';</script>";
        exit();
    }
}


// --- FILTER LOGIC ---
$filter_date = isset($_GET['filter_date']) ? mysqli_real_escape_string($conn, $_GET['filter_date']) : '';
$filter_month = isset($_GET['filter_month']) ? mysqli_real_escape_string($conn, $_GET['filter_month']) : '';

$where_clauses = [];
if ($filter_date) {
    $where_clauses[] = "DATE(b.due_date) = '$filter_date'";
}
if ($filter_month) {
    $where_clauses[] = "DATE_FORMAT(b.due_date, '%Y-%m') = '$filter_month'";
}
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// 3. FETCH BILLING DATA (Strict Order Filtering to push 'Pending Approval' first)
// FIXED: Idinamay natin ang pinakabagong individual bill id (b.id) para maipasa natin nang maayos sa single verification process
$query = "SELECT 
            b.id as single_bill_id,
            b.status,
            u.id as user_id,
            u.full_name, 
            u.email,
            u.contact, 
            un.unit_no,
            GROUP_CONCAT(b.id) as bill_ids,
            GROUP_CONCAT(b.bill_type SEPARATOR ', ') as combined_types, 
            SUM(b.amount) as total_base_amount, 
            SUM(IFNULL(b.penalty, 0)) as total_penalty,
            MIN(b.due_date) as earliest_due,
            b.reference_no,
            b.proof_of_payment
        FROM bills b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN units un ON u.assigned_unit_id = un.id
        $where_sql
        GROUP BY u.id, b.status, b.reference_no, b.id
        ORDER BY FIELD(b.status, 'Pending Approval', 'Unpaid', 'Paid') ASC, earliest_due DESC";
$result = mysqli_query($conn, $query);

// 4. GET STATS
$unpaid_res = mysqli_query($conn, "SELECT SUM(amount + IFNULL(penalty,0)) as s_amount FROM bills WHERE status = 'Unpaid'");
$unpaid_data = mysqli_fetch_assoc($unpaid_res);
$total_unpaid = $unpaid_data['s_amount'] ?? 0;

$cat_query = "SELECT bill_type, SUM(amount + IFNULL(penalty,0)) as total_cat 
            FROM bills WHERE status = 'Paid' GROUP BY bill_type";
$cat_result = mysqli_query($conn, $cat_query);

// Global Notif Counts for badge notification alignment
$res_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM rent_requests WHERE status = 'Pending'");
$pend_count = mysqli_fetch_assoc($res_pend)['total'] ?? 0;

$res_lease = mysqli_query($conn, "SELECT COUNT(*) as total FROM lease_requests WHERE status = 'Pending'");
$lease_pend_count = mysqli_fetch_assoc($res_lease)['total'] ?? 0;

$res_pay_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Pending Approval' OR (status = 'Pending' AND proof_of_payment IS NOT NULL)");
$pay_pend_count = mysqli_fetch_assoc($res_pay_pend)['total'] ?? 0;

$current_theme = $_SESSION['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Billing Management - MG's Apartment</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        :root { 
            --sidebar-bg: #111827; 
            --teal-accent: #008080; 
            --bg: #f8fafc; 
            --card-bg: #ffffff; 
            --text-main: #1e293b; 
            --text-muted: #64748b; 
            --border-color: #f1f5f9;
            --input-bg: #f8fafc;
        }
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #111827;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); transition: 0.3s ease; margin: 0; overflow-x: hidden; }
        
        h1, h2, h3, h4, h5, h6, .text-main, .fw-800 { color: var(--text-main) !important; }
        .text-muted { color: var(--text-muted) !important; }

        /* SIDEBAR PANEL DRAWER COMPONENTS */
        .sidebar { height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; width: 260px; display: flex; flex-direction: column; z-index: 1200; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); top: 0; left: 0; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header h5 { font-weight: 800; letter-spacing: 0.5px; color: white !important; margin: 0; font-size: 1.1rem; text-transform: uppercase; }
        .sidebar-header small { color: var(--teal-accent); font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-top: 2px; }

        .sidebar-content { flex-grow: 1; padding: 20px 15px; overflow-y: auto; }
        .sidebar a { color: #9ca3af; text-decoration: none; padding: 12px 15px; display: flex; align-items: center; transition: 0.2s; font-size: 0.9rem; font-weight: 600; border-radius: 12px; margin-bottom: 4px; }
        .sidebar a i { width: 25px; font-size: 1.1rem; margin-right: 10px; }
        .sidebar a:hover, .sidebar a.nav-link:not(.collapsed) { background: rgba(0, 128, 128, 0.15); color: #2dd4bf; }
        .sidebar a.active { background: var(--teal-accent) !important; color: white !important; }
        
        .collapse-inner { background: rgba(255,255,255,0.02); border-radius: 12px; margin: 5px 0; padding: 5px 0; border: 1px solid rgba(255,255,255,0.03); }
        .collapse-inner a { padding-left: 50px !important; font-size: 0.85rem !important; }

        .logout-link { padding: 20px 15px; border-top: 1px solid rgba(255,255,255,0.05); }
        .logout-link a { color: #f87171 !important; }

        /* VIEWPORT MAIN BOX CONTENT */
        .main-content { margin-left: 260px; padding: 30px; min-height: 100vh; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .header-title { color: var(--text-main); font-weight: 800; margin-bottom: 0; display: flex; align-items: center; }
        .header-title i { background: rgba(0, 128, 128, 0.1); color: var(--teal-accent); width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-right: 15px; }

        #theme-toggle { width: 45px; height: 45px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--teal-accent); box-shadow: 0 4px 6px rgba(0,0,0,0.02); cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }

        /* STATS CARDS GRID SLIDER */
        .stats-row { display: flex; overflow-x: auto; gap: 14px; padding-bottom: 12px; scrollbar-width: none; }
        .stats-row::-webkit-scrollbar { display: none; }
        .stat-card { min-width: 220px; flex: 1; background: var(--card-bg); border-radius: 20px; padding: 22px; border-left: 5px solid var(--teal-accent); border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.01); }

        /* PREMIUM APP CARD RENDER INTERFACES */
        .glass-card { background: var(--card-bg); border-radius: 24px; border: 1px solid var(--border-color); box-shadow: 0 4px 12px rgba(0,0,0,0.02); overflow: hidden; }
        
        .btn-teal { background: var(--teal-accent) !important; color: white !important; border-radius: 12px; font-weight: 700; padding: 10px 22px; border: none; transition: 0.23s ease-in-out; }
        .btn-teal:hover { background: #006666 !important; transform: translateY(-2px); }

        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; margin-bottom: 0; }
        .table thead th { background-color: var(--teal-accent) !important; color: white !important; font-size: 0.75rem; text-transform: uppercase; padding: 16px 20px; border: none; font-weight: 700; letter-spacing: 0.5px; }
        .table tbody td { padding: 18px 20px; border-bottom: 1px solid var(--border-color) !important; color: var(--text-main) !important; vertical-align: middle; }
        .table tbody tr:last-child td { border-bottom: none !important; }

        .status-pill { font-size: 0.65rem; font-weight: 800; padding: 6px 14px; border-radius: 10px; text-transform: uppercase; display: inline-block; }
        .bg-unpaid { background: #fee2e2 !important; color: #ef4444 !important; }
        .bg-paid { background: #dcfce7 !important; color: #166534 !important; }
        .bg-pending { background: #fef9c3 !important; color: #854d0e !important; }

        /* MODAL CANVAS FRAME STRUCTURES */
        .modal-content { background-color: var(--card-bg) !important; border-radius: 24px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .modal-header { border-bottom: 1px solid var(--border-color) !important; padding: 22px 26px; }

        #mobile-toggle { display: none; background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 45px; height: 45px; border-radius: 12px; align-items: center; justify-content: center; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            #mobile-toggle { display: flex; }
            .desktop-table { display: none; }
            .mobile-bill-card { display: block; background: var(--card-bg); border-radius: 20px; padding: 18px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid var(--border-color); }
            .card-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border-color); }
        }
        @media (min-width: 992px) { .mobile-bill-card { display: none; } }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebarMenuDrawer()"></div>

<div class="sidebar shadow-lg" id="sidebar">
    <div class="sidebar-header">
        <h5>MG'S APARTMENT</h5>
        <small>Property Management</small>
    </div>

    <div class="sidebar-content" id="sidebarMenu">
        <a href="admin_dashboard.php"><i class="fa fa-chart-pie"></i> Dashboard</a>
        <a href="admin_requests.php"><i class="fa fa-user-plus"></i> New Requests <?php if($pend_count > 0): ?><span class="badge rounded-pill bg-danger ms-auto"><?php echo $pend_count; ?></span><?php endif; ?></a>
        <a href="admin_lease_requests.php"><i class="fa fa-file-contract"></i> Lease Actions <?php if($lease_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $lease_pend_count; ?></span><?php endif; ?></a>
        <a href="billing.php" class="active"><i class="fa fa-file-invoice-dollar"></i> Billing &amp; Dues <?php if($pay_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $pay_pend_count; ?></span><?php endif; ?></a>
        <a href="admin_tenants.php"><i class="fa fa-users"></i> Tenant List</a>
        <a href="admin_units.php"><i class="fa fa-door-open"></i> Unit Management</a>

        <div class="mt-4 mb-2 px-3"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Services</small></div>
        
        <a class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseServices" style="cursor: pointer;">
            <i class="fa fa-screwdriver-wrench"></i> <span>Maintenance</span> <i class="fa fa-chevron-down ms-auto" style="font-size: 0.7rem;"></i>
        </a>
        <div id="collapseServices" class="collapse">
            <div class="collapse-inner">
                <a href="manage_complaints.php">Complaints</a>
                <a href="post_announcement.php">Announcements</a>
                <a href="manage_rules.php">House Rules</a>
            </div>
        </div>
    </div>

    <div class="logout-link">
        <a href="logout.php"><i class="fa fa-arrow-right-from-bracket"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <button id="mobile-toggle" onclick="toggleSidebarMenuDrawer()"><i class="fa fa-bars-staggered"></i></button>
            <div class="header-title">
                <i class="fa fa-file-invoice-dollar"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Billing</h3>
            </div>
        </div>
        <button id="theme-toggle" title="Switch Theme">
            <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-teal shadow-sm" data-bs-toggle="modal" data-bs-target="#addBillModal">
            <i class="fa fa-plus-circle me-1"></i> New Bill
        </button>
    </div>
    
    <div class="stats-row mb-4">
        <?php mysqli_data_seek($cat_result, 0); while($cat = mysqli_fetch_assoc($cat_result)): ?>
        <div class="stat-card">
            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.3px;"><?php echo htmlspecialchars($cat['bill_type']); ?> Collection</small>
            <h5 class="fw-800 mb-0 text-main" style="margin-top:2px;">₱<?php echo number_format($cat['total_cat'], 2); ?></h5>
        </div>
        <?php endwhile; ?>
        <div class="stat-card" style="border-left-color: #ef4444;">
            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.3px;">Total Unpaid</small>
            <h5 class="fw-800 mb-0 text-danger" style="margin-top:2px;">₱<?php echo number_format($total_unpaid, 2); ?></h5>
        </div>
    </div>

    <div class="glass-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <div class="input-group rounded-3" style="background: var(--input-bg); border: 1px solid var(--border-color);">
                    <span class="input-group-text bg-transparent border-0"><i class="fa fa-search text-muted"></i></span>
                    <input type="text" id="searchBill" class="form-control bg-transparent border-0 text-main" placeholder="Search tenant..." style="box-shadow: none; font-weight:600;">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <input type="month" name="filter_month" class="form-control border-0 p-2.5 text-main" value="<?php echo $filter_month; ?>" style="background: var(--input-bg); border: 1px solid var(--border-color) !important; font-weight:600; border-radius:10px;">
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-teal w-100">Filter</button>
                <a href="billing.php" class="btn btn-light w-100" style="background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); font-weight:700; border-radius:12px;"><i class="fa fa-rotate"></i></a>
            </div>
        </form>
    </div>

    <div class="glass-card desktop-table">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Tenant</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="billingTableBodyDesktop">
                    <?php mysqli_data_seek($result, 0); while($row = mysqli_fetch_assoc($result)): 
                        $total = $row['total_base_amount'] + $row['total_penalty'];
                        $status = $row['status'];
                        $due_date = date('M d, Y', strtotime($row['earliest_due']));
                        $cls = ($status == 'Paid') ? 'bg-paid' : (($status == 'Unpaid') ? 'bg-unpaid' : 'bg-pending');
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-main" style="font-size:0.92rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            <small class="text-muted fw-600">Unit <?php echo $row['unit_no'] ?? 'N/A'; ?></small>
                        </td>
                        <td><small class="fw-bold text-teal" style="color:var(--teal-accent) !important;"><?php echo $row['combined_types']; ?></small></td>
                        <td><div class="fw-800 text-main">₱<?php echo number_format($total, 2); ?></div></td>
                        <td><small class="fw-bold text-muted"><?php echo $due_date; ?></small></td>
                        <td><span class="status-pill <?php echo $cls; ?>"><?php echo $status; ?></span></td>
                        <td class="text-center">
                            <?php if($status == 'Paid'): ?>
                                <a href="view_receipt.php?ref=<?php echo urlencode($row['reference_no']); ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" style="font-size:0.75rem;">
                                    <i class="fa fa-print me-1"></i> Receipt
                                </a>
                            <?php elseif($status == 'Pending Approval'): ?>
                                <button class="btn btn-sm btn-warning fw-bold rounded-pill px-3" style="font-size:0.75rem;" onclick="verifyPayment('<?php echo $row['single_bill_id']; ?>')">
                                    Verify
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" style="font-size:0.75rem;" onclick="sendReminder('<?php echo $row['bill_ids']; ?>', '<?php echo $row['full_name']; ?>', '<?php echo $row['email']; ?>', '<?php echo $total; ?>', '<?php echo $due_date; ?>', '<?php echo addslashes($row['combined_types']); ?>')">
                                    <i class="fa fa-paper-plane me-1"></i> Remind
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="billingTableBodyMobile">
        <?php mysqli_data_seek($result, 0); while($row = mysqli_fetch_assoc($result)): 
            $total = $row['total_base_amount'] + $row['total_penalty'];
            $status = $row['status'];
            $due_date = date('M d, Y', strtotime($row['earliest_due']));
            $cls = ($status == 'Paid') ? 'bg-paid' : (($status == 'Unpaid') ? 'bg-unpaid' : 'bg-pending');
        ?>
        <div class="mobile-bill-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-800 text-main mb-0" style="font-size:1.05rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                    <small class="text-muted fw-600">Unit <?php echo $row['unit_no'] ?? 'N/A'; ?> • <strong class="text-teal"><?php echo $row['combined_types']; ?></strong></small>
                    <div class="small text-muted mt-1 fw-500">Due: <?php echo $due_date; ?></div>
                </div>
                <span class="status-pill <?php echo $cls; ?>"><?php echo $status; ?></span>
            </div>
            <div class="card-actions">
                <div class="fw-800 fs-5 text-main">₱<?php echo number_format($total, 2); ?></div>
                <div>
                    <?php if($status == 'Paid'): ?>
                        <a href="view_receipt.php?ref=<?php echo urlencode($row['reference_no']); ?>" target="_blank" class="btn btn-sm btn-teal px-4" style="border-radius:10px; font-size:0.8rem;">Receipt</a>
                    <?php elseif($status == 'Pending Approval'): ?>
                        <button class="btn btn-sm btn-warning fw-bold px-4 rounded-pill" style="font-size:0.8rem;" onclick="verifyPayment('<?php echo $row['single_bill_id']; ?>')">Verify</button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-danger px-4 rounded-pill" style="font-size:0.8rem; font-weight:700;" onclick="sendReminder('<?php echo $row['bill_ids']; ?>', '<?php echo $row['full_name']; ?>', '<?php echo $row['email']; ?>', '<?php echo $total; ?>', '<?php echo $due_date; ?>', '<?php echo addslashes($row['combined_types']); ?>')">Remind</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="addBillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--teal-accent); color: white;">
                <h5 class="modal-title fw-800 text-white"><i class="fa fa-plus-circle me-2"></i>Create New Bill</h5>
                <button type="button" class="btn-close btn-close-white" style="filter:invert(1);" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_bill.php" method="POST" id="billingForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Select Tenant</label>
                        <select name="user_id" id="tenantSelect" class="form-select border-0 p-2.5 text-main" style="background: var(--input-bg); border: 1px solid var(--border-color) !important; font-weight:600;" required>
                            <option value="" data-rent="0">Choose...</option>
                            <?php 
                            $tenants = mysqli_query($conn, "SELECT DISTINCT u.id, u.full_name, un.monthly_rent 
                                                            FROM users u 
                                                            JOIN rent_requests rr ON u.id = rr.user_id 
                                                            LEFT JOIN units un ON u.assigned_unit_id = un.id
                                                            WHERE u.role='tenant' AND rr.status='Approved' 
                                                            ORDER BY u.full_name ASC");
                            while($t = mysqli_fetch_assoc($tenants)) { 
                                $rent = $t['monthly_rent'] ?? 0;
                                echo "<option value='{$t['id']}' data-rent='{$rent}'>{$t['full_name']}</option>"; 
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Category</label>
                        <select name="bill_type" id="categorySelect" class="form-select border-0 p-2.5 text-main" style="background: var(--input-bg); border: 1px solid var(--border-color) !important; font-weight:600;" required>
                            <option value="Rent">Rent</option>
                            <option value="Water">Water</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Security Deposit">Security Deposit</option>
                            <option value="Advance Rent">Advance Rent</option>
                        </select>
                    </div>
                    <div id="readingSection" style="display:none;">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Prev Reading</label>
                                <input type="number" step="0.01" name="previous_reading" id="prev_reading" class="form-control border-0 p-2.5 text-main calc-trigger" style="background: var(--input-bg); border: 1px solid var(--border-color) !important;" value="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Curr Reading</label>
                                <input type="number" step="0.01" name="current_reading" id="curr_reading" class="form-control border-0 p-2.5 text-main calc-trigger" style="background: var(--input-bg); border: 1px solid var(--border-color) !important;" value="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Rate</label>
                            <input type="number" step="0.01" name="rate" id="rate" class="form-control border-0 p-2.5 text-main calc-trigger" style="background: var(--input-bg); border: 1px solid var(--border-color) !important;" value="0.00">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Total Amount</label>
                            <input type="number" step="0.01" name="amount" id="amountInput" class="form-control border-0 p-2.5 text-main fw-bold text-teal" style="background: var(--input-bg); border: 1px solid var(--border-color) !important;" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Due Date</label>
                            <input type="date" name="due_date" class="form-control border-0 p-2.5 text-main" style="background: var(--input-bg); border: 1px solid var(--border-color) !important; font-weight:600;" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" name="submit_bill" id="saveBtn" class="btn btn-teal w-100 py-3 fw-bold">Save Billing Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSidebarMenuDrawer() {
    $('#sidebar').toggleClass('active');
    $('#overlay').toggleClass('active');
}

$(document).ready(function() {
    // Pure AJAX Theme Toggle Core Mechanics Integration
    $('#theme-toggle').click(function() {
        const html = $('html');
        const currentMode = html.attr('data-theme');
        const nextMode = currentMode === 'light' ? 'dark' : 'light';
        
        html.attr('data-theme', nextMode);
        $(this).find('i').attr('class', nextMode === 'light' ? 'fa fa-moon' : 'fa fa-sun');
        $.post('update_theme.php', { theme: nextMode });
    });

    function calculateBill() {
        let prev = parseFloat($('#prev_reading').val()) || 0;
        let curr = parseFloat($('#curr_reading').val()) || 0;
        let rate = parseFloat($('#rate').val()) || 0;
        let amountInput = $('#amountInput');
        if (curr >= prev) {
            let total = (curr - prev) * rate;
            amountInput.val(total.toFixed(2));
        }
    }

    $(document).on('input', '.calc-trigger', function() { calculateBill(); });

    $('#categorySelect').on('change', function() {
        let cat = $(this).val();
        let selectedOption = $('#tenantSelect').find('option:selected');
        let rentAmount = parseFloat(selectedOption.data('rent')) || 0;

        if (cat === 'Water' || cat === 'Electricity') {
            $('#readingSection').slideDown();
            $('#rate').val(cat === 'Water' ? '50.00' : '15.00'); 
            $('#amountInput').val('0.00');
        } else {
            $('#readingSection').slideUp();
            if (cat === 'Rent') {
                $('#amountInput').val(rentAmount.toFixed(2));
            } else {
                $('#amountInput').val('');
            }
        }
    });

    $('#tenantSelect').on('change', function() {
        let cat = $('#categorySelect').val();
        let rentAmount = parseFloat($(this).find('option:selected').data('rent')) || 0;
        if (cat === 'Rent') $('#amountInput').val(rentAmount.toFixed(2));
    });

    $('#searchBill').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.mobile-bill-card, #billingTableBodyDesktop tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });
});

// GET-based execution router for sending asynchronous email reminders
function sendReminder(billIds, name, email, amount, due, types) {
    if(!email || email === '') {
        alert("No email address found for this tenant.");
        return;
    }
    
    if (confirm(`Send automated Email reminder statement to ${name}?`)) {
        window.location.href = `billing.php?remind_id=${billIds}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&amount=${amount}&due=${encodeURIComponent(due)}&types=${encodeURIComponent(types)}`;
    }
}

// FIXED: Binago ang redirect destination patungong verify_bill.php at ipinasa ang tamang Bill ID parameter
function verifyPayment(id) {
    window.location.href = `verify_bill.php?id=${id}`;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>