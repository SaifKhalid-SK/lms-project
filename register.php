<?php
session_start();
require 'db.php';

$error   = "";
$success = "";

// Fetch departments for dropdown
$depts_result = mysqli_query($conn, "SELECT name FROM departments ORDER BY name");
$departments  = [];
while ($d = mysqli_fetch_assoc($depts_result)) {
    $departments[] = $d['name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect and sanitize inputs
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $reg_no     = trim($_POST['reg_no']);
    $department = trim($_POST['department']);
    $semester   = (int)$_POST['semester'];
    $dob        = $_POST['dob'];
    $gender     = $_POST['gender'];
    $username   = trim($_POST['username']);
    $password   = trim($_POST['password']);
    $confirm    = trim($_POST['confirm_password']);

    // ---- VALIDATION ----
    if (
        empty($full_name) || empty($email)    || empty($phone) ||
        empty($reg_no)    || empty($department)|| empty($semester) ||
        empty($dob)       || empty($gender)   || empty($username) ||
        empty($password)  || empty($confirm)
    ) {
        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";

    } else {

        // ---- STEP 1: Check username uniqueness across ALL roles ----
        $check_user = mysqli_prepare($conn,
            "SELECT id FROM all_usernames WHERE username = ?");
        mysqli_stmt_bind_param($check_user, "s", $username);
        mysqli_stmt_execute($check_user);
        mysqli_stmt_store_result($check_user);

        if (mysqli_stmt_num_rows($check_user) > 0) {
            $error = "This username is already taken. Please choose another.";

        } else {

            // ---- STEP 2: Check email and reg_no uniqueness in users table ----
            $check_info = mysqli_prepare($conn,
                "SELECT id FROM users WHERE email = ? OR reg_no = ?");
            mysqli_stmt_bind_param($check_info, "ss", $email, $reg_no);
            mysqli_stmt_execute($check_info);
            mysqli_stmt_store_result($check_info);

            if (mysqli_stmt_num_rows($check_info) > 0) {
                $error = "Email or Registration Number already exists.";

            } else {

                // ---- STEP 3: Insert into users table ----
                $ins_user = mysqli_prepare($conn,
                    "INSERT INTO users 
                     (full_name, email, phone, reg_no, department, 
                      semester, dob, gender, username, password)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins_user, "sssssissss",
                    $full_name,
                    $email,
                    $phone,
                    $reg_no,
                    $department,
                    $semester,
                    $dob,
                    $gender,
                    $username,
                    $password
                );
                mysqli_stmt_execute($ins_user);
                $student_id = mysqli_insert_id($conn);

                // ---- STEP 4: Insert username into all_usernames ----
                $role     = 'student';
                $ins_uname = mysqli_prepare($conn,
                    "INSERT INTO all_usernames (username, role) VALUES (?, ?)");
                mysqli_stmt_bind_param($ins_uname, "ss", $username, $role);
                mysqli_stmt_execute($ins_uname);

                // ---- STEP 5: Auto-enroll in matching subjects ----
                $subj = mysqli_prepare($conn,
                    "SELECT id FROM subjects 
                     WHERE department = ? AND semester = ?");
                mysqli_stmt_bind_param($subj, "si", $department, $semester);
                mysqli_stmt_execute($subj);
                $subj_result = mysqli_stmt_get_result($subj);

                while ($row = mysqli_fetch_assoc($subj_result)) {
                    $enroll = mysqli_prepare($conn,
                        "INSERT INTO enrollments (student_id, subject_id) 
                         VALUES (?, ?)");
                    mysqli_stmt_bind_param($enroll, "ii", $student_id, $row['id']);
                    mysqli_stmt_execute($enroll);
                }

                $success = "Registration successful! You can now login.";

                // Clear POST data so form resets
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
    <title>LMS — Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            min-height: 100vh;
            padding: 40px 15px;
        }
        .register-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            max-width: 680px;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .register-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-logo h2 {
            color: #2C3E50;
            font-weight: 700;
            font-size: 26px;
            margin-top: 10px;
        }
        .register-logo p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #3498DB;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #eaf4fb;
        }
        label {
            font-size: 13px;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 5px;
        }
        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 11px 14px;
            border: 1px solid #dde1e7;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #3498DB;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        .input-group-text {
            background: #f0f4f8;
            border: 1px solid #dde1e7;
            color: #7f8c8d;
        }
        .btn-register {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 13px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
            transition: opacity 0.2s;
        }
        .btn-register:hover {
            opacity: 0.9;
            color: white;
        }
        .password-hint {
            font-size: 11px;
            color: #95a5a6;
            margin-top: 4px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .login-link a {
            color: #3498DB;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="register-card">

    <!-- Logo -->
    <div class="register-logo">
        <i class="fas fa-graduation-cap fa-3x" style="color:#3498DB;"></i>
        <h2>EduLMS</h2>
        <p>Student Registration Portal</p>
    </div>

    <!-- Error Message -->
    <?php if ($error): ?>
        <div class="alert alert-danger py-2" style="font-size:13px;">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Success Message -->
    <?php if ($success): ?>
        <div class="alert alert-success py-2" style="font-size:13px;">
            <i class="fas fa-check-circle me-2"></i><?= $success ?>
            <a href="login.php" class="alert-link ms-2 fw-bold">
                Go to Login →
            </a>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <!-- PERSONAL INFORMATION -->
        <div class="section-title">
            <i class="fas fa-user me-2"></i>Personal Information
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control"
                       placeholder="e.g. Ahmed Raza"
                       value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control"
                       placeholder="e.g. ahmed@email.com"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control"
                       placeholder="e.g. 03001234567"
                       value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label>Date of Birth</label>
                <input type="date" name="dob" class="form-control"
                       value="<?= isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : '' ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label>Gender</label>
                <select name="gender" class="form-select" required>
                    <option value="" disabled
                        <?= !isset($_POST['gender']) ? 'selected' : '' ?>>
                        Select gender
                    </option>
                    <option value="Male"
                        <?= (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : '' ?>>
                        Male
                    </option>
                    <option value="Female"
                        <?= (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : '' ?>>
                        Female
                    </option>
                    <option value="Other"
                        <?= (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : '' ?>>
                        Other
                    </option>
                </select>
            </div>
        </div>

        <!-- ACADEMIC INFORMATION -->
        <div class="section-title">
            <i class="fas fa-book me-2"></i>Academic Information
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label>Registration Number</label>
                <input type="text" name="reg_no" class="form-control"
                       placeholder="e.g. CS-2024-001"
                       value="<?= isset($_POST['reg_no']) ? htmlspecialchars($_POST['reg_no']) : '' ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label>Department / Course</label>
                <select name="department" class="form-select" required>
                    <option value="" disabled
                        <?= !isset($_POST['department']) ? 'selected' : '' ?>>
                        Select department
                    </option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>"
                            <?= (isset($_POST['department']) && $_POST['department'] == $dept) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label>Semester</label>
                <select name="semester" class="form-select" required>
                    <option value="" disabled
                        <?= !isset($_POST['semester']) ? 'selected' : '' ?>>
                        Select semester
                    </option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>"
                            <?= (isset($_POST['semester']) && (int)$_POST['semester'] == $i) ? 'selected' : '' ?>>
                            Semester <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- LOGIN CREDENTIALS -->
        <div class="section-title">
            <i class="fas fa-key me-2"></i>Login Credentials
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <label>Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-at"></i>
                    </span>
                    <input type="text" name="username" class="form-control"
                           placeholder="Choose a unique username"
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                           required>
                </div>
                <div class="password-hint">
                    Username must be unique across all students, teachers and admins.
                </div>
            </div>

            <div class="col-md-6">
                <label>Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control"
                           placeholder="Min 6 characters" required>
                </div>
                <div class="password-hint">At least 6 characters.</div>
            </div>

            <div class="col-md-6">
                <label>Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="confirm_password" class="form-control"
                           placeholder="Repeat your password" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-register">
            <i class="fas fa-user-plus me-2"></i>Register Now
        </button>

    </form>

    <div class="login-link">
        Already have an account?
        <a href="login.php">Login here</a>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>