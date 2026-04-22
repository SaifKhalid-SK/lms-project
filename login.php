<?php
session_start();
require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Both fields are required.";
    } else {

        // STEP 1: Find which role this username belongs to
        $stmt = mysqli_prepare($conn, 
            "SELECT role FROM all_usernames WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $found  = mysqli_fetch_assoc($result);

        if (!$found) {
            // Username does not exist in system at all
            $error = "Invalid username or password.";
        } else {
            $role = $found['role'];

            // STEP 2: Now check password in the correct table
            if ($role == 'admin') {
                $stmt = mysqli_prepare($conn,
                    "SELECT * FROM admins WHERE username = ? AND password = ?");
                mysqli_stmt_bind_param($stmt, "ss", $username, $password);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row    = mysqli_fetch_assoc($result);

                if ($row) {
                    $_SESSION['user_id']  = $row['id'];
                    $_SESSION['role']     = 'admin';
                    $_SESSION['username'] = $row['username'];
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }

            } elseif ($role == 'teacher') {
                $stmt = mysqli_prepare($conn,
                    "SELECT * FROM teachers WHERE username = ? AND password = ?");
                mysqli_stmt_bind_param($stmt, "ss", $username, $password);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row    = mysqli_fetch_assoc($result);

                if ($row) {
                    $upd = mysqli_prepare($conn,
                        "UPDATE teachers SET is_online = 1 WHERE id = ?");
                    mysqli_stmt_bind_param($upd, "i", $row['id']);
                    mysqli_stmt_execute($upd);

                    $_SESSION['user_id']   = $row['id'];
                    $_SESSION['role']      = 'teacher';
                    $_SESSION['username']  = $row['username'];
                    $_SESSION['full_name'] = $row['full_name'];
                    header("Location: teacher/dashboard.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }

            } elseif ($role == 'student') {
                $stmt = mysqli_prepare($conn,
                    "SELECT * FROM users WHERE username = ? AND password = ?");
                mysqli_stmt_bind_param($stmt, "ss", $username, $password);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row    = mysqli_fetch_assoc($result);

                if ($row) {
                    $upd = mysqli_prepare($conn,
                        "UPDATE users SET is_online = 1 WHERE id = ?");
                    mysqli_stmt_bind_param($upd, "i", $row['id']);
                    mysqli_stmt_execute($upd);

                    $_SESSION['user_id']   = $row['id'];
                    $_SESSION['role']      = 'student';
                    $_SESSION['username']  = $row['username'];
                    $_SESSION['full_name'] = $row['full_name'];
                    header("Location: student/dashboard.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo h2 {
            color: #2C3E50;
            font-weight: 700;
            font-size: 28px;
            margin-top: 10px;
        }
        .login-logo p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dde1e7;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #3498DB;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        .input-group-text {
            background: #f0f4f8;
            border: 1px solid #dde1e7;
            color: #7f8c8d;
            border-radius: 8px 0 0 8px;
        }
        label {
            font-size: 13px;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 5px;
        }
        .btn-login {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 13px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: opacity 0.2s;
        }
        .btn-login:hover {
            opacity: 0.9;
            color: white;
        }
        .divider {
            text-align: center;
            color: #bdc3c7;
            font-size: 12px;
            margin: 20px 0 5px;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <i class="fas fa-graduation-cap fa-3x" style="color:#3498DB;"></i>
        <h2>EduLMS</h2>
        <p>Learning Management System</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 text-center" style="font-size:13px;">
            <i class="fas fa-exclamation-circle me-1"></i><?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Username</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
                <input type="text" name="username" class="form-control"
                       placeholder="Enter your username"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                       required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
    </form>

    <div class="divider">─────────────────────</div>

    <div class="text-center" style="font-size:13px; color:#7f8c8d;">
        New student?
        <a href="register.php" style="color:#3498DB; font-weight:600; text-decoration:none;">
            Register here
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>