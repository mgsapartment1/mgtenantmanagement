<?php
include('db.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Kunin muna lahat ng notifications (habang unread pa ang iba para ma-style natin)
$q = "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC";
$res = mysqli_query($conn, $q);

// 2. Pagkatapos makuha para sa display, tsaka natin i-mark as read sa database
mysqli_query($conn, "UPDATE notifications SET status = 'read' WHERE user_id = '$user_id' AND status = 'unread'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .notif-card { border: none; border-radius: 12px; transition: 0.2s; margin-bottom: 10px; border-left: 4px solid transparent; }
        
        /* Style para sa mga bagong notifications */
        .notif-unread { 
            border-left: 4px solid #008080 !important; 
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,128,128,0.1) !important;
        }
        
        .back-btn { text-decoration: none; color: #008080; font-weight: 600; font-size: 0.9rem; }
        .text-teal { color: #008080; }
        .notif-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 600px;">
    <div class="d-flex align-items-center mb-4">
        <a href="tenant_dashboard.php" class="back-btn me-3"><i class="fa fa-chevron-left"></i></a>
        <h4 class="fw-bold mb-0">Notifications</h4>
    </div>

    <?php if(mysqli_num_rows($res) > 0): while($row = mysqli_fetch_assoc($res)): ?>
        <div class="card notif-card shadow-sm p-3 <?php echo ($row['status'] == 'unread') ? 'notif-unread' : ''; ?>">
            <div class="d-flex align-items-start">
                <div class="notif-icon me-3">
                    <i class="fa <?php echo ($row['status'] == 'unread') ? 'fa-bell text-teal' : 'fa-check-circle text-muted'; ?>"></i>
                </div>
                <div style="flex: 1;">
                    <p class="mb-1 text-dark" style="font-size: 0.9rem; line-height: 1.4;">
                        <?php echo htmlspecialchars($row['message']); ?>
                    </p>
                    <small class="text-muted" style="font-size: 0.7rem;">
                        <i class="fa fa-clock me-1"></i> <?php echo date('M d, Y • h:i A', strtotime($row['created_at'])); ?>
                    </small>
                </div>
                <?php if($row['status'] == 'unread'): ?>
                    <span class="badge rounded-pill bg-teal" style="background-color: #008080; font-size: 0.5rem;">NEW</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; else: ?>
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="fa fa-bell-slash fs-1 text-muted opacity-25"></i>
            </div>
            <p class="text-muted">No Available Notifications.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>