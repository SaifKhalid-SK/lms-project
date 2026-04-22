<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id   = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'];

// Get today's day name e.g. "Monday"
$today = date('l');

// Get today's classes for this teacher
$sql = "SELECT s.id, s.name, s.department, s.semester,
               t.time_start, t.time_end
        FROM subjects s
        JOIN timetable t ON t.subject_id = s.id
        WHERE s.teacher_id = ? AND t.day = ?
        ORDER BY t.time_start";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "is", $teacher_id, $today);
mysqli_stmt_execute($stmt);
$today_classes = mysqli_stmt_get_result($stmt);
$classes = [];
while ($c = mysqli_fetch_assoc($today_classes)) {
    $classes[] = $c;
}

// Get ALL subjects this teacher teaches (for all subjects view)

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard — EduLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        /* ── NAVBAR ── */
        .top-navbar {
            background: linear-gradient(135deg, #1a252f, #2980b9);
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
        .top-navbar .brand { font-size: 20px; font-weight: 700; }
        .top-navbar .brand i { margin-right: 8px; }
        .top-navbar .teacher-info {
            font-size: 14px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .role-badge {
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed;
            top: 60px; left: 0;
            height: calc(100vh - 60px);
            width: 60px;
            background: #1a252f;
            overflow: hidden;
            transition: width 0.3s ease;
            z-index: 999;
            padding-top: 20px;
        }
        .sidebar:hover { width: 220px; }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar a i { font-size: 18px; min-width: 24px; margin-right: 14px; }
        .sidebar-label { opacity: 0; transition: opacity 0.2s; }
        .sidebar:hover .sidebar-label { opacity: 1; }

        /* ── MAIN ── */
        .main-content {
            margin-left: 60px;
            margin-top: 60px;
            padding: 30px;
        }

        /* ── WELCOME BANNER ── */
        .welcome-banner {
            background: linear-gradient(135deg, #1a252f, #2980b9);
            border-radius: 12px;
            padding: 25px 30px;
            color: white;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .welcome-banner h4 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        .welcome-banner p {
            margin: 5px 0 0;
            opacity: 0.85;
            font-size: 14px;
        }
        .today-badge {
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 10px 18px;
            text-align: center;
        }
        .today-badge .day-name {
            font-size: 18px;
            font-weight: 700;
        }
        .today-badge .date-str {
            font-size: 12px;
            opacity: 0.85;
        }

        /* ── SECTION HEADING ── */
        .section-heading {
            font-size: 16px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-heading i { color: #2980b9; }

        /* ── TODAY CLASSES ── */
        .class-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #2980b9;
        }
        .class-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .class-card .subject-name {
            font-size: 16px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 4px;
        }
        .class-card .class-meta {
            font-size: 13px;
            color: #7f8c8d;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .class-card .class-meta span i {
            color: #2980b9;
            margin-right: 4px;
        }
        .class-time {
            background: #eaf4fb;
            border-radius: 8px;
            padding: 8px 14px;
            text-align: center;
            font-weight: 700;
            color: #2980b9;
            font-size: 14px;
            white-space: nowrap;
        }
        .btn-manage {
            background: linear-gradient(135deg, #1a252f, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 9px 20px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        .btn-manage:hover { opacity: 0.9; color: white; }

        
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="top-navbar">
    <div class="brand">
        <i class="fas fa-graduation-cap"></i>EduLMS
    </div>
    <div class="teacher-info">
        <i class="fas fa-user-tie"></i>
        <?= htmlspecialchars($teacher_name) ?>
        <span class="role-badge">TEACHER</span>
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="dashboard.php" class="active">
        <i class="fas fa-th-large"></i>
        <span class="sidebar-label">Dashboard</span>
    </a>
    <a href="all_subjects.php">
        <i class="fas fa-book"></i>
        <span class="sidebar-label">All Subjects</span>
    </a>
    <a href="profile.php">
        <i class="fas fa-user-tie"></i>
        <span class="sidebar-label">My Profile</span>
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
        <div>
            <h4><i class="fas fa-chalkboard-teacher me-2"></i>
                Welcome, <?= htmlspecialchars($teacher_name) ?>
            </h4>
            <p>Here are your classes scheduled for today.</p>
        </div>
        <div class="today-badge">
            <div class="day-name"><?= date('l') ?></div>
            <div class="date-str"><?= date('d M Y') ?></div>
        </div>
    </div>

    <!-- TODAY'S CLASSES -->
    <div class="section-heading">
        <i class="fas fa-calendar-day"></i>
        Today's Classes
        <span style="background:#eaf4fb; color:#2980b9; border-radius:20px;
                     padding:2px 10px; font-size:12px;">
            <?= count($classes) ?> class<?= count($classes) != 1 ? 'es' : '' ?>
        </span>
    </div>

    <?php if (empty($classes)): ?>
        <div class="no-class">
            <i class="fas fa-coffee"></i>
            <h5>No classes today!</h5>
            <p>You have no classes scheduled for <?= $today ?>.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
        <?php foreach ($classes as $class): ?>
            <div class="class-card">
                <div>
                    <div class="subject-name">
                        <?= htmlspecialchars($class['name']) ?>
                    </div>
                    <div class="class-meta">
                        <span>
                            <i class="fas fa-building"></i>
                            <?= htmlspecialchars($class['department']) ?>
                        </span>
                        <span>
                            <i class="fas fa-layer-group"></i>
                            Semester <?= $class['semester'] ?>
                        </span>
                    </div>
                </div>
                <div class="class-time">
                    <i class="fas fa-clock me-1"></i>
                    <?= date('h:i A', strtotime($class['time_start'])) ?>
                    —
                    <?= date('h:i A', strtotime($class['time_end'])) ?>
                </div>
                <a href="subject_manage.php?id=<?= $class['id'] ?>" class="btn-manage">
                    <i class="fas fa-cog me-1"></i>Manage
                </a>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>