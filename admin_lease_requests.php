<?php
include('db.php');
session_start();

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

// --- LOGIC: APPROVE O REJECT REQUEST ---
if (isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; 
    $admin_remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');

    $req_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM lease_requests WHERE id = '$request_id'"));
    $t_id = $req_data['user_id'];
    $u_id = $req_data['unit_id'];
    $type = $req_data['request_type'];
    $pref_date = $req_data['preferred_date'];

    if ($action === 'Approve') {
        if ($type === 'Renew') {
            mysqli_query($conn, "UPDATE users SET contract_end_date = '$pref_date' WHERE id = '$t_id'");
            $msg = "Your lease renewal has been approved until " . date('M d, Y', strtotime($pref_date));
        } else if ($type === 'Move-out') {
            mysqli_query($conn, "UPDATE users SET assigned_unit_id = NULL, contract_end_date = NULL WHERE id = '$t_id'");
            mysqli_query($conn, "UPDATE units SET status = 'Available' WHERE id = '$u_id'");
            $msg = "Your move-out request for " . date('M d, Y', strtotime($pref_date)) . " has been approved.";
        }
        
        mysqli_query($conn, "UPDATE lease_requests SET status = 'Approved', admin_remarks = '$admin_remarks' WHERE id = '$request_id'");
        mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, status) VALUES ('$t_id', 'Lease Update', '$msg', 'unread')");

    } else {
        mysqli_query($conn, "UPDATE lease_requests SET status = 'Rejected', admin_remarks = '$admin_remarks' WHERE id = '$request_id'");
        mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, status) VALUES ('$t_id', 'Lease Update', 'Your lease request was rejected. Remarks: $admin_remarks', 'unread')");
    }
    header("Location: admin_lease_requests.php?success=1");
    exit();
}

// Global Notif Counts for badge notification alignment
$res_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM rent_requests WHERE status = 'Pending'");
$pend_count = mysqli_fetch_assoc($res_pend)['total'] ?? 0;

$res_lease = mysqli_query($conn, "SELECT COUNT(*) as total FROM lease_requests WHERE status = 'Pending'");
$lease_pend_count = mysqli_fetch_assoc($res_lease)['total'] ?? 0;

$res_pay_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Pending Approval' OR (status = 'Pending' AND proof_of_payment IS NOT NULL)");
$pay_pend_count = mysqli_fetch_assoc($res_pay_pend)['total'] ?? 0;

