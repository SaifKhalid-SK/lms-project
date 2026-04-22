<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$error   = "";
$success = "";
$new_teacher = null;

$depts = ['Computer Science', 'Business Administration', 'Electrical Engineering'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $username   = trim($_POST['username']);
    $password   = trim($_POST['password']);

    if (empty($full_name) || empty($email) || empty($phone) ||
        empty($department) || empty($username) || empty($password)) {
        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";

    } else {
        // Check username uniqueness across ALL roles
        $chk_u = mysqli_prepare($conn,
            "SELECT id FROM all_usernames WHERE username = ?");
        mysqli_stmt_bind_param($chk_u, "s", $username);
        mysqli_stmt_execute($chk_u);
        mysqli_stmt_store_result($chk_u);

        if (mysqli_stmt_num_rows($chk_u) > 0) {
            $error = "This username is already taken.";

        } else {
            // Check email uniqueness in teachers table
            $chk_e = mysqli_prepare($conn,
                "SELECT id FROM teachers WHERE email = ?");
            mysqli_stmt_bind_param($chk_e, "s", $email);
            mysqli_stmt_execute($chk_e);
            mysqli_stmt_store_result($chk_e);

            if (mysqli_stmt_num_rows($chk_e) > 0) {
                $error = "This email is already registered.";

            } else {
                // Insert teacher
                $ins = mysqli_prepare($conn,
                    "INSERT INTO teachers
                     (full_name, email, phone, department, username, password)
                     VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins, "ssssss",
                    $full_name, $email, $phone,
                    $department, $username, $password);
                mysqli_stmt_execute($ins);

                // Insert into all_usernames
                $role  = 'teacher';
                $ins_u = mysqli_prepare($conn,
                    "INSERT INTO all_usernames (username, role) VALUES (?, ?)");
                mysqli_stmt_bind_param($ins_u, "ss", $username, $role);
                mysqli_stmt_execute($ins_u);

                $success     = "Teacher added successfully!";
                $new_teacher = [
                    'full_name'  => $full_name,
                    'email'      => $email,
                    'phone'      => $phone,
                    'department' => $department,
                    'username'   => $username,
                    'password'   => $password
                ];

                // Clear form
                $_POST = [];
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
    <title>Add Teacher — EduLMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; margin:0; }
        .top-navbar {
            background:linear-gradient(135deg,#1a0533,#6c3483);
            color:white; padding:0 25px; height:60px;
            display:flex; align-items:center; justify-content:space-between;
            position:fixed; top:0; left:0; right:0; z-index:1000;
            box-shadow:0 2px 10px rgba(0,0,0,0.2);
        }
        .top-navbar .brand { font-size:20px; font-weight:700; }
        .top-navbar .brand i { margin-right:8px; }
        .logout-btn {
            background:rgba(255,255,255,0.15);
            border:1px solid rgba(255,255,255,0.3);
            color:white; padding:6px 16px; border-radius:20px;
            font-size:13px; text-decoration:none;
        }
        .logout-btn:hover { background:rgba(255,255,255,0.25); color:white; }
        .sidebar {
            position:fixed; top:60px; left:0;
            height:calc(100vh - 60px); width:220px;
            background:#1a0533; z-index:999; padding-top:20px;
        }
        .sidebar a {
            display:flex; align-items:center; padding:14px 20px;
            color:rgba(255,255,255,0.75); text-decoration:none;
            font-size:14px; font-weight:500; gap:12px;
        }
        .sidebar a:hover, .sidebar a.active {
            background:rgba(255,255,255,0.1); color:white;
        }
        .sidebar a i { font-size:16px; min-width:20px; }
        .sidebar-section {
            padding:10px 20px 5px; font-size:10px; font-weight:700;
            color:rgba(255,255,255,0.35); text-transform:uppercase;
            letter-spacing:1.5px; margin-top:10px;
        }
        .main-content {
            margin-left:220px; margin-top:60px;
            padding:30px; max-width:850px;
        }
        .form-card {
            background:white; border-radius:12px; padding:30px;
            box-shadow:0 2px 12px rgba(0,0,0,0.07);
        }
        .section-title {
            font-size:13px; font-weight:700; color:#6c3483;
            text-transform:uppercase; letter-spacing:1px;
            margin:0 0 18px; padding-bottom:8px;
            border-bottom:2px solid #f0eafb;
        }
        label { font-size:13px; font-weight:600; color:#2C3E50; margin-bottom:5px; }
        .form-control, .form-select {
            border-radius:8px; padding:10px 14px;
            border:1px solid #dde1e7; font-size:14px;
        }
        .form-control:focus, .form-select:focus {
            border-color:#6c3483;
            box-shadow:0 0 0 3px rgba(108,52,131,0.15);
        }
        .btn-add {
            background:linear-gradient(135deg,#1a0533,#6c3483);
            color:white; border:none; border-radius:8px;
            padding:12px 30px; font-size:14px; font-weight:600;
        }
        .btn-add:hover { opacity:0.9; color:white; }
        .back-btn {
            display:inline-flex; align-items:center; gap:6px;
            color:#6c3483; text-decoration:none;
            font-size:14px; font-weight:600; margin-bottom:20px;
        }
        .back-btn:hover { color:#1a0533; }
        .credentials-card {
            background:linear-gradient(135deg,#1a0533,#6c3483);
            border-radius:12px; padding:25px; color:white;
            margin-bottom:20px;
        }
        .credentials-card h5 {
            font-weight:700; margin-bottom:15px;
            padding-bottom:10px;
            border-bottom:1px solid rgba(255,255,255,0.2);
        }
        .cred-row {
            display:flex; padding:8px 0;
            border-bottom:1px solid rgba(255,255,255,0.1);
            font-size:14px;
        }
        .cred-row:last-child { border-bottom:none; }
        .cred-label {
            min-width:140px; opacity:0.75; font-size:13px;
        }
        .cred-value { font-weight:600; }
        .copy-note {
            background:rgba(255,255,255,0.15);
            border-radius:8px; padding:12px 16px;
            font-size:13px; margin-top:15px;
            display:flex; align-items:center; gap:8px;
        }
    </style>
</head>
<body>

<div class="top-navbar">
    <div class="brand"><i class="fas fa-graduation-cap"></i>EduLMS Admin</div>
    <div style="font-size:14px; color:rgba(255,255,255,0.85);">
        Add New Teacher
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<div class="sidebar">
    <div class="sidebar-section">Main</div>
    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <div class="sidebar-section">Manage</div>
    <a href="dashboard.php#students">
        <i class="fas fa-user-graduate"></i> Students
    </a>
    <a href="dashboard.php#teachers">
        <i class="fas fa-chalkboard-teacher"></i> Teachers
    </a>
    <a href="add_teacher.php" class="active">
        <i class="fas fa-user-plus"></i> Add Teacher
    </a>
    <a href="add_fees.php">
        <i class="fas fa-file-invoice-dollar"></i> Add Fees
    </a>
</div>

<div class="main-content">

    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:13px;">
            <i class="fas fa-exclamation-circle me-1"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- SUCCESS — Show credentials card -->
    <?php if ($success && $new_teacher): ?>
        <div class="credentials-card">
            <h5>
                <i class="fas fa-check-circle me-2"></i>
                Teacher Added Successfully!
            </h5>
            <div class="cred-row">
                <div class="cred-label">Full Name</div>
                <div class="cred-value">
                    <?= htmlspecialchars($new_teacher['full_name']) ?>
                </div>
            </div>
            <div class="cred-row">
                <div class="cred-label">Email</div>
                <div class="cred-value">
                    <?= htmlspecialchars($new_teacher['email']) ?>
                </div>
            </div>
            <div class="cred-row">
                <div class="cred-label">Phone</div>
                <div class="cred-value">
                    <?= htmlspecialchars($new_teacher['phone']) ?>
                </div>
            </div>
            <div class="cred-row">
                <div class="cred-label">Department</div>
                <div class="cred-value">
                    <?= htmlspecialchars($new_teacher['department']) ?>
                </div>
            </div>
            <div class="cred-row">
                <div class="cred-label">Username</div>
                <div class="cred-value">
                    <?= htmlspecialchars($new_teacher['username']) ?>
                </div>
            </div>
            <div class="cred-row">
                <div class="cred-label">Password</div>
                <div class="cred-value">
                    <?= htmlspecialchars($new_teacher['password']) ?>
                </div>
            </div>
            <div class="copy-note">
                <i class="fas fa-info-circle"></i>
                Please note down or share these credentials with the teacher.
                Password will not be shown again.
            </div>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="form-card">
        <div class="section-title">
            <i class="fas fa-user-plus me-2"></i>New Teacher Details
        </div>

        <form method="POST" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           placeholder="e.g. Dr. Ahmed Khan"
                           value="<?= isset($_POST['full_name'])
                               ? htmlspecialchars($_POST['full_name']) : '' ?>"
                           required>
                </div>
                <div class="col-md-6">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="e.g. ahmed.khan@lms.com"
                           value="<?= isset($_POST['email'])
                               ? htmlspecialchars($_POST['email']) : '' ?>"
                           required>
                </div>
                <div class="col-md-6">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control"
                           placeholder="e.g. 03001234567"
                           value="<?= isset($_POST['phone'])
                               ? htmlspecialchars($_POST['phone']) : '' ?>"
                           required>
                </div>
                <div class="col-md-6">
                    <label>Department</label>
                    <select name="department" class="form-select" required>
                        <option value="" disabled selected>Select department</option>
                        <?php foreach ($depts as $d): ?>
                            <option value="<?= $d ?>"
                                <?= (isset($_POST['department']) &&
                                    $_POST['department'] == $d) ? 'selected' : '' ?>>
                                <?= $d ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control"
                           placeholder="Choose a unique username"
                           value="<?= isset($_POST['username'])
                               ? htmlspecialchars($_POST['username']) : '' ?>"
                           required>
                </div>
                <div class="col-md-6">
                    <label>Password</label>
                    <input type="text" name="password" class="form-control"
                           placeholder="Min 6 characters"
                           value="<?= isset($_POST['password'])
                               ? htmlspecialchars($_POST['password']) : '' ?>"
                           required>
                    <div style="font-size:11px; color:#95a5a6; margin-top:4px;">
                        Password shown in plain text so admin can share it.
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn-add">
                    <i class="fas fa-user-plus me-2"></i>Add Teacher
                </button>
            </div>
        </form>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>