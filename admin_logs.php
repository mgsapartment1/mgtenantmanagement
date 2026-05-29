<?php
include('db.php');
session_start();

// Security: Admin lang ang pwede dito
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/** * CHECK MO SA DATABASE MO: 
 * Kung 'created_at' ang column name sa audit_logs table, palitan ang 'timestamp' sa query sa ibaba.
 */
$column_name = "timestamp"; // O "created_at" depende sa database mo
$logs_query = mysqli_query($conn, "SELECT * FROM audit_logs ORDER BY $column_name DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --teal: #008080; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .log-card { border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.03); background: white; }
        .table thead th { 
            background-color: var(--teal) !important; 
            color: white !important; 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            padding: 15px;
        }
        .badge-action { background: #e8f0fe; color: #1967d2; font-weight: 600; padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0" style="color: var(--teal);"><i class="fa fa-history me-2"></i> Audit Trail</h2>
            <p class="text-muted small">Monitoring all administrative activities</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card log-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="ps-3" style="border-top-left-radius: 12px;">Date & Time</th>
                        <th>Admin Name</th>
                        <th>Action Performed</th>
                        <th style="border-top-right-radius: 12px;">Module/Table</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($logs_query) > 0): ?>
                        <?php while($log = mysqli_fetch_assoc($logs_query)): ?>
                            <tr>
                                <td class="ps-3 small text-muted">
                                    <?php echo date('M d, Y | h:i A', strtotime($log[$column_name])); ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 12px;">
                                            <?php echo strtoupper(substr($log['admin_name'], 0, 1)); ?>
                                        </div>
                                        <span class="fw-bold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($log['admin_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($log['action']); ?></span>
                                </td>
                                <td>
                                    <span class="badge-action text-uppercase"><?php echo htmlspecialchars($log['affected_table']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">No activities recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>