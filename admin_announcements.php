<?php
include('db.php');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

if (isset($_POST['post_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $admin_name = $_SESSION['full_name'];

    $query = "INSERT INTO announcements (title, content) VALUES ('$title', '$content')";
    if (mysqli_query($conn, $query)) {
        // Record to Audit Log
        mysqli_query($conn, "INSERT INTO audit_logs (admin_id, admin_name, action, affected_table) 
                             VALUES ('{$_SESSION['user_id']}', '$admin_name', 'Posted Announcement: $title', 'announcements')");
        header("Location: admin_announcements.php?success=1");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: #f8f9fa;">
    <div class="container mt-5">
        <div class="card p-4 shadow-sm border-0 mb-4" style="border-radius: 15px;">
            <h4 class="fw-bold mb-4 text-teal"><i class="fa fa-bullhorn me-2"></i>Post New Announcement</h4>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Water Interruption" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Message</label>
                    <textarea name="content" class="form-control" rows="4" placeholder="Write the details here..." required></textarea>
                </div>
                <button type="submit" name="post_announcement" class="btn btn-primary px-4" style="background: #008080; border: none;">Post Announcement</button>
            </form>
        </div>

        <div class="card p-4 shadow-sm border-0" style="border-radius: 15px;">
            <h5 class="fw-bold mb-3">Announcement History</h5>
            <table class="table">
                <thead><tr><th>Date</th><th>Title</th><th>Message</th></tr></thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
                    while($row = mysqli_fetch_assoc($res)) {
                        echo "<tr>
                                <td style='font-size: 0.85rem;'>".date('M d, Y', strtotime($row['created_at']))."</td>
                                <td class='fw-bold'>{$row['title']}</td>
                                <td class='text-muted small'>{$row['content']}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>