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

// --- THEME PERSISTENCE ---
if (!isset($_SESSION['theme'])) {
    $theme_q = mysqli_query($conn, "SELECT theme_preference FROM users WHERE id = '$user_id'");
    $theme_row = mysqli_fetch_assoc($theme_q);
    $_SESSION['theme'] = $theme_row['theme_preference'] ?? 'light';
}
$current_theme = $_SESSION['theme'];

// --- DATA FETCHING ---
$query = mysqli_query($conn, "SELECT u.*, un.unit_no, un.monthly_rent 
                             FROM users u 
                             LEFT JOIN units un ON u.assigned_unit_id = un.id 
                             WHERE u.id = '$user_id'");
$user_data = mysqli_fetch_assoc($query);

$full_name = $user_data['full_name'] ?? "N/A";
$unit_no = $user_data['unit_no'] ?? "TBA";
$monthly_rent = $user_data['monthly_rent'] ?? 0;
$pic = (!empty($user_data['profile_pic'])) ? 'uploads/' . $user_data['profile_pic'] : 'uploads/default.png';

// House Rules Content (Secure Smart Fallback Context)
$settings_q = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'house_rules'");
$house_rules = (mysqli_num_rows($settings_q) > 0) ? mysqli_fetch_assoc($settings_q)['setting_value'] : "Standard House Rules Apply.";

// Profile Update Logic
if (isset($_POST['update_profile'])) {
    $full_name_input = mysqli_real_escape_string($conn, $_POST['full_name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']); 
    $emergency = mysqli_real_escape_string($conn, $_POST['emergency_contact']);
    
    $update_fields = "full_name = '$full_name_input', contact = '$contact', emergency_contact = '$emergency'";

    if (!empty($_FILES['profile_pic']['name'])) {
        $p_file = time() . "_p_" . basename($_FILES["profile_pic"]["name"]);
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], "uploads/" . $p_file)) {
            $update_fields .= ", profile_pic = '$p_file'";
        }
    }

    if (!empty($_FILES['valid_id']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES["valid_id"]["name"]));
        if (move_uploaded_file($_FILES["valid_id"]["tmp_name"], $target_dir . $file_name)) {
            $update_fields .= ", valid_id = '$file_name'";
        }
    }

    $sql = "UPDATE users SET $update_fields WHERE id = '$user_id'";
    if (mysqli_query($conn, $sql)) {
        header("Location: profile_edit.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Profile | MG's Apartment</title>
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
            --input-bg: #f1f5f9; 
            --input-border: #e2e8f0; 
            --border-color: #cbd5e1;
        }
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8; 
            --input-bg: #334155; 
            --input-border: #475569; 
            --border-color: #475569;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); transition: 0.3s; margin: 0; overflow-x: hidden; }
        
        h1, h2, h3, h4, h5, h6, .text-main, .main-title-header { color: var(--text-main) !important; }
        .text-muted, .rent-label { color: var(--text-muted) !important; }
        
        /* SIDEBAR (UPGRADED STABLE POSITION LAYERS) */
        .sidebar { height: 100vh; background: var(--sidebar-bg); position: fixed; width: 260px; z-index: 1200; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); top: 0; left: 0; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-brand h5 { color: #fff !important; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 1px; }
        .sidebar-brand small { color: var(--teal-accent) !important; font-weight: 800; }
        .sidebar-links a { color: #9ca3af; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; font-weight: 600; border-radius: 12px; margin: 0 15px 5px 15px; }
        .sidebar-links a:hover, .sidebar-links a.active { color: #fff; background: var(--teal-accent); }

        .main-content { margin-left: 260px; padding: 30px; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); min-height: 100vh; }
        
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* Profile Specific Styles */
        .profile-container { background: var(--card-bg); border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-color); }
        .profile-cover { background: var(--teal-accent); height: 160px; position: relative; }
        .profile-pic-wrapper { position: absolute; bottom: -50px; left: 30px; }
        .profile-preview { width: 130px; height: 130px; object-fit: cover; border-radius: 20px; border: 5px solid var(--card-bg); box-shadow: 0 5px 15px rgba(0,0,0,0.1); background: var(--card-bg); }
        
        /* Floating Inner Accent Form Inputs */
        .input-group-custom { background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 15px; padding: 12px 18px; margin-bottom: 15px; transition: 0.2s; }
        .input-group-custom:focus-within { border-color: var(--teal-accent); }
        .input-group-custom label { display: block; font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px; }
        .input-group-custom input { border: none !important; background: transparent !important; width: 100%; outline: none; font-weight: 600; color: var(--text-main); padding: 0; }

        /* Modernized Dynamic Document Cards */
        .document-upload-box { padding: 20px; border: 1px solid var(--input-border) !important; border-radius: 20px; background: var(--input-bg) !important; color: var(--text-main) !important; }
        .document-upload-box .form-control { background: var(--card-bg) !important; border: 1px solid var(--input-border) !important; color: var(--text-main) !important; border-radius: 10px; }

        .status-card { background: var(--card-bg); border-radius: 24px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid var(--input-border); }
        .status-item { padding: 12px; border-radius: 15px; background: var(--input-bg); margin-bottom: 10px; }
        .status-item small { display: block; font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .status-item span { font-weight: 700; color: var(--text-main); }
        
        .btn-save { background: var(--teal-accent); color: white !important; border: none; padding: 14px; border-radius: 15px; font-weight: 800; width: 100%; transition: 0.2s; box-shadow: 0 4px 12px rgba(0,128,128,0.2); }
        .btn-save:hover { background: #006666; transform: translateY(-2px); }

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
            .profile-pic-wrapper { left: 50%; transform: translateX(-50%); bottom: -55px; }
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
        <a href="profile_edit.php" class="active"><i class="fa fa-user-circle"></i> My Profile</a>
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
            <h4 class="fw-800 mb-0 main-title-header">Edit Profile</h4>
        </div>
        <button id="themeToggle" class="btn btn-light rounded-circle shadow-sm" style="background: var(--card-bg); border: 1px solid var(--input-border); color: var(--text-main); width:40px; height:40px; display:flex; align-items:center; justify-content:center;"><i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i></button>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 fw-600"><i class="fa fa-check-circle me-2"></i> Profile adjustments deployed and synchronized successfully!</div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="profile-container mb-4">
                    <div class="profile-cover">
                        <div class="profile-pic-wrapper">
                            <img src="<?php echo $pic; ?>" class="profile-preview shadow">
                            <label for="p_pic" class="btn btn-sm btn-dark rounded-circle shadow position-absolute bottom-0 end-0" style="cursor: pointer; background: var(--sidebar-bg); border: 2px solid var(--card-bg);"><i class="fa fa-camera"></i></label>
                            <input type="file" name="profile_pic" id="p_pic" class="d-none">
                        </div>
                    </div>

                    <div class="p-4 p-md-5 mt-5">
                        <h6 class="fw-800 text-uppercase small mb-3" style="color: var(--teal-accent) !important; letter-spacing: 0.5px;">| Personal Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group-custom">
                                    <label>Legal Full Name</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group-custom">
                                    <label>Active Contact Number</label>
                                    <input type="text" name="contact" value="<?php echo htmlspecialchars($user_data['contact'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="input-group-custom">
                                    <label>Emergency Contact Info (Name - Phone)</label>
                                    <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($user_data['emergency_contact'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-800 text-uppercase small mt-4 mb-3" style="color: var(--teal-accent) !important; letter-spacing: 0.5px;">| Documents</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="document-upload-box border">
                                    <label class="small fw-800 text-muted text-uppercase mb-2 d-block">Valid ID Card</label>
                                    <input type="file" name="valid_id" class="form-control form-control-sm mb-2">
                                    <?php if(!empty($user_data['valid_id'])): ?>
                                        <button type="button" class="btn btn-dark btn-sm w-100 rounded-3 fw-bold" style="background: var(--sidebar-bg);" data-bs-toggle="modal" data-bs-target="#idModal">View Current ID</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="document-upload-box border">
                                    <label class="small fw-800 text-muted text-uppercase mb-2 d-block">Lease Contract</label>
                                    <a href="view_contract.php" target="_blank" class="btn btn-sm w-100 rounded-3 fw-bold border py-2" style="color: var(--teal-accent); background: var(--card-bg); border-color: var(--teal-accent) !important;">Review PDF</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="status-card shadow-sm mb-4">
                    <h6 class="fw-800 mb-3 small text-muted text-uppercase" style="letter-spacing: 0.5px;">Lease Overview</h6>
                    <div class="status-item"><small>Assigned Unit</small><span>Unit <?php echo $unit_no; ?></span></div>
                    <div class="status-item"><small>Monthly Rent</small><span>₱<?php echo number_format($monthly_rent, 2); ?></span></div>
                    <div class="status-item"><small>Contract Status</small>
                        <span class="<?php echo !empty($user_data['digital_signature']) ? 'text-success' : 'text-danger'; ?>">
                            <i class="fa <?php echo !empty($user_data['digital_signature']) ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-1"></i>
                            <?php echo !empty($user_data['digital_signature']) ? 'Signed' : 'Pending'; ?>
                        </span>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-save mt-3">SAVE CHANGES</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="idModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0" style="background: var(--card-bg);">
            <div class="modal-header border-0 pb-0"><button type="button" class="btn-close" style="filter: var(--text-main);" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center p-4">
                <img src="uploads/<?php echo $user_data['valid_id']; ?>" class="img-fluid rounded-3">
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

    // Pure Theme Toggle Core Integration
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

    // Instant Avatar Image Preview Feature
    document.getElementById('p_pic').onchange = function (evt) {
        var tgt = evt.target || window.event.srcElement,
            files = tgt.files;
        if (FileReader && files && files.length) {
            var fr = new FileReader();
            fr.onload = function () {
                document.querySelector(".profile-preview").src = fr.result;
            }
            fr.readAsDataURL(files[0]);
        }
    }
</script>
</body>
</html>