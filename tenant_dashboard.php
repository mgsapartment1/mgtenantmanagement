<?php
include('db.php');
session_start();

// Security: Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit();
}

mysqli_set_charset($conn, "utf8mb4");
$user_id = $_SESSION['user_id'];

// --- SMART MULTI-TENANT FALLBACK ---
if (!isset($_SESSION['property_id'])) {
    $user_p_q = mysqli_query($conn, "SELECT property_id FROM users WHERE id = '$user_id'");
    $user_p_row = mysqli_fetch_assoc($user_p_q);
    $_SESSION['property_id'] = $user_p_row['property_id'] ?? null;
}
$property_id = $_SESSION['property_id'];

// --- THEME PERSISTENCE ---
if (!isset($_SESSION['theme'])) {
    $theme_q = mysqli_query($conn, "SELECT theme_preference FROM users WHERE id = '$user_id'");
    $theme_row = mysqli_fetch_assoc($theme_q);
    $_SESSION['theme'] = $theme_row['theme_preference'] ?? 'light';
}
$current_theme = $_SESSION['theme'];

// --- DATA FETCHING ---
$full_name = $_SESSION['full_name'];
$termination_limit = 3; 

// Unread Notifications
$notif_count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM notifications WHERE user_id = '$user_id' AND status = 'unread'");
$unread_count = mysqli_fetch_assoc($notif_count_res)['total'] ?? 0;

// Termination Check
$unpaid_sql = "SELECT COUNT(*) as unpaid_months FROM bills WHERE user_id = '$user_id' AND status = 'Unpaid' AND bill_type = 'Rent'";
if (!empty($property_id)) { $unpaid_sql .= " AND property_id = '$property_id'"; }
$check_unpaid = mysqli_query($conn, $unpaid_sql);
$unpaid_months = mysqli_fetch_assoc($check_unpaid)['unpaid_months'] ?? 0;

if ($unpaid_months >= $termination_limit) {
    echo "<div style='height:100vh; display:flex; align-items:center; justify-content:center; background:#f8d7da; font-family:sans-serif; text-align:center; padding:20px;'><div style='background:white; padding:40px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.15); max-width:500px;'><div style='font-size:50px; margin-bottom:20px;'>🚫</div><h1 style='color:#721c24; font-weight:800;'>ACCOUNT TERMINATED</h1><p>Access blocked due to unpaid rent.</p><a href='logout.php' style='display:inline-block; margin-top:20px; padding:12px 25px; background:#721c24; color:white; text-decoration:none; border-radius:8px; font-weight:bold;'>Logout</a></div></div>";
    exit();
}

// Rule Acceptance Logic
if (isset($_POST['accept_rules'])) {
    $now = date('Y-m-d H:i:s');
    $sig_data = mysqli_real_escape_string($conn, $_POST['signature_data']); 
    $update_sql = "UPDATE users SET rules_accepted = 1, contract_signed_date = '$now', digital_signature = '$sig_data' WHERE id = '$user_id'";
    if(mysqli_query($conn, $update_sql)) {
        header("Location: tenant_dashboard.php?signed=success");
        exit();
    }
}

// User Profile & Unit Data
$u_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($u_query);
$pic = (!empty($user_data['profile_pic'])) ? 'uploads/' . $user_data['profile_pic'] : 'uploads/default.png';
$assigned_unit_id = $user_data['assigned_unit_id'];
$show_rules_overlay = ($user_data['rules_accepted'] == 0);

// House Rules Content (Smart Scoping Fallback)
$rules_sql = "SELECT setting_value FROM settings WHERE setting_key = 'house_rules'";
if (!empty($property_id)) { $rules_sql .= " AND property_id = '$property_id'"; }
$settings_q = mysqli_query($conn, $rules_sql);
if (mysqli_num_rows($settings_q) == 0 && !empty($property_id)) {
    $settings_q = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'house_rules' LIMIT 1");
}
$house_rules = (mysqli_num_rows($settings_q) > 0) ? mysqli_fetch_assoc($settings_q)['setting_value'] : "Standard House Rules Apply.";

