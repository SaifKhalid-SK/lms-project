<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id   = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'];

// Get ALL subjects this teacher teaches
$sql = "SELECT s.id, s.name, s.department, s.semester,
               COUNT(DISTINCT e.student_id) AS student_count
        FROM subjects s
        LEFT JOIN enrollments e ON e.subject_id = s.id
        WHERE s.teacher_id = ?
        GROUP BY s.id
        ORDER BY s.semester ASC, s.name ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subjects = [];
while ($s = mysqli_fetch_assoc($result)) {
    $subjects[] = $s;
}

// Group by semester for display
$by_semester = [];
foreach ($subjects as $s) {
    $by_semester[$s['semester']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Subjects — EduLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }
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
        .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white; padding: 6px 16px;
            border-radius: 20px; font-size: 13px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }
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
        .main-content {
            margin-left: 60px;
            margin-top: 60px;
            padding: 30px;
        }
        .page-heading h4 {
            color: #2C3E50;
            font-weight: 700;
            font-size: 22px;
            margin: 0 0 5px;
        }
        .page-heading p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0 0 25px;
        }
        .semester-heading {
            font-size: 13px;
            font-weight: 700;
            color: #2980b9;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 25px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #eaf4fb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .subject-card {
            background: white;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border-left: 4px solid #2980b9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .subject-name {
            font-size: 15px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 4px;
        }
        .subject-meta {
            font-size: 13px;
            color: #7f8c8d;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .subject-meta i { color: #2980b9; margin-right: 4px; }
        .btn-manage {
            background: linear-gradient(135deg, #1a252f, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        .btn-manage:hover { opacity: 0.9; color: white; }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2980b9;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .back-btn:hover { color: #1a252f; }
        .total-badge {
            background: #eaf4fb;
            color: #2980b9;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="top-navbar">
    <div class="brand">
        <i class="fas fa-graduation-cap"></i>EduLMS
    </div>
    <div style="font-size:14px; color:rgba(255,255,255,0.85);">
        <i class="fas fa-user-tie me-1"></i>
        <?= htmlspecialchars($teacher_name) ?>
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="dashboard.php">
        <i class="fas fa-th-large"></i>
        <span class="sidebar-label">Dashboard</span>
    </a>
    <a href="all_subjects.php" class="active">
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

    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="page-heading">
        <h4>
            <i class="fas fa-book me-2" style="color:#2980b9;"></i>
            All My Subjects
            <span class="total-badge ms-2"><?= count($subjects) ?> total</span>
        </h4>
        <p>All subjects assigned to you across all semesters.</p>
    </div>

    <?php if (empty($subjects)): ?>
        <div style="text-align:center; padding:60px; color:#95a5a6;">
            <i class="fas fa-book-open fa-3x mb-3 d-block"></i>
            No subjects assigned yet.
        </div>
    <?php else: ?>
        <?php foreach ($by_semester as $semester => $sem_subjects): ?>
            <div class="semester-heading">
                <i class="fas fa-layer-group"></i>
                Semester <?= $semester ?>
                <span class="total-badge">
                    <?= count($sem_subjects) ?> subjects
                </span>
            </div>
            <div class="d-flex flex-column gap-3 mb-2">
                <?php foreach ($sem_subjects as $s): ?>
                    <div class="subject-card">
                        <div>
                            <div class="subject-name">
                                <?= htmlspecialchars($s['name']) ?>
                            </div>
                            <div class="subject-meta">
                                <span>
                                    <i class="fas fa-building"></i>
                                    <?= htmlspecialchars($s['department']) ?>
                                </span>
                                <span>
                                    <i class="fas fa-users"></i>
                                    <?= $s['student_count'] ?> students
                                </span>
                            </div>
                        </div>
                        <a href="subject_manage.php?id=<?= $s['id'] ?>"
                           class="btn-manage">
                            <i class="fas fa-cog me-1"></i>Manage
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>