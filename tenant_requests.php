<?php
include('db.php');
session_start();

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit();
}

mysqli_set_charset($conn, "utf8mb4");
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// --- SMART MULTI-TENANT FALLBACK ---
if (!isset($_SESSION['property_id'])) {
    $user_p_q = mysqli_query($conn, "SELECT property_id FROM users WHERE id = '$user_id'");
    $user_p_row = mysqli_fetch_assoc($user_p_q);
    $_SESSION['property_id'] = $user_p_row['property_id'] ?? null;
}
$property_id = $_SESSION['property_id'];

// --- THEME LOGIC ---
if (!isset($_SESSION['theme'])) {
    $theme_q = mysqli_query($conn, "SELECT theme_preference FROM users WHERE id = '$user_id'");
    $theme_row = mysqli_fetch_assoc($theme_q);
    $_SESSION['theme'] = $theme_row['theme_preference'] ?? 'light';
}
$current_theme = $_SESSION['theme'];

// Kunin ang profile pic para sa sidebar
$u_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($u_query);
$pic = (!empty($user_data['profile_pic'])) ? 'uploads/' . $user_data['profile_pic'] : 'uploads/default.png';

// Get House Rules from settings securely tied to property context
$rules_sql = "SELECT setting_value FROM settings WHERE setting_key = 'house_rules'";
if (!empty($property_id)) { $rules_sql .= " AND property_id = '$property_id'"; }
$settings_q = mysqli_query($conn, $rules_sql);
if (mysqli_num_rows($settings_q) == 0 && !empty($property_id)) {
    $settings_q = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'house_rules' LIMIT 1");
}
$house_rules = (mysqli_num_rows($settings_q) > 0) ? mysqli_fetch_assoc($settings_q)['setting_value'] : "Standard House Rules Apply.";

