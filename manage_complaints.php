<?php
include('db.php');
session_start();

// Security check: Siguraduhin na Admin ang naka-login
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

// UPDATE LOGIC (Status and Remarks)
if (isset($_POST['update_status'])) {
    $complaint_id = mysqli_real_escape_string($conn, $_POST['complaint_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $admin_note = mysqli_real_escape_string($conn, $_POST['admin_note']);
    $tenant_id = mysqli_real_escape_string($conn, $_POST['tenant_id']); 

    $update_sql = "UPDATE maintenance SET 
                   status = '$new_status', 
                   admin_remarks = '$admin_note' 
                   WHERE id = '$complaint_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        // Mag-send ng notification sa tenant tungkol sa update
        $notif_msg = "Maintenance Update: Ang iyong request ay " . strtoupper($new_status) . ". Admin Note: $admin_note";
        $insert_notif = "INSERT INTO notifications (user_id, message, status, created_at) 
                         VALUES ('$tenant_id', '$notif_msg', 'unread', NOW())";
        mysqli_query($conn, $insert_notif);

        header("Location: manage_complaints.php?msg=Status updated successfully!");
        exit();
    }
}

// Global Notif Counts para sa badge notification panel alignment
$res_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM rent_requests WHERE status = 'Pending'");
$pend_count = mysqli_fetch_assoc($res_pend)['total'] ?? 0;

$res_lease = mysqli_query($conn, "SELECT COUNT(*) as total FROM lease_requests WHERE status = 'Pending'");
$lease_pend_count = mysqli_fetch_assoc($res_lease)['total'] ?? 0;

$res_pay_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Pending Approval' OR (status = 'Pending' AND proof_of_payment IS NOT NULL)");
$pay_pend_count = mysqli_fetch_assoc($res_pay_pend)['total'] ?? 0;

// FETCH QUERY
$query = "SELECT m.*, u.full_name, u.contact, u.id as tenant_id, ut.unit_no 
          FROM maintenance m 
          JOIN users u ON m.user_id = u.id 
          LEFT JOIN units ut ON u.assigned_unit_id = ut.id 
          ORDER BY FIELD(m.status, 'Pending', 'In Progress', 'Resolved'), m.id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Complaints - MG's Apartment</title>

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
            --input-bg: #f1f5f9;
            --input-border: #e2e8f0;
            --note-bg: #f0fdfa;
            --note-text: #134e4a;
            --note-border: #008080;
        }
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #111827;
            --input-border: #475569;
            --note-bg: #112827;
            --note-text: #2dd4bf;
            --note-border: #2dd4bf;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); transition: 0.3s ease; margin: 0; overflow-x: hidden; }
        
        h1, h2, h3, h4, h5, h6, .text-main, .fw-800 { color: var(--text-main) !important; }
        .text-muted { color: var(--text-muted) !important; }

        /* SIDEBAR PANEL DRAWER COMPONENTS */
        .sidebar { height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; width: 260px; display: flex; flex-direction: column; z-index: 1200; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); top: 0; left: 0; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-brand h5 { font-weight: 800; letter-spacing: 0.5px; color: white !important; margin: 0; font-size: 1.1rem; text-transform: uppercase; }
        .sidebar-brand small { color: var(--teal-accent); font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-top: 2px; }

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

        /* COMPLAINT CARDS COMPONENT GRID */
        .complaint-card { border: 1px solid var(--border-color); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); background: var(--card-bg); transition: 0.25s ease-in-out; height: 100%; overflow: hidden; }
        .complaint-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.05); }
        
        .issue-details { background: var(--input-bg); border-radius: 16px; padding: 16px; font-size: 0.88rem; color: var(--text-main); border: 1px solid var(--border-color); }
        .admin-note-box { background: var(--note-bg); border-left: 4px solid var(--note-border); padding: 14px; font-size: 0.82rem; color: var(--note-text) !important; border-radius: 12px; }
        
        .btn-teal { background: var(--teal-accent) !important; color: white !important; border: none; border-radius: 12px; font-weight: 700; transition: 0.2s; }
        .btn-teal:hover { background: #006666 !important; }
        
        .status-pill { font-size: 0.65rem; font-weight: 800; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; display: inline-block; letter-spacing: 0.3px; }
        .badge-pending { background: #fee2e2 !important; color: #ef4444 !important; }
        .badge-progress { background: #fef3c7 !important; color: #d97706 !important; }
        .badge-resolved { background: #dcfce7 !important; color: #16a34a !important; }
        
        .priority-badge { font-size: 0.65rem; padding: 4px 12px; border-radius: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.3px; display: inline-block; }
        .img-proof { width: 100%; height: 165px; object-fit: cover; border-radius: 16px; cursor: pointer; border: 1px solid var(--border-color); transition: 0.2s; }
        .img-proof:hover { opacity: 0.95; transform: scale(1.01); }

        /* MODAL INTERFACES STRUCTURES */
        .modal-content { background-color: var(--card-bg) !important; border-radius: 24px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .form-control, .form-select { background-color: var(--input-bg) !important; border: 1px solid var(--input-border) !important; color: var(--text-main) !important; border-radius: 12px; padding: 11px 16px; font-weight: 600; font-size: 0.92rem; }
        .form-control:focus, .form-select:focus { border-color: var(--teal-accent) !important; box-shadow: none !important; }

        #mobile-toggle { display: none; background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 45px; height: 45px; border-radius: 12px; align-items: center; justify-content: center; }
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
    <div class="sidebar-brand">
        <h5>MG'S APARTMENT</h5>
        <small>Property Management</small>
    </div>

    <div class="sidebar-content" id="sidebarMenu">
        <a href="admin_dashboard.php"><i class="fa fa-chart-pie"></i> Dashboard</a>
        <a href="admin_requests.php"><i class="fa fa-user-plus"></i> New Requests <?php if($pend_count > 0): ?><span class="badge rounded-pill bg-danger ms-auto"><?php echo $pend_count; ?></span><?php endif; ?></a>
        <a href="admin_lease_requests.php"><i class="fa fa-file-contract"></i> Lease Actions <?php if($lease_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $lease_pend_count; ?></span><?php endif; ?></a>
        <a href="billing.php"><i class="fa fa-file-invoice-dollar"></i> Billing &amp; Dues <?php if($pay_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $pay_pend_count; ?></span><?php endif; ?></a>
        <a href="admin_tenants.php"><i class="fa fa-users"></i> Tenant List</a>
        <a href="admin_units.php"><i class="fa fa-door-open"></i> Unit Management</a>

        <div class="mt-4 mb-2 px-3"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Services</small></div>
        
        <a class="nav-link" data-bs-toggle="collapse" data-bs-target="#collapseServices" style="cursor: pointer; background: rgba(0, 128, 128, 0.15); color: #2dd4bf; border-radius: 12px;">
            <i class="fa fa-screwdriver-wrench"></i> <span>Maintenance</span> <i class="fa fa-chevron-down ms-auto" style="font-size: 0.7rem;"></i>
        </a>
        <div id="collapseServices" class="collapse show">
            <div class="collapse-inner">
                <a href="manage_complaints.php" class="active" style="background: var(--teal-accent); color: white !important;">Complaints</a>
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
                <i class="fa fa-screwdriver-wrench"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Maintenance &amp; Repairs</h3>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button id="theme-toggle" title="Switch Theme">
                <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
            </button>
            <span class="badge bg-dark bg-opacity-75 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.72rem; letter-spacing:0.5px;">TOTAL RECORDS: <?php echo mysqli_num_rows($result); ?></span>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 fw-600 rounded-4"><i class="fa fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): 
                $status_pill = ($row['status'] == 'Pending') ? 'badge-pending' : (($row['status'] == 'In Progress') ? 'badge-progress' : 'badge-resolved');
                $priority_color = ($row['priority'] == 'High') ? 'danger' : (($row['priority'] == 'Medium') ? 'warning' : 'info');
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card complaint-card text-start">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-800 mb-0" style="font-size:1.05rem;"><?php echo htmlspecialchars($row['full_name']); ?></h6>
                                <small class="text-muted fw-700">Allocation: <strong class="text-teal" style="color:var(--teal-accent) !important;">Unit <?php echo htmlspecialchars($row['unit_no'] ?? 'TBA'); ?></strong></small>
                            </div>
                            <span class="status-pill <?php echo $status_pill; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <span class="priority-badge bg-<?php echo $priority_color; ?> bg-opacity-10 text-<?php echo $priority_color; ?>">
                                <?php echo htmlspecialchars($row['priority']); ?> Severity
                            </span>
                        </div>
                        
                        <div class="issue-details mb-3">
                            <small class="text-muted d-block mb-1.5 fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">REPORTED ISSUE SCOPE:</small>
                            <div class="fw-600 text-main" style="line-height:1.4;"><?php echo !empty($row['issue_details']) ? nl2br(htmlspecialchars($row['issue_details'])) : 'No explicit details provided.'; ?></div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block mb-1.5 fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">DATA PHOTO PROOF:</small>
                            <?php if(!empty($row['issue_image'])): ?>
                                <img src="uploads/maintenance/<?php echo $row['issue_image']; ?>" 
                                     class="img-proof shadow-sm" 
                                     data-bs-toggle="modal" 
                                     data-bs-target="#imgModal<?php echo $row['id']; ?>">
                            <?php else: ?>
                                <div class="text-center py-4 rounded-4 small text-muted font-weight-600" style="background: var(--input-bg); border: 1px dashed var(--border-color);">No image attachments logged.</div>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($row['admin_remarks'])): ?>
                            <div class="admin-note-box mb-3">
                                <small class="fw-800 d-block mb-1 text-uppercase" style="font-size:0.62rem; letter-spacing:0.3px;">ADMIN FEEDBACK REMARKS:</small>
                                <div class="fw-600"><?php echo htmlspecialchars($row['admin_remarks']); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top" style="border-color: var(--border-color) !important;">
                            <small class="text-muted fw-600" style="font-size: 0.7rem;"><i class="fa-regular fa-calendar-check me-1"></i> <?php echo date('M d, Y', strtotime($row['request_date'])); ?></small>
                            <button class="btn btn-sm btn-teal px-3 py-1.5 fw-bold" style="font-size:0.75rem; border-radius:8px;" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                Update Record
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="imgModal<?php echo $row['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content bg-transparent border-0 shadow-none">
                        <div class="modal-body p-0 text-center">
                            <img src="uploads/maintenance/<?php echo $row['issue_image']; ?>" class="img-fluid rounded-4 shadow-lg" style="max-height: 80vh;">
                            <br>
                            <button type="button" class="btn btn-dark rounded-circle mt-3" data-bs-dismiss="modal" style="width:40px; height:40px; padding:0; background:#111827;"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="manage_complaints.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0 px-4 pt-4">
                                <h5 class="modal-title fw-800 text-main">Maintenance Registry Update</h5>
                                <button type="button" class="btn-close" style="filter: var(--text-main);" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4 text-start">
                                <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="tenant_id" value="<?php echo $row['tenant_id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted" style="font-size:0.65rem; letter-spacing:0.5px;">OPERATIONAL REPAIR STATUS</label>
                                    <select name="status" class="form-select fw-bold">
                                        <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="In Progress" <?php if($row['status']=='In Progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="Resolved" <?php if($row['status']=='Resolved') echo 'selected'; ?>>Resolved</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted" style="font-size:0.65rem; letter-spacing:0.5px;">ADMIN TRANSACTION REMARKS</label>
                                    <textarea name="admin_note" class="form-control" rows="3" placeholder="Provide operational logs updates here..."><?php echo htmlspecialchars($row['admin_remarks'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-teal w-100 py-3 fw-bold" style="border-radius:14px;">SAVE RECORD DISPATCH</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="rounded-4 py-5" style="border: 1px dashed var(--border-color); background: var(--card-bg);">
                    <i class="fa fa-circle-check fa-3x text-teal-accent mb-3" style="color: var(--teal-accent);"></i>
                    <h5 class="text-muted fw-600">No active maintenance issues found logged. ✨</h5>
                </div>
            </div>
        <?php endif; ?>
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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>     