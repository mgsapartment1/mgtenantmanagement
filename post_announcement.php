<?php
include('db.php'); 
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

// --- DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM announcements WHERE id = '$id'");
    header("Location: post_announcement.php?msg=Deleted");
    exit();
}

// --- POST & UPDATE LOGIC ---
if (isset($_POST['save_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    
    if (!empty($_POST['announcement_id'])) {
        $id = mysqli_real_escape_string($conn, $_POST['announcement_id']);
        $query = "UPDATE announcements SET title='$title', content='$content', expiry_date='$expiry_date' WHERE id='$id'";
        $msg = "Updated";
    } else {
        $query = "INSERT INTO announcements (title, content, expiry_date, created_at) VALUES ('$title', '$content', '$expiry_date', NOW())";
        $msg = "Posted";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: post_announcement.php?msg=$msg");
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

$history = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MG's Apartment - Announcements</title>
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

        .btn-add-new { background: var(--teal-accent) !important; color: white !important; border-radius: 12px; padding: 10px 22px; font-weight: 700; border: none; font-size: 0.88rem; transition: 0.25s ease; }
        .btn-add-new:hover { background: #006666 !important; transform: translateY(-1px); }

        /* DATA RENDER COMPONENT PANELS */
        .card { border: 1px solid var(--border-color); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); background: var(--card-bg); overflow: hidden; }
        
        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; margin-bottom: 0; }
        .table thead th { background-color: var(--teal-accent) !important; color: #ffffff !important; font-size: 0.75rem; text-transform: uppercase; padding: 16px 20px; border: none; font-weight: 700; letter-spacing: 0.5px; }
        .table tbody td { padding: 16px 20px; border-bottom: 1px solid var(--border-color) !important; color: var(--text-main) !important; vertical-align: middle; }
        .table tbody tr:last-child td { border-bottom: none !important; }

        .status-pill { font-size: 0.65rem; font-weight: 800; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; display: inline-block; letter-spacing: 0.3px; }
        .bg-active { background: #dcfce7 !important; color: #166534 !important; }
        .bg-expired { background: #fee2e2 !important; color: #ef4444 !important; }

        .action-cell { white-space: nowrap; width: 100px; }
        .font-size-18 { font-size: 1.15rem; }

        /* MODAL STRUCTURE LAYERS */
        .modal-content { background-color: var(--card-bg) !important; border-radius: 24px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .modal-header { border-bottom: 1px solid var(--border-color) !important; }
        .form-control { background-color: var(--input-bg) !important; border: 1px solid var(--input-border) !important; color: var(--text-main) !important; border-radius: 12px; padding: 11px 16px; font-weight: 600; font-size: 0.92rem; }
        .form-control:focus { border-color: var(--teal-accent) !important; box-shadow: none !important; background-color: var(--input-bg) !important; }

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
            .table tbody tr { border-bottom: 1px solid var(--border-color); display: block; padding: 8px 0; }
            .table tbody td:first-child { font-weight: bold; }
            .table tbody td:last-child { padding-bottom: 15px; }
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
                <a href="manage_complaints.php">Complaints</a>
                <a href="post_announcement.php" class="active" style="background: var(--teal-accent); color: white !important;">Announcements</a>
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
                <i class="fa fa-bullhorn"></i>
                <h3 class="mb-0 fw-800" style="font-size: 1.5rem;">Announcements</h3>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button id="theme-toggle" title="Switch Theme">
                <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
            </button>
            <button class="btn-add-new shadow-sm" onclick="openModal()">
                <i class="fa fa-plus-circle me-1"></i> New Announcement
            </button>
        </div>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'Deleted'): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4 fw-600 rounded-4"><i class="fa fa-trash me-2"></i> Announcement broadcast successfully removed from tenant screens.</div>
    <?php elseif(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 fw-600 rounded-4"><i class="fa fa-check-circle me-2"></i> Announcement broadcast successfully saved and deployed!</div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Topic</th>
                        <th>Posted</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th class="ps-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($history) > 0): while($row = mysqli_fetch_assoc($history)): 
                        $is_expired = (date('Y-m-d') > $row['expiry_date']);
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-main" style="font-size:0.95rem;"><?php echo htmlspecialchars($row['title']); ?></div>
                        </td>
                        <td><small class="fw-bold text-main"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small></td>
                        <td><small class="fw-bold text-muted"><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></small></td>
                        <td><span class="status-pill <?php echo $is_expired ? 'bg-expired' : 'bg-active'; ?>"><?php echo $is_expired ? 'Expired' : 'Active'; ?></span></td>
                        <td class="action-cell ps-3">
                            <button class="btn btn-sm text-primary p-0 me-3 shadow-none btn-action-pc" onclick='openModal(<?php echo json_encode($row); ?>, true)'>
                                <i class="fa <?php echo $is_expired ? 'fa-eye' : 'fa-edit'; ?> font-size-18"></i>
                            </button>
                            <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm text-danger p-0 shadow-none btn-action-pc" onclick="return confirm('Delete this post broadcast record?')">
                                <i class="fa fa-trash-can font-size-18"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted fw-bold">No registered announcements catalog logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-800 text-main" id="modalTitle">New Announcement</h5>
                <button type="button" class="btn-close" style="filter: var(--text-main);" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="announcementForm">
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="announcement_id" id="announcement_id">
                    <div class="mb-3">
                        <label class="fw-bold small text-muted text-uppercase mb-1.5" style="font-size:0.65rem; letter-spacing:0.5px;">Broadcast Title</label>
                        <input type="text" name="title" id="form_title" class="form-control" required placeholder="Provide notice header...">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-muted text-uppercase mb-1.5" style="font-size:0.65rem; letter-spacing:0.5px;">Content Body</label>
                        <textarea name="content" id="form_content" class="form-control" rows="5" required placeholder="Write breakdown description logs here..."></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold small text-muted text-uppercase mb-1.5" style="font-size:0.65rem; letter-spacing:0.5px;">Expiration Date Visibility</label>
                        <input type="date" name="expiry_date" id="form_expiry" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0" id="modalFooter">
                    <button type="submit" name="save_announcement" class="btn btn-teal w-100 py-3 fw-bold" style="border-radius:14px;">SAVE RECORD DISPATCH</button>
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

function openModal(data = null, isView = false) {
    const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
    const title = document.getElementById('modalTitle');
    const footer = document.getElementById('modalFooter');
    const form = document.getElementById('announcementForm');
    
    form.reset();
    document.getElementById('announcement_id').value = "";
    
    const fields = ['form_title', 'form_content', 'form_expiry'];
    
    if (data) {
        const expired = new Date() > new Date(data.expiry_date);
        title.innerText = expired ? "View Announcement Details" : "Edit Announcement Content";
        
        document.getElementById('announcement_id').value = data.id;
        document.getElementById('form_title').value = data.title;
        document.getElementById('form_content').value = data.content;
        document.getElementById('form_expiry').value = data.expiry_date;
        
        fields.forEach(f => document.getElementById(f).disabled = expired);
        footer.style.display = expired ? "none" : "block";
    } else {
        title.innerText = "Register New Broadcast Notice";
        fields.forEach(f => document.getElementById(f).disabled = false);
        footer.style.display = "block";
    }
    modal.show();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>