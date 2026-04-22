<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($teacher_id == 0) {
    header("Location: dashboard.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM teachers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$teacher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$teacher) {
    header("Location: dashboard.php");
    exit();
}

// Subjects this teacher teaches
$subj_sql = "SELECT s.*, COUNT(DISTINCT e.student_id) AS student_count
             FROM subjects s
             LEFT JOIN enrollments e ON e.subject_id = s.id
             WHERE s.teacher_id = ?
             GROUP BY s.id
             ORDER BY s.semester, s.name";
$s_stmt = mysqli_prepare($conn, $subj_sql);
mysqli_stmt_bind_param($s_stmt, "i", $teacher_id);
mysqli_stmt_execute($s_stmt);
$subj_result = mysqli_stmt_get_result($s_stmt);
$subj_rows   = [];
while ($s = mysqli_fetch_assoc($subj_result)) {
    $subj_rows[] = $s;
}

// Timetable
$tt_sql = "SELECT t.day, t.time_start, t.time_end, s.name AS subject_name, s.semester
           FROM timetable t
           JOIN subjects s ON t.subject_id = s.id
           WHERE s.teacher_id = ?
           ORDER BY FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday'),
                    t.time_start";
$tt_stmt = mysqli_prepare($conn, $tt_sql);
mysqli_stmt_bind_param($tt_stmt, "i", $teacher_id);
mysqli_stmt_execute($tt_stmt);
$tt_result = mysqli_stmt_get_result($tt_stmt);
$tt_rows   = [];
while ($t = mysqli_fetch_assoc($tt_result)) {
    $tt_rows[] = $t;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Detail — EduLMS Admin</title>
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
        .sidebar a:hover { background:rgba(255,255,255,0.1); color:white; }
        .sidebar a i { font-size:16px; min-width:20px; }
        .sidebar-section {
            padding:10px 20px 5px; font-size:10px; font-weight:700;
            color:rgba(255,255,255,0.35); text-transform:uppercase;
            letter-spacing:1.5px; margin-top:10px;
        }
        .main-content { margin-left:220px; margin-top:60px; padding:30px; }
        .back-btn {
            display:inline-flex; align-items:center; gap:6px;
            color:#6c3483; text-decoration:none;
            font-size:14px; font-weight:600; margin-bottom:20px;
        }
        .back-btn:hover { color:#1a0533; }
        .profile-header {
            background:linear-gradient(135deg,#1a0533,#6c3483);
            border-radius:12px; padding:25px 30px; color:white;
            margin-bottom:25px; display:flex;
            align-items:center; gap:20px; flex-wrap:wrap;
        }
        .avatar {
            width:70px; height:70px; border-radius:50%;
            background:rgba(255,255,255,0.2);
            display:flex; align-items:center;
            justify-content:center; font-size:28px;
            flex-shrink:0;
        }
        .profile-header h4 { font-size:22px; font-weight:700; margin:0; }
        .profile-header p { margin:5px 0 0; opacity:0.85; font-size:14px; }
        .status-pill {
            padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700;
        }
        .pill-online  { background:#27AE60; color:white; }
        .pill-offline { background:rgba(255,255,255,0.2); color:white; }
        .info-card {
            background:white; border-radius:12px; padding:25px;
            box-shadow:0 2px 12px rgba(0,0,0,0.07); margin-bottom:20px;
        }
        .section-title {
            font-size:13px; font-weight:700; color:#6c3483;
            text-transform:uppercase; letter-spacing:1px;
            margin:0 0 18px; padding-bottom:8px;
            border-bottom:2px solid #f0eafb;
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
        .info-label i { color:#6c3483; margin-right:8px; }
        .info-value { color:#2C3E50; }
        .admin-table th {
            background:#1a0533; color:white;
            font-size:13px; padding:12px 15px;
        }
        .admin-table td {
            padding:12px 15px; font-size:14px;
            vertical-align:middle; border-bottom:1px solid #f0f4f8;
        }
        .admin-table tr:last-child td { border-bottom:none; }
    </style>
</head>
<body>

<div class="top-navbar">
    <div class="brand"><i class="fas fa-graduation-cap"></i>EduLMS Admin</div>
    <div style="font-size:14px; color:rgba(255,255,255,0.85);">
        Teacher Detail
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
    <a href="add_teacher.php">
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

    <!-- PROFILE HEADER -->
    <div class="profile-header">
        <div class="avatar"><i class="fas fa-user-tie"></i></div>
        <div>
            <h4><?= htmlspecialchars($teacher['full_name']) ?></h4>
            <p>
                <i class="fas fa-building me-1"></i>
                <?= htmlspecialchars($teacher['department']) ?>
                &nbsp;|&nbsp;
                <i class="fas fa-book me-1"></i>
                <?= count($subj_rows) ?> Subjects
            </p>
            <span class="status-pill mt-2 d-inline-block
                <?= $teacher['is_online'] ? 'pill-online' : 'pill-offline' ?>">
                <i class="fas fa-circle me-1" style="font-size:8px;"></i>
                <?= $teacher['is_online'] ? 'Currently Online' : 'Offline' ?>
            </span>
        </div>
    </div>

    <!-- TEACHER INFO -->
    <div class="info-card">
        <div class="section-title">
            <i class="fas fa-id-card me-2"></i>Teacher Information
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-user"></i>Full Name</div>
                    <div class="info-value">
                        <?= htmlspecialchars($teacher['full_name']) ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-envelope"></i>Email</div>
                    <div class="info-value">
                        <?= htmlspecialchars($teacher['email']) ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-phone"></i>Phone</div>
                    <div class="info-value">
                        <?= htmlspecialchars($teacher['phone']) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-at"></i>Username</div>
                    <div class="info-value">
                        <?= htmlspecialchars($teacher['username']) ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-building"></i>Department</div>
                    <div class="info-value">
                        <?= htmlspecialchars($teacher['department']) ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-calendar"></i>Joined</div>
                    <div class="info-value">
                        <?= date('d M Y', strtotime($teacher['created_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUBJECTS -->
    <div class="info-card">
        <div class="section-title">
            <i class="fas fa-book me-2"></i>Subjects Teaching
        </div>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>Semester</th>
                        <th>Students Enrolled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subj_rows as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td>
                                <span style="background:#f0eafb; color:#6c3483;
                                             border-radius:20px; padding:3px 10px;
                                             font-size:12px; font-weight:600;">
                                    Sem <?= $s['semester'] ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-users me-1" style="color:#27AE60;"></i>
                                <?= $s['student_count'] ?> students
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TIMETABLE -->
    <div class="info-card">
        <div class="section-title">
            <i class="fas fa-calendar-alt me-2"></i>Timetable
        </div>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Semester</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tt_rows as $t): ?>
                        <tr>
                            <td>
                                <strong><?= $t['day'] ?></strong>
                            </td>
                            <td style="color:#6c3483; font-weight:600;">
                                <?= date('h:i A', strtotime($t['time_start'])) ?>
                                — <?= date('h:i A', strtotime($t['time_end'])) ?>
                            </td>
                            <td><?= htmlspecialchars($t['subject_name']) ?></td>
                            <td>Sem <?= $t['semester'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>