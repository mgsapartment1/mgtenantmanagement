<?php
include('db.php');
session_start();
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

// --- THEME PERSISTENCE ---
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

// Get House Rules from settings securely tied to property
$rules_sql = "SELECT setting_value FROM settings WHERE setting_key = 'house_rules'";
if (!empty($property_id)) { $rules_sql .= " AND property_id = '$property_id'"; }
$settings_q = mysqli_query($conn, $rules_sql);
if (mysqli_num_rows($settings_q) == 0 && !empty($property_id)) {
    $settings_q = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'house_rules' LIMIT 1");
}
$house_rules = (mysqli_num_rows($settings_q) > 0) ? mysqli_fetch_assoc($settings_q)['setting_value'] : "Standard House Rules Apply.";

// Kunin ang Total Unpaid para sa alert box summary (Multi-Tenant Secure)
$unpaid_sql = "SELECT SUM(amount) as total FROM bills WHERE user_id = '$user_id' AND status = 'Unpaid'";
if (!empty($property_id)) { $unpaid_sql .= " AND property_id = '$property_id'"; }
$unpaid_check = mysqli_query($conn, $unpaid_sql);
$unpaid_data = mysqli_fetch_assoc($unpaid_check);
$total_unpaid = $unpaid_data['total'] ?? 0;

