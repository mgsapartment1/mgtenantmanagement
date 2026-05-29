<?php
include('db.php');
session_start();

// Security Check: Ensure only Admin can access
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

// --- DYNAMIC TARGET MONTH FILTER DETECTOR ---
$target_month_input = isset($_GET['report_month']) ? mysqli_real_escape_string($conn, $_GET['report_month']) : date('Y-m');

$selected_year  = date('Y', strtotime($target_month_input));
$selected_month = date('m', strtotime($target_month_input));

$current_month_name = date('F', strtotime($target_month_input));
$current_month_year = date('F Y', strtotime($target_month_input));
$report_date        = date('M d, Y h:i A');

// 1. Total Collected (Filtered by selected month and year including penalties)
$res_rev = mysqli_query($conn, "SELECT SUM(amount + IFNULL(penalty,0)) as total FROM bills WHERE status = 'Paid' AND MONTH(date_paid) = '$selected_month' AND YEAR(date_paid) = '$selected_year'");
$monthly_revenue = mysqli_fetch_assoc($res_rev)['total'] ?? 0;

// 2. Total Receivables (Filtered by selected month and year of due_date)
$res_receivables = mysqli_query($conn, "SELECT SUM(amount + IFNULL(penalty,0)) as total FROM bills WHERE status = 'Unpaid' AND MONTH(due_date) = '$selected_month' AND YEAR(due_date) = '$selected_year'");
$total_receivables = mysqli_fetch_assoc($res_receivables)['total'] ?? 0;

// 3. Occupancy Stats FIXED QUERY: Ginamit ang JOIN sa users table para makuha ang assigned unit ID nang walang error
$res_total_units = mysqli_query($conn, "SELECT COUNT(*) as total FROM units WHERE status != 'Archived'");
$total_units = mysqli_fetch_assoc($res_total_units)['total'] ?? 0;

// Binibilang ang mga unit na may active bill transactions sa pamamagitan ng pag-uugnay ng bills sa user records
$res_occ = mysqli_query($conn, "SELECT COUNT(DISTINCT u.assigned_unit_id) as total FROM bills b JOIN users u ON b.user_id = u.id WHERE MONTH(b.due_date) = '$selected_month' AND YEAR(b.due_date) = '$selected_year' AND u.assigned_unit_id IS NOT NULL");
$occupied_count = mysqli_fetch_assoc($res_occ)['total'] ?? 0;

if ($occupied_count > $total_units) {
    $occupied_count = $total_units;
}

$occupancy_rate = ($total_units > 0) ? round(($occupied_count / $total_units) * 100) : 0;

// 4. Maintenance Issues (Filtered by selected month and year)
$res_issues = mysqli_query($conn, "SELECT COUNT(*) as total FROM maintenance WHERE status = 'Pending' AND MONTH(request_date) = '$selected_month' AND YEAR(request_date) = '$selected_year'");
$pending_issues = mysqli_fetch_assoc($res_issues)['total'] ?? 0;

