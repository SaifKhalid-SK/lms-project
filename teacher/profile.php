<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$success    = "";
$error      = "";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);

    if (empty($full_name) || empty($email) || empty($phone)) {
        $error = "All fields are required.";
    } else {
        $chk = mysqli_prepare($conn,
            "SELECT id FROM teachers WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($chk, "si", $email, $teacher_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "This email is already used by another account.";
        } else {
            $upd = mysqli_prepare($conn,
                "UPDATE teachers SET full_name=?, email=?, phone=?
                 WHERE id=?");
            mysqli_stmt_bind_param($upd, "sssi",
                $full_name, $email, $phone, $teacher_id);
            mysqli_stmt_execute($upd);
            $_SESSION['full_name'] = $full_name;
            $success = "Profile updated successfully.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current  = trim($_POST['current_password']);
    $new_pass = trim($_POST['new_password']);
    $confirm  = trim($_POST['confirm_password']);

    $chk = mysqli_prepare($conn,
        "SELECT id FROM teachers WHERE id=? AND password=?");
    mysqli_stmt_bind_param($chk, "is", $teacher_id, $current);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);

    if (mysqli_stmt_num_rows($chk) == 0) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $upd = mysqli_prepare($conn,
            "UPDATE teachers SET password=? WHERE id=?");
        mysqli_stmt_bind_param($upd, "si", $new_pass, $teacher_id);
        mysqli_stmt_execute($upd);
        $success = "Password changed successfully.";
    }
}

