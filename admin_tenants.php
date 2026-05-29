<?php
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

// QUERY 1: Active Tenants
$active_sql = "SELECT u.*, r.status as rent_status, un.unit_no,
                (SELECT COUNT(*) FROM bills WHERE user_id = u.id AND status = 'Unpaid') as unpaid_count 
               FROM users u 
               INNER JOIN rent_requests r ON u.id = r.user_id 
               INNER JOIN units un ON r.unit_id = un.id 
               WHERE u.role = 'tenant' AND r.status = 'Approved'
               ORDER BY un.unit_no ASC";
$active_res = mysqli_query($conn, $active_sql);

// QUERY 2: Pending Requests
$pending_sql = "SELECT u.*, r.status as rent_status, un.unit_no 
                 FROM users u 
                 INNER JOIN rent_requests r ON u.id = r.user_id 
                 LEFT JOIN units un ON r.unit_id = un.id 
                 WHERE u.role = 'tenant' AND r.status = 'Pending'";
$pending_res = mysqli_query($conn, $pending_sql);

// QUERY 3: Rejected / Inactive
$rejected_sql = "SELECT u.*, r.status as rent_status 
                  FROM users u 
                  LEFT JOIN rent_requests r ON u.id = r.user_id 
                  WHERE u.role = 'tenant' AND (r.status = 'Rejected' OR r.id IS NULL)";
$rejected_res = mysqli_query($conn, $rejected_sql);

// Global Notif Counts for badge notification alignment
$res_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM rent_requests WHERE status = 'Pending'");
$pend_count = mysqli_fetch_assoc($res_pend)['total'] ?? 0;

$res_lease = mysqli_query($conn, "SELECT COUNT(*) as total FROM lease_requests WHERE status = 'Pending'");
$lease_pend_count = mysqli_fetch_assoc($res_lease)['total'] ?? 0;

