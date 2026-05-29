<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'tenant'; 

// 1. KUNIN ANG KINAKAILANGANG QUERY BASE SA PARAMETERS
if (isset($_GET['ref'])) {
    $ref = mysqli_real_escape_string($conn, $_GET['ref']);
    $items_query = "SELECT b.*, u.full_name, un.unit_no 
                    FROM bills b 
                    JOIN users u ON b.user_id = u.id 
                    LEFT JOIN units un ON u.assigned_unit_id = un.id 
                    WHERE b.reference_no = '$ref'";
} else if (isset($_GET['id'])) {
    $bill_id = mysqli_real_escape_string($conn, $_GET['id']);
    $items_query = "SELECT b.*, u.full_name, un.unit_no 
                    FROM bills b 
                    JOIN users u ON b.user_id = u.id 
                    LEFT JOIN units un ON u.assigned_unit_id = un.id 
                    WHERE b.reference_no = (SELECT reference_no FROM bills WHERE id = '$bill_id')";
} else if (isset($_GET['user']) && isset($_GET['date'])) {
    $u_id = mysqli_real_escape_string($conn, $_GET['user']);
    $date = mysqli_real_escape_string($conn, $_GET['date']);
    $items_query = "SELECT b.*, u.full_name, un.unit_no 
                    FROM bills b 
                    JOIN users u ON b.user_id = u.id 
                    LEFT JOIN units un ON u.assigned_unit_id = un.id 
                    WHERE b.user_id = '$u_id' AND DATE(b.date_paid) = '$date'";
} else {
    die("<div style='text-align:center; padding:50px;'><h3>Receipt parameters are missing.</h3></div>");
}

$res = mysqli_query($conn, $items_query);
$items = [];
while($row = mysqli_fetch_assoc($res)) {
    $items[] = $row;
}

if (empty($items)) {
    die("<div style='text-align:center; padding:50px;'><h3>Transaction not found.</h3></div>");
}

$data = $items[0];

// 2. SECURITY GATE: KUNG TENANT ANG TUMITINGIN AT HINDI PA 'PAID', ILOCK ANG RESIBO
if ($user_role !== 'admin' && $data['status'] !== 'Paid') {
    echo "<script>
            alert('Access Denied: This receipt is unavailable. Your payment is either unpaid or currently waiting for admin validation.'); 
            window.location.href = 'tenant_bills.php'; 
          </script>";
    exit();
}