$unit_no = "TBA"; $monthly_rent = 0.00;
if (!empty($assigned_unit_id)) {
    $unit_sql = "SELECT * FROM units WHERE id = '$assigned_unit_id'";
    if (!empty($property_id)) { $unit_sql .= " AND property_id = '$property_id'"; }
    $unit_q = mysqli_query($conn, $unit_sql);
    if ($u_row = mysqli_fetch_assoc($unit_q)) {
        $unit_no = $u_row['unit_no'];
        $monthly_rent = $u_row['monthly_rent'];
    }
}

// Pending Requests
$lease_sql = "SELECT request_type, status, preferred_date FROM lease_requests WHERE user_id = '$user_id' AND status = 'Pending'";
if (!empty($property_id)) { $lease_sql .= " AND property_id = '$property_id'"; }
$lease_sql .= " ORDER BY id DESC LIMIT 1";
$pending_lease_q = mysqli_query($conn, $lease_sql);
$pending_lease = mysqli_fetch_assoc($pending_lease_q);

// Chart Data
$usage_labels = []; $usage_data = [];
$chart_sql = "SELECT amount, due_date FROM bills WHERE user_id = '$user_id' AND (bill_type LIKE '%Electric%' OR bill_type LIKE '%Water%')";
if (!empty($property_id)) { $chart_sql .= " AND property_id = '$property_id'"; }
$chart_sql .= " ORDER BY due_date ASC LIMIT 6";
$chart_q = mysqli_query($conn, $chart_sql);
while($c_row = mysqli_fetch_assoc($chart_q)){
    $usage_labels[] = date('M', strtotime($c_row['due_date']));
    $usage_data[] = $c_row['amount'];
}

$rent_due_sql = "SELECT due_date FROM bills WHERE user_id = '$user_id' AND bill_type = 'Rent' AND status = 'Unpaid'";
if (!empty($property_id)) { $rent_due_sql .= " AND property_id = '$property_id'"; }
$rent_due_sql .= " ORDER BY due_date ASC LIMIT 1";
$rent_due_q = mysqli_query($conn, $rent_due_sql);
$rent_due_row = mysqli_fetch_assoc($rent_due_q);
$next_due_date = $rent_due_row['due_date'] ?? date('Y-m-d', strtotime('+1 month'));

// Bills & History Queries
$bills_sql = "SELECT * FROM bills WHERE user_id = '$user_id' AND status = 'Unpaid'";
if (!empty($property_id)) { $bills_sql .= " AND property_id = '$property_id'"; }
$bills_sql .= " ORDER BY due_date ASC";
$bills_res = mysqli_query($conn, $bills_sql);

$m_sql = "SELECT * FROM maintenance WHERE user_id = '$user_id'";
if (!empty($property_id)) { $m_sql .= " AND property_id = '$property_id'"; }
$m_sql .= " ORDER BY request_date DESC LIMIT 3";
$m_res = mysqli_query($conn, $m_sql);

// Helper function to dynamically parse HTML rules block into cards
function renderHouseRulesGrid($raw_html) {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $raw_html);
    libxml_clear_errors();

    $headings = $doc->getElementsByTagName('h3');
    $icons = ["", "fa-wallet", "fa-building-shield", "fa-file-shield", "fa-key", "fa-moon", "fa-ban"];
    $card_counter = 1;

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
            
            $current_icon = $icons[$card_counter] ?? "fa-book-bookmark";
            
            echo '<div class="rules-matrix-card shadow-sm">';
            echo '  <div class="rules-card-icon"><i class="fa ' . $current_icon . '"></i></div>';
            echo '  <h6>' . htmlspecialchars($title) . '</h6>';
            echo '  <div class="card-rule-body">' . $body_html . '</div>';
            echo '</div>';
            
            $card_counter++;
        }
    } else {
        echo '<div class="col-12 text-muted">' . nl2br($raw_html) . '</div>';
    }
}

