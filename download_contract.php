<?php
include('db.php');
session_start();

// Security Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

/** * 1. QUERY: Kunin ang data ng Tenant, Unit, at Digital Signature
 */
$query = "SELECT u.*, un.unit_no, un.monthly_rent 
          FROM users u 
          LEFT JOIN units un ON u.assigned_unit_id = un.id 
          WHERE u.id = '$user_id'";

$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

/** * 2. DATA HANDLING: Formatting para sa malinis na output
 */
// Fix para sa Undefined variable $signed_date
$signed_date = (isset($data['contract_signed_date']) && !empty($data['contract_signed_date'])) 
               ? date('F d, Y', strtotime($data['contract_signed_date'])) 
               : date('F d, Y'); 

$unit_display = $data['unit_no'] ?? 'TBA';
$rent_display = isset($data['monthly_rent']) ? number_format($data['monthly_rent'], 2) : '0.00';

/** * 3. HOUSE RULES & LEASE CONTENT
 */
$rules_res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'house_rules'");
$rules_data = mysqli_fetch_assoc($rules_res);

// formal House Rules as default kung maikli ang nasa database
$default_rules = "
    <div style='line-height: 1.6;'>
        <p><strong>I. RENTAL PAYMENTS AND DEPOSITS</strong></p>
        <ul>
            <li><strong>Monthly Rent:</strong> Rent is payable on or before the designated due date monthly.</li>
            <li><strong>Late Fees:</strong> Late payments are subject to a daily penalty of 5%.</li>
            <li><strong>Security Deposit:</strong> The security deposit shall not be applied to the final month's rent and is refundable only upon completion of a unit inspection.</li>
        </ul>

        <p><strong>II. OCCUPANCY RULES</strong></p>
        <ul>
            <li><strong>Authorized Tenants:</strong> Only authorized persons listed in the lease agreement may occupy the unit.</li>
            <li><strong>Quiet Hours:</strong> Strict silence must be observed between 10:00 PM and 7:00 AM.</li>
            <li><strong>Cleanliness:</strong> Tenants are responsible for keeping their immediate premises clean and orderly.</li>
        </ul>

        <p><strong>III. PROHIBITIONS</strong></p>
        <ul>
            <li><strong>Illegal Activities:</strong> Illegal activities of any kind are strictly forbidden on the property.</li>
            <li><strong>Pets:</strong> Written authorization from management is mandatory before bringing any pets into the unit.</li>
            <li><strong>Sub-leasing:</strong> Only registered tenants are permitted to stay; unauthorized guests or subletting without the Lessor's consent is prohibited.</li>
        </ul>

        <p><strong>IV. MAINTENANCE AND DAMAGES</strong></p>
        <ul>
            <li>Tenants are responsible for repairs or costs associated with damages caused by negligence.</li>
            <li>Please notify management immediately regarding any plumbing or electrical concerns to prevent further damage.</li>
        </ul>
    </div>
";

// Logic: Gamitin ang DB rules kung mahaba, kung hindi ay gamitin ang default
$raw_db_rules = $rules_data['setting_value'] ?? "";
$house_rules = (strlen(strip_tags($raw_db_rules)) > 50) ? $raw_db_rules : $default_rules;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lease_Contract_<?php echo str_replace(' ', '_', $data['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #525659; font-family: 'Times New Roman', serif; }
        .paper {
            width: 210mm;
            min-height: 297mm;
            padding: 25mm;
            margin: 20mm auto;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            position: relative;
        }
        .header-section { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 30px; }
        .section-title { font-weight: bold; text-decoration: underline; text-transform: uppercase; margin-top: 25px; margin-bottom: 10px; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 0 auto; }
        
        .digital-stamp {
            position: absolute; top: 40px; right: 40px; border: 3px solid #008080;
            color: #008080; padding: 10px; transform: rotate(10deg); opacity: 0.5;
            font-weight: bold; text-align: center; border-radius: 8px;
        }

        @media print {
            body { background: none; }
            .paper { margin: 0; box-shadow: none; width: 100%; padding: 15mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print text-center py-4">
    <button onclick="window.print()" class="btn btn-success btn-lg px-5 shadow">
        PRINT / SAVE AS PDF
    </button>
    <a href="tenant_dashboard.php" class="btn btn-outline-light btn-lg ms-2">Back</a>
</div>

<div class="paper">
    <div class="digital-stamp">
        DIGITALLY VERIFIED<br>
        <?php echo date('Y-m-d H:i'); ?>
    </div>

    <div class="header-section text-center">
        <h2 class="fw-bold mb-0">MG's APARTMENT</h2>
        <p class="text-muted small">Rental Management & Lease Agreement Portal</p>
    </div>

    <h4 class="text-center fw-bold mt-4 mb-5" style="text-decoration: underline;">CONTRACT OF LEASE</h4>

    <div class="mb-4" style="font-size: 1.1rem;">
        <p><strong>DATE:</strong> <?php echo $signed_date; ?></p>
        <p><strong>LESSOR:</strong> MG's Apartment Management</p>
        <p><strong>LESSEE:</strong> <?php echo strtoupper($data['full_name']); ?></p>
        <p><strong>PROPERTY:</strong> Unit <?php echo $unit_display; ?></p>
        <p><strong>MONTHLY RENT:</strong> ₱<?php echo $rent_display; ?></p>
    </div>

    <div class="terms-section">
        <h5 class="section-title">Terms and Conditions</h5>
        <div class="content">
            <?php echo $house_rules; ?>
        </div>
    </div>

    <div class="row mt-5 pt-5">
        <div class="col-6 text-center">
            <div style="height: 80px;"></div>
            <div class="signature-line"></div>
            <p class="fw-bold mb-0">MG'S MANAGEMENT</p>
            <small>Lessor</small>
        </div>
        <div class="col-6 text-center">
            <div style="height: 80px; display: flex; align-items: flex-end; justify-content: center;">
                <?php if(!empty($data['digital_signature'])): ?>
                    <img src="<?php echo $data['digital_signature']; ?>" style="max-width: 180px; max-height: 80px; margin-bottom: -10px;">
                <?php else: ?>
                    <div style="height: 80px;"></div>
                <?php endif; ?>
            </div>
            <div class="signature-line"></div>
            <p class="fw-bold mb-0"><?php echo strtoupper($data['full_name']); ?></p>
            <small>Lessee (Digitally Signed)</small>
        </div>
    </div>

    <div class="mt-5 pt-4 text-center text-muted" style="font-size: 10px; border-top: 1px solid #eee;">
        This document is an electronic record generated by MG's Rental System on <?php echo $signed_date; ?>.
    </div>
</div>

</body>
</html>