<?php
// 1. IMPORT PHPMAILER CLASSES AT THE VERY TOP
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include('db.php');
session_start();

// Security Check: Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

mysqli_set_charset($conn, "utf8mb4");
$admin_id = $_SESSION['user_id'];

// --- THEME PERSISTENCE LOGIC ---
if (!isset($_SESSION['theme'])) {
    $theme_q = mysqli_query($conn, "SELECT theme_preference FROM users WHERE id = '$admin_id'");
    $theme_row = mysqli_fetch_assoc($theme_q);
    $_SESSION['theme'] = $theme_row['theme_preference'] ?? 'light';
}
$current_theme = $_SESSION['theme'];

$msg = "";
$msg_type = "";

// 2. HELPER FUNCTION USING PHPMAILER (SYNCHRONIZED WITH STABLE APP PASSWORD)
function sendStatusEmail($to, $name, $unit, $status, $reason = "") {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mgsapartment1@gmail.com';       
        $mail->Password   = 'uicarhkqncphsitx'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Inalis ang apostrophe sa pangalan upang maiwasan ang spam blacklisting
        $mail->setFrom('mgsapartment1@gmail.com', "MGs Apartment");
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = "Application $status - MGs Apartment";
        
        $color = ($status == 'Approved') ? '#008080' : '#d9534f';
        $status_text = ($status == 'Approved') ? "Congratulations, your application is Approved!" : "We regret to inform you that your application was Declined.";
        
        $email_template = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; border: 1px solid $color; border-radius: 10px; overflow: hidden;'>
                <div style='background: $color; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>MGs Apartment Update</h2>
                </div>
                <div style='padding: 20px;'>
                    <p>Hi <b>$name</b>,</p>
                    <p>$status_text</p>
                    <p><b>Details:</b></p>
                    <ul>
                        <li><b>Unit:</b> $unit</li>
                        <li><b>Status:</b> $status</li>
                    </ul>";
        
        if ($status == 'Approved') {
            $email_template .= "<p>You can now log in to your tenant portal to settle your initial bills and sign your lease agreement.</p>
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/onlinetenantmanagement/login.php' style='background: #008080; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Login to Portal</a>
                    </p>";
        } else if ($reason) {
             $email_template .= "<p style='color: #d9534f;'><b>Reason:</b> $reason</p>";
        }

        $email_template .= "<br><p style='font-size: 12px; color: #888;'>This is an automated message. Please do not reply.</p></div></div></body></html>";
        
        $mail->Body = $email_template;
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// Global Notif Counts for badge notification alignment
$res_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM rent_requests WHERE status = 'Pending'");
$pend_count = mysqli_fetch_assoc($res_pend)['total'] ?? 0;

$res_lease = mysqli_query($conn, "SELECT COUNT(*) as total FROM lease_requests WHERE status = 'Pending'");
$lease_pend_count = mysqli_fetch_assoc($res_lease)['total'] ?? 0;

$res_pay_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Pending Approval' OR (status = 'Pending' AND proof_of_payment IS NOT NULL)");
$pay_pend_count = mysqli_fetch_assoc($res_pay_pend)['total'] ?? 0;

// APPROVE LOGIC
if (isset($_GET['approve_id'])) {
    $req_id = mysqli_real_escape_string($conn, $_GET['approve_id']);
    $get_info = mysqli_query($conn, "SELECT r.unit_id, r.user_id, u.full_name, u.email, u.contact, un.unit_no, un.monthly_rent 
                                     FROM rent_requests r 
                                     JOIN users u ON r.user_id = u.id 
                                     JOIN units un ON r.unit_id = un.id 
                                     WHERE r.id = '$req_id'");
    
    if($req_data = mysqli_fetch_assoc($get_info)) {
        $u_id = $req_data['unit_id'];
        $user_id = $req_data['user_id'];
        $rent_amount = $req_data['monthly_rent'];
        $today = date('Y-m-d');
        $t_name = $req_data['full_name'];
        $t_email = $req_data['email'];
        $u_no = $req_data['unit_no'];
        
        mysqli_query($conn, "INSERT INTO bills (user_id, bill_type, amount, status, due_date) VALUES ('$user_id', 'Security Deposit', '$rent_amount', 'Unpaid', '$today')");
        mysqli_query($conn, "INSERT INTO bills (user_id, bill_type, amount, status, due_date) VALUES ('$user_id', 'Advance Rent', '$rent_amount', 'Unpaid', '$today')");
        
        $notif_msg = "Congratulations $t_name! Your application for Unit $u_no has been APPROVED.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, status, created_at) VALUES ('$user_id', 'Application Approved', '$notif_msg', 'unread', NOW())");

        sendStatusEmail($t_email, $t_name, $u_no, 'Approved');
        mysqli_query($conn, "UPDATE rent_requests SET status = 'Approved', lease_start = '$today' WHERE id = '$req_id'");
        mysqli_query($conn, "UPDATE units SET status = 'Occupied' WHERE id = '$u_id'");

        echo "<script>alert('Application Approved! Automated Email Notification sent.'); window.location.href = 'admin_requests.php';</script>";
        exit();
    }
}

// REJECT LOGIC
if (isset($_GET['reject_id'])) {
    $req_id = mysqli_real_escape_string($conn, $_GET['reject_id']);
    $get_info = mysqli_query($conn, "SELECT u.full_name, u.email, u.contact, un.unit_no FROM rent_requests r JOIN users u ON r.user_id = u.id JOIN units un ON r.unit_id = un.id WHERE r.id = '$req_id'");
    if($req_data = mysqli_fetch_assoc($get_info)) {
        sendStatusEmail($req_data['email'], $req_data['full_name'], $req_data['unit_no'], 'Rejected');
        
        mysqli_query($conn, "UPDATE rent_requests SET status = 'Rejected' WHERE id = '$req_id'");
        echo "<script>alert('Application Rejected. Notification Email successfully dispatched.'); window.location.href = 'admin_requests.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tenant Applications | Admin Portal</title>
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

        /* PREMIUM APP CARD RENDER INTERFACES */
        .request-card { background: var(--card-bg); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid var(--border-color); overflow: hidden; padding: 10px 0; }
        
        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; margin-bottom: 0; }
        .table header, .table thead th { background-color: var(--teal-accent) !important; color: white !important; font-size: 0.75rem; text-transform: uppercase; padding: 16px 20px; border: none; font-weight: 700; letter-spacing: 0.5px; }
        .table tbody td { padding: 18px 20px; border-bottom: 1px solid var(--border-color) !important; color: var(--text-main) !important; vertical-align: middle; }
        .table tbody tr:last-child td { border-bottom: none !important; }

        .badge-unit-pill { background: var(--input-bg); color: var(--text-main); border: 1px solid var(--border-color); font-weight: 700; font-size: 0.8rem; padding: 8px 16px; border-radius: 50px; }
        .btn-review { background: var(--teal-accent) !important; color: white !important; border-radius: 50px; font-weight: 700; border: none; transition: 0.23s ease-in-out; }
        .btn-review:hover { background: #006666 !important; transform: scale(1.03); }

        /* MODAL LAYER ENHANCEMENTS */
        .modal-content { background-color: var(--card-bg) !important; border-radius: 24px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .modal-header-teal { background: var(--teal-accent); color: white; padding: 22px 26px; display: flex; align-items: center; }
        .req-thumbnail-box { height: 140px; width: 100%; border-radius: 16px; overflow: hidden; border: 2px dashed var(--border-color); cursor: pointer; transition: 0.25s ease; background: var(--input-bg); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; }
        .req-thumbnail-box:hover { border-color: var(--teal-accent); transform: translateY(-3px); background: rgba(0, 128, 128, 0.02); }
        .req-thumbnail { height: 100%; width: 100%; object-fit: cover; }
        .req-title { font-size: 0.65rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
        .pdf-placeholder { text-align: center; color: #ef4444; }
        .pdf-placeholder i { font-size: 2.2rem; }

        #mobile-toggle { display: none; background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 45px; height: 45px; border-radius: 12px; align-items: center; justify-content: center; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            #mobile-toggle { display: flex; }
        }

        @media (max-width: 768px) {
            .table thead { display: none; }
            .table tbody td { display: block; width: 100%; text-align: left; padding: 12px 20px; border: none !important; }
            .table tbody tr { border-bottom: 1px solid var(--border-color); display: block; padding: 10px 0; }
            .table tbody td:first-child { font-weight: bold; }
            .table tbody td:last-child { padding-bottom: 15px; text-align: left; }
            .btn-review { width: 100%; padding: 10px; }
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
        <a href="admin_dashboard.php"><i class="fa fa-chart-pie"></i> Dashboard</a>
        <a href="admin_requests.php" class="active"><i class="fa fa-user-plus"></i> New Requests <?php if($pend_count > 0): ?><span class="badge rounded-pill bg-danger ms-auto"><?php echo $pend_count; ?></span><?php endif; ?></a>
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
            <div class="header-title">
                <i class="fa fa-file-contract"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Tenant Applications</h3>
            </div>
        </div>
        <button id="theme-toggle" title="Switch Theme">
            <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
    </div>

    <div class="request-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Applicant Info</th>
                        <th>Unit Details</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = "SELECT r.id as req_id, u.*, un.unit_no, un.monthly_rent 
                          FROM rent_requests r 
                          JOIN users u ON r.user_id = u.id 
                          JOIN units un ON r.unit_id = un.id 
                          WHERE r.status = 'Pending'
                          ORDER BY r.request_date DESC";
                    $res = mysqli_query($conn, $q);
                    
                    if(mysqli_num_rows($res) > 0) {
                        while($row = mysqli_fetch_assoc($res)) { 
                            $safe_row = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/<?php echo $row['profile_pic'] ?: 'default.png'; ?>" class="rounded-circle me-3" style="width:45px; height:45px; object-fit:cover; border: 1px solid var(--border-color);">
                                        <div>
                                            <div class="fw-bold text-main" style="font-size:0.92rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                            <small class="text-muted fw-600"><?php echo htmlspecialchars($row['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-unit-pill">
                                        Unit <?php echo htmlspecialchars($row['unit_no']); ?> • ₱<?php echo number_format($row['monthly_rent'], 0); ?>
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-review rounded-pill fw-bold px-4 shadow-sm" onclick='openReviewModal(<?php echo $safe_row; ?>)'>
                                        <i class="fa fa-search me-1"></i> Review Application
                                    </button>
                                </td>
                            </tr>
                        <?php } 
                    } else { echo "<tr><td colspan='3' class='text-center py-5 text-muted fw-700'>No pending tenant applications log. ✨</td></tr>"; } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-teal">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h5 class="fw-800 mb-0 text-white" id="modal_name">Review Documents</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-light" style="background: var(--bg) !important;">
                <div class="row g-3">
                    <?php 
                        $doc_types = [['id' => 'id', 'label' => 'Valid ID'],['id' => 'police', 'label' => 'Police Clearance'],['id' => 'brgy', 'label' => 'Brgy Clearance'],['id' => 'nbi', 'label' => 'NBI Clearance']];
                        foreach($doc_types as $doc):
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="req-title"><?php echo $doc['label']; ?></div>
                        <div class="req-thumbnail-box" id="box_<?php echo $doc['id']; ?>" onclick="showFullDoc(this.dataset.src)">
                            <img id="img_<?php echo $doc['id']; ?>" class="req-thumbnail d-none">
                            <div id="pdf_<?php echo $doc['id']; ?>" class="pdf-placeholder d-none"><i class="fa fa-file-pdf"></i><br><small class="fw-bold" style="font-size:10px;">VIEW PDF</small></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 flex-column flex-md-row bg-white" style="background: var(--card-bg) !important; border-top: 1px solid var(--border-color) !important;">
                <a id="btn_reject" class="btn btn-outline-danger px-4 rounded-pill fw-bold mb-2 mb-md-0 w-100 w-md-auto py-2" onclick="return confirm('Reject application?')">Decline Request</a>
                <a id="btn_approve" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm w-100 w-md-auto py-2" style="background-color: #16a34a;" onclick="return confirm('Approve tenant?')">Approve Application</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fullDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-body p-0 text-center">
                <img id="full_img_view" class="img-fluid rounded-4 d-none shadow-lg" style="max-height: 85vh;">
                <iframe id="full_pdf_view" class="rounded-4 d-none shadow-lg" style="width: 100%; height: 85vh; border: none; background: white;"></iframe>
                <br>
                <button type="button" class="btn btn-dark rounded-circle mt-3" data-bs-dismiss="modal" style="width:42px; height:42px; padding:0; background: #111827;"><i class="fa fa-times"></i></button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebarMenuDrawer() {
    $('#sidebar').toggleClass('active');
    $('#overlay').toggleClass('active');
}

$(document).ready(function() {
    $('#theme-toggle').click(function() {
        const html = $('html');
        const currentMode = html.attr('data-theme');
        const nextMode = currentMode === 'light' ? 'dark' : 'light';
        
        html.attr('data-theme', nextMode);
        $(this).find('i').attr('class', nextMode === 'light' ? 'fa fa-moon' : 'fa fa-sun');
        $.post('update_theme.php', { theme: nextMode });
    });
});

function openReviewModal(data) {
    document.getElementById('modal_name').innerText = "Review: " + data.full_name;
    const docs = [{key: 'valid_id', id: 'id'},{key: 'police_clearance', id: 'police'},{key: 'brgy_clearance', id: 'brgy'},{key: 'nbi_clearance', id: 'nbi'}];
    docs.forEach(doc => {
        const filename = data[doc.key];
        const box = document.getElementById('box_' + doc.id);
        const img = document.getElementById('img_' + doc.id);
        const pdf = document.getElementById('pdf_' + doc.id);
        
        const src = filename && filename.trim() !== '' ? 'uploads/' + filename : '';
        box.dataset.src = src;
        
        img.classList.add('d-none'); pdf.classList.add('d-none');
        if(filename && filename.trim() !== '') {
            if(filename.toLowerCase().endsWith('.pdf')) { pdf.classList.remove('d-none'); }
            else { img.src = src; img.classList.remove('d-none'); }
        }
    });
    document.getElementById('btn_approve').href = 'admin_requests.php?approve_id=' + data.req_id;
    document.getElementById('btn_reject').href = 'admin_requests.php?reject_id=' + data.req_id;
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function showFullDoc(src) {
    if(!src) return;
    const imgView = document.getElementById('full_img_view');
    const pdfView = document.getElementById('full_pdf_view');
    imgView.classList.add('d-none'); pdfView.classList.add('d-none');
    if (src.toLowerCase().endsWith('.pdf')) { pdfView.src = src; pdfView.classList.remove('d-none'); }
    else { imgView.src = src; imgView.classList.remove('d-none'); }
    new bootstrap.Modal(document.getElementById('fullDocModal')).show();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>