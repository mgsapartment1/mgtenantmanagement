<?php
include('db.php');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: billing.php");
    exit();
}

$bill_id = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT b.*, u.full_name 
          FROM bills b 
          JOIN users u ON b.user_id = u.id 
          WHERE b.id = '$bill_id'";
$result = mysqli_query($conn, $query);
$bill = mysqli_fetch_assoc($result);

if (!$bill) {
    die("Billing record not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Payment | MGs Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .card { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .payment-header { background: #008080; color: white; padding: 20px; border-radius: 15px 15px 0 0; }
        .amount-val { font-size: 1.8rem; font-weight: 900; color: #008080; line-height: 1;}
        .proof-container { 
            background: #fff; border: 2px dashed #cbd5e1; border-radius: 15px; 
            padding: 20px; min-height: 300px; display: flex; align-items: center; justify-content: center;
        }
        .proof-img { max-width: 100%; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card">
                <div class="payment-header text-center">
                    <h4 class="fw-bold mb-0">Payment Verification</h4>
                    <span class="small opacity-75">Tenant: <?php echo htmlspecialchars($bill['full_name']); ?></span>
                </div>
                
                <div class="card-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-6 text-center border-end">
                            <label class="small fw-bold text-muted text-uppercase">Amount to Verify</label>
                            <div class="amount-val">₱<?php echo number_format($bill['amount'], 2); ?></div>
                            <span class="badge bg-dark">Method: <?php echo strtoupper($bill['payment_method']); ?></span>
                        </div>
                        <div class="col-md-6 text-center">
                            <label class="small fw-bold text-muted text-uppercase">Reference Number</label>
                            <h4 class="fw-bold text-primary mt-2"><?php echo htmlspecialchars($bill['reference_no'] ?? 'N/A'); ?></h4>
                        </div>
                    </div>

                    <label class="small fw-bold text-muted text-uppercase d-block mb-2">Proof of Payment</label>
                    <div class="proof-container">
                        <?php 
                        $file = $bill['proof_of_payment'];
                        $final_path = "uploads/" . $file;

                        if ($bill['payment_method'] === 'Cash'): ?>
                            <div class="text-center">
                                <i class="fa fa-hand-holding-dollar fa-4x text-warning mb-3"></i>
                                <h5 class="fw-bold">CASH PAYMENT</h5>
                                <p class="text-muted">No digital proof image required for cash hand-over.</p>
                            </div>
                        <?php elseif (!empty($file) && file_exists($final_path)): ?>
                            <a href="<?php echo $final_path; ?>" target="_blank">
                                <img src="<?php echo $final_path; ?>" class="proof-img" alt="Receipt">
                            </a>
                        <?php else: ?>
                            <div class="text-center text-danger">
                                <i class="fa fa-image-slash fa-4x mb-3 opacity-25"></i>
                                <h6 class="fw-bold">Image Not Found</h6>
                                <p class="small text-muted">Check filename: <code>uploads/<?php echo htmlspecialchars($file); ?></code></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="approve_payment.php" method="POST" class="mt-4">
                        <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <button type="submit" name="action" value="approve" class="btn btn-success w-100 py-3 fw-bold shadow-sm" onclick="return confirm('Approve this payment and send official receipt to tenant email?')">
                                    <i class="fa fa-check-circle me-2"></i> APPROVE PAYMENT
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger w-100 py-3 fw-bold" onclick="return confirm('Reject this payment request?')">
                                    REJECT
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="billing.php" class="text-muted text-decoration-none small">
                            <i class="fa fa-arrow-left me-1"></i> Back to Billing List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>