<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kunin ang rules mula sa settings table
$query = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'house_rules'");
$data = mysqli_fetch_assoc($query);
$house_rules = $data ? $data['setting_value'] : "<h5 class='text-center text-muted'>No house rules set yet.</h5>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>House Rules & Regulations | MG's Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --teal-primary: #008080; --bg-soft: #f8fafc; --text-dark: #1e293b; }
        body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-dark); padding-bottom: 50px; }

        /* Modern Navigation Header */
        .page-header { background: white; padding: 18px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .page-header h6 { margin: 0; font-weight: 800; color: var(--teal-primary); flex-grow: 1; text-align: center; margin-right: 30px; letter-spacing: -0.5px; }
        .back-btn { color: #64748b; transition: 0.2s; }
        .back-btn:hover { color: var(--teal-primary); }

        /* Card Container */
        .rules-container {
            max-width: 800px;
            margin: 25px auto;
            padding: 0 15px;
        }

        .rules-card {
            background: white;
            border-radius: 30px;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }

        /* Hero Section inside Card */
        .rules-hero { text-align: center; margin-bottom: 40px; }
        .icon-box {
            width: 70px; height: 70px; background: #f0fdfa; color: var(--teal-primary);
            border-radius: 22px; display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; margin: 0 auto 20px;
        }

        /* Content Styling (CKEditor support) */
        .rules-content { font-size: 1rem; line-height: 1.8; color: #475569; }
        .rules-content h2, .rules-content h3 { color: var(--text-dark); font-weight: 800; margin-top: 30px; border-left: 5px solid var(--teal-primary); padding-left: 15px; }
        .rules-content h4, .rules-content h5 { color: var(--teal-primary); font-weight: 700; margin-top: 20px; }
        .rules-content p { margin-bottom: 15px; }
        .rules-content ul, .rules-content ol { padding-left: 20px; margin-bottom: 25px; }
        .rules-content li { margin-bottom: 10px; }
        .rules-content strong { color: var(--text-dark); font-weight: 700; }
        
        /* Styled Blockquote for emphasis */
        .rules-content blockquote {
            background: #f1f5f9; border-left: 5px solid #64748b; padding: 20px;
            border-radius: 0 15px 15px 0; font-style: italic; margin: 25px 0;
        }

        /* Footer Action */
        .footer-note { margin-top: 50px; padding-top: 30px; border-top: 1px solid #f1f5f9; text-align: center; }
        
        .print-fab {
            background: var(--text-dark); color: white; border: none; width: 100%;
            padding: 18px; border-radius: 20px; font-weight: 700; font-size: 1rem;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1); transition: 0.3s;
        }
        .print-fab:hover { background: #000; transform: translateY(-2px); }

        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .rules-card { box-shadow: none; border: none; padding: 0; margin: 0; }
            .rules-container { max-width: 100%; margin: 0; }
        }

        @media (max-width: 576px) {
            .rules-card { padding: 30px 20px; border-radius: 25px; }
            .rules-content { font-size: 0.95rem; }
        }
    </style>
</head>
<body>

<div class="page-header no-print">
    <a href="profile_edit.php" class="back-btn"><i class="fa fa-chevron-left fs-5"></i></a>
    <h6>HOUSE RULES & POLICIES</h6>
</div>

<div class="rules-container">
    <div class="rules-card">
        <div class="rules-hero">
            <div class="icon-box shadow-sm">
                <i class="fa fa-gavel"></i>
            </div>
            <h3 class="fw-bold mb-1">Building Regulations</h3>
            <p class="text-muted small">Official Guidelines for MG's Apartment Tenants</p>
        </div>

        <div class="rules-content">
            <?php echo $house_rules; ?>
        </div>

        <div class="footer-note">
            <p class="small text-muted mb-4">By residing in MG's Apartment, the tenant acknowledges and agrees to comply with all the rules and regulations mentioned above.</p>
            <button onclick="window.print()" class="print-fab no-print">
                <i class="fa fa-file-pdf"></i> Download as PDF / Print
            </button>
        </div>
    </div>
    
    <div class="text-center mt-4 no-print">
        <a href="tenant_dashboard.php" class="text-decoration-none text-muted fw-bold small">
            <i class="fa fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>