// Scoped Context Ledger Query
$query = "SELECT * FROM bills WHERE user_id = '$user_id'";
if (!empty($property_id)) { $query .= " AND property_id = '$property_id'"; }
$query .= " ORDER BY due_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Bills - MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
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
            --border-color: #cbd5e1;
        }

        [data-theme="dark"] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --table-border: #334155;
            --input-border: #475569;
            --border-color: #475569;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); transition: 0.3s; margin: 0; overflow-x: hidden; }

        h1, h2, h3, h4, h5, h6, .text-main, .header-title h3 { color: var(--text-main) !important; }
        .text-muted, .rent-label { color: var(--text-muted) !important; }

        /* SIDEBAR (UPGRADED STABLE POSITIONING LAYER CLOSURE) */
        .sidebar { height: 100vh; background: var(--sidebar-bg); position: fixed; width: 260px; z-index: 1200; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); top: 0; left: 0; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-brand h5 { color: #fff !important; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 1px; }
        .sidebar-brand small { color: var(--teal-accent) !important; font-weight: 800; }
        .sidebar-links a { color: #9ca3af; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; font-weight: 600; border-radius: 12px; margin: 0 15px 5px 15px; }
        .sidebar-links a:hover, .sidebar-links a.active { color: #fff; background: var(--teal-accent); }

        .main-content { margin-left: 260px; padding: 30px; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); min-height: 100vh; }
        
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* Billing Card */
        .billing-card { background: var(--card-bg); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 1.5rem; border: 1px solid var(--table-border); }
        .header-title { color: var(--text-main); font-weight: 800; margin-bottom: 0; display: flex; align-items: center; }
        .header-title i { background: rgba(0, 128, 128, 0.1); color: var(--teal-accent); width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-right: 15px; }

        /* Table Styles and Dark Theme Overrides */
        .table { color: var(--text-main) !important; --bs-table-hover-color: var(--text-main); --bs-table-bg: transparent; }
        .table thead th { background-color: var(--teal-accent) !important; color: white !important; border: none; font-weight: 700; padding: 15px; }
        .table tbody td { padding: 1.2rem 1rem; border-bottom: 1px solid var(--table-border) !important; color: var(--text-main) !important; vertical-align: middle; }

        .status-badge { padding: 6px 14px; border-radius: 10px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .status-unpaid { background: #fee2e2 !important; color: #dc2626 !important; }
        .status-paid { background: #dcfce7 !important; color: #16a34a !important; }
        .status-pending { background: #fef3c7 !important; color: #d97706 !important; }

        .btn-receipt { background: transparent; color: var(--teal-accent); border: 2px solid var(--teal-accent); border-radius: 10px; padding: 6px 16px; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 0.85rem; }
        .btn-receipt:hover { background: var(--teal-accent); color: white !important; }

        /* Modal Content Canvas Frames */
        .modal-content { background-color: var(--card-bg) !important; color: var(--text-main); border: 1px solid var(--table-border); overflow: hidden; }
        #receiptFrame { width: 100%; height: 600px; border: none; display: block; background: white; }

        /* ULTRA-MODERN METRIC ARCHIVE WRAPPER GRID */
        .rules-matrix-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            padding: 24px;
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

        @media (max-width: 1100px) { .rules-matrix-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
        }
        @media (max-width: 768px) {
            .rules-matrix-grid { grid-template-columns: 1fr; padding: 16px; }
        }
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
        <a href="tenant_bills.php" class="active"><i class="fa fa-wallet"></i> My Bills</a>
        <a href="tenant_requests.php"><i class="fa fa-tools"></i> Maintenance</a>
        <a href="#" id="houseRulesLink" data-bs-toggle="modal" data-bs-target="#rulesModal"><i class="fa fa-book"></i> House Rules</a>
        <a href="logout.php" class="text-danger mt-5"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <button class="btn p-0 border-0" onclick="toggleSidebarView()">
                <i class="fa fa-bars-staggered fs-3 text-main"></i>
            </button>
            <div class="header-title">
                <i class="fa fa-file-invoice-dollar"></i>
                <h3 class="mb-0 fw-800">My Billing Statement</h3>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button id="themeToggle" class="btn btn-light rounded-circle shadow-sm" style="background: var(--card-bg); border: 1px solid var(--input-border); color: var(--text-main); width:40px; height:40px; display:flex; align-items:center; justify-content:center;"><i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i></button>
            <div class="d-none d-md-block">
                <span class="badge bg-dark rounded-pill px-3 py-2" style="background: var(--sidebar-bg) !important;">ID: #TN-<?php echo str_pad($user_id, 4, "0", STR_PAD_LEFT); ?></span>
            </div>
        </div>
    </div>

    <?php if($total_unpaid > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center mb-4 p-3" style="background: rgba(255, 193, 7, 0.1);">
        <i class="fa fa-circle-exclamation fs-4 me-3 text-warning"></i>
        <div>
            <p class="mb-0 small fw-800" style="color: var(--text-main);">Pending Balance: ₱<?php echo number_format($total_unpaid, 2); ?></p>
            <small class="text-muted fw-600">Please settle your unpaid bills using the payment portal.</small>
        </div>
    </div>
    <?php endif; ?>

    <div class="billing-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Due Date</th>
                        <th>Description</th>
                        <th>Amount Due</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="fw-600"><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                            <td><span class="fw-800"><?php echo htmlspecialchars($row['bill_type']); ?></span></td>
                            <td><span class="fw-800 text-teal" style="color: var(--teal-accent) !important;">₱<?php echo number_format($row['amount'], 2); ?></span></td>
                            <td>
                                <?php 
                                    $st = strtoupper($row['status']);
                                    $badge_class = ($st == 'UNPAID') ? 'status-unpaid' : (($st == 'PAID' || $st == 'APPROVED') ? 'status-paid' : 'status-pending');
                                ?>
                                <span class="status-badge <?php echo $badge_class; ?>"><?php echo $st; ?></span>
                            </td>
                            <td class="text-center">
                                <?php if($st == 'UNPAID'): ?>
                                    <a href="tenant_payment.php?bill_id=<?php echo $row['id']; ?>" class="btn px-4 py-2 text-white fw-800 rounded-3 text-decoration-none shadow-sm" style="background: var(--teal-accent); font-size: 0.75rem;">
                                        PAY NOW
                                    </a>
                                <?php elseif($st == 'PAID' || $st == 'APPROVED'): ?>
                                    <button class="btn-receipt" onclick="showReceipt(<?php echo $row['id']; ?>)">
                                        <i class="fa fa-receipt me-1"></i> RECEIPT
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small fw-700">FOR APPROVAL</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted fw-600">No billing records found. ✨</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-800 mb-0">Official Receipt</h5>
                <button type="button" class="btn-close" style="filter: var(--text-main);" data-bs-dismiss="modal"></button>
            </div>
            <div id="captureArea" class="modal-body p-0 mt-3">
                <iframe id="receiptFrame" src=""></iframe>
            </div>
            <div class="modal-footer border-0 p-3">
                <button type="button" class="btn btn-light fw-700 rounded-3" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn text-white fw-700 rounded-3 px-4 shadow-sm" style="background: var(--teal-accent);" onclick="saveReceiptImage()">
                    <i class="fa fa-download me-2"></i> Save Image
                </button>
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
                    <?php 
                    $doc = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $house_rules);
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

    // Theme Toggle Core Switcher
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

    // Sidebar Hide Logic for House Rules
    document.getElementById('houseRulesLink').onclick = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    };

    function showReceipt(billId) {
        const frame = document.getElementById('receiptFrame');
        frame.src = 'view_receipt.php?id=' + billId + '&t=' + new Date().getTime();
        const myModal = new bootstrap.Modal(document.getElementById('receiptModal'));
        myModal.show();
    }

    function saveReceiptImage() {
        const frame = document.getElementById('receiptFrame');
        const frameDoc = frame.contentDocument || frame.contentWindow.document;
        const receiptElement = frameDoc.body;

        html2canvas(receiptElement, {
            backgroundColor: "#ffffff",
            scale: 2,
            useCORS: true
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'MG-Receipt-' + new Date().getTime() + '.png';
            link.href = canvas.toDataURL("image/png");
            link.click();
        });
    }
</script>
</body>
</html>