$res_pay_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Pending Approval' OR (status = 'Pending' AND proof_of_payment IS NOT NULL)");
$pay_pend_count = mysqli_fetch_assoc($res_pay_pend)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tenant Directory - MG's Apartment</title>
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
            --tab-inactive: #f1f5f9;
        }
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #111827;
            --tab-inactive: #111827;
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

        /* PREMIUM DIRECTORY RENDER BOX */
        .directory-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); padding: 25px; }

        /* DYNAMIC APP TAB PILLS */
        .nav-pills { display: flex; flex-wrap: wrap; gap: 8px; padding: 0; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; }
        .nav-pills .nav-item { flex: 1; min-width: 110px; }
        .nav-pills .nav-link { color: var(--text-muted); font-weight: 700; padding: 12px 14px; border-radius: 14px; background: var(--tab-inactive); font-size: 0.82rem; width: 100%; text-align: center; border: 1px solid var(--border-color); transition: 0.2s ease; }
        .nav-pills .nav-link.active { background-color: var(--teal-accent) !important; color: white !important; border-color: transparent; }

        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; margin-bottom: 0; }
        .table thead th { background-color: var(--input-bg) !important; color: var(--text-muted) !important; font-size: 0.75rem; text-transform: uppercase; padding: 14px 18px; border-bottom: 1px solid var(--border-color) !important; font-weight: 700; letter-spacing: 0.5px; }
        .table tbody td { padding: 18px 18px; border-bottom: 1px solid var(--border-color) !important; color: var(--text-main) !important; vertical-align: middle; }
        .table tbody tr:last-child td { border-bottom: none !important; }

        .tenant-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); }
        .unit-badge { background: rgba(0, 128, 128, 0.08); color: var(--teal-accent); font-weight: 800; padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; letter-spacing: 0.3px; display: inline-block; }
        .payer-badge { font-size: 0.65rem; padding: 6px 14px; border-radius: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .btn-action-pc { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); color: var(--text-muted); transition: 0.2s ease; background: var(--input-bg); }
        .btn-action-pc:hover { transform: scale(1.05); border-color: var(--teal-accent); color: var(--teal-accent); }
        .btn-action-pc.text-danger:hover { border-color: #dc2626; color: #dc2626; }

        .btn-teal { background: var(--teal-accent) !important; color: white !important; border-radius: 50px; font-weight: 700; padding: 10px 24px; border: none; transition: 0.2s; }
        .btn-teal:hover { background: #006666 !important; transform: translateY(-1px); }

        /* RESPONSIVE LAYOUT MECHANICS */
        .mobile-cards { display: none; }
        #mobile-toggle { display: none; background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 45px; height: 45px; border-radius: 12px; align-items: center; justify-content: center; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            #mobile-toggle { display: flex; }
            .desktop-table { display: none; }
            .mobile-cards { display: block; }
            .tenant-mobile-card { background: var(--card-bg); border-radius: 24px; padding: 20px; margin-bottom: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.01); }
            .mobile-actions { display: flex; gap: 10px; margin-top: 15px; }
            .btn-big-mobile { flex: 1; padding: 12px; border-radius: 14px; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--border-color); text-decoration: none; background: var(--input-bg); }
        }
        @media (min-width: 992px) {
            .nav-pills .nav-item { flex: none; }
            .nav-pills .nav-link { min-width: 160px; }
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
        <a href="admin_lease_requests.php"><i class="fa fa-file-contract"></i> Lease Actions <?php if($lease_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $lease_pend_count; ?></span><?php endif; ?></a>
        <a href="billing.php"><i class="fa fa-file-invoice-dollar"></i> Billing &amp; Dues <?php if($pay_pend_count > 0): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo $pay_pend_count; ?></span><?php endif; ?></a>
        <a href="admin_tenants.php" class="active"><i class="fa fa-users"></i> Tenant List</a>
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
                <i class="fa fa-users"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Tenant Directory</h3>
            </div>
        </div>
        <button id="theme-toggle" title="Switch Theme">
            <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
    </div>

    <div class="directory-card">
        <ul class="nav nav-pills" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#active-tab" type="button">
                    Active (<?php echo mysqli_num_rows($active_res); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pending-tab" type="button">
                    Pending (<?php echo mysqli_num_rows($pending_res); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#rejected-tab" type="button">
                    Rejected
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="active-tab" role="tabpanel">
                <div class="table-responsive desktop-table">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">Tenant</th>
                                <th>Unit No</th>
                                <th>Contact Information</th>
                                <th class="text-center">Standing</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($active_res, 0); while($row = mysqli_fetch_assoc($active_res)): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center text-start">
                                        <img src="uploads/<?php echo $row['profile_pic'] ?: 'default.png'; ?>" class="tenant-img me-3">
                                        <div>
                                            <div class="fw-bold text-main" style="font-size: 0.92rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                            <small class="text-success fw-bold"><i class="fa fa-circle fs-6 me-1" style="font-size:0.5rem !important;"></i> Occupying</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="unit-badge">Unit <?php echo htmlspecialchars($row['unit_no']); ?></span></td>
                                <td class="text-start">
                                    <div class="small fw-bold text-main"><?php echo htmlspecialchars($row['contact']); ?></div>
                                    <div class="small text-muted fw-500"><?php echo htmlspecialchars($row['email']); ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if($row['unpaid_count'] == 0): ?>
                                        <span class="payer-badge bg-success bg-opacity-10 text-success border border-success border-opacity-20">GOOD PAYER</span>
                                    <?php else: ?>
                                        <span class="payer-badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20"><?php echo $row['unpaid_count']; ?> UNPAID</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="edit_tenant.php?id=<?php echo $row['id']; ?>" class="btn-action-pc text-primary me-1" title="Edit Profile"><i class="fa fa-edit"></i></a>
                                    <button class="btn-action-pc text-danger" title="Terminate Lease" onclick="confirmTermination(<?php echo $row['id']; ?>)"><i class="fa fa-user-slash"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-cards">
                    <?php mysqli_data_seek($active_res, 0); while($row = mysqli_fetch_assoc($active_res)): ?>
                    <div class="tenant-mobile-card text-start">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <img src="uploads/<?php echo $row['profile_pic'] ?: 'default.png'; ?>" class="tenant-img me-3">
                                <div>
                                    <div class="fw-800 h6 mb-1 text-main"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <span class="unit-badge">Unit <?php echo htmlspecialchars($row['unit_no']); ?></span>
                                </div>
                            </div>
                            <?php if($row['unpaid_count'] == 0): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1" style="font-size: 0.58rem; font-weight:800;">GOOD</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1" style="font-size: 0.58rem; font-weight:800;">ARREARS</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-3 rounded-3 mb-3" style="background: var(--input-bg); border: 1px solid var(--border-color);">
                            <div class="small mb-1 text-main fw-600"><i class="fa fa-phone text-teal me-2"></i> <?php echo htmlspecialchars($row['contact']); ?></div>
                            <div class="small text-muted fw-500"><i class="fa fa-envelope text-teal me-2"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                        </div>

                        <div class="mobile-actions">
                            <a href="edit_tenant.php?id=<?php echo $row['id']; ?>" class="btn-big-mobile text-primary fw-bold">
                                <i class="fa fa-edit"></i> Edit Profile
                            </a>
                            <button class="btn-big-mobile text-danger fw-bold" onclick="confirmTermination(<?php echo $row['id']; ?>)">
                                <i class="fa fa-user-slash"></i> End Lease
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="pending-tab" role="tabpanel">
                <div class="py-5 text-center rounded-4" style="background: var(--input-bg); border: 1px dashed var(--border-color);">
                    <i class="fa fa-user-clock fa-3x text-warning mb-3"></i>
                    <h5 class="fw-800 text-main">Review Admission Files</h5>
                    <p class="text-muted small px-4 fw-600">Mayroong <?php echo mysqli_num_rows($pending_res); ?> na aplikante ang naghihintay ng validation sa system network.</p>
                    <a href="admin_requests.php" class="btn btn-teal text-white fw-bold px-5 py-2.5 rounded-pill mt-2 shadow-sm">Open Requests Queue</a>
                </div>
            </div>

            <div class="tab-pane fade" id="rejected-tab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">Archived User</th>
                                <th>Contact Email</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($rejected_res) > 0): while($row = mysqli_fetch_assoc($rejected_res)): ?>
                            <tr>
                                <td class="ps-3 text-start">
                                    <div class="fw-bold text-main" style="font-size:0.9rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <small class="text-danger fw-bold">Rejected / Revoked</small>
                                </td>
                                <td class="text-start text-muted fw-500"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" style="font-size:0.75rem;">Clear Record</button>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted fw-600">No rejected directory files found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

    function confirmTermination(id) {
        if(confirm('Are you sure you want to terminate the lease of this tenant? The unit will become available again in the list.')) {
            window.location.href = 'terminate_logic.php?id=' + id;
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>