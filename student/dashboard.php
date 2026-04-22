<?php
session_start();
require '../db.php';

// Block access if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id   = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// Get all subjects this student is enrolled in
// Also get teacher name for each subject
$sql = "SELECT s.id, s.name, t.full_name AS teacher_name
        FROM enrollments e
        JOIN subjects s ON e.subject_id = s.id
        JOIN teachers t ON s.teacher_id = t.id
        WHERE e.student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$subjects = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — EduLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>

        * { box-sizing: border-box; }

        body {
            background: #f0f4f8;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        /* ── TOP NAVBAR ── */
        .top-navbar {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            padding: 0 25px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .top-navbar .brand {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .top-navbar .brand i {
            margin-right: 8px;
        }
        .top-navbar .welcome {
            font-size: 14px;
            opacity: 0.9;
        }
        .top-navbar .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .top-navbar .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            height: calc(100vh - 60px);
            width: 60px;               /* collapsed width */
            background: #2C3E50;
            overflow: hidden;
            transition: width 0.3s ease;
            z-index: 999;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.15);
        }
        .sidebar:hover {
            width: 220px;              /* expanded width on hover */
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.2s, color 0.2s;
            font-size: 14px;
            font-weight: 500;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar a i {
            font-size: 18px;
            min-width: 24px;
            margin-right: 14px;
        }
        .sidebar .sidebar-label {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .sidebar:hover .sidebar-label {
            opacity: 1;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            margin-left: 60px;
            margin-top: 60px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        /* ── PAGE HEADING ── */
        .page-heading {
            margin-bottom: 25px;
        }
        .page-heading h4 {
            color: #2C3E50;
            font-weight: 700;
            font-size: 22px;
            margin: 0;
        }
        .page-heading p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 4px 0 0;
        }

        /* ── SUBJECT CARDS ── */
        .subject-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .subject-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .subject-card .subject-name {
            font-size: 16px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 6px;
        }
        .subject-card .teacher-name {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        .subject-card .teacher-name i {
            color: #3498DB;
            margin-right: 5px;
        }
        .attendance-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        .progress {
            height: 8px;
            border-radius: 10px;
            background: #ecf0f1;
        }
        .btn-view {
            margin-top: 18px;
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 9px;
            font-size: 13px;
            font-weight: 600;
            width: 100%;
            text-decoration: none;
            text-align: center;
            display: block;
            transition: opacity 0.2s;
        }
        .btn-view:hover {
            opacity: 0.9;
            color: white;
        }
        .no-subjects {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        .no-subjects i {
            font-size: 50px;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<div class="top-navbar">
    <div class="brand">
        <i class="fas fa-graduation-cap"></i>EduLMS
    </div>
    <div class="welcome">
        <i class="fas fa-user-circle me-1"></i>
        Welcome, <?= htmlspecialchars($student_name) ?>
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="timetable.php">
        <i class="fas fa-calendar-alt"></i>
        <span class="sidebar-label">Timetable</span>
    </a>
    <a href="profile.php">
        <i class="fas fa-user"></i>
        <span class="sidebar-label">My Profile</span>
    </a>
    <a href="fees.php">
        <i class="fas fa-file-invoice-dollar"></i>
        <span class="sidebar-label">Fee Invoice</span>
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="page-heading">
        <h4><i class="fas fa-book-open me-2" style="color:#3498DB;"></i>My Subjects</h4>
        <p>Click on any subject to view attendance, grades and announcements.</p>
    </div>

    <div class="row g-4">
    <?php
    $has_subjects = false;

    while ($subject = mysqli_fetch_assoc($subjects)):
        $has_subjects  = true;
        $subject_id    = $subject['id'];

        // Count total classes for this student in this subject
        $total_sql  = "SELECT COUNT(*) AS total FROM attendance
                       WHERE student_id = ? AND subject_id = ?";
        $t_stmt     = mysqli_prepare($conn, $total_sql);
        mysqli_stmt_bind_param($t_stmt, "ii", $student_id, $subject_id);
        mysqli_stmt_execute($t_stmt);
        $total_res  = mysqli_stmt_get_result($t_stmt);
        $total_row  = mysqli_fetch_assoc($total_res);
        $total      = $total_row['total'];

        // Count present classes
        $present_sql = "SELECT COUNT(*) AS present FROM attendance
                        WHERE student_id = ? AND subject_id = ? AND status = 'Present'";
        $p_stmt      = mysqli_prepare($conn, $present_sql);
        mysqli_stmt_bind_param($p_stmt, "ii", $student_id, $subject_id);
        mysqli_stmt_execute($p_stmt);
        $present_res = mysqli_stmt_get_result($p_stmt);
        $present_row = mysqli_fetch_assoc($present_res);
        $present     = $present_row['present'];

        // Calculate percentage
        $percentage  = ($total > 0) ? round(($present / $total) * 100) : 0;

        // Set progress bar color based on percentage
        if ($percentage >= 75) {
            $bar_color = "success";   // green
        } elseif ($percentage >= 50) {
            $bar_color = "warning";   // yellow
        } else {
            $bar_color = "danger";    // red
        }
    ?>
        <div class="col-md-6 col-lg-3">
            <div class="subject-card">
                <div>
                    <div class="subject-name">
                        <?= htmlspecialchars($subject['name']) ?>
                    </div>
                    <div class="teacher-name">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <?= htmlspecialchars($subject['teacher_name']) ?>
                    </div>

                    <!-- Attendance Progress Bar -->
                    <div class="attendance-label">
                        <span>Attendance</span>
                        <span><?= $percentage ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-<?= $bar_color ?>"
                             role="progressbar"
                             style="width: <?= $percentage ?>%"
                             aria-valuenow="<?= $percentage ?>"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>

                    <?php if ($total == 0): ?>
                        <div style="font-size:11px; color:#95a5a6; margin-top:5px;">
                            No attendance records yet
                        </div>
                    <?php endif; ?>
                </div>

                <a href="subject.php?id=<?= $subject_id ?>" class="btn-view">
                    <i class="fas fa-arrow-right me-1"></i>View Subject
                </a>
            </div>
        </div>
    <?php endwhile; ?>

    <?php if (!$has_subjects): ?>
        <div class="col-12">
            <div class="no-subjects">
                <i class="fas fa-folder-open"></i>
                <h5>No subjects found</h5>
                <p>You are not enrolled in any subjects yet.</p>
            </div>
        </div>
    <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>