// Recent Transactions Table (Filtered by selected month and year)
$transactions = mysqli_query($conn, "SELECT b.*, u.full_name FROM bills b JOIN users u ON b.user_id = u.id WHERE b.status = 'Paid' AND MONTH(b.date_paid) = '$selected_month' AND YEAR(b.date_paid) = '$selected_year' ORDER BY b.date_paid DESC LIMIT 15");
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Monthly Report - <?php echo $current_month_year; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        :root { 
            --teal-accent: #008080; 
            --bg: #f8fafc; 
            --card-bg: #ffffff; 
            --text-main: #1e293b; 
            --text-muted: #64748b; 
            --border-color: #e2e8f0;
            --summary-bg: #f1f5f9;
            --table-header: #f8fafc;
        }
        [data-theme="dark"] { 
            --bg: #0f172a; 
            --card-bg: #1e293b; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8;
            --border-color: #334155;
            --summary-bg: #111827;
            --table-header: #111827;
        }
        
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); transition: 0.3s ease; padding-top: 30px; margin: 0; }
        
        h1, h2, h3, h4, h5, h6, .text-main, .fw-800 { color: var(--text-main) !important; }
        .text-muted { color: var(--text-muted) !important; }

        .report-wrapper { max-width: 950px; margin: auto; background: var(--card-bg); padding: 50px; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid var(--border-color); transition: 0.3s ease; }
        .header-line { border-bottom: 3px solid var(--teal-accent); padding-bottom: 24px; margin-bottom: 35px; }
        
        .stat-box { 
            border: 1px solid var(--border-color); 
            border-radius: 18px; 
            padding: 22px; 
            background: var(--card-bg);
            height: 100%;
        }
        .stat-label { font-size: 0.68rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.5rem; font-weight: 800; }
        
        .summary-box { background: var(--summary-bg); border-radius: 20px; padding: 26px; line-height: 1.8; font-size: 0.98rem; color: var(--text-main) !important; border: 1px solid var(--border-color); }
        .summary-box strong { color: var(--text-main) !important; }
        .summary-box strong.text-success { color: #10b981 !important; }
        .summary-box strong.text-danger { color: #ef4444 !important; }
        
        .table { color: #1e293b !important; }
        .table thead th { background-color: var(--teal-accent) !important; color: #ffffff !important; font-weight: 700; border: none !important; padding: 12px 15px; }
        .table tbody tr { background-color: #ffffff !important; }
        .table tbody td { border-bottom: 1px solid #e2e8f0 !important; color: #1e293b !important; padding: 14px 15px; }
        
        .badge-category { background-color: #f1f5f9 !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; font-weight: 700; padding: 5px 10px; border-radius: 8px; }

        #theme-toggle { width: 42px; height: 42px; border-radius: 50%; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--teal-accent); cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }

        @media print {
            body { background: #ffffff !important; color: #000000 !important; padding-top: 0; }
            .no-print { display: none !important; }
            .report-wrapper { box-shadow: none !important; width: 100% !important; max-width: 100% !important; border: none !important; padding: 0 !important; background: #ffffff !important; }
            .stat-box { border: 1px solid #cbd5e1 !important; background: #ffffff !important; }
            .summary-box { background: #f8fafc !important; border: 1px solid #cbd5e1 !important; color: #000000 !important; }
            .stat-value { color: #000000 !important; }
            .table thead th { background-color: #f1f5f9 !important; color: #000000 !important; border-bottom: 2px solid #000000 !important; }
            .text-main, h2, h5, .table tbody td, .fw-bold { color: #000000 !important; }
        }

        @media (max-width: 768px) {
            .report-wrapper { padding: 25px; margin: 12px; border-radius: 16px; }
            .header-line { flex-direction: column; text-align: center; gap: 12px; }
            .header-line .text-end { text-align: center !important; }
            .stat-value { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

<div class="container mb-4 no-print d-flex justify-content-between align-items-center flex-wrap gap-3" style="max-width: 950px; padding: 0 12px;">
    <div class="d-flex align-items-center gap-2">
        <a href="admin_dashboard.php" class="btn btn-light border d-inline-flex align-items-center gap-2 px-4 fw-bold" style="border-radius:12px; background:var(--card-bg); color:var(--text-main); border-color:var(--border-color) !important;">
            <i class="fa fa-arrow-left"></i> Dashboard
        </a>
        
        <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
            <input type="month" name="report_month" class="form-control border fw-bold" value="<?php echo $target_month_input; ?>" style="border-radius:12px; background:var(--card-bg); color:var(--text-main); border-color:var(--border-color) !important; width:170px;" onchange="this.form.submit()">
        </form>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <button id="theme-toggle" title="Switch Theme">
            <i class="fa <?php echo ($current_theme === 'light') ? 'fa-moon' : 'fa-sun'; ?>"></i>
        </button>
        <button onclick="window.print()" class="btn btn-dark d-inline-flex align-items-center gap-2 px-4 fw-bold shadow-sm" style="border-radius:12px; background:#111827; border:none;">
            <i class="fa fa-print"></i> Print to PDF
        </button>
    </div>
</div>

<div class="report-wrapper">
    <div class="header-line d-flex justify-content-between align-items-end">
        <div class="text-start">
            <h2 class="fw-800 mb-0">MG'S <span style="color: var(--teal-accent);">APARTMENT</span></h2>
            <p class="text-muted small mb-0 fw-600">Monthly Performance &amp; Financial Ledger Report</p>
        </div>
        <div class="text-end">
            <h5 class="fw-800 mb-0" style="font-size:1.2rem;"><?php echo $current_month_year; ?></h5>
            <small class="text-muted fw-500" style="font-size: 0.72rem;">Generated on: <?php echo $report_date; ?></small>
        </div>
    </div>

    <div class="row g-3 mb-5 text-start">
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-label">Total Revenue (<?php echo date('M', strtotime($target_month_input)); ?>)</div>
                <div class="stat-value text-success">₱<?php echo number_format($monthly_revenue, 2); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-label">Outstanding Receivables</div>
                <div class="stat-value text-danger">₱<?php echo number_format($total_receivables, 2); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-label">Occupancy Rate</div>
                <div class="stat-value text-primary"><?php echo $occupancy_rate; ?>%</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-label">Pending Issues</div>
                <div class="stat-value text-warning"><?php echo $pending_issues; ?></div>
            </div>
        </div>
    </div>

    <div class="mb-5 text-start">
        <h6 class="fw-800 mb-3 text-uppercase small" style="letter-spacing: 0.8px; font-size:0.75rem;">
            <i class="fa fa-file-invoice me-2" style="color: var(--teal-accent);"></i> Executive Performance Summary
        </h6>
        <div class="summary-box">
            For the month of <strong><?php echo $current_month_name; ?></strong>, MG's Apartment recorded a total collected revenue of 
            <strong class="text-success">₱<?php echo number_format($monthly_revenue, 2); ?></strong>. 
            The property currently maintains an occupancy rate of <strong><?php echo $occupancy_rate; ?>%</strong> 
            (<strong><?php echo $occupied_count; ?></strong> out of <strong><?php echo $total_units; ?></strong> total units). 
            There are outstanding receivables amounting to <strong class="text-danger">₱<?php echo number_format($total_receivables, 2); ?></strong> 
            which require active collection follow-up, and <strong><?php echo $pending_issues; ?></strong> tenant maintenance 
            requests that are currently being processed in the operations layout.
        </div>
    </div>

    <div class="mb-5 text-start">
        <h6 class="fw-800 mb-3 text-uppercase small" style="letter-spacing: 0.8px; font-size:0.75rem;">
            <i class="fa fa-receipt me-2" style="color: var(--teal-accent);"></i> Monthly Collections Transaction Audit
        </h6>
        <div class="table-responsive rounded-3 overflow-hidden border">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                <thead>
                    <tr>
                        <th class="py-3 ps-3">Date Paid</th>
                        <th class="py-3">Tenant Remitter</th>
                        <th class="py-3">Bill Category</th>
                        <th class="py-3 text-end pe-3">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($transactions, 0); if(mysqli_num_rows($transactions) > 0): 
                        while($row = mysqli_fetch_assoc($transactions)): ?>
                        <tr>
                            <td class="fw-semibold ps-3"><?php echo date('M d, Y', strtotime($row['date_paid'])); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><span class="badge badge-category"><?php echo htmlspecialchars($row['bill_type']); ?></span></td>
                            <td class="text-end fw-bold pe-3">₱<?php echo number_format($row['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted fw-600">No paid transaction entries found recorded for this tracking month window.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 pt-4 border-top text-center text-muted" style="font-size: 0.78rem; border-color: var(--border-color) !important;">
        <p class="mb-0 fw-bold text-uppercase" style="letter-spacing:0.5px;">End of Monthly Business Performance Statement Report</p>
        <p class="mb-0">MG's Apartment Management System • Generated via Admin Control Portal Panel</p>
    </div>
</div>

<script>
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
</body>
</html>