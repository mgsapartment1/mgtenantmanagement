<?php
include('db.php');
session_start();

if (isset($_POST['theme']) && isset($_SESSION['user_id'])) {
    $theme = $_POST['theme'];
    $user_id = $_SESSION['user_id'];
    
    // 1. I-save sa Session para sa kasalukuyang session
    $_SESSION['theme'] = $theme;
    
    // 2. I-save sa Database para pag-login ulit, tanda pa rin
    mysqli_query($conn, "UPDATE users SET theme_preference = '$theme' WHERE id = '$user_id'");
    
    echo "Success";
}
?>