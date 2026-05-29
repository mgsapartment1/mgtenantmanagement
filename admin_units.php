<?php
include('db.php');
session_start();

// SECURITY: Admin only
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

// --- LOGIC: ADD NEW UNIT ---
if (isset($_POST['add_unit'])) {
    $unit_no = mysqli_real_escape_string($conn, $_POST['unit_no']);
    $rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $unit_type = mysqli_real_escape_string($conn, $_POST['unit_type']);
    $floor_area = mysqli_real_escape_string($conn, $_POST['floor_area']);
    $amenities = mysqli_real_escape_string($conn, $_POST['amenities']);
    $status = "Available";

    $img_name = $_FILES['unit_image']['name'];
    $tmp_name = $_FILES['unit_image']['tmp_name'];
    $target_dir = "units/";
    
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $final_img = time() . "_" . $img_name;
    
    if (move_uploaded_file($tmp_name, $target_dir . $final_img)) {
        $sql = "INSERT INTO units (unit_no, unit_type, floor_area, monthly_rent, description, amenities, status, image_name) 
                VALUES ('$unit_no', '$unit_type', '$floor_area', '$rent', '$desc', '$amenities', '$status', '$final_img')";
        
        if (mysqli_query($conn, $sql)) {
            $admin_name = $_SESSION['full_name'];
            mysqli_query($conn, "INSERT INTO audit_logs (admin_id, admin_name, action, affected_table) VALUES ('{$_SESSION['user_id']}', '$admin_name', 'Added Unit $unit_no', 'units')");
            header("Location: admin_units.php?msg=Unit $unit_no added successfully!");
            exit();
        }
    }
}

