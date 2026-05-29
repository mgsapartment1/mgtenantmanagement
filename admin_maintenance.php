<?php
include('db.php');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

// --- LOGIC PARA SA PAG-UPDATE AT PAG-BATO NG NOTIFICATION ---
if (isset($_POST['update_maintenance'])) {
    $m_id = $_POST['m_id'];
    $u_id = $_POST['u_id'];
    $new_status = $_POST['status'];
    $remarks = mysqli_real_escape_string($conn, $_POST['admin_remarks']);
    $issue_name = mysqli_real_escape_string($conn, $_POST['issue_name']);

    // 1. Update Maintenance Table
    $update_sql = "UPDATE maintenance SET status = '$new_status', admin_remarks = '$remarks' WHERE id = '$m_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        // 2. INSERT NOTIFICATION PARA KAY TENANT
        $notif_msg = "Maintenance Update: Ang request mo na '$issue_name' ay " . strtoupper($new_status) . ". Admin: $remarks";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, status, created_at) VALUES ('$u_id', '$notif_msg', 'unread', NOW())");
        
        header("Location: admin_maintenance.php?update=success");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Requests | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: #f8f9fa;">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">Maintenance & Repair Requests</h4>
            <a href="admin_dashboard.php" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <div class="row">
            <?php
            $res = mysqli_query($conn, "SELECT maintenance.*, users.full_name, users.id as tenant_id, units.unit_no 
                                        FROM maintenance 
                                        JOIN users ON maintenance.user_id = users.id 
                                        LEFT JOIN units ON users.assigned_unit_id = units.id
                                        ORDER BY CASE priority 
                                            WHEN 'High' THEN 1 
                                            WHEN 'Medium' THEN 2 
                                            WHEN 'Low' THEN 3 END ASC");
            
            while($row = mysqli_fetch_assoc($res)) {
                $p_color = ($row['priority'] == 'High') ? 'danger' : (($row['priority'] == 'Medium') ? 'warning' : 'info');
                ?>
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm p-3" style="border-left: 5px solid var(--bs-<?php echo $p_color; ?>) !important; border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $p_color; ?> text-uppercase mb-2" style="font-size: 10px;"><?php echo $row['priority']; ?></span>
                            <span class="badge bg-light text-dark border small"><?php echo strtoupper($row['status']); ?></span>
                        </div>
                        <h6 class="fw-bold mb-1"><?php echo $row['full_name']; ?> (Unit <?php echo $row['unit_no']; ?>)</h6>
                        <p class="text-muted small mb-3"><?php echo $row['issue']; ?></p>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary px-3" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['id']; ?>">
                                <i class="fa fa-edit me-1"></i> Update Status
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form class="modal-content" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="m_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="u_id" value="<?php echo $row['tenant_id']; ?>">
                                <input type="hidden" name="issue_name" value="<?php echo $row['issue']; ?>">
                                
                                <div class="mb-3">
                                    <label class="small fw-bold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="In Progress" <?php if($row['status']=='In Progress') echo 'selected'; ?>>In Progress</option>
                                        <option value="Resolved" <?php if($row['status']=='Resolved') echo 'selected'; ?>>Resolved / Fixed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold">Admin Remarks (Ito ang makikita ni Tenant)</label>
                                    <textarea name="admin_remarks" class="form-control" rows="3" placeholder="Hal: On the way na si plumber..." required><?php echo $row['admin_remarks']; ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update_maintenance" class="btn btn-success w-100 fw-bold">SAVE & NOTIFY TENANT</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>