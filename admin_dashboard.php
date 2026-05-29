<?php
include('db.php');
session_start();

// 1. SECURITY - Sinisigurado na Admin lang ang pwedeng pumasok
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/** * --- FIX CHARACTER ENCODING --- */
mysqli_set_charset($conn, "utf8mb4");

// --- THEME PERSISTENCE LOGIC ---
$admin_id = $_SESSION['user_id'];
if (!isset($_SESSION['theme'])) {
    $theme_q = mysqli_query($conn, "SELECT theme_preference FROM users WHERE id = '$admin_id'");
    $theme_row = mysqli_fetch_assoc($theme_q);
    $_SESSION['theme'] = $theme_row['theme_preference'] ?? 'light';
}
$current_theme = $_SESSION['theme'];

/** * --- AUTOMATED PENALTY LOGIC (PURE UPDATE) --- */
$current_date = date('Y-m-d');
$penalty_amount = 100.00; 

$update_penalty = "UPDATE bills 
                   SET penalty = '$penalty_amount', 
                       amount = amount + '$penalty_amount' 
                   WHERE status = 'Unpaid' 
                   AND due_date < '$current_date' 
                   AND (penalty IS NULL OR penalty = 0)";
mysqli_query($conn, $update_penalty);

// --- DYNAMIC EMAIL NOTIFICATION DISPATCH ENGINE (PHPMAILER INTEGRATION) ---
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['send_notif_id'])) {
    $target_tenant_id = mysqli_real_escape_string($conn, $_GET['send_notif_id']);
    
    // Fetching targeted user details directly matching user/tenant join table architecture
    $tenant_query = mysqli_query($conn, "SELECT u.full_name, u.email, un.unit_no FROM users u LEFT JOIN rent_requests r ON r.user_id = u.id LEFT JOIN units un ON r.unit_id = un.id WHERE u.id = '$target_tenant_id' LIMIT 1");
    
    if (mysqli_num_rows($tenant_query) > 0) {
        $tenant_data = mysqli_fetch_assoc($tenant_query);
        $t_email = $tenant_data['email'];
        $t_name = $tenant_data['full_name'];
        $t_unit = $tenant_data['unit_no'] ?? 'Allocated Suite';

        $mail = new PHPMailer(true);
        try {
            // SMTP Server Configuration Parameters
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your-apartment-email@gmail.com'; // Admin Email Account
            $mail->Password   = 'abcd efgh ijkl mnop';           // 16-Digit Google App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Envelope Recipient Routing Allocation
            $mail->setFrom('your-apartment-email@gmail.com', "MG's Apartment");
            $mail->addAddress($t_email, $t_name);

            // Responsive Markup Content Execution
            $mail->isHTML(true);
            $mail->Subject = "Monthly Rent Notice - MG's Apartment";
            $mail->Body    = "
                <div style='font-family: sans-serif; padding: 20px; color: #1e293b;'>
                    <h2>Good day, $t_name!</h2>
                    <p>This is a friendly reminder that your dynamic monthly ledger invoice details for <b>Unit $t_unit</b> have been revised and posted.</p>
                    <p>Please log in to your dashboard platform profile to review outstanding items. Thank you!</p>
                </div>";

            $mail->send();
            echo "<script>alert('Notification successfully dispatched via SMTP engine to $t_name!'); window.location.href='admin_notification.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Mail engine warning: {$mail->ErrorInfo}'); window.location.href='admin_notification.php';</script>";
        }
    }
}

// --- DATA QUERIES PARA SA MGA CARDS ---
$res_avail = mysqli_query($conn, "SELECT COUNT(*) as total FROM units WHERE status = 'Available'");
$avail_count = mysqli_fetch_assoc($res_avail)['total'] ?? 0;

$res_occ = mysqli_query($conn, "SELECT COUNT(*) as total FROM units WHERE status = 'Occupied'");
$occ_count = mysqli_fetch_assoc($res_occ)['total'] ?? 0;

$res_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM rent_requests WHERE status = 'Pending'");
$pend_count = mysqli_fetch_assoc($res_pend)['total'] ?? 0;

$res_coll = mysqli_query($conn, "SELECT SUM(amount) as total FROM bills WHERE status = 'Paid'");
$total_coll = mysqli_fetch_assoc($res_coll)['total'] ?? 0;