$ann_sql = "SELECT * FROM announcements WHERE expiry_date >= CURDATE()";
if (!empty($property_id)) { $ann_sql .= " AND property_id = '$property_id'"; }
$ann_sql .= " ORDER BY created_at DESC LIMIT 1";
$ann_query = mysqli_query($conn, $ann_sql);
$announcement = ($ann_query && mysqli_num_rows($ann_query) > 0) ? mysqli_fetch_assoc($ann_query) : null;
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard | MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    
    <style>
        :root { 
            --sidebar-bg: #111827; 
            --teal-accent: #008080; 
            --bg: #f8fafc; 
            --card-bg: #ffffff; 
            --text-main: #1e293b; 
            --text-muted: #64748b; 
            --input-bg: #f1f5f9; 
            --border-color: #cbd5e1;
        }
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8; 
            --input-bg: #334155; 
            --border-color: #475569;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); transition: 0.3s; margin: 0; overflow-x: hidden; }
        
        h1, h2, h3, h4, h5, h6, .text-main, .main-title-header { color: var(--text-main) !important; }
        .text-muted, .rent-label { color: var(--text-muted) !important; }
        
        .card { background-color: var(--card-bg) !important; border: 1px solid var(--border-color) !important; color: var(--text-main) !important; }
        
        /* Sidebar layout dynamic translation layers */
        .sidebar { height: 100vh; background: var(--sidebar-bg); position: fixed; width: 260px; z-index: 1200; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); top: 0; left: 0; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-brand h5 { color: #fff !important; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 1px; }
        .sidebar-brand small { color: var(--teal-accent) !important; font-weight: 800; }
        .sidebar-links a { color: #9ca3af; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; font-weight: 600; border-radius: 12px; margin: 0 15px 5px 15px; }
        .sidebar-links a:hover, .sidebar-links a.active { color: #fff; background: var(--teal-accent); }

        .main-content { margin-left: 260px; padding: 30px; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); min-height: 100vh; }
        
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* Rent Card Optimization */
        .rent-card { background: var(--card-bg); border-radius: 24px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-color); }
        .rent-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: var(--text-muted); }
        .rent-amount { font-size: 2.8rem; font-weight: 800; margin: 5px 0; color: var(--text-main) !important; }
        
        /* Modern Split Ledger Components */
        .ledger-split-container { background: var(--card-bg) !important; border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; }
        .ledger-invoice-panel { padding: 20px; background: var(--card-bg) !important; }
        .ledger-timeline-panel { padding: 20px; background: var(--input-bg) !important; border-left: 1px solid var(--border-color); }
        .ledger-invoice-panel .border { border-color: var(--border-color) !important; }

        /* Clean Timeline Rules */
        .timeline-stream { position: relative; padding-left: 20px; border-left: 2px solid var(--teal-accent); }
        .timeline-node { position: relative; margin-bottom: 15px; }
        .timeline-node::before { content: ''; position: absolute; left: -26px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--teal-accent); }

        .id-card-body { background: linear-gradient(135deg, #1e293b, #334155); color: white !important; border-radius: 20px; padding: 25px; text-align: center; }
        .id-card-body h4 { color: white !important; }
        .qr-box { background: white; padding: 5px; border-radius: 10px; width: 100px; height: 100px; margin: 0 auto; }

        .btn-pay { background: var(--teal-accent); color: white !important; border-radius: 12px; padding: 8px 18px; font-weight: 700; text-decoration: none; border:none; transition: 0.2s; display: inline-block; }
        .btn-pay:hover { background: #006666; transform: translateY(-2px); }
        
        .status-badge { font-size: 0.65rem; padding: 5px 12px; border-radius: 10px; font-weight: 800; color: white !important; text-transform: uppercase; }
        .history-item { background: var(--input-bg) !important; border-radius: 15px; padding: 12px; margin-bottom: 10px; color: var(--text-main) !important; }

        .btn-lease { border-radius: 12px; padding: 10px; font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; gap: 8px; border: none; }
        .btn-lease-renew { background: rgba(0, 128, 128, 0.1); color: var(--teal-accent) !important; }
        .btn-lease-renew:hover { background: rgba(0, 128, 128, 0.2); }
        .btn-lease-moveout { background: rgba(220, 53, 69, 0.1); color: #dc3545 !important; }
        .btn-lease-moveout:hover { background: rgba(220, 53, 69, 0.2); }

        /* UPGRADED MODERN NO-SCROLL CARDS ARCHIVE WRAPPER */
        .modern-rules-window {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            width: 95%;
            max-width: 1050px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .rules-matrix-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            padding: 24px;
            overflow-y: auto;
            background: var(--bg);
        }
        .rules-matrix-card {
            background: var(--card-bg);
            padding: 22px;
            border-radius: 18px;
            border: 1px solid var(--border-color);
            transition: transform 0.25s ease, border-color 0.25s ease;
            display: flex;
            flex-direction: column;
        }
        .rules-matrix-card:hover {
            transform: translateY(-3px);
            border-color: var(--teal-accent);
            box-shadow: 0 8px 25px rgba(0, 128, 128, 0.05);
        }
        .rules-card-icon {
            width: 42px;
            height: 42px;
            background: rgba(0,128,128,0.08);
            color: var(--teal-accent);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 14px;
        }
        .rules-matrix-card h6 {
            font-weight: 800;
            color: var(--teal-accent) !important;
            margin-bottom: 10px;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 6px;
        }
        .card-rule-body {
            font-size: 0.82rem;
            color: var(--text-main);
            font-weight: 600;
            line-height: 1.5;
        }
        .card-rule-body p { margin-bottom: 10px; margin-top: 0; }
        .card-rule-body p:last-child { margin-bottom: 0; }
        .card-rule-body strong { color: var(--teal-accent) !important; font-weight: 700; }
        [data-theme="dark"] .card-rule-body strong { color: #00b3b3 !important; }

        .modern-sig-section {
            padding: 20px 24px;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .canvas-container {
            flex-grow: 1;
            max-width: 450px;
            position: relative;
        }

        @media (max-width: 1100px) { .rules-matrix-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 991px) { 
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; } 
            .rent-amount { font-size: 2.2rem; }
            .ledger-timeline-panel { border-left: none; border-top: 1px solid var(--border-color); }
        }
        @media (max-width: 768px) {
            .rules-matrix-grid { grid-template-columns: 1fr; padding: 16px; }
            .modern-sig-section { flex-direction: column; align-items: stretch; }
            .canvas-container { max-width: 100%; }
            .modern-rules-window { max-height: 95vh; }
        }
    </style>
</head>

<body <?php if ($show_rules_overlay) echo 'style="overflow:hidden"'; ?>>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebarView()"></div>

<?php if ($show_rules_overlay): ?>
<div id="rulesOverlay" style="position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.85); z-index:9999; display:flex; align-items:center; justify-content:center; backdrop-filter: blur(12px); padding: 15px;">
    <div class="modern-rules-window">
        <div class="p-4 border-bottom bg-white text-center" style="background: var(--card-bg) !important; border-color: var(--border-color) !important;">
            <span class="text-uppercase fw-800 tracking-wider small style" style="color: var(--teal-accent); letter-spacing: 2px;">Enterprise Hub Context</span>
            <h4 class="fw-800 mb-0">Digital Lease &amp; House Policies</h4>
        </div>
        
        <div class="rules-matrix-grid">
            <?php renderHouseRulesGrid($house_rules); ?>
        </div>

        <form id="sigForm" method="POST" class="m-0">
            <div class="modern-sig-section">
                <div class="canvas-container">
                    <canvas id="sig-pad" style="border: 2px dashed var(--border-color); background: var(--bg); border-radius: 14px; width: 100%; height: 110px; touch-action: none;"></canvas>
                    <input type="hidden" name="signature_data" id="sig_data">
                    <button type="button" id="clearSig" class="btn btn-sm text-danger fw-bold position-absolute" style="bottom: 8px; right: 12px; font-size: 0.75rem;"><i class="fa fa-eraser"></i> Clear</button>
                </div>
                <div class="flex-grow-1" style="max-width: 480px;">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="checkRules" required>
                        <label class="form-check-label small fw-600" for="checkRules">
                            I verify identity criteria and dynamically accept all system house rules.
                        </label>
                    </div>
                    <button type="submit" name="accept_rules" class="btn w-100 py-2.5 fw-800 text-white shadow-sm" style="background: var(--teal-accent); border-radius: 12px; font-size:0.9rem;">SIGN &amp; INITIALIZE HUB</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="sidebar shadow-lg" id="sidebar">
    <div class="sidebar-brand">
        <h5>MG'S APARTMENT</h5>
        <small>TENANT PORTAL</small>
    </div>
    <div class="sidebar-links mt-3">
        <a href="tenant_dashboard.php" class="active"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
        <a href="profile_edit.php"><i class="fa fa-user-circle"></i> My Profile</a>
        <a href="tenant_bills.php"><i class="fa fa-wallet"></i> My Bills</a>
        <a href="tenant_requests.php"><i class="fa fa-tools"></i> Maintenance</a>
        <a href="#" id="houseRulesLink" data-bs-toggle="modal" data-bs-target="#rulesModal"><i class="fa fa-book"></i> House Rules</a>
        <a href="logout.php" class="text-danger mt-5"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <button class="btn p-0 border-0" onclick="toggleSidebarView()"><i class="fa fa-bars-staggered fs-3 text-main"></i></button>
            <img src="<?php echo $pic; ?>" class="rounded-circle border border-2 border-white shadow-sm" style="width:55px; height:55px; object-fit: cover;">
            <div>
                <h4 class="fw-800 mb-0 main-title-header">Hi, <?php echo explode(' ', trim($full_name))[0]; ?>!</h4>
                <small class="text-muted fw-600">Unit <?php echo $unit_no; ?> • <button class="btn p-0 text-teal border-0 bg-transparent fw-bold small" data-bs-toggle="modal" data-bs-target="#idModal" style="font-size:0.7rem; text-decoration:underline; color:var(--teal-accent);">Digital ID</button></small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button id="themeToggle" class="btn btn-light rounded-circle shadow-sm" style="background: var(--card-bg); border: 1px solid var(--input-border); color: var(--text-main); width:40px; height:40px; display:flex; align-items:center; justify-content:center;"><i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i></button>
            <div class="position-relative cursor-pointer" onclick="window.location.href='tenant_notifications.php'">
                <i class="fa-regular fa-bell fs-3 text-muted"></i>
                <?php if($unread_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;"><?php echo $unread_count; ?></span><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <?php if ($announcement): ?>
            <div class="card p-3 border-0 shadow-sm mb-4" style="background: rgba(0, 128, 128, 0.1); color: var(--teal-accent);">
                <div class="d-flex align-items-center mb-1">
                    <i class="fa fa-bullhorn me-2"></i>
                    <h6 class="fw-800 mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                </div>
                <small class="text-truncate d-block fw-600"><?php echo htmlspecialchars($announcement['content']); ?></small>
            </div>
            <?php endif; ?>

            <div class="rent-card mb-4">
                <div class="row align-items-center">
                    <div class="col-md-7 border-md-end" style="border-color: var(--border-color) !important;">
                        <span class="rent-label">Monthly Rent</span>
                        <div class="rent-amount">₱<?php echo number_format($monthly_rent, 2); ?></div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa fa-calendar-check text-success"></i>
                            <span class="text-muted small fw-600">Next Due: <strong class="text-main"><?php echo date('M d, Y', strtotime($next_due_date)); ?></strong></span>
                        </div>
                    </div>
                    <div class="col-md-5 mt-4 mt-md-0 ps-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="rent-label" style="font-size: 0.6rem;">Usage Insight</span>
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success fw-800" style="font-size:0.55rem;">LIVE</span>
                        </div>
                        <canvas id="usageChart" style="max-height: 90px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>

            <h6 class="fw-800 mb-3 text-uppercase small main-title-header" style="letter-spacing: 1px;">Financial Timeline &amp; Split Ledger</h6>
            <div class="ledger-split-container shadow-sm mb-4">
                <div class="row g-0">
                    <div class="col-md-7 ledger-invoice-panel">
                        <small class="rent-label d-block mb-3">Outstanding Invoices</small>
                        <div class="row g-2">
                            <?php if(mysqli_num_rows($bills_res) > 0): while($bill = mysqli_fetch_assoc($bills_res)): ?>
                            <div class="col-12">
                                <div class="p-3 border rounded-3 d-flex justify-content-between align-items-center" style="background-color: var(--card-bg);">
                                    <div>
                                        <small class="fw-800" style="color: var(--teal-accent); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($bill['bill_type']); ?></small>
                                        <div class="fw-800 fs-4 text-main" style="color: var(--text-main) !important;">₱<?php echo number_format($bill['amount'], 2); ?></div>
                                        <small class="text-danger fw-bold d-block" style="font-size: 0.75rem; margin-top: 2px;">Due <?php echo date('M d', strtotime($bill['due_date'])); ?></small>
                                    </div>
                                    <a href="tenant_payment.php?bill_id=<?php echo $bill['id']; ?>" class="btn-pay">PAY</a>
                                </div>
                            </div>
                            <?php endwhile; else: ?>
                            <div class="col-12"><div class="p-4 text-center text-muted small" style="border: 2px dashed var(--border-color); border-radius:14px;">All statements are clear. ✨</div></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-5 ledger-timeline-panel">
                        <small class="rent-label d-block mb-3">Payment Stream</small>
                        <div class="timeline-stream">
                            <?php 
                            $hist_q = mysqli_query($conn, "SELECT * FROM receipts WHERE user_id = '$user_id' ORDER BY id DESC LIMIT 3");
                            if($hist_q && mysqli_num_rows($hist_q) > 0): while($h_row = mysqli_fetch_assoc($hist_q)): ?>
                            <div class="timeline-node">
                                <small class="text-muted d-block" style="font-size:0.65rem;"><?php echo date('M d, Y', strtotime($h_row['created_at'] ?? $h_row['payment_date'] ?? 'now')); ?></small>
                                <span class="small fw-700 text-main">Settled Payment of <strong>₱<?php echo number_format($h_row['amount'], 2); ?></strong></span>
                            </div>
                            <?php endwhile; else: ?>
                            <span class="small text-muted d-block p-2">No historical transaction activity logged.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 shadow-sm mb-4">
                <h6 class="fw-800 mb-3 small text-uppercase text-muted">Quick Maintenance</h6>
                <form action="submit_maintenance.php" method="POST" enctype="multipart/form-data">
                    <textarea name="issue" class="form-control mb-3 p-3" rows="2" placeholder="Describe the issue..." required style="background: var(--input-bg); border:none; border-radius: 15px; color: var(--text-main);"></textarea>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <select name="priority" class="form-select small" style="background: var(--input-bg); border:none; border-radius: 10px; color: var(--text-main);">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">Urgent</option>
                            </select>
                        </div>
                        <div class="col-6"><input type="file" name="issue_image" class="form-control form-control-sm" style="background: var(--input-bg); border:none; color: var(--text-main);"></div>
                    </div>
                    <button type="submit" class="btn btn-pay w-100 py-2 shadow-sm">SUBMIT REQUEST</button>
                </form>

                <div class="mt-4">
                    <small class="fw-800 text-muted d-block mb-3 text-uppercase" style="font-size: 0.65rem;">Recent Requests</small>
                    <?php if(isset($m_res) && mysqli_num_rows($m_res) > 0): while($m = mysqli_fetch_assoc($m_res)): ?>
                    <div class="history-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-truncate" style="max-width: 65%;">
                                <span class="small fw-800 d-block text-truncate text-main"><?php echo htmlspecialchars($m['issue_details']); ?></span>
                                <small class="text-muted" style="font-size:0.65rem;"><?php echo date('M d', strtotime($m['request_date'])); ?></small>
                            </div>
                            <span class="status-badge px-2" style="background: <?php 
                                $s = strtolower($m['status']);
                                echo ($s == 'pending') ? '#f59e0b' : (($s == 'in progress') ? '#3b82f6' : '#10b981');
                            ?>;"><?php echo $m['status']; ?></span>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="p-3 text-center text-muted small" style="border: 1px dashed var(--border-color); border-radius:15px;">No logged entries.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card p-4 shadow-sm">
                <h6 class="fw-800 mb-3 small text-uppercase text-muted">Lease Status</h6>
                <?php if($pending_lease): ?>
                    <div class="alert alert-warning py-2 px-3 border-0 rounded-4" style="font-size: 0.75rem;">
                        <i class="fa fa-clock me-1"></i> Pending: <strong><?php echo $pending_lease['request_type']; ?></strong>
                    </div>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <a href="lease_renew.php" class="btn btn-lease btn-lease-renew"><i class="fa fa-calendar-check"></i> Renew Lease</a>
                    <a href="lease_moveout.php" class="btn btn-lease btn-lease-moveout"><i class="fa fa-sign-out-alt"></i> Move-out Request</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="idModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-5 border-0 overflow-hidden">
            <div class="id-card-body">
                <img src="<?php echo $pic; ?>" class="rounded-circle border border-4 border-white shadow-lg mb-3" style="width:110px; height:110px; object-fit:cover;">
                <h4 class="fw-800 mb-0"><?php echo $full_name; ?></h4>
                <span class="badge bg-teal mb-4" style="background: var(--teal-accent);">UNIT <?php echo $unit_no; ?></span>
                <div class="qr-box mb-4"><img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Tenant-<?php echo $user_id; ?>" class="w-100"></div>
                <small class="text-white-50 d-block">Member since <?php echo date('Y', strtotime($user_data['contract_signed_date'] ?? 'now')); ?></small>
                <button type="button" class="btn btn-sm btn-light mt-4 rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
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
                
                <div class="rules-matrix-grid">
                    <?php renderHouseRulesGrid($house_rules); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebarView() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    // Theme Toggle
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

    // Sidebar Hide Logic
    document.getElementById('houseRulesLink').onclick = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    };

    // Usage Chart
    const ctx = document.getElementById('usageChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: { 
            labels: <?php echo json_encode($usage_labels); ?>, 
            datasets: [{ 
                label: 'Usage', 
                data: <?php echo json_encode($usage_data); ?>, 
                backgroundColor: '#008080', 
                borderRadius: 4 
            }] 
        },
        options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }, 
            scales: { y: { display: false }, x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } } } 
        }
    });

    <?php if ($show_rules_overlay): ?>
    const canvas = document.getElementById('sig-pad');
    const pad = new SignaturePad(canvas);
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        pad.clear();
    }
    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();

    document.getElementById('clearSig').onclick = () => pad.clear();
    document.getElementById('sigForm').onsubmit = (e) => {
        if(pad.isEmpty()) { 
            alert("Please provide your signature."); 
            e.preventDefault(); 
        } else {    
            document.getElementById('sig_data').value = pad.toDataURL();
        }
    };
    <?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>