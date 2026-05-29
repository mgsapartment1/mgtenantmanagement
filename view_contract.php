<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Kunin ang data ni tenant at unit details
$query = "SELECT u.*, un.unit_no, un.monthly_rent 
          FROM users u 
          LEFT JOIN units un ON u.assigned_unit_id = un.id 
          WHERE u.id = '$user_id'";
$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

$tenant_name = strtoupper($data['full_name'] ?? '');
$unit_no = $data['unit_no'] ?? "TBA";
$rent = number_format($data['monthly_rent'] ?? 0, 2);

// Sinigurado nating babasahin nito kung 'contract_signed_at' o 'contract_signed_date' ang gamit sa DB
$raw_date = !empty($data['contract_signed_at']) ? $data['contract_signed_at'] : (!empty($data['contract_signed_date']) ? $data['contract_signed_date'] : null);
$signed_date = $raw_date ? date('F d, Y', strtotime($raw_date)) : date('F d, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Lease Agreement | MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e9ecef; font-family: 'Times New Roman', Times, serif; }
        .contract-wrapper {
            background: white;
            padding: 80px;
            margin: 50px auto;
            max-width: 850px;
            position: relative;
            box-shadow: 0 0 30px rgba(0,0,0,0.15);
            border: 1px solid #ccc;
        }
        /* Watermark Background */
        .contract-wrapper::before {
            content: "OFFICIAL COPY";
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px; color: rgba(0,0,0,0.02);
            white-space: nowrap; pointer-events: none;
            z-index: 0;
        }
        .contract-header { text-align: center; margin-bottom: 40px; border-bottom: 3px double #333; padding-bottom: 20px; }
        .section-title { font-weight: bold; text-transform: uppercase; margin-top: 25px; border-bottom: 1px solid #eee; display: block; }
        .indent { padding-left: 30px; text-align: justify; line-height: 1.8; }
        .signature-box { margin-top: 60px; z-index: 10; position: relative; }
        .sig-line { border-bottom: 2px solid #333; display: inline-block; width: 250px; margin-bottom: 5px; }
        
        /* Tinanggal ko ang margin sa print para saktong bond paper */
        @media print {
            body { background: white; }
            .contract-wrapper { box-shadow: none; border: none; margin: 0; width: 100%; padding: 40px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container text-center no-print mt-4">
    <button onclick="window.print()" class="btn btn-dark rounded-pill px-4">
        <i class="fa fa-print me-2"></i> Print / Save as PDF
    </button>
</div>

<div class="contract-wrapper">
    <div class="contract-header">
        <p class="mb-0 small text-uppercase">Republic of the Philippines</p>
        <h2 class="fw-bold">LEASE CONTRACT</h2>
        <p class="mb-0"><strong>MG'S APARTMENT RENTAL SERVICES</strong></p>
        <p class="small text-muted">Contract Ref: MG-<?php echo date('Y'); ?>-<?php echo str_pad($user_id, 4, '0', STR_PAD_LEFT); ?></p>
    </div>

    <p><strong>KNOW ALL MEN BY THESE PRESENTS:</strong></p>
    <p class="indent">
        This Contract of Lease, made and entered into this <strong><?php echo $signed_date; ?></strong>, by and between 
        <strong>MG'S APARTMENT</strong>, hereinafter referred to as the <strong>LESSOR</strong>, and <strong><?php echo $tenant_name; ?></strong>, of legal age, hereinafter referred to as the <strong>LESSEE</strong>.
    </p>

    <span class="section-title">Section 1. The Property</span>
    <p class="indent">The LESSOR hereby leases unto the LESSEE <strong>Unit No. <?php echo $unit_no; ?></strong> located at MG Rental Premises. The LESSEE acknowledges that the unit is in good and habitable condition.</p>

    <span class="section-title">Section 2. Monthly Rental</span>
    <p class="indent">The agreed monthly rental for the leased premises is <strong>PESOS: ₱<?php echo $rent; ?></strong>, Philippine Currency. All payments must be settled through the official tenant portal on or before the designated due date.</p>

    <span class="section-title">Section 3. Security Deposit & Terms</span>
    <p class="indent">The LESSEE agrees to abide by all house rules and regulations. Illegal activities, subleasing without consent, and property damage are grounds for immediate termination. The security deposit shall be used to cover unpaid bills or repairs upon move-out.</p>

    <div class="row signature-box">
        <div class="col-6 text-center">
            <div class="sig-line"></div>
            <p class="mb-0 small"><strong>MARIA GRACE</strong></p>
            <p class="small text-muted">Lessor / Owner Signature</p>
        </div>
        <div class="col-6 text-center">
            <div class="position-relative">
                <?php if(!empty($data['digital_signature'])): ?>
                    <img src="<?php echo $data['digital_signature']; ?>" style="width: 180px; position: absolute; top: -75px; left: 50%; transform: translateX(-50%);">
                <?php endif; ?>
                <div class="sig-line"></div>
            </div>
            <p class="mb-0 small"><strong><?php echo $tenant_name; ?></strong></p>
            <p class="small text-muted">Lessee / Tenant Signature</p>
        </div>
    </div>

    <div class="mt-5 text-center small text-muted border-top pt-3">
        <p>This is a system-generated document. Digital signatures are legally binding under R.A. 8792 <br> (Electronic Commerce Act of 2000).</p>
    </div>
</div>

</body>
</html>