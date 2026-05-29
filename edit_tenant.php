<?php
include('db.php');
session_start();

// Security Check: Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_tenants.php");
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

$tenant_id = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT u.*, r.unit_id FROM users u 
          LEFT JOIN rent_requests r ON u.id = r.user_id 
          WHERE u.id = '$tenant_id' AND u.role = 'tenant'";
$result = mysqli_query($conn, $query);
$tenant = mysqli_fetch_assoc($result);

if (!$tenant) {
    header("Location: admin_tenants.php");
    exit();
}

$units_res = mysqli_query($conn, "SELECT * FROM units");

// Global Notif Counts para sa badge notification panel alignment
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
    <title>Edit Tenant - MG's Apartment</title>
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
            --input-border: #e2e8f0;
        }
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #111827;
            --input-border: #475569;
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

        /* BACK NAVIGATION BAR */
        .back-link { 
            text-decoration: none; 
            color: var(--teal-accent); 
            font-weight: 700; 
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            transition: 0.2s ease;
            letter-spacing: 0.5px;
        }
        .back-link:hover { color: #006666; transform: translateX(-4px); }

        /* EDIT DATA CONTROL RENDER BOX */
        .edit-card { 
            max-width: 680px; 
            margin: 10px auto 40px auto; 
            background: var(--card-bg); 
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
            border: 1px solid var(--border-color);
        }

        /* FLOATING BOX FOR INPUT WRAPPERS */
        .input-group-custom { 
            background: var(--input-bg); 
            border: 1px solid var(--input-border); 
            border-radius: 16px; 
            padding: 12px 18px; 
            margin-bottom: 18px; 
            transition: 0.2s ease; 
        }
        .input-group-custom:focus-within { border-color: var(--teal-accent); box-shadow: 0 0 0 1px var(--teal-accent); }
        .input-group-custom label { display: block; font-size: 0.68rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
        .input-group-custom input, .input-group-custom select { border: none !important; background: transparent !important; width: 100%; outline: none; font-weight: 600; color: var(--text-main); padding: 0; font-size: 0.95rem; }
        .input-group-custom select option { background: var(--card-bg); color: var(--text-main); }

        /* AVATAR SYSTEM DESIGN PREVIEW */
        .profile-container { position: relative; width: 120px; height: 120px; margin: 0 auto 30px auto; }
        .current-img { 
            width: 120px; height: 120px; 
            border-radius: 24px; 
            object-fit: cover; 
            border: 4px solid var(--card-bg);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            background: var(--card-bg);
        }
        .avatar-placeholder {
            width: 120px; height: 120px; 
            border-radius: 24px; 
            background: rgba(0, 128, 128, 0.1); 
            color: var(--teal-accent); 
            display: flex; align-items: center; justify-content: center;
            font-size: 2.8rem; font-weight: 800;
            border: 2px dashed var(--teal-accent);
        }

        .btn-teal { 
            background: var(--teal-accent) !important; 
            color: white !important; 
            font-weight: 800; 
            padding: 14px;
            border-radius: 16px;
            border: none;
            transition: 0.25s ease;
            font-size: 0.92rem;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.15);
        }
        .btn-teal:hover { background: #006666 !important; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0, 128, 128, 0.25); }

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
                <i class="fa fa-user-gear"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Tenant Profile Manager</h3>
            </div>
        </div>
        <button id="theme-toggle" title="Switch Theme">
            <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
    </div>

    <div class="edit-card">
        <a href="admin_tenants.php" class="back-link">
            <i class="fa fa-chevron-left me-2"></i> BACK TO TENANT DIRECTORY
        </a>
        
        <div class="text-center mb-4">
            <h4 class="fw-800 mb-1" style="font-size:1.35rem;">Edit Tenant Information</h4>
            <p class="text-muted small fw-600">Update system records of <strong class="text-teal" style="color:var(--teal-accent) !important;"><?php echo htmlspecialchars($tenant['full_name']); ?></strong></p>
        </div>

        <form action="update_tenant_logic.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">

            <div class="profile-container text-center">
                <?php if(!empty($tenant['profile_pic'])): ?>
                    <img src="uploads/<?php echo $tenant['profile_pic']; ?>" class="current-img">
                <?php else: ?>
                    <div class="avatar-placeholder"><span><?php echo strtoupper(substr($tenant['full_name'], 0, 1)); ?></span></div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="input-group-custom">
                        <label>Legal Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($tenant['full_name']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="input-group-custom">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($tenant['email']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="input-group-custom">
                        <label>Active Contact Number</label>
                        <input type="text" name="contact" value="<?php echo htmlspecialchars($tenant['contact']); ?>" required>
                    </div>
                </div>

                <div class="col-12">
                    <div class="input-group-custom">
                        <label>Assigned Apartment Unit</label>
                        <select name="unit_id">
                            <?php while($u = mysqli_fetch_assoc($units_res)): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo ($u['id'] == $tenant['unit_id']) ? 'selected' : ''; ?>>
                                    Unit <?php echo htmlspecialchars($u['unit_no']); ?> (Status: <?php echo htmlspecialchars($u['status']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-grid mt-2">
                <button type="submit" name="update_tenant" class="btn btn-teal">
                    <i class="fa fa-floppy-disk me-2"></i> UPDATE TENANT RECORD
                </button>
            </div>
        </form>
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