<?php
include('db.php'); 
if (isset($_POST['submit_announcement'])) {
    
    // 3. Kunin ang data mula sa form at linisin (Sanitize) para iwas SQL Injection
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $query = "INSERT INTO announcements (title, content, created_at) VALUES ('$title', '$content', NOW())";

    if (mysqli_query($conn, $query)) {
        header("Location: admin_dashboard.php?msg=success");
        exit();
    } else {
        echo "Error: " . $query . "<br>" . mysqli_error($conn);
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>