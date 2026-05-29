<?php
include('db.php');
session_start();

$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, un.unit_no, un.monthly_rent 
          FROM users u 
          LEFT JOIN units un ON u.assigned_unit_id = un.id 
          WHERE u.id = '$user_id'";
$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

$tenant_name = strtoupper($data['full_name']);
$unit_no = $data['unit_no'] ?? "TBA";
$rent = number_format($data['monthly_rent'], 2);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Digital Lease Agreement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .contract-box {
            background: white;
            padding: 50px;
            border: 1px solid #dee2e6;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
        }
        .signature-line { border-bottom: 2px solid #000; width: 250px; display: inline-block; }
    </style>
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="contract-box mx-auto col-lg-9">
        <h2 class="text-center fw-bold">LEASE AGREEMENT</h2>
        <p class="text-end">Date: <strong><?php echo date('F d, Y'); ?></strong></p>

        <h5><strong>THE PARTIES:</strong></h5>
        <p><strong>LESSOR (Owner):</strong> MG's Apartment<br>
        <strong>LESSEE (Tenant):</strong> <?php echo $tenant_name; ?></p>

        <h5><strong>PROPERTY DESCRIPTION:</strong></h5>
        <p>The Tenant hereby leases Unit <strong><?php echo $unit_no; ?></strong> located at MG's Rental Premises.</p>

        <h5><strong>RENT:</strong></h5>
        <p>Monthly rent: <strong>₱<?php echo $rent; ?></strong><br>
        Payment must be made no later than the due date set in the portal.</p>

        <hr>
        <h5><strong>TERMS AND CONDITIONS:</strong></h5>
        <ul>
            <li>Illegal activities like gambling and illegal drugs are strictly prohibited.</li>
            <li>Subleasing is not allowed without written consent.</li>
            <li>Security deposit is equivalent to 1 month rent.</li>
        </ul>

        <div class="mt-5 p-4 bg-light border-start border-4 border-primary">
            <h5><strong>Digital Signature & Confirmation</strong></h5>
            <form action="process_contract.php" method="POST">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="agree" required>
                    <label class="form-check-label" for="agree">
                        I, <strong><?php echo $tenant_name; ?></strong>, hereby agree to all terms and conditions stated in this Lease Agreement. I understand that this digital confirmation serves as my official signature.
                    </label>
                </div>
                <button type="submit" name="sign_contract" class="btn btn-primary btn-lg w-100">SIGN AND PROCEED</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>