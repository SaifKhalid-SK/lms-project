<?php
session_start();
require 'db.php';

// Set is_online = 0 based on role
if (isset($_SESSION['role']) && isset($_SESSION['user_id'])) {
    $id = $_SESSION['user_id'];

    if ($_SESSION['role'] == 'student') {
        $sql  = "UPDATE users SET is_online = 0 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

    } elseif ($_SESSION['role'] == 'teacher') {
        $sql  = "UPDATE teachers SET is_online = 0 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
    }
    // Admin has no is_online field
}

session_destroy();
header("Location: login.php");
exit();
?>