$res_pay_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Pending Approval' OR (status = 'Pending' AND proof_of_payment IS NOT NULL)");
$pay_pend_count = mysqli_fetch_assoc($res_pay_pend)['total'] ?? 0;

$res_complaints = mysqli_query($conn, "SELECT COUNT(*) as total FROM maintenance WHERE status = 'Pending'");
$complaints_count = mysqli_fetch_assoc($res_complaints)['total'] ?? 0;

$res_overdue = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Unpaid' AND due_date < '$current_date'");
$overdue_count = mysqli_fetch_assoc($res_overdue)['total'] ?? 0;

// Lease Requests (Renew/Move-out) para sa Sidebar
$res_lease = mysqli_query($conn, "SELECT COUNT(*) as total FROM lease_requests WHERE status = 'Pending'");
$lease_pend_count = mysqli_fetch_assoc($res_lease)['total'] ?? 0;

// --- FETCH LISTS ---
$latest_requests = mysqli_query($conn, "SELECT r.*, u.full_name, un.unit_no FROM rent_requests r JOIN users u ON r.user_id = u.id JOIN units un ON r.unit_id = un.id WHERE r.status = 'Pending' ORDER BY r.id DESC LIMIT 5");
$latest_complaints = mysqli_query($conn, "SELECT m.*, u.full_name FROM maintenance m JOIN users u ON m.user_id = u.id WHERE m.status = 'Pending' ORDER BY m.id DESC LIMIT 3");
$latest_announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY id DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard - MG's Apartment</title>

    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="uploads/logo.jpg">
    <meta name="theme-color" content="#008080">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
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

        body { background-color: var(--bg); color: var(--text-main); font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; transition: 0.3s ease; margin: 0; }

        h1, h2, h3, h4, h5, h6, .text-main, .fw-800 { color: var(--text-main) !important; }
        .text-muted { color: var(--text-muted) !important; }
        
        .text-teal { color: var(--teal-accent) !important; }
        [data-theme="dark"] .text-teal { color: #2dd4bf !important; }

        /* MONTHLY REPORT BUTTON CONFIGS */
        .btn-report {
            background-color: var(--card-bg) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border-color) !important;
            transition: 0.2s ease;
        }
        .btn-report:hover {
            transform: translateY(-2px);
            border-color: var(--teal-accent) !important;
        }

        /* SIDEBAR PANEL LAYOUT Drawer Closure Layers */
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

        /* MAIN APP VIEWPORT GRID WORKSPACE */
        .main-content { margin-left: 260px; padding: 30px; min-height: 100vh; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* THEME TOGGLE MECHANICS */
        #theme-toggle { width: 45px; height: 45px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--teal-accent); box-shadow: 0 4px 6px rgba(0,0,0,0.02); cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }

        /* GRADIENT PREMIUM STATISTICS COMPONENT CARDS */
        .stat-card { border: none; border-radius: 24px; color: white !important; position: relative; overflow: hidden; padding: 25px; transition: 0.25s ease-in-out; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.06); }
        .stat-card h2 { font-weight: 800; font-size: 2.2rem; margin: 5px 0 0 0; color: white !important; }
        .stat-card i { position: absolute; right: -10px; bottom: -10px; font-size: 4.5rem; opacity: 0.16; pointer-events: none; }
        .stat-card small { color: rgba(255,255,255,0.85) !important; text-transform: uppercase; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.6px; }
        
        .bg-gradient-teal { background: linear-gradient(135deg, #008080, #0d9488); }
        .bg-gradient-blue { background: linear-gradient(135deg, #2563eb, #3b82f6); }
        .bg-gradient-red { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .bg-gradient-dark { background: linear-gradient(135deg, #1e293b, #334155); }

        .card-custom { border: 1px solid var(--border-color); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); background: var(--card-bg); overflow: hidden; }
        
        /* TABLE LIST RENDER INTERFACE OVERRIDES */
        .table { color: var(--text-main) !important; }
        .table tbody tr { border-bottom: 1px solid var(--border-color); transition: background 0.2s; }
        .table tbody tr:hover { background: rgba(0, 128, 128, 0.02); }
        .btn-teal { background: var(--teal-accent); color: white !important; border-radius: 50px; font-weight: 700; transition: 0.2s; border: none; }
        .btn-teal:hover { background: #006666; transform: scale(1.03); }

        /* URGENT NOTIFIER PANELS ELEMENTS */
        .alert-badge-item { background: var(--card-bg); padding: 14px; border-radius: 16px; font-size: 0.82rem; font-weight: 800; shadow: 0 2px 8px rgba(0,0,0,0.01); border-left: 4px solid; display: flex; align-items: center; gap: 8px; }
        .alert-badge-item.overdue { border-left-color: #dc2626; color: #dc2626; }
        .alert-badge-item.lease { border-left-color: #2563eb; color: #2563eb; }
        .alert-badge-item.verif { border-left-color: #d97706; color: #d97706; }
        .alert-badge-item.repairs { border-left-color: #0d9488; color: #0d9488; }

        /* RESPONSIVE DRAWER BREAKPOINTS EXECUTION */
        #mobile-toggle { display: none; background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 45px; height: 45px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); align-items: center; justify-content: center; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            #mobile-toggle { display: flex; }
        }
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
        <a href="admin_dashboard.php" class="active"><i class="fa fa-chart-pie"></i> Dashboard</a>
        <a href="admin_requests.php"><i class="fa fa-user-plus"></i> New Requests <?php if($pend_count > 0): ?><span class="badge rounded-pill bg-danger ms-auto"><?php echo $pend_count; ?></span><?php endif; ?></a>
        <a href="admin_lease_requests.php"><i class="fa fa-file-contract"></i> Lease Actions <?php if($lease_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $lease_pend_count; ?></span><?php endif; ?></a>
        <a href="billing.php"><i class="fa fa-file-invoice-dollar"></i> Billing &amp; Dues <?php if($pay_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $pay_pend_count; ?></span><?php endif; ?></a>
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
            <div>
                <h2 class="fw-800 mb-0" style="font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">Hi, Ms. Mary Grace! 👋</h2>
                <p class="text-muted small fw-600 mb-0"><?php echo date('l, F d, Y'); ?></p>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button id="theme-toggle" title="Switch Theme">
                <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
            </button>
            <a href="generate_monthly_report.php" target="_blank" class="btn btn-report shadow-sm rounded-pill px-4 fw-bold small" style="font-size: 0.85rem;">
                <i class="fa fa-file-pdf text-danger me-2"></i> Monthly Report
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-gradient-blue h-100 border-0">
                <small>Available Units</small>
                <h2><?php echo $avail_count; ?></h2>
                <i class="fa fa-door-open"></i>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-gradient-teal h-100 border-0">
                <small>Occupied Units</small>
                <h2><?php echo $occ_count; ?></h2>
                <i class="fa fa-user-check"></i>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-gradient-red h-100 border-0">
                <small>Pending Repairs</small>
                <h2><?php echo $complaints_count; ?></h2>
                <i class="fa fa-wrench"></i>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-gradient-dark h-100 border-0">
                <small>Gross Earnings</small>
                <h2 style="font-size: 1.7rem;">₱<?php echo number_format($total_coll, 0); ?></h2>
                <i class="fa fa-coins"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-custom p-4 mb-4 border-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-800 mb-0" style="font-size: 1.05rem; font-weight: 800;">Tenant Admission Requests</h5>
                    <a href="admin_requests.php" class="btn btn-sm btn-light border-0 rounded-pill px-3 fw-bold text-muted" style="font-size: 0.72rem; background: var(--input-bg);">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-borderless mb-0">
                        <tbody id="requestTable">
                            <?php if(mysqli_num_rows($latest_requests) > 0): 
                                while($req = mysqli_fetch_assoc($latest_requests)): ?>
                                <tr>
                                    <td class="ps-0 py-3">
                                        <div class="fw-bold text-main" style="font-size: 0.9rem;"><?php echo htmlspecialchars($req['full_name']); ?></div>
                                        <small class="text-muted fw-600">Interested allocation: <strong class="text-teal">Unit <?php echo $req['unit_no']; ?></strong></small>
                                    </td>
                                    <td class="text-end pe-0 d-flex gap-2 justify-content-end">
                                    
                                        <a href="admin_requests.php" class="btn btn-sm btn-teal rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.72rem;">Review File</a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 fw-bold text-teal" style="font-size: 0.88rem; opacity: 0.85;">
                                        <i class="fa fa-info-circle me-1"></i> No new system admission files pending.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-custom p-4 border-0">
                <h5 class="fw-800 mb-4" style="font-size: 1.05rem; font-weight: 800;">Latest Announcements</h5>
                <?php if(mysqli_num_rows($latest_announcements) > 0): 
                    while($ann = mysqli_fetch_assoc($latest_announcements)): ?>
                    <div class="mb-3 pb-3 border-bottom border-light" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <h6 class="fw-bold mb-1 text-main" style="font-size: 0.9rem;"><?php echo htmlspecialchars($ann['title']); ?></h6>
                            <span class="text-muted fw-bold shrink-0" style="font-size: 0.65rem;"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></span>
                        </div>
                        <p class="small text-muted mb-0 text-truncate-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; font-weight: 500;"><?php echo htmlspecialchars($ann['content']); ?></p>
                    </div>
                <?php endwhile; else: ?>
                    <div class="text-center py-4">
                        <i class="fa fa-bullhorn text-light mb-2" style="font-size: 2rem; color: var(--border-color) !important;"></i>
                        <p class="text-muted small mb-0 fw-600">No active announcements broadcasted yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-custom p-4 mb-4 border-0 shadow-sm" style="background: var(--teal-accent); color: white;">
                <h6 class="fw-bold mb-3 d-flex align-items-center" style="color: white !important; font-weight: 800; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fa fa-triangle-exclamation me-2"></i>Urgent Alerts</h6>
                <div class="d-grid gap-2">
                    <?php if($overdue_count > 0): ?>
                        <div class="alert-badge-item overdue shadow-sm">
                            <i class="fa fa-clock"></i> <?php echo $overdue_count; ?> Overdue Accounts
                        </div>
                    <?php endif; ?>
                    <?php if($lease_pend_count > 0): ?>
                        <div class="alert-badge-item lease shadow-sm">
                            <i class="fa fa-file-signature"></i> <?php echo $lease_pend_count; ?> Pending Lease Action
                        </div>
                    <?php endif; ?>
                    <?php if($pay_pend_count > 0): ?>
                        <div class="alert-badge-item verif shadow-sm">
                            <i class="fa fa-hourglass-half"></i> <?php echo $pay_pend_count; ?> Remittance Approvals
                        </div>
                    <?php endif; ?>
                    <?php if($complaints_count > 0): ?>
                        <div class="alert-badge-item repairs shadow-sm">
                            <i class="fa fa-screwdriver-wrench"></i> <?php echo $complaints_count; ?> Actionable Repairs
                        </div>
                    <?php endif; ?>
                    <?php if($overdue_count == 0 && $lease_pend_count == 0 && $pay_pend_count == 0 && $complaints_count == 0): ?>
                        <div class="text-center py-2 opacity-90 small fw-bold" style="color: white !important;">All metrics clear! No urgent tasks logs.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-custom p-4 border-0 shadow-sm">
                <h6 class="fw-800 mb-3 text-muted text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px; font-weight: 800;">Recent Complaints</h6>
                <?php if(mysqli_num_rows($latest_complaints) > 0): 
                    while($c = mysqli_fetch_assoc($latest_complaints)): ?>
                    <div class="border-bottom border-light pb-3 mb-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-main" style="font-size: 0.85rem;"><?php echo htmlspecialchars($c['full_name']); ?></span>
                            <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger px-2 py-1" style="font-size: 0.58rem; font-weight: 800; letter-spacing: 0.3px;">PENDING</span>
                        </div>
                        <p class="small text-muted mb-0 text-truncate fw-600"><?php echo htmlspecialchars($c['issue_details'] ?? ''); ?></p>
                    </div>
                <?php endwhile; ?>
                    <a href="manage_complaints.php" class="text-teal small text-decoration-none fw-bold mt-2 d-block text-center" style="font-size: 0.78rem;">View All Complaints History →</a>
                <?php else: ?>
                    <div class="text-center py-3">
                        <p class="small text-muted mb-0 fw-600">No active operational operational complaints log.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Pure Stable Sidebar Toggle Navigation View Drawer
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

            // Save preference via AJAX session tunnel persistence
            $.post('update_theme.php', { theme: nextMode });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>