// Fetch teacher data
$stmt = mysqli_prepare($conn, "SELECT * FROM teachers WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$teacher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Fetch subjects count
$subj_count_stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total FROM subjects WHERE teacher_id=?");
mysqli_stmt_bind_param($subj_count_stmt, "i", $teacher_id);
mysqli_stmt_execute($subj_count_stmt);
$subj_count = mysqli_fetch_assoc(
    mysqli_stmt_get_result($subj_count_stmt))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — EduLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; margin:0; }
        .top-navbar {
            background: linear-gradient(135deg, #1a252f, #2980b9);
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
            height:calc(100vh - 60px); width:60px;
            background:#1a252f; overflow:hidden;
            transition:width 0.3s ease; z-index:999; padding-top:20px;
        }
        .sidebar:hover { width:220px; }
        .sidebar a {
            display:flex; align-items:center; padding:14px 18px;
            color:rgba(255,255,255,0.75); text-decoration:none;
            white-space:nowrap; font-size:14px; font-weight:500;
        }
        .sidebar a:hover, .sidebar a.active {
            background:rgba(255,255,255,0.1); color:white;
        }
        .sidebar a i { font-size:18px; min-width:24px; margin-right:14px; }
        .sidebar-label { opacity:0; transition:opacity 0.2s; }
        .sidebar:hover .sidebar-label { opacity:1; }
        .main-content {
            margin-left:60px; margin-top:60px;
            padding:30px; max-width:750px;
        }
        .profile-card {
            background:white; border-radius:12px; padding:30px;
            box-shadow:0 2px 12px rgba(0,0,0,0.07); margin-bottom:20px;
        }
        .profile-avatar {
            width:80px; height:80px;
            background:linear-gradient(135deg,#1a252f,#2980b9);
            border-radius:50%; display:flex; align-items:center;
            justify-content:center; font-size:32px; color:white;
            margin-bottom:15px;
        }
        .section-title {
            font-size:13px; font-weight:700; color:#2980b9;
            text-transform:uppercase; letter-spacing:1px;
            margin:0 0 18px; padding-bottom:8px;
            border-bottom:2px solid #eaf4fb;
        }
        .info-row {
            display:flex; align-items:center; padding:10px 0;
            border-bottom:1px solid #f5f7fa; font-size:14px;
        }
        .info-row:last-child { border-bottom:none; }
        .info-label {
            font-weight:600; color:#7f8c8d;
            min-width:160px; font-size:13px;
        }
        .info-label i { color:#2980b9; margin-right:8px; width:16px; }
        .info-value { color:#2C3E50; }
        label { font-size:13px; font-weight:600; color:#2C3E50; margin-bottom:5px; }
        .form-control {
            border-radius:8px; padding:10px 14px;
            border:1px solid #dde1e7; font-size:14px;
        }
        .form-control:focus {
            border-color:#2980b9;
            box-shadow:0 0 0 3px rgba(41,128,185,0.15);
        }
        .btn-save {
            background:linear-gradient(135deg,#1a252f,#2980b9);
            color:white; border:none; border-radius:8px;
            padding:10px 28px; font-size:14px; font-weight:600;
        }
        .btn-save:hover { opacity:0.9; color:white; }
        .back-btn {
            display:inline-flex; align-items:center; gap:6px;
            color:#2980b9; text-decoration:none;
            font-size:14px; font-weight:600; margin-bottom:20px;
        }
        .back-btn:hover { color:#1a252f; }
        .stat-pill {
            background:#eaf4fb; border-radius:8px;
            padding:8px 16px; font-size:13px;
            color:#2980b9; font-weight:600;
            display:inline-flex; align-items:center; gap:6px;
        }
    </style>
</head>
<body>

<div class="top-navbar">
    <div class="brand"><i class="fas fa-graduation-cap"></i>EduLMS</div>
    <div style="font-size:14px;color:rgba(255,255,255,0.85);">
        <i class="fas fa-user-tie me-1"></i>My Profile
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<div class="sidebar">
    <a href="dashboard.php">
        <i class="fas fa-th-large"></i>
        <span class="sidebar-label">Dashboard</span>
    </a>
    <a href="profile.php" class="active">
        <i class="fas fa-user-tie"></i>
        <span class="sidebar-label">My Profile</span>
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
    <?php if ($success): ?>
        <div class="alert alert-success py-2 mb-3" style="font-size:13px;">
            <i class="fas fa-check-circle me-1"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- PROFILE OVERVIEW -->
    <div class="profile-card">
        <div class="profile-avatar">
            <i class="fas fa-user-tie"></i>
        </div>
        <h5 style="font-weight:700; color:#2C3E50; margin:0;">
            <?= htmlspecialchars($teacher['full_name']) ?>
        </h5>
        <p style="color:#7f8c8d; font-size:13px; margin:4px 0 12px;">
            <?= htmlspecialchars($teacher['department']) ?>
        </p>
        <div class="stat-pill">
            <i class="fas fa-book"></i>
            <?= $subj_count ?> subjects assigned
        </div>

        <div class="section-title mt-4">Account Information</div>
        <div class="info-row">
            <div class="info-label"><i class="fas fa-at"></i>Username</div>
            <div class="info-value"><?= htmlspecialchars($teacher['username']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label"><i class="fas fa-envelope"></i>Email</div>
            <div class="info-value"><?= htmlspecialchars($teacher['email']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label"><i class="fas fa-phone"></i>Phone</div>
            <div class="info-value"><?= htmlspecialchars($teacher['phone']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label"><i class="fas fa-building"></i>Department</div>
            <div class="info-value"><?= htmlspecialchars($teacher['department']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label"><i class="fas fa-calendar"></i>Joined</div>
            <div class="info-value">
                <?= date('d M Y', strtotime($teacher['created_at'])) ?>
            </div>
        </div>
    </div>

    <!-- EDIT PROFILE -->
    <div class="profile-card">
        <div class="section-title">
            <i class="fas fa-edit me-2"></i>Edit Profile
        </div>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= htmlspecialchars($teacher['full_name']) ?>"
                           required>
                </div>
                <div class="col-md-6">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($teacher['email']) ?>"
                           required>
                </div>
                <div class="col-md-6">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($teacher['phone']) ?>"
                           required>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="update_profile" class="btn-save">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- CHANGE PASSWORD -->
    <div class="profile-card">
        <div class="section-title">
            <i class="fas fa-lock me-2"></i>Change Password
        </div>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-12">
                    <label>Current Password</label>
                    <input type="password" name="current_password"
                           class="form-control"
                           placeholder="Enter current password" required>
                </div>
                <div class="col-md-6">
                    <label>New Password</label>
                    <input type="password" name="new_password"
                           class="form-control"
                           placeholder="Min 6 characters" required>
                </div>
                <div class="col-md-6">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password"
                           class="form-control"
                           placeholder="Repeat new password" required>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="change_password" class="btn-save">
                    <i class="fas fa-key me-2"></i>Change Password
                </button>
            </div>
        </form>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>