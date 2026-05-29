<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit();
}

mysqli_set_charset($conn, "utf8mb4");
$user_id = $_SESSION['user_id'];
$bill_id = isset($_GET['bill_id']) ? mysqli_real_escape_string($conn, $_GET['bill_id']) : 0;

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

// Secure Context Isolation Query Hook
$query = "SELECT b.*, ut.unit_no FROM bills b 
          JOIN users u ON b.user_id = u.id 
          LEFT JOIN units ut ON u.assigned_unit_id = ut.id 
          WHERE b.id = '$bill_id' AND b.user_id = '$user_id'";
if (!empty($property_id)) { $query .= " AND b.property_id = '$property_id'"; }
$query .= " LIMIT 1";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) { header("Location: tenant_bills.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment Portal</title>
    
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="uploads/logo.jpg">
    <meta name="theme-color" content="#008080">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --teal: #008080; 
            --gcash: #007dfe; 
            --bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-muted: #64748b;
            --input-bg: #ffffff;
            --border-color: #cbd5e1;
            --panel-bg: #f8fafc;
        }
        [data-theme="dark"] {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: #334155;
            --border-color: #475569;
            --panel-bg: #111827;
        }
        
        body { background-color: var(--bg); font-family: 'Inter', sans-serif; color: var(--text-main); transition: 0.3s; padding-top: 10px; }
        
        /* Heading Overrides */
        h1, h2, h3, h4, h5, h6, .text-main, .header-title { color: var(--text-main) !important; }
        
        .payment-card { 
            background: var(--card-bg); border-radius: 20px; overflow: hidden; 
            max-width: 380px; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            border: 1px solid var(--border-color);
        }

        .card-header-small {
            background: linear-gradient(135deg, var(--teal), #004d4d);
            padding: 12px 15px; position: relative; color: white; text-align: center;
        }
        .back-link { 
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: white !important; text-decoration: none; font-size: 0.9rem;
        }
        .header-title { font-size: 0.95rem; font-weight: 700; margin: 0; color: white !important; }
        .unit-pill-small {
            background: #ffce00; color: #000 !important; font-weight: 800;
            padding: 2px 12px; border-radius: 50px; display: inline-block;
            margin-top: 4px; font-size: 0.75rem;
        }

        .qr-section { text-align: center; padding: 12px; background: var(--panel-bg); border-radius: 15px; border: 1px solid var(--border-color); }
        
        .account-row {
            display: flex; align-items: center; justify-content: space-between;
            background: var(--card-bg); border: 1.2px solid var(--border-color); border-radius: 12px;
            padding: 8px 12px; margin-bottom: 10px;
        }
        .acc-label { font-weight: 700; font-size: 0.75rem; color: var(--text-muted) !important; white-space: nowrap; }
        .acc-number { font-weight: 800; font-size: 0.8rem; color: var(--gcash); cursor: pointer; }

        .btn-gcash-action {
            background: var(--gcash); color: white !important;
            padding: 10px; border-radius: 12px; font-weight: 700;
            text-decoration: none; font-size: 0.8rem; border: none;
            width: 100%; display: block; margin-top: 10px; transition: 0.2s;
        }
        .btn-gcash-action:hover { background: #0066d6; }

        .amount-row {
            display: flex; justify-content: space-between; align-items: center;
            background: var(--panel-bg); padding: 10px 15px; border-radius: 12px; margin: 12px 0; border: 1px solid var(--border-color);
        }
        .text-teal { color: var(--teal) !important; }
        
        .btn-submit-pro {
            background: var(--teal); color: white !important; border: none; width: 100%;
            padding: 14px; border-radius: 14px; font-weight: 800; font-size: 0.95rem; transition: 0.2s;
        }
        .btn-submit-pro:hover { background: #006666; }

        .toast-msg {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            background: #333; color: white; padding: 8px 20px; border-radius: 50px;
            font-size: 0.7rem; display: none; z-index: 9999;
        }
        
        .form-select, .form-control { 
            background-color: var(--input-bg) !important; 
            border-color: var(--border-color) !important; 
            color: var(--text-main) !important; 
            border-radius: 10px; 
            font-size: 0.85rem; 
        }
        .form-control::placeholder { color: var(--text-muted); opacity: 0.7; }
    </style>
</head>
<body>

<div id="copyToast" class="toast-msg shadow">✅ Number Copied!</div>

<div class="container-wrapper">
    <div class="payment-card">
        <div class="card-header-small">
            <a href="tenant_bills.php" class="back-link"><i class="fa fa-chevron-left"></i></a>
            <h2 class="header-title">Confirm Payment</h2>
            <div class="unit-pill-small">UNIT <?php echo htmlspecialchars($row['unit_no']); ?></div>
        </div>

        <div class="card-body p-3">
            <form action="process_payment.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="bill_id" value="<?php echo $row['id']; ?>">

                <div class="mb-3">
                    <label class="small fw-bold text-uppercase mb-1 d-block" style="font-size: 0.65rem; color: var(--text-muted);">1. Choose Method</label>
                    <select name="payment_method" id="payMethod" class="form-select fw-bold" onchange="updateUI()" required>
                        <option value="GCash">GCash Mobile</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash (Hand-over)</option>
                    </select>
                </div>

                <div id="qrContainer" class="qr-section shadow-sm mb-2">
                    <div class="account-row">
                        <span class="acc-label" id="providerName">MARY GRACE</span>
                        <div class="acc-number" onclick="copyNum()">
                            <span id="accountNo">0912 345 6789</span>
                            <i class="fa fa-copy ms-2 opacity-50"></i>
                        </div>
                    </div>

                    <img id="qrImage" src="uploads/img/gcash_qr.jpg" class="img-fluid rounded border" style="width: 110px; border-color: var(--border-color) !important;">
                    
                    <button type="button" id="gcashLink" onclick="openGcashApp()" class="btn-gcash-action">
                        <i class="fa-solid fa-mobile-screen-button me-2"></i> OPEN GCASH APP
                    </button>
                </div>

                <div class="amount-row">
                    <span class="small fw-bold" style="color: var(--text-muted);">Total Due</span>
                    <span class="fw-bold fs-5 text-teal">₱<?php echo number_format($row['amount'], 2); ?></span>
                </div>

                <div id="onlineInputs">
                    <div class="mb-2">
                        <label id="refLabel" class="small fw-bold text-uppercase mb-1 d-block" style="font-size: 0.65rem; color: var(--text-muted);">Reference No. (13-Digit)</label>
                        <input type="text" name="reference_no" id="refNoInput" class="form-control" 
                               placeholder="13-digit number" 
                               pattern="\d*" minlength="13" maxlength="13" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-uppercase mb-1 d-block" style="font-size: 0.65rem; color: var(--text-muted);">Proof of Payment</label>
                        <input type="file" name="proof_img" id="proof_img" class="form-control" accept="image/*" required>
                    </div>
                </div>

                <div id="cashNote" style="display: none;" class="alert alert-warning border-0 small mb-3 py-2" style="font-size: 0.7rem; background: rgba(245, 158, 11, 0.15); color: #d97706;">
                    <i class="fa fa-info-circle me-1"></i> Pakibayaran po si Ms. Mary Grace personally.
                </div>

                <button type="submit" name="submit_payment" class="btn-submit-pro shadow-sm">
                    SUBMIT PAYMENT
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function copyNum() {
        const num = document.getElementById('accountNo').innerText.replace(/\s/g, '');
        const temp = document.createElement("input");
        temp.value = num;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand("copy");
        document.body.removeChild(temp);
        
        const toast = document.getElementById('copyToast');
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 2000);
    }

    function openGcashApp() {
        window.location.replace("gcash://entry");
        setTimeout(function() {
            if (confirm("GCash didn't open. Copy the number instead?")) { copyNum(); }
        }, 2500);
    }

    function updateUI() {
        const method = document.getElementById('payMethod').value;
        const online = document.getElementById('onlineInputs');
        const cash = document.getElementById('cashNote');
        const qr = document.getElementById('qrContainer');
        const link = document.getElementById('gcashLink');
        const refInput = document.getElementById('refNoInput');
        const refLabel = document.getElementById('refLabel');

        if (method === 'Cash') {
            online.style.display = 'none'; qr.style.display = 'none'; cash.style.display = 'block';
            refInput.required = false; 
            document.getElementById('proof_img').required = false;
        } else {
            online.style.display = 'block'; qr.style.display = 'block'; cash.style.display = 'none';
            refInput.required = true; 
            document.getElementById('proof_img').required = true;
            
            if(method === 'Bank Transfer') {
                document.getElementById('providerName').innerText = "BDO UNIBANK";
                document.getElementById('accountNo').innerText = "0012 3456 7890";
                document.getElementById('qrImage').src = "uploads/img/bank_qr.jpg";
                link.style.display = 'none';
                
                // Dynamic mapping change for bank parameters
                refLabel.innerText = "Reference / Transaction No. (10-12 Digit)";
                refInput.placeholder = "Enter Bank Ref Number";
                refInput.removeAttribute('minlength');
                refInput.removeAttribute('maxlength');
                refInput.setAttribute('minlength', '10');
                refInput.setAttribute('maxlength', '12');
            } else {
                document.getElementById('providerName').innerText = "MARY GRACE";
                document.getElementById('accountNo').innerText = "0912 345 6789";
                document.getElementById('qrImage').src = "uploads/img/gcash_qr.jpg";
                link.style.display = 'block';
                
                // Revert to GCash validation criteria rules
                refLabel.innerText = "Reference No. (13-Digit)";
                refInput.placeholder = "13-digit number";
                refInput.removeAttribute('minlength');
                refInput.removeAttribute('maxlength');
                refInput.setAttribute('minlength', '13');
                refInput.setAttribute('maxlength', '13');
            }
        }
    }
</script>

</body>
</html>a