$requests = mysqli_query($conn, "SELECT lr.*, u.full_name, un.unit_no 
                                FROM lease_requests lr 
                                JOIN users u ON lr.user_id = u.id 
                                JOIN units un ON lr.unit_id = un.id 
                                WHERE lr.status = 'Pending' 
                                ORDER BY lr.id DESC");
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Lease Management - MG's Apartment</title>
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
        .card-custom { background: var(--card-bg); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid var(--border-color); overflow: hidden; padding: 10px 0; }
        
        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; margin-bottom: 0; }
        .table thead th { background-color: var(--teal-accent) !important; color: white !important; font-size: 0.75rem; text-transform: uppercase; padding: 16px 20px; border: none; font-weight: 700; letter-spacing: 0.5px; }
        .table tbody td { padding: 18px 20px; border-bottom: 1px solid var(--border-color) !important; color: var(--text-main) !important; vertical-align: middle; }
        .table tbody tr:last-child td { border-bottom: none !important; }

        .badge-renew { background: rgba(0, 128, 128, 0.1); color: var(--teal-accent); font-weight: 800; border-radius: 8px; padding: 6px 14px; font-size: 0.68rem; letter-spacing: 0.3px; display: inline-block; }
        .badge-moveout { background: rgba(220, 53, 69, 0.1); color: #dc2626; font-weight: 800; border-radius: 8px; padding: 6px 14px; font-size: 0.68rem; letter-spacing: 0.3px; display: inline-block; }
        
        .btn-teal { background: var(--teal-accent) !important; color: white !important; border-radius: 10px; font-weight: 700; border: none; transition: 0.2s ease; }
        .btn-teal:hover { background: #006666 !important; transform: scale(1.03); }
        .btn-reject { color: #dc2626 !important; border: 1px solid rgba(220, 53, 69, 0.15) !important; background: rgba(220, 53, 69, 0.04) !important; border-radius: 10px; font-weight: 700; transition: 0.2s ease; }
        .btn-reject:hover { background: rgba(220, 53, 69, 0.1) !important; transform: scale(1.03); }

        /* MODAL STYLE ALIGNMENTS */
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
        }

        @media (max-width: 768px) {
            .table thead { display: none; }
            .table tbody td { display: block; width: 100%; text-align: left; padding: 12px 20px; border: none !important; }
            .table tbody tr { border-bottom: 1px solid var(--border-color); display: block; padding: 10px 0; }
            .table tbody td:first-child { font-weight: bold; }
            .table tbody td:last-child { padding-bottom: 15px; text-align: center; }
            .btn-teal, .btn-reject { width: 48%; padding: 8px; display: inline-block; }
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
        <a href="admin_requests.php"><i class="fa fa-user-plus"></i> New Requests <?php if($pend_count > 0): ?><span class="badge rounded-pill bg-danger ms-auto"><?php echo $pend_count; ?></span><?php endif; ?></a>
        <a href="admin_lease_requests.php" class="active"><i class="fa fa-file-contract"></i> Lease Actions <?php if($lease_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $lease_pend_count; ?></span><?php endif; ?></a>
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
                <i class="fa fa-file-signature"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Lease Management</h3>
            </div>
        </div>
        <button id="theme-toggle" title="Switch Theme">
            <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 fw-600"><i class="fa fa-check-circle me-2"></i> Lease status request updated and tenant notified successfully!</div>
    <?php endif; ?>

    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Tenant &amp; Unit</th>
                        <th>Type</th>
                        <th>Target Date</th>
                        <th>Reason/Notes</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($requests) > 0): while($row = mysqli_fetch_assoc($requests)): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-800 text-main" style="font-size: 0.92rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            <small class="text-muted fw-600">Allocation: <strong class="text-teal">Unit <?php echo htmlspecialchars($row['unit_no']); ?></strong></small>
                        </td>
                        <td>
                            <span class="<?php echo ($row['request_type'] == 'Renew') ? 'badge-renew' : 'badge-moveout'; ?>">
                                <?php echo strtoupper($row['request_type']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold small text-main"><?php echo date('M d, Y', strtotime($row['preferred_date'])); ?></div>
                        </td>
                        <td>
                            <p class="text-muted mb-0 small fw-500" style="max-width: 280px; text-overflow: ellipsis; overflow: hidden;"><?php echo htmlspecialchars($row['reason']); ?></p>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-teal px-3 py-1.5 me-1 fw-bold" 
                                    onclick="openActionModal(<?php echo $row['id']; ?>, 'Approve', '<?php echo $row['request_type']; ?>')">
                                Approve
                            </button>
                            <button class="btn btn-sm btn-reject px-3 py-1.5 fw-bold" 
                                    onclick="openActionModal(<?php echo $row['id']; ?>, 'Reject', '<?php echo $row['request_type']; ?>')">
                                Reject
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted fw-700">
                            <i class="fa-regular fa-folder-open fa-3x text-light mb-3 d-block" style="color: var(--border-color) !important;"></i>
                            <span>No pending tenant lease requests found. ✨</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-800 text-main" id="modalTitle">Process Request</h5>
                    <button type="button" class="btn-close" style="filter: var(--text-main);" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="request_id" id="modal_request_id">
                    <input type="hidden" name="action" id="modal_action_type">
                    <label class="small text-muted fw-700 text-uppercase d-block mb-2" style="font-size:0.65rem; letter-spacing:0.5px;">Add Remarks for the Tenant (Optional)</label>
                    <textarea name="remarks" class="form-control rounded-3 p-3" rows="3" placeholder="Write message feedback here..." style="background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); font-size: 0.88rem; font-weight: 600;"></textarea>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted small" style="background: var(--input-bg);" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="submitBtn" class="btn btn-teal rounded-pill px-4 fw-bold small">Confirm</button>
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
        $('#theme-toggle').click(function() {
            const html = $('html');
            const currentMode = html.attr('data-theme');
            const nextMode = currentMode === 'light' ? 'dark' : 'light';
            
            html.attr('data-theme', nextMode);
            $(this).find('i').attr('class', nextMode === 'light' ? 'fa fa-moon' : 'fa fa-sun');
            $.post('update_theme.php', { theme: nextMode });
        });
    });

    function openActionModal(id, action, type) {
        $('#modal_request_id').val(id);
        $('#modal_action_type').val(action);
        $('#modalTitle').text(action + ' ' + type + ' Request');
        
        if(action === 'Reject') {
            $('#submitBtn').removeClass('btn-teal').addClass('btn-danger').text('Reject Request').style;
        } else {
            $('#submitBtn').removeClass('btn-danger').addClass('btn-teal').text('Approve Request');
        }
        
        var myModal = new bootstrap.Modal(document.getElementById('actionModal'));
        myModal.show();
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>