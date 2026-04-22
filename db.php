<?php
$conn = mysqli_connect("localhost", "root", "", "lms_db");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>