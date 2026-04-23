<?php
// ============================================
// config.example.php
// ============================================
// HOW TO USE:
// 1. Copy this file
// 2. Rename the copy to config.php
// 3. Fill in your actual database credentials
// ============================================

if ($_SERVER['HTTP_HOST'] == 'localhost') {

    // ── LOCAL XAMPP ──
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'lms_db');

} else {

    // ── LIVE SERVER (InfinityFree) ──
    define('DB_HOST', 'your_host_here');
    define('DB_USER', 'your_username_here');
    define('DB_PASS', 'your_password_here');
    define('DB_NAME', 'your_dbname_here');
}
?>