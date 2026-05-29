<?php
include('db.php');
session_start();

if (!isset($_GET['id'])) {
    header("Location: billing_records.php");
    exit();
}

$bill_id = $_GET['id'];
$query = "SELECT b.*, u.full_name, u.contact 
          FROM bills b 
          JOIN users u ON b.user_id = u.id 
          WHERE b.id = '$bill_id' AND (b.bill_type = 'Water' OR b.bill_type = 'Electricity')";
$result = mysqli_query($conn, $query);
$bill = mysqli_fetch_assoc($result);

if (!$bill) {
    echo "<script>alert('Utility record not found.'); window.location='billing_records.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Utility Receipt #<?php echo $bill_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --teal: #008080; }
        body { background-color: #f4f7f6; padding: 30px; font-family: 'Inter', sans-serif; }
        .receipt-container { 
            max-width: 700px; 
            margin: auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .teal-text { color: var(--teal); }
        .divider { border-top: 2px dashed #dee2e6; margin: 20px 0; }
        
        /* Status Stamp Logic */
        .stamp {
            position: absolute;
            top: 40px;
            right: 40px;
            padding: 10px 20px;
            border: 4px solid;
            border-radius: 10px;
            font-weight: 900;
            text-transform: uppercase;
            transform: rotate(10deg);
            opacity: 0.8;
        }
        .paid-stamp { color: #166534; border-color: #166534; }
        .unpaid-stamp { color: #991b1b; border-color: #991b1b; }
        
        .proof-img { 
            width: 100%; 
            max-width: 250px; 
            border-radius: 10px; 
            margin-top: 15px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="stamp <?php echo ($bill['status'] == 'Paid') ? 'paid-stamp' : 'unpaid-stamp'; ?>">
        <?php echo $bill['status']; ?>
    </div>

    <div class="text-center mb-4">
        <h2 class="fw-bold teal-text">MG's APARTMENT</h2>
        <p class="text-muted small">Utility Billing Statement</p>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <h6 class="fw-bold text-muted mb-1">BILL TO:</h6>
            <h5 class="fw-bold"><?php echo htmlspecialchars($bill['full_name']); ?></h5>
            <p class="small text-muted mb-0">Contact: <?php echo $bill['contact']; ?></p>
        </div>
        <div class="col-6 text-end">
            <h6 class="fw-bold text-muted mb-1">INVOICE DETAILS:</h6>
            <p class="small mb-0">Invoice #: <strong><?php echo $bill['id']; ?></strong></p>
            <p class="small mb-0">Due Date: <strong><?php echo $bill['due_date']; ?></strong></p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-center">Consumption</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span class="fw-bold"><?php echo $bill['bill_type']; ?> Bill</span><br>
                        <small class="text-muted">Rate: ₱<?php echo number_format($bill['rate'], 2); ?> per unit</small>
                    </td>
                    <td class="text-center">
                        <small class="d-block text-muted">Prev: <?php echo $bill['previous_reading']; ?></small>
                        <small class="d-block text-muted">Curr: <?php echo $bill['current_reading']; ?></small>
                        <strong>Total: <?php echo $bill['current_reading'] - $bill['previous_reading']; ?></strong>
                    </td>
                    <td class="text-end fw-bold">₱<?php echo number_format($bill['amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="divider"></div>

    <div class="row align-items-center">
        <div class="col-7">
            <h6 class="fw-bold small">PAYMENT VERIFICATION:</h6>
            <p class="small text-muted mb-1">Ref No: <strong><?php echo $bill['reference_no'] ?: '---'; ?></strong></p>
            <p class="small text-muted">Date Paid: <strong><?php echo $bill['date_paid'] ?: 'Pending'; ?></strong></p>
        </div>
        <div class="col-5 text-center">
            <?php if(!empty($bill['proof_of_payment'])): ?>
                <img src="proofs/<?php echo $bill['proof_of_payment']; ?>" class="proof-img" alt="GCash Receipt">
                <p class="mt-1" style="font-size: 10px; color: gray;">In-upload na GCash Screenshot</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-4 d-print-none">
        <button onclick="window.print()" class="btn btn-dark px-4"><i class="fa fa-print"></i> Print</button>
        <a href="billing_records.php" class="btn btn-outline-secondary px-4">Back</a>
    </div>
</div>

</body>
</html>