// --- LOGIC: UPDATE UNIT ---
if (isset($_POST['update_unit'])) {
    $id = $_POST['unit_id'];
    $unit_no = mysqli_real_escape_string($conn, $_POST['unit_no']);
    $rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $unit_type = mysqli_real_escape_string($conn, $_POST['unit_type']);
    $floor_area = mysqli_real_escape_string($conn, $_POST['floor_area']);
    $amenities = mysqli_real_escape_string($conn, $_POST['amenities']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update_sql = "UPDATE units SET unit_no='$unit_no', unit_type='$unit_type', floor_area='$floor_area', 
                   monthly_rent='$rent', description='$desc', amenities='$amenities', status='$status' WHERE id='$id'";
    
    if (!empty($_FILES['unit_image']['name'])) {
        $img_name = time() . "_" . $_FILES['unit_image']['name'];
        move_uploaded_file($_FILES['unit_image']['tmp_name'], "units/" . $img_name);
        mysqli_query($conn, "UPDATE units SET image_name='$img_name' WHERE id='$id'");
    }

    if (mysqli_query($conn, $update_sql)) {
        $admin_name = $_SESSION['full_name'];
        mysqli_query($conn, "INSERT INTO audit_logs (admin_id, admin_name, action, affected_table) VALUES ('{$_SESSION['user_id']}', '$admin_name', 'Updated Unit $unit_no', 'units')");
        header("Location: admin_units.php?msg=Unit $unit_no updated successfully!");
        exit();
    }
}

// Global Notif Counts para sa badge notification alignment
$res_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM rent_requests WHERE status = 'Pending'");
$pend_count = mysqli_fetch_assoc($res_pend)['total'] ?? 0;

$res_lease = mysqli_query($conn, "SELECT COUNT(*) as total FROM lease_requests WHERE status = 'Pending'");
$lease_pend_count = mysqli_fetch_assoc($res_lease)['total'] ?? 0;

$res_pay_pend = mysqli_query($conn, "SELECT COUNT(*) as total FROM bills WHERE status = 'Pending Approval' OR (status = 'Pending' AND proof_of_payment IS NOT NULL)");
$pay_pend_count = mysqli_fetch_assoc($res_pay_pend)['total'] ?? 0;

// --- LOGIC: SEARCH & FILTER ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter = isset($_GET['status_filter']) ? mysqli_real_escape_string($conn, $_GET['status_filter']) : '';

$query = "SELECT * FROM units WHERE status != 'Archived' AND (unit_no LIKE '%$search%' OR unit_type LIKE '%$search%')";
if ($filter != '') { $query .= " AND status = '$filter'"; }
$query .= " ORDER BY unit_no ASC";
$units = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manage Units - MG's Apartment</title>
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

        .btn-teal { background: var(--teal-accent) !important; color: white !important; border: none; font-weight: 700; border-radius: 12px; transition: 0.25s ease; }
        .btn-teal:hover { background: #006666 !important; transform: translateY(-1px); }

        /* STATUS BADGES SPECIFICATION */
        .status-pill { font-size: 0.65rem; font-weight: 800; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; display: inline-block; letter-spacing: 0.3px; }
        .badge-available { background: #dcfce7 !important; color: #16a34a !important; }
        .badge-occupied { background: #fee2e2 !important; color: #dc2626 !important; }
        .badge-maintenance { background: #fef9c3 !important; color: #a16207 !important; }

        .filter-panel-box { border: 1px solid var(--border-color); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); background: var(--card-bg); }
        .desktop-table { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); overflow: hidden; }

        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; margin-bottom: 0; }
        .table thead th { background-color: var(--input-bg) !important; color: var(--text-muted) !important; font-size: 0.75rem; text-transform: uppercase; padding: 14px 20px; border-bottom: 1px solid var(--border-color) !important; font-weight: 700; letter-spacing: 0.5px; }
        .table tbody td { padding: 16px 20px; border-bottom: 1px solid var(--border-color) !important; color: var(--text-main) !important; vertical-align: middle; }
        .table tbody tr:last-child td { border-bottom: none !important; }

        .unit-img-table { width: 55px; height: 45px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border-color); }

        /* MODAL STRUCTURES CONFIGS */
        .modal-content { background-color: var(--card-bg) !important; border-radius: 24px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .modal-header-teal { background: var(--teal-accent); color: white; padding: 22px 26px; display: flex; align-items: center; }
        .form-control, .form-select { background-color: var(--input-bg) !important; border: 1px solid var(--input-border) !important; color: var(--text-main) !important; border-radius: 12px; padding: 11px 16px; font-weight: 600; font-size: 0.92rem; }
        .form-control:focus, .form-select:focus { border-color: var(--teal-accent) !important; box-shadow: none !important; }

        /* MOBILE RENDER ENGINE */
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
            .unit-card { background: var(--card-bg); border-radius: 24px; padding: 20px; margin-bottom: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.01); }
            .unit-img-mobile { width: 100%; height: 185px; object-fit: cover; border-radius: 18px; margin-bottom: 15px; border: 1px solid var(--border-color); }
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
        <a href="admin_units.php" class="active"><i class="fa fa-door-open"></i> Unit Management</a>

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
                <i class="fa fa-door-open"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Unit Inventory</h3>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button id="theme-toggle" title="Switch Theme" class="me-1">
                <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
            </button>
            <button class="btn btn-teal shadow-sm px-4 py-2 fw-bold" style="border-radius:12px;" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                <i class="fa fa-plus me-1"></i> Add Unit
            </button>
        </div>
    </div>

    <div class="filter-panel-box p-3 mb-4">
        <form action="" method="GET" class="row g-2">
            <div class="col-12 col-md-6">
                <div class="input-group rounded-3" style="background: var(--input-bg); border: 1px solid var(--border-color);">
                    <span class="input-group-text bg-transparent border-0"><i class="fa fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-transparent border-0 text-main" placeholder="Search Unit No..." value="<?php echo htmlspecialchars($search); ?>" style="box-shadow:none;">
                </div>
            </div>
            <div class="col-12 col-md-6">
                <select name="status_filter" class="form-select text-main fw-bold" onchange="this.form.submit()">
                    <option value="">All Status Records</option>
                    <option value="Available" <?php if($filter=='Available') echo 'selected'; ?>>Available</option>
                    <option value="Occupied" <?php if($filter=='Occupied') echo 'selected'; ?>>Occupied</option>
                    <option value="Maintenance" <?php if($filter=='Maintenance') echo 'selected'; ?>>Maintenance</option>
                </select>
            </div>
        </form>
    </div>

    <div class="desktop-table">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4 py-3">Unit Info</th>
                    <th>Type &amp; Floor Area</th>
                    <th>Rent Rate</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php mysqli_data_seek($units, 0); while($row = mysqli_fetch_assoc($units)): 
                    $status_cls = ($row['status'] == 'Available') ? 'badge-available' : (($row['status'] == 'Occupied') ? 'badge-occupied' : 'badge-maintenance');
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <img src="units/<?php echo $row['image_name']; ?>" class="unit-img-table me-3" onerror="this.src='https://via.placeholder.com/60?text=Unit'">
                            <span class="fw-bold text-main" style="font-size:0.95rem;"><?php echo htmlspecialchars($row['unit_no']); ?></span>
                        </div>
                    </td>
                    <td><small class="fw-bold text-main"><?php echo htmlspecialchars($row['unit_type']); ?> (<?php echo htmlspecialchars($row['floor_area']); ?> sqm)</small></td>
                    <td class="fw-bold text-teal">₱<?php echo number_format($row['monthly_rent'], 2); ?></td>
                    <td><span class="status-pill <?php echo $status_cls; ?>"><?php echo $row['status']; ?></span></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-light border p-2 me-1" style="border-radius:8px; background:var(--input-bg); border-color:var(--border-color) !important;" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>"><i class="fa fa-pencil-alt text-primary"></i></button>
                        <a href="delete_unit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-light border p-2" style="border-radius:8px; background:var(--input-bg); border-color:var(--border-color) !important;" onclick="return confirm('Archive unit configuration?')"><i class="fa fa-archive text-danger"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="mobile-cards">
        <?php mysqli_data_seek($units, 0); while($row = mysqli_fetch_assoc($units)): 
            $status_cls = ($row['status'] == 'Available') ? 'badge-available' : (($row['status'] == 'Occupied') ? 'badge-occupied' : 'badge-maintenance');
        ?>
        <div class="unit-card text-start">
            <img src="units/<?php echo $row['image_name']; ?>" class="unit-img-mobile" onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h5 class="fw-800 mb-0"><?php echo htmlspecialchars($row['unit_no']); ?></h5>
                    <small class="text-muted fw-bold"><?php echo htmlspecialchars($row['unit_type']); ?> • <?php echo htmlspecialchars($row['floor_area']); ?> sqm</small>
                </div>
                <span class="status-pill <?php echo $status_cls; ?>"><?php echo $row['status']; ?></span>
            </div>
            <div class="h5 fw-800 text-teal mb-3">₱<?php echo number_format($row['monthly_rent'], 0); ?>/mo</div>
            <div class="d-flex gap-2">
                <button class="btn btn-light border w-100 fw-bold py-2.5" style="border-radius:12px; background:var(--input-bg); border-color:var(--border-color) !important; color:var(--text-main);" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>"><i class="fa fa-edit me-2 text-primary"></i>Edit</button>
                <a href="delete_unit.php?id=<?php echo $row['id']; ?>" class="btn btn-light border text-danger w-100 fw-bold py-2.5 d-flex align-items-center justify-content-center" style="border-radius:12px; background:var(--input-bg); border-color:var(--border-color) !important; text-decoration:none;" onclick="return confirm('Archive unit configuration?')"><i class="fa fa-archive me-2"></i>Archive</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php mysqli_data_seek($units, 0); while($row = mysqli_fetch_assoc($units)): ?>
<div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header modal-header-teal">
                    <h5 class="fw-bold mb-0 text-white">Update Unit Details</h5>
                    <button type="button" class="btn-close btn-close-white" style="filter:invert(1);" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="unit_id" value="<?php echo $row['id']; ?>">
                    <div class="row g-3">
                        <div class="col-md-4 text-center">
                            <img src="units/<?php echo $row['image_name']; ?>" class="img-fluid rounded-4 mb-3 border" onerror="this.src='https://via.placeholder.com/150'" style="max-height:160px; object-fit:cover; width:100%;">
                            <input type="file" name="unit_image" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-6"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Unit No.</label><input type="text" name="unit_no" class="form-control" value="<?php echo htmlspecialchars($row['unit_no']); ?>" required></div>
                                <div class="col-6"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Rent (₱)</label><input type="number" name="monthly_rent" class="form-control" value="<?php echo $row['monthly_rent']; ?>" required></div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Available" <?php if($row['status']=='Available') echo 'selected'; ?>>Available</option>
                                        <option value="Occupied" <?php if($row['status']=='Occupied') echo 'selected'; ?>>Occupied</option>
                                        <option value="Maintenance" <?php if($row['status']=='Maintenance') echo 'selected'; ?>>Maintenance</option>
                                    </select>
                                </div>
                                <div class="col-6"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Type</label><select name="unit_type" class="form-select"><option value="Studio" <?php if($row['unit_type']=='Studio') echo 'selected'; ?>>Studio</option><option value="1BR" <?php if($row['unit_type']=='1BR') echo 'selected'; ?>>1BR</option><option value="2BR" <?php if($row['unit_type']=='2BR') echo 'selected'; ?>>2BR</option></select></div>
                                <div class="col-6"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Floor Area (sqm)</label><input type="text" name="floor_area" class="form-control" value="<?php echo htmlspecialchars($row['floor_area']); ?>"></div>
                                <div class="col-6"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Amenities</label><input type="text" name="amenities" class="form-control" value="<?php echo htmlspecialchars($row['amenities']); ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" name="update_unit" class="btn btn-teal w-100 py-3 fw-bold" style="border-radius:14px;">UPDATE LEASE CONFIGURATION</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endwhile; ?>

<div class="modal fade" id="addUnitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header modal-header-teal">
                    <h5 class="fw-bold mb-0 text-white">Register New Apartment Unit</h5>
                    <button type="button" class="btn-close btn-close-white" style="filter:invert(1);" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Unit Number</label><input type="text" name="unit_no" class="form-control" placeholder="e.g. B203" required></div>
                        <div class="col-md-6"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Monthly Rent</label><input type="number" name="monthly_rent" class="form-control" placeholder="₱ 0.00" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Type</label><select name="unit_type" class="form-select"><option>Studio</option><option>1BR</option><option>2BR</option></select></div>
                        <div class="col-md-4"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Floor Area (sqm)</label><input type="text" name="floor_area" class="form-control" placeholder="e.g. 30"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Photo File</label><input type="file" name="unit_image" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Amenities Listing</label><input type="text" name="amenities" class="form-control" placeholder="Free WiFi, Aircon, Balcony, etc."></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted text-uppercase" style="font-size:0.65rem;">Unit Description</label><textarea name="description" class="form-control" rows="2" placeholder="Provide breakdown context data..."></textarea></div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" name="add_unit" class="btn btn-teal w-100 py-3 fw-bold" style="border-radius:14px;">SAVE RECORD TO INVENTORY</button>
                </div>
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