$full_name      = htmlspecialchars($data['full_name']);
$unit_no        = (!empty($data['unit_no'])) ? htmlspecialchars($data['unit_no']) : 'TBA';
$receipt_no     = str_pad($data['id'], 5, '0', STR_PAD_LEFT);
$date_paid      = ($data['date_paid']) ? date('M d, Y', strtotime($data['date_paid'])) : date('M d, Y');
$payment_method = htmlspecialchars($data['method'] ?? ($data['payment_method'] ?? 'GCash'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Receipt #<?php echo $receipt_no; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; color: #1e293b; }
        
        .action-bar {
            background: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 550px;
            margin: 24px auto 0 auto;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .receipt-container { max-width: 550px; margin: 16px auto; padding: 10px; }
        .receipt-card { background: #ffffff; padding: 40px; border-radius: 24px; position: relative; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        
        .teal-text { color: #008080 !important; } 
        .btn-teal { background: #008080; color: white !important; border: none; font-weight: 700; border-radius: 12px; padding: 10px 24px; transition: 0.2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-teal:hover { background: #006666; transform: translateY(-1px); }
        .btn-secondary-custom { background: #475569; color: white !important; border: none; font-weight: 700; border-radius: 12px; padding: 10px 24px; text-decoration: none; font-size: 0.9rem; transition: 0.2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary-custom:hover { background: #334155; transform: translateY(-1px); }

        .paid-stamp { position: absolute; top: 110px; right: 35px; border: 4px solid #16a34a; color: #16a34a; padding: 6px 20px; font-weight: 900; font-size: 2rem; transform: rotate(-15deg); opacity: 0.18; border-radius: 14px; text-transform: uppercase; pointer-events: none; letter-spacing: 2px; }
        .info-label { color: #94a3b8; font-size: 0.68rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.8px; margin-bottom: 4px; }
        .method-box { background: #f8fafc; border-radius: 16px; padding: 16px; border: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-around; }
        .divider { border-top: 1px dashed #e2e8f0; margin: 24px 0; }
        .fw-800 { font-weight: 800; }

        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .receipt-container { margin: 0; max-width: 100%; padding: 0; }
            .receipt-card { box-shadow: none; border: none; border-radius: 0; padding: 0; }
            .paid-stamp { opacity: 0.3; }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <?php if ($user_role === 'admin'): ?>
        <a href="billing.php" class="btn-secondary-custom"><i class="fa fa-arrow-left"></i> Close Panel</a>
    <?php else: ?>
        <a href="tenant_bills.php" class="btn-secondary-custom"><i class="fa fa-arrow-left"></i> My Bills</a>
    <?php endif; ?>
    <button onclick="window.print()" class="btn-teal"><i class="fa fa-print"></i> Print Receipt</button>
</div>

<div class="receipt-container">
    <div class="receipt-card" id="printableReceipt">
        <div class="paid-stamp">PAID</div>
        
        <div class="text-center mb-2">
            <h4 class="fw-800 teal-text mb-1" style="letter-spacing: -0.5px;">MG'S APARTMENT</h4>
            <p class="text-muted small fw-bold mb-0" style="letter-spacing: 0.5px; font-size: 0.72rem;">ELECTRONIC PAYMENT RECEIPT</p>
        </div>
        
        <div class="divider"></div>
        
        <div class="row mb-4">
            <div class="col-6 text-start">
                <div class="info-label">Tenant</div>
                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;"><?php echo $full_name; ?></h6>
                <p class="small text-muted mb-0" style="font-weight: 500;">Unit Address: <strong class="text-dark"><?php echo $unit_no; ?></strong></p>
            </div>
            <div class="col-6 text-end">
                <div class="info-label">Date Remitted</div>
                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;"><?php echo $date_paid; ?></h6>
                <p class="small text-muted mb-0" style="font-weight: 500;">Receipt ID: <strong class="text-dark">#<?php echo $receipt_no; ?></strong></p>
            </div>
        </div>
        
        <div class="method-box mb-4">
            <div class="text-center px-2">
                <div class="info-label">Method</div>
                <div class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fa-solid fa-money-bill-transfer teal-text me-1"></i> <?php echo $payment_method; ?></div>
            </div>
            <div style="width: 1px; height: 30px; background: #cbd5e1;"></div>
            <div class="text-center px-2">
                <div class="info-label">Reference ID</div>
                <div class="fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px;"><?php echo htmlspecialchars($data['reference_no'] ?? 'N/A'); ?></div>
            </div>
        </div>
        
        <table class="table table-borderless mb-0">
            <thead>
                <tr style="border-bottom: 2px solid #f1f5f9;"><th class="info-label px-0 text-start">Description</th><th class="info-label text-end px-0">Amount Ledger</th></tr>
            </thead>
            <tbody>
                <?php $total = 0; foreach($items as $item): $amt = $item['amount'] + ($item['penalty'] ?? 0); $total += $amt; ?>
                <tr>
                    <td class="py-2.5 px-0 text-start">
                        <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($item['bill_type']); ?> Allocation</div>
                    </td>
                    <td class="py-2.5 px-0 text-end fw-bold text-dark" style="font-size: 0.9rem;">₱<?php echo number_format($amt, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 1px solid #cbd5e1;">
                    <td class="pt-3 px-0 fw-bold text-muted small text-start" style="letter-spacing: 0.5px;">TOTAL SETTLED</td>
                    <td class="pt-3 px-0 text-end fw-bold fs-5 teal-text">₱<?php echo number_format($total, 2); ?></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="text-center mt-5 pt-3 border-top" style="border-color: #f1f5f9 !important;">
            <p class="text-muted fw-500 mb-0" style="font-size: 0.7rem; line-height: 1.4;">This is an official system-generated payment summary acknowledgment.<br>No physical signature required.</p>
        </div>
    </div>
</div>

</body>
</html>