<?php
include('db.php');

// Details ng bagong Admin
$fullname = "System Admin";
$email = "superadmin@gmail.com";
$password = password_hash("admin12345", PASSWORD_DEFAULT); // Password: admin12345
$role = "admin";

// I-insert sa database
$sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$fullname', '$email', '$password', '$role')";

if (mysqli_query($conn, $sql)) {
    echo "<h3>✅ Success! Bagong Admin Created.</h3>";
    echo "<b>Email:</b> superadmin@gmail.com<br>";
    echo "<b>Password:</b> admin12345<br><br>";
    echo "<a href='login.php'>Pumunta na sa Login Page</a>";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}
?>