// Kunin ang maintenance requests (Multi-Tenant Secured)
$query = "SELECT * FROM maintenance WHERE user_id = '$user_id'";
if (!empty($property_id)) { $query .= " AND property_id = '$property_id'"; }
$query .= " ORDER BY request_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Maintenance History - MG's Apartment</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-bg: #111827;
            --teal-accent: #008080;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --table-border: #f1f5f9;
            --input-border: #e2e8f0;
            --input-bg: #f8fafc;
        }

        [data-theme="dark"] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --table-border: #334155;
            --input-border: #475569;
            --input-bg: #111827;
        }

        body { 
            background-color: var(--bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-main);
            transition: 0.3s ease;
            margin: 0;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .text-main, .title-text h3 { color: var(--text-main) !important; }
        .text-muted, .rent-label { color: var(--text-muted) !important; }

        /* SIDEBAR PANEL LAYOUT */
        .sidebar { height: 100vh; background: var(--sidebar-bg); position: fixed; width: 260px; z-index: 1200; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); top: 0; left: 0; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-brand h5 { color: #fff !important; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 1px; }
        .sidebar-brand small { color: var(--teal-accent) !important; font-weight: 800; }
        .sidebar-links a { color: #9ca3af; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; font-weight: 600; border-radius: 12px; margin: 0 15px 5px 15px; }
        .sidebar-links a:hover, .sidebar-links a.active { color: #fff; background: var(--teal-accent); }

        .main-content { margin-left: 260px; padding: 30px; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); min-height: 100vh; }

        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* Card & Table Component */
        .custom-card { background: var(--card-bg); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid var(--table-border); overflow: hidden; padding: 25px; }
        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; }
        .table thead th { background-color: var(--teal-accent) !important; color: white !important; font-size: 0.75rem; text-transform: uppercase; padding: 15px; border: none; font-weight: 700; }
        .table tbody td { border-bottom: 1px solid var(--table-border) !important; color: var(--text-main) !important; padding: 18px 10px; vertical-align: middle; }

        /* Admin Remarks Alert Frame */
        .remark-box { background-color: rgba(0, 128, 128, 0.08); border-left: 4px solid var(--teal-accent); padding: 12px; border-radius: 12px; margin-top: 5px; }
        .reply-label { color: var(--teal-accent); font-weight: 800; font-size: 0.65rem; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .remark-text { color: var(--teal-accent) !important; font-weight: 700; font-size: 0.85rem; }
        [data-theme="dark"] .remark-text { color: #00d1d1 !important; }

        /* Status & Badges Styling */
        .badge-status { padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.65rem; text-transform: uppercase; display: inline-block; }
        .status-resolved { background: #dcfce7 !important; color: #16a34a !important; }
        .status-inprogress { background: #dbeafe !important; color: #2563eb !important; }
        .status-pending { background: #fee2e2 !important; color: #dc2626 !important; }

        .priority-badge { font-size: 0.6rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; margin-bottom: 5px; display: inline-block; }
        .prio-high { background: #fef2f2 !important; color: #991b1b !important; border: 1px solid #fecaca; }
        .prio-medium { background: #fffbeb !important; color: #92400e !important; border: 1px solid #fec3c7; }
        .prio-low { background: #f0fdf4 !important; color: #166534 !important; border: 1px solid #bbf7d0; }

        .issue-img { width: 55px; height: 55px; object-fit: cover; border-radius: 12px; border: 2px solid var(--table-border); transition: 0.2s; background: var(--input-bg); }
        .issue-img:hover { transform: scale(1.1); }

        /* ULTRA-MODERN PURE INTERCEPTOR GRID SYSTEM */
        .rules-wrapper-cleanslate {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        .rules-block-element {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            padding: 22px;
            border-radius: 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.01);
            display: flex;
            flex-direction: column;
            transition: 0.25s ease-in-out;
        }
        .rules-block-element:hover {
            border-color: var(--teal-accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 128, 128, 0.05);
        }
        .card-rule-title {
            display: block;
            font-size: 0.88rem;
            font-weight: 800;
            color: var(--teal-accent) !important;
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: 0.6px;
            border-bottom: 1px dashed var(--input-border);
            padding-bottom: 8px;
        }
        .card-rule-body {
            color: var(--text-main);
            font-size: 0.82rem;
            font-weight: 600;
        }
        .card-rule-body p { margin-bottom: 12px; margin-top: 0; line-height: 1.5; }
        .card-rule-body p:last-child { margin-bottom: 0; }
        .card-rule-body strong { color: var(--teal-accent) !important; font-weight: 700; }
        [data-theme="dark"] .card-rule-body strong { color: #00b3b3 !important; }

        @media (max-width: 1100px) { .rules-wrapper-cleanslate { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 991px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 20px; } }
        @media (max-width: 768px) { .rules-wrapper-cleanslate { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebarView()"></div>

<div class="sidebar shadow-lg" id="sidebar">
    <div class="sidebar-brand">
        <h5>MG'S APARTMENT</h5>
        <small>TENANT PORTAL</small>
    </div>
    <div class="sidebar-links mt-3">
        <a href="tenant_dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
        <a href="profile_edit.php"><i class="fa fa-user-circle"></i> My Profile</a>
        <a href="tenant_bills.php"><i class="fa fa-wallet"></i> My Bills</a>
        <a href="tenant_requests.php" class="active"><i class="fa fa-tools"></i> Maintenance</a>
        <a href="#" id="houseRulesLink" data-bs-toggle="modal" data-bs-target="#rulesModal"><i class="fa fa-book"></i> House Rules</a>
        <a href="logout.php" class="text-danger mt-5"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <button class="btn d-lg-none p-0 border-0" onclick="toggleSidebarView()">
                <i class="fa fa-bars-staggered fs-3 text-main"></i>
            </button>
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box"><i class="fa fa-tools"></i></div>
                <div class="title-text">
                    <h3 class="fw-800 mb-0" style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.3px;">Maintenance History</h3>
                    <p class="small mb-0 text-muted">Monitor and track your reported issues.</p>
                </div>
            </div>
        </div>
        <button id="themeToggle" class="btn btn-light rounded-circle shadow-sm" style="background: var(--card-bg); border: 1px solid var(--input-border); color: var(--text-main); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i></button>
    </div>

    <div class="custom-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 40%;">Description &amp; Photo</th>
                        <th class="text-center" style="width: 15%;">Status</th>
                        <th style="width: 30%;">Admin Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): 
                            $status = $row['status'] ?? 'Pending';
                            $st_lower = strtolower($status);
                            $status_class = ($st_lower == 'pending') ? 'status-pending' : (($st_lower == 'in progress') ? 'status-inprogress' : 'status-resolved');
                            $prio = $row['priority'] ?? 'Low';
                            $prio_lower = strtolower($prio);
                            $prio_class = ($prio_lower == 'high' || $prio_lower == 'urgent') ? 'prio-high' : (($prio_lower == 'medium') ? 'prio-medium' : 'prio-low');
                            $img_path = $row['issue_image'];
                        ?>
                        <tr>
                            <td class="small fw-800 text-muted">
                                <?php echo date('M d, Y', strtotime($row['request_date'])); ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if(!empty($img_path)): ?>
                                        <a href="uploads/maintenance/<?php echo $img_path; ?>" target="_blank">
                                            <img src="uploads/maintenance/<?php echo $img_path; ?>" class="issue-img shadow-sm">
                                        </a>
                                    <?php endif; ?>
                                    <div class="text-start">
                                        <span class="priority-badge <?php echo $prio_class; ?>"><?php echo $prio; ?></span>
                                        <div class="fw-700 small text-main"><?php echo htmlspecialchars($row['issue_details']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php echo strtoupper($status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if(!empty($row['admin_remarks'])): ?>
                                    <div class="remark-box">
                                        <span class="reply-label">Admin Feedback:</span>
                                        <span class="remark-text">"<?php echo htmlspecialchars($row['admin_remarks']); ?>"</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small fw-600 italic"><i class="fa fa-clock-rotate-left me-1"></i> Waiting for response...</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted fw-600">No maintenance requests found. ✨</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="rulesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 980px;">
        <div class="modal-content rounded-4 shadow-lg border-0" style="background: var(--card-bg);">
            <div class="modal-header border-0 rounded-top-4" style="background: var(--card-bg); padding: 22px 26px 12px 26px;">
                <h5 class="fw-800 mb-0 text-main"><i class="fa fa-book-bookmark me-2" style="color: var(--teal-accent);"></i>House Rules &amp; Regulations</h5>
                <button type="button" class="btn-close" style="filter: var(--text-main);" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <div class="col-12 mb-4 p-3 rounded-4 text-center fw-bold text-uppercase" style="background: rgba(0, 128, 128, 0.05); color: var(--teal-accent); border: 1px dashed var(--teal-accent); letter-spacing: 1px; font-size: 1.05rem;">
                    🏢 OFFICIAL BUILDING POLICIES &amp; CODES
                </div>
                
                <div class="rules-wrapper-cleanslate">
                    <?php 
                    $doc = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $house_rules);
                    libxml_clear_errors();

                    $headings = $doc->getElementsByTagName('h3');
                    
                    if ($headings->length > 0) {
                        foreach ($headings as $heading) {
                            $title = trim($heading->nodeValue);
                            $body_html = "";
                            
                            $next_node = $heading->nextSibling;
                            while ($next_node && $next_node->nodeName !== 'h3') {
                                if ($next_node->nodeType === XML_ELEMENT_NODE) {
                                    $body_html .= $doc->saveHTML($next_node);
                                } elseif ($next_node->nodeType === XML_TEXT_NODE && trim($next_node->nodeValue) !== '') {
                                    $body_html .= '<p>' . htmlspecialchars($next_node->nodeValue) . '</p>';
                                }
                                $next_node = $next_node->nextSibling;
                            }
                            
                            echo '<div class="rules-block-element shadow-sm">';
                            echo '<span class="card-rule-title">' . htmlspecialchars($title) . '</span>';
                            echo '<div class="card-rule-body">' . $body_html . '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="col-12 text-muted">' . nl2br($house_rules) . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebarView() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    const themeToggle = document.getElementById('themeToggle');
    themeToggle.onclick = () => {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        themeToggle.querySelector('i').className = next === 'light' ? 'fa fa-moon' : 'fa fa-sun';
        
        let formData = new FormData();
        formData.append('theme', next);
        fetch('update_theme.php', { method: 'POST', body: formData });
    };

    document.getElementById('houseRulesLink').onclick = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    };
</script>
</body>
</html>